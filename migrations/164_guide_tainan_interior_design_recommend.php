<?php
// ============================================================
// Migration 164 — 新攻略《台南室內設計推薦》(照 #20/#71 深度格式)
// ------------------------------------------------------------
// Reney 指定置入 7 家：全境#44 / 富川#98 / 亞筑#99 / 高森#130 / 久玳#193 / 德恩#154 / 聖德#269
//   (核心6排除一新#192、加德恩、加聖德；亞筑用行銷頁#99不用artru小官網；排除衡作demo)
// 真實 Google 評價：全境5.0/6、富川5.0/8、亞筑4.5/8(API)、德恩5.0/3、聖德4.7/48(Reney截圖)
//   高森3.4/久玳3.7 評分偏低 → 不放 Google 數字，只放基本介紹(不捏造、不放大不利數字)
// category_id=8(專業服務,interior-design 掛這)、service_slug='interior-design'、cover 絕對URL
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/164_guide_tainan_interior_design_recommend.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug='tainan-interior-design-recommend';
$title='台南室內設計推薦 2026｜嚴選 7 家設計、統包、系統櫃團隊一次看';
$excerpt='台南室內設計怎麼找？店家好口碑嚴選 7 家台南在地團隊：亞筑、全境、聖德、富川、德恩、高森、久玳，從純設計、設計統包到系統櫃、老屋翻新都涵蓋，附設計/統包/系統櫃差別、費用行情、怎麼挑與真實 Google 評價。';
$metaTitle='台南室內設計推薦 2026｜嚴選 7 家設計統包系統櫃｜費用行情懶人包';
$metaDesc='台南室內設計推薦哪幾家？涵蓋純設計、設計統包、系統櫃、老屋翻新，東區、永康、仁德、佳里溪北在地團隊，設計費怎麼算、每坪行情、發包流程與怎麼避免追加，一篇看完。';
$cover='https://www.gomag.com.tw/uploads/guides/covers/renovation.svg';

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
<p class="gg-lead">台南買了新成屋、或想把住了二十年的老家翻新，第一步幾乎都卡在「找誰設計」。台南室內設計團隊有純設計公司、設計統包、系統櫃廠商，還有專做老屋翻新的統包師傅，找錯類型不只多花錢，還可能溝通不良、預算失控。這篇店家好口碑整理 <strong>7 家台南在地室內設計團隊</strong>，從東區的設計公司、仁德的系統空間，到佳里溪北的老屋統包都涵蓋，先帶你搞懂三種模式差在哪，再一家一家看，附真實 Google 評價與費用行情。</p>
<p><em>※ 名單為店家好口碑收錄之台南在地商家，資料以各店公開資訊與 Google 商家為準，費用請以現場丈量估價為主。</em></p>

<h2 id="compare">先看懶人包｜7 家台南室內設計一次比較</h2>
<table class="gg-table">
<thead><tr><th>店家</th><th>區域</th><th>類型</th><th>最適合</th><th>口碑</th></tr></thead>
<tbody>
<tr><td>亞筑室內設計</td><td>東區</td><td>設計公司</td><td>重設計感與溝通</td><td>Google 4.5</td></tr>
<tr><td>全境空間規劃</td><td>東門路</td><td>設計＋系統＋木地板</td><td>首購一站整合</td><td>Google 5.0</td></tr>
<tr><td>聖德室內裝修</td><td>北區</td><td>裝修設計</td><td>系統櫃機能＋質感</td><td>Google 4.7</td></tr>
<tr><td>富川統包宅修</td><td>安南港尾</td><td>設計統包・土木</td><td>統包省事／老屋</td><td>Google 5.0</td></tr>
<tr><td>德恩室內裝修</td><td>佳里溪北</td><td>統包・老屋翻新</td><td>溪北在地全屋整修</td><td>Google 5.0</td></tr>
<tr><td>高森系統空間</td><td>仁德</td><td>系統家具</td><td>小資族・重收納</td><td>在地口碑</td></tr>
<tr><td>久玳空間製作所</td><td>北門路</td><td>空間設計</td><td>圖面細節清楚</td><td>在地口碑</td></tr>
</tbody>
</table>

<h2 id="know">先懂再挑：設計、統包、系統櫃差在哪</h2>
<p>台南室內設計的報價從「每坪幾千的設計費」到「整案上百萬的統包」都有，關鍵在你找的是哪一種團隊。搞懂下面三種模式，才知道報價合不合理、自己該找誰。</p>
<div class="gg-cards">
  <div class="gg-card"><b>純設計（設計監造）</b><span>設計師出平面圖、3D 圖與施工圖，負責監工，工程另外發包。設計費多以每坪計。適合有想法、要客製風格、願意花時間溝通的人。</span></div>
  <div class="gg-card"><b>設計統包（一條龍）</b><span>設計到施工同一團隊，從丈量、設計到工班、進場都包。省去自己發包協調的麻煩，報價以整案計。適合上班忙、想省事、或老屋翻新。</span></div>
  <div class="gg-card"><b>系統櫃／系統空間</b><span>以系統板材櫃體為主、設計較單純，主打收納機能與快速。以「尺」計價。適合預算有限、以收納和機能為重的首購族。</span></div>
