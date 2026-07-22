<?php
// category.php  ─  主站分類列表頁 /category?slug=food
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();
$slug = strtolower(trim($_GET['slug'] ?? ''));

// 重複客戶 slug（已 301 到主檔）— 各分類列表 / 計數都排除
$dupSkip = getDuplicateSkipSlugs();
$dupPh   = implode(',', array_fill(0, count($dupSkip), '?'));

// 模式 A：沒指定分類 → 顯示所有分類入口
if (!$slug) {
    $allCatsStmt = $db->prepare("
        SELECT c.id, c.name, c.slug, c.icon, c.description,
               COUNT(cl.id) AS client_count
        FROM categories c
        LEFT JOIN clients cl ON cl.category_id = c.id AND cl.is_active = 1 AND cl.slug NOT IN ($dupPh)
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.sort_order, c.name
    ");
    $allCatsStmt->execute($dupSkip);
    $allCats = $allCatsStmt->fetchAll();

    $pageTitle = '所有分類｜店家好口碑';
    $metaDesc  = '瀏覽店家好口碑所有店家分類：餐飲美食、居家服務、美容美髮、教育學習等。';
    require_once __DIR__ . '/main/layout_head.php';
    ?>

    <div class="m-container">
      <div class="m-breadcrumb">
        <a href="<?= BASE_URL ?>/">首頁</a>
        <span class="sep">›</span>
        <span style="color:var(--m-primary); font-weight:600;">所有分類</span>
      </div>
    </div>

    <section class="m-section">
      <div class="m-container">
        <h1 class="m-section-title">所有分類</h1>
        <p class="m-section-sub">點擊分類查看該類別的所有店家</p>

        <div class="m-cat-grid" style="grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));">
          <?php foreach ($allCats as $cat): ?>
          <a class="m-cat-card" href="<?= BASE_URL ?>/category.php?slug=<?= h($cat['slug']) ?>">
            <div class="icon" style="font-size:2.4rem;"><?= h($cat['icon']) ?></div>
            <div class="name"><?= h($cat['name']) ?></div>
            <div class="count"><?= $cat['client_count'] ?> 家店家</div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php
    require_once __DIR__ . '/main/layout_foot.php';
    exit;
}

// 模式 B：指定分類 → 顯示該分類所有客戶
$cat = $db->prepare("SELECT * FROM categories WHERE slug=? AND is_active=1");
$cat->execute([$slug]);
$cat = $cat->fetch();
if (!$cat) {
    http_response_code(404);
    die('分類不存在');
}

$clientsStmt = $db->prepare("
    SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline,
           cl.has_minisite, cl.external_website_url, cl.hero_image_path, cl.hero_image_fit, cl.logo_path,
           cl.address, cl.phone, cl.city_slug
    FROM clients cl
    WHERE cl.category_id = ? AND cl.is_active = 1 AND cl.slug NOT IN ($dupPh)
    ORDER BY (cl.hero_image_path IS NULL OR cl.hero_image_path = '') ASC, cl.id DESC
");
$clientsStmt->execute(array_merge([$cat['id']], $dupSkip));
$clients = $clientsStmt->fetchAll();

$pageTitle = $cat['name'] . '｜店家好口碑 全台店家分類';
// 描述照實際收錄的縣市寫（原本寫死「台南店家」，但這頁的查詢不分縣市 → 描述與內容不符）
$_cityMap   = getCityMap();
$_catCities = [];
foreach ($clients as $_c) {
    $_cs = $_c['city_slug'] ?: deriveCitySlug($_c['address'] ?? '');
    if ($_cs && isset($_cityMap[$_cs])) $_catCities[$_cs] = ($_catCities[$_cs] ?? 0) + 1;
}
arsort($_catCities);
$_cityNames = array_map(fn($s) => $_cityMap[$s], array_slice(array_keys($_catCities), 0, 4));
$metaDesc = "店家好口碑「{$cat['name']}」分類共收錄 " . count($clients) . " 家在地商家"
          . ($_cityNames ? '，分布於' . implode('、', $_cityNames) . (count($_catCities) > 4 ? '等縣市' : '') : '')
          . '。每家都有服務項目、真實評價與聯絡方式，可直接比較後聯絡。'
          . ($cat['description'] ?: '');
require_once __DIR__ . '/main/layout_head.php';
?>

<div class="m-container">
  <div class="m-breadcrumb">
    <a href="<?= BASE_URL ?>/">首頁</a>
    <span class="sep">›</span>
    <a href="<?= BASE_URL ?>/category.php">所有分類</a>
    <span class="sep">›</span>
    <span style="color:var(--m-primary); font-weight:600;"><?= h($cat['icon']) ?> <?= h($cat['name']) ?></span>
  </div>
</div>

<?php
  $hasBanner = !empty($cat['banner_image_path']);
  $bannerUrl = $hasBanner ? BASE_URL . '/' . h($cat['banner_image_path']) : '';
?>
<section class="m-cat-hero<?= $hasBanner ? ' has-banner' : '' ?>"
         <?= $hasBanner ? 'style="background-image:url(\''.$bannerUrl.'\');"' : '' ?>>
  <?php if ($hasBanner): ?>
  <div class="m-cat-hero-overlay"></div>
  <?php endif; ?>
  <div class="m-container m-cat-hero-inner" style="text-align:center; position:relative; z-index:2;">
    <?php if (!$hasBanner): ?>
    <div style="font-size:3rem; margin-bottom:8px;"><?= h($cat['icon']) ?></div>
    <?php endif; ?>
    <h1 style="font-size:clamp(1.8rem, 4vw, 2.4rem); font-weight:900; margin-bottom:8px;"><?= h($cat['name']) ?></h1>
    <?php if ($cat['description']): ?>
    <p style="margin-bottom:8px; opacity:.9;"><?= h($cat['description']) ?></p>
    <?php endif; ?>
    <div style="font-size:.9rem; opacity:.85;">共 <?= count($clients) ?> 家店家</div>
  </div>
</section>

<style>
.m-cat-hero {
  background: var(--m-bg-alt);
  border-top: 1px solid var(--m-border);
  padding: 56px 0;
  position: relative;
  color: var(--m-primary);
}
.m-cat-hero.has-banner {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 80px 0;
  color: #fff;
  border: none;
}
.m-cat-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,.25) 0%, rgba(0,0,0,.55) 100%);
  z-index: 1;
}
.m-cat-hero.has-banner h1 {
  text-shadow: 0 2px 12px rgba(0,0,0,.5);
}
.m-cat-hero.has-banner p {
  text-shadow: 0 2px 8px rgba(0,0,0,.5);
}
@media (max-width: 768px) {
  .m-cat-hero.has-banner { padding: 60px 0; }
}
</style>

