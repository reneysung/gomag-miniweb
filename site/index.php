<?php
// site/index.php  ─  多客戶通用首頁模板
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

// CDN cache 5 分鐘，平衡新鮮度（後台更新後可見）跟 Core Web Vitals（TTFB）
header('Cache-Control: public, max-age=300, must-revalidate');

$sub = getSubdomain();
if (!$sub) { http_response_code(404); die('找不到網站'); }

$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在或已停用'); }

// ─── has_minisite 開關檢查 ───────────────────────
// 沒啟用小官網 → 跳外部官網（如有）或主站行銷頁
if (empty($site['client']['has_minisite'])) {
    if (!empty($site['client']['external_website_url'])) {
        header('Location: ' . $site['client']['external_website_url'], true, 301);
        exit;
    }
    // 跳主站行銷頁（301 永久轉址，保 SEO）
    $mainSite = (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/store.php?sub=' . urlencode($sub)
        : 'https://www.gomag.com.tw/store/' . urlencode($sub);
    header('Location: ' . $mainSite, true, 301);
    exit;
}

$slug    = $sub;
$pageKey = 'home';
$client  = $site['client'];
$social  = $site['social'];
$phone   = $client['phone'] ?? '';
// LINE URL 邏輯（line_url 直填 > line_id 自組 > ''）
$lineUrl = '';
if (!empty($social['line_url']) && filter_var($social['line_url'], FILTER_VALIDATE_URL)) {
    $lineUrl = $social['line_url'];
} elseif (!empty($social['line_id'])) {
    $rawId = ltrim(trim($social['line_id']), '@');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $rawId)) {
        $lineUrl = 'https://line.me/R/ti/p/@' . $rawId;
    }
}

// ── 依產業設定動態文案 ──
$ind = $client['industry'] ?? '';
$isFood = (bool)preg_match('/(餐|食|料理|咖啡|甜點|甜品|烘焙|燒肉|牛排|火鍋|鍋物|壽司|麵|飯|披薩|拉麵|烤|飲料|手搖|茶飲|甜|蛋糕|麵包|食坊|食堂|宵夜)/u', $ind);
$isDesign = !$isFood && (bool)preg_match('/(室內設計|室內裝修|室內裝潢|空間設計|空間規劃|裝潢設計|建築設計|景觀設計|商空設計|店面設計|室內空間)/u', $ind);

// ── 從 DB 讀取 hero_stats 和 about_tags（如果有），否則使用業種預設值 ──
$dbHeroStats = !empty($client['hero_stats']) ? json_decode($client['hero_stats'], true) : null;
$dbAboutTags = !empty($client['about_tags']) ? json_decode($client['about_tags'], true) : null;

if ($isFood) {
    $defaultHeroStats = [['4.5★','Google評分'],['429+','真實評價'],['10年','料理經驗'],['95%','回訪率']];
    $defaultAboutTags = ['🍽️ 進口頂級食材','👨‍🍳 專業主廚料理','🍷 免開瓶費','🎂 包場客製服務'];
    $svcSubtitle = '嚴選食材、用心烹調，為您呈現每一道暖心料理';
    $caseLabel = 'FEATURED DISHES'; $caseTitle = '招牌料理'; $caseSub = '主廚精選，每一道都是暖心之作';
    $fbDesc = '看最新菜單、季節限定、美食分享，第一手掌握優惠資訊';
    $contactSub = '歡迎來電訂位或 LINE 預約包場';
} else {
    $defaultHeroStats = [['500+','服務客戶'],['8年','專業經驗'],['98%','回購率'],['4.9★','Google評分']];
    $defaultAboutTags = ['🏆 專業認證技師','🌱 環保清潔用品','📋 免費到府估價','⏱ 準時守信到場'];
    $svcSubtitle = '每項服務由專業技師執行，使用環保清潔劑，安全無毒';
    $caseLabel = 'CASE STUDIES'; $caseTitle = '精選施工案例'; $caseSub = '真實 Before / After 對比，讓成果說話';
    $fbDesc = '看最新優惠、案例分享、清潔小知識，第一手掌握優惠資訊';
    $contactSub = '免費到府估價，專人為您說明';
}

// DB 優先，否則用業種預設
$heroStats = $dbHeroStats
    ? array_map(fn($s) => [$s['value'], $s['label']], $dbHeroStats)
    : $defaultHeroStats;
$aboutTags = $dbAboutTags ?: $defaultAboutTags;

// aboutStats 從 heroStats 衍生（交替顯示 accent 色）
$aboutStats = array_map(fn($s, $i) => [$s[0], $s[1], $i % 2 ? 'accent' : ''], $heroStats, array_keys($heroStats));