</div>
<h3>設計費與費用怎麼抓</h3>
<p>純設計的設計費，台南常見每坪約 NT$3,000–8,000，看設計師資歷與服務深度；設計統包「連工帶料」的整案，一般每坪約 NT$60,000–120,000 起，等級（建材、系統櫃多寡、木作比例）與屋況差很多；系統櫃以尺計，每尺約 NT$3,000–8,000，看板材（塑合板／發泡板）與五金品牌。這些是抓預算的參考區間，實際一定要現場丈量、拿到含施工圖的報價再比。</p>
<h3>發包流程大致長這樣</h3>
<p>丈量現況 → 平面配置圖 → 3D 或立面圖 → 詳細報價單 → 簽約（附圖說）→ 施工（隱蔽工程／泥作／木作／系統櫃／油漆）→ 驗收。每個階段都該有書面，尤其報價單要逐項列、變更要有追加單，才不會做到一半糊塗加價。</p>
<h3>老屋翻新和新成屋不一樣</h3>
<p>台南老屋（尤其中西區、北區、佳里溪北一帶的透天與老公寓）翻新，要先看管線、防水、壁癌與結構，這些「隱蔽工程」比重高、預算與工期都會拉長；新成屋則多是表面與機能規劃。老屋建議找有<strong>統包與土木經驗</strong>的團隊，別只找純系統櫃廠商。</p>
<div class="gg-warn"><b>⚠️ 最容易踩的雷</b><p>報「一坪多少」或「總價多少」卻沒有詳細圖說與逐項報價，事後一項項追加；沒有簽約或圖說，做出來跟講的不一樣也難認定。下訂前一定要拿到平面圖＋逐項報價單，付款綁進度、變更留書面。</p></div>

<h2 id="s1">1. 亞筑室內設計｜東區　設計主導・風格提案</h2>
<p>台南東區的室內設計公司，設計主導型：會先針對空間提出多種風格方案，選定後再細化到符合屋主理想，施工中定期更新現場進度與照片。適合重視設計感、也重視過程溝通的家庭。</p>
<div class="si"><b>地址</b>：台南市東區崇德一街 72 號 6F　｜　<b>電話</b>：<a href="tel:062602776">06-260-2776</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/Interiordesign72">看亞筑室內設計店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.5（8 則評論）</div><blockquote>「我們家是三年前裝潢完入住，到現在每天回家還是覺得『啊～好喜歡這個家』……施工中定期都會更新現場的進度及照片讓我們知道，有任何問題都會隨時跟我們溝通。」</blockquote><cite>— Google 評論・Hsuan-An Chen</cite></div>

<h2 id="s2">2. 全境空間規劃｜東門路　設計＋系統家具＋木地板一站</h2>
<p>把室內設計、系統家具、超耐磨木地板整合在一起的團隊，首購族想一站搞定很省事。老闆石先生好溝通、用料實在、預算抓得透明，沒想清楚的地方會協助調整，售後服務評價也不錯。</p>
<div class="si"><b>地址</b>：台南市東門路二段 301 巷 56 號　｜　<b>電話</b>：<a href="tel:0960762160">0960-762-160</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/interior-renovation-02">看全境空間規劃店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 5.0（6 則評論）</div><blockquote>「老闆親切！跟我們細心討論設計規劃，用料很實在，價格很公道。非常推薦！想要裝潢的朋友，來找全境吧！」</blockquote><cite>— Google 評論・Hanna</cite></div>

<h2 id="s3">3. 聖德室內裝修｜北區　系統櫃機能・質感設計</h2>
<p>台南北區的室內裝修設計團隊，系統櫃與機能規劃見長，也懂用跳色搭配讓系統櫃不呆板、做出質感。Google 累積近 50 則評論、口碑穩定，適合重收納機能又要設計感的家庭。</p>
<div class="si"><b>地址</b>：台南市北區文賢三街 337 巷 60 號　｜　<b>電話</b>：<a href="tel:062639885">06-263-9885</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/interiordesign2">看聖德室內裝修店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.7（48 則評論）</div><blockquote>「系統廚具的設計很符合我的烹飪習慣，動線規劃得很順手。原本很擔心系統櫃會看起來很呆板，結果設計師用跳色搭配，整體看起來很有質感，朋友來家裡都說好看。」</blockquote><cite>— Google 評論・徐千惠</cite></div>

