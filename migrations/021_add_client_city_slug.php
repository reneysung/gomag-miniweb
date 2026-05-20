<?php
// ============================================================
// Migration 021 — clients.city_slug 正規化（IA 落地第 6 步）
// ------------------------------------------------------------
// 加 city_slug 欄 + 從 address 一次回填，取代散落的 address LIKE 前綴比對。
// 跑法（本機 CLI）：php migrations/021_add_client_city_slug.php
//     （box：HTTP_HOST=...hostingersite.com php migrations/021_add_client_city_slug.php）
// 冪等：欄位用 IF NOT EXISTS 概念（先檢查），回填可重複跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/front_functions.php';  // deriveCitySlug()

$db = getDB();

// 1. 加欄（若不存在）
$has = $db->query("SHOW COLUMNS FROM clients LIKE 'city_slug'")->fetch();
if (!$has) {
    $db->exec("ALTER TABLE clients ADD COLUMN city_slug VARCHAR(50) NULL AFTER address, ADD INDEX idx_city_slug (city_slug)");
    echo "ALTER：已加 city_slug 欄 + index\n";
} else {
    echo "ALTER：city_slug 欄已存在，略過\n";
}

// 2. 回填
$rows = $db->query("SELECT id, address FROM clients")->fetchAll();
$upd = $db->prepare("UPDATE clients SET city_slug = ? WHERE id = ?");
$set = 0; $null = 0;
foreach ($rows as $r) {
    $slug = deriveCitySlug((string)($r['address'] ?? ''));
    $upd->execute([$slug, $r['id']]);
    if ($slug) $set++; else $null++;
}
echo "回填：{$set} 筆有 city_slug、{$null} 筆 null（無對映縣市或無地址）\n";

// 3. 分佈
echo "分佈：\n";
foreach ($db->query("SELECT COALESCE(city_slug,'(null)') cs, COUNT(*) c FROM clients WHERE is_active=1 GROUP BY city_slug ORDER BY c DESC") as $r) {
    echo "  {$r['cs']}: {$r['c']}\n";
}
