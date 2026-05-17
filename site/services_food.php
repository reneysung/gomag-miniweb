<?php
/**
 * site/services_food.php
 * 餐飲業菜單頁（Hawksmoor 風 — 暗黑 + 攝影 + 不顯示價格）
 * 由 site/services.php 在 $isFood 時 require 進來
 *
 * 上文已備好：$site, $sub, $slug, $client, $services, $lineUrl, $phone
 */
$photos = foodPhotoSet();

// 不再 hardcode 假菜單 — DB 空 → 顯示「整理中」
$menu = [];
if (!empty($services)) {
    $menu = [
        ['title' => '主廚精選', 'subtitle' => 'Chef\'s Selection', 'cover' => $photos['fire'], 'items' => array_map(fn($s) => ['name' => $s['name'], 'desc' => $s['short_desc'] ?? ''], $services)],
    ];
}

require __DIR__ . '/layout_head.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">

<!-- Header / topbar 暗色覆蓋（同 index_food） -->
<style>
.topbar-strip { display: none !important; }
.site-header { background: rgba(15,12,10,0.92) !important; backdrop-filter: blur(14px) !important; -webkit-backdrop-filter: blur(14px) !important; border-bottom: 1px solid rgba(245,230,200,0.1) !important; box-shadow: none !important; }
.site-logo { color: #f5e6c8 !important; font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-weight: 500; font-size: 1.35rem; letter-spacing: 0.02em; }
.logo-text .sub { color: rgba(245,230,200,0.55) !important; font-family: 'Cormorant Garamond', serif; font-style: italic; letter-spacing: 0.15em; text-transform: uppercase; }
.site-nav a { color: rgba(245,230,200,0.78) !important; font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: 1rem; letter-spacing: 0.06em; border-radius: 0 !important; padding: 8px 14px !important; }
.site-nav a:hover, .site-nav a.active { background: transparent !important; color: #f5e6c8 !important; border-bottom: 1px solid var(--g-accent, #c89968) !important; padding-bottom: 7px !important; }
.site-nav .btn-contact { background: transparent !important; color: #f5e6c8 !important; border: 1.5px solid rgba(245,230,200,0.4); border-radius: 0 !important; padding: 9px 22px !important; margin-left: 12px !important; font-family: 'Cormorant Garamond', serif; letter-spacing: 0.1em; font-size: .92rem !important; }
.site-nav .btn-contact:hover { border-color: #f5e6c8 !important; background: rgba(245,230,200,0.08) !important; filter: none !important; }
.nav-toggle { border-color: rgba(245,230,200,0.4) !important; color: #f5e6c8 !important; background: transparent !important; }
.nav-toggle:hover { background: rgba(245,230,200,0.1) !important; color: #f5e6c8 !important; border-color: #f5e6c8 !important; }
.mobile-menu { background: rgba(15,12,10,0.98) !important; border-top: 1px solid rgba(245,230,200,0.1) !important; }
.mobile-menu a { color: rgba(245,230,200,0.78) !important; border-bottom: 1px solid rgba(245,230,200,0.08) !important; }
.mobile-menu a.highlight { color: var(--g-accent, #c89968) !important; }
.bottom-tabbar { background: rgba(15,12,10,0.96) !important; border-top: 1px solid rgba(245,230,200,0.1) !important; box-shadow: 0 -2px 16px rgba(0,0,0,0.4) !important; }
.tab-item { color: rgba(245,230,200,0.55) !important; }
.tab-item.active, .tab-item:hover { color: #f5e6c8 !important; }
.tab-item.accent { color: var(--g-accent, #c89968) !important; }
:root { --dine-cream: #f5e6c8; --dine-bg: #0f0c0a; --dine-bg-alt: #1a1612; }
body { background: var(--dine-bg); color: var(--dine-cream); }
</style>

<!-- ══ Hero（小） ═══════════════════════════════════════════════════ -->
<?php
$heroImg = !empty($client['hero_image_path']) ? BASE_URL . '/' . h($client['hero_image_path']) : $photos['fire'];
?>
<section class="dinemenu-hero" style="background-image:url('<?= h($heroImg) ?>');">
  <div class="dinemenu-hero-overlay"></div>
  <div class="dinemenu-hero-content">
    <div class="dinemenu-eyebrow animate-in">
      <span class="dinemenu-line"></span>
      <span>OUR MENU</span>
      <span class="dinemenu-line"></span>
    </div>
    <h1 class="dinemenu-title animate-in delay-1">料理 · 菜單</h1>
    <p class="dinemenu-tagline animate-in delay-2">嚴選食材・用心料理・每一道都是暖心之作</p>
  </div>
</section>

<!-- ══ Anchor Nav（分類錨點） ════════════════════════════════════════ -->
<?php if (count($menu) > 1): ?>
<nav class="dinemenu-anchors">
  <div class="dinemenu-anchors-track">
    <?php foreach ($menu as $i => $m): ?>
    <a href="#dinemenu-cat-<?= $i ?>" class="dinemenu-anchor"><?= h($m['title']) ?></a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<!-- 空狀態：menu 空時顯示「整理中」 -->
<?php if (empty($menu)): ?>
<section style="padding:80px 20px;background:#0f0c0a;color:rgba(245,230,200,.7)">
  <div style="max-width:600px;margin:0 auto;padding:48px 24px;text-align:center;background:rgba(245,230,200,.04);border:1px solid rgba(245,230,200,.15);border-radius:14px">
    <div style="font-size:3rem;margin-bottom:14px">🍽️</div>
    <h3 style="font-size:1.2rem;font-weight:600;color:#f5e6c8;margin-bottom:8px;font-family:'Cormorant Garamond',serif">菜單整理中</h3>
    <p style="color:rgba(245,230,200,.6);font-size:.95rem;margin-bottom:24px">最新菜單與套餐優惠正在製作中，歡迎來電訂位諮詢。</p>
    <?php if (!empty($client['phone'])): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" style="display:inline-block;padding:12px 28px;background:#f5e6c8;color:#0f0c0a;border-radius:0;text-decoration:none;font-weight:600;letter-spacing:.1em">📞 訂位 <?= h($client['phone']) ?></a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ Menu Categories ═══════════════════════════════════════════════ -->
<?php foreach ($menu as $i => $cat): ?>
<section id="dinemenu-cat-<?= $i ?>" class="dinemenu-cat <?= $i % 2 === 1 ? 'is-alt' : '' ?>">
  <div class="dinemenu-cat-grid">
    <!-- 左：大封面圖 + 編號 + 分類 -->
    <div class="dinemenu-cat-cover animate-in" style="background-image:url('<?= h($cat['cover']) ?>');">
      <div class="dinemenu-cat-cover-meta">
        <div class="dinemenu-cat-num"><?= str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) ?></div>
        <div class="dinemenu-cat-subtitle"><?= h($cat['subtitle']) ?></div>
      </div>
    </div>

    <!-- 右：菜色清單 -->
    <div class="dinemenu-cat-items animate-in delay-1">
      <h2 class="dinemenu-cat-title"><?= h($cat['title']) ?></h2>
      <div class="dinemenu-items-list">
        <?php foreach ($cat['items'] as $j => $item): ?>
        <div class="dinemenu-item">
          <div class="dinemenu-item-dot">·</div>
          <div class="dinemenu-item-text">
            <h3 class="dinemenu-item-name"><?= h($item['name']) ?></h3>
            <p class="dinemenu-item-desc"><?= h($item['desc']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endforeach; ?>

<!-- ══ Reservation CTA ═══════════════════════════════════════════════ -->
<section class="dinemenu-cta" style="background-image:url('<?= h($photos['table']) ?>');">
  <div class="dinemenu-cta-overlay"></div>
  <div class="dinemenu-cta-inner">
    <div class="dinemenu-eyebrow" style="justify-content:center; color:rgba(245,230,200,0.7)">
      <span class="dinemenu-line"></span>
      <span>RESERVATIONS</span>
      <span class="dinemenu-line"></span>
    </div>
    <h2 class="dinemenu-cta-title">準備好品嚐了嗎？</h2>
    <p class="dinemenu-cta-sub">請提前預約，我們為您準備一張靜候的桌</p>
    <div class="dinemenu-cta-actions">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="dinemenu-btn-book" target="_blank">BOOK A TABLE · 立即訂位</a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="dinemenu-btn-phone">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
/* ════════════════════════════════════════════════════════════════
 * 餐飲菜單頁專屬樣式（Hawksmoor 風）
 * ════════════════════════════════════════════════════════════════ */
.dinemenu-eyebrow { display: flex; align-items: center; gap: 14px; }
.dinemenu-line { display: block; width: 36px; height: 1px; background: rgba(245,230,200,0.45); }
.dinemenu-eyebrow span:nth-child(2) { font-family: 'Cormorant Garamond', serif; font-style: italic; letter-spacing: 0.36em; font-size: .8rem; color: rgba(245,230,200,0.85); text-transform: uppercase; }

/* Hero（小） */
.dinemenu-hero {
  position: relative;
  min-height: 56vh;
  background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
  text-align: center;
}
.dinemenu-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.7) 0%, rgba(15,12,10,0.55) 50%, rgba(15,12,10,0.85) 100%); }
.dinemenu-hero-content { position: relative; z-index: 2; padding: 100px 20px 60px; max-width: 760px; }
.dinemenu-hero .dinemenu-eyebrow { justify-content: center; margin-bottom: 24px; }
.dinemenu-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.6rem, 6.5vw, 4.6rem);
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1;
  color: #fff;
  margin: 0 0 18px;
}
.dinemenu-tagline {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.15rem;
  color: rgba(245,230,200,0.8);
}

/* Anchors (sticky 分類 nav) */
.dinemenu-anchors {
  position: sticky;
  top: 66px; z-index: 100;
  background: rgba(15,12,10,0.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(245,230,200,0.1);
}
.dinemenu-anchors-track {
  display: flex; justify-content: center; gap: 4px;
  max-width: 1180px; margin: 0 auto;
  padding: 14px 20px;
  overflow-x: auto;
  scrollbar-width: none;
}
.dinemenu-anchors-track::-webkit-scrollbar { display: none; }
.dinemenu-anchor {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1rem; letter-spacing: 0.08em;
  color: rgba(245,230,200,0.72);
  padding: 8px 18px;
  text-decoration: none;
  border: 1px solid transparent;
  white-space: nowrap;
  transition: all .25s ease;
}
.dinemenu-anchor:hover { color: var(--dine-cream); border-color: rgba(245,230,200,0.3); }

/* Menu Category */
.dinemenu-cat { padding: 100px 0; background: var(--dine-bg); }
.dinemenu-cat.is-alt { background: var(--dine-bg-alt); }
.dinemenu-cat-grid {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 64px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 20px;
  align-items: center;
}
.dinemenu-cat.is-alt .dinemenu-cat-grid { grid-template-columns: 1.2fr 1fr; }
.dinemenu-cat.is-alt .dinemenu-cat-cover { order: 2; }
.dinemenu-cat.is-alt .dinemenu-cat-items { order: 1; }

.dinemenu-cat-cover {
  aspect-ratio: 4/5;
  background-size: cover; background-position: center;
  position: relative;
  box-shadow: 0 30px 60px -20px rgba(0,0,0,0.6);
}
.dinemenu-cat-cover-meta {
  position: absolute;
  bottom: 24px; left: 24px;
  color: var(--dine-cream);
  background: rgba(15,12,10,0.8);
  backdrop-filter: blur(8px);
  padding: 16px 22px;
  border-left: 2px solid var(--g-accent, #c89968);
}
.dinemenu-cat-num {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 2rem;
  color: var(--g-accent, #c89968);
  line-height: 1;
  margin-bottom: 4px;
}
.dinemenu-cat-subtitle {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.2em;
  font-size: .78rem;
  color: rgba(245,230,200,0.85);
}
.dinemenu-cat-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 500;
  color: var(--dine-cream);
  margin: 0 0 36px;
  position: relative;
}
.dinemenu-cat-title::after {
  content: '';
  display: block;
  width: 48px; height: 2px;
  background: var(--g-accent, #c89968);
  margin-top: 14px;
}

.dinemenu-items-list { display: flex; flex-direction: column; gap: 24px; }
.dinemenu-item {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  padding: 16px 0;
  border-bottom: 1px solid rgba(245,230,200,0.1);
}
.dinemenu-item:last-child { border-bottom: 0; }
.dinemenu-item-dot {
  font-family: 'Cormorant Garamond', serif;
  color: var(--g-accent, #c89968);
  font-size: 2rem;
  line-height: 1;
  margin-top: -4px;
}
.dinemenu-item-name {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: 1.4rem;
  font-weight: 500;
  color: var(--dine-cream);
  margin: 0 0 8px;
  letter-spacing: 0.02em;
}
.dinemenu-item-desc {
  font-family: 'Noto Sans TC', sans-serif;
  font-size: .92rem;
  line-height: 1.7;
  color: rgba(245,230,200,0.65);
  margin: 0;
}

/* CTA */
.dinemenu-cta {
  position: relative;
  padding: 130px 20px;
  background-size: cover; background-position: center;
  background-attachment: fixed;
  text-align: center;
}
.dinemenu-cta-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.85) 0%, rgba(15,12,10,0.95) 100%); }
.dinemenu-cta-inner { position: relative; z-index: 2; max-width: 720px; margin: 0 auto; }
.dinemenu-cta-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.2rem, 5vw, 3.6rem);
  font-weight: 500;
  color: #fff;
  margin: 24px 0 18px;
  line-height: 1.15;
}
.dinemenu-cta-sub {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.15rem;
  color: rgba(245,230,200,0.78);
  margin-bottom: 36px;
}
.dinemenu-cta-actions { display: flex; flex-direction: column; align-items: center; gap: 16px; }
.dinemenu-btn-book {
  display: inline-flex; align-items: center;
  padding: 18px 44px;
  font-size: .9rem; font-weight: 700;
  letter-spacing: 0.16em;
  color: #fff; text-decoration: none; text-transform: uppercase;
  background: transparent;
  border: 1.5px solid rgba(245,230,200,0.7);
  transition: all .3s ease;
}
.dinemenu-btn-book:hover { background: rgba(245,230,200,0.1); border-color: var(--dine-cream); letter-spacing: 0.22em; }
.dinemenu-btn-phone {
  font-family: 'Cormorant Garamond', serif;
  color: rgba(245,230,200,0.85);
  font-size: 1.05rem; font-weight: 500;
  text-decoration: none;
  letter-spacing: 0.04em;
  border-bottom: 1px solid rgba(245,230,200,0.3);
  padding-bottom: 2px;
}
.dinemenu-btn-phone:hover { color: var(--dine-cream); border-color: var(--dine-cream); }

/* RWD */
@media (max-width: 900px) {
  .dinemenu-cat-grid, .dinemenu-cat.is-alt .dinemenu-cat-grid { grid-template-columns: 1fr; gap: 40px; }
  .dinemenu-cat.is-alt .dinemenu-cat-cover { order: 0; }
  .dinemenu-cat.is-alt .dinemenu-cat-items { order: 0; }
  .dinemenu-cat-cover { aspect-ratio: 16/10; max-height: 380px; }
  .dinemenu-cta { padding: 80px 20px; background-attachment: scroll; }
}
@media (max-width: 600px) {
  .dinemenu-hero { min-height: 46vh; }
  .dinemenu-hero-content { padding: 80px 16px 50px; }
  .dinemenu-cat { padding: 60px 0; }
  .dinemenu-item-name { font-size: 1.2rem; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