// ─── Mini-site template system Phase 4（脫鉤的小官網模板，2026-05-28）─────
// 優先順序高於 industry-based 分流：客戶有設 minisite_template 就走模板
require_once __DIR__ . '/../includes/minisite_template_loader.php';
$_mtpl = $client['minisite_template'] ?? '_default';
if ($_mtpl !== '_default') {
    $_renderPath = minisiteTemplateRenderPath($_mtpl, 'home');
    if ($_renderPath) {
        require __DIR__ . '/layout_head.php';
        require $_renderPath;
        require __DIR__ . '/layout_foot.php';
        return;
    }
}

// 餐飲業走專屬攝影集 layout（Hawksmoor 風）
if ($isFood) {
    require __DIR__ . '/index_food.php';
    return;
}

// 室內設計走 Editorial / 建築攝影風 layout
if ($isDesign) {
    require __DIR__ . '/index_design.php';
    return;
}

require __DIR__ . '/layout_head.php';
?>

<!-- ══ 1. HERO（業種切換：餐飲 → Hawksmoor 風，其他 → PrettyClean 風）══ -->
<?php
$heroImg = !empty($client['hero_image_path'])
    ? BASE_URL . '/' . h($client['hero_image_path'])
    : industryDefaultHero($client['industry'] ?? '');
$rating = $heroStats[3][0] ?? '4.9★';
$ratingNum = preg_replace('/[★\s]/u', '', $rating) ?: '4.9';
?>

<?php if ($isFood): ?>
<!-- ─── 餐飲 Hawksmoor 風 Hero（暗黑優雅 cinematic）─── -->
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">

<section class="hero-dine">
  <div class="hero-dine-bg" style="background-image:url('<?= h($heroImg) ?>');"></div>
  <div class="hero-dine-overlay"></div>
  <div class="hero-dine-vignette"></div>

  <div class="hero-dine-content">
    <div class="hero-dine-eyebrow animate-in">
      <span class="dine-line"></span>
      <span class="dine-eyebrow-text">WELCOME TO</span>
      <span class="dine-line"></span>
    </div>

    <h1 class="hero-dine-title animate-in delay-1">
      <?= h($client['brand_name']) ?><?php if (!empty($client['tagline'])): ?><span class="hero-dine-title-sub">｜<?= h($client['tagline']) ?></span><?php endif; ?>
    </h1>

    <p class="hero-dine-quote animate-in delay-2">
      <?php
        // H1 已帶 tagline → quote 顯示 about 摘要；否則維持 tagline / 預設
        $_dineQuote = !empty($client['tagline']) && !empty($client['about_text'])
            ? mb_strimwidth(strip_tags($client['about_text']), 0, 60, '…')
            : ($client['tagline'] ?? '一道道用心料理，等你慢慢品嚐');
      ?>
      <span class="dine-quote-mark">“</span><?= h($_dineQuote) ?><span class="dine-quote-mark">”</span>
    </p>

    <div class="hero-dine-actions animate-in delay-3">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="dine-cta dine-cta-book" target="_blank">
        BOOK A TABLE · 立即訂位
      </a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="dine-cta-phone">
        📞 <?= h($phone) ?>
      </a>
      <?php endif; ?>
    </div>

    <div class="hero-dine-meta animate-in delay-4">
      <?php if($client['address']): ?><span><?= h($client['address']) ?></span><?php endif; ?>
      <?php if($client['business_hours']): ?>
      <span class="dine-meta-sep">·</span><span><?= h($client['business_hours']) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Award/評鑑引用 band -->
  <div class="hero-dine-awards">
    <div class="container">
      <div class="dine-award-quote animate-in">
        <div class="dine-award-stars">★★★★★</div>
        <div class="dine-award-text">「<?= h($ratingNum) ?> Google 評分・<?= h($heroStats[1][0] ?? '10年') ?> 在地經營・<?= h($heroStats[2][0] ?? '5000+') ?> <?= h($heroStats[2][1] ?? '位食客') ?>」</div>
      </div>
    </div>
  </div>
</section>

<style>
/* Hawksmoor 風 Hero — 暗黑 cinematic、置中、襯線標題、award band */
.hero-dine {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  background: #0a0807;
}
.hero-dine-bg {
  position: absolute; inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0.45;
  filter: contrast(1.1) saturate(1.05);
  transform: scale(1.04);
  animation: hero-dine-zoom 30s ease-in-out infinite alternate;
}
@keyframes hero-dine-zoom {
  from { transform: scale(1.04); }
  to   { transform: scale(1.14); }
}
.hero-dine-overlay {
  position: absolute; inset: 0;
  background:
    linear-gradient(180deg, rgba(10,8,7,0.6) 0%, rgba(10,8,7,0.3) 35%, rgba(10,8,7,0.85) 100%);
}
.hero-dine-vignette {
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, transparent 30%, rgba(10,8,7,0.6) 100%);
  pointer-events: none;
}
.hero-dine-content {
  position: relative; z-index: 2;
  text-align: center;
  padding: 80px 20px 140px;
  max-width: 900px;
  color: #f5e6c8;
}

