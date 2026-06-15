<?php
// Migration 087 — clients 加 hide_blocks：行銷頁隱藏「服務/價目表/常見問題等區塊」的顯示
// （不刪區塊資料、不影響 useBlocks 版型；只是 store.php 不渲染 renderStoreBlocks）
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/087_add_hide_blocks.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/../includes/config.php';
$db = getDB();
try {
    $db->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS hide_blocks TINYINT(1) NOT NULL DEFAULT 0 COMMENT '行銷頁隱藏區塊(核心服務/價目表/常見問題)顯示，不刪資料' AFTER landing_only");
    echo "✅ 欄位 hide_blocks 已確保存在\n";
} catch (PDOException $e) {
    echo "⚠️ " . $e->getMessage() . "\n";
}
foreach ($db->query("SHOW COLUMNS FROM clients WHERE Field='hide_blocks'") as $c) {
    echo "  - {$c['Field']} ({$c['Type']}) default={$c['Default']}\n";
}
echo "完成。\n";
