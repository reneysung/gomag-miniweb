<?php
/**
 * 174_city_variant_hide_address.php
 * 城市變體頁「只留電話、不放地址」開關（2026-07-24，Reney 需求）
 *
 * 情境：洪廚 #221 本店在台南，開新竹變體頁 /store/hongchu/hsinchu 想吃「新竹」關鍵字，
 *       但它在新竹沒有實體地址（跨縣承接）。原程式變體地址留空＝沿用主檔 →
 *       新竹頁被迫顯示台南地址、麵包屑也變「台南」。
 *
 * 解法：client_city_pages 加 hide_address 旗標。勾了 → 該變體頁不顯示地址、
 *       JSON-LD 不輸出 PostalAddress、麵包屑改用變體城市（見 store.php 改動）。
 *       電話／手機不受影響（照常沿用主檔或變體自填）。
 *
 * 冪等。跑法：HTTP_HOST=www.gomag.com.tw php migrations/174_city_variant_hide_address.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$has = $db->query("SHOW COLUMNS FROM client_city_pages LIKE 'hide_address'")->fetch();
if ($has) {
    echo "↷ hide_address 欄位已存在，略過\n";
} else {
    $db->exec("ALTER TABLE client_city_pages ADD COLUMN hide_address TINYINT(1) NOT NULL DEFAULT 0 AFTER hide_shared_sections");
    echo "✅ client_city_pages 已加 hide_address 欄位（預設 0，不影響既有變體）\n";
}

echo "\n=== 目前欄位確認 ===\n";
foreach ($db->query("SHOW COLUMNS FROM client_city_pages LIKE 'hide_%'") as $c)
    echo "  {$c['Field']} ({$c['Type']}) default={$c['Default']}\n";
