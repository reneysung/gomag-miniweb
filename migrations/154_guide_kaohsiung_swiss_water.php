<?php
// ============================================================
// Migration 154 — 攻略《高雄為什麼需要裝瑞士水》(淨水器 cluster 第 2 篇，滴滴角度)
// ------------------------------------------------------------
// 滴滴主攻 Swiss Aqua 瑞士水抑垢系統 → 教育文：瑞士水是什麼、抑垢 vs 傳統軟水、
// 高雄哪些情況適合。用滴滴 /store 的高雄安裝照片；不提滴滴品牌、不放聯絡、不連客戶。
// service_slug=jingshuiqi,ruanshui、city=kaohsiung。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/154_guide_kaohsiung_swiss_water.php
// 冪等：slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$IMG = 'https://www.gomag.com.tw/uploads/guides/kaohsiung-swiss-water';

$slug = 'kaohsiung-swiss-water';
$title = '高雄為什麼很多人改裝「瑞士水」？抑垢原理、跟軟水差別與安裝一次看';
$excerpt = '高雄水偏硬、水垢惱人，除了傳統加鹽軟水，近年很多家庭改裝免插電、免耗材、免鹽桶的「瑞士水」抑垢系統。這篇講瑞士水是什麼、跟軟水差在哪、高雄透天大樓怎麼裝。';
$metaTitle = '高雄瑞士水是什麼？抑垢 vs 軟水差別、免插電免鹽桶、安裝情境｜店家好口碑';
$metaDesc = '高雄水質偏硬、水垢多。瑞士水（抑垢系統）是什麼、跟傳統離子交換軟水差在哪、要不要加鹽插電、能不能喝、透天大樓怎麼裝，一篇看懂。';
$cover = $IMG . '/sw-system.jpg';

$body = <<<HTML
<p class="gg-lead">高雄的水偏硬，浴室玻璃白垢、熱水器和水龍頭結垢是日常。想處理水垢，除了傳統要加鹽的軟水機，近年不少高雄家庭改裝「瑞士水」這類免插電、免耗材、免鹽桶的抑垢系統。這篇講瑞士水到底是什麼、跟傳統軟水差在哪、高雄哪些情況適合裝。</p>

<figure><img src="{$IMG}/sw-system.jpg" alt="高雄住宅前置過濾搭配瑞士水抑垢系統安裝實景" loading="lazy"><figcaption>瑞士水常搭配前置過濾一起裝，處理全家用水的水垢問題。</figcaption></figure>

<h2>高雄的水為什麼容易結垢</h2>
<p>水的軟硬看鈣、鎂含量。高雄自來水主要來自高屏溪與澄清湖系統，硬度普遍比北部高一些，鈣鎂多，長期就在玻璃、磁磚、熱水器、管線、蓮蓬頭上結成白垢。垢卡久了不只難看，也影響家電效率與壽命。</p>

<h2>瑞士水是什麼？重點在「抑垢」不是「軟化」</h2>
<p>市面上的瑞士水（例如 Swiss Aqua 這類系統）走的是「抑垢」路線。它不是把水裡的鈣鎂拿掉，而是改變鈣鎂在水中的結晶方式，讓它不容易結成硬垢、附著在管壁和家電上。因為原理不同，它的使用方式跟傳統軟水差很多：</p>
<div class="gg-cards">
  <div class="gg-card"><b>免插電</b><span>物理抑垢、不靠電，安裝位置更彈性。</span></div>
  <div class="gg-card"><b>免耗材、免加鹽</b><span>不用像傳統軟水定期買再生鹽、換樹脂。</span></div>
  <div class="gg-card"><b>免鹽桶、體積小</b><span>沒有大鹽桶，大樓、空間小的地方比較好裝。</span></div>
  <div class="gg-card"><b>保留礦物質</b><span>不走鈉離子交換，水的礦物質相對保留。</span></div>
</div>

<h2>瑞士水 vs 傳統軟水，差在哪</h2>
<p>兩種都在對付水垢，但作法完全不同，適合的家庭也不一樣。</p>
<table class="gg-table">
  <thead><tr><th>比較</th><th>瑞士水（抑垢）</th><th>傳統離子交換軟水</th></tr></thead>
  <tbody>
    <tr><td>原理</td><td>改變鈣鎂結晶、抑制結垢附著</td><td>用樹脂把鈣鎂換成鈉，真正軟化</td></tr>
    <tr><td>耗材</td><td>免加鹽、少耗材</td><td>要定期加再生鹽、換樹脂</td></tr>
    <tr><td>用電／廢水</td><td>免插電、無再生廢水</td><td>需再生、會產生再生廢水</td></tr>
    <tr><td>體積</td><td>小，好裝</td><td>較大（含鹽桶）</td></tr>
    <tr><td>手感</td><td>接近原水、保留礦物質</td><td>軟水偏滑、鈉略增</td></tr>
  </tbody>
</table>

<h2>高雄哪些情況適合瑞士水</h2>
<div class="gg-cards">
  <div class="gg-card"><b>大樓、空間有限</b><span>沒地方放鹽桶、不想處理再生廢水的家庭，免鹽免耗材好安排。</span></div>
  <div class="gg-card"><b>透天全戶</b><span>裝在水塔進水或浴室上方，讓全家用水都經過抑垢處理。</span></div>
  <div class="gg-card"><b>不想常維護</b><span>不用記得加鹽、換樹脂，適合忙碌或懶得保養的家庭。</span></div>
  <div class="gg-card"><b>在意加鹽、用電</b><span>不想家裡多一桶鹽、多一個插電設備的人。</span></div>
