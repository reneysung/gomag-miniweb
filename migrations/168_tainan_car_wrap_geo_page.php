<?php
// ============================================================
// Migration 168 — 開「台南汽車包膜」子服務落地頁 geo（攻略#99 的家）
// ------------------------------------------------------------
// 診斷：台南 car-wrap 子服務頁 404 = 無 geo row（有 6 店卻不 render，需 geo 內容才上架）。
// 開 geo(tainan,11,car-wrap) active → 子服務頁 200、攻略#99 掛得上、cluster 內鏈通、6 店有落地頁。
// 只開包膜(必須)；鍍膜不擅自開(攻略連結已改指樞紐頁)。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/168_tainan_car_wrap_geo_page.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$intro = <<<'H'
<p>台南汽車包膜的店家集中在仁德、安平、安南（海佃路、安中路）與市區一帶。新車落地想保護原漆、或想幫愛車改個顏色質感，都能在台南找到專業的包膜行家。包膜主要分兩種需求：貼<strong>TPU 犀牛皮（PPF）</strong>做透明保護、抗石擊抗刮（高階款還有熱修復），或貼<strong>改色包膜</strong>換亮面、消光、電鍍等質感。</p>
<p>挑台南包膜看幾件事：①<strong>膜料品牌與保固</strong>（好的 TPU 材料保固約 5 年、不龜裂粉化）；②<strong>無塵施工環境</strong>（避免膜下棉絮沙點）；③<strong>正規車漆隔離劑</strong>（非坊間泡泡水，才不傷原漆）；④<strong>收邊工藝</strong>（門把、彎角收不好會翹邊起泡）。牽新車包膜是旺季，熱門店家檔期要排，建議先預約、把車開去現場評估報價。想一次比較台南在地包膜行家與真實評價，可看下方推薦攻略。</p>
H;
$faqs = json_encode([
 ['q'=>'汽車包膜和鍍膜有什麼不同？','a'=>'包膜是貼一層有厚度的實體膜，能抗石擊、抗刮、可改色、可撕除；鍍膜是塗一層很薄的鍍層，主要增豔、撥水、好清潔，不防刮擊。要保護車漆選包膜（犀牛皮），要亮與好洗選鍍膜。'],
 ['q'=>'犀牛皮（TPU）和改色包膜怎麼選？','a'=>'犀牛皮（TPU／PPF）是透明保護膜，原色不變、抗刮抗石擊；改色包膜是換顏色或質感，偏造型需求、保護性通常較低。要保護選犀牛皮，要換色選改色膜。'],
 ['q'=>'台南全車包膜大概多少錢？','a'=>'全車 TPU 犀牛皮常見約 NT$60,000–200,000 以上，依膜料品牌、等級與車型；全車改色包膜約 NT$40,000–120,000；局部犀牛皮約 NT$8,000–30,000。以現場車輛評估報價為準。'],
 ['q'=>'台南包膜店集中在哪一區？','a'=>'多集中在仁德、安平、安南（海佃路、安中路）與市區一帶。牽新車包膜是旺季，熱門店家檔期要排，建議先預約現場評估。'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$metaTitle = '台南汽車包膜推薦｜TPU 犀牛皮、改色包膜與行情｜店家好口碑';
$metaDesc = '台南汽車包膜找哪裡？TPU 犀牛皮（PPF）與改色包膜差在哪、包膜與鍍膜怎麼選、全車與局部行情、保固與無塵施工重點，仁德、安平、海佃路在地包膜行家一次看。';

$db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active, created_at)
    VALUES ('tainan', 11, 'car-wrap', '汽車包膜', ?, ?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE service_name='汽車包膜', intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1")
    ->execute([$intro,$faqs,$metaTitle,$metaDesc]);
$r=$db->query("SELECT id,is_active,LENGTH(intro_html) il FROM geo_category_pages WHERE city_slug='tainan' AND category_id=11 AND service_slug='car-wrap'")->fetch(PDO::FETCH_ASSOC);
echo "✅ 台南汽車包膜 geo #{$r['id']} active={$r['is_active']} intro={$r['il']}B\n";
echo "實頁： https://www.gomag.com.tw/city/tainan/auto-service/car-wrap\n";
