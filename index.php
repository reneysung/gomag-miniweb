<?php
// index.php  ─  主站 www.gomag.com.tw 首頁
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();

// ─── 取分類列表（含每個分類的客戶數）────────────────
$categories = $db->query("
    SELECT c.id, c.name, c.slug, c.icon, c.description,
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
$cityNameToSlug = [
    '台南市' => 'tainan', '高雄市' => 'kaohsiung', '嘉義市' => 'chiayi',
    '台中市' => 'taichung', '台北市' => 'taipei', '新北市' => 'newtaipei',
    '桃園市' => 'taoyuan', '台東縣' => 'taitung', '屏東縣' => 'pingtung',
    '新竹市' => 'hsinchu', '宜蘭縣' => 'yilan', '花蓮縣' => 'hualien',
];
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

<!-- ═══════ Hero 區 ═══════ -->
<?php if (!empty($banners)): ?>
<!-- ──── 有 Banner：圖片輪播模式 ──── -->
<section class="m-hero m-hero-carousel" id="heroCarousel">
  <div class="hero-slides">
    <?php foreach ($banners as $i => $b): ?>
    <?php
      $imgUrl = BASE_URL . '/' . h($b['image_path']);
      $hasLink = !empty($b['link_url']);
      $tag = $hasLink ? 'a' : 'div';
      $linkAttr = $hasLink ? 'href="' . h($b['link_url']) . '"' : '';
    ?>
    <<?= $tag ?> class="hero-slide<?= $i === 0 ? ' active' : '' ?>" <?= $linkAttr ?>
       style="background-image:url('<?= $imgUrl ?>');">
      <?php if ($b['title'] || $b['subtitle']): ?>
      <div class="hero-slide-overlay"></div>
      <div class="hero-slide-content">
        <?php if ($b['title']): ?>
        <h1><?= h($b['title']) ?></h1>
        <?php endif; ?>
        <?php if ($b['subtitle']): ?>
        <p><?= h($b['subtitle']) ?></p>
        <?php endif; ?>
        <form class="m-hero-search" method="GET" action="<?= BASE_URL ?>/search.php"
              onclick="event.stopPropagation();" onsubmit="event.stopPropagation();">
          <input type="text" name="q" placeholder="搜尋店家、服務、地點..." autocomplete="off">
          <button type="submit">搜尋</button>
        </form>
      </div>
      <?php endif; ?>
    </<?= $tag ?>>
    <?php endforeach; ?>
  </div>

  <?php if (count($banners) > 1): ?>
  <!-- 控制鈕 -->
  <button class="hero-nav hero-prev" onclick="heroSlide(-1)" aria-label="上一張">‹</button>
  <button class="hero-nav hero-next" onclick="heroSlide(1)" aria-label="下一張">›</button>
  <!-- 指示點 -->
  <div class="hero-dots">
    <?php foreach ($banners as $i => $b): ?>
    <button class="hero-dot<?= $i === 0 ? ' active' : '' ?>"
            onclick="heroGoTo(<?= $i ?>)" aria-label="第 <?= $i + 1 ?> 張"></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<style>
.m-hero-carousel {
  position: relative;
  padding: 0;
  overflow: hidden;
  min-height: 480px;
  background: #1c3d3d;
}
.m-hero-carousel .hero-slides {
  position: relative;
  width: 100%;
  height: 480px;
}
.m-hero-carousel .hero-slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  opacity: 0;
  transition: opacity .8s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: #fff;
}
.m-hero-carousel .hero-slide.active { opacity: 1; z-index: 2; }
.m-hero-carousel .hero-slide-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,.2) 0%, rgba(0,0,0,.55) 100%);
}
.m-hero-carousel .hero-slide-content {
  position: relative;
  z-index: 2;
  text-align: center;
  padding: 0 20px;
  max-width: 800px;
  width: 100%;
}
.m-hero-carousel .hero-slide-content h1 {
  font-size: clamp(1.8rem, 5vw, 3rem);
  font-weight: 800;
  margin-bottom: 14px;
  text-shadow: 0 2px 12px rgba(0,0,0,.5);
  line-height: 1.2;
}
.m-hero-carousel .hero-slide-content p {
  font-size: clamp(1rem, 2vw, 1.2rem);
  margin-bottom: 24px;
  text-shadow: 0 2px 8px rgba(0,0,0,.5);
}

