<?php
// ============================================================
// Migration 067 — food 分類補婚宴/尾牙子服務關鍵字 + 洪廚標 3 組
// ------------------------------------------------------------
// Phase 1: service_keywords 池調整（cat=1 food）
//   - 加 2 個主 slug：wedding-banquet（喜宴婚宴外燴）/ year-end-party（尾牙春酒外燴）
//   - 加 4 個同義詞 slug：wedding-catering/xiyan-catering→wedding-banquet,
//                          corporate-banquet/spring-banquet→year-end-party
//   - 清 3 個污染 slug：clean / cleaning / face（已查無依賴）
//
// Phase 2: 洪廚 (id=221) 標 3 組 client_service_keywords
//   - banquet-catering（辦桌外燴）
//   - wedding-banquet（喜宴婚宴外燴）
//   - year-end-party（尾牙春酒外燴）
//   不標 banquet（婚宴餐廳）— 洪廚是外燴到家、不是定點餐廳
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/067_food_banquet_subservices_and_hongchu_tags.php
// 冪等：INSERT IGNORE / DELETE IGNORE，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food'")->fetchColumn();
if (!$foodId) { echo "❌ food category not found\n"; exit(1); }
echo "ℹ️  food category_id={$foodId}\n\n";

// ─── Phase 1a: 清污染 ───────────────────────────────────────
echo "=== Phase 1a: 清污染 slug（clean/cleaning/face）===\n";
$polluted = ['clean', 'cleaning', 'face'];
$in = "'" . implode("','", $polluted) . "'";
$d = $db->exec("DELETE FROM service_keywords WHERE category_id={$foodId} AND slug IN ({$in})");
echo "✅ DELETE {$d} row\n";

// ─── Phase 1b: 加主 slug + 同義詞 ──────────────────────────
echo "\n=== Phase 1b: 加 2 主 slug + 4 同義詞 ===\n";
$keywords = [
    // [slug, name, page_slug(空=主slug), sort_order]
    ['wedding-banquet',   '喜宴婚宴外燴', '',                 110],
    ['wedding-catering',  '婚宴外燴',     'wedding-banquet',  111],
    ['xiyan-catering',    '喜宴外燴',     'wedding-banquet',  112],
    ['year-end-party',    '尾牙春酒外燴', '',                 120],
    ['corporate-banquet', '公司尾牙',     'year-end-party',   121],
    ['spring-banquet',    '春酒辦桌',     'year-end-party',   122],
];
$ins = $db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order, is_active) VALUES (?,?,?,?,?,1)");
foreach ($keywords as [$slug, $name, $pageSlug, $sort]) {
    $ins->execute([$foodId, $slug, $name, $pageSlug, $sort]);
    $aff = $ins->rowCount();
    $kind = $pageSlug ? "(同義→{$pageSlug})" : "[主 slug]";
    echo ($aff ? "✅" : "ℹ️ ") . " {$slug}  {$name}  {$kind}" . ($aff ? "" : " (已存在略過)") . "\n";
}

// ─── Phase 2: 洪廚標 3 組 ──────────────────────────────────
echo "\n=== Phase 2: 洪廚 (id=221) 標 client_service_keywords ===\n";
$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu'")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }

$tagSlugs = ['banquet-catering', 'wedding-banquet', 'year-end-party'];
foreach ($tagSlugs as $slug) {
    $skId = (int)$db->prepare("SELECT id FROM service_keywords WHERE category_id=? AND slug=?");
    $skId = $db->prepare("SELECT id FROM service_keywords WHERE category_id=? AND slug=?");
    $skId->execute([$foodId, $slug]);
    $kwId = (int)$skId->fetchColumn();
    if (!$kwId) { echo "⚠️  slug={$slug} 找不到，跳過\n"; continue; }

    $u = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?,?)");
    $u->execute([$cid, $kwId]);
    $aff = $u->rowCount();
    echo ($aff ? "✅" : "ℹ️ ") . " 標 {$slug} (kw_id={$kwId})" . ($aff ? "" : " (已標過略過)") . "\n";
}

echo "\n完成。\n";
echo "註：洪廚現在會出現在以下子服務頁的「店家列表」段（前提是該頁有 geo_category_pages 內容）：\n";
echo "  /city/{c}/food/banquet-catering（辦桌外燴）\n";
echo "  /city/{c}/food/wedding-banquet（喜宴婚宴外燴）\n";
echo "  /city/{c}/food/year-end-party（尾牙春酒外燴）\n";
echo "Phase 3：5 城 × 4 子服務的內容頁待 migration 068+ 處理。\n";
