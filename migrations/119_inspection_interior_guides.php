<?php
// ============================================================
// Migration 119 — 階段 2：驗屋 + 室內設計通用指南 + 修 113 樞紐壞鏈
// ------------------------------------------------------------
// A. 修 113 的 bug：tainan/kh home-service 樞紐 intro 內鏈用錯 slug
//    - handover-inspection → home-inspection（實際 slug）
//    - paint-waterproof    → painting（實際 slug）
//    - system-cabinet      → 無此 geo row 會 404 → 換成 flooring（兩城都有）
//    - meta_title 的「系統櫃」一併換成「木地板」
// B. 寫《全台交屋驗屋指南 2026》通用 guide 內鏈 3 城 home-inspection
// C. 寫《全台室內設計指南 2026》通用 guide 內鏈 3 城 interior-design
// D. 2 篇既有 city 驗屋 guide append 對應子服務內鏈
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/119_inspection_interior_guides.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ════════════════════════════════════════════
// A. 修 113 樞紐頁壞鏈
// ════════════════════════════════════════════
echo "═══ A. 修 113 樞紐頁壞鏈 ═══\n";
foreach (['tainan', 'kaohsiung'] as $c) {
    $row = $db->prepare("SELECT intro_html, meta_title FROM geo_category_pages WHERE city_slug=? AND category_id=2 AND service_slug=''");
    $row->execute([$c]);
    $r = $row->fetch(PDO::FETCH_ASSOC);
    if (!$r) { echo "  ❌ {$c} 樞紐 row 不存在\n"; continue; }

    $intro = $r['intro_html'];
    $intro = str_replace("/city/{$c}/home-service/handover-inspection", "/city/{$c}/home-service/home-inspection", $intro);
    $intro = str_replace("/city/{$c}/home-service/paint-waterproof", "/city/{$c}/home-service/painting", $intro);
    // system-cabinet 無 geo row 會 404 → 換成 flooring（連結與錨點文字一起換）
    $cn = $c === 'tainan' ? '台南' : '高雄';
    $intro = str_replace(
        "<a href=\"/city/{$c}/home-service/system-cabinet\">{$cn}系統櫃</a>",
        "<a href=\"/city/{$c}/home-service/flooring\">{$cn}木地板</a>",
        $intro);
    $metaTitle = str_replace('系統櫃', '木地板', $r['meta_title']);

    $db->prepare("UPDATE geo_category_pages SET intro_html=?, meta_title=? WHERE city_slug=? AND category_id=2 AND service_slug=''")
       ->execute([$intro, $metaTitle, $c]);

    // 驗證修補後 slug 清單
    preg_match_all("#/city/{$c}/home-service/([a-z-]+)#", $intro, $m);
    echo "  ✅ {$c} 樞紐 intro 內鏈 slugs: " . implode(', ', array_unique($m[1])) . "\n";
}

// ════════════════════════════════════════════
// B. 全台交屋驗屋指南
// ════════════════════════════════════════════
echo "\n═══ B. 通用 guide《全台交屋驗屋指南 2026》═══\n";

$inspCities = [
    'taichung' => ['name'=>'台中', 'angle'=>'七期、北屯機捷特區、南屯文心路重劃區新建案交屋量大，預售屋客變多、驗屋要核對客變單；中部建案常見驗屋爭點是窗框漏水與磁磚空心。'],
    'tainan'   => ['name'=>'台南', 'angle'=>'南科效應帶動善化、新市、永康、九份子重劃區交屋潮；台南夏季西曬與午後雷陣雨，外牆與窗框水密性是驗屋重點；新成屋與老屋翻修後驗收都常見。'],
    'kaohsiung'=> ['name'=>'高雄', 'angle'=>'亞洲新灣區、橋頭科學園區、左營高鐵特區建案多；港都濕熱與近海鹽害，給排水、防水與金屬件鏽蝕是驗屋必查；中古屋交易驗屋需求也在增加。'],
];

