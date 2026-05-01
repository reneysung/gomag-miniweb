<?php
/**
 * site/layout_head.php
 * 共用前台 Header — 透過 $slug 與 $site 動態渲染
 * 需在引入前定義：$site, $slug, $pageKey
 */
require_once __DIR__ . '/../includes/seo_schema.php';

$client   = $site['client'];
$theme    = $site['theme'];
$social   = $site['social'];
$services = $site['services'];
$seo      = getSeo($site, $pageKey);

$metaTitle = $seo['meta_title'] ?? ($client['brand_name'] . '｜' . ($client['tagline'] ?? ''));
$metaDesc  = $seo['meta_desc']  ?? ($client['about_text'] ? mb_strimwidth($client['about_text'], 0, 120, '…') : '');
$canonicalUrl = getCanonicalUrl($slug, $pageKey);
$ogImage   = $seo['og_image'] ?? ($client['hero_image_path'] ? BASE_URL . '/' . $client['hero_image_path'] : '');
$lineUrl   = $social['line_url']     ?? '#';
$fbUrl     = $social['fb_page_url']  ?? '#';
$phone     = $client['phone']        ?? '';
$_ind = $client['industry'] ?? '';
$_isFood = str_contains($_ind,'餐') || str_contains($_ind,'食') || str_contains($_ind,'料理') || str_contains($_ind,'咖啡') || str_contains($_ind,'甜點');
$_casesLabel = $_isFood ? '料理作品' : '施工案例';
$_casesIcon  = $_isFood ? '🍽️' : '📸';
$_logoIcon   = $_isFood ? '🍝' : '🌊';
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($metaTitle) ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<link rel="canonical" href="<?= h($canonicalUrl) ?>">
<meta property="og:title"       content="<?= h($seo['og_title'] ?? $metaTitle) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<meta property="og:type"        content="website">
<meta property="og:url"         content="<?= h($canonicalUrl) ?>">
<?php if ($ogImage): ?>
<meta property="og:image"       content="<?= h($ogImage) ?>">
<meta name="twitter:card"       content="summary_large_image">
<meta name="twitter:image"      content="<?= h($ogImage) ?>">
<?php endif; ?>
<meta name="twitter:title"      content="<?= h($metaTitle) ?>">
<meta name="twitter:description" content="<?= h($metaDesc) ?>">
<?php outputJsonLd($site, $slug, $pageKey); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&display=swap" rel="stylesheet">
<?php outputThemeCss($theme); ?>
<style>
/* ══ RESET & BASE ══════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;font-size:16px}
body{font-family:'Noto Sans TC',sans-serif;color:var(--c-text);background:var(--c-bg);line-height:1.7;padding-bottom:56px}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}

/* ══ LAYOUT ════════════════════════════════════════════════ */
.container{max-width:1120px;margin:0 auto;padding:0 20px}
.section{padding:72px 0}
.section-sm{padding:48px 0}

