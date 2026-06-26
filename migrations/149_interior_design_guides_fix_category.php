<?php
// ============================================================
// Migration 149 — 修室內設計攻略分類錯置（攻略掛不上子服務頁的 bug）
// ------------------------------------------------------------
// 現象：高雄/台中/台南室內設計子服務頁「沒攻略」(Reney 2026 回報)。
// 根因：室內設計關鍵字/geo 頁已在 category 8（專業服務），但 7 篇室內設計攻略
//   還停在 category 2（居家服務）。citycat 撈攻略條件 = (category_id=本頁分類)
//   AND city AND FIND_IN_SET(service_slug)（citycat.php:364-365）→ cat 對不上 →
//   攻略一篇都掛不上室內設計頁（雖然 service_slug/city/noindex 都對）。
// 修：把這 7 篇 interior-design 攻略改 category_id=8（與現行 IA 一致）。
//   這些攻略 service_slug 都只標 interior-design（非多分類），改 cat 不影響別頁。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/149_interior_design_guides_fix_category.php
// 冪等：UPDATE，重跑安全。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 安全起見只動 service_slug 僅含 interior-design 的攻略（避免誤改多分類文）
$rows = $db->query("SELECT id, slug, category_id, service_slug, city_slug
    FROM guides WHERE FIND_IN_SET('interior-design', service_slug) > 0")->fetchAll(PDO::FETCH_ASSOC);

echo "=== 室內設計攻略 分類修正前 ===\n";
$toFix = [];
foreach ($rows as $r) {
    $only = (trim($r['service_slug']) === 'interior-design');
    echo sprintf("  #%-3d cat=%s city=%-9s svc=[%s] %s %s\n",
        $r['id'], $r['category_id'], $r['city_slug'] ?: '(通用)', $r['service_slug'], $r['slug'],
        $only ? '' : '← 多分類, 跳過(不動避免影響別頁)');
    if ($only && (int)$r['category_id'] !== 8) $toFix[] = $r['id'];
}

if ($toFix) {
    $in = implode(',', array_fill(0, count($toFix), '?'));
    $db->prepare("UPDATE guides SET category_id=8 WHERE id IN ($in)")->execute($toFix);
    echo "\n✅ 已改 " . count($toFix) . " 篇 → category_id=8（專業服務）: #" . implode(' #', $toFix) . "\n";
} else {
    echo "\n⏭ 無需修正（可能已套用）\n";
}

// 驗證：模擬高雄/台中/台南室內設計頁現在撈得到幾篇
echo "\n=== 驗證 citycat 各城室內設計頁撈攻略 ===\n";
foreach (['kaohsiung','taichung','tainan','taitung'] as $c) {
    $q = $db->prepare("SELECT slug FROM guides WHERE status='published'
        AND (category_id=8 OR category_id IS NULL) AND city_slug=?
        AND FIND_IN_SET('interior-design', service_slug) > 0
        ORDER BY published_at DESC, id DESC LIMIT 4");
    $q->execute([$c]);
    $g = $q->fetchAll(PDO::FETCH_COLUMN);
    echo "  {$c}: " . count($g) . " 篇 " . ($g ? "(" . implode("、", $g) . ")" : "(無攻略)") . "\n";
}
echo "\n實頁驗證：\n  https://www.gomag.com.tw/city/kaohsiung/professional/interior-design\n  https://www.gomag.com.tw/city/taitung/professional/interior-design\n";
