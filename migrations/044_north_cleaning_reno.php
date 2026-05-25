<?php
// ============================================================
// Migration 044 — 清潔改名「清潔公司」+ 裝潢細清升獨立頁 + 北部 4 城開頁
// ------------------------------------------------------------
// 1. service_keywords cleaning name 清潔→清潔公司；既有 geo cleaning 列 service_name 同步。
// 2. reno-detail（裝潢細清）page_slug cleaning→''（升成獨立頁關鍵字）。
// 3. 台北/新北/桃園/新竹 各開「清潔公司」(cleaning) + 「裝潢細清」(reno-detail) 內容頁
//    （目前 0 店、內容撐排名，北部都會在地角度，北部行情）。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/045_north_cleaning_reno.php
// 冪等：UPDATE/upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$h = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

// 1. 改名 + reno-detail 升獨立頁
$db->prepare("UPDATE service_keywords SET name='清潔公司' WHERE category_id=? AND slug='cleaning'")->execute([$h]);
$db->prepare("UPDATE service_keywords SET page_slug='' WHERE category_id=? AND slug='reno-detail'")->execute([$h]);
$n1 = $db->prepare("UPDATE geo_category_pages SET service_name='清潔公司' WHERE category_id=? AND service_slug='cleaning'");
$n1->execute([$h]);
echo "KW：cleaning→清潔公司、reno-detail 升獨立頁；既有清潔頁改名 {$n1->rowCount()} 列\n";

