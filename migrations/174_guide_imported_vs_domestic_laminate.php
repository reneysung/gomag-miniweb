<?php
// ============================================================
// Migration 174 — 新攻略②《進口 vs 國產超耐磨木地板、總代理 vs 平行輸入》
// ------------------------------------------------------------
// Gerpros 規劃文②。角度：產地/來源/代理制度辨識 → 與 #108(規格品質)、#98(店家清單)、#67(類型) 皆不同詞。
// 立場中性：產地不等於品質，重點是規格與文件；不貶低國產、不神化進口。
// cat=2 svc=flooring city=tainan。圖：客戶官網授權照(住宅實鋪＋歐洲板材設計)。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/174_guide_imported_vs_domestic_laminate.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$B = 'https://www.gomag.com.tw/uploads/guides/building-materials';

$slug='imported-vs-domestic-laminate';
$title='進口 vs 國產超耐磨木地板怎麼選？總代理與平行輸入差在哪';
$excerpt='同樣叫「超耐磨」，歐洲進口、土耳其、中國製、國內加工價差可以到兩三倍。差別在哪？「總代理」和「平行輸入」又差在哪？這篇拆解產地與來源怎麼影響品質、保固與補料，教你辨識正品、不被「歐洲進口」四個字帶著走。';
$metaTitle='進口 vs 國產超耐磨木地板｜總代理與平行輸入差在哪、怎麼辨識';
$metaDesc='超耐磨木地板產地怎麼看？歐洲、土耳其、中國製與國內加工差在哪、為什麼進口較貴、總代理與平行輸入（水貨）在保固補料上的差異，以及怎麼從包裝批號與原廠文件辨識正品。';
$cover=$B.'/flooring-imported-living.jpg';

