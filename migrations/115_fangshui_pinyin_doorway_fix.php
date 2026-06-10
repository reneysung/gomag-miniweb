<?php
// ============================================================
// Migration 115 — 防水拼音 doorway 重整（仿 105 aircon-clean 模式）
// ------------------------------------------------------------
// 問題：service_keywords 同時有 #4 painting(油漆防水) 跟 #131 fangshuigongcheng
// (防水工程) 兩個獨立頁，內容意圖 95% 重疊；3 城（kh/pingtung/tainan）兩種
// geo_category_pages row 都寫 → doorway。
//
// 處理：
// 1. service_keywords #131/#135/#136 → page_slug='painting'（折入 painting 主頁）
// 2. geo_category_pages fangshuigongcheng 3 row → is_active=0（拼音 URL 不再被索引）
// 3. 客戶 #15/#249 既有標籤不動（透過 page_slug 折入自動生效，店家會出現在
//    /city/{city}/home-service/painting 頁面）
//
// 不刪 keyword：保留 client tagging 與 audit；折入後等同停用獨立頁但客戶仍生效。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/115_fangshui_pinyin_doorway_fix.php
// 冪等：UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

echo "═══ 處理前：service_keywords ═══\n";
foreach ($db->query("SELECT id, slug, name, page_slug, is_active FROM service_keywords WHERE id IN (4, 131, 135, 136)") as $r) {
    printf("  #%-3d %-25s %s page=%s active=%d\n", $r['id'], $r['slug'], $r['name'], $r['page_slug'] ?: '(獨立)', $r['is_active']);
}

// 1. 折入 painting
$db->exec("UPDATE service_keywords SET page_slug='painting' WHERE id IN (131, 135, 136)");
echo "\n>> UPDATE #131/#135/#136 → page_slug='painting'（折入油漆防水主頁）\n";

// 2. 停用拼音 geo_category_pages
$n = $db->exec("UPDATE geo_category_pages SET is_active=0 WHERE service_slug='fangshuigongcheng'");
echo ">> UPDATE geo_category_pages fangshuigongcheng → is_active=0（{$n} 城）\n";

// 驗證
echo "\n═══ 驗證 ═══\n";
echo "\n[a] service_keywords 變動後\n";
foreach ($db->query("SELECT id, slug, name, page_slug, is_active FROM service_keywords WHERE id IN (4, 131, 135, 136) ORDER BY id") as $r) {
    $tag = $r['page_slug'] === '' ? '【獨立】' : "  └ 折入 {$r['page_slug']}";
    printf("  %s #%-3d %-25s %s active=%d\n", $tag, $r['id'], $r['slug'], $r['name'], $r['is_active']);
}

echo "\n[b] geo_category_pages 變動後\n";
foreach ($db->query("SELECT city_slug, service_slug, service_name, is_active FROM geo_category_pages WHERE service_slug IN ('fangshuigongcheng','painting') ORDER BY service_slug, city_slug") as $r) {
    $flag = $r['is_active'] ? '✅' : '🚫';
    printf("  %s %s/%s (%s)\n", $flag, $r['city_slug'], $r['service_slug'], $r['service_name']);
}

echo "\n[c] #15/#249 客戶現在會出現在哪些 painting 頁\n";
echo "  #15 胖匠宅修防水油漆工程 → ";
$st = $db->prepare("SELECT city_slug FROM clients WHERE id=15"); $st->execute();
$c = $st->fetchColumn();
echo "/city/{$c}/home-service/painting（透過拼音 keyword 折入）\n";
echo "  #249 雨多填專業防水工程 → ";
$st = $db->prepare("SELECT city_slug FROM clients WHERE id=249"); $st->execute();
$c = $st->fetchColumn();
echo "/city/{$c}/home-service/painting（透過拼音 keyword 折入）\n";

echo "\n[d] 孤立度修補 — fangshuigongcheng 從 3 城 indexable → 0（不再有孤立 doorway）\n";
echo "\n完成。\n";
