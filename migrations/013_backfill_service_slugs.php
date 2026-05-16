<?php
// Migration 013 — 回填 services.slug 欄位（從 name 衍生）
// 旭森風格：URL 直接含中文，例 /services/裝潢後細清
// 已有 slug 的不動；空的才回填

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDB();

// 抓所有空 slug 的 services
$rows = $db->query("SELECT id, client_id, name, slug FROM services WHERE is_active=1 AND (slug IS NULL OR slug='')")->fetchAll(PDO::FETCH_ASSOC);
echo "找到 " . count($rows) . " 個 service 需要回填 slug\n\n";

$upd = $db->prepare("UPDATE services SET slug=? WHERE id=?");
$existSlugs = [];

foreach ($rows as $r) {
    $name = trim($r['name']);
    if (!$name) continue;

    // slug 衍生規則：
    //   1. 全形空白 / 半形空白 / 標點 → 連字號
    //   2. 移除其他標點（保留中英文數字）
    //   3. 同一 client 內若衝突，補 -2 / -3
    $base = preg_replace('/[\s\x{3000}・·、，,。．\.（）()【】「」"\'#&\/\\\\?]+/u', '-', $name);
    $base = preg_replace('/-+/', '-', $base);
    $base = trim($base, '-');
    if (!$base) $base = 'service-' . $r['id'];

    // 檢查同 client 內是否已存在（包含本次已分配的）
    $key = $r['client_id'] . '|' . $base;
    $candidate = $base;
    $n = 2;
    while (isset($existSlugs[$r['client_id'] . '|' . $candidate])) {
        $candidate = $base . '-' . $n;
        $n++;
    }
    // 也要查 DB 已存在（避免跟之前已填的衝突）
    $check = $db->prepare("SELECT id FROM services WHERE client_id=? AND slug=? AND id!=? LIMIT 1");
    while (true) {
        $check->execute([$r['client_id'], $candidate, $r['id']]);
        if (!$check->fetch()) break;
        $candidate = $base . '-' . $n;
        $n++;
    }
    $existSlugs[$r['client_id'] . '|' . $candidate] = true;

    $upd->execute([$candidate, $r['id']]);
    echo "  id={$r['id']} client_id={$r['client_id']} name='{$name}' → slug='{$candidate}'\n";
}

echo "\n完成。\n";
