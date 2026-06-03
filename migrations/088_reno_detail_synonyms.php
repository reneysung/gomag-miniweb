<?php
// ============================================================
// Migration 088 — 裝潢細清(reno-detail) 加 5 個同義字關鍵字
// ------------------------------------------------------------
// 全部 page_slug='reno-detail' 折進該頁，避 doorway。後台店家可標、消費者
// 搜尋變體都接得到，同 1 頁 SEO 多接點。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/088_reno_detail_synonyms.php
// 冪等：INSERT ON DUPLICATE KEY UPDATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$h = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$kws = [
  ['decoration-cleaning',     '裝潢清潔',       60],
  ['new-house-cleaning',      '新成屋清潔',     61],
  ['move-in-cleaning',        '入厝清潔',       62],
  ['detailed-cleaning',       '細部清潔',       63],
  ['post-renovation-cleaning','裝潢後清潔',     64],
];

$up = $db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active) VALUES (?,?,?,?,?,1)
    ON DUPLICATE KEY UPDATE name=VALUES(name), page_slug=VALUES(page_slug), sort_order=VALUES(sort_order), is_active=1");

foreach ($kws as $k) {
    $up->execute([$h, $k[0], $k[1], 'reno-detail', $k[2]]);
    printf("KW: %-26s %s → reno-detail\n", $k[0], $k[1]);
}
echo "完成。\n";
echo "驗證：";
foreach ($db->query("SELECT slug,name,page_slug FROM service_keywords WHERE category_id={$h} AND page_slug='reno-detail' ORDER BY sort_order") as $r) {
    echo "\n  {$r['slug']}({$r['name']}) → {$r['page_slug']}";
}
echo "\n";
