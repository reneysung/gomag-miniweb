<?php
// ============================================================
// Migration 033 — 驗屋(home-inspection) 關鍵字 + 高雄/台南/台中 三城驗屋頁
// ------------------------------------------------------------
// home-service 池加 home-inspection(驗屋)，標台南 2 家驗屋店；
// 開 台南(2店)/高雄(0)/台中(0) 三頁，各城建案熱區差異化避 doorway。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/033_home_inspection_3cities.php
// 冪等：INSERT IGNORE 關鍵字/標籤；geo 以 (city,cat,service) upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$hid = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
if (!$hid) { echo "找不到 home-service\n"; exit(1); }

// ── 1. 加 home-inspection 關鍵字 ─────────────────────────
$db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)")
   ->execute([$hid, 'home-inspection', '驗屋', '', 150]);
$kwId = (int)$db->query("SELECT id FROM service_keywords WHERE category_id={$hid} AND slug='home-inspection'")->fetchColumn();
echo "KW：home-inspection(驗屋) id={$kwId}\n";

// ── 2. 標台南驗屋店（鼎創科技驗屋、李好房）─────────────
$ins = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
$cnt = 0;
foreach ($db->query("SELECT id FROM clients WHERE is_active=1 AND COALESCE(is_placeholder,0)=0 AND (brand_name LIKE '%驗屋%' OR store_keywords LIKE '%驗屋%' OR tagline LIKE '%驗屋%')") as $r) {
    $ins->execute([(int)$r['id'], $kwId]); $cnt += $ins->rowCount();
}
echo "TAG：標到 {$cnt} 家驗屋店\n";

// ── 3. 三城內容（各城建案熱區差異化）─────────────────────
$checklist = <<<'HTML'
<ul>
<li><strong>抓漏與防水</strong>：窗框、外牆、浴室、陽台滲漏（紅外線熱顯像輔助）。</li>
<li><strong>泥作與磁磚</strong>：地壁磁磚空心、裂縫、洩水坡度。</li>
<li><strong>門窗氣密與五金</strong>：開關順暢、氣密水密、紗窗。</li>
<li><strong>水電與排水</strong>：插座迴路、給排水、糞管測試、弱電。</li>
</ul>
HTML;

function faqs(string $city, string $districts, string $special): array {
    return [
        ['q' => '什麼是驗屋？一定要驗嗎？', 'a' => '驗屋是交屋前請專業第三方逐項檢查、列出瑕疵供建商修繕。非強制，但新成屋瑕疵入住後難舉證，建議交屋前驗。'],
        ['q' => "{$city}驗屋多少錢？", 'a' => '標準／實品屋每戶約 NT$5,000–12,000、毛胚屋 NT$8,000–15,000+（或每坪約 NT$200–400），複驗多另計或打折。'],
        ['q' => '毛胚屋和標準屋驗屋差在哪？', 'a' => '毛胚屋無裝修、可全面檢視結構與管線，項目多、費用較高；標準／實品屋檢查裝修面與設備。'],
        ['q' => '驗屋會檢查哪些？', 'a' => '抓漏防水、磁磚空心裂縫、洩水坡度、門窗氣密、水電迴路、給排水糞管、客變確認等，並出具書面報告。'],
        ['q' => "{$city}哪些區交屋驗屋需求最多？", 'a' => $districts],
        ['q' => '驗出問題怎麼處理？', 'a' => '驗屋報告附照片，於交屋前向建商提出修繕清單，修繕後再複驗確認，避免簽收後難主張。'],
        ['q' => '要不要複驗？', 'a' => '建議要。建商修繕後複驗確認問題解決，多數驗屋方案含一次複驗或另計。'],
        ['q' => '自己驗和找專業差在哪？', 'a' => '專業有儀器（紅外線熱顯像、水分計、氣密測試）與經驗、出具報告較有公信力；自己驗難抓隱蔽瑕疵。'],
        ['q' => '驗屋要多久？', 'a' => '一般一戶約 2–4 小時，視坪數與毛胚／標準；建議交屋前預留時間並提前預約。'],
        ['q' => '怎麼挑驗屋師？', 'a' => "看是否出完整書面報告與照片、儀器與經驗、是否含複驗、收費透明，以及在地口碑。{$special}"],
    ];
}

