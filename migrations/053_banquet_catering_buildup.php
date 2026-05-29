<?php
// ============================================================
// Migration 053 — 辦桌外燴 子服務頁升級 + 關鍵字擴充 + 台南/高雄 geo + 2 篇攻略
// ------------------------------------------------------------
// 1) service_keywords：banquet-catering 由折進 banquet → 升獨立頁；加同義字
//    master-chef(總舖師)、table-menu(桌菜外燴) 折進 banquet-catering。
// 2) geo_category_pages：台南 + 高雄 各 1 個 辦桌外燴 子服務頁（在地差異化）。
// 3) guides：補高雄辦桌指南 + 1 篇通用「辦桌 vs 餐廳婚宴」對比攻略（台南已有）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/053_banquet_catering_buildup.php
// 冪等：keyword INSERT IGNORE / geo+guide upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$fid = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();

// ── 1. 關鍵字升級 ──
$db->prepare("UPDATE service_keywords SET page_slug='' WHERE category_id=? AND slug='banquet-catering'")->execute([$fid]);
echo "KW：banquet-catering 升獨立頁\n";

$kwUp = $db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active) VALUES (?,?,?,?,?,1)
    ON DUPLICATE KEY UPDATE name=VALUES(name), page_slug=VALUES(page_slug), is_active=1");
$kwUp->execute([$fid, 'master-chef', '總舖師', 'banquet-catering', 50]);
$kwUp->execute([$fid, 'table-menu', '桌菜外燴', 'banquet-catering', 51]);
echo "KW：加 總舖師、桌菜外燴 同義字（折進 banquet-catering）\n";

// ── 2. geo_category_pages 子服務內容 ──
$tnIntro = <<<'HTML'
<p>台南是台灣辦桌文化的重鎮——從府城婚宴、入厝桌菜、滿月酒、神明生到尾牙，總舖師外燴一直是在地人宴客的首選。台南辦桌跟餐廳婚宴最大的差別是「人情味、菜色澎湃、可在自家或廟埕辦」，加上總舖師之鄉的傳承累積，行情也比北部實惠。</p>
<p>台南常見的辦桌情境：</p>
<ul>
<li><strong>婚宴喜慶</strong>：訂婚、結婚、回門，每桌約 NT$8,000–15,000，含海鮮、佛跳牆、烤乳豬等澎湃菜。</li>
<li><strong>入厝／喬遷</strong>：新家完工請親友吃飯，桌數較少（5–10 桌）很常見。</li>
<li><strong>滿月酒／收涎／週歲</strong>：嬰兒禮俗，桌數視親友規模。</li>
<li><strong>神明生／廟會／中元普渡</strong>：廟埕辦桌、犒賞兵將。</li>
<li><strong>尾牙、開幕、慶功</strong>：公司行號宴客，請總舖師到場。</li>
</ul>
<p>台南辦桌每桌（10 人）行情約 NT$6,000–15,000+，依菜色等級、海鮮與特色食材、是否含人力與酒水浮動。旺日（好日子、年底、過年前）總舖師檔期搶手，建議提前 1–3 個月預約。</p>
<p>挑台南總舖師三件事：①實際辦桌案例與在地口碑、②菜色是否現場現做、食材新鮮度、③桌椅碗盤／人力服務／場地清潔是否含。把桌數、菜單、含哪些寫進確認單最保險。</p>
<p>歡迎台南在地優質總舖師、外燴業者上架，讓更多台南家庭找到。</p>
HTML;

