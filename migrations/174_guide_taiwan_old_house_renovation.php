<?php
// ============================================================
// Migration 174 — 新攻略：全台老屋翻新流程指南 2026（通用知識文）
// ------------------------------------------------------------
// 缺口：站上已有台南版老屋翻新(city guide)，但無「通用流程樞紐文」。
//   w-design 分析顯示上游決策知識文是我們最薄的一層。這篇補通用樞紐，
//   內鏈導向各城市老屋翻新頁與 interior-design 子服務頁。
// 反競食(任務 E)：與 taiwan-interior-design-guide-2026 主題錯開——
//   那篇打「設計費行情/挑設計師」，這篇打「老屋翻新流程/結構管線/費用」，
//   不同搜尋意圖、不同關鍵字束。
// 規格(WRITING-GUIDES.md)：通用 city=NULL、cat=2(居家服務)、
//   svc=interior-design、cover 絕對URL、無h1、gg-元件、內嵌 FAQPage JSON-LD。
// 不能無中生有：費用行情用市場公開區間(6–18萬/坪，多來源)＋對齊本站既有
//   台南老屋翻新文的 6–12萬 下錨，分級呈現；補助只提危老/耐震/老屋健檢
//   「概念與查詢方向」，不捏造任何具體金額或資格。
// 內鏈 slug 皆已驗證存在(interior-design 子服務頁 active: tainan/kaohsiung/
//   taichung/taitung；城市老屋翻新 guide: tainan-old-house-renovation、
//   kaohsiung-interior-design-cost；驗屋通用文 taiwan-home-inspection-guide-2026)。
//
// 冪等：以 slug 查重，存在則 UPDATE、否則 INSERT。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/174_guide_taiwan_old_house_renovation.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug       = 'taiwan-old-house-renovation-guide-2026';
$title      = '全台老屋翻新流程指南 2026：評估、流程、費用行情到補助一次看懂';
$excerpt    = '老屋翻新前先搞懂結構、管線、防水、違建 4 件事，完整流程 8 階段、每坪 NT$6–15 萬行情與隱蔽工程追加、2026 危老與耐震補助方向、挑團隊重點與常見地雷，避開翻新地雷。';
$metaTitle  = '全台老屋翻新流程指南 2026｜評估流程費用行情與補助一次看｜店家好口碑';
$metaDesc   = '老屋翻新怎麼開始？先評估結構、管線、防水、違建，完整 8 階段流程、每坪 NT$6–15 萬費用行情與隱蔽工程追加、2026 危老／耐震補助查詢方向、挑團隊重點與常見地雷，全台老屋、老透天翻新一次看懂。';
$cover      = 'https://www.gomag.com.tw/uploads/guides/covers/renovation.svg';
$citySlug   = null;      // 通用
$categoryId = 8;         // 專業服務（interior-design 子服務歸在 professional，與既有 interior-design guide / geo 頁一致；home-service 會 301 轉 professional）
$serviceSlug= 'interior-design';

// FAQPage 的 Q/A（gg-faq 與 JSON-LD 共用同一份，內容一致）
$faqs = [
  ['老屋翻新一坪大概多少錢？',
   '看範圍。全室輕裝修（不動格局管線）每坪約 NT$6–10 萬；全室翻新（含水電、防水、格局變動）每坪約 NT$10–15 萬；只做廚衛、油漆、地板等局部翻修則以項目計、彈性大。屋況落差大，務必現場勘估後報價，別只看每坪均價。'],
  ['老屋翻新要做多久？',
   '全室翻新一般約 2–4 個月，視屋況、格局變動與客製程度而定。老屋因為拆除後才看得到真實屋況，若遇到結構或管線問題會延長，排程時建議預留彈性。'],
  ['老屋翻新前要先驗屋嗎？',
   '中古屋建議簽約前先驗屋，把漏水、壁癌、海砂、結構問題查清楚，既能作為議價依據，也能讓翻新報價更準。驗屋與翻新是兩件事，可參考本站的全台交屋驗屋指南。'],
  ['海砂屋、輻射屋還能翻新嗎？',
   '要先找結構技師與專業檢測評估。氯離子（海砂）含量過高會腐蝕鋼筋、影響結構安全，嚴重者不建議大額翻新。花大錢翻新前，先確認房子值不值得投入。'],
  ['老屋翻新和危老重建差在哪？',
   '翻新是在原建築內做裝修與工程更新；危老重建是依「都市危險及老舊建築物加速重建條例」把舊屋拆掉重蓋，屬於重建、有容積獎勵與補助，門檻與流程完全不同。想清楚是要「整理現有的」還是「打掉重蓋」。'],
  ['預算有限，可以只做局部翻修嗎？',
   '可以，而且很常見。若結構、管線、防水沒有立即問題，可先做廚衛、油漆、地板等影響生活品質最大的部分，其餘分階段進行。但漏水、電線老化這類安全問題別拖，會越拖越貴。'],
];

