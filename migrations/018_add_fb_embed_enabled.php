<?php
// migrations/018_add_fb_embed_enabled.php
// 加 client_social.fb_embed_enabled — 控制 FB Page Plugin 是否自動嵌入到 /store/{slug} 頁面下方
// 預設 0（不嵌入），須在後台手動勾選

if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'www.gomag.com.tw';
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDB();

echo "=== Migration 018: 加 client_social.fb_embed_enabled ===\n";

// 檢查欄位是否已存在
$check = $db->query("SHOW COLUMNS FROM client_social LIKE 'fb_embed_enabled'")->fetch();
if ($check) {
    echo "✓ 欄位 fb_embed_enabled 已存在，略過\n";
} else {
    $db->exec("ALTER TABLE client_social ADD COLUMN fb_embed_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER fb_page_url");
    echo "✓ 已加 client_social.fb_embed_enabled (TINYINT, default 0)\n";
}

echo "完成。\n";
