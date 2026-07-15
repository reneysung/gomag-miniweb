<?php
// ============================================================
// Migration 165 — 新攻略《台南木地板推薦》(照深度格式；知識型主題重內容)
// ------------------------------------------------------------
// Reney 指定置入 4 家(全真實高分)：昇揚#67(5.0/51)、安居#136(4.9/36)、快步#45(4.8/25)、亞拓#113(4.8/126)
//   亞拓誠實標「主打塑膠地板/窗簾」；文章定位「台南木地板/地板」涵蓋超耐磨/海島型/實木/SPC。
// category_id=2(居家)、service_slug='flooring'、cover flooring.svg。既有 #67 是全台通用指南(互補)。
// 4 家較少 → 用超厚「地板類型深度知識段」補足(知識型主題，本就該重內容)。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/165_guide_tainan_flooring_recommend.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug='tainan-flooring-recommend';
$title='台南木地板推薦 2026｜超耐磨、海島型、實木、SPC 怎麼選｜在地嚴選地板行家';
$excerpt='台南木地板怎麼挑？店家好口碑嚴選昇揚、安居、快步、亞拓等台南在地地板行家，一次搞懂超耐磨、海島型、實木、SPC 塑膠地板的差別、每坪行情、卡扣與平鋪工法、怎麼選不踩雷，附真實 Google 評價。';
$metaTitle='台南木地板推薦 2026｜超耐磨/海島型/實木/SPC 怎麼選｜行情懶人包';
$metaDesc='台南木地板推薦哪幾家？超耐磨、海島型、實木、SPC 塑膠地板差在哪、每坪行情、卡扣免膠 vs 平鋪、有小孩寵物怎麼選，東區、安平、仁德在地地板行家與真實 Google 評價一次看。';
$cover='https://www.gomag.com.tw/uploads/guides/covers/flooring.svg';