// gg-faq HTML
$ggFaq = "<div class=\"gg-faq\">\n";
foreach ($faqs as $qa) {
    $ggFaq .= '  <div class="gg-q">' . $qa[0] . "</div>\n";
    $ggFaq .= '  <div class="gg-a">' . $qa[1] . "</div>\n";
}
$ggFaq .= "</div>\n";

// FAQPage JSON-LD（與 gg-faq 同一份 Q/A）
$faqLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => array_map(fn($qa) => [
        '@type' => 'Question',
        'name'  => $qa[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
    ], $faqs),
];
$faqScript = '<script type="application/ld+json">'
    . json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    . '</script>';

$body = <<<HTML
<p class="gg-lead">買老屋、繼承老宅，或想把住了多年的老透天整理得更好住，翻新是繞不過的一關。老屋翻新和新成屋裝修是兩種工程：新成屋是在乾淨的殼裡做設計，老屋得先把看不見的結構、管線、防水處理好，才輪到美觀。這篇把全台老屋翻新的評估、流程、費用、補助跟挑團隊一次講清楚，看完再去找廠商比較，比較不會踩雷。</p>

<h2 id="toc">📖 文章目錄</h2>
<ol>
  <li><a href="#check">動工前先評估這 4 件事</a></li>
  <li><a href="#flow">完整翻新流程 8 階段</a></li>
  <li><a href="#price">費用行情：一坪多少才合理</a></li>
  <li><a href="#vs">老屋翻新 vs 新成屋裝修</a></li>
  <li><a href="#scope">全室翻新還是局部翻修</a></li>
  <li><a href="#subsidy">2026 補助與法規：先查再動工</a></li>
  <li><a href="#trap">老屋翻新常見地雷</a></li>
  <li><a href="#pick">怎麼挑老屋翻新團隊</a></li>
  <li><a href="#faq">常見問題 FAQ</a></li>
</ol>

<h2 id="check">動工前先評估這 4 件事</h2>
<p>老屋翻新最貴的錢常花在看不見的地方。動工前把這 4 件事評估清楚，後面的預算與設計才站得住腳。</p>
<div class="gg-cards">
  <div class="gg-card"><b>🏗️ 結構安全</b><span>屋齡 30 年以上、樑柱裂縫、鋼筋外露、海砂屋（氯離子）疑慮要先評估。必要時請結構技師出報告，這關係到能不能動格局、要不要補強，也決定房子值不值得投入大錢。</span></div>
  <div class="gg-card"><b>🔌 管線老化</b><span>老屋電線多是舊線徑、迴路不足，冷氣一開就跳電；水管可能是鏽蝕的鐵管。全屋水電更新是老屋翻新的核心工程，不是選配，別只做表面。</span></div>
  <div class="gg-card"><b>💧 防水與漏水</b><span>頂樓、外牆、浴室、窗框是老屋漏水熱區。要先找到源頭再施工，否則面子工程做得再漂亮，壁癌半年後照樣冒出來。</span></div>
  <div class="gg-card"><b>📋 違建與法規</b><span>頂樓加蓋、陽台外推、夾層可能涉及違建，動到會有被舉報或無法報備的風險。動工前先確認產權範圍與可合法施工的部分。</span></div>
</div>

<h2 id="flow">完整翻新流程 8 階段</h2>
<p>老屋翻新的流程和新成屋大致相同，差別在中段的隱蔽工程份量重很多。標準流程如下：</p>
<ol>
  <li><strong>現場勘估與需求確認</strong>：屋況、格局、漏水、管線先看過一輪，同時談清楚居住需求與預算上限。</li>
  <li><strong>設計規劃與 3D</strong>：平面配置、動線、收納、風格定案，複雜案子出 3D 圖對齊想像。</li>
  <li><strong>簽約與報價拆項</strong>：設計費、監工費、工程款分開列，材料品牌型號寫進附件。</li>
  <li><strong>拆除</strong>：拆到見底，這時候屋況才真正攤開，也常是追加的起點。</li>
  <li><strong>結構／防水／水電（隱蔽工程）</strong>：補強、重做防水、全屋換線換管，這段做得紮實，房子才耐住。</li>
  <li><strong>泥作／木作／系統櫃</strong>：地坪、牆面、櫃體成形，空間輪廓出來。</li>
  <li><strong>油漆／設備安裝</strong>：粉刷、燈具、衛浴廚具、冷氣就定位。</li>
  <li><strong>清潔驗收與保固</strong>：對照估價單逐項驗，水電、防水、五金都實測，確認保固範圍與年限。</li>