$pages = [
    'tainan' => [
        'city' => '台南',
        'intro_lead' => '台南新成屋交屋潮持續，永康、安平、南科（台積電擴廠帶動）一帶建案多，交屋前的「驗屋」需求明顯。',
        'area' => '行情大致：標準／實品屋每戶約 NT$5,000–12,000、毛胚屋約 NT$8,000–15,000+（或每坪約 NT$200–400），複驗多另計或打折。永康、安平、南科一帶新案多，交屋旺季建議提前預約驗屋師。',
        'closing' => '下方為店家好口碑收錄的台南在地驗屋服務。',
        'districts' => '永康、安平、南科（台積電擴廠帶動）一帶新案最多；歸仁、仁德、安南也有建案，老屋翻新後也常驗。',
        'special' => '台南近海與老城並存，窗框防水與老屋翻新後的滲漏尤其要留意。',
    ],
    'kaohsiung' => [
        'city' => '高雄',
        'intro_lead' => '高雄重劃區新案爆量，橋頭（台積電設廠）、楠梓（半導體聚落）、巨蛋、左營一帶交屋潮帶動「驗屋」需求大增。',
        'area' => '行情大致：標準／實品屋每戶約 NT$5,000–12,000、毛胚屋約 NT$8,000–15,000+（或每坪約 NT$200–400），複驗多另計或打折。高雄近海，窗框與外牆防水、頂樓滲漏尤其要留意。橋頭、楠梓、巨蛋、左營新案多，交屋旺季建議提前預約。',
        'closing' => '歡迎在地優質驗屋業者上架，讓更多高雄交屋族找到。',
        'districts' => '橋頭（台積電設廠）、楠梓（半導體聚落）、巨蛋、左營、三民重劃區新案爆量，交屋驗屋需求最高。',
        'special' => '高雄近海濕氣重，外牆與頂樓防水、窗框滲漏是檢查重點。',
    ],
    'taichung' => [
        'city' => '台中',
        'intro_lead' => '台中重劃區新成屋交屋潮持續，七期、北屯機捷特區、太平、烏日一帶建案密集，「驗屋」需求大。',
        'area' => '行情大致：標準／實品屋每戶約 NT$5,000–12,000、毛胚屋約 NT$8,000–15,000+（或每坪約 NT$200–400），複驗多另計或打折。台中透天與大樓並存，透天驗屋要含各樓層與頂樓、前後院。七期、北屯、太平新案多，交屋旺季建議提前預約。',
        'closing' => '歡迎在地優質驗屋業者上架，讓更多台中交屋族找到。',
        'districts' => '西屯（七期）、北屯（機捷特區）、南屯、太平、烏日重劃區新成屋交屋潮，驗屋需求最多。',
        'special' => '台中透天比例高，透天驗屋要含各樓層、頂樓加蓋與前後院排水。',
    ],
];

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, ?, 'home-inspection', '驗屋', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");

foreach ($pages as $slug => $p) {
    $intro = "<p>{$p['intro_lead']}驗屋是請專業第三方在交屋前逐項檢查，抓出瑕疵讓建商修繕，避免入住後才發現問題。</p>\n"
           . "<p>{$p['city']}驗屋常見的檢查重點：</p>\n{$checklist}\n"
           . "<p>{$p['area']}</p>\n"
           . "<p>挑{$p['city']}驗屋，建議看三件事：①<strong>是否提供完整書面報告與照片</strong>（供向建商主張修繕）；②<strong>使用的儀器</strong>（紅外線、水分計、氣密測試）與經驗；③<strong>是否含複驗、收費與範圍說清楚</strong>。{$p['closing']}</p>";
    $mt = "{$p['city']}驗屋推薦｜新成屋交屋驗收・抓漏・客變確認";
    $md = "{$p['city']}驗屋怎麼找？新成屋交屋前驗收的檢查項目、行情、報告與挑驗屋師重點，加上店家好口碑收錄的{$p['city']}在地驗屋資訊。";
    $up->execute([$slug, $hid, $intro, json_encode(faqs($p['city'], $p['districts'], $p['special']), JSON_UNESCAPED_UNICODE), $mt, $md]);
    printf("PAGE：%s驗屋 intro %d 字\n", $p['city'], mb_strlen(strip_tags($intro)));
}
echo "完成。\n";
