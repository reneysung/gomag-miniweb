<?php
// ============================================================
// Migration 176 — 啟用「台南系統廚具」落地頁 + 新攻略④《進口德國廚具 vs 台製系統廚具》
// ------------------------------------------------------------
// 台南 kitchen geo 原本 active=0（草稿）→ 補內容並啟用。Gerpros 已標 kitchen/chuju。
// ⚠️ 目前掛牌店家少，Reney 會再補廚具客戶；先以內容撐頁。
// 攻略④角度：進口/台製的製造地、五金、模組、交期、售後 → 消費者辨識為主，不吹捧單一來源。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/176_tainan_kitchen_page_and_guide.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$B = 'https://www.gomag.com.tw/uploads/guides/building-materials';

// ── 1. 啟用台南系統廚具 geo ──
$intro = <<<'H'
<p>台南換廚具，選擇大致分成兩條路：<strong>進口品牌廚具</strong>（德國、義大利等，門板與櫃體多為原廠模組）與<strong>台製系統廚具</strong>（在地工廠依現場尺寸客製）。兩者在尺寸彈性、交期、五金配置與售後上差很多，沒有絕對誰好，要看你的廚房條件與預算。</p>
<p>挑台南廚具看幾件事：①<strong>櫃體板材與門板材質</strong>（含甲醛等級、防潮處理）；②<strong>五金品牌</strong>（鉸鏈、滑軌、緩衝，直接影響手感壽命）；③<strong>檯面材質</strong>（人造石、石英石、不鏽鋼各有優缺）；④<strong>報價含哪些</strong>（檯面、水槽、龍頭、電器、拆除是否另計）；⑤<strong>交期與售後</strong>（進口需海運、台製通常較快，維修零件取得也不同）。建議先到有實體門板與五金樣品的展間，實際開闔看看再決定。</p>
H;
$faqs = json_encode([
 ['q'=>'台南廚具大概多少錢？','a'=>'依櫃體長度、門板材質、五金與檯面差異很大，通常以「延尺／公分」加項目計價，電器與水槽多半另計。比價前先確認報價含哪些項目，否則不同東西沒得比。'],
 ['q'=>'進口廚具和台製系統廚具差在哪？','a'=>'進口多為原廠模組尺寸、飾面與五金整合度高、交期較長、價格高；台製依現場尺寸客製、交期短、售後就近、價格較親民。看廚房格局與預算決定。'],
 ['q'=>'廚具檯面要選哪一種？','a'=>'人造石接縫細緻好清但怕高溫；石英石硬度高耐刮、價格較高；不鏽鋼耐熱耐用、偏工業感。依使用習慣與預算選。'],
 ['q'=>'舊廚具拆除有含在報價裡嗎？','a'=>'不一定。拆除、廢棄物清運、水電移位、泥作補強常是另計項目，下訂前要逐項確認並寫進報價單。'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active, created_at)
    VALUES ('tainan', 2, 'kitchen', '系統廚具', ?, ?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE service_name='系統廚具', intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1")
  ->execute([$intro,$faqs,'台南系統廚具推薦｜進口與台製怎麼選、五金檯面重點｜店家好口碑','台南系統廚具怎麼挑？進口品牌與台製系統廚具差在哪、櫃體板材與甲醛、五金品牌、檯面材質怎麼選、報價含哪些項目，台南在地廚具與建材商家一次看。']);
$g=$db->query("SELECT id,is_active FROM geo_category_pages WHERE city_slug='tainan' AND category_id=2 AND service_slug='kitchen'")->fetch(PDO::FETCH_ASSOC);
echo "1) 台南系統廚具 geo #{$g['id']} active={$g['is_active']}\n";

// ── 2. 攻略④ ──
$slug='imported-vs-domestic-kitchen';
$title='進口德國廚具 vs 台製系統廚具怎麼選？五金、模組、交期差在哪';
$excerpt='「德國廚具」聽起來就很厲害，但它跟台製系統廚具到底差在哪？這篇從製造地、模組尺寸、五金配置、檯面、交期到售後逐項比較，也教你問出「這台真的在哪裡做的」，依自己的廚房條件與預算選對。';
$metaTitle='進口德國廚具 vs 台製系統廚具｜五金、模組、交期與售後怎麼比';
$metaDesc='進口廚具和台製系統廚具差在哪？製造地怎麼問、模組尺寸與客製彈性、五金品牌、檯面材質、交期與售後維修的差異，以及報價要逐項列什麼，一篇看懂不被品牌名帶著走。';
$cover=$B.'/kitchen-system-cabinet.jpg';

$body = <<<HTML
<style>
.g-guide-body figure{margin:2.2rem 0 2.8rem;}
.g-guide-body figure img{border-radius:12px;display:block;width:100%;}
.g-guide-body figcaption{margin-top:12px;padding:0 4px;font-size:.9rem;line-height:1.75;color:#8a8a8a;text-align:center;}
</style>
<p class="gg-lead">換廚具時最常聽到的兩句話：「這是德國進口的」跟「我們可以完全客製」。前者講的是原廠模組與整合度，後者講的是現場配合度——兩種思路不同，適合的人也不同。這篇把進口廚具與台製系統廚具的差別攤開來比，順便教你問出最關鍵的一句：<strong>「這組櫃體，實際上是在哪裡做的？」</strong></p>

<h2>先分清楚：品牌國籍 ≠ 製造地</h2>
<p>「德國廚具」可能是德國原廠生產整組進口，也可能是德國品牌授權、在其他地方製造，或門板進口、櫃體在地組裝。這幾種在價格與售後上差很多，但講出來都叫「德國廚具」。</p>
<div class="gg-warn"><b>⚠️ 一定要問的三個問題</b><p>①<strong>櫃體與門板分別在哪裡製造？</strong>②<strong>五金是哪個品牌型號？</strong>③<strong>維修零件多久調得到？</strong>——這三題問完，你大概就知道自己買的是什麼等級的東西了。</p></div>

<figure><img src="{$B}/kitchen-system-cabinet.jpg" alt="住宅系統廚具與收納櫃體的整體規劃實景" loading="lazy"><figcaption>廚具要連同櫃體、檯面與五金一起看；完成後的耐用度多半取決於看不見的部分。</figcaption></figure>

<h2>進口廚具 vs 台製系統廚具：逐項比較</h2>
<table class="gg-table">
  <thead><tr><th>比較項目</th><th>進口品牌廚具</th><th>台製系統廚具</th></tr></thead>
  <tbody>
    <tr><td>尺寸</td><td>原廠模組尺寸為主</td><td>依現場尺寸客製，彈性大</td></tr>
    <tr><td>飾面與設計</td><td>系列完整、整合度高</td><td>選擇多，視廠商而定</td></tr>
    <tr><td>五金</td><td>多為原廠配置的知名品牌</td><td>可指定、可依預算選配</td></tr>
    <tr><td>交期</td><td>較長（含海運與排程）</td><td>較短</td></tr>
    <tr><td>價格</td><td>較高</td><td>較親民</td></tr>
    <tr><td>售後維修</td><td>零件需調貨，時間較長</td><td>就近處理、較快</td></tr>
    <tr><td>適合</td><td>格局方正、預算充足、重整體設計</td><td>格局特殊、要客製、重交期與售後</td></tr>
  </tbody>
</table>
<p>台灣的廚房常有樑柱、管道間、非標準開間，這時<strong>客製彈性</strong>的價值會很明顯；反過來說，如果格局方正、又重視整體飾面與五金的一致性，進口模組的完成度確實較高。</p>

<h2>不管選哪種，這幾項都要問</h2>
<ol>
  <li><strong>櫃體板材與甲醛等級</strong>：廚房濕氣與油煙重，防潮型板材與封邊品質很關鍵；甲醛等級（F☆☆☆☆／E0／E1）要問清楚。</li>
  <li><strong>五金品牌與型號</strong>：鉸鏈、滑軌、緩衝，是每天開闔的東西，也是最容易被省的地方。</li>
  <li><strong>檯面材質</strong>：人造石接縫細緻好清但怕高溫；石英石耐刮硬度高、價格較高；不鏽鋼耐熱耐用。</li>
  <li><strong>報價含哪些</strong>：檯面、水槽、龍頭、電器、拆除、廢棄物清運、水電移位——這些常常另計。</li>
  <li><strong>交期與現場配合</strong>：從丈量、下單到安裝要多久，跟裝潢其他工種怎麼銜接。</li>
</ol>

<div class="gg-tip"><b>💡 台南換廚具的實務建議</b><p>老屋換廚具常牽動水電位置與排煙管路，建議在丈量階段就把「要不要移水槽／爐具」講清楚，避免後面追加。另外台南濕氣重、料理多用煎炒，<strong>檯面好清潔與櫃體防潮</strong>會比多一個造型更有感。決定前到展間實際開闔門片與抽屜、摸檯面接縫，感受差別最快。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">「德國廚具」就一定是德國製造嗎？</div>
  <div class="gg-a">不一定。可能是原廠整組進口，也可能是品牌授權在他地製造，或門板進口、櫃體在地組裝。要直接問櫃體與門板分別在哪裡製造，以及五金品牌型號。</div>
  <div class="gg-q">進口廚具和台製系統廚具差在哪？</div>
  <div class="gg-a">進口以原廠模組尺寸為主、飾面與五金整合度高、交期長、價格高；台製依現場客製、彈性大、交期短、售後就近、價格較親民。台灣廚房格局常不方正，客製彈性有時很關鍵。</div>
  <div class="gg-q">廚具檯面要選哪一種？</div>
  <div class="gg-a">人造石接縫細緻、好清潔但怕高溫；石英石硬度高、耐刮，價格較高；不鏽鋼耐熱耐用、偏工業感。依料理習慣與預算選。</div>
  <div class="gg-q">廚具的五金要不要指定？</div>
  <div class="gg-a">建議指定並寫進報價。鉸鏈、滑軌與緩衝每天使用，直接影響手感與壽命，也是報價最容易被簡化的部分。</div>
  <div class="gg-q">舊廚具拆除、水電移位有含嗎？</div>
  <div class="gg-a">通常另計。拆除、廢棄物清運、水電移位、泥作補強都要逐項確認並寫進報價單，避免施工中追加。</div>
  <div class="gg-q">進口廚具壞了維修方便嗎？</div>
  <div class="gg-a">零件多半要調貨，時間較長，取得管道也看代理是否穩定。下訂前問清楚維修窗口與常見零件的調貨時間。</div>
</div>

<p>想看台南在地的廚具與建材商家，可到 <a href="https://www.gomag.com.tw/city/tainan/home-service/kitchen"><strong>台南系統廚具</strong></a> 專頁；櫃體板材可延伸看 <a href="https://www.gomag.com.tw/guide/system-cabinet-board-guide">系統櫃板材怎麼挑</a>，整體裝潢規劃見 <a href="https://www.gomag.com.tw/guide/tainan-interior-design-recommend">台南室內設計推薦</a>。</p>
HTML;

$faqs2=[
 ['「德國廚具」就一定是德國製造嗎？','不一定。可能是原廠整組進口，也可能是品牌授權在他地製造，或門板進口、櫃體在地組裝。要直接問櫃體與門板分別在哪裡製造，以及五金品牌型號。'],
 ['進口廚具和台製系統廚具差在哪？','進口以原廠模組尺寸為主、整合度高、交期長、價格高；台製依現場客製、彈性大、交期短、售後就近、價格較親民。台灣廚房格局常不方正，客製彈性有時很關鍵。'],
 ['廚具檯面要選哪一種？','人造石接縫細緻好清但怕高溫；石英石硬度高耐刮、價格較高；不鏽鋼耐熱耐用、偏工業感。依料理習慣與預算選。'],
 ['廚具的五金要不要指定？','建議指定並寫進報價。鉸鏈、滑軌與緩衝每天使用，直接影響手感與壽命，也是報價最容易被簡化的部分。'],
 ['舊廚具拆除、水電移位有含嗎？','通常另計。拆除、廢棄物清運、水電移位、泥作補強都要逐項確認並寫進報價單。'],
 ['進口廚具壞了維修方便嗎？','零件多半要調貨，時間較長，也看代理是否穩定。下訂前問清楚維修窗口與常見零件調貨時間。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs2 as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'tainan', 2, 'kitchen', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=2,service_slug='kitchen',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),cover_image=VALUES(cover_image),status='published'")
  ->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "2) ④ #{$r['id']} {$slug} body={$r['bl']}B 約".mb_strlen(strip_tags($body))."字 圖=".substr_count($body,'<img')."\n";
echo "\n實頁： https://www.gomag.com.tw/city/tainan/home-service/kitchen\n";
