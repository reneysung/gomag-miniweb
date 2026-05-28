<?php
// ============================================================
// Migration 072 — 洪廚 menu block + 6 條 testimonials
// ------------------------------------------------------------
// menu block：8 道精選辦桌菜色（圖來自 4 篇痞客邦業配文授權）
//   - febgo（查克貝斯）4 道：真鯛刺身、龍蝦壽司、松露大蝦、真鯛鍋
//   - ocgo10（享受人生在台南）4 道：廣島生蠔、馬頭魚、松露燉飯、梅花豚
//   - 不寫 price（守「不能無中生有」紀律 — 行情由洪廚直接報價）
//   - desc 寫菜名 + 1 句料理特色
//
// testimonials：從 9 篇外部分享文章中抽 6 篇好評做 testimonials block
//   - 4 篇痞客邦 + WalkerLand + 94sis + stanleylove 3941 → 6 條
//   - 含 source_url 點回原文，符合「真評真連結」原則
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/072_hongchu_menu_block_and_testimonials.php
// 冪等：DELETE 舊 menu block + 6 條 testimonials by source_url 再 INSERT
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

// ─── 1. menu block ──────────────────────────────────────────
// 結構：{ title, subtitle, groups:[{name, items:[{name, desc, image, tag?}]}] }
$menuData = [
    'title' => '精選辦桌菜色',
    'subtitle' => '懷石擺盤 × 法式手法 × 台菜底蘊・以下為實場辦桌照片，菜色依季節食材與場合彈性調整',
    'groups' => [
        [
            'name' => '海鮮主打',
            'items' => [
                [
                    'name' => '真鯛鮪魚刺身盛合',
                    'desc' => '夢幻造型擺盤，當日新鮮真鯛與鮪魚現切，是婚宴開場的視覺亮點。',
                    'image' => 'uploads/menu/hongchu/febgo-01-sashimi.jpg',
                ],
                [
                    'name' => '龍蝦澎湖中卷職人壽司',
                    'desc' => '澎湖中卷搭龍蝦現場捏製職人壽司，呈現懷石料理的儀式感。',
                    'image' => 'uploads/menu/hongchu/febgo-02-lobster-sushi.jpg',
                ],
                [
                    'name' => '義式奶油松露煎海大蝦',
                    'desc' => '海大蝦以義式手法煎香、松露奶油提香，是融合台菜與西餐的代表菜。',
                    'image' => 'uploads/menu/hongchu/febgo-03-truffle-shrimp.jpg',
                ],
                [
                    'name' => '廣島生蠔',
                    'desc' => '進口廣島生蠔，現場開殼供應，鎖住海洋鮮甜。',
                    'image' => 'uploads/menu/hongchu/ocgo10-01-oyster.jpg',
                ],
                [
                    'name' => '脆鱗馬頭魚',
                    'desc' => '法式煎魚手法呈現馬頭魚的脆鱗與細膩肉質。',
                    'image' => 'uploads/menu/hongchu/ocgo10-02-fish.jpg',
                ],
                [
                    'name' => '真鯛魚首寄世鍋',
                    'desc' => '魚首入鍋熬煮的職人鍋物，膠質豐厚、湯頭濃郁。',
                    'image' => 'uploads/menu/hongchu/febgo-04-tai-pot.jpg',
                ],
            ],
        ],
        [
            'name' => '主餐／燉飯',
            'items' => [
                [
                    'name' => '梅花豚主餐',
                    'desc' => '低溫慢煮梅花豚，搭配時令蔬菜與醬汁，肉質軟嫩入口即化。',
                    'image' => 'uploads/menu/hongchu/ocgo10-04-pork.jpg',
                ],
                [
                    'name' => '黑松露麻油薏仁燉飯',
                    'desc' => '台式麻油配義式燉飯，黑松露點綴香氣，是混搭創意的代表菜。',
                    'image' => 'uploads/menu/hongchu/ocgo10-03-risotto.jpg',
                ],
            ],
        ],
    ],
];