/* ══ SECTION TITLE ═════════════════════════════════════════ */
.section-title{text-align:center;margin-bottom:48px}
.section-title .label{display:inline-block;background:rgba(var(--c-accent-rgb),.12);color:var(--c-accent);font-size:.75rem;font-weight:700;letter-spacing:.14em;padding:4px 14px;border-radius:20px;margin-bottom:12px}
.section-title h2{font-size:clamp(1.5rem,3vw,2rem);font-weight:900;color:var(--c-primary);line-height:1.3}
.section-title p{margin-top:12px;color:#666;font-size:.95rem;max-width:520px;margin-left:auto;margin-right:auto}

/* ══ TOPBAR ════════════════════════════════════════════════ */
.topbar-strip{background:var(--c-primary);color:rgba(255,255,255,.85);font-size:.8rem;padding:7px 0;display:none}
@media(min-width:768px){.topbar-strip{display:block}}
.topbar-strip .inner{display:flex;justify-content:space-between;align-items:center}
.topbar-strip a{color:rgba(255,255,255,.85);transition:color .2s}
.topbar-strip a:hover{color:#fff}
.topbar-strip .links{display:flex;gap:20px}

/* ══ HEADER ════════════════════════════════════════════════ */
.site-header{position:sticky;top:0;z-index:200;background:rgba(255,255,255,.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(var(--c-primary-rgb),.08);box-shadow:0 2px 20px rgba(0,0,0,.06)}
.header-inner{display:flex;align-items:center;justify-content:space-between;height:66px}
.site-logo{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.15rem;color:var(--c-primary)}
.site-logo img{height:40px;width:auto;object-fit:contain}
.logo-text .sub{display:block;font-size:.65rem;font-weight:400;color:#888;letter-spacing:.05em;margin-top:-2px}
.site-nav{display:none}
@media(min-width:768px){.site-nav{display:flex;align-items:center;gap:4px}}
.site-nav a{padding:8px 16px;border-radius:8px;font-size:.9rem;font-weight:600;color:var(--c-text);transition:all .2s;white-space:nowrap}
.site-nav a:hover,.site-nav a.active{background:rgba(var(--c-primary-rgb),.08);color:var(--c-primary)}
.site-nav .btn-contact{background:var(--c-primary);color:#fff!important;padding:8px 18px;border-radius:20px;margin-left:8px}
.site-nav .btn-contact:hover{filter:brightness(1.1)}
.nav-toggle{display:flex;align-items:center;justify-content:center;width:42px;height:42px;background:none;border:1.5px solid var(--c-primary);border-radius:8px;cursor:pointer;color:var(--c-primary);font-size:1.3rem;transition:all .2s}
.nav-toggle:hover{background:var(--c-primary);color:#fff}
@media(min-width:768px){.nav-toggle{display:none}}
.mobile-menu{display:none;flex-direction:column;background:#fff;border-top:1px solid rgba(var(--c-primary-rgb),.08);padding:12px 20px 16px;position:absolute;top:100%;left:0;right:0;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:199}
.mobile-menu.open{display:flex}
.mobile-menu a{padding:12px 0;border-bottom:1px solid #f0f0f0;font-size:.95rem;font-weight:600;color:var(--c-text);display:flex;align-items:center;gap:8px}
.mobile-menu a:last-child{border-bottom:none}
.mobile-menu a.highlight{color:var(--c-accent);font-weight:700;font-size:1.05rem}

/* ══ FLOAT LINE ════════════════════════════════════════════ */
.float-line{position:fixed;right:20px;bottom:80px;z-index:300;width:54px;height:54px;background:#06c755;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(6,199,85,.4);transition:transform .2s,box-shadow .2s;animation:float-pulse 3s ease-in-out infinite}
.float-line:hover{transform:scale(1.1);box-shadow:0 6px 28px rgba(6,199,85,.5)}
.float-line svg{width:30px;height:30px;fill:#fff}
@keyframes float-pulse{0%,100%{box-shadow:0 4px 20px rgba(6,199,85,.4)}50%{box-shadow:0 4px 28px rgba(6,199,85,.65)}}

/* ══ BOTTOM TAB ════════════════════════════════════════════ */
.bottom-tabbar{position:fixed;bottom:0;left:0;right:0;height:56px;background:#fff;border-top:1px solid #e8e8e8;display:flex;z-index:300;box-shadow:0 -2px 16px rgba(0,0,0,.08)}
@media(min-width:768px){.bottom-tabbar{display:none}}
.tab-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;color:#999;font-size:.62rem;font-weight:600;cursor:pointer;transition:color .2s;text-decoration:none}
.tab-item.active{color:var(--c-primary)}
.tab-item:hover{color:var(--c-primary)}
.tab-item.accent{color:var(--c-accent)!important}
.tab-item .icon{font-size:1.25rem}

/* ══ BUTTONS ═══════════════════════════════════════════════ */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 28px;border-radius:50px;font-size:.9rem;font-weight:700;letter-spacing:.04em;cursor:pointer;border:2px solid transparent;transition:all .2s;white-space:nowrap}
.btn-primary{background:var(--c-primary);color:#fff;border-color:var(--c-primary)}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px);box-shadow:0 6px 20px rgba(var(--c-primary-rgb),.3)}
.btn-accent{background:var(--c-accent);color:#fff;border-color:var(--c-accent)}
.btn-accent:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-outline{background:transparent;color:var(--c-primary);border-color:var(--c-primary)}
.btn-outline:hover{background:var(--c-primary);color:#fff}

/* ══ GRID ══════════════════════════════════════════════════ */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
@media(max-width:900px){.grid-3,.grid-4{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.grid-2,.grid-3,.grid-4{grid-template-columns:1fr}}

/* ══ PAGE BANNER ═══════════════════════════════════════════ */
.page-banner{background:linear-gradient(135deg,var(--c-primary) 0%,rgba(var(--c-primary-rgb),.8) 100%);color:#fff;padding:52px 0 44px;text-align:center;position:relative;overflow:hidden}
.page-banner::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E")}
.page-banner h1{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;position:relative}
.page-banner p{margin-top:10px;opacity:.8;font-size:.95rem;position:relative}

/* ══ BREADCRUMB ════════════════════════════════════════════ */
.breadcrumb{padding:12px 0;font-size:.8rem;color:#999;display:flex;align-items:center;gap:6px}
.breadcrumb a{color:var(--c-primary)}
.breadcrumb a:hover{text-decoration:underline}

/* ══ FOOTER ════════════════════════════════════════════════ */
.site-footer{background:var(--c-primary);color:rgba(255,255,255,.8);padding:48px 0 24px;font-size:.875rem}
.footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:40px;margin-bottom:40px}
@media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:28px}}
.footer-brand{font-size:1.1rem;font-weight:900;color:#fff;margin-bottom:10px}
.footer-tagline{font-size:.85rem;opacity:.7;margin-bottom:16px}
.footer-contact-item{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:.875rem}
.footer-contact-item a{color:rgba(255,255,255,.8)}
.footer-contact-item a:hover{color:#fff}
.footer-h{font-weight:700;color:#fff;margin-bottom:14px;font-size:.875rem;letter-spacing:.05em}
.footer-link{display:block;color:rgba(255,255,255,.7);padding:4px 0;transition:color .2s}
.footer-link:hover{color:#fff}
.footer-social{display:flex;gap:10px;margin-top:14px}
.footer-social a{width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.95rem;transition:background .2s}
.footer-social a:hover{background:rgba(255,255,255,.25)}
.footer-bottom{border-top:1px solid rgba(255,255,255,.12);padding-top:20px;text-align:center;font-size:.8rem;opacity:.55}

/* ══ ANIMATE IN ════════════════════════════════════════════ */
.animate-in{opacity:0;transform:translateY(28px);transition:opacity .55s ease,transform .55s ease}
.animate-in.visible{opacity:1;transform:none}
.delay-1{transition-delay:.1s}.delay-2{transition-delay:.2s}
.delay-3{transition-delay:.3s}.delay-4{transition-delay:.4s}
</style>
</head>
<body>

<!-- Top Info Bar -->
<div class="topbar-strip">
  <div class="container inner">
    <div>📞 <?= h($phone) ?>&nbsp;&nbsp;📍 <?= h($client['address'] ?? '') ?></div>
    <div class="links">
      <?php if ($lineUrl !== '#'): ?><a href="<?= h($lineUrl) ?>" target="_blank">💬 LINE 聯絡</a><?php endif; ?>
      <?php if ($fbUrl   !== '#'): ?><a href="<?= h($fbUrl)   ?>" target="_blank">📘 Facebook</a><?php endif; ?>
    </div>
  </div>
</div>

<!-- Header -->
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= siteUrl($slug) ?>" class="site-logo">
      <?php if ($client['logo_path']): ?>
        <img src="<?= BASE_URL . '/' . h($client['logo_path']) ?>" alt="<?= h($client['brand_name']) ?>">
      <?php else: ?>
        <div style="width:40px;height:40px;background:var(--c-primary);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0;"><?= $_logoIcon ?></div>
      <?php endif; ?>
      <div class="logo-text">
        <?= h($client['brand_name']) ?>
        <?php if ($client['industry']): ?><span class="sub"><?= h($client['industry']) ?></span><?php endif; ?>
      </div>
    </a>

    <nav class="site-nav">
      <a href="<?= siteUrl($slug) ?>"             class="<?= $pageKey==='home'?'active':'' ?>">首頁</a>
      <a href="<?= siteUrl($slug,'services') ?>"  class="<?= $pageKey==='services'?'active':'' ?>">服務項目</a>
      <a href="<?= siteUrl($slug,'cases') ?>"     class="<?= $pageKey==='cases'?'active':'' ?>"><?= $_casesLabel ?></a>
      <a href="<?= siteUrl($slug,'testimonials') ?>" class="<?= $pageKey==='testimonials'?'active':'' ?>">客戶好評</a>
      <?php if ($phone): ?>
        <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn-contact">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </nav>

    <button class="nav-toggle" onclick="toggleMobileMenu()" aria-label="選單">☰</button>
  </div>

  <nav class="mobile-menu" id="mobileMenu">
    <a href="<?= siteUrl($slug) ?>"            onclick="closeMobileMenu()">🏠 首頁</a>
    <a href="<?= siteUrl($slug,'services') ?>" onclick="closeMobileMenu()">🛠️ 服務項目</a>
    <a href="<?= siteUrl($slug,'cases') ?>"    onclick="closeMobileMenu()"><?= $_casesIcon ?> <?= $_casesLabel ?></a>
    <a href="<?= siteUrl($slug,'testimonials') ?>" onclick="closeMobileMenu()">⭐ 客戶好評</a>
    <?php if ($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="highlight">📞 <?= h($phone) ?></a>
    <?php endif; ?>
    <?php if ($lineUrl !== '#'): ?>
      <a href="<?= h($lineUrl) ?>" class="highlight" target="_blank">💬 LINE 諮詢</a>
    <?php endif; ?>
  </nav>
</header>

<!-- Floating LINE -->
<?php if ($lineUrl !== '#'): ?>
<a href="<?= h($lineUrl) ?>" class="float-line" target="_blank" title="LINE 聯絡我們">
  <svg viewBox="0 0 24 24"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
</a>
<?php endif; ?>
