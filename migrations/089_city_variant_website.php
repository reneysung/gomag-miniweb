<?php
// Migration 089 — client_city_pages 加 external_website_url（變體各縣自己的官網網址，沒填則用主檔）
// 解決：多縣市分點各有不同官網，但變體後台沒得填的問題。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/089_city_variant_website.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/../includes/config.php';
$db = getDB();
try {
    $db->exec("ALTER TABLE client_city_pages ADD COLUMN IF NOT EXISTS external_website_url VARCHAR(500) DEFAULT NULL COMMENT '本縣市官網網址(空=用主檔)' AFTER google_maps_embed");
    echo "✅ 欄位 external_website_url 已確保存在\n";
} catch (PDOException $e) { echo "⚠️ " . $e->getMessage() . "\n"; }
foreach ($db->query("SHOW COLUMNS FROM client_city_pages WHERE Field='external_website_url'") as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
echo "完成。\n";
