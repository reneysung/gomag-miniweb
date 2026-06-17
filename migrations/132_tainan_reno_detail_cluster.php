<?php
// ============================================================
// Migration 132 — 台南裝潢細清束補 2 篇（gg- 範本、台南在地化）→ 細清頁滿 4
// ------------------------------------------------------------
// 台南裝潢細清頁原 2 篇（都是懶人包/推薦清單）。補 2 篇資訊型（角度錯開）：
//   1) 台南裝潢細清指南：流程/行情/各區/怎麼挑
//   2) 粗清 vs 細清（台南交屋族版）
// 標 reno-detail,cleaning（也餵台南清潔頁）。台南在地化：中西區府城老屋/國華街海安路、
// 東區成大、永康、安平安南鹽害、南科善化新市九份子沙崙重劃區交屋潮、南部長夏灰塵。
// 語氣套「保留人味鐵則」：少金句、少「不是X而是Y」、少破折號。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/132_tainan_reno_detail_cluster.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cover = 'https://www.gomag.com.tw/uploads/guides/covers/fineclean.svg';
$hub   = 'https://www.gomag.com.tw/city/tainan/home-service';
$pReno = 'https://www.gomag.com.tw/city/tainan/home-service/reno-detail';
$pClean = 'https://www.gomag.com.tw/city/tainan/home-service/cleaning';

// ── 1) 台南裝潢細清指南 ──
$b1 = <<<HTML
<p class="gg-lead">南科這幾年帶動善化、新市、永康、九份子、沙崙一帶交屋，加上府城老屋翻修一直很熱，台南裝潢後的細清需求穩定成長。裝潢完留下的粉塵、矽利康殘膠、玻璃水痕，一般打掃清不掉。這篇整理台南裝潢細清到底在清什麼、行情多少、各區要注意什麼，以及怎麼挑團隊。</p>

<h2>台南裝潢細清在清什麼</h2>
<p>細清是裝潢完、入住前的深層清潔，目標是清完當天就能舒服住進去。</p>
<div class="gg-cards">
  <div class="gg-card"><b>🌫 全室除塵</b><span>天花板、牆面、層板、踢腳板的裝潢粉塵，連空調出風口都清。</span></div>
  <div class="gg-card"><b>🧴 矽利康殘膠</b><span>窗框、廚衛、櫃體接縫的矽利康、貼紙與標籤殘膠。</span></div>
  <div class="gg-card"><b>🪟 玻璃水痕</b><span>窗戶、淋浴拉門、鏡面的油漆點與水痕。</span></div>
  <div class="gg-card"><b>🗄 櫃體內部</b><span>系統櫃、抽屜、五金軌道裡的木屑與粉塵。</span></div>
  <div class="gg-card"><b>🧹 地板處理</b><span>木地板、磁磚的施工汙漬清理與初步養護。</span></div>
  <div class="gg-card"><b>🚽 廚衛細清</b><span>新衛浴、廚具的保護膜、矽利康與水垢前處理。</span></div>
</div>

<h2>台南裝潢細清行情</h2>
<p>細清以坪數計價，櫃體與玻璃越多越貴。以下為台南常見區間，實際以現場估價為準。</p>
<table class="gg-table">
  <thead><tr><th>坪數／房型</th><th>常見行情</th><th>備註</th></tr></thead>
  <tbody>
    <tr><td>每坪單價</td><td>NT\$120–250 / 坪</td><td>櫃體、玻璃多偏高</td></tr>
    <tr><td>套房／小宅（10–20 坪）</td><td>約 NT\$3,000–8,000</td><td>東區、成大周邊租屋常見</td></tr>
    <tr><td>2–3 房（25–40 坪）</td><td>約 NT\$8,000–20,000</td><td>永康、南科重劃區常見</td></tr>
    <tr><td>透天／大坪數（40 坪+）</td><td>NT\$20,000 起</td><td>府城老透天、樓層多另計</td></tr>
  </tbody>
</table>

<h2>台南各區細清重點</h2>
<ul>
  <li><strong>中西區、府城老屋</strong>：老透天、街屋翻修多，常有老木作與石材，細清要懂得保護舊建材，國華街、海安路一帶案子不少。</li>
  <li><strong>東區（成大商圈）、永康</strong>：租屋與標準格局新成屋多，2–3 房細清需求集中，學生季交屋潮要早約。</li>
  <li><strong>安平、安南</strong>：靠海、鹽分高，金屬五金與窗框容易卡鹽，細清後最好順便檢查。</li>
  <li><strong>南科周邊（善化、新市、九份子、沙崙）</strong>：新建案集中交屋，2–3 房細清量大，旺季團隊很滿。</li>
