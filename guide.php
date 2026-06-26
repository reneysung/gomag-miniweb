<?php
// guide.php  ─  攻略文 /guide（總覽）與 /guide/{slug}（單篇）（IA 落地第 5 步）
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();
$slug = strtolower(trim($_GET['slug'] ?? ''));
$base = (IS_LOCAL || IS_STAGING) ? BASE_URL : 'https://www.gomag.com.tw';
$cityMap = getCityMap();
$catById = [];
foreach ($db->query("SELECT id, slug, name, icon FROM categories WHERE is_active=1") as $c) $catById[(int)$c['id']] = $c;
$extraCss = [BASE_URL . '/assets/css/gomag.css'];

// 攻略文卡片連結
$guideUrl = fn($s) => (IS_LOCAL || IS_STAGING) ? BASE_URL . '/guide.php?slug=' . urlencode($s) : 'https://www.gomag.com.tw/guide/' . urlencode($s);

// ═══════════════════════════════════════════════════════════
//   單篇模式 /guide/{slug}
// ═══════════════════════════════════════════════════════════
if ($slug) {
    $g = $db->prepare("SELECT * FROM guides WHERE slug = ? LIMIT 1");
    $g->execute([$slug]);
    $guide = $g->fetch();
    if (!$guide || $guide['status'] !== 'published') {
        http_response_code(404);
        die('攻略文不存在或未發布');
    }
    $gCat  = $guide['category_id'] ? ($catById[(int)$guide['category_id']] ?? null) : null;
    $gCity = $guide['city_slug'] && isset($cityMap[$guide['city_slug']]) ? $cityMap[$guide['city_slug']] : null;

    $pageTitle = !empty($guide['meta_title']) ? $guide['meta_title'] : $guide['title'] . '｜店家好口碑攻略';
    $metaDesc  = !empty($guide['meta_desc']) ? $guide['meta_desc'] : ($guide['excerpt'] ?: mb_strimwidth(strip_tags($guide['body_html'] ?? ''), 0, 150, '…'));
    $canonical = $guideUrl($slug);
    $cover = $guide['cover_image'] ?: '';

    if (!empty($guide['noindex'])) $metaRobots = 'noindex,follow';
    require_once __DIR__ . '/main/layout_head.php';

    $articleLd = [
        '@context' => 'https://schema.org', '@type' => 'Article',
        'headline' => $guide['title'], 'description' => $metaDesc, 'url' => $canonical,
        'datePublished' => $guide['published_at'] ? date('c', strtotime($guide['published_at'])) : null,
        'dateModified'  => $guide['updated_at'] ? date('c', strtotime($guide['updated_at'])) : null,
        'publisher' => ['@type' => 'Organization', 'name' => '店家好口碑', 'url' => $base . '/'],
    ];
    if ($cover) $articleLd['image'] = $cover;
    $breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => $base . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => '攻略', 'item' => $base . '/guide'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $guide['title']],
    ]];
    ?>
    <script type="application/ld+json"><?= json_encode($articleLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

    <section class="g-hero" style="min-height:auto;">
      <?php if ($cover): ?>
      <div class="g-hero-bg" style="background-image:url('<?= h($cover) ?>');"></div>
      <?php else: ?>
      <div class="g-hero-bg" style="background:linear-gradient(135deg, #2a2a2a, #0f0f0f); animation:none; transform:none;"></div>
      <?php endif; ?>
      <div class="g-hero-overlay"></div>
      <div class="g-hero-content">
        <div class="g-hero-tag"><span class="g-hero-tag-dot"></span><span>📖 攻略</span></div>
        <h1 class="g-hero-title" style="font-size:2.4rem;"><?= h($guide['title']) ?></h1>
        <?php if ($guide['excerpt']): ?><p class="g-hero-desc"><?= h($guide['excerpt']) ?></p><?php endif; ?>
      </div>
    </section>

    <div class="g-breadcrumb-wrap">
      <nav class="g-breadcrumb" aria-label="breadcrumb">
        <a href="<?= BASE_URL ?>/">首頁</a>
        <span class="g-breadcrumb-sep">›</span>
        <a href="<?= BASE_URL ?>/guide.php">攻略</a>
        <span class="g-breadcrumb-sep">›</span>
        <span class="current"><?= h($guide['title']) ?></span>
      </nav>
    </div>

    <section class="g-section" style="max-width:820px;">
      <div class="g-guide-body" style="line-height:1.9; color:var(--g-ink-soft); font-size:1.05rem;">
        <?= $guide['body_html'] /* admin 受信任 HTML */ ?>
      </div>
    </section>

    <!-- 互鏈：對應交叉頁 / 看全台分類 -->
    <?php if ($gCity || $gCat): ?>
    <section class="g-section" style="padding-top:0;">
      <div style="display:flex; flex-wrap:wrap; gap:12px;">
        <?php if ($gCity && $gCat): ?>
        <a href="<?= BASE_URL ?>/city/<?= h($guide['city_slug']) ?>/<?= h($gCat['slug']) ?>" class="g-cta-btn g-cta-btn-primary"><?= h($gCity) ?><?= h($gCat['name']) ?>店家 →</a>
        <?php endif; ?>
        <?php if ($gCat): ?>
        <a href="<?= BASE_URL ?>/category.php?slug=<?= h($gCat['slug']) ?>" class="g-cta-btn g-cta-btn-secondary">看全台<?= h($gCat['name']) ?> →</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/guide.php" class="g-cta-btn g-cta-btn-secondary">← 更多攻略</a>
      </div>
    </section>
    <?php endif; ?>

    <section class="g-cta">
      <div class="g-cta-inner">
        <div class="g-cta-eyebrow">Make it Yours</div>
        <h2 class="g-cta-title">想被更多在地客人找到？</h2>
        <p class="g-cta-desc">店家好口碑專注在地口碑曝光 — 月費 NT$300 起，業務團隊到店服務。</p>
        <div class="g-cta-btns"><a href="<?= BASE_URL ?>/" class="g-cta-btn g-cta-btn-primary">立即聯絡</a></div>
      </div>
    </section>

    <?php require_once __DIR__ . '/main/layout_foot.php';
    exit;
}

