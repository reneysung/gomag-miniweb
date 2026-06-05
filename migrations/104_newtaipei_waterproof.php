<?php
// ============================================================
// Migration 104 — 新北防水工程 painting geo + 2 篇攻略（6 都齊）
// ------------------------------------------------------------
// 6 都防水齊全：屏東(098)/台南(098)/高雄(098)/台中(100)/台北(101)/新北(本)。
// 新北特色：重劃區（林口/淡水/三峽/江翠）+老公寓（三重/永和/新店）+
// 山邊潮濕（汐止/烏來/新店山區）+ 頂樓加蓋多 + 移居族。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/104_newtaipei_waterproof.php
// 冪等：upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$intro = <<<'HTML'
<p>新北人口最多、重劃區密集（林口、淡水新市鎮、三峽北大、新莊副都心、板橋江翠、中和環球），加上老公寓多（三重、永和、新店）、汐止/烏來/新店山邊潮濕、頂樓加蓋普及，防水工程是新北住宅長期維護的核心。新北腹地大、施工型態從重劃區大坪數新案到山邊透天都有，挑師傅要看是否熟悉該屋型。</p>
<p>新北常見的防水／油漆工程：</p>
<ul>
<li><strong>老公寓外牆翻新＋防水</strong>：三重、永和、新店、中和屋齡 30–50 年公寓，磁磚剝落公安疑慮。</li>
<li><strong>頂樓加蓋／鐵皮屋頂防水</strong>：老社區常見、漏水主因，建議全面重做。</li>
<li><strong>重劃區新成屋外牆／屋頂收尾</strong>：林口、淡海、江翠新案的初期問題修補。</li>
<li><strong>透天屋頂防水</strong>：三峽、淡海、林口透天厝最常見漏水點。</li>
<li><strong>抓漏修補</strong>：壁癌、滲水、樓上樓下糾紛追查。</li>
</ul>
<p>新北防水行情約：外牆每坪 NT$1,400–3,200、屋頂每坪 NT$1,500–3,800、浴室整間防水 NT$18,000–45,000、抓漏單次 NT$3,000–16,000+。比台北略親民。山邊（汐止/烏來/新店山區）車馬費另計。</p>
<p>挑新北防水師傅三件事：①<strong>重劃區／老公寓／透天施工經驗</strong>（三類差異大）；②<strong>山邊潮濕／頂樓加蓋處理</strong>；③<strong>保固年限</strong>（外牆 3–8 年、屋頂 5–10 年、浴室 1–3 年常見）。歡迎新北在地優質防水油漆業者上架。</p>
HTML;

$faqs = [
    ['q'=>'新北防水工程一坪多少錢？','a'=>'外牆每坪約 NT$1,400–3,200、屋頂每坪 NT$1,500–3,800，依材料與屋況浮動。比台北略親民。'],
    ['q'=>'新北浴室防水多少錢？','a'=>'整間浴室防水重做約 NT$18,000–45,000，含拆磁磚、底層處理、防水層、防護試水。'],
    ['q'=>'頂樓加蓋漏水要怎麼處理？','a'=>'頂樓加蓋多是鐵皮、油毛氈或 PU 防水層，老化後漏水普遍。建議全面重做（PU 或防水鋪面），別只局部補。'],
    ['q'=>'三重/永和老公寓外牆磁磚剝落怎麼辦？','a'=>'敲除空鼓磁磚→裂縫填補→重新防水＋面漆或磁磚翻新。可結合外牆健檢一次處理。'],
    ['q'=>'汐止/烏來/新店山邊潮濕區建議？','a'=>'濕氣與霉氣重，建議抗霉塗料＋雙層防護；屋頂排水坡度與洩水孔特別檢查。'],
    ['q'=>'重劃區新成屋外牆要做防水嗎？','a'=>'新成屋多含基本防水，但建議入住一年內檢查矽利康縫、磁磚黏著與漆面，初期問題建商保固期內處理。'],
    ['q'=>'樓上漏水到我家怎麼辦？','a'=>'先找抓漏業者定位漏水源頭、釐清是樓上問題還是公共管線；管理委員會、區公所調解委員會可協助。'],
    ['q'=>'防水可以保固多久？','a'=>'外牆 3–8 年、屋頂 5–10 年、浴室 1–3 年是常見區間，下訂前要白紙黑字寫進合約。'],
];

