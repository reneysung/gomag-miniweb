<?php
// ============================================================
// Migration 041 — 在地攻略 3 篇（高雄中古屋翻新 / 台中木地板選購 / 彰化裝修清潔）
// ------------------------------------------------------------
// 掛 city + home-service，呼應各城既有居家頁。城市獨有角度無 doorway，行情沿用已核可。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/041_more_guides_c.php
// 冪等：以 slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$guides = [];

$guides[] = [
    'slug' => 'kaohsiung-old-apartment-renovation', 'city' => 'kaohsiung', 'cat' => $homeId,
    'title' => '高雄中古屋、老公寓翻新指南：管線、防水、預算怎麼抓',
    'excerpt' => '高雄中古屋、老公寓翻新前必懂的管線更新、防水抓漏、格局改造與預算，近海濕氣注意事項一次看。',
    'meta_title' => '高雄中古屋、老公寓翻新指南｜管線防水預算怎麼抓｜店家好口碑',
    'meta_desc' => '高雄中古屋、老公寓翻新前必懂的管線更新、防水抓漏、格局與預算，近海濕氣防水注意事項一次看。',
    'body' => <<<'HTML'
<p>高雄房價相對親民，不少首購族選擇中古屋、老公寓翻新。但老屋翻新跟新成屋裝修不同——管線、防水、結構沒處理好，住進去問題不斷。加上高雄近海濕氣重，防水更是重點。這篇整理高雄中古屋翻新該注意的。</p>
<h3>翻新前先確認</h3>
<ul>
<li><strong>結構與屋齡</strong>：30 年以上老公寓、海砂屋疑慮、樑柱裂縫先評估，必要時找結構技師。</li>
<li><strong>管線老化</strong>：老屋電線、水管多需全屋更新，是翻新重點，別只做表面。</li>
<li><strong>防水抓漏</strong>：高雄近海＋老屋防水老化，頂樓、外牆、浴室、窗框是漏水熱區，先抓源頭。</li>
</ul>
<h3>預算怎麼抓</h3>
<p>中古屋／老公寓翻新（連工帶料）每坪約 NT$6–12 萬，視屋況（管線、防水、格局變動）落差大；局部翻新（廚衛、油漆、地板）彈性大。建議預留 10–15% 因應拆除後發現的隱蔽問題。</p>
<h3>高雄翻新特別注意</h3>
<ul>
<li>近海（鼓山、鹽埕、前鎮）濕氣重，<strong>防水與防鏽</strong>要加強，金屬五金選不鏽鋼。</li>
<li>老公寓多無電梯，<strong>進料與廢料清運</strong>成本較高，先確認。</li>
<li>大樓社區翻新要遵守管委會施工規範與時段。</li>
</ul>
<h3>挑高雄翻新團隊</h3>
<p>找有高雄老屋／公寓實績的設計／統包，看是否先做結構與漏水評估、報價是否拆清楚、合約與分階段付款。可參考本站高雄室內設計與相關商家。</p>
HTML,
];

