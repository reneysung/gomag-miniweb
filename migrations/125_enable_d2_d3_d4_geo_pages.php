<?php
// ============================================================
// Migration 125 — 補開 D2/D3/D4 交叉頁（9 頁）+ 防孤立內鏈
// ------------------------------------------------------------
// gomag 開頁 11 天計劃進度補齊：D1（122）已開，補 D2~D4 共 9 頁。
// 流程同 122：確認行情 → 去草稿註記「（行情請依實際確認）」→ 啟用。
//
// 行情盤點：Reney 估的區間整體合理（台南<台中、保養/除蟲/甜點皆在地行情內），
// 僅 1 處 2026 微調：台南牛肉麵上緣 180 → 200（在地 premium 牛肉麵已破 180）。
//
// 9 頁：
//   D2  tainan/food/buffet, tainan/food/korean, kaohsiung/auto-service/repair
//   D3  taichung/food/buffet, tainan/food/noodles, tainan/home-service/pest-control
//   D4  tainan/food/dessert, kaohsiung/food/korean, tainan/auto-service/repair
//
// 防孤立內鏈（食類有現成 guide 對口；保養/台中buffet 靠樞紐 nav，另記追蹤）：
//   台南美食懶人包(id=65) += buffet/korean/noodles/dessert
//   高雄聚餐攻略 += korean
//   台南清潔懶人包(id=71) += pest-control（尚撰除蟲已在名單內）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/125_enable_d2_d3_d4_geo_pages.php
// 冪等：str_replace 找不到不動、is_active 重跑無害、內鏈查 URL 已存在則跳過
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$DRAFT = '（行情請依實際確認）';

function enableRow($db, $city, $cat, $svc, $patches, $DRAFT) {
    $st = $db->prepare("SELECT intro_html, faqs FROM geo_category_pages WHERE city_slug=? AND category_id=? AND service_slug=?");
    $st->execute([$city, $cat, $svc]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) { echo "  ❌ {$city}/{$svc} row 不存在\n"; return; }
    $intro = $r['intro_html'];
    $faqs  = $r['faqs'];
    $cnt = 0;
    // 先套行情微調
    foreach ($patches as [$old, $new]) {
        if (strpos($intro, $old) !== false) { $intro = str_replace($old, $new, $intro); $cnt++; }
        if (strpos($faqs, $old) !== false)  { $faqs  = str_replace($old, $new, $faqs);  $cnt++; }
    }
    // 去草稿註記
    $intro = str_replace($DRAFT, '', $intro);
    $db->prepare("UPDATE geo_category_pages SET intro_html=?, faqs=?, is_active=1 WHERE city_slug=? AND category_id=? AND service_slug=?")
       ->execute([$intro, $faqs, $city, $cat, $svc]);
    printf("  ✅ %-9s/%-13s 啟用（行情微調 %d 處 + 去草稿註記）\n", $city, $svc, $cnt);
}

echo "═══ 啟用 9 頁 ═══\n";
// D2
enableRow($db, 'tainan', 1, 'buffet', [], $DRAFT);
enableRow($db, 'tainan', 1, 'korean', [], $DRAFT);
enableRow($db, 'kaohsiung', 11, 'repair', [], $DRAFT);
// D3
enableRow($db, 'taichung', 1, 'buffet', [], $DRAFT);
enableRow($db, 'tainan', 1, 'noodles', [
    ['牛肉麵約 NT$120–180', '牛肉麵約 NT$130–200'],
], $DRAFT);
enableRow($db, 'tainan', 2, 'pest-control', [], $DRAFT);
// D4
enableRow($db, 'tainan', 1, 'dessert', [], $DRAFT);
enableRow($db, 'kaohsiung', 1, 'korean', [], $DRAFT);
enableRow($db, 'tainan', 11, 'repair', [], $DRAFT);

// ════════════════════════════════════════════
// 防孤立內鏈
// ════════════════════════════════════════════
echo "\n═══ 防孤立內鏈 ═══\n";

function getBody($db, $slug) { $st = $db->prepare("SELECT body_html FROM guides WHERE slug=?"); $st->execute([$slug]); return $st->fetchColumn(); }
function setBody($db, $slug, $b) { $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$b, $slug]); }