/* Eyebrow */
.hero-dine-eyebrow {
  display: flex; align-items: center; justify-content: center;
  gap: 18px;
  margin-bottom: 36px;
}
.dine-line {
  display: block;
  width: 50px; height: 1px;
  background: rgba(245,230,200,0.5);
}
.dine-eyebrow-text {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  letter-spacing: 0.4em;
  font-size: .85rem;
  color: rgba(245,230,200,0.85);
  text-transform: uppercase;
}

/* Title — serif, big, elegant */
.hero-dine-title {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-size: clamp(3.2rem, 9vw, 7rem);
  font-weight: 500;
  letter-spacing: -0.01em;
  line-height: 1.05;
  color: #fff;
  margin: 0 0 32px;
  text-shadow: 0 4px 40px rgba(0,0,0,0.6);
}
.hero-dine-title-sub {
  display: inline-block;
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-style: italic;
  font-size: 0.32em;
  font-weight: 400;
  letter-spacing: 0;
  color: rgba(245,230,200,0.88);
  margin-left: 8px;
  vertical-align: middle;
  text-shadow: 0 2px 18px rgba(0,0,0,0.5);
}
@media (max-width: 640px) {
  .hero-dine-title-sub { display: block; margin-top: 10px; font-size: 0.42em; }
}

/* Quote */
.hero-dine-quote {
  font-family: 'Cormorant Garamond', 'Noto Serif TC', serif;
  font-style: italic;
  font-size: clamp(1.1rem, 1.8vw, 1.4rem);
  font-weight: 400;
  color: rgba(245,230,200,0.88);
  line-height: 1.5;
  margin: 0 auto 48px;
  max-width: 620px;
  text-shadow: 0 2px 18px rgba(0,0,0,0.5);
}
.dine-quote-mark {
  font-family: 'Cormorant Garamond', serif;
  color: rgba(245,230,200,0.55);
  font-size: 1.4em;
  font-weight: 500;
  vertical-align: -0.1em;
  margin: 0 -2px;
}

