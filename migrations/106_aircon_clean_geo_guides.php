<?php
// ============================================================
// Migration 106 — 6 都冷氣清洗 aircon-clean geo + 6 篇在地攻略
// ------------------------------------------------------------
// 接 105 keyword 重整。為 aircon-clean(冷氣清洗) 獨立頁建 6 都子服務頁
// + 各城在地差異化攻略。順便補 #186 涼拾冷氣清潔（台南）aircon-clean 標籤。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/106_aircon_clean_geo_guides.php
// 冪等：upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 0. 補 #186 涼拾冷氣清潔 aircon-clean 標籤
$mainId = (int)$db->query("SELECT id FROM service_keywords WHERE category_id=2 AND slug='aircon-clean'")->fetchColumn();
$db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (186, ?)")->execute([$mainId]);
echo "TAG: #186 涼拾冷氣清潔 加 aircon-clean 標籤\n\n";

$cities = [
  'taipei' => [
    'name'=>'台北',
    'areas'=>'信義、大安、松山、中山、內湖、士林等全區，老公寓密集、夏季冷氣使用率全國最高。',
    'special'=>'老公寓無電梯施工費較高、雙北濕熱+空污 PM2.5 滲入冷氣濾網',
    'price_low'=>'1,800–2,800', 'price_high'=>'3,000–5,000',
  ],
  'newtaipei' => [
    'name'=>'新北',
    'areas'=>'板橋、三重、永和、中和、林口、淡水、新莊等全區，重劃區大坪數新成屋與老公寓並存。',
    'special'=>'重劃區大坪數多台冷氣（總價可談）、汐止/烏來山區潮濕黴菌易生',
    'price_low'=>'1,600–2,600', 'price_high'=>'2,800–4,500',
  ],
  'taoyuan' => [
    'name'=>'桃園',
    'areas'=>'桃園區、中壢、青埔、藝文特區、八德等全區，重劃區與工業區並存。',
    'special'=>'機場線與工業區粉塵多、新成屋交屋驗收（建商裝的冷氣多需保養）',
    'price_low'=>'1,500–2,500', 'price_high'=>'2,500–4,200',
  ],
  'taichung' => [
    'name'=>'台中',
    'areas'=>'西屯、北屯、南屯、太平、大里等全區，七期豪宅與重劃區新成屋多。',
    'special'=>'西部夏季悶熱+盆地熱島效應、冷氣負荷高、商辦多',
    'price_low'=>'1,500–2,500', 'price_high'=>'2,500–4,200',
  ],
  'tainan' => [
    'name'=>'台南',
    'areas'=>'中西區、北區、東區、永康、安平、安南等全區，老屋與新成屋並存。',
    'special'=>'南部高溫長夏（4–10 月持續使用）、灰塵多、安平/安南近海鹽分',
    'price_low'=>'1,400–2,400', 'price_high'=>'2,400–4,000',
  ],
  'kaohsiung' => [
    'name'=>'高雄',
    'areas'=>'三民、左營、苓雅、前金、鼓山、楠梓、鳳山等全區，港都濕熱與老公寓密集。',
    'special'=>'港都濕熱年中無休、鼓山/前鎮/小港近海鹽害、冷氣需求最大',
    'price_low'=>'1,400–2,400', 'price_high'=>'2,400–4,000',
  ],
];

function makeIntro($c) {
    return "<p>{$c['name']}冷氣清洗需求集中在{$c['areas']}{$c['special']}，加上濾網與蒸發器累積灰塵、霉斑、PM2.5，冷氣不洗會耗電、出風變弱、甚至吹出異味影響健康。建議每年至少清洗 1–2 次。</p>\n"
      . "<p>{$c['name']}常見的冷氣清洗服務：</p>\n<ul>\n"
      . "<li><strong>分離式冷氣基本清洗</strong>：濾網、面板、出風口清洗，每台約 NT\${$c['price_low']}。</li>\n"
      . "<li><strong>全機拆卸高壓清洗</strong>：徹底清洗蒸發器、風扇、排水管，每台約 NT\${$c['price_high']}。</li>\n"
      . "<li><strong>變頻／吊隱式／商用冷氣清洗</strong>：難度與時間較高，單價較貴。</li>\n"
      . "<li><strong>排水管疏通＋藥水消毒</strong>：解決冷氣漏水與細菌異味。</li>\n</ul>\n"
      . "<p>挑{$c['name']}冷氣清洗師傅三件事：①<strong>拆機保固</strong>（拆裝過程要不要為冷氣損壞負責）；②<strong>清洗方式</strong>（藥水成分、是否會殘留）；③<strong>完工試運轉與報告</strong>（風量、溫度、異味是否改善）。歡迎{$c['name']}在地優質冷氣清洗業者上架。</p>";
}

