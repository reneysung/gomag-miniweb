<?php
// citycat.php  ─  城市×產業交叉頁 /city/{city}/{cat}（落地順序第 3 步）
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();
$slug    = strtolower(trim($_GET['slug'] ?? ''));
$catSlug = strtolower(trim($_GET['cat']  ?? ''));

// ─── 縣市 slug → 中文名（唯一來源：cities 表）──────────────
$cityMap = getCityMap();
if (!isset($cityMap[$slug])) {
    http_response_code(404);
    die('縣市不存在或尚未開放');
}
$cityName = $cityMap[$slug];

// ─── 分類 slug → 分類 ─────────────────────────────────────
$catStmt = $db->prepare("SELECT id, slug, name, icon FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1");
$catStmt->execute([$catSlug]);
$cat = $catStmt->fetch();
if (!$cat) {
    http_response_code(404);
    die('分類不存在');
}
$catId   = (int)$cat['id'];
$catName = $cat['name'];
$catIcon = $cat['icon'] ?? '🏪';

// ─── 交叉頁內容（可選；有就脫離庫存撐排名）──────────────
$geo = null;
try {
    $g = $db->prepare("SELECT intro_html, faqs, hero_image, meta_title, meta_desc FROM geo_category_pages WHERE city_slug = ? AND category_id = ? AND is_active = 1 LIMIT 1");
    $g->execute([$slug, $catId]);
    $geo = $g->fetch() ?: null;
} catch (\Throwable $e) {
    $geo = null;  // 表還沒建時不致命
}
$geoFaqs = $geo && !empty($geo['faqs']) ? (json_decode($geo['faqs'], true) ?: []) : [];
$hasContent = $geo && (trim((string)$geo['intro_html']) !== '' || !empty($geoFaqs));

// ─── 該城該類店家（排除重複客戶）─────────────────────────
$dupSkip = getDuplicateSkipSlugs();
$dupPh   = implode(',', array_fill(0, count($dupSkip), '?'));
$sql = "
    SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline,
           cl.has_minisite, cl.external_website_url, cl.hero_image_path,
           cl.address, cl.phone, cl.is_placeholder
    FROM clients cl
    WHERE cl.is_active = 1 AND cl.category_id = ? AND cl.city_slug = ?
      AND cl.slug NOT IN ($dupPh)
    ORDER BY cl.is_placeholder ASC, cl.id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute(array_merge([$catId, $slug], $dupSkip));
$clients = $stmt->fetchAll();

$totalStores = count($clients);
$realCount   = count(array_filter($clients, fn($c) => empty($c['is_placeholder'])));

// ─── 上架閘門（IA 文件核心）────────────────────────────────
//   render：有內容 或 ≥1 店，否則 404
//   index ：有內容 或 ≥3 真實店，否則 noindex,follow
if (!$hasContent && $totalStores === 0) {
    http_response_code(404);
    die("「{$cityName}{$catName}」目前尚無收錄");
}
$indexable = $hasContent || $realCount >= 3;
if (!$indexable) {
    $metaRobots = 'noindex,follow';  // 沿用 layout_head 的 robots 機制
}

// ─── Hero 圖 fallback ────────────────────────────────────
$heroImg = '';
if ($geo && !empty($geo['hero_image'])) {
    $heroImg = $geo['hero_image'];
} else {
    foreach ($clients as $cl) {
        if (!empty($cl['hero_image_path']) && empty($cl['is_placeholder'])) {
            $heroImg = BASE_URL . '/' . $cl['hero_image_path'];
            break;
        }
    }
}

$cityShort = str_replace(['市', '縣'], '', $cityName);

// ─── SEO ─────────────────────────────────────────────────
$pageTitle = !empty($geo['meta_title'])
    ? $geo['meta_title']
    : "{$cityName}{$catName}推薦｜{$totalStores} 家在地口碑商家";
$metaDesc = !empty($geo['meta_desc'])
    ? $geo['meta_desc']
    : "{$cityName}{$catName}店家精選：在地口碑名單、評價、營業資訊一次看。共收錄 {$totalStores} 家。";
$canonical = (IS_LOCAL || IS_STAGING)
    ? BASE_URL . '/citycat.php?slug=' . urlencode($slug) . '&cat=' . urlencode($catSlug)
    : 'https://www.gomag.com.tw/city/' . urlencode($slug) . '/' . urlencode($catSlug);
$extraCss = [BASE_URL . '/assets/css/gomag.css'];

require_once __DIR__ . '/main/layout_head.php';

