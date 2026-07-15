<?php
/**
 * 166_pilot_filler_stores_north.php
 * 空殼頁試點：台北清潔 + 新北裝潢細清 各補 3 家陪襯店（2026-07-15 城市健檢第三波）
 *
 * 背景：29 頁空殼中先試點 2 頁（Reney 拍板「照推」+ 名單已核）。
 *       陪襯店照 motel 慣例：真實業者、基本資訊（名/址/電話）+ 1 張官網照 +
 *       主按鈕導回官方網站；不寫評價、不捏內容；業者要求即撤。
 *       資料 2026-07-15 逐一從各官網查證。
 *
 * 陪襯店 6 家：
 *   台北清潔  cleaning-taipei-1..3：家適美／愛潔淨／文潔（文潔在汐止、主打內湖南港，Reney 核過）
 *   新北細清  renodetail-newtaipei-1..3：永創／家事達／立寶
 * 標籤：清潔=sk#1(cleaning)、細清=sk#13(reno-detail) → citycat 子服務頁自動列店。
 * 冪等：以 slug 判斷已存在則跳過。
 *
 * 跑法：HTTP_HOST=www.gomag.com.tw php migrations/166_pilot_filler_stores_north.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$stores = [
    [
        'slug' => 'cleaning-taipei-1', 'brand_name' => '家適美居家清潔', 'city_slug' => 'taipei', 'kw' => 1,
        'address' => '台北市南港區八德路4段768巷7號1樓', 'phone' => '02-2653-9977',
        'url' => 'https://www.justclean.com.tw/',
        'meta_title' => '家適美居家清潔｜台北清潔公司推薦｜店家好口碑',
        'meta_desc'  => '台北清潔公司「家適美居家清潔」店家資訊、地址、電話與官網連結；居家清潔、定期打掃、清潔加清運服務，服務大台北地區。',
    ],
    [
        'slug' => 'cleaning-taipei-2', 'brand_name' => '愛潔淨居家清潔', 'city_slug' => 'taipei', 'kw' => 1,
        'address' => '台北市萬華區西園路二段11巷13號1樓', 'phone' => '02-2302-1312',
        'url' => 'https://loveclean.com.tw/',
        'meta_title' => '愛潔淨居家清潔｜台北清潔公司推薦｜店家好口碑',
        'meta_desc'  => '台北清潔公司「愛潔淨居家清潔」店家資訊、地址、電話與官網連結；居家清潔、家電清洗等多元清潔服務。',
    ],
    [
        'slug' => 'cleaning-taipei-3', 'brand_name' => '文潔清潔企業社', 'city_slug' => 'taipei', 'kw' => 1,
        'address' => '新北市汐止區仁愛路119巷18號1樓', 'phone' => '0956-264-217',
        'url' => 'https://www.wen-chieh.com/',
        'meta_title' => '文潔清潔企業社｜台北清潔公司推薦｜店家好口碑',
        'meta_desc'  => '清潔公司「文潔清潔企業社」店家資訊、地址、電話與官網連結；主要服務台北內湖、南港、信義與汐止、基隆一帶。',
    ],
    [
        'slug' => 'renodetail-newtaipei-1', 'brand_name' => '永創清潔公司', 'city_slug' => 'newtaipei', 'kw' => 13,
        'address' => '新北市三重區', 'phone' => '0916-529-925',
        'url' => 'https://www.postclean.com.tw/',
        'meta_title' => '永創清潔公司｜新北裝潢細清推薦｜店家好口碑',
        'meta_desc'  => '新北裝潢細清「永創清潔公司」店家資訊、電話與官網連結；裝潢後細清、空屋清潔、洗地打蠟，服務北北基桃。',
    ],
    [
        'slug' => 'renodetail-newtaipei-2', 'brand_name' => '家事達家事服務', 'city_slug' => 'newtaipei', 'kw' => 13,
        'address' => '新北市三重區大同南路158巷30號', 'phone' => '02-8972-1158',
        'url' => 'https://jaster.tw/',
        'meta_title' => '家事達家事服務｜新北裝潢細清推薦｜店家好口碑',
        'meta_desc'  => '新北裝潢細清「家事達家事服務」店家資訊、地址、電話與官網連結；裝潢粗清細清、空屋清潔、地板打蠟與石材保養。',
    ],
    [
        'slug' => 'renodetail-newtaipei-3', 'brand_name' => '立寶清潔工程', 'city_slug' => 'newtaipei', 'kw' => 13,
        'address' => '新北市板橋區信義路110巷4弄14號4樓', 'phone' => '0978-313-597',
        'url' => 'https://www.libau-clean.com.tw/',
        'meta_title' => '立寶清潔工程｜新北裝潢細清推薦｜店家好口碑',
        'meta_desc'  => '新北裝潢細清「立寶清潔工程」店家資訊、地址、電話與官網連結；裝潢細清、居家與辦公室清潔，服務台北新北桃園。',
    ],
];

$ins = $db->prepare("INSERT INTO clients
    (slug, subdomain, brand_name, category_id, city_slug, address, phone, external_website_url,
     hero_image_path, hero_image_fit, store_meta_title, store_meta_desc,
     is_active, is_placeholder, has_minisite, store_template, minisite_template)
    VALUES (?, ?, ?, 2, ?, ?, ?, ?, ?, 'cover', ?, ?, 1, 0, 0, '_default', '_default')");
$tag = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");

foreach ($stores as $s) {
    $exists = $db->prepare("SELECT id FROM clients WHERE slug=?");
    $exists->execute([$s['slug']]);
    if ($id = $exists->fetchColumn()) {
        echo "↷ {$s['brand_name']}（{$s['slug']}）已存在 id={$id}，補標籤即可\n";
    } else {
        $ins->execute([
            $s['slug'], $s['slug'], $s['brand_name'], $s['city_slug'],
            $s['address'], $s['phone'], $s['url'],
            'uploads/clients/cleaning/' . $s['slug'] . '.jpg',
            $s['meta_title'], $s['meta_desc'],
        ]);
        $id = (int)$db->lastInsertId();
        echo "✅ 建立 {$s['brand_name']}（{$s['slug']}）id={$id}\n";
    }
    $tag->execute([$id, $s['kw']]);
}

echo "\n=== 驗證：兩頁子服務店數 ===\n";
foreach ([['taipei','cleaning'], ['newtaipei','reno-detail']] as [$city, $svc]) {
    $q = $db->prepare("
        SELECT COUNT(DISTINCT cl.id) FROM clients cl
        JOIN client_service_keywords csk ON csk.client_id=cl.id
        JOIN service_keywords sk ON sk.id=csk.service_keyword_id
        WHERE cl.is_active=1 AND (sk.slug=? OR sk.page_slug=?)
          AND (cl.city_slug=? OR EXISTS (SELECT 1 FROM client_city_pages p WHERE p.client_id=cl.id AND p.city_slug=? AND p.is_active=1))");
    $q->execute([$svc, $svc, $city, $city]);
    echo "  /city/{$city}/home-service/{$svc} → " . $q->fetchColumn() . " 店\n";
}
