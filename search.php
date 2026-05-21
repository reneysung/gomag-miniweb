<?php
// search.php  ─  主站搜尋 /search?q=xxx
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();
$q = trim($_GET['q'] ?? '');

$results = [];
if (mb_strlen($q) >= 1) {
    // 跨欄位 LIKE 搜尋（PDO 非 emulate 模式，每個 placeholder 要獨立 param）
    $sql = "
        SELECT DISTINCT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline, cl.industry,
               cl.has_minisite, cl.external_website_url, cl.hero_image_path,
               cl.address, cl.phone,
               c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
        FROM clients cl
        LEFT JOIN categories c ON cl.category_id = c.id
        LEFT JOIN services s ON s.client_id = cl.id AND s.is_active = 1
        WHERE cl.is_active = 1
          AND (
            cl.brand_name LIKE ?
            OR cl.tagline LIKE ?
            OR cl.industry LIKE ?
            OR cl.address LIKE ?
            OR cl.about_text LIKE ?
            OR cl.landing_extra_content LIKE ?
            OR s.name LIKE ?
            OR s.short_desc LIKE ?
          )
        ORDER BY cl.id DESC
        LIMIT 50
    ";
    $like = '%' . $q . '%';
    $stmt = $db->prepare($sql);
    $stmt->execute(array_fill(0, 8, $like));
    $results = $stmt->fetchAll();
}

$pageTitle = $q ? "搜尋「{$q}」｜店家好口碑" : '搜尋店家｜店家好口碑';
$metaDesc  = $q ? "搜尋「{$q}」找到 " . count($results) . " 家店家。" : '搜尋台南店家、服務、地點。';

require_once __DIR__ . '/main/layout_head.php';
?>

<div class="m-container">
  <div class="m-breadcrumb">
    <a href="<?= BASE_URL ?>/">首頁</a>
    <span class="sep">›</span>
    <span style="color:var(--m-primary); font-weight:600;">搜尋</span>
  </div>
</div>

<!-- 搜尋區 -->
<section style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); padding:30px 0;">
  <div class="m-container">
    <h1 style="font-size:1.6rem; font-weight:900; color:var(--m-primary); margin-bottom:16px;">
      搜尋店家
    </h1>
    <form method="GET" action="<?= BASE_URL ?>/search.php" style="position:relative; max-width:600px;">
      <input type="text" name="q" value="<?= h($q) ?>"
             placeholder="輸入店家名、服務、地點..."
             style="width:100%; padding:14px 22px; padding-right:120px; font-size:1rem; border:1.5px solid var(--m-border); border-radius:999px; outline:none; font-family:inherit;"
             autofocus>
      <button type="submit" class="m-btn m-btn-accent" style="position:absolute; right:4px; top:4px; bottom:4px; border-radius:999px;">
        🔍 搜尋
      </button>
    </form>
  </div>
</section>

<section class="m-section">
  <div class="m-container">

    <?php if (!$q): ?>
    <div style="text-align:center; padding:40px 20px; color:var(--m-text-muted);">
      <div style="font-size:3rem; margin-bottom:12px; opacity:.4;">🔍</div>
      請輸入關鍵字開始搜尋
    </div>

    <?php elseif (empty($results)): ?>
    <div style="text-align:center; padding:60px 20px;">
      <div style="font-size:3rem; margin-bottom:12px; opacity:.4;">🤔</div>
      <h3 style="color:var(--m-text-muted); font-weight:600; margin-bottom:8px;">
        找不到符合「<?= h($q) ?>」的店家
      </h3>
      <p style="color:var(--m-text-muted); font-size:.9rem;">試試其他關鍵字，或瀏覽分類</p>
      <a href="<?= BASE_URL ?>/category.php" class="m-btn m-btn-outline" style="margin-top:20px;">瀏覽所有分類</a>
    </div>

    <?php else: ?>
    <div style="margin-bottom:20px; color:var(--m-text-muted); font-size:.9rem;">
      找到 <strong style="color:var(--m-primary);"><?= count($results) ?></strong> 家店家符合「<strong style="color:var(--m-primary);"><?= h($q) ?></strong>」
    </div>

    <div class="m-store-grid">
      <?php foreach ($results as $cl): ?>
      <?php
        $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
        $linkUrl = clientStoreUrl($cl);  // 統一指向 /store/{slug} 行銷頁（prod 用 pretty URL）
      ?>
      <a class="m-store-card" href="<?= $linkUrl ?>">
        <div class="cover" <?= $heroImg ? 'style="background-image:url(\''.$heroImg.'\')"' : '' ?>>
          <?php if ($cl['cat_name']): ?>
          <span class="cat-tag"><?= h($cl['cat_icon']) ?> <?= h($cl['cat_name']) ?></span>
          <?php endif; ?>
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
