<?php
// ============================================================
// Migration 100 — 台中防水工程 painting geo + 2 篇攻略（一般 + 外牆）
// ------------------------------------------------------------
// 接 098/099。台中：painting 子服務頁尚未建、補上；
// + 1 篇 taichung-waterproof-guide（一般）
// + 1 篇 taichung-exterior-waterproof-guide（外牆專文）
// 中部特色：七期/北屯重劃區新成屋、西區/中區老屋、大坑山區潮濕、中部地震帶。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/100_taichung_waterproof.php
// 冪等：upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$h = 2;

// ── 1. 台中 painting geo ──
$intro = <<<'HTML'
<p>台中是中台灣建案重鎮，七期、單元二、北屯機捷特區、烏日高鐵特區新成屋密集，西區、中區老屋也多，加上大里、太平的老透天與大坑、新社山區，防水與油漆工程需求多元。台中位於中部地震帶（921 與 823 大里），外牆與屋頂裂縫風險高，加上中部多霧、冬季潮濕，防水工程是長期維護的重點。</p>
<p>台中常見的防水／油漆工程：</p>
<ul>
<li><strong>新成屋外牆磁磚／漆面驗收與補強</strong>：七期、北屯重劃區交屋常見問題。</li>
<li><strong>老屋外牆翻新</strong>：西區、中區屋齡 30–50 年公寓重新批土上漆。</li>
<li><strong>透天屋頂防水</strong>：太平、大里、北屯透天最常見漏水點。</li>
<li><strong>浴室、陽台防水</strong>：翻新前的基礎防水。</li>
<li><strong>抓漏修補</strong>：地震裂縫、壁癌、滲水追查。</li>
</ul>
<p>台中防水行情約：外牆每坪 NT$1,100–2,800、屋頂每坪 NT$1,400–3,200、浴室整間防水 NT$15,000–40,000、抓漏單次 NT$2,500–15,000+。山區（大坑、新社）車馬費另計。</p>
<p>挑台中防水師傅三件事：①<strong>新成屋／老屋施工經驗</strong>（七期豪宅 vs 西區老公寓做法不同）；②<strong>地震裂縫處理經驗</strong>（中部地震帶外牆裂縫常見）；③<strong>保固年限</strong>（外牆 3–8 年、屋頂 5–10 年、浴室 1–3 年常見）。歡迎台中在地優質防水油漆業者上架。</p>
HTML;

$faqs = [
    ['q'=>'台中防水工程一坪多少錢？','a'=>'外牆每坪約 NT$1,100–2,800、屋頂每坪 NT$1,400–3,200，依材料與屋況浮動。'],
    ['q'=>'台中浴室防水多少錢？','a'=>'整間浴室防水重做約 NT$15,000–40,000，含拆磁磚、底層處理、防水層、防護試水。'],
    ['q'=>'台中新成屋外牆會有什麼問題？','a'=>'七期/北屯重劃區新案常見磁磚黏著不良、漆面起鼓、矽利康縫脫膠，交屋前驗收要看仔細。'],
    ['q'=>'地震後外牆裂縫要處理嗎？','a'=>'要。中部地震帶（921/823 大里）外牆裂縫常見，細微裂縫進水會逐步擴大成壁癌，建議地震後檢查、必要時局部修補。'],
    ['q'=>'大坑/新社山區潮濕區建議？','a'=>'濕氣重、霉氣易生，外牆建議抗霉塗料、雙層防護；屋頂排水坡度與洩水孔要特別檢查。'],
    ['q'=>'抓漏一次多少？','a'=>'單點抓漏約 NT$2,500–15,000+，依漏點難度與是否需打牆、爬高浮動，先到府勘估較準。'],
    ['q'=>'防水可以保固多久？','a'=>'外牆 3–8 年、屋頂 5–10 年、浴室 1–3 年是常見區間，下訂前要白紙黑字寫進合約。'],
    ['q'=>'壁癌只刷油漆有用嗎？','a'=>'治標不治本。要先找漏水源頭、底層處理（除鹽、防水、批土）再上漆才會耐久。'],
];

