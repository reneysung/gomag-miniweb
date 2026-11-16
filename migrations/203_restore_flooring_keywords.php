<?php
// ============================================================
// Migration 203 — 重建被後台刪掉的「木地板 flooring」「超耐磨地板 chaonaimodiban」關鍵字並標回店家
// ------------------------------------------------------------
// 2026-11-16 發現：居家服務(category 2) 的 flooring(原 023 建)、chaonaimodiban(173 折進 flooring) 已被
// admin/pages/service_keywords.php 刪除（連標籤一起刪、無備份），7 城 /city/*/home-service/flooring 全 0 家。
// Reney 定「重建並標回」。標回名單＝023 自動標籤規則（店名含「地板」的居家服務店）：
//   台南 快步45、昇揚67、亞拓113、安居136、Gerpros286；台中 佳翔152。Gerpros 另標回 chaonaimodiban（173 狀態）。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/203_restore_flooring_keywords.php [go]
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB(); $go = ($argv[1] ?? '') === 'go';
$cat = (int)$db->query("SELECT id FROM categories WHERE slug='home-service'")->fetchColumn();
$cols = array_column($db->query("SHOW COLUMNS FROM service_keywords")->fetchAll(PDO::FETCH_ASSOC), 'Field');
echo "cat=$cat cols=" . implode(',', $cols) . "\n";
$exist = $db->query("SELECT slug FROM service_keywords WHERE category_id=$cat AND slug IN ('flooring','chaonaimodiban')")->fetchAll(PDO::FETCH_COLUMN);
echo "已存在：" . json_encode($exist) . "\n";
$tags = ['flooring' => [45, 67, 113, 136, 286, 152], 'chaonaimodiban' => [286]];
$cl = $db->query("SELECT id,brand_name,city_slug,category_id,is_active FROM clients WHERE id IN (45,67,113,136,286,152)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cl as $c) { echo "  {$c['id']} {$c['brand_name']} {$c['city_slug']} cat{$c['category_id']} active{$c['is_active']}\n"; if ((int)$c['category_id'] !== $cat || !(int)$c['is_active']) { echo "ABORT 店家狀態不符\n"; exit(1); } }
if (count($cl) !== 6) { echo "ABORT 店家數\n"; exit(1); }
if (!$go) { echo "dry-run OK\n"; exit; }
$ins = $db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active) VALUES (?,?,?,?,?,1)");
$kid = [];
foreach ([['flooring','木地板','',30], ['chaonaimodiban','超耐磨地板','flooring',31]] as [$s,$n,$p,$o]) {
    $id = $db->query("SELECT id FROM service_keywords WHERE category_id=$cat AND slug=" . $db->quote($s))->fetchColumn();
    if (!$id) { $ins->execute([$cat,$s,$n,$p,$o]); $id = $db->lastInsertId(); echo "建立 $s($n) id=$id page_slug=[$p]\n"; }
    $kid[$s] = (int)$id;
}
$t = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?,?)");
foreach ($tags as $s => $ids) foreach ($ids as $c) { $t->execute([$c, $kid[$s]]); echo "標 $c → $s ({$t->rowCount()})\n"; }
echo "DONE\n";
