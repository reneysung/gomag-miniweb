<?php
// ============================================================
// Migration 147 — SEO 改造 Round 3：noindex 弱重複城市攻略（反 scaled-content）
// ------------------------------------------------------------
// 方案 A（Reney 核可）：把近乎雷同、~0 曝光的城市攻略設 noindex,follow，
//   移除 Google 眼中的 doorway/scaled-content 足跡，但頁面仍留站上（城市頁相關
//   攻略槽+內鏈照用），可逆（拿掉 noindex 即可）。
//   保留索引：通用版(cleaning-detail-vs-basic)、各城裝潢細清懶人包、台南清潔公司
//   推薦懶人包、台中冷氣(差異化版)。
//
// 機制：guides 加 noindex 欄（之後可接後台 checkbox）；guide.php 讀旗標→$metaRobots。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/147_guides_noindex_dedupe.php
// 冪等：欄位查存在才加；UPDATE 重跑安全。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1) 加 noindex 欄（MySQL8/MariaDB 都安全）
$has = (int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='guides' AND COLUMN_NAME='noindex'")->fetchColumn();
if (!$has) {
    $db->exec("ALTER TABLE guides ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0");
    echo "✅ 已加 guides.noindex 欄\n";
} else {
    echo "⏭ guides.noindex 欄已存在\n";
}

// 2) 標 noindex（19 篇弱重複城市版）
$slugs = [
    // 冷氣指南 弱城 ×5（台中差異化版保留索引）
    'taipei-aircon-clean-guide','newtaipei-aircon-clean-guide','taoyuan-aircon-clean-guide',
    'tainan-aircon-clean-guide','kaohsiung-aircon-clean-guide',
    // 粗清vs細清 城市版 ×5（通用 cleaning-detail-vs-basic 保留索引）
    'changhua-rough-vs-detail-cleaning','chiayi-rough-vs-detail-cleaning','kaohsiung-rough-vs-detail-cleaning',
    'taichung-rough-vs-detail-cleaning','tainan-rough-vs-detail-cleaning',
    // 清潔公司怎麼挑 城市版 ×4（台南推薦懶人包保留索引）
    'changhua-cleaning-company-howto','chiayi-cleaning-company-howto',
    'kaohsiung-cleaning-company-howto','taichung-cleaning-company-howto',
    // 居家清潔指南 城市版 ×5
    'changhua-home-cleaning-guide','chiayi-home-cleaning-guide','kaohsiung-home-cleaning-guide',
    'taichung-home-cleaning-guide','tainan-home-cleaning-guide',
];
$in = implode(',', array_fill(0, count($slugs), '?'));
// 先全部歸零（避免之前誤標殘留），再標這批
$db->exec("UPDATE guides SET noindex=0 WHERE noindex=1");
$db->prepare("UPDATE guides SET noindex=1 WHERE slug IN ($in)")->execute($slugs);

// 3) 報告
$set = $db->query("SELECT slug FROM guides WHERE noindex=1 ORDER BY slug")->fetchAll(PDO::FETCH_COLUMN);
echo "\n已標 noindex 共 " . count($set) . " 篇（預期 " . count($slugs) . "）：\n";
foreach ($set as $s) echo "  - {$s}\n";
$miss = array_diff($slugs, $set);
if ($miss) { echo "\n⚠️ 下列 slug 沒對到（DB 找不到，請查）：\n"; foreach ($miss as $m) echo "  - {$m}\n"; }

echo "\n保留索引的清潔家族重點頁（抽查 noindex 應為 0）：\n";
foreach (['cleaning-detail-vs-basic','tainan-cleaning-company-recommend','taichung-aircon-clean-guide',
          'taipei-fine-clean-recommend','regular-cleaning-frequency'] as $k) {
    $v = $db->query("SELECT noindex FROM guides WHERE slug='{$k}'")->fetchColumn();
    echo "  - {$k}: noindex=" . ($v===false?'(無此篇)':$v) . "\n";
}
