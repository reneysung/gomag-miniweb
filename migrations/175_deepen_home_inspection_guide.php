<?php
// ============================================================
// Migration 175 — 深化既有驗屋知識文（B）
// ------------------------------------------------------------
// 對象：taiwan-home-inspection-guide-2026（已上線、已收錄）
//   原況 ~1620 字、純 HTML(ul/ol/h2)、無 gg- 元件、無 FAQPage JSON-LD。
// 做法（w-design 教訓＝厚度贏）：擴到 ~3000+ 字、轉 gg- 結構化元件、
//   補 FAQPage JSON-LD（w-design 也缺 FAQPage → 這是我們的贏面）。
// 對稿紀律：這是「已收錄頁」，只 UPDATE body_html + updated_at。
//   meta_title / meta_desc / excerpt / cover / category_id / city_slug /
//   service_slug 一律不動（既有的都好，改動越少對排名越安全）。
//   保留原有全部事實、行情數字（每坪 NT$150–500 三級）、常見瑕疵 TOP 8、
//   3 城在地考量與既有內鏈；只做「擴充 + 轉排版 + 補 FAQPage」，不改寫既有論述。
//
// 冪等：以 slug 定位 UPDATE。可重跑。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/175_deepen_home_inspection_guide.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'taiwan-home-inspection-guide-2026';

// FAQ（保留原 5 題原文，補到 6 題；gg-faq 與 FAQPage 共用）
$faqs = [
  ['驗屋要驗多久？',
   '30 坪標準方案約 2–3 小時；含管道內視鏡與甲醛檢測的高階方案 3–4 小時。建議屋主全程在場，邊驗邊了解屋況。'],
  ['建商說「公司有自己的驗收流程」，可以不請第三方嗎？',
   '建商自驗是球員兼裁判。第三方驗屋的價值在「獨立報告書」，初驗缺失單有照片、有編號，建商修繕與複驗都有依據。NT$1 萬上下保障數百萬的交易，值得。'],
  ['驗出問題但建商不修怎麼辦？',
   '預售屋依合約可要求修繕完成才交屋（尾款是談判籌碼，未點交前不要繳清）；協商不成可向消保官申訴或依相關法規處理。報告書就是證據。'],
  ['中古屋也需要驗屋嗎？',
   '需要，而且最好簽約前驗。中古屋的瑕疵（漏水、壁癌、海砂、輻射）比新成屋更隱蔽，驗屋報告可作為議價依據，也避免買到「現況交屋」的陷阱。'],
  ['驗屋和裝潢細清的順序是？',
   '先驗屋 → 建商修繕 → 複驗 → 交屋 → 裝修 → 裝潢細清 → 入住。細清放最後，把裝修粉塵一次清掉。'],
  ['自己驗和找專業驗屋，怎麼選？',
   '屋況單純、預算有限可先自驗基本項目；但牆內管線、防水層、磁磚空心率需要儀器才驗得出來，預售屋初驗尤其建議請第三方，缺失單有憑有據建商不能賴。'],
];

$ggFaq = "<div class=\"gg-faq\">\n";
foreach ($faqs as $qa) {
    $ggFaq .= '  <div class="gg-q">' . $qa[0] . "</div>\n";
    $ggFaq .= '  <div class="gg-a">' . $qa[1] . "</div>\n";
}
$ggFaq .= "</div>\n";

$faqLd = [
    '@context' => 'https://schema.org', '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($qa) => [
        '@type' => 'Question', 'name' => $qa[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
    ], $faqs),
];
$faqScript = '<script type="application/ld+json">'
    . json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    . '</script>';

$body = <<<HTML
<p class="gg-lead">交屋驗屋是買房流程裡最後、也最關鍵的把關：建商蓋好的房子有沒有照圖施工？水電管線通不通？防水有沒有做足？驗屋當下沒抓到的瑕疵，交屋後就變成自己的維修費。這篇整理驗屋的時機與流程、自驗 vs 專業驗屋、費用行情、常見瑕疵清單、驗屋當天怎麼配合、報告書怎麼看，以及 3 城在地考量。</p>

<h2 id="toc">📖 文章目錄</h2>
<ol>
  <li><a href="#when">驗屋時機與流程</a></li>
  <li><a href="#diy">自驗 vs 專業驗屋</a></li>
  <li><a href="#price">費用行情：每坪 NT\$150–500</a></li>
  <li><a href="#defects">常見瑕疵 TOP 8</a></li>
  <li><a href="#onsite">驗屋當天怎麼配合</a></li>
  <li><a href="#report">驗屋報告書怎麼看</a></li>
  <li><a href="#cities">3 城在地考量</a></li>
  <li><a href="#faq">常見問題 FAQ</a></li>
</ol>

