<?php
// ============================================================
// Migration 058 — 補設奧喜 clients.city_slug = 'taichung'
// ------------------------------------------------------------
// 奧喜的 clients.city_slug 在 migration 054 建立時忘記填，導致
// /city/taichung 找不到本店。雖然新版 city.php 用 EXISTS 條件
// 透過 client_city_pages 也找得到，但本店所在地的 city_slug
// 該填還是要填（資料衛生 + 其他可能查詢這欄位的地方）。
//
// 跑法：HTTP_HOST=<host> php migrations/058_oishii_city_slug_taichung.php
// 冪等：UPDATE，可重複跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$updated = $db->prepare("UPDATE clients SET city_slug='taichung' WHERE subdomain='oishii' AND (city_slug IS NULL OR city_slug='')");
$updated->execute();
echo "✅ 已更新 oishii.city_slug='taichung'（影響 " . $updated->rowCount() . " 列）\n";

$check = $db->query("SELECT brand_name, city_slug FROM clients WHERE subdomain='oishii'")->fetch();
echo "確認: {$check['brand_name']} → city_slug='{$check['city_slug']}'\n";
