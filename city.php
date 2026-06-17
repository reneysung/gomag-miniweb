<?php
// city.php  ─  縣市落地頁 /city/taichung
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();
$slug = strtolower(trim($_GET['slug'] ?? ''));

// 重複客戶 slug（已 301 到主檔）— 縣市頁所有店家清單都排除
$dupSkip = getDuplicateSkipSlugs();
$dupPh   = implode(',', array_fill(0, count($dupSkip), '?'));

// ─── slug → 縣市中文名 對映（唯一來源：cities 表，見 getCityMap()）──
$cityMap = getCityMap();
$cityNameToSlug = array_flip($cityMap);

// ─── 縣市專屬內文 — 從 cities 表讀取（取代寫死 PHP array）──
//    Migration 006 建表，後台 admin/pages/cities.php 可編輯
$cityIntros = [];
try {
    $rows = $db->query("SELECT slug, full_name, tagline, intro, highlights, areas, hero_image FROM cities WHERE is_active=1")->fetchAll();
    foreach ($rows as $r) {
        $cityIntros[$r['full_name']] = [
            'tagline'    => $r['tagline'] ?? '',
            'intro'      => $r['intro']   ?? '',
            'highlights' => json_decode($r['highlights'] ?? '[]', true) ?: [],
            'areas'      => json_decode($r['areas']      ?? '[]', true) ?: [],
            'hero_image' => $r['hero_image'] ?? '',
        ];
    }
} catch (\Throwable $e) {
    // cities 表還不存在時的 fallback（避免 city.php 直接爆）— 給最小可用版本
    $cityIntros = [];
}

// 共用：載入 gomag 設計系統
$extraCss = [BASE_URL . '/assets/css/gomag.css'];

// ═══════════════════════════════════════════════════════════
//   模式 A：未指定縣市 → 顯示所有縣市入口
// ═══════════════════════════════════════════════════════════
if (!$slug) {
    // 縣市店數：用已正規化的 clients.city_slug（與城市頁一致；修原 address regex 漏郵遞區號 bug）
    $stats = [];
    $dupPhA = implode(',', array_fill(0, count($dupSkip), '?'));
    $stStmt = $db->prepare("SELECT city_slug, COUNT(*) c FROM clients
        WHERE is_active=1 AND COALESCE(city_slug,'') <> '' AND slug NOT IN ($dupPhA)
        GROUP BY city_slug");
    $stStmt->execute($dupSkip);
    foreach ($stStmt->fetchAll() as $r) {
        if (isset($cityMap[$r['city_slug']])) $stats[$cityMap[$r['city_slug']]] = (int)$r['c'];
    }
    arsort($stats);

    $pageTitle = '依縣市瀏覽店家｜店家好口碑';
    $metaDesc  = '依縣市快速找店：' . implode('、', array_slice(array_keys($stats), 0, 5)) . '等地區店家。';
    $canonical = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/city.php' : 'https://www.gomag.com.tw/city';
    require_once __DIR__ . '/main/layout_head.php';
    ?>

    <div class="g-breadcrumb-wrap">
      <nav class="g-breadcrumb" aria-label="breadcrumb">
        <a href="<?= BASE_URL ?>/">首頁</a>
        <span class="g-breadcrumb-sep">›</span>
        <span class="current">📍 縣市瀏覽</span>
      </nav>
    </div>

    <section class="g-section">
      <div class="g-section-head">
        <div>
          <h1 class="g-section-title">📍 依縣市瀏覽店家</h1>
          <p class="g-section-sub">點擊縣市查看該地區所有口碑商家</p>
        </div>
      </div>

      <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <?php foreach ($stats as $cityName => $cnt):
            if (!isset($cityNameToSlug[$cityName])) continue;
            if ($cnt < 3) continue;
            $citySlug = $cityNameToSlug[$cityName];
        ?>
        <a href="<?= BASE_URL ?>/city.php?slug=<?= h($citySlug) ?>" class="g-city-meta-tag" style="background:var(--g-accent-light); color:var(--g-accent); border-color:var(--g-accent-light); text-decoration:none; font-size:.95rem; padding:9px 16px;">📍 <?= h($cityName) ?> <span style="font-weight:800;"><?= $cnt ?></span></a>
        <?php endforeach; ?>
      </div>
    </section>

    <?php
    // 更多服務地區：有交叉頁內容、但店家 < 3 的縣市（sanfeng 內容縣市）
    $contentCities = [];
    try {
        $ccRows = $db->query("SELECT DISTINCT city_slug FROM geo_category_pages WHERE is_active=1 AND (COALESCE(intro_html,'') <> '' OR JSON_LENGTH(COALESCE(faqs,'[]')) > 0)")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ccRows as $cs) {
            if (!isset($cityMap[$cs])) continue;
            $cn = $cityMap[$cs];
            if (($stats[$cn] ?? 0) >= 3) continue;  // 已在上方店家清單就不重複
            $contentCities[$cs] = $cn;
        }
    } catch (\Throwable $e) { $contentCities = []; }
    if ($contentCities):
    ?>
    <section class="g-section">
      <div class="g-section-head">
        <div>
          <h2 class="g-section-title">更多服務地區</h2>
          <p class="g-section-sub">這些地區的在地服務專頁</p>
        </div>
      </div>
      <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <?php foreach ($contentCities as $cs => $cn): ?>
        <a href="<?= BASE_URL ?>/city.php?slug=<?= h($cs) ?>" class="g-city-meta-tag" style="background:var(--g-bg-alt); color:var(--g-ink); border-color:var(--g-border); text-decoration:none; font-size:.95rem; padding:9px 16px;">🧭 <?= h($cn) ?></a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php
    require_once __DIR__ . '/main/layout_foot.php';
    exit;
}

