<?php
// ============================================================
// Migration 153 — 攻略《為什麼高雄需要全戶軟水》(淨水器 cluster 範本，蕎藝角度)
// ------------------------------------------------------------
// Reney 設計方向：一家一角度差異化。蕎藝主攻全戶軟水 → 純教育文，用蕎藝 /store/
// 頁的照片配圖（我們帳號），但**不提品牌、不放聯絡資訊、不連客戶**。這是 3 篇淨水器
// 攻略的第一篇範本，過稿後再寫滴滴(瑞士水)、水領導(AquaLeader)。
// 內鏈先連「高雄居家服務」樞紐；軟水/淨水器子服務頁待啟用後再補連。
// service_slug=ruanshui,jingshuiqi、city=kaohsiung。人味鐵則：平實、無金句。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/153_guide_kaohsiung_whole_house_soft_water.php
// 冪等：slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'kaohsiung-whole-house-soft-water';
$title = '為什麼高雄需要全戶軟水？硬水困擾、軟水原理與安裝重點一次看';
$excerpt = '高雄的水偏硬，玻璃白垢、熱水器積垢、皮膚乾澀都跟它有關。全戶軟水從水塔進水處理全家的水，這篇講高雄為什麼特別需要、跟淨水/RO 差在哪、怎麼裝、行情與保養。';
$metaTitle = '高雄全戶軟水為什麼需要？硬水困擾、跟淨水差別、安裝行情｜店家好口碑';
$metaDesc = '高雄水質偏硬，水垢、白垢、家電積垢是日常。全戶軟水是什麼、跟廚下淨水/RO 差在哪、透天大樓怎麼裝、要不要加鹽、行情與保養週期，一篇看懂。';
$cover = 'https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water/kh-softwater-install.webp';

$body = <<<'HTML'
<p class="gg-lead">高雄的水，用久了看得出來。浴室玻璃一層洗不掉的白垢、熱水器和水龍頭出水口鈣化、深色衣服洗完泛白、洗完頭髮澀澀的——這些多半跟「水偏硬」有關。全戶軟水就是在水進到家裡之前先把水軟化，讓全家的水都變好用。這篇講高雄為什麼特別容易遇到硬水、全戶軟水跟一般淨水/RO 差在哪、透天和大樓怎麼裝、以及行情與保養。</p>

<figure><img src="https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water/kh-softwater-install.webp" alt="高雄全戶淨軟水系統安裝實景" loading="lazy"><figcaption>全戶軟水裝在水進屋前的總管或水塔端，處理全家的水。</figcaption></figure>

<h2>高雄的水為什麼偏「硬」</h2>
<p>水的軟硬看的是溶在水裡的鈣、鎂含量（總硬度）。高雄的自來水主要來自高屏溪與澄清湖系統，南部地質與水源條件讓水的硬度普遍比北部高一些；加上夏季雨後原水濁度變化、部分老社區的管線與屋頂水塔久未清洗，實際到你家水龍頭的水質，常比想像中更容易結垢。</p>
<p>硬水本身不是「不能用」，但鈣鎂長期累積，會在看得到和看不到的地方留下痕跡。</p>

<h2>硬水在高雄家裡的實際困擾</h2>
<div class="gg-cards">
  <div class="gg-card"><b>玻璃、磁磚白垢</b><span>浴室玻璃、鏡子、拋光磚上一層白白的水垢，擦掉又很快回來。</span></div>
  <div class="gg-card"><b>家電積垢、耗電</b><span>熱水器、電熱水瓶、洗碗機內部結垢，加熱效率變差、也比較容易壞。</span></div>
  <div class="gg-card"><b>洗滌手感差</b><span>肥皂洗劑不好起泡、要用更多量；深色衣物洗久泛白、毛巾變硬。</span></div>
  <div class="gg-card"><b>皮膚頭髮乾澀</b><span>洗完澀澀緊繃，敏感肌或有小孩的家庭比較在意。</span></div>
  <div class="gg-card"><b>水龍頭、蓮蓬頭鈣化</b><span>出水口結垢、出水變小變散，得定期泡醋除垢。</span></div>
  <div class="gg-card"><b>管線與閥件</b><span>長期水垢累積在管線、混水閥，維修更換的機會變高。</span></div>
