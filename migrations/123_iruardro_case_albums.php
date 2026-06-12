<?php
// ============================================================
// Migration 123 — 冷淨億點（#251）施工案例改相簿模式 + 分地區
// ------------------------------------------------------------
// 7 筆單圖案例 → 5 筆相簿案例（每案一個資料夾，case_detail 自動 glob 整本相簿）：
//   before_image / after_image 指到相簿資料夾內的代表圖（亞雷慣例，
//   case_detail.php 用 dirname() 掃同資料夾全部照片 + lightbox）
//   拼圖/純完工照案例 → 只設 after_image（資料夾名 album）
// 地區：location 寫到行政區，模板依「台中／彰化」分組
// 圖片已上傳 uploads/clients/iruardro/cases-albums/（兩 docroot）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/123_iruardro_case_albums.php
// 冪等：先 DELETE 該客戶 cases 再 INSERT
// ============================================================
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cid = (int)$db->query("SELECT id FROM clients WHERE subdomain='iruardro' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ iruardro not found\n"; exit(1); }

$svc = [];
foreach ($db->query("SELECT id, slug FROM services WHERE client_id={$cid}") as $r) $svc[$r['slug']] = (int)$r['id'];

$DIR = 'uploads/clients/iruardro/cases-albums';

// [slug, title, svc_slug, location, description, before_image, after_image, is_featured]
$cases = [
    ['ceiling-shengang', '吊隱式冷氣深層清洗', 'home-aircon', '彰化縣伸港鄉',
     '吊隱式室內機全機拆卸：濾網、風輪、集水盤逐件高壓清洗。清洗前先測溫測風，完工後鰭片煥然一新、再次測溫當面點交，整個過程完整記錄。',
     "$DIR/ceiling-shengang/before/01.jpg", "$DIR/ceiling-shengang/after/01.jpg", 1],
    ['washer-vertical', '直立式洗衣機內槽拆洗', 'washing-machine', '台中市',
     '直立式洗衣機拆開內槽與底盤，把長期累積的污垢黴菌徹底刷洗，內槽亮晶晶，衣物洗得更安心。',
     "$DIR/washer-vertical/before/01.jpg", "$DIR/washer-vertical/after/01.jpg", 1],
    ['cassette-wuqi', '連鎖藥局門市・四方吹冷氣清洗', 'commercial-aircon', '台中市梧棲區',
     '營業場所四方吹（嵌入式）空調定期清洗保養，維持門市空氣品質與顧客體驗，營業時間外施工不影響生意。',
     '', "$DIR/cassette-wuqi/album/01.jpg", 1],
    ['window-west', '透天窗型冷氣全機拆洗（三、四樓臥室）', 'home-aircon', '台中市西區',
     '同戶透天三、四樓共 4 台窗型冷氣同日施工，全機拆卸＋專用無毒藥水＋高壓水槍清洗，每台都有 Before／After 對比紀錄。',
     '', "$DIR/window-west/album/01.jpg", 0],
    ['split-shengang', '透天分離式冷氣全機拆洗（客廳＋辦公室＋臥室）', 'home-aircon', '彰化縣伸港鄉',
     '同戶 4 台分離式室內機同日施工：風輪與鰭片黑垢深層洗淨、濾網外殼逐件清潔，每台都有 Before／After 對比紀錄。',
     '', "$DIR/split-shengang/album/01.jpg", 0],
];

$db->beginTransaction();
try {
    $db->prepare("DELETE FROM cases WHERE client_id=?")->execute([$cid]);
    $ins = $db->prepare("INSERT INTO cases (client_id,service_id,slug,title,description,location,before_image,after_image,sort_order,is_featured,is_active)
                         VALUES (?,?,?,?,?,?,?,?,?,?,1)");
    foreach ($cases as $i => [$cslug, $t, $s, $loc, $d, $b, $a, $feat]) {
        $ins->execute([$cid, $svc[$s] ?? null, $cslug, $t, $d, $loc, $b, $a, $i, $feat]);
    }
    $db->commit();
    echo "✅ 相簿案例 ×" . count($cases) . "\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "❌ " . $e->getMessage() . "\n";
    exit(1);
}
