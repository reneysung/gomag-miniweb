<?php
/**
 * site/index_food.php
 * 餐飲業專屬首頁（Hawksmoor 風 — cinematic dark + 大量攝影）
 * 由 site/index.php 在 $isFood 時 require 進來
 *
 * 上文已備好：$client, $site, $services, $aboutTags, $heroStats,
 *           $sub, $slug, $lineUrl, $phone, $caseLabel, $caseTitle,
 *           $svcSubtitle 等變數
 */
$photos = foodPhotoSet();
$heroImg = !empty($client['hero_image_path'])
    ? BASE_URL . '/' . h($client['hero_image_path'])
    : $photos['hero'];
$ratingNum = preg_replace('/[★\s]/u', '', $heroStats[3][0] ?? '4.9') ?: '4.9';

// signature dishes — DB 沒 services 就用 default 牛排館菜單（不顯示價格）
$dishes = !empty($services) ? array_slice($services, 0, 3) : [
    ['name' => '招牌沙朗牛排', 'short_desc' => '美國 Choice 級頂級沙朗，炭火直烤鎖住肉汁', 'image_path' => null, '__photo' => $photos['fire']],
    ['name' => '酥皮巧達濃湯', 'short_desc' => '濃郁香純，手工酥皮現烤，紮實食材一碗到底', 'image_path' => null, '__photo' => $photos['side']],
    ['name' => '海陸雙拼套餐', 'short_desc' => '頂級牛排 + 鮮蝦組合，附湯 / 沙拉吧 / 飲料', 'image_path' => null, '__photo' => $photos['plating']],
];

// gallery photos — 6 張全牛排館主題，無咖哩、無彩甜點
$galleryPhotos = [$photos['fire'], $photos['sliced'], $photos['meat'], $photos['wine'], $photos['chef'], $photos['table']];

require __DIR__ . '/layout_head.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">

<!-- 餐飲頁專屬 header / topbar 暗色覆蓋 -->
<style>
.topbar-strip { display: none !important; }

.site-header {
  background: rgba(15,12,10,0.92) !important;
  backdrop-filter: blur(14px) !important;
  -webkit-backdrop-filter: blur(14px) !important;
  border-bottom: 1px solid rgba(245,230,200,0.1) !important;
  box-shadow: none !important;
}
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

