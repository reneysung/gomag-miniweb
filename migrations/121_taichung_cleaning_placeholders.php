<?php
// ============================================================
// Migration 121 — 台中裝潢細清：亞雷歸位 + 8 家 placeholder
// ------------------------------------------------------------
// 背景：「台中裝潢細清」GSC 表現差，細清頁 0 店（空狀態）。
// 診斷發現 #215 亞雷（台中北屯、主打台中細清、有 rre mini-site）
// 建檔疏漏：category_id=8(專業服務) 應為 2(居家服務)、且 0 個服務關鍵字
// （對照彰化分身 #219 標好好的 11/12/13）。
//
// A. #215 亞雷：category 8→2 + 複製 #219 的 keyword set (11,12,13)
// B. 8 家台中真實清潔業者 placeholder（仿鬥牛士 #206/#207 模式）
//    資料來源＝各家官網/公開頁面的 NAP 公開資訊（2026-06-11 抓取），
//    不捏造評價與服務細節，tagline 僅轉述官網自述服務項目。
// C. 每家標 cleaning(#1) + reno-detail(#13)
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/121_taichung_cleaning_placeholders.php
// 冪等：slug 存在則跳過 INSERT、keyword 用 INSERT IGNORE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ════════════════════════════════════════════
// A. 亞雷 #215 歸位
// ════════════════════════════════════════════
echo "═══ A. #215 亞雷清潔團隊 歸位 ═══\n";
$db->exec("UPDATE clients SET category_id=2 WHERE id=215");
echo ">> category_id 8(專業服務) → 2(居家服務)\n";
foreach ([11, 12, 13] as $kid) {
    $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (215, ?)")->execute([$kid]);
}
echo ">> 標 keyword #11 清潔公司 / #12 居家清潔 / #13 裝潢細清（比照彰化分身 #219）\n";

// ════════════════════════════════════════════
// B. 8 家 placeholder（資料＝官網公開 NAP）
// ════════════════════════════════════════════
echo "\n═══ B. 8 家台中清潔業者 placeholder ═══\n";

$stores = [
    [
        'slug' => 'taichung-jie-housekeeper', 'brand' => '潔管家企業社',
        'tagline' => '大台中裝潢清潔/細清、入住退租空屋清潔、居家清潔、家電清洗',
        'phone' => '0905-757-766', 'address' => '台中市（大台中地區到府服務）',
    ],
    [
        'slug' => 'taichung-uchan-clean', 'brand' => '宇橙清潔管理',
        'tagline' => '台中、彰化、南投裝潢後細清、空屋清潔、商業空間清潔',
        'phone' => '04-37005391', 'address' => '台中市西屯區台灣大道二段910號',
    ],
    [
        'slug' => 'taichung-pinchen-clean', 'brand' => '品宸環境清潔有限公司',
        'tagline' => '台中裝潢後細清、交屋清潔、居家清潔、外牆清洗、水塔清洗',
        'phone' => '0981-979700', 'address' => '台中市烏日區榮泰街146號6樓',
    ],
    [
        'slug' => 'taichung-jioucheng-clean', 'brand' => '久承清潔環保',
        'tagline' => '台中裝潢後細清、交屋清潔、辦公室廠房清潔、地板清洗打蠟',
        'phone' => '0912-600-007', 'address' => '台中市南區南平路337號',
    ],
    [
        'slug' => 'taichung-yaju-clean', 'brand' => '亞巨清潔企業社',
        'tagline' => '台中裝潢後細清、地板高壓清洗、水塔外牆清洗、廠辦清潔',
        'phone' => '04-2298-9008', 'address' => '台中市北區文心路四段182號14樓之2',
    ],
    [
        'slug' => 'taichung-dr-clean', 'brand' => 'Dr.Clean 清潔大夫',
        'tagline' => '台中裝潢細清專門店、裝潢後清潔、空屋清潔',
        'phone' => '04-2463-2483', 'address' => '台中市西屯區西屯路三段160之43號',
    ],
    [
        'slug' => 'taichung-tsanhuang-clean', 'brand' => '燦煌居家清潔（廖小姐居家清潔）',
        'tagline' => '台中、彰化、南投裝潢後清潔、居家清潔、空屋清潔、辦公室清潔',
        'phone' => '0916-998036', 'address' => '台中市西區台灣大道二段239號13樓',
    ],
    [
        'slug' => 'taichung-aiwu-clean', 'brand' => '愛屋居家清潔',
        'tagline' => '台中裝潢後清潔、居家清潔、定時清潔、辦公室清潔',
        'phone' => '04-24528886', 'address' => '台中市西屯區西屯路三段57之1號',
    ],
];

