<?php
// ============================================================
// Migration 126 — guides 加 service_slug 子服務標籤（修攻略跨子服務外溢）
// ------------------------------------------------------------
// 問題：citycat 子服務頁的「相關攻略」只比大分類(category_id)，不比子服務 →
// 例如彰化清潔頁撈到「全台室內設計/驗屋/油漆」通用文（既非清潔也非彰化），
// 因為彰化清潔專屬攻略只有 1 篇，其餘 3 格被最新通用文補滿。
//
// 修法：guides 加 service_slug（逗號分隔、可多服務），標好每篇屬於哪些子服務。
// 之後 citycat 在子服務頁用 FIND_IN_SET 過濾 → 清潔頁只出清潔文、木地板頁只出木地板文。
// 樞紐頁不受影響（仍以大分類總覽）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/126_guides_service_slug.php
// 冪等：ADD COLUMN IF NOT EXISTS 等價處理 + UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 1. 加欄位（已存在則略過）
$has = $db->query("SHOW COLUMNS FROM guides LIKE 'service_slug'")->fetch();
if (!$has) {
    $db->exec("ALTER TABLE guides ADD COLUMN service_slug VARCHAR(200) NOT NULL DEFAULT '' AFTER category_id");
    echo ">> ALTER：guides 加 service_slug 欄\n";
} else {
    echo ">> service_slug 欄已存在，略過 ALTER\n";
}

// 2. slug → service_slug（逗號分隔，可多服務）
$map = [
    // ── 居家服務 ──
    'cleaning-detail-vs-basic'                 => 'cleaning,reno-detail',
    'moving-cleaning-cost'                      => 'cleaning',
    'regular-cleaning-frequency'                => 'cleaning',
    'taiwan-flooring-guide-2026'               => 'flooring',
    'taiwan-home-inspection-guide-2026'        => 'home-inspection',
    'taiwan-interior-design-guide-2026'        => 'interior-design',
    'taiwan-painting-waterproof-guide-2026'    => 'painting',
    'changhua-renovation-cleaning-guide'       => 'cleaning,reno-detail',
    'chiayi-townhouse-cleaning-guide'          => 'cleaning,reno-detail',
    'hsinchu-fine-clean-recommend'             => 'reno-detail',
    'hsinchu-flooring-guide'                    => 'flooring',
    'hsinchu-tech-fine-clean-guide'            => 'reno-detail,cleaning',
    'kaohsiung-aircon-clean-guide'             => 'aircon-clean',
    'kaohsiung-exterior-waterproof-guide'      => 'painting',
    'kaohsiung-fine-clean-recommend'           => 'reno-detail',
    'kaohsiung-flooring-guide'                  => 'flooring',
    'kaohsiung-humidity-mold-rust'             => 'painting,cleaning',
    'kaohsiung-old-apartment-renovation'       => 'interior-design',
    'kaohsiung-tsmc-home-renovation'           => 'interior-design',
    'kaohsiung-waterproof-guide'                => 'painting',
    'newtaipei-aircon-clean-guide'             => 'aircon-clean',
    'newtaipei-exterior-waterproof-guide'      => 'painting',
    'newtaipei-fine-clean-recommend'           => 'reno-detail',
    'newtaipei-flooring-guide'                  => 'flooring',
    'newtaipei-handover-fine-clean-guide'      => 'reno-detail,home-inspection',
    'newtaipei-waterproof-guide'                => 'painting',
    'pingtung-exterior-waterproof-guide'       => 'painting',
    'pingtung-townhouse-cleaning-guide'        => 'cleaning,reno-detail',
    'pingtung-waterproof-guide'                 => 'painting',
    'taichung-7th-luxury-renovation'           => 'interior-design',
    'taichung-aircon-clean-guide'              => 'aircon-clean',
    'taichung-exterior-waterproof-guide'       => 'painting',
    'taichung-fine-clean-recommend'            => 'reno-detail',
    'taichung-flooring-material-guide'         => 'flooring',
    'taichung-new-house-handover-inspection'   => 'home-inspection,reno-detail',
    'taichung-waterproof-guide'                 => 'painting',
    'tainan-aircon-clean-guide'                => 'aircon-clean',
    'tainan-cleaning-company-recommend'        => 'cleaning,reno-detail',
    'tainan-exterior-waterproof-guide'         => 'painting',
    'tainan-fine-clean-recommend'              => 'reno-detail',
    'tainan-handover-inspection-guide'         => 'home-inspection',
    'tainan-old-house-renovation'              => 'interior-design',
    'tainan-sofa-mattress-custom'              => 'furniture',
    'tainan-waterproof-guide'                   => 'painting',
    'taipei-aircon-clean-guide'                => 'aircon-clean',
    'taipei-exterior-waterproof-guide'         => 'painting',
    'taipei-fine-clean-recommend'              => 'reno-detail',
    'taipei-flooring-guide'                     => 'flooring',
    'taipei-move-out-fine-clean-guide'         => 'reno-detail,cleaning',
    'taipei-waterproof-guide'                   => 'painting',
    'taitung-homestay-relocation-cleaning-guide' => 'cleaning',
    'taoyuan-aircon-clean-guide'               => 'aircon-clean',
    'taoyuan-fine-clean-recommend'             => 'reno-detail',
    'taoyuan-flooring-guide'                    => 'flooring',
    'taoyuan-new-house-fine-clean-guide'       => 'reno-detail',
    // ── 餐飲 ──
    'taiwan-banquet-catering-guide-2026'       => 'banquet,banquet-catering,wedding-banquet,year-end-party',
    'banquet-catering-vs-restaurant-guide'     => 'banquet,banquet-catering',
    'kaohsiung-banquet-catering-guide'         => 'banquet-catering,banquet',
    'tainan-banquet-catering-guide'            => 'banquet-catering,banquet',
    'kaohsiung-group-dining-guide'             => 'hotpot,yakiniku,japanese,buffet',
    'tainan-izakaya-recommend'                 => 'japanese',
    'tainan-beef-soup-street-food'             => 'noodles',
    // tainan-meishi-recommend-12 = 全菜系城市級 listicle，留空（只在餐飲樞紐頁出現）
    // ── 汽車 ──
    'chiayi-car-wrap-vs-coating'               => 'car-wrap,coating',
    // ── 健康 ──
    'tainan-tcm-guide'                         => 'chinese-medicine',
];

