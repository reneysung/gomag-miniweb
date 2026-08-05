<?php
// migrations/153_oishii_order_url.php
// 奧喜長崎蛋糕（client_id=220）設定官網訂購/宅配連結 → banshin 官網商品頁
// 效果：小官網首頁 CTA「線上訂購・宅配全台」會出現「前往線上訂購 →」按鈕，
//       連到 https://www.banshin.com.tw/product/（與 services / 顧客分享頁一致）。
// 此欄位後台可改（order_url），非寫死。
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/153_oishii_order_url.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;
const ORDER_URL = 'https://www.banshin.com.tw/product/';

echo "=== Migration 153: 奧喜 order_url（宅配訂購連結）===\n\n";

$db->prepare("UPDATE clients SET order_url = ?, updated_at = NOW() WHERE id = ?")
   ->execute([ORDER_URL, OISHII_ID]);

echo "✅ 已設定 order_url = " . ORDER_URL . "\n\n";

echo "=== 驗證 ===\n";
$r = $db->prepare("SELECT id, brand_name, order_url FROM clients WHERE id = ?");
$r->execute([OISHII_ID]);
print_r($r->fetch(PDO::FETCH_ASSOC));

echo "\n✅ Migration 153 完成\n";
