<?php
// ============================================================
// Migration 069 — client_city_pages 加 hide_shared_sections 欄位
// ------------------------------------------------------------
// 城市變體頁勾選後，store.php 藏掉「服務項目以下」的共用區塊
// （服務項目/精選菜色/評價/網友分享/Map/社群/相似店家），
// 只留 Hero + 該城自寫的 HTML(landing_extra_content) + 聯絡卡 + 城市切換 nav。
// 預設 0 → 不影響任何既有客戶變體頁（亞雷/麥田/奧喜照舊共用）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/069_city_variant_hide_shared.php
// 冪等：先查 information_schema，欄位已存在就跳過。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$col = $db->query("
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'client_city_pages'
      AND COLUMN_NAME = 'hide_shared_sections'
")->fetchColumn();

if ($col > 0) {
    echo "ℹ️  hide_shared_sections 已存在，跳過。\n";
} else {
    $db->exec("ALTER TABLE client_city_pages
        ADD COLUMN hide_shared_sections TINYINT(1) NOT NULL DEFAULT 0
        AFTER filter_cases_by_region");
    echo "✅ 已加欄位 hide_shared_sections（預設 0）。\n";
}

echo "完成。\n";
