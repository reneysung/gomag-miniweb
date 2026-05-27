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

// Action: 批次建立 skeleton（一次勾多城市，一鍵建 N 筆 client_city_pages skeleton）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_create') {
    verifyCsrf();
    $citiesPost = (array)($_POST['cities'] ?? []);
    $citiesRef  = $db->query("SELECT slug, name FROM cities")->fetchAll(PDO::FETCH_KEY_PAIR);
    $msStart = (int)($_POST['minisite_on'] ?? 0); // 是否一併開小官網覆寫(預設關)
    $maxSortStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM client_city_pages WHERE client_id=?");
    $maxSortStmt->execute([$clientId]);
    $maxSort = (int)$maxSortStmt->fetchColumn();
    $existStmt = $db->prepare("SELECT city_slug FROM client_city_pages WHERE client_id=?");
    $existStmt->execute([$clientId]);
    $existMap = array_flip($existStmt->fetchAll(PDO::FETCH_COLUMN));
    $ins = $db->prepare("INSERT INTO client_city_pages
        (client_id, city_slug, city_label, sort_order, is_active, filter_cases_by_region, created_at)
        VALUES (?,?,?,?,1,1,NOW())");
    $added = 0; $skipped = 0;
    foreach ($citiesPost as $cs) {
        $cs = strtolower(trim($cs));
        if (!isset($citiesRef[$cs])) continue;
        if (isset($existMap[$cs])) { $skipped++; continue; }
        $maxSort += 10;
        $ins->execute([$clientId, $cs, $citiesRef[$cs], $maxSort]);
        $added++;
    }
    $msg = "✅ 批次建立 {$added} 個城市行銷頁 skeleton";
    if ($skipped) $msg .= "（{$skipped} 個已存在略過）";
    if ($added)   $msg .= "。請逐個進編輯頁填 SEO 標題／描述／內容。";
    setFlash($added ? 'success' : 'error', $msg);
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

// 批次建立卡片用：所有城市清單 + 已存在的 city_slug
$allCities = $db->query("SELECT slug, name FROM cities ORDER BY sort_order, slug")->fetchAll();
$existingSlugs = array_column($rows, 'city_slug');

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

<!-- ═══ 批次建立 skeleton（一鍵勾多城市）═══ -->
<?php $remainingCities = array_filter($allCities, fn($c) => !in_array($c['slug'], $existingSlugs, true)); ?>
<?php if ($remainingCities): ?>
<details class="card" style="margin-top:20px; border-left:4px solid #2563eb;">
  <summary style="cursor:pointer; padding:16px 20px; font-weight:800; font-size:1.05rem; color:#1e40af;">
    ⚡ 批次建立 skeleton（一鍵勾多個城市）
    <span style="font-size:.75rem; font-weight:500; color:var(--muted); margin-left:8px;">
      省去一個一個進「新增」頁填城市選單
    </span>
  </summary>
  <div class="card-body">
    <form method="POST" id="cv-bulk-form">
      <input type="hidden" name="_token" value="<?= h($csrfToken) ?>">
      <input type="hidden" name="action" value="bulk_create">
      <p style="color:var(--muted); font-size:.88rem; margin-bottom:14px;">
        勾選的城市會建立成 <strong>skeleton</strong>（is_active=1、filter_cases_by_region=1、其他覆寫欄位空）。
        建完後請逐個進「編輯」頁填 SEO 標題／描述／一頁式內容／小官網覆寫。
        <strong>已建立過的城市不在清單中</strong>。
      </p>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:10px;">
        <?php foreach ($remainingCities as $c): ?>
        <label style="display:flex; align-items:center; gap:8px; padding:10px 12px; background:#fff; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:all .15s;"
               onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='var(--border)'">
          <input type="checkbox" name="cities[]" value="<?= h($c['slug']) ?>" style="width:18px; height:18px; cursor:pointer;">
          <div>
            <div style="font-weight:700; font-size:.92rem;"><?= h($c['name']) ?></div>
            <div style="font-size:.7rem; color:var(--muted);"><code><?= h($c['slug']) ?></code></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:18px; display:flex; gap:10px; align-items:center;">
        <button type="submit" class="btn btn-primary" id="cv-bulk-btn" disabled>
          ⚡ 批次建立（<span id="cv-bulk-count">0</span> 個城市）
        </button>
        <button type="button" class="btn btn-ghost" onclick="document.querySelectorAll('#cv-bulk-form input[type=checkbox]').forEach(c=>{c.checked=true;c.dispatchEvent(new Event('change'))})">全選</button>
        <button type="button" class="btn btn-ghost" onclick="document.querySelectorAll('#cv-bulk-form input[type=checkbox]').forEach(c=>{c.checked=false;c.dispatchEvent(new Event('change'))})">全不選</button>
        <span style="margin-left:auto; font-size:.85rem; color:var(--muted);">
          建完後請逐個編輯填內容
        </span>
      </div>
    </form>
    <script>
      (function(){
        var form = document.getElementById('cv-bulk-form');
        var btn  = document.getElementById('cv-bulk-btn');
        var cnt  = document.getElementById('cv-bulk-count');
        if (!form) return;
        function refresh() {
          var n = form.querySelectorAll('input[type=checkbox]:checked').length;
          cnt.textContent = n;
          btn.disabled = n === 0;
        }
        form.addEventListener('change', refresh);
      })();
    </script>
  </div>
</details>
<?php endif; ?>

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
