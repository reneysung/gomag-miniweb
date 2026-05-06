<?php
/**
 * site/index_design.php  ─  室內設計 / 建築事務所專屬 Editorial 風 layout
 * 從 site/index.php 進來時 isDesign=true 會 require 這支
 *
 * 設計重點：
 *   • 純黑底 + 米白色字 + 一個 accent（金/木色）
 *   • Serif 標題 (Noto Serif TC) + 細小 sans-serif 標籤
 *   • 滿版作品 hero（左下角 typography）
 *   • Editorial portfolio masonry grid
 *   • 大留白、滾動沉浸式
 *
 * 用 $client / $services / $cases / $testimonials 等變數（已 layout_head 載入）
 */

// require layout_head 以取得 $lineUrl, $fbUrl, $phone 等共用變數（跟 _food 同模式）
require __DIR__ . '/layout_head.php';

// $cases / $testimonials 在 layout_head 沒 set，從 $site 補
$cases = $site['cases'] ?? [];
$testimonials = $site['testimonials'] ?? [];

// 取資料（layout_head 內已撈好 $client / $services / $lineUrl）
$brand    = $client['brand_name'] ?? '';
$tagline  = $client['tagline'] ?? '';
$industry = $client['industry'] ?? '';
$about    = $client['about_text'] ?? '';
$phoneRaw = preg_replace('/[^0-9+]/', '', $phone ?? '');
$address  = $client['address'] ?? '';
$hours    = $client['business_hours'] ?? '';
$heroImg  = !empty($client['hero_image_path']) ? BASE_URL . '/' . h($client['hero_image_path']) : '';

// 取作品（cases 可當 portfolio）
$portfolios = $cases ?? [];
?>