.m-hero-carousel .hero-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px; height: 44px;
  background: rgba(255,255,255,.2);
  backdrop-filter: blur(8px);
  border: none;
  border-radius: 50%;
  color: #fff;
  font-size: 1.8rem;
  font-weight: 300;
  cursor: pointer;
  z-index: 5;
  transition: background .2s;
  display: flex; align-items: center; justify-content: center;
}
.m-hero-carousel .hero-nav:hover { background: rgba(255,255,255,.35); }
.m-hero-carousel .hero-prev { left: 20px; }
.m-hero-carousel .hero-next { right: 20px; }

.m-hero-carousel .hero-dots {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 8px;
  z-index: 5;
}
.m-hero-carousel .hero-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: rgba(255,255,255,.5);
  border: none;
  cursor: pointer;
  padding: 0;
  transition: all .2s;
}
.m-hero-carousel .hero-dot.active {
  background: #fff;
  width: 28px;
  border-radius: 5px;
}

@media (max-width: 768px) {
  .m-hero-carousel,
  .m-hero-carousel .hero-slides { height: 360px; min-height: 360px; }
  .m-hero-carousel .hero-nav { width: 36px; height: 36px; font-size: 1.4rem; }
  .m-hero-carousel .hero-prev { left: 10px; }
  .m-hero-carousel .hero-next { right: 10px; }
}
</style>

<script>
(function() {
  const slides = document.querySelectorAll('#heroCarousel .hero-slide');
  const dots   = document.querySelectorAll('#heroCarousel .hero-dot');
  if (slides.length < 2) return;
  let idx = 0;
  let timer = null;

  function show(i) {
    idx = (i + slides.length) % slides.length;
    slides.forEach((s, k) => s.classList.toggle('active', k === idx));
    dots.forEach((d, k) => d.classList.toggle('active', k === idx));
  }
  function start() {
    stop();
    timer = setInterval(() => show(idx + 1), 5000);
  }
  function stop() { if (timer) clearInterval(timer); }

  window.heroSlide = (dir) => { show(idx + dir); start(); };
  window.heroGoTo  = (i)   => { show(i); start(); };

  const carousel = document.getElementById('heroCarousel');
  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);

  // 觸控滑動
  let startX = 0;
  carousel.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, {passive:true});
  carousel.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 50) show(idx + (dx < 0 ? 1 : -1));
    start();
  });

  start();
})();
</script>

<?php else: ?>
<!-- ──── 沒有 Banner：預設漸層模式 ──── -->
<section class="m-hero">
  <div class="m-hero-inner">
    <h1>找在地好店家<br><span class="accent">就上店家好口碑</span></h1>
    <p>匯集在地 <?= $totalClients ?>+ 家優質商家，從餐飲到專業服務，找對店家從這裡開始。</p>
    <form class="m-hero-search" method="GET" action="<?= BASE_URL ?>/search.php">
      <input type="text" name="q" placeholder="搜尋店家、服務、地點..." autocomplete="off">
      <button type="submit">搜尋</button>
    </form>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 分類入口 ═══════ -->
<section class="m-section">
  <div class="m-container">
    <h2 class="m-section-title">瀏覽分類</h2>
    <p class="m-section-sub">依產業類別找到您要的店家</p>

    <div class="m-cat-grid">
      <?php foreach ($categories as $cat): ?>
      <a class="m-cat-card" href="<?= BASE_URL ?>/category.php?slug=<?= h($cat['slug']) ?>">
        <div class="icon"><?= h($cat['icon']) ?></div>
        <div class="name"><?= h($cat['name']) ?></div>
        <div class="count"><?= $cat['client_count'] ?> 家店家</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════ 縣市入口 ═══════ -->
