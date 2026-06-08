<?php
// ============================================================
// Migration 105 — 冷氣清洗(aircon-clean) keyword 重整
// ------------------------------------------------------------
// 後台手建的 3 個拼音 slug (lengqi...) 全設獨立頁、有 doorway 風險。
// 修法：
// 1. 新增 aircon-clean (冷氣清洗) 獨立頁主關鍵字
// 2. 把 #138/139/140 改：
//    - slug 拼音 → 英文 (aircon-cleaning / aircon-cleaning-company / aircon-cleaning-recommend)
//    - page_slug 由空 → 'aircon-clean' (折進主頁，避 doorway)
// 3. #251 客戶 retag：原 3 個拼音透過 service_keyword_id 仍有效（外鍵不變、page 折入）
//    + 加標 aircon-clean 主關鍵字（讓他直接在主頁出現）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/105_aircon_clean_keyword_restructure.php
// 冪等：INSERT IGNORE / UPDATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. 新增 aircon-clean 獨立頁主 keyword
$db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active)
    VALUES (2, 'aircon-clean', '冷氣清洗', '', 9, 1)
    ON DUPLICATE KEY UPDATE name=VALUES(name), page_slug=VALUES(page_slug), sort_order=VALUES(sort_order), is_active=1")->execute();
$mainId = (int)$db->query("SELECT id FROM service_keywords WHERE category_id=2 AND slug='aircon-clean'")->fetchColumn();
echo "KW: aircon-clean(冷氣清洗) 獨立頁 id={$mainId}\n";

// 2. 改 #138/139/140 slug 拼音→英文 + page 折進 aircon-clean
$db->prepare("UPDATE service_keywords SET slug='aircon-cleaning', page_slug='aircon-clean' WHERE id=138")->execute();
$db->prepare("UPDATE service_keywords SET slug='aircon-cleaning-company', page_slug='aircon-clean' WHERE id=139")->execute();
$db->prepare("UPDATE service_keywords SET slug='aircon-cleaning-recommend', page_slug='aircon-clean' WHERE id=140")->execute();
echo "KW: #138/139/140 slug 改英文 + page 折進 aircon-clean\n";

// 3. #251 加標 aircon-clean 主關鍵字（除了原本的 3 個同義字）
$db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (251, ?)")->execute([$mainId]);
echo "TAG: #251 冷淨億點 加 aircon-clean 標籤\n";

// 順便 audit：找其他可能該歸到 aircon-clean 的客戶（涼拾冷氣清潔 #186 應該重歸）
echo "\n== 既有冷氣相關客戶（檢視是否該補標 aircon-clean）==\n";
foreach ($db->query("SELECT id,brand_name,city_slug FROM clients WHERE brand_name LIKE '%冷氣清%' OR brand_name LIKE '%冷氣清洗%' OR brand_name LIKE '%空調清%' OR brand_name LIKE '%洗冷氣%'") as $r) {
    $k = $db->prepare("SELECT GROUP_CONCAT(sk.name SEPARATOR '/') FROM client_service_keywords csk JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE csk.client_id=?");
    $k->execute([$r['id']]);
    $tags = $k->fetchColumn() ?: '(無)';
    echo "  #{$r['id']} {$r['brand_name']} ({$r['city_slug']}) → {$tags}\n";
}

echo "\n== 驗證 ==\n";
foreach ($db->query("SELECT id,slug,name,page_slug FROM service_keywords WHERE id IN (138,139,140,{$mainId}) OR (category_id=2 AND slug='aircon')") as $r) {
    echo "  #{$r['id']} {$r['slug']}({$r['name']}) page=" . ($r['page_slug'] ?: '(獨立)') . "\n";
}
echo "\n== #251 標籤 ==\n";
foreach ($db->query("SELECT sk.id,sk.slug,sk.name FROM client_service_keywords csk JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE csk.client_id=251") as $r) {
    echo "  #{$r['id']} {$r['slug']}({$r['name']})\n";
}
echo "完成。\n";
