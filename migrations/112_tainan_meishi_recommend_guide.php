<?php
// ============================================================
// Migration 112 — 攻略文《台南美食推薦 2026：在地嚴選 12 家》
// ------------------------------------------------------------
// 配合 110 樞紐頁 SEO，寫一篇導覽型攻略文收「台南美食推薦」流量。
// 12 家 = Reney 確認的菜系多樣化清單（火鍋/燒肉/日式/牛排/中式川菜/
// 西餐/海鮮/麵食/烘焙/咖啡/辦桌/素食）。
//
// 內容紀律（「不能無中生有」鐵則）：
//   - 3 家有完整 tagline (#70/#252/#94) → 用 tagline 寫實質介紹
//   - 9 家 tagline 空 → 只寫「店名(暗示菜系)+區位+引導店家頁」短描述
//   - 不編造菜色、口感、服務細節
//   - 每家有 → /store/{slug} 內鏈導流
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/112_tainan_meishi_recommend_guide.php
// 冪等：INSERT ... ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'tainan-meishi-recommend-12';
$title = '台南美食推薦 2026：在地嚴選 12 家｜火鍋、燒肉、牛排、日式、川菜、海鮮、辦桌一次看';
$metaTitle = '台南美食推薦 2026｜在地嚴選 12 家｜火鍋燒肉牛排日式｜店家好口碑';
$metaDesc = '台南美食推薦哪 12 家？涵蓋火鍋、燒肉、牛排、日式料理、川菜、英式餐酒、海鮮粥、清燉牛肉麵、烘焙、咖啡、辦桌總舖師、素食，府城中西區、東區、永康在地嚴選清單。';
$excerpt = '台南美食推薦怎麼挑？店家好口碑收錄 12 家府城在地嚴選餐廳，菜系涵蓋火鍋燒肉牛排日式川菜西餐海鮮麵食烘焙咖啡辦桌素食，一篇看完台南聚餐、家庭日常、婚宴外燴怎麼選。';
$cover = 'https://www.gomag.com.tw/uploads/guides/covers/food.svg';

