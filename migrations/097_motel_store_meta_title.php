<?php
// ============================================================
// Migration 097 — 32 家汽車旅館設 store_meta_title（精準 SEO 標題）
// ------------------------------------------------------------
// 修城市 bug 後，fallback 變成 "{城市市}旅宿住宿店家" 仍籠統。
// 設 store_meta_title = "{brand_name}｜{城市}汽車旅館推薦｜店家好口碑"
// 直接拿「{城市}汽車旅館」這個主關鍵字進 title。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/097_motel_store_meta_title.php
// 冪等：UPDATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cityMap = ['taipei'=>'台北','newtaipei'=>'新北','taoyuan'=>'桃園','taichung'=>'台中','tainan'=>'台南','kaohsiung'=>'高雄'];
$MOTEL_KW = 124;

// 撈所有標 motel 的店家
$rows = $db->query("SELECT DISTINCT c.id, c.brand_name, c.city_slug
    FROM clients c JOIN client_service_keywords csk ON csk.client_id=c.id
    WHERE csk.service_keyword_id={$MOTEL_KW} AND c.is_active=1 AND c.is_placeholder=0
    ORDER BY c.city_slug, c.id")->fetchAll(PDO::FETCH_ASSOC);

$upd = $db->prepare("UPDATE clients SET store_meta_title=?, store_meta_desc=? WHERE id=?");
foreach ($rows as $r) {
    $city = $cityMap[$r['city_slug']] ?? '';
    $title = $r['brand_name'] . '｜' . $city . '汽車旅館推薦｜店家好口碑';
    $desc = $city . '汽車旅館「' . $r['brand_name'] . '」店家資訊、地址、電話、官網訂房連結；商務出差住宿、約會休息、家庭過夜、party 包房都適用。';
    $upd->execute([$title, $desc, $r['id']]);
    echo "  #{$r['id']} → {$title}\n";
}
echo "\n完成。{count($rows)} 家\n";
