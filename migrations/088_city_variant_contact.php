<?php
// Migration 088 — client_city_pages 加「本縣市聯絡資訊」欄位（變體可各縣自填，沒填則 fallback 主檔）
// 解決：多縣市變體(如雨多填 屏東/台南/高雄)目前共用主檔地址電話的問題。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/088_city_variant_contact.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/../includes/config.php';
$db = getDB();

$cols = [
    'address'           => "ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL COMMENT '本縣市地址(空=用主檔)' AFTER city_label",
    'phone'             => "ADD COLUMN IF NOT EXISTS phone VARCHAR(50) DEFAULT NULL COMMENT '本縣市電話' AFTER address",
    'mobile_phone'      => "ADD COLUMN IF NOT EXISTS mobile_phone VARCHAR(50) DEFAULT NULL COMMENT '本縣市手機' AFTER phone",
    'business_hours'    => "ADD COLUMN IF NOT EXISTS business_hours VARCHAR(255) DEFAULT NULL COMMENT '本縣市營業時間' AFTER mobile_phone",
    'google_maps_embed' => "ADD COLUMN IF NOT EXISTS google_maps_embed TEXT DEFAULT NULL COMMENT '本縣市 Google 地圖' AFTER business_hours",
];
foreach ($cols as $name => $sql) {
    try { $db->exec("ALTER TABLE client_city_pages $sql"); echo "✅ {$name}\n"; }
    catch (PDOException $e) { echo "⚠️ {$name}: " . $e->getMessage() . "\n"; }
}
echo "\n現有聯絡欄位：\n";
foreach ($db->query("SHOW COLUMNS FROM client_city_pages WHERE Field IN ('address','phone','mobile_phone','business_hours','google_maps_embed')") as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
echo "完成。\n";
