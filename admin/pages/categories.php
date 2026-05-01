<?php
// admin/pages/categories.php  ─  分類管理（Super Admin only）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

// 只有 super admin 能管分類
if (currentAdmin()['role'] !== 'super') {
    setFlash('danger', '權限不足（只有超級管理員可管理分類）');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '分類管理';
$db = getDB();

// 自動加上 banner_image_path 欄位（若不存在）
try {
    $db->exec("ALTER TABLE categories ADD COLUMN banner_image_path VARCHAR(255) DEFAULT NULL AFTER icon");
} catch (PDOException $e) {
    // 已存在就忽略
}

// 處理 banner 上傳的 helper
function handleCategoryBannerUpload(?string $existingPath): ?string {
    if (empty($_FILES['banner_image']['tmp_name'])) {
        return $existingPath; // 沒上傳新的，保留原本
    }

    $upDir = __DIR__ . '/../../uploads/categories';
    if (!is_dir($upDir)) @mkdir($upDir, 0755, true);

    $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        return $existingPath;
    }

    $fname = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest  = $upDir . '/' . $fname;
    if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $dest)) {
        return $existingPath;
    }

    // 刪掉舊的（如果有）
    if ($existingPath) {
        $oldFile = __DIR__ . '/../../' . $existingPath;
        if (file_exists($oldFile) && strpos($existingPath, 'uploads/categories/') === 0) {
            @unlink($oldFile);
        }
    }

    return 'uploads/categories/' . $fname;
}

// ─────────────────────────────────────────
// POST：新增 / 更新 / 刪除 / 移除 banner
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $bannerPath = handleCategoryBannerUpload(null);
            $stmt = $db->prepare("INSERT INTO categories (name, slug, icon, banner_image_path, description, sort_order, is_active) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['slug'] ?? ''))),
                trim($_POST['icon'] ?? ''),
                $bannerPath,
                trim($_POST['description'] ?? ''),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
            ]);
            setFlash('success', '✅ 分類新增成功');
        }
        elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            // 取得目前的 banner path
            $cur = $db->prepare("SELECT banner_image_path FROM categories WHERE id=?");
            $cur->execute([$id]);
            $existingPath = $cur->fetchColumn();

            $bannerPath = handleCategoryBannerUpload($existingPath);

            $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, icon=?, banner_image_path=?, description=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['slug'] ?? ''))),
                trim($_POST['icon'] ?? ''),
                $bannerPath,
                trim($_POST['description'] ?? ''),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                $id
            ]);
            setFlash('success', '✅ 分類已更新');
        }
        elseif ($action === 'remove_banner') {
            $id = (int)$_POST['id'];
            $cur = $db->prepare("SELECT banner_image_path FROM categories WHERE id=?");
            $cur->execute([$id]);
            $existingPath = $cur->fetchColumn();
            if ($existingPath && strpos($existingPath, 'uploads/categories/') === 0) {
                $oldFile = __DIR__ . '/../../' . $existingPath;
                if (file_exists($oldFile)) @unlink($oldFile);
            }
            $db->prepare("UPDATE categories SET banner_image_path=NULL WHERE id=?")->execute([$id]);
            setFlash('success', '🗑️ 分類 banner 已移除');
        }
        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            // 檢查是否有客戶在用
            $cnt = $db->prepare("SELECT COUNT(*) FROM clients WHERE category_id=?");
            $cnt->execute([$id]);
            $usedBy = $cnt->fetchColumn();
            if ($usedBy > 0) {
                setFlash('danger', "❌ 無法刪除：有 $usedBy 個客戶正在使用此分類");
            } else {
                // 刪掉 banner 檔
                $cur = $db->prepare("SELECT banner_image_path FROM categories WHERE id=?");
                $cur->execute([$id]);
                $bp = $cur->fetchColumn();
                if ($bp && strpos($bp, 'uploads/categories/') === 0) {
                    $f = __DIR__ . '/../../' . $bp;
                    if (file_exists($f)) @unlink($f);
                }
                $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
                setFlash('success', '✅ 分類已刪除');
            }
        }
    } catch (PDOException $e) {
        setFlash('danger', '操作失敗：' . $e->getMessage());
    }

    redirect(BASE_URL . '/admin/pages/categories.php' . ($action === 'update' ? '?edit=' . (int)$_POST['id'] : ''));
}

