<?php
// ============================================================
// Migration 155 — 攻略《高雄人為什麼都裝 AquaLeader 淨水器》(cluster 第 3 篇，水領導角度)
// ------------------------------------------------------------
// 水領導 = 高雄自有品牌 AquaLeader，主打「通規濾芯打破綁機賣濾心」。教育文角度：
// 綁機賣濾心痛點 → AquaLeader 怎麼不一樣 → 機型一覽。用水領導 /store 產品照片；
// 依 Reney 設計此篇點名品牌 AquaLeader，但**不放聯絡資訊、不連客戶**。
// service_slug=jingshuiqi、city=kaohsiung。內容依官網事實（通規濾芯/台灣製/認證/機型）。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/155_guide_kaohsiung_aqualeader.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$IMG = 'https://www.gomag.com.tw/uploads/guides/kaohsiung-aqualeader';

$slug = 'kaohsiung-aqualeader-water-purifier';
$title = '高雄人為什麼開始選 AquaLeader（水領導）淨水器？從「綁機賣濾心」看起';
$excerpt = '買淨水器最怕被「綁機賣濾心」——機器便宜、濾心規格特殊只能跟原廠買。高雄品牌 AquaLeader 用國際通規濾芯打破這件事。這篇講綁機為什麼要避、AquaLeader 怎麼不一樣、有哪些機型。';
$metaTitle = '高雄 AquaLeader 水領導淨水器：通規濾芯不綁機、台灣製、機型一覽｜店家好口碑';
$metaDesc = '淨水器最怕綁機賣濾心。高雄品牌 AquaLeader（水領導）用國際通規（10"/20"）濾芯、台灣研發台灣製、NSF/SGS 認證，全戶淨軟水到 RO 機型一次看。';
$cover = $IMG . '/al-fullset.jpg';

$body = <<<HTML
<p class="gg-lead">買淨水器，最容易踩的坑是「綁機賣濾心」——機器賣你便宜，但濾心是特殊規格、只能跟原廠買，之後一綁綁一輩子。高雄的淨水品牌 AquaLeader（水領導）主打用國際通規濾芯打破這件事。這篇從「綁機」的痛點講起，說 AquaLeader 怎麼不一樣、有哪些機型。</p>

<figure><img src="{$IMG}/al-fullset.jpg" alt="AquaLeader 全戶淨軟水組" loading="lazy"><figcaption>從全戶淨軟水到廚下生飲，AquaLeader 走一站式規劃。</figcaption></figure>

<h2>「綁機賣濾心」是什麼、為什麼要避</h2>
<p>不少淨水器用特殊規格的濾心，裝了之後你只能跟原廠買濾心，價格自己說了算；哪天廠商收了、停產了，濾心就斷貨、機器變廢鐵。淨水器真正的長期成本其實在「濾心」，不在機器本身，所以濾心能不能自由選、好不好買，比機器便宜幾千塊重要得多。</p>

<h2>AquaLeader 怎麼不一樣</h2>
<div class="gg-cards">
  <div class="gg-card"><b>國際通規濾芯</b><span>走 10 吋、20 吋國際通用規格，不綁特殊規格，換濾心選擇多、成本可控。</span></div>
  <div class="gg-card"><b>多元濾芯可選</b><span>複合式、氫水、鹼性等多種濾芯，同一台機器依需求換不同濾材。</span></div>
  <div class="gg-card"><b>台灣研發、台灣製濾芯</b><span>100% 台灣在地研發團隊、與實驗室合作，濾芯台灣工廠生產。</span></div>
  <div class="gg-card"><b>工程級規格帶進家用</b><span>2006 年起做企業水處理工程起家，把工程級規格下放到家用市場。</span></div>
</div>
<figure><img src="{$IMG}/al-filter.jpg" alt="AquaLeader 國際通用規格碳纖維濾芯" loading="lazy"><figcaption>通規濾芯的重點：之後換濾心不被單一廠商綁住。</figcaption></figure>

<h2>AquaLeader 有哪些機型</h2>
<p>從全屋到單點、從用水到生飲都有，可依需求組合。</p>
<div class="gg-cards">
  <div class="gg-card"><b>全戶淨軟水組</b><span>鋼盾、白騎士、黑武士等系列，處理全家用水的雜質與水垢。</span></div>
  <div class="gg-card"><b>窄身旗艦軟水機</b><span>SPW-L／SPW-S，機身窄、適合空間有限的高雄大樓與透天。</span></div>
  <div class="gg-card"><b>無桶直出 RO</b><span>GK600 這類 RO 純水機，做飲用、煮飯的水。</span></div>
  <div class="gg-card"><b>智能櫥下雙溫飲水機</b><span>WS901，冷熱一機，廚下省空間。</span></div>
  <div class="gg-card"><b>大流量碳纖淨水器</b><span>High Flow 系列，單點大流量過濾。</span></div>
</div>
<figure><img src="{$IMG}/al-softener.jpg" alt="AquaLeader 窄身旗艦軟水機" loading="lazy"><figcaption>窄身軟水機讓空間有限的住家也能裝全戶軟水。</figcaption></figure>

