<?php
/**
 * site/testimonials_design.php  ─  室內設計事務所 Testimonials 頁
 * 雜誌引用體 + 大留白
 */
$pageKey = 'testimonials';
require __DIR__ . '/layout_head.php';

// $testimonials 在 layout_head 沒 set，從 $site 補上（同 testimonials.php 主檔）
$testimonials = $site['testimonials'] ?? [];
?>

<style>
  body { background: #0a0a0a !important; color: #e8e3d8 !important; }
  .site-header { background: rgba(10,10,10,.92) !important; border-bottom: 1px solid rgba(232,227,216,.08) !important; box-shadow: none !important; }
  .site-header .site-logo, .site-header .site-nav a { color: #e8e3d8 !important; }
  .site-header .site-nav a:hover, .site-header .site-nav a.active { background: rgba(232,227,216,.08) !important; color: #fff !important; }
  .site-header .btn-contact { background: #c8a57a !important; color: #0a0a0a !important; }
  .site-footer { background: #050505 !important; }

  .ed-page-hero { padding: 120px 30px 60px; text-align: center; max-width: 1100px; margin: 0 auto; }
  .ed-page-hero .eyebrow { font-size: .75rem; letter-spacing: .25em; color: #c8a57a; text-transform: uppercase; font-weight: 600; margin-bottom: 18px; display: inline-block; }
  .ed-page-hero h1 { font-family: 'Noto Serif TC', serif; font-weight: 400; font-size: clamp(2.2rem, 5vw, 3.4rem); line-height: 1.2; color: #f4ede0; margin-bottom: 20px; }
  .ed-page-hero .divider { width: 60px; height: 1px; background: #c8a57a; margin: 30px auto; }
  .ed-page-hero p { font-size: 1rem; line-height: 1.95; color: #b8b0a3; max-width: 580px; margin: 0 auto; }

  .ed-testi-list { max-width: 920px; margin: 60px auto 100px; padding: 0 30px; }
  .ed-testi {
    padding: 60px 0;
    border-top: 1px solid rgba(232,227,216,.12);
    text-align: center;
  }
  .ed-testi:last-child { border-bottom: 1px solid rgba(232,227,216,.12); }
  .ed-testi .stars { color: #c8a57a; margin-bottom: 28px; font-size: 1.1rem; letter-spacing: .25em; }
  .ed-testi .quote {
    font-family: 'Noto Serif TC', serif;
    font-size: clamp(1.15rem, 2vw, 1.5rem);
    line-height: 1.9;
    color: #e8e3d8;
    font-weight: 400;
    margin-bottom: 32px;
    max-width: 720px;
    margin-left: auto;
    margin-right: auto;
    font-style: italic;
  }
  .ed-testi .author {
    font-size: .8rem;
    letter-spacing: .15em;
    color: #c8a57a;
    text-transform: uppercase;
    font-weight: 600;
  }
  .ed-testi .meta {
    font-size: .75rem;
    color: #807868;
    margin-top: 6px;
    letter-spacing: .08em;
  }

  .ed-cta-row { background: linear-gradient(180deg, #0a0a0a 0%, #050505 100%); padding: 100px 30px; text-align: center; }
  .ed-cta-row h2 { font-family: 'Noto Serif TC', serif; font-size: clamp(1.6rem, 3vw, 2.2rem); color: #f4ede0; margin-bottom: 16px; font-weight: 400; }
  .ed-cta-row p { color: #b8b0a3; margin-bottom: 30px; }
  .ed-cta-btn { display: inline-block; padding: 16px 40px; border: 1px solid #c8a57a; color: #c8a57a; text-decoration: none; font-size: .85rem; letter-spacing: .15em; font-weight: 600; transition: all .25s; text-transform: uppercase; margin: 0 6px; }
  .ed-cta-btn:hover { background: #c8a57a; color: #0a0a0a; }
  .ed-cta-btn.primary { background: #c8a57a; color: #0a0a0a; }
  .ed-cta-btn.primary:hover { background: transparent; color: #c8a57a; }

  .ed-empty { text-align: center; padding: 80px 30px; color: #807868; }
</style>

<section class="ed-page-hero animate-in">
  <span class="eyebrow">CLIENT VOICES</span>
  <h1>屋主好評</h1>
  <div class="divider"></div>
  <p>真實住進去的人，最真實的回饋</p>
</section>

<?php if (empty($testimonials)): ?>
<div class="ed-empty">客戶分享整理中</div>
<?php else: ?>

<section class="ed-testi-list">
  <?php foreach ($testimonials as $t): ?>
  <div class="ed-testi animate-in">
    <div class="stars">
      <?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?>
    </div>
    <p class="quote">「<?= h($t['content']) ?>」</p>
    <div class="author">— <?= h($t['reviewer_name']) ?></div>
    <?php if (!empty($t['source']) && $t['source'] !== 'demo'): ?>
    <div class="meta">via <?= h(strtoupper($t['source'])) ?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</section>

<?php endif; ?>

<section class="ed-cta-row animate-in">
  <h2>讓你的家成為下一個分享</h2>
  <p>免費丈量・30 分鐘現場諮詢</p>
  <?php if (!empty($client['phone'])): ?>
  <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="ed-cta-btn primary">📞 <?= h($client['phone']) ?></a>
  <?php endif; ?>
  <?php if (!empty($lineUrl)): ?>
  <a href="<?= h($lineUrl) ?>" target="_blank" class="ed-cta-btn">💬 LINE 預約</a>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/layout_foot.php'; exit; ?>