/* 底部 mobile tabbar 配合 */
.bottom-tabbar { background: rgba(15,12,10,0.96) !important; border-top: 1px solid rgba(245,230,200,0.1) !important; box-shadow: 0 -2px 16px rgba(0,0,0,0.4) !important; }
.tab-item { color: rgba(245,230,200,0.55) !important; }
.tab-item.active, .tab-item:hover { color: #f5e6c8 !important; }
.tab-item.accent { color: var(--g-accent, #c89968) !important; }
</style>

<!-- ══ 1. HERO（Hawksmoor cinematic）══════════════════════════════════ -->
<section class="dine-hero">
  <div class="dine-hero-bg" style="background-image:url('<?= h($heroImg) ?>');"></div>
  <div class="dine-hero-overlay"></div>
  <div class="dine-hero-vignette"></div>

  <div class="dine-hero-content">
    <div class="dine-eyebrow animate-in">
      <span class="dine-line"></span>
      <span class="dine-eyebrow-text">WELCOME TO</span>
      <span class="dine-line"></span>
    </div>
    <h1 class="dine-hero-title animate-in delay-1"><?= h($client['brand_name']) ?></h1>
    <p class="dine-hero-quote animate-in delay-2">
      <span class="dine-qm">“</span><?= h($client['tagline'] ?? '一道道用心料理，等你慢慢品嚐') ?><span class="dine-qm">”</span>
    </p>
    <div class="dine-hero-actions animate-in delay-3">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="dine-btn-book" target="_blank">BOOK A TABLE · 立即訂位</a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="dine-btn-phone">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </div>
    <div class="dine-hero-meta animate-in delay-4">
      <?php if($client['address']): ?><span><?= h($client['address']) ?></span><?php endif; ?>
      <?php if($client['business_hours']): ?><span class="dine-meta-sep">·</span><span><?= h($client['business_hours']) ?></span><?php endif; ?>
    </div>
  </div>

  <div class="dine-hero-awards">
    <div class="dine-award">
      <div class="dine-award-stars">★★★★★</div>
      <div class="dine-award-text">「<?= h($ratingNum) ?> Google · <?= h($heroStats[1][0] ?? '20年') ?> 在地經營 · <?= h($heroStats[2][0] ?? '5000+') ?> 位食客」</div>
    </div>
  </div>
</section>

<!-- ══ 2. ATMOSPHERE STRIP（5 張 atmospheric 圖橫條）═══════════════════ -->
<section class="dine-strip">
  <div class="dine-strip-track">
    <?php foreach (['interior','fire','plating','cocktail','closeup'] as $key): ?>
    <div class="dine-strip-item" style="background-image:url('<?= h($photos[$key]) ?>');"></div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ 3. ABOUT — 大照片 + 編輯式雙欄 ════════════════════════════════ -->
<section class="dine-about">
  <div class="dine-about-grid">
    <div class="dine-about-photo animate-in" style="background-image:url('<?= h($photos['interior']) ?>');"></div>
    <div class="dine-about-text animate-in delay-1">
      <div class="dine-section-eyebrow">OUR STORY</div>
      <h2 class="dine-section-title">關於<?= h($client['brand_name']) ?></h2>
      <p class="dine-about-body"><?= nl2br(h(mb_strimwidth($client['about_text'] ?? '用心料理，溫度上桌。每一道菜，都是廚房裡的一份故事。', 0, 280, '…'))) ?></p>
      <div class="dine-about-tags">
        <?php foreach($aboutTags as $f): ?><span class="dine-about-tag"><?= h($f) ?></span><?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ══ 4. SIGNATURE DISHES — 3 大盤招牌料理 ════════════════════════════ -->
<section class="dine-dishes">
  <div class="dine-dishes-head">
    <div class="dine-section-eyebrow">SIGNATURE DISHES</div>
    <h2 class="dine-section-title">主廚招牌</h2>
    <p class="dine-section-sub"><?= h($svcSubtitle ?? '嚴選食材、用心烹調') ?></p>
  </div>
  <div class="dine-dishes-grid">
    <?php foreach ($dishes as $i => $d):
      $img = !empty($d['image_path']) ? BASE_URL . '/' . h($d['image_path']) : ($d['__photo'] ?? $photos['plating']);
    ?>
    <article class="dine-dish-card animate-in delay-<?= min($i+1, 4) ?>">
      <div class="dine-dish-img" style="background-image:url('<?= h($img) ?>');"></div>
      <div class="dine-dish-meta">
        <div class="dine-dish-num"><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></div>
        <h3 class="dine-dish-name"><?= h($d['name']) ?></h3>
        <p class="dine-dish-desc"><?= h($d['short_desc'] ?? '') ?></p>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <div class="dine-dishes-cta">
    <a href="<?= siteUrl($sub,'services') ?>" class="dine-link-arrow">看完整菜單 <span>→</span></a>
  </div>
</section>

<!-- ══ 5. GALLERY MASONRY ═════════════════════════════════════════════ -->
<section class="dine-gallery">
  <div class="dine-gallery-head">
    <div class="dine-section-eyebrow">GALLERY</div>
    <h2 class="dine-section-title">餐桌時光</h2>
  </div>
  <div class="dine-gallery-grid">
    <?php foreach ($galleryPhotos as $i => $url): ?>
    <div class="dine-gallery-item dine-gallery-item-<?= $i+1 ?>" style="background-image:url('<?= h($url) ?>');"></div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ 6. RESERVATION CTA — 全寬深色 ════════════════════════════════════ -->
<section class="dine-reserve" style="background-image:url('<?= h($photos['table']) ?>');">
  <div class="dine-reserve-overlay"></div>
  <div class="dine-reserve-inner">
    <div class="dine-section-eyebrow" style="color:rgba(245,230,200,0.7)">RESERVATIONS</div>
    <h2 class="dine-reserve-title">預約一個<br>美好夜晚</h2>
    <p class="dine-reserve-sub">無論是慶祝、聚會、約會 — 我們為您留一張桌子。</p>
    <div class="dine-reserve-actions">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="dine-btn-book" target="_blank">立即預約</a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="dine-btn-phone-light">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══ 7. TESTIMONIALS（食客之聲）═════════════════════════════════════ -->
<?php
$ts = $site['testimonials'] ?? [];
if (!empty($ts)):
?>
<section class="dine-tt">
  <div class="dine-tt-head">
    <div class="dine-section-eyebrow">GUESTS</div>
    <h2 class="dine-section-title">食客之聲</h2>
  </div>
  <div class="dine-tt-grid">
    <?php foreach (array_slice($ts, 0, 3) as $i => $t): ?>
    <article class="dine-tt-card animate-in delay-<?= min($i+1,4) ?>">
      <div class="dine-tt-stars">★★★★★</div>
      <p class="dine-tt-text">「<?= h(mb_strimwidth($t['content'], 0, 140, '…')) ?>」</p>
      <div class="dine-tt-author">— <?= h($t['reviewer_name']) ?></div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 7.5 FB 粉絲頁 + 地圖 ═══════════════════════════════════════════ -->
<?php
$hasFb = !empty($social['fb_page_url']);
$hasMap = !empty($client['google_maps_embed']);
if ($hasFb || $hasMap):
?>
<section class="dine-social">
  <div class="dine-social-head">
    <div class="dine-section-eyebrow">FIND US</div>
    <h2 class="dine-section-title">最新動態與位置</h2>
  </div>
  <div class="dine-social-grid <?= ($hasFb && $hasMap) ? '' : 'is-single' ?>">
    <?php if ($hasFb): ?>
    <div class="dine-social-card dine-social-fb animate-in">
      <div class="dine-social-card-head">
        <span class="dine-social-icon">📘</span>
        <span>FACEBOOK</span>
      </div>
      <div class="dine-social-card-body">
        <div id="fb-root"></div>
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/zh_TW/sdk.js#xfbml=1&version=v21.0" nonce="fbSDK"></script>
        <div class="fb-page"
             data-href="<?= h($social['fb_page_url']) ?>"
             data-tabs="timeline"
             data-width="500"
             data-height="600"
             data-small-header="true"
             data-adapt-container-width="true"
             data-hide-cover="false"
             data-show-facepile="true">
          <blockquote cite="<?= h($social['fb_page_url']) ?>" class="fb-xfbml-parse-ignore">
            <a href="<?= h($social['fb_page_url']) ?>" target="_blank" class="dine-social-fallback">
              <span class="fb-fallback-icon">📘</span>
              <span class="fb-fallback-title">前往 Facebook 粉絲頁</span>
              <span class="fb-fallback-sub">查看最新菜單與動態</span>
            </a>
          </blockquote>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($hasMap): ?>
    <div class="dine-social-card dine-social-map animate-in delay-2">
      <div class="dine-social-card-head">
        <span class="dine-social-icon">📍</span>
        <span>LOCATION</span>
      </div>
      <div class="dine-social-card-body dine-social-map-body">
        <?= $client['google_maps_embed'] /* 已含 iframe 全 markup */ ?>
      </div>
      <div class="dine-social-card-foot">
        <?= h($client['address'] ?? '') ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 8. CONTACT INFO（簡潔深色 footer-like）════════════════════════════ -->
<section class="dine-contact">
  <div class="dine-contact-grid">
    <div>
      <div class="dine-section-eyebrow" style="color:rgba(245,230,200,0.7)">VISIT US</div>
      <h2 class="dine-contact-name"><?= h($client['brand_name']) ?></h2>
      <?php if($client['address']): ?><p class="dine-contact-line">📍 <?= h($client['address']) ?></p><?php endif; ?>
      <?php if($client['business_hours']): ?><p class="dine-contact-line">🕐 <?= h($client['business_hours']) ?></p><?php endif; ?>
      <?php if($phone): ?><p class="dine-contact-line">📞 <?= h($phone) ?></p><?php endif; ?>
    </div>
    <div class="dine-contact-photo" style="background-image:url('<?= h($photos['chef']) ?>');"></div>
  </div>
</section>

<style>
/* ════════════════════════════════════════════════════════════════
 * 餐飲業專屬樣式 — Hawksmoor 風（暗黑 cinematic + 大量攝影）
 * ════════════════════════════════════════════════════════════════ */
:root { --dine-cream: #f5e6c8; --dine-bg: #0f0c0a; --dine-bg-alt: #1a1612; }
body { background: var(--dine-bg); color: var(--dine-cream); }

.dine-section-eyebrow {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.4em;
  font-size: .8rem;
  color: var(--g-accent, #c89968);
  text-transform: uppercase;
  margin-bottom: 16px;
}
.dine-section-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(2rem, 4.5vw, 3.5rem);
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1.1;
  color: var(--dine-cream);
  margin-bottom: 14px;
}
.dine-section-sub {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.1rem;
  color: rgba(245,230,200,0.75);
}

/* 1. Hero */
.dine-hero { position: relative; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; overflow: hidden; }
.dine-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.5; filter: contrast(1.1) saturate(1.05); transform: scale(1.04); animation: dine-zoom 30s ease-in-out infinite alternate; }
@keyframes dine-zoom { from { transform: scale(1.04); } to { transform: scale(1.14); } }
.dine-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.65) 0%, rgba(15,12,10,0.3) 35%, rgba(15,12,10,0.88) 100%); }
.dine-hero-vignette { position: absolute; inset: 0; background: radial-gradient(ellipse at center, transparent 30%, rgba(15,12,10,0.7) 100%); pointer-events: none; }
.dine-hero-content { position: relative; z-index: 2; text-align: center; padding: 80px 20px 140px; max-width: 920px; }
.dine-eyebrow { display: flex; align-items: center; justify-content: center; gap: 18px; margin-bottom: 36px; }
.dine-line { display: block; width: 50px; height: 1px; background: rgba(245,230,200,0.5); }
.dine-eyebrow-text { font-family: 'Cormorant Garamond', serif; font-style: italic; letter-spacing: 0.4em; font-size: .85rem; color: rgba(245,230,200,0.85); text-transform: uppercase; }
.dine-hero-title { font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-size: clamp(3rem, 9vw, 7rem); font-weight: 500; letter-spacing: -0.01em; line-height: 1; color: #fff; margin: 0 0 28px; text-shadow: 0 4px 40px rgba(0,0,0,0.6); }
.dine-hero-quote { font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-style: italic; font-size: clamp(1.05rem, 1.8vw, 1.4rem); color: rgba(245,230,200,0.88); line-height: 1.55; margin: 0 auto 44px; max-width: 620px; text-shadow: 0 2px 16px rgba(0,0,0,0.5); }
.dine-qm { font-family: 'Cormorant Garamond', serif; color: rgba(245,230,200,0.55); font-size: 1.4em; vertical-align: -0.1em; margin: 0 -2px; }
.dine-hero-actions { display: flex; flex-direction: column; align-items: center; gap: 18px; margin-bottom: 32px; }
.dine-btn-book { display: inline-flex; align-items: center; padding: 18px 44px; font-size: .9rem; font-weight: 700; letter-spacing: 0.16em; color: #fff; text-decoration: none; text-transform: uppercase; background: transparent; border: 1.5px solid rgba(245,230,200,0.7); transition: all .3s ease; }
.dine-btn-book:hover { background: rgba(245,230,200,0.1); border-color: var(--dine-cream); letter-spacing: 0.22em; }
.dine-btn-phone, .dine-btn-phone-light { font-family: 'Cormorant Garamond', serif; color: rgba(245,230,200,0.85); font-size: 1.1rem; font-weight: 500; text-decoration: none; letter-spacing: 0.04em; border-bottom: 1px solid rgba(245,230,200,0.3); padding-bottom: 2px; transition: all .25s ease; }
.dine-btn-phone:hover, .dine-btn-phone-light:hover { color: var(--dine-cream); border-color: var(--dine-cream); }
.dine-hero-meta { display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 10px; font-family: 'Cormorant Garamond', serif; font-size: .95rem; color: rgba(245,230,200,0.65); font-style: italic; }
.dine-meta-sep { color: rgba(245,230,200,0.35); }
.dine-hero-awards { position: absolute; bottom: 0; left: 0; right: 0; z-index: 2; padding: 22px 20px; background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.55) 100%); border-top: 1px solid rgba(245,230,200,0.12); }
.dine-award { text-align: center; color: rgba(245,230,200,0.85); }
.dine-award-stars { font-size: 1rem; color: #f4b53b; letter-spacing: 4px; margin-bottom: 6px; text-shadow: 0 0 14px rgba(244,181,59,0.4); }
.dine-award-text { font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: .92rem; letter-spacing: 0.04em; }

/* 2. Atmospheric Strip */
.dine-strip { background: var(--dine-bg); padding: 0; }
.dine-strip-track { display: grid; grid-template-columns: repeat(5, 1fr); gap: 2px; height: 220px; }
.dine-strip-item { background-size: cover; background-position: center; transition: filter .5s ease, transform .5s ease; filter: grayscale(0.3) brightness(0.85); }
.dine-strip-item:hover { filter: grayscale(0) brightness(1); transform: scale(1.02); }

/* 3. About */
.dine-about { background: var(--dine-bg); padding: 100px 0; }
.dine-about-grid { display: grid; grid-template-columns: 1fr 1.05fr; gap: 64px; max-width: 1180px; margin: 0 auto; padding: 0 20px; align-items: center; }
.dine-about-photo { aspect-ratio: 4/5; background-size: cover; background-position: center; box-shadow: 0 30px 60px -20px rgba(0,0,0,0.6); }
.dine-about-body { font-family: 'Noto Sans TC', serif; font-size: 1rem; line-height: 1.85; color: rgba(245,230,200,0.75); margin: 18px 0 28px; }
.dine-about-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.dine-about-tag { display: inline-block; border: 1px solid rgba(245,230,200,0.25); color: rgba(245,230,200,0.85); padding: 6px 14px; font-size: .82rem; font-weight: 500; }

/* 4. Signature Dishes */
.dine-dishes { background: var(--dine-bg-alt); padding: 110px 0 90px; }
.dine-dishes-head { text-align: center; max-width: 720px; margin: 0 auto 60px; padding: 0 20px; }
.dine-dishes-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.dine-dish-card { background: var(--dine-bg); }
.dine-dish-img { aspect-ratio: 4/5; background-size: cover; background-position: center; }
.dine-dish-meta { padding: 28px 26px 32px; position: relative; }
.dine-dish-num { font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 2.4rem; color: var(--g-accent, #c89968); position: absolute; top: 18px; right: 26px; opacity: 0.55; }
.dine-dish-name { font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-size: 1.5rem; font-weight: 500; color: var(--dine-cream); margin: 0 0 10px; max-width: 75%; }
.dine-dish-desc { font-family: 'Noto Sans TC', sans-serif; font-size: .92rem; line-height: 1.65; color: rgba(245,230,200,0.65); margin: 0 0 14px; }
.dine-dish-price { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; color: var(--g-accent, #c89968); font-weight: 500; letter-spacing: 0.04em; }
.dine-dishes-cta { text-align: center; margin-top: 56px; }
.dine-link-arrow { display: inline-flex; align-items: center; gap: 8px; font-family: 'Cormorant Garamond', serif; font-size: 1.05rem; letter-spacing: 0.12em; color: var(--dine-cream); text-decoration: none; padding-bottom: 4px; border-bottom: 1px solid rgba(245,230,200,0.4); text-transform: uppercase; }
.dine-link-arrow:hover { border-color: var(--dine-cream); }
.dine-link-arrow span { transition: transform .25s ease; }
.dine-link-arrow:hover span { transform: translateX(4px); }

/* 5. Gallery Masonry */
.dine-gallery { background: var(--dine-bg); padding: 100px 0; }
.dine-gallery-head { text-align: center; margin-bottom: 56px; }
.dine-gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 220px; gap: 8px; max-width: 1280px; margin: 0 auto; padding: 0 20px; }
.dine-gallery-item { background-size: cover; background-position: center; transition: filter .4s ease, transform .4s ease; filter: brightness(0.85); cursor: pointer; }
.dine-gallery-item:hover { filter: brightness(1.05); transform: scale(1.01); }
.dine-gallery-item-1 { grid-column: span 2; grid-row: span 2; }
.dine-gallery-item-2 { grid-column: span 1; grid-row: span 1; }
.dine-gallery-item-3 { grid-column: span 1; grid-row: span 1; }
.dine-gallery-item-4 { grid-column: span 1; grid-row: span 1; }
.dine-gallery-item-5 { grid-column: span 1; grid-row: span 1; }
.dine-gallery-item-6 { grid-column: span 2; grid-row: span 1; }

/* 6. Reservation CTA */
.dine-reserve { position: relative; padding: 140px 20px; text-align: center; background-size: cover; background-position: center; background-attachment: fixed; }
.dine-reserve-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,12,10,0.85) 0%, rgba(15,12,10,0.95) 100%); }
.dine-reserve-inner { position: relative; z-index: 2; max-width: 720px; margin: 0 auto; }
.dine-reserve-title { font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 500; line-height: 1.1; color: #fff; margin: 0 0 24px; }
.dine-reserve-sub { font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2rem; color: rgba(245,230,200,0.78); margin-bottom: 40px; }
.dine-reserve-actions { display: flex; flex-direction: column; align-items: center; gap: 18px; }

/* 7. Testimonials */
.dine-tt { background: var(--dine-bg-alt); padding: 100px 0; }
.dine-tt-head { text-align: center; margin-bottom: 56px; }
.dine-tt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.dine-tt-card { padding: 36px 28px; background: var(--dine-bg); border-top: 1px solid var(--g-accent, #c89968); }
.dine-tt-stars { color: #f4b53b; font-size: .95rem; letter-spacing: 3px; margin-bottom: 16px; }
.dine-tt-text { font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-style: italic; font-size: 1.1rem; line-height: 1.6; color: rgba(245,230,200,0.85); margin: 0 0 18px; }
.dine-tt-author { font-family: 'Cormorant Garamond', serif; font-style: italic; color: var(--g-accent, #c89968); font-size: .95rem; letter-spacing: 0.04em; }

/* 7.5 Social — FB + Map */
.dine-social { background: var(--dine-bg); padding: 100px 0; }
.dine-social-head { text-align: center; margin-bottom: 56px; padding: 0 20px; }
.dine-social-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.dine-social-grid.is-single { grid-template-columns: 1fr; max-width: 820px; }
.dine-social-card { background: var(--dine-bg-alt); border: 1px solid rgba(245,230,200,0.1); overflow: hidden; display: flex; flex-direction: column; }
.dine-social-card-head { padding: 18px 22px; border-bottom: 1px solid rgba(245,230,200,0.1); font-family: 'Cormorant Garamond', serif; font-style: italic; letter-spacing: 0.2em; font-size: .85rem; color: rgba(245,230,200,0.7); display: flex; align-items: center; gap: 10px; }
.dine-social-icon { font-size: 1.1rem; }
.dine-social-card-body { padding: 0; min-height: 460px; background: rgba(0,0,0,0.3); display: flex; align-items: stretch; justify-content: center; }
.dine-social-card-body .fb-page,
.dine-social-card-body .fb-page span,
.dine-social-card-body iframe { width: 100% !important; max-width: 100% !important; }
.dine-social-card-body iframe { border: 0; min-height: 460px; }
.dine-social-map-body { min-height: 460px; padding: 0; background: var(--dine-bg-alt); }
.dine-social-map-body iframe { width: 100% !important; height: 460px !important; min-height: 460px; filter: invert(0.92) hue-rotate(180deg) saturate(0.7); }
.dine-social-card-foot { padding: 14px 22px; border-top: 1px solid rgba(245,230,200,0.08); font-family: 'Cormorant Garamond', serif; color: rgba(245,230,200,0.7); font-size: .92rem; font-style: italic; }
.dine-social-fallback { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 460px; text-decoration: none; gap: 8px; }
.fb-fallback-icon { font-size: 3rem; }
.fb-fallback-title { color: var(--dine-cream); font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; font-weight: 500; }
.fb-fallback-sub { color: rgba(245,230,200,0.6); font-size: .9rem; font-style: italic; }

/* 8. Contact */
.dine-contact { background: var(--dine-bg); padding: 100px 0 60px; }
.dine-contact-grid { display: grid; grid-template-columns: 1fr 1.1fr; gap: 64px; max-width: 1180px; margin: 0 auto; padding: 0 20px; align-items: center; }
.dine-contact-name { font-family: 'Cormorant Garamond', 'Noto Serif TC', serif; font-size: 2.4rem; font-weight: 500; color: var(--dine-cream); margin: 8px 0 22px; }
.dine-contact-line { font-family: 'Noto Sans TC', sans-serif; font-size: 1rem; color: rgba(245,230,200,0.7); margin: 8px 0; }
.dine-contact-photo { aspect-ratio: 5/4; background-size: cover; background-position: center; }

/* RWD */
@media (max-width: 900px) {
  .dine-strip-track { grid-template-columns: repeat(3, 1fr); height: 160px; }
  .dine-strip-item:nth-child(n+4) { display: none; }
  .dine-about-grid { grid-template-columns: 1fr; gap: 40px; }
  .dine-about-photo { aspect-ratio: 16/10; max-height: 400px; }
  .dine-dishes-grid { grid-template-columns: 1fr; gap: 24px; }
  .dine-gallery-grid { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 180px; }
  .dine-gallery-item-1 { grid-column: span 2; grid-row: span 2; }
  .dine-gallery-item-6 { grid-column: span 2; }
  .dine-tt-grid { grid-template-columns: 1fr; }
  .dine-social-grid { grid-template-columns: 1fr; gap: 20px; }
  .dine-contact-grid { grid-template-columns: 1fr; gap: 32px; }
  .dine-reserve { padding: 80px 20px; background-attachment: scroll; }
}
@media (max-width: 600px) {
  .dine-hero-content { padding: 56px 16px 130px; }
  .dine-hero-title { font-size: clamp(2.4rem, 11vw, 3.6rem); }
  .dine-strip-track { grid-template-columns: 1fr 1fr; height: 130px; }
  .dine-strip-item:nth-child(n+3) { display: none; }
  .dine-about, .dine-dishes, .dine-gallery, .dine-tt, .dine-contact { padding: 60px 0; }
  .dine-gallery-grid { grid-template-columns: 1fr; grid-auto-rows: 220px; gap: 4px; }
  .dine-gallery-item-1, .dine-gallery-item-6 { grid-column: span 1; grid-row: span 1; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
