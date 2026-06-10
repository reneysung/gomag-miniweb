<?php
// ============================================================
// Migration 114 — 通用攻略《全台辦桌外燴指南 2026》
// ------------------------------------------------------------
// 修補 A4 揭露的 20 個婚宴/辦桌孤立子服務頁（changhua/chiayi/hsinchu/
// kh/taichung 各 4 個 + tainan wedding-banquet/year-end-party）。
//
// 通用攻略一篇打 6 都 22 個 banquet 系列子服務頁的內鏈（含已有內鏈的
// tainan banquet/banquet-catering 也加上做完整 6 都）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/114_taiwan_banquet_guide.php
// 冪等：INSERT ... ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'taiwan-banquet-catering-guide-2026';
$title = '全台辦桌外燴指南 2026：婚宴、喜宴、尾牙、春酒怎麼挑、桌菜行情、6 都比較';
$metaTitle = '全台辦桌外燴指南 2026｜婚宴喜宴尾牙春酒｜桌菜行情 6 都比較｜店家好口碑';
$metaDesc = '辦桌外燴怎麼挑？婚宴、喜宴、入厝、滿月、公司尾牙、春酒、廟會 7 種場合適用，桌菜行情每桌 NT$8,000–25,000 怎麼分，6 都辦桌文化差異與在地總舖師清單一次看。';
$excerpt = '辦桌外燴是台灣特有的婚宴慶典文化，從南部澎湃辦桌到北部精緻路線各有特色。本文整理婚宴、喜宴、尾牙、春酒、入厝、滿月、廟會 7 種場合的桌菜行情、預訂時機、總舖師怎麼挑，並對照 6 都（彰化／嘉義／新竹／台中／台南／高雄）辦桌文化差異。';
$cover = 'https://www.gomag.com.tw/uploads/guides/covers/banquet.svg';

// 6 都辦桌文化角度 + 子服務內鏈
$citiesInfo = [
    'taichung' => [
        'name' => '台中',
        'angle' => '中部最大都會、北屯／南屯／西屯／烏日重劃區戶外婚禮多，南部職人北上承接大型婚宴與企業尾牙常見。潭子加工出口區、神岡科技園區、台中工業區的企業尾牙是 11–1 月旺季主力。',
    ],
    'changhua' => [
        'name' => '彰化',
        'angle' => '保留完整中部辦桌文化，員林是婚宴會館最密集的城市，鹿港、田中、二林、溪湖、北斗、田尾保留庄頭辦桌與廟會流水席。彰濱工業區（鹿港、線西、伸港）、芳苑工業區的尾牙是 11 月起旺季。',
    ],
    'chiayi' => [
        'name' => '嘉義',
        'angle' => '辦桌文化深厚，新港、朴子、民雄、大林、義竹、布袋仍保留完整庄頭辦桌傳統。從喜宴、滿月酒、入厝到神明慶典、廟會流水席都有專業總舖師。耐斯王子、新象園、太保高鐵特區婚宴會館近年增加。',
    ],
    'hsinchu' => [
        'name' => '新竹',
        'angle' => '保留客家庄辦桌文化，湖口、竹東、新埔、北埔、芎林等客家庄頭仍有完整的婚宴、滿月、家族辦桌。竹科是台灣科技業重鎮，竹科尾牙桌數常達 500–700 桌，跨縣總舖師承接需求高。',
    ],
    'tainan' => [
        'name' => '台南',
        'angle' => '府城是台灣辦桌文化重鎮，從府城婚宴、入厝桌菜、滿月酒、神明生到尾牙都是在地人宴客首選。台南辦桌跟餐廳婚宴最大差別是「人情味、菜色澎湃、菜色傳統」，外場服務由總舖師自己團隊負責。新營、後壁、鹽水保留庄頭辦桌。',
    ],
    'kaohsiung' => [
        'name' => '高雄',
        'angle' => '辦桌文化承襲府城傳統、結合港都海鮮優勢，無論婚宴、入厝、神明生或公司尾牙都是高雄人「澎湃實在」的首選。市區辦桌空間較有限，部分總舖師移到鳳山、岡山外圍場地承接大型外燴。亞洲新灣區、左營、巨蛋有現代化婚宴會館。',
    ],
];