</ul>
<div class="gg-tip">
  <b>💡 台南在地提醒</b>
  <p>台南夏天長、灰塵多，交屋後若先空關一段時間會再積塵，建議家具家電進場前、入住前再做細清。南科交屋旺季團隊排得滿，提早兩三週預約比較保險。</p>
</div>

<h2>怎麼挑台南細清團隊</h2>
<ol>
  <li><strong>先確認是細清不是粗清</strong>：報價含不含玻璃、櫃內、矽利康殘膠（看<a href="{$pReno}">粗清 vs 細清</a>）。</li>
  <li><strong>有沒有拆裝、損壞保固</strong>：細清會動到五金與玻璃，要講清楚損壞由誰負責。</li>
  <li><strong>現場估價、報價透明</strong>：每坪單價、含哪些、預計工時人數寫清楚。</li>
  <li><strong>完工驗收</strong>：逐區檢查，不滿意能不能補清。</li>
  <li><strong>在地口碑</strong>：看 Google 評論的分數和評論數，台南設計師、統包的推薦也可參考。</li>
</ol>
<div class="gg-warn">
  <b>⚠️ 避雷</b>
  <p>不到府估價只給均一價、報價特別低的，常是用粗清的料收細清的錢，或現場再加價。訂金不超過三成，驗收滿意再付清。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">台南裝潢細清一坪多少錢？</div>
  <div class="gg-a">約 NT\$120–250／坪，櫃體和玻璃多會偏高。2–3 房整戶常見 NT\$8,000–20,000，實際看現場估價。</div>
  <div class="gg-q">建商交屋會清，還要自己找細清嗎？</div>
  <div class="gg-a">建商多半只做粗清（清場），清完還是有粉塵，要乾淨入住通常得自己另外找細清。</div>
  <div class="gg-q">細清要做多久、什麼時候做？</div>
  <div class="gg-a">整戶看坪數約半天到一天。建議家具進場前、入住前做，台南灰塵多別太早清。</div>
  <div class="gg-q">府城老屋的細清和新成屋一樣嗎？</div>
  <div class="gg-a">老屋常有舊木作、石材與較複雜的格局，細清要懂保護舊建材、工時也較長，找有老屋經驗的團隊比較放心。</div>
  <div class="gg-q">細清和一般居家清潔差在哪？</div>
  <div class="gg-a"><a href="{$pClean}">一般清潔</a>是日常維護；細清專門處理裝潢後的粉塵殘膠，工具藥水不同，費用約一般清潔的 1.5 到 2 倍。</div>
</div>

<p>找台南細清團隊看 <a href="{$pReno}"><strong>台南裝潢細清</strong></a>，日常清潔看 <a href="{$pClean}">台南清潔公司</a>，更多服務看 <a href="{$hub}">台南居家服務</a>。</p>
HTML;

// ── 2) 粗清 vs 細清（台南版）──
$b2 = <<<HTML
<p class="gg-lead">台南交屋時，建商常說「會幫您清潔」，結果搬進去才發現滿屋粉塵、窗框一堆矽利康，因為那通常是「粗清」不是「細清」。這兩種差很多，搞錯可能用粗清的料被收細清的錢。這篇用台南交屋的實際情況說清楚。</p>

<h2>粗清和細清，一張表看懂</h2>
<table class="gg-table">
  <thead><tr><th>項目</th><th>粗清（初步清潔）</th><th>細清（裝潢後深層）</th></tr></thead>
  <tbody>
    <tr><td>目的</td><td>能進場</td><td>能入住</td></tr>
    <tr><td>誰做</td><td>建商、工班、統包</td><td>專業細清團隊</td></tr>
    <tr><td>內容</td><td>大型廢料、木屑、垃圾</td><td>粉塵、矽利康殘膠、玻璃水痕、櫃內、軌道</td></tr>
    <tr><td>費用</td><td>低（常含在工程裡）</td><td>每坪 NT\$120–250</td></tr>
    <tr><td>清完能入住嗎</td><td>不行，還是髒</td><td>可以，當天就能住</td></tr>
  </tbody>
</table>

<h2>各自實際在做什麼</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧱 粗清</b><span>清掉裝潢留下的大型廢料、板材邊角、包裝和明顯垃圾，讓空間清得出來、進得了場。台南交屋時建商或工班順手做的多半是這個。</span></div>
  <div class="gg-card"><b>✨ 細清</b><span>逐一處理粉塵、矽利康殘膠、玻璃水痕，連櫃內、五金軌道、燈具縫隙都清乾淨，做完當天就能搬進去住。</span></div>