</ol>
<div class="gg-tip"><b>💡 隱蔽工程是關鍵</b><p>結構、防水、水電這段最花錢也最重要，而且要拆除後才看得到真實屋況，這時候常有追加。挑團隊時就先問清楚追加怎麼計、由誰確認、要不要先徵得同意，別等帳單來才知道。</p></div>

<h2 id="price">費用行情：一坪多少才合理</h2>
<p>老屋翻新（連工帶料）的價格區間很寬，關鍵在「動到多少」。以下是全台常見的分級行情，實際仍以現場勘估為準。</p>
<table class="gg-table">
<thead><tr><th>翻新範圍</th><th>每坪行情（連工帶料）</th><th>說明</th></tr></thead>
<tbody>
<tr><td>局部翻修（廚衛／油漆／地板）</td><td>依項目計，非每坪</td><td>彈性最大，數十萬內常可完成</td></tr>
<tr><td>全室輕裝修（不動格局管線）</td><td>NT$6–10 萬</td><td>以表面更新、換設備為主</td></tr>
<tr><td>全室翻新（含水電／防水／格局）</td><td>NT$10–15 萬</td><td>老屋主流，隱蔽工程齊做</td></tr>
<tr><td>結構補強／海砂屋處理</td><td>另計</td><td>需結構技師評估後報價</td></tr>
</tbody>
</table>
<div class="gg-warn"><b>⚠️ 最容易被低估的是隱蔽工程</b><p>管線開挖、防水重做、結構補強，拆開後追加 3–5 萬很常見，整體隱蔽工程加總達 30–80 萬也不誇張。預算務必預留 10–15% 預備金，只看每坪均價很容易失準。</p></div>

<h2 id="vs">老屋翻新 vs 新成屋裝修</h2>
<p>兩者都叫裝修，但工程性質差很多，預算與心理準備也不同。</p>
<table class="gg-table">
<thead><tr><th>比較項目</th><th>老屋翻新</th><th>新成屋裝修</th></tr></thead>
<tbody>
<tr><td>工程起點</td><td>先處理結構、管線、防水</td><td>乾淨毛胚或建商標配</td></tr>
<tr><td>隱蔽工程</td><td>幾乎必做，占比高</td><td>相對少</td></tr>
<tr><td>屋況不確定性</td><td>高，拆了才知道</td><td>低</td></tr>
<tr><td>費用可控性</td><td>追加風險較高</td><td>較好抓</td></tr>
<tr><td>適合誰</td><td>地段好的老屋、老宅整理、預算彈性者</td><td>想一次到位、省心的人</td></tr>
</tbody>
</table>

<h2 id="scope">全室翻新還是局部翻修</h2>
<p>不一定要一次全做。用三個問題判斷範圍：這房子還要住多久、預算多少、屋況有沒有立即的安全問題。</p>
<div class="gg-cards">
  <div class="gg-card"><b>適合全室翻新</b><span>打算長住、格局不合用、管線防水都到了該換的年紀。一次做到位，省下日後重複施工與搬遷的麻煩。</span></div>
  <div class="gg-card"><b>適合局部翻修</b><span>結構管線沒有立即問題、預算有限，或只是想改善生活品質最有感的地方（廚衛、油漆、地板），可分階段進行。</span></div>
</div>
<div class="gg-tip"><b>💡 安全問題別分階段</b><p>漏水源頭、電線老化、結構裂縫這類問題不要為了省錢往後拖，會越拖越貴，也可能影響居住安全。可以先做這些，美觀的部分再慢慢來。</p></div>

<h2 id="subsidy">2026 補助與法規：先查再動工</h2>
<p>老屋相關的公部門資源分兩類，別混為一談。一類是「重建」：依「都市危險及老舊建築物加速重建條例」的危老重建、都市更新，把舊屋拆掉重蓋，有容積獎勵；另一類是「安全」：耐震能力初步評估補助、部分縣市的老屋健檢或修繕補助。翻新裝修本身多半不在補助範圍，但若你的老屋涉及結構安全或有重建打算，這些資源值得先查。</p>
<div class="gg-tip"><b>💡 補助資格與金額每年公告不同</b><p>危老、耐震評估、老屋健檢的適用條件、補助金額與申請窗口，依各縣市政府年度公告而定，有些還規定要「先申請、後施工」。動工前先上你所在縣市的都發局或建管處官網查詢、或直接洽詢，別只聽廠商片面說法。</p></div>