<h2 id="when">驗屋時機與流程</h2>
<p>不同屋型的驗屋時機不一樣，共通原則是：在「還握有籌碼」的時候驗，也就是尾款付清、正式點交之前。</p>
<div class="gg-cards">
  <div class="gg-card"><b>🏢 預售屋</b><span>建商通知交屋前安排「初驗」→ 列缺失單 → 建商修繕 → 「複驗」確認修妥 → 才簽交屋。初驗到複驗約 2–4 週。</span></div>
  <div class="gg-card"><b>🔑 新成屋</b><span>簽約後、過戶交屋前驗屋；把缺失修繕寫進合約附件，修好再點交。</span></div>
  <div class="gg-card"><b>🏠 中古屋</b><span>簽約前驗屋最有利（可反映在價格上）；至少在交屋前驗，留存證據作為議價與日後依據。</span></div>
  <div class="gg-card"><b>🔧 裝修後驗收</b><span>對照估價單逐項驗，水電、防水、櫃體五金都要實測，確認做的跟報價一致。</span></div>
</div>
<div class="gg-tip"><b>💡 尾款是最好的籌碼</b><p>預售屋未完成點交前不要繳清尾款。缺失單上的項目修繕完成、複驗確認後再付，是最有效的談判籌碼。順序千萬別顛倒。</p></div>

<h2 id="diy">自驗 vs 專業驗屋</h2>
<p>兩者不是互斥，預算與屋況決定怎麼配。自驗抓得到表面問題，牆內與防水層還是得靠儀器。</p>
<div class="gg-cards">
  <div class="gg-card"><b>🧰 自驗帶這些</b><span>手機手電筒（側光看批土裂縫）、衛生紙（測地排水流）、小夜燈或插座測電筆（測插座通電）、彈珠或水平 App（測地板水平）、捲尺（核對尺寸）。</span></div>
  <div class="gg-card"><b>🔬 專業用這些</b><span>熱顯像儀（抓滲水與漏水熱區）、空心鎚（敲磁磚空心率）、水分計（壁癌前兆）、管道內視鏡（看排水管內部）、插座迴路檢測器（逐一驗接地與極性）。</span></div>
</div>
<p><strong>專業驗屋</strong>最大的價值是出具「獨立報告書」，對預售屋初驗特別值：缺失單有照片、有位置編號，建商修繕與複驗都有依據，不會口說無憑。</p>

<h2 id="price">費用行情</h2>
<p>專業驗屋多以「每坪」計價，方案差在儀器多寡與含不含複驗、報告書。全台常見三級行情如下：</p>
<table class="gg-table">
<thead><tr><th>方案分級</th><th>每坪行情</th><th>含哪些／適合誰</th></tr></thead>
<tbody>
<tr><td>基本方案</td><td>NT\$150–250/坪</td><td>目視＋基本儀器，30 坪約 NT\$5,000–7,500，屋況單純適用</td></tr>
<tr><td>標準方案</td><td>NT\$250–400/坪</td><td>含熱顯像、空心鎚全屋、報告書，30 坪約 NT\$8,000–12,000，主流選擇</td></tr>
<tr><td>高階方案</td><td>NT\$400–500+/坪</td><td>含複驗一次、管道內視鏡、甲醛檢測，高總價或精裝修宅適用</td></tr>
</tbody>
</table>
<div class="gg-warn"><b>⚠️ 簽約前先問清楚三件事</b><p>① 報告書是否含照片並標註位置；② 複驗是否另計、算一次還是無限次；③ 儀器清單有哪些，別只聽「全套」兩個字。這三點決定你付的錢值不值。</p></div>

<h2 id="defects">常見瑕疵 TOP 8</h2>
<p>這 8 項是驗屋最常抓到、也最影響日後居住的問題，自驗時可以先照這份清單看一輪。</p>
<ol>
  <li><strong>磁磚空心</strong>：空心率過高日後易膨拱爆裂，浴室、廚房尤其要敲。</li>
  <li><strong>窗框滲水</strong>：水密性不足，颱風天才現形，熱顯像可提前抓。</li>
  <li><strong>排水不通／逆流</strong>：陽台、浴室地排倒水實測，糞管接錯時有所聞。</li>
  <li><strong>插座無電／接地異常</strong>：迴路檢測器逐一插測。</li>
  <li><strong>門窗開闔不順</strong>：五金鬆動、框體變形。</li>
  <li><strong>天花板水痕</strong>：樓上浴室漏水的前兆。</li>
  <li><strong>油漆批土不平／裂縫</strong>：側光照最明顯。</li>
  <li><strong>建材與合約不符</strong>：對照建材表逐項核對品牌型號。</li>
</ol>
<div class="gg-tip"><b>💡 最貴的往往看不見</b><p>磁磚空心、窗框滲水、排水逆流這類問題，交屋當下不明顯，住進去才爆發，修起來要敲要挖最花錢。這也是專業儀器最能發揮價值的地方。</p></div>