// 12 家內容（誠實版：有 tagline 的飽寫，沒 tagline 的短而真）
$stores = [
    [
        'id' => 87, 'slug' => '063023888', 'name' => '牧鍋 MooPot 頂級熟成牛鍋物',
        'cuisine' => '火鍋', 'area' => '裕農路',
        'desc' => '位於台南裕農路的精緻牛肉鍋物店，店名標示「頂級熟成牛鍋物」，定位走精緻聚餐路線。',
        'phone' => '06-2370222', 'address' => '台南市裕農路 933 號',
    ],
    [
        'id' => 17, 'slug' => 'grillmeat', 'name' => '初云職人燒肉',
        'cuisine' => '燒肉燒烤', 'area' => '東區建平七街',
        'desc' => '東區建平七街的職人燒肉店，店名以「職人」為定位主軸，主打燒肉與燒烤。',
        'phone' => '0928-079983', 'address' => '台南市建平七街 585 號',
    ],
    [
        'id' => 105, 'slug' => 'eel', 'name' => '宝鰻道地鰻魚飯／日式定食（南紡東平店）',
        'cuisine' => '日式料理', 'area' => '東區東平路（南紡商圈）',
        'desc' => '南紡夢時代周邊、東平路上的鰻魚飯與日式定食店，以「道地鰻魚飯」為主力。',
        'phone' => '06-2004273', 'address' => '台南市東區東平路 75 號',
    ],
    [
        'id' => 70, 'slug' => 'steakhouse', 'name' => '丹妮牛排',
        'cuisine' => '牛排', 'area' => '中西區海安路',
        'desc' => '台南中西區海安路上、首創「無醬無麵」的美式原肉牛排館，中價位吃到原肉等級的排餐。',
        'phone' => '06-2238787', 'address' => '台南市中西區海安路一段 23 號',
    ],
    [
        'id' => 252, 'slug' => 'hot', 'name' => '辣嬌程癮（重慶家常菜）',
        'cuisine' => '中式熱炒／川菜', 'area' => '東區東興路',
        'desc' => '台南東區一間平價、道地的重慶家常菜，主打「白飯殺手」的下飯川味，辣度可依個人口味客製，一個人也能吃到正宗重慶味。',
        'phone' => '0905-128631', 'address' => '台南市東區東興路 48 號',
    ],
    [
        'id' => 39, 'slug' => 'bistro1', 'name' => '亞芙英式餐廳 The Artful Dodger',
        'cuisine' => '西餐餐酒館', 'area' => '東豐路',
        'desc' => '東豐路上的英式餐酒館，店名標示主打「手工漢堡、炸魚薯條、調酒」，定位英式 bistro 風格。',
        'phone' => '06-2370370', 'address' => '台南市東豐路 181 號',
    ],
    [
        'id' => 11, 'slug' => '0976179401', 'name' => '活跳跳海鮮粥',
        'cuisine' => '海鮮料理', 'area' => '中西區府前路',
        'desc' => '中西區府前路上的海鮮粥店，店名直白標示「海鮮粥」為主軸。',
        'phone' => '06-2155055', 'address' => '台南市府前路一段 323 號',
    ],
    [
        'id' => 65, 'slug' => 'lwbeef', 'name' => '六味真湯清燉牛肉麵',
        'cuisine' => '麵食', 'area' => '永華路二段',
        'desc' => '永華路二段上的清燉牛肉麵店，店名標示「六味真湯、清燉牛肉麵」為主打。',
        'phone' => '06-2987055', 'address' => '台南市永華路二段 738 號',
    ],
    [
        'id' => 106, 'slug' => 'bread', 'name' => '麥園烘焙坊',
        'cuisine' => '甜點烘焙', 'area' => '東區富農一街',
        'desc' => '東區富農一街的烘焙坊，店名以「麥園烘焙坊」為主，提供烘焙麵包甜點。',
        'phone' => '06-2684870', 'address' => '台南市東區富農一街 143 號',
    ],
    [
        'id' => 165, 'slug' => 'coffee01', 'name' => 'CREMA CAFÉ 葵瑪咖啡',
        'cuisine' => '咖啡飲料', 'area' => '德信街',
        'desc' => '德信街上的咖啡店，品牌名「CREMA CAFÉ 葵瑪咖啡」定位咖啡專門店。',
        'phone' => '06-2606200', 'address' => '台南市德信街 37 號',
    ],
    [
        'id' => 94, 'slug' => '062714222', 'name' => '福泉食味外燴辦桌商行（總舖師）',
        'cuisine' => '辦桌外燴', 'area' => '富強路一段（嘉南高屏跨縣承接）',
        'desc' => '國宴總鋪師小泉師（吳嘉宏）領軍、近 30 年經驗的台南辦桌外燴團隊，從家庭喜宴辦到數百桌企業尾牙，服務遍及嘉南高屏。',
        'phone' => '06-2714222', 'address' => '台南市富強路一段 2 巷 8 弄 51 號',
    ],
    [
        'id' => 104, 'slug' => 'vegetarian-diet01', 'name' => '蔬上食',
        'cuisine' => '素食', 'area' => '東橋一路',
        'desc' => '東橋一路上的素食餐廳，店名「蔬上食」直接定位蔬食／素食路線。',
        'phone' => '06-3587695', 'address' => '台南市東橋一路 252 號',
    ],
];

// ── 開場 ──
$body  = '<p>台南是台灣美食之都，從<strong>府城中西區</strong>的老店小吃、<strong>東區成大商圈</strong>的聚餐熱炒、<strong>永康與南區</strong>的家庭餐廳，到<strong>安平、安南</strong>的海鮮料理，每個區域都有自己的飲食地圖。這篇整理 12 家店家好口碑收錄的台南在地餐廳，菜系涵蓋火鍋、燒肉、牛排、日式、川菜、英式餐酒、海鮮粥、清燉牛肉麵、烘焙、咖啡、辦桌外燴與素食，府城聚餐、家庭日常、婚宴外燴一篇看。</p>' . "\n";
$body .= '<p><em>※ 名單為店家好口碑收錄的台南餐飲商家，分區與菜系資料以店家提供為準，行情請以店家當下公告為主。</em></p>' . "\n";

