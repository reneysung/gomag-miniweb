<?php
// migrations/067_clients_minisite_template.php
// 加 clients.minisite_template 欄位 — 小官網視覺模板系統（跟 store_template 脫鉤）
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/067_clients_minisite_template.php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

echo "=== Migration 067: clients.minisite_template ===\n\n";

$col = $db->query("SHOW COLUMNS FROM clients LIKE 'minisite_template'")->fetch();
if ($col) {
    echo "✓ minisite_template 已存在，跳過\n";
} else {
    $db->exec("ALTER TABLE clients ADD COLUMN minisite_template VARCHAR(50) NOT NULL DEFAULT '_default' AFTER store_template");
    echo "✓ 已加 clients.minisite_template (default '_default')\n";
}

// 顯示分佈
$rows = $db->query("SELECT minisite_template, COUNT(*) AS n FROM clients GROUP BY minisite_template ORDER BY n DESC")->fetchAll();
echo "\n目前分佈：\n";
foreach ($rows as $r) printf("  %-20s  %d 客戶\n", $r['minisite_template'], $r['n']);

echo "\n✅ Migration 067 完成\n";
