<?php
// ============================================================
// Migration 086 — 洪廚拿掉「多縣市互鏈」+「精選評價」section
// ------------------------------------------------------------
// Reney 對稿：截圖兩個 section 都要拿掉
//   1. 📍 洪廚囍宴・全台服務縣市（多縣市互鏈卡片）
//   2. 6 則精選評價（testimonials）
//
// 做法：
//   - clients 加 hide_city_grid_section TINYINT，洪廚設 1
//     store.php line 1135 加條件 && empty($client['hide_city_grid_section'])
//     → 不顯示「多縣市互鏈」section
//   - testimonials 6 條 is_active=0
//     → 不顯示「精選評價」section（store.php 條件 if ($testimonials)）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/086_hongchu_hide_city_grid_and_testimonials.php
// 冪等：ADD IF NOT EXISTS + UPDATE，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }

// 1. 加 schema
try {
    $db->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS hide_city_grid_section TINYINT(1) NOT NULL DEFAULT 0 COMMENT '隱藏「全台服務縣市」多縣市互鏈 section（主頁 + 變體頁通用）'");
    echo "✅ clients ADD COLUMN hide_city_grid_section\n";
} catch (\Throwable $e) {
    echo "ℹ️  ALTER skip: " . $e->getMessage() . "\n";
}

// 2. 洪廚啟用 hide_city_grid_section
$db->prepare("UPDATE clients SET hide_city_grid_section=1 WHERE id=?")->execute([$cid]);
echo "✅ hongchu hide_city_grid_section=1\n";

// 3. testimonials 6 條 inactive
$db->prepare("UPDATE testimonials SET is_active=0 WHERE client_id=?")->execute([$cid]);
$n = (int)$db->query("SELECT COUNT(*) FROM testimonials WHERE client_id={$cid} AND is_active=0")->fetchColumn();
echo "✅ testimonials {$n} 條 is_active=0\n";

echo "\n完成。下一步：scp 新 store.php 到 prod（line 1135 已加 hide_city_grid_section 條件）\n";