$body = <<<'HTML'
<style>
.g-guide-body .si{background:#f7f4f2;border-radius:10px;padding:10px 15px;margin:11px 0;font-size:.93rem;line-height:1.95;color:#555;}
.g-guide-body .si b{color:#1a1a1a;}
.g-guide-body .si a{color:#FF5A36;font-weight:700;text-decoration:none;}
.g-guide-body .gr{background:#fff;border:1px solid #eadfd9;border-left:3px solid #34A853;border-radius:10px;padding:11px 15px;margin:11px 0;}
.g-guide-body .gr .h{font-size:.88rem;font-weight:700;color:#555;margin-bottom:5px;}
.g-guide-body .gr .h .st{color:#FBBC04;letter-spacing:1px;}
.g-guide-body .gr blockquote{margin:0;font-size:.94rem;line-height:1.85;color:#333;}
.g-guide-body .gr cite{display:block;margin-top:6px;font-style:normal;font-size:.82rem;color:#888;}
</style>
<p class="gg-lead">裝潢時，木地板幾乎是每個家的必選，但一走進門市就被「超耐磨、海島型、實木、SPC」搞得頭很大——價差好幾倍，防水、耐刮、觸感也都不一樣，選錯不是踩到就是幾年後起翹。台南找木地板，門市集中在東區、安平、仁德一帶，這篇店家好口碑先幫你把<strong>四種地板</strong>徹底搞懂、附每坪行情與工法差別，再嚴選 <strong>4 家台南在地地板行家</strong>，帶你選對又選得安心。</p>
<p><em>※ 名單為店家好口碑收錄之台南在地商家，資料以各店公開資訊與 Google 商家為準，行情請以現場丈量估價為主。</em></p>

<h2 id="compare">先看懶人包｜4 家台南地板行家一次比較</h2>
<table class="gg-table">
<thead><tr><th>店家</th><th>區域</th><th>主打</th><th>Google</th></tr></thead>
<tbody>
<tr><td>昇揚木地板</td><td>東區東門路</td><td>超耐磨・海島型・實木</td><td>5.0（51）</td></tr>
<tr><td>安居木地板</td><td>東區東橋</td><td>超耐磨・SPC</td><td>4.9（36）</td></tr>
<tr><td>快步地板（富崧）</td><td>安平</td><td>QuickStep 卡扣免膠超耐磨</td><td>4.8（25）</td></tr>
<tr><td>亞拓</td><td>仁德</td><td>塑膠地板・窗簾（地板窗簾一起）</td><td>4.8（126）</td></tr>
</tbody>
</table>

<h2 id="know">先懂再挑：四種地板差在哪</h2>
<p>市面上講的「木地板」其實包含好幾種完全不同的材質，價格、防水、觸感差很多。搞懂下面四種，才知道自己家該用哪一種、報價合不合理。</p>
<div class="gg-cards">
  <div class="gg-card"><b>超耐磨木地板</b><span>其實是高壓木紋耐磨板（俗稱美耐板／KRONO 類）。表面耐磨、抗刮、好清潔、價格最親民，是台灣最主流的選擇。花色多、施工快，缺點是長期泡水會膨脹，不適合浴室陽台。</span></div>
  <div class="gg-card"><b>海島型木地板</b><span>上層真實木皮＋下層多層夾板。有實木的觸感與質感，又比實木耐變形，較適合台灣潮濕氣候。價格中間，是「想要實木感又怕變形」的折衷選擇。</span></div>
  <div class="gg-card"><b>實木地板</b><span>100% 原木，觸感溫潤、可整平重新打磨保養、越用越有味道。但最怕潮濕變形、需要保養、價格最高，適合預算充足、重視質感的人。</span></div>
  <div class="gg-card"><b>SPC 石塑地板（塑膠地板）</b><span>石粉＋PVC，100% 防水、耐刮、卡扣式好施工，浴室、廚房、陽台、租屋都能用。價格親民、CP 值高，缺點是觸感與質感不如木地板，屬另一種需求。</span></div>
</div>
<h3>卡扣免膠 vs 平鋪打膠</h3>
<p>施工工法主要兩種：<strong>卡扣式（浮動式、免膠）</strong>——地板一片片卡起來、不打膠，日後單片壞了好抽換、也好維修，QuickStep 這類品牌就是主打免膠卡扣；<strong>平鋪打膠</strong>——直接黏在地面，整體感好但拆換較麻煩。另外還有「直鋪（架高木地板）」用在有高低差或想架高收納的情況。</p>
<h3>有小孩、養寵物、想防水怎麼選</h3>
<ul>
<li><strong>有小孩、好清潔優先</strong>：超耐磨（抗刮好擦）或高抗水超耐磨。</li>
<li><strong>養貓狗、怕刮怕尿</strong>：SPC 石塑或高耐刮超耐磨，防水好清。</li>
<li><strong>重觸感與質感</strong>：海島型或實木。</li>
<li><strong>浴室、陽台、廚房、租屋</strong>：SPC 塑膠地板（唯一能全區防水）。</li>
</ul>
<div class="gg-warn"><b>⚠️ 最容易踩的雷</b><p>只比「一坪多少」不看板材等級與品牌——同樣叫超耐磨，耐磨轉數、甲醛等級（認明 F☆☆☆☆ 或 E1 以上）、防潮底差很多。另外要問清楚含不含「拆舊、防潮布、踢腳板、收邊條」，這些常另計；施工前地面不平要不要補土也先講好。</p></div>

<h2 id="price">台南木地板行情（每坪，連工帶料參考）</h2>
<table class="gg-table">
<thead><tr><th>類型</th><th>參考區間</th><th>特點</th></tr></thead>
<tbody>
<tr><td>超耐磨木地板</td><td>NT$2,500–5,000／坪</td><td>主流、好清、CP 高</td></tr>
<tr><td>海島型木地板</td><td>NT$4,000–8,000／坪</td><td>實木感、較抗變形</td></tr>
<tr><td>實木地板</td><td>NT$6,000–15,000＋／坪</td><td>質感最好、需保養</td></tr>
<tr><td>SPC 石塑地板</td><td>NT$2,000–4,500／坪</td><td>全防水、可用於衛浴陽台</td></tr>
</tbody>
</table>
<div class="gg-tip"><b>💡 台南在地觀察</b><p>台南門市多集中在東區（東門路一帶）與安平、仁德。建議先到<strong>實體展間試踩、看拼色</strong>——同一款地板在不同光線、面積下視覺差很多，展間看比看型錄準。旺季（交屋潮、農曆年前）師傅檔期會滿，看好房子就先預約丈量。</p></div>

<h2 id="s1">1. 昇揚木地板｜東區東門路　超耐磨・海島型・實木全備</h2>
<p>台南東門路的老字號地板門市，超耐磨、海島型、實木都有，展示架外還有樣品冊可翻。老闆娘會把各種材質差異講解到很清楚、不強迫推銷，讓你自己決定；施工前會先補平地面、完工矽利康收得漂亮，是很多台南家庭的口袋名單。</p>
<div class="si"><b>地址</b>：台南市東區東門路三段 204 號　｜　<b>電話</b>：<a href="tel:062695800">06-269-5800</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/Shengyangwoodenfloor">看昇揚木地板店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 5.0（51 則評論）</div><blockquote>「老闆娘在門市講解各種材質和地板差異非常詳盡！而且不會強迫推銷……我們地板有稍微不平整，老闆也在施工前一天進場先補土，完工後也協助我們把屋內的設施微調，相當細心。」</blockquote><cite>— Google 評論・Chun Ming Shen</cite></div>

<h2 id="s2">2. 安居木地板｜東區東橋　超耐磨・SPC 展間可試踩</h2>
<p>東區的地板門市，展間拼了各色木地板可以實際試踩、比較，從 SPC 到超耐磨都有實品鋪設，摸得到、踩得到差異。老闆會依你家的裝潢風格與需求給中肯建議（例如推薦高抗水超耐磨），對選擇障礙的人很友善，施工細節也顧得到。</p>
<div class="si"><b>地址</b>：台南市東區東橋十二街 176 號　｜　<b>電話</b>：<a href="tel:063035661">06-303-5661</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/BetterLife">看安居木地板店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.9（36 則評論）</div><blockquote>「因為不同光線照射都會影響地板顏色呈現，老闆建議到展間實際挑選……在 SPC 跟超耐磨地板之中，最後選擇了高抗水的超耐磨木地板，實際鋪設後覺得質感很好、很漂亮。」</blockquote><cite>— Google 評論・蕭郁軒</cite></div>

<h2 id="s3">3. 快步地板（富崧興業）｜安平　QuickStep 卡扣免膠超耐磨</h2>
<p>比利時品牌 QuickStep 的台南南區經銷，安平門市採預約制。主打卡扣免膠工法——不打膠、日後維修好、防水時效長，也是不少設計師愛用的地板。劉小姐現場介紹細緻，連黑色木地板這類少見選擇都能給到位建議，家裡破損還能補色補到看不出來。</p>
<div class="si"><b>地址</b>：台南市安平區建平五街 59 號（預約制）　｜　<b>電話</b>：<a href="tel:0965031001">0965-031-001</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/062132726">看快步地板店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.8（25 則評論）</div><blockquote>「先去門市選擇顏色，小姐幫我詳盡的介紹 QS 的各項好處（不打膠，維修容易、防水時效長⋯），家裡被小孩弄破損的木地板也非常謝謝劉小姐的補色，技術非常好，完全看不出來。」</blockquote><cite>— Google 評論・yuanling Chiu</cite></div>

<h2 id="s4">4. 亞拓｜仁德　塑膠地板・窗簾一起做</h2>
<p>台南仁德的老口碑店（累積 126 則評論），<strong>主打窗簾與塑膠地板（PVC／調光簾）</strong>，不是傳統木地板行；優點是「地板＋窗簾」可以交給同一家一次搞定，省去分頭找的麻煩。想用 SPC 塑膠地板、又要一起處理窗簾的家庭，找亞拓很方便；純想做實木或海島型的，建議參考前面三家。</p>
<div class="si"><b>地址</b>：台南市仁德區中山路 820 號　｜　<b>電話</b>：<a href="tel:062706977">06-270-6977</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/062706977">看亞拓店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.8（126 則評論）</div><blockquote>「整間店的地板與琴房的窗簾都是交給亞拓！老闆非常有耐心的介紹適合我們想法的產品，施作的工人也是非常細心的！」</blockquote><cite>— Google 評論・黃雲瑜</cite></div>

<h2 id="tips">台南挑木地板：4 個重點</h2>
<ol>
<li><strong>先定材質</strong>：預算有限＋好清找超耐磨；要實木感找海島型；重質感找實木；要防水（衛浴陽台）找 SPC。</li>
<li><strong>一定到展間試踩</strong>：光線與面積會改變視覺，型錄看不準，實體踩過再決定花色。</li>
<li><strong>看等級不只看單價</strong>：耐磨轉數、甲醛等級（F☆☆☆☆／E1）、防潮底，同樣「超耐磨」差很多。</li>
<li><strong>報價問清楚含什麼</strong>：拆舊、防潮布、踢腳板、收邊條、補土是否另計，白紙黑字寫下來。</li>
</ol>

<h2 id="faq">常見問題</h2>
<div class="gg-faq">
<div class="gg-q">超耐磨、海島型、實木木地板差在哪？</div>
<div class="gg-a">超耐磨是高壓木紋耐磨板，耐磨好清、最親民但怕長期泡水；海島型是實木皮＋夾板，有實木感又較抗變形；實木是原木，質感最好但怕潮、需保養、最貴。台灣潮濕，怕變形可優先考慮超耐磨或海島型。</div>
<div class="gg-q">木地板一坪大概多少錢？</div>
<div class="gg-a">連工帶料參考：超耐磨約 NT$2,500–5,000／坪、海島型 NT$4,000–8,000、實木 NT$6,000 以上、SPC 塑膠地板 NT$2,000–4,500。實際依板材等級、品牌、坪數與現場狀況，以丈量估價為準。</div>
<div class="gg-q">SPC 塑膠地板和木地板怎麼選？</div>
<div class="gg-a">SPC 石塑地板 100% 防水、耐刮、可用於衛浴廚房陽台，價格親民；但觸感與質感不如木地板。要全區防水或租屋、預算有限選 SPC；重觸感質感選海島型或實木。</div>
<div class="gg-q">卡扣免膠和平鋪打膠有什麼差？</div>
<div class="gg-a">卡扣免膠（浮動式）不打膠、一片片卡起來，單片壞了好抽換、維修方便；平鋪打膠直接黏地面，整體感好但拆換較麻煩。重維修彈性可選卡扣式。</div>
<div class="gg-q">家裡有小孩或養寵物適合哪種？</div>
<div class="gg-a">好清潔、抗刮優先選超耐磨或高抗水超耐磨；養貓狗怕刮怕尿選 SPC 石塑或高耐刮超耐磨，防水好清。挑時認明甲醛等級（F☆☆☆☆／E1 以上）較安心。</div>
<div class="gg-q">台南木地板門市集中在哪？</div>
<div class="gg-a">多集中在東區（東門路一帶）與安平、仁德。建議先到實體展間試踩、看拼色，同款地板在不同光線與面積下視覺差很多，展間比型錄準。</div>
</div>

<p>想看更多台南在地木地板／地板商家，可到 <a href="https://www.gomag.com.tw/city/tainan/home-service/flooring"><strong>台南木地板</strong></a> 專頁；想更全面了解各種地板，看 <a href="https://www.gomag.com.tw/guide/taiwan-flooring-guide-2026">全台木地板挑選完整指南</a>。裝潢相關可延伸看 <a href="https://www.gomag.com.tw/guide/tainan-interior-design-recommend">台南室內設計推薦</a>、<a href="https://www.gomag.com.tw/city/tainan/home-service">台南居家服務</a> 樞紐頁。</p>
HTML;

$faqs=[
 ['超耐磨、海島型、實木木地板差在哪？','超耐磨是高壓木紋耐磨板，耐磨好清、最親民但怕長期泡水；海島型是實木皮加夾板，有實木感又較抗變形；實木是原木，質感最好但怕潮、需保養、最貴。台灣潮濕，怕變形可優先考慮超耐磨或海島型。'],
 ['木地板一坪大概多少錢？','連工帶料參考：超耐磨約 NT$2,500–5,000／坪、海島型 NT$4,000–8,000、實木 NT$6,000 以上、SPC 塑膠地板 NT$2,000–4,500。實際依板材等級、品牌、坪數與現場狀況，以丈量估價為準。'],
 ['SPC 塑膠地板和木地板怎麼選？','SPC 石塑地板 100% 防水、耐刮、可用於衛浴廚房陽台，價格親民；但觸感與質感不如木地板。要全區防水或租屋、預算有限選 SPC；重觸感質感選海島型或實木。'],
 ['卡扣免膠和平鋪打膠有什麼差？','卡扣免膠（浮動式）不打膠、一片片卡起來，單片壞了好抽換、維修方便；平鋪打膠直接黏地面，整體感好但拆換較麻煩。重維修彈性可選卡扣式。'],
 ['家裡有小孩或養寵物適合哪種？','好清潔、抗刮優先選超耐磨或高抗水超耐磨；養貓狗怕刮怕尿選 SPC 石塑或高耐刮超耐磨，防水好清。挑時認明甲醛等級（F☆☆☆☆／E1 以上）較安心。'],
 ['台南木地板門市集中在哪？','多集中在東區（東門路一帶）與安平、仁德。建議先到實體展間試踩、看拼色，同款地板在不同光線與面積下視覺差很多，展間比型錄準。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$sql="INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'tainan', 2, 'flooring', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=2,service_slug='flooring',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),
    cover_image=VALUES(cover_image),status='published'";
$db->prepare($sql)->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug}\n   body={$r['bl']}B 純文字約=".mb_strlen(strip_tags($body))."字 gg-=".substr_count($body,'gg-')." 評價區=".substr_count($body,'<div class="gr">')." 店連結=".substr_count($body,'/store/')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
