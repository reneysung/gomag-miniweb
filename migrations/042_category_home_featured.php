<?php
// ============================================================
// Migration 042 — categories.home_featured 欄位
// ------------------------------------------------------------
// 首頁「精選分類」改成後台可勾選（取代寫死 slug='food'）。
// 加一欄 home_featured，並把 food 預設設 1 以保留現狀。
// index.php 改讀 home_featured=1 的分類來渲染精選區。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/042_category_home_featured.php
// 冪等：欄位已存在會被 try/catch 吞掉；UPDATE 可重複跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1) 加欄位（冪等）
try {
    $db->exec("ALTER TABLE categories ADD COLUMN home_featured TINYINT(1) NOT NULL DEFAULT 0 COMMENT '首頁精選分類' AFTER is_active");
    echo "✅ 已新增 categories.home_featured 欄位\n";
} catch (PDOException $e) {
    echo "ℹ️  home_featured 欄位已存在，略過\n";
}

// 2) 預設把「餐飲美食」設為首頁精選，保留改版前的顯示
$n = $db->exec("UPDATE categories SET home_featured=1 WHERE slug='food'");
echo "✅ food 設為首頁精選（影響 {$n} 列）\n";

// 3) 顯示目前勾選狀態
echo "\n目前首頁精選分類：\n";
foreach ($db->query("SELECT name, slug, home_featured FROM categories ORDER BY sort_order, name")->fetchAll() as $r) {
    echo sprintf("  [%s] %s (%s)\n", $r['home_featured'] ? '✓' : ' ', $r['name'], $r['slug']);
}
echo "\n完成。\n";
