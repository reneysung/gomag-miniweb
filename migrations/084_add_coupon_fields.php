<?php
// Migration 084 — clients 加優惠券欄位（行銷頁「加 LINE 領取優惠券 → 出示畫面」）
//
// 跑法（CLI only，需前綴 HTTP_HOST）：
//   prod: HTTP_HOST=www.gomag.com.tw php migrations/084_add_coupon_fields.php
// （staging 與 prod 共用同一個 DB，跑一次兩邊都生效）

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

require __DIR__ . '/../includes/config.php';
$db = getDB();

$cols = [
    'coupon_enabled' => "ADD COLUMN IF NOT EXISTS coupon_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT '行銷頁顯示優惠券' AFTER ubereats_url",
    'coupon_title'   => "ADD COLUMN IF NOT EXISTS coupon_title VARCHAR(120) DEFAULT NULL COMMENT '優惠券標題 例:新客9折' AFTER coupon_enabled",
    'coupon_code'    => "ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(60) DEFAULT NULL COMMENT '優惠碼(可選)' AFTER coupon_title",
    'coupon_expiry'  => "ADD COLUMN IF NOT EXISTS coupon_expiry DATE DEFAULT NULL COMMENT '有效期限(可選,過期不顯示)' AFTER coupon_code",
    'coupon_desc'    => "ADD COLUMN IF NOT EXISTS coupon_desc VARCHAR(500) DEFAULT NULL COMMENT '使用說明/條款(可選)' AFTER coupon_expiry",
];

foreach ($cols as $name => $sql) {
    try {
        $db->exec("ALTER TABLE clients $sql");
        echo "✅ 欄位 {$name} 已確保存在\n";
    } catch (PDOException $e) {
        echo "⚠️  {$name}: " . $e->getMessage() . "\n";
    }
}

echo "\n確認 clients 優惠券欄位：\n";
foreach ($db->query("SHOW COLUMNS FROM clients WHERE Field LIKE 'coupon_%'") as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
echo "\n完成。\n";