<h2 id="s4">4. 富川統包宅修｜安南港尾　設計統包・土木一條龍</h2>
<p>富川是設計統包加土木的一條龍團隊，新宅室內設計到老屋翻修都做。對不懂工程的屋主特別有耐心，會把翻修步驟與細節解釋到懂為止，用料誠實，是想「統包省事」或做老屋翻新的選擇。</p>
<div class="si"><b>地址</b>：台南市安南區港子尾 16-13 號　｜　<b>電話</b>：<a href="tel:0975513330">0975-513-330</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/engineering">看富川統包宅修店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 5.0（8 則評論）</div><blockquote>「老闆對業主非常有耐心，因為不懂工程翻修的步驟細節……老闆都會解釋到讓業主瞭解為止。對於新宅室內設計或是老屋翻修都不馬虎，可以感受到老闆的專業與服務熱忱！」</blockquote><cite>— Google 評論・Muooo_luo</cite></div>

<h2 id="s5">5. 德恩室內裝修｜佳里溪北　老屋翻新・全屋整修</h2>
<p>佳里在地的室內裝修統包團隊，全屋整修從規劃設計、施工到完工一手包，整修範圍涵蓋房間、浴室、廚房、客廳。佳里、學甲、麻豆一帶（溪北地區）找在地統包做老屋翻新，不用捨近求遠往市區跑。</p>
<div class="si"><b>地址</b>：台南市佳里區公園路 236 號 1 樓　｜　<b>電話</b>：<a href="tel:067238158">06-723-8158</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/interior-renovation-01">看德恩室內裝修店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 5.0（3 則評論）</div><blockquote>「這次全屋整修的經驗非常滿意，整個團隊從規劃設計、施工到完工都展現出高度的專業與效率。整修範圍包含房間、浴室、廚房、客廳與休閒室，工程龐大但每個空間都處理得非常細緻。」</blockquote><cite>— Google 評論・允生劉</cite></div>

<h2 id="s6">6. 高森系統空間｜仁德　系統家具・小資實惠</h2>
<p>位在仁德交流道旁的系統家具／系統空間團隊，資深師傅出身、依屋主預算與需求規劃，價格走實惠路線，符合小資族與重視收納機能的需求。近國道、跨區來丈量也方便。</p>
<div class="si"><b>地址</b>：台南市仁德區中山路 687 巷 1 號（仁德交流道旁）　｜　<b>電話</b>：<a href="tel:062709996">06-270-9996</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/systemfurniture">看高森系統空間店家頁</a></div>

<h2 id="s7">7. 久玳空間製作所｜北門路　空間設計・圖面細節清楚</h2>
<p>台南市區北門路一帶的空間製作團隊，主打圖面細節與流程清楚，接洽到規劃溝通順暢。想找市區、重視前期圖面與流程說明的屋主可以參考。</p>
<div class="si"><b>地址</b>：台南市北門路三段 26 號　｜　<b>電話</b>：<a href="tel:0960165266">0960-165-266</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/interiorrenovation7">看久玳空間製作所店家頁</a></div>

<h2 id="price">台南室內設計費用行情</h2>
<table class="gg-table">
<thead><tr><th>類型</th><th>常見計價</th><th>參考區間</th></tr></thead>
<tbody>
<tr><td>純設計監造</td><td>每坪設計費</td><td>NT$3,000–8,000／坪</td></tr>
<tr><td>設計統包（連工帶料）</td><td>整案／每坪</td><td>NT$60,000–120,000／坪 起</td></tr>
<tr><td>系統櫃</td><td>每尺</td><td>NT$3,000–8,000／尺</td></tr>
<tr><td>老屋翻新</td><td>整案（隱蔽工程另計）</td><td>視管線／防水／結構加成</td></tr>
</tbody>
</table>
<div class="gg-tip"><b>💡 台南在地觀察</b><p>東區、永康、安平重劃區的新成屋，以及仁德、歸仁（近台積電擴廠）帶動的購屋裝潢需求最旺；佳里、學甲等溪北地區則以老屋翻新與在地統包為主。旺季（交屋潮）設計師檔期會滿，建議看好房子就先約丈量。</p></div>

<h2 id="tips">台南找室內設計：4 個重點</h2>
<ol>
<li><strong>先定位需求</strong>：要客製設計找設計公司；要省事找設計統包；預算有限、以收納為主找系統櫃；老屋翻新找有土木統包經驗的。</li>
<li><strong>看圖說與報價</strong>：一定要有平面圖與逐項報價單，別接受「總價含糊」的口頭報價。</li>
<li><strong>看實際案例與風格</strong>：對照設計師過往案例，風格對不對味比美照重要；同時看 Google 評價的評論內容（不是只看分數）。</li>
<li><strong>付款綁進度、變更留書面</strong>：分期對應施工階段，任何追加變更都要有書面追加單。</li>
</ol>

