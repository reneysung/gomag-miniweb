<?php
// admin/pages/cases.php  ─  施工案例 CRUD
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '施工案例管理';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── DELETE ────────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    $row = $db->prepare('SELECT before_image,after_image FROM cases WHERE id=? AND client_id=?');
    $row->execute([$editId, $clientId]);
    $r = $row->fetch();
    if ($r) {
        if ($r['before_image']) deleteImage($r['before_image']);
        if ($r['after_image'])  deleteImage($r['after_image']);
        $db->prepare('DELETE FROM cases WHERE id=? AND client_id=?')->execute([$editId, $clientId]);
        setFlash('success', '案例已刪除。');
    }
    redirect(BASE_URL . '/admin/pages/cases.php');
}

// ── TOGGLE ────────────────────────────────────────────────
if ($action === 'toggle' && $editId) {
    $db->prepare('UPDATE cases SET is_active=1-is_active WHERE id=? AND client_id=?')
       ->execute([$editId, $clientId]);
    redirect(BASE_URL . '/admin/pages/cases.php');
}
if ($action === 'featured' && $editId) {
    $db->prepare('UPDATE cases SET is_featured=1-is_featured WHERE id=? AND client_id=?')
       ->execute([$editId, $clientId]);
    redirect(BASE_URL . '/admin/pages/cases.php');
}

// ── SAVE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $sourceUrl = trim($_POST['source_url'] ?? '');
    $data = [
        'client_id'   => $clientId,
        'service_id'  => $_POST['service_id'] ? (int)$_POST['service_id'] : null,
        'title'       => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'location'    => trim($_POST['location'] ?? ''),
        'area_sqm'    => $_POST['area_sqm'] !== '' ? (float)$_POST['area_sqm'] : null,
        'source_url'  => $sourceUrl ?: null,
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
    ];

    // 自動抓取 OG Image
    if ($sourceUrl) {
        $ogImg = fetchOgImage($sourceUrl);
        if ($ogImg) $data['og_image'] = $ogImg;
    }

    $saveId = (int)($_POST['edit_id'] ?? 0);

    // 取得現有圖片
    $existBefore = $existAfter = '';
    if ($saveId) {
        $ex = $db->prepare('SELECT before_image,after_image FROM cases WHERE id=? AND client_id=?');
        $ex->execute([$saveId, $clientId]);
        $exRow = $ex->fetch();
        $existBefore = $exRow['before_image'] ?? '';
        $existAfter  = $exRow['after_image']  ?? '';
    }

    // 施工前圖片
    if (!empty($_FILES['before_image']['name'])) {
        $path = uploadImage($_FILES['before_image'], 'cases');
        $data['before_image'] = $path ?: $existBefore;
    } else {
        $data['before_image'] = $existBefore;
    }
    // 施工後圖片
    if (!empty($_FILES['after_image']['name'])) {
        $path = uploadImage($_FILES['after_image'], 'cases');
        $data['after_image'] = $path ?: $existAfter;
    } else {
        $data['after_image'] = $existAfter;
    }

    if ($saveId) {
        $sets = implode(',', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
        $db->prepare("UPDATE cases SET $sets WHERE id=:id")->execute(array_merge($data, ['id'=>$saveId]));
        setFlash('success', '案例已更新！');
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $db->prepare("INSERT INTO cases ($cols) VALUES ($vals)")->execute($data);
        setFlash('success', '案例已新增！');
    }
    redirect(BASE_URL . '/admin/pages/cases.php');
}

// 服務下拉清單
$services = $db->prepare('SELECT id,name FROM services WHERE client_id=? AND is_active=1 ORDER BY sort_order');
$services->execute([$clientId]);
$services = $services->fetchAll();

// EDIT data
$editData = null;
if (($action === 'edit') && $editId) {
    $stmt = $db->prepare('SELECT * FROM cases WHERE id=? AND client_id=?');
    $stmt->execute([$editId, $clientId]);
    $editData = $stmt->fetch();
}

// 列表
$list = $db->prepare('SELECT c.*,s.name AS svc_name FROM cases c LEFT JOIN services s ON c.service_id=s.id WHERE c.client_id=? ORDER BY c.sort_order,c.id DESC');
$list->execute([$clientId]);
$list = $list->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/deprecated_banner.php';

if ($action === 'add' || ($action === 'edit' && $editId)):
    $d = $editData ?? [];
