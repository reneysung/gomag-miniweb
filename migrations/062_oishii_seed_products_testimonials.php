<?php
// migrations/062_oishii_seed_products_testimonials.php
// 為奧喜長崎蛋糕（client_id=220）seed：
//   - 1 個 service block，內含 4 個產品（蜂蜜／黑糖／抹茶長崎 + 綜合禮盒）
//   - 3 條 demo 評價（標 source='demo'，前台可顯示也好標記）
//
// 注意：idempotent — 先檢查是否已 seed 過，避免重複插入
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/062_oishii_seed_products_testimonials.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();

const OISHII_ID = 220;

echo "=== Migration 062: 奧喜 seed 產品+評價 ===\n\n";

// ─── 1. Service block（4 個產品）─────────────────────
$exist = (int)$db->query("SELECT COUNT(*) FROM store_blocks WHERE client_id = " . OISHII_ID . " AND type = 'service'")->fetchColumn();
if ($exist > 0) {
    echo "⏭  service block 已存在（$exist 筆），跳過產品 seed\n";
} else {
    $products = [
        [
            'name'       => '蜂蜜長崎蛋糕',
            'short_desc' => '經典原味，蜂蜜香氣濃郁，蜜糖底結晶綿密。每日限量現烤。',
            'price'      => 'NT$ 380 / 條',
            'image'      => 'uploads/clients/oishii/oishii-product-slices.jpg',
        ],
        [
            'name'       => '黑糖蜜長崎蛋糕',
            'short_desc' => '沖繩黑糖蜜熬製，深焙香醇，回甘層次豐富。',
            'price'      => 'NT$ 420 / 條',
            'image'      => '',
        ],
        [
            'name'       => '抹茶長崎蛋糕',
            'short_desc' => '京都宇治抹茶粉，鮮綠透亮，茶香清雅不苦澀。',
            'price'      => 'NT$ 460 / 條',
            'image'      => '',
        ],
        [
            'name'       => '綜合禮盒・三入',
            'short_desc' => '蜂蜜＋黑糖＋抹茶各一條，金箔燙印禮盒包裝。商務、彌月、節慶皆宜。',
            'price'      => 'NT$ 1,280 / 盒',
            'image'      => 'uploads/clients/oishii/oishii-package-gold.jpg',
        ],
    ];

    $blockData = json_encode([
        'title'    => '看板商品',
        'subtitle' => '每日限量現烤，預約取貨',
        'items'    => $products,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt = $db->prepare("
        INSERT INTO store_blocks (client_id, type, data, sort_order, is_active)
        VALUES (?, 'service', ?, 10, 1)
    ");
    $stmt->execute([OISHII_ID, $blockData]);
    echo "✅ Seed 1 個 service block（4 個產品）\n";
}

// ─── 2. Testimonials（3 條 demo）────────────────────
$exist = (int)$db->query("SELECT COUNT(*) FROM testimonials WHERE client_id = " . OISHII_ID . " AND source = 'demo'")->fetchColumn();
if ($exist > 0) {
    echo "⏭  demo testimonials 已存在（$exist 筆），跳過\n";
} else {
    $testimonials = [
        [
            'name'    => '蔡小姐',
            'rating'  => 5,
            'content' => '台中朋友介紹來買，第一次吃到底部蜜糖結晶這麼明顯的長崎蛋糕。包裝盒很精緻，後來商務送禮都指定這家，客戶反應極好。',
        ],
        [
            'name'    => '林先生',
            'rating'  => 5,
            'content' => '北屯松竹路本店環境很有日式老舖的氛圍，店員會仔細介紹三款風味的差異。我自己最愛黑糖蜜的版本，當天現烤帶回桃園還是很濕潤。',
        ],
        [
            'name'    => '陳太太',
            'rating'  => 5,
            'content' => '彌月禮盒選了奧喜，金箔包裝跟朱紅腰封拍照超有質感。長輩收到都讚不絕口，特別問是哪一家。手工現烤蛋糕真的吃得出來不一樣。',
        ],
    ];

    $stmt = $db->prepare("
        INSERT INTO testimonials (client_id, reviewer_name, rating, content, source, sort_order, is_active)
        VALUES (?, ?, ?, ?, 'demo', ?, 1)
    ");
    $i = 1;
    foreach ($testimonials as $t) {
        $stmt->execute([OISHII_ID, $t['name'], $t['rating'], $t['content'], $i++]);
    }
    echo "✅ Seed 3 條 demo testimonials\n";
}

// ─── 3. 確認 oishii 仍在 japan-minimal ─────────────
$tpl = $db->query("SELECT store_template FROM clients WHERE id = " . OISHII_ID)->fetchColumn();
echo "\n奧喜目前模板：$tpl\n";

echo "\n=== 統計 ===\n";
$counts = $db->query("
    SELECT
      (SELECT COUNT(*) FROM store_blocks WHERE client_id=" . OISHII_ID . ") AS blocks,
      (SELECT COUNT(*) FROM testimonials WHERE client_id=" . OISHII_ID . ") AS testimonials
")->fetch();
printf("  blocks: %d   testimonials: %d\n", $counts['blocks'], $counts['testimonials']);
echo "\n✅ Migration 062 完成\n";
