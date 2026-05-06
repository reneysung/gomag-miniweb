<?php
/**
 * site/cases_design.php  ─  室內設計事務所專屬 Cases 頁
 * 全屏 case study：每個 case 一個 hero + 故事 + before/after
 */
$pageKey = 'cases';
require __DIR__ . '/layout_head.php';

// $cases 在 layout_head 沒 set，從 $site 補
$cases = $site['cases'] ?? [];
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

  .ed-case { padding: 100px 30px; max-width: 1280px; margin: 0 auto; border-top: 1px solid rgba(232,227,216,.1); }
  .ed-case:first-of-type { border-top: 0; }
  .ed-case-meta { font-size: .78rem; letter-spacing: .2em; color: #807868; text-transform: uppercase; margin-bottom: 16px; }
  .ed-case h2 { font-family: 'Noto Serif TC', serif; font-size: clamp(1.6rem, 3vw, 2.4rem); color: #f4ede0; margin-bottom: 24px; font-weight: 500; line-height: 1.3; max-width: 720px; }
  .ed-case .case-stats { display: flex; gap: 30px; margin-bottom: 36px; flex-wrap: wrap; }
  .ed-case .stat { font-size: .85rem; color: #b8b0a3; padding-right: 30px; border-right: 1px solid rgba(232,227,216,.15); }
  .ed-case .stat:last-child { border-right: 0; }
  .ed-case .stat strong { display: block; color: #c8a57a; font-size: 1.05rem; font-weight: 600; margin-top: 4px; }
  .ed-case .desc { font-size: 1rem; line-height: 1.95; color: #b8b0a3; max-width: 720px; margin-bottom: 40px; }
  .ed-case-imgs { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 20px; }
  @media(max-width:768px) { .ed-case-imgs { grid-template-columns: 1fr; } }
  .ed-case-img-wrap { position: relative; aspect-ratio: 4/3; overflow: hidden; }
  .ed-case-img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .ed-case-img-wrap .label { position: absolute; top: 16px; left: 16px; background: rgba(10,10,10,.85); color: #c8a57a; padding: 6px 14px; font-size: .7rem; letter-spacing: .15em; font-weight: 600; text-transform: uppercase; }

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
  <span class="eyebrow">SELECTED WORKS</span>
  <h1>作品案例</h1>
  <div class="divider"></div>
  <p>每一件作品，都是與屋主共同打磨的空間敘事</p>
</section>

<?php if (empty($cases)): ?>
<div class="ed-empty">作品集整理中</div>
<?php else: ?>

<?php foreach ($cases as $idx => $c):
    $b = $c['before_image'] ? BASE_URL . '/' . h($c['before_image']) : '';
    $a = $c['after_image']  ? BASE_URL . '/' . h($c['after_image']) : '';
?>
<article class="ed-case animate-in" id="case-<?= $c['id'] ?>">
  <div class="ed-case-meta">CASE STUDY · <?= sprintf('%02d', $idx + 1) ?></div>
  <h2><?= h($c['title']) ?></h2>
  <div class="case-stats">
    <?php if (!empty($c['location'])): ?>
    <div class="stat">地點 <strong><?= h($c['location']) ?></strong></div>
    <?php endif; ?>
    <?php if (!empty($c['area_sqm'])): ?>
    <div class="stat">坪數 <strong><?= h($c['area_sqm']) ?> 坪</strong></div>
    <?php endif; ?>
  </div>
  <?php if (!empty($c['description'])): ?>
  <p class="desc"><?= nl2br(h($c['description'])) ?></p>
  <?php endif; ?>
  <?php if ($b || $a): ?>
  <div class="ed-case-imgs">
    <?php if ($b): ?>
    <div class="ed-case-img-wrap">
      <span class="label">Before</span>
      <img src="<?= h($b) ?>" alt="<?= h($c['title']) ?> · 改造前">
    </div>
    <?php endif; ?>
    <?php if ($a): ?>
    <div class="ed-case-img-wrap">
      <span class="label">After</span>
      <img src="<?= h($a) ?>" alt="<?= h($c['title']) ?> · 改造後">
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</article>
<?php endforeach; ?>

<?php endif; ?>

<section class="ed-cta-row animate-in">
  <h2>下一個故事，從你開始</h2>
  <p>免費丈量・30 分鐘現場諮詢・讓我們聽你的空間故事</p>
  <?php if (!empty($client['phone'])): ?>
  <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="ed-cta-btn primary">📞 <?= h($client['phone']) ?></a>
  <?php endif; ?>
  <?php if (!empty($lineUrl)): ?>
  <a href="<?= h($lineUrl) ?>" target="_blank" class="ed-cta-btn">💬 LINE 預約</a>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/layout_foot.php'; exit; ?>
