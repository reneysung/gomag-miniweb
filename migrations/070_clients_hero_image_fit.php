<?php
// ============================================================
// Migration 070 — clients 加 hero_image_fit 欄位
// ------------------------------------------------------------
// Hero 圖顯示方式：'cover'=照片填滿（裁切，預設，維持現狀）
//                  'contain'=Logo 完整顯示（不裁切、不變形）
// 預設 'cover' → 所有既有客戶顯示不變。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/070_clients_hero_image_fit.php
// 冪等：先查 information_schema，欄位已存在就跳過。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$exists = $db->query("
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'hero_image_fit'
")->fetchColumn();

if ($exists > 0) {
    echo "ℹ️  hero_image_fit 已存在，跳過。\n";
} else {
    $db->exec("ALTER TABLE clients
        ADD COLUMN hero_image_fit VARCHAR(10) NOT NULL DEFAULT 'cover'
        AFTER hero_image_path");
    echo "✅ 已加欄位 hero_image_fit（預設 cover）。\n";
}
echo "完成。\n";