</div>
<figure><img src="{$IMG}/sw-guhshan.jpg" alt="高雄透天浴室上方安裝瑞士水抑垢系統" loading="lazy"><figcaption>高雄透天常見裝在浴室上方或水塔端，安裝前先看空間與管線。</figcaption></figure>

<h2>瑞士水要搭配什麼一起用</h2>
<p>瑞士水主要顧「水垢」，實務上常搭配前置過濾（例如 20 吋大胖）一起裝，先濾掉泥沙、餘氯，再抑垢，整體用水品質更好。要注意的是：抑垢系統處理的是用水，飲用水還是建議另接 RO 或淨水設備。</p>
<figure><img src="{$IMG}/sw-sanmin.jpg" alt="高雄大樓住宅瑞士水淨水系統安裝" loading="lazy"><figcaption>大樓住宅也能裝，免鹽桶、體積小是它在都會區受歡迎的原因之一。</figcaption></figure>

<div class="gg-warn"><b>⚠️ 抑垢不等於軟化到零、也不能生飲</b><p>瑞士水是「抑制結垢」，不是把硬度降到零；效果也會因原水水質而有差。它解決的是用水的水垢問題，喝的水請另外走 RO 或淨水。</p></div>

<div class="gg-tip"><b>💡 高雄大樓、租屋族</b><p>大樓空間小、又不想處理鹽桶跟再生廢水的話，瑞士水這種免鹽、免耗材、免插電的抑垢系統相對省事。裝前先請業者評估水質與安裝位置。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">瑞士水跟軟水一樣嗎？</div>
  <div class="gg-a">不一樣。傳統軟水用樹脂把鈣鎂換掉、真正軟化，要加鹽再生；瑞士水是抑垢，改變鈣鎂結晶讓它不易結垢，免加鹽、免耗材。作法和維護方式都不同。</div>
  <div class="gg-q">瑞士水要加鹽、要插電嗎？</div>
  <div class="gg-a">這類抑垢系統多半免加鹽、免插電、也沒有再生廢水，體積小、維護少，這是它跟傳統軟水最大的差別之一。</div>
  <div class="gg-q">瑞士水的水可以直接喝嗎？</div>
  <div class="gg-a">不建議把它當飲用水處理。抑垢系統顧的是全家用水的水垢，飲用水請另接 RO 或淨水設備。</div>
  <div class="gg-q">高雄大樓可以裝嗎？</div>
  <div class="gg-a">可以。因為免鹽桶、體積小，大樓和空間有限的住家反而比傳統軟水好安排，安裝前請業者看水質與位置。</div>
  <div class="gg-q">瑞士水要換濾心嗎？</div>
  <div class="gg-a">抑垢主體多半少耗材，但通常會搭配前置過濾，前置的濾材仍需依水質定期更換，下訂前問清楚保養方式。</div>
  <div class="gg-q">瑞士水跟前置過濾差在哪？</div>
  <div class="gg-a">前置過濾除泥沙、餘氯等雜質；瑞士水抑制水垢。兩者處理的東西不同，常搭配使用，用水品質更完整。</div>
</div>

<p>更多高雄在地居家服務，可到 <a href="https://www.gomag.com.tw/city/kaohsiung/home-service">高雄居家服務</a> 總覽；水垢與軟水的比較，另見 <a href="https://www.gomag.com.tw/guide/kaohsiung-whole-house-soft-water">為什麼高雄需要全戶軟水</a>。</p>
HTML;

$faqs = [
 ['瑞士水跟軟水一樣嗎？','不一樣。傳統軟水用樹脂把鈣鎂換掉、真正軟化，要加鹽再生；瑞士水是抑垢，改變鈣鎂結晶讓它不易結垢，免加鹽、免耗材。'],
 ['瑞士水要加鹽、要插電嗎？','這類抑垢系統多半免加鹽、免插電、也沒有再生廢水，體積小、維護少。'],
 ['瑞士水的水可以直接喝嗎？','不建議當飲用水。抑垢系統顧的是全家用水的水垢，飲用水請另接 RO 或淨水設備。'],
 ['高雄大樓可以裝瑞士水嗎？','可以。免鹽桶、體積小，大樓和空間有限的住家比傳統軟水好安排，安裝前請業者看水質與位置。'],
 ['瑞士水要換濾心嗎？','抑垢主體少耗材，但通常搭配前置過濾，前置濾材仍需依水質定期更換。'],
 ['瑞士水跟前置過濾差在哪？','前置過濾除泥沙、餘氯；瑞士水抑制水垢。兩者處理的東西不同，常搭配使用。'],
];
$faqLd = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f) $faqLd['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($faqLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$g = $db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
    VALUES (?,?,?,?, 'kaohsiung', 2, 'jingshuiqi,ruanshui', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),service_slug=VALUES(service_slug),
      meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),cover_image=VALUES(cover_image),status='published'");
$g->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug} body={$r['bl']}B gg-=".substr_count($body,'gg-')." 圖=".substr_count($body,'<img')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
