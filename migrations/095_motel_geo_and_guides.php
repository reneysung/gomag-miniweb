<?php
// ============================================================
// Migration 095 — 6 都汽車旅館子服務頁 + 6 篇懶人包
// ------------------------------------------------------------
// 1. geo_category_pages：台北/新北/桃園/台中/台南/高雄 各 1 個 motel 子服務頁
//    城市差異化（區域、行情）。
// 2. guides：6 城各 1 篇懶人包，從 DB 撈 motel-tagged 店家、生卡片清單
//    （照片+名稱+區+地址+電話+官網按鈕、無描述）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/095_motel_geo_and_guides.php
// 冪等：upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$cat = 12; // lodging
$MOTEL_KW = 124;

// ── helpers ──
function img_url($p) {
    if (!$p) return '';
    if (preg_match('/^https?:\/\//', $p)) return $p;
    return 'https://www.gomag.com.tw/' . ltrim($p, '/');
}
function area_chip($address, $city_name) {
    // 從地址抽「X區」
    if (preg_match('/([\x{4e00}-\x{9fff}]+(?:區|市|鎮))/u', preg_replace("/^{$city_name}市?/u", '', $address ?? ''), $m)) {
        return $m[1];
    }
    return $city_name;
}

function motelIntro($city, $areas, $priceLine) {
    return "<p>{$city}汽車旅館多分布在{$areas}，提供獨棟車庫、隔音房型，主打隱私、便利位置，無論「商務出差住宿、約會休息、家庭旅遊過夜、活動 party 包房」都適合。</p>\n"
      . "<p>{$city}常見的汽車旅館使用情境：</p>\n<ul>\n"
      . "<li><strong>商務出差住宿</strong>：交流道周邊、近高鐵車站，隔天直接上班/開會方便。</li>\n"
      . "<li><strong>約會休息（1.5–3 小時）</strong>：午後或晚間短暫休息，附按摩浴缸、影音設備。</li>\n"
      . "<li><strong>家庭旅遊／活動過夜</strong>：大坪數家庭房、KTV／投影包房，適合多人聚會。</li>\n"
      . "<li><strong>聚會 party</strong>：派對房型、視聽 KTV，朋友群聚。</li>\n</ul>\n"
      . "<p>{$priceLine}{$areas}</p>\n"
      . "<p>挑{$city}汽車旅館，建議看：①<strong>地段</strong>（離市區/交流道）；②<strong>隱私與隔音</strong>（獨棟車庫、刷卡感應）；③<strong>清潔度與設備</strong>；④<strong>加價項目</strong>（過夜延長、第二人費、KTV/投影）；⑤<strong>訂房政策</strong>（旺日是否需訂位、ID/押金規矩）。歡迎{$city}在地優質汽車旅館上架。</p>";
}
function motelFaqs($city, $areas, $priceFaq) {
    return [
        ['q'=>"{$city}汽車旅館休息和住宿多少錢？", 'a'=>$priceFaq],
        ['q'=>"{$city}哪些區汽車旅館最多？", 'a'=>$areas],
        ['q'=>'汽車旅館休息和住宿差在哪？', 'a'=>'休息按 1.5–3 小時計，價格較低；住宿過夜含早餐機率較高、有 check-in/check-out 時間。'],
        ['q'=>'汽車旅館需要先訂位嗎？', 'a'=>'平日多可現場入住；旺日、連假、知名節日（情人節、跨年）建議提前電話/官網訂位。'],
        ['q'=>'多人入住會加錢嗎？', 'a'=>'多數汽車旅館以「2 人」為基本入住人數，超出會收第二人費；訂位前先確認房型容量與加價規則。'],
        ['q'=>'需要押 ID 證件嗎？', 'a'=>'多數要驗證 ID（身分證/駕照），部分收押金、退房時返還。隱密性多數有獨棟車庫遮擋。'],
        ['q'=>'適合家庭過夜嗎？', 'a'=>'大坪數家庭房適合，但要確認嬰兒床、嬰兒澡盆等需求是否可提供。'],
        ['q'=>"{$city}汽車旅館有附停車嗎？", 'a'=>'絕大多數汽車旅館「房內車庫」設計，一車位包含在房價內。少數市區型可能採共用停車場、訂房先問清楚。'],
    ];
}

