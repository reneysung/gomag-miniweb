<?php
// ============================================================
// Migration 051 — 北部 4 城裝潢細清懶人包（台北/新北/桃園/新竹）
// ------------------------------------------------------------
// 沿用台中「精緻編輯卡片」版型（scoped .lrb-g + lrb- 前綴）。4 城皆無 gomag 客戶
// → 全 Google 在地口碑（資料皆查證、未捏造），每家連各自官網/FB/部落格、CTA 導該城清潔頁。
// 有好圖放 16:9（桃園3/新竹3，圖已轉存 gomag 圖床）、無好圖用品牌標題帶（台北/新北全帶）。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/051_north_fine_clean_guides.php
// 冪等：slug upsert（保留首次 published_at）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$h = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
$IMG = 'https://www.gomag.com.tw/uploads/guides/';

$STYLE = <<<'CSS'
<style>
.lrb-g{--ink:#211e1b;--soft:#574f49;--mut:#938a82;--acc:#FF5A36;--line:#ece6e0;--cardbg:#fdfbf9;font-family:'Noto Serif TC','Songti TC',serif;color:var(--ink);line-height:1.95;}
.lrb-g p{margin:0 0 16px;}
.lrb-g .lrb-lead{font-size:1.07rem;color:var(--soft);}
.lrb-g .lrb-rule{display:flex;align-items:center;gap:14px;margin:34px 0 6px;}
.lrb-g .lrb-rule::before,.lrb-g .lrb-rule::after{content:"";height:1px;background:var(--line);flex:1;}
.lrb-g .lrb-rule span{font-family:'Noto Sans TC',sans-serif;font-size:.74rem;letter-spacing:.24em;color:var(--mut);}
.lrb-g .lrb-note{font-family:'Noto Sans TC',sans-serif;background:var(--cardbg);border:1px solid var(--line);border-radius:14px;padding:20px 22px;margin:26px 0 8px;}
.lrb-g .lrb-note h3{font-family:'Noto Serif TC',serif;font-size:1.06rem;margin:0 0 10px;letter-spacing:1.5px;}
.lrb-g .lrb-note ol{margin:0;padding-left:1.25em;font-size:.94rem;color:var(--soft);line-height:1.95;}
.lrb-g .lrb-card{border:1px solid var(--line);border-radius:18px;background:var(--cardbg);margin:32px 0;overflow:hidden;box-shadow:0 14px 34px -26px rgba(60,40,30,.5);}
.lrb-g .lrb-pic{width:100%;height:228px;object-fit:cover;display:block;background:#efeae6;}
.lrb-g .lrb-band{height:124px;background:linear-gradient(120deg,#2b2724 0%,#4b423c 60%,#5d5048 100%);display:flex;align-items:center;justify-content:center;position:relative;}
.lrb-g .lrb-band::after{content:"店家好口碑・精選";position:absolute;bottom:11px;font-family:'Noto Sans TC',sans-serif;font-size:.62rem;letter-spacing:.3em;color:rgba(255,255,255,.4);}
.lrb-g .lrb-band .bn{font-family:'Noto Serif TC',serif;color:#fff;font-size:1.5rem;font-weight:900;letter-spacing:4px;}
.lrb-g .lrb-body{padding:22px 24px 24px;}
.lrb-g .lrb-head{display:flex;align-items:baseline;gap:12px;}
.lrb-g .lrb-no{font-family:'Noto Serif TC',serif;font-size:2rem;font-weight:900;color:var(--acc);line-height:.9;}
.lrb-g .lrb-nm{font-size:1.26rem;font-weight:900;letter-spacing:.5px;}
.lrb-g .lrb-area{margin-left:auto;font-family:'Noto Sans TC',sans-serif;font-size:.73rem;color:var(--mut);border:1px solid var(--line);border-radius:999px;padding:3px 11px;white-space:nowrap;}
.lrb-g .lrb-tags{font-family:'Noto Sans TC',sans-serif;margin:13px 0 14px;display:flex;flex-wrap:wrap;gap:7px;}
.lrb-g .lrb-tags span{font-size:.75rem;color:#b14a2f;background:#fdeee9;border-radius:999px;padding:4px 11px;}
.lrb-g .lrb-desc{font-size:1.01rem;color:#3f3a36;margin:0 0 16px;line-height:1.9;}
.lrb-g .lrb-info{font-family:'Noto Sans TC',sans-serif;display:flex;flex-wrap:wrap;gap:6px 24px;border-top:1px solid var(--line);padding-top:14px;font-size:.9rem;color:var(--soft);}
.lrb-g .lrb-info b{color:var(--ink);font-weight:700;}
.lrb-g .lrb-src{margin:14px 0 0;}
.lrb-g .lrb-src a{font-family:'Noto Sans TC',sans-serif;font-size:.9rem;color:var(--acc);font-weight:700;text-decoration:none;}
.lrb-g .lrb-sum{font-size:1.3rem;font-weight:900;margin:42px 0 8px;letter-spacing:1px;}
.lrb-g .lrb-closing{font-size:1.04rem;color:var(--soft);}
.lrb-g .lrb-cta{border:1px solid var(--acc);background:#fff7f4;border-radius:16px;padding:24px;margin:34px 0 0;text-align:center;font-family:'Noto Sans TC',sans-serif;}
.lrb-g .lrb-cta p{margin:0 0 14px;color:var(--soft);font-size:.98rem;}
.lrb-g .lrb-cta a{display:inline-block;background:var(--acc);color:#fff;padding:12px 28px;border-radius:999px;font-weight:700;text-decoration:none;letter-spacing:1px;}
</style>
CSS;

// card: [no, name, area, tags[], desc, info[], srcUrl, srcText, img|null]
function lrbCard(array $c): string {
    $no = $c[0]; $name = $c[1]; $area = $c[2]; $tags = $c[3]; $desc = $c[4];
    $info = $c[5]; $src = $c[6]; $srcText = $c[7]; $img = $c[8] ?? null;
    $top = $img
        ? '<img class="lrb-pic" src="' . $img . '" alt="' . $name . '｜裝潢後細清成果" loading="lazy">'
        : '<div class="lrb-band"><span class="bn">' . $name . '</span></div>';
    $tagH = '';
    foreach ($tags as $t) $tagH .= '<span>' . $t . '</span>';
    $infoH = '';
    foreach ($info as $i) $infoH .= '<span>' . $i . '</span>';
    return '<div class="lrb-card">' . $top
        . '<div class="lrb-body">'
        . '<div class="lrb-head"><span class="lrb-no">' . $no . '</span><span class="lrb-nm">' . $name . '</span><span class="lrb-area">' . $area . '</span></div>'
        . '<div class="lrb-tags">' . $tagH . '</div>'
        . '<p class="lrb-desc">' . $desc . '</p>'
        . '<div class="lrb-info">' . $infoH . '</div>'
        . '<p class="lrb-src"><a href="' . $src . '" target="_blank" rel="noopener">' . $srcText . '</a></p>'
        . '</div></div>';
}

function lrbBody(string $style, string $lead, string $city, array $cards, string $closing, string $ctaUrl, string $ctaText): string {
    $tip = '<div class="lrb-note"><h3>編輯筆記｜挑' . $city . '裝潢細清看 5 點</h3><ol>'
        . '<li>先分需求：裝潢後細清／交屋驗收／退租／定期居家。</li>'
        . '<li>到府或視訊估價、報價透明（坪數 × 單價或包項）。</li>'
        . '<li>細節到不到位：踢腳板、矽利康縫、插座周圍、門框上方、燈溝窗軌、玻璃水漬。</li>'
        . '<li>看在地口碑與實際案例照。</li>'
        . '<li>是否含驗收複清（不滿意再補清）。</li></ol></div>';
    $cardsH = '';
    foreach ($cards as $c) $cardsH .= lrbCard($c);
    return $style
        . '<div class="lrb-g">'
        . '<p class="lrb-lead">' . $lead . '</p>'
        . '<div class="lrb-rule"><span>編輯精選 ・ 7 家</span></div>'
        . $tip
        . $cardsH
        . '<h2 class="lrb-sum">編輯小結｜怎麼挑最對味</h2>'
        . '<p class="lrb-closing">' . $closing . '</p>'
        . '<div class="lrb-cta"><p>想看更多' . $city . '在地裝潢細清／清潔商家、一次比較？</p>'
        . '<a href="' . $ctaUrl . '" target="_blank" rel="noopener">' . $ctaText . '</a></div>'
        . '</div>';
}

// ───────── 各城資料 ─────────
$guides = [];

// 台北（全品牌帶）
$guides[] = [
  'slug'=>'taipei-fine-clean-recommend', 'city_slug'=>'taipei', 'cityName'=>'台北',
  'title'=>'台北裝潢細清懶人包｜2026 嚴選 7 家在地口碑',
  'excerpt'=>'台北老公寓翻新、電梯大樓新成屋交屋後的細清怎麼選？店家好口碑精選 7 家台北在地口碑清潔團隊，從裝潢後細清、交屋驗收到退租一次看。',
  'meta_title'=>'台北裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑',
  'meta_desc'=>'台北裝潢細清推薦 2026！老公寓翻新、電梯大樓新成屋交屋後的細清這樣選——店家好口碑精選 7 家台北在地口碑清潔團隊，從裝潢後細清、交屋驗收到退租一次看。',
  'lead'=>'台北寸土寸金，老公寓翻新與電梯大樓新成屋交屋都需要專業「裝潢細清」——裝潢完的粉塵、矽利康殘膠、油漆點與保護膜殘膠，自己怎麼擦都擦不完。這次店家好口碑彙整台北在地口碑，精選 <strong>7 家清潔團隊</strong>，從裝潢後細清、交屋驗收到退租一次看完。',
  'closing'=>'想找台北老公寓退租或新成屋交屋細清，這 7 家各有專精。記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，台北找裝潢細清就不踩雷。',
  'cover'=>'', 'cta'=>'https://www.gomag.com.tw/city/taipei/home-service/cleaning', 'ctaText'=>'店家好口碑・台北清潔／裝潢細清專頁 →',
  'cards'=>[
    ['01','御用清潔公司','台北・萬華',['持證員工有保險','驗收後才收費','工業級設備'],'清潔員持專業訓練合格證明、本國全職員工有保險，主打「驗收後才收費」、工業級設備，品質有保障。',['<b>電話</b>　02-2309-8500','<b>專精</b>　裝潢細清・空屋・到府'],'https://sweet-clean.com.tw/','看御用清潔官網服務 →',null],
    ['02','文潔清潔公司','台北全區',['台北全區','裝潢後細清','服務面最廣'],'服務涵蓋南港、內湖、松山、信義、大安、士林、中山、文山、北投等台北全區（含汐止、基隆周邊），裝潢後細清服務範圍最廣。',['<b>電話</b>　0956-264-217','<b>專精</b>　裝潢後細清・全台北'],'https://www.wen-chieh.com/','看文潔清潔官網服務 →',null],
    ['03','清吉清潔公司','台北・中正/松山',['24h免費估價','透明報價','高 CP'],'24 小時免費估價、強調透明報價與高 CP 值，裝潢後清潔與居家清潔都接，預算有限想找實惠的可參考。',['<b>電話</b>　0919-776-026','<b>專精</b>　裝潢後清潔・居家'],'https://www.0919776026.com.tw/','看清吉清潔官網服務 →',null],
    ['04','家必潔專業清潔','台北・大台北',['裝潢細清','空屋','搬家一條龍'],'台北老牌專業清潔，裝潢細清＋空屋清潔＋搬家清潔一條龍，需求多元、想一個窗口搞定的人很適合。',['<b>電話</b>　02-2322-3336','<b>專精</b>　裝潢細清・空屋・搬家'],'https://shop.familyclean.com.tw/','看家必潔官網服務 →',null],
    ['05','家適美居家清潔','台北・南港',['專員到府估價','量身訂做','空屋細清'],'專員到府估價、依坪數與格局量身訂做方案，主打空屋與裝潢後清潔，2026 大台北推薦清潔品牌之一。',['<b>電話</b>　02-2653-9977','<b>專精</b>　空屋・裝潢後清潔'],'https://www.justclean.com.tw/service/10','看家適美官網服務 →',null],
    ['06','愛潔王居家清潔','大台北',['大安起家','居家+裝潢','平日服務'],'從大安區裝潢清潔起家，主打居家＋裝潢清潔，平日 09–18 服務，台北市與新北市都跑。',['<b>電話</b>　02-7729-3661','<b>專精</b>　裝潢清潔・居家'],'https://www.lovecleanking.com.tw/','看愛潔王官網服務 →',null],
    ['07','信義居家清潔','台北市',['信義房屋旗下','全室深度清潔','品牌保障'],'信義房屋旗下的居家清潔服務，全室深度清潔，適合入住前、遷出後、裝修後的完整清潔需求，品牌信任度高。',['<b>電話</b>　02-2722-3589','<b>專精</b>　裝修後・入住前・全室深清'],'https://livinglife.com.tw/service/cleaning-1p4h','看信義居家官網服務 →',null],
  ],
];

// 新北（全品牌帶）
$guides[] = [
  'slug'=>'newtaipei-fine-clean-recommend', 'city_slug'=>'newtaipei', 'cityName'=>'新北',
  'title'=>'新北裝潢細清懶人包｜2026 嚴選 7 家在地口碑',
  'excerpt'=>'新北重劃區新成屋交屋後的細清怎麼選？店家好口碑精選 7 家新北在地口碑清潔團隊，林口、板橋、三重、新莊、淡水都涵蓋，從裝潢後細清到退租一次看。',
  'meta_title'=>'新北裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑',
  'meta_desc'=>'新北裝潢細清推薦 2026！林口、板橋、三重、新莊副都心、淡水重劃區新成屋交屋後的細清這樣選——店家好口碑精選 7 家新北在地口碑清潔團隊，從裝潢後細清到退租一次看。',
  'lead'=>'新北重劃區建案多——林口、板橋、三重、新莊副都心、淡水交屋潮持續，新成屋交屋前的「裝潢後細清」做得好，入住才舒服。這次店家好口碑彙整新北在地口碑，精選 <strong>7 家清潔團隊</strong>，從裝潢後細清、交屋驗收到退租一次看完。',
  'closing'=>'新北重劃區交屋細清、老屋翻新與退租，這 7 家各有專精。記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，新北找裝潢細清就不踩雷。',
  'cover'=>'', 'cta'=>'https://www.gomag.com.tw/city/newtaipei/home-service/cleaning', 'ctaText'=>'店家好口碑・新北清潔／裝潢細清專頁 →',
  'cards'=>[
    ['01','永創清潔公司','新北・三重/新莊',['新裝潢全面清','天花板到地面','除粉塵油漬'],'主打新裝潢全面清，從天花板到地面徹底清除裝修粉塵、油漬與殘留，三重、蘆洲、淡水、新莊一帶服務。',['<b>電話</b>　0916-529-925','<b>專精</b>　裝潢後全面清・除粉塵'],'https://postclean.com.tw/','看永創清潔官網服務 →',null],
    ['02','立寶清潔工程','新北・板橋/中和',['大樓交屋清潔','一塵不染','品質保證'],'專營大樓交屋清潔與裝潢後細清，主打「一塵不染」高標準、有品質保證，板橋、土城、中和一帶。',['<b>電話</b>　0978-313-597','<b>專精</b>　交屋清潔・裝潢後細清'],'http://www.libau-clean.com.tw/','看立寶清潔官網服務 →',null],
    ['03','佐沐清潔管理','新北・林口/淡水',['數千場經驗','粉塵細部','可直接入住'],'累積數千場裝潢後細清經驗，擅長粉塵與細部、熟悉各式藥劑與建材，做到可直接入住，北部重劃區常見。',['<b>電話</b>　02-7753-8583','<b>專精</b>　裝潢後細清・粉塵細部'],'https://www.zomuclean.com/','看佐沐清潔官網服務 →',null],
    ['04','家事達家事服務','新北・三重',['15 年經驗','油漆水痕殘膠','拆玻璃系統櫃'],'15 年以上經驗，專清裝潢後油漆、水痕、殘膠，連拆玻璃、系統櫃、燈飾與冷氣都處理，老資歷團隊。',['<b>電話</b>　02-8972-1158','<b>專精</b>　裝潢後細清・殘膠水痕'],'https://jaster.tw/','看家事達官網服務 →',null],
    ['05','鷹翰裝潢細清','新北・中和/板橋',['豪宅細清','ESG 環保清潔劑','保固驗收'],'主攻裝潢細清與豪宅細清，使用 ESG 環保清潔劑、附保固與驗收服務，講究品質的屋主可參考。',['<b>LINE</b>　@323ukvry','<b>專精</b>　裝潢細清・豪宅細清'],'https://www.yinghanclean.com/','看鷹翰裝潢細清官網 →',null],
    ['06','愛潔淨居家清潔','新北・板橋/中和',['粉塵木屑','殘漆殘膠','全屋細清'],'專業細清因裝修引起的粉塵、木屑、殘漆殘膠與水泥沙，一次全屋細清到位，板橋、中和、三重、新莊等多區服務。',['<b>電話</b>　02-2302-1312','<b>專精</b>　裝潢後細清・全屋到位'],'https://loveclean.com.tw/page/product/show.aspx?num=74','看愛潔淨官網服務 →',null],
    ['07','康庭家事管理','新北・板橋',['成立十年','特殊吸塵器','新屋粉塵'],'成立十年的家事管理品牌，用特殊吸塵器解決新屋落成後的粉塵問題，裝潢後細清與居家家事一站包辦。',['<b>電話</b>　02-2963-0160','<b>專精</b>　裝潢後細清・家事管理'],'https://www.kt-house.com.tw/','看康庭家事官網服務 →',null],
  ],
];

// 桃園（沐悅/紅龍/鑫億 有圖）
$guides[] = [
  'slug'=>'taoyuan-fine-clean-recommend', 'city_slug'=>'taoyuan', 'cityName'=>'桃園',
  'title'=>'桃園裝潢細清懶人包｜2026 嚴選 7 家在地口碑',
  'excerpt'=>'桃園青埔、藝文特區、中壢新成屋交屋後的細清怎麼選？店家好口碑精選 7 家桃園在地口碑清潔團隊，從裝潢後細清、交屋驗收到退租一次看。',
  'meta_title'=>'桃園裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑',
  'meta_desc'=>'桃園裝潢細清推薦 2026！青埔高鐵特區、藝文特區、中壢新成屋交屋後的細清這樣選——店家好口碑精選 7 家桃園在地口碑清潔團隊，從裝潢後細清、交屋驗收到退租一次看。',
  'lead'=>'桃園是雙北移居族首選，青埔高鐵特區、藝文特區、中壢建案密集，新成屋交屋潮大。裝潢完的粉塵、殘膠、油漆點得靠專業「裝潢細清」才能入住。這次店家好口碑彙整桃園在地口碑，精選 <strong>7 家清潔團隊</strong>，從裝潢後細清、交屋驗收到退租一次看完。',
  'closing'=>'桃園青埔與重劃區新成屋交屋細清、移居族入厝，這 7 家各有專精。記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，桃園找裝潢細清就不踩雷。',
  'cover'=>'https://www.gomag.com.tw/uploads/guides/taoyuan-fine-clean/1-muyue.jpg',
  'cta'=>'https://www.gomag.com.tw/city/taoyuan/home-service/cleaning', 'ctaText'=>'店家好口碑・桃園清潔／裝潢細清專頁 →',
  'cards'=>[
    ['01','沐悅專業細緻清潔','桃園・中壢',['粗清+細清','入住前落塵','成果可見'],'主打裝潢粗清＋細清＋入住前落塵清潔，標榜成果看得見、價格實在，桃園、中壢一帶。',['<b>電話</b>　0987-323-611','<b>專精</b>　裝潢粗細清・入住前落塵'],'https://www.muyueclean.com/','看沐悅清潔官網服務 →', $IMG.'taoyuan-fine-clean/1-muyue.jpg'],
    ['02','紅龍清潔公司','桃園・蘆竹',['粗清細清','入住退租','空屋交屋一條龍'],'裝潢粗清／細清、入住與退租清潔、空屋交屋清潔一條龍，蘆竹與大桃園地區服務。',['<b>電話</b>　0936-588-923','<b>專精</b>　裝潢細清・交屋退租'],'https://www.hongrong88.com/','看紅龍清潔官網服務 →', $IMG.'taoyuan-fine-clean/2-hongrong.jpg'],
    ['03','潔暘國際','桃園・全區',['交屋清潔','公司化團隊','全桃園'],'交屋清潔＋裝潢細清，公司化團隊、服務區涵蓋全桃園（桃園、龜山、中壢、平鎮、八德、蘆竹、大園）。',['<b>電話</b>　03-377-8558','<b>專精</b>　交屋清潔・裝潢細清'],'https://www.jieyangclean.com/','看潔暘國際官網服務 →',null],
    ['04','廖媽媽清潔企業社','桃園・楊梅/中壢',['裝潢後細清','空屋','精緻交屋'],'裝潢後細清＋空屋打掃＋精緻交屋清潔，南桃園在地老牌，楊梅、中壢、平鎮、龍潭一帶。',['<b>電話</b>　0937-802-076','<b>專精</b>　裝潢後細清・交屋'],'https://www.0937802076.com.tw/about-us.html','看廖媽媽清潔官網服務 →',null],
    ['05','鑫億專業清潔公司','桃園・中壢/平鎮',['樣品屋細清','裝潢細清','退租空屋'],'樣品屋細清＋裝潢細清＋退租／空屋清潔，有建案樣品屋清潔經驗，桃園、中壢、平鎮、龜山一帶。',['<b>電話</b>　0979-350-923','<b>專精</b>　樣品屋・裝潢細清'],'https://www.majorxinyi.com/','看鑫億清潔官網服務 →', $IMG.'taoyuan-fine-clean/5-xinyi.jpg'],
    ['06','佳世潔清潔公司','桃園・青埔/八德',['裝潢細清','空屋','涵蓋青埔特區'],'裝潢細清＋空屋清潔＋大掃除，服務明確涵蓋青埔特區，桃園、中壢、八德、龜山都跑。',['<b>電話</b>　0907-496-330','<b>專精</b>　裝潢細清・青埔空屋'],'https://www.just-clean.com.tw/','看佳世潔清潔官網服務 →',null],
    ['07','WeClean 威清','桃園・全區',['新成屋專精','櫃內軌道踢腳板','建築粉塵'],'新成屋裝潢細清專精，櫃內、抽屜軌道、踢腳板、窗溝的建築粉塵都顧到，桃園設有實體分公司。',['<b>電話</b>　03-2717-712','<b>專精</b>　新成屋細清・細部工法'],'https://weclean.com.tw/news/display/177','看 WeClean 威清官網服務 →',null],
  ],
];

// 新竹（傑淨/愛幫忙/愛家 有圖）
$guides[] = [
  'slug'=>'hsinchu-fine-clean-recommend', 'city_slug'=>'hsinchu', 'cityName'=>'新竹',
  'title'=>'新竹裝潢細清懶人包｜2026 嚴選 7 家在地口碑',
  'excerpt'=>'新竹竹北、關埔重劃區新成屋交屋後的細清怎麼選？店家好口碑精選 7 家新竹在地口碑清潔團隊，竹科雙薪族友善，從裝潢後細清到定期居家一次看。',
  'meta_title'=>'新竹裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑',
  'meta_desc'=>'新竹裝潢細清推薦 2026！竹北高鐵特區、關埔重劃區新成屋交屋後的細清這樣選——店家好口碑精選 7 家新竹在地口碑清潔團隊，竹科雙薪族友善，從裝潢後細清到定期居家一次看。',
  'lead'=>'新竹竹科聚集大量科技人，竹北高鐵特區、關埔重劃區新成屋一棟接一棟，加上雙薪家庭忙碌，「裝潢後細清」與「定期清潔」需求都很高。這次店家好口碑彙整新竹在地口碑，精選 <strong>7 家清潔團隊</strong>，從裝潢後細清、交屋驗收到定期居家一次看完。',
  'closing'=>'竹北與關埔重劃區新成屋交屋細清、竹科雙薪族的定期清潔，這 7 家各有專精。記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，新竹找裝潢細清就不踩雷。',
  'cover'=>'https://www.gomag.com.tw/uploads/guides/hsinchu-fine-clean/3-help.jpg',
  'cta'=>'https://www.gomag.com.tw/city/hsinchu/home-service/cleaning', 'ctaText'=>'店家好口碑・新竹清潔／裝潢細清專頁 →',
  'cards'=>[
    ['01','清潔醫生','新竹・北區/竹北',['細清三合一','估價可折抵','空屋'],'裝潢後細清＋新屋入住＋空屋清潔三合一，現場估價費可折抵清潔費，新竹市北區與竹北一帶。',['<b>電話</b>　03-527-7733','<b>專精</b>　裝潢後細清・新屋入住'],'https://www.dr717.com/','看清潔醫生官網服務 →',null],
    ['02','傑淨清潔公司','新竹・新豐',['3 年經驗','交屋死角','施工品質佳'],'團隊 3 年以上經驗，主打交屋後細節死角清得乾淨、施工品質佳，新竹（新豐）、苗栗、桃園都跑。',['<b>電話</b>　0928-668-290','<b>專精</b>　交屋細清・死角清潔'],'https://jiehjing.com.tw/','看傑淨清潔官網服務 →', $IMG.'hsinchu-fine-clean/2-jiehjing.jpg'],
    ['03','愛幫忙清潔公司','新竹・竹北',['新成屋整修後','去殘膠','環保清潔劑'],'新成屋／整修後全面清，牆面、地板、窗戶、櫥櫃、燈具去殘膠，採環保清潔劑，竹北為主、大新竹服務。',['<b>電話</b>　0963-914-913','<b>專精</b>　新成屋細清・去殘膠'],'https://www.0963914913.com.tw/','看愛幫忙清潔官網服務 →', $IMG.'hsinchu-fine-clean/3-help.jpg'],
    ['04','立捷環保清潔','新竹・竹北/湖口',['大新竹 13 鄉鎮','專家把關','效率品質'],'裝潢交屋細清由專家親自把關，效率與品質並重，服務涵蓋竹北、湖口、新豐、竹東等大新竹 13 鄉鎮。',['<b>電話</b>　0926-440-220','<b>專精</b>　裝潢交屋細清'],'https://www.houseclean.com.tw/index.html','看立捷清潔官網服務 →',null],
    ['05','愛家家事服務','新竹・竹北',['粉塵殘膠','即刻入住','PTT 推薦'],'裝潢細清專清粉塵與殘膠、讓屋主即刻入住，PTT 推薦、免費現場報價，竹北與新竹市縣服務。',['<b>電話</b>　03-667-0165','<b>專精</b>　裝潢細清・即刻入住'],'https://www.lovehome.mymailer.com.tw/','看愛家家事官網服務 →', $IMG.'hsinchu-fine-clean/5-lovehome.jpg'],
    ['06','均禾 House Clean','新竹縣市・苗栗',['洗地機作業','竹科友善','定價透明'],'洗地機作業＋裝潢細清，竹科雙薪族友善，單次／定期方案明碼標價，新竹縣市與苗栗服務（以 FB 預約為主）。',['<b>電話</b>　0908-977-080','<b>專精</b>　裝潢細清・洗地機'],'https://www.facebook.com/jiunheclearn/','看均禾 House Clean FB →',null],
    ['07','管家太太','大新竹・竹北',['裝潢清潔','空屋','冷氣水塔消毒'],'裝潢清潔＋空屋清潔＋冷氣水塔消毒，全方位家事一站包辦，大新竹、新竹市縣與竹北服務。',['<b>電話</b>　03-558-4588','<b>專精</b>　裝潢清潔・空屋・專項'],'http://www.eputis.com/','看管家太太官網服務 →',null],
  ],
];

// ───────── upsert ─────────
$sql = "INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :cover, :city, :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            cover_image=VALUES(cover_image), city_slug=VALUES(city_slug), category_id=VALUES(category_id),
            meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
foreach ($guides as $g) {
    $body = lrbBody($STYLE, $g['lead'], $g['cityName'], $g['cards'], $g['closing'], $g['cta'], $g['ctaText']);
    $stmt->execute([':slug'=>$g['slug'], ':title'=>$g['title'], ':excerpt'=>$g['excerpt'], ':body'=>$body,
        ':cover'=>$g['cover'], ':city'=>$g['city_slug'], ':cat'=>$h, ':mt'=>$g['meta_title'], ':md'=>$g['meta_desc']]);
    printf("UPSERT %-32s body %d 字\n", $g['slug'], mb_strlen(strip_tags($body)));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