<style>
  /* ═══ Editorial Design 專屬 CSS（命名空間 .ed-*）═══ */
  body { background: #0a0a0a !important; color: #e8e3d8 !important; }
  .site-header { background: rgba(10,10,10,.92) !important; border-bottom: 1px solid rgba(232,227,216,.08) !important; box-shadow: none !important; }
  .site-header .site-logo, .site-header .site-nav a { color: #e8e3d8 !important; }
  .site-header .site-nav a:hover, .site-header .site-nav a.active { background: rgba(232,227,216,.08) !important; color: #fff !important; }
  .site-header .btn-contact { background: var(--g-accent, #c8a57a) !important; color: #0a0a0a !important; }

  .ed-section { padding: 100px 30px; max-width: 1280px; margin: 0 auto; }
  @media(max-width:768px) { .ed-section { padding: 60px 20px; } }
  .ed-eyebrow { font-size: .7rem; letter-spacing: .25em; color: #c8a57a; text-transform: uppercase; font-weight: 600; margin-bottom: 18px; display: inline-block; }
  .ed-h1, .ed-h2 { font-family: 'Noto Serif TC', 'Times New Roman', serif; font-weight: 400; line-height: 1.25; letter-spacing: -.01em; color: #f4ede0; }
  .ed-h1 { font-size: clamp(2rem, 5vw, 3.6rem); }
  .ed-h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); margin-bottom: 20px; }
  .ed-h3 { font-family: 'Noto Serif TC', serif; font-weight: 500; font-size: 1.4rem; color: #f4ede0; margin-bottom: 10px; }
  .ed-body { font-size: 1rem; line-height: 1.95; color: #b8b0a3; }
  .ed-divider { width: 60px; height: 1px; background: #c8a57a; margin: 24px 0; }

  /* ═══ Hero（左圖右文 / 全黑底 / 大留白）═══ */
  .ed-hero { padding: 0; position: relative; min-height: 80vh; display: flex; align-items: stretch; }
  .ed-hero-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 0; width: 100%; }
  @media(max-width:900px) { .ed-hero-grid { grid-template-columns: 1fr; min-height: auto; } }
  .ed-hero-img {
    background-size: cover; background-position: center;
    min-height: 80vh;
    position: relative;
  }
  .ed-hero-img::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(0,0,0,.05) 0%, rgba(0,0,0,.4) 100%); }
  @media(max-width:900px) { .ed-hero-img { min-height: 50vh; } }
  .ed-hero-text {
    padding: 80px 60px;
    display: flex; flex-direction: column; justify-content: center;
    background: #0a0a0a;
  }
  @media(max-width:900px) { .ed-hero-text { padding: 60px 24px; } }
  .ed-hero-text .ed-eyebrow { margin-bottom: 28px; }
  .ed-hero-text h1 { margin-bottom: 22px; }
  .ed-hero-text p { font-size: 1.05rem; line-height: 2; color: #b8b0a3; max-width: 480px; margin-bottom: 36px; }
  .ed-hero-meta { font-size: .82rem; color: #807868; letter-spacing: .08em; }
  .ed-hero-meta span { margin-right: 18px; }

  /* ═══ Philosophy / 設計理念區 ═══ */
  .ed-philosophy { background: #0a0a0a; }
  .ed-philosophy-inner { max-width: 720px; margin: 0 auto; text-align: center; }
  .ed-philosophy-quote { font-family: 'Noto Serif TC', serif; font-size: clamp(1.4rem,2.6vw,1.95rem); line-height: 1.7; color: #e8e3d8; font-weight: 400; font-style: italic; margin: 30px 0; }
  .ed-philosophy-attribution { font-size: .78rem; letter-spacing: .2em; color: #807868; text-transform: uppercase; margin-top: 24px; }

  /* ═══ Portfolio Masonry（不規則 grid）═══ */
  .ed-portfolio { background: #0a0a0a; padding: 80px 0; }
  .ed-portfolio-head { text-align: center; padding: 0 30px 50px; max-width: 720px; margin: 0 auto; }
  .ed-portfolio-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 8px;
    padding: 0 8px;
  }
  .ed-pf-item {
    position: relative; overflow: hidden; cursor: pointer;
    background: #1a1a1a;
    aspect-ratio: 4/3;
  }
  .ed-pf-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .8s cubic-bezier(.25,.46,.45,.94); }
  .ed-pf-item:hover img { transform: scale(1.04); }
  .ed-pf-item:hover .ed-pf-overlay { opacity: 1; }
  .ed-pf-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.85) 100%);
    display: flex; flex-direction: column; justify-content: flex-end;
    padding: 28px;
    opacity: .85;
    transition: opacity .35s;
    color: #fff;
  }
  .ed-pf-overlay h4 { font-family: 'Noto Serif TC', serif; font-size: 1.2rem; font-weight: 500; margin-bottom: 6px; }
  .ed-pf-overlay p { font-size: .82rem; opacity: .8; }
  /* item layout — 不規則 */
  .ed-pf-large   { grid-column: span 7; aspect-ratio: 16/10; }
  .ed-pf-medium  { grid-column: span 5; aspect-ratio: 16/10; }
  .ed-pf-small   { grid-column: span 4; aspect-ratio: 4/3; }
  .ed-pf-wide    { grid-column: span 8; aspect-ratio: 16/9; }
  @media(max-width:900px) {
    .ed-pf-large, .ed-pf-medium, .ed-pf-small, .ed-pf-wide {
      grid-column: span 12; aspect-ratio: 4/3;
    }
  }

  /* ═══ Services / 服務（最簡約清單）═══ */
  .ed-services { background: #050505; padding: 100px 30px; }
  .ed-services-inner { max-width: 1100px; margin: 0 auto; }
  .ed-services-head { text-align: center; margin-bottom: 60px; }
  .ed-services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1px; background: rgba(232,227,216,.1); border: 1px solid rgba(232,227,216,.1); }
  .ed-svc { background: #050505; padding: 48px 36px; transition: background .3s; }
  .ed-svc:hover { background: #0d0d0d; }
  .ed-svc-num { font-family: 'Noto Serif TC', serif; font-size: 2.4rem; color: #c8a57a; margin-bottom: 14px; line-height: 1; opacity: .55; }
  .ed-svc h4 { font-family: 'Noto Serif TC', serif; font-size: 1.25rem; color: #f4ede0; margin-bottom: 12px; font-weight: 500; }
  .ed-svc p { font-size: .92rem; line-height: 1.85; color: #998f7f; margin-bottom: 16px; min-height: 80px; }
  .ed-svc .price { font-size: .85rem; color: #c8a57a; font-weight: 600; letter-spacing: .03em; }

  /* ═══ Testimonials（雜誌引用體）═══ */
  .ed-testimonials { background: #0a0a0a; padding: 100px 30px; }
  .ed-testi-inner { max-width: 980px; margin: 0 auto; text-align: center; }
  .ed-testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 50px; text-align: left; }
  .ed-testi-item { padding: 32px 0; border-top: 1px solid rgba(232,227,216,.15); }
  .ed-testi-stars { color: #c8a57a; margin-bottom: 14px; font-size: .95rem; letter-spacing: .15em; }
  .ed-testi-quote { font-family: 'Noto Serif TC', serif; font-size: 1.05rem; line-height: 1.85; color: #d8d2c4; margin-bottom: 18px; }
  .ed-testi-author { font-size: .8rem; color: #807868; letter-spacing: .08em; }

  /* ═══ Contact CTA ═══ */
  .ed-contact { background: linear-gradient(180deg, #0a0a0a 0%, #050505 100%); padding: 120px 30px; text-align: center; }
  .ed-contact-inner { max-width: 720px; margin: 0 auto; }
  .ed-contact-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-top: 40px; }
  .ed-cta-btn { display: inline-block; padding: 16px 40px; border: 1px solid #c8a57a; color: #c8a57a; text-decoration: none; font-size: .85rem; letter-spacing: .15em; font-weight: 600; transition: all .25s; text-transform: uppercase; }
  .ed-cta-btn:hover { background: #c8a57a; color: #0a0a0a; }
  .ed-cta-btn.primary { background: #c8a57a; color: #0a0a0a; }
  .ed-cta-btn.primary:hover { background: transparent; color: #c8a57a; }
  .ed-contact-meta { font-size: .85rem; color: #807868; line-height: 2; margin-top: 30px; }
  .ed-contact-meta div { margin: 4px 0; }
</style>

<!-- ═════ HERO ═════ -->
<section class="ed-hero">
  <div class="ed-hero-grid">
    <?php if ($heroImg): ?>
    <div class="ed-hero-img" style="background-image:url('<?= h($heroImg) ?>');"></div>
    <?php else: ?>
    <div class="ed-hero-img" style="background:linear-gradient(135deg,#1a1a1a,#2c2c2c)"></div>
    <?php endif; ?>
    <div class="ed-hero-text">
      <span class="ed-eyebrow animate-in"><?= h($industry ?: 'INTERIOR DESIGN STUDIO') ?></span>
      <h1 class="ed-h1 animate-in delay-1"><?= h($brand) ?></h1>
      <div class="ed-divider animate-in delay-1"></div>
      <p class="animate-in delay-2"><?= h($tagline ?: '空間敘事・職人工藝・住的剛剛好') ?></p>
      <div class="ed-hero-meta animate-in delay-3">
        <?php if ($address): ?><span>📍 <?= h(mb_strimwidth($address, 0, 30)) ?></span><?php endif; ?>
        <?php if ($phone): ?><span>📞 <?= h($phone) ?></span><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ═════ PHILOSOPHY / 設計理念 ═════ -->
<?php if ($about): ?>
<section class="ed-section ed-philosophy">
  <div class="ed-philosophy-inner animate-in">
    <span class="ed-eyebrow">PHILOSOPHY</span>
    <h2 class="ed-h2">設計理念</h2>
    <div class="ed-divider" style="margin:24px auto"></div>
    <?php
    $aboutShort = strip_tags($about);
    $aboutShort = mb_strimwidth($aboutShort, 0, 280, '…');
    ?>
    <blockquote class="ed-philosophy-quote">「<?= h($aboutShort) ?>」</blockquote>
    <div class="ed-philosophy-attribution">— <?= h($brand) ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ═════ PORTFOLIO ═════ -->
<?php if ($portfolios): ?>
<section class="ed-portfolio animate-in">
  <div class="ed-portfolio-head">
    <span class="ed-eyebrow">SELECTED WORKS</span>
    <h2 class="ed-h2">作品案例</h2>
    <p class="ed-body">每一件作品，都是與屋主共同打磨的空間敘事</p>
  </div>
  <div class="ed-portfolio-grid">
    <?php
    // 用不規則 layout 展示前 6 件作品
    $sizes = ['ed-pf-large', 'ed-pf-medium', 'ed-pf-small', 'ed-pf-small', 'ed-pf-small', 'ed-pf-wide'];
    foreach (array_slice($portfolios, 0, 6) as $i => $c):
        $img = $c['after_image'] ?: $c['before_image'] ?: $heroImg;
        $imgUrl = $img ? (str_starts_with($img, 'http') ? $img : BASE_URL . '/' . h($img)) : '';
        $size = $sizes[$i] ?? 'ed-pf-medium';
    ?>
    <a class="ed-pf-item <?= $size ?>" href="<?= siteUrl($slug, 'cases') ?>#case-<?= $c['id'] ?>">
      <?php if ($imgUrl): ?>
      <img src="<?= h($imgUrl) ?>" alt="<?= h($c['title']) ?>" loading="lazy">
      <?php endif; ?>
      <div class="ed-pf-overlay">
        <h4><?= h($c['title']) ?></h4>
        <p>
          <?php if (!empty($c['location'])): ?><?= h($c['location']) ?> · <?php endif; ?>
          <?php if (!empty($c['area_sqm'])): ?><?= h($c['area_sqm']) ?>坪<?php endif; ?>
        </p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <div style="text-align:center;margin-top:40px">
    <a href="<?= siteUrl($slug, 'cases') ?>" class="ed-cta-btn">查看所有作品 →</a>
  </div>
</section>
<?php endif; ?>

<!-- ═════ SERVICES ═════ -->
<?php if (!empty($services)): ?>
<section class="ed-services animate-in">
  <div class="ed-services-inner">
    <div class="ed-services-head">
      <span class="ed-eyebrow">SERVICES</span>
      <h2 class="ed-h2">專業服務</h2>
    </div>
    <div class="ed-services-grid">
      <?php foreach (array_slice($services, 0, 6) as $i => $svc): ?>
      <div class="ed-svc">
        <div class="ed-svc-num"><?= sprintf('%02d', $i + 1) ?></div>
        <h4><?= h($svc['name']) ?></h4>
        <p><?= h($svc['short_desc'] ?? '') ?></p>
        <?php if (!empty($svc['price_text'])): ?>
        <div class="price"><?= h($svc['price_text']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═════ TESTIMONIALS ═════ -->
<?php if (!empty($testimonials)): ?>
<section class="ed-testimonials animate-in">
  <div class="ed-testi-inner">
    <span class="ed-eyebrow">CLIENT VOICES</span>
    <h2 class="ed-h2">屋主好評</h2>
    <div class="ed-divider" style="margin:24px auto"></div>
    <div class="ed-testi-grid">
      <?php foreach (array_slice($testimonials, 0, 4) as $t): ?>
      <div class="ed-testi-item">
        <div class="ed-testi-stars">
          <?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?>
        </div>
        <p class="ed-testi-quote">「<?= h(mb_strimwidth($t['content'], 0, 140, '…')) ?>」</p>
        <div class="ed-testi-author">— <?= h($t['reviewer_name']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═════ CONTACT ═════ -->
<section class="ed-contact animate-in">
  <div class="ed-contact-inner">
    <span class="ed-eyebrow">GET IN TOUCH</span>
    <h2 class="ed-h2">開始你的空間提案</h2>
    <div class="ed-divider" style="margin:24px auto"></div>
    <p class="ed-body" style="max-width:560px;margin:0 auto">
      我們提供免費丈量與 30 分鐘現場諮詢，讓我們從「你的日常」開始量身打造。
    </p>
    <div class="ed-contact-actions">
      <?php if ($phoneRaw): ?>
      <a href="tel:<?= h($phoneRaw) ?>" class="ed-cta-btn primary">📞 <?= h($phone) ?></a>
      <?php endif; ?>
      <?php if ($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" target="_blank" class="ed-cta-btn">💬 LINE 預約</a>
      <?php endif; ?>
      <a href="<?= siteUrl($slug, 'cases') ?>" class="ed-cta-btn">查看作品</a>
    </div>
    <div class="ed-contact-meta">
      <?php if ($hours): ?><div>🕐 <?= nl2br(h($hours)) ?></div><?php endif; ?>
      <?php if ($address): ?><div>📍 <?= h($address) ?></div><?php endif; ?>
    </div>
  </div>
</section>
