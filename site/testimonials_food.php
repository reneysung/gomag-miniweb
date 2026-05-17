<?php
/**
 * site/testimonials_food.php
 * 餐飲業食客之聲頁（Hawksmoor 風 — 大引言、編輯式排版）
 * 由 site/testimonials.php 在 $isFood 時 require 進來
 *
 * 上文已備好：$site, $sub, $client, $services, $lineUrl, $phone
 */
$photos = foodPhotoSet();
$ts = $site['testimonials'] ?? [];
// 不再 hardcode 假評價 — DB 空 → 顯示「整理中」

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
<section class="dinetes-hero" style="background-image:url('<?= h($photos['table']) ?>');">
  <div class="dinetes-hero-overlay"></div>
  <div class="dinetes-hero-content">
    <div class="dinetes-eyebrow">
      <span class="dinetes-line"></span>
      <span>GUESTS</span>
      <span class="dinetes-line"></span>
    </div>
    <h1 class="dinetes-title">食客之聲</h1>
    <p class="dinetes-tagline">真實食客的味蕾記憶</p>
  </div>
</section>

<?php if (empty($ts)): ?>
<section style="padding:80px 20px;background:#0f0c0a;color:rgba(245,230,200,.7)">
  <div style="max-width:600px;margin:0 auto;padding:48px 24px;text-align:center;background:rgba(245,230,200,.04);border:1px solid rgba(245,230,200,.15);border-radius:14px">
    <div style="font-size:3rem;margin-bottom:14px">⭐</div>
    <h3 style="font-size:1.2rem;font-weight:600;color:#f5e6c8;margin-bottom:8px;font-family:'Cormorant Garamond',serif">食客之聲整理中</h3>
    <p style="color:rgba(245,230,200,.6);font-size:.95rem;margin-bottom:24px">期待與您共享美好餐桌。</p>
    <?php if (!empty($client['phone'])): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" style="display:inline-block;padding:12px 28px;background:#f5e6c8;color:#0f0c0a;border-radius:0;text-decoration:none;font-weight:600;letter-spacing:.1em">📞 訂位 <?= h($client['phone']) ?></a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 2. 大引言 Featured Quote ════════════════════════════════════ -->
<?php $headline = $ts[0] ?? null; if ($headline): ?>
<section class="dinetes-feature">
  <div class="dinetes-feature-inner">
    <div class="dinetes-quote-big">“</div>
    <p class="dinetes-feature-text"><?= h($headline['content']) ?></p>
    <div class="dinetes-feature-stars"><?= str_repeat('★', (int)($headline['rating'] ?? 5)) ?></div>
    <div class="dinetes-feature-author">
      <span class="dinetes-line-short"></span>
      <span><?= h($headline['reviewer_name']) ?></span>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 3. 評論卡片 Grid ═══════════════════════════════════════════════ -->
<?php $rest = array_slice($ts, 1); if (!empty($rest)): ?>
<section class="dinetes-grid-section">
  <div class="dinetes-grid-head">
    <div class="dinetes-eyebrow" style="justify-content:center">
      <span class="dinetes-line"></span>
      <span>MORE VOICES</span>
      <span class="dinetes-line"></span>
    </div>
    <h2 class="dinetes-section-title">更多食客分享</h2>
  </div>
  <div class="dinetes-grid">
    <?php foreach ($rest as $i => $t): ?>
    <article class="dinetes-card animate-in delay-<?= min(($i % 4) + 1, 4) ?>">
      <div class="dinetes-card-stars"><?= str_repeat('★', (int)($t['rating'] ?? 5)) ?></div>
      <p class="dinetes-card-text">「<?= h($t['content']) ?>」</p>
      <div class="dinetes-card-author">
        <span class="dinetes-card-avatar"><?= h(mb_substr($t['reviewer_name'], 0, 1)) ?></span>
        <div>
          <div class="dinetes-card-name">— <?= h($t['reviewer_name']) ?></div>
          <?php if (!empty($t['service_name'])): ?>
          <div class="dinetes-card-meta"><?= h($t['service_name']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 4. CTA ═════════════════════════════════════════════════════════ -->
<section class="dinetes-cta" style="background-image:url('<?= h($photos['fire']) ?>');">
  <div class="dinetes-cta-overlay"></div>
  <div class="dinetes-cta-inner">
    <h2 class="dinetes-cta-title">下一位<br>就是您</h2>
    <p class="dinetes-cta-sub">為您的味蕾留一個位子</p>
    <div class="dinetes-cta-actions">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="dinetes-btn-book" target="_blank">BOOK A TABLE · 立即訂位</a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="dinetes-btn-phone">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
/* ═════════════════ 食客之聲 樣式 ════════════════════════════════ */
.dinetes-eyebrow { display: flex; align-items: center; gap: 14px; }
.dinetes-line { display: block; width: 36px; height: 1px; background: rgba(245,230,200,0.45); }
.dinetes-line-short { display: block; width: 24px; height: 1px; background: rgba(245,230,200,0.4); }
.dinetes-eyebrow span:nth-child(2) {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.36em;
  font-size: .8rem;
  color: rgba(245,230,200,0.85);
  text-transform: uppercase;
}
.dinetes-section-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 500;
  color: var(--dine-cream);
  text-align: center;
  margin: 14px 0 0;
}

/* 1. Hero */
.dinetes-hero {
  position: relative;
  min-height: 50vh;
  background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
  text-align: center;
}
.dinetes-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.7) 0%, rgba(15,12,10,0.55) 50%, rgba(15,12,10,0.85) 100%); }
.dinetes-hero-content { position: relative; z-index: 2; padding: 100px 20px 60px; max-width: 720px; }
.dinetes-hero .dinetes-eyebrow { justify-content: center; margin-bottom: 20px; }
.dinetes-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.6rem, 6.5vw, 4.4rem);
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1;
  color: #fff;
  margin: 0 0 16px;
}
.dinetes-tagline {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.1rem;
  color: rgba(245,230,200,0.78);
}

