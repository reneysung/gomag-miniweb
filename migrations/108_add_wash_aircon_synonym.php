<?php
// ============================================================
// Migration 108 — 補「洗冷氣」同義字，折入 aircon-clean 主頁
// ------------------------------------------------------------
// 「洗冷氣」是搜尋量最高的口語詞（高於「冷氣清洗」「冷氣清潔」），
// 業者標題大量使用，但服務關鍵字池漏收。補成 page_slug='aircon-clean'
// 同義字，避免 doorway、把口語長尾流量收進主頁。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/108_add_wash_aircon_synonym.php
// 冪等：INSERT ... ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. 補「洗冷氣」同義字
$db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active)
    VALUES (2, 'wash-aircon', '洗冷氣', 'aircon-clean', 13, 1)
    ON DUPLICATE KEY UPDATE name=VALUES(name), page_slug=VALUES(page_slug), sort_order=VALUES(sort_order), is_active=1")->execute();
echo "KW: wash-aircon(洗冷氣) → 折入 aircon-clean\n";

// 2. 驗證 aircon-clean 主頁與所有同義字
echo "\n== aircon-clean 主頁 + 同義字 ==\n";
foreach ($db->query("SELECT id,slug,name,page_slug,sort_order
                     FROM service_keywords
                     WHERE category_id=2 AND (slug='aircon-clean' OR page_slug='aircon-clean')
                     ORDER BY page_slug, sort_order") as $r) {
    $tag = $r['page_slug'] === '' ? '【主頁】' : "  └ 折入";
    echo "  {$tag} #{$r['id']} {$r['slug']}({$r['name']}) sort={$r['sort_order']}\n";
}

echo "\n完成。\n";