</div>

<h2>台南交屋族該選哪種</h2>
<p>南科、永康、九份子重劃區交屋要直接入住的，選細清，多數人要的就是這個。如果之後還要再進場做二次工程或追加裝修，先粗清即可。簡單記：要住人就要細清。</p>

<div class="gg-warn">
  <b>⚠️ 費用怎麼抓才不會被當細清收</b>
  <p>細清每坪約 NT\$120–250，2–3 房整戶約 NT\$8,000–20,000，明顯高於一般打掃。下訂前問三件事：報的是粗清還是細清、含不含玻璃和櫃內和矽利康殘膠、完工驗收標準。實際以現場估價為準。</p>
</div>

<h2>台南在地提醒</h2>
<p>南科新建案集中交屋的時段，細清團隊會很滿，提早兩三週預約。台南夏天長、灰塵多，交屋後空關會再積塵，建議入住前再清。府城老透天樓層多、玻璃案工時長，估價要算清楚。</p>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">建商說交屋會清潔，那是細清嗎？</div>
  <div class="gg-a">多半只是粗清（清場），不等於可以入住的細清。要乾淨入住通常得自己另外找細清。</div>
  <div class="gg-q">粗清可以自己做嗎？</div>
  <div class="gg-a">大型垃圾可以自己處理，粉塵和殘膠的細清建議交給專業，自己做容易刮傷材質又清不乾淨。</div>
  <div class="gg-q">細清要做多久？</div>
  <div class="gg-a">整戶看坪數約半天到一天，玻璃和櫃體多會更久。</div>
  <div class="gg-q">細清跟裝潢前的保護工程一樣嗎？</div>
  <div class="gg-a">不一樣。保護工程是施工前鋪設保護材，細清是施工後的深層清潔。</div>
</div>

<p>找台南細清團隊看 <a href="{$pReno}"><strong>台南裝潢細清</strong></a>，日常清潔看 <a href="{$pClean}">台南清潔公司</a>，更多服務看 <a href="{$hub}">台南居家服務</a>。</p>
HTML;

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'tainan', 2, 'reno-detail,cleaning', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug='reno-detail,cleaning', cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");

$gs->execute([
    'tainan-reno-detail-guide',
    '台南裝潢細清指南：流程、行情、各區重點、怎麼挑團隊',
    '台南裝潢細清怎麼做？南科交屋潮與府城老屋翻修必看：細清清什麼、每坪 NT$120–250 行情、中西區/東區/安平/南科各區重點與挑團隊重點，入住前一篇看懂。',
    $b1, $cover,
    '台南裝潢細清指南｜流程行情各區怎麼挑｜店家好口碑',
    '台南裝潢細清指南：細清清什麼、每坪 NT$120–250 行情、中西區府城老屋/東區成大/安平鹽害/南科重劃區各區重點、挑團隊與避雷，台南交屋入住前必看。',
]);
$gs->execute([
    'tainan-rough-vs-detail-cleaning',
    '粗清 vs 細清差在哪？台南交屋族該選哪種、費用怎麼抓',
    '台南交屋，建商說的清潔是粗清還是細清？兩者對比、該選哪種、費用怎麼抓不被當細清收，南科、永康、九份子重劃區交屋族入住前必看。',
    $b2, $cover,
    '粗清vs細清差在哪｜台南交屋怎麼選費用｜店家好口碑',
    '粗清（清廢料）和細清（深層可入住）差在哪？台南交屋該選哪種、費用每坪 NT$120–250 怎麼抓不被當細清收，南科、永康、九份子重劃區交屋族必看。',
]);

echo "✅ 寫入 2 篇台南細清文\n";
echo "\n== 台南裝潢細清(reno-detail)頁 修後 ==\n";
$rg = $db->prepare("SELECT title, service_slug FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='tainan' AND (FIND_IN_SET('reno-detail',service_slug)>0 OR FIND_IN_SET('cleaning',service_slug)>0) ORDER BY (FIND_IN_SET('reno-detail',service_slug)>0) DESC, published_at DESC LIMIT 4");
$rg->execute();
$n=0; foreach ($rg as $r) { $n++; printf("  %d. %s (svc=%s)\n", $n, $r['title'], $r['service_slug']); }
echo "  → 共 {$n} 篇\n預覽：{$pReno}\n";