<h2>國際認證</h2>
<p>濾芯與設備通過美國國家衛生基金會（NSF）、SGS 台灣檢驗、美國水質協會（WQA）、歐洲合格認證（CE）等驗證，這些是挑淨水器時判斷品質的參考。</p>
<figure><img src="{$IMG}/al-ro.jpg" alt="AquaLeader 無桶直出 RO 淨水器" loading="lazy"><figcaption>飲用水走 RO，全戶用水走淨軟水，兩者分開規劃。</figcaption></figure>

<h2>挑淨水器，除了牌子還要看什麼</h2>
<ol>
  <li><strong>濾心是不是通規、好不好買</strong>：這決定你長期的濾心成本與斷貨風險。</li>
  <li><strong>濾心成本</strong>：算的是幾年下來的濾心錢，不是只看機器價格。</li>
  <li><strong>認證</strong>：NSF、SGS、WQA 等第三方認證。</li>
  <li><strong>對需求</strong>：飲用找 RO、全家用水找全戶淨軟水，別買錯類型。</li>
  <li><strong>安裝與售後</strong>：屋型能不能裝、後續換濾心與保養方不方便。</li>
</ol>

<div class="gg-tip"><b>💡 想避免被綁濾心</b><p>選「國際通用規格（10"/20"）」濾心的機型，之後換濾心選擇多、比較不會被單一廠商綁住，長期成本也好控制。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">什麼是「綁機賣濾心」？為什麼要避？</div>
  <div class="gg-a">用特殊規格濾心、只能跟原廠買，價格被綁、廠商收了就斷貨。淨水器長期成本在濾心，濾心能不能自由選比機器便宜更重要。</div>
  <div class="gg-q">通規濾芯是什麼？</div>
  <div class="gg-a">10 吋、20 吋這類國際通用規格的濾心，市面上多家都有生產，換濾心選擇多、不被單一廠商綁住，成本比較可控。</div>
  <div class="gg-q">AquaLeader 是台灣品牌嗎？</div>
  <div class="gg-a">是。AquaLeader（水領導）是高雄的自有品牌，100% 台灣在地研發、濾芯台灣工廠生產，做企業水處理工程起家。</div>
  <div class="gg-q">AquaLeader 有哪些認證？</div>
  <div class="gg-a">濾芯與設備通過 NSF、SGS 台灣檢驗、WQA、CE 等驗證。</div>
  <div class="gg-q">有哪些機型可選？</div>
  <div class="gg-a">全戶淨軟水組、窄身軟水機、無桶直出 RO、智能櫥下雙溫飲水機、大流量碳纖淨水器等，從全屋到單點、用水到生飲都有。</div>
  <div class="gg-q">濾心多久換一次？</div>
  <div class="gg-a">看水質與使用量、濾材種類而定，通用規格濾心的好處是到期換的選擇多、成本好抓，依機型說明與水質定期更換。</div>
</div>

<p>更多高雄在地居家服務，可到 <a href="https://www.gomag.com.tw/city/kaohsiung/home-service">高雄居家服務</a> 總覽；全戶軟水與瑞士水的比較，另見 <a href="https://www.gomag.com.tw/guide/kaohsiung-whole-house-soft-water">為什麼高雄需要全戶軟水</a>、<a href="https://www.gomag.com.tw/guide/kaohsiung-swiss-water">高雄為什麼很多人改裝瑞士水</a>。</p>
HTML;

$faqs = [
 ['什麼是「綁機賣濾心」？為什麼要避？','用特殊規格濾心、只能跟原廠買，價格被綁、廠商收了就斷貨。淨水器長期成本在濾心，濾心能不能自由選比機器便宜更重要。'],
 ['通規濾芯是什麼？','10 吋、20 吋國際通用規格的濾心，市面多家生產、換濾心選擇多、不被單一廠商綁住。'],
 ['AquaLeader 是台灣品牌嗎？','是。AquaLeader（水領導）是高雄自有品牌，100% 台灣研發、濾芯台灣工廠生產，做企業水處理工程起家。'],
 ['AquaLeader 有哪些認證？','濾芯與設備通過 NSF、SGS 台灣檢驗、WQA、CE 等驗證。'],
 ['有哪些機型可選？','全戶淨軟水組、窄身軟水機、無桶直出 RO、智能櫥下雙溫飲水機、大流量碳纖淨水器等。'],
 ['濾心多久換一次？','看水質、使用量與濾材種類，通用規格濾心到期換的選擇多、成本好抓，依機型說明定期更換。'],
];
$faqLd = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f) $faqLd['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($faqLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$g = $db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
    VALUES (?,?,?,?, 'kaohsiung', 2, 'jingshuiqi', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),service_slug=VALUES(service_slug),
      meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),cover_image=VALUES(cover_image),status='published'");
$g->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug} body={$r['bl']}B gg-=".substr_count($body,'gg-')." 圖=".substr_count($body,'<img')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