$gp = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES ('newtaipei', 2, 'painting', '油漆防水', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
$gp->execute([$intro, json_encode($faqs, JSON_UNESCAPED_UNICODE),
    '新北油漆防水推薦｜重劃區老公寓頂樓加蓋抓漏行情',
    '新北油漆防水工程怎麼找？林口/淡海/江翠重劃區、三重/永和老公寓、汐止山邊潮濕、頂樓加蓋處理，店家好口碑收錄的新北在地油漆防水業者。']);
echo "GEO: 新北 油漆防水\n";

$g1 = <<<'HTML'
<p>新北人口最多、重劃區密集（林口、淡水、三峽、江翠、副都心）、老公寓多（三重、永和、新店）、山邊汐止/烏來潮濕、頂樓加蓋普及，防水工程需求多元。這篇把新北防水的種類、行情、挑師傅重點與在地眉角說清楚。</p>
<h3>新北最常見的 5 種防水需求</h3>
<ul>
<li><strong>老公寓外牆翻新＋防水</strong>：三重、永和、新店、中和屋齡 30–50 年公寓，磁磚剝落公安疑慮。</li>
<li><strong>頂樓加蓋／鐵皮屋頂防水</strong>：老社區雨季漏水主因。</li>
<li><strong>重劃區新成屋外牆／屋頂收尾</strong>：林口、淡海、江翠新案初期問題。</li>
<li><strong>透天屋頂防水</strong>：三峽、淡海、林口透天厝常見漏水點。</li>
<li><strong>抓漏修補</strong>：壁癌、滲水、樓上樓下糾紛追查。</li>
</ul>
<h3>新北在地要注意：屋型多、山邊潮濕、頂樓加蓋</h3>
<ul>
<li><strong>屋型差異大</strong>：重劃區大坪數新案 / 老公寓 / 山邊透天，挑師傅要看是否熟悉該類型。</li>
<li><strong>汐止/烏來/新店山邊</strong>：濕氣與霉氣重，建議抗霉塗料＋雙層防護。</li>
<li><strong>頂樓加蓋普及</strong>：鐵皮或油毛氈防水老化漏水普遍，建議全面重做。</li>
<li><strong>梅雨＋東北季風</strong>：北部雨季長，建議梅雨季前完工。</li>
</ul>
<h3>新北防水行情</h3>
<p>外牆每坪約 NT$1,400–3,200、屋頂每坪 NT$1,500–3,800、浴室整間防水 NT$18,000–45,000、抓漏單次 NT$3,000–16,000+。山邊車馬費另計。</p>
<h3>挑新北防水師傅三件事</h3>
<p>①<strong>重劃區／老公寓／透天經驗</strong>（差異大）、②<strong>山邊潮濕／頂樓加蓋處理</strong>、③<strong>保固年限</strong>（外牆 3–8 年、屋頂 5–10 年、浴室 1–3 年常見）。可參考本站新北油漆防水頁。</p>
HTML;

$g2 = <<<'HTML'
<p>新北重劃區密集（林口、淡水、三峽、江翠、副都心）、老公寓多（三重、永和、新店），加上汐止/烏來山邊潮濕、頂樓加蓋普及，外牆防水是新北住宅維護的重點。老公寓磁磚脫落還涉及公安責任，預防比意外賠償便宜很多。這篇專門講外牆防水。</p>
<h3>新北外牆防水的 4 大常見問題</h3>
<ul>
<li><strong>老公寓磁磚空鼓／脫落</strong>：三重、永和、新店、中和屋齡 30–50 年公寓最常見，公安疑慮。</li>
<li><strong>裂縫滲水</strong>：地震、熱漲冷縮造成的細微裂縫，雨季漏水進室內。</li>
<li><strong>粉光剝落／起鼓</strong>：老屋常見，外觀變差也代表防水層失效。</li>
<li><strong>山邊霉氣／粉光</strong>：汐止、烏來、新店山區濕氣重、霉氣易生。</li>
</ul>
<h3>常見 3 種外牆防水工法</h3>
<ul>
<li><strong>彈性水泥（彈泥）</strong>：CP 值高，適合一般公寓外牆，保固 3–5 年。</li>
<li><strong>PU 防水＋面漆</strong>：彈性與耐候性佳，新北濕氣環境推薦，保固 5–8 年。</li>
<li><strong>抗霉防水漆＋批土</strong>：汐止／烏來／新店山區與老屋翻新常用，5–7 年。</li>
</ul>
<h3>施工流程（老公寓外牆）</h3>
<p>搭鷹架 → 高壓水柱清洗 → 敲除空鼓磁磚（公安重點）→ 裂縫填補（彈泥／PU 填縫）→ 底漆 → 防水主層 → 面漆／批土上漆。新北老公寓 4–5 樓整套約 5–10 天，依坪數、樓層、屋況。雨季容易延宕。</p>
<h3>新北外牆防水行情</h3>
<p>每坪約 NT$1,400–3,200（彈泥）／ NT$2,200–4,000（PU 加抗霉）；含搭鷹架另計（公寓 4–5 樓約 NT$20,000–50,000）。山邊地區可能含車馬費。</p>
<h3>山邊潮濕／頂樓加蓋特別注意</h3>
<p>汐止／烏來／新店山區<strong>濕氣與霉氣</strong>嚴重，建議抗霉塗料＋雙層防護；維護週期較內陸短。<strong>頂樓加蓋</strong>外牆延伸到加蓋結構，建議全面重做、別只局部補。</p>
<h3>挑師傅 3 件事</h3>
<p>①<strong>老公寓外牆案例</strong>（三重／永和／新店／中和）、②<strong>山邊潮濕／頂樓加蓋處理</strong>、③<strong>保固年限</strong>（外牆 3–8 年合理、寫進合約）。可參考本站新北油漆防水頁。</p>
HTML;

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, 'newtaipei', 2, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug='newtaipei', category_id=2, meta_title=VALUES(meta_title),
            meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);

