<?php
// ============================================================
// Migration 032 — 汽車服務關鍵字池 + 嘉義汽車包膜/鍍膜交叉頁
// ------------------------------------------------------------
// auto-service 分類建關鍵字池（包膜/鍍膜/汽車美容/保養維修/音響/大燈/駕訓），
// 自動標 20 家，開「嘉義汽車包膜」（1 店）與「嘉義汽車鍍膜」（0 店，內容撐）兩頁。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/032_auto_chiayi_wrap_coating.php
// 冪等：INSERT IGNORE 關鍵字/標籤；geo 以 (city,cat,service) upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$aid = (int)$db->query("SELECT id FROM categories WHERE slug='auto-service' LIMIT 1")->fetchColumn();
if (!$aid) { echo "找不到 auto-service\n"; exit(1); }

// ── 1. 關鍵字池 ───────────────────────────────────────────
$pool = [
    ['car-wrap',       '汽車包膜', '', 10],
    ['coating',        '汽車鍍膜', '', 20],
    ['detailing',      '汽車美容', '', 30],
    ['repair',         '汽車保養維修', '', 40],
    ['car-audio',      '汽車音響', '', 50],
    ['headlight',      '汽車大燈', '', 60],
    ['driving-school', '駕訓班',   '', 70],
];
$insKw = $db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)");
$n = 0; foreach ($pool as $p) { $insKw->execute([$aid, $p[0], $p[1], $p[2], $p[3]]); $n += $insKw->rowCount(); }
echo "SEED：汽車關鍵字池新增 {$n} 筆（共 " . count($pool) . "）\n";
$kwId = [];
foreach ($db->query("SELECT id, slug FROM service_keywords WHERE category_id={$aid}") as $r) $kwId[$r['slug']] = (int)$r['id'];

// ── 2. 自動標 ─────────────────────────────────────────────
$rules = [
    'car-wrap'       => ['包膜', '犀牛皮', 'PPF', '改色膜'],
    'coating'        => ['鍍膜'],
    'detailing'      => ['美容', '美研', '美學', '車體工藝', '漆面校正', '拋光', '車體美'],
    'repair'         => ['保養廠', '車業', '修車', '維修', '驗車', '車庫', '保養'],
    'car-audio'      => ['音響'],
    'headlight'      => ['大燈'],
    'driving-school' => ['駕訓'],
];
$stores = $db->query("SELECT cl.id, cl.brand_name FROM clients cl JOIN categories c ON cl.category_id=c.id WHERE c.slug='auto-service' AND cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0")->fetchAll();
$insTag = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
$tagged = 0; $tagCount = 0; $untagged = [];
foreach ($stores as $s) {
    $name = (string)$s['brand_name']; $hit = [];
    foreach ($rules as $kw => $needles) foreach ($needles as $nd) if (mb_stripos($name, $nd) !== false) { $hit[$kw] = true; break; }
    if (!$hit) { $untagged[] = $s['id'] . ' ' . mb_substr($name, 0, 16); continue; }
    foreach (array_keys($hit) as $kw) { if (isset($kwId[$kw])) { $insTag->execute([(int)$s['id'], $kwId[$kw]]); $tagCount++; } }
    $tagged++;
}
echo "AUTO-TAG：{$tagged}/" . count($stores) . " 家標到（{$tagCount} 標籤），未命中 " . count($untagged) . "：\n";
foreach ($untagged as $u) echo "    · {$u}\n";

