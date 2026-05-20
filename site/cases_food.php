<?php
/**
 * site/cases_food.php
 * 餐飲業料理作品頁（Hawksmoor 風 — 編輯雜誌式攝影集）
 * 由 site/cases.php 在 $isFood 時 require 進來
 *
 * 上文已備好：$site, $sub, $client, $services, $lineUrl, $phone
 */
$photos = foodPhotoSet();
$cases = $site['cases'] ?? [];

// 不再 hardcode 假料理作品 — DB 空 → 顯示「整理中」
$works = [];
if (!empty($cases)) {
    $works = array_map(function($c) use ($photos) {
        $img = !empty($c['after_image']) ? BASE_URL . '/' . $c['after_image']
             : (!empty($c['before_image']) ? BASE_URL . '/' . $c['before_image'] : $photos['fire']);
        return [
            'name' => $c['title'] ?? '料理作品',
            'desc' => $c['description'] ?? '',
            'img'  => $img,
        ];
    }, array_slice($cases, 0, 12));
}

// 第一張當大 feature
$featured = $works[0] ?? null;
$gallery  = array_slice($works, 1);

require __DIR__ . '/layout_head.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">

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
.mobile-menu { background: rgba(15,12,10,0.98) !important; border-top: 1px solid rgba(245,230,200,0.1) !important; }
.mobile-menu a { color: rgba(245,230,200,0.78) !important; border-bottom: 1px solid rgba(245,230,200,0.08) !important; }
.mobile-menu a.highlight { color: var(--g-accent, #c89968) !important; }
.bottom-tabbar { background: rgba(15,12,10,0.96) !important; border-top: 1px solid rgba(245,230,200,0.1) !important; }
.tab-item { color: rgba(245,230,200,0.55) !important; }
.tab-item.active, .tab-item:hover { color: #f5e6c8 !important; }
.tab-item.accent { color: var(--g-accent, #c89968) !important; }
:root { --dine-cream: #f5e6c8; --dine-bg: #0f0c0a; --dine-bg-alt: #1a1612; }
body { background: var(--dine-bg); color: var(--dine-cream); }
</style>

<!-- ══ 1. Page Hero ═══════════════════════════════════════════════════ -->
<section class="dineport-hero" style="background-image:url('<?= h($photos['plating']) ?>');">
  <div class="dineport-hero-overlay"></div>
  <div class="dineport-hero-content">
    <div class="dineport-eyebrow">
      <span class="dineport-line"></span>
      <span>OUR CRAFT</span>
      <span class="dineport-line"></span>
    </div>
    <h1 class="dineport-title">料理作品</h1>
    <p class="dineport-tagline">每一道料理，都是廚房裡的一份故事</p>
  </div>
</section>

<!-- 空狀態：DB 沒 cases -->
<?php if (empty($works)): ?>
<section style="padding:80px 20px;background:#0f0c0a;color:rgba(245,230,200,.7)">
  <div style="max-width:600px;margin:0 auto;padding:48px 24px;text-align:center;background:rgba(245,230,200,.04);border:1px solid rgba(245,230,200,.15);border-radius:14px">
    <div style="font-size:3rem;margin-bottom:14px">🍳</div>
    <h3 style="font-size:1.2rem;font-weight:600;color:#f5e6c8;margin-bottom:8px;font-family:'Cormorant Garamond',serif">料理作品整理中</h3>
    <p style="color:rgba(245,230,200,.6);font-size:.95rem;margin-bottom:24px">主廚精心料理的相片正在拍攝中，歡迎來電訂位品嚐。</p>
    <?php if (!empty($client['phone'])): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" style="display:inline-block;padding:12px 28px;background:#f5e6c8;color:#0f0c0a;border-radius:0;text-decoration:none;font-weight:600;letter-spacing:.1em">📞 訂位 <?= h($client['phone']) ?></a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 2. Featured Work（大 feature） ════════════════════════════════ -->
<?php if ($featured): ?>
<section class="dineport-feature">
  <div class="dineport-feature-grid">
    <div class="dineport-feature-img" style="background-image:url('<?= h($featured['img']) ?>');"></div>
    <div class="dineport-feature-text">
      <div class="dineport-feature-num">01</div>
      <div class="dineport-feature-eyebrow">FEATURED</div>
      <h2 class="dineport-feature-name"><?= h($featured['name']) ?></h2>
      <p class="dineport-feature-desc"><?= h($featured['desc']) ?></p>
      <div class="dineport-feature-quote">
        <span class="dineport-quote-mark">“</span>
        最頂級的食材，遇上最精準的火候。<br>每一刀切下，肉汁仍在跳動。
        <span class="dineport-quote-mark">”</span>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 3. Portfolio Grid ═════════════════════════════════════════════ -->
<section class="dineport-grid-section">
  <div class="dineport-grid-head">
    <div class="dineport-eyebrow" style="justify-content:center">
      <span class="dineport-line"></span>
      <span>SELECTED WORKS</span>
      <span class="dineport-line"></span>
    </div>
    <h2 class="dineport-section-title">全部作品集</h2>
  </div>
  <div class="dineport-grid">
    <?php foreach ($gallery as $i => $w): ?>
    <article class="dineport-card animate-in delay-<?= min(($i % 4) + 1, 4) ?>">
      <div class="dineport-card-img" style="background-image:url('<?= h($w['img']) ?>');"></div>
      <div class="dineport-card-meta">
        <div class="dineport-card-num"><?= str_pad((string)($i+2), 2, '0', STR_PAD_LEFT) ?></div>
        <h3 class="dineport-card-name"><?= h($w['name']) ?></h3>
        <p class="dineport-card-desc"><?= h($w['desc']) ?></p>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ 4. Chef's Note ════════════════════════════════════════════════ -->
<section class="dineport-note">
  <div class="dineport-note-inner">
    <div class="dineport-quote-mark dineport-quote-big">“</div>
    <p class="dineport-note-text">
      做料理不只是把食材變成食物 —<br>
      是把溫度、心意、記憶，一起送上桌。<br>
      <span class="dineport-note-emphasis">這，就是我們的堅持。</span>
    </p>
    <div class="dineport-note-author">
      <span class="dineport-line-short"></span>
      <span>主廚 · <?= h($client['brand_name']) ?></span>
    </div>
  </div>
</section>

<!-- ══ 5. CTA ═════════════════════════════════════════════════════════ -->
<section class="dineport-cta" style="background-image:url('<?= h($photos['interior']) ?>');">
  <div class="dineport-cta-overlay"></div>
  <div class="dineport-cta-inner">
    <h2 class="dineport-cta-title">想親自品嚐嗎？</h2>
    <p class="dineport-cta-sub">預約一個位子，讓我們為您獻上最好的料理</p>
    <div class="dineport-cta-actions">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="dineport-btn-book" target="_blank">BOOK A TABLE · 立即訂位</a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="dineport-btn-phone">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
/* ════════════════════════════════════════════════════════════════
 * 餐飲料理作品頁專屬樣式
 * ════════════════════════════════════════════════════════════════ */
.dineport-eyebrow { display: flex; align-items: center; gap: 14px; }
.dineport-line { display: block; width: 36px; height: 1px; background: rgba(245,230,200,0.45); }
.dineport-line-short { display: block; width: 24px; height: 1px; background: rgba(245,230,200,0.4); }
.dineport-eyebrow span:nth-child(2) {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.36em;
  font-size: .8rem;
  color: rgba(245,230,200,0.85);
  text-transform: uppercase;
}
.dineport-section-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2rem, 4.5vw, 3.2rem);
  font-weight: 500;
  color: var(--dine-cream);
  margin: 14px 0 0;
  text-align: center;
}

/* 1. Hero */
.dineport-hero {
  position: relative;
  min-height: 50vh;
  background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
  text-align: center;
}
.dineport-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.7) 0%, rgba(15,12,10,0.55) 50%, rgba(15,12,10,0.85) 100%); }
.dineport-hero-content { position: relative; z-index: 2; padding: 100px 20px 60px; max-width: 720px; }
.dineport-hero .dineport-eyebrow { justify-content: center; margin-bottom: 20px; }
.dineport-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.6rem, 6.5vw, 4.4rem);
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1;
  color: #fff;
  margin: 0 0 16px;
}
.dineport-tagline {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.1rem;
  color: rgba(245,230,200,0.78);
}

