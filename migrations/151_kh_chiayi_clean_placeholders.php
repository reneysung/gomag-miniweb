<?php
// ============================================================
// Migration 151 — 補 placeholder 店家：高雄裝潢細清 ×2 + 嘉義清潔 ×1
// ------------------------------------------------------------
// 子服務頁偏薄（高雄細清原只三峰 1 家、嘉義清潔薄）→ 補真實在地業者 placeholder
// （Reney 核可名單；NAP 2026-06 上各官網核對、不捏造；tagline 只轉述官網自述服務）。
// placeholder：is_placeholder=1（排正式客戶後、不虛報家數、薄頁 noindex,follow）。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/151_kh_chiayi_clean_placeholders.php
// 冪等：slug 查重。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// service_keyword id：cleaning=#1、reno-detail=#13
$stores = [
    [
        'slug' => 'kaohsiung-qianlun-clean', 'brand' => '仟倫清潔有限公司',
        'tagline' => '高雄鳳山清潔公司，空屋清潔、裝潢後細清、建商交屋清潔，居家、大樓、外牆、水塔清洗、石材養護都做。',
        'phone' => '07-7105200', 'address' => '高雄市鳳山區中信街80號', 'city' => 'kaohsiung',
        'keywords' => [13], // 裝潢細清
    ],
    [
        'slug' => 'kaohsiung-yitai-clean', 'brand' => '一台高雄清潔公司',
        'tagline' => '高雄鳳山清潔公司，居家交屋清潔、裝潢後清潔、大樓清潔、水塔清洗、地板打蠟與廢棄物處理。',
        'phone' => '0937-691184', 'address' => '高雄市鳳山區新富路94巷2號之4', 'city' => 'kaohsiung',
        'keywords' => [13], // 裝潢細清
    ],
    [
        'slug' => 'chiayi-jiangtai-clean', 'brand' => '將太清潔公司',
        'tagline' => '嘉義市文化路清潔公司，服務嘉義市與嘉義縣，居家清潔、裝潢細清、廠房辦公室、定期與鐘點清潔、火災清理、石材美容。',
        'phone' => '05-2325500', 'address' => '嘉義市文化路566巷61號', 'city' => 'chiayi',
        'keywords' => [1], // 清潔
    ],
];

$ins = $db->prepare("INSERT INTO clients
    (subdomain, slug, brand_name, tagline, industry, category_id, phone, address, city_slug,
     is_active, has_minisite, store_template, minisite_template, is_placeholder)
    VALUES (?, ?, ?, ?, '清潔服務', 2, ?, ?, ?, 1, 0, '_default', '_default', 1)");
$tag = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");

foreach ($stores as $s) {
    $ex = $db->prepare("SELECT id FROM clients WHERE slug=?");
    $ex->execute([$s['slug']]);
    $cid = $ex->fetchColumn();
    if ($cid) {
        echo "  ⏭ 已存在 #{$cid} {$s['brand']}\n";
    } else {
        $ins->execute([$s['slug'], $s['slug'], $s['brand'], $s['tagline'], $s['phone'], $s['address'], $s['city']]);
        $cid = (int)$db->lastInsertId();
        echo "  ✅ #{$cid} {$s['brand']}（{$s['city']}）\n";
    }
    foreach ($s['keywords'] as $kid) $tag->execute([$cid, $kid]);
}

// 驗證：各子服務頁店家數
echo "\n=== 驗證 子服務頁店家數 ===\n";
foreach ([['kaohsiung','reno-detail'],['chiayi','cleaning']] as [$c,$svc]) {
    $n = $db->query("SELECT COUNT(DISTINCT cl.id) FROM clients cl
        JOIN client_service_keywords csk ON csk.client_id=cl.id
        JOIN service_keywords sk ON sk.id=csk.service_keyword_id
        WHERE COALESCE(NULLIF(sk.page_slug,''),sk.slug)='{$svc}' AND cl.is_active=1 AND cl.city_slug='{$c}'")->fetchColumn();
    echo "  {$c}/{$svc}: {$n} 家\n";
}
echo "\n實頁：\n  https://www.gomag.com.tw/city/kaohsiung/home-service/reno-detail\n  https://www.gomag.com.tw/city/chiayi/home-service/cleaning\n";
