<?php
// ============================================================
// Migration 022 — geo_category_pages 子服務維度（city › 大分類 › 子服務）
// ------------------------------------------------------------
// 加 service_slug / service_name 兩欄；unique key 從 (city_slug, category_id)
// 改成 (city_slug, category_id, service_slug)，讓一個 city×大分類底下能放多個
// 子服務 SEO 頁（清潔/木地板/水電…）。並把現有「居家服務」的清潔內容歸位成
// service_slug='cleaning'。
//
// 跑法（本機 CLI）：php migrations/022_add_geo_service_dimension.php
//   （box：HTTP_HOST=...hostingersite.com php migrations/022_add_geo_service_dimension.php）
// 冪等：欄位/索引先檢查再動；資料遷移只挑 service_slug='' 的 home-service 列。
// ============================================================
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

// ── 1. 加欄（若不存在）────────────────────────────────────
$hasSvcSlug = $db->query("SHOW COLUMNS FROM geo_category_pages LIKE 'service_slug'")->fetch();
if (!$hasSvcSlug) {
    $db->exec("ALTER TABLE geo_category_pages
        ADD COLUMN service_slug VARCHAR(60) NOT NULL DEFAULT '' COMMENT '子服務 slug（空=大分類層；cleaning=清潔子服務）' AFTER category_id,
        ADD COLUMN service_name VARCHAR(60) NOT NULL DEFAULT '' COMMENT '子服務顯示名（清潔）' AFTER service_slug");
    echo "ALTER：已加 service_slug + service_name 兩欄\n";
} else {
    echo "ALTER：service_slug 欄已存在，略過加欄\n";
}

// ── 2. 換 unique key ──────────────────────────────────────
// 取得目前所有 index 名稱
$idx = [];
foreach ($db->query("SHOW INDEX FROM geo_category_pages") as $r) {
    $idx[$r['Key_name']] = true;
}
if (isset($idx['uq_city_cat'])) {
    $db->exec("ALTER TABLE geo_category_pages DROP INDEX uq_city_cat");
    echo "INDEX：已 DROP 舊 uq_city_cat (city_slug, category_id)\n";
} else {
    echo "INDEX：uq_city_cat 不存在，略過 drop\n";
}
if (!isset($idx['uq_city_cat_svc'])) {
    $db->exec("ALTER TABLE geo_category_pages ADD UNIQUE KEY uq_city_cat_svc (city_slug, category_id, service_slug)");
    echo "INDEX：已 ADD uq_city_cat_svc (city_slug, category_id, service_slug)\n";
} else {
    echo "INDEX：uq_city_cat_svc 已存在，略過 add\n";
}

// ── 3. 現有清潔內容歸位成 cleaning 子服務 ─────────────────
// 目前所有 home-service 的 geo 列都是清潔內容 → 設成 service_slug='cleaning'
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
if ($homeId) {
    $mig = $db->prepare("UPDATE geo_category_pages
        SET service_slug='cleaning', service_name='清潔'
        WHERE category_id = ? AND service_slug = ''");
    $mig->execute([$homeId]);
    echo "MIGRATE：{$mig->rowCount()} 筆 home-service 清潔內容 → service_slug='cleaning'\n";
} else {
    echo "MIGRATE：找不到 home-service 分類，略過資料遷移\n";
}

// ── 4. 現況 ───────────────────────────────────────────────
echo "現況：\n";
$q = $db->query("SELECT g.city_slug, c.slug cat_slug, g.service_slug, g.service_name, g.is_active
    FROM geo_category_pages g JOIN categories c ON g.category_id=c.id
    ORDER BY g.city_slug, c.id, g.service_slug");
foreach ($q as $r) {
    $svc = $r['service_slug'] !== '' ? "/{$r['service_slug']}({$r['service_name']})" : '（大分類層）';
    printf("  /city/%s/%s%s  act=%d\n", $r['city_slug'], $r['cat_slug'], $svc, $r['is_active']);
}
