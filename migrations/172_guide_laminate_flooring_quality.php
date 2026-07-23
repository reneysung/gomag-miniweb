<?php
// ============================================================
// Migration 172 — 新攻略《超耐磨木地板怎麼看品質》(為新客戶 Gerpros 德藝緻規劃)
// ------------------------------------------------------------
// 角度：AC 耐磨等級(EN 13329)、甲醛等級、基材結構、吸水膨脹、怎麼驗證正品。
//   → 與 #98 台南木地板推薦(店家清單)、#67 全台木地板指南(類型比較) 皆不同查詢詞，不競食。
// 知識全部採產業公開標準，不引用未經查證的客戶認證；照片取自客戶官網(Reney 授權)當實際鋪設情境，
//   alt 只描述地板/空間，不掛品牌宣稱。教育文，不放客戶聯絡資訊。
// cat=2 svc=flooring city=tainan。跑法：
//   HTTP_HOST=www.gomag.com.tw php migrations/172_guide_laminate_flooring_quality.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$L = 'https://www.gomag.com.tw/uploads/guides/laminate-flooring';

$slug='laminate-flooring-quality-guide';
$title='超耐磨木地板怎麼看品質？AC 耐磨等級、甲醛標準與檢測一次搞懂';
$excerpt='店家都說「我們的很耐磨、很環保」，但怎麼判斷？超耐磨木地板其實有客觀規格可以比：AC 耐磨等級、甲醛等級、HDF 基材密度、吸水膨脹率、鎖扣工法。這篇教你看懂規格表與檢測文件，挑板不再只憑感覺。';
$metaTitle='超耐磨木地板怎麼看品質｜AC 耐磨等級、甲醛標準、檢測怎麼看';
$metaDesc='超耐磨木地板怎麼挑不踩雷？AC1–AC6 耐磨等級對應什麼空間、甲醛 F☆☆☆☆/E0/E1 差在哪、HDF 基材與吸水膨脹率怎麼看、如何確認原廠正品與檢測文件，一篇看懂規格表。';
$cover=$L.'/laminate-floor-living.jpg';