$guides[] = [
    'slug' => 'taichung-flooring-material-guide', 'city' => 'taichung', 'cat' => $homeId,
    'title' => '台中木地板材質選購指南：超耐磨、海島型、SPC 怎麼選',
    'excerpt' => '台中鋪木地板怎麼選材質？超耐磨、海島型、實木、SPC 的差別、價格與適用情境，新成屋與透天都適用。',
    'meta_title' => '台中木地板材質選購指南｜超耐磨海島型 SPC 怎麼選｜店家好口碑',
    'meta_desc' => '台中木地板怎麼選材質？超耐磨、海島型、實木、SPC 的差別、價格與適用情境，新成屋與透天都適用。',
    'body' => <<<'HTML'
<p>台中重劃區新成屋交屋潮、透天換地板需求大，但木地板材質百百種，超耐磨、海島型、實木、SPC 差在哪？這篇幫你依預算與屋況選對材質。</p>
<h3>四種材質怎麼選</h3>
<ul>
<li><strong>超耐磨木地板</strong>：耐刮好清潔、CP 值高，適合有小孩寵物。連工帶料每坪約 NT$2,500–4,500，看「耐磨轉數」分等級。</li>
<li><strong>海島型木地板</strong>：表層實木、底層多層夾板，較耐潮、有實木質感，每坪約 NT$4,500–8,000，看「表層厚度」。</li>
<li><strong>實木地板</strong>：質感與腳感最佳，但價格高、保養要求高，每坪約 NT$7,000 起。</li>
<li><strong>SPC 石塑地板</strong>：防水耐潮、好清潔，適合一樓或潮濕區，每坪約 NT$2,500–4,000。</li>
</ul>
<h3>依情境選</h3>
<ul>
<li>新成屋直鋪、預算考量 → 超耐磨。</li>
<li>想要實木質感又耐用 → 海島型。</li>
<li>一樓、潮濕區、租屋 → SPC 防水。</li>
<li>預算充足、重質感 → 實木。</li>
</ul>
<h3>施工要注意</h3>
<p>新成屋多直鋪（先確認地面平整乾燥）；透天／老屋換地板常需拆除整平、清運（多另計）。收邊條、踢腳板通常另算，報價時一起列清楚。</p>
<h3>保固與挑店</h3>
<p>材料看原廠保固（超耐磨常 5–10 年結構保固）、施工保固依業者。台中木地板業者多，看實際施工案例與在地口碑。可參考本站台中木地板頁。</p>
HTML,
];

$guides[] = [
    'slug' => 'changhua-renovation-cleaning-guide', 'city' => 'changhua', 'cat' => $homeId,
    'title' => '彰化、員林裝修與老屋清潔指南：透天、老宅怎麼整理',
    'excerpt' => '彰化、員林、鹿港透天與老宅多，裝修翻新與清潔怎麼規劃、預約、抓預算，在地實用指南。',
    'meta_title' => '彰化、員林裝修與老屋清潔指南｜透天老宅怎麼整理｜店家好口碑',
    'meta_desc' => '彰化、員林、鹿港透天與老宅裝修翻新、清潔怎麼規劃、預約、抓預算，在地實用指南。',
    'body' => <<<'HTML'
<p>彰化以透天厝與自有住宅為主，從彰化市、員林到鹿港、和美，新建案與老屋並存。不論是透天裝修翻新還是老宅清潔，跟大樓住宅做法不太一樣。這篇整理彰化在地實用重點。</p>
<h3>彰化透天裝修／翻新</h3>
<ul>
<li>透天坪數大、樓層多，<strong>裝修預算依屋況落差大</strong>；老屋翻新涉管線、防水、結構，每坪約 NT$6–12 萬。</li>
<li>員林、彰化市新建案多走客變＋輕裝修；鹿港、和美老宅翻新需處理老建材。</li>
<li>彰化在地設計／統包業者分布散，先到府勘估、報價拆清楚（設計／監工／工程款）。</li>
</ul>
<h3>彰化透天／老屋清潔</h3>
<ul>
<li>透天清潔多按<strong>樓層或坪數</strong>計，是否含樓梯、頂樓、前後院影響報價，預約先講清楚。</li>
<li>定期清潔每次約 NT$1,800–3,000；裝潢後細清每坪約 NT$120–250。</li>
<li>老宅常有長年油垢、壁面問題，工時較長，找有老屋經驗的師傅。</li>
</ul>
<h3>預約眉角</h3>
<p>彰化業者分布散、調度需時間，年節大掃除與入厝旺季建議提前 1–2 週預約。較遠區（田中、二林、芳苑）更要早約。</p>
<h3>彰化怎麼挑</h3>
<p>看在地口碑與實際案例、報價含／不含項目寫清楚、是否到府估價。可參考本站彰化清潔頁。歡迎在地優質裝修與清潔業者上架。</p>
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
    printf("UPSERT %-38s (%s) body %4d 字\n", $g['slug'], $g['city'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
