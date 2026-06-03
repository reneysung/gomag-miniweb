<?php
// ============================================================
// Migration 089 — 清潔/裝潢細清 客戶分類稽核修補
// ------------------------------------------------------------
// 1. #142 享清清潔 補標 reno-detail（店名明寫裝潢細清、之前漏標）+
//    移除誤標的 室內設計(#2)
// 2. #100 天天沙發床墊 移除多餘的 清潔公司 標籤（保留 沙發床墊 #9）
// 3. #186 涼拾冷氣清潔 移除多餘的 清潔公司 標籤（保留 冷氣空調 #8）
// 4. #208/#210 Demo 店設 is_placeholder=1 → 前台清潔頁排除 demo
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/089_clean_tag_audit_fix.php
// 冪等：UPDATE / INSERT IGNORE / DELETE 安全。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. #142 享清 → 補 reno-detail (#13)、移除誤標室內設計 (#2)
$db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (142, 13)")->execute();
$r = $db->prepare("DELETE FROM client_service_keywords WHERE client_id=142 AND service_keyword_id=2");
$r->execute();
echo "FIX #142 享清：補 reno-detail；移除誤標 室內設計(影響 {$r->rowCount()})\n";

// 2. #100 天天沙發床墊 → 移除 cleaning (#1)
$r = $db->prepare("DELETE FROM client_service_keywords WHERE client_id=100 AND service_keyword_id=1");
$r->execute();
echo "FIX #100 天天沙發床墊：移除 清潔公司 標籤 (影響 {$r->rowCount()})\n";

// 3. #186 涼拾冷氣 → 移除 cleaning (#1)
$r = $db->prepare("DELETE FROM client_service_keywords WHERE client_id=186 AND service_keyword_id=1");
$r->execute();
echo "FIX #186 涼拾冷氣：移除 清潔公司 標籤 (影響 {$r->rowCount()})\n";

// 4. Demo 店設 is_placeholder=1（前台 citycat 子服務頁會排除 placeholder）
$r = $db->prepare("UPDATE clients SET is_placeholder=1 WHERE id IN (208, 210)");
$r->execute();
echo "FIX #208/#210 Demo 店：is_placeholder=1 (影響 {$r->rowCount()})\n";

echo "\n== 驗證 ==\n";
foreach ($db->query("SELECT c.id,c.brand_name,c.is_placeholder, GROUP_CONCAT(sk.name SEPARATOR '/') tags
    FROM clients c LEFT JOIN client_service_keywords csk ON csk.client_id=c.id
    LEFT JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE c.id IN (142,100,186,208,210) GROUP BY c.id ORDER BY c.id") as $r) {
    $ph = $r['is_placeholder'] ? ' [PLACEHOLDER]' : '';
    echo "  #{$r['id']} {$r['brand_name']}{$ph} → " . ($r['tags'] ?: '(無標籤)') . "\n";
}

echo "\n== 台南清潔頁實際店家清單（已啟用、非 placeholder）==\n";
$h = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
$sql = "SELECT DISTINCT c.id, c.brand_name
        FROM clients c
        JOIN client_service_keywords csk ON csk.client_id=c.id
        JOIN service_keywords sk ON sk.id=csk.service_keyword_id
        WHERE c.city_slug='tainan' AND c.is_active=1 AND c.is_placeholder=0
          AND (sk.slug='cleaning' OR sk.page_slug='cleaning')
        ORDER BY c.id";
foreach ($db->query($sql) as $r) echo "  #{$r['id']} {$r['brand_name']}\n";
