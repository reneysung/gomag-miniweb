<?php
// admin/pages/audit.php  ─  客戶資料健診（Super Admin）
// 把每個客戶的完整度視覺化，並提供「跳到該客戶設定頁」的快捷鍵
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '資料健診';
$currentPage = 'audit';
$db = getDB();

// ─── filter ────────────────────────────────────────────────
$filterScore = isset($_GET['max_score']) ? (int)$_GET['max_score'] : 99;
$filterField = trim($_GET['missing'] ?? '');
$filterCat   = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

// ─── 撈所有客戶 ────────────────────────────────────────────
$rows = $db->query("
SELECT
  cl.id, cl.subdomain, cl.brand_name, cl.tagline, cl.industry,
  cl.category_id, cl.phone, cl.email, cl.address, cl.about_text,
  cl.hero_image_path, cl.business_hours, cl.google_place_id,
  cl.legacy_store_id, cl.has_minisite,
  c.name AS cat_name, c.icon AS cat_icon,
  (SELECT COUNT(*) FROM services      WHERE client_id=cl.id) AS svc_n,
  (SELECT COUNT(*) FROM testimonials WHERE client_id=cl.id) AS testi_n,
  (SELECT COUNT(*) FROM cases         WHERE client_id=cl.id) AS case_n,
  (SELECT COUNT(*) FROM store_blocks WHERE client_id=cl.id AND is_active=1) AS block_n
FROM clients cl
LEFT JOIN categories c ON c.id = cl.category_id
WHERE cl.is_active = 1
ORDER BY cl.id
")->fetchAll();

$categories = $db->query("SELECT id, name, icon FROM categories WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();

// ─── 評分 ──────────────────────────────────────────────────
$fields = [
    'tagline'         => ['標語',  false],
    'industry'        => ['業種',  false],
    'category_id'     => ['分類',  true],
    'phone'           => ['電話',  true],
    'email'           => ['Email', false],
    'address'         => ['地址',  true],
    'about_text'      => ['關於',  false],
    'hero_image_path' => ['Hero圖', false],
    'business_hours'  => ['時段',  false],
    'google_place_id' => ['G Place', false],
    'brand_name'      => ['品牌',  true],
];

function score(array $r, array $fields): array {
    $score = 0; $missing = [];
    foreach ($fields as $f => [$label, $req]) {
        if ($f === 'category_id') {
            if (!empty($r[$f])) $score++;
            else $missing[$f] = $label;
        } else {
            if (!empty($r[$f])) $score++;
            else $missing[$f] = $label;
        }
    }
    return ['score' => $score, 'max' => count($fields), 'missing' => $missing];
}

// ─── 分布計算 + filter ─────────────────────────────────────
$bucket = []; $missingCount = array_fill_keys(array_keys($fields), 0);
$rowsScored = [];
foreach ($rows as $r) {
    $s = score($r, $fields);
    $r['_score']   = $s['score'];
    $r['_missing'] = $s['missing'];
    $bucket[$s['score']] = ($bucket[$s['score']] ?? 0) + 1;
    foreach ($s['missing'] as $f => $_) $missingCount[$f]++;
    $rowsScored[] = $r;
}
ksort($bucket);

// 套 filter
$filtered = array_filter($rowsScored, function($r) use ($filterScore, $filterField, $filterCat) {
    if ($r['_score'] > $filterScore) return false;
    if ($filterField && !isset($r['_missing'][$filterField])) return false;
    if ($filterCat && (int)$r['category_id'] !== $filterCat) return false;
    return true;
});

// 排序（缺最多在最前）
usort($filtered, fn($a, $b) => $a['_score'] <=> $b['_score']);

$total = count($rows);
$shown = count($filtered);

require_once __DIR__ . '/../includes/layout_head.php';
?>

<style>
  .audit-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; margin-bottom:20px; }
  .audit-stat { background:#fff; border:1px solid var(--border); border-radius:10px; padding:14px; text-align:center; }
  .audit-stat .n { font-size:1.6rem; font-weight:900; color:var(--primary); line-height:1; margin-bottom:4px; }
  .audit-stat .l { font-size:.7rem; color:var(--muted); letter-spacing:.05em; text-transform:uppercase; }
  .score-bar { display:flex; height:32px; border-radius:6px; overflow:hidden; border:1px solid var(--border); margin-bottom:16px; }
  .score-bar .seg { display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:700; color:#fff; transition:filter .2s; }
  .score-bar .seg:hover { filter:brightness(1.15); }
  .seg-low  { background:#e74c3c; } .seg-mid  { background:#f39c12; }
  .seg-good { background:#27ae60; } .seg-full { background:#16a085; }
  .field-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; }
  .field-chip { background:#fff; border:1px solid var(--border); border-radius:18px; padding:5px 12px; font-size:.78rem; cursor:pointer; transition:all .15s; text-decoration:none; color:var(--text); }
  .field-chip:hover { border-color:var(--primary); color:var(--primary); }
  .field-chip.active { background:var(--primary); color:#fff; border-color:var(--primary); }
  .field-chip .n { background:rgba(0,0,0,.15); margin-left:6px; padding:0 6px; border-radius:10px; font-size:.7rem; }
  .field-chip.active .n { background:rgba(255,255,255,.25); }
  .audit-table { width:100%; background:#fff; border-collapse:collapse; }
  .audit-table th { background:#f8f9fa; padding:10px 8px; text-align:left; font-size:.75rem; font-weight:700; color:var(--muted); border-bottom:2px solid var(--border); position:sticky; top:0; }
  .audit-table td { padding:8px; border-bottom:1px solid var(--border); font-size:.85rem; vertical-align:middle; }
  .audit-table tr:hover { background:#f8f9fa; }
  .badge-score { display:inline-block; min-width:34px; padding:2px 8px; border-radius:12px; font-size:.78rem; font-weight:800; text-align:center; color:#fff; }
  .b-low { background:#e74c3c; } .b-mid { background:#f39c12; } .b-good { background:#27ae60; } .b-full { background:#16a085; }
  .miss-tag { display:inline-block; background:#fee; color:#c0392b; border:1px solid #fcc; border-radius:4px; padding:1px 6px; font-size:.7rem; margin:1px 2px; }
  .miss-tag.req { background:#c0392b; color:#fff; border-color:#a02020; font-weight:700; }
  .child-tag { display:inline-block; padding:1px 6px; font-size:.68rem; border-radius:3px; margin:0 1px; }
  .ct-have { background:#d5f5e0; color:#27ae60; }
  .ct-none { background:#eee; color:#999; }
  .filter-row { display:flex; gap:14px; align-items:center; flex-wrap:wrap; padding:14px; background:#fff; border:1px solid var(--border); border-radius:10px; margin-bottom:14px; }
  .filter-row select { padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:.85rem; }
</style>

<h1 style="margin-bottom:6px">🩺 客戶資料健診</h1>
<div style="font-size:.85rem; color:var(--muted); margin-bottom:18px">
  全 <?= $total ?> 客戶完整度視覺化，按缺欄位排序。每行可一鍵切換到該客戶並跳「基本資訊」頁編輯。
</div>

<!-- 上方統計卡 -->
<div class="audit-stats">
  <?php
  $lowN  = ($bucket[4] ?? 0) + ($bucket[5] ?? 0) + ($bucket[6] ?? 0);
  $midN  = ($bucket[7] ?? 0) + ($bucket[8] ?? 0);
  $goodN = ($bucket[9] ?? 0) + ($bucket[10] ?? 0);
  $fullN = $bucket[11] ?? 0;
  ?>
  <div class="audit-stat"><div class="n" style="color:#e74c3c"><?= $lowN ?></div><div class="l">嚴重缺漏 (≤6 分)</div></div>
  <div class="audit-stat"><div class="n" style="color:#f39c12"><?= $midN ?></div><div class="l">中等 (7-8 分)</div></div>
  <div class="audit-stat"><div class="n" style="color:#27ae60"><?= $goodN ?></div><div class="l">良好 (9-10 分)</div></div>
  <div class="audit-stat"><div class="n" style="color:#16a085"><?= $fullN ?></div><div class="l">滿分 (11 分)</div></div>
  <div class="audit-stat"><div class="n"><?= $total ?></div><div class="l">客戶總數</div></div>
</div>

<!-- 分數條 -->
<div class="score-bar" title="完整度分布">
  <?php
  $segs = [
    'seg-low'  => ['客戶資料嚴重缺漏 (4-6)',  $lowN],
    'seg-mid'  => ['中等 (7-8)',                  $midN],
    'seg-good' => ['良好 (9-10)',                 $goodN],
    'seg-full' => ['滿分 (11)',                   $fullN],
  ];
  foreach ($segs as $cls => [$lab, $n]) {
      if ($n === 0) continue;
      $pct = $n / $total * 100;
      echo "<div class='seg $cls' style='width:{$pct}%' title='{$lab}: {$n} 家'>{$n}</div>";
  }
  ?>
</div>

<!-- 欄位 chip filter -->
<div style="font-size:.75rem; color:var(--muted); margin-bottom:6px">點 chip 篩選缺該欄位的客戶：</div>
<div class="field-chips">
  <a class="field-chip <?= $filterField==='' && $filterScore==99 && !$filterCat ? 'active' : '' ?>" href="?">全部 <span class="n"><?= $total ?></span></a>
  <?php foreach ($missingCount as $f => $n):
      if ($n === 0) continue;
      $isActive = $filterField === $f;
  ?>
  <a class="field-chip <?= $isActive ? 'active' : '' ?>" href="?missing=<?= h($f) ?>">
    <?= h($fields[$f][0]) ?>
    <span class="n"><?= $n ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- 進階 filter -->
<form class="filter-row" method="GET">
  <label>分類：
    <select name="cat" onchange="this.form.submit()">
      <option value="0">全部</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $filterCat==$c['id']?'selected':'' ?>><?= h($c['icon']) ?> <?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>分數 ≤
    <select name="max_score" onchange="this.form.submit()">
      <?php foreach ([99,11,10,9,8,7,6,5,4] as $s): ?>
      <option value="<?= $s ?>" <?= $filterScore==$s?'selected':'' ?>><?= $s==99 ? '不限' : $s ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <input type="hidden" name="missing" value="<?= h($filterField) ?>">
  <span style="margin-left:auto; font-size:.85rem; color:var(--muted)">顯示 <strong><?= $shown ?></strong> / <?= $total ?> 客戶</span>
</form>

<!-- 表格 -->
<div style="background:#fff; border:1px solid var(--border); border-radius:10px; overflow:auto; max-height:75vh">
<table class="audit-table">
  <thead>
    <tr>
      <th style="width:60px">分數</th>
      <th style="width:50px">ID</th>
      <th>品牌</th>
      <th>分類</th>
      <th>缺欄位</th>
      <th style="width:130px">子表</th>
      <th style="width:160px">操作</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($filtered as $r):
      $sc = $r['_score'];
      $cls = $sc <= 6 ? 'b-low' : ($sc <= 8 ? 'b-mid' : ($sc < 11 ? 'b-good' : 'b-full'));
  ?>
    <tr>
      <td><span class="badge-score <?= $cls ?>"><?= $sc ?>/11</span></td>
      <td style="color:var(--muted); font-size:.78rem">#<?= $r['id'] ?></td>
      <td>
        <div style="font-weight:600"><?= h($r['brand_name']) ?></div>
        <div style="font-size:.72rem; color:var(--muted)">
          <code><?= h($r['subdomain']) ?></code>
          <?php if (!$r['has_minisite']): ?><span style="color:#999"> · 無 mini-site</span><?php endif; ?>
        </div>
      </td>
      <td style="font-size:.78rem">
        <?= $r['cat_icon'] ?? '' ?> <?= h($r['cat_name'] ?? '-') ?>
      </td>
      <td>
        <?php foreach ($r['_missing'] as $f => $lab):
            $isReq = !empty($fields[$f][1]);
        ?>
          <span class="miss-tag <?= $isReq ? 'req' : '' ?>"><?= h($lab) ?></span>
        <?php endforeach; ?>
      </td>
      <td style="font-size:.7rem">
        <span class="child-tag <?= $r['svc_n']>0?'ct-have':'ct-none' ?>">服 <?= $r['svc_n'] ?></span>
        <span class="child-tag <?= $r['testi_n']>0?'ct-have':'ct-none' ?>">評 <?= $r['testi_n'] ?></span>
        <span class="child-tag <?= $r['case_n']>0?'ct-have':'ct-none' ?>">案 <?= $r['case_n'] ?></span>
        <span class="child-tag <?= $r['block_n']>0?'ct-have':'ct-none' ?>">塊 <?= $r['block_n'] ?></span>
      </td>
      <td>
        <a href="<?= BASE_URL ?>/admin/pages/clients.php?switch=<?= $r['id'] ?>"
           class="btn btn-sm btn-primary"
           style="display:inline-flex; gap:4px; align-items:center"
           title="切換到此客戶並跳 Dashboard">
          ✏️ 切換編輯
        </a>
        <a href="<?= BASE_URL ?>/store.php?sub=<?= h($r['subdomain']) ?>"
           target="_blank" class="btn btn-sm btn-ghost" title="前台預覽">
          👁
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$filtered): ?>
    <tr><td colspan="7" style="text-align:center; padding:40px; color:var(--muted)">沒有符合篩選的客戶 🎉</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<div style="margin-top:14px; font-size:.75rem; color:var(--muted)">
  💡 <strong>使用流程</strong>：點上方欄位 chip 篩選 → 看缺漏的客戶 → 點「切換編輯」自動切到該客戶 → 在 Dashboard 點「⚙️ 基本資訊」即可填欄位。
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
