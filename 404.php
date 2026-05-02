<?php
// 404.php — 自訂找不到頁面（搭配 .htaccess ErrorDocument 404 /404.php）
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

http_response_code(404);
header('X-Robots-Tag: noindex');

$db = getDB();

// 抓 12 個分類做建議連結
$cats = [];
try {
    $cats = $db->query("SELECT name, slug, icon FROM categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll();
} catch (\Throwable $e) {}

// 抓有店家的城市做建議
$citiesByName = [];
try {
    $citiesByName = $db->query("SELECT slug, full_name FROM cities WHERE is_active=1 ORDER BY sort_order, full_name")->fetchAll();
} catch (\Throwable $e) {}

$pageTitle = '找不到頁面｜店家好口碑';
$metaDesc  = '抱歉，您要找的頁面不存在。回首頁或瀏覽分類找到您需要的店家。';
$canonical = BASE_URL . '/404';
$extraCss  = [BASE_URL . '/assets/css/gomag.css'];

require_once __DIR__ . '/main/layout_head.php';
?>

<section class="g-section" style="text-align:center; padding-top:80px; padding-bottom:40px;">
  <div class="g-city-intro-eyebrow" style="font-size:13px; letter-spacing:0.15em; color:var(--g-accent); font-weight:700; margin-bottom:12px;">404 NOT FOUND</div>
  <h1 style="font-family:var(--g-font-num); font-size:clamp(96px, 18vw, 180px); line-height:1; font-weight:900; color:var(--g-ink); letter-spacing:-0.06em; margin:0 0 24px;">404</h1>
  <h2 class="g-section-title" style="font-size:clamp(28px, 4vw, 40px); margin-bottom:16px;">這條路<span style="color:var(--g-accent)">沒店家</span>。</h2>
  <p class="g-section-sub" style="max-width:520px; margin:0 auto 32px;">您要找的頁面不存在或已搬家。試試下面的入口找到對的店：</p>
  <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:12px; margin-bottom:8px;">
    <a href="<?= BASE_URL ?>/" class="g-cta-btn g-cta-btn-primary" style="background:var(--g-ink); color:white; padding:12px 24px; border-radius:100px; text-decoration:none; font-weight:600;">← 回首頁</a>
    <a href="<?= BASE_URL ?>/category.php" class="g-cta-btn g-cta-btn-secondary" style="background:white; color:var(--g-ink); border:1.5px solid var(--g-border); padding:12px 24px; border-radius:100px; text-decoration:none; font-weight:600;">瀏覽分類</a>
    <a href="<?= BASE_URL ?>/city.php" class="g-cta-btn g-cta-btn-secondary" style="background:white; color:var(--g-ink); border:1.5px solid var(--g-border); padding:12px 24px; border-radius:100px; text-decoration:none; font-weight:600;">瀏覽縣市</a>
  </div>
</section>

<?php if ($cats): ?>
<section class="g-section" style="padding-top:32px;">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title" style="font-size:22px;">依分類探索</h2>
    </div>
  </div>
  <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center;">
    <?php foreach ($cats as $c): ?>
    <a href="<?= BASE_URL ?>/category.php?slug=<?= h($c['slug']) ?>"
       style="display:inline-flex; align-items:center; gap:6px; background:white; border:1.5px solid var(--g-border); color:var(--g-ink); padding:8px 14px; border-radius:100px; font-size:14px; text-decoration:none; transition:all .2s;"
       onmouseover="this.style.borderColor='var(--g-ink)'; this.style.background='var(--g-bg-alt)';"
       onmouseout="this.style.borderColor='var(--g-border)'; this.style.background='white';">
      <?= h($c['icon'] ?? '') ?> <?= h($c['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($citiesByName): ?>
<section class="g-section">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title" style="font-size:22px;">依縣市瀏覽</h2>
    </div>
  </div>
  <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center;">
    <?php foreach ($citiesByName as $city): ?>
    <a href="<?= BASE_URL ?>/city.php?slug=<?= h($city['slug']) ?>"
       style="display:inline-flex; align-items:center; gap:6px; background:white; border:1.5px solid var(--g-border); color:var(--g-ink); padding:8px 14px; border-radius:100px; font-size:14px; text-decoration:none; transition:all .2s;"
       onmouseover="this.style.borderColor='var(--g-ink)'; this.style.background='var(--g-bg-alt)';"
       onmouseout="this.style.borderColor='var(--g-border)'; this.style.background='white';">
      📍 <?= h($city['full_name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