echo "\n>> 標 service_slug\n";
$upd = $db->prepare("UPDATE guides SET service_slug=? WHERE slug=?");
$done = 0; $miss = [];
foreach ($map as $slug => $svc) {
    $upd->execute([$svc, $slug]);
    if ($upd->rowCount() > 0 || $db->query("SELECT 1 FROM guides WHERE slug=" . $db->quote($slug))->fetch()) {
        $done++;
    } else { $miss[] = $slug; }
}
printf("   已標 %d 篇\n", $done);
if ($miss) echo "   ⚠️ 找不到（map 有但 DB 無）：" . implode(', ', $miss) . "\n";

// 3. 列出「居家服務 + 餐飲 + 汽車 + 健康」裡仍未標的 published 攻略（留空=只出現在樞紐頁）
echo "\n>> 仍未標 service_slug 的 published 攻略（將只在樞紐頁顯示，不會誤入子服務頁）：\n";
foreach ($db->query("SELECT slug, city_slug, category_id FROM guides WHERE status='published' AND (service_slug='' OR service_slug IS NULL) ORDER BY category_id, slug") as $r) {
    printf("   [cat%s][%s] %s\n", $r['category_id'] ?: '-', $r['city_slug'] ?: '通用', $r['slug']);
}

// 4. 驗證：彰化清潔頁修後會撈到什麼（模擬 citycat 新查詢）
echo "\n>> 驗證：彰化清潔(cleaning)頁 修後攻略（FIND_IN_SET 過濾）\n";
$rg = $db->prepare("SELECT slug, title, city_slug, service_slug FROM guides
    WHERE status='published' AND (category_id=2 OR category_id IS NULL)
      AND (city_slug='changhua' OR city_slug IS NULL OR city_slug='')
      AND FIND_IN_SET('cleaning', service_slug) > 0
    ORDER BY (city_slug='changhua') DESC, published_at DESC, id DESC LIMIT 4");
$rg->execute();
foreach ($rg as $r) printf("   [%s] %s (svc=%s)\n", $r['city_slug'] ?: '通用', $r['title'], $r['service_slug']);

echo "\n完成。\n";
