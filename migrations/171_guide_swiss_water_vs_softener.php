<?php
// ============================================================
// Migration 171 — 新攻略《瑞士水 vs 離子交換軟水怎麼選》(滴滴淨水，比較型)
// ------------------------------------------------------------
// Reney 選「另寫全新一篇、瑞士水 vs 離子交換軟水比較」角度（避開 #94 高雄瑞士水安裝競食）。
// 誠實核心：瑞士水=免鹽「抑垢」(硬度不變、防結垢)；離子交換=真「軟化」(降硬度、要加鹽/廢水)。不誇大。
// 真圖：瑞士水側=滴滴真實照(swiss-vs-softener/)、軟水側=蕎藝離子交換控制器(kaohsiung-soft-water/)。
// cat=2、svc=[ruanshui,jingshuiqi]、city=kaohsiung（與 #91/#94 一致；不同查詢詞不競食）。
// 教育文：不放客戶聯絡/不連客戶頁。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/171_guide_swiss_water_vs_softener.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$D = 'https://www.gomag.com.tw/uploads/guides/swiss-vs-softener';

$slug='swiss-water-vs-softener';
$title='瑞士水 vs 離子交換軟水怎麼選？免鹽抑垢與加鹽軟化差在哪一次看懂';
$excerpt='瑞士水（免鹽抑垢）和傳統離子交換軟水（加鹽軟化）常被混為一談，其實原理完全不同——一個是「防水垢附著、硬度不變」，一個是「真的降低硬度但要加鹽、有廢水」。這篇用比較表、真實安裝照與 FAQ，帶你搞懂差別、依需求選對。';
$metaTitle='瑞士水 vs 離子交換軟水怎麼選｜免鹽抑垢 vs 加鹽軟化差別一次看';
$metaDesc='瑞士水和軟水差在哪？免鹽抑垢（硬度不變、防結垢）與離子交換軟水（真軟化、要加鹽與廢水）原理、體積、保養、洗感、適合對象完整比較，附真實安裝照，教你依需求選對不踩雷。';
$cover=$D.'/didi-swisswater-system.jpg';