$ins = $db->prepare("INSERT INTO clients
    (subdomain, slug, brand_name, tagline, industry, category_id, phone, address, city_slug,
     is_active, has_minisite, store_template, minisite_template, is_placeholder)
    VALUES (?, ?, ?, ?, '清潔服務', 2, ?, ?, 'taichung', 1, 0, '_default', '_default', 1)");
$tagStmt = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");

foreach ($stores as $s) {
    $exists = $db->prepare("SELECT id FROM clients WHERE slug=?");
    $exists->execute([$s['slug']]);
    $cid = $exists->fetchColumn();
    if ($cid) {
        echo "  ⏭ skip {$s['brand']}（slug 已存在 #{$cid}）\n";
    } else {
        $ins->execute([$s['slug'], $s['slug'], $s['brand'], $s['tagline'], $s['phone'], $s['address']]);
        $cid = (int)$db->lastInsertId();
        echo "  ✅ #{$cid} {$s['brand']}\n";
    }
    // C. 標 cleaning + reno-detail
    $tagStmt->execute([$cid, 1]);   // cleaning 清潔公司
    $tagStmt->execute([$cid, 13]);  // reno-detail 裝潢細清
}

// ════════════════════════════════════════════
// 驗證
// ════════════════════════════════════════════
echo "\n═══ 驗證：台中 細清/清潔 頁列店 ═══\n";
foreach (['reno-detail' => '裝潢細清', 'cleaning' => '清潔公司'] as $svc => $name) {
    $sql = "SELECT cl.id, cl.brand_name, cl.is_placeholder FROM clients cl
        JOIN client_service_keywords csk ON csk.client_id=cl.id
        JOIN service_keywords sk ON sk.id=csk.service_keyword_id AND sk.category_id=2 AND sk.is_active=1
        LEFT JOIN client_city_pages ccp ON ccp.client_id=cl.id AND ccp.city_slug='taichung' AND ccp.is_active=1
        WHERE cl.is_active=1 AND (cl.city_slug='taichung' OR ccp.client_id IS NOT NULL)
          AND COALESCE(NULLIF(sk.page_slug,''), sk.slug) = ?
        GROUP BY cl.id ORDER BY cl.is_placeholder ASC, cl.id DESC";
    $st = $db->prepare($sql);
    $st->execute([$svc]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "\n[{$name} /city/taichung/home-service/{$svc}] " . count($rows) . " 家：\n";
    foreach ($rows as $r) {
        $tag = $r['is_placeholder'] ? '⚪ placeholder' : '🟢 正式';
        printf("  %s #%-4d %s\n", $tag, $r['id'], $r['brand_name']);
    }
}

echo "\n═══ #215 亞雷最終狀態 ═══\n";
$r = $db->query("SELECT c.category_id, GROUP_CONCAT(sk.name SEPARATOR '/') tags
    FROM clients c LEFT JOIN client_service_keywords csk ON csk.client_id=c.id
    LEFT JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE c.id=215 GROUP BY c.id")->fetch(PDO::FETCH_ASSOC);
echo "  category_id={$r['category_id']} tags={$r['tags']}\n";
echo "\n完成。預覽：https://www.gomag.com.tw/city/taichung/home-service/reno-detail\n";