$body  = "<p>辦桌外燴是台灣特有的婚宴慶典文化，從南部澎湃辦桌的「人情味、菜色傳統、總舖師親自掌廚」，到北部精緻路線的「飯店宴會廳婚禮、精緻菜餚、SOP 服務」，每個地區都有自己的味道。這篇整理 <strong>7 種辦桌場合</strong>、<strong>桌菜行情區間</strong>、<strong>預訂時機與旺季</strong>、<strong>6 都辦桌文化差異</strong>，協助你挑對總舖師與場合。</p>\n\n";

// 目錄
$body .= "<h2 id=\"toc\">📖 文章目錄</h2>\n<ol>\n";
$body .= "  <li><a href=\"#scenes\">7 種辦桌場合（婚宴／喜宴／入厝／滿月／尾牙／春酒／廟會）</a></li>\n";
$body .= "  <li><a href=\"#price\">桌菜行情：每桌 NT\$8,000–25,000 怎麼分</a></li>\n";
$body .= "  <li><a href=\"#timing\">預訂時機與 4 大旺季</a></li>\n";
$body .= "  <li><a href=\"#cities\">6 都辦桌文化差異（彰化／嘉義／新竹／台中／台南／高雄）</a></li>\n";
$body .= "  <li><a href=\"#choose\">怎麼挑總舖師：5 個重點</a></li>\n";
$body .= "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n";
$body .= "</ol>\n\n";

// 7 種場合
$body .= "<h2 id=\"scenes\">7 種辦桌場合</h2>\n";
$body .= "<ul>\n";
$body .= "  <li><strong>婚宴（傳統辦桌）</strong>：12–30 桌主流、新人主場、菜色 12–14 道、總舖師主導全程，是辦桌最大宗。</li>\n";
$body .= "  <li><strong>喜宴（婚宴會館／飯店）</strong>：餐廳場地、12–20 道精緻菜、適合賓客眾多、外地賓客方便。</li>\n";
$body .= "  <li><strong>入厝</strong>：新家落成宴請親友，8–15 桌、菜色澎湃、家族長輩主場。</li>\n";
$body .= "  <li><strong>滿月／週歲酒</strong>：5–12 桌、家族聚會、菜色含傳統油飯紅蛋。</li>\n";
$body .= "  <li><strong>公司尾牙</strong>：11 月–2 月旺季，10–80 桌不等，工業區常見 30–500 桌大型場。</li>\n";
$body .= "  <li><strong>公司春酒</strong>：1–3 月、開春團拜、常結合摸彩活動。</li>\n";
$body .= "  <li><strong>廟會／神明生／流水席</strong>：傳統宗教場合，不限桌數、開放席桌、桌費由信徒分攤。</li>\n";
$body .= "</ul>\n\n";

// 桌菜行情
$body .= "<h2 id=\"price\">桌菜行情：每桌 NT\$8,000–25,000 怎麼分</h2>\n";
$body .= "<ul>\n";
$body .= "  <li><strong>NT\$8,000–10,000/桌</strong>：基本款桌菜，10–12 道、雞鴨魚肉海鮮齊全，廟會流水席與小型家族宴常用。</li>\n";
$body .= "  <li><strong>NT\$10,000–14,000/桌</strong>：婚宴主流區間，12–14 道、含 1–2 道海鮮主菜（龍蝦／海鮮拼盤），適合 10–30 桌規模。</li>\n";
$body .= "  <li><strong>NT\$14,000–18,000/桌</strong>：精緻路線、含進口食材（澳洲牛、波士頓龍蝦），適合企業尾牙或重要婚宴。</li>\n";
$body .= "  <li><strong>NT\$18,000–25,000/桌</strong>：高端定位、總舖師量身設計、含特殊食材（鮑魚、神戶牛、伊比利豬），市區婚宴會館常見。</li>\n";
$body .= "</ul>\n";
$body .= "<p><strong>含什麼／不含什麼很重要</strong>：是否含碗筷桌椅、紅地毯、舞台燈光、紅包、服務人員、清潔、運費？大部分庄頭辦桌「全包」，部分業者「另計」，下訂前必問清楚。</p>\n\n";

