<?php
// ============================================================
// Migration 122 — 開 3 個交叉頁（行情微調 + 啟用）
// ------------------------------------------------------------
// Reney 流程：交叉頁草稿 → 行情依在地實際修 → 啟用 → 前台確認。
// 1) kaohsiung/food/buffet      高雄吃到飽
// 2) tainan/auto-service/detailing  台南汽車美容
// 3) kaohsiung/home-service/kitchen 高雄系統廚具
//
// 行情修正（只動數字與草稿註記，其他原樣）：
// - 高雄吃到飽：飯店 buffet 1,000 → 1,100（漢來海港級平日午餐已破 1,100）
// - 台南汽車美容：鍍膜上緣 20,000 → 25,000（高階多層陶瓷鍍膜）
// - 高雄系統廚具：一字型 5–12 萬 → 6–15 萬、L 型 10 萬起 → 12 萬起
// - 三頁拿掉「（行情請依實際確認）」草稿註記
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/122_enable_3_geo_pages.php
// 冪等：str_replace 找不到舊字串就不動、is_active 重跑無害
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

function patchRow($db, $city, $cat, $svc, $patches) {
    $st = $db->prepare("SELECT intro_html, faqs FROM geo_category_pages WHERE city_slug=? AND category_id=? AND service_slug=?");
    $st->execute([$city, $cat, $svc]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) { echo "  ❌ {$city}/{$svc} row 不存在\n"; return; }
    $intro = $r['intro_html'];
    $faqs  = $r['faqs'];
    $n = 0;
    foreach ($patches as [$old, $new]) {
        if (strpos($intro, $old) !== false) { $intro = str_replace($old, $new, $intro); $n++; }
        if (strpos($faqs, $old) !== false)  { $faqs  = str_replace($old, $new, $faqs);  $n++; }
    }
    $db->prepare("UPDATE geo_category_pages SET intro_html=?, faqs=?, is_active=1 WHERE city_slug=? AND category_id=? AND service_slug=?")
       ->execute([$intro, $faqs, $city, $cat, $svc]);
    echo "  ✅ {$city}/{$svc} 套用 {$n} 處修改 + is_active=1\n";
}

echo "═══ 1) 高雄吃到飽 ═══\n";
patchRow($db, 'kaohsiung', 1, 'buffet', [
    ['高檔燒肉／飯店 buffet 可達 NT$1,000 以上（行情請依實際確認）', '高檔燒肉／飯店 buffet 可達 NT$1,100 以上'],
    ['飯店 buffet NT$1,000 起', '飯店 buffet NT$1,100 起'],
]);

echo "\n═══ 2) 台南汽車美容 ═══\n";
patchRow($db, 'tainan', 11, 'detailing', [
    ['鍍膜依等級約 NT$6,000–20,000、內裝深層清潔約 NT$2,000 起（行情請依實際確認）', '鍍膜依等級約 NT$6,000–25,000、內裝深層清潔約 NT$2,000 起'],
    ['依等級約 NT$6,000–20,000，能保護車漆', '依等級約 NT$6,000–25,000，能保護車漆'],
]);

echo "\n═══ 3) 高雄系統廚具 ═══\n";
patchRow($db, 'kaohsiung', 2, 'kitchen', [
    ['系統廚具一字型約 NT$5–12 萬、L 型更高（行情請依實際確認）', '系統廚具一字型約 NT$6–15 萬、L 型 NT$12 萬起'],
    ['一字型約 NT$5–12 萬、L 型 NT$10 萬起', '一字型約 NT$6–15 萬、L 型 NT$12 萬起'],
]);

// 驗證
echo "\n═══ 驗證 ═══\n";
foreach ([['kaohsiung',1,'buffet'],['tainan',11,'detailing'],['kaohsiung',2,'kitchen']] as [$c,$cat,$s]) {
    $st = $db->prepare("SELECT is_active, intro_html LIKE '%行情請依實際確認%' has_draft_note FROM geo_category_pages WHERE city_slug=? AND category_id=? AND service_slug=?");
    $st->execute([$c,$cat,$s]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    printf("  %s/%s active=%d 草稿註記殘留=%s\n", $c, $s, $r['is_active'], $r['has_draft_note'] ? '⚠️ 有' : '無');
}
echo "\n完成。\n";
