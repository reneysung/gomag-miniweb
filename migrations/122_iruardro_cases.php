<?php
// ============================================================
// Migration 122 — 冷淨億點（#251 iruardro）施工案例種子
// ------------------------------------------------------------
// 照片來源：Reney 提供之客戶施工照（桌面「冷淨億點/施工案例」資料夾），
// 已壓縮上傳 uploads/clients/iruardro/cases/。
// 拼圖 .png（窗型/伸港分離式）為客戶自製 Before/After 對比圖 → 單圖呈現。
// 標題/地點只到行政區層級，不放客戶姓名與完整地址。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/122_iruardro_cases.php
// 冪等：先 DELETE 該客戶 cases 再 INSERT
// ============================================================
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cid = (int)$db->query("SELECT id FROM clients WHERE subdomain='iruardro' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ iruardro not found\n"; exit(1); }

// 服務 id 對照（migration 121 種的 slug）
$svc = [];
foreach ($db->query("SELECT id, slug FROM services WHERE client_id={$cid}") as $r) $svc[$r['slug']] = (int)$r['id'];

$DIR = 'uploads/clients/iruardro/cases';

// [title, svc_slug, location, description, before, after, is_featured]
// 拼圖單圖 → 放 after_image；實照前後 → before+after 對比呈現
$cases = [
    ['吊隱式冷氣深層清洗', 'home-aircon', '彰化縣伸港鄉',
     '吊隱式室內機全機拆卸：濾網、風輪、集水盤逐件高壓清洗，完工後鰭片煥然一新，前後皆測溫測風。',
     "$DIR/ceiling-before.jpg", "$DIR/ceiling-after.jpg", 1],
    ['直立式洗衣機內槽拆洗', 'washing-machine', '台中市',
     '直立式洗衣機拆開內槽與底盤，把長期累積的污垢黴菌徹底刷洗，衣物洗得更安心。',
     "$DIR/washer-before.jpg", "$DIR/washer-after.jpg", 1],
    ['連鎖藥局門市・四方吹冷氣清洗', 'commercial-aircon', '台中市梧棲區',
     '營業場所四方吹（嵌入式）空調定期清洗保養，維持門市空氣品質與顧客體驗。',
     '', "$DIR/cassette-pharmacy.jpg", 1],
    ['透天三樓臥室・窗型冷氣全機拆洗', 'home-aircon', '台中市西區',
     '窗型冷氣全機拆卸清洗，風輪與鰭片上的陳年積塵徹底洗淨，前後對比一目了然。',
     '', "$DIR/window-3f.jpg", 0],
    ['透天四樓臥室・窗型冷氣全機拆洗', 'home-aircon', '台中市西區',
     '同戶四樓臥室窗型機同步施工，全機拆卸＋專用無毒藥水＋高壓水槍清洗。',
     '', "$DIR/window-4f.jpg", 0],
    ['客廳分離式冷氣全機拆洗', 'home-aircon', '彰化縣伸港鄉',
     '分離式室內機風輪與鰭片黑垢深層洗淨，濾網、外殼逐件清潔，前後對比清楚可見。',
     '', "$DIR/split-living.jpg", 0],
    ['辦公室分離式冷氣全機拆洗', 'home-aircon', '彰化縣伸港鄉',
     '居家辦公空間分離式冷氣全機拆洗，洗後出風更乾淨、運轉更有效率。',
     '', "$DIR/split-office.jpg", 0],
];

$db->beginTransaction();
try {
    $db->prepare("DELETE FROM cases WHERE client_id=?")->execute([$cid]);
    $ins = $db->prepare("INSERT INTO cases (client_id,service_id,title,description,location,before_image,after_image,sort_order,is_featured,is_active)
                         VALUES (?,?,?,?,?,?,?,?,?,1)");
    foreach ($cases as $i => [$t, $s, $loc, $d, $b, $a, $feat]) {
        $ins->execute([$cid, $svc[$s] ?? null, $t, $d, $loc, $b, $a, $i, $feat]);
    }
    $db->commit();
    echo "✅ cases ×" . count($cases) . "（featured ×" . count(array_filter($cases, fn($c) => $c[6])) . "）\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "❌ " . $e->getMessage() . "\n";
    exit(1);
}
