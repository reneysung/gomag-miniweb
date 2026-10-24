<?php
// admin/pages/page_stats.php — 每個網頁的成效：Google 搜尋點擊（GSC）＋瀏覽數（GA4）＋店家頁按鈕點擊（click_events）
// 只有 super 看得到。GSC/GA4 由 includes/google_stats.php 抓、12 小時快取；按「立即更新」強制重抓。
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/google_stats.php';
requireLogin();

if ((currentAdmin()['role'] ?? '') !== 'super') { http_response_code(403); exit('只有系統管理員可以看這頁'); }

$pageTitle = '網頁成效';
$db = getDB();

$ranges = [7 => '近 7 天', 28 => '近 28 天', 90 => '近 90 天', 365 => '近 365 天'];
$days = isset($_REQUEST['d']) && array_key_exists((int)$_REQUEST['d'], $ranges) ? (int)$_REQUEST['d'] : 28;

$force = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { verifyCsrf(); $force = true; }

$sa   = gstatsServiceAccount();
$data = $sa ? gstatsGet($days, $force) : null;

// ── 合併 GSC + GA4 → 每頁一列 ──
$rows = [];
if ($data) {
    foreach (($data['gsc'] ?? []) ?: [] as $k => $g) $rows[$k]['gsc'] = $g;
    foreach (($data['ga4'] ?? []) ?: [] as $k => $v) $rows[$k]['ga4'] = $v;
}

// 店家頁：slug → 店名＋按鈕點擊（同一段日期）
$slugInfo = []; $hostInfo = [];
foreach ($db->query("SELECT id, slug, subdomain, minisite_domain, has_minisite, brand_name FROM clients") as $c) {
    $slugInfo[strtolower($c['slug'])] = $c;
    if (!empty($c['has_minisite'])) {
        $hostInfo[strtolower(($c['subdomain'] ?: $c['slug']) . '.' . ($c['minisite_domain'] ?: 'gomag.com.tw'))] = $c;
    }
}
$clickByKey = [];
if ($data) {
    $st = $db->prepare("SELECT source, page_path, COUNT(*) n FROM click_events WHERE created_at >= ? AND created_at < ? + INTERVAL 1 DAY GROUP BY source, page_path");
    // click_events 是 UTC；區間是台北日期，換成 UTC 邊界
    $st->execute([gmdate('Y-m-d H:i:s', strtotime($data['start'] . ' 00:00:00')), gmdate('Y-m-d H:i:s', strtotime($data['end'] . ' 00:00:00'))]);
    foreach ($st as $r) {
        // 店家頁存 pathname；小官網存 host+pathname → 用跟 GSC/GA4 同一套 gstatsKey() 對到每一列
        $pp = (string)$r['page_path'];
        if ($r['source'] === 'mini') { $sl = strpos($pp, '/'); $k = gstatsKey($sl === false ? $pp : substr($pp, 0, $sl), $sl === false ? '/' : substr($pp, $sl)); }
        else $k = gstatsKey('www.gomag.com.tw', $pp);
        $clickByKey[$k] = ($clickByKey[$k] ?? 0) + (int)$r['n'];
    }
}

$typeLabels = ['store' => '店家頁', 'city' => '城市・子服務', 'guide' => '攻略', 'mini' => '小官網', 'home' => '首頁・分類', 'other' => '其他'];
$list = [];
foreach ($rows as $k => $r) {
    $slash = strpos($k, '/');
    $host  = $slash === false ? $k : substr($k, 0, $slash);
    $path  = $slash === false ? '/' : substr($k, $slash);
    $isMain = in_array($host, ['www.gomag.com.tw', 'gomag.com.tw'], true);
    $label = ''; $cid = 0;
    if (!$isMain && preg_match('/\.(gomag|wmf)\.com\.tw$/', $host)) {
        $type = 'mini';
        if ($info = $hostInfo[$host] ?? null) { $label = $info['brand_name']; $cid = (int)$info['id']; }
    }
    elseif (preg_match('#^/store/([^/]+)#', $path, $m)) {
        $type = 'store';
        if ($info = $slugInfo[strtolower($m[1])] ?? null) { $label = $info['brand_name']; $cid = (int)$info['id']; }
    }
    elseif (str_starts_with($path, '/city')) $type = 'city';
    elseif (str_starts_with($path, '/guide')) $type = 'guide';
    elseif ($path === '/' || str_starts_with($path, '/category')) $type = 'home';
    else $type = 'other';

    $g = $r['gsc'] ?? null; $v = $r['ga4'] ?? null;
    $list[] = [
        'url' => 'https://' . $k, 'host' => $host, 'path' => $path, 'isMain' => $isMain, 'type' => $type, 'label' => $label,
        'c' => $g['c'] ?? 0, 'i' => $g['i'] ?? 0,
        'ctr' => ($g && $g['i'] > 0) ? $g['c'] / $g['i'] : null,
        'pos' => ($g && $g['i'] > 0) ? $g['pw'] / $g['i'] : null,
        'v' => $v['v'] ?? 0, 'u' => $v['u'] ?? 0,
        'btn' => ($cid && in_array($type, ['store', 'mini'], true)) ? ($clickByKey[$k] ?? 0) : null,
    ];
}
usort($list, fn($a, $b) => [$b['c'], $b['v']] <=> [$a['c'], $a['v']]);

