<?php
// index.php ─ 主站 www.gomag.com.tw 首頁
// 2026-11 改版：b2「活潑生活」定案版落地（任你發現好口碑店家／看主題找需求→送進主題頁）
// 自含 head/header/footer（內頁仍走 main/layout_*），資料全部真實：分類/縣市家數、攻略文、交叉頁
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/front_functions.php';

$db = getDB();

// ─── 分類（含家數）───────────────────────────────
$categories = $db->query("
    SELECT c.id, c.name, c.slug, c.icon, COUNT(cl.id) AS n
    FROM categories c
    LEFT JOIN clients cl ON cl.category_id = c.id AND cl.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY n DESC, c.sort_order
")->fetchAll();
$catBySlug = [];
foreach ($categories as $c) { $catBySlug[$c['slug']] = $c; }

// ─── 攻略：焦點一篇 + 最新 6 篇（排行榜）──────────
$featGuide = null;
try {
    $st = $db->prepare("SELECT slug, title FROM guides WHERE status='published' AND slug=? LIMIT 1");
    $st->execute(['taiwan-old-house-renovation-guide-2026']);
    $featGuide = $st->fetch() ?: null;
    $rankGuides = $db->query("
        SELECT slug, title FROM guides
        WHERE status='published' AND slug != 'taiwan-old-house-renovation-guide-2026'
        ORDER BY published_at DESC LIMIT 6
    ")->fetchAll();
} catch (PDOException $e) { $rankGuides = []; }

// ─── 縣市家數（city_slug 為準，≥3 家才顯示數字）────
$cityNames = getCityMap(); // slug => 中文名
$cityCounts = $db->query("
    SELECT city_slug, COUNT(*) AS n FROM clients
    WHERE is_active = 1 AND city_slug != ''
    GROUP BY city_slug HAVING n >= 3 ORDER BY n DESC
")->fetchAll();

$totalClients = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_active=1")->fetchColumn();

// ─── 影音直擊（後台「平台設定」貼連結；每行：標題|網址|縮圖網址，空=隱藏整區）──
$homeVideos = [];
foreach (preg_split('/\r?\n/', (string)getPlatformSetting('home_videos', '')) as $ln) {
    $p = array_map('trim', explode('|', $ln));
    if (count($p) >= 2 && $p[0] !== '' && $p[1] !== '') {
        // 縮圖沒填就試著從 YouTube 網址推
        $thumb = $p[2] ?? '';
        if ($thumb === '' && preg_match('~(?:youtu\.be/|v=|shorts/)([A-Za-z0-9_-]{6,})~', $p[1], $m)) {
            $thumb = 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
        $homeVideos[] = ['t' => $p[0], 'u' => $p[1], 'img' => $thumb];
    }
}
$homeVideos = array_slice($homeVideos, 0, 4);

// ─── SEO（沿用平台設定；schema 與 layout_head 同款）──
$pageTitle = getPlatformSetting('main_meta_title', '店家好口碑｜先搞懂，再挑店');
$metaDesc  = getPlatformSetting('main_meta_desc', "挑店家前，先把該懂的事一次弄清楚——然後從在地真實口碑裡，挑你最安心的那一家。全台 {$totalClients}+ 家在地店家。");
$ogImage   = getPlatformSetting('main_og_image', '') ?: BASE_URL . '/assets/images/main-og.jpg';
$gaTrackingId    = getPlatformSetting('ga_tracking_id', '');
$gtmId           = getPlatformSetting('gtm_id', '');
$gscVerification = getPlatformSetting('search_console_verification', '');
$mainBase  = (IS_LOCAL || IS_STAGING) ? BASE_URL : 'https://www.gomag.com.tw';
$canonical = $mainBase . '/';
$V = @filemtime(__FILE__) ?: 1; // 資產 cache-busting
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<link rel="canonical" href="<?= h($canonical) ?>">
<link rel="alternate" hreflang="zh-Hant" href="<?= h($canonical) ?>">
<link rel="alternate" hreflang="x-default" href="<?= h($canonical) ?>">
<link rel="sitemap" type="application/xml" href="<?= h($mainBase) ?>/sitemap.xml" title="Sitemap">
<meta property="og:site_name" content="店家好口碑">
<meta property="og:title" content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta property="og:image" content="<?= h($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>/favicon.svg">
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'Organization',
    '@id' => $mainBase . '/#organization', 'name' => '店家好口碑', 'alternateName' => 'gomag',
    'url' => $mainBase . '/', 'logo' => $mainBase . '/favicon.svg', 'slogan' => '任你發現好口碑店家',
    'description' => '全台在地店家平台 — 看主題找需求，從在地真實口碑挑你最安心的店家。',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebSite',
    '@id' => $mainBase . '/#website', 'url' => $mainBase . '/', 'name' => '店家好口碑',
    'description' => '任你發現好口碑店家 — 看主題找需求，先搞懂再決定。',
    'publisher' => ['@id' => $mainBase . '/#organization'],
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $mainBase . '/search?q={search_term_string}'],
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<?php if ($gscVerification): ?>
<meta name="google-site-verification" content="<?= h($gscVerification) ?>">
<?php endif; ?>
<?php if ($gaTrackingId): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($gaTrackingId) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= h($gaTrackingId) ?>');</script>
<?php endif; ?>
<?php if ($gtmId): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= h($gtmId) ?>');</script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="preload" href="<?= BASE_URL ?>/assets/fonts/gensenround-sub.woff2?v=<?= $V ?>" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:'GenSenRound';src:url('<?= BASE_URL ?>/assets/fonts/gensenround-sub.woff2?v=<?= $V ?>') format('woff2');font-display:swap;font-weight:700}
:root{
  --paper:#FFFFFF;--ink:#211A12;--ink2:#574C3F;--mut:#8C8072;--faint:#B4A999;
  --line:#ECE6DC;--line-soft:#F4EFE7;--wash:#FAF8F4;--wash-2:#F2EEE7;
  --orange:#EE5A2E;--orange-d:#C4441C;--orange-wash:#FCEBE3;
  --teal:#147E6E;--teal-wash:#E4F1EE;--butter:#F7C948;--pink:#F7D3C4;
  --sans:'Noto Sans TC','PingFang TC',sans-serif;
  --disp:'GenSenRound','Noto Sans TC',sans-serif;
  --num:'Manrope',sans-serif;
  --wrap:1120px;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--sans);background:var(--paper);color:var(--ink);line-height:1.6;-webkit-font-smoothing:antialiased;font-synthesis:none}
img{display:block;max-width:100%}
a{color:inherit;text-decoration:none;-webkit-tap-highlight-color:transparent}
h1,h2,h3,.disp{font-family:var(--disp);font-weight:700;letter-spacing:.04em}
:focus-visible{outline:3px solid var(--orange);outline-offset:3px;border-radius:6px}
.eyebrow{font:800 .72rem/1 var(--num);letter-spacing:.16em;text-transform:uppercase;color:var(--orange);display:inline-flex;align-items:center;gap:8px}
.eyebrow::before{content:"";width:22px;height:2px;background:var(--orange);border-radius:2px}
.wrap{max-width:var(--wrap);margin:0 auto;padding:0 20px}

/* header */
.hd{position:sticky;top:0;z-index:60;background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.hd .in{max-width:var(--wrap);margin:0 auto;padding:11px 20px;display:flex;align-items:center;gap:14px;position:relative}
.lg{display:flex;align-items:center;gap:8px;font-family:var(--disp);font-weight:700;font-size:19px;flex-shrink:0}
.lg .m{width:31px;height:31px;border-radius:10px;background:var(--orange);color:#fff;display:grid;place-items:center;font-size:16px;transform:rotate(-5deg)}
.hd .sp{flex:1}
.hd .hnav{display:none;gap:26px;position:absolute;left:50%;top:50%;transform:translate(-50%,-50%)}
.hd .hnav a{font-size:14.5px;font-weight:700;color:var(--ink2)}
.hd .hnav a:hover{color:var(--orange)}
@media(min-width:820px){.hd .hnav{display:flex}}
.hd .navcta{display:inline-flex;align-items:center;height:40px;padding:0 16px;font-weight:700;font-size:13.5px;color:var(--ink);background:#fff;border:1.5px solid var(--ink);border-radius:11px;transition:.15s;white-space:nowrap}
.hd .navcta:hover{background:var(--ink);color:#fff}
.hd .burg{width:40px;height:40px;border:1.5px solid var(--ink);border-radius:11px;display:grid;place-items:center;font-size:19px;cursor:pointer;background:#fff}
.hd .burg:hover{background:var(--butter)}

/* hero */
.hero{padding:48px 0 32px}
.hero .in{max-width:var(--wrap);margin:0 auto;padding:0 20px;display:grid;gap:32px}
.hero h1{font-size:clamp(2.35rem,6.6vw,4rem);line-height:1.28;letter-spacing:.05em}
.hero .circ{position:relative;display:inline-block;color:var(--ink);white-space:nowrap}
.hero .circ svg{position:absolute;left:50%;top:52%;width:138%;height:150%;transform:translate(-50%,-50%);overflow:visible;pointer-events:none}
.hero .circ path{fill:none;stroke:var(--orange);stroke-width:5;stroke-linecap:round;stroke-dasharray:520;stroke-dashoffset:520;animation:draw 1.1s .35s cubic-bezier(.6,0,.2,1) forwards}
@keyframes draw{to{stroke-dashoffset:0}}
.hero p.sub{font-size:clamp(1rem,2.4vw,1.16rem);color:var(--ink2);max-width:30em;line-height:1.85}
.search{display:flex;align-items:center;gap:10px;background:#fff;border:2.5px solid var(--ink);border-radius:16px;padding:6px 6px 6px 16px;max-width:540px;box-shadow:5px 6px 0 var(--ink)}
.search .ic{font-size:18px;color:var(--mut)}
.search input{flex:1;border:0;font:500 15px var(--sans);color:var(--ink);background:transparent;padding:11px 4px;min-width:0}
.search input::placeholder{color:var(--mut)}
.search button{border:0;background:var(--orange);color:#fff;font:800 14px var(--sans);padding:12px 20px;border-radius:11px;cursor:pointer;flex-shrink:0}
.search button:hover{background:var(--orange-d)}
.exlist{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;align-items:center}
.exlist .lb{font-size:12.5px;color:var(--mut)}
.ex{font-size:13px;font-weight:700;border:1.5px solid var(--line);background:#fff;border-radius:100px;padding:6px 13px;transition:.15s}
.ex:hover{border-color:var(--ink);background:var(--butter)}
.hero .collage{display:none}
@media(min-width:860px){
  .hero .in{grid-template-columns:1.05fr .95fr;align-items:center;gap:48px}
  .hero .collage{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:150px 150px;gap:12px}
  .hero .collage figure{border-radius:16px;overflow:hidden;position:relative;box-shadow:0 10px 24px rgba(80,50,20,.14)}
  .hero .collage img{width:100%;height:100%;object-fit:cover}
  .hero .collage .tall{grid-row:span 2;transform:rotate(-1.5deg)}
  .hero .collage .wide{transform:rotate(1.5deg)}
  .hero .collage .note{position:absolute;left:10px;bottom:10px;background:#fff;font:800 11px var(--num);color:var(--ink);padding:4px 10px;border-radius:100px}
}

/* category strip */
.strip{border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin-top:24px}
.strip .in{max-width:var(--wrap);margin:0 auto;padding:12px 20px;display:flex;gap:8px;overflow-x:auto;scrollbar-width:none}
.strip .in::-webkit-scrollbar{display:none}
.tab{flex:0 0 auto;font-size:13.5px;font-weight:700;padding:7px 15px;border-radius:100px;border:1.5px solid var(--line);background:#fff;color:var(--ink2)}
.tab.on{background:var(--ink);color:#fff;border-color:var(--ink)}
.tab:hover{border-color:var(--ink)}

/* lead: feature + rank */
.lead{padding:56px 0 8px}
.lead .in{max-width:var(--wrap);margin:0 auto;padding:0 20px}
.lead .hd2{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.seeall{display:inline-flex;align-items:center;gap:7px;font:700 13px var(--sans);color:var(--orange-d);white-space:nowrap}
.seeall svg{width:15px;height:15px}
.seeall:hover{gap:10px}
.lead h2{font-size:clamp(1.55rem,3.6vw,2rem);line-height:1.15}
.lead .grid{display:grid;gap:24px}
@media(min-width:860px){.lead .grid{grid-template-columns:1.15fr .85fr;gap:32px}}
.feat{position:relative;border-radius:16px;overflow:hidden;display:block;box-shadow:0 14px 30px rgba(80,50,20,.16)}
.feat img{width:100%;height:100%;object-fit:cover;aspect-ratio:16/11;transition:transform .5s}
.feat:hover img{transform:scale(1.04)}
.feat .grad{position:absolute;inset:0;background:linear-gradient(180deg,rgba(20,12,5,0) 34%,rgba(20,12,5,.86))}
.feat .tag{position:absolute;top:14px;left:14px;background:var(--butter);color:var(--ink);font:900 12px var(--sans);padding:5px 13px;border-radius:100px}
.feat .cap{position:absolute;left:18px;right:18px;bottom:16px;color:#fff}
.feat .cap h3{font-size:clamp(1.3rem,3vw,1.7rem);line-height:1.28;text-shadow:0 2px 10px rgba(0,0,0,.5)}
.feat .cap p{font-size:13.5px;opacity:.92;margin-top:8px;line-height:1.6;max-width:26em}
.feat .cap .learn{display:inline-flex;gap:6px;font:800 12.5px var(--num);letter-spacing:.02em;margin-top:11px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.4);padding:5px 12px;border-radius:100px;backdrop-filter:blur(4px)}
.rank{border:1.5px solid var(--line);border-radius:16px;padding:8px 4px}
.rank .rh{display:flex;align-items:center;gap:8px;padding:12px 16px 8px;font:800 .72rem var(--num);letter-spacing:.12em;text-transform:uppercase;color:var(--teal)}
.rank .rh .live{width:7px;height:7px;border-radius:50%;background:var(--teal);box-shadow:0 0 0 0 rgba(14,140,122,.5);animation:pulse 2s infinite}
@keyframes pulse{70%{box-shadow:0 0 0 7px rgba(14,140,122,0)}100%{box-shadow:0 0 0 0 rgba(14,140,122,0)}}
.rk{display:flex;align-items:center;gap:16px;padding:12px 16px;border-top:1px solid var(--line)}
.rk:first-of-type{border-top:0}
.rk .no{font:800 15px var(--num);color:var(--mut);width:24px;flex-shrink:0}
.rk:nth-of-type(1) .no,.rk:nth-of-type(2) .no,.rk:nth-of-type(3) .no{color:var(--orange)}
.rk .tx{font-size:15px;font-weight:700;line-height:1.35}
.rk .go{margin-left:auto;color:var(--mut);font-size:15px;flex-shrink:0;transition:transform .2s}
.rk:hover{background:var(--wash)}
.rk:hover .go{transform:translateX(4px);color:var(--orange)}
.rk:hover .tx{color:var(--orange)}

/* situation mosaic */
.sit{padding:56px 0 8px}
.sit .in{max-width:var(--wrap);margin:0 auto;padding:0 20px}
.sit h2{font-size:clamp(1.55rem,3.6vw,2rem);margin:8px 0 24px;line-height:1.15;padding-bottom:16px;border-bottom:1px solid var(--line)}
.mos{display:grid;grid-template-columns:repeat(2,1fr);grid-auto-rows:118px;gap:12px}
@media(min-width:720px){.mos{grid-template-columns:repeat(4,1fr);grid-auto-rows:130px}}
.mt{position:relative;border-radius:16px;overflow:hidden;display:flex;align-items:flex-end;padding:16px;box-shadow:0 8px 18px rgba(80,50,20,.1);transition:transform .2s,box-shadow .2s}
.mt:hover{transform:translateY(-4px);box-shadow:0 16px 28px rgba(80,50,20,.2)}
.mt img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.mt .gd{position:absolute;inset:0;background:linear-gradient(180deg,rgba(20,12,5,0) 38%,rgba(20,12,5,.8))}
.mt .lab{position:relative;color:#fff;font-family:var(--disp);font-weight:700;font-size:19px;text-shadow:0 1px 8px rgba(0,0,0,.5)}
.mt .lab small{display:block;font-family:var(--sans);font-weight:700;font-size:11.5px;opacity:.9;margin-top:2px;text-shadow:none}
.mt.wide{grid-column:span 2}
.mt.solid{align-items:flex-start;flex-direction:column;justify-content:space-between}
.mt.o{background:var(--orange)}.mt.tl{background:var(--teal)}.mt.rs{background:#E8618B}
.mt.solid .big{position:relative;font-family:var(--disp);font-weight:700;font-size:34px;color:rgba(255,255,255,.9)}

/* video band */
.vid{background:var(--ink);color:#fff;margin-top:48px;padding:48px 0 48px}
.vid .in{max-width:var(--wrap);margin:0 auto;padding:0 20px}
.vid .eyebrow{color:var(--butter)}
.vid .eyebrow::before{background:var(--butter)}
.vid h2{color:#fff;font-size:clamp(1.55rem,3.6vw,2rem);margin:8px 0 4px}
.vid .sub{color:rgba(255,255,255,.62);font-size:14px;margin-bottom:24px}
.vrow{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
@media(min-width:720px){.vrow{grid-template-columns:repeat(4,1fr)}}
.vc{position:relative;border-radius:16px;overflow:hidden;aspect-ratio:9/15;display:flex;align-items:flex-end;padding:12px}
.vc img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .5s}
.vc:hover img{transform:scale(1.06)}
.vc .gd{position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.1) 30%,rgba(0,0,0,.75))}
.vc .pl{position:absolute;top:12px;left:12px;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.92);color:var(--ink);display:grid;place-items:center;font-size:13px}
.vc .t{position:relative;color:#fff;font-weight:700;font-size:14px;line-height:1.35;text-shadow:0 1px 6px rgba(0,0,0,.6)}

/* browse chips */
.browse{padding:48px 0 8px}
.browse .in{max-width:var(--wrap);margin:0 auto;padding:0 20px}
.browse h2{font-size:clamp(1.4rem,3.2vw,1.75rem);margin:6px 0 20px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.browse+.browse{padding-top:8px}
.cchips{display:flex;flex-wrap:wrap;gap:8px}
.cc{font-size:14px;font-weight:700;border:1.5px solid var(--line);background:#fff;border-radius:100px;padding:9px 16px;transition:.15s}
.cc:hover{border-color:var(--ink);background:var(--butter);transform:translateY(-2px)}
.cc .n{font:700 11.5px var(--num);color:var(--mut);margin-left:6px}
.cc:hover .n{color:var(--ink)}

/* belief */
.belief{padding:64px 0}
.belief .in{max-width:820px;margin:0 auto;padding:0 20px;text-align:center}
.belief .eyebrow{justify-content:center}
.belief blockquote{font-family:var(--disp);font-weight:700;font-size:clamp(1.5rem,4vw,2.2rem);line-height:1.5;margin-top:18px}
.belief blockquote .u{background:linear-gradient(180deg,transparent 62%,var(--butter) 62%)}

/* b2b */
.b2b{max-width:calc(var(--wrap) - 40px);margin:0 auto 48px;background:var(--wash);border:1.5px solid var(--line);border-radius:16px;padding:32px 24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.b2b .tx h3{font-size:1.3rem;margin-bottom:6px}
.b2b .tx p{color:var(--ink2);font-size:14px;max-width:34em;line-height:1.7}
.b2b .line{margin-left:auto;background:#06C755;color:#fff;font:800 15px var(--sans);padding:13px 22px;border-radius:13px;display:inline-flex;align-items:center;gap:8px;flex-shrink:0}
.b2b .line:hover{filter:brightness(.95)}

/* footer */
.ft{background:var(--ink);color:#fff;padding:48px 0 24px}
.ft .in{max-width:var(--wrap);margin:0 auto;padding:0 20px;display:grid;grid-template-columns:1fr 1fr;gap:28px 20px}
@media(min-width:760px){.ft .in{grid-template-columns:1.6fr 1fr 1fr 1fr 1fr}}
.ft .brand{grid-column:1/-1}
@media(min-width:760px){.ft .brand{grid-column:auto}}
.ft .brand .lg2{display:flex;align-items:center;gap:8px;font-family:var(--disp);font-weight:700;font-size:19px;margin-bottom:10px}
.ft .brand .lg2 .m{width:29px;height:29px;border-radius:9px;background:var(--orange);display:grid;place-items:center}
.ft .brand p{font-size:12.5px;color:rgba(255,255,255,.6);line-height:1.75;max-width:20em}
.ft h5{font:800 11px var(--num);letter-spacing:.09em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:12px}
.ft .in a{display:block;font-size:13px;color:rgba(255,255,255,.85);padding:4px 0}
.ft .in a:hover{color:var(--butter)}
.ftbot{max-width:var(--wrap);margin:26px auto 0;padding:16px 20px 0;border-top:1px solid rgba(255,255,255,.15);display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;font-size:12px;color:rgba(255,255,255,.5)}

/* mega menu */
.mega{position:fixed;inset:0;z-index:90;background:rgba(255,255,255,.82);backdrop-filter:blur(24px) saturate(1.15);-webkit-backdrop-filter:blur(24px) saturate(1.15);transform:translateY(-100%);transition:transform .34s cubic-bezier(.5,0,.2,1);overflow-y:auto}
.mega.open{transform:none}
.mega .mh{position:sticky;top:0;display:flex;align-items:center;justify-content:space-between;padding:15px 20px;background:var(--orange);color:#fff}
.mega .mh b{font-family:var(--disp);font-weight:700;font-size:18px}
.mega .mh .x{font-size:26px;cursor:pointer;line-height:1;background:none;border:0;color:#fff}
.mega .mb{max-width:1000px;margin:0 auto;padding:24px 20px 64px}
.mega .grp{margin-bottom:32px}
.mega .grp h4{font:800 12px var(--num);letter-spacing:.1em;text-transform:uppercase;color:var(--mut);margin-bottom:15px;display:flex;align-items:center;gap:10px}
.mega .grp h4::after{content:"";flex:1;height:0;border-top:2px dotted var(--line)}
.mega .links{display:flex;flex-wrap:wrap;gap:7px}
.mega .links a{font-size:12.5px;font-weight:700;background:rgba(255,255,255,.55);border:1.5px solid var(--ink);border-radius:100px;padding:5px 12px}
.mega .links a:hover{background:var(--butter)}

@media(prefers-reduced-motion:reduce){
  *{animation:none!important;transition:none!important;scroll-behavior:auto!important}
  .hero .circ path{stroke-dashoffset:0}
}
</style>
</head>
<body>

<header class="hd"><div class="in">
  <a class="lg" href="<?= BASE_URL ?>/"><span class="m">店</span>店家好口碑</a>
  <nav class="hnav">
    <a href="<?= BASE_URL ?>/category.php">探索主題</a>
    <a href="<?= BASE_URL ?>/city.php">依縣市</a>
    <a href="<?= BASE_URL ?>/guide">在地攻略</a>
  </nav>
  <span class="sp"></span>
  <a class="navcta" href="#b2b">我是店家</a>
  <button class="burg" aria-label="開啟選單" onclick="document.getElementById('mega').classList.add('open')">☰</button>
</div></header>

<main>
<section class="hero" aria-labelledby="h1"><div class="in">
  <div>
    <span class="eyebrow">任你發現好口碑店家</span>
    <h1 id="h1">先搞懂，<br>再<span class="circ">挑店<svg viewBox="0 0 160 70" aria-hidden="true"><path d="M80 6 C132 4 156 20 154 36 C152 55 108 66 74 66 C30 66 6 52 8 34 C10 16 44 7 96 8"/></svg></span>。</h1>
    <p class="sub">從真實口碑出發，把每個決定該懂的眉角一次講清楚——然後從在地好店裡，挑你最安心的那一家。</p>
    <form class="search" role="search" method="GET" action="<?= BASE_URL ?>/search.php">
      <span class="ic" aria-hidden="true">⌕</span>
      <input type="search" name="q" aria-label="搜尋主題或店家" placeholder="想搞懂什麼？例如「木地板」「系統櫃」">
      <button type="submit">搜尋</button>
    </form>
    <div class="exlist">
      <span class="lb">熱門：</span>
      <a class="ex" href="<?= BASE_URL ?>/guide/tainan-flooring-recommend">台南木地板</a>
      <a class="ex" href="<?= BASE_URL ?>/guide/system-cabinet-board-guide">系統櫃怎麼挑</a>
      <a class="ex" href="<?= BASE_URL ?>/guide/tainan-car-wrap-recommend">汽車包膜</a>
      <a class="ex" href="<?= BASE_URL ?>/guide/tainan-hotpot-recommend">台南火鍋</a>
      <a class="ex" href="<?= BASE_URL ?>/guide/taiwan-old-house-renovation-guide-2026">老屋翻新</a>
    </div>
  </div>
  <div class="collage" aria-hidden="true">
    <figure class="tall"><img src="<?= BASE_URL ?>/assets/images/home/han_p2.jpg" alt="" fetchpriority="high"><span class="note">在地真實案例</span></figure>
    <figure class="wide"><img src="<?= BASE_URL ?>/assets/images/home/han_p9.jpg" alt=""></figure>
    <figure class="wide"><img src="<?= BASE_URL ?>/assets/images/home/han_p5.jpg" alt=""></figure>
  </div>
</div></section>

<nav class="strip" aria-label="分類快選"><div class="in">
  <a class="tab on" href="<?= BASE_URL ?>/category.php">全部</a>
  <?php foreach ($categories as $c): if ((int)$c['n'] === 0) continue; ?>
  <a class="tab" href="<?= BASE_URL ?>/category/<?= h($c['slug']) ?>"><?= h($c['name']) ?></a>
  <?php endforeach; ?>
</div></nav>

<section class="lead" aria-labelledby="lead-h"><div class="in">
  <div class="hd2">
    <div><span class="eyebrow">熱門主題</span><h2 id="lead-h">大家最近在弄懂的事</h2></div>
    <a class="seeall" href="<?= BASE_URL ?>/guide">看所有攻略 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
  </div>
  <div class="grid">
    <?php if ($featGuide): ?>
    <a class="feat" href="<?= BASE_URL ?>/guide/<?= h($featGuide['slug']) ?>">
      <img src="<?= BASE_URL ?>/assets/images/home/han_p11.jpg" alt="老屋翻新後的室內空間">
      <div class="grad"></div>
      <span class="tag">老屋翻新</span>
      <div class="cap">
        <h3>老屋翻新怎麼開始？流程、費用到補助一次看懂</h3>
        <p>從評估、拆除到完工的順序一次列清楚，每一步該找哪種店、行情怎麼抓，先搞懂再動工。</p>
        <span class="learn">看完你會知道 →</span>
      </div>
    </a>
    <?php endif; ?>
    <div class="rank">
      <div class="rh"><span class="live"></span>熱門攻略</div>
      <?php foreach ($rankGuides as $i => $g): ?>
      <a class="rk" href="<?= BASE_URL ?>/guide/<?= h($g['slug']) ?>">
        <span class="no"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
        <span class="tx"><?= h(mb_strimwidth($g['title'], 0, 52, '…', 'UTF-8')) ?></span>
        <span class="go">→</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div></section>

<section class="sit" aria-labelledby="sit-h"><div class="in">
  <span class="eyebrow">依你的處境</span>
  <h2 id="sit-h">人生正走到哪一步？</h2>
  <div class="mos">
    <a class="mt wide" href="<?= BASE_URL ?>/category/home-service"><img src="<?= BASE_URL ?>/assets/images/home/han_p13.jpg" alt="剛交屋的居家空間" loading="lazy"><div class="gd"></div><span class="lab">剛交屋<small>細清・驗屋・木地板</small></span></a>
    <a class="mt" href="<?= BASE_URL ?>/category/auto-service"><img src="<?= BASE_URL ?>/uploads/categories/cat_auto-service.jpg" alt="汽車服務" loading="lazy"><div class="gd"></div><span class="lab">想換車<small>包膜・鍍膜</small></span></a>
    <a class="mt solid o" href="<?= BASE_URL ?>/city/tainan/food/banquet"><span class="big">♥</span><span class="lab">要結婚<small>婚宴餐廳</small></span></a>
    <a class="mt solid rs" href="<?= BASE_URL ?>/category/beauty"><span class="big">✿</span><span class="lab">想變美<small>美髮・美甲・SPA</small></span></a>
    <a class="mt" href="<?= BASE_URL ?>/category/food"><img src="<?= BASE_URL ?>/uploads/categories/cat_food.jpg" alt="餐飲美食" loading="lazy"><div class="gd"></div><span class="lab">找吃的<small>火鍋・燒肉・日式</small></span></a>
    <a class="mt solid tl" href="<?= BASE_URL ?>/category/health"><span class="big">＋</span><span class="lab">顧健康<small>中醫・皮膚科</small></span></a>
  </div>
</div></section>

<?php if (!empty($homeVideos)): ?>
<section class="vid" aria-labelledby="vid-h"><div class="in">
  <span class="eyebrow">影音直擊</span>
  <h2 id="vid-h">影片帶你先看現場</h2>
  <p class="sub">施工、實吃、開箱——現場實拍，好壞自己看。</p>
  <div class="vrow">
    <?php foreach ($homeVideos as $v): ?>
    <a class="vc" href="<?= h($v['u']) ?>" target="_blank" rel="noopener">
      <?php if ($v['img']): ?><img src="<?= h($v['img']) ?>" alt="" loading="lazy"><?php endif; ?>
      <div class="gd"></div><span class="pl">▶</span><span class="t"><?= h($v['t']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>

<section class="browse" aria-labelledby="cat-h"><div class="in">
  <span class="eyebrow">依分類逛</span>
  <h2 id="cat-h">你想找哪一類？</h2>
  <div class="cchips">
    <?php foreach ($categories as $c): if ((int)$c['n'] === 0) continue; ?>
    <a class="cc" href="<?= BASE_URL ?>/category/<?= h($c['slug']) ?>"><?= h($c['name']) ?><span class="n"><?= (int)$c['n'] ?></span></a>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="browse" aria-labelledby="city-h"><div class="in">
  <span class="eyebrow">依縣市逛</span>
  <h2 id="city-h">你在哪個城市？</h2>
  <div class="cchips">
    <?php foreach ($cityCounts as $r): $cn = $cityNames[$r['city_slug']] ?? ''; if ($cn === '') continue; ?>
    <a class="cc" href="<?= BASE_URL ?>/city/<?= h($r['city_slug']) ?>"><?= h(str_replace(['市','縣'], '', $cn)) ?><span class="n"><?= (int)$r['n'] ?></span></a>
    <?php endforeach; ?>
    <a class="cc" href="<?= BASE_URL ?>/city.php">看所有縣市 →</a>
  </div>
</div></section>

<section class="belief" aria-label="我們相信的事"><div class="in">
  <span class="eyebrow">我們相信的事</span>
  <blockquote>口碑不該只是<span class="u">幾顆星</span>。<br>把好店的眉角攤開來，你看懂了，好店自然留得住。</blockquote>
</div></section>

<section class="b2b" id="b2b">
  <div class="tx">
    <h3>你是店家？</h3>
    <p>把你的真實口碑，變成會被 Google 搜到、會帶客上門的頁面。免費諮詢，先聊聊你的店。</p>
  </div>
  <a class="line" href="https://lin.ee/N0z1eq9" target="_blank" rel="noopener"><b>LINE</b> 免費諮詢</a>
</section>
</main>

<footer class="ft">
  <div class="in">
    <div class="brand">
      <div class="lg2"><span class="m">店</span>店家好口碑</div>
      <p>任你發現好口碑店家。看主題找需求，先搞懂再決定。全台 <?= $totalClients ?>+ 家在地店家。</p>
    </div>
    <div><h5>探索主題</h5>
      <?php foreach (array_slice($categories, 0, 5) as $c): ?>
      <a href="<?= BASE_URL ?>/category/<?= h($c['slug']) ?>"><?= h($c['name']) ?></a>
      <?php endforeach; ?>
      <a href="<?= BASE_URL ?>/category.php">所有分類</a>
    </div>
    <div><h5>依縣市</h5>
      <a href="<?= BASE_URL ?>/city/tainan">台南</a>
      <a href="<?= BASE_URL ?>/city/kaohsiung">高雄</a>
      <a href="<?= BASE_URL ?>/city/taichung">台中</a>
      <a href="<?= BASE_URL ?>/city/chiayi">嘉義</a>
      <a href="<?= BASE_URL ?>/city.php">所有縣市</a>
    </div>
    <div><h5>內容</h5>
      <a href="<?= BASE_URL ?>/guide">在地攻略</a>
      <a href="<?= BASE_URL ?>/category.php">主題總覽</a>
    </div>
    <div><h5>關於 / 店家</h5>
      <a href="#b2b">我是店家</a>
      <a href="https://lin.ee/N0z1eq9" target="_blank" rel="noopener">聯絡我們（LINE）</a>
    </div>
  </div>
  <div class="ftbot"><span>© <?= date('Y') ?> 店家好口碑・任你發現好口碑店家</span><span>gomag.com.tw</span></div>
</footer>

<div class="mega" id="mega">
  <div class="mh"><b>探索</b><button class="x" aria-label="關閉選單" onclick="document.getElementById('mega').classList.remove('open')">✕</button></div>
  <div class="mb">
    <div class="grp"><h4>探索主題</h4><div class="links">
      <?php foreach ($categories as $c): if ((int)$c['n'] === 0) continue; ?>
      <a href="<?= BASE_URL ?>/category/<?= h($c['slug']) ?>"><?= h($c['icon']) ?> <?= h($c['name']) ?></a>
      <?php endforeach; ?>
    </div></div>
    <div class="grp"><h4>探索縣市</h4><div class="links">
      <?php foreach ($cityCounts as $r): $cn = $cityNames[$r['city_slug']] ?? ''; if ($cn === '') continue; ?>
      <a href="<?= BASE_URL ?>/city/<?= h($r['city_slug']) ?>"><?= h($cn) ?></a>
      <?php endforeach; ?>
      <a href="<?= BASE_URL ?>/city.php">更多</a>
    </div></div>
    <div class="grp"><h4>更多</h4><div class="links">
      <a href="<?= BASE_URL ?>/guide">在地攻略</a>
      <a href="#b2b" onclick="document.getElementById('mega').classList.remove('open')">我是店家</a>
    </div></div>
  </div>
</div>

</body>
</html>
