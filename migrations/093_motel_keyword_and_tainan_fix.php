<?php
// ============================================================
// Migration 093 — 旅宿類加 motel 關鍵字 + 台南既有 7 家汽車旅館分類/標籤修補
// ------------------------------------------------------------
// 旅宿類 (cat=12) 已有 bnb/hotel/villa/homestay-family/guesthouse 但缺 motel。
// 台南 7 家汽車旅館裡 6 家被歸到 cat=10(其他)、且無 motel 標籤。
// 本 migration：
// 1. INSERT motel(汽車旅館) keyword、獨立頁(page_slug='')
// 2. UPDATE 台南 6 家汽車旅館 cat=10→12
// 3. INSERT motel 標籤給 7 家(#20,23,24,25,27,29,30)
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/093_motel_keyword_and_tainan_fix.php
// 冪等：INSERT IGNORE/ON DUPLICATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. 建 motel 關鍵字
$kwUp = $db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active)
    VALUES (12, 'motel', '汽車旅館', '', 97, 1)
    ON DUPLICATE KEY UPDATE name=VALUES(name), page_slug=VALUES(page_slug), is_active=1");
$kwUp->execute();
$motelKwId = (int)$db->query("SELECT id FROM service_keywords WHERE category_id=12 AND slug='motel'")->fetchColumn();
echo "KW: motel(汽車旅館) keyword id={$motelKwId}（旅宿類獨立頁）\n";

// 2. 修台南 6 家 cat=10→12
$ids = [20,23,24,25,27,29];
$in = implode(',', $ids);
$r = $db->prepare("UPDATE clients SET category_id=12 WHERE id IN ({$in}) AND category_id<>12");
$r->execute();
echo "FIX cat: {$r->rowCount()} 家從「其他」改到「旅宿住宿」\n";

// 3. 補 motel 標籤給 7 家（含 #30 已是 cat=12）
$tagIds = [20,23,24,25,27,29,30];
$ins = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
foreach ($tagIds as $cid) {
    $ins->execute([$cid, $motelKwId]);
}
echo "TAG: 7 家補 motel 標籤完成\n";

echo "\n== 驗證：台南 7 家汽車旅館 ==\n";
foreach ($db->query("SELECT c.id,c.brand_name,c.category_id,c.is_active,c.is_placeholder, GROUP_CONCAT(sk.name SEPARATOR '/') tags
    FROM clients c LEFT JOIN client_service_keywords csk ON csk.client_id=c.id
    LEFT JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE c.id IN (20,23,24,25,27,29,30) GROUP BY c.id ORDER BY c.id") as $r) {
    echo "  #{$r['id']} {$r['brand_name']} | cat={$r['category_id']} | act={$r['is_active']} ph={$r['is_placeholder']} → " . ($r['tags'] ?: '(無)') . "\n";
}
