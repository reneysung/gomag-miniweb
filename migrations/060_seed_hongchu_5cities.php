<?php
// ============================================================
// Migration 060 — 洪廚喜宴 (hongchu) 客戶資料 + 5 城市行銷頁
// ------------------------------------------------------------
// 客戶買 5 縣市方案：台南(總店/柳營)、嘉義、彰化、台中、新竹
// - 只做行銷頁 has_minisite=0（不開小官網子網域）
// - 行銷頁 SEO 每城市差異化角度：辦桌 / 婚宴 / 尾牙春酒 / 年菜 / 外燴
// - 不寫 minisite_* 欄位（NULL），避免後台誤以為要開小官網
//
// 跑法：HTTP_HOST=<host> php migrations/060_seed_hongchu_5cities.php
//      （CLI 一定要前綴 HTTP_HOST，否則 config.php 走 local 連 root/root）
// 冪等：clients 存在則略過 INSERT；client_city_pages 用 ON DUPLICATE UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();
if (!$foodId) { echo "❌ 找不到 food 分類\n"; exit(1); }

// ─── 1. clients row ──────────────────────────────────────────
$existingId = (int)$db->query("SELECT id FROM clients WHERE subdomain='hongchu' OR slug='hongchu' LIMIT 1")->fetchColumn();
if ($existingId) {
    echo "ℹ️  hongchu 已存在 (id={$existingId})，略過 clients INSERT\n";
    $clientId = $existingId;
} else {
    $aboutText = '<p>洪廚喜宴（洪廚囍宴），<strong>台南在地40年</strong>外燴婚宴團隊，'
        . '以總鋪師傳承為底蘊，將傳統台菜辦桌升級為<strong>懷石／法式擺盤等級</strong>的精緻宴席。'
        . '<br>魚翅、龍蝦、鮑魚、海鮮給得不手軟，霸氣大菜搭配創意呈現，<em>讓台菜變得更吸睛、更有氣勢</em>。</p>'
        . '<p>服務範圍：<strong>婚宴喜宴／辦桌外燴／企業尾牙春酒／年菜團購／場地租借</strong>，'
        . '從台南本店為據點，跨縣市承接嘉義、彰化、台中、新竹等地宴席。</p>';
    $ins = $db->prepare("INSERT INTO clients
        (slug, subdomain, brand_name, tagline, industry, category_id,
         address, phone, business_hours, about_text,
         has_minisite, is_active, is_placeholder, is_featured,
         store_meta_title, store_meta_desc, store_keywords,
         created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,0,1,0,0,?,?,?,NOW())");
    $ins->execute([
        'hongchu',                                                              // slug
        'hongchu',                                                              // subdomain（保留，便於日後升級小官網）
        '洪廚喜宴',                                                             // brand_name
        '台南40年浮誇系外燴｜辦桌升級懷石擺盤',                                 // tagline
        '婚宴喜宴／辦桌外燴／年菜團購',                                          // industry
        $foodId,                                                                // category_id
        '台南市柳營區雙和路51號',                                                // address
        '06-6223925',                                                           // phone
        '10:00–21:00',                                                          // business_hours
        $aboutText,                                                             // about_text
        // 主檔 store_* SEO（城市未指定時的預設，走「口碑/評價」角度）
        '洪廚喜宴評價・口碑推薦｜台南40年辦桌外燴｜婚宴／年菜／尾牙團隊',
        '洪廚喜宴（洪廚囍宴）的真實評價與客戶口碑：台南柳營40年外燴婚宴老牌，辦桌升級懷石擺盤、魚翅龍蝦鮑魚澎湃菜色，婚宴喜宴／企業尾牙／年菜團購一次評估。',
        '洪廚喜宴評價,洪廚囍宴推薦,台南辦桌口碑,台南外燴婚宴推薦,台南年菜推薦',
    ]);
    $clientId = (int)$db->lastInsertId();
    echo "✅ clients 已新增 (id={$clientId})\n";
}

// ─── 2. client_social（FB / 線上訂購；LINE 留空待補）─────────
try {
    $sExist = (int)$db->query("SELECT COUNT(*) FROM client_social WHERE client_id={$clientId}")->fetchColumn();
    if (!$sExist) {
        $db->prepare("INSERT INTO client_social (client_id, fb_page_url) VALUES (?,?)")
           ->execute([
               $clientId,
               'https://www.facebook.com/p/%E6%B4%AA%E5%BB%9A%E5%9B%8D%E5%AE%B4-100050966366964/',
           ]);
        echo "✅ client_social 已新增（FB 已填，IG/LINE/YT 留空待後台補）\n";
    } else {
        echo "ℹ️  client_social 已存在，略過\n";
    }
} catch (\Throwable $e) {
    echo "⚠️  client_social 略過: " . $e->getMessage() . "\n";
}

// ─── 3. client_city_pages × 5 ────────────────────────────────
// 每城角度刻意錯開（避 SEO 自相 cannibalize）：
//   台南＝總店本店、辦桌/婚宴/年菜
//   嘉義＝跨縣市外燴、雲嘉南婚宴
//   彰化＝中部辦桌、職人南下北上
//   台中＝中部婚宴外燴、企業尾牙
//   新竹＝竹科尾牙春酒、商務宴會
$cities = [
    [
        'slug' => 'tainan', 'label' => '台南市', 'sort' => 10,
        'store_title' => '台南婚宴外燴推薦｜洪廚喜宴・柳營40年辦桌總鋪師',
        'store_desc'  => '洪廚喜宴台南柳營總店，台南在地40年外燴婚宴老牌。辦桌升級懷石／法式擺盤等級，魚翅龍蝦鮑魚海鮮給得澎湃。婚宴喜宴、企業尾牙、年菜團購首選。訂席電話 06-6223925。',
        'store_kw'    => '台南婚宴,台南辦桌,台南外燴,台南喜宴,柳營辦桌,新營外燴,台南婚宴外燴推薦,洪廚喜宴',
    ],
    [
        'slug' => 'chiayi', 'label' => '嘉義市', 'sort' => 20,
        'store_title' => '嘉義外燴辦桌推薦｜洪廚喜宴・台南40年總鋪師跨縣市承辦',
        'store_desc'  => '洪廚喜宴嘉義外燴服務，台南柳營40年總鋪師北上承辦嘉義市區、太保、朴子婚宴喜宴與企業尾牙。澎湃台菜搭配懷石擺盤，海鮮辦桌規格、價格透明。預約電話 06-6223925。',
        'store_kw'    => '嘉義外燴,嘉義辦桌,嘉義婚宴,嘉義喜宴,嘉義尾牙,嘉義總鋪師,嘉義外燴推薦,洪廚喜宴',
    ],
    [
        'slug' => 'changhua', 'label' => '彰化縣', 'sort' => 30,
        'store_title' => '彰化外燴婚宴推薦｜洪廚喜宴・台南職人北上辦桌',
        'store_desc'  => '洪廚喜宴彰化外燴，台南柳營40年辦桌團隊北上承接彰化市、員林、鹿港、二林婚宴喜宴與廟會流水席。懷石擺盤、海鮮霸氣，南部總鋪師手藝中部直送。預約 06-6223925。',
        'store_kw'    => '彰化外燴,彰化辦桌,彰化婚宴,彰化喜宴,員林辦桌,鹿港婚宴,彰化外燴推薦,洪廚喜宴',
    ],
    [
        'slug' => 'taichung', 'label' => '台中市', 'sort' => 40,
        'store_title' => '台中外燴婚宴推薦｜洪廚喜宴・南部40年總鋪師中部辦桌',
        'store_desc'  => '洪廚喜宴台中外燴，台南40年辦桌團隊北上服務台中市、北屯、南屯、太平、大里。婚宴喜宴、企業尾牙春酒、戶外辦桌一條龍；懷石擺盤、海鮮澎湃。訂席 06-6223925。',
        'store_kw'    => '台中外燴,台中辦桌,台中婚宴,台中喜宴,台中尾牙,台中春酒,台中外燴推薦,洪廚喜宴',
    ],
    [
        'slug' => 'hsinchu', 'label' => '新竹市', 'sort' => 50,
        'store_title' => '新竹尾牙春酒外燴推薦｜洪廚喜宴・竹科商務宴會總鋪師',
        'store_desc'  => '洪廚喜宴新竹外燴，專接竹科尾牙春酒、企業商務宴會、新竹市區婚宴喜宴。台南40年辦桌總鋪師北上承辦，懷石擺盤、海鮮霸氣大方體面。預約洽詢 06-6223925。',
        'store_kw'    => '新竹外燴,竹科尾牙,新竹春酒,新竹婚宴,新竹辦桌,新竹企業宴會,新竹外燴推薦,洪廚喜宴',
    ],
];

// 有 unique key (client_id, city_slug) → 用 ON DUPLICATE KEY UPDATE
$sql = "INSERT INTO client_city_pages
    (client_id, city_slug, city_label, sort_order, is_active, filter_cases_by_region,
     store_meta_title, store_meta_desc, store_keywords,
     created_at)
    VALUES (?,?,?,?,1,0,?,?,?,NOW())
    ON DUPLICATE KEY UPDATE
        city_label=VALUES(city_label),
        sort_order=VALUES(sort_order),
        is_active=1,
        store_meta_title=VALUES(store_meta_title),
        store_meta_desc=VALUES(store_meta_desc),
        store_keywords=VALUES(store_keywords)";
$stmt = $db->prepare($sql);

foreach ($cities as $c) {
    $stmt->execute([
        $clientId, $c['slug'], $c['label'], $c['sort'],
        $c['store_title'], $c['store_desc'], $c['store_kw'],
    ]);
    echo "✅ {$c['label']} upsert 完成\n";
}

// ─── 4. 輸出驗證 URL ────────────────────────────────────────
echo "\n=== 5 條 SEO 入口 URL（驗證用）===\n";
foreach ($cities as $c) {
    echo "  📢 https://www.gomag.com.tw/store/hongchu/{$c['slug']}\n";
}
echo "\n備註：has_minisite=0 → 不開小官網子網域；client_city_pages 的 minisite_* 欄位留 NULL。\n";
echo "若日後升級小官網方案，到後台把 has_minisite=1、補各城 ms_title/ms_desc/ms_intro 即可。\n";
echo "完成。\n";
