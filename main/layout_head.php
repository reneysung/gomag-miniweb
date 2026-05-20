<?php
/**
 * main/layout_head.php
 * 主站（www.gomag.com.tw）共用 Header
 * 需在引入前定義：$pageTitle, $metaDesc（可選）
 */

// 從 platform_settings 撈預設值，個別頁面可覆蓋
$platformName    = getPlatformSetting('platform_name', '店家好口碑');
$defaultMetaTitle = getPlatformSetting('main_meta_title', $platformName . '｜全台在地店家平台');
$defaultMetaDesc  = getPlatformSetting('main_meta_desc', '匯集全台優質店家。');
$defaultOgImage   = getPlatformSetting('main_og_image', '');
$gaTrackingId     = getPlatformSetting('ga_tracking_id', '');
$gtmId            = getPlatformSetting('gtm_id', '');
$gscVerification  = getPlatformSetting('search_console_verification', '');

$pageTitle  = $pageTitle ?? $defaultMetaTitle;
$metaDesc   = $metaDesc  ?? $defaultMetaDesc;
$ogImage    = $ogImage   ?? ($defaultOgImage ?: BASE_URL . '/assets/images/main-og.jpg');
$canonical  = $canonical ?? BASE_URL . $_SERVER['REQUEST_URI'];
$metaKeywords = $metaKeywords ?? '';
$metaRobots = $metaRobots ?? '';  // 個別頁面設 'noindex,follow' 即可擋索引（placeholder / 薄頁閘門）
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<?php if ($metaRobots): ?>
<meta name="robots" content="<?= h($metaRobots) ?>">
<?php endif; ?>
<?php if ($metaKeywords): ?>
<meta name="keywords" content="<?= h($metaKeywords) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= h($canonical) ?>">
<meta property="og:title"       content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<meta property="og:type"        content="website">
<meta property="og:url"         content="<?= h($canonical) ?>">
<meta property="og:image"       content="<?= h($ogImage) ?>">
<meta name="twitter:card"       content="summary_large_image">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>/favicon.svg">
<?php if ($gscVerification): ?>
<meta name="google-site-verification" content="<?= h($gscVerification) ?>">
<?php endif; ?>
<?php if ($gaTrackingId): ?>
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($gaTrackingId) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= h($gaTrackingId) ?>');
</script>
<?php endif; ?>
<?php if ($gtmId): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= h($gtmId) ?>');</script>
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700;900&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<?php
/* 讓個別頁面可在 require 此檔前設 $extraCss = ['/path.css',...] 載入額外樣式
 * 自動加 ?v={mtime} cache-busting，避免 CDN 卡住舊版 CSS
 */
// Phase F：全站預設載 gomag.css（header/footer 都用 g-* 樣式）
$autoCss = [BASE_URL . '/assets/css/gomag.css'];
$mergedCss = array_merge($autoCss, (array)($extraCss ?? []));
$mergedCss = array_unique($mergedCss);
if (!empty($mergedCss)):
    foreach ($mergedCss as $cssUrl):
        // 嘗試解析本機路徑取 mtime 做 cache key
        $cssVer = '';
        // 把 BASE_URL prefix 移除，剩下的就是相對於專案 root 的路徑
        $relativePath = str_replace(BASE_URL, '', $cssUrl);
        $localPath = __DIR__ . '/..' . $relativePath;
        if (is_file($localPath)) $cssVer = '?v=' . filemtime($localPath);
        ?>
<link rel="stylesheet" href="<?= h($cssUrl . $cssVer) ?>">
<?php
    endforeach;
endif;
if (!empty($extraHead)): echo $extraHead; endif;
?>

<style>
/* ══ MAIN SITE — 主站樣式（獨立於客戶 mini-site）═════════ */
:root {
  --m-primary:    #0f172a;    /* 深藍灰 — 平台主色 */
  --m-accent:     #d4a853;    /* 金黃 — 強調色 */
  --m-bg:         #f8fafc;
  --m-bg-alt:     #ffffff;
  --m-text:       #0f172a;
  --m-text-muted: #64748b;
  --m-border:     #e2e8f0;
  --m-radius:     12px;
  --m-shadow-sm:  0 1px 3px rgba(15,23,42,.06);
  --m-shadow:     0 4px 12px rgba(15,23,42,.08);
  --m-shadow-lg:  0 12px 32px rgba(15,23,42,.12);
}