// 預訂時機
$body .= "<h2 id=\"timing\">預訂時機與 4 大旺季</h2>\n";
$body .= "<ul>\n";
$body .= "  <li><strong>婚宴</strong>：建議 3–6 個月前預訂，黃道吉日的好日子要 6–12 個月前定。</li>\n";
$body .= "  <li><strong>企業尾牙</strong>：建議 8–10 月就預訂 11–1 月場次（11 月起爆量、12 月最忙）。</li>\n";
$body .= "  <li><strong>春酒</strong>：1 月起預訂 2–3 月場次。</li>\n";
$body .= "  <li><strong>入厝／滿月</strong>：建議 1–2 個月前預訂，淡季可能 2–4 週也排得到。</li>\n";
$body .= "</ul>\n";
$body .= "<p><strong>4 大旺季排序</strong>：① 11–1 月（尾牙）、② 春節後 2–4 月（春酒＋婚宴）、③ 國定假日週末（婚宴）、④ 黃道吉日（婚宴）。</p>\n\n";

// 6 都對比
$body .= "<h2 id=\"cities\">6 都辦桌文化差異</h2>\n";
$body .= "<p>每個城市的辦桌文化、消費習慣與在地特色不同，挑對地區也影響菜色風格與行情。</p>\n\n";
foreach ($citiesInfo as $citySlug => $info) {
    $body .= "<h3>{$info['name']}</h3>\n";
    $body .= "<p>{$info['angle']}</p>\n";
    $body .= "<p><strong>{$info['name']}在地辦桌相關子服務</strong>：\n";
    $body .= "<a href=\"/city/{$citySlug}/food/banquet\">{$info['name']}婚宴餐廳</a>｜";
    $body .= "<a href=\"/city/{$citySlug}/food/banquet-catering\">{$info['name']}辦桌外燴</a>｜";
    $body .= "<a href=\"/city/{$citySlug}/food/wedding-banquet\">{$info['name']}喜宴婚宴外燴</a>｜";
    $body .= "<a href=\"/city/{$citySlug}/food/year-end-party\">{$info['name']}尾牙春酒外燴</a></p>\n\n";
}

// 怎麼挑
$body .= "<h2 id=\"choose\">怎麼挑總舖師：5 個重點</h2>\n";
$body .= "<ol>\n";
$body .= "  <li><strong>看在地口碑</strong>：請當地長輩或親友推薦、看 Google 評論（≥ 4.3、≥ 30 則較穩）、本站收錄商家清單交叉驗證。</li>\n";
$body .= "  <li><strong>看作品照</strong>：要求看過往婚宴／尾牙的菜色實拍（不是宣傳合成圖）、現場擺設、賓客動線。</li>\n";
$body .= "  <li><strong>看報價單透明度</strong>：每桌菜色明列、含/不含項目寫清楚、餐具桌椅是否含、預付訂金不超過 30%。</li>\n";
$body .= "  <li><strong>看備案能力</strong>：天氣不好（戶外婚宴）、人數臨時增減、菜色微調是否彈性。</li>\n";
$body .= "  <li><strong>看服務範圍</strong>：是否服務你的場地（公部門禮堂、廟埕、社區、戶外）、是否能跨縣承接。</li>\n";
$body .= "</ol>\n\n";

