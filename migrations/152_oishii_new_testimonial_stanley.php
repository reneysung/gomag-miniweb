<?php
// migrations/152_oishii_new_testimonial_stanley.php
// 奧喜長崎蛋糕（client_id=220）新增一篇真實部落客顧客分享
// 來源：史丹利 樂福（痞客邦 aronrandom）
//   https://aronrandom.pixnet.net/blog/posts/907945401122715493
//   「台中伴手禮推薦｜你不能不知道的奧喜長崎蛋糕！」
//
// 遵守「不能無中生有」原則：content 全部取自原文直接引句（口感 + 停車便利）。
// 冪等：先刪同 source_url 的舊列，再插入。
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/152_oishii_new_testimonial_stanley.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;

$review = [
    'name'    => '史丹利樂福',
    'rating'  => 5,
    'content' => '蛋香明顯、甜度乾淨、不黏手，鬆軟順口，甜得很乾淨不會膩。門口就有停車格，正正當當停進去，不用繞、不用臨停、不用看拖吊車臉色；店裡寬闊、冷氣超涼，乾淨又明亮，還有位子可以坐。',
    'url'     => 'https://aronrandom.pixnet.net/blog/posts/907945401122715493',
];

echo "=== Migration 152: 奧喜新增部落客顧客分享（史丹利樂福）===\n\n";

// 冪等：先移除同網址舊列
$db->prepare("DELETE FROM testimonials WHERE client_id = ? AND source_url = ?")
   ->execute([OISHII_ID, $review['url']]);

// 接在現有評論之後（sort_order 取現有最大值 + 1）
$max = (int) $db->query(
    "SELECT COALESCE(MAX(sort_order), 0) FROM testimonials WHERE client_id = " . OISHII_ID
)->fetchColumn();
$sortOrder = $max + 1;

$db->prepare(
    "INSERT INTO testimonials (client_id, reviewer_name, rating, content, source, source_url, sort_order, is_active)
     VALUES (?, ?, ?, ?, 'blog', ?, ?, 1)"
)->execute([OISHII_ID, $review['name'], $review['rating'], $review['content'], $review['url'], $sortOrder]);

echo "✅ 寫入 1 條真實部落客 quote（sort_order={$sortOrder}）\n\n";

// ─── 驗證 ───────────────────────────────────────────────
echo "=== 驗證：奧喜目前所有顧客分享 ===\n";
$tStmt = $db->prepare("SELECT reviewer_name, rating, source, sort_order, source_url FROM testimonials WHERE client_id = ? AND is_active = 1 ORDER BY sort_order, id");
$tStmt->execute([OISHII_ID]);
foreach ($tStmt->fetchAll() as $t) {
    printf("  #%d  %-12s ★%d  [%s] %s\n", $t['sort_order'], $t['reviewer_name'], $t['rating'], $t['source'], $t['source_url']);
}

echo "\n✅ Migration 152 完成\n";
