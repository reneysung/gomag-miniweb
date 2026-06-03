<?php
// ============================================================
// Migration 092 — 硬刪 #1 旭浪清潔 (slug=xusen, 已停用，重複記錄)
// ------------------------------------------------------------
// #1 旭浪清潔 與 #42 旭浪清潔公司(stonebeauty) 是同品牌不同時期記錄。
// #42 已 091 修補完整、是保留方。#1 已停用、無人引用、可硬刪。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/092_delete_xulang_dup.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$id = 1;

echo "== 找含 client_id 欄的表 ==\n";
$tables = [];
foreach ($db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='client_id'") as $r) {
    $t = $r['TABLE_NAME'];
    $tables[] = $t;
    $cnt = (int)$db->query("SELECT COUNT(*) FROM `{$t}` WHERE client_id={$id}")->fetchColumn();
    if ($cnt > 0) echo "  {$t}: {$cnt} 列\n";
}

echo "\n== 開始刪除 ==\n";
foreach ($tables as $t) {
    $r = $db->prepare("DELETE FROM `{$t}` WHERE client_id=?");
    $r->execute([$id]);
    if ($r->rowCount() > 0) echo "  DELETE FROM {$t} → {$r->rowCount()} 列\n";
}
$r = $db->prepare("DELETE FROM clients WHERE id=?");
$r->execute([$id]);
echo "  DELETE FROM clients → {$r->rowCount()} 列\n";

echo "\n== 驗證 ==\n";
$n = (int)$db->query("SELECT COUNT(*) FROM clients WHERE id={$id}")->fetchColumn();
echo "  clients 表 #{$id}: {$n} (應為 0)\n";
foreach ($db->query("SELECT id,brand_name,slug,city_slug,category_id,is_active FROM clients WHERE brand_name LIKE '%旭浪%'") as $r) {
    echo "  剩餘旭浪: #{$r['id']} {$r['brand_name']} | slug={$r['slug']} | city={$r['city_slug']} | cat={$r['category_id']} | act={$r['is_active']}\n";
}
echo "完成。\n";
