<?php
// ============================================================
// Migration 091 — 旭浪清潔 (#42 stonebeauty) 分類與標籤修正
// ------------------------------------------------------------
// 問題：#42 旭浪清潔公司 被歸到 cat=10(其他)、且無任何 service_keyword 標籤，
// 所以台南清潔頁完全看不到它。
// 修補：cat=10 → 2(home-service)，補標 cleaning + reno-detail + home-clean。
//
// 註：#1 旭浪清潔 (slug=xusen, 已停用) 與 #42 可能是重複記錄（同品牌、不同電話/地址）。
//     #1 停用保留為歷史、待 Reney 確認是否合併或刪除。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/091_xulang_fix_category_tags.php
// 冪等：UPDATE / INSERT IGNORE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. 改分類
$r = $db->prepare("UPDATE clients SET category_id=2 WHERE id=42 AND category_id<>2");
$r->execute();
echo "FIX #42 旭浪清潔公司：category 10→2 (影響 {$r->rowCount()})\n";

// 2. 補標籤：cleaning(1), reno-detail(13), home-clean(12)
$tags = [1=>'清潔公司', 13=>'裝潢細清', 12=>'居家清潔'];
$ins = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
foreach ($tags as $kid => $name) {
    $ins->execute([42, $kid]);
    echo "  + 標籤 #{$kid} {$name}\n";
}

echo "\n== 驗證 ==\n";
foreach ($db->query("SELECT c.id,c.brand_name,c.slug,c.city_slug,c.category_id,c.is_active, GROUP_CONCAT(sk.name SEPARATOR '/') tags
    FROM clients c LEFT JOIN client_service_keywords csk ON csk.client_id=c.id
    LEFT JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE c.id=42 GROUP BY c.id") as $r) {
    echo "  #{$r['id']} {$r['brand_name']} | cat={$r['category_id']} | act={$r['is_active']} | slug={$r['slug']} → " . ($r['tags'] ?: '(無)') . "\n";
}

echo "\n== 台南清潔頁實際店家清單（已啟用、非 placeholder）==\n";
$sql = "SELECT DISTINCT c.id, c.brand_name
        FROM clients c JOIN client_service_keywords csk ON csk.client_id=c.id
        JOIN service_keywords sk ON sk.id=csk.service_keyword_id
        WHERE c.city_slug='tainan' AND c.is_active=1 AND c.is_placeholder=0
          AND (sk.slug='cleaning' OR sk.page_slug='cleaning')
        ORDER BY c.id";
foreach ($db->query($sql) as $r) echo "  #{$r['id']} {$r['brand_name']}\n";

echo "\n== 台南裝潢細清頁實際店家清單 ==\n";
$sql2 = "SELECT DISTINCT c.id, c.brand_name
         FROM clients c JOIN client_service_keywords csk ON csk.client_id=c.id
         JOIN service_keywords sk ON sk.id=csk.service_keyword_id
         WHERE c.city_slug='tainan' AND c.is_active=1 AND c.is_placeholder=0
           AND (sk.slug='reno-detail' OR sk.page_slug='reno-detail')
         ORDER BY c.id";
foreach ($db->query($sql2) as $r) echo "  #{$r['id']} {$r['brand_name']}\n";