$cities = [
  'taipei' => [
    'name'=>'台北', 'areas'=>'中山（大直/南京）、信義、內湖、松山、北投一帶最密集，內科與南港產業園區商務人口、信義商圈休閒人潮交織。',
    'price'=>'行情大致：休息（1.5–3 小時）NT$1,200–2,800、住宿過夜 NT$2,500–6,000+，平假日與連假浮動大。',
    'priceFaq'=>'休息（1.5–3 小時）約 NT$1,200–2,800、住宿過夜約 NT$2,500–6,000+；信義/大直高端房型較高，內湖/北投平實。',
  ],
  'newtaipei' => [
    'name'=>'新北', 'areas'=>'中和、三重、板橋、林口、三峽一帶最常見，林口重劃區與三重夜生活商圈各有特色。',
    'price'=>'行情大致：休息（1.5–3 小時）NT$1,000–2,500、住宿過夜 NT$2,200–5,500+。',
    'priceFaq'=>'休息（1.5–3 小時）約 NT$1,000–2,500、住宿過夜約 NT$2,200–5,500+；林口/中和精品館較高、三重/三峽親民。',
  ],
  'taoyuan' => [
    'name'=>'桃園', 'areas'=>'桃園區、中壢、龜山、大園、楊梅一帶最常見，近高鐵、機場與重劃區為主，商務與機場過境需求穩定。',
    'price'=>'行情大致：休息（1.5–3 小時）NT$900–2,200、住宿過夜 NT$1,800–4,800+。',
    'priceFaq'=>'休息（1.5–3 小時）約 NT$900–2,200、住宿過夜約 NT$1,800–4,800+；大園/楊梅較實惠、桃園市區較高。',
  ],
  'taichung' => [
    'name'=>'台中', 'areas'=>'北屯、西屯、南屯、太平、大里一帶最密集，七期商圈與北屯重劃區的精品館特別多。',
    'price'=>'行情大致：休息（1.5–3 小時）NT$1,000–2,800、住宿過夜 NT$2,200–6,000+，台中精品館等級齊全。',
    'priceFaq'=>'休息（1.5–3 小時）約 NT$1,000–2,800、住宿過夜約 NT$2,200–6,000+；七期/西屯精品館較高、太平/大里親民。',
  ],
  'tainan' => [
    'name'=>'台南', 'areas'=>'中西區、北區、東區、永康、安南一帶最常見，府城傳統與南科商務出差需求並存。',
    'price'=>'行情大致：休息（1.5–3 小時）NT$800–2,200、住宿過夜 NT$1,600–4,500+，比北部都會略親民。',
    'priceFaq'=>'休息（1.5–3 小時）約 NT$800–2,200、住宿過夜約 NT$1,600–4,500+；府城精品館較高、永康/安南親民。',
  ],
  'kaohsiung' => [
    'name'=>'高雄', 'areas'=>'楠梓、仁武、鼓山、鳳山、鳥松一帶最常見，南科與港都商業活動帶動商務與休閒需求。',
    'price'=>'行情大致：休息（1.5–3 小時）NT$800–2,200、住宿過夜 NT$1,800–4,500+。',
    'priceFaq'=>'休息（1.5–3 小時）約 NT$800–2,200、住宿過夜約 NT$1,800–4,500+；鼓山/楠梓精品館較高、鳳山/鳥松親民。',
  ],
];

