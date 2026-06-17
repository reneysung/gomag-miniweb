<?php
// citycat.php  ─  城市×產業交叉頁 /city/{city}/{cat}（落地順序第 3 步）
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();
$slug    = strtolower(trim($_GET['slug'] ?? ''));
$catSlug = strtolower(trim($_GET['cat']  ?? ''));
$svcSlug = strtolower(trim($_GET['svc']  ?? ''));   // 子服務（空=大分類樞紐頁）

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
$isSub   = ($svcSlug !== '');

// ─── 該大分類底下的子服務（有內容的子頁；給樞紐頁列出 + 兄弟互鏈）──
$subServices = [];
try {
    $ss = $db->prepare("SELECT service_slug, service_name, meta_title FROM geo_category_pages
        WHERE city_slug = ? AND category_id = ? AND service_slug <> '' AND is_active = 1
        ORDER BY service_slug");
    $ss->execute([$slug, $catId]);
    $subServices = $ss->fetchAll();
} catch (\Throwable $e) { $subServices = []; }
$hasSub = count($subServices) > 0;

// ─── 本頁交叉頁內容（樞紐：service_slug=''；子服務：service_slug=svc）──
$geo = null;
try {
    $g = $db->prepare("SELECT intro_html, faqs, hero_image, meta_title, meta_desc, service_name
        FROM geo_category_pages WHERE city_slug = ? AND category_id = ? AND service_slug = ? AND is_active = 1 LIMIT 1");
    $g->execute([$slug, $catId, $svcSlug]);
    $geo = $g->fetch() ?: null;
} catch (\Throwable $e) {
    $geo = null;  // 表還沒建時不致命
}
$geoFaqs = $geo && !empty($geo['faqs']) ? (json_decode($geo['faqs'], true) ?: []) : [];
$hasContent = $geo && (trim((string)$geo['intro_html']) !== '' || !empty($geoFaqs));

// 子服務頁必須要有內容列才存在，否則 404
if ($isSub && !$geo) {
    http_response_code(404);
    die("「{$cityName}{$catName}」底下查無此服務");
}

// 本頁主標籤：子服務頁=子服務名（清潔）；樞紐頁=大分類名（居家服務）
$svcName   = $isSub ? (($geo['service_name'] ?? '') ?: $svcSlug) : '';
$pageLabel = $isSub ? $svcName : $catName;

// ─── 店家清單（排除重複客戶）────────────────────────────
//   樞紐頁：以「大分類」列店；子服務頁：以「服務關鍵字標籤」列店（含同義 page_slug 折入）
$dupSkip = getDuplicateSkipSlugs();
$dupPh   = implode(',', array_fill(0, count($dupSkip), '?'));
$cols = "cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline,
         cl.has_minisite, cl.external_website_url, cl.hero_image_path, cl.hero_image_fit,
         cl.address, cl.phone, cl.is_placeholder";
$clients = [];
if ($isSub) {
    // 子服務頁：列出該城、有標到「effective page = svc」關鍵字的店
    try {
        // 列店條件：本城地址店（city_slug 相符）OR 在本城有啟用的城市行銷頁變體
        // （公司地址只在一城，但跨縣行銷頁要在該城的關鍵字頁被列出）
        $sql = "
            SELECT DISTINCT $cols,
                   (ccp.client_id IS NOT NULL) AS via_variant
            FROM clients cl
            JOIN client_service_keywords csk ON csk.client_id = cl.id
            JOIN service_keywords sk ON sk.id = csk.service_keyword_id
                 AND sk.category_id = ? AND sk.is_active = 1
            LEFT JOIN client_city_pages ccp
                 ON ccp.client_id = cl.id AND ccp.city_slug = ? AND ccp.is_active = 1
            WHERE cl.is_active = 1
              AND (cl.city_slug = ? OR ccp.client_id IS NOT NULL)
              AND COALESCE(NULLIF(sk.page_slug, ''), sk.slug) = ?
              AND cl.slug NOT IN ($dupPh)
            ORDER BY cl.is_placeholder ASC, cl.id DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge([$catId, $slug, $slug, $svcSlug], $dupSkip));
        $clients = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $clients = [];  // 關鍵字表未建時不致命（內容頁仍可渲染）
    }
} else {
    // 樞紐頁：整個大分類
    $sql = "
        SELECT $cols
        FROM clients cl
        WHERE cl.is_active = 1 AND cl.category_id = ? AND cl.city_slug = ?
          AND cl.slug NOT IN ($dupPh)
        ORDER BY cl.is_placeholder ASC, cl.id DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$catId, $slug], $dupSkip));
    $clients = $stmt->fetchAll();
}

