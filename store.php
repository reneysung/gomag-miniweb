<?php
// store.php  ─  主站店家行銷頁 /store?sub=xusen
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/block_helpers.php';
require_once __DIR__ . '/includes/front_functions.php';  // getCityMap()

$db = getDB();
$sub = strtolower(trim($_GET['sub'] ?? $_GET['store'] ?? ''));
if (!$sub || !preg_match('/^[a-z0-9_-]+$/', $sub)) {
    http_response_code(404);
    die('找不到店家');
}

// ─── 舊 slug 301 永久轉址（保住 SEO 權重）──────────
// 比對時 $sub 已 lowercase，所以 key 全部用小寫
$slug_redirects = [
    'docaroating' => 'docar',  // 鍍卡 Do Car：拼錯修正
    // ── 重複資料合併（同店多筆）→ 301 到主檔（2026-05-16）──
    'interiordesign72'   => 'artru',                // 亞筑室內設計（id=99 → 213）
    'interiordesign214'  => 'lanhung',              // 聯漢室內設計（id=124 → 214）
    'modifiedcars3'      => 'modifiedcars',         // 光點線專業汽車大燈（id=195 → 46）
    'gourmetrestaurant1' => 'gourmetrestaurant2',   // 來道好食雞（id=76 → 145）
    '065957487'          => '062263168',            // 二鍋壽喜燒（id=90 → 13）
    'cleaningcompany5'   => 'sanfengclean',         // 三峰清潔公司（id=197 → 218）
];
if (isset($slug_redirects[$sub])) {
    $newSub = $slug_redirects[$sub];
    $newUrl = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/store.php?sub=' . urlencode($newSub) : 'https://www.gomag.com.tw/store/' . urlencode($newSub);
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $newUrl);
    exit;
}