/* 2. Featured */
.dineport-feature { background: var(--dine-bg); padding: 110px 0 90px; }
.dineport-feature-grid {
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 64px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 20px;
  align-items: center;
}
.dineport-feature-img {
  aspect-ratio: 4/5;
  background-size: cover; background-position: center;
  box-shadow: 0 30px 60px -20px rgba(0,0,0,0.6);
}
.dineport-feature-num {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 5rem;
  color: var(--g-accent, #c89968);
  opacity: 0.6;
  line-height: 1;
}
.dineport-feature-eyebrow {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.36em;
  font-size: .8rem;
  color: var(--g-accent, #c89968);
  text-transform: uppercase;
  margin: 18px 0 16px;
}
.dineport-feature-name {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.2rem, 4vw, 3rem);
  font-weight: 500;
  color: var(--dine-cream);
  margin: 0 0 18px;
  letter-spacing: -0.01em;
}
.dineport-feature-desc {
  font-size: 1.05rem;
  line-height: 1.85;
  color: rgba(245,230,200,0.75);
  margin: 0 0 32px;
  max-width: 460px;
}
.dineport-feature-quote {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-style: italic;
  font-size: 1.2rem;
  line-height: 1.7;
  color: var(--dine-cream);
  padding: 24px 0;
  border-top: 1px solid rgba(245,230,200,0.15);
  border-bottom: 1px solid rgba(245,230,200,0.15);
  max-width: 460px;
}
.dineport-quote-mark {
  font-family: 'Cormorant Garamond', serif;
  color: var(--g-accent, #c89968);
  font-size: 1.5em;
  vertical-align: -0.1em;
  margin: 0 -2px;
}

/* 3. Grid */
.dineport-grid-section { background: var(--dine-bg-alt); padding: 110px 0 100px; }
.dineport-grid-head { text-align: center; margin-bottom: 60px; padding: 0 20px; }
.dineport-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 20px;
}
.dineport-card { background: var(--dine-bg); transition: transform .35s ease; }
.dineport-card:hover { transform: translateY(-4px); }
.dineport-card-img {
  aspect-ratio: 4/5;
  background-size: cover; background-position: center;
  filter: brightness(0.92);
  transition: filter .35s ease;
}
.dineport-card:hover .dineport-card-img { filter: brightness(1.05); }
.dineport-card-meta { padding: 26px 24px 30px; position: relative; }
.dineport-card-num {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.8rem;
  color: var(--g-accent, #c89968);
  opacity: 0.55;
  position: absolute;
  top: 18px; right: 24px;
}
.dineport-card-name {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: 1.4rem;
  font-weight: 500;
  color: var(--dine-cream);
  margin: 0 0 10px;
  max-width: 75%;
}
.dineport-card-desc {
  font-size: .92rem;
  line-height: 1.65;
  color: rgba(245,230,200,0.65);
  margin: 0;
}

/* 4. Chef Note（中央引言） */
.dineport-note {
  background: var(--dine-bg);
  padding: 130px 20px;
  text-align: center;
  position: relative;
}
.dineport-note::before, .dineport-note::after {
  content: '';
  position: absolute;
  width: 60%; height: 1px;
  background: rgba(245,230,200,0.15);
  left: 20%;
}
.dineport-note::before { top: 60px; }
.dineport-note::after  { bottom: 60px; }
.dineport-note-inner { max-width: 720px; margin: 0 auto; }
.dineport-quote-big {
  font-family: 'Cormorant Garamond', serif;
  font-size: 5rem;
  color: var(--g-accent, #c89968);
  opacity: 0.5;
  line-height: 0.6;
  margin-bottom: 12px;
  display: block;
}
.dineport-note-text {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-style: italic;
  font-size: clamp(1.3rem, 2.2vw, 1.7rem);
  line-height: 1.85;
  color: var(--dine-cream);
  margin: 0 0 32px;
}
.dineport-note-emphasis { color: var(--g-accent, #c89968); font-style: normal; font-weight: 600; letter-spacing: 0.05em; }
.dineport-note-author {
  display: inline-flex; align-items: center; gap: 12px;
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.1em;
  color: rgba(245,230,200,0.7);
  font-size: .95rem;
}

/* 5. CTA */
.dineport-cta {
  position: relative;
  padding: 130px 20px;
  text-align: center;
  background-size: cover; background-position: center;
  background-attachment: fixed;
}
.dineport-cta-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.85) 0%, rgba(15,12,10,0.95) 100%); }
.dineport-cta-inner { position: relative; z-index: 2; max-width: 680px; margin: 0 auto; }
.dineport-cta-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.2rem, 5vw, 3.6rem);
  font-weight: 500;
  color: #fff;
  margin: 0 0 18px;
  line-height: 1.15;
}
.dineport-cta-sub {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.15rem;
  color: rgba(245,230,200,0.78);
  margin-bottom: 36px;
}
.dineport-cta-actions { display: flex; flex-direction: column; align-items: center; gap: 16px; }
.dineport-btn-book {
  display: inline-flex; align-items: center;
  padding: 18px 44px;
  font-size: .9rem; font-weight: 700;
  letter-spacing: 0.16em;
  color: #fff; text-decoration: none; text-transform: uppercase;
  background: transparent;
  border: 1.5px solid rgba(245,230,200,0.7);
  transition: all .3s ease;
}
.dineport-btn-book:hover { background: rgba(245,230,200,0.1); border-color: var(--dine-cream); letter-spacing: 0.22em; }
.dineport-btn-phone {
  font-family: 'Cormorant Garamond', serif;
  color: rgba(245,230,200,0.85);
  font-size: 1.05rem;
  text-decoration: none;
  letter-spacing: 0.04em;
  border-bottom: 1px solid rgba(245,230,200,0.3);
  padding-bottom: 2px;
}
.dineport-btn-phone:hover { color: var(--dine-cream); border-color: var(--dine-cream); }

/* RWD */
@media (max-width: 900px) {
  .dineport-feature-grid { grid-template-columns: 1fr; gap: 40px; }
  .dineport-feature-img { aspect-ratio: 4/3; max-height: 400px; }
  .dineport-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
  .dineport-cta { padding: 80px 20px; background-attachment: scroll; }
}
@media (max-width: 600px) {
  .dineport-hero { min-height: 42vh; }
  .dineport-grid { grid-template-columns: 1fr; }
  .dineport-feature { padding: 70px 0; }
  .dineport-grid-section { padding: 70px 0; }
  .dineport-note { padding: 80px 20px; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