// ─── JSON-LD: CollectionPage + ItemList + BreadcrumbList ──
$base = (IS_LOCAL || IS_STAGING) ? BASE_URL : 'https://www.gomag.com.tw';
$itemList = [];
$idx = 1;
foreach ($clients as $cl) {
    $itemList[] = [
        '@type' => 'ListItem',
        'position' => $idx++,
        'item' => ['@type' => 'LocalBusiness', 'name' => $cl['brand_name'], 'address' => $cl['address'], 'url' => clientStoreUrl($cl)],
    ];
}
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $metaDesc,
    'url' => $canonical,
    'about' => ['@type' => 'Place', 'name' => $cityName, 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $cityName, 'addressCountry' => 'TW']],
    'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => $totalStores, 'itemListElement' => $itemList],
];
$breadcrumbLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => $base . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => '縣市瀏覽', 'item' => $base . '/city'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $cityName, 'item' => $base . '/city/' . $slug],
        ['@type' => 'ListItem', 'position' => 4, 'name' => $catName],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php if ($geoFaqs): ?>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'] ?? '', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a'] ?? '']], $geoFaqs),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>

<!-- ═══ Hero ═══ -->
<section class="g-hero">
  <?php if ($heroImg): ?>
  <div class="g-hero-bg" style="background-image:url('<?= h($heroImg) ?>');"></div>
  <?php else: ?>
  <div class="g-hero-bg" style="background:linear-gradient(135deg, #2a2a2a, #0f0f0f); animation:none; transform:none;"></div>
  <?php endif; ?>
  <div class="g-hero-overlay"></div>
  <div class="g-hero-content">
    <div class="g-hero-tag">
      <span class="g-hero-tag-dot"></span>
      <span><?= h($catIcon) ?> <?= h($cityName) ?>・<?= h($catName) ?></span>
    </div>
    <h1 class="g-hero-title">
      <?= h($cityShort) ?><?= h($catName) ?>，<br>找一家<span>對的店</span>。
    </h1>
    <p class="g-hero-desc">在地口碑名單・共 <?= $totalStores ?> 家</p>
  </div>
</section>

<!-- ═══ 麵包屑 ═══ -->
<div class="g-breadcrumb-wrap">
  <nav class="g-breadcrumb" aria-label="breadcrumb">
    <a href="<?= BASE_URL ?>/">首頁</a>
    <span class="g-breadcrumb-sep">›</span>
    <a href="<?= BASE_URL ?>/city.php">縣市瀏覽</a>
    <span class="g-breadcrumb-sep">›</span>
    <a href="<?= BASE_URL ?>/city.php?slug=<?= h($slug) ?>">📍 <?= h($cityName) ?></a>
    <span class="g-breadcrumb-sep">›</span>
    <span class="current"><?= h($catIcon) ?> <?= h($catName) ?></span>
  </nav>
</div>

<!-- ═══ 在地內文（有才顯示）═══ -->
<?php if ($geo && trim((string)$geo['intro_html']) !== ''): ?>
<section class="g-city-intro">
  <div class="g-city-intro-text-block">
    <div class="g-city-intro-eyebrow">Explore <?= h($cityName) ?> · <?= h($catName) ?></div>
    <h2 class="g-city-intro-title"><?= h($cityName) ?><?= h($catName) ?>怎麼挑？</h2>
    <div class="g-city-intro-text"><?= $geo['intro_html'] /* admin 受信任 HTML */ ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ═══ 店家清單 ═══ -->
<?php if ($totalStores > 0): ?>
<section class="g-section g-cat-anchor">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($catIcon) ?> <?= h($cityName) ?><?= h($catName) ?>店家 <span class="g-section-title-meta">（<?= $totalStores ?> 家）</span></h2>
    </div>
    <a href="<?= BASE_URL ?>/category.php?slug=<?= h($catSlug) ?>" class="g-section-link">看全台<?= h($catName) ?></a>
  </div>

  <div class="g-store-grid">
    <?php foreach ($clients as $cl):
        $cHero = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
        $isPH = !empty($cl['is_placeholder']);
        $cardClass = 'g-store-card' . ($isPH ? ' g-store-card-ph' : '');
    ?>
    <a class="<?= $cardClass ?>" href="<?= h(clientStoreUrl($cl)) ?>">
      <div class="g-store-img" <?= $cHero ? 'style="background-image:url(\''.$cHero.'\')"' : '' ?>>
        <?php if (!$cHero): ?>
        <div class="g-store-img-fallback"><span class="icon"><?= h($catIcon) ?></span><span class="label"><?= h($catName) ?></span></div>
        <?php endif; ?>
        <?php if ($isPH): ?><span class="g-store-badge g-store-badge-ph">📋 資料整理中</span><?php endif; ?>
      </div>
      <div class="g-store-meta-top"><div class="g-store-name"><?= h($cl['brand_name']) ?></div></div>
      <?php if ($cl['tagline']): ?><div class="g-store-loc"><?= h(mb_strimwidth($cl['tagline'], 0, 36, '…', 'UTF-8')) ?></div><?php endif; ?>
      <div class="g-store-cat-label">
        <?php if ($cl['address']): ?>📍 <?= h(mb_strimwidth(str_replace($cityName, '', $cl['address']), 0, 24, '…', 'UTF-8')) ?><?php endif; ?>
        <?php if ($cl['phone'] && !$isPH): ?> · 📞 <?= h($cl['phone']) ?><?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══ FAQ（有才顯示）═══ -->