// ═══════════════════════════════════════════════════════════
//   模式 B：指定縣市 → 顯示該縣市店家
// ═══════════════════════════════════════════════════════════
if (!isset($cityMap[$slug])) {
    http_response_code(404);
    die('縣市不存在或尚未開放');
}
$cityName = $cityMap[$slug];
$intro = $cityIntros[$cityName] ?? null;

// Phase D Day 3：城市內 brand_name / tagline 關鍵字搜尋
$qRaw = trim($_GET['q'] ?? '');
$q    = mb_substr($qRaw, 0, 50);   // cap 長度防 abuse

// 抓該縣市所有店家（按分類分組）— 套用關鍵字過濾
// 城市店家清單條件：本店在該城市 OR 有 client_city_pages 啟用該城市的多城市方案 row
// → 多縣市方案客戶（如奧喜：1 個本店在台中 + 5 城市行銷頁變體）在 5 個城市頁都看得到
$sql = "
    SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline,
           cl.has_minisite, cl.external_website_url, cl.hero_image_path, cl.hero_image_fit, cl.logo_path,
           cl.address, cl.phone, cl.is_placeholder,
           c.id AS cat_id, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
           EXISTS (SELECT 1 FROM client_city_pages ccp2
                   WHERE ccp2.client_id = cl.id AND ccp2.city_slug = ? AND ccp2.is_active = 1) AS has_city_variant
    FROM clients cl
    LEFT JOIN categories c ON cl.category_id = c.id
    WHERE cl.is_active = 1
      AND (cl.city_slug = ? OR EXISTS (
          SELECT 1 FROM client_city_pages ccp
          WHERE ccp.client_id = cl.id AND ccp.city_slug = ? AND ccp.is_active = 1
      ))
      AND cl.slug NOT IN ($dupPh)
";
$params = array_merge([$slug, $slug, $slug], $dupSkip);
if ($q !== '') {
    $sql .= " AND (cl.brand_name LIKE ? OR cl.tagline LIKE ? OR c.name LIKE ?)";
    $kw = '%' . $q . '%';
    array_push($params, $kw, $kw, $kw);
}
$sql .= " ORDER BY cl.is_placeholder ASC, c.sort_order, c.name, cl.id DESC";
$clientsStmt = $db->prepare($sql);
$clientsStmt->execute($params);
$clients = $clientsStmt->fetchAll();

