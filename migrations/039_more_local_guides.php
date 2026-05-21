<?php
// ============================================================
// Migration 039 — 在地攻略 3 篇（嘉義包膜vs鍍膜 / 高雄聚餐 / 嘉義透天清潔）
// ------------------------------------------------------------
// 各城獨有主題、掛 city + 對應分類，出現在對應子服務頁攻略區。行情沿用已核可。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/039_more_local_guides.php
// 冪等：以 slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();
$autoId = (int)$db->query("SELECT id FROM categories WHERE slug='auto-service' LIMIT 1")->fetchColumn();

$guides = [];

$guides[] = [
    'slug' => 'chiayi-car-wrap-vs-coating', 'city' => 'chiayi', 'cat' => $autoId,
    'title' => '嘉義汽車包膜 vs 鍍膜怎麼選？差別、費用、誰先做一次懂',
    'excerpt' => '包膜和鍍膜常被搞混，一個是貼膜保護、一個是鍍上保護層。差別、費用、該先做哪個，嘉義車主一次看懂。',
    'meta_title' => '嘉義汽車包膜 vs 鍍膜怎麼選？差別費用一次懂｜店家好口碑',
    'meta_desc' => '包膜和鍍膜差在哪？一個貼膜保護、一個鍍保護層。費用、該先做哪個、怎麼搭配，嘉義車主必看。',
    'body' => <<<'HTML'
<p>很多嘉義車主分不清「包膜」和「鍍膜」，以為是同一件事，結果花錯錢。其實兩者目的、工法、費用差很多。這篇一次說清楚，幫你決定該做哪個、怎麼搭配。</p>
<h3>一句話差別</h3>
<p><strong>包膜</strong>＝在車漆上「貼一層膜」（PPF 犀牛皮保護或改色膜變色）；<strong>鍍膜</strong>＝在車漆上「鍍一層保護劑」（增加光澤、潑水、抗氧化）。包膜是實體膜、鍍膜是塗層。</p>
<h3>各自做什麼、費用多少</h3>
<ul>
<li><strong>包膜（PPF 犀牛皮）</strong>：抗刮、抗石擊，保護原漆，全車約 NT$30,000–120,000+。</li>
<li><strong>改色膜</strong>：改變車色不傷原漆，全車約 NT$30,000–80,000。</li>
<li><strong>鍍膜（一般）</strong>：光澤潑水、約一年，NT$3,000–8,000。</li>
<li><strong>陶瓷鍍膜</strong>：硬度高、持久多年，NT$8,000–25,000+。</li>
</ul>
<h3>該先做哪個？</h3>
<p>若兩個都要：<strong>先包膜（PPF）再鍍膜</strong>——膜貼好後在膜上鍍膜，保護更全面、好清潔。只想低成本維持光澤與好洗 → 鍍膜即可；想徹底防刮防石擊或改色 → 包膜。</p>
<h3>嘉義怎麼挑</h3>
<p>嘉義包膜／鍍膜業者多在市區（東區、西區）。看膜種品牌與保固（PPF 泛黃／翹邊、鍍膜持久度）、施工環境（包膜要無塵）、前置處理（鍍膜要洗車去鐵粉、研磨）、在地口碑。可參考本站嘉義汽車包膜與鍍膜頁。</p>
HTML,
];

