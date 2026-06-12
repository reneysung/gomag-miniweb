<?php
// ============================================================
// Migration 124 — 台南清潔雙修：3 家 placeholder + 《台南清潔公司推薦》懶人包
// ------------------------------------------------------------
// 背景：/city/tainan/home-service/cleaning 已收錄、內容厚、4 家正式店，
// 但「台南清潔公司/推薦/居家清潔」主力詞 3 個月 0 曝光（市場競爭強 +
// 域內信號分散）。
//
// A. 3 家 placeholder（Reney 漫途懶人包名單舊識，NAP=公開資料 2026-06-11 查）：
//    倍亮清潔（新市）/ 臥龍閣居家清潔（新營）/ 沐沐清潔（安平）
//    → 台南 cleaning 頁 4 → 7 家
// B. 懶人包 guide《台南清潔公司推薦 2026：在地嚴選 7 家》
//    - 置入順序照漫途主打：旭浪 → 廣達 → 倍亮 → 享清 → 臥龍閣 → 沐沐 → 尚撰
//    - 4 自家連 /store/ 行銷頁；3 在地口碑標註、不外連、只列公開資訊
//    - 直接打「台南清潔公司推薦」這個詞（懶人包格式 CTR 最高）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/124_tainan_cleaning_boost.php
// 冪等：slug 查重 + upsert
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ════════════════════════════════════════════
// A. 3 家 placeholder
// ════════════════════════════════════════════
echo "═══ A. 3 家台南清潔 placeholder ═══\n";
$stores = [
    [
        'slug' => 'tainan-beiliang-clean', 'brand' => '倍亮清潔',
        'tagline' => '台南居家清潔＋冷氣、水塔、抽油煙機拆洗一條龍，浴廁防霉工程',
        'phone' => '0938-286-652', 'address' => '台南市新市區光華街165號',
        'keywords' => [1, 141], // cleaning + aircon-clean（有冷氣清洗服務）
    ],
    [
        'slug' => 'tainan-wolonge-clean', 'brand' => '臥龍閣居家清潔',
        'tagline' => '台南新營定期居家維護、大掃除、退租清潔、裝潢後細清、除塵蟎水洗、地板打蠟、石材美容',
        'phone' => '0930-546-164', 'address' => '台南市新營區太子宮太南里305-37號',
        'keywords' => [1, 13], // cleaning + reno-detail
    ],
    [
        'slug' => 'tainan-mumu-clean', 'brand' => '沐沐清潔有限公司',
        'tagline' => '大台南居家清潔、空屋清潔、裝潢清潔、店面辦公室、租屋套房清潔',
        'phone' => '06-2990076', 'address' => '台南市安平區建平八街372巷38號',
        'keywords' => [1, 13],
    ],
];

