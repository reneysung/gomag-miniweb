<?php
/**
 * migrations/202_click_events_source.php
 * click_events 加 source：store＝店家頁 /store/…、mini＝小官網 {sub}.gomag|wmf.com.tw
 * 既有資料都是店家頁 → 預設 'store'
 * CLI: HTTP_HOST=www.gomag.com.tw php migrations/202_click_events_source.php
 */
require_once __DIR__ . '/../includes/config.php';
$db = new PDO('mysql:host=' . DB_HOST . (defined('DB_PORT') && DB_PORT ? ';port=' . DB_PORT : '') . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$has = $db->query("SHOW COLUMNS FROM click_events LIKE 'source'")->fetch();
if (!$has) {
    $db->exec("ALTER TABLE click_events ADD COLUMN source VARCHAR(10) NOT NULL DEFAULT 'store' AFTER action");
    echo "click_events.source added\n";
} else {
    echo "click_events.source already exists\n";
}