// 2. 內容模板
function cleaningIntro($city, $area, $closing) {
    return "<p>{$city}都會大樓與公寓密集、租屋族多，清潔需求從定期到府、搬家退租到專項清洗都有。找清潔公司，先分清楚需求——定期居家清潔、搬家退租大掃除，還是專項清洗，再比價與看在地口碑。</p>\n"
      . "<p>{$city}常見的清潔需求大致分四類：</p>\n<ul>\n"
      . "<li><strong>定期居家清潔</strong>：大樓／公寓每週或雙週到府。每次約 NT\$2,200–4,000（北部都會較高）；鐘點制約每小時 NT\$500–700。</li>\n"
      . "<li><strong>裝潢後細清</strong>：新成屋／重劃區交屋旺季的粉塵、殘膠、保護膜清理（詳見本站{$city}裝潢細清頁）。</li>\n"
      . "<li><strong>搬家／退租大掃除</strong>：一次性深度清潔，約 NT\$4,000 起，依坪數與屋況估。</li>\n"
      . "<li><strong>專項清洗</strong>：分離式冷氣、沙發、地毯清洗等，多單件計價（分離式冷氣每台約 NT\$1,500–2,500）。</li>\n</ul>\n"
      . "<p>{$area}</p>\n"
      . "<p>挑{$city}清潔公司，建議看三件事：①<strong>報價方式</strong>（單次／坪數／鐘點、含不含外窗、垃圾清運、冷氣）；②<strong>是否到府或視訊估價</strong>；③<strong>在地口碑與評價</strong>。{$closing}</p>";
}
function renoIntro($city, $area, $closing) {
    return "<p>{$city}新成屋與重劃區交屋潮帶動「裝潢後細清」需求。細清跟一般打掃不同——處理裝潢殘留的粉塵、殘膠、矽利康與建材保護膜，讓新家達到可入住的乾淨度。</p>\n"
      . "<p>{$city}常見的裝潢細清需求：</p>\n<ul>\n"
      . "<li><strong>粉塵全室清運</strong>：櫃內、軌道、燈具、冷氣出風口的裝潢粉塵。</li>\n"
      . "<li><strong>殘膠與矽利康殘漬</strong>：地板、玻璃、衛浴的施工膠殘漬。</li>\n"
      . "<li><strong>建材保護膜撕除</strong>：門框、廚具、衛浴設備的出廠保護膜與殘膠。</li>\n"
      . "<li><strong>五金、玻璃、衛浴除垢</strong>：水龍頭、把手、玻璃水痕、衛浴水泥漬。</li>\n</ul>\n"
      . "<p>行情大致：裝潢後細清每坪約 NT\$150–300，依屋況、建材與粉塵程度浮動（毛胚／全屋較高）。{$area}</p>\n"
      . "<p>挑{$city}裝潢細清，建議看三件事：①<strong>估價單列出含哪些項目</strong>（粉塵／殘膠／矽利康／保護膜／玻璃水痕）；②<strong>不含項目</strong>（外窗、垃圾清運、冷氣是否另計）；③<strong>到府或視訊估價</strong>。{$closing}</p>";
}
function cleaningFaqs($city, $districts) {
    return [
        ['q'=>"{$city}居家清潔一次多少錢？", 'a'=>'定期清潔每次約 NT$2,200–4,000（北部都會較高）；鐘點制每小時約 NT$500–700，依坪數、屋況與頻率。'],
        ['q'=>'定期清潔和單次大掃除怎麼選？', 'a'=>'想維持日常整潔選定期（每週／雙週）；搬家、退租、年前選單次大掃除。'],
        ['q'=>"{$city}哪些區有到府清潔服務？", 'a'=>$districts],
        ['q'=>'搬家退租清潔怎麼算？', 'a'=>'約 NT$4,000 起，依坪數與屋況；退租建議對照租約點交標準，先請業者列含／不含清單。'],
        ['q'=>'分離式冷氣清洗多少錢？', 'a'=>'一般分離式每台約 NT$1,500–2,500，吊隱式較高；夏季旺季建議提前預約。'],
        ['q'=>'租屋、套房清潔或退租也接嗎？', 'a'=>'多數接，套房多以坪數或件計；畢業季、搬遷旺季建議提前預約。'],
        ['q'=>'大樓社區清潔要注意什麼？', 'a'=>'多數社區對清潔人員出入、電梯使用有規定，預約時先確認管委會規範，避免當天受阻。'],
        ['q'=>'怎麼避免清潔被事後追加費用？', 'a'=>'到府或視訊估價，白紙黑字列含／不含項目（外窗、垃圾清運、冷氣）再下訂。'],
    ];
}
function renoFaqs($city, $districts) {
    return [
        ['q'=>"{$city}裝潢後細清每坪多少？", 'a'=>'每坪約 NT$150–300，依屋況、建材與粉塵程度；毛胚或全屋細清較高，建議到府估價。'],
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
    'taipei' => [
        'name' => '台北市',
        'clean_area' => '信義、大安、中山、松山、內湖、文山、士林、北投到府最密集；老公寓多無電梯、套房租屋退租清潔需求高。',
        'clean_districts' => '信義、大安、中山、松山、內湖、文山、士林、北投最密集；老公寓與租屋退租需求高。',
        'reno_area' => '信義、大安、中山等精華區新案與都更案多；老屋翻新後的細清尤其常見。',
        'reno_districts' => '信義、大安、中山、內湖、文山；都更與老屋翻新後細清最多。',
    ],
    'newtaipei' => [
        'name' => '新北市',
        'clean_area' => '板橋、新莊、中和、永和、三重、新店到府最密集；林口、三峽、淡海重劃區新成屋多。',
        'clean_districts' => '板橋、新莊、中和、永和、三重、新店、土城最密集；林口、三峽、淡水重劃區新案多。',
        'reno_area' => '林口、三峽、淡海、新莊副都心重劃區新成屋交屋潮大；板橋、中和老屋翻新也有。',
        'reno_districts' => '林口、三峽、淡海、新莊副都心新成屋最多；板橋、中和老屋翻新。',
    ],
    'taoyuan' => [
        'name' => '桃園市',
        'clean_area' => '桃園區、中壢、青埔、藝文特區到府最密集；八德、龜山、蘆竹一帶也多。重劃區新案與科技廠換屋潮帶動需求。',
        'clean_districts' => '桃園區、中壢、青埔、藝文特區、八德、龜山、蘆竹。',
        'reno_area' => '青埔（高鐵特區）、藝文特區、中壢、八德重劃區新成屋交屋潮大。',
        'reno_districts' => '青埔（高鐵特區）、藝文特區、中壢、八德、桃園區。',
    ],
    'hsinchu' => [
        'name' => '新竹',
        'clean_area' => '東區（關埔、科學園區周邊）、北區、香山到府最密集；竹科雙薪家庭定期清潔與搬家退租需求高。',
        'clean_districts' => '東區、關埔、北區、香山、科學園區周邊。',
        'reno_area' => '東區關埔重劃區、科學園區周邊新成屋交屋潮持續，高所得科技家庭細清需求高。',
        'reno_districts' => '關埔、東區、科學園區周邊新成屋最多。',
    ],
];

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");

foreach ($cities as $slug => $c) {
    $city = $c['name'];
    $closing = '歡迎在地優質清潔業者上架，讓更多' . $city . '住戶找到。';
    // 清潔公司頁
    $up->execute([$slug, $h, 'cleaning', '清潔公司',
        cleaningIntro($city, $c['clean_area'], $closing),
        json_encode(cleaningFaqs($city, $c['clean_districts']), JSON_UNESCAPED_UNICODE),
        "{$city}清潔公司推薦｜定期居家清潔・搬家退租・專項清洗",
        "{$city}清潔公司怎麼選？定期居家清潔、搬家退租大掃除、冷氣沙發專項清洗的行情與挑選重點，加上店家好口碑收錄的{$city}在地清潔商家。"]);
    // 裝潢細清頁
    $up->execute([$slug, $h, 'reno-detail', '裝潢細清',
        renoIntro($city, $c['reno_area'], $closing),
        json_encode(renoFaqs($city, $c['reno_districts']), JSON_UNESCAPED_UNICODE),
        "{$city}裝潢細清推薦｜新成屋・重劃區裝潢後細清",
        "{$city}裝潢後細清怎麼找？粉塵、殘膠、保護膜清理的每坪行情與挑選重點，新成屋重劃區交屋必看，加上店家好口碑收錄的{$city}在地細清商家。"]);
    printf("PAGE：%s 清潔公司 + 裝潢細清\n", $city);
}
echo "完成。\n";
