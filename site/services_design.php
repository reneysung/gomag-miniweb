<?php
/**
 * site/services_design.php  ─  室內設計事務所專屬 Services 頁
 * 從 site/services.php 進來時 isDesign=true require 這支
 */
$pageKey = 'services';
require __DIR__ . '/layout_head.php';

$brand = $client['brand_name'] ?? '';
$tagline = $client['tagline'] ?? '';
?>

<style>
  body { background: #0a0a0a !important; color: #e8e3d8 !important; }
  .site-header { background: rgba(10,10,10,.92) !important; border-bottom: 1px solid rgba(232,227,216,.08) !important; box-shadow: none !important; }
  .site-header .site-logo, .site-header .site-nav a { color: #e8e3d8 !important; }
  .site-header .site-nav a:hover, .site-header .site-nav a.active { background: rgba(232,227,216,.08) !important; color: #fff !important; }
  .site-header .btn-contact { background: #c8a57a !important; color: #0a0a0a !important; }
  .site-footer { background: #050505 !important; }

  .ed-page-hero { padding: 120px 30px 80px; text-align: center; max-width: 1100px; margin: 0 auto; }
  .ed-page-hero .eyebrow { font-size: .75rem; letter-spacing: .25em; color: #c8a57a; text-transform: uppercase; font-weight: 600; margin-bottom: 18px; display: inline-block; }
  .ed-page-hero h1 { font-family: 'Noto Serif TC', serif; font-weight: 400; font-size: clamp(2.2rem, 5vw, 3.4rem); line-height: 1.2; color: #f4ede0; margin-bottom: 20px; letter-spacing: -.01em; }
  .ed-page-hero .divider { width: 60px; height: 1px; background: #c8a57a; margin: 30px auto; }
  .ed-page-hero p { font-size: 1rem; line-height: 1.95; color: #b8b0a3; max-width: 580px; margin: 0 auto; }

  .ed-svc-list { max-width: 1280px; margin: 60px auto 100px; padding: 0 30px; }
  .ed-svc-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 0;
    border-top: 1px solid rgba(232,227,216,.12);
    min-height: 480px;
  }
  .ed-svc-row:last-child { border-bottom: 1px solid rgba(232,227,216,.12); }
  .ed-svc-row.reverse .ed-svc-img { order: 2; }
  @media(max-width:900px) {
    .ed-svc-row { grid-template-columns: 1fr; min-height: auto; }
    .ed-svc-row.reverse .ed-svc-img { order: 0; }
  }
  .ed-svc-img {
    background-size: cover; background-position: center;
    min-height: 480px;
    position: relative;
  }
  @media(max-width:900px) { .ed-svc-img { min-height: 320px; } }
  .ed-svc-text {
    padding: 70px 60px;
    display: flex; flex-direction: column; justify-content: center;
  }
  @media(max-width:900px) { .ed-svc-text { padding: 50px 24px; } }
  .ed-svc-num { font-family: 'Noto Serif TC', serif; font-size: 3.5rem; color: #c8a57a; line-height: 1; opacity: .55; margin-bottom: 18px; font-weight: 300; }
  .ed-svc-text h2 { font-family: 'Noto Serif TC', serif; font-size: clamp(1.5rem, 2.5vw, 2rem); color: #f4ede0; margin-bottom: 14px; font-weight: 500; line-height: 1.3; }
  .ed-svc-text .short { font-size: .82rem; letter-spacing: .12em; color: #c8a57a; text-transform: uppercase; margin-bottom: 28px; }
  .ed-svc-text .full { font-size: 1rem; line-height: 1.95; color: #b8b0a3; margin-bottom: 24px; }
  .ed-svc-text .full br + br { content: ''; }
  .ed-svc-text .price { font-size: .9rem; color: #c8a57a; font-weight: 600; letter-spacing: .03em; padding-top: 18px; border-top: 1px solid rgba(232,227,216,.15); }

  .ed-cta-row { background: linear-gradient(180deg, #0a0a0a 0%, #050505 100%); padding: 100px 30px; text-align: center; }
  .ed-cta-row h2 { font-family: 'Noto Serif TC', serif; font-size: clamp(1.6rem, 3vw, 2.2rem); color: #f4ede0; margin-bottom: 16px; font-weight: 400; }
  .ed-cta-row p { color: #b8b0a3; margin-bottom: 30px; }
  .ed-cta-btn { display: inline-block; padding: 16px 40px; border: 1px solid #c8a57a; color: #c8a57a; text-decoration: none; font-size: .85rem; letter-spacing: .15em; font-weight: 600; transition: all .25s; text-transform: uppercase; margin: 0 6px; }
  .ed-cta-btn:hover { background: #c8a57a; color: #0a0a0a; }
  .ed-cta-btn.primary { background: #c8a57a; color: #0a0a0a; }
  .ed-cta-btn.primary:hover { background: transparent; color: #c8a57a; }
</style>

<section class="ed-page-hero animate-in">
  <span class="eyebrow">SERVICES</span>
  <h1>專業服務</h1>
  <div class="divider"></div>
  <p>從第一次丈量到驗收交屋，每一個案子，都從理解你的日常開始</p>
</section>

<section class="ed-svc-list">
  <?php foreach ($services as $i => $svc):
      $img = $svc['image_path'] ? BASE_URL . '/' . h($svc['image_path']) : '';
      $reverse = $i % 2 === 1 ? ' reverse' : '';
  ?>
  <div class="ed-svc-row<?= $reverse ?> animate-in">
    <?php if ($img): ?>
    <div class="ed-svc-img" style="background-image:url('<?= h($img) ?>');" id="svc-<?= $svc['id'] ?>"></div>
    <?php else: ?>
    <div class="ed-svc-img" style="background:#1a1a1a;display:flex;align-items:center;justify-content:center;font-size:4rem;color:#c8a57a;opacity:.3"><?= $svc['icon'] ?? '🏠' ?></div>
    <?php endif; ?>
    <div class="ed-svc-text">
      <div class="ed-svc-num"><?= sprintf('%02d', $i + 1) ?></div>
      <h2><?= h($svc['name']) ?></h2>
      <?php if (!empty($svc['short_desc'])): ?>
      <div class="short"><?= h($svc['short_desc']) ?></div>
      <?php endif; ?>
      <?php if (!empty($svc['full_desc'])): ?>
      <div class="full"><?= nl2br(h($svc['full_desc'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($svc['price_text'])): ?>
      <div class="price"><?= h($svc['price_text']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</section>

<section class="ed-cta-row animate-in">
  <h2>讓我們從你的日常開始</h2>
  <p>免費丈量・30 分鐘現場諮詢・無壓力評估</p>
  <?php if (!empty($client['phone'])): ?>
  <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="ed-cta-btn primary">📞 <?= h($client['phone']) ?></a>
  <?php endif; ?>
  <?php if (!empty($lineUrl)): ?>
  <a href="<?= h($lineUrl) ?>" target="_blank" class="ed-cta-btn">💬 LINE 預約</a>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/layout_foot.php'; exit; ?>