*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; font-size: 16px; }
body {
  font-family: 'Noto Sans TC', -apple-system, BlinkMacSystemFont, sans-serif;
  color: var(--m-text);
  background: var(--m-bg);
  line-height: 1.7;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
a { color: inherit; text-decoration: none; }
img { max-width: 100%; display: block; height: auto; }

.m-container { max-width: 1180px; margin: 0 auto; padding: 0 20px; }

/* ── 主站 Header ───────────────────────────────────── */
.m-header {
  background: var(--m-bg-alt);
  border-bottom: 1px solid var(--m-border);
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: var(--m-shadow-sm);
}
.m-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  max-width: 1180px;
  margin: 0 auto;
}
.m-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.25rem;
  font-weight: 900;
  letter-spacing: .02em;
  color: var(--m-primary);
}
.m-logo .icon {
  width: 36px; height: 36px;
  background: var(--m-primary);
  color: var(--m-accent);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
}
.m-nav {
  display: flex;
  align-items: center;
  gap: 8px;
}
.m-nav a {
  padding: 8px 14px;
  font-weight: 500;
  font-size: .9rem;
  color: var(--m-text-muted);
  border-radius: 6px;
  transition: all .15s;
}
.m-nav a:hover, .m-nav a.active {
  color: var(--m-primary);
  background: var(--m-bg);
}
.m-search-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: var(--m-bg);
  border: 1px solid var(--m-border);
  border-radius: 999px;
  font-size: .85rem;
  color: var(--m-text-muted);
  cursor: pointer;
}
.m-search-btn:hover { background: var(--m-border); }

/* ── 通用元件 ──────────────────────────────────────── */
.m-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 22px;
  border-radius: 8px;
  font-size: .9rem;
  font-weight: 700;
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all .2s;
  white-space: nowrap;
}
.m-btn-primary { background: var(--m-primary); color: #fff; }
.m-btn-primary:hover { background: #1e293b; transform: translateY(-1px); }
.m-btn-accent { background: var(--m-accent); color: #fff; }
.m-btn-accent:hover { filter: brightness(1.05); }
.m-btn-outline { background: transparent; color: var(--m-primary); border-color: var(--m-primary); }
.m-btn-outline:hover { background: var(--m-primary); color: #fff; }

.m-section { padding: 60px 0; }
.m-section-title {
  font-size: 1.8rem;
  font-weight: 900;
  text-align: center;
  margin-bottom: 8px;
  color: var(--m-primary);
}
.m-section-sub {
  text-align: center;
  color: var(--m-text-muted);
  font-size: .95rem;
  margin-bottom: 40px;
}

/* ── 店家卡片 ──────────────────────────────────────── */
.m-store-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}
.m-store-card {
  background: var(--m-bg-alt);
  border: 1px solid var(--m-border);
  border-radius: var(--m-radius);
  overflow: hidden;
  transition: all .2s;
  display: flex;
  flex-direction: column;
}
.m-store-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--m-shadow-lg);
  border-color: var(--m-accent);
}
.m-store-card .cover {
  aspect-ratio: 16/9;
  background: linear-gradient(135deg, #cbd5e1, #e2e8f0);
  background-size: cover;
  background-position: center;
  position: relative;
}
.m-store-card .cat-tag {
  position: absolute;
  top: 12px;
  left: 12px;
  background: rgba(15,23,42,.85);
  color: #fff;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
}
.m-store-card .body { padding: 16px 18px; flex: 1; display: flex; flex-direction: column; }
.m-store-card h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--m-primary);
  margin-bottom: 4px;
}
.m-store-card .tagline {
  font-size: .85rem;
  color: var(--m-text-muted);
  margin-bottom: 12px;
  flex: 1;
}
.m-store-card .meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: .75rem;
  color: var(--m-text-muted);
  border-top: 1px solid var(--m-border);
  padding-top: 10px;
}
.m-store-card .badges {
  display: flex;
  gap: 6px;
  margin-top: 8px;
}
.m-store-card .badge {
  font-size: .7rem;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 600;
}
.m-store-card .badge-mini {
  background: #ecfdf5;
  color: #047857;
}
.m-store-card .badge-ext {
  background: #eff6ff;
  color: #1e40af;
}

