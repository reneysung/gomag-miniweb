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

  /* === Card layout：左右交替 圖 + 引用 === */
  .ed-testi-card {
    display: grid; grid-template-columns: 1fr 1.2fr; gap: 0;
    border-top: 1px solid rgba(232,227,216,.12);
    align-items: stretch;
  }
  .ed-testi-card:last-child { border-bottom: 1px solid rgba(232,227,216,.12); }
  .ed-testi-card.reverse .ed-testi-img { order: 2; }
  @media(max-width:768px) {
    .ed-testi-card, .ed-testi-card.reverse { grid-template-columns: 1fr; }
    .ed-testi-card.reverse .ed-testi-img { order: 0; }
  }
  .ed-testi-img {
    background-size: cover; background-position: center;
    min-height: 320px;
    background-color: #1a1a1a;
  }
  .ed-testi-body {
    padding: 60px 50px;
    display: flex; flex-direction: column; justify-content: center;
  }
  @media(max-width:768px) { .ed-testi-body { padding: 48px 24px; } }
  .ed-testi-body .stars { color: #c8a57a; margin-bottom: 18px; font-size: 1rem; letter-spacing: .25em; }
  .ed-testi-body .quote {
    font-family: 'Noto Serif TC', serif;
    font-size: clamp(1.05rem, 1.8vw, 1.3rem);
    line-height: 1.9;
    color: #e8e3d8;
    font-weight: 400;
    margin-bottom: 26px;
    font-style: italic;
  }
  .ed-testi-body .author {
    font-size: .82rem; letter-spacing: .12em;
    color: #c8a57a; text-transform: uppercase; font-weight: 600;
    margin-bottom: 8px;
  }
  .ed-testi-body .meta { font-size: .72rem; color: #807868; letter-spacing: .08em; margin-bottom: 22px; }
  .ed-testi-body .read-more {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 24px;
    border: 1px solid rgba(200,165,122,.4);
    color: #c8a57a; text-decoration: none;
    font-size: .78rem; letter-spacing: .12em; font-weight: 600;
    text-transform: uppercase;
    transition: all .2s;
    align-self: flex-start;
  }
  .ed-testi-body .read-more:hover { background: #c8a57a; color: #0a0a0a; border-color: #c8a57a; }
  .ed-testi-body .read-more svg { width: 14px; height: 14px; }
</style>

<?php
// 5 條 testimonial 配 5 張案例圖（按 client_id=213 撈相關圖；若是 artru subdomain 用既有 staging 圖）
$bgImages = [
  '/uploads/clients/artru/artru_p2_b88d1f9e.jpg',
  '/uploads/clients/artru/artru_p2_179d0125.jpg',
  '/uploads/clients/artru/artru_p7_a3356e11.jpg',
  '/uploads/clients/artru/artru_p6_e60bfe00.jpg',
  '/uploads/clients/artru/artru_p1_ecdce62e.jpg',
];

// source 顯示對應
$sourceLabels = [
  'mobile01' => 'Mobile01 開箱',
  'vocus'    => 'vocus.cc 開箱',
  'pixnet'   => 'pixnet 部落客',
  'google'   => 'Google 評論',
  'fb'       => 'Facebook 推薦',
];
?>

<section class="ed-page-hero animate-in">
  <span class="eyebrow">CLIENT VOICES</span>
  <h1>屋主好評</h1>
  <div class="divider"></div>
  <p>來自 Mobile01・vocus 部落客・Google 評論——真實住進去的人，最真實的回饋</p>
</section>

<?php if (empty($testimonials)): ?>
<div class="ed-empty">客戶分享整理中</div>
<?php else: ?>

<section style="max-width:1200px;margin:60px auto;padding:0 24px">
  <?php foreach ($testimonials as $i => $t):
      $reverse = ($i % 2 === 1) ? ' reverse' : '';
      $bg = $bgImages[$i % count($bgImages)];
      // 從 reviewer_name 抽出 source（vocus / mobile01 等關鍵字）作來源 label
      $sourceLabel = $sourceLabels[$t['source']] ?? ucfirst($t['source'] ?? '');
      // 若 source 是 'mobile01' 但 vocus URL，特別處理
      if (!empty($t['source_url']) && str_contains($t['source_url'], 'vocus.cc')) {
          $sourceLabel = 'vocus.cc 開箱';
      }
  ?>
  <div class="ed-testi-card animate-in<?= $reverse ?>">
    <div class="ed-testi-img" style="background-image:url('<?= h($bg) ?>')"></div>
    <div class="ed-testi-body">
      <div class="stars"><?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?></div>
      <p class="quote">「<?= h($t['content']) ?>」</p>
      <div class="author">— <?= h($t['reviewer_name']) ?></div>
      <?php if (!empty($t['svc_name'])): ?>
        <?php if (!empty($t['svc_slug'])): $_svcUrl = siteUrl($sub ?? $slug, 'services') . '/' . rawurlencode($t['svc_slug']); ?>
          <div class="meta"><a href="<?= h($_svcUrl) ?>" style="color:#c8a57a;text-decoration:none;font-weight:600">服務：<?= h($t['svc_name']) ?> →</a></div>
        <?php else: ?>
          <div class="meta">服務：<?= h($t['svc_name']) ?></div>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($sourceLabel): ?>
      <div class="meta">via <?= h($sourceLabel) ?></div>
      <?php endif; ?>
      <?php if (!empty($t['source_url'])): ?>
      <a href="<?= h($t['source_url']) ?>" target="_blank" rel="nofollow" class="read-more">
        看完整文章
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
      <?php endif; ?>
    </div>
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