$slug1 = 'taiwan-home-inspection-guide-2026';
$body  = "<p>交屋驗屋是買房流程裡最後、也最關鍵的把關：建商蓋好的房子有沒有照圖施工？水電管線通不通？防水有沒有做足？驗屋當下沒抓到的瑕疵，交屋後就變成自己的維修費。這篇整理 <strong>驗屋時機與流程</strong>、<strong>自驗 vs 專業驗屋</strong>、<strong>費用行情</strong>、<strong>常見瑕疵清單</strong>與 <strong>3 城在地考量</strong>。</p>\n\n";

$body .= "<h2 id=\"toc\">📖 文章目錄</h2>\n<ol>\n";
$body .= "  <li><a href=\"#when\">驗屋時機與流程</a></li>\n";
$body .= "  <li><a href=\"#diy\">自驗 vs 專業驗屋</a></li>\n";
$body .= "  <li><a href=\"#price\">費用行情：每坪 NT\$150–500</a></li>\n";
$body .= "  <li><a href=\"#defects\">常見瑕疵 TOP 8</a></li>\n";
$body .= "  <li><a href=\"#cities\">3 城在地考量</a></li>\n";
$body .= "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"when\">驗屋時機與流程</h2>\n<ul>\n";
$body .= "  <li><strong>預售屋</strong>：建商通知交屋前安排「初驗」→ 列缺失單 → 建商修繕 → 「複驗」確認修妥 → 才簽交屋。初驗到複驗約 2–4 週。</li>\n";
$body .= "  <li><strong>新成屋</strong>：簽約後、過戶交屋前驗屋；缺失修繕入合約附件。</li>\n";
$body .= "  <li><strong>中古屋</strong>：簽約前驗屋最有利（可反映價格）；至少在交屋前驗，留存證據。</li>\n";
$body .= "  <li><strong>裝修後驗收</strong>：對照估價單逐項驗，水電、防水、櫃體五金都要實測。</li>\n";
$body .= "</ul>\n\n";

$body .= "<h2 id=\"diy\">自驗 vs 專業驗屋</h2>\n";
$body .= "<p><strong>自驗</strong>適合預算有限、屋況單純：帶手機手電筒、衛生紙（測排水）、小夜燈（測插座）、彈珠（測地板水平）可做基本檢查。但牆內管線、防水層、磁磚空心率需要儀器。</p>\n";
$body .= "<p><strong>專業驗屋</strong>用熱顯像儀（抓滲水）、空心鎚（磁磚空心）、水分計（壁癌前兆）、管道內視鏡（排水管）、插座迴路檢測器，並出具報告書——對預售屋初驗特別值，缺失單有憑有據建商不能賴。</p>\n\n";

$body .= "<h2 id=\"price\">費用行情</h2>\n<ul>\n";
$body .= "  <li><strong>NT\$150–250/坪</strong>：基本方案（目視 + 基本儀器），30 坪約 NT\$5,000–7,500。</li>\n";
$body .= "  <li><strong>NT\$250–400/坪</strong>：標準方案（含熱顯像、空心鎚全屋、報告書），30 坪約 NT\$8,000–12,000，主流選擇。</li>\n";
$body .= "  <li><strong>NT\$400–500+/坪</strong>：高階方案（含複驗一次、管道內視鏡、甲醛檢測），高總價或精裝修宅適用。</li>\n";
$body .= "</ul>\n";
$body .= "<p><strong>問清楚</strong>：報告書是否含照片標註位置、複驗是否另計、儀器清單有哪些。</p>\n\n";