<h2 id="trap">老屋翻新常見地雷</h2>
<p>這些是老屋翻新最常見、也最花錢的坑，開工前先對照一遍。</p>
<div class="gg-warn"><b>⚠️ 六個最常見的翻新地雷</b><p>
① 只做表面沒處理漏水源頭，壁癌半年後復發。<br>
② 貪便宜跳過結構評估，動到承重牆才發現不能拆。<br>
③ 報價沒拆項（設計／監工／工程款混在一起），追加時說不清。<br>
④ 沒簽分階段付款，尾款一次付掉，最後失去談判籌碼。<br>
⑤ 工班沒有老屋經驗，巷弄清運、舊結構、老管線一再卡關。<br>
⑥ 把違建部分照樣施工，事後被檢舉要求拆除。
</p></div>

<h2 id="pick">怎麼挑老屋翻新團隊</h2>
<p>老屋翻新比新屋更吃經驗，挑團隊時看這幾點：</p>
<ol>
  <li><strong>有老屋實績</strong>：看過往案例是不是真的做過老屋、老透天，而不只是新成屋。</li>
  <li><strong>會先評估再報價</strong>：願意先做結構與漏水評估、現場勘估後才報價，而非電話報均價。</li>
  <li><strong>報價拆得清楚</strong>：設計、監工、工程款分項，材料寫明品牌型號。</li>
  <li><strong>合約與付款明確</strong>：分階段付款、追加須事先同意、保固範圍白紙黑字。</li>
  <li><strong>溝通給得出替代方案</strong>：預算卡住時能提替代做法，而不是一句「這樣不行」。</li>
</ol>

<h2 id="faq">常見問題 FAQ</h2>
{$ggFaq}

<h2>看完就開始比較</h2>
<p>老屋翻新沒有標準答案，屋況決定一切。抓好順序：先評估結構管線防水、再談設計與報價、簽約拆清楚分階段付款、施工留好追加確認、最後驗收保固。想找在地有老屋經驗的團隊，可以從下面的城市頁與相關攻略開始比較。</p>
<p><strong>城市老屋翻新延伸閱讀</strong>：<a href="/guide/tainan-old-house-renovation">台南老屋翻新全攻略</a>｜<a href="/guide/kaohsiung-interior-design-cost">高雄室內設計費用行情（含老屋翻新）</a></p>
<p><strong>在地室內設計、統包團隊</strong>：<a href="/city/tainan/professional/interior-design">台南室內設計</a>｜<a href="/city/kaohsiung/professional/interior-design">高雄室內設計</a>｜<a href="/city/taichung/professional/interior-design">台中室內設計</a></p>
<p><strong>相關攻略</strong>：<a href="/guide/taiwan-home-inspection-guide-2026">全台交屋驗屋指南</a>｜<a href="/guide/taiwan-interior-design-guide-2026">全台室內設計指南</a></p>

{$faqScript}
HTML;

$chars = mb_strlen(preg_replace('/\s+/u', '', strip_tags($body)), 'UTF-8');

// 冪等 upsert
$exists = $db->prepare("SELECT id FROM guides WHERE slug = ?");
$exists->execute([$slug]);
$id = $exists->fetchColumn();

if ($id) {
    $db->prepare("UPDATE guides SET title=?, excerpt=?, body_html=?, cover_image=?, city_slug=?, category_id=?, service_slug=?, meta_title=?, meta_desc=?, status='published', noindex=0, updated_at=NOW() WHERE id=?")
       ->execute([$title, $excerpt, $body, $cover, $citySlug, $categoryId, $serviceSlug, $metaTitle, $metaDesc, $id]);
    echo "🔁 已更新既有 guide（id={$id}）\n";
} else {
    $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, service_slug, meta_title, meta_desc, status, noindex, published_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?, 'published', 0, NOW(), NOW(), NOW())")
       ->execute([$slug, $title, $excerpt, $body, $cover, $citySlug, $categoryId, $serviceSlug, $metaTitle, $metaDesc]);
    echo "✅ 已新增 guide（id={$db->lastInsertId()}）\n";
}

echo "   slug: {$slug}\n";
echo "   內文純文字約 {$chars} 字\n";
echo "   service_slug: {$serviceSlug}  city: (通用)  cover: {$cover}\n";
echo "   URL: https://www.gomag.com.tw/guide/{$slug}\n";
