<?php
// ============================================================
// Migration 070 — client_city_pages 加 service_tags + 洪廚 5 城填值
// ------------------------------------------------------------
// schema 變更（207 客戶通用）：
//   client_city_pages.service_tags  JSON — 行銷頁 hero 下方顯示的關鍵字膠囊
//
// 洪廚 5 城各填 4 標籤：
//   {city}辦桌推薦 / {city}尾牙推薦 / {city}喜宴外燴推薦 / {city}婚宴外燴推薦
//
// 視覺：hero 下方一排橘紅膠囊，告訴搜尋者「這頁吃這 4 個關鍵字」
// SEO：hero 區塊內含「{city}辦桌推薦」等短關鍵字，helps ranking
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/070_service_tags_hongchu_5cities.php
// 冪等：ADD COLUMN IF NOT EXISTS + UPDATE，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ─── 1. schema ─────────────────────────────────────────────
try {
    $db->exec("ALTER TABLE client_city_pages ADD COLUMN IF NOT EXISTS service_tags LONGTEXT DEFAULT NULL COMMENT 'hero 下方關鍵字膠囊（JSON array of strings）' AFTER store_keywords");
    echo "✅ ALTER service_tags 欄位\n";
} catch (\Throwable $e) {
    echo "ℹ️  ALTER skip: " . $e->getMessage() . "\n";
}

// ─── 2. 洪廚 id ────────────────────────────────────────────
$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

// ─── 3. 5 城各填 4 標籤 ────────────────────────────────────
$tagsByCity = [
    'tainan'   => ['台南辦桌推薦', '台南尾牙推薦', '台南喜宴外燴推薦', '台南婚宴外燴推薦'],
    'chiayi'   => ['嘉義辦桌推薦', '嘉義尾牙推薦', '嘉義喜宴外燴推薦', '嘉義婚宴外燴推薦'],
    'changhua' => ['彰化辦桌推薦', '彰化尾牙推薦', '彰化喜宴外燴推薦', '彰化婚宴外燴推薦'],
    'taichung' => ['台中辦桌推薦', '台中尾牙推薦', '台中喜宴外燴推薦', '台中婚宴外燴推薦'],
    'hsinchu'  => ['新竹辦桌推薦', '新竹尾牙推薦', '新竹喜宴外燴推薦', '新竹婚宴外燴推薦'],
];

$upd = $db->prepare("UPDATE client_city_pages
    SET service_tags=?
    WHERE client_id=? AND city_slug=?");

foreach ($tagsByCity as $citySlug => $tags) {
    $json = json_encode($tags, JSON_UNESCAPED_UNICODE);
    $upd->execute([$json, $cid, $citySlug]);
    echo "✅ {$citySlug}: " . implode(' / ', $tags) . "\n";
}

echo "\n完成。\n";
echo "下一步：改 store.php 渲染 service_tags（hero 下方膠囊 row）\n";