$body .= "<h2 id=\"defects\">常見瑕疵 TOP 8</h2>\n<ol>\n";
$body .= "  <li><strong>磁磚空心</strong>：空心率過高日後易膨拱爆裂，浴室廚房尤其要敲。</li>\n";
$body .= "  <li><strong>窗框滲水</strong>：水密性不足，颱風天才現形，熱顯像可提前抓。</li>\n";
$body .= "  <li><strong>排水不通／逆流</strong>：陽台、浴室地排倒水實測，糞管接錯時有所聞。</li>\n";
$body .= "  <li><strong>插座無電／接地異常</strong>：迴路檢測器逐一插測。</li>\n";
$body .= "  <li><strong>門窗開闔不順</strong>：五金鬆動、框體變形。</li>\n";
$body .= "  <li><strong>天花板水痕</strong>：樓上浴室漏水前兆。</li>\n";
$body .= "  <li><strong>油漆批土不平／裂縫</strong>：側光照最明顯。</li>\n";
$body .= "  <li><strong>建材與合約不符</strong>：對照建材表逐項核對品牌型號。</li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"cities\">3 城在地考量</h2>\n";
foreach ($inspCities as $citySlug => $info) {
    $body .= "<h3>{$info['name']}</h3>\n";
    $body .= "<p>{$info['angle']}</p>\n";
    $body .= "<p><strong>{$info['name']}在地驗屋與相關居家服務</strong>："
        . "<a href=\"/city/{$citySlug}/home-service/home-inspection\">{$info['name']}驗屋</a>｜"
        . "<a href=\"/city/{$citySlug}/home-service/reno-detail\">{$info['name']}裝潢細清</a>｜"
        . "<a href=\"/city/{$citySlug}/home-service\">{$info['name']}居家服務</a></p>\n\n";
}

$body .= "<h2 id=\"faq\">常見問題 FAQ</h2>\n";
$body .= "<h3>Q1：驗屋要驗多久？</h3>\n<p>30 坪標準方案約 2–3 小時；含管道內視鏡與甲醛檢測的高階方案 3–4 小時。建議屋主全程在場，邊驗邊了解屋況。</p>\n";
$body .= "<h3>Q2：建商說「公司有自己的驗收流程」可以不請第三方嗎？</h3>\n<p>建商自驗是球員兼裁判。第三方驗屋的價值在「獨立報告書」，初驗缺失單有照片有編號，建商修繕與複驗都有依據。NT\$1 萬上下保障數百萬交易，值得。</p>\n";
$body .= "<h3>Q3：驗出問題建商不修怎麼辦？</h3>\n<p>預售屋依合約可要求修繕完成才交屋（尾款是談判籌碼，未點交前不要繳清）；協商不成可向消保官申訴或依公平交易法處理。報告書就是證據。</p>\n";
$body .= "<h3>Q4：中古屋也需要驗屋嗎？</h3>\n<p>需要，而且最好簽約前驗。中古屋瑕疵（漏水、壁癌、海砂、輻射）比新成屋更隱蔽，驗屋報告可作為議價依據，也避免買到「現況交屋」陷阱。</p>\n";
$body .= "<h3>Q5：驗屋跟裝潢細清的順序？</h3>\n<p>先驗屋 → 建商修繕 → 複驗 → 交屋 → 裝修 → 裝潢細清 → 入住。細清放最後，把裝修粉塵一次清掉。</p>\n\n";

