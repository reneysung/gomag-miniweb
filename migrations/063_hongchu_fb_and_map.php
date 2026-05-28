<?php
// ============================================================
// Migration 063 — 洪廚 FB 換現役粉專 + Google Map URL
// ------------------------------------------------------------
// 1. client_social.fb_page_url：之前找的 100050966366964 → 換現役 720924347985691
// 2. clients.google_maps_embed：寫入 maps.app.goo.gl 短網址（Reney 提供）
//    （store.php 會把短網址渲染成按鈕，不嵌 iframe — 短網址 iframe Google 會擋）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/063_hongchu_fb_and_map.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu client_id={$cid}\n\n";

// 1. FB 換現役
$newFb = 'https://www.facebook.com/洪廚囍宴-720924347985691/';
$u1 = $db->prepare("UPDATE client_social SET fb_page_url=? WHERE client_id=?");
$u1->execute([$newFb, $cid]);
echo "✅ client_social.fb_page_url 換為現役粉專 ({$u1->rowCount()} row)\n";
echo "   {$newFb}\n";

// 2. Google Map 短網址
$mapUrl = 'https://maps.app.goo.gl/hPN3GyzGks4yqPVWA';
$u2 = $db->prepare("UPDATE clients SET google_maps_embed=? WHERE id=?");
$u2->execute([$mapUrl, $cid]);
echo "✅ clients.google_maps_embed 寫入 ({$u2->rowCount()} row)\n";
echo "   {$mapUrl}\n";

// 3. Banner CTA 文字：立即訂席 → 立即預約（covered by 062 重新跑亦可，這裡直接 REPLACE）
$u3 = $db->prepare("UPDATE clients SET top_banner_html=REPLACE(top_banner_html,'立即訂席','立即預約') WHERE id=?");
$u3->execute([$cid]);
echo "✅ top_banner_html CTA「立即訂席」→「立即預約」({$u3->rowCount()} row)\n";

echo "\n完成。\n";
