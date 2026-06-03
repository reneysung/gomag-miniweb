<?php
// ============================================================
// Migration 054 — 5 城補裝潢細清 (reno-detail) 子服務頁
// ------------------------------------------------------------
// 北部 4 城 (migration 044) 已有 cleaning + reno-detail。
// 本次補：台中/台南/高雄/嘉義/彰化 各 1 個 reno-detail 子服務頁。
// 目的：給「{城市}裝潢細清」搜尋一個專屬主站 URL 落地（之前只有 cleaning 頁，
// title 沒含「裝潢細清」、Google 匹配不到）。城市在地差異化避 doorway。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/054_reno_detail_5cities.php
// 冪等：geo upsert（unique city+cat+svc）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$h = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

function renoIntro($city, $area, $closing) {
    return "<p>{$city}新成屋與重劃區交屋潮帶動「裝潢後細清」需求。細清跟一般打掃不同——專處理裝潢殘留的粉塵、殘膠、矽利康與建材保護膜，讓新家達到可入住的乾淨度。</p>\n"
      . "<p>{$city}常見的裝潢細清需求：</p>\n<ul>\n"
      . "<li><strong>粉塵全室清運</strong>：櫃內、軌道、燈具、冷氣出風口的裝潢粉塵。</li>\n"
      . "<li><strong>殘膠與矽利康殘漬</strong>：地板、玻璃、衛浴的施工膠殘漬。</li>\n"
      . "<li><strong>建材保護膜撕除</strong>：門框、廚具、衛浴設備的出廠保護膜與殘膠。</li>\n"
      . "<li><strong>五金、玻璃、衛浴除垢</strong>：水龍頭、把手、玻璃水痕、衛浴水泥漬。</li>\n</ul>\n"
      . "<p>行情大致：裝潢後細清每坪約 NT\$130–280，依屋況、建材與粉塵程度浮動（毛胚／全屋較高）。{$area}</p>\n"
      . "<p>挑{$city}裝潢細清，建議看三件事：①<strong>估價單列出含哪些項目</strong>（粉塵／殘膠／矽利康／保護膜／玻璃水痕）；②<strong>不含項目</strong>（外窗、垃圾清運、冷氣是否另計）；③<strong>到府或視訊估價</strong>。{$closing}</p>";
}
function renoFaqs($city, $districts) {
    return [
        ['q'=>"{$city}裝潢後細清每坪多少？", 'a'=>'每坪約 NT$130–280，依屋況、建材與粉塵程度；毛胚或全屋細清較高，建議到府估價。'],
        ['q'=>'裝潢細清和一般打掃差在哪？', 'a'=>'細清處理裝潢殘留的粉塵、殘膠、矽利康、保護膜、玻璃水痕，工序多、與日常打掃不同。'],
        ['q'=>"{$city}哪些區裝潢細清需求最多？", 'a'=>$districts],
        ['q'=>'細清要提前多久預約？', 'a'=>'交屋旺季建議提前 1–2 週；重劃區新案交屋潮集中時更早。'],
        ['q'=>'裝潢細清含哪些項目？', 'a'=>'粉塵清運、殘膠矽利康、保護膜撕除、五金玻璃衛浴除垢；外窗、垃圾清運常另計，報價先確認。'],
        ['q'=>'新成屋一定要做細清嗎？', 'a'=>'建議要。裝潢與施工粉塵、殘膠入住前清乾淨較健康，也保護新建材。'],
        ['q'=>'怎麼確認買到的是細清不是打掃？', 'a'=>'請業者在估價單列出含哪些項目（粉塵／殘膠／保護膜／玻璃水痕），白紙黑字最準。'],
        ['q'=>'細清完不滿意可以補清嗎？', 'a'=>'正規業者多有驗收與補清機制，下訂前確認並把含／不含項目寫進估價單。'],
    ];
}

$cities = [
    'taichung' => [
        'name' => '台中',
        'area' => '台中重劃區建案密集：七期、單元二、北屯機捷特區、烏日高鐵特區、太平與大里都有新成屋交屋潮；老屋翻新後細清需求也穩定。',
        'districts' => '七期、單元二、北屯機捷特區、烏日高鐵、太平、大里；老屋翻新後細清也常見。',
    ],
    'tainan' => [
        'name' => '台南',
        'area' => '台南永康、安平、南科周邊新成屋交屋潮帶動細清需求；府城（中西、北、東區）老屋翻新後的細清也是強項。',
        'districts' => '永康、安平、南科、東區；府城老屋翻新後細清需求高。',
    ],
    'kaohsiung' => [
        'name' => '高雄',
        'area' => '高雄左營、楠梓、鼓山、亞洲新灣區、農16重劃區新成屋交屋潮持續；前鎮與苓雅老公寓翻新後細清也常見。',
        'districts' => '左營、楠梓、鼓山、亞洲新灣區、農16重劃區；前鎮、苓雅老公寓翻新。',
    ],
    'chiayi' => [
        'name' => '嘉義',
        'area' => '嘉義市區與中埔、水上、太保新成屋交屋與老屋翻新後細清需求穩定；嘉義透天厝坪數較大、細清工時較長。',
        'districts' => '嘉義市區、中埔、水上、太保；透天厝細清為大宗。',
    ],
    'changhua' => [
        'name' => '彰化',
        'area' => '彰化市區、員林、和美、鹿港新成屋與透天翻新後的裝潢細清需求；中部建案近年增多，員林捷運話題帶動需求。',
        'districts' => '彰化市區、員林、和美、鹿港；員林近年新案多。',
    ],
];

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, ?, 'reno-detail', '裝潢細清', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");

foreach ($cities as $slug => $c) {
    $city = $c['name'];
    $closing = '歡迎在地優質裝潢細清業者上架，讓更多' . $city . '住戶找到。';
    $up->execute([$slug, $h,
        renoIntro($city, $c['area'], $closing),
        json_encode(renoFaqs($city, $c['districts']), JSON_UNESCAPED_UNICODE),
        "{$city}裝潢細清推薦｜新成屋・重劃區裝潢後細清",
        "{$city}裝潢後細清怎麼找？粉塵、殘膠、保護膜清理的每坪行情與挑選重點，新成屋重劃區交屋必看，加上店家好口碑收錄的{$city}在地細清商家。"]);
    echo "PAGE：{$city}裝潢細清 (reno-detail)\n";
}
echo "完成。\n";