<?php if (!empty($cityCounts)): ?>
<section class="m-section" style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); border-bottom:1px solid var(--m-border);">
  <div class="m-container">
    <h2 class="m-section-title">📍 依縣市瀏覽</h2>
    <p class="m-section-sub">在地店家依城市分區，找你附近的口碑商家</p>

    <div class="m-cat-grid" style="grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));">
      <?php foreach ($cityCounts as $cityName => $cnt):
          $citySlug = $cityNameToSlug[$cityName];
      ?>
      <a class="m-cat-card" href="<?= BASE_URL ?>/city.php?slug=<?= h($citySlug) ?>">
        <div class="icon" style="font-size:2.4rem;">📍</div>
        <div class="name"><?= h($cityName) ?></div>
        <div class="count"><?= $cnt ?> 家店家</div>
      </a>
      <?php endforeach; ?>
      <a class="m-cat-card" href="<?= BASE_URL ?>/city.php" style="border-style:dashed;">
        <div class="icon" style="font-size:2rem; opacity:.5;">→</div>
        <div class="name" style="opacity:.7;">看全部縣市</div>
        <div class="count">&nbsp;</div>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════ 精選店家（每分類一家）═══════ -->
<section class="m-section" style="background:var(--m-bg-alt); border-top:1px solid var(--m-border); border-bottom:1px solid var(--m-border);">
  <div class="m-container">
    <h2 class="m-section-title">分類精選</h2>
    <p class="m-section-sub">每個分類為您挑選一家代表店家</p>

    <?php if (empty($featuredClients)): ?>
    <div style="text-align:center; padding:40px; color:var(--m-text-muted);">
      尚無店家資料
    </div>
    <?php else: ?>
    <div class="m-store-grid">
      <?php foreach ($featuredClients as $cl): ?>
      <?php
        $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
        $linkUrl = BASE_URL . '/store.php?sub=' . urlencode($cl['subdomain'] ?? $cl['slug']);
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

<!-- ═══════ 本月新加入 ═══════ -->
<?php if (!empty($newThisMonth)): ?>
<section class="m-section">
  <div class="m-container">
    <h2 class="m-section-title">
      <span style="background:var(--m-accent); color:#fff; font-size:.75rem; padding:3px 10px; border-radius:20px; vertical-align:middle; margin-right:8px;">NEW</span>
      本月新加入
    </h2>
    <p class="m-section-sub"><?= date('Y年n月') ?>．新增 <?= count($newThisMonth) ?> 家店家</p>

    <div class="m-store-grid">
      <?php foreach ($newThisMonth as $cl): ?>
      <?php
        $heroImg = $cl['hero_image_path'] ? BASE_URL . '/' . h($cl['hero_image_path']) : '';
        $linkUrl = BASE_URL . '/store.php?sub=' . urlencode($cl['subdomain'] ?? $cl['slug']);
        $daysAgo = (int)((time() - strtotime($cl['created_at'])) / 86400);
        $timeLabel = $daysAgo == 0 ? '今天' : ($daysAgo == 1 ? '昨天' : "{$daysAgo} 天前");
      ?>
      <a class="m-store-card" href="<?= $linkUrl ?>">
        <div class="cover" <?= $heroImg ? 'style="background-image:url(\''.$heroImg.'\')"' : '' ?>>
          <?php if ($cl['cat_name']): ?>
          <span class="cat-tag"><?= h($cl['cat_icon']) ?> <?= h($cl['cat_name']) ?></span>
          <?php endif; ?>
          <span class="cat-tag" style="position:absolute; top:12px; right:12px; left:auto; background:var(--m-accent);">
            ⏰ <?= $timeLabel ?>
          </span>
        </div>
        <div class="body">
          <h3><?= h($cl['brand_name']) ?></h3>
          <?php if ($cl['tagline']): ?>
          <div class="tagline"><?= h($cl['tagline']) ?></div>
          <?php endif; ?>
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
  </div>
</section>
<?php endif; ?>

<!-- ═══════ CTA 區 ═══════ -->
<section class="m-section" style="text-align:center;">
  <div class="m-container">
    <h2 class="m-section-title">想讓店家曝光？</h2>
    <p class="m-section-sub">加入店家好口碑平台，獲得專屬行銷頁與小官網・月費 NT$300 起</p>
    <a href="mailto:contact@gomag.com.tw" class="m-btn m-btn-primary" style="padding:14px 32px; font-size:1rem;">
      聯絡我們申請加入 →
    </a>
  </div>
</section>

<?php require_once __DIR__ . '/main/layout_foot.php'; ?>