$tot = ['c' => 0, 'i' => 0, 'pw' => 0.0, 'v' => 0];
foreach ($list as $x) { $tot['c'] += $x['c']; $tot['i'] += $x['i']; $tot['pw'] += ($x['pos'] ?? 0) * $x['i']; $tot['v'] += $x['v']; }
$typeCount = array_count_values(array_column($list, 'type'));
$MAX = 3000;
$shown = array_slice($list, 0, $MAX);

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="card" style="margin-bottom:20px; border-left:4px solid #2563eb;">
  <div class="card-header"><h2>📊 網頁成效</h2></div>
  <div class="card-body">

<?php if (!$sa): ?>
    <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:16px 18px; line-height:1.8;">
      <b>還沒接上 Google。</b>需要一個 Google Cloud「服務帳戶」金鑰，放在主機
      <code><?= h(gstatsKeyPath()) ?></code>（網站目錄外面）。步驟：
      <ol style="margin:8px 0 0 18px; padding:0;">
        <li>Google Cloud 專案啟用「Google Search Console API」和「Google Analytics Data API」</li>
        <li>建立服務帳戶 → 建立 JSON 金鑰（會下載一個 .json 檔）</li>
        <li>把服務帳戶的 email 加進 Search Console（設定 → 使用者和權限，「受限」即可）和 GA4（管理 → 資源存取權管理，「檢視者」）</li>
        <li>到<a href="<?= BASE_URL ?>/admin/pages/platform.php">平台設定</a>填「GA4 資源編號」</li>
      </ol>
    </div>