$gp = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES ('taichung', 2, 'painting', '油漆防水', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
$gp->execute([$intro, json_encode($faqs, JSON_UNESCAPED_UNICODE),
    '台中油漆防水推薦｜外牆屋頂浴室抓漏行情・新成屋老屋翻新',
    '台中油漆防水工程怎麼找？外牆、屋頂、浴室、抓漏行情、用料選擇，七期/北屯重劃區新成屋、西區老屋翻新建議，店家好口碑收錄的台中在地油漆防水業者。']);
echo "GEO: 台中 油漆防水\n";

// ── 2. 一般防水攻略 ──
$g1 = <<<'HTML'
<p>台中是中台灣建案重鎮，七期、單元二、北屯機捷特區、烏日高鐵特區新成屋密集，西區、中區老屋多，加上中部地震帶與大坑山區潮濕，防水工程需求多元。這篇把台中防水的種類、行情、挑師傅重點與在地眉角說清楚。</p>
<h3>台中最常見的 5 種防水需求</h3>
<ul>
<li><strong>新成屋外牆驗收／補強</strong>：七期、北屯重劃區交屋常見磁磚黏著、漆面起鼓問題。</li>
<li><strong>老屋外牆翻新</strong>：西區、中區屋齡 30–50 年公寓重新批土上漆。</li>
<li><strong>透天屋頂防水</strong>：太平、大里、北屯透天最常見漏水點。</li>
<li><strong>浴室／陽台防水</strong>：翻新前的基礎防水，避免漏到樓下。</li>
<li><strong>抓漏修補</strong>：地震裂縫、壁癌、滲水源頭追查。</li>
</ul>
<h3>台中在地要注意：地震、新成屋、山區潮濕</h3>
<ul>
<li><strong>中部地震帶</strong>：921、823 大里地震後外牆裂縫常見，細微裂縫進水會擴大成壁癌，地震後建議檢查。</li>
<li><strong>七期／北屯新成屋</strong>：交屋驗收時建議檢查外牆磁磚黏著、漆面、矽利康縫，初期問題未處理會擴大。</li>
<li><strong>大坑／新社山區</strong>：濕氣重、霉氣易生，建議抗霉塗料＋雙層防護；屋頂排水坡度特別重要。</li>
</ul>
<h3>台中防水行情</h3>
<p>外牆每坪約 NT$1,100–2,800、屋頂每坪 NT$1,400–3,200、浴室整間防水 NT$15,000–40,000、抓漏單次 NT$2,500–15,000+。山區可能含車馬費，先到府或視訊估價、把含哪些寫清楚。</p>
<h3>挑台中防水師傅三件事</h3>
<p>①<strong>新成屋／老屋施工經驗</strong>（七期豪宅 vs 西區老公寓做法不同）、②<strong>地震裂縫處理</strong>（中部地震帶必問）、③<strong>保固年限</strong>（外牆 3–8 年、屋頂 5–10 年、浴室 1–3 年常見）。可參考本站台中油漆防水頁。</p>
HTML;

// ── 3. 外牆防水專文 ──
$g2 = <<<'HTML'
<p>台中七期、北屯、烏日重劃區新成屋密集，西區、中區老屋與大里、太平老透天並存，加上中部地震帶與大坑山區潮濕，外牆防水是台中住宅長期維護的重點。這篇專門講外牆防水。</p>
<h3>台中外牆防水的 4 大常見問題</h3>
<ul>
<li><strong>新成屋磁磚黏著不良／漆面起鼓</strong>：七期、北屯重劃區常見，交屋初期就要處理。</li>
<li><strong>老屋粉光剝落／磁磚脫落</strong>：西區、中區屋齡 30–50 年公寓常見。</li>
<li><strong>地震裂縫滲水</strong>：中部地震帶（921、823 大里）造成的細微裂縫，下雨就滲。</li>
<li><strong>山區霉氣／粉光起鼓</strong>：大坑、新社濕氣重、霉氣易生。</li>
</ul>
<h3>常見 3 種外牆防水工法</h3>
<ul>
<li><strong>彈性水泥（彈泥）</strong>：CP 值高、施工容易，適合一般老公寓與透天外牆，保固 3–5 年。</li>
<li><strong>PU 防水＋面漆</strong>：彈性與耐候性佳，七期、北屯重劃區與新成屋常用，保固 5–8 年。</li>
<li><strong>壓克力／抗霉防水漆＋批土</strong>：兼顧外觀，大坑、新社山區與老屋翻新常用，5–7 年。</li>
</ul>
<h3>施工流程（透天／老公寓）</h3>
<p>搭鷹架 → 高壓水柱清洗 → 敲除空鼓磁磚 → 裂縫填補（彈泥／PU 填縫）→ 底漆 → 防水主層 → 面漆／批土上漆。透天 3–5 樓整套約 5–10 天，老公寓 4 樓約 4–7 天，依坪數與屋況。</p>
<h3>台中外牆防水行情</h3>
<p>每坪約 NT$1,100–2,800（彈泥）／ NT$1,800–3,500（PU 加抗霉）；含搭鷹架另計（透天／公寓 3–5 樓約 NT$18,000–45,000）。山區（大坑、新社）可能含車馬費。</p>
<h3>七期新成屋／中部地震帶特別注意</h3>
<p>七期、北屯重劃區<strong>新成屋交屋驗收</strong>建議檢查外牆磁磚黏著、漆面起鼓、矽利康縫，初期問題未處理會擴大。中部地震帶<strong>地震後外牆裂縫</strong>要檢查，細微裂縫局部修補較划算。</p>
<h3>挑師傅 3 件事</h3>
<p>①<strong>實際外牆案例</strong>（新成屋／老公寓／透天）、②<strong>用料品牌</strong>（Sika、勝得寶、東莞等）、③<strong>保固年限</strong>（外牆 3–8 年合理、寫進合約）。可參考本站台中油漆防水頁。</p>
HTML;

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, 'taichung', 2, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug='taichung', category_id=2, meta_title=VALUES(meta_title),
            meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);