/* ── 分類網格 ──────────────────────────────────────── */
.m-cat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 14px;
}
.m-cat-card {
  background: var(--m-bg-alt);
  border: 1.5px solid var(--m-border);
  border-radius: var(--m-radius);
  padding: 22px 12px;
  text-align: center;
  transition: all .2s;
}
.m-cat-card:hover {
  border-color: var(--m-accent);
  transform: translateY(-2px);
  box-shadow: var(--m-shadow);
}
.m-cat-card .icon { font-size: 2rem; margin-bottom: 8px; }
.m-cat-card .name { font-weight: 700; font-size: .95rem; color: var(--m-primary); }
.m-cat-card .count { font-size: .75rem; color: var(--m-text-muted); margin-top: 2px; }

/* ── Hero 區 ──────────────────────────────────────── */
.m-hero {
  background: linear-gradient(135deg, var(--m-primary), #1e293b);
  color: #fff;
  padding: 80px 0 70px;
  position: relative;
  overflow: hidden;
}
.m-hero::before {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 50%;
  height: 100%;
  background: radial-gradient(circle at top right, rgba(212,168,83,.2), transparent 70%);
}
.m-hero-inner { position: relative; z-index: 1; text-align: center; }
.m-hero h1 {
  font-size: 2.6rem;
  font-weight: 900;
  margin-bottom: 14px;
  line-height: 1.3;
}
.m-hero h1 .accent { color: var(--m-accent); }
.m-hero p {
  font-size: 1.1rem;
  opacity: .85;
  margin-bottom: 32px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}
.m-hero-search {
  max-width: 540px;
  margin: 0 auto;
  position: relative;
}
.m-hero-search input {
  width: 100%;
  padding: 16px 22px;
  padding-right: 130px;
  font-size: 1rem;
  border: none;
  border-radius: 999px;
  outline: none;
  box-shadow: var(--m-shadow-lg);
  font-family: inherit;
}
.m-hero-search button {
  position: absolute;
  right: 4px; top: 4px; bottom: 4px;
  padding: 0 26px;
  background: var(--m-accent);
  color: #fff;
  border: none;
  border-radius: 999px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
}
.m-hero-search button:hover { filter: brightness(1.05); }

/* ── Breadcrumb ──────────────────────────────────── */
.m-breadcrumb {
  font-size: .85rem;
  color: var(--m-text-muted);
  padding: 16px 0;
}
.m-breadcrumb a { color: var(--m-text-muted); }
.m-breadcrumb a:hover { color: var(--m-primary); }
.m-breadcrumb .sep { margin: 0 8px; opacity: .5; }

/* ── 響應式 ──────────────────────────────────────── */
@media (max-width: 768px) {
  .m-hero { padding: 50px 0 40px; }
  .m-hero h1 { font-size: 1.8rem; }
  .m-hero p { font-size: .95rem; }
  .m-section { padding: 40px 0; }
  .m-section-title { font-size: 1.4rem; }
  .m-nav a:not(.m-search-btn) { display: none; }
  .m-store-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
}
</style>
</head>
<body>

<header class="g-site-header">
  <div class="g-site-header-inner">
    <a class="g-site-logo" href="<?= BASE_URL ?>/">
      <span class="g-site-logo-mark">店</span>
      <span><?= h($platformName) ?></span>
    </a>
    <nav class="g-site-nav">
      <a href="<?= BASE_URL ?>/" class="<?= $currentPage === 'index' ? 'is-active' : '' ?>">首頁</a>
      <a href="<?= BASE_URL ?>/category.php" class="<?= $currentPage === 'category' ? 'is-active' : '' ?>">分類</a>
      <a href="<?= BASE_URL ?>/city.php" class="<?= $currentPage === 'city' ? 'is-active' : '' ?>">縣市</a>
      <a href="<?= BASE_URL ?>/search.php" class="g-site-nav-search">
        🔍 <span>搜尋店家</span>
      </a>
    </nav>
  </div>
</header>

<main style="flex: 1;">