$body = <<<HTML
<style>
.g-guide-body figure{margin:2.2rem 0 2.8rem;}
.g-guide-body figure img{border-radius:12px;display:block;width:100%;}
.g-guide-body figcaption{margin-top:12px;padding:0 4px;font-size:.9rem;line-height:1.75;color:#8a8a8a;text-align:center;}
</style>
<p class="gg-lead">想解決家裡的水垢困擾，上網一查會看到「軟水機」「瑞士水」「免鹽軟水」「電子除垢」一堆名詞，價格差好幾倍、講的效果也不太一樣。其中最常被搞混的，就是<strong>傳統離子交換軟水</strong>和<strong>瑞士水這類免鹽抑垢系統</strong>——兩個常被一起叫「軟水」，但原理其實完全不同：一個是「真的把水變軟（降低硬度）」，一個是「讓水垢不附著（硬度其實沒變）」。搞懂差別，才知道自己該裝哪一種。</p>

<h2>先分清楚：軟化 vs 抑垢，是兩件事</h2>
<p>「水垢」來自水中的鈣、鎂（硬度）。要處理水垢有兩種完全不同的路線：</p>
<div class="gg-cards">
  <div class="gg-card"><b>離子交換軟水（軟化）</b><span>用樹脂把水中的鈣鎂「換掉」，總硬度真的下降，這是嚴格定義的「軟水」。代價是要用再生鹽定期再生、會排再生廢水、體積也較大。</span></div>
  <div class="gg-card"><b>瑞士水／免鹽抑垢</b><span>不移除鈣鎂，而是改變它的結晶型態，讓水垢「不容易附著結塊」。硬度數值幾乎不變，但看得到的白垢、結垢明顯減少。免鹽、免電、免廢水、體積小。</span></div>
</div>
<div class="gg-warn"><b>⚠️ 關鍵觀念：抑垢不等於軟化</b><p>市面上「免鹽軟水」「電子除垢」「磁化」多半屬於「抑垢」——它讓水垢不附著，但<strong>水的硬度數值其實沒有下降</strong>。只有離子交換（加鹽再生）是真的把硬度降下來的「軟化」。下訂前一定要問清楚：這台是「降硬度（軟化）」還是「防結垢（抑垢）」，兩者訴求不同、別被「軟水」兩個字混淆。</p></div>

<figure><img src="{$D}/didi-swisswater-piping.jpg" alt="免鹽抑垢型全戶淨水系統的不鏽鋼管線完工實景" loading="lazy"><figcaption>免鹽抑垢型系統無鹽桶、管線完工後體積小，都會區與大樓也好安置。</figcaption></figure>

<h2>瑞士水 vs 離子交換軟水：一張表看懂</h2>
<table class="gg-table">
  <thead><tr><th>比較項目</th><th>離子交換軟水（加鹽）</th><th>瑞士水／免鹽抑垢</th></tr></thead>
  <tbody>
    <tr><td>原理</td><td>樹脂交換掉鈣鎂</td><td>改變結晶、防止附著</td></tr>
    <tr><td>硬度是否下降</td><td>是（真軟化）</td><td>否（硬度不變、抑制結垢）</td></tr>
    <tr><td>要不要加鹽</td><td>要再生鹽，定期補</td><td>免鹽</td></tr>
    <tr><td>再生廢水</td><td>有（再生時排廢水）</td><td>無</td></tr>
    <tr><td>用電</td><td>部分機型需要</td><td>多半免電</td></tr>
    <tr><td>體積</td><td>較大（樹脂桶＋鹽桶）</td><td>小、好安置</td></tr>
    <tr><td>洗感</td><td>滑順感明顯</td><td>接近原水、保留礦物質</td></tr>
    <tr><td>保養</td><td>補鹽、樹脂更換</td><td>定期沖洗／更換濾芯</td></tr>
    <tr><td>適合</td><td>要真軟化、重洗感、空間夠</td><td>都會大樓、不想顧鹽與廢水</td></tr>
  </tbody>
</table>

<h2>離子交換軟水：真的降硬度，但要顧鹽與廢水</h2>
<p>離子交換是「軟水」的正統做法：水流過樹脂，鈣鎂離子被鈉離子換掉，出來的水總硬度確實下降，洗澡會有明顯的滑順感、肥皂好起泡。樹脂用久了要用<strong>再生鹽</strong>再生，這個過程會排出再生廢水；系統通常有樹脂桶加鹽桶，體積較大，需要固定補鹽與後續保養。適合空間夠、想要「真正降硬度」與滑順洗感的家庭。</p>

<h2>瑞士水／免鹽抑垢：免鹽免廢水，硬度不變但防結垢</h2>
<p>瑞士水這類免鹽抑垢系統，走的是另一條路：不把鈣鎂拿掉，而是讓它變成不易附著的結晶，水垢就不會結塊卡在玻璃、花灑、家電上。它<strong>免鹽、免電、免再生廢水</strong>，體積小、好安置，都會區大樓也裝得下；保留了水中礦物質，洗感接近原水。要注意的是——它的訴求是「防結垢」，<strong>水的硬度數值不會下降</strong>，如果你要的是「洗起來滑滑的真軟水」，那要的是離子交換而不是抑垢。</p>
<figure><img src="{$D}/didi-swisswater-unit.jpg" alt="免鹽抑垢型瑞士水主機（不鏽鋼抑垢筒）特寫" loading="lazy"><figcaption>免鹽抑垢主機體積小、無鹽桶，常見裝在浴室上方或陽台進水端。</figcaption></figure>
<figure><img src="{$D}/didi-swisswater-install.jpg" alt="高雄住宅陽台的免鹽抑垢型全戶淨水系統安裝實景" loading="lazy"><figcaption>免電、免鹽、無再生廢水，是它在都會區與大樓受歡迎的原因。</figcaption></figure>
<figure><img src="{$D}/didi-swisswater-filter.jpg" alt="免鹽抑垢型系統搭配的前置過濾器濾芯更換" loading="lazy"><figcaption>免鹽抑垢不用加鹽，但仍需依機型定期沖洗或更換前置濾芯，保養相對單純。</figcaption></figure>

<h2>你該選哪一種？</h2>
<ol>
  <li><strong>想要真正降硬度、重視洗澡滑順感</strong> → 選離子交換軟水，接受補鹽與再生廢水。</li>
  <li><strong>空間小、不想顧鹽與廢水、想保留礦物質</strong> → 選瑞士水／免鹽抑垢，重點在防水垢。</li>
  <li><strong>都會區大樓、公共管線受限</strong> → 免鹽抑垢體積小、免廢水，通常比較好施作。</li>
  <li><strong>透天、有空間、追求極致軟水手感</strong> → 離子交換的滑順感是抑垢做不到的。</li>
  <li><strong>飲用水</strong> → 兩者都不是為了生飲；喝的水另接廚下 RO 或淨水設備。</li>
</ol>

<div class="gg-tip"><b>💡 高雄在地怎麼看</b><p>高雄自來水硬度普遍偏高，水垢、白垢困擾明顯。透天有空間、想要真軟化，離子交換是選項；都會大樓空間有限、不想處理鹽與廢水，免鹽抑垢型更好安置。無論哪一種，建議先量家裡水的總硬度、請廠商到府看空間與管線，再決定機型與大小。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">瑞士水和離子交換軟水最大的差別是什麼？</div>
  <div class="gg-a">離子交換是「軟化」——用樹脂把鈣鎂換掉、總硬度真的下降，但要加再生鹽、有再生廢水；瑞士水是「抑垢」——改變結晶讓水垢不附著，硬度數值不變，但免鹽、免電、免廢水、體積小。</div>
  <div class="gg-q">瑞士水能把水變軟嗎？</div>
  <div class="gg-a">嚴格講不能。瑞士水這類免鹽系統是「抑垢」，讓水垢不容易附著，水的硬度其實沒有下降。要「真軟化（降硬度）」得靠離子交換式軟水機。</div>
  <div class="gg-q">「免鹽軟水」是真的有效嗎？</div>
  <div class="gg-a">免鹽抑垢對「減少水垢附著」有幫助——玻璃、花灑、家電結垢會明顯改善；但它不會讓總硬度下降，也不會有離子交換那種明顯滑順洗感。看你要解決的是「水垢」還是「洗感」。</div>
  <div class="gg-q">家裡水垢困擾，我該選哪一種？</div>
  <div class="gg-a">只想解決水垢、空間小、不想顧鹽與廢水 → 選免鹽抑垢；想要真正降硬度與滑順洗感、空間也夠 → 選離子交換軟水。可先量水質、請廠商到府評估。</div>
  <div class="gg-q">瑞士水要保養嗎？要換濾芯或加鹽嗎？</div>
  <div class="gg-a">免鹽抑垢不用加鹽、沒有再生廢水，但仍需依機型定期沖洗或更換濾芯／濾材；離子交換則要定期補再生鹽、樹脂到期更換。下訂前問清楚保養方式與週期。</div>
  <div class="gg-q">高雄水偏硬，兩種都可以裝嗎？</div>
  <div class="gg-a">都可以，差在屋型與需求。高雄透天有空間、要真軟化可考慮離子交換；都會大樓空間有限、不想處理鹽與廢水，免鹽抑垢型較好安置。安裝前先看水塔／進水位置與管線。</div>
</div>

<p>想深入看單一做法，可延伸閱讀 <a href="https://www.gomag.com.tw/guide/kaohsiung-whole-house-soft-water">高雄全戶軟水（離子交換）怎麼裝</a> 與 <a href="https://www.gomag.com.tw/guide/kaohsiung-swiss-water">高雄瑞士水抑垢系統介紹</a>；更多高雄在地居家服務見 <a href="https://www.gomag.com.tw/city/kaohsiung/home-service">高雄居家服務</a> 總覽。</p>
HTML;

$faqs=[
 ['瑞士水和離子交換軟水最大的差別是什麼？','離子交換是軟化——用樹脂把鈣鎂換掉、總硬度真的下降，但要加再生鹽、有再生廢水；瑞士水是抑垢——改變結晶讓水垢不附著，硬度數值不變，但免鹽、免電、免廢水、體積小。'],
 ['瑞士水能把水變軟嗎？','嚴格講不能。瑞士水這類免鹽系統是抑垢，讓水垢不容易附著，水的硬度其實沒有下降。要真軟化（降硬度）得靠離子交換式軟水機。'],
 ['「免鹽軟水」是真的有效嗎？','免鹽抑垢對減少水垢附著有幫助，玻璃、花灑、家電結垢會明顯改善；但它不會讓總硬度下降，也沒有離子交換那種明顯滑順洗感。看你要解決的是水垢還是洗感。'],
 ['家裡水垢困擾，我該選哪一種？','只想解決水垢、空間小、不想顧鹽與廢水就選免鹽抑垢；想要真正降硬度與滑順洗感、空間也夠就選離子交換軟水。可先量水質、請廠商到府評估。'],
 ['瑞士水要保養嗎？要換濾芯或加鹽嗎？','免鹽抑垢不用加鹽、沒有再生廢水，但仍需依機型定期沖洗或更換濾芯／濾材；離子交換則要定期補再生鹽、樹脂到期更換。'],
 ['高雄水偏硬，兩種都可以裝嗎？','都可以，差在屋型與需求。高雄透天有空間、要真軟化可考慮離子交換；都會大樓空間有限、不想處理鹽與廢水，免鹽抑垢型較好安置。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$sql="INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'kaohsiung', 2, 'ruanshui,jingshuiqi', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=2,service_slug='ruanshui,jingshuiqi',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),
    cover_image=VALUES(cover_image),status='published'";
$db->prepare($sql)->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug} body={$r['bl']}B 純文字約=".mb_strlen(strip_tags($body))."字 圖=".substr_count($body,'<img')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