?>
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h2><?= $editId ? '✏️ 編輯案例' : '➕ 新增案例' ?></h2>
    <a href="<?= BASE_URL ?>/admin/pages/cases.php" class="btn btn-sm btn-ghost">← 返回</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="edit_id" value="<?= h($editId) ?>">

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>案例標題 *</label>
          <input type="text" name="title" class="form-control" required
                 value="<?= h($d['title'] ?? '') ?>" placeholder="例：台南永康區 40坪裝潢後細清">
        </div>
        <div class="form-group-admin">
          <label>關聯服務（標籤）</label>
          <select name="service_id" class="form-control">
            <option value="">─ 不分類 ─</option>
            <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($d['service_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>施工地點</label>
          <input type="text" name="location" class="form-control"
                 value="<?= h($d['location'] ?? '') ?>" placeholder="例：台南永康區">
        </div>
        <div class="form-group-admin">
          <label>坪數 / 面積</label>
          <input type="number" name="area_sqm" class="form-control" step="0.1"
                 value="<?= h($d['area_sqm'] ?? '') ?>" placeholder="40">
        </div>
      </div>

      <div class="form-group-admin">
        <label>案例說明</label>
        <textarea name="description" class="form-control" rows="3"><?= h($d['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group-admin">
        <label>文章連結（痞客邦 / 部落格網址）</label>
        <input type="url" name="source_url" class="form-control"
               value="<?= h($d['source_url'] ?? '') ?>" placeholder="https://xusen.pixnet.net/blog/post/...">
        <div class="hint">貼上文章網址，系統會自動抓取縮圖（OG Image）顯示在前台</div>
        <?php if (!empty($d['og_image'])): ?>
        <div style="margin-top:8px;">
          <img src="<?= h($d['og_image']) ?>" style="max-width:240px;max-height:140px;border-radius:8px;border:1px solid var(--border);">
          <div class="hint" style="margin-top:4px;">目前抓到的縮圖</div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Before/After 圖片 -->
      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>施工前圖片（Before）</label>
          <?php if (!empty($d['before_image'])): ?>
            <img src="<?= BASE_URL . '/' . h($d['before_image']) ?>"
                 style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
          <?php endif; ?>
          <img id="before_preview" style="display:none;width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;" alt="">
          <input type="file" name="before_image" class="form-control" accept="image/*"
                 onchange="previewImage(this,'before_preview')">
        </div>
        <div class="form-group-admin">
          <label>施工後圖片（After）</label>
          <?php if (!empty($d['after_image'])): ?>
            <img src="<?= BASE_URL . '/' . h($d['after_image']) ?>"
                 style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
          <?php endif; ?>
          <img id="after_preview" style="display:none;width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;" alt="">
          <input type="file" name="after_image" class="form-control" accept="image/*"
                 onchange="previewImage(this,'after_preview')">
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>排序</label>
          <input type="number" name="sort_order" class="form-control" value="<?= h($d['sort_order'] ?? 0) ?>">
        </div>
        <div class="form-group-admin" style="display:flex;align-items:center;gap:8px;padding-top:24px;">
          <input type="checkbox" name="is_featured" id="is_featured" value="1"
                 <?= !empty($d['is_featured']) ? 'checked' : '' ?>>
          <label for="is_featured" style="cursor:pointer;margin-bottom:0;">⭐ 設為首頁精選案例</label>
        </div>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">💾 儲存</button>
        <a href="<?= BASE_URL ?>/admin/pages/cases.php" class="btn btn-ghost">取消</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- 列表 -->
<div class="card">
  <div class="card-header">
    <h2>📸 案例列表</h2>
    <a href="?action=add" class="btn btn-primary btn-sm">➕ 新增案例</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th width="120">Before / After</th>
          <th>案例名稱</th>
          <th>服務標籤</th>
          <th>地點</th>
          <th>精選</th>
          <th>狀態</th>
          <th width="140">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $c): ?>
        <tr>
          <td>
            <div style="display:flex;gap:4px;">
              <?php if ($c['before_image']): ?>
                <img src="<?= BASE_URL . '/' . h($c['before_image']) ?>"
                     style="width:52px;height:36px;object-fit:cover;border-radius:4px;border:2px solid #e74c3c;">
              <?php endif; ?>
              <?php if ($c['after_image']): ?>
                <img src="<?= BASE_URL . '/' . h($c['after_image']) ?>"
                     style="width:52px;height:36px;object-fit:cover;border-radius:4px;border:2px solid #27ae60;">
              <?php endif; ?>
            </div>
          </td>
          <td>
            <strong><?= h($c['title']) ?></strong>
            <?php if ($c['area_sqm']): ?>
              <span style="font-size:.75rem;color:var(--muted);"> · <?= $c['area_sqm'] ?>坪</span>
            <?php endif; ?>
          </td>
          <td><?= $c['svc_name'] ? '<span class="badge badge-muted">' . h($c['svc_name']) . '</span>' : '─' ?></td>
          <td style="font-size:.85rem;"><?= h($c['location'] ?? '─') ?></td>
          <td>
            <a href="?action=featured&id=<?= $c['id'] ?>"
               class="badge <?= $c['is_featured'] ? 'badge-warning' : 'badge-muted' ?>">
              <?= $c['is_featured'] ? '⭐ 精選' : '一般' ?>
            </a>
          </td>
          <td>
            <a href="?action=toggle&id=<?= $c['id'] ?>"
               class="badge <?= $c['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $c['is_active'] ? '顯示' : '隱藏' ?>
            </a>
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
              <button onclick="if(confirm('確定刪除「<?= h(addslashes($c['title'])) ?>」？'))
                location.href='?action=delete&id=<?= $c['id'] ?>'"
                class="btn btn-sm btn-danger">🗑</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">
          尚無案例，<a href="?action=add">點此新增</a>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
