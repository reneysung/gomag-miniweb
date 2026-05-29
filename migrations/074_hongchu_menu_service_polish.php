<?php
// ============================================================
// Migration 074 — 洪廚 menu 合 1 group + service 加圖
// ------------------------------------------------------------
// Reney feedback:
//   - 精選辦桌菜色只要一種、不要再分類顯示 → menu 8 道菜合 1 group
//   - 服務項目太大、沒有圖片很怪 → service 4 服務各加 image
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/074_hongchu_menu_service_polish.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

// ─── 1. menu block 合 1 group ─────────────────────────────
$menuData = [
    'title' => '精選辦桌菜色',
    'subtitle' => '懷石擺盤 × 法式手法 × 台菜底蘊・以下為實場辦桌照片，菜色依季節食材與場合彈性調整',
    'groups' => [
        [
            'name' => '',  // 不分類
            'items' => [
                ['name' => '真鯛鮪魚刺身盛合',     'desc' => '夢幻造型擺盤，當日新鮮真鯛與鮪魚，是婚宴開場的視覺亮點。', 'image' => 'uploads/menu/hongchu/febgo-01-sashimi.jpg'],
                ['name' => '龍蝦澎湖中卷職人壽司', 'desc' => '澎湖中卷搭龍蝦的職人壽司，呈現懷石料理的儀式感。',       'image' => 'uploads/menu/hongchu/febgo-02-lobster-sushi.jpg'],
                ['name' => '義式奶油松露煎海大蝦', 'desc' => '海大蝦以義式手法呈現，松露奶油提香，融合台菜與西餐風味。', 'image' => 'uploads/menu/hongchu/febgo-03-truffle-shrimp.jpg'],
                ['name' => '廣島生蠔',             'desc' => '進口廣島生蠔，鮮甜直送。',                                   'image' => 'uploads/menu/hongchu/ocgo10-01-oyster.jpg'],
                ['name' => '脆鱗馬頭魚',           'desc' => '法式手法呈現馬頭魚，脆鱗與細膩肉質。',                       'image' => 'uploads/menu/hongchu/ocgo10-02-fish.jpg'],
                ['name' => '真鯛魚首寄世鍋',       'desc' => '真鯛魚首鍋物，湯頭濃郁。',                                   'image' => 'uploads/menu/hongchu/febgo-04-tai-pot.jpg'],
                ['name' => '梅花豚主餐',           'desc' => '梅花豚主餐，搭配時令蔬菜與醬汁。',                           'image' => 'uploads/menu/hongchu/ocgo10-04-pork.jpg'],
                ['name' => '黑松露麻油薏仁燉飯',   'desc' => '台式麻油配義式燉飯，黑松露點綴，是混搭創意的代表菜。',       'image' => 'uploads/menu/hongchu/ocgo10-03-risotto.jpg'],
            ],
        ],
    ],
];

$db->prepare("UPDATE store_blocks SET data=? WHERE client_id=? AND type='menu'")
   ->execute([json_encode($menuData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cid]);
echo "✅ menu block 合成 1 group（8 道菜不再分類）\n";

// ─── 2. service block 加圖 ────────────────────────────────
$serviceData = [
    'title' => '服務項目',
    'subtitle' => '40 年總鋪師傳承・婚宴喜慶到企業尾牙一條龍承辦',
    'items' => [
        [
            'name' => '婚宴喜宴外燴',
            'desc' => '台南 40 年辦桌經驗，懷石／法式擺盤等級的精緻台菜。跨縣市承辦戶外婚禮、會館婚宴、流水席。',
            'image' => 'uploads/menu/hongchu/febgo-01-sashimi.jpg',
        ],
        [
            'name' => '辦桌外燴',
            'desc' => '在地辦桌的職人團隊，棚架、桌椅、廚具到上百萬級專業烤箱設備一條龍。家族聚會、滿月酒、入厝、廟會流水席皆可承辦。',
            'image' => 'uploads/menu/hongchu/ocgo10-03-risotto.jpg',
        ],
        [
            'name' => '企業尾牙春酒',
            'desc' => '450 桌、700 桌大型尾牙的調度經驗，從廠房空地搭棚、大型會展中心包場到飯店宴會廳。',
            'image' => 'uploads/menu/hongchu/febgo-04-tai-pot.jpg',
        ],
        [
            'name' => '年菜團購／場地租借',
            'desc' => '農曆春節年菜外帶／宅配、戶外場地租借、家庭聚會用桌等彈性服務，iCHEF 線上訂購方便結帳。',
            'image' => 'uploads/menu/hongchu/ocgo10-01-oyster.jpg',
        ],
    ],
];

// service block 用 short_desc / image（依 blocks/service.php 規格）
foreach ($serviceData['items'] as &$it) {
    $it['short_desc'] = $it['desc'];
    unset($it['desc']);
}
unset($it);

$db->prepare("UPDATE store_blocks SET data=? WHERE client_id=? AND type='service'")
   ->execute([json_encode($serviceData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cid]);
echo "✅ service block 4 服務各加 image（從 4 張菜色照中挑代表性）\n";

echo "\n完成。\n";