<?php if ($geoFaqs): ?>
<section class="g-section">
  <div class="g-section-head"><div><h2 class="g-section-title">❓ <?= h($cityName) ?><?= h($catName) ?>常見問題</h2></div></div>
  <div style="max-width:820px;">
    <?php foreach ($geoFaqs as $f): if (empty($f['q'])) continue; ?>
    <details style="border:1px solid var(--g-border); border-radius:10px; padding:14px 18px; margin-bottom:10px;">
      <summary style="font-weight:700; cursor:pointer; color:var(--g-ink);"><?= h($f['q']) ?></summary>
      <div style="margin-top:10px; color:var(--g-ink-soft); line-height:1.8;"><?= h($f['a'] ?? '') ?></div>
    </details>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══ 相關攻略文（內容層互鏈，第 5 步）═══ -->
<?php
$relGuides = [];
try {
    $rg = $db->prepare("SELECT slug, title, excerpt, cover_image FROM guides WHERE status='published' AND (city_slug = ? OR category_id = ?) ORDER BY published_at DESC, id DESC LIMIT 4");
    $rg->execute([$slug, $catId]);
    $relGuides = $rg->fetchAll();
} catch (\Throwable $e) { $relGuides = []; }
if ($relGuides):
?>
<section class="g-section">
  <div class="g-section-head"><div><h2 class="g-section-title">📖 <?= h($cityShort) ?><?= h($catName) ?>攻略</h2></div></div>
  <div class="g-store-grid">
    <?php foreach ($relGuides as $gd):
        $gUrl = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/guide.php?slug=' . urlencode($gd['slug']) : 'https://www.gomag.com.tw/guide/' . urlencode($gd['slug']);
        $gc = $gd['cover_image'] ?: '';
    ?>
    <a class="g-store-card" href="<?= h($gUrl) ?>">
      <div class="g-store-img" <?= $gc ? 'style="background-image:url(\''.h($gc).'\')"' : '' ?>>
        <?php if (!$gc): ?><div class="g-store-img-fallback"><span class="icon">📖</span><span class="label">攻略</span></div><?php endif; ?>
      </div>
      <div class="g-store-meta-top"><div class="g-store-name"><?= h($gd['title']) ?></div></div>
      <?php if ($gd['excerpt']): ?><div class="g-store-loc"><?= h(mb_strimwidth($gd['excerpt'], 0, 50, '…', 'UTF-8')) ?></div><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══ 內鏈：回縣市 / 看全台分類 ═══ -->
<section class="g-section" style="padding-top:0;">
  <div style="display:flex; flex-wrap:wrap; gap:12px;">
    <a href="<?= BASE_URL ?>/city.php?slug=<?= h($slug) ?>" class="g-cta-btn g-cta-btn-secondary">← 回<?= h($cityName) ?>全部店家</a>
    <a href="<?= BASE_URL ?>/category.php?slug=<?= h($catSlug) ?>" class="g-cta-btn g-cta-btn-secondary">看全台<?= h($catName) ?> →</a>
  </div>
</section>

<!-- ═══ B 端 banner ═══ -->
<div class="g-banner-wrap">
  <div class="g-banner">
    <div class="g-banner-bg"></div>
    <div class="g-banner-text">
      <div class="g-banner-eyebrow">B2B Partnership</div>
      <h3 class="g-banner-title"><?= h($cityShort) ?>做<?= h($catName) ?>？想被更多在地客人找到？</h3>
      <p class="g-banner-desc">店家好口碑業務團隊到店服務，零學習成本上架。</p>
    </div>
    <a href="<?= BASE_URL ?>/" class="g-banner-btn">立即聯絡</a>
  </div>
</div>

<!-- ═══ 大 CTA ═══ -->
<section class="g-cta">
  <div class="g-cta-inner">
    <div class="g-cta-eyebrow">Make it Yours</div>
    <h2 class="g-cta-title">讓<?= h($cityShort) ?>找<?= h($catName) ?>的客人，<br>找到對的你。</h2>
    <p class="g-cta-desc">店家好口碑專注在地口碑曝光 — 月費 NT$300 起，業務團隊到店服務。</p>
    <div class="g-cta-btns">
      <a href="<?= BASE_URL ?>/" class="g-cta-btn g-cta-btn-primary">立即聯絡</a>
      <a href="<?= BASE_URL ?>/category.php" class="g-cta-btn g-cta-btn-secondary">了解合作</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
