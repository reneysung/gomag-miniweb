<?php
// ============================================================
// Migration 073 — 洪廚內容稽核：弱證據改保守版
// ------------------------------------------------------------
// 守「不能無中生有」紀律，改 menu / faq 中 4 個推測式描述：
//   menu:
//     - 梅花豚「低溫慢煮...肉質軟嫩入口即化」 → 「搭配時令蔬菜與醬汁」
//     - 廣島生蠔「現場開殼，鎖住海洋鮮甜」 → 「鮮甜直送」
//     - 真鯛魚首「入鍋熬煮，膠質豐厚」 → 「真鯛魚首鍋物，湯頭濃郁」
//     - 龍蝦壽司「現場捏製職人壽司，呈現懷石儀式感」 → 「職人壽司」
//   faq:
//     - 「報價含哪些」原本列了 5 項通用辦桌規格 → 改成「請洽電話索取報價」
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/073_hongchu_content_audit_conservative.php
// 冪等：直接 UPDATE，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

// ─── 1. menu block 保守版 ───────────────────────────────────
$menuData = [
    'title' => '精選辦桌菜色',
    'subtitle' => '懷石擺盤 × 法式手法 × 台菜底蘊・以下為實場辦桌照片，菜色依季節食材與場合彈性調整',
    'groups' => [
        [
            'name' => '海鮮主打',
            'items' => [
                [
                    'name' => '真鯛鮪魚刺身盛合',
                    'desc' => '夢幻造型擺盤，當日新鮮真鯛與鮪魚，是婚宴開場的視覺亮點。',
                    'image' => 'uploads/menu/hongchu/febgo-01-sashimi.jpg',
                ],
                [
                    'name' => '龍蝦澎湖中卷職人壽司',
                    'desc' => '澎湖中卷搭龍蝦的職人壽司，呈現懷石料理的儀式感。',
                    'image' => 'uploads/menu/hongchu/febgo-02-lobster-sushi.jpg',
                ],
                [
                    'name' => '義式奶油松露煎海大蝦',
                    'desc' => '海大蝦以義式手法呈現，松露奶油提香，融合台菜與西餐風味。',
                    'image' => 'uploads/menu/hongchu/febgo-03-truffle-shrimp.jpg',
                ],
                [
                    'name' => '廣島生蠔',
                    'desc' => '進口廣島生蠔，鮮甜直送。',
                    'image' => 'uploads/menu/hongchu/ocgo10-01-oyster.jpg',
                ],
                [
                    'name' => '脆鱗馬頭魚',
                    'desc' => '法式手法呈現馬頭魚，脆鱗與細膩肉質。',
                    'image' => 'uploads/menu/hongchu/ocgo10-02-fish.jpg',
                ],
                [
                    'name' => '真鯛魚首寄世鍋',
                    'desc' => '真鯛魚首鍋物，湯頭濃郁。',
                    'image' => 'uploads/menu/hongchu/febgo-04-tai-pot.jpg',
                ],
            ],
        ],
        [
            'name' => '主餐／燉飯',
            'items' => [
                [
                    'name' => '梅花豚主餐',
                    'desc' => '梅花豚主餐，搭配時令蔬菜與醬汁。',
                    'image' => 'uploads/menu/hongchu/ocgo10-04-pork.jpg',
                ],
                [
                    'name' => '黑松露麻油薏仁燉飯',
                    'desc' => '台式麻油配義式燉飯，黑松露點綴，是混搭創意的代表菜。',
                    'image' => 'uploads/menu/hongchu/ocgo10-03-risotto.jpg',
                ],
            ],
        ],
    ],
];

$db->prepare("UPDATE store_blocks SET data=? WHERE client_id=? AND type='menu'")
   ->execute([json_encode($menuData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cid]);
echo "✅ menu block 改保守版（4 個菜色描述去除推測元素）\n";

// ─── 2. faq block 改 faq #4「報價含哪些」───────────────────
$faqData = [
    'title' => '常見問題',
    'subtitle' => '預約／流程／菜色相關說明',
    'items' => [
        [
            'q' => '洪廚喜宴可以承辦哪些縣市？',
            'a' => '從台南柳營本店出發，跨縣市承辦嘉義、彰化、台中、新竹等地宴席。婚宴、辦桌、尾牙、外燴皆可調度；行動電話 0932715143、店面電話 06-6223925。',
        ],
        [
            'q' => '主廚阿男師是誰？',
            'a' => '阿男師人稱「辦桌南霸天」，憑著 25 年總鋪師經驗，2026 年隨「台灣辦桌團」登上三立《請世界吃桌》實境節目，把台南辦桌文化帶上雪梨歌劇院、紐約時報廣場、熊本城與台北 101。',
        ],
        [
            'q' => '辦桌／婚宴需要提前多久預約？',
            'a' => '婚宴喜宴建議 3-6 個月前預約；尾牙、農曆春節旺季要更早。冷門檔期一個月前也可能訂到，但好日子（農曆好日、寒暑假）週末通常半年到一年前就被預訂滿。',
        ],
        [
            'q' => '報價含哪些？',
            'a' => '實際報價依菜色、桌數、場地需求不同，請洽 06-6223925 索取詳細報價單。',
        ],
        [
            'q' => '戶外婚禮可以承辦嗎？',
            'a' => '可以。曾承辦戶外莊園、糖廠、海邊、農場、廟埕等多種場合，「變形金剛護嫁」「大黃蜂二次進場」等創意婚禮也有經驗。',
        ],
    ],
];

$db->prepare("UPDATE store_blocks SET data=? WHERE client_id=? AND type='faq'")
   ->execute([json_encode($faqData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cid]);
echo "✅ faq block 改保守版（report 含哪些 → 改洽電話）\n";

echo "\n完成。\n";
