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
    $cityList = '臺北市|台北市|新北市|桃園市|臺中市|台中市|臺南市|台南市|高雄市|基隆市|新竹市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義市|嘉義縣|屏東縣|宜蘭縣|花蓮縣|臺東縣|台東縣|澎湖縣|金門縣|連江縣';
    $rows = $db->query("SELECT address FROM clients WHERE is_active=1 AND address IS NOT NULL AND address != ''")->fetchAll();
    $stats = [];
    foreach ($rows as $r) {
        if (preg_match('/^(' . $cityList . ')/u', $r['address'], $m)) {
            $c = str_replace('臺', '台', $m[1]);
            $stats[$c] = ($stats[$c] ?? 0) + 1;
        }
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

      <div class="g-store-grid" style="grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));">
        <?php foreach ($stats as $cityName => $cnt):
            if (!isset($cityNameToSlug[$cityName])) continue;
            if ($cnt < 3) continue;
            $citySlug = $cityNameToSlug[$cityName];
        ?>
        <a class="g-store-card" href="<?= BASE_URL ?>/city.php?slug=<?= h($citySlug) ?>" style="text-align:center;">
          <div class="g-store-img" style="aspect-ratio:1.4; background:var(--g-bg-alt); display:grid; place-items:center; font-size:48px;">📍</div>
          <div class="g-store-meta-top" style="justify-content:center;">
            <div class="g-store-name" style="font-size:18px;"><?= h($cityName) ?></div>
          </div>
          <div class="g-store-loc" style="font-family:var(--g-font-num); font-weight:600; color:var(--g-accent);"><?= $cnt ?> 家店家</div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

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
$sql = "
    SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline,
           cl.has_minisite, cl.external_website_url, cl.hero_image_path,
           cl.address, cl.phone, cl.is_placeholder,
           c.id AS cat_id, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
    FROM clients cl
    LEFT JOIN categories c ON cl.category_id = c.id
    WHERE cl.is_active = 1 AND cl.address LIKE ? AND cl.slug NOT IN ($dupPh)
";
$params = array_merge([$cityName . '%'], $dupSkip);
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

if (empty($clients)) {
    if ($q !== '') {
        // 搜尋無結果不要 404，讓使用者看到搜尋 banner + 清空連結
        // fallthrough：下方靠 if (!$clients) 的判斷退出主要 SECTION，但 hero/intro/banner 仍會渲染
    } else {
        http_response_code(404);
        die("「{$cityName}」目前尚未收錄店家");
    }
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

// SEO
$pageTitle  = "{$cityName}店家推薦｜共 {$totalStores} 家口碑商家整理";
$metaDesc   = ($intro['tagline'] ?? '') . '。';
$metaDesc  .= "{$cityName} {$totalStores} 家店家精選：" . implode('、', array_keys($catCounts)) . "。";
$canonical  = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/city.php?slug=' . urlencode($slug) : 'https://www.gomag.com.tw/city/' . urlencode($slug);

require_once __DIR__ . '/main/layout_head.php';

// ─── JSON-LD: CollectionPage + ItemList ───────────────────
$itemList = [];
$idx = 1;
foreach ($clients as $cl) {
    $sub = $cl['subdomain'] ?: $cl['slug'];
    $itemList[] = [
        '@type' => 'ListItem',
        'position' => $idx++,
        'item' => [
            '@type' => 'LocalBusiness',
            'name' => $cl['brand_name'],
            'address' => $cl['address'],
            'url' => ((IS_LOCAL || IS_STAGING) ? BASE_URL . '/store.php?sub=' . $sub : 'https://www.gomag.com.tw/store/' . $sub),
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
    <?php if (!empty($intro['highlights'])): ?>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:18px;">
      <?php foreach ($intro['highlights'] as $hl): ?>
      <span class="g-city-meta-tag" style="background:var(--g-accent-light); color:var(--g-accent); border-color:var(--g-accent-light);">✨ <?= h($hl) ?></span>
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

<?php
// ═══ Phase D: 該縣市本週熱門 / 最新加入 / 口碑 ═══
$hotStmt = $db->prepare("
  SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline, cl.hero_image_path,
         cl.address, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
  FROM clients cl LEFT JOIN categories c ON cl.category_id=c.id
  WHERE cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0 AND cl.address LIKE ?
    AND cl.is_featured=1 AND cl.slug NOT IN ($dupPh)
  ORDER BY cl.id DESC LIMIT 4");
$hotStmt->execute(array_merge([$cityName . '%'], $dupSkip));
$hotStores = $hotStmt->fetchAll();

$latestStmt = $db->prepare("
  SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline, cl.hero_image_path,
         cl.address, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
  FROM clients cl LEFT JOIN categories c ON cl.category_id=c.id
  WHERE cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0 AND cl.address LIKE ?
    AND cl.slug NOT IN ($dupPh)
  ORDER BY cl.created_at DESC, cl.id DESC LIMIT 4");
$latestStmt->execute(array_merge([$cityName . '%'], $dupSkip));
$latestStores = $latestStmt->fetchAll();

$cityReviewsStmt = $db->prepare("
  SELECT t.reviewer_name, t.rating, t.content, cl.brand_name, cl.subdomain, cl.slug
  FROM testimonials t JOIN clients cl ON t.client_id = cl.id
  WHERE t.is_active=1 AND cl.is_active=1 AND cl.address LIKE ?
    AND cl.slug NOT IN ($dupPh)
  ORDER BY t.rating DESC, t.id DESC LIMIT 6");
$cityReviewsStmt->execute(array_merge([$cityName . '%'], $dupSkip));
$cityReviews = $cityReviewsStmt->fetchAll();

// 共用 render 卡片 helper
function renderCityStoreCard(array $cl): void {
  $sub = $cl['subdomain'] ?? $cl['slug'];
  $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
  ?>
  <a class="g-store-card" href="<?= BASE_URL ?>/store.php?sub=<?= urlencode($sub) ?>">
    <div class="g-store-img" <?= $heroImg ? 'style="background-image:url(\''.$heroImg.'\')"' : '' ?>>
      <?php if (!$heroImg): ?><div class="g-store-img-fallback"><span class="icon"><?= h($cl['cat_icon']??'🏪') ?></span><span class="label"><?= h($cl['cat_name']??'') ?></span></div><?php endif; ?>
    </div>
    <div class="g-store-meta-top"><div class="g-store-name"><?= h($cl['brand_name']) ?></div></div>
    <div class="g-store-loc"><?= h($cl['cat_icon']??'') ?> <?= h($cl['cat_name']??'') ?></div>
    <?php if ($cl['tagline']): ?><div class="g-store-cat-label"><?= h(mb_strimwidth($cl['tagline'], 0, 32, '…', 'UTF-8')) ?></div><?php endif; ?>
  </a>
  <?php
}
?>

<?php if ($q === '' && count($byCat) >= 2): ?>
<!-- ═══ 依分類探索（Phase D Day 2） ═══ -->
<section class="g-section">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title">依分類探索<?= h($cityName) ?></h2>
      <p class="g-section-sub"><?= count($byCat) ?> 個分類，找到你需要的<?= h(str_replace(['市','縣'], '', $cityName)) ?>服務</p>
    </div>
  </div>
  <div class="g-explore-grid">
    <?php foreach ($byCat as $catName => $catClients):
        $first = $catClients[0];
        $catSlug = $first['cat_slug'] ?? '';
        $catIcon = $first['cat_icon'] ?? '🏪';
        $cover = $catCovers[$catName] ?? '';
        $coverUrl = $cover ? BASE_URL . '/' . $cover : '';
        $anchor = $catSlug ? '#cat-' . $catSlug : '#';
    ?>
    <a class="g-explore-card" href="<?= h($anchor) ?>">
      <?php if ($coverUrl): ?>
      <div class="g-explore-card-img" style="background-image:url('<?= h($coverUrl) ?>');"></div>
      <?php else: ?>
      <div class="g-explore-card-fallback"><?= h($catIcon) ?></div>
      <?php endif; ?>
      <div class="g-explore-card-overlay">
        <div class="g-explore-card-name"><?= h(str_replace(['市','縣'], '', $cityName)) ?>・<?= h($catName) ?></div>
        <div class="g-explore-card-count"><?= count($catClients) ?> 家店家</div>
      </div>
      <div class="g-explore-card-arrow">→</div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($q === '' && $hotStores): ?>
<!-- ═══ 本週熱門 ═══ -->
<section class="g-section">
  <div class="g-section-head">
    <div><h2 class="g-section-title">🔥 本週<?= h($cityName) ?>熱門</h2></div>
  </div>
  <div class="g-store-grid">
    <?php foreach ($hotStores as $cl) renderCityStoreCard($cl); ?>
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
        $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
        $linkUrl = BASE_URL . '/store.php?sub=' . urlencode($sub);
        $isPH = !empty($cl['is_placeholder']);
        $cardClass = 'g-store-card' . ($isPH ? ' g-store-card-ph' : '');
    ?>
    <a class="<?= $cardClass ?>" href="<?= $linkUrl ?>">
      <div class="g-store-img" <?= $heroImg ? 'style="background-image:url(\''.$heroImg.'\')"' : '' ?>>
        <?php if (!$heroImg): ?>
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
    <?php foreach ($latestStores as $cl) renderCityStoreCard($cl); ?>
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
        <a href="<?= BASE_URL ?>/store.php?sub=<?= urlencode($sub) ?>" style="display:inline-block; margin-top:10px; font-size:.8rem; color:var(--g-ink-muted); text-decoration:none; border-bottom:1px dashed var(--g-border);">
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
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
