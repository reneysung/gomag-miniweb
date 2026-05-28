<?php
// ============================================================
// Migration 065 — 洪廚 嘉義/彰化/新竹 3 城補網友分享
// ------------------------------------------------------------
// Reney 給的 URL 抓 title + 摘要寫進 external_reviews_json：
//   嘉義 → 2 篇（Mobile01 那篇 fetch 403，未含）
//   彰化 → 4 篇全收
//   新竹 → 1 篇（vocus 67e524 內文整篇是「潮州聞香閣」非洪廚，未含）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/065_hongchu_more_city_reviews.php
// 冪等：直接 UPDATE，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

$reviewsByCity = [
    'chiayi' => [
        [
            'title'   => '嘉義尾牙推薦｜450 桌盛大場面，洪廚喜宴阿男師菜色開箱',
            'url'     => 'https://www.stanleylove.com.tw/archives/3941',
            'source'  => '史丹利樂福',
            'excerpt' => '450 桌企業尾牙開箱：龍蝦月光寶盒、黑蒜頭燉雞湯、剁椒龍虎斑；上百萬級專業烤箱現場出菜，飯店級擺盤、流程穩定。',
        ],
        [
            'title'   => '嘉義辦桌外燴｜新人二次進場大黃蜂護嫁・南台灣辦桌天花板洪廚囍宴',
            'url'     => 'https://vocus.cc/article/665b0640fd897800010857ea',
            'source'  => '南搞先生',
            'excerpt' => '一水綠洲莊園婚宴實錄：生魚片、龍蝦壽司、精緻甜點，「辦桌菜撐起了大場面」，挑戰高級飯店等級的擺盤與食材。',
        ],
    ],
    'changhua' => [
        [
            'title'   => '彰化尾牙辦桌哪家強？洪廚喜宴阿男師 450 桌尾牙全紀錄',
            'url'     => 'https://vocus.cc/article/69a79baffd897800014894d3',
            'source'  => 'vocus 方格子',
            'excerpt' => '450 桌大型辦桌調度紀錄：龍蝦月光寶盒、鮑魚沙拉、千層蛋糕等精緻菜色，企業尾牙外燴的優質選擇。',
        ],
        [
            'title'   => '彰化辦桌吃得比餐廳還要好？跳脫傳統框架的洪廚囍宴',
            'url'     => 'https://www.stanleylove.com.tw/archives/2968',
            'source'  => '史丹利樂福',
            'excerpt' => '同事婚禮親臨體驗：澎湃菜色、五星飯店級擺盤，融合日料、法式，打破傳統辦桌印象。',
        ],
        [
            'title'   => '彰化辦桌外燴有變形金剛護嫁？辦桌天花板洪廚囍宴 11 道創新料理',
            'url'     => 'https://www.mrtrouble.com.tw/2024/06/blog-post_01.html',
            'source'  => '南搞先生',
            'excerpt' => '戶外婚禮宴席紀錄：創新料理 + 精緻擺盤 + 變形金剛道具助興，傳統辦桌的全新風貌。',
        ],
        [
            'title'   => '彰化婚宴辦桌新選擇！洪廚喜宴高顏值擺盤＋現煮真鯛料理必吃',
            'url'     => 'https://www.walkerland.com.tw/article/view/414314',
            'source'  => 'WalkerLand 窩客島',
            'excerpt' => '婚宴實戰開箱：現場搭設廚具與冷凍車、真鯛刺身與松露蝦等創意菜色，總鋪師手作的職人講究。',
        ],
    ],
    'hsinchu' => [
        [
            'title'   => '新竹尾牙辦桌新高度！700 桌尾牙大場面・黑蒜花膠雞湯到義式奶酪',
            'url'     => 'https://vocus.cc/article/69b7b32afd897800012749de',
            'source'  => 'Running man 的吃喝玩樂',
            'excerpt' => 'Garmin 公司 700 桌尾牙宴席紀錄：台南阿男師北上掌廚，傳統辦桌結合現代宴席，從菜色到品牌視覺全面升級。',
        ],
    ],
];

$upd = $db->prepare("UPDATE client_city_pages
    SET external_reviews_json = ?
    WHERE client_id = ? AND city_slug = ?");

foreach ($reviewsByCity as $citySlug => $rows) {
    $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $upd->execute([$json, $cid, $citySlug]);
    echo "✅ {$citySlug}: 寫入 " . count($rows) . " 篇\n";
}

echo "\n完成。\n";
echo "備註：\n";
echo "  ⚠️  Mobile01 t=7215008 fetch 403 擋下，未含。要請 Reney 給 title 或之後在後台補。\n";
echo "  ⚠️  vocus 67e5249cfd 內文整篇是「潮州聞香閣」非洪廚，未含。Reney 可能貼錯連結，要確認。\n";
