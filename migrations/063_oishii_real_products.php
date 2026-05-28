<?php
// migrations/063_oishii_real_products.php
// 用 banshin.com.tw 官網的真實產品資料 UPDATE 奧喜的 service block
// 取代之前 migration 062 寫的假產品（蜂蜜/黑糖蜜/抹茶/綜合禮盒）
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/063_oishii_real_products.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;

echo "=== Migration 063: 奧喜真實產品（取代假資料）===\n\n";

// 4 個代表 SKU（從 banshin.com.tw 官網實際抓的）
$products = [
    [
        'name'       => '長崎蛋糕・5 片',
        'short_desc' => '入門嚐鮮款。濃郁蛋香、麥芽醇厚，鬆軟濕潤綿密。',
        'price'      => 'NT$ 135',
        'image'      => 'uploads/clients/oishii/p1-castella-5p.jpg',
    ],
    [
        'name'       => '長崎蛋糕・12 片',
        'short_desc' => '經典分享款，最受歡迎尺寸。商務、家用兩相宜。',
        'price'      => 'NT$ 288',
        'image'      => 'uploads/clients/oishii/p2-castella-12p.jpg',
    ],
    [
        'name'       => '奧喜燒',
        'short_desc' => '本店蛋糕二次烘烤製成的蛋糕餅乾，香酥脆爽，茶點良伴。',
        'price'      => 'NT$ 135',
        'image'      => 'uploads/clients/oishii/p3-oishii-yaki.jpg',
    ],
    [
        'name'       => '小巧禮盒',
        'short_desc' => '長崎蛋糕＋奧喜燒組合，送禮、商務拜訪首選。',
        'price'      => 'NT$ 280',
        'image'      => 'uploads/clients/oishii/p4-mini-giftbox.jpg',
    ],
];

$blockData = json_encode([
    'title'     => '看板商品',
    'subtitle'  => '當日預訂、當日現烤，最新鮮的長崎蛋糕風味。',
    'more_note' => '另有長崎蛋糕 8 片 NT$ 225／16 片 NT$ 350／24 片 NT$ 560，現場或致電洽詢。',
    'items'     => $products,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 找現有 service block UPDATE，沒有則 INSERT
$blockId = $db->prepare("SELECT id FROM store_blocks WHERE client_id = ? AND type = 'service' ORDER BY sort_order LIMIT 1");
$blockId->execute([OISHII_ID]);
$existingId = $blockId->fetchColumn();

if ($existingId) {
    $db->prepare("UPDATE store_blocks SET data = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$blockData, $existingId]);
    echo "✅ Updated service block #$existingId（4 個真實 SKU + 更多 SKU 附註）\n";
} else {
    $db->prepare("INSERT INTO store_blocks (client_id, type, data, sort_order, is_active) VALUES (?, 'service', ?, 10, 1)")
       ->execute([OISHII_ID, $blockData]);
    echo "✅ Inserted new service block（4 個真實 SKU）\n";
}

// 驗證
echo "\n=== 驗證 ===\n";
$row = $db->prepare("SELECT data FROM store_blocks WHERE client_id = ? AND type = 'service'");
$row->execute([OISHII_ID]);
$data = json_decode($row->fetchColumn(), true);
echo "Title: " . ($data['title'] ?? '') . "\n";
echo "Items: " . count($data['items'] ?? []) . "\n";
foreach ($data['items'] ?? [] as $i => $it) {
    printf("  %d. %-20s  %s\n", $i + 1, $it['name'] ?? '', $it['price'] ?? '');
}
echo "More note: " . ($data['more_note'] ?? '') . "\n";
echo "\n✅ Migration 063 完成\n";
