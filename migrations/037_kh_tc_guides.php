<?php
// ============================================================
// Migration 037 — 高雄/台中 在地攻略 4 篇（各城獨有主題）
// ------------------------------------------------------------
// 高雄台積電購屋裝修潮 / 高雄海港潮濕除霉防鏽 / 台中七期豪宅裝修 /
// 台中重劃區新成屋交屋驗屋。掛 city + home-service，出現在該城居家頁攻略區。
// 各城獨有角度、不可複製到別城 → 無 doorway。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/037_kh_tc_guides.php
// 冪等：以 slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$guides = [];

$guides[] = [
    'slug' => 'kaohsiung-tsmc-home-renovation', 'city' => 'kaohsiung', 'cat' => $homeId,
    'title' => '高雄台積電購屋裝修潮指南：橋頭、楠梓換屋族必看',
    'excerpt' => '台積電進駐高雄橋頭、楠梓帶動換屋潮，購屋後裝修怎麼規劃、預算怎麼抓、找誰做，一篇看懂。',
    'meta_title' => '高雄台積電購屋裝修潮指南｜橋頭楠梓換屋族必看｜店家好口碑',
    'meta_desc' => '台積電進駐高雄帶動換屋潮，橋頭楠梓左營購屋後裝修怎麼規劃、預算時程怎麼抓、找誰做，科技人裝修必看。',
    'body' => <<<'HTML'
<p>台積電在高雄楠梓設廠、橋頭科學園區帶動，周邊房市與換屋需求大增，不少科技人、首購族在橋頭、楠梓、左營一帶買房後面臨裝修課題。這篇整理高雄換屋裝修潮下該怎麼規劃。</p>
<h3>新成屋 vs 中古屋裝修</h3>
<ul>
<li><strong>新成屋（重劃區新案）</strong>：多為客變＋輕裝修＋系統櫃，重點在格局微調與收納，連工帶料每坪約 NT$4–8 萬。</li>
<li><strong>中古屋／老公寓</strong>：常需更新管線、防水、衛浴，每坪約 NT$6–12 萬，屋況落差大需勘估。</li>
</ul>
<h3>科技人裝修常見需求</h3>
<ul>
<li>雙薪、工時長 → 重收納、好清潔、低維護建材。</li>
<li>在家工作 → 書房／視訊空間、網路與插座配置。</li>
<li>交屋潮集中 → 設計師與工班檔期緊，建議交屋前 1–2 個月先談。</li>
</ul>
<h3>預算與時程</h3>
<p>新成屋輕裝修約 2–4 個月、中古屋翻新 3–6 個月。建議預留 10–15% 變更彈性，並把選材等級、品牌寫進合約附件避免追加。</p>
<h3>挑高雄裝修團隊</h3>
<p>找有橋頭、楠梓、左營重劃區實績的設計／統包，看報價是否拆清楚（設計／監工／工程款）、合約與分階段付款、實際完工案例與在地口碑。可參考本站高雄室內設計與相關商家。</p>
HTML,
];

$guides[] = [
    'slug' => 'kaohsiung-humidity-mold-rust', 'city' => 'kaohsiung', 'cat' => $homeId,
    'title' => '高雄海港潮濕居家除霉防銹全攻略：壁癌、霉斑怎麼根治',
    'excerpt' => '高雄近海濕氣重，居家壁癌、霉斑、金屬生鏽怎麼處理與預防，從清潔到防水一次說清楚。',
    'meta_title' => '高雄海港潮濕除霉防銹全攻略｜壁癌霉斑怎麼根治｜店家好口碑',
    'meta_desc' => '高雄近海濕氣重，居家壁癌、霉斑、金屬生鏽怎麼處理與預防，除霉、防水、防鏽一次說清楚。',
    'body' => <<<'HTML'
<p>高雄近海、濕氣重，尤其鼓山、鹽埕、前鎮、小港靠海一帶，居家常見壁癌、浴室霉斑、金屬五金生鏽。這些跟一般髒污不同，處理錯方法很快復發。這篇教你高雄潮濕居家怎麼除霉防鏽。</p>
<h3>霉斑、壁癌差在哪</h3>
<ul>
<li><strong>表面霉斑</strong>：浴室矽利康、磁磚縫、牆面表層的黑黴，屬清潔範圍，用專用除黴劑處理。</li>
<li><strong>壁癌</strong>：牆體含水、油漆粉化剝落，是<strong>防水問題</strong>，要打除粉化層、殺菌、做防水底再上漆，只清表面會復發。</li>
</ul>
<h3>除霉防鏽怎麼做</h3>
<ul>
<li>浴室、廚房定期除黴 ＋ 保持通風乾燥（除濕機、抽風扇）。</li>
<li>壁癌找油漆防水師傅處理根源（可看本站油漆防水分類）。</li>
<li>金屬五金、鐵件選不鏽鋼或做防鏽處理，近海陽台尤其。</li>
</ul>
<h3>找清潔 vs 找防水</h3>
<p>表面霉斑、定期除黴找清潔業者；結構性壁癌、滲漏找油漆防水／抓漏。判斷不出來時，請業者到府評估是「表面」還是「源頭」問題。</p>
<h3>高雄潮濕季重點</h3>
<p>梅雨與颱風季前後是壁癌、漏水高發期，建議入夏前檢查窗框、外牆、頂樓防水。可參考本站高雄清潔與油漆防水相關商家。</p>
HTML,
];