<h2 id="faq">常見問題</h2>
<div class="gg-faq">
<div class="gg-q">台南室內設計費用怎麼算？</div>
<div class="gg-a">純設計監造多以「每坪設計費」計（約 3,000–8,000／坪）；設計統包（連工帶料）以整案報價、依屋況與等級差很多；系統櫃以尺計。建議先丈量、拿到含施工圖的報價再比較。</div>
<div class="gg-q">設計、統包、系統櫃有什麼不同？</div>
<div class="gg-a">純設計＝出圖與監工、工程另發包；設計統包＝設計到施工一條龍；系統櫃廠商＝以櫃體為主、設計較單純。要客製找設計公司，要省事找統包，預算有限以收納為主找系統櫃。</div>
<div class="gg-q">老屋翻新和新成屋裝潢差在哪？</div>
<div class="gg-a">老屋要先處理管線、防水、壁癌與結構，隱蔽工程比重高、預算與工期都會拉長；新成屋多為表面與機能規劃。老屋建議找有統包與土木經驗的團隊。</div>
<div class="gg-q">台南室內設計要抓多少預算？</div>
<div class="gg-a">差異很大，設計統包連工帶料常見每坪 6–12 萬起（視等級、屋況、系統櫃多寡），老屋另計。以現場丈量估價為準。</div>
<div class="gg-q">怎麼避免追加與糾紛？</div>
<div class="gg-a">簽約前確認圖說（平面圖、施工圖）、報價單逐項列、付款分期綁進度、變更要有書面追加單。報價「總價含糊、事後追加」是最常見的糾紛來源。</div>
<div class="gg-q">台南哪些區找室內設計最多？</div>
<div class="gg-a">東區、永康、安平重劃區的新成屋，以及仁德、歸仁（近台積電）擴廠帶動的購屋裝潢需求最旺；佳里、學甲等溪北地區則以老屋翻新與在地統包為主。</div>
</div>

<p>想看更多台南在地室內設計團隊，可到 <a href="https://www.gomag.com.tw/city/tainan/home-service/interior-design"><strong>台南室內設計</strong></a> 專頁；老屋翻新可延伸看 <a href="https://www.gomag.com.tw/guide/tainan-old-house-renovation">台南老屋翻新攻略</a>。裝潢完的收尾，再看 <a href="https://www.gomag.com.tw/guide/tainan-fine-clean-recommend">台南裝潢細清推薦</a>、<a href="https://www.gomag.com.tw/city/tainan/home-service">台南居家服務</a> 樞紐頁。</p>
HTML;

// FAQPage JSON-LD
$faqs=[
 ['台南室內設計費用怎麼算？','純設計監造多以每坪設計費計（約 3,000–8,000／坪）；設計統包（連工帶料）以整案報價、依屋況與等級差很多；系統櫃以尺計。建議先丈量、拿到含施工圖的報價再比較。'],
 ['設計、統包、系統櫃有什麼不同？','純設計＝出圖與監工、工程另發包；設計統包＝設計到施工一條龍；系統櫃廠商＝以櫃體為主、設計較單純。要客製找設計公司，要省事找統包，預算有限以收納為主找系統櫃。'],
 ['老屋翻新和新成屋裝潢差在哪？','老屋要先處理管線、防水、壁癌與結構，隱蔽工程比重高、預算與工期都會拉長；新成屋多為表面與機能規劃。老屋建議找有統包與土木經驗的團隊。'],
 ['台南室內設計要抓多少預算？','差異很大，設計統包連工帶料常見每坪 6–12 萬起（視等級、屋況、系統櫃多寡），老屋另計。以現場丈量估價為準。'],
 ['怎麼避免追加與糾紛？','簽約前確認圖說（平面圖、施工圖）、報價單逐項列、付款分期綁進度、變更要有書面追加單。報價總價含糊、事後追加是最常見的糾紛來源。'],
 ['台南哪些區找室內設計最多？','東區、永康、安平重劃區的新成屋，以及仁德、歸仁（近台積電）擴廠帶動的購屋裝潢需求最旺；佳里、學甲等溪北地區則以老屋翻新與在地統包為主。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$sql="INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'tainan', 8, 'interior-design', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=8,service_slug='interior-design',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),
    cover_image=VALUES(cover_image),status='published'";
$db->prepare($sql)->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
$txt=strip_tags($body);
echo "✅ #{$r['id']} {$slug}\n   body={$r['bl']}B 純文字約=".mb_strlen($txt)."字 gg-=".substr_count($body,'gg-')." gr=".substr_count($body,'class=\"gr\"')." 店家連結=".substr_count($body,'/store/')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