</div>

<h2>全戶軟水 vs 廚下淨水 vs RO，差在哪</h2>
<p>這三個常被混在一起，其實處理的東西不一樣，最好搭配用、不是二選一。</p>
<table class="gg-table">
  <thead><tr><th>類型</th><th>處理什麼</th><th>用在哪</th></tr></thead>
  <tbody>
    <tr><td>全戶軟水</td><td>降低鈣鎂硬度（除水垢）</td><td>全家用水：洗澡、洗衣、清潔</td></tr>
    <tr><td>全戶淨水</td><td>除氯、泥沙、雜質（不軟化）</td><td>全家用水的前置過濾</td></tr>
    <tr><td>廚下 RO 純水機</td><td>逆滲透除雜質、重金屬、細菌</td><td>只做飲用、煮飯的水</td></tr>
  </tbody>
</table>
<div class="gg-warn"><b>⚠️ 軟水不等於可以生飲</b><p>軟水是把水「變軟、少水垢」，不是把水「變乾淨到能喝」。飲用水還是建議另外走廚下 RO 或淨水設備。全戶軟水解決的是洗澡、洗衣、家電結垢這類「用水」問題。</p></div>

<h2>全戶軟水怎麼運作、高雄安裝重點</h2>
<p>常見的全戶軟水用「離子交換」樹脂把水中的鈣鎂換掉，樹脂用久要再生。傳統機型靠加「再生鹽」定期再生，近年也有主打免鹽、免耗材的機型。系統通常裝在水進屋的總管或屋頂水塔進水端，讓後續全家的水都經過處理。</p>
<figure><img src="https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water/kh-softwater-technician.jpg" alt="淨水師傅到高雄透天頂樓安裝全戶軟水系統" loading="lazy"><figcaption>全戶軟水多在透天頂樓水塔端施工，找有經驗、能到府場勘的安裝團隊比較安心。</figcaption></figure>
<figure><img src="https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water/kh-softwater-rooftop-tank.webp" alt="高雄透天頂樓水塔的軟水管線配置" loading="lazy"><figcaption>高雄透天多在頂樓水塔端配置，安裝前要看水塔位置與管線走向。</figcaption></figure>
<ul>
  <li><strong>高雄透天</strong>：多半配在頂樓水塔進出水端，要留意水塔位置、樓層落差與加壓需求。</li>
  <li><strong>社區大樓</strong>：受限公共管線與空間，常見裝在自家進水處或改以廚下、局部軟水處理。</li>
  <li><strong>老公寓</strong>：先確認水壓與可施作的管線位置，必要時搭配加壓。</li>
</ul>
<figure><img src="https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water/kh-softwater-regen-salt.jpg" alt="離子交換式軟水系統的再生鹽添加槽" loading="lazy"><figcaption>傳統軟水靠再生鹽定期再生；選機型時先問清楚耗鹽、廢水與保養方式。</figcaption></figure>

<h2>高雄裝全戶軟水前，先想這幾件</h2>
<ol>
  <li><strong>先測水質</strong>：量一下家裡水的總硬度，才知道要不要裝、裝多大。</li>
  <li><strong>屋型與位置</strong>：透天水塔端 vs 大樓進水端，安裝方式和費用不同。</li>
  <li><strong>加鹽還是免鹽</strong>：傳統再生鹽機型耗鹽、有再生廢水；免鹽/免耗材機型單價高但省後續。</li>
  <li><strong>耗材與保養</strong>：樹脂或濾料多久換、鹽多久補、有沒有到府保養。</li>
  <li><strong>飲用另外規劃</strong>：軟水不生飲，飲用水另接 RO 或淨水。</li>
  <li><strong>報價要透明</strong>：機器、安裝、管線改接、後續保養分開列清楚，現場看過屋況再報。</li>
</ol>

<div class="gg-tip"><b>💡 租屋族與小資家庭</b><p>全戶軟水要固定安裝、動到管線，比較適合自有透天或長住。租屋或預算有限，可以先從蓮蓬頭除氯、廚下淨水這種「局部、可拆」的方案入手，之後再考慮全戶。</p></div>