$guides[] = [
    'slug' => 'taichung-7th-luxury-renovation', 'city' => 'taichung', 'cat' => $homeId,
    'title' => '台中七期豪宅裝修指南：高端設計、客變怎麼規劃',
    'excerpt' => '台中七期豪宅、預售客變怎麼找設計師、預算怎麼抓、流程怎麼走，高端裝修一次看懂。',
    'meta_title' => '台中七期豪宅裝修指南｜高端設計與客變規劃｜店家好口碑',
    'meta_desc' => '台中七期豪宅、預售客變怎麼找設計師、預算行情怎麼抓、流程時程怎麼走，高端裝修必看。',
    'body' => <<<'HTML'
<p>台中七期（西屯）是中部豪宅與高端住宅聚落，室內設計能量強、名設計師多。七期的裝修跟一般住宅不同——坪數大、預算高、客變與選材講究。這篇整理七期豪宅裝修怎麼規劃。</p>
<h3>七期裝修的特點</h3>
<ul>
<li><strong>大坪數全案設計</strong>：多走全案設計（設計＋監工＋工程），重整體風格與空間規劃。</li>
<li><strong>預售客變</strong>：豪宅多在預售階段就找設計師與建商客變，從格局、管線、建材一次到位。</li>
<li><strong>高端選材</strong>：石材、進口建材、智慧家居、系統整合等。</li>
</ul>
<h3>預算與行情</h3>
<p>七期高端全案設計費每坪常 NT$6,000 以上（名設計師更高），連工帶料總價依選材落差極大。建議以「總預算＋優先順序」跟設計師溝通，避免無止境追加。</p>
<h3>流程與時程</h3>
<p>預售客變從交屋前 6–12 個月就啟動；成屋全案設計到完工約 4–8 個月，大坪數更久。分階段驗收付款、書面確認變更是關鍵。</p>
<h3>挑七期設計師</h3>
<p>看同等級（豪宅／大坪數）實際作品、設計與監工是否同團隊、合約與付款分期、是否有完整工程管理。可參考本站台中室內設計與相關商家。</p>
HTML,
];

$guides[] = [
    'slug' => 'taichung-new-house-handover-inspection', 'city' => 'taichung', 'cat' => $homeId,
    'title' => '台中重劃區新成屋交屋驗屋指南：七期、北屯機捷必看',
    'excerpt' => '台中七期、北屯機捷特區、太平新成屋交屋潮，驗屋怎麼準備、檢查哪些、抓到問題怎麼處理。',
    'meta_title' => '台中重劃區新成屋交屋驗屋指南｜七期北屯機捷必看｜店家好口碑',
    'meta_desc' => '台中七期、北屯機捷特區、太平新成屋交屋前驗屋怎麼準備、檢查哪些、透天驗屋注意，交屋潮必看。',
    'body' => <<<'HTML'
<p>台中重劃區新案密集，七期、北屯機捷特區、太平、烏日交屋潮持續。交屋是買房最後一關，簽收前驗屋抓出瑕疵，才不會入住後求償無門。這篇教你台中新成屋交屋驗屋怎麼準備。</p>
<h3>交屋前準備</h3>
<ul>
<li>對照<strong>合約與建材表</strong>：建材品牌、設備型號是否一致。</li>
<li>準備工具或<strong>找專業驗屋</strong>（含紅外線、水分計、氣密測試）。</li>
<li>挑白天天氣好時段驗，採光與漏水較易看出。</li>
</ul>
<h3>驗屋檢查重點</h3>
<ul>
<li>漏水防水：窗框、外牆、浴室、陽台滲漏。</li>
<li>泥作磁磚：地壁磁磚空心、裂縫、洩水坡度。</li>
<li>門窗水電：門窗氣密、插座迴路、給排水、糞管測試。</li>
<li>客變確認：客變項目是否如實施作。</li>
</ul>
<h3>台中透天驗屋特別注意</h3>
<p>台中透天比例高，透天驗屋要含<strong>各樓層、頂樓加蓋、前後院排水</strong>，工時與費用比大樓高，預約時先講清楚樓層。</p>
<h3>驗出問題怎麼辦 + 行情</h3>
<p>瑕疵拍照列清單、交屋前向建商提出修繕、修繕後複驗。找專業驗屋：標準／實品屋每戶約 NT$5,000–12,000、毛胚屋 NT$8,000–15,000+。七期、北屯交屋旺季驗屋師滿檔，建議提前預約。可參考本站台中驗屋頁。</p>
HTML,
];

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :city, :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug=VALUES(city_slug), category_id=VALUES(category_id), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
foreach ($guides as $g) {
    $stmt->execute([':slug'=>$g['slug'], ':title'=>$g['title'], ':excerpt'=>$g['excerpt'], ':body'=>$g['body'], ':city'=>$g['city'], ':cat'=>$g['cat'], ':mt'=>$g['meta_title'], ':md'=>$g['meta_desc']]);
    printf("UPSERT %-40s (%s) body %4d 字\n", $g['slug'], $g['city'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