<section class="m-section">
  <div class="m-container">

    <?php if (empty($clients)): ?>
    <div style="text-align:center; padding:60px 20px;">
      <div style="font-size:3rem; margin-bottom:12px; opacity:.4;">📭</div>
      <h3 style="color:var(--m-text-muted); font-weight:600;">此分類下尚無店家</h3>
      <a href="<?= BASE_URL ?>/" class="m-btn m-btn-primary" style="margin-top:20px;">回首頁</a>
    </div>
    <?php else: ?>

    <div class="m-store-grid">
      <?php foreach ($clients as $cl): ?>
      <?php
        $linkUrl = clientStoreUrl($cl);
      ?>
      <a class="m-store-card" href="<?= $linkUrl ?>">
        <div class="cover"<?= gStoreCardImg($cl) ?>>
          <span class="cat-tag"><?= h($cat['icon']) ?> <?= h($cat['name']) ?></span>
        </div>
        <div class="body">
          <h3><?= h($cl['brand_name']) ?></h3>
          <?php if ($cl['tagline']): ?>
          <div class="tagline"><?= h($cl['tagline']) ?></div>
          <?php endif; ?>
          <div class="badges">
            <?php if ($cl['has_minisite']): ?>
            <span class="badge badge-mini">🌐 有官網</span>
            <?php endif; ?>
            <?php if ($cl['external_website_url']): ?>
            <span class="badge badge-ext">🔗 自有官網</span>
            <?php endif; ?>
          </div>
          <div class="meta">
            <?php if ($cl['address']): ?>
            <span>📍 <?= h(mb_strimwidth($cl['address'], 0, 18, '…')) ?></span>
            <?php endif; ?>
            <?php if ($cl['phone']): ?>
            <span>📞 <?= h($cl['phone']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
