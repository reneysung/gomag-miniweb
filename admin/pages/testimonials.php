<?php
// admin/pages/testimonials.php  ─  客戶評價 CRUD
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '客戶評價管理';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $editId) {
    $db->prepare('DELETE FROM testimonials WHERE id=? AND client_id=?')->execute([$editId, $clientId]);
    setFlash('success', '評價已刪除。');
    redirect(BASE_URL . '/admin/pages/testimonials.php');
}
if ($action === 'toggle' && $editId) {
    $db->prepare('UPDATE testimonials SET is_active=1-is_active WHERE id=? AND client_id=?')
       ->execute([$editId, $clientId]);
    redirect(BASE_URL . '/admin/pages/testimonials.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'client_id'     => $clientId,
        'service_id'    => $_POST['service_id'] ? (int)$_POST['service_id'] : null,
        'reviewer_name' => trim($_POST['reviewer_name'] ?? ''),
        'rating'        => max(1, min(5, (int)($_POST['rating'] ?? 5))),
        'content'       => trim($_POST['content'] ?? ''),
        'source'        => $_POST['source'] ?? 'google',
        'source_url'    => trim($_POST['source_url'] ?? '') ?: null,
        'sort_order'    => (int)($_POST['sort_order'] ?? 0),
    ];
    // Auto-fetch OG image from blog URL
    $srcUrl = $data['source_url'];
    if ($srcUrl) {
        $ogImg = fetchOgImage($srcUrl);
        if ($ogImg) $data['og_image'] = $ogImg;
    }
    $saveId = (int)($_POST['edit_id'] ?? 0);
    if ($saveId) {
        $sets = implode(',', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
        $db->prepare("UPDATE testimonials SET $sets WHERE id=:id")->execute(array_merge($data, ['id'=>$saveId]));
        setFlash('success', '評價已更新！');
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $db->prepare("INSERT INTO testimonials ($cols) VALUES ($vals)")->execute($data);
        setFlash('success', '評價已新增！');
    }
    redirect(BASE_URL . '/admin/pages/testimonials.php');
}

$services = $db->prepare('SELECT id,name FROM services WHERE client_id=? AND is_active=1 ORDER BY sort_order');
$services->execute([$clientId]);
$services = $services->fetchAll();

$editData = null;
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare('SELECT * FROM testimonials WHERE id=? AND client_id=?');
    $stmt->execute([$editId, $clientId]);
    $editData = $stmt->fetch();
}

$list = $db->prepare('SELECT t.*,s.name AS svc_name FROM testimonials t LEFT JOIN services s ON t.service_id=s.id WHERE t.client_id=? ORDER BY t.sort_order,t.id DESC');
$list->execute([$clientId]);
$list = $list->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';

if ($action === 'add' || ($action === 'edit' && $editId)):
    $d = $editData ?? [];
?>
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h2><?= $editId ? '✏️ 編輯評價' : '➕ 新增評價' ?></h2>
    <a href="<?= BASE_URL ?>/admin/pages/testimonials.php" class="btn btn-sm btn-ghost">← 返回</a>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="edit_id" value="<?= h($editId) ?>">

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>評論者名稱 *</label>
          <input type="text" name="reviewer_name" class="form-control" required
                 value="<?= h($d['reviewer_name'] ?? '') ?>" placeholder="王小姐">
          <div class="hint">例如：王小姐、陳先生、林太太</div>
        </div>
        <div class="form-group-admin">
          <label>評分</label>
          <div style="font-size:1.3rem;color:#f39c12;padding:8px 0;">★★★★★ <span style="font-size:.8rem;color:var(--muted)">（固定 5 星）</span></div>
          <input type="hidden" name="rating" value="5">
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>評價的服務</label>
          <select name="service_id" class="form-control">
            <option value="">─ 不指定 ─</option>
            <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($d['service_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-admin">
          <label>評價來源</label>
          <select name="source" class="form-control">
            <?php foreach (['google'=>'Google', 'fb'=>'Facebook', 'line'=>'LINE', 'other'=>'其他'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($d['source'] ?? 'google') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group-admin">
        <label>評價內容 *</label>
        <textarea name="content" class="form-control" rows="4" required><?= h($d['content'] ?? '') ?></textarea>
      </div>

      <div class="form-group-admin">
        <label>口碑文章連結（痞客邦 / 部落格網址）</label>
        <input type="url" name="source_url" class="form-control"
               value="<?= h($d['source_url'] ?? '') ?>" placeholder="https://xxx.pixnet.net/blog/post/...">
        <div class="hint">貼上口碑文章網址，系統會自動抓取縮圖（OG Image）顯示在前台</div>
        <?php if (!empty($d['og_image'])): ?>
        <div style="margin-top:8px;">
          <img src="<?= h($d['og_image']) ?>" style="max-width:240px;max-height:140px;border-radius:8px;border:1px solid var(--border);">
          <div class="hint" style="margin-top:4px;">目前抓到的縮圖</div>
        </div>
        <?php endif; ?>
      </div>

      <div class="form-group-admin">
        <label>排序</label>
        <input type="number" name="sort_order" class="form-control" value="<?= h($d['sort_order'] ?? 0) ?>" style="width:100px;">
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">💾 儲存</button>
        <a href="<?= BASE_URL ?>/admin/pages/testimonials.php" class="btn btn-ghost">取消</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h2>⭐ 評價列表</h2>
    <a href="?action=add" class="btn btn-primary btn-sm">➕ 新增評價</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>評論者</th><th>評分</th><th>內容摘要</th><th>服務</th><th>來源</th><th>狀態</th><th width="120">操作</th></tr>
      </thead>
      <tbody>
        <?php foreach ($list as $t): ?>
        <tr>
          <td><strong><?= h($t['reviewer_name']) ?></strong></td>
          <td style="color:#f39c12;font-size:1rem;"><?= renderStars((int)$t['rating']) ?></td>
          <td style="max-width:200px;font-size:.85rem;">
            <?= h(mb_strimwidth($t['content'], 0, 60, '…')) ?>
          </td>
          <td><?= $t['svc_name'] ? '<span class="badge badge-muted">' . h($t['svc_name']) . '</span>' : '─' ?></td>
          <td><span class="badge badge-muted"><?= h($t['source']) ?></span></td>
          <td>
            <a href="?action=toggle&id=<?= $t['id'] ?>"
               class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $t['is_active'] ? '顯示' : '隱藏' ?>
            </a>
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost">✏️</a>
              <button onclick="if(confirm('確定刪除？'))location.href='?action=delete&id=<?= $t['id'] ?>'"
                      class="btn btn-sm btn-danger">🗑</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">尚無評價</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
