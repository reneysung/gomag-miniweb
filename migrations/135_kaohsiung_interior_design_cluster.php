<?php
// ============================================================
// Migration 135 — 高雄室內設計束補 2 篇（gg- 範本）→ 室內設計頁滿 4
// ------------------------------------------------------------
// 高雄室內設計頁現 2 篇（老屋翻新+台積電裝修，皆翻新場景）。interior-design 不在
// $guideFamily 補位名單 → 需自己 4 篇。補 2 篇核心室內設計角度（角度錯開）：
//   1) 高雄室內設計怎麼挑：合作模式/挑設計師/避坑
//   2) 高雄室內設計費用行情：設計費/輕裝修vs全室/老屋翻新貴在哪
// 高雄在地化：亞洲新灣區/美術館/農十六/巨蛋豪宅、楠梓台積電換屋、鳳山透天、
// 港都濕熱（建材防潮/通風採光）。語氣套保留人味鐵則。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/135_kaohsiung_interior_design_cluster.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cover = 'https://www.gomag.com.tw/uploads/guides/covers/renovation.svg';
$hub   = 'https://www.gomag.com.tw/city/kaohsiung/home-service';
$pId   = 'https://www.gomag.com.tw/city/kaohsiung/home-service/interior-design';
$pReno = 'https://www.gomag.com.tw/city/kaohsiung/home-service/reno-detail';

$articles = [];

// ── 1) 高雄室內設計怎麼挑 ──
$articles[] = ['slug'=>'kaohsiung-interior-design-howto', 'cover'=>$cover,
  'title'=>'高雄室內設計怎麼挑？合作模式、挑設計師重點、避坑一次看',
  'excerpt'=>'高雄找室內設計怎麼選？純設計、設計加監工、統包三種合作模式差在哪、挑設計師 5 個重點與避坑清單，亞灣、農十六、楠梓換屋族裝修前必看。',
  'meta_title'=>'高雄室內設計怎麼挑｜合作模式挑設計師避坑｜店家好口碑',
  'meta_desc'=>'高雄室內設計怎麼選？純設計/設計加監工/統包三種模式、挑設計師 5 重點、合約與避坑清單，亞洲新灣區、農十六、楠梓台積電換屋族裝修前必看。',
  'body'=> <<<HTML
<p class="gg-lead">在高雄找室內設計，從亞洲新灣區、農十六的新成屋客變，到鳳山、楠梓的中古翻修，需求差很多。設計費怎麼算、要找純設計還是統包、怎麼避免做到一半追加不停，先搞懂這些再開始談，比較不會踩雷。</p>

<h2>三種合作模式</h2>
<div class="gg-cards">
  <div class="gg-card"><b>純設計</b><span>只出圖（平面、3D、施工圖），自己發包工班。設計費每坪約 NT\$3,000–8,000。適合有監工能力或認識工班的人。</span></div>
  <div class="gg-card"><b>設計加監工</b><span>設計師出圖並監工，工程另外發包。監工費約工程款 5–10%。品質與彈性的平衡點。</span></div>
  <div class="gg-card"><b>統包（設計施工一體）</b><span>設計到施工一家負責，溝通成本低、責任清楚，但要慎選、貨比三家。</span></div>
</div>

<h2>挑高雄設計師的 5 個重點</h2>
<ol>
  <li><strong>看同類型作品</strong>：做慣豪宅的未必擅長小宅收納，老屋翻新要找有老屋實績的。</li>
  <li><strong>報價單顆粒度</strong>：「一式」越多越危險，正規報價會列到品牌、型號、數量、單價。</li>
  <li><strong>合約完整</strong>：付款節點綁工程進度、變更追加要書面、保固條款寫清楚。</li>
  <li><strong>溝通有沒有紀錄</strong>：工程群組、週報、現場照片，有紀錄才有依據。</li>
  <li><strong>口碑與糾紛紀錄</strong>：Google 評論、社群討論，也查公司登記與消費爭議。</li>
</ol>

<h2>高雄在地要注意</h2>
<ul>
  <li><strong>濕熱與通風</strong>：高雄悶熱，建材選防潮板材、規劃通風採光很重要，靠海區（鼓山、前鎮）更要留意防鏽防潮。</li>
  <li><strong>亞洲新灣區、農十六、美術館</strong>：新成屋客變多，及早找設計師卡客變時程。</li>
  <li><strong>楠梓、橋頭</strong>：台積電換屋族多，中古整理與新成屋裝修需求都旺。</li>
  <li><strong>鳳山、岡山透天</strong>：樓層多、格局調整空間大，預算與工期要抓好。</li>
</ul>

<div class="gg-warn">
  <b>⚠️ 避坑清單</b>
  <p>報價明顯低於行情，後期常追加；要求頭款超過三成或一次付清；不給看施工圖、不讓去工地；口頭承諾不寫進合約；完工拖延卻沒違約條款。遇到這些要特別小心。</p>
</div>
<div class="gg-tip">
  <b>💡 小提醒</b>
  <p>設計約和工程約建議分開簽：設計約收設計費、交付圖面；工程約綁施工品質與工期。出問題時責任比較好切割。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">純設計和統包哪個好？</div>
  <div class="gg-a">看你有沒有時間監工、認不認識工班。要省心找統包；想自己掌控、比價找純設計或設計加監工。</div>
  <div class="gg-q">設計費可以折抵工程款嗎？</div>
  <div class="gg-a">部分統包公司「簽工程約折抵設計費」，但折抵通常綁定工程給同一家做，比價空間就沒了，把設計費當獨立成本看比較不會被綁。</div>
  <div class="gg-q">高雄濕熱對裝修材料有影響嗎？</div>
  <div class="gg-a">有。建議選防潮板材、做好通風採光，靠海區留意金屬件防鏽，能降低日後發霉、變形的機會。</div>
  <div class="gg-q">設計約和工程約要分開嗎？</div>
  <div class="gg-a">建議分開，責任與付款比較清楚。</div>
</div>

<p>找高雄室內設計看 <a href="{$pId}"><strong>高雄室內設計</strong></a>，裝潢後入住看 <a href="{$pReno}">高雄裝潢細清</a>，更多服務看 <a href="{$hub}">高雄居家服務</a>。</p>
HTML];