// ── 3. 兩頁內容 ───────────────────────────────────────────
$pages = [];
$pages[] = [
    'svc' => 'car-wrap', 'name' => '汽車包膜',
    'mt' => '嘉義汽車包膜推薦｜PPF 犀牛皮・改色膜・局部包膜',
    'md' => '嘉義汽車包膜怎麼選？PPF 犀牛皮漆面保護、改色膜、局部包膜的行情、膜種差別與挑店重點，加上店家好口碑收錄的嘉義在地汽車包膜店。',
    'intro' => <<<'HTML'
<p>嘉義汽車包膜選擇雖不像六都多，但需求穩定，從 PPF 犀牛皮（漆面保護）、改色膜到局部包膜都有。包膜「膜種」與「施工範圍」差很大，先想好要的是保護漆面、改變車色還是局部防刮，再比價。</p>
<p>嘉義常見的汽車包膜需求大致分四類：</p>
<ul>
<li><strong>PPF 犀牛皮（透明保護膜）</strong>：保護原廠漆面、抗刮抗石擊，全車約 NT$30,000–120,000+（依膜種與全車／半車）。</li>
<li><strong>改色膜（卡夢／消光／亮面）</strong>：改變車色不傷原漆，全車約 NT$30,000–80,000。</li>
<li><strong>局部包膜</strong>：引擎蓋、後視鏡、車頂、保桿等局部，依面積數千至數萬。</li>
<li><strong>犀牛皮局部（易刮部位）</strong>：A 柱、門把、車門下緣等，較全車經濟。</li>
</ul>
<p>嘉義包膜業者多集中市區（東區、西區），施工需專業無塵環境與時間（全車數天）。包膜前建議先洗車、評估漆面，確認膜種品牌與保固。</p>
<p>挑嘉義汽車包膜，建議看三件事：①<strong>膜種與品牌</strong>（PPF／改色膜等級、原廠保固年限）；②<strong>施工環境</strong>（無塵、技師經驗）與工時；③<strong>保固範圍</strong>（泛黃、翹邊、起泡是否保固）。下方為店家好口碑收錄的嘉義在地汽車包膜店。</p>
HTML,
    'faqs' => [
        ['q' => '嘉義汽車包膜多少錢？', 'a' => 'PPF 犀牛皮全車約 NT$30,000–120,000+、改色膜全車 NT$30,000–80,000、局部包膜數千至數萬，依膜種品牌與範圍。'],
        ['q' => 'PPF 犀牛皮和改色膜差在哪？', 'a' => 'PPF（透明）保護原漆、抗刮抗石擊；改色膜改變車色（消光／卡夢／亮面）不傷原漆。目的不同。'],
        ['q' => '全車包膜還是局部包膜？', 'a' => '重防護選全車或易刮部位（A 柱、車門下緣、引擎蓋）局部；改色多全車。預算與需求決定。'],
        ['q' => '包膜會傷原廠漆嗎？', 'a' => '正規膜材與施工不傷原漆，撕除後多可復原；劣質膜或施工不當才有殘膠風險，選有品牌與保固的。'],
        ['q' => '包膜施工要多久？', 'a' => '全車 PPF／改色膜常需 3–5 天（需無塵環境、細修），局部 1 天內，依範圍。'],
        ['q' => '包膜保固多久？', 'a' => 'PPF 原廠膜常見 5–10 年（泛黃、龜裂保固），改色膜依品牌；確認泛黃、翹邊、起泡是否在保固範圍。'],
        ['q' => '包膜前要先做什麼？', 'a' => '建議先洗車、評估漆面（必要時先處理刮痕），無塵環境施工品質較好。'],
        ['q' => '嘉義哪裡找汽車包膜？', 'a' => '嘉義市區（東區、西區）為主；選有無塵施工環境、技師經驗與品牌保固的店。'],
        ['q' => '包膜和鍍膜可以一起做嗎？', 'a' => '可以，常見先包膜（PPF）再鍍膜，或局部包膜＋全車鍍膜搭配，依需求與預算規劃。'],
        ['q' => '怎麼挑包膜店？', 'a' => '看膜種品牌與保固、施工環境（無塵）、實際案例與在地口碑，避免只比價格。'],
    ],
];
$pages[] = [
    'svc' => 'coating', 'name' => '汽車鍍膜',
    'mt' => '嘉義汽車鍍膜推薦｜陶瓷鍍膜・玻璃鍍膜・鍍膜保養',
    'md' => '嘉義汽車鍍膜怎麼選？一般鍍膜、陶瓷鍍膜、玻璃鍍膜的行情、持久度與前置處理重點，加上店家好口碑收錄的嘉義在地汽車鍍膜資訊。',
    'intro' => <<<'HTML'
<p>嘉義汽車鍍膜需求穩定，從一般鍍膜、陶瓷鍍膜到鍍膜保養都有。鍍膜「等級」與「持久度」差很大，先想好要的是基礎光澤保護還是長效陶瓷鍍膜，再比價。</p>
<p>嘉義常見的汽車鍍膜需求大致分四類：</p>
<ul>
<li><strong>一般鍍膜（一年期）</strong>：基礎光澤與潑水保護，約 NT$3,000–8,000。</li>
<li><strong>陶瓷鍍膜（多年期）</strong>：硬度高、持久、抗氧化，約 NT$8,000–25,000+。</li>
<li><strong>玻璃鍍膜</strong>：擋風玻璃撥水、提升雨天視線，約 NT$1,500–4,000。</li>
<li><strong>鍍膜保養（定期回廠）</strong>：維持鍍膜效果的洗車保養，依方案。</li>
</ul>
<p>鍍膜前需徹底洗車、去鐵粉，漆面有細紋或氧化時建議先研磨拋光（漆面校正）再上鍍膜，效果與持久度才好。嘉義市區汽車美容／車體店多兼做鍍膜。</p>
<p>挑嘉義汽車鍍膜，建議看三件事：①<strong>鍍膜等級與持久度</strong>（一年 vs 陶瓷多年）；②<strong>前置處理</strong>（是否含洗車去鐵粉、研磨拋光）；③<strong>保固與回廠保養</strong>。歡迎在地優質汽車鍍膜業者上架，讓更多嘉義車主找到。</p>
HTML,
    'faqs' => [
        ['q' => '嘉義汽車鍍膜多少錢？', 'a' => '一般鍍膜（一年期）約 NT$3,000–8,000、陶瓷鍍膜（多年期）NT$8,000–25,000+、玻璃鍍膜 NT$1,500–4,000，依等級與前置處理。'],
        ['q' => '一般鍍膜和陶瓷鍍膜差在哪？', 'a' => '一般鍍膜光澤與潑水、約一年；陶瓷鍍膜硬度高、抗氧化、持久多年，價格也高。'],
        ['q' => '鍍膜前要研磨拋光嗎？', 'a' => '漆面有細紋、氧化建議先研磨拋光（漆面校正）再鍍膜，效果與持久度較好；新車漆況好可省。'],
        ['q' => '鍍膜能取代打蠟嗎？', 'a' => '鍍膜持久度遠優於打蠟、保護更好；但仍需定期洗車保養維持效果。'],
        ['q' => '鍍膜可以撐多久？', 'a' => '一般鍍膜約一年、陶瓷鍍膜視等級 2–5 年，實際依用車環境與保養。'],
        ['q' => '鍍膜後要怎麼保養？', 'a' => '定期正確洗車（避免傷膜的洗劑與工具），部分店家提供回廠保養維持鍍膜效果。'],
        ['q' => '玻璃鍍膜有用嗎？', 'a' => '擋風玻璃撥水可提升雨天視線，約半年至一年；可單做或與車體鍍膜一起。'],
        ['q' => '鍍膜和包膜哪個先做？', 'a' => '若要包膜（PPF），通常先包膜再鍍膜；只做鍍膜則洗車去鐵粉、研磨後直接鍍膜。'],
        ['q' => '嘉義哪裡找汽車鍍膜？', 'a' => '嘉義市區汽車美容／車體店多兼做鍍膜；選含完整前置（洗車去鐵粉、研磨）與回廠保養的店。'],
        ['q' => '怎麼挑鍍膜店？', 'a' => '看鍍膜等級與持久度、前置處理是否完整、保固與回廠保養、在地口碑。'],
    ],
];

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES ('chiayi', ?, ?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
foreach ($pages as $p) {
    $up->execute([$aid, $p['svc'], $p['name'], $p['intro'], json_encode($p['faqs'], JSON_UNESCAPED_UNICODE), $p['mt'], $p['md']]);
    printf("PAGE：嘉義%s intro %d 字、FAQ %d 題\n", $p['name'], mb_strlen(strip_tags($p['intro'])), count($p['faqs']));
}
echo "完成。\n";
