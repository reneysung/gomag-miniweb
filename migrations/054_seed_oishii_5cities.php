<?php
// ============================================================
// Migration 054 — 奧喜長崎蛋糕 (oishii) 客戶資料 + 5 城市行銷頁/小官網
// ------------------------------------------------------------
// 客戶買 5 縣市方案：台中(總店)、台北、新北、桃園、新竹
// - 行銷頁 SEO 走「在地關鍵字 + 推薦/宅配」角度
// - 小官網 SEO 走「品牌 + 城市服務」角度
// 兩邊角度刻意錯開避免互相 cannibalize
//
// 跑法：HTTP_HOST=<host> php migrations/054_seed_oishii_5cities.php
// 冪等：INSERT IGNORE for clients；client_city_pages 用 UPDATE 不存在則 INSERT
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();
if (!$foodId) { echo "❌ 找不到 food 分類\n"; exit(1); }

// ─── 1. clients row（INSERT 若 subdomain 已存在則略過 + 取既有 id）───
$existingId = (int)$db->query("SELECT id FROM clients WHERE subdomain='oishii' OR slug='oishii' LIMIT 1")->fetchColumn();
if ($existingId) {
    echo "ℹ️  oishii 已存在 (id={$existingId})，略過 clients INSERT\n";
    $clientId = $existingId;
} else {
    // 註：clients 表沒有 minisite_keywords 欄（那是 client_city_pages 才有），
    //     keywords 只在城市 row 設，主檔不設預設 keywords。
    $ins = $db->prepare("INSERT INTO clients
        (slug, subdomain, brand_name, tagline, industry, category_id,
         address, phone, about_text, has_minisite, is_active, is_placeholder, is_featured,
         minisite_meta_title, minisite_meta_desc,
         store_meta_title, store_meta_desc, store_keywords,
         created_at)
        VALUES (?,?,?,?,?,?,?,?,?,1,1,0,0,?,?,?,?,?,NOW())");
    $aboutText = '<p>奧喜長崎蛋糕，秉持「<strong>誠信為本・踏實為重</strong>」的職人精神，'
        . '以最單純的<strong>雞蛋、糖、麵粉、麥芽</strong>手工現烤日式長崎蛋糕。'
        . '<br>濕潤蛋香、柔軟細緻、甜度恰到好處——'
        . '<em>簡單卻不凡的滋味</em>，是台中北屯松竹路在地的伴手禮名店。</p>';
    $ins->execute([
        'oishii',                                                          // slug
        'oishii',                                                          // subdomain
        '奧喜長崎蛋糕',                                                    // brand_name
        '日式手工現烤長崎蛋糕｜簡單卻不凡',                                // tagline
        '長崎蛋糕／日式糕點／伴手禮',                                       // industry
        $foodId,                                                            // category_id
        '台中市北屯區松竹路三段22號',                                       // address
        '04-22416560',                                                      // phone
        $aboutText,                                                         // about_text
        // 主檔 minisite_* SEO（城市未指定時的預設）
        '奧喜長崎蛋糕｜日式手工現烤・台中北屯松竹路',
        '奧喜長崎蛋糕，台中北屯松竹路三段日式糕點名店。最單純雞蛋糖麵粉麥芽，手工現烤日式長崎蛋糕，濕潤蛋香、簡單卻不凡。台中伴手禮、彌月禮盒、宅配自取。',
        // 主檔 store_* SEO（城市未指定時的預設,走口碑/評價角度）
        '奧喜長崎蛋糕評價・口碑推薦｜真實客戶評論｜長崎蛋糕／日式糕點',
        '奧喜長崎蛋糕的真實評價與客戶口碑整理：日式手工現烤、最單純食材、濕潤蛋香的好評心得，看實際口碑再決定。',
        '奧喜長崎蛋糕評價,奧喜長崎蛋糕推薦,長崎蛋糕口碑,台中蛋糕推薦',
    ]);
    $clientId = (int)$db->lastInsertId();
    echo "✅ clients 已新增 (id={$clientId})\n";
}

// ─── 2. client_social（IG/FB,LINE 留空待後台補）───
try {
    $sExist = (int)$db->query("SELECT COUNT(*) FROM client_social WHERE client_id={$clientId}")->fetchColumn();
    if (!$sExist) {
        $db->prepare("INSERT INTO client_social (client_id, ig_url, fb_page_url, google_review_url) VALUES (?,?,?,?)")
           ->execute([
               $clientId,
               'https://www.instagram.com/oishii_castella/',
               '',
               '',
           ]);
        echo "✅ client_social 已新增（IG 已填，FB/LINE 留空待後台補）\n";
    } else {
        echo "ℹ️  client_social 已存在，略過\n";
    }
} catch (\Throwable $e) {
    echo "⚠️  client_social 略過: " . $e->getMessage() . "\n";
}

// ─── 3. client_city_pages × 5（台中/台北/新北/桃園/新竹）───
$cities = [
    [
        'slug' => 'taichung', 'label' => '台中市', 'sort' => 10,
        'store_title' => '台中長崎蛋糕推薦｜奧喜古早味手工現烤・北屯松竹路名店',
        'store_desc'  => '奧喜長崎蛋糕台中北屯松竹路三段名店，日式手工現烤、濕潤蛋香、最單純雞蛋糖麵粉麥芽。台中伴手禮、彌月禮盒首選。可宅配自取，訂購電話 04-22416560。',
        'store_kw'    => '台中長崎蛋糕,台中伴手禮,北屯伴手禮,松竹路蛋糕,台中彌月禮盒,台中日式糕點',
        'ms_title'    => '奧喜長崎蛋糕 台中總店｜北屯松竹路・手工現烤長崎蛋糕',
        'ms_desc'     => '奧喜長崎蛋糕台中總店，北屯松竹路三段 22 號。秉持誠信為本、踏實為重，以最單純的雞蛋、糖、麵粉、麥芽手工現烤日式長崎蛋糕。簡單卻不凡的滋味，台中在地名店。',
        'ms_kw'       => '奧喜長崎蛋糕台中,台中總店,北屯松竹路,台中蛋糕名店,台中日式糕點',
        'ms_intro'    => '<p>奧喜長崎蛋糕<strong>台中總店</strong>位於北屯區松竹路三段 22 號，是品牌的本店所在。'
            . '每日上午 9 點至晚上 7 點，師傅在店內以最單純的<strong>雞蛋、糖、麵粉、麥芽</strong>手工現烤長崎蛋糕。'
            . '台中在地客人可<em>現場購買</em>，也可<em>電話訂購自取</em>，是北屯居民與台中伴手禮的常駐選擇。</p>',
    ],
    [
        'slug' => 'taipei', 'label' => '台北市', 'sort' => 20,
        'store_title' => '台北長崎蛋糕宅配推薦｜奧喜手工現烤・台中名店冷藏直送',
        'store_desc'  => '奧喜長崎蛋糕台北宅配，台中老字號職人手工現烤、冷藏直送台北。日式長崎蛋糕、最單純食材，適合送禮／彌月／伴手禮。訂購電話 04-22416560。',
        'store_kw'    => '台北長崎蛋糕,台北宅配蛋糕,台北伴手禮,日式長崎蛋糕宅配,台北彌月禮盒',
        'ms_title'    => '奧喜長崎蛋糕 台北宅配｜手工現烤・台中職人直送',
        'ms_desc'     => '奧喜長崎蛋糕台北宅配服務，台中職人手工現烤、冷藏直送台北。秉持簡單食材的純粹，以濕潤蛋香的日式長崎蛋糕，讓台北客戶在家享受台中職人傳承的好味道。',
        'ms_kw'       => '奧喜長崎蛋糕台北,台北蛋糕宅配,日式長崎蛋糕台北,台北送禮蛋糕',
        'ms_intro'    => '<p>奧喜長崎蛋糕提供<strong>台北全區宅配</strong>服務，由台中總店師傅<em>每日手工現烤</em>後冷藏直送。'
            . '從<strong>大安、信義、中山、松山、士林</strong>等全台北市區皆可送達，'
            . '是台北客戶不必跑台中就能享受日式長崎蛋糕職人滋味的最佳選擇。</p>',
    ],
    [
        'slug' => 'newtaipei', 'label' => '新北市', 'sort' => 30,
        'store_title' => '新北長崎蛋糕宅配｜奧喜古早味現烤・中和板橋伴手禮推薦',
        'store_desc'  => '奧喜長崎蛋糕新北宅配，中和、板橋、新莊、林口宅配到府。台中職人手工現烤、最單純食材，日式長崎蛋糕送禮首選。訂購 04-22416560，可加 LINE 預訂。',
        'store_kw'    => '新北長崎蛋糕,新北宅配蛋糕,板橋蛋糕,中和蛋糕,新莊蛋糕,林口蛋糕,新北伴手禮',
        'ms_title'    => '奧喜長崎蛋糕 新北宅配｜手工現烤・板橋中和林口都送',
        'ms_desc'     => '奧喜長崎蛋糕新北宅配，從台中總店現烤直送中和、板橋、新莊、林口、三重、新店全區。秉持簡單食材製作的初心，以日式長崎蛋糕傳遞「簡單卻不凡」的品牌精神。',
        'ms_kw'       => '奧喜長崎蛋糕新北,板橋宅配,中和宅配,林口宅配,新北蛋糕',
        'ms_intro'    => '<p>奧喜長崎蛋糕<strong>新北全區宅配</strong>，從<strong>板橋、中和、永和、新莊、三重、林口、新店、土城</strong>都送達。'
            . '台中總店<em>每日手工現烤</em>後冷藏直送，新北客戶可享受跟台中本店同樣的職人滋味。'
            . '彌月、商務、年節送禮體面大方。</p>',
    ],
    [
        'slug' => 'taoyuan', 'label' => '桃園市', 'sort' => 40,
        'store_title' => '桃園長崎蛋糕宅配推薦｜奧喜手工現烤・中壢龜山伴手禮',
        'store_desc'  => '奧喜長崎蛋糕桃園宅配，中壢、龜山、桃園市區到府。台中職人手工現烤、最單純食材，日式長崎蛋糕。彌月、伴手禮、送禮體面。訂購 04-22416560。',
        'store_kw'    => '桃園長崎蛋糕,桃園宅配蛋糕,中壢蛋糕,龜山蛋糕,桃園伴手禮,桃園彌月禮盒',
        'ms_title'    => '奧喜長崎蛋糕 桃園宅配｜手工現烤・中壢龜山桃園送達',
        'ms_desc'     => '奧喜長崎蛋糕桃園宅配，台中職人手工現烤直送中壢、龜山、桃園市區、八德、平鎮。秉持誠信為本，以最單純的雞蛋糖麵粉麥芽，讓桃園客戶感受奧喜的職人初心。',
        'ms_kw'       => '奧喜長崎蛋糕桃園,中壢宅配,龜山宅配,桃園蛋糕,桃園日式糕點',
        'ms_intro'    => '<p>奧喜長崎蛋糕<strong>桃園市全區宅配</strong>，覆蓋<strong>桃園市區、中壢、龜山、八德、平鎮、楊梅</strong>。'
            . '由台中總店<em>每日手工現烤</em>，冷藏直送桃園客戶手中。'
            . '適合彌月、年節、商務送禮，傳遞職人手作的純粹蛋香。</p>',
    ],
    [
        'slug' => 'hsinchu', 'label' => '新竹市', 'sort' => 50,
        'store_title' => '新竹長崎蛋糕宅配｜奧喜手工現烤・竹科伴手禮推薦',
        'store_desc'  => '奧喜長崎蛋糕新竹宅配，竹科、新竹市區到府。台中職人手工現烤、最單純食材，日式長崎蛋糕送禮首選。彌月、商務送禮體面大方。訂購 04-22416560。',
        'store_kw'    => '新竹長崎蛋糕,新竹宅配蛋糕,竹科伴手禮,新竹彌月禮盒,新竹日式糕點',
        'ms_title'    => '奧喜長崎蛋糕 新竹宅配｜手工現烤・竹科商務送禮首選',
        'ms_desc'     => '奧喜長崎蛋糕新竹宅配，直送竹科與新竹市區。台中職人手工現烤，以最單純食材傳達職人精神，日式長崎蛋糕的純粹蛋香，適合商務送禮與彌月禮盒。',
        'ms_kw'       => '奧喜長崎蛋糕新竹,竹科送禮,新竹宅配,新竹蛋糕,新竹日式糕點',
        'ms_intro'    => '<p>奧喜長崎蛋糕<strong>新竹宅配</strong>，覆蓋<strong>新竹市、竹北、竹科園區</strong>。'
            . '由台中總店<em>每日手工現烤</em>、冷藏直送，是新竹科技業常見的<strong>商務送禮選擇</strong>。'
            . '彌月禮盒大方體面，職人手作蛋香傳遞「簡單卻不凡」的品牌初心。</p>',
    ],
];

$insCity = $db->prepare("INSERT INTO client_city_pages
    (client_id, city_slug, city_label, sort_order, is_active, filter_cases_by_region,
     store_meta_title, store_meta_desc, store_keywords,
     minisite_meta_title, minisite_meta_desc, minisite_keywords, minisite_intro_html,
     created_at)
    VALUES (?,?,?,?,1,0,?,?,?,?,?,?,?,NOW())");
$updCity = $db->prepare("UPDATE client_city_pages SET
     sort_order=?, is_active=1,
     store_meta_title=?, store_meta_desc=?, store_keywords=?,
     minisite_meta_title=?, minisite_meta_desc=?, minisite_keywords=?, minisite_intro_html=?
     WHERE client_id=? AND city_slug=?");

foreach ($cities as $c) {
    $existing = $db->prepare("SELECT id FROM client_city_pages WHERE client_id=? AND city_slug=?");
    $existing->execute([$clientId, $c['slug']]);
    if ($existing->fetchColumn()) {
        $updCity->execute([
            $c['sort'],
            $c['store_title'], $c['store_desc'], $c['store_kw'],
            $c['ms_title'], $c['ms_desc'], $c['ms_kw'], $c['ms_intro'],
            $clientId, $c['slug'],
        ]);
        echo "ℹ️  {$c['label']} 已存在 → UPDATE\n";
    } else {
        $insCity->execute([
            $clientId, $c['slug'], $c['label'], $c['sort'],
            $c['store_title'], $c['store_desc'], $c['store_kw'],
            $c['ms_title'], $c['ms_desc'], $c['ms_kw'], $c['ms_intro'],
        ]);
        echo "✅ {$c['label']} 已新增\n";
    }
}

// ─── 4. 輸出 10 條驗證 URL ───
echo "\n=== 10 條 SEO 入口 URL（驗證用）===\n";
foreach ($cities as $c) {
    echo "  📢 https://www.gomag.com.tw/store/oishii/{$c['slug']}\n";
}
foreach ($cities as $c) {
    echo "  🌐 https://oishii.gomag.com.tw/{$c['slug']}\n";
}
echo "\n備註：oishii.gomag.com.tw 子網域需在 Hostinger hPanel 設定 + SSL 簽發，未設前 https 子網域會 SSL 錯誤。\n";
echo "完成。\n";
