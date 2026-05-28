<?php
// ============================================================
// Migration 064 — 加 mobile_phone + external_reviews_json
// ------------------------------------------------------------
// schema 變更（207 客戶通用）：
//   clients.mobile_phone                       VARCHAR(30) — 第二支電話/行動
//   client_city_pages.external_reviews_json    LONGTEXT    — 外部口碑文章
//
// hongchu (id=221) 資料：
//   mobile_phone = 0932715143
//   external_reviews_json：5 城各自分配
//     (洪廚只有 tainan/chiayi/changhua/taichung/hsinchu，
//      橋頭=高雄相關文章歸入 tainan「跨縣市承辦」案例)
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/064_mobile_phone_and_external_reviews.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ─── 1. schema ─────────────────────────────────────────────
$alters = [
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS mobile_phone VARCHAR(30) DEFAULT NULL COMMENT '第二支電話/行動' AFTER phone",
    "ALTER TABLE client_city_pages ADD COLUMN IF NOT EXISTS external_reviews_json LONGTEXT DEFAULT NULL COMMENT '外部口碑/網友分享文章 JSON' AFTER landing_extra_content",
];
foreach ($alters as $a) {
    try { $db->exec($a); echo "✅ " . substr($a, 0, 90) . "...\n"; }
    catch (\Throwable $e) { echo "ℹ️  skip: " . $e->getMessage() . "\n"; }
}

// ─── 2. hongchu id ─────────────────────────────────────────
$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "\nℹ️  hongchu id={$cid}\n\n";

// ─── 3. mobile_phone ────────────────────────────────────────
$db->prepare("UPDATE clients SET mobile_phone=? WHERE id=?")
   ->execute(['0932715143', $cid]);
echo "✅ mobile_phone = 0932715143\n";

// ─── 4. 5 城 external_reviews_json ──────────────────────────
// 文章對應規則：
//   ・標題或內文有指明城市 → 歸該城
//   ・橋頭婚禮（高雄）→ 歸 tainan「跨縣市出場」例證（洪廚 5 城沒高雄）
//   ・通用介紹文（40 年介紹、浮誇系外燴）→ 歸 tainan 本店
$reviewsByCity = [
    'tainan' => [
        [
            'title'   => '台南辦桌【洪廚囍宴】還以為自己跑錯場！辦桌菜擺盤好美，根本是懷石料理法式料理等級',
            'url'     => 'https://aronrandom.pixnet.net/blog/post/41206340',
            'source'  => '史丹利樂福',
            'excerpt' => '魚翅龍蝦鮑魚海鮮給得不手軟，台南辦桌也能吃出懷石料理的儀式感。',
        ],
        [
            'title'   => '澎湃海鮮盛宴，魚翅龍蝦鮑魚全都有！質感升級新人婚宴',
            'url'     => 'https://www.mobile01.com/topicdetail.php?f=202&t=6938173',
            'source'  => 'Mobile01',
            'excerpt' => '台南本店分享，浮誇海鮮辦桌、新人婚宴大方體面。',
        ],
        [
            'title'   => '台南尾牙辦桌｜洪廚喜宴：顛覆傳統的法式尾牙盛宴！',
            'url'     => 'https://ocgo10.pixnet.net/blog/post/366815752',
            'source'  => '享受生活不設限',
            'excerpt' => '台南尾牙改走法式擺盤，員工拍照拍到忘記吃飯。',
        ],
        [
            'title'   => '台南辦桌廠商推薦【洪廚囍宴】婚宴辦桌菜色豐盛，滿足口腹之慾',
            'url'     => 'https://jlgo07.pixnet.net/blog/post/366080983',
            'source'  => '鮪魚肚的秘笈',
            'excerpt' => '婚宴菜色豐盛、留下滿滿歡樂，準新人考慮台南辦桌可參考。',
        ],
        [
            'title'   => '洪廚囍宴｜浮誇系台南外燴！創意擺盤好吃好拍，人氣總鋪師辦桌啦！',
            'url'     => 'https://94sis.com/wedding-banquet/',
            'source'  => '94sis 婚禮好姊妹',
            'excerpt' => '浮誇系外燴介紹，從擺盤到菜色完整紀錄。',
        ],
        [
            'title'   => '【台南美食餐廳推薦】洪廚囍宴',
            'url'     => 'https://www.ivylife.com.tw/chowder2.html',
            'source'  => 'IvyLife 旅遊誌',
            'excerpt' => '台南外燴辦桌完整介紹，含菜色清單、價格、訂席方式。',
        ],
        [
            'title'   => '洪廚囍宴 — 顛覆傳統的浮誇系外燴',
            'url'     => 'https://etaiwan.blog/chef-hong-catering/',
            'source'  => '口袋吃吃五十咩',
            'excerpt' => '台南柳營 40 年外燴老牌的職人故事與菜色亮點。',
        ],
    ],
    'chiayi' => [], // 暫無對應文章
    'changhua' => [], // 暫無對應文章
    'taichung' => [
        // 暫無城市專屬文章，但「請世界吃桌」主題可放這
        [
            'title'   => '紐約時報廣場炸台味！洪俊男（阿男師）帶台灣辦桌團登世界舞台',
            'url'     => 'https://stars.udn.com/star/story/10091/9450929',
            'source'  => '噓星聞・聯合新聞網',
            'excerpt' => '三立《請世界吃桌》6/6 首播，阿男師領台灣辦桌團出國辦桌。',
        ],
    ],
    'hsinchu' => [], // 暫無對應文章
];
// 註：第三方文章不一定有城市 — 洪廚台南本店為主，
//     橋頭婚禮（高雄）文章其實也是「台南本店跨縣市出場」案例
//     因此放在 tainan 較合理（高雄不在 5 城方案內）

$upd = $db->prepare("UPDATE client_city_pages
    SET external_reviews_json = ?
    WHERE client_id = ? AND city_slug = ?");

foreach ($reviewsByCity as $citySlug => $rows) {
    $json = !empty($rows) ? json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $upd->execute([$json, $cid, $citySlug]);
    $n = count($rows);
    echo "✅ {$citySlug}: " . ($n ? "{$n} 篇外部分享" : "(無，留 NULL)") . "\n";
}

echo "\n完成。\n";
