<?php
// ============================================================
// Migration 120 — 階段 3：最後 7 個孤立子服務頁補內鏈（孤立清零）
// ------------------------------------------------------------
// 7 個孤立頁全部有完美對口的既有 city guide，直接 append 內鏈即可：
//   kh hotpot/japanese/yakiniku → kaohsiung-group-dining-guide
//   chiayi car-wrap/coating     → chiayi-car-wrap-vs-coating（自己主題就是這 2 頁）
//   tainan furniture            → tainan-sofa-mattress-custom
//   tainan chinese-medicine     → tainan-tcm-guide
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/120_stage3_isolated_link_fix.php
// 冪等：marker
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$marker = '<!-- related-services-block-v1 -->';

// guide slug → [標題, 說明, 連結清單 [url, 錨點文字]]
$jobs = [
    'kaohsiung-group-dining-guide' => [
        'h2' => '延伸閱讀：高雄聚餐子分類',
        'intro' => '<p>照類型找高雄在地聚餐店家：</p>',
        'links' => [
            ['/city/kaohsiung/food/hotpot', '高雄火鍋'],
            ['/city/kaohsiung/food/yakiniku', '高雄燒肉燒烤'],
            ['/city/kaohsiung/food/japanese', '高雄日式料理'],
            ['/city/kaohsiung/food/banquet', '高雄婚宴餐廳'],
        ],
        'tail' => '<p>更多高雄餐飲店家見 <a href="/city/kaohsiung/food">高雄餐飲美食</a> 樞紐頁。</p>',
    ],
    'chiayi-car-wrap-vs-coating' => [
        'h2' => '延伸閱讀：嘉義汽車服務子分類',
        'intro' => '<p>找嘉義在地包膜鍍膜店家：</p>',
        'links' => [
            ['/city/chiayi/auto-service/car-wrap', '嘉義汽車包膜'],
            ['/city/chiayi/auto-service/coating', '嘉義汽車鍍膜'],
        ],
        'tail' => '<p>更多嘉義汽車服務見 <a href="/city/chiayi/auto-service">嘉義汽車服務</a> 樞紐頁。</p>',
    ],
    'tainan-sofa-mattress-custom' => [
        'h2' => '延伸閱讀：台南居家服務子分類',
        'intro' => '<p>找台南在地家具與居家服務：</p>',
        'links' => [
            ['/city/tainan/home-service/furniture', '台南訂製沙發床墊'],
            ['/city/tainan/home-service/interior-design', '台南室內設計'],
            ['/city/tainan/home-service/cleaning', '台南清潔公司'],
        ],
        'tail' => '<p>更多服務見 <a href="/city/tainan/home-service">台南居家服務</a> 樞紐頁。</p>',
    ],
    'tainan-tcm-guide' => [
        'h2' => '延伸閱讀：台南健康醫療子分類',
        'intro' => '<p>找台南在地中醫與健康服務：</p>',
        'links' => [
            ['/city/tainan/health/chinese-medicine', '台南中醫診所'],
        ],
        'tail' => '<p>更多台南健康醫療店家見 <a href="/city/tainan/health">台南健康醫療</a> 樞紐頁。</p>',
    ],
];

foreach ($jobs as $slug => $j) {
    $row = $db->prepare("SELECT body_html FROM guides WHERE slug=?");
    $row->execute([$slug]);
    $b = $row->fetchColumn();
    if (!$b) { echo "❌ 找不到 {$slug}\n"; continue; }
    if (strpos($b, $marker) !== false) { echo "⏭ skip {$slug}（已有 marker）\n"; continue; }

    $append = "\n\n{$marker}\n<h2 id=\"related-services\">{$j['h2']}</h2>\n{$j['intro']}\n<ul>\n";
    foreach ($j['links'] as [$url, $label]) {
        $append .= "  <li><a href=\"{$url}\">{$label}</a></li>\n";
    }
    $append .= "</ul>\n{$j['tail']}\n";
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$b . $append, $slug]);
    echo "✅ {$slug} append " . count($j['links']) . " 內鏈\n";
}

// 驗證 7 個目標頁
echo "\n═══ 驗證：7 個目標子服務頁 ═══\n";
foreach ([
    ['kaohsiung','food','hotpot'],['kaohsiung','food','japanese'],['kaohsiung','food','yakiniku'],
    ['chiayi','auto-service','car-wrap'],['chiayi','auto-service','coating'],
    ['tainan','home-service','furniture'],['tainan','health','chinese-medicine'],
] as [$c, $cat, $s]) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$c}/{$cat}/{$s}%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %s/%s → %d 篇 guide 內鏈\n", $flag, $c, $s, $n);
}

// 全站終盤
$cats = $db->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$iso = 0; $tot = 0; $isoList = [];
foreach ($db->query("SELECT city_slug, category_id, service_slug FROM geo_category_pages WHERE service_slug<>'' AND is_active=1") as $r) {
    $tot++;
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$r['city_slug']}/{$cats[$r['category_id']]}/{$r['service_slug']}%"]);
    if ((int)$st->fetchColumn() === 0) { $iso++; $isoList[] = "{$r['city_slug']}/{$r['service_slug']}"; }
}
printf("\n═══ 全站終盤：孤立 %d / %d（%.0f%%）═══\n", $iso, $tot, 100 * $iso / max(1, $tot));
if ($isoList) { echo "  剩餘：" . implode(', ', $isoList) . "\n"; }
echo "\n完成。\n";