$khIntro = <<<'HTML'
<p>高雄辦桌文化承襲府城傳統、結合港都海鮮優勢，無論婚宴、入厝、神明生或公司尾牙，總舖師外燴都是高雄人「澎湃實在」宴客的首選。高雄市區辦桌空間較有限，部分總舖師同時提供「外燴到場」與「合作場地」兩種方案，方便不同需求。</p>
<p>高雄常見的辦桌情境：</p>
<ul>
<li><strong>婚宴喜慶</strong>：訂婚、結婚、回門，每桌約 NT$8,000–15,000，海鮮為主、佛跳牆與烤乳豬等。</li>
<li><strong>入厝／喬遷</strong>：左營、楠梓、鳳山新成屋多，入厝請桌很常見。</li>
<li><strong>滿月酒／週歲</strong>：嬰兒禮俗，可選總舖師外燴或合作餐廳。</li>
<li><strong>神明生／廟會</strong>：廟埕辦桌、宗親宴客。</li>
<li><strong>公司尾牙／開幕</strong>：園區與工廠尾牙旺季 11–2 月。</li>
</ul>
<p>高雄辦桌每桌（10 人）行情約 NT$7,000–15,000+，依菜色等級、海鮮種類與是否含人力與酒水。港都海鮮優勢，海鮮桌相對划算。旺日提前 1–3 個月預約最保險。</p>
<p>挑高雄總舖師三件事：①實際辦桌案例與在地口碑、②菜色是否現場現做、食材新鮮度、③桌椅碗盤、服務人力、場地清潔是否含。先把細節寫進確認單。</p>
<p>歡迎高雄在地優質總舖師、外燴業者上架，讓更多高雄家庭找到。</p>
HTML;

$tnFaqs = [
  ['q'=>'台南辦桌每桌多少錢？','a'=>'依菜色與食材，每桌（10 人）約 NT$6,000–15,000+，婚宴喜慶較高、家庭聚會較實惠。確認是否含人力、餐具、清潔再下訂。'],
  ['q'=>'要提前多久訂台南總舖師？','a'=>'旺日（好日子、年底）建議提前 1–3 個月；一般日子提前 2–4 週較穩。台南總舖師檔期搶手。'],
  ['q'=>'辦桌跟餐廳婚宴有什麼差別？','a'=>'辦桌由總舖師到指定場地現場辦，菜色澎湃、有人情味、單價較實惠；餐廳婚宴則場地舒適、免張羅，但每桌單價較高。'],
  ['q'=>'辦桌可以辦在哪裡？','a'=>'自宅、廟埕、活動中心、社區空地都可以，總舖師會評估場地用水、停車與動線。',],
  ['q'=>'桌菜可以怎麼配？','a'=>'可請總舖師依預算配菜，或指定海鮮、佛跳牆、烤乳豬等澎湃菜。也可以指定要避開的食材（過敏、宗教）。'],
  ['q'=>'怎麼挑台南總舖師？','a'=>'看實際辦桌案例與在地口碑、菜色是否現場現做、食材新鮮度、服務人力是否足夠；菜單、桌數、含哪些項目都寫進確認單。'],
  ['q'=>'尾牙公司辦桌可以嗎？','a'=>'可以，總舖師到公司、廠房、戶外場地都接，配合表演、抽獎時間調整出菜節奏。'],
];
$khFaqs = [
  ['q'=>'高雄辦桌每桌多少錢？','a'=>'每桌（10 人）約 NT$7,000–15,000+，海鮮桌相對划算。確認是否含人力、餐具、清潔再下訂。'],
  ['q'=>'高雄哪些區辦桌最常見？','a'=>'左營、楠梓、鳳山、岡山等住宅與廟宇較多的區，廟會與入厝辦桌常見；市區則多走外燴到場或合作場地。'],
  ['q'=>'高雄總舖師有外燴到場服務嗎？','a'=>'多數有，會自帶炊具、桌椅、餐具與服務人力。市區辦桌空間有限時也有合作場地方案。'],
  ['q'=>'要提前多久訂高雄總舖師？','a'=>'旺日提前 1–3 個月；尾牙旺季（11–2 月）建議更早預約。'],
  ['q'=>'公司尾牙辦桌怎麼配菜色？','a'=>'人數多桌時可請總舖師配澎湃菜＋分桌出菜節奏；配合抽獎、表演調整。'],
  ['q'=>'神明生／廟會辦桌怎麼訂？','a'=>'與廟方確認場地與用水後，提前 1–2 個月聯絡總舖師，菜色多走澎湃實在路線。'],
  ['q'=>'怎麼挑高雄總舖師？','a'=>'看辦桌案例、菜色與食材新鮮度、服務人力與善後清潔，並把細節寫進確認單。'],
];

