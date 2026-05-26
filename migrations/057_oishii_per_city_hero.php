<?php
// ============================================================
// Migration 057 — 奧喜 6 行銷頁分配不同 hero 圖
// ------------------------------------------------------------
// 把桌面 4 張奧喜實拍 + 2 張產品圖分配給 6 個頁面（每頁不同視覺）：
//   /store/oishii (主)   → brand-curtain.jpg    (品牌門簾，最有代表性)
//   /store/oishii/taichung → store-exterior.jpg (店面夜景外觀，本地實體店感)
//   /store/oishii/taipei   → store-interior.jpg (店內紅牆精緻陳列，商務體面)
//   /store/oishii/newtaipei → grand-opening.jpg (開幕熱鬧，社區家族感)
//   /store/oishii/taoyuan  → castella.jpg       (長崎蛋糕產品，企業送禮代表)
//   /store/oishii/hsinchu  → yaki.jpg           (奧喜燒餅乾，商務伴手禮)
//
// 同時 og_image 比照（FB/LINE 分享預覽圖每頁也不同）。
//
// 跑法：HTTP_HOST=<host> php migrations/057_oishii_per_city_hero.php
// 冪等：UPDATE，可重複跑。圖檔須已存在於 uploads/clients/oishii/
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$clientId = (int)$db->query("SELECT id FROM clients WHERE subdomain='oishii' OR slug='oishii' LIMIT 1")->fetchColumn();
if (!$clientId) { echo "❌ 找不到 oishii 客戶\n"; exit(1); }

$base = 'uploads/clients/oishii/';

// ─── 主行銷頁 / 小官網 root（clients row）→ brand-curtain ───
$mainHero = $base . 'brand-curtain.jpg';
$db->prepare("UPDATE clients SET hero_image_path=?, store_og_image=?, minisite_og_image=? WHERE id=?")
   ->execute([$mainHero, $mainHero, $mainHero, $clientId]);
echo "✅ 主行銷頁/小官網 root → brand-curtain.jpg\n";

// ─── 5 城市各自的 hero + og ───
$assign = [
    'taichung'  => ['store-exterior.jpg', '店面夜景外觀'],
    'taipei'    => ['store-interior.jpg', '店內紅牆精緻陳列'],
    'newtaipei' => ['grand-opening.jpg',  '開幕熱鬧場景'],
    'taoyuan'   => ['castella.jpg',       '長崎蛋糕產品'],
    'hsinchu'   => ['yaki.jpg',           '奧喜燒餅乾'],
];

$upd = $db->prepare("UPDATE client_city_pages SET hero_image_path=?, store_og_image=?, minisite_og_image=? WHERE client_id=? AND city_slug=?");
foreach ($assign as $slug => [$img, $note]) {
    $path = $base . $img;
    $upd->execute([$path, $path, $path, $clientId, $slug]);
    printf("✅ %-10s → %s（%s）\n", $slug, $img, $note);
}

echo "\n=== 確認 DB 內所有 6 個頁面的 hero_image_path ===\n";
$check = $db->query("
    SELECT 'MAIN' as page, hero_image_path FROM clients WHERE id={$clientId}
    UNION ALL
    SELECT CONCAT('CITY ', city_slug) as page, hero_image_path FROM client_city_pages
    WHERE client_id={$clientId} ORDER BY page
")->fetchAll();
foreach ($check as $c) printf("  %-18s %s\n", $c['page'], $c['hero_image_path']);

echo "\n完成。記得 .htaccess 子網域開了之後也驗小官網 5 條 URL 的 hero。\n";
