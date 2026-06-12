<?php
// ============================================================
// Migration 123 — 3 個新啟用交叉頁補 guide 內鏈（接 122）
// ------------------------------------------------------------
// 122 啟用的 3 頁都是 0 guide 內鏈（孤立），按 113-120 配方補：
// 1) kaohsiung/food/buffet → 插入 kaohsiung-group-dining-guide 的
//    既有延伸區塊（120 建的 v1 marker ul）
// 2) tainan/auto-service/detailing →
//    a. tainan-motel-recommend append 延伸區塊（同城同受眾·有車族）
//    b. chiayi-car-wrap-vs-coating 延伸區塊加跨城行（同主題）
// 3) kaohsiung/home-service/kitchen →
//    kaohsiung-old-apartment-renovation + kaohsiung-tsmc-home-renovation
//    各 append 延伸區塊（廚房翻新核心場景）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/123_link_3_new_geo_pages.php
// 冪等：先查 body 是否已含目標 URL
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

function getBody($db, $slug) {
    $st = $db->prepare("SELECT body_html FROM guides WHERE slug=?");
    $st->execute([$slug]);
    return $st->fetchColumn();
}
function setBody($db, $slug, $body) {
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$body, $slug]);
}

// ── 1) buffet 插入高雄聚餐 guide 既有延伸 ul ──
echo "═══ 1) 高雄吃到飽 ═══\n";
$slug = 'kaohsiung-group-dining-guide';
$b = getBody($db, $slug);
if (strpos($b, '/city/kaohsiung/food/buffet') !== false) {
    echo "  ⏭ {$slug} 已含 buffet 連結\n";
} else {
    $old = '<li><a href="/city/kaohsiung/food/hotpot">高雄火鍋</a></li>';
    $new = $old . "\n  <li><a href=\"/city/kaohsiung/food/buffet\">高雄吃到飽</a></li>";
    if (strpos($b, $old) === false) { echo "  ❌ 找不到插入點\n"; }
    else { setBody($db, $slug, str_replace($old, $new, $b)); echo "  ✅ {$slug} 插入 buffet 連結\n"; }
}

// ── 2a) 台南汽車旅館懶人包 append 有車族延伸 ──
echo "\n═══ 2) 台南汽車美容 ═══\n";
$slug = 'tainan-motel-recommend';
$b = getBody($db, $slug);
$marker = '<!-- related-services-block-v1 -->';
if (strpos($b, $marker) !== false) {
    echo "  ⏭ {$slug} 已有延伸區塊\n";
} else {
    $append = "\n\n{$marker}\n<h2 id=\"related-services\">延伸閱讀：台南有車族相關服務</h2>\n";
    $append .= "<p>開車跑台南，車子的面子也要顧：</p>\n<ul>\n";
    $append .= "  <li><a href=\"/city/tainan/auto-service/detailing\">台南汽車美容</a>（洗車打蠟、鍍膜、內裝清潔）</li>\n";
    $append .= "</ul>\n<p>更多汽車服務見 <a href=\"/city/tainan/auto-service\">台南汽車服務</a> 樞紐頁。</p>\n";
    setBody($db, $slug, $b . $append);
    echo "  ✅ {$slug} append 延伸區塊\n";
}

// ── 2b) 嘉義包膜鍍膜文 延伸區塊加跨城行 ──
$slug = 'chiayi-car-wrap-vs-coating';
$b = getBody($db, $slug);
if (strpos($b, '/city/tainan/auto-service/detailing') !== false) {
    echo "  ⏭ {$slug} 已含台南 detailing 連結\n";
} else {
    $old = '<p>更多嘉義汽車服務見 <a href="/city/chiayi/auto-service">嘉義汽車服務</a> 樞紐頁。</p>';
    $new = $old . "\n<p>其他縣市：<a href=\"/city/tainan/auto-service/detailing\">台南汽車美容</a></p>";
    if (strpos($b, $old) === false) { echo "  ❌ {$slug} 找不到插入點\n"; }
    else { setBody($db, $slug, str_replace($old, $new, $b)); echo "  ✅ {$slug} 加跨城行\n"; }
}

// ── 3) kitchen → 高雄 2 篇翻新 guide append ──
echo "\n═══ 3) 高雄系統廚具 ═══\n";
foreach (['kaohsiung-old-apartment-renovation', 'kaohsiung-tsmc-home-renovation'] as $slug) {
    $b = getBody($db, $slug);
    if (strpos($b, $marker) !== false) { echo "  ⏭ {$slug} 已有延伸區塊\n"; continue; }
    $append = "\n\n{$marker}\n<h2 id=\"related-services\">延伸閱讀：高雄居家服務子分類</h2>\n";
    $append .= "<p>翻新裝修常見的下一步：</p>\n<ul>\n";
    $append .= "  <li><a href=\"/city/kaohsiung/home-service/kitchen\">高雄系統廚具</a>（系統櫥櫃、流理台、廚房規劃）</li>\n";
    $append .= "  <li><a href=\"/city/kaohsiung/home-service/interior-design\">高雄室內設計</a></li>\n";
    $append .= "  <li><a href=\"/city/kaohsiung/home-service/reno-detail\">高雄裝潢細清</a></li>\n";
    $append .= "</ul>\n<p>更多服務見 <a href=\"/city/kaohsiung/home-service\">高雄居家服務</a> 樞紐頁。</p>\n";
    setBody($db, $slug, $b . $append);
    echo "  ✅ {$slug} append 延伸區塊\n";
}

// ── 驗證 ──
echo "\n═══ 驗證：3 頁內鏈數 ═══\n";
foreach ([
    '/city/kaohsiung/food/buffet',
    '/city/tainan/auto-service/detailing',
    '/city/kaohsiung/home-service/kitchen',
] as $url) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%{$url}%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %-45s → %d 篇 guide 內鏈\n", $flag, $url, $n);
}
echo "\n完成。\n";