$body = <<<HTML
<style>
.g-guide-body figure{margin:2.2rem 0 2.8rem;}
.g-guide-body figure img{border-radius:12px;display:block;width:100%;}
.g-guide-body figcaption{margin-top:12px;padding:0 4px;font-size:.9rem;line-height:1.75;color:#8a8a8a;text-align:center;}
</style>
<p class="gg-lead">逛地板門市時，「歐洲進口」四個字幾乎每家都會講。但同樣掛著超耐磨，價格可以從一坪兩千多到六七千，差距不小。這中間有兩件事最常被混在一起——<strong>產地</strong>（板在哪裡做的）和<strong>來源</strong>（你是跟總代理買，還是平行輸入的貨）。兩件事影響的東西不一樣，也都不等於「品質好壞」，但都會影響你日後的保固與補料。</p>

<h2>產地：歐洲、土耳其、中國製差在哪</h2>
<p>超耐磨木地板是全球化生產的建材，主要產地大致分成幾塊：</p>
<div class="gg-cards">
  <div class="gg-card"><b>歐洲（德、奧、波蘭等）</b><span>老牌大廠集中地，長期依 EN 系列標準生產，花色與壓紋開發成熟。價格最高，但規格文件通常最完整。</span></div>
  <div class="gg-card"><b>土耳其</b><span>近年在超耐磨地板產能上成長快，走高 CP 值路線，不少品牌也依歐規檢測。</span></div>
  <div class="gg-card"><b>中國／東南亞</b><span>價格帶最廣、選擇多，品質落差也最大——同產地可以有很好的，也有很差的，關鍵在品牌與檢測。</span></div>
  <div class="gg-card"><b>國內加工／代理組裝</b><span>部分產品在海外製基材、國內加工或分裝。要問清楚「哪一段在哪裡做」，別把加工地當製造地。</span></div>
</div>
<div class="gg-warn"><b>⚠️ 產地不等於品質</b><p>「歐洲製」不保證一定好，「中國製」也不代表一定差——真正能比的是<strong>規格與檢測</strong>：AC 耐磨等級、甲醛等級、基材密度、吸水膨脹率。產地只是參考，看不到規格表就只是行銷話術。</p></div>

<figure><img src="{$B}/flooring-imported-living.jpg" alt="進口超耐磨木地板鋪設於台灣住宅客廳的實際效果" loading="lazy"><figcaption>同一個價格帶裡，紋理擬真度與壓紋質感是進口板常見的差異點。</figcaption></figure>

<h2>為什麼進口的比較貴</h2>
<p>價差主要來自幾塊：<strong>原料與製程標準</strong>（基材密度、耐磨層配方）、<strong>花色與壓紋開發</strong>（歐洲大廠每年推新色、同步壓紋成本高）、<strong>運費與關稅</strong>，以及<strong>庫存成本</strong>——代理商要壓貨才能保證補料補得到。所以進口板貴的部分，不只是「板本身」，也包含後面那條供應鏈。</p>

<h2>總代理 vs 平行輸入：影響的是售後</h2>
<p>這是很多人下訂後才發現的差別。同一個品牌，可以透過原廠授權的<strong>總代理／經銷體系</strong>進來，也可能是貿易商自行採購的<strong>平行輸入（俗稱水貨）</strong>。板可能一樣，但你拿到的服務不一樣：</p>
<table class="gg-table">
  <thead><tr><th>比較項目</th><th>原廠總代理／授權經銷</th><th>平行輸入（水貨）</th></tr></thead>
  <tbody>
    <tr><td>原廠文件</td><td>提供型錄、規格與檢測資料</td><td>不一定拿得到</td></tr>
    <tr><td>保固</td><td>有明確保固窗口與範圍</td><td>常只有店家自行認定</td></tr>
    <tr><td>補料</td><td>同批號／同花色較易調到</td><td>賣完可能就沒了</td></tr>
    <tr><td>花色供應</td><td>完整系列、可查型號</td><td>看採購批次，零散</td></tr>
    <tr><td>價格</td><td>較高</td><td>可能較低</td></tr>
  </tbody>
</table>
<p>平行輸入不等於假貨，也不一定不好；但如果你在意<strong>日後單片損壞要補、或想加鋪同款</strong>，代理體系的供應穩定度差別會很明顯。</p>

<figure><img src="{$B}/flooring-euro-design.jpg" alt="歐洲板材品牌的設計應用情境展示" loading="lazy"><figcaption>歐洲大廠每年投入花色與壓紋開發，這也是進口板價格結構的一部分。</figcaption></figure>

<h2>怎麼辨識你買到的是什麼</h2>
<ol>
  <li><strong>問品牌與型號，不是只問「哪裡進口」</strong>：正規品牌有明確系列名與花色編號，可以查得到。</li>
  <li><strong>看整箱包裝</strong>：品牌、產地、批號、規格標示都印在箱上，開箱時看得到。</li>
  <li><strong>要原廠型錄與規格表</strong>：AC 等級、甲醛等級、尺寸與適用標準，正規品牌都寫得出來。</li>
  <li><strong>問代理身分</strong>：是原廠授權代理／經銷，還是自行採購？請對方說明保固由誰負責。</li>
  <li><strong>保固白紙黑字</strong>：保幾年、保什麼、由誰執行，寫進報價單或合約。</li>
</ol>

<div class="gg-tip"><b>💡 台南看板小提醒</b><p>台南的建材門市與展間多集中在市區與交流道周邊。同一款板在不同門市可能報價不同——先確認是<strong>同品牌、同系列、同型號</strong>再比價，不然是在比不同東西。另外，看實體大板時順便問一句「這批補料補得到嗎」，通常最能問出貨源狀況。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">歐洲進口的超耐磨木地板一定比較好嗎？</div>
  <div class="gg-a">不一定。歐洲大廠在標準與花色開發上通常較成熟，但真正能比的是規格：AC 耐磨等級、甲醛等級、基材密度、吸水膨脹率。看不到規格表，產地只是行銷用語。</div>
  <div class="gg-q">總代理和平行輸入（水貨）差在哪？</div>
  <div class="gg-a">板可能相同，差別在售後：原廠文件、保固窗口、補料與花色供應的穩定度。平行輸入不等於假貨，但日後要補料或加鋪同款時，代理體系通常比較有保障。</div>
  <div class="gg-q">為什麼同樣「超耐磨」價差這麼大？</div>
  <div class="gg-a">差在耐磨層與基材規格、花色與壓紋開發成本、運費關稅，以及代理商為了保證補料而壓的庫存成本。價格高低要對照規格才有意義。</div>
  <div class="gg-q">怎麼確認買到的是原廠正品？</div>
  <div class="gg-a">問明品牌、系列與花色型號；看整箱包裝上的品牌、產地與批號；索取原廠型錄與規格表；確認代理身分與保固由誰負責，並寫進合約。</div>
  <div class="gg-q">國產或中國製的就不能買嗎？</div>
  <div class="gg-a">可以買，重點一樣是規格與檢測文件是否齊全、保固與補料是否有著落。預算有限時，與其追產地，不如把 AC 等級與甲醛等級顧好。</div>
  <div class="gg-q">日後單片壞掉補得到嗎？</div>
  <div class="gg-a">看貨源。代理體系同系列多半調得到同花色（批號可能略有色差）；零散採購的貨賣完就沒了。下訂時可以直接問補料政策，必要時多留幾片備品。</div>
</div>

<p>想先搞懂規格怎麼看，接著讀 <a href="https://www.gomag.com.tw/guide/laminate-flooring-quality-guide">超耐磨木地板怎麼看品質（AC 等級與甲醛）</a>；想比較不同地板類型與行情，看 <a href="https://www.gomag.com.tw/guide/tainan-flooring-recommend">台南木地板推薦與行情</a>。更多台南在地建材與地板商家見 <a href="https://www.gomag.com.tw/city/tainan/home-service/flooring">台南木地板</a> 專頁。</p>
HTML;

$faqs=[
 ['歐洲進口的超耐磨木地板一定比較好嗎？','不一定。歐洲大廠在標準與花色開發上通常較成熟，但真正能比的是規格：AC 耐磨等級、甲醛等級、基材密度、吸水膨脹率。看不到規格表，產地只是行銷用語。'],
 ['總代理和平行輸入（水貨）差在哪？','板可能相同，差別在售後：原廠文件、保固窗口、補料與花色供應的穩定度。平行輸入不等於假貨，但日後補料或加鋪同款時，代理體系通常比較有保障。'],
 ['為什麼同樣「超耐磨」價差這麼大？','差在耐磨層與基材規格、花色與壓紋開發成本、運費關稅，以及代理商為保證補料而壓的庫存成本。價格高低要對照規格才有意義。'],
 ['怎麼確認買到的是原廠正品？','問明品牌、系列與花色型號；看整箱包裝上的品牌、產地與批號；索取原廠型錄與規格表；確認代理身分與保固由誰負責，並寫進合約。'],
 ['國產或中國製的就不能買嗎？','可以買，重點一樣是規格與檢測文件是否齊全、保固與補料是否有著落。預算有限時，與其追產地，不如把 AC 等級與甲醛等級顧好。'],
 ['日後單片壞掉補得到嗎？','看貨源。代理體系同系列多半調得到同花色（批號可能略有色差）；零散採購的貨賣完就沒了。下訂時直接問補料政策，必要時多留幾片備品。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'tainan', 2, 'flooring', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=2,service_slug='flooring',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),cover_image=VALUES(cover_image),status='published'")
  ->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ ② #{$r['id']} {$slug} body={$r['bl']}B 約".mb_strlen(strip_tags($body))."字 圖=".substr_count($body,'<img')." FAQ=".count($faqs)."\n";