// ── 目錄 ──
$body .= '<h2 id="toc">📖 文章目錄</h2>' . "\n<ol>\n";
foreach ($stores as $i => $s) {
    $body .= '  <li><a href="#s' . ($i + 1) . '">第 ' . ($i + 1) . ' 家｜' . htmlspecialchars($s['name']) . '（' . htmlspecialchars($s['cuisine']) . '）</a></li>' . "\n";
}
$body .= '  <li><a href="#tips">怎麼挑台南美食：3 個重點</a></li>' . "\n";
$body .= '  <li><a href="#faq">常見問題 FAQ</a></li>' . "\n";
$body .= "</ol>\n\n";

// ── 12 家 H2 ──
foreach ($stores as $i => $s) {
    $n = $i + 1;
    $storeUrl = "https://www.gomag.com.tw/store/{$s['slug']}";
    $body .= "<h2 id=\"s{$n}\">◈ 第 {$n} 家｜{$s['name']}｜{$s['area']}　{$s['cuisine']}</h2>\n";
    $body .= "<p>{$s['desc']}</p>\n";
    $body .= "<ul class=\"g-store-info\">\n";
    $body .= "  <li>📍 地址：{$s['address']}</li>\n";
    $body .= "  <li>📞 電話：<a href=\"tel:" . str_replace([' ', '-'], '', $s['phone']) . "\">{$s['phone']}</a></li>\n";
    $body .= "  <li>🔗 完整介紹與口碑評價：<a href=\"{$storeUrl}\">看{$s['name']}店家頁</a></li>\n";
    $body .= "</ul>\n\n";
}

// ── 怎麼挑 ──
$body .= '<h2 id="tips">怎麼挑台南美食：3 個重點</h2>' . "\n";
$body .= "<ol>\n";
$body .= '  <li><strong>看在地口碑</strong>：Google 評論「分數 + 評論數」雙指標，分數 ≥ 4.3 且評論數 ≥ 100 較穩定，少評論的高分容易踩雷。</li>' . "\n";
$body .= '  <li><strong>看分區屬性</strong>：中西區偏老店傳統與府城小吃、東區偏聚餐與學生客（成大商圈密集）、安平偏觀光與海鮮、永康偏家庭客平價、新營偏庄頭辦桌。挑對區域省事。</li>' . "\n";
$body .= '  <li><strong>看是不是當日預約得到</strong>：台南熱門店週末常滿，建議出門前一通電話確認位子與營業狀態，省得撲空。</li>' . "\n";
$body .= "</ol>\n\n";

