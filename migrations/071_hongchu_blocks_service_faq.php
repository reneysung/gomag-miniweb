<?php
// ============================================================
// Migration 071 — 洪廚啟用 store_blocks (service + faq)
// ------------------------------------------------------------
// 目標：切換洪廚 useBlocks=true，行銷頁從 m-* fallback → g-* 新樣式
//
// 加 2 個 block（守「不能無中生有」紀律）：
//   1. service block：4 個服務（婚宴/辦桌/尾牙/年菜）— 都是洪廚明確提供
//   2. faq block：5 個通用 FAQ（流程/服務性，不涉具體價格桌數）
//
// 不加 menu（沒菜色照）/ portfolio（沒案例照版權）/ pricing（行情不無中生有）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/071_hongchu_blocks_service_faq.php
// 冪等：DELETE + INSERT (避免 partial update 問題)，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

// ─── 清掉舊 hongchu blocks（如果有）────────────────────────
$d = $db->exec("DELETE FROM store_blocks WHERE client_id={$cid}");
echo "ℹ️  清舊 blocks: {$d} row\n\n";

// ─── 1. service block ──────────────────────────────────────
$serviceData = [
    'title' => '服務項目',
    'subtitle' => '40 年總鋪師傳承・婚宴喜慶到企業尾牙一條龍承辦',
    'items' => [
        [
            'name' => '婚宴喜宴外燴',
            'desc' => '台南 40 年辦桌經驗，從府城戶外婚禮、莊園草坪婚宴到家鄉庄頭流水席，懷石／法式擺盤等級的精緻台菜，海鮮給得不手軟。跨縣市承辦嘉義、彰化、台中、新竹宴席。',
            'icon' => '💍',
        ],
        [
            'name' => '辦桌外燴',
            'desc' => '在地辦桌的職人團隊，從棚架、桌椅、廚具到上百萬級專業烤箱設備一條龍。家族聚會、滿月酒、入厝、神明慶典、廟會流水席皆能承辦。',
            'icon' => '🍽️',
        ],
        [
            'name' => '企業尾牙春酒',
            'desc' => '450 桌、700 桌大型尾牙的調度經驗，從廠房空地搭棚、大型會展中心包場到飯店宴會廳，台南阿男師領軍的辦桌團隊把傳統台菜結合現代宴席。',
            'icon' => '🎉',
        ],
        [
            'name' => '年菜團購／場地租借',
            'desc' => '農曆春節年菜外帶／宅配、戶外場地租借、家庭聚會用桌等彈性服務，iCHEF 線上訂購方便結帳。',
            'icon' => '🥡',
        ],
    ],
];

$ins = $db->prepare("INSERT INTO store_blocks (client_id, type, data, sort_order, is_active) VALUES (?,?,?,?,1)");
$ins->execute([$cid, 'service', json_encode($serviceData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 10]);
echo "✅ service block 寫入（4 服務）\n";

// ─── 2. faq block ──────────────────────────────────────────
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
            'q' => '報價含哪些？另外要付什麼？',
            'a' => '一般含菜色、桌椅、餐具、現場服務人員、冷藏設備。可能另計：場地租金、棚架／帳篷、電源／冷氣、酒水、菸品、額外加菜。實際請洽 06-6223925 索取詳細報價。',
        ],
        [
            'q' => '戶外婚禮可以承辦嗎？',
            'a' => '可以。從帳篷、桌椅、烹調設備到敬酒動線一條龍。曾承辦戶外莊園、糖廠、海邊、農場、廟埕等多種場合，「變形金剛護嫁」「大黃蜂二次進場」等創意婚禮也有經驗。',
        ],
    ],
];

$ins->execute([$cid, 'faq', json_encode($faqData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 20]);
echo "✅ faq block 寫入（5 FAQ）\n";

echo "\n=== 驗證：useBlocks 切換 ===\n";
$bcount = (int)$db->query("SELECT COUNT(*) FROM store_blocks WHERE client_id={$cid} AND is_active=1")->fetchColumn();
echo "✅ 洪廚 active blocks: {$bcount}（useBlocks 將為 true → 切到 g-* 樣式）\n";

echo "\n完成。\n";
echo "驗證 URL：5 條 /store/hongchu/{city} 應切換到 g-* 樣式 + 顯示 service + faq block\n";