function makeFaqs($c) {
    return [
        ['q'=>"{$c['name']}冷氣清洗一台多少錢？", 'a'=>"分離式基本清洗約 NT\${$c['price_low']}、全機拆卸高壓清洗約 NT\${$c['price_high']}；變頻、吊隱式、商用會更高。"],
        ['q'=>'多久清一次冷氣？', 'a'=>"住家建議每年 1–2 次（夏季前必做）；過敏體質、養寵物、近灰塵源建議每年 2 次。{$c['name']}使用頻率高、灰塵多更頻繁。"],
        ['q'=>'基本清洗 vs 全機拆洗差別？', 'a'=>'基本清洗只洗濾網、面板、出風口（表面）；全機拆洗會卸下機殼、清洗蒸發器內部與風扇，徹底度高很多、價格約 1.5–2 倍。'],
        ['q'=>"{$c['name']}哪些區清洗需求最大？", 'a'=>$c['areas']],
        ['q'=>'冷氣漏水可以一起處理嗎？', 'a'=>'可以。漏水多是排水管堵塞或排水盤龜裂，清洗時可順便疏通＋消毒，避免細菌孳生與異味。'],
        ['q'=>'拆機清洗會傷冷氣嗎？', 'a'=>'正規業者有 SOP 與保固；下訂前確認師傅有無「拆裝保固」（拆裝過程造成損壞由業者負責）較安心。'],
        ['q'=>'旺季要提前預約嗎？', 'a'=>'要。每年 4–8 月是旺季，越接近夏天越難排，建議 3–4 月就預約。'],
        ['q'=>'自己 DIY 清洗可以嗎？', 'a'=>'濾網拆下沖水、面板擦拭可以；但蒸發器內部與排水盤需要拆機，自己做容易刮傷或漏水，建議交給專業。'],
    ];
}

function makeGuide($c) {
    return "<p>{$c['name']}{$c['special']}，冷氣是家家戶戶夏季的命根子。但冷氣濾網與蒸發器一年沒清，灰塵、霉斑、細菌會累積、耗電、出風變弱、甚至吹出異味。這篇把{$c['name']}冷氣清洗的種類、行情、挑師傅重點與在地眉角說清楚。</p>\n"
      . "<h3>{$c['name']}冷氣清洗 3 種主要方式</h3>\n<ul>\n"
      . "<li><strong>基本清洗（每台約 NT\${$c['price_low']}）</strong>：拆濾網、面板、出風口、用藥水擦拭；維持基本清潔，但內部累積物清不到。</li>\n"
      . "<li><strong>全機拆卸高壓清洗（每台約 NT\${$c['price_high']}）</strong>：卸下機殼、用高壓水柱清洗蒸發器與風扇、疏通排水管，徹底度最高、建議每 1–2 年做 1 次。</li>\n"
      . "<li><strong>變頻／吊隱式／商用冷氣</strong>：拆裝難度高、線路複雜，單價較貴（NT\${$c['price_high']}+ 至更高）；要找有相關證照的師傅。</li>\n</ul>\n"
      . "<h3>{$c['name']}在地要注意</h3>\n<p>{$c['areas']}{$c['special']}。{$c['name']}冷氣使用頻率與條件影響清洗週期：一般家用建議每年 1 次，過敏體質、養寵物、近灰塵源建議每年 2 次（夏季前 + 中段）。</p>\n"
      . "<h3>清洗 SOP 與該注意的細節</h3>\n<ul>\n"
      . "<li><strong>事前溝通</strong>：報價含哪些（藥水、保固、拆裝、現場保護）。</li>\n"
      . "<li><strong>現場保護</strong>：地板鋪墊、家具覆蓋、排水管接妥避免漏水。</li>\n"
      . "<li><strong>藥水成分</strong>：環保／無毒、避免殘留刺鼻味；過敏家庭更要問。</li>\n"
      . "<li><strong>完工試運轉</strong>：風量、溫度、異味改善前後對比；正規業者會附報告。</li>\n</ul>\n"
      . "<h3>挑{$c['name']}冷氣清洗師傅三件事</h3>\n<p>①<strong>拆機保固</strong>（拆裝損壞責任歸誰）、②<strong>清洗方式與藥水</strong>（環保無毒、不殘留）、③<strong>完工試運轉與報告</strong>（風量、溫度數據）。可參考本站{$c['name']}冷氣清洗頁。</p>";
}

$gp = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, 2, 'aircon-clean', '冷氣清洗', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, ?, 2, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug=VALUES(city_slug), category_id=2, meta_title=VALUES(meta_title),
        meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");

foreach ($cities as $slug => $c) {
    $gp->execute([$slug, makeIntro($c), json_encode(makeFaqs($c), JSON_UNESCAPED_UNICODE),
        "{$c['name']}冷氣清洗推薦｜全機拆洗藥水保固行情",
        "{$c['name']}冷氣清洗怎麼找？分離式基本清洗、全機拆卸高壓清洗、變頻/吊隱式行情、藥水成分與保固重點，店家好口碑收錄的{$c['name']}在地冷氣清洗業者。"]);

    $gs->execute([
        "{$slug}-aircon-clean-guide",
        "{$c['name']}冷氣清洗指南：拆機保固、藥水成分、行情怎麼挑",
        "{$c['name']}冷氣清洗一台多少錢？分離式基本清洗 vs 全機拆卸高壓清洗、藥水安全、拆機保固、{$c['name']}在地眉角，一篇看懂。",
        makeGuide($c),
        $slug,
        "{$c['name']}冷氣清洗指南｜全機拆洗行情藥水保固｜店家好口碑",
        "{$c['name']}冷氣清洗怎麼挑？分離式基本清洗、全機拆卸高壓清洗 vs 變頻吊隱式、藥水成分、拆機保固重點與在地清洗週期建議，{$c['name']}住戶必看。",
    ]);
    printf("CITY: %-12s geo + guide\n", $c['name']);
}

echo "\n完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
