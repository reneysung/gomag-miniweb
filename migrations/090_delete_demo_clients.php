<?php
// ============================================================
// Migration 090 — 硬刪 #208 / #210 Demo 客戶
// ------------------------------------------------------------
// 089 已設 is_placeholder=1 從前台隱藏，但 Reney 決定 hard delete。
// 先查 / 刪 所有外鍵參照（store_blocks/testimonials/client_service_keywords/
// client_city_pages 等），再刪 clients 本身。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/090_delete_demo_clients.php
// 冪等：DELETE 安全（重跑無事）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$ids = [208, 210];
$in = implode(',', $ids);

// 1. 找有 client_id 欄的表
echo "== 找含 client_id 欄的表 ==\n";
$tables = [];
foreach ($db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='client_id'") as $r) {
    $t = $r['TABLE_NAME'];
    $tables[] = $t;
    $cnt = (int)$db->query("SELECT COUNT(*) FROM `{$t}` WHERE client_id IN ({$in})")->fetchColumn();
    echo "  {$t}: {$cnt} 列\n";
}

echo "\n== 開始刪除 ==\n";
foreach ($tables as $t) {
    $r = $db->prepare("DELETE FROM `{$t}` WHERE client_id IN ({$in})");
    $r->execute();
    if ($r->rowCount() > 0) echo "  DELETE FROM {$t} → {$r->rowCount()} 列\n";
}

$r = $db->prepare("DELETE FROM clients WHERE id IN ({$in})");
$r->execute();
echo "  DELETE FROM clients → {$r->rowCount()} 列\n";

echo "\n== 驗證 ==\n";
$still = (int)$db->query("SELECT COUNT(*) FROM clients WHERE id IN ({$in})")->fetchColumn();
echo "  clients 表剩餘 #208/#210: {$still} (應為 0)\n";
echo "完成。\n";
