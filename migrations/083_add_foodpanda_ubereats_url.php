<?php
// Migration 083 — clients 加 foodpanda_url / ubereats_url 兩個獨立外送連結欄位
// 原本只有單一 order_url（通用：iCHEF/官方訂購頁）。餐飲客戶常同時有 foodpanda + Uber Eats，
// 拆成獨立欄位 → 前台各自顯示品牌色按鈕。
//
// 跑法（CLI only，需前綴 HTTP_HOST）：
//   staging: HTTP_HOST=aqua-elephant-856571.hostingersite.com php migrations/083_add_foodpanda_ubereats_url.php
//   prod:    HTTP_HOST=www.gomag.com.tw php migrations/083_add_foodpanda_ubereats_url.php
// （staging 與 prod 共用同一個 DB，跑一次兩邊都生效）

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

require __DIR__ . '/../includes/config.php';
$db = getDB();

$cols = [
    'foodpanda_url' => "ADD COLUMN IF NOT EXISTS foodpanda_url VARCHAR(500) DEFAULT NULL COMMENT 'foodpanda 外送連結' AFTER order_url",
    'ubereats_url'  => "ADD COLUMN IF NOT EXISTS ubereats_url VARCHAR(500) DEFAULT NULL COMMENT 'Uber Eats 外送連結' AFTER foodpanda_url",
];

foreach ($cols as $name => $sql) {
    try {
        $db->exec("ALTER TABLE clients $sql");
        echo "✅ 欄位 {$name} 已確保存在\n";
    } catch (PDOException $e) {
        echo "⚠️  {$name}: " . $e->getMessage() . "\n";
    }
}

// 驗證
$check = $db->query("SHOW COLUMNS FROM clients LIKE '%eats_url'")->fetchAll(PDO::FETCH_COLUMN);
echo "\n確認 clients 現有外送相關欄位：\n";
foreach ($db->query("SHOW COLUMNS FROM clients WHERE Field IN ('order_url','foodpanda_url','ubereats_url')") as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
echo "\n完成。\n";