$totalStores = count($clients);
$realCount   = count(array_filter($clients, fn($c) => empty($c['is_placeholder'])));

// ─── 上架閘門（IA 文件核心）────────────────────────────────
//   render：有內容 或 ≥1 店 或（樞紐頁）有子服務，否則 404
//   index ：有內容 或 ≥3 真實店 或（樞紐頁）有子服務，否則 noindex,follow
if (!$hasContent && $totalStores === 0 && !($isSub === false && $hasSub)) {
    http_response_code(404);
    die("「{$cityName}{$pageLabel}」目前尚無收錄");
}
$indexable = $hasContent || $realCount >= 3 || ($isSub === false && $hasSub);
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

// ─── 本頁 URL（local/staging 用 query；prod 用 pretty）────────
$catPath  = '/city/' . urlencode($slug) . '/' . urlencode($catSlug);
$selfPath = $catPath . ($isSub ? '/' . urlencode($svcSlug) : '');
$catUrl   = (IS_LOCAL || IS_STAGING)
    ? BASE_URL . '/citycat.php?slug=' . urlencode($slug) . '&cat=' . urlencode($catSlug)
    : 'https://www.gomag.com.tw' . $catPath;

// ─── SEO ─────────────────────────────────────────────────
// 家數進 title：只有「真實店家數」≥ 門檻才顯示，少於門檻不自曝短（避免「1 家」之類弱標題、數字變動 SEO 不穩）
$titleMinCount = 5;                       // ← 要調門檻改這裡
$showCount     = $realCount >= $titleMinCount;
if (!empty($geo['meta_title'])) {
    $pageTitle = $geo['meta_title'] . ($showCount ? "｜共 {$realCount} 家" : '');
} else {
    $pageTitle = $showCount
        ? "{$cityName}{$pageLabel}推薦｜{$realCount} 家在地口碑商家"
        : "{$cityName}{$pageLabel}推薦｜在地口碑商家";
}
$metaDesc = !empty($geo['meta_desc'])
    ? $geo['meta_desc']
    : "{$cityName}{$pageLabel}店家精選：在地口碑名單、評價、營業資訊一次看。" . ($showCount ? "共收錄 {$realCount} 家。" : "");
$canonical = (IS_LOCAL || IS_STAGING)
    ? BASE_URL . '/citycat.php?slug=' . urlencode($slug) . '&cat=' . urlencode($catSlug) . ($isSub ? '&svc=' . urlencode($svcSlug) : '')
    : 'https://www.gomag.com.tw' . $selfPath;
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
$bcItems = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => $base . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => '縣市瀏覽', 'item' => $base . '/city'],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $cityName, 'item' => $base . '/city/' . $slug],
];
if ($isSub) {
    $bcItems[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $catName, 'item' => $base . $catPath];
    $bcItems[] = ['@type' => 'ListItem', 'position' => 5, 'name' => $svcName];
} else {
    $bcItems[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $catName];
}
$breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $bcItems];
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
      <span><?= h($catIcon) ?> <?= h($cityName) ?>・<?= h($catName) ?><?= $isSub ? '・' . h($svcName) : '' ?></span>
    </div>
    <h1 class="g-hero-title">
      <?= h($cityShort) ?><?= h($pageLabel) ?>，<br>找一家<span>對的店</span>。
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
    <?php if ($isSub): ?>
    <a href="<?= BASE_URL ?>/citycat.php?slug=<?= h($slug) ?>&cat=<?= h($catSlug) ?>"><?= h($catIcon) ?> <?= h($catName) ?></a>
    <span class="g-breadcrumb-sep">›</span>
    <span class="current"><?= h($svcName) ?></span>
    <?php else: ?>
    <span class="current"><?= h($catIcon) ?> <?= h($catName) ?></span>
    <?php endif; ?>
  </nav>