$stmt->execute([':slug'=>'taichung-waterproof-guide',
    ':title'=>'台中防水工程指南：七期新成屋、西區老屋、地震裂縫怎麼防',
    ':excerpt'=>'台中七期/北屯新成屋驗收、西區老屋翻新、中部地震帶外牆裂縫、大坑山區潮濕，防水工程怎麼挑、種類與行情，一篇看懂。',
    ':body'=>$g1,
    ':mt'=>'台中防水工程指南｜七期新成屋老屋外牆屋頂浴室行情｜店家好口碑',
    ':md'=>'台中防水工程怎麼做？七期/北屯新成屋驗收、西區老屋翻新、中部地震帶外牆裂縫、大坑山區潮濕的防水種類、行情與挑師傅重點，台中住戶必看。',
]);
printf("GUIDE: %-38s %d 字\n", 'taichung-waterproof-guide', mb_strlen(strip_tags($g1)));

$stmt->execute([':slug'=>'taichung-exterior-waterproof-guide',
    ':title'=>'台中外牆防水工程指南：七期新成屋、西區老公寓、地震裂縫怎麼做',
    ':excerpt'=>'台中七期/北屯新成屋外牆磁磚黏著問題、西區老公寓粉光剝落、中部地震帶裂縫，外牆防水工法、材料與行情，一篇看懂。',
    ':body'=>$g2,
    ':mt'=>'台中外牆防水工程指南｜七期新成屋老公寓工法行情｜店家好口碑',
    ':md'=>'台中外牆防水工法（彈泥／PU／抗霉）、施工流程、材料品牌與行情，七期新成屋驗收、西區老公寓翻新、中部地震帶裂縫怎麼處理，台中住戶必看。',
]);
printf("GUIDE: %-38s %d 字\n", 'taichung-exterior-waterproof-guide', mb_strlen(strip_tags($g2)));

echo "\n完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