<figure><img src="https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water/kh-softwater-water-test.jpg" alt="全戶軟水安裝前後的水質測試對比" loading="lazy"><figcaption>安裝前後可做水質測試對比，確認硬度真的有下降。</figcaption></figure>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">高雄的水真的比較硬嗎？一定要裝軟水？</div>
  <div class="gg-a">高雄自來水硬度普遍比北部高一些，水垢、白垢、家電結垢比較明顯。要不要裝看你的困擾程度與水質檢測結果，不是每家都必須，但在意水垢與家電壽命的家庭多半有感。</div>
  <div class="gg-q">全戶軟水和淨水器有什麼不同？</div>
  <div class="gg-a">軟水降低鈣鎂硬度、解決水垢；淨水/RO 是除氯、雜質、重金屬做飲用。兩者處理的東西不同，通常搭配：全戶軟水顧洗澡洗衣、廚下 RO 顧喝的水。</div>
  <div class="gg-q">軟水可以直接喝嗎？</div>
  <div class="gg-a">不建議。軟水是把水變軟、減少水垢，不等於過濾到可生飲。飲用水請另接 RO 或淨水設備。</div>
  <div class="gg-q">軟水一定要加鹽嗎？</div>
  <div class="gg-a">傳統離子交換機型要用再生鹽定期再生、也會產生再生廢水；市面上也有主打免鹽、免耗材的機型，單價較高但保養較省。下訂前問清楚耗材與廢水。</div>
  <div class="gg-q">透天和大樓裝法一樣嗎？</div>
  <div class="gg-a">不一樣。高雄透天多在頂樓水塔進出水端配置；社區大樓受公共管線限制，常改在自家進水處或做局部處理。安裝前要到現場看水塔位置與管線。</div>
  <div class="gg-q">全戶軟水行情大概多少？</div>
  <div class="gg-a">依機型、水量與屋況差異大，系統加安裝常見落在數萬元起跳，免鹽或進口機型更高；另有樹脂／濾料更換與鹽的後續費用。實際以現場場勘估價為準。</div>
</div>

<p>更多高雄在地居家服務，可到 <a href="https://www.gomag.com.tw/city/kaohsiung/home-service">高雄居家服務</a> 總覽。</p>
HTML;

$faqs = [
  ['高雄的水真的比較硬嗎？一定要裝軟水？','高雄自來水硬度普遍比北部高一些，水垢、白垢、家電結垢比較明顯。要不要裝看你的困擾程度與水質檢測結果，在意水垢與家電壽命的家庭多半有感。'],
  ['全戶軟水和淨水器有什麼不同？','軟水降低鈣鎂硬度、解決水垢；淨水/RO 是除氯、雜質、重金屬做飲用。兩者處理的東西不同，通常搭配使用。'],
  ['軟水可以直接喝嗎？','不建議。軟水是把水變軟、減少水垢，不等於過濾到可生飲，飲用水請另接 RO 或淨水設備。'],
  ['軟水一定要加鹽嗎？','傳統離子交換機型要用再生鹽再生、也有再生廢水；市面上也有免鹽、免耗材機型，單價較高但保養較省。'],
  ['透天和大樓裝法一樣嗎？','不一樣。高雄透天多在頂樓水塔端配置；社區大樓受公共管線限制，常改自家進水處或局部處理，安裝前要現場看。'],
  ['全戶軟水行情大概多少？','依機型、水量與屋況差異大，系統加安裝常見數萬元起跳，免鹽或進口更高，另有濾料/鹽的後續費用，以現場場勘估價為準。'],
];
$faqLd = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f) $faqLd['mainEntity'][] = ['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($faqLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$g = $db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
    VALUES (?,?,?,?, 'kaohsiung', 2, 'ruanshui,jingshuiqi', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
      service_slug=VALUES(service_slug),meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),
      cover_image=VALUES(cover_image),status='published'");
$g->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r = $db->query("SELECT id,LENGTH(body_html) bl,service_slug,status FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug}  body={$r['bl']}B  svc=[{$r['service_slug']}] status={$r['status']}\n";
echo "  gg-=".substr_count($body,'gg-')."  圖=".substr_count($body,'<img')."  FAQ=".count($faqs)."\n";
echo "\n驗證： https://www.gomag.com.tw/guide/{$slug}\n";
