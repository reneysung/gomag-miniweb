<?php
// ============================================================
// Migration 173 — 停用兩個測試帳號 + 修正兩筆錯誤的城市/地址欄位
// ------------------------------------------------------------
// 1) 停用測試帳號（Reney 2026-07-20 確認）
//    /store/flowtest「這是一個設計流程測試用的广广广広广客戶。」
//    /store/reney  「122344t35g2hynjume」
//    兩筆 is_active=1 且非 placeholder → 目前公開可爬、也在 sitemap 裡。
//    改 is_active=0：store.php:41 查詢帶 is_active=1 → 該頁直接 404，
//    sitemap.php 也不再收錄。不刪資料（clients 是多張表的外鍵中心）。
//
// 2) beefhotpot01 牛老大涮牛肉：city_slug / address 修正（Reney 確認「是高雄沒錯」）
//    原：city_slug=tainan、address=「台南市自前三路281」
//    該頁內文寫的是高雄二店，地址取自頁面內容本身：高雄市前金區自強三路281號
//    （原欄位是這個地址的損毀版：自強三路 → 自前三路、高雄 → 台南）。
//
// 3) hotpot4 貍偷聚門鍋物：❌ 已撤回，勿再套用
//    原本要改，理由是「address 欄被填成店名（台南市貍偷聚門鍋物）」——但那是本機
//    過期 DB 才有的狀況。伺服器上的原值是「台南市中西區海安路二段90號」，
//    前提不成立，2026-07-20 已還原成該原值。
//    ⚠️ 未解：DB 的海安路二段90號 與 頁面內文的東區崇善路205巷5號 互相矛盾，
//       兩個都是站內資料，無法從內部判斷哪個是現址 → 待 Reney 跟店家確認。
//
// ⚠️ 地址一律取自該店自己頁面的內容，不從外部查、不推測。
// 不動：其他任何欄位與其他客戶。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/173_deactivate_test_clients_fix_city.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 1) 停用測試帳號 ──────────────────────────────────────
$sel = $db->prepare("SELECT id, brand_name, is_active FROM clients WHERE slug = ?");
$off = $db->prepare("UPDATE clients SET is_active = 0 WHERE id = ?");
foreach (['flowtest', 'reney'] as $slug) {
    $sel->execute([$slug]);
    if (!$c = $sel->fetch()) { echo "  ⚠️  查無 slug={$slug}\n"; continue; }
    if (!$c['is_active']) { echo "  ⏭  {$slug}（{$c['brand_name']}）本來就是停用\n"; continue; }
    $off->execute([$c['id']]);
    echo "  ⏸  已停用 {$slug}（{$c['brand_name']}，id={$c['id']}）→ /store/{$slug} 將回 404\n";
}

// ── 2)(3) 修正城市 / 地址 ────────────────────────────────
$fixes = [
    'beefhotpot01' => ['city_slug' => 'kaohsiung', 'address' => '高雄市前金區自強三路281號'],
    // 'hotpot4' 已撤回，見上方說明第 3 點 — 不要重新加回來
];
$selFix = $db->prepare("SELECT id, brand_name, city_slug, address FROM clients WHERE slug = ?");
$updFix = $db->prepare("UPDATE clients SET city_slug = ?, address = ? WHERE id = ?");
foreach ($fixes as $slug => $f) {
    $selFix->execute([$slug]);
    if (!$c = $selFix->fetch()) { echo "  ⚠️  查無 slug={$slug}\n"; continue; }
    $updFix->execute([$f['city_slug'], $f['address'], $c['id']]);
    echo "  ✅ {$slug}（{$c['brand_name']}）\n";
    echo "       city_slug：{$c['city_slug']} → {$f['city_slug']}\n";
    echo "       address  ：{$c['address']} → {$f['address']}\n";
}

echo "\n完成。未動：其他欄位與其他客戶。\n";
