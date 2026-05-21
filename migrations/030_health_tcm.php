<?php
// ============================================================
// Migration 030 — 健康醫療關鍵字池 + 台南中醫診所交叉頁
// ------------------------------------------------------------
// 健康醫療(health)分類建關鍵字池（中醫/皮膚科/醫學美容/醫事檢驗），
// 自動標 health 店家，並開「台南中醫診所」內容頁（真實店家：3 中醫 + 中藥材）。
// 醫療類內容只做選診所/行情/健保自費的消費指南，不做療效宣稱。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/030_health_tcm.php
// 冪等：INSERT IGNORE 關鍵字/標籤；geo 以 (city,cat,service) upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$hid = (int)$db->query("SELECT id FROM categories WHERE slug='health' LIMIT 1")->fetchColumn();
if (!$hid) { echo "找不到 health\n"; exit(1); }

// ── 1. 關鍵字池 ───────────────────────────────────────────
$pool = [
    ['chinese-medicine',  '中醫診所',  '', 10],
    ['dermatology',       '皮膚科',    '', 20],
    ['medical-aesthetics','醫學美容',  '', 30],
    ['lab-test',          '醫事檢驗',  '', 40],
];
$insKw = $db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)");
$n = 0; foreach ($pool as $p) { $insKw->execute([$hid, $p[0], $p[1], $p[2], $p[3]]); $n += $insKw->rowCount(); }
echo "SEED：健康關鍵字池新增 {$n} 筆（共 " . count($pool) . "）\n";
$kwId = [];
foreach ($db->query("SELECT id, slug FROM service_keywords WHERE category_id={$hid}") as $r) $kwId[$r['slug']] = (int)$r['id'];

// ── 2. 自動標 health 店家 ─────────────────────────────────
$rules = [
    'chinese-medicine'   => ['中醫', '中藥', '漢方', '針灸', '推拿'],
    'dermatology'        => ['皮膚科'],
    'medical-aesthetics' => ['醫學美容', '醫美', '醫療美容'],
    'lab-test'           => ['檢驗', '醫事檢驗'],
];
$stores = $db->query("SELECT cl.id, cl.brand_name FROM clients cl JOIN categories c ON cl.category_id=c.id WHERE c.slug='health' AND cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0")->fetchAll();
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

// ── 3. 台南中醫診所內容頁 ─────────────────────────────────
$intro = <<<'HTML'
<p>台南中醫診所選擇多，從健保門診、針灸推拿到自費調理、減重、轉骨都有。看中醫前先分清楚自己的需求——是健保看診（感冒、痠痛）、傷科針灸推拿，還是自費體質調理，再挑診所與醫師。</p>
<p>台南常見的中醫需求大致分四類：</p>
<ul>
<li><strong>健保一般門診</strong>：內科調理、感冒、腸胃、婦科等，部分負擔約 NT$50–150（依診所層級與是否轉診）。</li>
<li><strong>針灸／傷科推拿</strong>：痠痛、運動傷害、落枕，多可健保給付；自費療程每次約 NT$300–1,000。</li>
<li><strong>自費調理</strong>：體質調理、轉骨、坐月子、水煎藥等，依療程與藥材而定。</li>
<li><strong>減重門診</strong>：中醫減重多為自費月費制，約 NT$2,000–5,000／月，依方案，效果因人而異。</li>
</ul>
<p>區域上，中西區、東區、北區、永康診所最密集；挑診所建議找看診細、會把脈問診、衛福部合格立案的醫師，避免誇大療效的話術。</p>
<p>挑台南中醫診所，建議看三件事：①<strong>是否合格立案、醫師資歷</strong>（衛福部可查）；②<strong>健保 vs 自費講清楚</strong>（自費療程、藥材費用先問明）；③<strong>看診細不細、在地口碑</strong>，而非只看廣告。下方為店家好口碑收錄的台南在地中醫診所。</p>
HTML;
$faqs = [
    ['q' => '台南看中醫多少錢？', 'a' => '健保門診部分負擔約 NT$50–150；自費針灸／推拿每次約 NT$300–1,000；自費調理、減重等依療程，減重門診月費約 NT$2,000–5,000。'],
    ['q' => '中醫看診有健保嗎？', 'a' => '多數中醫診所有健保門診（內科、針灸、傷科），部分負擔依層級；自費療程（特殊藥材、減重、調理）另計，看診前先問清楚。'],
    ['q' => '針灸、推拿健保有給付嗎？', 'a' => '多數針灸與傷科推拿可健保給付（有療程次數規範）；自費另有較完整的療程，依診所說明。'],
    ['q' => '中醫減重怎麼算費用？', 'a' => '多為自費月費制（含藥粉／茶飲／針灸），約 NT$2,000–5,000／月，依方案與療程，效果因人而異，先確認內容與費用。'],
    ['q' => '台南哪些區中醫診所最多？', 'a' => '中西區、東區、北區、永康最密集；挑離家近、方便回診的較好。'],
    ['q' => '看中醫要注意什麼？', 'a' => '選衛福部合格立案、醫師資歷清楚的診所；如實告知病史與西藥使用，避免相沖；對誇大「保證療效」的話術保持警覺。'],
    ['q' => '中醫和西醫可以一起看嗎？', 'a' => '可以，但中西藥服用建議間隔，並主動告知雙方醫師目前用藥，避免交互作用。'],
    ['q' => '第一次看中醫要帶什麼？', 'a' => '健保卡、目前服用的藥物清單（或藥袋）、近期檢查報告（如有），方便醫師判斷。'],
    ['q' => '中藥材、水煎藥費用怎麼算？', 'a' => '自費藥材依品項與療程而定，水煎藥通常較科學中藥粉貴；抓藥前先問清楚單價與療程天數。'],
    ['q' => '怎麼挑到看得細的中醫師？', 'a' => '看是否仔細把脈問診、解釋病因與療程、在地口碑與回診評價，避免只憑廣告或誇大療效。'],
];
$mt = '台南中醫診所推薦｜健保門診・針灸推拿・自費調理';
$md = '台南中醫診所怎麼挑？健保門診、針灸推拿、自費調理與減重的行情、健保自費差別與挑診所重點，加上店家好口碑收錄的台南在地中醫診所。';

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES ('tainan', ?, 'chinese-medicine', '中醫診所', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
$up->execute([$hid, $intro, json_encode($faqs, JSON_UNESCAPED_UNICODE), $mt, $md]);
echo "PAGE：台南中醫診所 intro " . mb_strlen(strip_tags($intro)) . " 字、FAQ " . count($faqs) . " 題\n完成。\n";
