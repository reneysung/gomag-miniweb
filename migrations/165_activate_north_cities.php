<?php
/**
 * 165_activate_north_cities.php
 * 啟用北部三城（台北／新北／桃園）＋補城市文案（2026-07-15 城市健檢第二波）
 *
 * 背景：三城各有 1 家真客戶 + 5 家旅宿陪襯店（基本資訊+官網照規格，Reney 已核可門面），
 *       頁面與 sitemap 一直是活的，卻因 is_active=0 不在 /city 總覽 → 內鏈孤立。
 *       Reney 2026-07-15 拍板「北部三城要開」。
 *
 * 內容：is_active=1 + tagline/intro/meta（原本全空）。文案照站上既有口吻
 *       （地理錨定、平實、「陸續收錄」不誇大店數），不捏造行情與數字。
 * 冪等：文案只在原值為空時寫入，不覆蓋日後後台的人工編輯。
 *
 * 跑法：HTTP_HOST=www.gomag.com.tw php migrations/165_activate_north_cities.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cities = [
    'taipei' => [
        'tagline'    => '台北在地店家口碑，從生活服務到旅宿一次看。',
        'intro'      => '<p>台北生活圈密度高、選擇多，反而更需要口碑篩選。從中山、內湖的旅宿，到大安、信義的生活服務，我們陸續收錄台北的在地店家，附真實資訊與聯絡方式，方便你比較後再決定。</p>',
        'meta_title' => '台北在地店家推薦｜店家好口碑',
        'meta_desc'  => '台北市在地店家推薦：旅宿住宿、生活服務等口碑商家，收錄中山、信義、內湖、松山等地區，真實店家資訊與聯絡方式。',
    ],
    'newtaipei' => [
        'tagline'    => '新北在地店家口碑——板橋、中和、三重到林口。',
        'intro'      => '<p>新北幅員大、生活圈各自獨立：板橋、中和永和、三重蘆洲、新莊、林口各有生活節奏。跨區找店最怕資訊過時，我們陸續收錄新北在地店家，附完整資訊與聯絡方式，找對區、找對店。</p>',
        'meta_title' => '新北在地店家推薦｜店家好口碑',
        'meta_desc'  => '新北市在地店家推薦：板橋、中和、三重、新莊、林口等地區的旅宿住宿、教育學習等口碑商家，真實店家資訊與聯絡方式。',
    ],
    'taoyuan' => [
        'tagline'    => '桃園在地店家口碑——桃園、中壢雙核心生活圈。',
        'intro'      => '<p>桃園是北台灣成長快速的城市，桃園、中壢雙核心加上青埔、八德、平鎮，新舊生活圈並存。我們陸續收錄桃園在地店家，從專業服務到旅宿住宿，附真實資訊與聯絡方式。</p>',
        'meta_title' => '桃園在地店家推薦｜店家好口碑',
        'meta_desc'  => '桃園市在地店家推薦：桃園區、中壢、青埔、八德等地區的專業服務、旅宿住宿等口碑商家，真實店家資訊與聯絡方式。',
    ],
];

foreach ($cities as $slug => $c) {
    // 文案：僅原值為空時寫入（不覆蓋人工編輯）
    $st = $db->prepare("UPDATE cities SET
        tagline    = IF(COALESCE(tagline,'')='',    :tagline,    tagline),
        intro      = IF(COALESCE(intro,'')='',      :intro,      intro),
        meta_title = IF(COALESCE(meta_title,'')='', :meta_title, meta_title),
        meta_desc  = IF(COALESCE(meta_desc,'')='',  :meta_desc,  meta_desc),
        is_active  = 1
        WHERE slug = :slug");
    $st->execute($c + ['slug' => $slug]);
    echo "✅ {$slug} 啟用＋文案就位\n";
}

echo "\n=== 驗證 ===\n";
foreach ($db->query("SELECT slug, name, is_active, CHAR_LENGTH(COALESCE(tagline,'')) tl, CHAR_LENGTH(COALESCE(intro,'')) il, CHAR_LENGTH(COALESCE(meta_title,'')) mt FROM cities WHERE slug IN ('taipei','newtaipei','taoyuan')") as $r) {
    echo "  {$r['slug']} {$r['name']} active={$r['is_active']} tagline={$r['tl']}字 intro={$r['il']}字 meta_title={$r['mt']}字\n";
}
$n = $db->query("SELECT COUNT(*) FROM cities WHERE is_active=1")->fetchColumn();
echo "  /city 總覽現在會列 {$n} 個城市\n";