/* CTAs */
.hero-dine-actions {
  display: flex; flex-direction: column; align-items: center;
  gap: 18px;
  margin-bottom: 36px;
}
.dine-cta {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 18px 44px;
  font-size: .9rem; font-weight: 700;
  letter-spacing: 0.16em;
  text-decoration: none;
  text-transform: uppercase;
  transition: all .3s ease;
  white-space: nowrap;
}
.dine-cta-book {
  background: transparent;
  color: #fff;
  border: 1.5px solid rgba(245,230,200,0.7);
  border-radius: 0;
  position: relative;
}
.dine-cta-book::before {
  content: ''; position: absolute; inset: 0;
  background: rgba(245,230,200,0.08);
  opacity: 0; transition: opacity .3s ease;
}
.dine-cta-book:hover {
  border-color: #f5e6c8;
  letter-spacing: 0.2em;
}
.dine-cta-book:hover::before { opacity: 1; }
.dine-cta-phone {
  font-family: 'Cormorant Garamond', serif;
  color: rgba(245,230,200,0.85);
  font-size: 1.1rem; font-weight: 500;
  text-decoration: none;
  letter-spacing: 0.04em;
  border-bottom: 1px solid rgba(245,230,200,0.3);
  padding-bottom: 2px;
  transition: all .25s ease;
}
.dine-cta-phone:hover { color: #f5e6c8; border-color: #f5e6c8; }

/* Meta */
.hero-dine-meta {
  display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 10px;
  font-family: 'Cormorant Garamond', serif;
  font-size: .95rem;
  color: rgba(245,230,200,0.65);
  font-style: italic;
}
.dine-meta-sep { color: rgba(245,230,200,0.35); }

/* Award band — 底部評鑑引用 */
.hero-dine-awards {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  z-index: 2;
  padding: 28px 20px;
  background: linear-gradient(180deg, transparent 0%, rgba(10,8,7,0.6) 100%);
  border-top: 1px solid rgba(245,230,200,0.12);
}
.dine-award-quote {
  text-align: center;
  color: rgba(245,230,200,0.85);
}
.dine-award-stars {
  font-size: 1rem;
  color: #f4b53b;
  letter-spacing: 4px;
  margin-bottom: 6px;
  text-shadow: 0 0 14px rgba(244,181,59,0.4);
}
.dine-award-text {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: .95rem;
  letter-spacing: 0.04em;
}

/* RWD */
@media (max-width: 768px) {
  .hero-dine { min-height: 92vh; }
  .hero-dine-content { padding: 56px 16px 120px; }
  .hero-dine-title { font-size: clamp(2.5rem, 11vw, 4rem); }
  .hero-dine-quote { font-size: 1.05rem; margin-bottom: 36px; }
  .dine-cta-book { padding: 15px 32px; font-size: .82rem; letter-spacing: 0.12em; }
  .dine-line { width: 32px; }
  .dine-eyebrow-text { font-size: .78rem; letter-spacing: 0.32em; }
  .hero-dine-meta { font-size: .85rem; }
  .dine-award-text { font-size: .85rem; }
}
</style>

<?php else: ?>
<!-- ─── 服務業 PrettyClean 風 Hero（亮色照片 + 雙 CTA + marquee）─── -->
<section class="hero-pro">
  <div class="hero-pro-bg" style="background-image:url('<?= h($heroImg) ?>');"></div>
  <div class="hero-pro-overlay"></div>

  <div class="container hero-pro-content">
    <div class="hero-pro-tag animate-in">
      <span class="hero-pro-tag-dot"></span>
      <?= h($client['industry'] ?? '專業服務') ?>
      <?php if(preg_match('/^(.{2,3}[市縣])/u', $client['address'] ?? '', $m)): ?>
      <span class="hero-pro-tag-sep">·</span> <?= h($m[1]) ?>
      <?php endif; ?>
    </div>

    <h1 class="hero-pro-title animate-in delay-1">
      <?= h($client['brand_name']) ?>
    </h1>

    <p class="hero-pro-sub animate-in delay-2">
      <?= h($client['tagline'] ?? '用心服務，品質保證') ?>
    </p>

    <div class="hero-pro-actions animate-in delay-3">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="hero-pro-cta hero-pro-cta-primary" target="_blank">
        免費估價諮詢 →
      </a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="hero-pro-cta hero-pro-cta-secondary">
        📞 <?= h($phone) ?>
      </a>
      <?php endif; ?>
    </div>

    <div class="hero-pro-trust animate-in delay-4">
      <div class="trust-item">
        <span class="trust-stars">★★★★★</span>
        <span class="trust-text"><strong><?= h($ratingNum) ?></strong> Google 評分</span>
      </div>
      <span class="trust-divider"></span>
      <div class="trust-item">
        <span class="trust-text"><strong><?= h($heroStats[0][0] ?? '500+') ?></strong> <?= h($heroStats[0][1] ?? '服務客戶') ?></span>
      </div>
      <span class="trust-divider"></span>
      <div class="trust-item">
        <span class="trust-text"><strong><?= h($heroStats[1][0] ?? '8年') ?></strong> <?= h($heroStats[1][1] ?? '深耕在地') ?></span>
      </div>
    </div>
  </div>

  <!-- 跑馬燈裝飾文字（仿 prettycleantx 重複文字） -->
  <div class="hero-pro-marquee" aria-hidden="true">
    <div class="marquee-track">
      <?php $marqueeText = ($client['industry'] ?? '專業服務') . ' · ' . ($client['brand_name']) . ' · '; ?>
      <span><?= str_repeat(h($marqueeText), 6) ?></span>
      <span><?= str_repeat(h($marqueeText), 6) ?></span>
    </div>
  </div>
</section>

<style>
/* Phase 2.2「PrettyClean 風」Hero — 大照片 + 置中粗標 + 雙 CTA + marquee 跑馬燈 */
.hero-pro {
  position: relative;
  min-height: 88vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  background: var(--g-ink);
}
.hero-pro-bg {
  position: absolute; inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0.55;
  transform: scale(1.04);
  animation: hero-pro-zoom 24s ease-in-out infinite alternate;
}
@keyframes hero-pro-zoom {
  from { transform: scale(1.04); }
  to   { transform: scale(1.12); }
}
.hero-pro-overlay {
  position: absolute; inset: 0;
  background:
    linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 30%, rgba(0,0,0,0.55) 100%),
    linear-gradient(135deg, rgba(var(--g-accent-rgb),0.12) 0%, transparent 60%);
}
.hero-pro-content {
  position: relative; z-index: 2;
  text-align: center;
  padding: 80px 20px 100px;
  max-width: 920px;
}

.hero-pro-tag {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,0.95);
  color: var(--g-ink);
  padding: 8px 18px;
  border-radius: 100px;
  font-size: .82rem; font-weight: 700;
  letter-spacing: 0.04em;
  margin-bottom: 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.hero-pro-tag-dot {
  width: 6px; height: 6px;
  background: var(--g-accent); border-radius: 50%;
  animation: hero-pro-pulse 2s ease-in-out infinite;
}
@keyframes hero-pro-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(var(--g-accent-rgb), 0.6); }
  50% { box-shadow: 0 0 0 6px rgba(var(--g-accent-rgb), 0); }
}
.hero-pro-tag-sep { color: rgba(0,0,0,0.25); }

.hero-pro-title {
  font-family: 'Noto Sans TC', sans-serif;
  font-size: clamp(3rem, 8vw, 6rem);
  font-weight: 900;
  letter-spacing: -0.04em;
  line-height: 1;
  color: #fff;
  margin: 0 0 22px;
  text-shadow: 0 4px 30px rgba(0,0,0,0.4);
}

.hero-pro-sub {
  font-size: clamp(1.05rem, 2vw, 1.4rem);
  font-weight: 400;
  color: rgba(255,255,255,0.92);
  line-height: 1.55;
  margin: 0 auto 40px;
  max-width: 640px;
  text-shadow: 0 2px 16px rgba(0,0,0,0.3);
}