$body .= "<h2>結尾</h2>\n<p>驗屋是買房最後一道保險：① 初驗要在交屋付尾款前、② 預算允許就請第三方專業驗屋、③ 缺失單入合約附件、④ 複驗確認才點交。下方 3 城子分類頁可看在地驗屋團隊。</p>\n";

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, cover_image, status, published_at)
    VALUES (?, ?, ?, ?, NULL, 2, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug=NULL, category_id=VALUES(category_id),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
        cover_image=VALUES(cover_image), status='published', published_at=COALESCE(published_at, NOW())");
$gs->execute([
    $slug1,
    '全台交屋驗屋指南 2026：時機、流程、費用行情、常見瑕疵 TOP 8',
    '交屋驗屋怎麼做？預售屋初驗複驗流程、自驗 vs 專業驗屋差在哪、每坪 NT$150–500 行情怎麼分、磁磚空心窗框滲水等常見瑕疵 TOP 8，台中、台南、高雄交屋潮必看。',
    $body,
    '全台交屋驗屋指南 2026｜流程費用行情常見瑕疵｜店家好口碑',
    '交屋驗屋怎麼做？預售屋初驗複驗流程、自驗 vs 專業驗屋、每坪 NT$150–500 費用行情、常見瑕疵 TOP 8（磁磚空心/窗框滲水/排水逆流），台中七期、台南南科、高雄亞灣交屋潮必看。',
    'https://www.gomag.com.tw/uploads/guides/covers/handover.svg',
]);
$r1 = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug='{$slug1}'")->fetch(PDO::FETCH_ASSOC);
echo "  ✅ {$slug1} id={$r1['id']} body={$r1['l']} bytes\n";

// ════════════════════════════════════════════
// C. 全台室內設計指南
// ════════════════════════════════════════════
echo "\n═══ C. 通用 guide《全台室內設計指南 2026》═══\n";

$idCities = [
    'taichung' => ['name'=>'台中', 'angle'=>'七期豪宅與北屯、南屯重劃區新成屋客變需求大；台中設計公司密度高、風格選擇多，從輕裝修到全室客製都有市場。'],
    'tainan'   => ['name'=>'台南', 'angle'=>'府城老屋翻新是台南室內設計最大特色——老透天、街屋改造需要懂結構與防潮的設計師；南科帶動的新成屋輕裝修需求也在成長。'],
    'kaohsiung'=> ['name'=>'高雄', 'angle'=>'亞洲新灣區、美術館、農十六特區新成屋設計需求穩定；港都濕熱氣候，建材選擇（防潮板材、塗料）與通風採光規劃是在地設計重點。'],
];

$slug2 = 'taiwan-interior-design-guide-2026';
$body  = "<p>室內設計是裝修裡最值得花時間挑的環節：同一間房子，不同設計師做出來的動線、收納、採光可以差一個檔次。但設計費怎麼算？統包、設計＋監工、純設計差在哪？這篇整理 <strong>3 種合作模式</strong>、<strong>費用行情</strong>、<strong>挑設計師 5 個重點</strong>、<strong>避坑清單</strong>與 <strong>3 城在地考量</strong>。</p>\n\n";

$body .= "<h2 id=\"toc\">📖 文章目錄</h2>\n<ol>\n";
$body .= "  <li><a href=\"#models\">3 種合作模式（純設計／設計＋監工／統包）</a></li>\n";
$body .= "  <li><a href=\"#price\">費用行情</a></li>\n";
$body .= "  <li><a href=\"#choose\">挑設計師 5 個重點</a></li>\n";
$body .= "  <li><a href=\"#traps\">避坑清單</a></li>\n";
$body .= "  <li><a href=\"#cities\">3 城在地考量</a></li>\n";
$body .= "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"models\">3 種合作模式</h2>\n<ul>\n";
$body .= "  <li><strong>純設計</strong>：只出圖（平面配置、3D、施工圖），自己發包工班。設計費每坪 NT\$3,000–8,000。適合有監工能力或認識工班的屋主。</li>\n";
$body .= "  <li><strong>設計＋監工</strong>：設計師出圖並監工，工程另外發包。監工費約工程款 5–10%。品質與彈性的平衡點。</li>\n";
$body .= "  <li><strong>統包（設計施工一體）</strong>：設計到施工一家負責，溝通成本最低、責任歸屬最清楚，但要慎選——蘋果比蘋果地比價。</li>\n";
$body .= "</ul>\n\n";

$body .= "<h2 id=\"price\">費用行情</h2>\n<ul>\n";
$body .= "  <li><strong>設計費</strong>：每坪 NT\$3,000–8,000（知名設計師可達萬元以上）；丈量與初步配置有的免費、有的收 NT\$3,000–10,000 可折抵。</li>\n";
$body .= "  <li><strong>輕裝修</strong>（油漆、地板、燈具、系統櫃）：每坪 NT\$2–5 萬。</li>\n";
$body .= "  <li><strong>全室裝修</strong>（含格局調整、水電、衛浴廚房）：新成屋每坪 NT\$5–10 萬；老屋翻新每坪 NT\$8–15 萬（基礎工程占比高）。</li>\n";
$body .= "</ul>\n";
$body .= "<p><strong>老屋翻新貴在看不見的地方</strong>：電線重拉、水管更新、防水重做、結構補強——這些占老屋預算 4–5 成，省了日後加倍奉還。</p>\n\n";

$body .= "<h2 id=\"choose\">挑設計師 5 個重點</h2>\n<ol>\n";
$body .= "  <li><strong>看同類型作品</strong>：做慣豪宅的未必擅長小宅收納，老屋翻新要找有老屋實績的。</li>\n";
$body .= "  <li><strong>看報價單顆粒度</strong>：「一式」越多越危險；正規報價列到品牌、型號、數量、單價。</li>\n";
$body .= "  <li><strong>看合約完整度</strong>：付款節點綁工程進度、變更追加要書面、保固條款寫清楚。</li>\n";
$body .= "  <li><strong>看溝通頻率與紀錄</strong>：工程群組、週報、現場照片——有紀錄才有依據。</li>\n";
$body .= "  <li><strong>看口碑與糾紛紀錄</strong>：Google 評論、社群討論，也查公司登記與消費爭議紀錄。</li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"traps\">避坑清單</h2>\n<ul>\n";
$body .= "  <li>🚩 報價明顯低於行情 → 後期追加是常見套路，總價反而更高。</li>\n";
$body .= "  <li>🚩 要求一次付清或頭款超過 30% → 付款節點要綁進度。</li>\n";
$body .= "  <li>🚩 不給看施工圖、不讓去工地 → 透明度是品質的前提。</li>\n";
$body .= "  <li>🚩 口頭承諾不入合約 → 「到時候幫你用好一點的」要白紙黑字。</li>\n";
$body .= "  <li>🚩 完工拖延無違約條款 → 工期與違約金事先談。</li>\n";
$body .= "</ul>\n\n";

$body .= "<h2 id=\"cities\">3 城在地考量</h2>\n";
foreach ($idCities as $citySlug => $info) {
    $body .= "<h3>{$info['name']}</h3>\n";
    $body .= "<p>{$info['angle']}</p>\n";
    $body .= "<p><strong>{$info['name']}在地室內設計與相關居家服務</strong>："
        . "<a href=\"/city/{$citySlug}/home-service/interior-design\">{$info['name']}室內設計</a>｜"
        . "<a href=\"/city/{$citySlug}/home-service/flooring\">{$info['name']}木地板</a>｜"
        . "<a href=\"/city/{$citySlug}/home-service\">{$info['name']}居家服務</a></p>\n\n";
}

$body .= "<h2 id=\"faq\">常見問題 FAQ</h2>\n";
$body .= "<h3>Q1：設計費可以折抵工程款嗎？</h3>\n<p>部分統包公司「簽工程約折抵設計費」。注意：折抵通常綁定工程要給同一家做，比價空間就沒了。把設計費當獨立成本看，比較不會被綁。</p>\n";
$body .= "<h3>Q2：30 坪新成屋輕裝修要多少？</h3>\n<p>油漆＋超耐磨地板＋系統櫃＋燈具窗簾的輕裝修，約 NT\$60–150 萬（每坪 2–5 萬），看櫃體數量與建材等級。不動格局與水電是控制預算的關鍵。</p>\n";
$body .= "<h3>Q3：老屋翻新為什麼比新成屋貴？</h3>\n<p>30 年以上老屋的電線、水管、防水都到壽命，必須翻新（每坪基礎工程 NT\$3–6 萬起），再加上格局調整與表面材，每坪 NT\$8–15 萬是合理區間。只做表面不動基礎，漏水跳電隨後就到。</p>\n";
$body .= "<h3>Q4：設計約跟工程約要分開簽嗎？</h3>\n<p>建議分開。設計約收設計費、交付圖面；工程約綁施工品質與工期。一張約混在一起，出問題時責任難切割。</p>\n";
$body .= "<h3>Q5：裝修完多久可以入住？</h3>\n<p>視板材與塗料等級，通風 2 週–1 個月；有小孩、孕婦或過敏體質建議做甲醛檢測再入住，裝修時就指定 F4★／E0 低甲醛板材最保險。入住前記得安排<a href=\"/guide/cleaning-detail-vs-basic\">裝潢細清</a>。</p>\n\n";

$body .= "<h2>結尾</h2>\n<p>挑室內設計：① 看同類型作品、② 報價列到型號數量、③ 合約綁付款節點、④ 留溝通紀錄、⑤ 查口碑。下方 3 城子分類頁可看在地設計團隊。</p>\n";

$gs->execute([
    $slug2,
    '全台室內設計指南 2026：設計費行情、統包 vs 設計監工、挑設計師 5 重點',
    '室內設計怎麼挑？純設計、設計＋監工、統包 3 種模式差在哪、設計費每坪 NT$3,000–8,000 行情、老屋翻新為什麼貴、避坑清單與合約重點，台中、台南、高雄在地考量一篇看。',
    $body,
    '全台室內設計指南 2026｜設計費行情挑設計師避坑｜店家好口碑',
    '室內設計怎麼挑？純設計／設計＋監工／統包 3 種合作模式、設計費每坪 NT$3,000–8,000、輕裝修與老屋翻新行情、挑設計師 5 重點與避坑清單，台中七期、台南老屋、高雄亞灣在地考量。',
    'https://www.gomag.com.tw/uploads/guides/covers/renovation.svg',
]);
$r2 = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug='{$slug2}'")->fetch(PDO::FETCH_ASSOC);
echo "  ✅ {$slug2} id={$r2['id']} body={$r2['l']} bytes\n";

// ════════════════════════════════════════════
// D. 2 篇既有 city 驗屋 guide append 內鏈
// ════════════════════════════════════════════
echo "\n═══ D. 既有 city 驗屋 guide append 內鏈 ═══\n";
$marker = '<!-- related-services-block-v1 -->';
$cityGuides = [
    ['slug' => 'taichung-new-house-handover-inspection', 'city' => 'taichung', 'cn' => '台中'],
    ['slug' => 'tainan-handover-inspection-guide', 'city' => 'tainan', 'cn' => '台南'],
];
foreach ($cityGuides as $g) {
    $row = $db->prepare("SELECT body_html FROM guides WHERE slug=?");
    $row->execute([$g['slug']]);
    $b = $row->fetchColumn();
    if (!$b) { echo "  ❌ 找不到 {$g['slug']}\n"; continue; }
    if (strpos($b, $marker) !== false) { echo "  ⏭ skip {$g['slug']}\n"; continue; }
    $append = "\n\n{$marker}\n<h2 id=\"related-services\">延伸閱讀：{$g['cn']} 居家服務子分類</h2>\n";
    $append .= "<p>找{$g['cn']}在地相關居家服務：</p>\n<ul>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/home-inspection\">{$g['cn']}驗屋</a></li>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/reno-detail\">{$g['cn']}裝潢細清</a></li>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/interior-design\">{$g['cn']}室內設計</a></li>\n";
    $append .= "</ul>\n<p>更多服務見 <a href=\"/city/{$g['city']}/home-service\">{$g['cn']}居家服務</a> 樞紐頁。</p>\n";
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$b . $append, $g['slug']]);
    echo "  ✅ {$g['slug']}\n";
}