// ── 2) 高雄室內設計費用行情 ──
$articles[] = ['slug'=>'kaohsiung-interior-design-cost', 'cover'=>$cover,
  'title'=>'高雄室內設計費用行情：設計費、輕裝修 vs 全室、老屋翻新貴在哪',
  'excerpt'=>'高雄室內設計多少錢？設計費每坪行情、輕裝修與全室裝修每坪費用、老屋翻新為什麼比新成屋貴，亞灣、楠梓、鳳山裝修前先抓預算。',
  'meta_title'=>'高雄室內設計費用行情｜設計費裝修每坪價｜店家好口碑',
  'meta_desc'=>'高雄室內設計費用：設計費每坪 NT$3,000–8,000、輕裝修每坪 2–5 萬、全室新成屋 5–10 萬、老屋翻新 8–15 萬，亞灣、楠梓、鳳山裝修預算一次看。',
  'body'=> <<<HTML
<p class="gg-lead">高雄裝修預算怎麼抓？室內設計的錢分成「設計費」和「工程費」兩塊，新成屋輕裝修和老屋全室翻新差很多。先抓個範圍，談的時候才有底。以下為高雄常見區間，實際以丈量與報價為準。</p>

<h2>設計費與工程費怎麼分</h2>
<table class="gg-table">
  <thead><tr><th>項目</th><th>計價</th><th>高雄常見行情</th></tr></thead>
  <tbody>
    <tr><td>設計費</td><td>每坪</td><td>NT\$3,000–8,000（知名設計師更高）</td></tr>
    <tr><td>丈量／初步配置</td><td>單次</td><td>有的免費、有的 NT\$3,000–10,000 可折抵</td></tr>
    <tr><td>輕裝修（油漆/地板/燈/系統櫃）</td><td>每坪</td><td>NT\$2–5 萬</td></tr>
    <tr><td>全室裝修・新成屋</td><td>每坪</td><td>NT\$5–10 萬</td></tr>
    <tr><td>全室裝修・老屋翻新</td><td>每坪</td><td>NT\$8–15 萬</td></tr>
  </tbody>
</table>

<h2>輕裝修 vs 全室裝修</h2>
<div class="gg-cards">
  <div class="gg-card"><b>輕裝修</b><span>不動格局與水電，做油漆、地板、燈具、系統櫃、窗簾。30 坪新成屋約 NT\$60–150 萬，控制預算關鍵就是不動格局與水電。</span></div>
  <div class="gg-card"><b>全室裝修</b><span>含格局調整、水電、衛浴廚房。新成屋每坪 5–10 萬；老屋每坪 8–15 萬，基礎工程占比高。</span></div>
</div>

<h2>老屋翻新為什麼比較貴</h2>
<p>高雄不少 30 年以上的中古屋與老公寓（鹽埕、前金、三民一帶），電線、水管、防水都到壽命，必須翻新。這些「看不見的基礎工程」每坪約 NT\$3–6 萬起，加上格局調整與表面材，每坪 NT\$8–15 萬是合理範圍。只做表面不動基礎，漏水跳電很快就找上門。</p>

<div class="gg-tip">
  <b>💡 高雄在地提醒</b>
  <p>高雄濕熱，老屋翻新時防水、除壁癌、通風要一起處理，編預算時別省這塊。靠海區（鼓山、前鎮、小港）金屬件與外牆防鏽也要算進去。</p>
</div>
<div class="gg-warn">
  <b>⚠️ 抓預算提醒</b>
  <p>報價特別低的，常用「一式」含糊帶過、後期追加。要求報價單列到品牌、型號、數量、單價，並預留約 10% 的彈性預算因應現場狀況。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">高雄 30 坪新成屋輕裝修要多少？</div>
  <div class="gg-a">油漆加超耐磨地板加系統櫃加燈具窗簾，約 NT\$60–150 萬（每坪 2–5 萬），看櫃體數量與建材等級。</div>
  <div class="gg-q">老屋翻新為什麼比新成屋貴？</div>
  <div class="gg-a">老屋的電線、水管、防水都要重做（每坪基礎工程 3–6 萬起），再加格局與表面材，每坪 8–15 萬是合理區間。</div>
  <div class="gg-q">設計費和工程費要分開看嗎？</div>
  <div class="gg-a">建議分開。設計費是出圖的費用，工程費是施工的費用，分清楚比較不會被綁。</div>
  <div class="gg-q">裝修完多久可以入住？</div>
  <div class="gg-a">看板材與塗料等級，通風 2 週到 1 個月。有小孩、孕婦建議做甲醛檢測再入住，入住前記得安排<a href="{$pReno}">裝潢細清</a>。</div>
</div>

<p>找高雄室內設計看 <a href="{$pId}"><strong>高雄室內設計</strong></a>，裝潢後入住看 <a href="{$pReno}">高雄裝潢細清</a>，更多服務看 <a href="{$hub}">高雄居家服務</a>。</p>
HTML];

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'kaohsiung', 2, 'interior-design', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug='interior-design', cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");
foreach ($articles as $a) {
    $gs->execute([$a['slug'], $a['title'], $a['excerpt'], $a['body'], $a['cover'], $a['meta_title'], $a['meta_desc']]);
    $row = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug=" . $db->quote($a['slug']))->fetch(PDO::FETCH_ASSOC);
    printf("  ✅ #%s %s (%dB)\n", $row['id'], $a['slug'], $row['l']);
}

echo "\n== 高雄室內設計頁 修後 ==\n";
$rg = $db->query("SELECT title FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='kaohsiung' AND FIND_IN_SET('interior-design',service_slug)>0 ORDER BY published_at DESC LIMIT 4");
$n=0; foreach ($rg as $r) { $n++; printf("  %d. %s\n", $n, $r['title']); }
echo "  → 共 {$n} 篇\n預覽：{$pId}\n";