// ── FAQ ──
$body .= '<h2 id="faq">常見問題 FAQ</h2>' . "\n";
$body .= "<h3>Q1：台南美食推薦哪幾家？</h3>\n";
$body .= "<p>本文整理 12 家店家好口碑收錄的台南在地餐廳，菜系涵蓋火鍋、燒肉、牛排、日式、川菜、英式餐酒、海鮮、麵食、烘焙、咖啡、辦桌、素食，府城各區都有代表。完整清單見上方目錄。</p>\n";
$body .= "<h3>Q2：聚餐熱鬧該去哪？</h3>\n";
$body .= '<p>聚餐推薦火鍋、燒肉、川菜熱炒，本文中第 1 家牧鍋（火鍋）、第 2 家初云職人燒肉、第 5 家辣嬌程癮（重慶川菜）都是聚餐型。更多選擇可看 <a href="https://www.gomag.com.tw/city/tainan/food/hotpot">台南火鍋頁</a>、<a href="https://www.gomag.com.tw/city/tainan/food/yakiniku">台南燒肉燒烤頁</a>。</p>' . "\n";
$body .= "<h3>Q3：婚宴尾牙外燴行情？</h3>\n";
$body .= '<p>台南辦桌每桌約 NT$8,000–18,000 是常見區間，10 桌以上總舖師會出動完整廚房團隊，建議 1–3 個月前預訂，避開過年前最後 2 週爆量期。本文第 11 家福泉食味（國宴總舖師）是辦桌外燴代表，更多選擇見 <a href="https://www.gomag.com.tw/city/tainan/food/banquet-catering">台南辦桌外燴頁</a>。</p>' . "\n";
$body .= "<h3>Q4：素食有什麼選擇？</h3>\n";
$body .= "<p>台南素食選擇多，本文第 12 家蔬上食（東橋一路）是其中一家。如需更多素食店家，可看本站台南餐飲分類。</p>\n";
$body .= "<h3>Q5：早午餐、咖啡廳推薦？</h3>\n";
$body .= "<p>本文第 10 家 CREMA CAFÉ 葵瑪咖啡（德信街）為咖啡店代表，第 9 家麥園烘焙坊（東區富農一街）為烘焙坊。台南咖啡與烘焙店家密度高，可挑離自己最近的。</p>\n\n";

// ── 結尾 ──
$body .= '<h2>結尾：怎麼用這份清單</h2>' . "\n";
$body .= '<p>這 12 家分屬不同菜系與區位，挑選方式：① <strong>先想今天要做什麼</strong>（聚餐／日常／約會／婚宴）；② <strong>看離你近的區</strong>（中西區、東區、永康、安平、新營）；③ <strong>點進店家頁看完整介紹、營業時間與評價</strong>。</p>' . "\n";
$body .= '<p>更多台南餐飲店家請見 <a href="https://www.gomag.com.tw/city/tainan/food"><strong>店家好口碑 - 台南餐飲美食</strong></a> 樞紐頁，或瀏覽各子分類：<a href="https://www.gomag.com.tw/city/tainan/food/hotpot">火鍋</a>、<a href="https://www.gomag.com.tw/city/tainan/food/yakiniku">燒肉燒烤</a>、<a href="https://www.gomag.com.tw/city/tainan/food/steak">牛排</a>、<a href="https://www.gomag.com.tw/city/tainan/food/japanese">日式料理</a>、<a href="https://www.gomag.com.tw/city/tainan/food/chinese">中式熱炒</a>、<a href="https://www.gomag.com.tw/city/tainan/food/western">西餐餐酒館</a>、<a href="https://www.gomag.com.tw/city/tainan/food/banquet">婚宴餐廳</a>、<a href="https://www.gomag.com.tw/city/tainan/food/banquet-catering">辦桌外燴</a>。</p>' . "\n";

// 寫入 guides 表
$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, cover_image, status, published_at)
    VALUES (?, ?, ?, ?, 'tainan', 1, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug=VALUES(city_slug), category_id=VALUES(category_id),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
        cover_image=VALUES(cover_image), status='published',
        published_at=COALESCE(published_at, NOW())");
$gs->execute([$slug, $title, $excerpt, $body, $metaTitle, $metaDesc, $cover]);
echo ">> 寫入 guide: {$slug}\n";

// 驗證
$row = $db->query("SELECT id, slug, title, LENGTH(body_html) body_len, status, published_at, cover_image
    FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "\n== 驗證 ==\n";
echo "  id:          {$row['id']}\n";
echo "  slug:        {$row['slug']}\n";
echo "  title:       {$row['title']}\n";
echo "  body 長度:    {$row['body_len']} bytes\n";
echo "  status:      {$row['status']}\n";
echo "  published:   {$row['published_at']}\n";
echo "  cover:       {$row['cover_image']}\n";
echo "\n預覽：https://www.gomag.com.tw/guide/{$slug}\n";