.hero-pro-actions {
  display: flex; gap: 14px; flex-wrap: wrap;
  justify-content: center;
  margin-bottom: 48px;
}
.hero-pro-cta {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 16px 32px;
  border-radius: 100px;
  font-size: 1rem; font-weight: 700;
  text-decoration: none;
  transition: all .3s cubic-bezier(.22,.61,.36,1);
  white-space: nowrap;
  letter-spacing: 0.02em;
}
.hero-pro-cta-primary {
  background: var(--g-accent);
  color: #fff;
  box-shadow: 0 12px 30px -8px rgba(var(--g-accent-rgb), 0.65);
}
.hero-pro-cta-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 18px 40px -10px rgba(var(--g-accent-rgb), 0.8);
  filter: brightness(1.05);
}
.hero-pro-cta-secondary {
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  color: #fff;
  border: 1.5px solid rgba(255,255,255,0.4);
}
.hero-pro-cta-secondary:hover {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255,255,255,0.7);
}

.hero-pro-trust {
  display: inline-flex; align-items: center; gap: 18px;
  flex-wrap: wrap;
  justify-content: center;
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 100px;
  padding: 14px 28px;
  color: rgba(255,255,255,0.92);
  font-size: .92rem;
}
.trust-item { display: inline-flex; align-items: center; gap: 8px; }
.trust-item strong { color: #fff; font-weight: 800; font-size: 1rem; }
.trust-stars {
  color: #f4b53b;
  font-size: .95rem;
  letter-spacing: 1px;
  text-shadow: 0 0 12px rgba(244,181,59,0.5);
}
.trust-divider {
  width: 1px; height: 16px;
  background: rgba(255,255,255,0.3);
}

/* Marquee 跑馬燈（仿 prettycleantx） */
.hero-pro-marquee {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  z-index: 1;
  overflow: hidden;
  height: 60px;
  display: flex; align-items: center;
  border-top: 1px solid rgba(255,255,255,0.1);
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(8px);
}
.marquee-track {
  display: flex; gap: 0;
  white-space: nowrap;
  animation: hero-pro-marquee 40s linear infinite;
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: rgba(255,255,255,0.18);
  text-transform: uppercase;
  font-family: 'Noto Sans TC', sans-serif;
}
.marquee-track span {
  flex-shrink: 0;
  padding: 0 8px;
}
@keyframes hero-pro-marquee {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

/* RWD */
@media (max-width: 768px) {
  .hero-pro { min-height: 95vh; }
  .hero-pro-content { padding: 56px 16px 100px; }
  .hero-pro-title { font-size: clamp(2.2rem, 9vw, 3.4rem); }
  .hero-pro-sub { font-size: 1rem; margin-bottom: 32px; }
  .hero-pro-actions { gap: 10px; margin-bottom: 36px; }
  .hero-pro-cta { padding: 14px 22px; font-size: .92rem; }
  .hero-pro-trust { padding: 12px 18px; gap: 10px; font-size: .82rem; }
  .trust-divider { display: none; }
  .hero-pro-marquee { height: 44px; }
  .marquee-track { font-size: 1rem; }
}
</style>
<?php endif; ?>
<!-- /HERO 業種切換結束 -->

<!-- ══ 2. 關於我們 ════════════════════════════════════════ -->
<section class="section" style="background:var(--g-bg-alt)">
  <div class="container">
    <div class="about-layout">
      <div class="about-text animate-in">
        <div class="section-title" style="text-align:left;margin-bottom:24px">
          <div class="label">ABOUT US</div>
          <h2>關於<?= h($client['brand_name']) ?></h2>
        </div>
        <div style="color:#555;line-height:1.9;margin-bottom:20px" class="rich-content">
          <?php
          // about_text 由 Quill 編富文本（HTML），含 HTML 直接 echo；純文字才 escape
          $_about = $client['about_text'] ?? '';
          if ($_about !== '' && strip_tags($_about) !== $_about) echo $_about;
          else echo nl2br(h($_about));
          ?>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px">
          <?php foreach($aboutTags as $f): ?>
            <div style="background:rgba(var(--g-ink-rgb),.07);color:var(--g-ink);padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:700;border:1px solid rgba(var(--g-ink-rgb),.15)"><?= $f ?></div>
          <?php endforeach; ?>
        </div>
        <a href="<?= siteUrl($sub,'services') ?>" class="btn btn-primary" style="margin-top:24px">查看服務項目 →</a>
      </div>
      <div class="about-visual animate-in delay-2">
        <?php foreach($aboutStats as $v): ?>
        <div style="background:<?= $v[2]?'var(--g-ink)':'#fff' ?>;border-radius:16px;padding:28px 20px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.07)">
          <div style="font-size:2.2rem;font-weight:900;color:<?= $v[2]?'#fff':'var(--g-ink)' ?>;line-height:1"><?= $v[0] ?></div>
          <div style="font-size:.78rem;color:<?= $v[2]?'rgba(255,255,255,.75)':'#888' ?>;margin-top:6px;font-weight:600"><?= $v[1] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<style>
.about-layout{display:grid;grid-template-columns:1.2fr 1fr;gap:60px;align-items:center}
@media(max-width:768px){.about-layout{grid-template-columns:1fr;gap:32px}}
.about-visual{display:grid;grid-template-columns:1fr 1fr;gap:16px}
</style>

<!-- ══ 3. 服務預覽 ════════════════════════════════════════ -->
<section class="section">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">OUR SERVICES</div>
      <h2>專業服務項目</h2>
      <p><?= $svcSubtitle ?></p>
    </div>
    <div class="grid-<?= min(count($site['services']),4) ?>">
      <?php foreach($site['services'] as $i=>$svc): ?>
      <a href="<?= siteUrl($sub,'services') ?>#svc-<?= $svc['id'] ?>"
         class="animate-in delay-<?= min($i+1,4) ?>"
         style="background:#fff;border-radius:16px;padding:0 0 0 0;border:1.5px solid rgba(var(--g-ink-rgb),.08);transition:all .25s;display:block;color:inherit;overflow:hidden">
        <?php
        $icon = $svc['icon'] ?? '';
        $imgPath = $svc['image_path'] ?? '';
        if ($imgPath): ?>
          <div style="overflow:hidden">
            <img src="<?= BASE_URL.'/'.h($imgPath) ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block" alt="<?= h($svc['name']) ?>"
                 onerror="this.parentElement.innerHTML='<div style=\'width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.6));display:flex;align-items:center;justify-content:center;font-size:3rem\'><?= $isFood ? '🍽️' : addslashes(h($icon ?: '🛠️')) ?></div>'">
          </div>
        <?php elseif ($icon && preg_match('/\.(svg|jpg|jpeg|png|webp)$/i', $icon)): ?>
          <div style="overflow:hidden">
            <img src="<?= BASE_URL.'/'.h($icon) ?>" style="width:100%;aspect-ratio:4/3;object-fit:contain;display:block;background:#f8f9fa;padding:20px" alt="<?= h($svc['name']) ?>"
                 onerror="this.parentElement.innerHTML='<div style=\'width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.6));display:flex;align-items:center;justify-content:center;font-size:3rem\'>🛠️</div>'">
          </div>
        <?php else: ?>
          <div style="overflow:hidden">
            <div style="width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.6));display:flex;align-items:center;justify-content:center;font-size:3rem"><?= $isFood ? '🍽️' : h($icon ?: '🛠️') ?></div>
          </div>
        <?php endif; ?>
        <div style="padding:20px 22px 24px">
          <h3 style="font-size:1.05rem;font-weight:800;color:var(--g-ink);margin-bottom:8px"><?= h($svc['name']) ?></h3>
          <p  style="font-size:.85rem;color:#666;line-height:1.7;margin-bottom:12px"><?= h($svc['short_desc']??'') ?></p>
          <?php if($svc['price_text']): ?>
            <div style="display:inline-block;background:rgba(var(--g-accent-rgb),.12);color:var(--g-accent);font-weight:800;font-size:.85rem;padding:4px 12px;border-radius:20px;margin-bottom:10px"><?= h($svc['price_text']) ?></div>
          <?php endif; ?>
          <div style="font-size:.8rem;color:var(--g-ink);font-weight:700">了解詳情 →</div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:40px">
      <a href="<?= siteUrl($sub,'services') ?>" class="btn btn-primary">查看完整服務說明與 FAQ</a>
    </div>
  </div>
</section>

<!-- ══ 4. 精選案例 ════════════════════════════════════════ -->
<?php $featured=array_slice(array_values(array_filter($site['cases'],fn($c)=>$c['is_featured'])),0,3);
if($featured): ?>
<section class="section" style="background:var(--g-bg-alt)">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label"><?= $caseLabel ?></div>
      <h2><?= $caseTitle ?></h2>
      <p><?= $caseSub ?></p>
    </div>
    <div class="grid-3">
      <?php foreach($featured as $i=>$c): ?>
      <?php $bThumb = caseThumb($c['before_image']??''); $aThumb = caseThumb($c['after_image']??''); ?>
      <a href="<?= siteUrl($sub,'cases') ?>" class="animate-in delay-<?= $i+1 ?>" style="display:block;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);transition:transform .2s,box-shadow .2s;text-decoration:none;color:inherit"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 32px rgba(0,0,0,.12)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <?php $_caseAlt = h(($c['title'] ?? '') . ($c['svc_name'] ? ' · ' . $c['svc_name'] : '')); ?>
        <?php if($bThumb && $aThumb && !$isFood): ?>
        <div style="position:relative;height:200px;overflow:hidden">
          <img src="<?= BASE_URL.'/'.h($bThumb) ?>" alt="<?= $_caseAlt ?>・施作前" style="position:absolute;top:0;left:0;width:50%;height:100%;object-fit:cover;filter:saturate(.6) brightness(.95)">
          <img src="<?= BASE_URL.'/'.h($aThumb) ?>" alt="<?= $_caseAlt ?>・施作後" style="position:absolute;top:0;right:0;width:50%;height:100%;object-fit:cover">
          <div style="position:absolute;left:50%;top:0;width:2px;height:100%;background:#fff;z-index:2"></div>
          <div style="position:absolute;top:8px;left:0;right:0;display:flex;justify-content:space-between;padding:0 8px;z-index:3">
            <span style="font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:4px;background:rgba(0,0,0,.55);color:#fff">BEFORE</span>
            <span style="font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:4px;background:var(--g-accent);color:#fff">AFTER</span>
          </div>
        </div>
        <?php elseif($aThumb): ?>
          <img src="<?= BASE_URL.'/'.h($aThumb) ?>" alt="<?= $_caseAlt ?>" style="width:100%;height:200px;object-fit:cover">
        <?php elseif($bThumb): ?>
          <img src="<?= BASE_URL.'/'.h($bThumb) ?>" alt="<?= $_caseAlt ?>" style="width:100%;height:200px;object-fit:cover">
        <?php else: ?>
          <div style="width:100%;height:200px;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.7));display:flex;align-items:center;justify-content:center">
            <span style="font-size:3rem;opacity:.3"><?= $isFood ? '🍽️' : '📸' ?></span>
          </div>
        <?php endif; ?>
        <div style="padding:16px 18px">
          <?php if($c['svc_name']): ?>
            <span style="background:rgba(var(--g-ink-rgb),.08);color:var(--g-ink);font-size:.72rem;font-weight:700;padding:2px 10px;border-radius:12px;margin-bottom:8px;display:inline-block"><?= h($c['svc_name']) ?></span>
          <?php endif; ?>
          <h3 style="font-size:.95rem;font-weight:800;color:var(--g-ink-soft);margin-bottom:6px"><?= h($c['title']) ?></h3>
          <div style="display:flex;gap:12px;font-size:.78rem;color:#888">
            <?php if($c['location']): ?><span>📍 <?= h($c['location']) ?></span><?php endif; ?>
            <?php if($c['area_sqm'] && !$isFood): ?><span>📐 <?= $c['area_sqm'] ?>坪</span><?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:36px">
      <a href="<?= siteUrl($sub,'cases') ?>" class="btn btn-outline">查看全部案例 →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 5. 網友評價 ════════════════════════════════════════ -->
<?php if($site['testimonials']): ?>
<section class="section">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">TESTIMONIALS</div>
      <h2>客戶真實評價</h2>
      <p>每一個笑容，都是我們最大的動力</p>
    </div>
    <div class="grid-3">
      <?php foreach($site['testimonials'] as $i=>$t): ?>
      <div class="animate-in delay-<?= min($i%4+1,4) ?>" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);transition:transform .2s,box-shadow .2s"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 32px rgba(0,0,0,.12)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <?php if(!empty($t['og_image'])): ?>
          <img src="<?= h($t['og_image']) ?>" style="width:100%;height:180px;object-fit:cover" loading="lazy" alt="口碑文章"
               onerror="this.style.display='none'">
        <?php endif; ?>
        <div style="padding:18px 20px">
          <div style="color:#f4a611;font-size:1rem;margin-bottom:10px">★★★★★</div>
          <p style="font-size:.88rem;color:#555;line-height:1.8;margin-bottom:14px;font-style:italic">"<?= h(mb_strimwidth($t['content'],0,100,'…')) ?>"</p>
          <?php if($t['svc_name']): ?>
            <span style="display:inline-block;background:rgba(var(--g-ink-rgb),.08);color:var(--g-ink);font-size:.72rem;font-weight:700;padding:2px 10px;border-radius:12px"><?= h($t['svc_name']) ?></span>
          <?php endif; ?>
          <?php if(!empty($t['source_url'])): ?>
            <a href="<?= h($t['source_url']) ?>" target="_blank" rel="noopener nofollow ugc" style="display:inline-block;margin-top:10px;font-size:.78rem;color:var(--g-ink);text-decoration:none;font-weight:600">📖 閱讀全文 →</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:36px">
      <a href="<?= siteUrl($sub,'testimonials') ?>" class="btn btn-outline">查看全部評價 →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 5.3 經營者介紹（partial） ═══════════════════════ -->
<?php require __DIR__ . '/_partial_owner_block.php'; ?>

<!-- ══ 5.4 店家相簿（partial） ═══════════════════════ -->
<?php require __DIR__ . '/_partial_photos_gallery.php'; ?>

<!-- ══ 5.5 最新專欄 preview（partial）═══════════════════════ -->
<?php require __DIR__ . '/_partial_column_preview.php'; ?>

<!-- ══ 6. 聯絡 + 地圖 + FB 粉絲頁 ═══════════════════════ -->
<section class="section" id="contact">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">CONTACT</div>
      <h2>立即聯絡我們</h2>
      <p><?= $contactSub ?></p>
    </div>
    <style>
    .contact-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:40px;align-items:start}
    @media(max-width:768px){.contact-grid{grid-template-columns:1fr;gap:24px}}
    </style>
    <div class="contact-grid">
      <!-- 左：Facebook 粉絲頁嵌入（最新貼文） -->
      <div class="animate-in">
        <?php if(!empty($social['fb_page_url'])): ?>
          <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);border:1px solid #e4e4e4">
            <div style="background:#1877f2;color:#fff;padding:14px 20px;display:flex;align-items:center;gap:10px">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="#fff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              <span style="font-weight:700;font-size:.95rem">Facebook 粉絲頁</span>
            </div>
            <div style="padding:0">
              <div id="fb-root"></div>
              <script async defer crossorigin="anonymous" src="https://connect.facebook.net/zh_TW/sdk.js#xfbml=1&version=v21.0" nonce="fbSDK"></script>
              <div class="fb-page"
                   data-href="<?= h($social['fb_page_url']) ?>"
                   data-tabs="timeline"
                   data-width="500"
                   data-height="700"
                   data-small-header="true"
                   data-adapt-container-width="true"
                   data-hide-cover="false"
                   data-show-facepile="true">
                <blockquote cite="<?= h($social['fb_page_url']) ?>" class="fb-xfbml-parse-ignore">
                  <a href="<?= h($social['fb_page_url']) ?>" target="_blank" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:400px;text-decoration:none;color:#666">
                    <div style="font-size:3rem;margin-bottom:12px">📘</div>
                    <div style="font-weight:700;color:var(--g-ink);margin-bottom:8px">前往 Facebook 粉絲頁</div>
                    <div style="font-size:.85rem">查看最新動態與施工分享</div>
                  </a>
                </blockquote>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- 沒有 FB 粉絲頁：地圖放大 -->
          <div style="border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);min-height:400px">
            <?php if($client['google_maps_embed']): ?>
              <iframe src="<?= h($client['google_maps_embed']) ?>" width="100%" height="500" style="border:0" allowfullscreen loading="lazy"></iframe>
            <?php else: ?>
              <div style="height:400px;background:var(--g-bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px">
                <div style="font-size:3rem;margin-bottom:12px">🗺️</div>
                <div style="font-weight:700;color:var(--g-ink)">📍 <?= h($client['address']??'') ?></div>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- 右：聯絡資訊 + LINE CTA + 地圖 -->
      <div class="animate-in delay-2">
        <?php
        $rows=[
          ['📞','電話',$phone?'<a href="tel:'.preg_replace('/[^0-9+]/','',$phone).'">'.h($phone).'</a>':''],
          ['📍','地址',h($client['address']??'')],
          ['📧','Email',$client['email']?'<a href="mailto:'.h($client['email']).'">'.h($client['email']).'</a>':''],
          ['💬','LINE',h($social['line_id']??'')],
        ];
        foreach(array_filter($rows,fn($r)=>$r[2]) as $r): ?>
        <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 0;border-bottom:1px solid rgba(var(--g-ink-rgb),.07)">
          <div style="font-size:1.4rem;flex-shrink:0;margin-top:2px"><?= $r[0] ?></div>
          <div>
            <div style="font-size:.75rem;color:#999;font-weight:600;letter-spacing:.05em;margin-bottom:2px"><?= $r[1] ?></div>
            <div style="font-size:1rem;font-weight:700"><?= $r[2] ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- LINE + 電話 CTA -->
        <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap">
          <?php if($lineUrl): ?>
            <a href="<?= h($lineUrl) ?>" class="btn btn-accent" target="_blank" style="flex:1;justify-content:center">💬 LINE 立即諮詢</a>
          <?php endif; ?>
          <?php if($phone): ?>
            <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn btn-primary" style="flex:1;justify-content:center">📞 <?= h($phone) ?></a>
          <?php endif; ?>
        </div>

        <!-- 地圖 -->
        <div style="margin-top:24px;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);height:280px">
          <?php if($client['google_maps_embed']): ?>
            <iframe src="<?= h($client['google_maps_embed']) ?>" width="100%" height="100%" style="border:0;min-height:280px" allowfullscreen loading="lazy"></iframe>
          <?php else: ?>
            <div style="height:100%;background:var(--g-bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px">
              <div style="font-size:3rem;margin-bottom:12px">🗺️</div>
              <div style="font-weight:700;color:var(--g-ink)">📍 <?= h($client['address']??'') ?></div>
              <div style="font-size:.85rem;color:#888;margin-top:6px">後台設定 Google Maps 後會顯示地圖</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/layout_foot.php'; ?>