// Phase D Day 3.1：搜尋查詢應用層日誌（給 analytics 用）
if ($q !== '') {
    $logDir  = __DIR__ . '/_logs';
    $logFile = $logDir . '/search.log';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    if (is_dir($logDir)) {
        $ip   = $_SERVER['HTTP_CF_CONNECTING_IP']
              ?? $_SERVER['HTTP_X_FORWARDED_FOR']
              ?? $_SERVER['REMOTE_ADDR']
              ?? '';
        $ip   = explode(',', $ip)[0];
        // 每日輪替 salt：同一人同一天會 hash 成一致值，跨天就還原為匿名
        $ipH  = $ip ? substr(sha1($ip . '|' . date('Y-m-d')), 0, 10) : '-';
        // tab 分隔；q 內含的 tab/newline 換成空白
        $qSafe = preg_replace('/[\t\r\n]+/', ' ', $q);
        $line = implode("\t", [
            date('c'),
            $cityName,
            $slug,
            $qSafe,
            count($clients),
            $ipH,
        ]) . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

// 該縣市「有寫內容」的交叉頁（即使沒店也要在城市頁露出入口；解決 sanfeng 內容縣市的站內可逛性）
$contentCells = [];
try {
    $ccStmt = $db->prepare("SELECT c.slug AS cat_slug, c.name AS cat_name, c.icon AS cat_icon,
            g.service_slug, g.service_name, g.meta_title
        FROM geo_category_pages g JOIN categories c ON g.category_id = c.id
        WHERE g.city_slug = ? AND g.is_active = 1
          AND (COALESCE(g.intro_html,'') <> '' OR JSON_LENGTH(COALESCE(g.faqs,'[]')) > 0)
        ORDER BY c.sort_order, c.id, g.service_slug");
    $ccStmt->execute([$slug]);
    $contentCells = $ccStmt->fetchAll();
} catch (\Throwable $e) { $contentCells = []; }

// 沒店、沒內容、又非搜尋 → 才 404；有內容（如沒店的彰化）仍渲染
if (empty($clients) && empty($contentCells) && $q === '') {
    http_response_code(404);
    die("「{$cityName}」目前尚未收錄店家");
}

// 按分類分組
$byCat = [];
$catCounts = [];
$catCovers = [];   // 分類 → 第一張可用 hero_image_path（給 explore card 用）
foreach ($clients as $cl) {
    $cName = $cl['cat_name'] ?? '其他';
    $byCat[$cName][] = $cl;
    $catCounts[$cName] = ($catCounts[$cName] ?? 0) + 1;
    if (empty($catCovers[$cName]) && !empty($cl['hero_image_path']) && empty($cl['is_placeholder'])) {
        $catCovers[$cName] = $cl['hero_image_path'];
    }
}

// 城市 hero 圖 fallback：cities.hero_image 沒設時，挑該城市第一張可用的店家 hero 圖
$cityHeroImg = '';
if (!empty($intro['hero_image'])) {
    $cityHeroImg = $intro['hero_image'];
} else {
    foreach ($clients as $cl) {
        if (!empty($cl['hero_image_path']) && empty($cl['is_placeholder'])) {
            $cityHeroImg = BASE_URL . '/' . $cl['hero_image_path'];
            break;
        }
    }
}

// 統計
$totalStores      = count($clients);
$totalPlaceholder = count(array_filter($clients, fn($c) => !empty($c['is_placeholder'])));
$totalReal        = $totalStores - $totalPlaceholder;
$totalCategories  = count($catCounts);

// ═══ 服務關鍵字標籤（該城市所有交叉頁內鏈；秀在 intro 粉紅區，給消費者分類 + SEO 內鏈）═══
$cityShort   = str_replace(['市', '縣'], '', $cityName);
$serviceTags = [];   // key => ['label'=>, 'cat'=>, 'svc'=>]
$catsWithSub = [];   // 已有子服務標籤的 cat_slug（避免再加大分類層標籤而重複）
// 有寫內容的交叉頁 → 子服務頁用 service_name（台中清潔）；大分類層內容取 meta_title 短標
foreach ($contentCells as $cc) {
    $cs = $cc['cat_slug'] ?? '';
    if ($cs === '') continue;
    if (!empty($cc['service_slug'])) {
        $svcN = ($cc['service_name'] ?? '') !== '' ? $cc['service_name'] : $cc['service_slug'];
        $lbl  = $cityShort . $svcN;
        $key  = $cs . '/' . $cc['service_slug'];
        $serviceTags[$key] = ['label' => $lbl, 'cat' => $cs, 'svc' => $cc['service_slug']];
        $catsWithSub[$cs] = true;
    } else {
        $lbl = '';
        if (!empty($cc['meta_title'])) {
            $lbl = trim(preg_replace('/(公司)?推薦.*$/u', '', explode('｜', $cc['meta_title'])[0]));
        }
        if ($lbl === '') $lbl = $cityShort . ($cc['cat_name'] ?? '');
        $serviceTags[$cs] = ['label' => $lbl, 'cat' => $cs, 'svc' => ''];
    }
}
// 有店家、但沒寫內容、也沒子服務的分類 → 大分類層標籤（城市+分類名）
foreach ($byCat as $cName => $catClients) {
    $cs2 = $catClients[0]['cat_slug'] ?? '';
    if (!$cs2 || isset($serviceTags[$cs2]) || isset($catsWithSub[$cs2])) continue;
    $serviceTags[$cs2] = ['label' => $cityShort . $cName, 'cat' => $cs2, 'svc' => ''];
}

// SEO（沒店但有內容的縣市改用服務導向標題，避免「共 0 家」）
if ($totalStores > 0) {
    $pageTitle  = "{$cityName}店家推薦｜共 {$totalStores} 家口碑商家整理";
    $metaDesc   = ($intro['tagline'] ?? '') . '。';
    $metaDesc  .= "{$cityName} {$totalStores} 家店家精選：" . implode('、', array_keys($catCounts)) . "。";
} else {
    $pageTitle  = "{$cityName}在地服務｜店家好口碑";
    $metaDesc   = "{$cityName}在地服務專頁：" . implode('、', array_map(fn($c) => $c['cat_name'], $contentCells)) . "的選店指南與在地資訊。";
}
$canonical  = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/city.php?slug=' . urlencode($slug) : 'https://www.gomag.com.tw/city/' . urlencode($slug);

require_once __DIR__ . '/main/layout_head.php';

// ─── JSON-LD: CollectionPage + ItemList ───────────────────
$itemList = [];
$idx = 1;
foreach ($clients as $cl) {
    $itemList[] = [
        '@type' => 'ListItem',
        'position' => $idx++,
        'item' => [
            '@type' => 'LocalBusiness',
            'name' => $cl['brand_name'],
            'address' => $cl['address'],
            // 主站 directory 卡片永遠指向 /store/{slug}（行銷頁）；mini-site 是獨立品牌入口
            'url' => clientStoreUrl($cl),
        ],
    ];
}
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonical,
    'about' => [
        '@type' => 'Place',
        'name' => $cityName,
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $cityName, 'addressCountry' => 'TW'],
    ],
    'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => $totalStores, 'itemListElement' => $itemList],
];
$breadcrumbLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => ((IS_LOCAL || IS_STAGING) ? BASE_URL . '/' : 'https://www.gomag.com.tw/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => '縣市瀏覽', 'item' => ((IS_LOCAL || IS_STAGING) ? BASE_URL . '/city.php' : 'https://www.gomag.com.tw/city')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $cityName],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<!-- ═══ Hero ═══ -->
<section class="g-hero">
  <?php if ($cityHeroImg): ?>
  <div class="g-hero-bg" style="background-image:url('<?= h($cityHeroImg) ?>');"></div>
  <?php else: ?>
  <div class="g-hero-bg" style="background:linear-gradient(135deg, #2a2a2a, #0f0f0f); animation:none; transform:none;"></div>
  <?php endif; ?>
  <div class="g-hero-overlay"></div>
  <div class="g-hero-content">
    <div class="g-hero-tag">
      <span class="g-hero-tag-dot"></span>
      <span>📍 <?= h($cityName) ?>・在地口碑名單</span>
    </div>
    <h1 class="g-hero-title">
      在<?= h(str_replace(['市', '縣'], '', $cityName)) ?>，<br>找一家<span>對的店</span>。
    </h1>
    <?php if ($intro): ?>
    <p class="g-hero-desc"><?= h($intro['tagline']) ?></p>
    <?php endif; ?>
    <form class="g-hero-search" action="<?= BASE_URL ?>/city.php" method="get" role="search">
      <input type="hidden" name="slug" value="<?= h($slug) ?>">
      <input type="text" class="g-hero-search-input" name="q" value="<?= h($q) ?>"
             placeholder="搜尋<?= h($cityName) ?>店家：例如 燒肉、剪髮、清潔" maxlength="50" autocomplete="off">
      <button type="submit" class="g-hero-search-btn" aria-label="搜尋">🔍</button>
    </form>
  </div>
</section>

<?php if ($q !== ''): ?>
<div class="g-search-banner">
  <span>「<strong><?= h($q) ?></strong>」搜尋結果：<strong><?= count($clients) ?></strong> 家<?= h($cityName) ?>店家</span>
  <a href="<?= BASE_URL ?>/city.php?slug=<?= h($slug) ?>">✕ 清除搜尋</a>
</div>
<?php endif; ?>

<?php if ($byCat ?? null): ?>
<!-- ═══ Sticky 分類 pill nav ═══ -->
<div class="g-cat-nav-wrap" id="g-cat-nav-wrap">
  <nav class="g-cat-nav" aria-label="<?= h($cityName) ?>分類導覽">
    <a class="g-cat-pill is-active" href="#" data-cat-pill="__all">
      全部 <span class="g-cat-pill-count"><?= $totalStores ?></span>
    </a>
    <?php foreach ($byCat as $catName => $catClients):
        $first = $catClients[0];
        $catSlug = $first['cat_slug'] ?? '';
        $catIcon = $first['cat_icon'] ?? '';
    ?>
    <a class="g-cat-pill" href="#cat-<?= h($catSlug) ?>" data-cat-pill="<?= h($catSlug) ?>">
      <?= h($catIcon) ?> <?= h($catName) ?>
      <span class="g-cat-pill-count"><?= count($catClients) ?></span>
    </a>
    <?php endforeach; ?>
  </nav>
</div>
<?php endif; ?>

<!-- ═══ 麵包屑 ═══ -->
<div class="g-breadcrumb-wrap">
  <nav class="g-breadcrumb" aria-label="breadcrumb">
    <a href="<?= BASE_URL ?>/">首頁</a>
    <span class="g-breadcrumb-sep">›</span>
    <a href="<?= BASE_URL ?>/city.php">縣市瀏覽</a>
    <span class="g-breadcrumb-sep">›</span>
    <span class="current">📍 <?= h($cityName) ?></span>
  </nav>
</div>

<!-- ═══ 城市介紹 + 快速資訊 ═══ -->
<?php if ($intro): ?>
<section class="g-city-intro">
  <div class="g-city-intro-text-block">
    <div class="g-city-intro-eyebrow">Explore <?= h($cityName) ?></div>
    <h2 class="g-city-intro-title">探索<?= h($cityName) ?><br>在地優質店家</h2>
    <p class="g-city-intro-text"><?= h($intro['intro']) ?></p>
    <?php if ($serviceTags): ?>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:18px;">
      <?php foreach ($serviceTags as $st):
          $stUrl = BASE_URL . '/city/' . h($slug) . '/' . h($st['cat']) . ($st['svc'] !== '' ? '/' . h($st['svc']) : ''); ?>
      <a href="<?= $stUrl ?>" class="g-city-meta-tag" style="background:var(--g-accent-light); color:var(--g-accent); border-color:var(--g-accent-light); text-decoration:none;"><?= h($st['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <aside class="g-city-meta-card">
    <h3><?= h($cityName) ?>站快速資訊</h3>
    <div class="g-city-meta-row">
      <span class="g-city-meta-label">收錄店家</span>
      <span class="g-city-meta-value g-city-meta-value-accent"><?= $totalStores ?> 家</span>
    </div>
    <div class="g-city-meta-row">
      <span class="g-city-meta-label">分類數</span>
      <span class="g-city-meta-value"><?= $totalCategories ?></span>
    </div>
    <?php if ($totalReal > 0): ?>
    <div class="g-city-meta-row">
      <span class="g-city-meta-label">已上線</span>
      <span class="g-city-meta-value"><?= $totalReal ?></span>
    </div>
    <?php endif; ?>
    <?php if ($totalPlaceholder > 0): ?>
    <div class="g-city-meta-row">
      <span class="g-city-meta-label">資料整理中</span>
      <span class="g-city-meta-value" style="color:var(--g-ink-muted);"><?= $totalPlaceholder ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($intro['areas'])): ?>
    <div class="g-city-meta-tags">
      <div class="g-city-meta-tags-label">主要服務區域</div>
      <div class="g-city-meta-tag-list">
        <?php foreach ($intro['areas'] as $a): ?>
        <span class="g-city-meta-tag"><?= h($a) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </aside>
</section>
<?php endif; ?>

<?php /* 內容型縣市（無 cities intro，如彰化）→ 仍在頂部秀服務關鍵字標籤 */ ?>
<?php if (!$intro && $q === '' && $serviceTags): ?>
<section class="g-section" style="padding-top:14px; padding-bottom:0;">
  <div style="display:flex; flex-wrap:wrap; gap:8px;">
    <?php foreach ($serviceTags as $st):
        $stUrl = BASE_URL . '/city/' . h($slug) . '/' . h($st['cat']) . ($st['svc'] !== '' ? '/' . h($st['svc']) : ''); ?>
    <a href="<?= $stUrl ?>" class="g-city-meta-tag" style="background:var(--g-accent-light); color:var(--g-accent); border-color:var(--g-accent-light); text-decoration:none;"><?= h($st['label']) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php
// ═══ Phase D: 該縣市本週熱門 / 最新加入 / 口碑 ═══
// 本週熱門 / 最新加入也加多城市條件（讓多縣市方案客戶可在 5 個城市頁出現）
$hotStmt = $db->prepare("
  SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline, cl.hero_image_path, cl.hero_image_fit, cl.logo_path,
         cl.has_minisite, cl.external_website_url,
         cl.address, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
         EXISTS (SELECT 1 FROM client_city_pages ccp2
                 WHERE ccp2.client_id = cl.id AND ccp2.city_slug = ? AND ccp2.is_active = 1) AS has_city_variant
  FROM clients cl LEFT JOIN categories c ON cl.category_id=c.id
  WHERE cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0
    AND (cl.city_slug = ? OR EXISTS (
        SELECT 1 FROM client_city_pages ccp
        WHERE ccp.client_id = cl.id AND ccp.city_slug = ? AND ccp.is_active = 1
    ))
    AND cl.is_featured=1 AND cl.slug NOT IN ($dupPh)
  ORDER BY cl.id DESC LIMIT 4");
$hotStmt->execute(array_merge([$slug, $slug, $slug], $dupSkip));
$hotStores = $hotStmt->fetchAll();

$latestStmt = $db->prepare("
  SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline, cl.hero_image_path, cl.hero_image_fit, cl.logo_path,
         cl.has_minisite, cl.external_website_url,
         cl.address, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
         EXISTS (SELECT 1 FROM client_city_pages ccp2
                 WHERE ccp2.client_id = cl.id AND ccp2.city_slug = ? AND ccp2.is_active = 1) AS has_city_variant
  FROM clients cl LEFT JOIN categories c ON cl.category_id=c.id
  WHERE cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0
    AND (cl.city_slug = ? OR EXISTS (
        SELECT 1 FROM client_city_pages ccp
        WHERE ccp.client_id = cl.id AND ccp.city_slug = ? AND ccp.is_active = 1
    ))
    AND cl.slug NOT IN ($dupPh)
  ORDER BY cl.created_at DESC, cl.id DESC LIMIT 4");
$latestStmt->execute(array_merge([$slug, $slug, $slug], $dupSkip));
$latestStores = $latestStmt->fetchAll();

// 城市口碑：同店去重（每店最多 1 筆，取該店最高分／最新一則），再跨店取前 6
// 避免某家店 5 筆五星霸佔版面、確保 6 個方塊來自 6 家不同店家
$cityReviewsStmt = $db->prepare("
  WITH ranked AS (
    SELECT t.*, ROW_NUMBER() OVER (PARTITION BY t.client_id ORDER BY t.rating DESC, t.id DESC) AS rn
    FROM testimonials t
    WHERE t.is_active=1 AND COALESCE(t.source,'') <> 'demo'
  )
  SELECT r.reviewer_name, r.rating, r.content, cl.brand_name, cl.subdomain, cl.slug,
         cl.has_minisite, cl.external_website_url
  FROM ranked r JOIN clients cl ON r.client_id = cl.id
  WHERE r.rn = 1 AND cl.is_active=1 AND cl.city_slug = ?
    AND cl.slug NOT IN ($dupPh)
  ORDER BY r.rating DESC, r.id DESC LIMIT 6");
$cityReviewsStmt->execute(array_merge([$slug], $dupSkip));
$cityReviews = $cityReviewsStmt->fetchAll();

// 共用 render 卡片 helper
// $currentCity = 當前城市頁的 slug；若該店在此城市有 client_city_pages 變體，連結改用變體 URL
function renderCityStoreCard(array $cl, string $currentCity = ''): void {
  $sub = $cl['subdomain'] ?? $cl['slug'];
  $cardStyle = gStoreCardImg($cl);
  $cardUrl = !empty($cl['has_city_variant']) && $currentCity !== ''
      ? rtrim(clientStoreUrl($cl), '/') . '/' . urlencode($currentCity)
      : clientStoreUrl($cl);
  ?>
  <a class="g-store-card" href="<?= h($cardUrl) ?>">
    <div class="g-store-img"<?= $cardStyle ?>>
      <?php if ($cardStyle === ''): ?><div class="g-store-img-fallback"><span class="icon"><?= h($cl['cat_icon']??'🏪') ?></span><span class="label"><?= h($cl['cat_name']??'') ?></span></div><?php endif; ?>
    </div>
    <div class="g-store-meta-top"><div class="g-store-name"><?= h($cl['brand_name']) ?></div></div>
    <div class="g-store-loc"><?= h($cl['cat_icon']??'') ?> <?= h($cl['cat_name']??'') ?></div>
    <?php if ($cl['tagline']): ?><div class="g-store-cat-label"><?= h(mb_strimwidth($cl['tagline'], 0, 32, '…', 'UTF-8')) ?></div><?php endif; ?>
  </a>
  <?php
}
?>

<?php if ($q === '' && $hotStores): ?>
<!-- ═══ 本週熱門 ═══ -->
<section class="g-section">
  <div class="g-section-head">
    <div><h2 class="g-section-title">🔥 本週<?= h($cityName) ?>熱門</h2></div>
  </div>
  <div class="g-store-grid">
    <?php foreach ($hotStores as $cl) renderCityStoreCard($cl, $slug); ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══ 各分類店家清單（保留現有 SEO 結構，套新樣式）═══ -->
<?php foreach ($byCat as $catName => $catClients):
    $first = $catClients[0];
    $catSlug = $first['cat_slug'] ?? '';
    $catIcon = $first['cat_icon'] ?? '🏪';
?>
<section class="g-section g-cat-anchor"<?= $catSlug ? ' id="cat-'.h($catSlug).'"' : '' ?>>
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title">
        <?= h($catIcon) ?> <?= h($catName) ?>
        <span class="g-section-title-meta">（<?= count($catClients) ?> 家）</span>
      </h2>
    </div>
    <?php if ($catSlug): ?>
    <a href="<?= BASE_URL ?>/category.php?slug=<?= h($catSlug) ?>" class="g-section-link">看全台<?= h($catName) ?></a>
    <?php endif; ?>
  </div>

  <div class="g-store-grid">
    <?php foreach ($catClients as $cl):
        $sub = $cl['subdomain'] ?? $cl['slug'];
        $cardStyle = gStoreCardImg($cl);
        // 該店在此城市有多城市變體 → 卡片連到變體 URL（強化 SEO 內鏈 + UX 對應）
        $linkUrl = !empty($cl['has_city_variant'])
            ? rtrim(clientStoreUrl($cl), '/') . '/' . urlencode($slug)
            : clientStoreUrl($cl);
        $isPH = !empty($cl['is_placeholder']);
        $cardClass = 'g-store-card' . ($isPH ? ' g-store-card-ph' : '');
    ?>
    <a class="<?= $cardClass ?>" href="<?= $linkUrl ?>">
      <div class="g-store-img"<?= $cardStyle ?>>
        <?php if ($cardStyle === ''): ?>
        <div class="g-store-img-fallback">
          <span class="icon"><?= h($catIcon) ?></span>
          <span class="label"><?= h($catName) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($isPH): ?>
        <span class="g-store-badge g-store-badge-ph">📋 資料整理中</span>
        <?php endif; ?>
      </div>
      <div class="g-store-meta-top">
        <div class="g-store-name"><?= h($cl['brand_name']) ?></div>
      </div>
      <?php if ($cl['tagline']): ?>
      <div class="g-store-loc"><?= h(mb_strimwidth($cl['tagline'], 0, 36, '…', 'UTF-8')) ?></div>
      <?php endif; ?>
      <div class="g-store-cat-label">
        <?php if ($cl['address']): ?>📍 <?= h(mb_strimwidth(str_replace($cityName, '', $cl['address']), 0, 24, '…', 'UTF-8')) ?><?php endif; ?>
        <?php if ($cl['phone'] && !$isPH): ?> · 📞 <?= h($cl['phone']) ?><?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<?php if ($q === '' && $latestStores && count($latestStores) >= 2): ?>
<!-- ═══ 最新加入 ═══ -->
<section class="g-section">
  <div class="g-section-head">
    <div><h2 class="g-section-title">🆕 <?= h($cityName) ?>最新加入</h2></div>
  </div>
  <div class="g-store-grid">
    <?php foreach ($latestStores as $cl) renderCityStoreCard($cl, $slug); ?>
  </div>
</section>
<?php endif; ?>

<?php if ($q === '' && $cityReviews): ?>
<!-- ═══ 真實口碑 ═══ -->
<section class="g-section" style="background:var(--g-bg-alt); max-width:none; padding-left:0; padding-right:0;">
  <div style="max-width:1320px; margin:0 auto; padding:0 32px;">
    <div class="g-section-head">
      <div><h2 class="g-section-title">💬 <?= h($cityName) ?>真實口碑</h2></div>
    </div>
    <div class="g-reviews-list">
      <?php foreach ($cityReviews as $r):
        $sub = $r['subdomain'] ?? $r['slug'];
        $initial = mb_substr($r['reviewer_name'], 0, 1);
      ?>
      <div class="g-review-item">
        <div class="g-review-head">
          <div class="g-review-avatar"><?= h($initial) ?></div>
          <div>
            <div class="g-review-name"><?= h($r['reviewer_name']) ?></div>
            <div class="g-review-stars-small"><?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?></div>
          </div>
        </div>
        <p class="g-review-text"><?= h(mb_strimwidth($r['content'], 0, 160, '…', 'UTF-8')) ?></p>
        <a href="<?= h(clientStoreUrl($r)) ?>" style="display:inline-block; margin-top:10px; font-size:.8rem; color:var(--g-ink-muted); text-decoration:none; border-bottom:1px dashed var(--g-border);">
          → <?= h($r['brand_name']) ?>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══ B 端業務 banner ═══ -->
<div class="g-banner-wrap">
  <div class="g-banner">
    <div class="g-banner-bg"></div>
    <div class="g-banner-text">
      <div class="g-banner-eyebrow">B2B Partnership</div>
      <h3 class="g-banner-title">想加入店家好口碑？</h3>
      <p class="g-banner-desc">我們專業業務團隊到店服務，零學習成本上架。</p>
    </div>
    <a href="<?= BASE_URL ?>/" class="g-banner-btn">立即聯絡</a>
  </div>
</div>

<!-- ═══ 大 CTA ═══ -->
<section class="g-cta">
  <div class="g-cta-inner">
    <div class="g-cta-eyebrow">Make it Yours</div>
    <h2 class="g-cta-title">讓<?= h(str_replace(['市', '縣'], '', $cityName)) ?>的客人，<br>找到對的你。</h2>
    <p class="g-cta-desc">店家好口碑專注在地口碑曝光 — 月費 NT$300 起，業務團隊到店服務，幫你把生意做大。</p>
    <div class="g-cta-btns">
      <a href="<?= BASE_URL ?>/" class="g-cta-btn g-cta-btn-primary">立即聯絡</a>
      <a href="<?= BASE_URL ?>/category.php" class="g-cta-btn g-cta-btn-secondary">了解合作</a>
    </div>
  </div>
</section>

<?php if (!empty($byCat)): ?>
<script>
(function() {
  // Phase D Day 3：scroll → highlight active cat pill
  var pills = document.querySelectorAll('.g-cat-pill[data-cat-pill]');
  if (!pills.length) return;
  var sections = Array.from(document.querySelectorAll('.g-cat-anchor[id^="cat-"]'));
  if (!sections.length) return;

  function setActive(slug) {
    pills.forEach(function(p) {
      var on = p.getAttribute('data-cat-pill') === slug;
      p.classList.toggle('is-active', on);
      if (on) {
        var nav = p.parentElement;
        var off = p.offsetLeft - nav.offsetWidth / 2 + p.offsetWidth / 2;
        nav.scrollTo({ left: off, behavior: 'smooth' });
      }
    });
  }

  // top of page → 全部 active
  setActive('__all');

  function pickActive() {
    if (window.scrollY < 400) { setActive('__all'); return; }
    // 找出當前覆蓋 y=140（sticky header+nav 下方 10px）的 section
    var line = 140;
    var found = null;
    for (var i = 0; i < sections.length; i++) {
      var r = sections[i].getBoundingClientRect();
      if (r.top <= line && r.bottom > line) { found = sections[i]; break; }
    }
    if (found) setActive(found.id.replace(/^cat-/, ''));
  }

  var ticking = false;
  window.addEventListener('scroll', function() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function() { pickActive(); ticking = false; });
  }, { passive: true });
  pickActive();

  // 點 "全部" → 回頂端
  var allPill = document.querySelector('.g-cat-pill[data-cat-pill="__all"]');
  if (allPill) {
    allPill.addEventListener('click', function(e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
})();

// 產業標籤列：可滑動提示（右側漸層+箭頭，內容超出才顯示，滑到底自動隱藏）
(function() {
  var wrap = document.getElementById('g-cat-nav-wrap');
  if (!wrap) return;
  var nav = wrap.querySelector('.g-cat-nav');
  if (!nav) return;
  function upd() {
    var more = nav.scrollWidth - nav.clientWidth;
    wrap.classList.toggle('is-overflowing', more > 4);
    wrap.classList.toggle('at-end', nav.scrollLeft >= more - 4);
  }
  nav.addEventListener('scroll', upd, { passive: true });
  window.addEventListener('resize', upd);
  upd();
  setTimeout(upd, 300); // 等字型載入後再算一次寬度
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
