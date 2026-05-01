<?php
/**
 * migrations/005_seed_demo_blocks.php
 *
 * 為 3 個示範客戶建立完整 store_blocks 資料：
 * - id=10  歡樂牛排嘉義店（餐飲）→ menu + faq
 * - id=18  濱緻車體美學（汽車）→ service + portfolio + pricing + faq
 * - id=3   綝綝美甲美睫紋繡學院（美容）→ service + pricing + faq
 *
 * 冪等：重跑會 DELETE 該 client 已有的 blocks 再重建
 *
 * 用法：
 *   php migrations/005_seed_demo_blocks.php
 *   或 web: BASE_URL/migrations/005_seed_demo_blocks.php?key=seed-demo-2026
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/block_helpers.php';

if (PHP_SAPI !== 'cli') {
    if (($_GET['key'] ?? '') !== 'seed-demo-2026') { http_response_code(403); die('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

$db = getDB();

function out(string $msg): void { echo "$msg\n"; if (PHP_SAPI !== 'cli') flush(); }

// 清空指定 client 的 blocks
function resetClientBlocks(int $cid): void {
    global $db;
    $db->prepare("DELETE FROM store_blocks WHERE client_id=?")->execute([$cid]);
}

out("═══ Migration 005: 建立示範客戶 blocks ═══\n");

// ════════════════════════════════════════════════════════════
//   1. 歡樂牛排嘉義店 (id=10) — 餐飲
// ════════════════════════════════════════════════════════════
out("─ #10 歡樂牛排嘉義店（餐飲）─");
resetClientBlocks(10);

saveStoreBlock(10, 'menu', [
    'title' => '招牌菜單',
    'groups' => [
        ['name' => '經典套餐', 'items' => [
            ['name' => '美式安格斯沙朗牛排', 'price' => 380, 'desc' => '20oz 美國進口安格斯沙朗，自選熟度', 'tag' => '招牌'],
            ['name' => '丁骨大牛排',         'price' => 480, 'desc' => '32oz 雙重口感、骨邊肉鮮甜'],
            ['name' => '法式紅酒燉牛肉',     'price' => 320, 'desc' => '慢燉 6 小時，肉質酥嫩'],
            ['name' => '香煎雞腿排',         'price' => 280, 'desc' => '酥脆外皮、多汁雞肉'],
        ]],
        ['name' => '人氣副食', 'items' => [
            ['name' => '焗烤海鮮義大利麵', 'price' => 280, 'desc' => '滿滿蛤蜊、鮮蝦、起司'],
            ['name' => '薯條起司洋蔥圈拼盤', 'price' => 160, 'desc' => '酒酣耳熱必點'],
            ['name' => '烤雞翅',           'price' => 180, 'desc' => '蜜糖醬汁特調'],
        ]],
        ['name' => '飲品', 'items' => [
            ['name' => '無限續杯飲料吧', 'price' => 79,  'desc' => '熱湯、果汁、汽水自取', 'tag' => '吃到飽'],
            ['name' => '生啤酒（500ml）', 'price' => 120, 'desc' => '台啤金牌 / 海尼根'],
        ]],
    ],
], 20);

saveStoreBlock(10, 'faq', [
    'title' => '常見問題',
    'items' => [
        ['q' => '需要訂位嗎？', 'a' => '建議週末提早 2-3 天訂位，平日中午通常有位置。可電話 05-2723973 預約。'],
        ['q' => '有兒童餐嗎？', 'a' => '有，提供義大利麵 + 雞塊 + 飲料的兒童餐 NT$220，附小玩具。'],
        ['q' => '平均消費多少？', 'a' => '一份套餐約 NT$280-480 含飲料吧，兩人用餐約 NT$700 起。'],
        ['q' => '可以聚餐嗎？', 'a' => '6-12 人的中型聚餐歡迎電話預約包廂；20 人以上請洽客服安排辦桌。'],
    ],
], 50);
out("  ✅ 建立 menu (3 群組 9 品項) + faq (4 題)\n");

// ════════════════════════════════════════════════════════════
//   2. 濱緻車體美學 (id=18) — 汽車
// ════════════════════════════════════════════════════════════
out("─ #18 濱緻車體美學（汽車）─");
resetClientBlocks(18);

saveStoreBlock(18, 'service', [
    'title' => '核心服務',
    'items' => [
        ['icon' => '🛡', 'name' => 'XPEL 頂級漆面保護膜', 'short_desc' => '全車犀牛皮包覆，10 年保固，抗刮防跳石', 'price_text' => 'NT$38,000 起'],
        ['icon' => '✨', 'name' => '陶瓷鍍膜 (5 年型)',     'short_desc' => '德國 Soft99 鍍膜，深度防護 + 鏡面光澤',         'price_text' => 'NT$15,000 起'],
        ['icon' => '🚿', 'name' => '深度漆面美容',          'short_desc' => '太陽紋拋光、引擎室細清、室內全套',                 'price_text' => 'NT$6,800'],
        ['icon' => '🪟', 'name' => '隔熱紙施工',            'short_desc' => '美國 3M / Llumar 進口隔熱紙',                       'price_text' => 'NT$8,000 起'],
    ],
], 10);

saveStoreBlock(18, 'pricing', [
    'title' => '車身大小報價',
    'currency' => 'TWD',
    'items' => [
        ['name' => '小型車（K5/Yaris/Fit）', 'price' => 38000, 'unit' => '/ 全車包膜', 'features' => [
            'XPEL Ultimate Plus 漆面保護膜', '10 年原廠保固', '無塵施工環境', '免費年度檢視',
        ]],
        ['name' => '中型房車 / Tesla', 'price' => 48000, 'unit' => '/ 全車包膜', 'features' => [
            'XPEL Ultimate Plus 漆面保護膜', '10 年原廠保固', '無塵施工環境', '免費年度檢視', '送德國 Soft99 鍍膜',
        ], 'highlight' => true],
        ['name' => '休旅 / 大型車', 'price' => 58000, 'unit' => '/ 全車包膜', 'features' => [
            'XPEL Ultimate Plus 漆面保護膜', '10 年原廠保固', '無塵施工環境', '免費年度檢視',
        ]],
    ],
], 30);

saveStoreBlock(18, 'faq', [
    'title' => '常見問題',
    'items' => [
        ['q' => '包膜會傷原廠漆嗎？', 'a' => '不會。XPEL 採用低黏性背膠，貼膜過程不傷漆，後續想要拆除也不會殘膠。包膜反而能保護原廠漆免受日曬、刮傷、跳石損傷。'],
        ['q' => '保固範圍？', 'a' => 'XPEL Ultimate Plus 提供 10 年原廠保固，涵蓋黃化、龜裂、起泡、剝離。施工瑕疵則由本店終身負責。'],
        ['q' => '施工要多久？', 'a' => '小型車 2 天、中型 3 天、大型 4 天。期間可代訂送修代步車。'],
        ['q' => '可以分期付款嗎？', 'a' => '可。本店配合台新、玉山銀行 0 利率分期 6/12/24 期，當日刷卡即可享優惠。'],
        ['q' => '有提供到府服務嗎？', 'a' => '台南市 + 嘉義市區免費到府牽車。其他縣市請洽 0905-xxx-xxx 詢問。'],
    ],
], 50);
out("  ✅ 建立 service (4 服務) + pricing (3 方案，中型推薦) + faq (5 題)\n");

// ════════════════════════════════════════════════════════════
//   3. 綝綝美甲美睫紋繡學院 (id=3) — 美容
// ════════════════════════════════════════════════════════════
out("─ #3 綝綝美甲美睫紋繡學院（美容）─");
resetClientBlocks(3);

saveStoreBlock(3, 'service', [
    'title' => '核心服務',
    'items' => [
        ['icon' => '💅', 'name' => '日式手部光療', 'short_desc' => '基礎光療 + 簡約款式，新手友善'],
        ['icon' => '✨', 'name' => '韓式美睫嫁接', 'short_desc' => '專業 6D 立體睫，自然 / 濃密 / 風扇捲款式可選'],
        ['icon' => '🎨', 'name' => '韓式半永久紋繡', 'short_desc' => '飄霧眉 / 美瞳線 / 漂唇，1-2 年自然褪色'],
        ['icon' => '🎓', 'name' => '專業師資證照課程', 'short_desc' => '美甲 / 美睫 / 紋繡證照班，小班授課'],
    ],
], 10);

saveStoreBlock(3, 'pricing', [
    'title' => '價目表',
    'currency' => 'TWD',
    'items' => [
        ['name' => '基礎光療', 'price' => 800,  'unit' => '/ 次', 'features' => [
            '單色光療美甲', '基礎修型', '附贈卸甲一次',
        ]],
        ['name' => '進階款式（最受歡迎）', 'price' => 1500, 'unit' => '/ 次', 'features' => [
            '雙色 / 法式 / 璀璨彩繪', '專業師傅施作', '附贈卸甲一次', '可選 1 種貼片造型',
        ], 'highlight' => true],
        ['name' => 'VIP 客製', 'price' => 2800, 'unit' => '/ 次', 'features' => [
            '全客製設計', '特殊材料（貓眼 / 鏡面 / 鑄模）', '雙人共享休息空間', '附贈手部 SPA',
        ]],
    ],
], 30);

saveStoreBlock(3, 'faq', [
    'title' => '常見問題',
    'items' => [
        ['q' => '需要預約嗎？', 'a' => '需要。請至少提早 2 天電話或 LINE 預約。基礎款 1.5 小時、進階 2-3 小時。'],
        ['q' => '光療會傷指甲嗎？', 'a' => '使用日本進口無酸基底，遵循正確卸甲流程不會傷甲。建議 3-4 週做一次保養。'],
        ['q' => '證照課程要學多久？', 'a' => '美甲基礎班 80 小時、進階班 120 小時。考取乙級證照另含 40 小時實作。'],
        ['q' => '可以體驗一次嗎？', 'a' => '新客首次體驗價 NT$590（基礎款），LINE 加入好友再享 9 折優惠。'],
    ],
], 50);
out("  ✅ 建立 service (4 服務) + pricing (3 方案，進階推薦) + faq (4 題)\n");

// Summary
$total = $db->query("SELECT COUNT(*) FROM store_blocks")->fetchColumn();
out("═══════════════════════════════════════");
out("Migration 005 complete. store_blocks 總筆數: $total");
out("─ 各 client：");
foreach ([1, 3, 10, 18] as $cid) {
    $cnt = $db->prepare("SELECT COUNT(*) FROM store_blocks WHERE client_id=?");
    $cnt->execute([$cid]);
    $name = $db->prepare("SELECT brand_name FROM clients WHERE id=?");
    $name->execute([$cid]);
    out("    #$cid {$name->fetchColumn()}: " . $cnt->fetchColumn() . " blocks");
}