$gp = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, ?, 'banquet-catering', '辦桌外燴', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
$gp->execute(['tainan', $fid, $tnIntro, json_encode($tnFaqs, JSON_UNESCAPED_UNICODE),
  '台南辦桌外燴推薦｜總舖師婚宴入厝桌菜行情',
  '台南辦桌總舖師外燴怎麼找？婚宴、入厝、神明生、尾牙桌菜行情、挑選重點與訂位時程，加上店家好口碑收錄的台南在地總舖師與外燴業者。']);
$gp->execute(['kaohsiung', $fid, $khIntro, json_encode($khFaqs, JSON_UNESCAPED_UNICODE),
  '高雄辦桌外燴推薦｜總舖師婚宴入厝尾牙桌菜行情',
  '高雄辦桌總舖師外燴怎麼找？婚宴、入厝、神明生、公司尾牙桌菜行情、海鮮桌與挑選重點，加上店家好口碑收錄的高雄在地總舖師。']);
echo "GEO：台南辦桌外燴 + 高雄辦桌外燴\n";

// ── 3. guides ──
$guides = [];
$guides[] = [
  'slug'=>'kaohsiung-banquet-catering-guide','city'=>'kaohsiung','cat'=>$fid,
  'title'=>'高雄辦桌總舖師宴客指南：婚宴、入厝、尾牙桌菜怎麼訂',
  'excerpt'=>'高雄辦桌結合港都海鮮優勢，婚宴、入厝、神明生、公司尾牙怎麼找總舖師、配菜色、抓行情，一篇看懂。',
  'meta_title'=>'高雄辦桌總舖師宴客指南｜婚宴入厝尾牙桌菜｜店家好口碑',
  'meta_desc'=>'高雄辦桌總舖師怎麼找、桌菜怎麼配、海鮮桌行情、婚宴與尾牙旺季預約眉角，港都辦桌一篇搞懂。',
  'body'=> <<<'HTML'
<p>高雄辦桌文化承襲府城、結合港都海鮮優勢，無論婚宴、入厝、神明生或公司尾牙，總舖師外燴都是高雄人「澎湃實在」宴客的首選。這篇說清楚高雄辦桌怎麼訂。</p>
<h3>怎麼訂高雄辦桌</h3>
<ul>
<li><strong>確認日期、地點、桌數</strong>：旺日（好日子、年底）總舖師檔期搶手，提前 1–3 個月聯絡；尾牙旺季（11–2 月）更早。</li>
<li><strong>談菜單與預算</strong>：以「桌」計，可請總舖師依預算與場合配菜（海鮮為主／含佛跳牆與烤乳豬等澎湃菜）。</li>
<li><strong>確認包含項目</strong>：桌椅碗盤、人力服務、場地清潔、是否含酒水飲料先白紙黑字。</li>
</ul>
<h3>高雄辦桌情境</h3>
<ul>
<li><strong>婚宴喜慶</strong>：每桌約 NT$8,000–15,000，海鮮為主、佛跳牆與烤乳豬等澎湃菜。</li>
<li><strong>入厝／喬遷</strong>：左營、楠梓、鳳山新成屋多，入厝請桌很常見。</li>
<li><strong>神明生／廟會</strong>：廟埕辦桌、宗親宴客，菜色澎湃實在。</li>
<li><strong>公司尾牙／開幕</strong>：總舖師到場，配合抽獎、表演調整出菜節奏。</li>
</ul>
<h3>高雄辦桌行情</h3>
<p>每桌（10 人）約 NT$7,000–15,000+，依菜色等級與海鮮種類。港都海鮮優勢，海鮮桌相對划算。是否含服務人力、餐具、清潔、酒水先確認，避免事後追加。</p>
<h3>挑高雄總舖師</h3>
<p>看實際辦桌案例與在地口碑、菜色是否現場現做、食材新鮮度、服務人力是否足夠，並把菜單、桌數、含哪些寫進確認單。可參考本站高雄辦桌外燴頁。</p>
HTML,
];
$guides[] = [
  'slug'=>'banquet-catering-vs-restaurant-guide','city'=>'','cat'=>$fid,
  'title'=>'辦桌 vs 餐廳婚宴怎麼選？人數、預算、場合一次比較',
  'excerpt'=>'婚宴、入厝、尾牙宴客，到底要找辦桌總舖師還是訂餐廳婚宴？人數、預算、場地、菜色、服務一次比一比。',
  'meta_title'=>'辦桌 vs 餐廳婚宴怎麼選｜人數預算場合一次比較｜店家好口碑',
  'meta_desc'=>'辦桌總舖師外燴 vs 餐廳婚宴會館，人數、預算、場地、菜色、服務、適合場合一次對比，宴客前必看。',
  'body'=> <<<'HTML'
<p>要請婚宴、入厝、尾牙桌菜，第一個糾結通常不是「找哪一家」，而是「要找辦桌總舖師到場辦，還是訂餐廳婚宴會館？」這篇把兩者差異說清楚，幫你快速決定。</p>
<h3>核心差別</h3>
<ul>
<li><strong>辦桌（總舖師外燴）</strong>：總舖師到指定場地（自宅、廟埕、活動中心）現場辦，自備炊具、桌椅、人力，現做出菜，氛圍熱鬧、有人情味。</li>
<li><strong>餐廳婚宴</strong>：到婚宴會館或大型餐廳，場地、桌椅、餐具、人力都現成，動線與冷氣舒適。</li>
</ul>
<h3>怎麼選：6 個面向比一比</h3>
<ul>
<li><strong>人數</strong>：30 桌以上多選餐廳；20 桌以內辦桌彈性大。</li>
<li><strong>預算</strong>：辦桌每桌通常較實惠（同等菜色），餐廳婚宴單價較高（含場地）。</li>
<li><strong>場地</strong>：辦桌彈性（自家、廟埕、戶外），餐廳婚宴固定。</li>
<li><strong>菜色</strong>：辦桌菜色澎湃、海鮮與特色菜多；餐廳婚宴菜色精緻、份量適中。</li>
<li><strong>服務</strong>：辦桌人情味重；餐廳婚宴流程順、有禮儀人員與舞台。</li>
<li><strong>場合氛圍</strong>：傳統喜慶／入厝／神明生＝辦桌對味；都會婚禮／公司大型尾牙＝餐廳順手。</li>
</ul>
<h3>什麼情況選辦桌</h3>
<p>傳統喜慶（南部辦桌文化重的地區）、入厝喬遷、神明生／廟會、人情味重的小型宴客、想在自家／廟埕辦、預算想抓緊一點。</p>
<h3>什麼情況選餐廳婚宴</h3>
<p>桌數多（30 桌以上）、想要場地穩定舒適、需要婚宴流程與舞台設備、賓客遠地多怕找路、希望省事免張羅場地。</p>
<h3>決定後該怎麼下手</h3>
<p>不管選哪邊，三件事一定要做：①實際案例與在地口碑、②菜單／含哪些寫進確認單、③旺日提前 1–3 個月預約。可參考本站各城市的辦桌外燴頁與婚宴餐廳頁。</p>
HTML,
];

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :city, :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug=VALUES(city_slug), category_id=VALUES(category_id),
            meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
foreach ($guides as $g) {
    $stmt->execute([':slug'=>$g['slug'],':title'=>$g['title'],':excerpt'=>$g['excerpt'],':body'=>$g['body'],
        ':city'=>$g['city']?:null,':cat'=>$g['cat'],':mt'=>$g['meta_title'],':md'=>$g['meta_desc']]);
    printf("GUIDE：%-44s body %4d 字\n", $g['slug'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
