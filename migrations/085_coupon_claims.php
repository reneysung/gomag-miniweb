<?php
// Migration 085 — 優惠券領取記錄表 coupon_claims（第一批：領取/發碼；redeemed_* 欄位留給第二批核銷用）
// + platform_settings 預設一條平台主站 LINE（platform_line_url）
//
// 跑法（CLI only）：HTTP_HOST=www.gomag.com.tw php migrations/085_coupon_claims.php
// （staging / prod 共用同一 DB，跑一次兩邊生效）

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

require __DIR__ . '/../includes/config.php';
$db = getDB();

$db->exec("
CREATE TABLE IF NOT EXISTS coupon_claims (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  client_id     INT NOT NULL,
  code          VARCHAR(12) NOT NULL,
  coupon_title  VARCHAR(120) DEFAULT NULL,
  ip_hash       VARCHAR(16) DEFAULT NULL,
  claimed_at    DATETIME NOT NULL,
  redeemed_at   DATETIME DEFAULT NULL,
  redeemed_note VARCHAR(120) DEFAULT NULL,
  UNIQUE KEY uniq_code (code),
  KEY idx_client (client_id),
  KEY idx_iphash (client_id, ip_hash, claimed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "✅ 表 coupon_claims 已確保存在\n";

// 平台主站 LINE 預設值（沿用 footer 既有的官方 LINE）
$exist = (int)$db->query("SELECT COUNT(*) FROM platform_settings WHERE setting_key='platform_line_url'")->fetchColumn();
if (!$exist) {
    $db->prepare("INSERT INTO platform_settings (setting_key,setting_value) VALUES ('platform_line_url',?)")
       ->execute(['https://lin.ee/N0z1eq9']);
    echo "✅ platform_line_url 預設為 https://lin.ee/N0z1eq9\n";
} else {
    echo "ℹ️  platform_line_url 已存在，不覆蓋\n";
}

echo "\n完成。\n";