// ─────────────────────────────────────────
// GET：列表
// ─────────────────────────────────────────
$rows = $db->query("
    SELECT c.*, COUNT(cl.id) AS client_count
    FROM categories c
    LEFT JOIN clients cl ON cl.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
")->fetchAll();

// 編輯模式
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;">

  <!-- 列表 -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <h2>📂 所有分類（<?= count($rows) ?>）</h2>
    </div>
    <div class="card-body" style="padding:0;">
      <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:var(--bg);">
            <th style="padding:10px;text-align:left;width:80px;">Banner</th>
            <th style="padding:10px;text-align:left;width:50px;">圖示</th>
            <th style="padding:10px;text-align:left;">名稱 / Slug</th>
            <th style="padding:10px;text-align:center;width:70px;">客戶數</th>
            <th style="padding:10px;text-align:center;width:60px;">排序</th>
            <th style="padding:10px;text-align:center;width:70px;">狀態</th>
            <th style="padding:10px;text-align:right;width:140px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr style="border-top:1px solid var(--border);">
            <td style="padding:10px;">
              <?php if (!empty($r['banner_image_path'])): ?>
              <img src="<?= BASE_URL ?>/<?= h($r['banner_image_path']) ?>"
                   style="width:64px; height:36px; object-fit:cover; border-radius:4px; border:1px solid var(--border);">
              <?php else: ?>
              <div style="width:64px; height:36px; background:linear-gradient(135deg,#1c3d3d,#2a5252); border-radius:4px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.75rem;">無</div>
              <?php endif; ?>
            </td>
            <td style="padding:10px;font-size:1.4rem;text-align:center;"><?= h($r['icon']) ?></td>
            <td style="padding:10px;">
              <div style="font-weight:700;"><?= h($r['name']) ?></div>
              <div style="color:var(--muted);font-size:.8rem;"><code><?= h($r['slug']) ?></code></div>
              <?php if ($r['description']): ?>
              <div style="color:var(--muted);font-size:.75rem;margin-top:2px;"><?= h($r['description']) ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:10px;text-align:center;font-weight:700;"><?= $r['client_count'] ?></td>
            <td style="padding:10px;text-align:center;"><?= $r['sort_order'] ?></td>
            <td style="padding:10px;text-align:center;">
              <?php if ($r['is_active']): ?>
              <span style="color:#16a34a;font-weight:700;">●  啟用</span>
              <?php else: ?>
              <span style="color:#999;">○ 停用</span>
              <?php endif; ?>
            </td>
            <td style="padding:10px;text-align:right;">
              <a href="?edit=<?= $r['id'] ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:.85rem;">編輯</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('確定刪除「<?= h($r['name']) ?>」？');">
                <input type="hidden" name="_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-ghost" style="padding:4px 10px;font-size:.85rem;color:#c00;">刪除</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 新增 / 編輯 表單 -->
  <div>
    <div class="card" style="position:sticky;top:20px;">
      <div class="card-header">
        <h2><?= $editing ? '✏️ 編輯分類' : '➕ 新增分類' ?></h2>
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
          <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= $editing['id'] ?>">
          <?php endif; ?>

          <div class="form-group-admin">
            <label>分類名稱 *</label>
            <input type="text" name="name" class="form-control" required
                   placeholder="例：餐飲美食"
                   value="<?= h($editing['name'] ?? '') ?>">
          </div>

          <div class="form-group-admin">
            <label>Slug（網址 ID）*</label>
            <input type="text" name="slug" class="form-control" required
                   placeholder="food"
                   value="<?= h($editing['slug'] ?? '') ?>"
                   oninput="this.value=this.value.replace(/[^a-z0-9-]/g,'')">
            <div class="hint">英文小寫、數字、連字號</div>
          </div>

          <div class="form-group-admin">
            <label>圖示（emoji，沒上傳 banner 時顯示）</label>
            <input type="text" name="icon" class="form-control"
                   placeholder="🍽️"
                   value="<?= h($editing['icon'] ?? '') ?>">
          </div>

          <!-- 分類 Banner 上傳 -->
          <div class="form-group-admin" style="background:#fef9e7; border-left:3px solid #f39c12; padding:12px; border-radius:6px;">
            <label>🎴 分類頁 Banner（選填）</label>
            <?php if ($editing && !empty($editing['banner_image_path'])): ?>
            <div style="margin-bottom:8px;">
              <img src="<?= BASE_URL ?>/<?= h($editing['banner_image_path']) ?>"
                   style="width:100%; max-height:90px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">
              <button type="button" onclick="if(confirm('移除此 banner？')) { document.getElementById('rm-banner-form').submit(); }"
                      class="btn btn-ghost" style="margin-top:6px; font-size:.75rem; padding:4px 10px; color:#c00;">
                🗑️ 移除目前 banner
              </button>
            </div>
            <?php endif; ?>
            <input type="file" name="banner_image" class="form-control" accept="image/*">
            <div class="hint">建議 1920×600 / JPG 或 PNG / 不超過 2MB。沒上傳則使用 emoji + 漸層</div>
          </div>

          <div class="form-group-admin">
            <label>分類說明（可選）</label>
            <textarea name="description" class="form-control" rows="2"
                      placeholder="餐廳、咖啡廳、甜點店..."><?= h($editing['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group-admin">
            <label>排序（小的在前）</label>
            <input type="number" name="sort_order" class="form-control"
                   value="<?= $editing['sort_order'] ?? 100 ?>">
          </div>

          <div class="form-group-admin" style="background:var(--bg);padding:10px;border-radius:6px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="is_active" value="1"
                     <?= !empty($editing['is_active']) || !$editing ? 'checked' : '' ?>
                     style="width:18px;height:18px;">
              <span>啟用此分類</span>
            </label>
          </div>

          <div style="display:flex;gap:8px;margin-top:14px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">
              <?= $editing ? '💾 更新' : '➕ 新增' ?>
            </button>
            <?php if ($editing): ?>
            <a href="<?= BASE_URL ?>/admin/pages/categories.php" class="btn btn-ghost">取消</a>
            <?php endif; ?>
          </div>
        </form>

        <?php if ($editing && !empty($editing['banner_image_path'])): ?>
        <!-- 隱藏的移除 banner 表單 -->
        <form id="rm-banner-form" method="POST" style="display:none;">
          <input type="hidden" name="_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="remove_banner">
          <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
