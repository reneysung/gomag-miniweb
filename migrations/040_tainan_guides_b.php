<?php
// ============================================================
// Migration 040 — 台南在地攻略 3 篇（牛肉湯小吃 / 看中醫 / 沙發床墊）
// ------------------------------------------------------------
// 掛 city=tainan + 對應分類，呼應台南既有子服務頁。府城獨有主題無 doorway。
// 醫療類只做選診所指南、不宣稱療效。行情沿用已核可。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/040_tainan_guides_b.php
// 冪等：以 slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();
$healthId = (int)$db->query("SELECT id FROM categories WHERE slug='health' LIMIT 1")->fetchColumn();

$guides = [];

$guides[] = [
    'slug' => 'tainan-beef-soup-street-food', 'city' => 'tainan', 'cat' => $foodId,
    'title' => '台南牛肉湯、小吃美食指南：在地人怎麼吃',
    'excerpt' => '台南牛肉湯、虱目魚、碗粿怎麼吃才道地？府城在地小吃文化與挑店重點一次看。',
    'meta_title' => '台南牛肉湯、小吃美食指南｜在地人怎麼吃｜店家好口碑',
    'meta_desc' => '台南牛肉湯、虱目魚、碗粿怎麼吃才道地？府城在地小吃文化、牛肉湯挑店與避雷重點一次看。',
    'body' => <<<'HTML'
<p>台南是台灣小吃之都，牛肉湯、虱目魚、碗粿、鱔魚意麵、棺材板…府城美食密度全台數一數二。但觀光客容易踩雷、在地人有自己的吃法。這篇整理台南小吃怎麼吃才道地。</p>
<h3>台南必吃在地小吃</h3>
<ul>
<li><strong>溫體牛肉湯</strong>：台南獨有的早餐文化，當日現宰溫體牛、滾湯沖泡，講究新鮮。中西區、安平、永康都有名店。</li>
<li><strong>虱目魚</strong>：魚肚、魚皮、魚丸湯、鹹粥，台南養殖虱目魚的代表吃法。</li>
<li><strong>碗粿、肉燥飯、鱔魚意麵</strong>：府城經典庶民美食。</li>
<li><strong>牛肉爐、羊肉爐</strong>：冬季在地進補，溫體現宰是特色。</li>
</ul>
<h3>牛肉湯怎麼吃才道地</h3>
<p>在地人多<strong>早上吃</strong>（凌晨現宰最新鮮）；湯頭清甜、肉粉嫩不過老；加薑絲去腥；配肉燥飯。判斷好店看：是否溫體現宰（非冷凍）、湯是否現沖、在地排隊人潮。</p>
<h3>避開觀光雷區</h3>
<p>熱門景點周邊不一定道地；多看在地口碑、營業時間（很多名店只賣早上或賣完就收）、排隊的是否在地人。平日離峰較不用排隊。</p>
<h3>找台南在地餐飲</h3>
<p>除了小吃，台南日式、火鍋、燒肉、牛排、合菜選擇也豐富。可參考本站台南餐飲各子服務頁，對照在地口碑挑店。</p>
HTML,
];

