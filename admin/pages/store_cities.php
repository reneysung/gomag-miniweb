<?php
// admin/pages/store_cities.php  ─  行銷頁城市變體列表（client_city_pages）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '城市行銷頁';
$db = getDB();
$clientId = getCurrentClientId();
if (!$clientId) {
    setFlash('error', '請先選擇要管理的客戶');
    redirect(BASE_URL . '/admin/index.php');
}

$client = $db->prepare("SELECT id, slug, subdomain, brand_name FROM clients WHERE id=?");
$client->execute([$clientId]);
$client = $client->fetch();
if (!$client) { redirect(BASE_URL . '/admin/index.php'); }

// Action: 切換啟用
if (isset($_GET['toggle'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['t'] ?? '')) { http_response_code(403); die('CSRF'); }
    $vid = (int)$_GET['toggle'];
    $db->prepare("UPDATE client_city_pages SET is_active = 1 - is_active WHERE id=? AND client_id=?")->execute([$vid, $clientId]);
    setFlash('success', '狀態已切換');
    redirect(BASE_URL . '/admin/pages/store_cities.php');
}

// Action: 刪除
if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['t'] ?? '')) { http_response_code(403); die('CSRF'); }
    $vid = (int)$_GET['delete'];
    // 順手刪掉專用 hero 圖（如果有設）
    $oldHero = $db->prepare("SELECT hero_image_path FROM client_city_pages WHERE id=? AND client_id=?");
    $oldHero->execute([$vid, $clientId]);
    $heroPath = $oldHero->fetchColumn();
    if ($heroPath) deleteImage($heroPath);
    $db->prepare("DELETE FROM client_city_pages WHERE id=? AND client_id=?")->execute([$vid, $clientId]);
    setFlash('success', '城市變體已刪除');
    redirect(BASE_URL . '/admin/pages/store_cities.php');
}

$rows = $db->prepare("SELECT * FROM client_city_pages WHERE client_id=? ORDER BY sort_order, id");
$rows->execute([$clientId]);
$rows = $rows->fetchAll();

$sub = $client['subdomain'] ?: $client['slug'];

require_once __DIR__ . '/../includes/layout_head.php';
$csrfToken = csrfToken();
?>

<div class="page-header">
  <div>
    <h1>🗺️ 城市行銷頁</h1>
    <p style="color:var(--muted); margin-top:6px;">
      為 <strong><?= h($client['brand_name']) ?></strong> 開啟多城市行銷頁。URL 格式：<code>/store/<?= h($sub) ?>/{city}</code>
    </p>
  </div>
  <div>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/admin/pages/store_city_edit.php">➕ 新增城市行銷頁</a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<!-- 說明卡 -->
<div class="card" style="background:linear-gradient(135deg,#FFF8F4,#FFE8DF); border-color:#FF5A36;">
  <div class="card-body" style="display:flex; align-items:center; gap:16px;">
    <div style="font-size:2.5rem;">🗺️</div>
    <div style="flex:1;">
      <h3 style="margin:0 0 4px;">什麼是城市行銷頁？</h3>
      <p style="margin:0; font-size:.85rem; color:var(--muted); line-height:1.55;">
        同一個客戶可開 N 個城市專屬行銷頁（例：<code>/store/<?= h($sub) ?>/taichung</code>、<code>/store/<?= h($sub) ?>/changhua</code>），
        各自有獨立的 SEO 標題、描述、Hero、延伸內容，案例自動依城市篩選。
        留空的欄位會 fallback 到主檔（行銷頁 SEO + 內容）。
      </p>
    </div>
    <div>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/store/<?= h($sub) ?>" target="_blank" rel="noopener">🔍 預覽主行銷頁</a>
    </div>
  </div>
</div>

<!-- 列表 -->
<div class="card" style="margin-top:20px;">
  <div class="card-header"><h2>📋 目前的城市行銷頁（<?= count($rows) ?>）</h2></div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($rows)): ?>
    <div style="padding:40px; text-align:center; color:var(--muted);">
      <div style="font-size:3rem; margin-bottom:10px;">🗺️</div>
      <p>尚未建立任何城市行銷頁。</p>
      <p style="font-size:.85rem;">如果客戶只服務單一地區，直接用主行銷頁即可，不必開城市變體。</p>
      <a class="btn btn-primary" style="margin-top:12px;" href="<?= BASE_URL ?>/admin/pages/store_city_edit.php">➕ 新增第一個城市行銷頁</a>
    </div>
    <?php else: ?>
    <table class="table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:var(--bg); border-bottom:1px solid var(--border);">
          <th style="padding:12px; text-align:center; width:60px;">#</th>
          <th style="padding:12px; text-align:left;">城市</th>
          <th style="padding:12px; text-align:left;">SEO 標題</th>
          <th style="padding:12px; text-align:center; width:100px;">案例篩選</th>
          <th style="padding:12px; text-align:center; width:90px;">狀態</th>
          <th style="padding:12px; text-align:right; width:300px;">操作</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
      <tr style="border-bottom:1px solid var(--border);<?= !$r['is_active'] ? ' opacity:.55' : '' ?>">
        <td style="padding:12px; text-align:center; font-family:monospace; color:var(--muted);"><?= $r['sort_order'] ?></td>
        <td style="padding:12px;">
          <div style="font-weight:700;"><?= h($r['city_label']) ?></div>
          <div style="font-size:.78rem; color:var(--muted);">
            <code style="background:var(--bg); padding:1px 6px; border-radius:3px;">/<?= h($r['city_slug']) ?></code>
          </div>
        </td>
        <td style="padding:12px;">
          <?php if (!empty($r['store_meta_title'])): ?>
            <div style="font-size:.85rem;"><?= h($r['store_meta_title']) ?></div>
          <?php else: ?>
            <span style="color:var(--muted); font-size:.8rem; font-style:italic;">（fallback 主檔）</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:center;">
          <?php if ($r['filter_cases_by_region']): ?>
            <span style="background:#E8F5EE; color:#048A50; padding:3px 8px; border-radius:100px; font-size:.7rem; font-weight:700;">依城市</span>
          <?php else: ?>
            <span style="color:var(--muted); font-size:.75rem;">全部</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:center;">
          <?php if ($r['is_active']): ?>
            <span style="background:#E8F5EE; color:#048A50; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">啟用</span>
          <?php else: ?>
            <span style="background:#fce; color:#a44; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">停用</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:right; white-space:nowrap;">
          <a href="<?= BASE_URL ?>/store/<?= h($sub) ?>/<?= h($r['city_slug']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost" title="預覽">🌐</a>
          <a href="?toggle=<?= $r['id'] ?>&t=<?= h($csrfToken) ?>" class="btn btn-sm btn-ghost" title="切換啟用">
            <?= $r['is_active'] ? '⏸' : '▶' ?>
          </a>
          <a href="<?= BASE_URL ?>/admin/pages/store_city_edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">編輯</a>
          <a href="?delete=<?= $r['id'] ?>&t=<?= h($csrfToken) ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('確定刪除『<?= h($r['city_label']) ?>』城市行銷頁？此動作不可復原（會一併移除此城市專用的 hero 圖）。')">刪除</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