</div>

<!-- ═══ 在地內文（有才顯示）═══ -->
<?php if ($geo && trim((string)$geo['intro_html']) !== ''): ?>
<section class="g-city-intro">
  <div class="g-city-intro-text-block">
    <div class="g-city-intro-eyebrow">Explore <?= h($cityName) ?> · <?= h($pageLabel) ?></div>
    <h2 class="g-city-intro-title"><?= h($cityName) ?><?= h($pageLabel) ?>怎麼挑？</h2>
    <div class="g-city-intro-text"><?= $geo['intro_html'] /* admin 受信任 HTML */ ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ═══ 子服務（樞紐頁列出 / 子服務頁互鏈兄弟）═══ -->
<?php
// 樞紐頁：列全部；子服務頁：列兄弟（排除自己）
$navServices = $isSub
    ? array_values(array_filter($subServices, fn($s) => $s['service_slug'] !== $svcSlug))
    : $subServices;
if ($navServices):
?>
<section class="g-section" style="padding-bottom:0;">
  <div class="g-section-head">
    <div><h2 class="g-section-title">🧭 <?= $isSub ? '其他服務' : '熱門服務' ?></h2></div>
  </div>
  <div style="display:flex; flex-wrap:wrap; gap:10px;">
    <?php foreach ($navServices as $sv):
        $svUrl = (IS_LOCAL || IS_STAGING)
            ? BASE_URL . '/citycat.php?slug=' . urlencode($slug) . '&cat=' . urlencode($catSlug) . '&svc=' . urlencode($sv['service_slug'])
            : 'https://www.gomag.com.tw/city/' . urlencode($slug) . '/' . urlencode($catSlug) . '/' . urlencode($sv['service_slug']);
        $svLbl = $sv['service_name'] !== '' ? ($cityShort . $sv['service_name']) : $sv['service_slug'];
    ?>
    <a href="<?= h($svUrl) ?>" class="g-city-meta-tag" style="background:var(--g-accent-light); color:var(--g-accent); border-color:var(--g-accent-light); text-decoration:none;"><?= h($svLbl) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══ 店家清單 ═══ -->
<?php if ($totalStores > 0): ?>
<section class="g-section g-cat-anchor">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($catIcon) ?> <?= h($cityName) ?><?= h($pageLabel) ?>店家 <span class="g-section-title-meta">（<?= $totalStores ?> 家）</span></h2>
    </div>
    <a href="<?= BASE_URL ?>/category.php?slug=<?= h($catSlug) ?>" class="g-section-link">看全台<?= h($catName) ?></a>
  </div>

  <div class="g-store-grid">
    <?php foreach ($clients as $cl):
        $cHero = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
        $isPH = !empty($cl['is_placeholder']);
        $cardClass = 'g-store-card' . ($isPH ? ' g-store-card-ph' : '');
    ?>
    <a class="<?= $cardClass ?>" href="<?= h(clientStoreUrl($cl, !empty($cl['via_variant']) ? $slug : '')) ?>">
      <div class="g-store-img"<?= gStoreImgStyle($cHero, $cl['hero_image_fit'] ?? null) ?>>
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
<?php else: ?>
<!-- 0 店空狀態：有內容但本區尚無收錄商家 → B2B 歡迎上架（隨有沒有店自動切換）-->
<section class="g-section g-cat-anchor">
  <div class="g-section-head"><div><h2 class="g-section-title"><?= h($catIcon) ?> <?= h($cityName) ?><?= h($pageLabel) ?>店家</h2></div></div>
  <div style="padding:32px 24px; border:1px dashed var(--g-border); border-radius:14px; text-align:center; background:var(--g-bg-alt);">
    <p style="margin:0 0 16px; color:var(--g-ink-soft);">本區<?= h($pageLabel) ?>商家陸續收錄中。你是在地<?= h($pageLabel) ?>業者嗎？</p>
    <a href="<?= BASE_URL ?>/" class="g-cta-btn g-cta-btn-primary">免費上架，被更多<?= h($cityShort) ?>客人找到 →</a>
  </div>
