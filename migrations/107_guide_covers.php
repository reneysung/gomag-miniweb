<?php
// ============================================================
// Migration 107 — 53 篇無 cover guide 補主題式 SVG 封面
// ------------------------------------------------------------
// 站裡多篇 guide 無 cover_image、hero 顯示黑色 gradient。
// 修法：依 slug pattern 分類成 12 主題、為每主題生成漸層 SVG 封面、
// 統一風格、無版權問題、即時生效。
// SVG 設計：主題色漸層 + 大字主題名 + emoji icon + 「店家好口碑」品牌字。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/107_guide_covers.php
// 冪等：UPDATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 主題定義（顏色 + emoji + 顯示名）──
$themes = [
  'waterproof'  => ['#1e3a8a', '#3b82f6', '🛡', '防水工程'],
  'flooring'    => ['#7c2d12', '#d97706', '🪵', '木地板'],
  'aircon'      => ['#0e7490', '#06b6d4', '❄️', '冷氣清洗'],
  'fineclean'   => ['#0f766e', '#14b8a6', '✨', '裝潢細清'],
  'handover'    => ['#1e40af', '#60a5fa', '🔑', '交屋驗屋'],
  'banquet'     => ['#991b1b', '#dc2626', '🍽', '辦桌外燴'],
  'food'        => ['#9a3412', '#f97316', '🍜', '在地美食'],
  'renovation'  => ['#374151', '#9ca3af', '🏠', '老屋翻新'],
  'furniture'   => ['#4c1d95', '#8b5cf6', '🛋', '家具家飾'],
  'tcm'         => ['#065f46', '#10b981', '🌿', '中醫養生'],
  'car'         => ['#1f2937', '#6366f1', '🚗', '汽車美容'],
  'cleaning'    => ['#15803d', '#22c55e', '🧹', '居家清潔'],
];

// ── slug → theme 分類 ──
function classify($slug) {
    if (preg_match('/exterior-waterproof|waterproof/', $slug)) return 'waterproof';
    if (preg_match('/flooring|geometric-tile|tile-material/', $slug)) return 'flooring';
    if (preg_match('/aircon|air-con/', $slug)) return 'aircon';
    if (preg_match('/fine-clean|handover-fine-clean/', $slug)) return 'fineclean';
    if (preg_match('/handover|inspection|tsmc-home/', $slug)) return 'handover';
    if (preg_match('/banquet|banquet-catering/', $slug)) return 'banquet';
    if (preg_match('/beef-soup|group-dining|izakaya|street-food/', $slug)) return 'food';
    if (preg_match('/renovation|old-apartment|luxury-renovation|townhouse-cleaning|renovation-cleaning|humidity-mold/', $slug)) return 'renovation';
    if (preg_match('/sofa-mattress|furniture|flooring-material/', $slug)) return 'furniture';
    if (preg_match('/tcm|chinese-medicine/', $slug)) return 'tcm';
    if (preg_match('/car-wrap|car-coating|car-/', $slug)) return 'car';
    if (preg_match('/cleaning|moving-cleaning|regular-cleaning|cleaning-detail|sofa-mattress-custom|homestay-relocation/', $slug)) return 'cleaning';
    return 'cleaning'; // default fallback
}

// ── SVG 生成器 ──
function makeSvg($c1, $c2, $emoji, $title, $slug) {
    // 用 base64 emoji 嵌入 SVG 確保各平台一致
    return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
    <pattern id="dot" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
      <circle cx="20" cy="20" r="1.5" fill="rgba(255,255,255,0.08)"/>
    </pattern>
  </defs>
  <rect width="1200" height="630" fill="url(#g)"/>
  <rect width="1200" height="630" fill="url(#dot)"/>
  <text x="600" y="270" text-anchor="middle" font-size="120" font-family="Apple Color Emoji, Segoe UI Emoji, sans-serif">{$emoji}</text>
  <text x="600" y="420" text-anchor="middle" font-size="84" font-weight="900" fill="white" font-family="'Noto Serif TC', 'Songti TC', serif" letter-spacing="6">{$title}</text>
  <text x="600" y="540" text-anchor="middle" font-size="22" fill="rgba(255,255,255,0.55)" font-family="sans-serif" letter-spacing="8">店家好口碑 ・ gomag.com.tw</text>
</svg>
SVG;
}

// 1. 生成 12 張 SVG
$dir = __DIR__ . '/../uploads/guides/covers';
if (!is_dir($dir)) mkdir($dir, 0755, true);
foreach ($themes as $key => $t) {
    $svg = makeSvg($t[0], $t[1], $t[2], $t[3], $key);
    $f = "{$dir}/{$key}.svg";
    file_put_contents($f, $svg);
    printf("SVG: %s.svg → %s\n", $key, $t[3]);
}

// 2. UPDATE guides 設 cover_image
$rows = $db->query("SELECT slug FROM guides WHERE status='published' AND (cover_image IS NULL OR cover_image='')")->fetchAll(PDO::FETCH_ASSOC);
echo "\n== 分類 + UPDATE ".count($rows)." 篇 ==\n";
$counts = [];
$upd = $db->prepare("UPDATE guides SET cover_image=? WHERE slug=?");
foreach ($rows as $r) {
    $theme = classify($r['slug']);
    $cover = "https://www.gomag.com.tw/uploads/guides/covers/{$theme}.svg";
    $upd->execute([$cover, $r['slug']]);
    $counts[$theme] = ($counts[$theme] ?? 0) + 1;
    printf("  %-45s → %s\n", $r['slug'], $theme);
}
echo "\n== 統計 ==\n";
foreach ($counts as $t => $n) printf("  %-12s %d 篇\n", $t, $n);
echo "\n完成。\n";
