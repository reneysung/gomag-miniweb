<?php
/**
 * migrations/201_click_events.php
 * 店家頁按鈕點擊記錄（撥電話/LINE/導航/優惠券/社群/外送/官網/小官網）
 * 寫入端：/track_click.php（店家頁 sendBeacon）；讀取端：admin/pages/click_stats.php（super only）
 * CLI: HTTP_HOST=www.gomag.com.tw php migrations/201_click_events.php
 */
require_once __DIR__ . '/../includes/config.php';
$db = new PDO('mysql:host=' . DB_HOST . (defined('DB_PORT') && DB_PORT ? ';port=' . DB_PORT : '') . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS click_events (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id  INT UNSIGNED NOT NULL,
  action     VARCHAR(20)  NOT NULL,
  page_path  VARCHAR(255) NOT NULL DEFAULT '',
  ip_hash    CHAR(10)     NOT NULL DEFAULT '',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_client_time (client_id, created_at),
  KEY idx_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "click_events ready\n";