// ═══════════════════════════════════════════════════════════
//   總覽模式 /guide
// ═══════════════════════════════════════════════════════════
$guides = $db->query("SELECT slug, title, excerpt, cover_image, city_slug, category_id FROM guides WHERE status='published' ORDER BY published_at DESC, id DESC")->fetchAll();

$pageTitle = '在地服務攻略｜店家好口碑';
$metaDesc  = '怎麼挑清潔、美髮、汽車美容…在地服務？店家好口碑攻略：選店重點、價格行情、避雷指南。';
$canonical = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/guide.php' : 'https://www.gomag.com.tw/guide';
require_once __DIR__ . '/main/layout_head.php';
?>
<div class="g-breadcrumb-wrap">
  <nav class="g-breadcrumb" aria-label="breadcrumb">
    <a href="<?= BASE_URL ?>/">首頁</a>
    <span class="g-breadcrumb-sep">›</span>
    <span class="current">📖 攻略</span>
  </nav>
</div>

<section class="g-section">
  <div class="g-section-head">
    <div>
      <h1 class="g-section-title">📖 在地服務攻略</h1>
      <p class="g-section-sub">選店重點、價格行情、避雷指南 — 找服務前先看這裡</p>
    </div>
  </div>

  <?php if (!$guides): ?>
  <div style="padding:40px; text-align:center; color:var(--g-ink-muted);">攻略文陸續上線中，敬請期待。</div>
  <?php else: ?>
  <div class="g-store-grid">
    <?php foreach ($guides as $gd):
        $gc = $gd['cover_image'] ?: '';
        $tags = [];
        if ($gd['city_slug'] && isset($cityMap[$gd['city_slug']])) $tags[] = $cityMap[$gd['city_slug']];
        if ($gd['category_id'] && isset($catById[(int)$gd['category_id']])) $tags[] = $catById[(int)$gd['category_id']]['name'];
    ?>
    <a class="g-store-card" href="<?= h($guideUrl($gd['slug'])) ?>">
      <div class="g-store-img" <?= $gc ? 'style="background-image:url(\''.h($gc).'\')"' : '' ?>>
        <?php if (!$gc): ?><div class="g-store-img-fallback"><span class="icon">📖</span><span class="label">攻略</span></div><?php endif; ?>
      </div>
      <div class="g-store-meta-top"><div class="g-store-name"><?= h($gd['title']) ?></div></div>
      <?php if ($gd['excerpt']): ?><div class="g-store-loc"><?= h(mb_strimwidth($gd['excerpt'], 0, 50, '…', 'UTF-8')) ?></div><?php endif; ?>
      <?php if ($tags): ?><div class="g-store-cat-label"><?= h(implode('・', $tags)) ?></div><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