<?php else: ?>
    <p style="color:#666; margin:0 0 12px;">
      <b><?= h($data['start']) ?> ～ <?= h($data['end']) ?></b>（Google 搜尋資料約晚 2 天，所以區間到前天）。
      資料更新於 <?= h(date('m/d H:i', (int)$data['fetched_at'])) ?>，12 小時內自動沿用。
      服務帳戶：<code style="font-size:.8rem;"><?= h($sa['client_email']) ?></code>
    </p>

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
      <?php foreach ($ranges as $d => $lab): ?>
        <a href="?d=<?= $d ?>" style="padding:6px 14px; border-radius:999px; text-decoration:none; font-size:.88rem;
           <?= $d === $days ? 'background:#2563eb; color:#fff; font-weight:700;' : 'background:#f4f5f7; color:#444;' ?>"><?= $lab ?></a>
      <?php endforeach; ?>
      <form method="POST" style="margin-left:auto;">
        <input type="hidden" name="_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="d" value="<?= $days ?>">
        <button type="submit" style="padding:6px 14px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; cursor:pointer;">🔄 立即更新</button>
      </form>
    </div>

    <?php foreach ($data['errors'] as $e): ?>
      <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:10px; padding:10px 14px; margin-bottom:10px;">⚠️ <?= h($e) ?></div>
    <?php endforeach; ?>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
      <?php foreach ([
          ['Google 點擊', number_format($tot['c']), '#2563eb'],
          ['Google 曝光', number_format($tot['i']), '#333'],
          ['平均排名', $tot['i'] ? number_format($tot['pw'] / $tot['i'], 1) : '—', '#333'],
          ['瀏覽數', $data['ga4'] === null ? '—' : number_format($tot['v']), '#16a34a'],
          ['有數據的網頁', number_format(count($list)), '#333'],
      ] as [$lab, $val, $col]): ?>
      <div style="flex:1; min-width:110px; background:#f8fafc; border-radius:12px; padding:12px; text-align:center;">
        <div style="font-size:1.6rem; font-weight:900; color:<?= $col ?>;"><?= $val ?></div>
        <div style="font-size:.8rem; color:#888;"><?= $lab ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:10px;">
      <button type="button" class="ps-type" data-t="" style="padding:5px 12px; border-radius:999px; border:0; background:#1e293b; color:#fff; cursor:pointer;">全部 <?= count($list) ?></button>
      <?php foreach ($typeLabels as $t => $lab): if (empty($typeCount[$t])) continue; ?>
        <button type="button" class="ps-type" data-t="<?= $t ?>" style="padding:5px 12px; border-radius:999px; border:0; background:#f1f5f9; color:#334155; cursor:pointer;"><?= $lab ?> <?= $typeCount[$t] ?></button>
      <?php endforeach; ?>
      <input id="ps-q" type="search" placeholder="搜尋網址或店名…" style="margin-left:auto; padding:6px 10px; border:1px solid #cbd5e1; border-radius:8px; min-width:200px;">
    </div>

    <?php if (count($list) > $MAX): ?><p style="color:#888; font-size:.85rem;">網頁太多，只列前 <?= $MAX ?> 名（依 Google 點擊、瀏覽數排序）。</p><?php endif; ?>

    <div style="overflow-x:auto;">
      <table id="ps-table" style="width:100%; border-collapse:collapse; font-size:.86rem;">
        <thead>
          <tr style="background:#f4f5f7; text-align:left; cursor:pointer;">
            <th style="padding:9px;" data-k="0">網頁</th>
            <th style="padding:9px; text-align:right; white-space:nowrap;" data-k="1" data-n>Google 點擊</th>
            <th style="padding:9px; text-align:right;" data-k="2" data-n>曝光</th>
            <th style="padding:9px; text-align:right;" data-k="3" data-n>點閱率</th>
            <th style="padding:9px; text-align:right; white-space:nowrap;" data-k="4" data-n>平均排名</th>
            <th style="padding:9px; text-align:right;" data-k="5" data-n>瀏覽數</th>
            <th style="padding:9px; text-align:right;" data-k="6" data-n>使用者</th>
            <th style="padding:9px; text-align:right; white-space:nowrap;" data-k="7" data-n title="店家頁從 2026-10-19、小官網從 2026-10-24 開始記錄">按鈕點擊</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($shown as $x): ?>
          <tr data-t="<?= $x['type'] ?>" data-s="<?= h(mb_strtolower($x['path'] . ' ' . $x['host'] . ' ' . $x['label'])) ?>" style="border-bottom:1px solid #eee;">
            <td style="padding:8px 9px; max-width:420px;">
              <a href="<?= h($x['url']) ?>" target="_blank" rel="noopener" style="color:#1a1a1a; text-decoration:none; word-break:break-all;">
                <?php if ($x['label']): ?><b><?= h($x['label']) ?></b> <?php endif; ?>
                <span style="color:#64748b;"><?= h(($x['isMain'] ? '' : $x['host']) . $x['path']) ?></span>
              </a>
            </td>
            <td style="padding:8px 9px; text-align:right; font-weight:800; color:#2563eb;" data-v="<?= $x['c'] ?>"><?= $x['c'] ?: '·' ?></td>
            <td style="padding:8px 9px; text-align:right;" data-v="<?= $x['i'] ?>"><?= $x['i'] ? number_format($x['i']) : '·' ?></td>
            <td style="padding:8px 9px; text-align:right;" data-v="<?= $x['ctr'] ?? -1 ?>"><?= $x['ctr'] === null ? '·' : number_format($x['ctr'] * 100, 1) . '%' ?></td>
            <td style="padding:8px 9px; text-align:right;" data-v="<?= $x['pos'] ?? 9999 ?>"><?= $x['pos'] === null ? '·' : number_format($x['pos'], 1) ?></td>
            <td style="padding:8px 9px; text-align:right; font-weight:700; color:#16a34a;" data-v="<?= $x['v'] ?>"><?= $x['v'] ? number_format($x['v']) : '·' ?></td>
            <td style="padding:8px 9px; text-align:right;" data-v="<?= $x['u'] ?>"><?= $x['u'] ? number_format($x['u']) : '·' ?></td>
            <td style="padding:8px 9px; text-align:right;" data-v="<?= $x['btn'] ?? -1 ?>"><?= $x['btn'] === null ? '' : ($x['btn'] ?: '·') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php endif; ?>
  </div>
</div>

<script>
(function(){
  var tb=document.querySelector('#ps-table tbody'); if(!tb) return;
  var type='', q='';
  function apply(){ tb.querySelectorAll('tr').forEach(function(tr){
    var ok=(!type || tr.dataset.t===type) && (!q || tr.dataset.s.indexOf(q)>-1); tr.style.display=ok?'':'none'; }); }
  document.querySelectorAll('.ps-type').forEach(function(b){ b.addEventListener('click',function(){
    type=b.dataset.t; document.querySelectorAll('.ps-type').forEach(function(x){ x.style.background=x===b?'#1e293b':'#f1f5f9'; x.style.color=x===b?'#fff':'#334155'; }); apply(); }); });
  document.getElementById('ps-q').addEventListener('input',function(e){ q=e.target.value.trim().toLowerCase(); apply(); });
  var dir={};
  document.querySelectorAll('#ps-table th').forEach(function(th){ th.addEventListener('click',function(){
    var k=+th.dataset.k, num=th.hasAttribute('data-n'); dir[k]=!dir[k];
    var rows=[].slice.call(tb.rows);
    rows.sort(function(a,b){
      var x=a.cells[k], y=b.cells[k];
      var r=num ? (parseFloat(x.dataset.v)-parseFloat(y.dataset.v)) : x.textContent.localeCompare(y.textContent,'zh-Hant');
      // 平均排名：數字小＝好，第一次點就由好排到差
      if(k===4) r=-r;
      return dir[k] ? -r : r; });
    rows.forEach(function(r){ tb.appendChild(r); }); }); });
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