// ════════════════════════════════════════════
// 驗證
// ════════════════════════════════════════════
echo "\n═══ 驗證：6 個目標子服務頁內鏈數 ═══\n";
foreach ([
    ['kaohsiung','home-inspection'],['taichung','home-inspection'],['tainan','home-inspection'],
    ['kaohsiung','interior-design'],['taichung','interior-design'],['tainan','interior-design'],
] as [$c, $s]) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$c}/home-service/{$s}%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %s/%s → %d 篇 guide 內鏈\n", $flag, $c, $s, $n);
}

// 全站剩餘孤立
$cats = $db->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
$iso = 0; $tot = 0;
foreach ($db->query("SELECT city_slug, category_id, service_slug FROM geo_category_pages WHERE service_slug<>'' AND is_active=1") as $r) {
    $tot++;
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$r['city_slug']}/{$cats[$r['category_id']]}/{$r['service_slug']}%"]);
    if ((int)$st->fetchColumn() === 0) $iso++;
}
printf("\n全站剩餘孤立：%d / %d（%.0f%%）\n", $iso, $tot, 100 * $iso / max(1, $tot));
echo "\n預覽：\n  https://www.gomag.com.tw/guide/{$slug1}\n  https://www.gomag.com.tw/guide/{$slug2}\n";