// 1) 台南美食懶人包 id=65：在結尾子服務內鏈列加 buffet/korean/noodles/dessert
$b = getBody($db, 'tainan-meishi-recommend-12');
if ($b) {
    $anchor = '<a href="https://www.gomag.com.tw/city/tainan/food/banquet-catering">辦桌外燴</a>。';
    if (strpos($b, '/city/tainan/food/buffet') !== false) {
        echo "  ⏭ 台南美食懶人包 已含新連結\n";
    } elseif (strpos($b, $anchor) !== false) {
        $add = '<a href="https://www.gomag.com.tw/city/tainan/food/banquet-catering">辦桌外燴</a>、'
             . '<a href="https://www.gomag.com.tw/city/tainan/food/buffet">吃到飽</a>、'
             . '<a href="https://www.gomag.com.tw/city/tainan/food/korean">韓式料理</a>、'
             . '<a href="https://www.gomag.com.tw/city/tainan/food/noodles">麵食</a>、'
             . '<a href="https://www.gomag.com.tw/city/tainan/food/dessert">甜點烘焙</a>。';
        setBody($db, 'tainan-meishi-recommend-12', str_replace($anchor, $add, $b));
        echo "  ✅ 台南美食懶人包 += buffet/korean/noodles/dessert\n";
    } else {
        echo "  ⚠️ 台南美食懶人包 找不到錨點，跳過\n";
    }
}

// 2) 高雄聚餐攻略：在子分類 ul 加 korean
$b = getBody($db, 'kaohsiung-group-dining-guide');
if ($b) {
    if (strpos($b, '/city/kaohsiung/food/korean') !== false) {
        echo "  ⏭ 高雄聚餐攻略 已含 korean\n";
    } else {
        $old = '<li><a href="/city/kaohsiung/food/buffet">高雄吃到飽</a></li>';
        if (strpos($b, $old) !== false) {
            setBody($db, 'kaohsiung-group-dining-guide', str_replace($old, $old . "\n  <li><a href=\"/city/kaohsiung/food/korean\">高雄韓式料理</a></li>", $b));
            echo "  ✅ 高雄聚餐攻略 += korean\n";
        } else { echo "  ⚠️ 高雄聚餐攻略 找不到錨點\n"; }
    }
}

// 3) 台南清潔懶人包 id=71：CTA 段加 pest-control
$b = getBody($db, 'tainan-cleaning-company-recommend');
if ($b) {
    if (strpos($b, '/city/tainan/home-service/pest-control') !== false) {
        echo "  ⏭ 台南清潔懶人包 已含 pest-control\n";
    } else {
        $old = '<a href="https://www.gomag.com.tw/city/tainan/home-service/aircon-clean">台南冷氣清洗</a>、';
        if (strpos($b, $old) !== false) {
            setBody($db, 'tainan-cleaning-company-recommend', str_replace($old, $old . '<a href="https://www.gomag.com.tw/city/tainan/home-service/pest-control">台南除蟲消毒</a>、', $b));
            echo "  ✅ 台南清潔懶人包 += pest-control\n";
        } else { echo "  ⚠️ 台南清潔懶人包 找不到錨點\n"; }
    }
}

// ════════════════════════════════════════════
// 驗證
// ════════════════════════════════════════════
echo "\n═══ 驗證：9 頁啟用狀態 + 草稿殘留 + guide 內鏈 ═══\n";
$cats = $db->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$pages = [
    ['tainan',1,'buffet'],['tainan',1,'korean'],['kaohsiung',11,'repair'],
    ['taichung',1,'buffet'],['tainan',1,'noodles'],['tainan',2,'pest-control'],
    ['tainan',1,'dessert'],['kaohsiung',1,'korean'],['tainan',11,'repair'],
];
foreach ($pages as [$c,$cat,$s]) {
    $st = $db->prepare("SELECT is_active, intro_html LIKE '%行情請依實際確認%' note FROM geo_category_pages WHERE city_slug=? AND category_id=? AND service_slug=?");
    $st->execute([$c,$cat,$s]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    $url = "/city/{$c}/{$cats[$cat]}/{$s}";
    $lk = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $lk->execute(["%{$url}%"]);
    $n = (int)$lk->fetchColumn();
    printf("  %s active=%d 草稿=%s guide內鏈=%d  %s\n", $r['is_active']?'✅':'❌', $r['is_active'], $r['note']?'⚠️有':'無', $n, $url);
}
echo "\n完成。\n";