</section>
<?php endif; ?>

<!-- ═══ FAQ（有才顯示）═══ -->
<?php if ($geoFaqs): ?>
<section class="g-section">
  <div class="g-section-head"><div><h2 class="g-section-title">❓ <?= h($cityName) ?><?= h($pageLabel) ?>常見問題</h2></div></div>
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
    // 攻略需「分類相符（或通用）」且「城市相符（或通用）」，避免跨分類/跨城外溢；城市專屬優先
    // 城市頁攻略只放「本城在地文」（不用全台通用 backfill；全台文仍是獨立 /guide/ 頁）。
    // 子服務頁：該子服務攻略優先，不足 4 篇時撈「同家族」文補滿（如冷氣清洗/裝潢細清同屬
    // 清潔家族 → 撈清潔文補）。$guideFamily: key=子服務 slug, value=補位來源池。樞紐頁不過濾。
    $guideFamily = ['reno-detail' => 'cleaning', 'aircon-clean' => 'cleaning'];
    $gSql = "SELECT slug, title, excerpt, cover_image FROM guides
        WHERE status='published' AND (category_id = ? OR category_id IS NULL) AND city_slug = ?";
    $gParams = [$catId, $slug];
    if ($isSub) {
        $famPool = $guideFamily[$svcSlug] ?? '';
        if ($famPool !== '') {
            $gSql .= " AND (FIND_IN_SET(?, service_slug) > 0 OR FIND_IN_SET(?, service_slug) > 0)
                       ORDER BY (FIND_IN_SET(?, service_slug) > 0) DESC, published_at DESC, id DESC LIMIT 4";
            $gParams[] = $svcSlug; $gParams[] = $famPool; $gParams[] = $svcSlug;
        } else {
            $gSql .= " AND FIND_IN_SET(?, service_slug) > 0 ORDER BY published_at DESC, id DESC LIMIT 4";
            $gParams[] = $svcSlug;
        }
    } else {
        $gSql .= " ORDER BY published_at DESC, id DESC LIMIT 4";
    }
    $rg = $db->prepare($gSql);
    $rg->execute($gParams);
    $relGuides = $rg->fetchAll();
} catch (\Throwable $e) { $relGuides = []; }
if ($relGuides):
?>
<section class="g-section">
  <div class="g-section-head"><div><h2 class="g-section-title">📖 <?= h($cityShort) ?><?= h($pageLabel) ?>攻略</h2></div></div>
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
    <?php if ($isSub): ?>
    <a href="<?= BASE_URL ?>/citycat.php?slug=<?= h($slug) ?>&cat=<?= h($catSlug) ?>" class="g-cta-btn g-cta-btn-secondary">← 回<?= h($cityName) ?><?= h($catName) ?></a>
    <?php endif; ?>
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
      <h3 class="g-banner-title"><?= h($cityShort) ?>做<?= h($pageLabel) ?>？想被更多在地客人找到？</h3>
      <p class="g-banner-desc">店家好口碑業務團隊到店服務，零學習成本上架。</p>
    </div>
    <a href="<?= BASE_URL ?>/" class="g-banner-btn">立即聯絡</a>
  </div>
</div>

<!-- ═══ 大 CTA ═══ -->
<section class="g-cta">
  <div class="g-cta-inner">
    <div class="g-cta-eyebrow">Make it Yours</div>
    <h2 class="g-cta-title">讓<?= h($cityShort) ?>找<?= h($pageLabel) ?>的客人，<br>找到對的你。</h2>
    <p class="g-cta-desc">店家好口碑專注在地口碑曝光 — 月費 NT$300 起，業務團隊到店服務。</p>
    <div class="g-cta-btns">
      <a href="<?= BASE_URL ?>/" class="g-cta-btn g-cta-btn-primary">立即聯絡</a>
      <a href="<?= BASE_URL ?>/category.php" class="g-cta-btn g-cta-btn-secondary">了解合作</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