$ins = $db->prepare("INSERT INTO clients
    (subdomain, slug, brand_name, tagline, industry, category_id, phone, address, city_slug,
     is_active, has_minisite, store_template, minisite_template, is_placeholder)
    VALUES (?, ?, ?, ?, '清潔服務', 2, ?, ?, 'tainan', 1, 0, '_default', '_default', 1)");
$tagStmt = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");

foreach ($stores as $s) {
    $exists = $db->prepare("SELECT id FROM clients WHERE slug=?");
    $exists->execute([$s['slug']]);
    $cid = $exists->fetchColumn();
    if ($cid) {
        echo "  ⏭ skip {$s['brand']}（已存在 #{$cid}）\n";
    } else {
        $ins->execute([$s['slug'], $s['slug'], $s['brand'], $s['tagline'], $s['phone'], $s['address']]);
        $cid = (int)$db->lastInsertId();
        echo "  ✅ #{$cid} {$s['brand']}\n";
    }
    foreach ($s['keywords'] as $kid) $tagStmt->execute([$cid, $kid]);
}

// ════════════════════════════════════════════
// B. 懶人包 guide
// ════════════════════════════════════════════
echo "\n═══ B. 懶人包《台南清潔公司推薦 2026》═══\n";

$gSlug = 'tainan-cleaning-company-recommend';
$title = '台南清潔公司推薦 2026：在地嚴選 7 家｜居家清潔、裝潢細清、定期清潔懶人包';
$metaTitle = '台南清潔公司推薦 2026｜在地嚴選 7 家｜居家清潔裝潢細清｜店家好口碑';
$metaDesc = '台南清潔公司推薦哪 7 家？涵蓋居家清潔、裝潢細清、定期清潔、退租清潔、除蟲消毒、冷氣水塔清洗，中西區、東區、永康、安平、新營在地嚴選，行情每小時 NT$400–600、細清每坪 NT$500–900。';
$excerpt = '台南清潔公司怎麼挑？店家好口碑整理 7 家台南在地清潔團隊：旭浪、廣達、倍亮、享清、臥龍閣、沐沐、尚撰，服務涵蓋居家清潔、裝潢細清、定期清潔、除蟲消毒，行情與挑選重點一篇看。';
$cover = 'https://www.gomag.com.tw/uploads/guides/covers/cleaning.svg';

// 7 家（順序照漫途主打：旭浪→廣達→倍亮→享清→臥龍閣→沐沐→尚撰）
$list = [
    ['name' => '旭浪清潔公司', 'area' => '永康區', 'own' => 'stonebeauty',
     'cuisine' => '宅修清潔・綜合居家清潔',
     'desc' => '台南在地宅修清潔團隊，從居家清潔到宅修整理都有服務，是店家好口碑收錄的老牌台南清潔客戶。',
     'phone' => '0977-340903', 'address' => '台南市大灣路 581 號'],
    ['name' => '廣達清潔公司', 'area' => '北區中華北路', 'own' => 'cleaningcompany4',
     'cuisine' => '裝潢細清・交屋清潔',
     'desc' => '主打裝潢細清，從新成屋、中古翻修到辦公室、廠房交屋都做，制度化團隊＋現場估價透明，把粉塵屋打掃到能直接入住。',
     'phone' => '06-2523773', 'address' => '台南市中華北路二段 283 號'],
    ['name' => '倍亮清潔', 'area' => '新市區', 'own' => null,
     'cuisine' => '居家清潔・家電清洗一條龍',
     'desc' => '居家清潔加上冷氣、水塔、抽油煙機拆洗一條龍服務，並有浴廁防霉工程，一次處理多種居家清潔需求。',
     'phone' => '0938-286-652', 'address' => '台南市新市區光華街 165 號'],
    ['name' => '享清清潔工程企業社', 'area' => '仁安八街', 'own' => 'cleaningcompany2',
     'cuisine' => '居家清潔・裝潢細清・搬家搬運',
     'desc' => '居家清潔、裝潢細清加搬家搬運一站式服務，搬家前後的清潔需求可以一次解決。',
     'phone' => '0975-721796', 'address' => '台南市仁安八街 5 號'],
    ['name' => '臥龍閣居家清潔', 'area' => '新營區', 'own' => null,
     'cuisine' => '定期維護・細清・石材美容',
     'desc' => '台南新營在地團隊，定期居家維護、大掃除、退租清潔、裝潢後細清、除塵蟎水洗、地板打蠟到石材美容拋光都有服務，溪北地區（新營、鹽水、柳營）找清潔不用捨近求遠。',
     'phone' => '0930-546-164', 'address' => '台南市新營區太子宮太南里 305-37 號'],
    ['name' => '沐沐清潔有限公司', 'area' => '安平區', 'own' => null,
     'cuisine' => '居家・空屋・店面辦公室清潔',
     'desc' => '大台南服務範圍，居家清潔、空屋清潔、裝潢清潔、店面、辦公室、租屋套房清潔都做，估價與服務全年無休。',
     'phone' => '06-2990076', 'address' => '台南市安平區建平八街 372 巷 38 號'],
    ['name' => '尚撰除蟲消毒清潔有限公司', 'area' => '安南區', 'own' => 'PCO1',
     'cuisine' => '除蟲消毒・清潔',
     'desc' => '清潔加除蟲消毒雙專業，家裡有蟲害、黴菌、消毒需求時，清潔與病媒防治可以同一團隊處理。',
     'phone' => '06-2560313', 'address' => '台南市安南區安中路四段 4 巷 79 號 1 樓'],
];

$body  = '<p>台南清潔公司怎麼挑？從<strong>中西區、東區的老屋與租屋退租</strong>、<strong>永康、安平的新成屋裝潢細清</strong>，到<strong>新營溪北地區的定期維護</strong>，每種需求適合的團隊不一樣。這篇整理 7 家店家好口碑收錄與在地口碑的台南清潔團隊，服務涵蓋居家清潔、裝潢細清、定期清潔、退租清潔、除蟲消毒、冷氣水塔清洗，一篇看完怎麼挑。</p>' . "\n";
$body .= '<p><em>※ 名單含店家好口碑收錄商家與網路在地口碑團隊，資料以各店公開資訊為準，行情請以現場估價為主。</em></p>' . "\n\n";

$body .= '<h2 id="toc">📖 文章目錄</h2>' . "\n<ol>\n";
foreach ($list as $i => $s) {
    $body .= '  <li><a href="#s' . ($i + 1) . '">第 ' . ($i + 1) . ' 家｜' . htmlspecialchars($s['name']) . '（' . htmlspecialchars($s['cuisine']) . '）</a></li>' . "\n";
}
$body .= '  <li><a href="#price">台南清潔行情</a></li>' . "\n";
$body .= '  <li><a href="#tips">怎麼挑：3 個重點</a></li>' . "\n";
$body .= '  <li><a href="#faq">常見問題 FAQ</a></li>' . "\n";
$body .= "</ol>\n\n";

foreach ($list as $i => $s) {
    $n = $i + 1;
    $body .= "<h2 id=\"s{$n}\">◈ 第 {$n} 家｜{$s['name']}｜{$s['area']}　{$s['cuisine']}</h2>\n";
    $body .= "<p>{$s['desc']}</p>\n";
    $body .= "<ul class=\"g-store-info\">\n";
    $body .= "  <li>📍 地址：{$s['address']}</li>\n";
    $body .= "  <li>📞 電話：<a href=\"tel:" . str_replace([' ', '-'], '', $s['phone']) . "\">{$s['phone']}</a></li>\n";
    if ($s['own']) {
        $body .= "  <li>🔗 完整介紹與口碑評價：<a href=\"https://www.gomag.com.tw/store/{$s['own']}\">看{$s['name']}店家頁</a></li>\n";
    } else {
        $body .= "  <li>💬 網路在地口碑團隊</li>\n";
    }
    $body .= "</ul>\n\n";
}

$body .= '<h2 id="price">台南清潔行情</h2>' . "\n<ul>\n";
$body .= '  <li><strong>鐘點居家清潔</strong>：每小時約 NT$400–600，多數每次最低 3 小時。</li>' . "\n";
$body .= '  <li><strong>裝潢後細清</strong>：每坪約 NT$500–900，整戶視坪數與粉塵量 NT$15,000–40,000。</li>' . "\n";
$body .= '  <li><strong>退租／空屋清潔</strong>：套房約 NT$2,500–5,000、整戶 NT$5,000–15,000。</li>' . "\n";
$body .= '  <li><strong>定期清潔（簽約）</strong>：月約制每次單價比單次便宜 1–2 成。</li>' . "\n";
$body .= "</ul>\n\n";

$body .= '<h2 id="tips">怎麼挑：3 個重點</h2>' . "\n<ol>\n";
$body .= '  <li><strong>需求對人</strong>：日常維護找鐘點型；交屋／裝潢後找細清專門（工具藥水不同）；新營溪北找在地團隊省車馬費。</li>' . "\n";
$body .= '  <li><strong>看報價透明度</strong>：含不含耗材、垃圾清運、家具搬移？免費到府估價的優先。</li>' . "\n";
$body .= '  <li><strong>看在地口碑</strong>：Google 評論分數＋評論數雙指標，再對照本站收錄商家交叉驗證。</li>' . "\n";
$body .= "</ol>\n\n";

$body .= '<h2 id="faq">常見問題 FAQ</h2>' . "\n";
$body .= "<h3>Q1：台南清潔公司行情多少？</h3>\n<p>鐘點清潔每小時 NT\$400–600；裝潢細清每坪 NT\$500–900；退租清潔套房 NT\$2,500–5,000。簽定期約通常比單次便宜。</p>\n";
$body .= "<h3>Q2：裝潢細清跟一般打掃差在哪？</h3>\n<p>細清專門處理裝潢後的粉塵、矽利康殘膠、玻璃水痕，工具與藥水都不同，費用約一般清潔 1.5–2 倍。詳見<a href=\"https://www.gomag.com.tw/guide/cleaning-detail-vs-basic\">細清 vs 一般打掃</a>。</p>\n";
$body .= "<h3>Q3：過年大掃除要提前多久預約？</h3>\n<p>11 月就開始搶，12 月到過年前 2 週是爆量期，建議 11 月中前預約。</p>\n";
$body .= "<h3>Q4：新營、鹽水有在地清潔團隊嗎？</h3>\n<p>有，本文第 5 家臥龍閣就在新營，溪北地區（新營、鹽水、柳營、白河）可優先找在地團隊，省跨區車馬費。</p>\n";
$body .= "<h3>Q5：除蟲消毒可以跟清潔一起做嗎？</h3>\n<p>可以，本文第 7 家尚撰是清潔＋病媒防治雙專業，蟲害、黴菌、消毒可一次處理。</p>\n\n";

$body .= '<h2>結尾：怎麼用這份清單</h2>' . "\n";
$body .= '<p>挑台南清潔公司：① 先想需求類型（日常／細清／退租／除蟲）、② 看區域（市區 vs 溪北）、③ 點進店家頁看完整介紹與評價。更多台南清潔團隊見 <a href="https://www.gomag.com.tw/city/tainan/home-service/cleaning"><strong>台南清潔公司</strong></a> 子分類頁，或瀏覽 <a href="https://www.gomag.com.tw/city/tainan/home-service/reno-detail">台南裝潢細清</a>、<a href="https://www.gomag.com.tw/city/tainan/home-service/aircon-clean">台南冷氣清洗</a>、<a href="https://www.gomag.com.tw/city/tainan/home-service">台南居家服務</a> 樞紐頁。</p>' . "\n";

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, cover_image, status, published_at)
    VALUES (?, ?, ?, ?, 'tainan', 2, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug=VALUES(city_slug), category_id=VALUES(category_id),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
        cover_image=VALUES(cover_image), status='published', published_at=COALESCE(published_at, NOW())");
$gs->execute([$gSlug, $title, $excerpt, $body, $metaTitle, $metaDesc, $cover]);
$r = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug='{$gSlug}'")->fetch(PDO::FETCH_ASSOC);
echo "  ✅ guide id={$r['id']} body={$r['l']} bytes\n";

// ════════════════════════════════════════════
// 驗證
// ════════════════════════════════════════════
echo "\n═══ 驗證 ═══\n";
$sql = "SELECT cl.id, cl.brand_name, cl.is_placeholder FROM clients cl
  JOIN client_service_keywords csk ON csk.client_id=cl.id
  JOIN service_keywords sk ON sk.id=csk.service_keyword_id AND sk.category_id=2 AND sk.is_active=1
  LEFT JOIN client_city_pages ccp ON ccp.client_id=cl.id AND ccp.city_slug='tainan' AND ccp.is_active=1
  WHERE cl.is_active=1 AND (cl.city_slug='tainan' OR ccp.client_id IS NOT NULL)
    AND COALESCE(NULLIF(sk.page_slug,''), sk.slug)='cleaning' GROUP BY cl.id ORDER BY cl.is_placeholder, cl.id";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "[台南 cleaning 頁] " . count($rows) . " 家：\n";
foreach ($rows as $r2) printf("  %s #%-4d %s\n", $r2['is_placeholder'] ? '⚪' : '🟢', $r2['id'], $r2['brand_name']);

$st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
$st->execute(['%/city/tainan/home-service/cleaning%']);
echo "\n台南 cleaning 頁 guide 內鏈：" . (int)$st->fetchColumn() . " 篇\n";
echo "\n預覽：\n  https://www.gomag.com.tw/guide/{$gSlug}\n  https://www.gomag.com.tw/city/tainan/home-service/cleaning\n";