// 撈店家完整資料 + 分類
$stmt = $db->prepare("
    SELECT cl.*, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
    FROM clients cl
    LEFT JOIN categories c ON cl.category_id = c.id
    WHERE (cl.subdomain = ? OR cl.slug = ?) AND cl.is_active = 1
    LIMIT 1
");
$stmt->execute([$sub, $sub]);
$client = $stmt->fetch();

if (!$client) {
    http_response_code(404);
    die('店家不存在或已停用');
}

$cid = (int)$client['id'];
$isPlaceholder = !empty($client['is_placeholder']);

// 客戶社群（FB/IG/LINE/YT）— 給 hero/aside icon row 用
$_sStmt = $db->prepare("SELECT * FROM client_social WHERE client_id=? LIMIT 1");
$_sStmt->execute([$cid]);
$clientSocial = $_sStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// ─── Modular Blocks 雙寫期判斷 ─────────────────────────
//  - 該 client 已有 store_blocks → 用新系統 render，舊 services/cases 變數清空
//  - 沒有 store_blocks → 走舊邏輯（向後相容）
$useBlocks = !$isPlaceholder && clientHasBlocks($cid);

// 類似店家（同分類，隨機 4 家，排除自己）
$similarStores = [];
if ($client['category_id']) {
    $sim = $db->prepare("
        SELECT cl.id, cl.slug, cl.subdomain, cl.brand_name, cl.tagline,
               cl.hero_image_path, cl.address, c.icon AS cat_icon
        FROM clients cl
        LEFT JOIN categories c ON cl.category_id = c.id
        WHERE cl.is_active = 1
          AND COALESCE(cl.is_placeholder, 0) = 0
          AND cl.category_id = ?
          AND cl.id != ?
        ORDER BY RAND()
        LIMIT 4
    ");
    $sim->execute([$client['category_id'], $cid]);
    $similarStores = $sim->fetchAll();
}

// Placeholder 客戶不抓 services/cases/testimonials/Google 評價
if ($isPlaceholder) {
    $services = $cases = $testimonials = [];
    $rating = ['avg' => 0, 'cnt' => 0];
    $googleReviews = null;
} elseif ($useBlocks) {
    // 新系統：清空舊變數（block 區塊由 renderStoreBlocks() 負責），僅保留 testimonials/評價
    $services = $cases = [];

    $testimonials = $db->prepare("SELECT * FROM testimonials WHERE client_id=? AND is_active=1 AND COALESCE(source,'') <> 'demo' ORDER BY sort_order, id LIMIT 3");
    $testimonials->execute([$cid]);
    $testimonials = $testimonials->fetchAll();

    $avgRating = $db->prepare("SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM testimonials WHERE client_id=? AND is_active=1 AND COALESCE(source,'') <> 'demo'");
    $avgRating->execute([$cid]);
    $rating = $avgRating->fetch();

    // 行銷頁不顯示 Google 評價（API 成本 + 需逐家 place_id + 無法濾掉負評）— 改用站內 testimonials
    $googleReviews = null;
} else {
    $services = $db->prepare("SELECT * FROM services WHERE client_id=? AND is_active=1 ORDER BY sort_order, id LIMIT 6");
    $services->execute([$cid]);
    $services = $services->fetchAll();

    $cases = $db->prepare("SELECT * FROM cases WHERE client_id=? AND is_active=1 ORDER BY is_featured DESC, sort_order LIMIT 3");
    $cases->execute([$cid]);
    $cases = $cases->fetchAll();

    $testimonials = $db->prepare("SELECT * FROM testimonials WHERE client_id=? AND is_active=1 AND COALESCE(source,'') <> 'demo' ORDER BY sort_order, id LIMIT 3");
    $testimonials->execute([$cid]);
    $testimonials = $testimonials->fetchAll();

    // 計算評價平均
    $avgRating = $db->prepare("SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM testimonials WHERE client_id=? AND is_active=1 AND COALESCE(source,'') <> 'demo'");
    $avgRating->execute([$cid]);
    $rating = $avgRating->fetch();

    // 行銷頁不顯示 Google 評價（API 成本 + 需逐家 place_id + 無法濾掉負評）— 改用站內 testimonials
    $googleReviews = null;
}

// 主 CTA URL：mini-site > 外部官網 > none
$miniSiteUrl = null;
if ($client['has_minisite']) {
    $miniSiteUrl = (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/site/index.php?sub=' . urlencode($client['subdomain'] ?? $client['slug'])
        : 'https://' . ($client['subdomain'] ?? $client['slug']) . '.' . MINISITE_DOMAIN . '/';
}

// ── 城市行銷頁變體 /store/{slug}/{city} ──
// 同一個客戶可以開多個城市行銷頁（如亞雷台中／亞雷彰化），各自獨立 SEO 文案、案例篩選。
// 變體 row 缺欄位則 fallback 主檔，無變體則 404。
$cityVariant = null;
$hideShared  = false;  // 城市變體頁勾「只顯示自寫內容」時，藏服務項目以下的共用區塊
$citySlug = strtolower(trim($_GET['city'] ?? ''));
if ($citySlug !== '') {
    $cvStmt = $db->prepare("SELECT * FROM client_city_pages WHERE client_id=? AND city_slug=? AND is_active=1 LIMIT 1");
    $cvStmt->execute([(int)$client['id'], $citySlug]);
    $cityVariant = $cvStmt->fetch(PDO::FETCH_ASSOC);
    if (!$cityVariant) {
        http_response_code(404);
        die('找不到該城市的行銷頁');
    }
    foreach (['brand_name','store_meta_title','store_meta_desc','store_keywords','store_og_image','hero_image_path','landing_extra_content'] as $_f) {
        if (!empty($cityVariant[$_f])) $client[$_f] = $cityVariant[$_f];
    }
    $hideShared = !empty($cityVariant['hide_shared_sections']);
    // 依城市篩案例（location 前綴對 city_slug）
    if (!empty($cityVariant['filter_cases_by_region'])) {
        require_once __DIR__ . '/includes/front_functions.php';
        $_cs = $db->prepare("SELECT * FROM cases WHERE client_id=? AND is_active=1 ORDER BY is_featured DESC, sort_order LIMIT 50");
        $_cs->execute([(int)$client['id']]);
        $_all = $_cs->fetchAll(PDO::FETCH_ASSOC);
        $cases = array_slice(array_values(array_filter($_all, fn($c) => caseRegionSlug($c['location'] ?? '') === $citySlug)), 0, 3);
    }
}

// SEO：客戶自定 > 自動產生
// 行銷頁 SEO 預設：有小官網的客戶 → 行銷頁走「口碑/評價」角度，與小官網(品牌/服務)錯開，避免兩頁互搶排名(cannibalization)
// 城市名稱（依 client.city_slug 從 cities 表抓；無 → fallback 台南）
$_cityName = '台南';
if (!empty($client['city_slug'])) {
    $_cm = getCityMap();
    if (isset($_cm[$client['city_slug']])) $_cityName = $_cm[$client['city_slug']];
}
if (!empty($client['store_meta_title'])) {
    $pageTitle = $client['store_meta_title'];
} elseif (!empty($client['has_minisite'])) {
    $pageTitle = $client['brand_name'] . '評價・口碑推薦｜真實客戶評論' . ($client['cat_name'] ? '・' . $client['cat_name'] : '');
} else {
    $pageTitle = $client['brand_name'] . '｜' . ($client['tagline'] ?? $_cityName . ($client['cat_name'] ?? '') . '店家');
}
if (!empty($client['store_meta_desc'])) {
    $metaDesc = $client['store_meta_desc'];
} elseif (!empty($client['has_minisite'])) {
    $metaDesc = $client['brand_name'] . '的真實評價與客戶口碑整理：服務心得、Google 評論與推薦，看實際口碑再決定。';
} else {
    $metaDesc = $client['about_text']
        ? mb_strimwidth(strip_tags($client['about_text']), 0, 150, '…')
        : "{$_cityName}{$client['cat_name']}店家：{$client['brand_name']}";
}
$metaKeywords = !empty($client['store_keywords']) ? $client['store_keywords'] : '';
$ogImage = !empty($client['store_og_image'])
    ? (str_starts_with($client['store_og_image'], 'http') ? $client['store_og_image'] : BASE_URL . '/' . $client['store_og_image'])
    : ($client['hero_image_path'] ? BASE_URL . '/' . $client['hero_image_path'] : '');
$canonical = IS_LOCAL
    ? BASE_URL . '/store.php?sub=' . urlencode($sub) . ($cityVariant ? '&city=' . urlencode($citySlug) : '')
    : 'https://www.gomag.com.tw/store/' . urlencode($sub) . ($cityVariant ? '/' . urlencode($citySlug) : '');

// Placeholder（資料整理中）為薄頁 → noindex，但保留 follow 讓內連權重續流
if ($isPlaceholder) {
    $metaRobots = 'noindex,follow';
}

// 如果用新 blocks 系統，載 gomag.css 樣式
if ($useBlocks) {
    $extraCss = [BASE_URL . '/assets/css/gomag.css'];
}

// ─── 視覺模板系統 Phase 3（opt-in，default 完全不影響）──
require_once __DIR__ . '/includes/template_loader.php';
$_tplSlug = $client['store_template'] ?? '_default';
if ($_tplSlug !== '_default' && is_file(__DIR__ . "/templates/store/{$_tplSlug}/theme.css")) {
    $extraCss[] = BASE_URL . "/templates/store/{$_tplSlug}/theme.css?v=" . filemtime(__DIR__ . "/templates/store/{$_tplSlug}/theme.css");
}

require_once __DIR__ . '/main/layout_head.php';

// ─── 多縣市方案：撈該客戶所有啟用城市（給 schema areaServed + 底部互鏈用）────
$cityVariantList = [];
try {
    $cvListStmt = $db->prepare("SELECT city_slug, city_label FROM client_city_pages
        WHERE client_id=? AND is_active=1 ORDER BY sort_order, id");
    $cvListStmt->execute([(int)$client['id']]);
    $cityVariantList = $cvListStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) { /* 表未建時略過 */ }

// ─── Schema.org LocalBusiness JSON-LD ────────────
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $client['brand_name'],
    'url' => $canonical,
];
if (!empty($client['tagline'])) $jsonLd['description'] = $client['tagline'];
elseif (!empty($metaDesc)) $jsonLd['description'] = $metaDesc;
if ($ogImage) $jsonLd['image'] = $ogImage;
if (!empty($client['phone'])) $jsonLd['telephone'] = $client['phone'];
if (!empty($client['email'])) $jsonLd['email'] = $client['email'];
if (!empty($client['address'])) {
    // 從地址自動抓城市（嘉義市/嘉義縣/台南市/高雄市/...），抓不到 fallback 台南市
    $locality = '台南市';
    if (preg_match('/([台臺][北中南東]市|新北市|桃園市|高雄市|嘉義[市縣]|新竹[市縣]|苗栗[市縣]|彰化縣|南投縣|雲林縣|屏東縣|宜蘭縣|花蓮縣|台東縣|澎湖縣|金門縣|連江縣|基隆市)/u', $client['address'], $m)) {
        $locality = str_replace('臺', '台', $m[1]);
    }
    $jsonLd['address'] = [
        '@type' => 'PostalAddress',
        'streetAddress' => $client['address'],
        'addressLocality' => $locality,
        'addressCountry' => 'TW',
    ];
}
// 不輸出 aggregateRating：行銷頁評價為站內 testimonials（店家自填），對 Google 宣稱星等屬 self-serving，故不放 schema

// areaServed：城市變體頁=該城市；主行銷頁=所有啟用城市列表
if ($cityVariant) {
    $jsonLd['areaServed'] = [
        '@type' => 'City',
        'name'  => $cityVariant['city_label'] ?? $citySlug,
    ];
} elseif ($cityVariantList) {
    $jsonLd['areaServed'] = array_map(fn($c) => [
        '@type' => 'City',
        'name'  => $c['city_label'],
    ], $cityVariantList);
}

if (!empty($client['updated_at'])) {
    $jsonLd['dateModified'] = date('c', strtotime($client['updated_at']));
}
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php
// ─── 視覺模板系統 Phase 3：若客戶選了非 _default 模板且模板有 render.php，delegate 過去 ──
// 注意：必須直接 require（不能透過 function），否則 $client/$blocks/$services 等變數會被 function scope 吃掉
$_tplRender = ($_tplSlug !== '_default') ? templateRenderPath($_tplSlug) : '';
if ($_tplRender) {
    require $_tplRender;
    require_once __DIR__ . '/main/layout_foot.php';
    exit;
}
?>

<!-- ═══════ Breadcrumb ═══════ -->
<?php
// 從 address 抓縣市供麵包屑用
$_breadcrumbCity = null;
$_breadcrumbCitySlug = null;
$_cityNameToSlug = array_flip(getCityMap());  // 唯一來源：cities 表
if (!empty($client['address']) && preg_match('/^([台臺][北中南東]市|新北市|桃園市|高雄市|嘉義[市縣]|新竹[市縣]|苗栗縣|彰化縣|南投縣|雲林縣|屏東縣|宜蘭縣|花蓮縣|台東縣|基隆市)/u', $client['address'], $_m)) {
    $_breadcrumbCity = str_replace('臺', '台', $_m[1]);
    $_breadcrumbCitySlug = $_cityNameToSlug[$_breadcrumbCity] ?? null;
}
?>
<div class="m-container">
  <div class="m-breadcrumb">
    <a href="<?= BASE_URL ?>/">首頁</a>
    <span class="sep">›</span>
    <?php if ($_breadcrumbCity && $_breadcrumbCitySlug): ?>
    <a href="<?= BASE_URL ?>/city.php?slug=<?= h($_breadcrumbCitySlug) ?>">📍 <?= h($_breadcrumbCity) ?></a>
    <span class="sep">›</span>
    <?php endif; ?>
    <?php if ($client['cat_slug']): ?>
    <a href="<?= BASE_URL ?>/category.php?slug=<?= h($client['cat_slug']) ?>"><?= h($client['cat_icon']) ?> <?= h($client['cat_name']) ?></a>
    <span class="sep">›</span>
    <?php endif; ?>
    <span style="color:var(--m-primary); font-weight:600;"><?= h($client['brand_name']) ?></span>
  </div>
</div>

<?php $heroStats = !empty($client['hero_stats']) ? json_decode($client['hero_stats'], true) : []; ?>

<?php
// ─── 頁面頂部 banner（top_banner_html）— 有設定且未過期才顯示 ──
$showTopBanner = !empty($client['top_banner_html'] ?? '');
if ($showTopBanner && !empty($client['top_banner_until'])) {
    $showTopBanner = strtotime($client['top_banner_until']) >= strtotime(date('Y-m-d'));
}
if ($showTopBanner):
?>
<style>
.g-top-banner{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:14px;
  padding:12px 20px;background:linear-gradient(90deg,#FF5A36 0%,#FF8155 100%);color:#fff;
  font-size:.95rem;line-height:1.5;text-align:center}
.g-top-banner-tag{font-weight:700;background:rgba(255,255,255,.18);padding:4px 10px;
  border-radius:999px;font-size:.85rem;white-space:nowrap}
.g-top-banner-text{flex:1 1 auto;min-width:200px}
.g-top-banner-text strong{font-weight:800}
.g-top-banner-cta{display:inline-block;background:#fff;color:#FF5A36;font-weight:800;
  padding:6px 16px;border-radius:999px;text-decoration:none;white-space:nowrap;
  transition:transform .15s}
.g-top-banner-cta:hover{transform:translateX(2px);color:#FF5A36}
@media(max-width:640px){.g-top-banner{font-size:.88rem;padding:10px 14px;gap:8px}}
</style>
<?= $client['top_banner_html'] ?>
<?php endif; ?>

<?php if ($useBlocks): ?>
<!-- ═══════ Store Hero（gomag 新樣式 — 僅 blocks 啟用客戶）═══════ -->
<section class="g-store-hero">
  <div class="g-store-hero-inner">
    <div>
      <?php if ($client['cat_name']): ?>
      <a href="<?= BASE_URL ?>/category.php?slug=<?= h($client['cat_slug']) ?>" class="g-store-hero-cat-pill">
        <?= h($client['cat_icon']) ?> <?= h($client['cat_name']) ?>
      </a>
      <?php endif; ?>

      <h1 class="g-store-hero-title"><?= h($client['brand_name']) ?></h1>

      <?php if ($isPlaceholder): ?>
      <div class="g-store-hero-ph-banner">📋 資料整理中　・　完整介紹即將上線</div>
      <?php endif; ?>

      <?php if ($client['tagline']): ?>
      <p class="g-store-hero-tagline"><?= h($client['tagline']) ?></p>
      <?php endif; ?>

      <?php
      // 城市變體頁的「服務關鍵字膠囊 row」— 顯眼告訴搜尋者該頁主打哪些關鍵字
      $_serviceTags = [];
      if ($cityVariant && !empty($cityVariant['service_tags'])) {
          $_serviceTags = json_decode($cityVariant['service_tags'], true) ?: [];
      }
      ?>
      <?php if ($_serviceTags): ?>
      <div class="g-store-hero-svc-tags" style="display:flex; flex-wrap:wrap; gap:8px; margin:14px 0;">
        <?php foreach ($_serviceTags as $tag): ?>
        <span style="display:inline-block; padding:6px 14px; background:rgba(255,90,54,.12); color:#FF5A36; border:1.5px solid rgba(255,90,54,.35); border-radius:999px; font-size:.88rem; font-weight:700;">
          <?= h($tag) ?>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($rating['cnt'] > 0): ?>
      <div class="g-store-hero-rating">
        <span class="g-store-hero-rating-stars">
          <?= str_repeat('★', round($rating['avg'])) ?><?= str_repeat('☆', 5 - round($rating['avg'])) ?>
        </span>
        <span class="g-store-hero-rating-text">
          <?= number_format($rating['avg'], 1) ?> · <?= $rating['cnt'] ?> 則評價
        </span>
      </div>
      <?php endif; ?>

      <div class="g-store-hero-ctas">
        <?php if ($client['phone']): ?>
        <a href="tel:<?= h($client['phone']) ?>" class="g-store-btn g-store-btn-primary">📞 撥打電話</a>
        <?php endif; ?>
        <?php if (!empty($client['mobile_phone'])): ?>
        <a href="tel:<?= h($client['mobile_phone']) ?>" class="g-store-btn g-store-btn-primary">📱 撥打手機</a>
        <?php endif; ?>
        <?php if ($miniSiteUrl): ?>
        <a href="<?= h($miniSiteUrl) ?>" class="g-store-btn g-store-btn-secondary" target="_blank">🌐 查看完整官網</a>
        <?php endif; ?>
        <?php if ($client['external_website_url']): ?>
        <a href="<?= h($client['external_website_url']) ?>" class="g-store-btn g-store-btn-outline" target="_blank" rel="noopener">🔗 官方網站</a>
        <?php endif; ?>
        <?php if (!empty($client['order_url'])): ?>
        <a href="<?= h($client['order_url']) ?>" class="g-store-btn g-store-btn-outline" target="_blank" rel="noopener">🍱 線上訂購</a>
        <?php endif; ?>
      </div>

      <?php if ($heroStats && is_array($heroStats)): ?>
      <div class="g-store-hero-stats">
        <?php foreach ($heroStats as $st): ?>
        <div class="g-store-hero-stat">
          <div class="g-store-hero-stat-value"><?= h($st['value'] ?? '') ?></div>
          <div class="g-store-hero-stat-label"><?= h($st['label'] ?? '') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="g-store-hero-info">
        <?php if ($client['address']): ?><div>📍 <?= h($client['address']) ?></div><?php endif; ?>
        <?php if ($client['phone']): ?><div>📞 <?= h($client['phone']) ?></div><?php endif; ?>
        <?php if ($client['email']): ?><div>✉️ <a href="mailto:<?= h($client['email']) ?>"><?= h($client['email']) ?></a></div><?php endif; ?>
        <?php if ($client['business_hours']): ?><div>🕐 <?= nl2br(h($client['business_hours'])) ?></div><?php endif; ?>
      </div>
    </div>

    <?php $_heroFit = ($client['hero_image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover'; ?>
    <div class="g-store-hero-image"<?= $_heroFit === 'contain' ? ' style="background:#fff"' : '' ?>>
      <?php if ($client['hero_image_path']): ?>
      <img src="<?= BASE_URL ?>/<?= h($client['hero_image_path']) ?>" alt="<?= h($client['brand_name']) ?>" style="object-fit: <?= $_heroFit ?>;<?= $_heroFit === 'contain' ? ' padding:18px;' : '' ?>">
      <?php else: ?>
      <div class="g-store-hero-image-fallback"><?= h($client['cat_icon'] ?? '🏪') ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php else: ?>
<!-- ═══════ Store Hero（舊樣式 — 未啟用 blocks 的客戶）═══════ -->
<?php if (!empty($client['hero_image_path'])): ?>
<!-- 店面圖橫幅（hero_image_path 存在時自動顯示，無則完全不出現） -->
<section style="background:var(--m-bg-alt); padding:0;">
  <div style="max-width:1100px; margin:0 auto; padding:0 20px;">
    <div style="aspect-ratio:16/6; overflow:hidden; border-radius:0 0 16px 16px; background:#efeae6;">
      <img src="<?= BASE_URL ?>/<?= h($client['hero_image_path']) ?>" alt="<?= h($client['brand_name']) ?>" loading="lazy"
           style="width:100%; height:100%; object-fit:cover; display:block;">
    </div>
  </div>
</section>
<?php endif; ?>
<section style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); padding:40px 0;">
  <div class="m-container">
    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:40px; align-items:start;">

      <!-- 左：基本資訊 -->
      <div>
        <?php if ($client['cat_name']): ?>
        <div style="margin-bottom:8px;">
          <a href="<?= BASE_URL ?>/category.php?slug=<?= h($client['cat_slug']) ?>"
             style="display:inline-block; padding:4px 12px; background:var(--m-bg); border-radius:999px; font-size:.8rem; color:var(--m-text-muted);">
            <?= h($client['cat_icon']) ?> <?= h($client['cat_name']) ?>
          </a>
        </div>
        <?php endif; ?>

        <h1 style="font-size:2.2rem; font-weight:900; color:var(--m-primary); margin-bottom:8px;">
          <?= h($client['brand_name']) ?>
        </h1>

        <?php if ($isPlaceholder): ?>
        <div style="display:inline-block; padding:6px 14px; background:#fef3c7; border:1px solid #fbbf24; border-radius:8px; font-size:.9rem; color:#92400e; font-weight:600; margin-bottom:12px;">
          📋 資料整理中　・　完整介紹即將上線
        </div>
        <?php endif; ?>

        <?php if ($client['tagline']): ?>
        <p style="font-size:1.1rem; color:var(--m-text-muted); margin-bottom:16px;">
          <?= h($client['tagline']) ?>
        </p>
        <?php endif; ?>

        <?php
        // 城市變體頁的「服務關鍵字膠囊 row」（m-* fallback hero）
        $_serviceTagsM = [];
        if ($cityVariant && !empty($cityVariant['service_tags'])) {
            $_serviceTagsM = json_decode($cityVariant['service_tags'], true) ?: [];
        }
        ?>
        <?php if ($_serviceTagsM): ?>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
          <?php foreach ($_serviceTagsM as $tag): ?>
          <span style="display:inline-block; padding:6px 14px; background:rgba(255,90,54,.12); color:#FF5A36; border:1.5px solid rgba(255,90,54,.35); border-radius:999px; font-size:.88rem; font-weight:700;">
            <?= h($tag) ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($rating['cnt'] > 0): ?>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
          <span style="color:#fbbf24; font-size:1.1rem;">
            <?= str_repeat('★', round($rating['avg'])) ?><?= str_repeat('☆', 5 - round($rating['avg'])) ?>
          </span>
          <span style="font-size:.9rem; color:var(--m-text-muted);">
            <?= number_format($rating['avg'], 1) ?> ・ <?= $rating['cnt'] ?> 則評價
          </span>
        </div>
        <?php endif; ?>

        <!-- 主 CTA 按鈕 -->
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px;">
          <?php if ($miniSiteUrl): ?>
          <a href="<?= h($miniSiteUrl) ?>" class="m-btn m-btn-primary" target="_blank">
            🌐 查看完整官網
          </a>
          <?php endif; ?>
          <?php if ($client['external_website_url']): ?>
          <a href="<?= h($client['external_website_url']) ?>" class="m-btn m-btn-accent" target="_blank" rel="noopener">
            🔗 前往官方網站
          </a>
          <?php endif; ?>
          <?php if (!empty($client['order_url'])): ?>
          <a href="<?= h($client['order_url']) ?>" class="m-btn m-btn-accent" target="_blank" rel="noopener">
            🍱 線上訂購
          </a>
          <?php endif; ?>
          <?php if ($client['phone']): ?>
          <a href="tel:<?= h($client['phone']) ?>" class="m-btn m-btn-outline">
            📞 撥打電話
          </a>
          <?php endif; ?>
          <?php if (!empty($client['mobile_phone'])): ?>
          <a href="tel:<?= h($client['mobile_phone']) ?>" class="m-btn m-btn-outline">
            📱 撥打手機
          </a>
          <?php endif; ?>
        </div>

        <!-- Hero 統計數字 -->
        <?php if ($heroStats && is_array($heroStats)): ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:14px; margin-bottom:24px; padding:18px; background:var(--m-bg); border-radius:var(--m-radius); border:1px solid var(--m-border);">
          <?php foreach ($heroStats as $st): ?>
          <div style="text-align:center;">
            <div style="font-size:1.8rem; font-weight:900; color:var(--m-primary); line-height:1;"><?= h($st['value'] ?? '') ?></div>
            <div style="font-size:.8rem; color:var(--m-text-muted); margin-top:4px;"><?= h($st['label'] ?? '') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 聯絡與資訊 -->
        <div style="display:grid; gap:8px; font-size:.9rem; color:var(--m-text);">
          <?php if ($client['address']): ?>
          <div>📍 <?= h($client['address']) ?></div>
          <?php endif; ?>
          <?php if ($client['phone']): ?>
          <div>📞 <?= h($client['phone']) ?></div>
          <?php endif; ?>
          <?php if ($client['email']): ?>
          <div>✉️ <a href="mailto:<?= h($client['email']) ?>" style="color:var(--m-primary);"><?= h($client['email']) ?></a></div>
          <?php endif; ?>
          <?php if ($client['business_hours']): ?>
          <div>🕐 <?= nl2br(h($client['business_hours'])) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 右：Hero 圖 -->
      <div>
        <?php if ($client['hero_image_path']): ?>
        <img src="<?= BASE_URL ?>/<?= h($client['hero_image_path']) ?>"
             alt="<?= h($client['brand_name']) ?>"
             style="width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:var(--m-radius); box-shadow:var(--m-shadow);">
        <?php else: ?>
        <div style="aspect-ratio:4/3; background:linear-gradient(135deg, var(--m-bg), var(--m-border)); border-radius:var(--m-radius); display:flex; align-items:center; justify-content:center; font-size:5rem; opacity:.4;">
          <?= h($client['cat_icon'] ?? '🏪') ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>
<?php endif; /* end if useBlocks / else (new vs old hero) */ ?>

<?php
// 解析 photos JSON（gallery 用）
$photos = [];
if ($useBlocks && !empty($client['photos'])) {
    $decoded = json_decode($client['photos'], true);
    if (is_array($decoded)) $photos = array_values(array_filter($decoded));
}
?>

<?php if ($useBlocks && count($photos) >= 1): ?>
<!-- ═══════ Photo Gallery（5 格 Airbnb 樣式）═══════ -->
<section class="g-photo-gallery">
  <div class="g-photo-gallery-grid">
    <?php $idx = 0; foreach (array_slice($photos, 0, 5) as $photo):
      $idx++;
      $url = BASE_URL . '/' . ltrim($photo, '/');
    ?>
    <div class="g-gallery-tile g-gallery-tile-<?= $idx ?>">
      <img src="<?= h($url) ?>" alt="<?= h($client['brand_name']) ?> 照片 <?= $idx ?>" loading="lazy">
    </div>
    <?php endforeach; ?>
    <?php
    // 補空格（少於 5 張時用 fallback 填滿）
    for ($i = $idx + 1; $i <= 5; $i++): ?>
    <div class="g-gallery-tile g-gallery-tile-<?= $i ?>">
      <div class="g-gallery-fallback"><?= h($client['cat_icon'] ?? '🏪') ?></div>
    </div>
    <?php endfor; ?>
    <?php if (count($photos) > 5): ?>
    <a href="#" class="g-gallery-show-all">📷 查看全部 <?= count($photos) ?> 張</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($useBlocks): ?>
<!-- ═══════ Body 開始：左 main / 右 sticky sidebar ═══════ -->
<div class="g-store-body">
  <div class="g-store-main">

    <?php if (!empty($client['owner_name']) || !empty($client['owner_intro'])): ?>
    <!-- ═══ Owner Block ═══ -->
    <div class="g-owner-block">
      <div class="g-owner-avatar">
        <?php if (!empty($client['owner_avatar'])): ?>
        <img src="<?= BASE_URL ?>/<?= h($client['owner_avatar']) ?>" alt="<?= h($client['owner_name']) ?>">
        <?php else: ?>
        <?= h(mb_substr($client['owner_name'] ?? $client['brand_name'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="g-owner-info">
        <?php if (!empty($client['owner_name'])): ?>
        <div class="g-owner-name"><?= h($client['owner_name']) ?></div>
        <?php endif; ?>
        <?php if (!empty($client['owner_intro'])): ?>
        <div class="g-owner-intro"><?= h($client['owner_intro']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ═══════ 關於我們 ═══════ -->
<?php
$aboutTags = !empty($client['about_tags']) ? json_decode($client['about_tags'], true) : [];
if ($client['about_text'] || ($aboutTags && is_array($aboutTags))):
?>
<section class="m-section">
  <div class="m-container" style="max-width:800px;">
    <h2 class="m-section-title" style="text-align:left;">關於 <?= h($client['brand_name']) ?></h2>

    <?php if ($aboutTags && is_array($aboutTags)): ?>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px;">
      <?php foreach ($aboutTags as $tag): ?>
      <span style="display:inline-block; padding:6px 14px; background:var(--m-bg-alt); border:1px solid var(--m-border); border-radius:999px; font-size:.85rem; color:var(--m-primary); font-weight:600;">
        ✨ <?= h($tag) ?>
      </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($client['about_text']): ?>
    <div class="rich-content" style="line-height:1.9; font-size:1rem; color:var(--m-text);">
      <?php
      $aboutContent = $client['about_text'];
      if (strip_tags($aboutContent) !== $aboutContent) {
          echo $aboutContent;
      } else {
          echo nl2br(h($aboutContent));
      }
      ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 營業時間（獨立區塊）═══════ -->
<?php if (!empty($client['business_hours'])): ?>
<section class="m-section" style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); border-bottom:1px solid var(--m-border);">
  <div class="m-container" style="max-width:600px; text-align:center;">
    <h2 class="m-section-title">🕐 營業時間</h2>
    <div style="font-size:1rem; line-height:2; color:var(--m-text); background:var(--m-bg); padding:24px 28px; border-radius:var(--m-radius); border:1px solid var(--m-border); display:inline-block; text-align:left; min-width:280px;">
      <?= nl2br(h($client['business_hours'])) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 行銷頁延伸內容（HTML）═══════ -->
<?php if (!empty($client['landing_extra_content'])): ?>
<section style="background:var(--m-bg-alt); padding:60px 0; border-top:1px solid var(--m-border);">
  <div class="m-container" style="max-width:800px;">
    <div class="landing-content" style="line-height:1.8;">
      <?php
      // 行銷頁 HTML 輸出前安全過濾：補圖片 alt（SEO + 無障礙）
      $_landing = $client['landing_extra_content'];
      $_brand = $client['brand_name'];
      $_alt = $_brand . ($client['cat_name'] ? ' - ' . $client['cat_name'] : '');
      // 補上沒有 alt 的 <img>
      $_landing = preg_replace_callback('/<img\b([^>]*)>/i', function($m) use ($_alt) {
          $attrs = $m[1];
          if (preg_match('/\balt\s*=/i', $attrs)) return $m[0]; // 已有 alt 就不動
          return '<img' . $attrs . ' alt="' . htmlspecialchars($_alt, ENT_QUOTES) . '">';
      }, $_landing);
      echo $_landing;
      ?>
    </div>
  </div>
</section>
<style>
.landing-content h3 { font-size:1.4rem; font-weight:800; color:var(--m-primary); margin:24px 0 12px; }
.landing-content p { margin-bottom:14px; }
.landing-content ul, .landing-content ol { margin:14px 0 14px 24px; }
.landing-content li { margin-bottom:6px; }
.landing-content img { border-radius:8px; margin:14px 0; }
.landing-content a { color:var(--m-accent); text-decoration:underline; }
</style>
<?php endif; ?>

<!-- ═══════ Modular Blocks（新系統 — 優先渲染）═══════ -->
<?php if ($useBlocks): ?>
<?php if (!$hideShared) renderStoreBlocks($cid); ?>

  </div><!-- /.g-store-main -->

  <aside class="g-store-aside">
    <div class="g-store-aside-card">
      <h3 class="g-store-aside-title">立即聯絡</h3>
      <p class="g-store-aside-sub">透過下方資訊洽詢服務或訂位</p>

      <?php if ($client['phone']): ?>
      <div class="g-info-row">
        <div class="g-info-icon">📞</div>
        <div class="g-info-text">
          <div class="g-info-label">電話</div>
          <div class="g-info-value"><a href="tel:<?= h($client['phone']) ?>"><?= h($client['phone']) ?></a></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($client['address']): ?>
      <div class="g-info-row">
        <div class="g-info-icon">📍</div>
        <div class="g-info-text">
          <div class="g-info-label">地址</div>
          <div class="g-info-value"><?= h($client['address']) ?></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($client['business_hours']): ?>
      <div class="g-info-row">
        <div class="g-info-icon">🕐</div>
        <div class="g-info-text">
          <div class="g-info-label">營業時間</div>
          <div class="g-info-value"><?= nl2br(h($client['business_hours'])) ?></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($client['email']): ?>
      <div class="g-info-row">
        <div class="g-info-icon">✉️</div>
        <div class="g-info-text">
          <div class="g-info-label">信箱</div>
          <div class="g-info-value"><a href="mailto:<?= h($client['email']) ?>"><?= h($client['email']) ?></a></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="g-aside-ctas">
        <?php if ($client['phone']): ?>
        <a href="tel:<?= h($client['phone']) ?>" class="g-aside-btn g-aside-btn-primary">📞 撥打電話</a>
        <?php endif; ?>
        <?php if (!empty($client['mobile_phone'])): ?>
        <a href="tel:<?= h($client['mobile_phone']) ?>" class="g-aside-btn g-aside-btn-primary">📱 撥打手機</a>
        <?php endif; ?>
        <?php if ($miniSiteUrl): ?>
        <a href="<?= h($miniSiteUrl) ?>" target="_blank" rel="noopener" class="g-aside-btn g-aside-btn-outline">🌐 完整官網</a>
        <?php endif; ?>
        <?php if ($client['external_website_url']): ?>
        <a href="<?= h($client['external_website_url']) ?>" target="_blank" rel="noopener" class="g-aside-btn g-aside-btn-outline">🔗 官方網站</a>
        <?php endif; ?>
        <?php if (!empty($client['order_url'])): ?>
        <a href="<?= h($client['order_url']) ?>" target="_blank" rel="noopener" class="g-aside-btn g-aside-btn-outline">🍱 線上訂購</a>
        <?php endif; ?>
      </div>

      <?php if ($rating['cnt'] > 0 || ($googleReviews && $googleReviews['rating'] > 0)): ?>
      <div class="g-aside-foot">
        ⭐ <strong><?= number_format($googleReviews['rating'] ?? $rating['avg'], 1) ?></strong> 顆星
        ・ <?= ($googleReviews['userRatingCount'] ?? $rating['cnt']) ?> 則評價
      </div>
      <?php endif; ?>
    </div>
  </aside>
</div><!-- /.g-store-body -->
<?php endif; ?>

<?php /* 城市變體勾「只顯示自寫內容」→ 藏「服務項目以下」共用區塊（評價/網友分享等）；城市切換 nav 例外保留 */ ?>
<?php if (!$hideShared): ?>
<!-- ═══════ 服務項目（舊 fallback — 沒 blocks 才用）═══════ -->
<?php if (!$useBlocks && $services): ?>
<section class="m-section" style="background:var(--m-bg);">
  <div class="m-container">
    <h2 class="m-section-title">服務項目</h2>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
      <?php foreach ($services as $s): ?>
      <div style="background:var(--m-bg-alt); padding:20px; border-radius:var(--m-radius); border:1px solid var(--m-border);">
        <div style="font-size:1.8rem; margin-bottom:8px;"><?= h($s['icon'] ?: '📋') ?></div>
        <h3 style="font-size:1.05rem; font-weight:800; color:var(--m-primary); margin-bottom:6px;"><?= h($s['name']) ?></h3>
        <?php if ($s['short_desc']): ?>
        <p style="font-size:.85rem; color:var(--m-text-muted); margin-bottom:8px;"><?= h(mb_strimwidth($s['short_desc'], 0, 80, '…')) ?></p>
        <?php endif; ?>
        <?php if ($s['price_text']): ?>
        <div style="font-size:.9rem; font-weight:700; color:var(--m-accent);"><?= h($s['price_text']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($miniSiteUrl): ?>
    <div style="text-align:center; margin-top:30px;">
      <a href="<?= h($miniSiteUrl) ?>" target="_blank" class="m-btn m-btn-outline">
        查看完整服務 →
      </a>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 案例（舊 fallback — 沒 blocks 才用）═══════ -->
<?php if (!$useBlocks && $cases): ?>
<section class="m-section">
  <div class="m-container">
    <h2 class="m-section-title">精選案例</h2>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
      <?php foreach ($cases as $cs): ?>
      <?php
        $beforeImg = !empty($cs['before_image']) ? BASE_URL . '/' . h($cs['before_image']) : '';
        $afterImg  = !empty($cs['after_image'])  ? BASE_URL . '/' . h($cs['after_image'])  : '';
      ?>
      <div style="background:var(--m-bg-alt); border:1px solid var(--m-border); border-radius:var(--m-radius); overflow:hidden;">
        <?php if ($afterImg): ?>
        <img src="<?= $afterImg ?>" alt="<?= h($cs['title']) ?>" style="width:100%; aspect-ratio:4/3; object-fit:cover;">
        <?php endif; ?>
        <div style="padding:14px 18px;">
          <h3 style="font-size:1rem; font-weight:700; color:var(--m-primary); margin-bottom:4px;"><?= h($cs['title']) ?></h3>
          <?php if ($cs['location']): ?>
          <div style="font-size:.8rem; color:var(--m-text-muted);">📍 <?= h($cs['location']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ Google 評價 ═══════ -->
<?php if ($googleReviews && $googleReviews['rating'] > 0): ?>
<section class="m-section" style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); border-bottom:1px solid var(--m-border);">
  <div class="m-container">
    <div style="display:flex; align-items:center; justify-content:center; gap:14px; margin-bottom:8px; flex-wrap:wrap;">
      <h2 class="m-section-title" style="margin-bottom:0;">Google 評價</h2>
      <a href="<?= h($googleReviews['mapsUrl']) ?: '#' ?>" target="_blank" rel="noopener"
         style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:#fff; border:1px solid var(--m-border); border-radius:20px; font-size:.85rem; color:var(--m-primary); text-decoration:none;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        在 Google 看更多
      </a>
    </div>

    <div style="text-align:center; margin-bottom:30px;">
      <div style="font-size:3rem; font-weight:900; color:var(--m-primary); line-height:1;">
        <?= number_format($googleReviews['rating'], 1) ?>
      </div>
      <div style="color:#fbbf24; font-size:1.4rem; margin:4px 0;">
        <?php
          $r = $googleReviews['rating'];
          $full = floor($r);
          $half = ($r - $full) >= 0.3 && ($r - $full) < 0.8 ? 1 : 0;
          $empty = 5 - $full - $half;
          echo str_repeat('★', $full) . ($half ? '✬' : '') . str_repeat('☆', $empty);
        ?>
      </div>
      <div style="color:var(--m-text-muted); font-size:.9rem;">
        共 <?= number_format($googleReviews['userRatingCount']) ?> 則 Google 評價
      </div>
    </div>

    <?php if (!empty($googleReviews['reviews'])): ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:18px; max-width:1100px; margin:0 auto;">
      <?php foreach (array_slice($googleReviews['reviews'], 0, 6) as $rev): ?>
      <div style="background:var(--m-bg); padding:18px; border-radius:var(--m-radius); border:1px solid var(--m-border);">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
          <?php if (!empty($rev['avatar'])): ?>
          <img src="<?= h($rev['avatar']) ?>" alt="" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" referrerpolicy="no-referrer">
          <?php else: ?>
          <div style="width:36px; height:36px; border-radius:50%; background:var(--m-primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700;"><?= mb_substr($rev['author'], 0, 1) ?></div>
          <?php endif; ?>
          <div style="flex:1; min-width:0;">
            <div style="font-weight:700; font-size:.9rem; color:var(--m-primary);"><?= h($rev['author']) ?></div>
            <div style="color:#fbbf24; font-size:.85rem;">
              <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
              <span style="color:var(--m-text-muted); margin-left:6px;"><?= h($rev['relative']) ?></span>
            </div>
          </div>
        </div>
        <p style="font-size:.88rem; line-height:1.7; color:var(--m-text); margin:0;">
          <?= h(mb_strimwidth($rev['text'], 0, 200, '…')) ?>
        </p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php
// ─── Reviews summary 計算（測試 useBlocks 客戶五星分布）─────────
if ($useBlocks && $testimonials) {
    $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($testimonials as $t) {
        $r = max(1, min(5, (int)$t['rating']));
        $ratingCounts[$r]++;
    }
    $maxCount = max($ratingCounts) ?: 1;
}
?>

<?php if ($useBlocks && $testimonials): ?>
<!-- ═══════ Reviews Summary（新版 — 含五星分布）═══════ -->
<section class="g-reviews-block">
  <div class="g-reviews-block-inner">
    <div class="g-reviews-summary">
      <div class="g-summary-score">
        <div class="g-summary-num"><?= number_format($rating['avg'], 1) ?></div>
        <div class="g-summary-stars">
          <?= str_repeat('★', round($rating['avg'])) ?><?= str_repeat('☆', 5 - round($rating['avg'])) ?>
        </div>
        <div class="g-summary-count"><?= (int)$rating['cnt'] ?> 則精選評價</div>
      </div>
      <div class="g-rating-bars">
        <?php for ($i = 5; $i >= 1; $i--):
          $cnt = $ratingCounts[$i];
          $pct = $cnt > 0 ? round($cnt / $maxCount * 100) : 0;
        ?>
        <div class="g-rating-bar">
          <span class="g-rating-bar-label"><?= $i ?> 星</span>
          <div class="g-rating-bar-track">
            <div class="g-rating-bar-fill" style="width: <?= $pct ?>%"></div>
          </div>
          <span class="g-rating-bar-num"><?= $cnt ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="g-reviews-list">
      <?php foreach ($testimonials as $t):
        $initial = mb_substr($t['reviewer_name'], 0, 1);
      ?>
      <div class="g-review-item">
        <div class="g-review-head">
          <div class="g-review-avatar"><?= h($initial) ?></div>
          <div>
            <div class="g-review-name"><?= h($t['reviewer_name']) ?></div>
            <div class="g-review-stars-small"><?= str_repeat('★', $t['rating']) ?><?= str_repeat('☆', 5 - $t['rating']) ?></div>
          </div>
        </div>
        <p class="g-review-text"><?= h(mb_strimwidth($t['content'], 0, 200, '…', 'UTF-8')) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($testimonials): ?>
<!-- ═══════ 老闆精選評價（舊版 — 未啟用 blocks 客戶）═══════ -->
<section class="m-section"<?= $googleReviews ? '' : ' style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); border-bottom:1px solid var(--m-border);"' ?>>
  <div class="m-container">
    <h2 class="m-section-title"><?= $googleReviews ? '老闆精選評價' : '客戶評價' ?></h2>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px; max-width:1000px; margin:0 auto;">
      <?php foreach ($testimonials as $t): ?>
      <div style="background:var(--m-bg); padding:20px; border-radius:var(--m-radius); border:1px solid var(--m-border);">
        <div style="color:#fbbf24; margin-bottom:8px;">
          <?= str_repeat('★', $t['rating']) ?><?= str_repeat('☆', 5 - $t['rating']) ?>
        </div>
        <p style="font-size:.9rem; line-height:1.7; color:var(--m-text); margin-bottom:12px;">
          「<?= h(mb_strimwidth($t['content'], 0, 150, '…')) ?>」
        </p>
        <div style="font-size:.85rem; font-weight:700; color:var(--m-primary);"><?= h($t['reviewer_name']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ Google Map ═══════ -->
<?php
  $_mapsSrc = trim($client['google_maps_embed'] ?? '');

  // 如果使用者貼了完整 <iframe> 標籤，自動抽出 src
  if ($_mapsSrc && stripos($_mapsSrc, '<iframe') !== false) {
      if (preg_match('/src=["\']([^"\']+)["\']/i', $_mapsSrc, $_m)) {
          $_mapsSrc = $_m[1];
      }
  }

  // 短網址（maps.app.goo.gl）iframe 會被 Google 擋住，改渲染按鈕
  $_isMapShortUrl = $_mapsSrc && preg_match('#^https?://maps\.app\.goo\.gl/#i', $_mapsSrc);
  // 長 embed URL（google.com/maps/...）才適合 iframe
  $_isMapEmbeddable = $_mapsSrc && !$_isMapShortUrl && (
      preg_match('#^https?://(www\.)?google\.[^/]+/maps/#i', $_mapsSrc) ||
      preg_match('#^https?://maps\.google\.[^/]+/#i', $_mapsSrc)
  );
?>
<?php if ($_isMapEmbeddable): ?>
<section class="m-section">
  <div class="m-container">
    <h2 class="m-section-title">店家位置</h2>
    <div style="border-radius:var(--m-radius); overflow:hidden; box-shadow:var(--m-shadow);">
      <iframe src="<?= h($_mapsSrc) ?>"
              width="100%" height="380" style="border:0; display:block;"
              allowfullscreen loading="lazy"></iframe>
    </div>
  </div>
</section>
<?php elseif ($_isMapShortUrl): ?>
<section class="m-section">
  <div class="m-container" style="text-align:center;">
    <h2 class="m-section-title">店家位置</h2>
    <p style="color:var(--m-text-muted); margin-bottom:18px;">點下方按鈕用 Google 地圖查看完整位置、路線規劃與評論。</p>
    <a href="<?= h($_mapsSrc) ?>" target="_blank" rel="noopener"
       style="display:inline-flex; align-items:center; gap:8px; padding:14px 28px; background:#fff; border:2px solid #4285F4; color:#4285F4; border-radius:999px; text-decoration:none; font-weight:700;">
      📍 開啟 Google 地圖
    </a>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ Facebook Page Plugin（client_social.fb_embed_enabled=1 才嵌入）═══════ -->
<?php if (!empty($clientSocial['fb_page_url']) && !empty($clientSocial['fb_embed_enabled'])):
  $_fbHref = $clientSocial['fb_page_url'];
  $_fbPluginSrc = 'https://www.facebook.com/plugins/page.php?'
    . http_build_query([
        'href' => $_fbHref,
        'tabs' => 'timeline',
        'width' => 500,
        'height' => 500,
        'small_header' => 'false',
        'adapt_container_width' => 'true',
        'hide_cover' => 'false',
        'show_facepile' => 'true',
      ]);
?>
<section class="m-section" style="background:var(--m-bg-alt);">
  <div class="m-container" style="max-width:560px; text-align:center;">
    <h2 class="m-section-title">📘 Facebook 粉絲專頁</h2>
    <div style="display:flex; justify-content:center; margin-top:8px;">
      <iframe src="<?= h($_fbPluginSrc) ?>"
              width="500" height="500"
              style="border:none; overflow:hidden; max-width:100%; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.08);"
              scrolling="no" frameborder="0" allowfullscreen="true"
              allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
              loading="lazy"></iframe>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 社群連結 row（client_social 有資料時顯示）═══════ -->
<?php
$_socialIcons = [
  'fb_page_url'   => ['label' => 'Facebook', 'emoji' => '📘', 'color' => '#1877F2'],
  'instagram_url' => ['label' => 'Instagram','emoji' => '📷', 'color' => '#E4405F'],
  'line_url'      => ['label' => 'LINE',     'emoji' => '💬', 'color' => '#06C755'],
  'youtube_url'   => ['label' => 'YouTube',  'emoji' => '▶️', 'color' => '#FF0000'],
];
$_hasAnySocial = false;
foreach ($_socialIcons as $f => $_) { if (!empty($clientSocial[$f])) { $_hasAnySocial = true; break; } }
?>
<?php if ($_hasAnySocial): ?>
<section class="m-section" style="background:#fff; padding:30px 0;">
  <div class="m-container" style="text-align:center;">
    <div style="display:inline-flex; gap:12px; flex-wrap:wrap; justify-content:center; align-items:center;">
      <span style="color:var(--m-text-muted); font-size:.9rem; margin-right:6px;">追蹤我們：</span>
      <?php foreach ($_socialIcons as $field => $cfg): ?>
        <?php if (!empty($clientSocial[$field])): ?>
        <a href="<?= h($clientSocial[$field]) ?>" target="_blank" rel="noopener"
           title="<?= h($cfg['label']) ?>"
           style="display:inline-flex; align-items:center; gap:6px; padding:10px 18px; border:2px solid <?= $cfg['color'] ?>; color:<?= $cfg['color'] ?>; border-radius:999px; text-decoration:none; font-size:.92rem; font-weight:700; transition:transform .15s;"
           onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
          <span><?= $cfg['emoji'] ?></span><span><?= h($cfg['label']) ?></span>
        </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 網友分享（external_reviews_json，每城市專屬）═══════ -->
<?php
$_extReviews = [];
if ($cityVariant && !empty($cityVariant['external_reviews_json'])) {
    $_extReviews = json_decode($cityVariant['external_reviews_json'], true) ?: [];
}
?>
<?php if ($_extReviews): ?>
<section class="m-section" style="background:#fafafa;">
  <div class="m-container" style="max-width:1000px;">
    <h2 class="m-section-title">📰 網友分享</h2>
    <p style="text-align:center; color:var(--m-text-muted); margin:-10px 0 26px; font-size:.95rem;">
      網友對 <?= h($client['brand_name']) ?> 的真實分享紀錄
    </p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
      <?php foreach ($_extReviews as $r): ?>
      <a href="<?= h($r['url'] ?? '#') ?>" target="_blank" rel="noopener"
         style="display:block; padding:20px; background:#fff; border:1px solid var(--m-border); border-radius:10px; text-decoration:none; color:var(--m-text); transition:transform .2s, box-shadow .2s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(0,0,0,.08)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:inline-block; padding:3px 10px; background:var(--m-accent); color:#fff; font-size:.72rem; border-radius:999px; margin-bottom:10px;">
          網友分享
        </div>
        <h3 style="font-size:1rem; font-weight:700; line-height:1.45; margin:0 0 8px; color:var(--m-text);">
          <?= h($r['title'] ?? '') ?>
        </h3>
        <?php if (!empty($r['excerpt'])): ?>
        <p style="font-size:.88rem; color:var(--m-text-muted); line-height:1.55; margin:0;">
          <?= h($r['excerpt']) ?>
        </p>
        <?php endif; ?>
        <div style="margin-top:12px; font-size:.82rem; color:var(--m-primary); font-weight:600;">
          閱讀全文 →
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php endif; /* /hideShared：服務項目以下共用區塊結束，以下城市切換 nav 一律顯示 */ ?>

<!-- ═══════ 多縣市互鏈：該客戶有多城市方案時列出其他城市 ═══════ -->
<?php
$otherCities = $cityVariant
    ? array_values(array_filter($cityVariantList, fn($c) => $c['city_slug'] !== $citySlug))
    : $cityVariantList;
?>
<?php if (count($cityVariantList) >= 2 && empty($client['hide_city_grid_section'])): ?>
<section class="m-section" style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); border-bottom:1px solid var(--m-border);">
  <div class="m-container">
    <h2 class="m-section-title" style="text-align:center; margin-bottom:10px;">
      📍 <?= h($client['brand_name']) ?>．全台服務縣市
    </h2>
    <p style="text-align:center; color:var(--m-text-muted); margin-bottom:26px; font-size:.95rem;">
      <?= $cityVariant ? '看 ' . h($client['brand_name']) . ' 在其他縣市的在地視角內容' : '點各縣市看該城市專屬內容（在地觀點、配送範圍、城市場景）' ?>
    </p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; max-width:920px; margin:0 auto;">
      <?php if ($cityVariant): /* 變體頁時加「本店資訊」回主行銷頁 */ ?>
      <a href="<?= BASE_URL ?>/store/<?= h($sub) ?>" style="display:block; text-align:center; padding:20px 12px; background:#fff; border:1.5px solid var(--m-border); border-radius:10px; text-decoration:none; color:var(--m-text); transition:transform .2s, border-color .2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.borderColor='var(--m-primary)'" onmouseout="this.style.transform='';this.style.borderColor='var(--m-border)'">
        <div style="font-size:1.5rem; margin-bottom:6px;">🏠</div>
        <div style="font-weight:700; font-size:.95rem;"><?= h($client['brand_name']) ?></div>
        <div style="font-size:.78rem; color:var(--m-text-muted); margin-top:4px;">本店／品牌主頁</div>
      </a>
      <?php endif; ?>
      <?php foreach ($otherCities as $oc): ?>
      <a href="<?= BASE_URL ?>/store/<?= h($sub) ?>/<?= h($oc['city_slug']) ?>" style="display:block; text-align:center; padding:20px 12px; background:#fff; border:1.5px solid var(--m-border); border-radius:10px; text-decoration:none; color:var(--m-text); transition:transform .2s, border-color .2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.borderColor='var(--m-primary)'" onmouseout="this.style.transform='';this.style.borderColor='var(--m-border)'">
        <div style="font-size:1.5rem; margin-bottom:6px;">📍</div>
        <div style="font-weight:700; font-size:.95rem;"><?= h($oc['city_label']) ?></div>
        <div style="font-size:.78rem; color:var(--m-text-muted); margin-top:4px;">在地視角</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($useBlocks && $similarStores && !$hideShared): ?>
<!-- ═══════ Similar Stores（同分類隨機 4 卡）═══════ -->
<section class="g-similar-block">
  <div class="g-similar-inner">
    <div class="g-similar-head">
      <h2 class="g-similar-title">類似的<?= h($client['cat_name'] ?? '') ?>店家</h2>
      <?php if ($client['cat_slug']): ?>
      <a href="<?= BASE_URL ?>/category.php?slug=<?= h($client['cat_slug']) ?>" class="g-section-link">看全部 <?= h($client['cat_name']) ?></a>
      <?php endif; ?>
    </div>
    <div class="g-similar-grid">
      <?php foreach ($similarStores as $s):
        $simSub = $s['subdomain'] ?: $s['slug'];
        $simHero = $s['hero_image_path'] ? BASE_URL . '/' . h($s['hero_image_path']) : '';
        $simLoc  = $s['address'] ? mb_substr($s['address'], 0, 8, 'UTF-8') : '';
      ?>
      <a class="g-similar-card" href="<?= BASE_URL ?>/store.php?sub=<?= urlencode($simSub) ?>">
        <div class="g-similar-img" <?= $simHero ? 'style="background-image:url(\''.$simHero.'\')"' : '' ?>>
          <?php if (!$simHero): ?>
          <div class="g-similar-img-fallback"><?= h($s['cat_icon'] ?? '🏪') ?></div>
          <?php endif; ?>
        </div>
        <div class="g-similar-body">
          <div class="g-similar-name"><?= h($s['brand_name']) ?></div>
          <?php if ($s['tagline']): ?>
          <div class="g-similar-loc"><?= h(mb_strimwidth($s['tagline'], 0, 30, '…', 'UTF-8')) ?></div>
          <?php elseif ($simLoc): ?>
          <div class="g-similar-loc">📍 <?= h($simLoc) ?></div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