/* 2. Feature Quote */
.dinetes-feature {
  background: var(--dine-bg);
  padding: 130px 20px;
  text-align: center;
  position: relative;
}
.dinetes-feature::before, .dinetes-feature::after {
  content: '';
  position: absolute;
  width: 50%; height: 1px;
  background: rgba(245,230,200,0.15);
  left: 25%;
}
.dinetes-feature::before { top: 60px; }
.dinetes-feature::after  { bottom: 60px; }
.dinetes-feature-inner { max-width: 760px; margin: 0 auto; }
.dinetes-quote-big {
  font-family: 'Cormorant Garamond', serif;
  font-size: 6rem;
  color: var(--g-accent, #c89968);
  opacity: 0.5;
  line-height: 0.6;
  margin-bottom: 14px;
}
.dinetes-feature-text {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-style: italic;
  font-size: clamp(1.4rem, 2.5vw, 1.95rem);
  line-height: 1.75;
  color: var(--dine-cream);
  margin: 0 0 24px;
}
.dinetes-feature-stars {
  color: #f4b53b;
  font-size: 1.1rem;
  letter-spacing: 5px;
  margin-bottom: 20px;
  text-shadow: 0 0 18px rgba(244,181,59,0.4);
}
.dinetes-feature-author {
  display: inline-flex; align-items: center; gap: 12px;
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.1em;
  color: rgba(245,230,200,0.7);
  font-size: 1rem;
}

/* 3. Grid */
.dinetes-grid-section { background: var(--dine-bg-alt); padding: 110px 0 100px; }
.dinetes-grid-head { text-align: center; margin-bottom: 60px; padding: 0 20px; }
.dinetes-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 20px;
}
.dinetes-card {
  background: var(--dine-bg);
  padding: 36px 30px;
  border-top: 1px solid var(--g-accent, #c89968);
  display: flex; flex-direction: column;
  transition: transform .3s ease, border-color .3s ease;
}
.dinetes-card:hover { transform: translateY(-4px); border-top-color: var(--dine-cream); }
.dinetes-card-stars {
  color: #f4b53b;
  font-size: .95rem;
  letter-spacing: 3px;
  margin-bottom: 18px;
}
.dinetes-card-text {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-style: italic;
  font-size: 1.1rem;
  line-height: 1.7;
  color: rgba(245,230,200,0.88);
  margin: 0 0 24px;
  flex: 1;
}
.dinetes-card-author {
  display: flex; align-items: center; gap: 12px;
  padding-top: 18px;
  border-top: 1px solid rgba(245,230,200,0.12);
}
.dinetes-card-avatar {
  width: 42px; height: 42px;
  border-radius: 50%;
  background: rgba(var(--g-accent-rgb, 200,153,104), 0.18);
  color: var(--g-accent, #c89968);
  display: grid; place-items: center;
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.2rem;
  font-weight: 600;
}
.dinetes-card-name {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  color: var(--g-accent, #c89968);
  font-size: 1rem;
  letter-spacing: 0.04em;
}
.dinetes-card-meta {
  font-size: .82rem;
  color: rgba(245,230,200,0.5);
  margin-top: 2px;
}

/* 4. CTA */
.dinetes-cta {
  position: relative;
  padding: 120px 20px;
  text-align: center;
  background-size: cover; background-position: center;
  background-attachment: fixed;
}
.dinetes-cta-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.85) 0%, rgba(15,12,10,0.95) 100%); }
.dinetes-cta-inner { position: relative; z-index: 2; max-width: 680px; margin: 0 auto; }
.dinetes-cta-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2.2rem, 5vw, 3.6rem);
  font-weight: 500;
  color: #fff;
  margin: 0 0 18px;
  line-height: 1.1;
}
.dinetes-cta-sub {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.15rem;
  color: rgba(245,230,200,0.78);
  margin-bottom: 36px;
}
.dinetes-cta-actions { display: flex; flex-direction: column; align-items: center; gap: 16px; }
.dinetes-btn-book {
  display: inline-flex; align-items: center;
  padding: 18px 44px;
  font-size: .9rem; font-weight: 700;
  letter-spacing: 0.16em;
  color: #fff; text-decoration: none; text-transform: uppercase;
  background: transparent;
  border: 1.5px solid rgba(245,230,200,0.7);
  transition: all .3s ease;
}
.dinetes-btn-book:hover { background: rgba(245,230,200,0.1); border-color: var(--dine-cream); letter-spacing: 0.22em; }
.dinetes-btn-phone {
  font-family: 'Cormorant Garamond', serif;
  color: rgba(245,230,200,0.85);
  font-size: 1.05rem;
  text-decoration: none;
  letter-spacing: 0.04em;
  border-bottom: 1px solid rgba(245,230,200,0.3);
  padding-bottom: 2px;
}
.dinetes-btn-phone:hover { color: var(--dine-cream); border-color: var(--dine-cream); }

/* RWD */
@media (max-width: 900px) {
  .dinetes-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; }
  .dinetes-cta { padding: 80px 20px; background-attachment: scroll; }
}
@media (max-width: 600px) {
  .dinetes-hero { min-height: 42vh; }
  .dinetes-feature { padding: 80px 20px; }
  .dinetes-grid { grid-template-columns: 1fr; }
  .dinetes-grid-section { padding: 70px 0; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
