<?php
// ============================================================
// Migration 049 — 台南/高雄 裝潢細清懶人包 標題統一加「懶人包」字樣
// ------------------------------------------------------------
// 原標題「…推薦｜2026 嚴選 7 家…」看不出是懶人包；統一改「…懶人包｜…」。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/049_unify_lanrenbao_titles.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$rows = [
  'tainan-fine-clean-recommend' => [
     '台南裝潢細清懶人包｜2026 嚴選 7 家在地口碑',
     '台南裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑',
  ],
  'kaohsiung-fine-clean-recommend' => [
     '高雄裝潢細清懶人包｜2026 嚴選 7 家在地口碑',
     '高雄裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑',
  ],
];
$stmt = $db->prepare("UPDATE guides SET title=?, meta_title=? WHERE slug=?");
foreach ($rows as $slug => $t) {
    $stmt->execute([$t[0], $t[1], $slug]);
    printf("UPDATE %-32s rows=%d  → %s\n", $slug, $stmt->rowCount(), $t[0]);
}
echo "完成。\n";
