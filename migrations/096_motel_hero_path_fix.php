<?php
// ============================================================
// Migration 096 — 修 25 家 motel hero_image_path 從絕對 URL → 相對路徑
// ------------------------------------------------------------
// store.php line 181: ($client['hero_image_path'] ? BASE_URL.'/'.$path : '')
// → 預期相對路徑、自己 prepend BASE_URL。
// 我存了 https://www.gomag.com.tw/uploads/... 變成雙重前綴失效（store 頁無 hero）。
// 修法：UPDATE 把 prefix 拿掉、改 uploads/clients/motels/{file}.jpg。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/096_motel_hero_path_fix.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$r = $db->prepare("UPDATE clients SET hero_image_path = REPLACE(hero_image_path, 'https://www.gomag.com.tw/', '')
    WHERE hero_image_path LIKE 'https://www.gomag.com.tw/uploads/clients/motels/%'");
$r->execute();
echo "FIX: {$r->rowCount()} 家 motel hero_image_path 改回相對路徑\n";

echo "\n== 驗證 ==\n";
foreach ($db->query("SELECT id,brand_name,hero_image_path FROM clients WHERE slug LIKE 'motel-%' ORDER BY id LIMIT 5") as $r) {
    echo "  #{$r['id']} {$r['brand_name']} → {$r['hero_image_path']}\n";
}