// FAQ
$body .= "<h2 id=\"faq\">常見問題 FAQ</h2>\n";
$body .= "<h3>Q1：辦桌跟婚宴餐廳差在哪？</h3>\n";
$body .= "<p>辦桌＝總舖師外燴到指定場地（自家門前、廟埕、活動中心、戶外），人情味重、菜色澎湃；婚宴餐廳＝飯店或宴會館場地，服務 SOP 化、外地賓客方便。預算差不多，看你要的氛圍。</p>\n";
$body .= "<h3>Q2：戶外婚宴下雨怎麼辦？</h3>\n";
$body .= "<p>要求總舖師提供「雨備方案」（雨棚、室內備案場地），下訂前要寫進合約。台中、台南、嘉義有專門承接戶外婚宴的莊園型場地，多有雨備備案。</p>\n";
$body .= "<h3>Q3：跨縣總舖師可以承接嗎？</h3>\n";
$body .= "<p>可以，但要先確認①是否含遠程運費（10–20 公里內常含、跨縣會加費 NT\$3,000–10,000）、②是否承接你的桌數、③是否在當地有合作場地。彰化、嘉義、台南的總舖師北上承接台中／新竹企業尾牙是常見模式。</p>\n";
$body .= "<h3>Q4：辦桌可以客製菜單嗎？</h3>\n";
$body .= "<p>可以。多數總舖師有「客製選單」服務，可依預算挑 12–14 道、加減特殊食材（龍蝦、鮑魚、神戶牛）。素食桌、清真桌、回族桌都可預訂，下訂時提早講。</p>\n";
$body .= "<h3>Q5：尾牙外燴怎麼避開最爆量期？</h3>\n";
$body .= "<p>避開 12 月中下旬到農曆過年前 2 週，這段全台總舖師最忙。建議：① 10–11 月舉辦（業者剛開季）、② 春酒（1–3 月）替代尾牙（很多企業已改春酒，避免尾牙塞車）、③ 平日場（週一到週四）有折扣空間。</p>\n";
$body .= "<h3>Q6：婚宴桌菜紅包要包多少？</h3>\n";
$body .= "<p>賓客角度：婚宴每桌行情 NT\$10,000–15,000 / 10 人 = 平均每位 NT\$1,000–1,500 起跳，紅包 NT\$2,000（單身）/NT\$3,200（夫妻）是常見區間。台南、嘉義庄頭辦桌偏「人情禮」NT\$1,200–1,600 也接受。</p>\n\n";

// 結尾
$body .= "<h2>結尾：怎麼用這份清單</h2>\n";
$body .= "<p>辦桌外燴是台灣最有人情味的宴客方式，挑對總舖師關鍵在 ①在地口碑、②作品照、③報價透明度、④備案能力、⑤服務範圍。下方按 6 都列出店家好口碑收錄的在地辦桌外燴子分類頁，可直接點進去看在地商家清單。</p>\n";
$body .= "<p>本文未涵蓋的城市子服務頁（如台北／新北／桃園婚宴餐廳）將陸續補上，可常回站關注。</p>\n";

// 寫入
$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, cover_image, status, published_at)
    VALUES (?, ?, ?, ?, NULL, 1, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug=NULL, category_id=VALUES(category_id),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
        cover_image=VALUES(cover_image), status='published',
        published_at=COALESCE(published_at, NOW())");
$gs->execute([$slug, $title, $excerpt, $body, $metaTitle, $metaDesc, $cover]);

$row = $db->query("SELECT id, LENGTH(body_html) body_len FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo ">> 寫入 guide id={$row['id']}, body={$row['body_len']} bytes\n";

// 驗證 20 個孤立婚宴頁的內鏈狀況
echo "\n== 22 個 banquet 系列子服務頁 修補後內鏈數 ==\n";
foreach ($db->query("SELECT city_slug, service_slug FROM geo_category_pages WHERE category_id=1 AND service_slug IN ('banquet','banquet-catering','wedding-banquet','year-end-party') AND is_active=1 ORDER BY city_slug, service_slug") as $r) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$r['city_slug']}/food/{$r['service_slug']}%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %s/%s → %d 篇 guide 內鏈\n", $flag, $r['city_slug'], $r['service_slug'], $n);
}
echo "\n預覽：https://www.gomag.com.tw/guide/{$slug}\n";
