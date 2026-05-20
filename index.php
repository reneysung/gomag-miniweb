<?php
// index.php  ─  主站 www.gomag.com.tw 首頁
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';  // getCityMap()

$db = getDB();

// ─── 取分類列表（含每個分類的客戶數）────────────────
$categories = $db->query("
    SELECT c.id, c.name, c.slug, c.icon, c.description, c.banner_image_path,
           COUNT(cl.id) AS client_count
    FROM categories c
    LEFT JOIN clients cl ON cl.category_id = c.id AND cl.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
")->fetchAll();

// ─── 精選店家：每分類取 1 家（優先有圖、最新）──
$featuredClients = [];
$pickStmt = $db->prepare("
    SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline, cl.industry,
           cl.has_minisite, cl.external_website_url, cl.hero_image_path,
           cl.address, cl.phone,
           c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
    FROM clients cl
    LEFT JOIN categories c ON cl.category_id = c.id
    WHERE cl.is_active = 1 AND cl.category_id = ?
    ORDER BY (cl.hero_image_path IS NULL OR cl.hero_image_path = '') ASC, cl.id DESC
    LIMIT 1
");
foreach ($categories as $cat) {
    if ($cat['client_count'] == 0) continue;
    $pickStmt->execute([$cat['id']]);
    $row = $pickStmt->fetch();
    if ($row) $featuredClients[] = $row;
}

// ─── 本月新加入店家（依 created_at）─────────────────
$newThisMonth = $db->query("
    SELECT cl.id, cl.subdomain, cl.slug, cl.brand_name, cl.tagline,
           cl.has_minisite, cl.external_website_url, cl.hero_image_path,
           cl.address, cl.phone, cl.created_at,
           c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug
    FROM clients cl
    LEFT JOIN categories c ON cl.category_id = c.id
    WHERE cl.is_active = 1
      AND cl.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
    ORDER BY cl.created_at DESC
    LIMIT 6
")->fetchAll();

// ─── 統計總客戶數 ─────────────────────────────────
$totalClients = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_active=1")->fetchColumn();

// ─── 縣市分布（用於首頁縣市選單）──────────────────
$cityNameToSlug = array_flip(getCityMap());  // 唯一來源：cities 表
$cityRegex = '臺北市|台北市|新北市|桃園市|臺中市|台中市|臺南市|台南市|高雄市|基隆市|新竹市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義市|嘉義縣|屏東縣|宜蘭縣|花蓮縣|臺東縣|台東縣';
$cityRows = $db->query("SELECT address FROM clients WHERE is_active=1 AND address IS NOT NULL AND address != ''")->fetchAll();
$cityCounts = [];
foreach ($cityRows as $r) {
    if (preg_match('/^(' . $cityRegex . ')/u', $r['address'], $m)) {
        $c = str_replace('臺', '台', $m[1]);
        $cityCounts[$c] = ($cityCounts[$c] ?? 0) + 1;
    }
}
arsort($cityCounts);
// 只保留有 ≥ 3 家的縣市，且有 slug 對映
$cityCounts = array_filter($cityCounts, function($cnt, $name) use ($cityNameToSlug) {
    return $cnt >= 3 && isset($cityNameToSlug[$name]);
}, ARRAY_FILTER_USE_BOTH);

// ─── 取啟用中的 Banner（如有）─────────────────────
$banners = [];
try {
    $banners = $db->query("
        SELECT id, image_path, title, subtitle, link_url
        FROM banners
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ")->fetchAll();
} catch (PDOException $e) {
    // 表不存在時忽略（首次部署）
    $banners = [];
}

$pageTitle = getPlatformSetting('main_meta_title', '店家好口碑｜全台在地店家平台');
$metaDesc  = "匯集 {$totalClients}+ 家全台優質店家：餐飲美食、居家服務、美容美髮、專業服務 — 一站式店家平台。";

require_once __DIR__ . '/main/layout_head.php';
?>

<!-- ═══════ Hero（g-* 設計，輪播 banners + 文案 overlay）═══════ -->
<?php if (!empty($banners)): ?>
<section class="g-hero-carousel" id="g-hero">
  <?php foreach ($banners as $i => $b):
    $imgUrl = BASE_URL . '/' . h($b['image_path']);
  ?>
  <div class="g-hero-slide<?= $i === 0 ? ' is-active' : '' ?>" style="background-image:url('<?= $imgUrl ?>');">
    <div class="g-hero-slide-overlay"></div>
    <div class="g-hero-slide-inner">
      <?php if ($b['title']): ?>
      <div class="g-hero-slide-tag">
        <span class="g-hero-slide-tag-dot"></span>
        <span><?= h($b['subtitle'] ?: '在地優質店家・口碑驗證') ?></span>
      </div>
      <h1 class="g-hero-slide-title"><?= h($b['title']) ?></h1>
      <?php endif; ?>
      <form class="g-hero-slide-search" method="GET" action="<?= BASE_URL ?>/search.php" role="search">
        <input type="text" name="q" placeholder="搜尋店家、服務、地點…" autocomplete="off">
        <button type="submit" aria-label="搜尋">🔍</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (count($banners) > 1): ?>
  <button class="g-hero-nav g-hero-prev" aria-label="上一張">‹</button>
  <button class="g-hero-nav g-hero-next" aria-label="下一張">›</button>
  <div class="g-hero-dots">
    <?php foreach ($banners as $i => $b): ?>
    <button class="g-hero-dot<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= $i ?>" aria-label="第 <?= $i + 1 ?> 張"></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<script>
(function() {
  var slides = document.querySelectorAll('#g-hero .g-hero-slide');
  var dots   = document.querySelectorAll('#g-hero .g-hero-dot');
  if (slides.length < 2) return;
  var idx = 0;
  function go(n) {
    idx = (n + slides.length) % slides.length;
    slides.forEach(function(s, i) { s.classList.toggle('is-active', i === idx); });
    dots.forEach(function(d, i) { d.classList.toggle('is-active', i === idx); });
  }
  document.querySelector('#g-hero .g-hero-prev').addEventListener('click', function() { go(idx - 1); reset(); });
  document.querySelector('#g-hero .g-hero-next').addEventListener('click', function() { go(idx + 1); reset(); });
  dots.forEach(function(d) {
    d.addEventListener('click', function() { go(parseInt(d.dataset.slide, 10)); reset(); });
  });
  var timer = null;
  function reset() { if (timer) clearInterval(timer); timer = setInterval(function() { go(idx + 1); }, 6000); }
  reset();
})();
</script>
<?php else: ?>
<section class="g-hero-carousel">
  <div class="g-hero-slide is-active" style="background:linear-gradient(135deg,#2a2a2a,#0f0f0f);">
    <div class="g-hero-slide-inner">
      <div class="g-hero-slide-tag">
        <span class="g-hero-slide-tag-dot"></span>
        <span>全台 <?= $totalClients ?>+ 家在地優質店家</span>
      </div>
      <h1 class="g-hero-slide-title">找在地好店家，<br><span>就上店家好口碑</span></h1>
      <p class="g-hero-slide-desc">從餐飲到專業服務，全台口碑商家一站式找對店家。</p>
      <form class="g-hero-slide-search" method="GET" action="<?= BASE_URL ?>/search.php" role="search">
        <input type="text" name="q" placeholder="搜尋店家、服務、地點…" autocomplete="off">
        <button type="submit" aria-label="搜尋">🔍</button>
      </form>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 12 大分類 explore cards ═══════ -->
<section class="g-section">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title">瀏覽分類</h2>
      <p class="g-section-sub"><?= count($categories) ?> 大分類，找到您要的服務</p>
    </div>
    <a href="<?= BASE_URL ?>/category.php" class="g-section-link">所有分類</a>
  </div>
  <div class="g-explore-grid g-explore-grid--4col">
    <?php foreach ($categories as $cat):
      // 卡片背景優先用 categories.banner_image_path（分類專屬通用美圖）
      // 沒設才 fallback 到該分類底下第一家客戶的 hero
      if (!empty($cat['banner_image_path'])) {
          $coverUrl = BASE_URL . '/' . h($cat['banner_image_path']);
      } else {
          $coverStmt = $db->prepare("SELECT hero_image_path FROM clients WHERE category_id=? AND is_active=1 AND hero_image_path IS NOT NULL AND hero_image_path != '' AND COALESCE(is_placeholder,0)=0 ORDER BY id DESC LIMIT 1");
          $coverStmt->execute([$cat['id']]);
          $cover = $coverStmt->fetchColumn();
          $coverUrl = $cover ? BASE_URL . '/' . h($cover) : '';
      }
    ?>
    <a class="g-explore-card" href="<?= BASE_URL ?>/category.php?slug=<?= h($cat['slug']) ?>">
      <?php if ($coverUrl): ?>
      <div class="g-explore-card-img" style="background-image:url('<?= h($coverUrl) ?>');"></div>
      <?php else: ?>
      <div class="g-explore-card-fallback"><?= h($cat['icon']) ?></div>
      <?php endif; ?>
      <div class="g-explore-card-overlay">
        <div class="g-explore-card-name"><?= h($cat['icon']) ?> <?= h($cat['name']) ?></div>
        <div class="g-explore-card-count"><?= $cat['client_count'] ?> 家店家</div>
      </div>
      <div class="g-explore-card-arrow">→</div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ═══════ 縣市瀏覽 ═══════ -->
<?php if (!empty($cityCounts)): ?>
<section class="g-section" style="background:var(--g-bg-alt);">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title">📍 依縣市瀏覽</h2>
      <p class="g-section-sub">在地店家依城市分區，找你附近的口碑商家</p>
    </div>
    <a href="<?= BASE_URL ?>/city.php" class="g-section-link">所有縣市</a>
  </div>
  <div class="g-store-grid" style="grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));">
    <?php foreach ($cityCounts as $cityName => $cnt):
      $citySlug = $cityNameToSlug[$cityName];
    ?>
    <a class="g-store-card" href="<?= BASE_URL ?>/city.php?slug=<?= h($citySlug) ?>" style="text-align:center;">
      <div class="g-store-img" style="aspect-ratio:1.4; background:linear-gradient(135deg, var(--g-bg-alt), var(--g-bg)); display:grid; place-items:center; font-size:48px;">📍</div>
      <div class="g-store-meta-top" style="justify-content:center;">
        <div class="g-store-name" style="font-size:18px;"><?= h($cityName) ?></div>
      </div>
      <div class="g-store-loc" style="font-family:var(--g-font-num); font-weight:700; color:var(--g-accent);"><?= $cnt ?> 家店家</div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 分類精選店家 ═══════ -->
<?php if (!empty($featuredClients)): ?>
<section class="g-section">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title">分類精選</h2>
      <p class="g-section-sub">每個分類為您挑選一家代表店家</p>
    </div>
  </div>
  <div class="g-store-grid">
    <?php foreach ($featuredClients as $cl):
      $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
      $linkUrl = BASE_URL . '/store.php?sub=' . urlencode($cl['subdomain'] ?? $cl['slug']);
    ?>
    <a class="g-store-card" href="<?= $linkUrl ?>">
      <div class="g-store-img" <?= $heroImg ? 'style="background-image:url(\''.$heroImg.'\')"' : '' ?>>
        <?php if (!$heroImg): ?>
        <div class="g-store-img-fallback">
          <span class="icon"><?= h($cl['cat_icon'] ?? '🏪') ?></span>
          <span class="label"><?= h($cl['cat_name'] ?? '') ?></span>
        </div>
        <?php endif; ?>
      </div>
      <div class="g-store-meta-top">
        <div class="g-store-name"><?= h($cl['brand_name']) ?></div>
      </div>
      <div class="g-store-loc"><?= h($cl['cat_icon'] ?? '') ?> <?= h($cl['cat_name'] ?? '') ?></div>
      <?php if ($cl['tagline']): ?>
      <div class="g-store-cat-label"><?= h(mb_strimwidth($cl['tagline'], 0, 36, '…', 'UTF-8')) ?></div>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 本月新加入 ═══════ -->
<?php if (!empty($newThisMonth)): ?>
<section class="g-section" style="background:var(--g-bg-alt);">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title">🆕 本月新加入</h2>
      <p class="g-section-sub"><?= date('Y 年 n 月') ?>・新增 <?= count($newThisMonth) ?> 家店家</p>
    </div>
  </div>
  <div class="g-store-grid">
    <?php foreach ($newThisMonth as $cl):
      $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
      $linkUrl = BASE_URL . '/store.php?sub=' . urlencode($cl['subdomain'] ?? $cl['slug']);
      $daysAgo = (int)((time() - strtotime($cl['created_at'])) / 86400);
      $timeLabel = $daysAgo == 0 ? '今天' : ($daysAgo == 1 ? '昨天' : "{$daysAgo} 天前");
    ?>
    <a class="g-store-card" href="<?= $linkUrl ?>">
      <div class="g-store-img" <?= $heroImg ? 'style="background-image:url(\''.$heroImg.'\')"' : '' ?>>
        <?php if (!$heroImg): ?>
        <div class="g-store-img-fallback">
          <span class="icon"><?= h($cl['cat_icon'] ?? '🏪') ?></span>
          <span class="label"><?= h($cl['cat_name'] ?? '') ?></span>
        </div>
        <?php endif; ?>
        <span class="g-store-badge" style="background:var(--g-accent); color:white;">⏰ <?= $timeLabel ?></span>
      </div>
      <div class="g-store-meta-top">
        <div class="g-store-name"><?= h($cl['brand_name']) ?></div>
      </div>
      <div class="g-store-loc"><?= h($cl['cat_icon'] ?? '') ?> <?= h($cl['cat_name'] ?? '') ?></div>
      <?php if ($cl['tagline']): ?>
      <div class="g-store-cat-label"><?= h(mb_strimwidth($cl['tagline'], 0, 36, '…', 'UTF-8')) ?></div>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ B 端 CTA banner ═══════ -->
<div class="g-banner-wrap">
  <div class="g-banner">
    <div class="g-banner-bg"></div>
    <div class="g-banner-text">
      <div class="g-banner-eyebrow">B2B Partnership</div>
      <h3 class="g-banner-title">想讓店家曝光？</h3>
      <p class="g-banner-desc">加入店家好口碑，專屬行銷頁 + 小官網一次擁有・月費 NT$300 起。業務團隊到店服務，零學習成本。</p>
    </div>
    <a href="mailto:<?= h(getPlatformSetting('contact_email', 'contact@gomag.com.tw')) ?>" class="g-banner-btn">立即聯絡 →</a>
  </div>
</div>

<!-- ═══════ 大 CTA ═══════ -->
<section class="g-cta">
  <div class="g-cta-inner">
    <div class="g-cta-eyebrow">Make it Yours</div>
    <h2 class="g-cta-title">把生意做大，<br>從找到對的客人開始。</h2>
    <p class="g-cta-desc">店家好口碑專注在地口碑曝光 — 月費 NT$300 起，業務團隊到店服務，幫你把生意做大。</p>
    <div class="g-cta-btns">
      <a href="mailto:<?= h(getPlatformSetting('contact_email', 'contact@gomag.com.tw')) ?>" class="g-cta-btn g-cta-btn-primary">立即聯絡</a>
      <a href="<?= BASE_URL ?>/category.php" class="g-cta-btn g-cta-btn-secondary">瀏覽店家</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
