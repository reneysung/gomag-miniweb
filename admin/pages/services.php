<?php
// admin/pages/services.php  ─  服務項目 CRUD
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '服務項目管理';
$clientId  = requireClientId();
$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── DELETE ────────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    verifyCsrf();
    // 刪除前先撈圖片路徑，刪 row 後一起清檔
    $row = $db->prepare('SELECT image_path FROM services WHERE id=? AND client_id=?');
    $row->execute([$editId, $clientId]);
    $r = $row->fetch();
    if ($r && !empty($r['image_path'])) deleteImage($r['image_path']);
    $db->prepare('DELETE FROM services WHERE id=? AND client_id=?')->execute([$editId, $clientId]);
    setFlash('success', '服務項目已刪除。');
    redirect(BASE_URL . '/admin/pages/services.php');
}

// ── TOGGLE ACTIVE ─────────────────────────────────────────
if ($action === 'toggle' && $editId) {
    $db->prepare('UPDATE services SET is_active = 1-is_active WHERE id=? AND client_id=?')
       ->execute([$editId, $clientId]);
    redirect(BASE_URL . '/admin/pages/services.php');
}

// ── SAVE (add / edit) ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'client_id'   => $clientId,
        'name'        => trim($_POST['name'] ?? ''),
        'slug'        => trim($_POST['slug'] ?? ''),
        'short_desc'  => trim($_POST['short_desc'] ?? ''),
        'full_desc'   => trim($_POST['full_desc'] ?? ''),
        'price_text'  => trim($_POST['price_text'] ?? ''),
        'icon'        => trim($_POST['icon'] ?? ''),
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
    ];

    $saveId = (int)($_POST['edit_id'] ?? 0);

    // 撈舊圖路徑（替換 / 移除時要刪檔）
    $oldImagePath = '';
    if ($saveId) {
        $ex = $db->prepare('SELECT image_path FROM services WHERE id=? AND client_id=?');
        $ex->execute([$saveId, $clientId]);
        $oldImagePath = $ex->fetchColumn() ?: '';
    }

    // ── 圖片上傳處理 ──
    $uploadDir = dirname(__DIR__, 2) . '/uploads/services/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (in_array($_FILES['image']['type'], $allowed) && $_FILES['image']['size'] <= 5*1024*1024) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'svc-' . $clientId . '-' . time() . '-' . mt_rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $data['image_path'] = 'uploads/services/' . $filename;
                // 上傳成功 → 刪舊檔
                if ($oldImagePath && $oldImagePath !== $data['image_path']) {
                    deleteImage($oldImagePath);
                }
            }
        }
    }
    // 如果勾選刪除圖片 → 從硬碟刪
    if (!empty($_POST['remove_image'])) {
        if ($oldImagePath) deleteImage($oldImagePath);
        $data['image_path'] = '';
    }
    if ($saveId) {
        // UPDATE
        $sets = implode(',', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
        $db->prepare("UPDATE services SET $sets WHERE id=:id")->execute(array_merge($data, ['id'=>$saveId]));
        setFlash('success', '服務項目已更新！');
    } else {
        // INSERT
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $db->prepare("INSERT INTO services ($cols) VALUES ($vals)")->execute($data);
        setFlash('success', '服務項目已新增！');
    }
    redirect(BASE_URL . '/admin/pages/services.php');
}

// ── LOAD EDIT DATA ────────────────────────────────────────
$editData = null;
if (($action === 'edit' || $action === 'add') && $editId) {
    $stmt = $db->prepare('SELECT * FROM services WHERE id=? AND client_id=?');
    $stmt->execute([$editId, $clientId]);
    $editData = $stmt->fetch();
}

// ── LIST ──────────────────────────────────────────────────
$list = $db->prepare('SELECT * FROM services WHERE client_id=? ORDER BY sort_order,id');
$list->execute([$clientId]);
$list = $list->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/deprecated_banner.php';

// 表單（新增 / 編輯）
if ($action === 'add' || ($action === 'edit' && $editId)):
    $d = $editData ?? [];