$stmt->execute([':slug'=>'newtaipei-waterproof-guide',
    ':title'=>'新北防水工程指南：重劃區、老公寓頂樓加蓋、汐止山邊潮濕怎麼防',
    ':excerpt'=>'新北重劃區、三重永和老公寓、頂樓加蓋鐵皮屋頂、汐止山邊潮濕，防水工程怎麼挑、種類與行情，一篇看懂。',
    ':body'=>$g1,
    ':mt'=>'新北防水工程指南｜老公寓重劃區頂樓加蓋抓漏行情｜店家好口碑',
    ':md'=>'新北防水工程怎麼做？林口/淡海重劃區、三重/永和老公寓、頂樓加蓋鐵皮屋頂、汐止山邊潮濕的種類、行情與挑師傅重點，新北住戶必看。',
]);
printf("GUIDE: %-38s %d 字\n", 'newtaipei-waterproof-guide', mb_strlen(strip_tags($g1)));

$stmt->execute([':slug'=>'newtaipei-exterior-waterproof-guide',
    ':title'=>'新北外牆防水工程指南：老公寓磁磚剝落、頂樓加蓋、山邊潮濕怎麼做',
    ':excerpt'=>'新北三重永和老公寓磁磚剝落公安疑慮、汐止山邊霉氣、頂樓加蓋延伸外牆，外牆防水工法、材料、行情一篇看懂。',
    ':body'=>$g2,
    ':mt'=>'新北外牆防水工程指南｜老公寓山邊潮濕工法行情｜店家好口碑',
    ':md'=>'新北外牆防水工法（彈泥／PU／抗霉）、施工流程、材料品牌與行情，三重/永和老公寓磁磚剝落、汐止山邊潮濕、頂樓加蓋延伸怎麼處理，新北住戶必看。',
]);
printf("GUIDE: %-38s %d 字\n", 'newtaipei-exterior-waterproof-guide', mb_strlen(strip_tags($g2)));

echo "\n完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