$guides[] = [
    'slug' => 'tainan-tcm-guide', 'city' => 'tainan', 'cat' => $healthId,
    'title' => '台南看中醫調理指南：健保自費、針灸推拿怎麼選診所',
    'excerpt' => '台南看中醫怎麼挑診所？健保門診、針灸推拿、自費調理的差別與挑診所重點，看中醫前必懂。',
    'meta_title' => '台南看中醫調理指南｜健保自費、針灸推拿怎麼選診所｜店家好口碑',
    'meta_desc' => '台南看中醫怎麼挑診所？健保門診、針灸推拿、自費調理差別與挑診所、安全提醒，看中醫前必懂。',
    'body' => <<<'HTML'
<p>台南中醫診所多，從健保門診、針灸推拿到自費調理都有。但第一次看中醫的人常不知道怎麼挑診所、健保與自費差在哪。這篇做純消費指南（不涉療效宣稱），幫你看中醫前先有概念。</p>
<h3>中醫看診類型</h3>
<ul>
<li><strong>健保一般門診</strong>：內科、感冒、腸胃、婦科等，部分負擔約 NT$50–150。</li>
<li><strong>針灸／傷科推拿</strong>：痠痛、運動傷害，多可健保給付；自費療程每次約 NT$300–1,000。</li>
<li><strong>自費調理</strong>：體質調理、轉骨、坐月子、水煎藥等，依療程與藥材。</li>
</ul>
<h3>挑診所看什麼</h3>
<ul>
<li><strong>合格立案、醫師資歷</strong>：衛福部可查，避免來路不明。</li>
<li><strong>看診是否仔細</strong>：把脈問診、解釋病因與療程，而非急著推銷自費。</li>
<li><strong>健保 vs 自費講清楚</strong>：自費療程、藥材費用先問明，避免事後爭議。</li>
</ul>
<h3>看中醫的安全提醒</h3>
<p>如實告知病史與正在服用的西藥；中西藥服用建議間隔；對「保證療效」「快速見效」的誇大話術保持警覺。第一次看診帶健保卡、藥物清單、近期檢查報告。</p>
<h3>台南哪裡找中醫</h3>
<p>中西區、東區、北區、永康診所最密集，挑離家近、方便回診的較好。可參考本站台南中醫診所頁，對照在地口碑再決定。</p>
HTML,
];

$guides[] = [
    'slug' => 'tainan-sofa-mattress-custom', 'city' => 'tainan', 'cat' => $homeId,
    'title' => '台南沙發訂製、換皮換布選購指南：手工家具怎麼挑',
    'excerpt' => '台南手工沙發、床墊師傅多，訂製、整新、換皮換布怎麼選材質、抓預算、避免做完不滿意，一次看懂。',
    'meta_title' => '台南沙發訂製、換皮換布選購指南｜手工家具怎麼挑｜店家好口碑',
    'meta_desc' => '台南手工沙發、床墊訂製、整新、換皮換布怎麼選材質、抓預算、避免做完不滿意，一次看懂。',
    'body' => <<<'HTML'
<p>台南有不少手工沙發與床墊的老師傅，訂製、整新、換皮換布都做。但材質、內材、尺寸沒搞清楚，做完不滿意又難退。這篇整理台南沙發床墊選購重點。</p>
<h3>訂製、整新、換套怎麼選</h3>
<ul>
<li><strong>全新訂製</strong>：依空間尺寸與材質訂做，三人座約 NT$15,000–40,000。</li>
<li><strong>整新／換皮換布</strong>：骨架還穩時翻新，比買新省，三人座約 NT$8,000–20,000。</li>
<li><strong>訂製床墊</strong>：依尺寸與軟硬客製，雙人約 NT$10,000–30,000。</li>
<li><strong>泡棉更換</strong>：椅墊塌陷的局部修繕，較經濟。</li>
</ul>
<h3>材質怎麼選</h3>
<ul>
<li><strong>布、皮、貓抓布</strong>：有寵物選貓抓布或耐刮皮；好清潔選皮；觸感透氣選布。</li>
<li><strong>內材</strong>：泡棉密度、彈簧型式（獨立筒／連結式）影響耐用與支撐。</li>
</ul>
<h3>訂製前要做的事</h3>
<p>量好擺放尺寸（含搬入動線、電梯）、想要的軟硬與材質、預算；最好到店摸實品或請師傅到府丈量。把規格（材質、泡棉密度、尺寸、顏色）寫進訂單，付訂前再核對。</p>
<h3>台南怎麼挑</h3>
<p>台南手工家具師傅多在中西區、北區、東區老店。看實際作品、保固（結構／車工常 1–3 年）、是否提供樣品確認。可參考本站台南沙發床墊頁。</p>
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
    printf("UPSERT %-32s body %4d 字\n", $g['slug'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
