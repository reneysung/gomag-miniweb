<?php
// ============================================================
// Migration 059 — 補上奧喜的 Instagram URL
// ------------------------------------------------------------
// Migration 054 用了錯的欄位名（ig_url），實際欄位是 instagram_url。
// 本 migration 用正確欄位名 INSERT/UPDATE。
//
// 跑法：HTTP_HOST=<host> php migrations/059_oishii_instagram_url.php
// 冪等：先查有沒有 row，UPDATE 或 INSERT 二擇一。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$clientId = (int)$db->query("SELECT id FROM clients WHERE subdomain='oishii' LIMIT 1")->fetchColumn();
if (!$clientId) { echo "❌ 找不到 oishii\n"; exit(1); }

$igUrl = 'https://www.instagram.com/oishii_castella/';

$existing = $db->prepare("SELECT COUNT(*) FROM client_social WHERE client_id=?");
$existing->execute([$clientId]);
$has = (int)$existing->fetchColumn();

if ($has) {
    $db->prepare("UPDATE client_social SET instagram_url=? WHERE client_id=?")->execute([$igUrl, $clientId]);
    echo "✅ UPDATE: client_social.instagram_url = {$igUrl}\n";
} else {
    $db->prepare("INSERT INTO client_social (client_id, instagram_url) VALUES (?, ?)")->execute([$clientId, $igUrl]);
    echo "✅ INSERT: client_social client_id={$clientId} instagram_url={$igUrl}\n";
}

$check = $db->prepare("SELECT instagram_url FROM client_social WHERE client_id=?");
$check->execute([$clientId]);
echo "確認: instagram_url='" . $check->fetchColumn() . "'\n";