$body = <<<HTML
<style>
.g-guide-body figure{margin:2.2rem 0 2.8rem;}
.g-guide-body figure img{border-radius:12px;display:block;width:100%;}
.g-guide-body figcaption{margin-top:12px;padding:0 4px;font-size:.9rem;line-height:1.75;color:#8a8a8a;text-align:center;}
</style>
<p class="gg-lead">走進地板門市，每一家都說自己的板「很耐磨、很環保、歐洲進口」。問題是——聽起來都一樣，怎麼分好壞？其實超耐磨木地板有一套<strong>可以攤在桌上比的客觀規格</strong>：耐磨等級、甲醛等級、基材密度、吸水膨脹率、鎖扣方式。看懂這幾項，你就不用靠「業務講得比較誠懇」來做決定。</p>

<h2>先搞懂：超耐磨木地板是什麼做的</h2>
<p>很多人以為「超耐磨」是某種特別的木頭，其實它是一種<strong>複合結構的板材</strong>，由上到下大致四層：</p>
<div class="gg-cards">
  <div class="gg-card"><b>① 耐磨層</b><span>表面透明保護層，通常含三氧化二鋁顆粒，決定「耐不耐刮、耐不耐磨」——也就是 AC 等級的來源。</span></div>
  <div class="gg-card"><b>② 裝飾層</b><span>印刷木紋紙。花色、紋理、同步壓紋的擬真度差別就在這一層。</span></div>
  <div class="gg-card"><b>③ HDF 基材</b><span>高密度纖維板，是整片板的骨架。密度越高越穩定、抗變形，也影響腳感與聲音。</span></div>
  <div class="gg-card"><b>④ 平衡／防潮層</b><span>底層平衡板材應力、阻隔地面濕氣，台灣潮濕氣候特別重要。</span></div>
</div>
<p>知道這四層，就知道為什麼同樣叫「超耐磨」，價格可以差一倍以上——差在耐磨層厚度、基材密度與環保等級。</p>

<figure><img src="{$L}/laminate-floor-living.jpg" alt="淺色超耐磨木地板鋪設於住宅客廳的實際效果" loading="lazy"><figcaption>大面積鋪設時，花色與紋理的重複感、拼接自然度，比單看小樣品明顯得多。</figcaption></figure>

<h2>AC 耐磨等級：家用該選幾級</h2>
<p>AC 等級來自歐洲標準 <strong>EN 13329</strong>，用實驗室磨轉數測出來，數字越大越耐磨。對應的使用空間大致如下：</p>
<table class="gg-table">
  <thead><tr><th>耐磨等級</th><th>對應使用強度</th><th>適合空間</th></tr></thead>
  <tbody>
    <tr><td>AC1</td><td>輕度住宅</td><td>臥室、更衣室</td></tr>
    <tr><td>AC2</td><td>一般住宅</td><td>一般起居空間</td></tr>
    <tr><td>AC3</td><td>高使用住宅／輕商用</td><td>客廳、走道、小型辦公</td></tr>
    <tr><td>AC4</td><td>一般商用</td><td>商辦、店面</td></tr>
    <tr><td>AC5</td><td>重度商用</td><td>高人流賣場</td></tr>
    <tr><td>AC6</td><td>極重度商用</td><td>展場、機場等</td></tr>
  </tbody>
</table>
<p>台灣一般住宅常見選 <strong>AC3～AC4</strong>；有養寵物、小孩、常拖拉椅子的家庭，往上選一級比較安心；店面、商空則從 AC5 起跳。要注意的是——<strong>「超耐磨」不是等級</strong>，是俗稱；問清楚實際 AC 等級，才是有意義的比較。</p>

<h2>甲醛與環保等級：認標示，也要看文件</h2>
<p>板材類建材最常被問的就是甲醛。常見標示由嚴到寬大致是 <strong>F☆☆☆☆（日本 JIS／JAS 最高等級）→ E0 → E1</strong>。台灣市場多以 E1 為基本門檻，在意的家庭（有小孩、孕婦、過敏體質）會往 F☆☆☆☆ 或 E0 找。</p>
<div class="gg-warn"><b>⚠️ 只講「低甲醛」等於沒講</b><p>「環保」「低甲醛」「符合國家標準」都不是等級。要問的是：<strong>具體是哪一級（F☆☆☆☆／E0／E1）、有沒有對應的檢測報告或原廠標示</strong>。正規品牌的型錄上會直接載明等級與適用標準，拿得出文件的才算數。</p></div>

<figure><img src="{$L}/laminate-floor-space.jpg" alt="超耐磨木地板搭配系統櫃的住宅整體空間" loading="lazy"><figcaption>地板花色要跟櫃體、門片一起看；同色系裡的深淺與紋理差異，現場對照最準。</figcaption></figure>

<h2>基材密度與吸水膨脹率：台灣氣候的重點</h2>
<p>這兩項最少人問，卻最影響「用幾年後會不會出事」。<strong>HDF 基材密度</strong>越高，板越紮實、踩起來越安靜、不容易變形；<strong>吸水厚度膨脹率</strong>（同樣是 EN 13329 的檢測項目）越低，代表遇潮時膨脹越小。</p>
<p>台灣潮濕、夏季悶熱，浴室外、後陽台旁、一樓臨地面的空間更容易受潮。挑板時把這兩個數字問出來、跟不同品牌對比，比聽「我們的很防潮」實在得多。</p>

<h2>鎖扣與工法：好不好修，差很多</h2>
<p>超耐磨木地板多採<strong>卡扣式（浮動式、免膠）</strong>：一片片卡合、不打膠黏死，日後單片損壞可以抽換、搬家甚至能拆走再鋪。鎖扣的精度也影響拼接後的縫隙與踩踏聲響。若店家建議打膠平鋪，要問清楚原因與日後維修方式。</p>

<figure><img src="{$L}/laminate-floor-texture.jpg" alt="住宅客廳的超耐磨木地板紋理與整體搭配" loading="lazy"><figcaption>光線會改變木地板的視覺色溫；同一款板在自然光與燈光下差很多。</figcaption></figure>

<h2>怎麼確認你買到的是「原廠正品」</h2>
<ol>
  <li><strong>要規格表，不要口頭形容</strong>：AC 等級、甲醛等級、基材密度、吸水膨脹率，正規品牌型錄都寫得出來。</li>
  <li><strong>看檢測與標準</strong>：歐系板常見以 EN 13329 等標準檢測，另有第三方檢測報告可佐證。</li>
  <li><strong>問來源</strong>：是原廠代理進口，還是來源不明的平行輸入？代理商通常能提供原廠文件與保固窗口。</li>
  <li><strong>包裝與批號</strong>：整箱包裝上的品牌、產地、批號是最基本的辨識。</li>
  <li><strong>保固範圍</strong>：保幾年、保什麼（耐磨？受潮？），白紙黑字寫進合約。</li>
</ol>

<div class="gg-tip"><b>💡 一定要看「大板」再決定花色</b><p>型錄和 A4 小樣看不出整片鋪起來的樣子——木紋的重複週期、拼接後的視覺、深淺在大面積下的效果，都跟小樣差很多。建議到有<strong>實體大板展示</strong>的門市現場看，並且把樣板拿到窗邊、也放到燈下各看一次。台南門市多集中在市區與交流道周邊，出發前先預約，通常能請對方先把候選花色備好。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">超耐磨木地板的 AC 等級是什麼？家用要選幾級？</div>
  <div class="gg-a">AC 是歐洲標準 EN 13329 的耐磨等級，數字越大越耐磨。台灣一般住宅常見 AC3～AC4；有小孩、寵物或常移動家具的家庭可往上一級，店面商空多從 AC5 起。</div>
  <div class="gg-q">甲醛等級 F☆☆☆☆、E0、E1 差在哪？</div>
  <div class="gg-a">由嚴到寬大致是 F☆☆☆☆（日本 JIS／JAS 最高等級）→ E0 → E1。台灣市場多以 E1 為基本門檻；在意空氣品質的家庭會挑 F☆☆☆☆ 或 E0。重點是要有明確等級標示與可佐證的文件，不是只說「低甲醛」。</div>
  <div class="gg-q">超耐磨木地板是實木做的嗎？</div>
  <div class="gg-a">不是。它是複合結構板材——表面耐磨層、印刷木紋裝飾層、HDF 高密度基材、底層平衡防潮層。優點是耐磨好清、花色選擇多、價格親民；想要真實木觸感要看海島型或實木地板。</div>
  <div class="gg-q">台灣這麼潮濕，超耐磨木地板會膨脹嗎？</div>
  <div class="gg-a">會受潮，程度看基材密度與吸水厚度膨脹率，以及底層防潮處理與現場施工。挑板時把這兩個數字問出來比較，浴室外、後陽台、一樓臨地面的空間更要留意。</div>
  <div class="gg-q">怎麼確認買到的是原廠正品？</div>
  <div class="gg-a">要原廠型錄與規格表、檢測報告，確認是代理進口還是來源不明的平行輸入，並看整箱包裝上的品牌、產地與批號，保固範圍也要寫進合約。</div>
  <div class="gg-q">選花色為什麼一定要看大板？</div>
  <div class="gg-a">小樣看不出木紋的重複週期與大面積鋪設後的視覺；光線也會改變色溫。到有實體大板展示的門市現場看，並在自然光與燈光下各看一次，最不會後悔。</div>
</div>

<p>想看台南在地的木地板／建材商家，可到 <a href="https://www.gomag.com.tw/city/tainan/home-service/flooring"><strong>台南木地板</strong></a> 專頁；想比較不同地板類型（超耐磨／海島型／實木／SPC），看 <a href="https://www.gomag.com.tw/guide/tainan-flooring-recommend">台南木地板推薦與行情</a> 與 <a href="https://www.gomag.com.tw/guide/taiwan-flooring-guide-2026">全台木地板挑選完整指南</a>。</p>
HTML;

$faqs=[
 ['超耐磨木地板的 AC 等級是什麼？家用要選幾級？','AC 是歐洲標準 EN 13329 的耐磨等級，數字越大越耐磨。台灣一般住宅常見 AC3～AC4；有小孩、寵物或常移動家具的家庭可往上一級，店面商空多從 AC5 起。'],
 ['甲醛等級 F☆☆☆☆、E0、E1 差在哪？','由嚴到寬大致是 F☆☆☆☆（日本 JIS／JAS 最高等級）、E0、E1。台灣市場多以 E1 為基本門檻；在意空氣品質的家庭會挑 F☆☆☆☆ 或 E0。重點是要有明確等級標示與可佐證文件。'],
 ['超耐磨木地板是實木做的嗎？','不是。它是複合結構板材：表面耐磨層、印刷木紋裝飾層、HDF 高密度基材、底層平衡防潮層。耐磨好清、花色多、價格親民；想要實木觸感要看海島型或實木地板。'],
 ['台灣這麼潮濕，超耐磨木地板會膨脹嗎？','會受潮，程度看基材密度與吸水厚度膨脹率，以及底層防潮處理與施工。浴室外、後陽台、一樓臨地面的空間更要留意。'],
 ['怎麼確認買到的是原廠正品？','要原廠型錄與規格表、檢測報告，確認是代理進口或來源不明的平行輸入，並看整箱包裝上的品牌、產地與批號，保固範圍寫進合約。'],
 ['選花色為什麼一定要看大板？','小樣看不出木紋重複週期與大面積鋪設後的視覺，光線也會改變色溫。到有實體大板展示的門市現場看，並在自然光與燈光下各看一次。'],
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
echo "✅ #{$r['id']} {$slug} body={$r['bl']}B 純文字約=".mb_strlen(strip_tags($body))."字 圖=".substr_count($body,'<img')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
