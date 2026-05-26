<?php
// ============================================================
// Migration 053 — client_city_pages 加 minisite_* 城市覆寫欄位
// ------------------------------------------------------------
// 多縣市方案：客戶買 N 縣市 → 行銷頁(主站 /store/{slug}/{city})
// 已支援 store_* 覆寫；本次補上「小官網({sub}.gomag.com.tw/{city})」
// 也能用同一 row 的 minisite_* 覆寫 meta/keywords/og/intro。
//
// 共用欄位（已存在）：
//   brand_name, city_label, hero_image_path
// 行銷頁專屬（已存在）：
//   store_meta_title, store_meta_desc, store_keywords, store_og_image,
//   landing_extra_content, filter_cases_by_region
// 小官網專屬（本次新增 5 個）：
//   minisite_meta_title, minisite_meta_desc, minisite_keywords,
//   minisite_og_image, minisite_intro_html
//
// 跑法：HTTP_HOST=<host> php migrations/053_minisite_city_overrides.php
// 冪等：try/catch 吞掉「欄位已存在」錯誤；可重複跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cols = [
    ['minisite_meta_title', "VARCHAR(300) DEFAULT NULL COMMENT '小官網該城市 SEO 標題'"],
    ['minisite_meta_desc',  "TEXT DEFAULT NULL COMMENT '小官網該城市 SEO 描述'"],
    ['minisite_keywords',   "VARCHAR(500) DEFAULT NULL COMMENT '小官網該城市 SEO 關鍵字'"],
    ['minisite_og_image',   "VARCHAR(500) DEFAULT NULL COMMENT '小官網該城市 OG 分享圖'"],
    ['minisite_intro_html', "LONGTEXT DEFAULT NULL COMMENT '小官網該城市首頁/落地頁簡介內容'"],
];

$added = 0;
foreach ($cols as [$name, $def]) {
    try {
        $db->exec("ALTER TABLE client_city_pages ADD COLUMN {$name} {$def}");
        echo "✅ 新增欄位 {$name}\n";
        $added++;
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  {$name} 已存在，略過\n";
        } else {
            echo "❌ {$name} 失敗：" . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== client_city_pages 目前所有欄位 ===\n";
foreach ($db->query("DESCRIBE client_city_pages")->fetchAll() as $c) {
    $star = strpos($c['Field'], 'minisite_') === 0 ? ' ⭐' : '';
    printf("  %-26s %s%s\n", $c['Field'], $c['Type'], $star);
}

echo "\n本次新增 {$added} 欄。完成。\n";