// 先刪舊 menu block（如果有）再插
$db->exec("DELETE FROM store_blocks WHERE client_id={$cid} AND type='menu'");
$db->prepare("INSERT INTO store_blocks (client_id, type, data, sort_order, is_active) VALUES (?,?,?,?,1)")
   ->execute([$cid, 'menu', json_encode($menuData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 15]);
echo "✅ menu block 寫入（8 道菜，2 群組）\n";

// ─── 2. testimonials ───────────────────────────────────────
$testimonials = [
    [
        'reviewer_name' => '查克貝斯',
        'content' => '每道菜都能感受到總鋪師的用心，從食材選用到現場烹調，再到精美的擺盤，每個環節都很到位。',
        'source' => '痞客邦',
        'source_url' => 'https://febgo.pixnet.net/blog/post/367018057',
        'sort_order' => 10,
    ],
    [
        'reviewer_name' => '享受人生在台南',
        'content' => '精緻的份量與擺盤，是洪廚喜宴法式辦桌的一大特色。',
        'source' => '痞客邦',
        'source_url' => 'https://ocgo10.pixnet.net/blog/post/366815752',
        'sort_order' => 20,
    ],
    [
        'reviewer_name' => 'WalkerLand 窩客島',
        'content' => '洪廚喜宴不只是辦桌，根本就是把高級餐廳的水準帶到了婚宴現場，每個環節都很到位。',
        'source' => 'WalkerLand',
        'source_url' => 'https://www.walkerland.com.tw/article/view/414314',
        'sort_order' => 30,
    ],
    [
        'reviewer_name' => '94sis 婚禮好姊妹',
        'content' => '擺盤最具創意，美味排行榜前三名水準的辦桌菜色。',
        'source' => '部落客',
        'source_url' => 'https://94sis.com/wedding-banquet/',
        'sort_order' => 40,
    ],
    [
        'reviewer_name' => '史丹利樂福',
        'content' => '能把 450 桌辦得這麼漂亮，真的不簡單；公司行號或大型活動要辦桌，我會很推薦洪廚喜宴阿男師！',
        'source' => '部落客',
        'source_url' => 'https://www.stanleylove.com.tw/archives/3941',
        'sort_order' => 50,
    ],
    [
        'reviewer_name' => '鮪魚肚的秘笈',
        'content' => '菜色豐盛，每道菜都讓人食指大動，辦桌菜色豐富多樣，留下滿滿歡樂。',
        'source' => '痞客邦',
        'source_url' => 'https://jlgo07.pixnet.net/blog/post/366080983',
        'sort_order' => 60,
    ],
];

// 先刪這 6 個 URL 對應的舊 row（idempotent）
$urls = array_map(fn($t) => $t['source_url'], $testimonials);
$ph = implode(',', array_fill(0, count($urls), '?'));
$delStmt = $db->prepare("DELETE FROM testimonials WHERE client_id={$cid} AND source_url IN ($ph)");
$delStmt->execute($urls);

$ins = $db->prepare("INSERT INTO testimonials
    (client_id, reviewer_name, rating, content, source, source_url, sort_order, is_active)
    VALUES (?,?,5,?,?,?,?,1)");
foreach ($testimonials as $t) {
    $ins->execute([
        $cid, $t['reviewer_name'], $t['content'],
        $t['source'], $t['source_url'], $t['sort_order'],
    ]);
    echo "✅ testimonial: {$t['reviewer_name']}（{$t['source']}）\n";
}

echo "\n=== 完成 ===\n";
$bc = (int)$db->query("SELECT COUNT(*) FROM store_blocks WHERE client_id={$cid} AND is_active=1")->fetchColumn();
$tc = (int)$db->query("SELECT COUNT(*) FROM testimonials WHERE client_id={$cid} AND is_active=1")->fetchColumn();
echo "洪廚 active blocks: {$bc}\n";
echo "洪廚 active testimonials: {$tc}\n";