?>
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h2><?= $editId ? '✏️ 編輯服務項目' : '➕ 新增服務項目' ?></h2>
    <a href="<?= BASE_URL ?>/admin/pages/services.php" class="btn btn-sm btn-ghost">← 返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="edit_id" value="<?= h($editId) ?>">

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>服務名稱 *</label>
          <input type="text" name="name" class="form-control" required
                 value="<?= h($d['name'] ?? '') ?>" placeholder="例：裝潢後細清">
        </div>
        <div class="form-group-admin">
          <label>價格文字</label>
          <input type="text" name="price_text" class="form-control"
                 value="<?= h($d['price_text'] ?? '') ?>" placeholder="NT$300/坪起">
        </div>
      </div>

      <div class="form-group-admin">
        <label>服務圖片（首頁卡片與詳情頁用，建議 800×600 以上）</label>
        <?php if (!empty($d['image_path'])): ?>
          <div style="margin-bottom:10px;display:flex;align-items:center;gap:12px">
            <img src="<?= BASE_URL . '/' . h($d['image_path']) ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #ddd" alt="">
            <label style="font-size:.85rem;color:#666;cursor:pointer;display:flex;align-items:center;gap:4px">
              <input type="checkbox" name="remove_image" value="1"> 移除目前圖片
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <div class="hint">支援 JPG / PNG / WebP，檔案上限 5MB。上傳後將顯示於首頁服務卡片和服務詳情頁。</div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>Icon（SVG 路徑或 Emoji）</label>
          <input type="text" name="icon" class="form-control"
                 value="<?= h($d['icon'] ?? '') ?>" placeholder="uploads/services/icon-xxx.svg 或 🏗️">
          <div class="hint">填入 SVG 檔案路徑或 Emoji。有上傳圖片時，首頁卡片會優先顯示圖片。</div>
        </div>
        <div class="form-group-admin">
          <label>排序（數字越小越前面）</label>
          <input type="number" name="sort_order" class="form-control"
                 value="<?= h($d['sort_order'] ?? 0) ?>">
        </div>
      </div>

      <div class="form-group-admin">
        <label>服務簡介（首頁卡片用，約 50 字）</label>
        <textarea name="short_desc" class="form-control" rows="2"><?= h($d['short_desc'] ?? '') ?></textarea>
      </div>

      <div class="form-group-admin">
        <label>完整說明（服務頁用，可多段）</label>
        <textarea name="full_desc" class="form-control" rows="5"><?= h($d['full_desc'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">💾 儲存</button>
        <a href="<?= BASE_URL ?>/admin/pages/services.php" class="btn btn-ghost">取消</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- 列表 -->
<div class="card">
  <div class="card-header">
    <h2>🛠️ 服務項目列表</h2>
    <a href="?action=add" class="btn btn-primary btn-sm">➕ 新增服務</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th width="60">圖片</th>
          <th>服務名稱</th>
          <th>Icon</th>
          <th>價格</th>
          <th width="60">排序</th>
          <th width="80">狀態</th>
          <th width="160">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $svc): ?>
        <tr>
          <td>
            <?php if (!empty($svc['image_path'])): ?>
              <img src="<?= BASE_URL . '/' . h($svc['image_path']) ?>"
                   style="width:56px;height:42px;object-fit:cover;border-radius:6px;border:1px solid #eee" alt="<?= h($svc['name']) ?>">
            <?php else:
              $iconVal = $svc['icon'] ?? '';
              if ($iconVal && preg_match('/\.(svg|jpg|jpeg|png|webp)$/i', $iconVal)): ?>
                <img src="<?= BASE_URL . '/' . h($iconVal) ?>"
                     style="width:44px;height:44px;" alt="<?= h($svc['name']) ?>">
              <?php else: ?>
                <div style="width:44px;height:44px;background:var(--bg);border-radius:8px;
                            display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                  <?= h($iconVal ?: '🛠') ?>
                </div>
              <?php endif;
            endif; ?>
          </td>
          <td><strong><?= h($svc['name']) ?></strong>
            <div style="font-size:.75rem;color:var(--muted);margin-top:2px;"><?= h(mb_strimwidth($svc['short_desc'] ?? '', 0, 40, '…')) ?></div>
          </td>
          <td style="font-size:.78rem;color:var(--muted);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($svc['icon'] ?? '') ?></td>
          <td><?= h($svc['price_text'] ?? '─') ?></td>
          <td style="text-align:center;"><?= $svc['sort_order'] ?></td>
          <td>
            <a href="?action=toggle&id=<?= $svc['id'] ?>"
               class="badge <?= $svc['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $svc['is_active'] ? '顯示' : '隱藏' ?>
            </a>
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <a href="?action=edit&id=<?= $svc['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
              <button onclick="confirmDeleteService(<?= $svc['id'] ?>,'<?= h(addslashes($svc['name'])) ?>')"
                      class="btn btn-sm btn-danger">🗑</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">
          尚無服務項目，<a href="?action=add">點此新增</a>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="POST" id="delete_form" style="display:none;">
  <input type="hidden" name="_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="delete_id" id="delete_id">
</form>

<script>
function confirmDeleteService(id, name) {
  if (confirm('確定要刪除服務「' + name + '」嗎？相關 FAQ 也會一併刪除。')) {
    window.location.href = '<?= BASE_URL ?>/admin/pages/services.php?action=delete&id=' + id
      + '&_token=<?= urlencode(csrfToken()) ?>';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