<h2 id="onsite">驗屋當天怎麼配合</h2>
<p>驗屋不是把房子丟給師傅就好，屋主在場、配合到位，才驗得徹底。</p>
<ol>
  <li><strong>帶齊文件</strong>：買賣合約、建材設備表、平面圖、客變單（預售屋），現場逐項對照。</li>
  <li><strong>全程在場</strong>：邊驗邊聽師傅說明，了解問題在哪、嚴不嚴重，日後溝通才有底。</li>
  <li><strong>缺失當場拍照編號</strong>：每個缺失標位置、拍照，對照報告書，複驗時逐項確認。</li>
  <li><strong>缺失單入合約附件</strong>：白紙黑字寫明修繕項目與期限，避免口頭承諾跳票。</li>
  <li><strong>約好複驗</strong>：確認複驗時間與方式，修好再點交、再付尾款。</li>
</ol>

<h2 id="report">驗屋報告書怎麼看</h2>
<p>一份合格的驗屋報告書，通常把檢查項目分成三大類、逐項標示合格或不合格，並附照片與位置。</p>
<div class="gg-cards">
  <div class="gg-card"><b>建築裝修工程</b><span>天花板、牆面、地坪裝修、防水等細項。</span></div>
  <div class="gg-card"><b>機電消防</b><span>分電箱、插座、弱電箱、瓦斯、消防等細項。</span></div>
  <div class="gg-card"><b>衛浴廚房陽台設備</b><span>排水管、淋浴設備、馬桶、廚具等細項。</span></div>
</div>
<p>看報告書時重點看三件事：<strong>不合格項目有沒有照片與位置</strong>、<strong>問題的嚴重度分級</strong>、<strong>是否列出建議處理方式</strong>。報告書是你跟建商協商、要求修繕的最有力證據，收好別弄丟。</p>

<h2 id="cities">3 城在地考量</h2>
<h3>台中</h3>
<p>七期、北屯機捷特區、南屯文心路重劃區新建案交屋量大，預售屋客變多、驗屋要核對客變單；中部建案常見的驗屋爭點是窗框漏水與磁磚空心。</p>
<p><strong>台中在地驗屋與相關居家服務</strong>：<a href="/city/taichung/home-service/home-inspection">台中驗屋</a>｜<a href="/city/taichung/home-service/reno-detail">台中裝潢細清</a>｜<a href="/city/taichung/home-service">台中居家服務</a></p>

<h3>台南</h3>
<p>南科效應帶動善化、新市、永康、九份子重劃區交屋潮；台南夏季西曬與午後雷陣雨，外牆與窗框水密性是驗屋重點；新成屋與老屋翻修後驗收都常見。</p>
<p><strong>台南在地驗屋與相關居家服務</strong>：<a href="/city/tainan/home-service/home-inspection">台南驗屋</a>｜<a href="/city/tainan/home-service/reno-detail">台南裝潢細清</a>｜<a href="/city/tainan/home-service">台南居家服務</a></p>

<h3>高雄</h3>
<p>亞洲新灣區、橋頭科學園區、左營高鐵特區建案多；港都濕熱與近海鹽害，給排水、防水與金屬件鏽蝕是驗屋必查；中古屋交易的驗屋需求也在增加。</p>
<p><strong>高雄在地驗屋與相關居家服務</strong>：<a href="/city/kaohsiung/home-service/home-inspection">高雄驗屋</a>｜<a href="/city/kaohsiung/home-service/reno-detail">高雄裝潢細清</a>｜<a href="/city/kaohsiung/home-service">高雄居家服務</a></p>

<h2 id="faq">常見問題 FAQ</h2>
{$ggFaq}

<h2>結尾</h2>
<p>驗屋是買房最後一道保險：① 初驗要在交屋付尾款前、② 預算允許就請第三方專業驗屋、③ 缺失單入合約附件、④ 複驗確認才點交。想找在地驗屋團隊，可以從下方的城市頁開始，也可以延伸看老屋翻新與裝潢細清的相關攻略。</p>
<p><strong>相關攻略</strong>：<a href="/guide/taiwan-old-house-renovation-guide-2026">全台老屋翻新流程指南</a>｜<a href="/guide/taiwan-interior-design-guide-2026">全台室內設計指南</a></p>

{$faqScript}
HTML;

$chars = mb_strlen(preg_replace('/\s+/u', '', strip_tags($body)), 'UTF-8');

$stmt = $db->prepare("SELECT id, LENGTH(body_html) AS oldlen FROM guides WHERE slug = ?");
$stmt->execute([$slug]);
$row = $stmt->fetch();
if (!$row) { echo "⚠️ 查無 slug={$slug}，中止\n"; exit(1); }

// 只更新 body_html + updated_at；其餘欄位一律不動
$db->prepare("UPDATE guides SET body_html = ?, updated_at = NOW() WHERE id = ?")
   ->execute([$body, $row['id']]);

echo "✅ 已深化 {$slug}（id={$row['id']}）\n";
echo "   body_html: {$row['oldlen']} bytes → " . strlen($body) . " bytes\n";
echo "   內文純文字約 {$chars} 字\n";
echo "   只動 body_html + updated_at；meta/excerpt/cover/category/city/service_slug 未動。\n";
echo "   URL: https://www.gomag.com.tw/guide/{$slug}\n";
