<?php
// ============================================================
// Migration 035 — 人工修正 2 家無法自動推導的 city_slug
// ------------------------------------------------------------
// #196 麥斯室內設計（地址「（台中）彰化市…」矛盾）→ 彰化(changhua)
// #216 鍍卡 Do Car汽車鍍膜（地址空白）→ 嘉義(chiayi) + 標 coating(汽車鍍膜)
// （#75 歡樂牛排雲林店 維持 null，雲林尚未開城）
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/035_fix_city_slug_manual.php
// 冪等：UPDATE 指定 id；INSERT IGNORE 標籤。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. 麥斯室內設計 → 彰化
$st = $db->prepare("UPDATE clients SET city_slug='changhua' WHERE id=196");
$st->execute();
echo "#196 麥斯室內設計 → changhua（影響 {$st->rowCount()} 列）\n";

// 2. 鍍卡 Do Car汽車鍍膜 → 嘉義
$st = $db->prepare("UPDATE clients SET city_slug='chiayi' WHERE id=216");
$st->execute();
echo "#216 鍍卡汽車鍍膜 → chiayi（影響 {$st->rowCount()} 列）\n";

// 3. 確保 #216 標 coating（汽車鍍膜）
$row = $db->query("SELECT id, brand_name, category_id FROM clients WHERE id=216")->fetch();
if ($row) {
    $coatId = (int)$db->query("SELECT sk.id FROM service_keywords sk WHERE sk.category_id={$row['category_id']} AND sk.slug='coating' LIMIT 1")->fetchColumn();
    if ($coatId) {
        $ins = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (216, ?)");
        $ins->execute([$coatId]);
        echo "#216 標 coating（kw id={$coatId}，新增 {$ins->rowCount()} 筆）\n";
    } else {
        echo "找不到該分類的 coating 關鍵字（category_id={$row['category_id']}），略過標籤\n";
    }
} else {
    echo "查無 #216\n";
}

// 驗證
echo "\n驗證：\n";
foreach ([196, 216] as $id) {
    $r = $db->query("SELECT id, brand_name, city_slug FROM clients WHERE id={$id}")->fetch();
    if ($r) printf("  #%d %s → city_slug=%s\n", $r['id'], $r['brand_name'], $r['city_slug'] ?: '(空)');
}
$n = (int)$db->query("SELECT COUNT(DISTINCT cl.id) FROM clients cl JOIN client_service_keywords csk ON csk.client_id=cl.id JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE cl.is_active=1 AND cl.city_slug='chiayi' AND sk.slug='coating'")->fetchColumn();
echo "  嘉義汽車鍍膜 店數：{$n}\n";