// ── 1. geo 子服務頁 ──
$gp = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, 12, 'motel', '汽車旅館', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
foreach ($cities as $slug => $c) {
    $intro = motelIntro($c['name'], $c['areas'], $c['price']);
    $faqs  = motelFaqs($c['name'], $c['areas'], $c['priceFaq']);
    $mt = "{$c['name']}汽車旅館推薦｜在地精品 Motel 行情・地段・隱私一次看";
    $md = "{$c['name']}汽車旅館怎麼挑？休息與住宿行情、各區精品 Motel 比較、隱私隔音、訂房眉角，店家好口碑收錄的{$c['name']}在地汽車旅館。";
    $gp->execute([$slug, $intro, json_encode($faqs, JSON_UNESCAPED_UNICODE), $mt, $md]);
    echo "GEO: {$c['name']} 汽車旅館\n";
}

// ── 2. listicle guides (data-driven) ──
$STYLE = <<<'CSS'
<style>
.mot-g{font-family:'Noto Serif TC','Songti TC',serif;color:#211e1b;line-height:1.95;}
.mot-g p{margin:0 0 16px;}
.mot-g .mot-lead{font-size:1.07rem;color:#574f49;}
.mot-g .mot-rule{display:flex;align-items:center;gap:14px;margin:34px 0 6px;}
.mot-g .mot-rule::before,.mot-g .mot-rule::after{content:"";height:1px;background:#ece6e0;flex:1;}
.mot-g .mot-rule span{font-family:'Noto Sans TC',sans-serif;font-size:.74rem;letter-spacing:.24em;color:#938a82;}
.mot-g .mot-card{border:1px solid #ece6e0;border-radius:18px;background:#fdfbf9;margin:32px 0;overflow:hidden;box-shadow:0 14px 34px -26px rgba(60,40,30,.5);}
.mot-g .mot-pic{width:100%;height:240px;object-fit:cover;display:block;background:#efeae6;}
.mot-g .mot-body{padding:22px 24px 24px;}
.mot-g .mot-head{display:flex;align-items:baseline;gap:12px;margin-bottom:14px;}
.mot-g .mot-no{font-family:'Noto Serif TC',serif;font-size:2rem;font-weight:900;color:#FF5A36;line-height:.9;}
.mot-g .mot-nm{font-size:1.22rem;font-weight:900;letter-spacing:.5px;}
.mot-g .mot-area{margin-left:auto;font-family:'Noto Sans TC',sans-serif;font-size:.73rem;color:#938a82;border:1px solid #ece6e0;border-radius:999px;padding:3px 11px;white-space:nowrap;}
.mot-g .mot-info{font-family:'Noto Sans TC',sans-serif;font-size:.96rem;color:#3f3a36;line-height:2;border-top:1px solid #ece6e0;padding-top:14px;}
.mot-g .mot-info b{color:#211e1b;margin-right:8px;}
.mot-g .mot-info .r{display:block;margin-bottom:2px;}
.mot-g .mot-btn{display:inline-block;margin-top:14px;background:#FF5A36;color:#fff;padding:9px 22px;border-radius:999px;font-weight:700;text-decoration:none;font-size:.92rem;font-family:'Noto Sans TC',sans-serif;}
.mot-g .mot-cta{border:1px solid #FF5A36;background:#fff7f4;border-radius:16px;padding:24px;margin:34px 0 0;text-align:center;font-family:'Noto Sans TC',sans-serif;}
.mot-g .mot-cta p{margin:0 0 14px;color:#574f49;font-size:.98rem;}
.mot-g .mot-cta a{display:inline-block;background:#FF5A36;color:#fff;padding:12px 28px;border-radius:999px;font-weight:700;text-decoration:none;letter-spacing:1px;}
</style>
CSS;

$ql = $db->prepare("SELECT c.id, c.brand_name, c.address, c.phone, c.external_website_url, c.hero_image_path
    FROM clients c JOIN client_service_keywords csk ON csk.client_id=c.id
    WHERE csk.service_keyword_id=? AND c.city_slug=? AND c.is_active=1 AND c.is_placeholder=0
    ORDER BY c.id");

$gsql = "INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, meta_title, meta_desc, status, published_at)
    VALUES (:slug, :title, :excerpt, :body, :cover, :city, 12, :mt, :md, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        cover_image=VALUES(cover_image), city_slug=VALUES(city_slug), category_id=12,
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
        status='published', published_at=COALESCE(published_at, NOW())";
$gins = $db->prepare($gsql);

foreach ($cities as $cslug => $c) {
    $ql->execute([$MOTEL_KW, $cslug]);
    $motels = $ql->fetchAll(PDO::FETCH_ASSOC);
    $n = count($motels);
    if ($n === 0) { echo "GUIDE: {$c['name']} 0 家、跳過\n"; continue; }
    $cover = img_url($motels[0]['hero_image_path']);
    $cardsH = '';
    $i = 0;
    foreach ($motels as $m) {
        $i++;
        $no = sprintf('%02d', $i);
        $img = img_url($m['hero_image_path']);
        $area = area_chip($m['address'], $c['name']);
        $picH = $img ? '<img class="mot-pic" src="'.$img.'" alt="'.htmlspecialchars($m['brand_name'], ENT_QUOTES).'｜'.$c['name'].'汽車旅館" loading="lazy">' : '';
        $web = $m['external_website_url'] ? '<a class="mot-btn" href="'.htmlspecialchars($m['external_website_url'], ENT_QUOTES).'" target="_blank" rel="noopener">看官網／訂房 →</a>' : '';
        $cardsH .= '<div class="mot-card">'.$picH.'<div class="mot-body">'
            .'<div class="mot-head"><span class="mot-no">'.$no.'</span><span class="mot-nm">'.htmlspecialchars($m['brand_name']).'</span><span class="mot-area">'.htmlspecialchars($area).'</span></div>'
            .'<div class="mot-info">'
            .'<span class="r"><b>地址</b>'.htmlspecialchars($m['address']).'</span>'
            .'<span class="r"><b>電話</b>'.htmlspecialchars($m['phone'] ?: '-').'</span>'
            .'</div>'.$web
            .'</div></div>';
    }
    $lead = "找{$c['name']}汽車旅館，從商務出差住宿、約會休息、家庭過夜到 party 包房，店家好口碑彙整 <strong>{$n} 家{$c['name']}在地汽車旅館</strong>，含店面照、地址、電話與官網訂房連結，一次看完。";
    $body = $STYLE
      .'<div class="mot-g">'
      .'<p class="mot-lead">'.$lead.'</p>'
      .'<div class="mot-rule"><span>編輯精選 ・ '.$n.' 家</span></div>'
      .$cardsH
      .'<div class="mot-cta"><p>想看更多'.$c['name'].'在地汽車旅館、行情與選房眉角？</p>'
      .'<a href="https://www.gomag.com.tw/city/'.$cslug.'/lodging/motel" target="_blank" rel="noopener">店家好口碑・'.$c['name'].'汽車旅館專頁 →</a></div>'
      .'</div>';
    $gins->execute([
      ':slug'=>"{$cslug}-motel-recommend",
      ':title'=>"{$c['name']}汽車旅館懶人包｜2026 嚴選 {$n} 家在地推薦",
      ':excerpt'=>"找{$c['name']}汽車旅館？店家好口碑精選 {$n} 家在地汽車旅館，含店面照、地址、電話、官網訂房一次看，商務出差/約會休息/家庭過夜都適用。",
      ':body'=>$body,
      ':cover'=>$cover,
      ':city'=>$cslug,
      ':mt'=>"{$c['name']}汽車旅館懶人包｜2026 嚴選 {$n} 家在地推薦｜店家好口碑",
      ':md'=>"{$c['name']}汽車旅館推薦 2026！店家好口碑精選 {$n} 家在地 Motel，含店面照、地址、電話、官網訂房連結，商務出差住宿、約會休息、家庭過夜一次看。",
    ]);
    printf("GUIDE: %-25s %d 家 (cover=%s)\n", $cslug."-motel-recommend", $n, basename($cover));
}

echo "\n完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