$guides[] = [
    'slug' => 'kaohsiung-group-dining-guide', 'city' => 'kaohsiung', 'cat' => $foodId,
    'title' => '高雄聚餐餐廳攻略：火鍋、燒肉、海鮮怎麼挑與訂位',
    'excerpt' => '高雄聚餐怎麼挑餐廳？火鍋、燒肉、海鮮、合菜依人數場合怎麼選，巨蛋、瑞豐、漢神商圈聚餐攻略。',
    'meta_title' => '高雄聚餐餐廳攻略｜火鍋燒肉海鮮怎麼挑與訂位｜店家好口碑',
    'meta_desc' => '高雄聚餐怎麼挑餐廳？火鍋、燒肉、海鮮、合菜依人數場合怎麼選，巨蛋瑞豐漢神商圈聚餐攻略。',
    'body' => <<<'HTML'
<p>高雄是聚餐大城，朋友聚會、家庭聚餐、公司尾牙選擇多。但餐廳類型多、商圈分散，怎麼依人數與場合挑對餐廳？這篇整理高雄聚餐攻略。</p>
<h3>依場合選餐廳類型</h3>
<ul>
<li><strong>朋友聚會、想多吃</strong>：火鍋／燒肉吃到飽，每人約 NT$450–900，氣氛熱鬧。</li>
<li><strong>家庭聚餐、長輩</strong>：合菜、海鮮餐廳、宴會餐廳，可坐圓桌、口味大眾。</li>
<li><strong>小酌聊天</strong>：日式居酒屋、餐酒館，每人約 NT$400–1,200。</li>
<li><strong>大型聚餐、尾牙</strong>：宴會餐廳、婚宴會館，可容納多桌。</li>
</ul>
<h3>高雄聚餐商圈</h3>
<p>巨蛋、漢神商圈餐廳密集、停車方便，聚餐首選；瑞豐夜市一帶燒肉、串燒、平價聚餐多；左營、楠梓新興餐廳與吃到飽多；鹽埕、鼓山有海鮮與老字號。高雄近海，海鮮餐廳是聚餐特色。</p>
<h3>訂位與省錢眉角</h3>
<p>吃到飽與人氣店週末旺日務必訂位；確認時段限制、低消、加價升級規則；多人聚餐先問可否包廂、是否收服務費。</p>
<h3>怎麼挑不踩雷</h3>
<p>看在地口碑與餐點實拍、確認價位與低消、避免只看裝潢。可參考本站高雄火鍋、燒肉、日式料理等餐飲頁，對照評價再決定。</p>
HTML,
];

$guides[] = [
    'slug' => 'chiayi-townhouse-cleaning-guide', 'city' => 'chiayi', 'cat' => $homeId,
    'title' => '嘉義透天、老屋清潔指南：樓層、頂樓、前後院怎麼算',
    'excerpt' => '嘉義透天與老宅多，清潔怎麼算、含不含樓梯頂樓前後院、年節大掃除怎麼預約，一篇看懂。',
    'meta_title' => '嘉義透天、老屋清潔指南｜樓層頂樓前後院怎麼算｜店家好口碑',
    'meta_desc' => '嘉義透天與老宅清潔怎麼算、含不含樓梯頂樓前後院、老屋清潔注意、年節大掃除怎麼預約，一篇看懂。',
    'body' => <<<'HTML'
<p>嘉義以透天厝與老宅為主，清潔需求跟大樓不太一樣——樓層多、常有頂樓加蓋與前後院，計價與工時都受影響。這篇整理嘉義透天老屋清潔該注意的重點。</p>
<h3>透天清潔怎麼算</h3>
<ul>
<li>多按<strong>樓層或坪數</strong>計，是否含<strong>樓梯、頂樓、前後院</strong>會影響報價，預約時先講清楚。</li>
<li>定期清潔每次約 NT$1,800–2,800，透天因樓層多通常較高。</li>
<li>鐘點制約每小時 NT$400–550，適合定點加強。</li>
</ul>
<h3>老屋清潔特別注意</h3>
<ul>
<li>老屋常有<strong>長年油垢、廚衛水垢、壁面問題</strong>，工時較長，建議找有老屋經驗的師傅。</li>
<li>木造、磨石子等老建材清潔方式不同，先告知屋況。</li>
<li>頂樓、外牆、水塔清潔需高處作業，確認是否含、是否加價。</li>
</ul>
<h3>年節大掃除預約</h3>
<p>嘉義清潔業者較六都少，年前是旺季、調度緊，建議提前 2–4 週預約。確認重點區域（廚房油污、浴室水垢、神明廳）讓報價更準。</p>
<h3>嘉義怎麼挑清潔</h3>
<p>嘉義圈子小、口碑重要，找做得久、有透天／老屋經驗的在地師傅；報價含／不含項目（外窗、垃圾清運、高處）寫清楚。可參考本站嘉義清潔頁。</p>
HTML,
];

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :city, :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug=VALUES(city_slug), category_id=VALUES(category_id), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
foreach ($guides as $g) {
    $stmt->execute([':slug'=>$g['slug'], ':title'=>$g['title'], ':excerpt'=>$g['excerpt'], ':body'=>$g['body'], ':city'=>$g['city'], ':cat'=>$g['cat'], ':mt'=>$g['meta_title'], ':md'=>$g['meta_desc']]);
    printf("UPSERT %-34s (%s) body %4d 字\n", $g['slug'], $g['city'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
