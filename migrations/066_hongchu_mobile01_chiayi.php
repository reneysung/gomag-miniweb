<?php
// ============================================================
// Migration 066 — 補 Mobile01 嘉義文章到 hongchu chiayi external_reviews_json
// ------------------------------------------------------------
// Mobile01 t=7215008 被 Cloudflare 擋下 fetch 抓不到原始 title，
// Reney 同意用 placeholder「Mobile01 婚禮版・洪廚喜宴嘉義開箱」
// 之後在後台 store_city_edit.php 可自行改 title。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/066_hongchu_mobile01_chiayi.php
// 冪等：UPDATE WHERE client_id+city_slug，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }

// 撈現有 chiayi external_reviews_json，append 一筆
$existing = $db->prepare("SELECT external_reviews_json FROM client_city_pages WHERE client_id=? AND city_slug='chiayi'");
$existing->execute([$cid]);
$current = json_decode($existing->fetchColumn() ?: '[]', true) ?: [];

// 檢查是否已包含此 URL（冪等）
$mobile01Url = 'https://www.mobile01.com/topicdetail.php?f=200&t=7215008';
$alreadyIn = false;
foreach ($current as $r) {
    if (($r['url'] ?? '') === $mobile01Url) { $alreadyIn = true; break; }
}

if ($alreadyIn) {
    echo "ℹ️  Mobile01 已在嘉義 reviews 內，略過\n";
} else {
    $current[] = [
        'title'   => 'Mobile01 婚禮版・洪廚喜宴嘉義開箱',
        'url'     => $mobile01Url,
        'source'  => 'Mobile01 婚禮版',
        'excerpt' => '網友嘉義婚宴實場開箱分享（標題與摘要待後台補）。',
    ];
    $newJson = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $db->prepare("UPDATE client_city_pages SET external_reviews_json=? WHERE client_id=? AND city_slug='chiayi'")
       ->execute([$newJson, $cid]);
    echo "✅ chiayi 補入 Mobile01 一篇（共 " . count($current) . " 篇）\n";
}
echo "完成。\n";
