<?php
// migrations/061_clients_store_template.php
// 加 clients.store_template 欄位 — 視覺模板系統 Phase 3
// 預設值 '_default'，所有現有客戶不變
//
// CLI only：php migrations/061_clients_store_template.php
// box 上跑需 HTTP_HOST=www.gomag.com.tw php migrations/061_clients_store_template.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';

$db = getDB();

echo "=== Migration 061: clients.store_template ===\n";

// 檢查欄位是否已存在（idempotent）
$col = $db->query("SHOW COLUMNS FROM clients LIKE 'store_template'")->fetch();
if ($col) {
    echo "✓ store_template column already exists, skipping.\n";
} else {
    $db->exec("
        ALTER TABLE clients
        ADD COLUMN store_template VARCHAR(50) NOT NULL DEFAULT '_default'
        AFTER has_minisite
    ");
    echo "✓ Added clients.store_template (default '_default').\n";
}

// 顯示目前分佈
$rows = $db->query("SELECT store_template, COUNT(*) AS n FROM clients GROUP BY store_template ORDER BY n DESC")->fetchAll();
echo "\n目前模板分佈：\n";
foreach ($rows as $r) {
    printf("  %-20s  %d 客戶\n", $r['store_template'], $r['n']);
}

echo "\n✅ Migration 061 完成\n";
