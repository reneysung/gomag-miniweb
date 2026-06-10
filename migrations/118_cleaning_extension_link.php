<?php
// ============================================================
// Migration 118 — cleaning + reno-detail 補 6 城延伸內鏈
// ------------------------------------------------------------
// 剩餘孤立：
//   cleaning: changhua/chiayi/pingtung/taitung（4 城）
//   reno-detail: changhua/chiayi（2 城）
//
// 用 v2 marker 在 cleaning-detail-vs-basic 通用篇尾部 append 第二段延伸內鏈。
// 因 113 已 append 過 7 都（v1 marker），用獨立 v2 marker 確保冪等不衝突。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/118_cleaning_extension_link.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$marker = '<!-- related-services-extension-v2 -->';
$cityNames = [
    'changhua' => '彰化', 'chiayi' => '嘉義', 'pingtung' => '屏東', 'taitung' => '台東',
];

$row = $db->prepare("SELECT body_html FROM guides WHERE slug='cleaning-detail-vs-basic'");
$row->execute();
$body = $row->fetchColumn();
if (!$body) { echo "❌ 找不到 cleaning-detail-vs-basic\n"; exit; }
if (strpos($body, $marker) !== false) { echo "⏭ skip（已有 v2 marker）\n"; exit; }

$append = "\n\n{$marker}\n<h2 id=\"related-services-ext\">延伸閱讀：更多縣市居家清潔與細清服務</h2>\n";
$append .= "<p>除上述 7 都外，店家好口碑也收錄以下縣市的清潔／細清服務：</p>\n<ul>\n";
foreach ($cityNames as $c => $cn) {
    $append .= "  <li><strong>{$cn}</strong>：";
    $append .= "<a href=\"/city/{$c}/home-service/cleaning\">{$cn}清潔公司</a>";
    if (in_array($c, ['changhua', 'chiayi'])) {
        $append .= "｜<a href=\"/city/{$c}/home-service/reno-detail\">{$cn}裝潢細清</a>";
    }
    $append .= "</li>\n";
}
$append .= "</ul>\n<p>建議：搬家退租／裝潢後細清屬高難度清理（粉塵、矽利康殘膠、玻璃水痕），費用約一般清潔的 1.5–2 倍。日常維護則可挑離家近的在地清潔公司，省搬運費。</p>\n";

$newBody = $body . $append;
$db->prepare("UPDATE guides SET body_html=? WHERE slug='cleaning-detail-vs-basic'")->execute([$newBody]);
echo ">> UPDATE cleaning-detail-vs-basic append 6 城延伸內鏈\n";

// 驗證
echo "\n═══ 4 城 cleaning + 2 城 reno-detail 內鏈數 ═══\n";
foreach ([
    ['changhua','cleaning'],['chiayi','cleaning'],['pingtung','cleaning'],['taitung','cleaning'],
    ['changhua','reno-detail'],['chiayi','reno-detail'],
] as [$c, $s]) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$c}/home-service/{$s}%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %s/%s → %d 篇 guide 內鏈\n", $flag, $c, $s, $n);
}

// 階段 1 完成度盤點
echo "\n═══ 階段 1 完成度盤點 ═══\n";
$cats = $db->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$isolatedCount = 0;
foreach ($db->query("SELECT city_slug, category_id, service_slug FROM geo_category_pages WHERE service_slug<>'' AND is_active=1") as $r) {
    $url = "/city/{$r['city_slug']}/{$cats[$r['category_id']]}/{$r['service_slug']}";
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%{$url}%"]);
    if ((int)$st->fetchColumn() === 0) $isolatedCount++;
}
$total = (int)$db->query("SELECT COUNT(*) FROM geo_category_pages WHERE service_slug<>'' AND is_active=1")->fetchColumn();
printf("  剩餘孤立：%d 個 / 總 %d 個（%.0f%%）\n", $isolatedCount, $total, 100 * $isolatedCount / max(1, $total));
echo "\n完成。\n";
