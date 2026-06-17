<?php
// ============================================================
// Migration 130 — 台中裝潢細清束補 2 篇（gg- 範本、台中在地化）
// ------------------------------------------------------------
// 台中裝潢細清(reno-detail)頁現只 2 篇（懶人包+交屋驗屋），無 cleaning 可補位。
// 補 2 篇細清主題文（角度錯開）→ 滿 4，並標 reno-detail,cleaning（也餵台中清潔頁）：
//   1) 台中裝潢細清指南：流程/行情/各區/怎麼挑
//   2) 粗清 vs 細清（台中版）
// 台中在地化：七期/北屯廍子機捷/西屯/南屯文心/烏日高鐵/太平大里、盆地氣候、新成屋交屋潮。
// gg- 排版範本（migration 128/129 風格）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/130_taichung_reno_detail_cluster.php
// 冪等：INSERT … ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cover = 'https://www.gomag.com.tw/uploads/guides/covers/fineclean.svg';
$hub   = 'https://www.gomag.com.tw/city/taichung/home-service';
$pReno = 'https://www.gomag.com.tw/city/taichung/home-service/reno-detail';
$pClean = 'https://www.gomag.com.tw/city/taichung/home-service/cleaning';

// ── 1) 台中裝潢細清指南 ──
$b1 = <<<HTML
<p class="gg-lead">台中新成屋交屋潮一波接一波——<strong>七期、北屯廍子、西屯、南屯文心路、烏日高鐵特區</strong>重劃區大量交屋，加上中科、台積電帶動的換屋裝修，裝潢後的「細清」需求特別旺。但裝潢完的粉塵、矽利康殘膠、玻璃水痕不是一般打掃清得掉。這篇講清楚台中裝潢細清在做什麼、行情多少、各區重點與怎麼挑團隊。</p>

<h2>台中裝潢細清在做什麼？</h2>
<p>細清是「裝潢後、入住前」的深層清潔，目標是<strong>當天就能舒服搬進去住</strong>：</p>
<div class="gg-cards">
  <div class="gg-card"><b>🌫 全室除塵</b><span>天花板、牆面、層板、踢腳板的裝潢粉塵，連空調出風口都清。</span></div>
  <div class="gg-card"><b>🧴 矽利康殘膠</b><span>窗框、廚衛、櫃體接縫的矽利康與貼紙殘膠去除。</span></div>
  <div class="gg-card"><b>🪟 玻璃水痕</b><span>窗戶、淋浴拉門、鏡面的油漆點與水痕處理。</span></div>
  <div class="gg-card"><b>🗄 櫃體內部</b><span>系統櫃、抽屜、五金軌道內的木屑粉塵。</span></div>
  <div class="gg-card"><b>🧹 地板養護</b><span>木地板、磁磚的施工汙漬清理與初步養護。</span></div>
  <div class="gg-card"><b>🚽 廚衛細清</b><span>新衛浴、廚具的保護膜、矽利康與水垢前處理。</span></div>
</div>

<h2>台中裝潢細清行情參考</h2>
<p>細清以坪數計價，受粉塵量、櫃體與玻璃多寡影響，<strong>實際以現場估價為準</strong>：</p>
<table class="gg-table">
  <thead><tr><th>坪數／房型</th><th>常見行情</th><th>備註</th></tr></thead>
  <tbody>
    <tr><td>每坪單價</td><td>NT\$120–250 / 坪</td><td>櫃體、玻璃多偏高</td></tr>
    <tr><td>套房／小宅（10–20 坪）</td><td>約 NT\$3,000–8,000</td><td>—</td></tr>
    <tr><td>2–3 房（25–40 坪）</td><td>約 NT\$8,000–20,000</td><td>七期、重劃區常見</td></tr>
    <tr><td>透天／大坪數（40 坪+）</td><td>NT\$20,000 起</td><td>樓層多另計</td></tr>
  </tbody>
</table>

<h2>台中各區細清重點</h2>
<ul>
  <li><strong>七期、市政特區</strong>：豪宅大坪數、進口建材與大面玻璃多，細清重玻璃水痕與石材養護，要找有高端案經驗的團隊。</li>
  <li><strong>北屯（廍子、機捷特區）、西屯</strong>：新成屋交屋量最大，2–3 房標準格局細清需求集中，旺季要早約。</li>
  <li><strong>南屯（文心路、五期）</strong>：新舊交雜，中古翻修後細清也多。</li>
  <li><strong>烏日高鐵特區、太平、大里</strong>：重劃區與透天並存，透天樓層多、坪數大，估價要說明清楚。</li>
</ul>
<div class="gg-tip">
  <b>💡 台中在地提醒</b>
  <p>台中盆地夏季悶熱、灰塵重，交屋後若空關一段時間粉塵會再積，建議<strong>入住前再細清、別太早做</strong>；中科、台積電換屋族多，旺季（建案集中交屋）細清團隊很滿，提早 2–3 週預約。</p>
</div>

<h2>怎麼挑台中細清團隊？</h2>
<ol>
  <li><strong>確認是「細清」不是「粗清」</strong>：報價含不含玻璃、櫃內、矽利康殘膠（<a href="{$pReno}">粗清vs細清差在哪</a>）。</li>
  <li><strong>拆裝／損壞保固</strong>：細清會動到五金、玻璃，要確認損壞由誰負責。</li>
  <li><strong>現場估價、報價透明</strong>：每坪單價、含哪些、預計工時人數寫清楚。</li>
  <li><strong>完工驗收標準</strong>：逐區檢查、不滿意能否補清。</li>
  <li><strong>在地口碑</strong>：Google 評論分數＋評論數，台中設計師／統包推薦也是參考。</li>
</ol>

<div class="gg-warn">
  <b>⚠️ 避雷</b>
  <p>「均一價、不到府估」「報價超低」常是用粗清的料收細清的錢，或現場再加價。訂金不超過 30%、驗收滿意再付清。</p>
</div>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">台中裝潢細清一坪多少錢？</div>
  <div class="gg-a">約 NT\$120–250／坪，櫃體、玻璃多偏高。2–3 房整戶常見 NT\$8,000–20,000，實際以現場估價為準。</div>
  <div class="gg-q">交屋建商會清，還要自己找細清嗎？</div>
  <div class="gg-a">建商多半只做粗清（清場），不等於可入住。要乾淨搬進去通常得自己另找細清。</div>
  <div class="gg-q">細清要多久？什麼時候做最好？</div>
  <div class="gg-a">整戶視坪數約半天到一天。建議家具家電進場前、入住前做，台中灰塵重別太早清。</div>
  <div class="gg-q">七期豪宅細清和一般新成屋差在哪？</div>
  <div class="gg-a">豪宅大面玻璃、石材、進口建材多，重玻璃水痕與石材養護、工時長，建議找有高端案經驗的團隊。</div>
  <div class="gg-q">細清和一般居家清潔一樣嗎？</div>
  <div class="gg-a">不一樣。<a href="{$pClean}">一般清潔</a>是日常維護；細清專處理裝潢後粉塵殘膠，工具藥水不同、費用約 1.5–2 倍。</div>
</div>

<p>找台中細清團隊見 <a href="{$pReno}"><strong>台中裝潢細清</strong></a>；日常清潔見 <a href="{$pClean}">台中清潔公司</a>；更多服務見 <a href="{$hub}">台中居家服務</a>。</p>
HTML;

// ── 2) 粗清 vs 細清（台中版）──
$b2 = <<<HTML
<p class="gg-lead">台中新成屋交屋時，建商常說「會幫您清潔」，但你搬進去卻發現滿屋粉塵、窗上一堆矽利康——因為那是「粗清」不是「細清」。這兩個差很多，搞錯不只白花錢，還可能<strong>用粗清的料被收細清的錢</strong>。這篇用台中交屋實況一次說清楚。</p>

<h2>粗清 vs 細清，一張表看懂</h2>
<table class="gg-table">
  <thead><tr><th>項目</th><th>粗清（初步清潔）</th><th>細清（裝潢後深層）</th></tr></thead>
  <tbody>
    <tr><td>目的</td><td>能進場</td><td>能入住</td></tr>
    <tr><td>誰做</td><td>建商／工班／統包</td><td>專業細清團隊</td></tr>
    <tr><td>內容</td><td>大型廢料、木屑、垃圾</td><td>粉塵、矽利康殘膠、玻璃水痕、櫃內、軌道</td></tr>
    <tr><td>費用</td><td>低（常含工程）</td><td>每坪 NT\$120–250</td></tr>
    <tr><td>做完能入住？</td><td>不行，還是髒</td><td>可以，當天舒服入住</td></tr>
  </tbody>
</table>

<h2>各自實際做什麼</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧱 粗清</b><span>清掉裝潢大型廢料、板材邊角、包裝、明顯垃圾，讓空間「進得了場」。台中交屋時建商／工班常順手做的就是這個。</span></div>
  <div class="gg-card"><b>✨ 細清</b><span>逐一處理粉塵、矽利康殘膠、玻璃水痕，連櫃內、五金軌道、燈具縫隙都清，做完當天就能搬進住。</span></div>
</div>

<h2>台中交屋族該選哪種？</h2>
<p>七期、北屯、西屯重劃區交屋要直接入住 → <strong>細清</strong>（多數人要的是這個）。若還要再進場施工（二次工程、追加裝修）→ 粗清即可。簡單記：<strong>「要住人就要細清」</strong>。</p>

<div class="gg-warn">
  <b>⚠️ 費用怎麼抓才不被當細清收</b>
  <p>細清每坪約 NT\$120–250（2–3 房整戶約 NT\$8,000–20,000），明顯高於一般打掃。下訂前問清楚：① 報的是粗清還是細清；② 含不含玻璃、櫃內、矽利康殘膠；③ 完工驗收標準。<em>實際以現場估價為準。</em></p>
</div>

<h2>台中在地提醒</h2>
<p>台中新建案集中交屋（七期、北屯廍子、烏日高鐵），<strong>旺季細清團隊很滿</strong>，提早 2–3 週約。盆地灰塵重，交屋後空關會再積塵，建議入住前再細清。豪宅、大面玻璃案工時長，估價要算清楚。</p>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">建商說交屋會清潔，是細清嗎？</div>
  <div class="gg-a">多半只是粗清（清場），不等於可入住的細清。要乾淨入住通常得自己另找細清。</div>
  <div class="gg-q">粗清可以自己做嗎？</div>
  <div class="gg-a">大型垃圾可以；粉塵與殘膠的細清建議交專業，自己做容易刮傷又清不乾淨。</div>
  <div class="gg-q">細清要做多久？</div>
  <div class="gg-a">整戶視坪數約半天到一天，玻璃、櫃體多會更久。</div>
  <div class="gg-q">細清和裝潢前的保護工程一樣嗎？</div>
  <div class="gg-a">不一樣。保護工程是施工前鋪設保護材；細清是施工後的深層清潔。</div>
</div>

<p>找台中細清團隊見 <a href="{$pReno}"><strong>台中裝潢細清</strong></a>；日常清潔見 <a href="{$pClean}">台中清潔公司</a>；更多服務見 <a href="{$hub}">台中居家服務</a>。</p>
HTML;

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'taichung', 2, ?, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug=VALUES(service_slug), cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");

$gs->execute([
    'taichung-reno-detail-guide',
    '台中裝潢細清指南：流程、行情、各區重點、怎麼挑團隊',
    '台中裝潢細清怎麼做？七期、北屯、西屯重劃區新成屋交屋潮必看：細清做什麼、每坪 NT$120–250 行情、各區重點與挑團隊 5 重點，裝潢後入住前一篇看懂。',
    $b1, 'reno-detail,cleaning', $cover,
    '台中裝潢細清指南｜流程行情各區怎麼挑｜店家好口碑',
    '台中裝潢細清指南：細清做什麼、每坪 NT$120–250 行情、七期/北屯/西屯/烏日各區重點、挑團隊 5 重點與避雷，台中新成屋交屋潮入住前必看。',
]);
$gs->execute([
    'taichung-rough-vs-detail-cleaning',
    '粗清 vs 細清差在哪？台中交屋族該選哪種、費用怎麼抓',
    '台中新成屋交屋，建商說的清潔是粗清還是細清？兩者對比表、該選哪種、費用怎麼抓不被當細清收，七期/北屯交屋族入住前必看。',
    $b2, 'reno-detail,cleaning', $cover,
    '粗清vs細清差在哪｜台中交屋怎麼選費用｜店家好口碑',
    '粗清（清廢料）vs 細清（深層可入住）差在哪？台中交屋該選哪種、費用每坪 NT$120–250 怎麼抓不被當細清收，七期、北屯、烏日重劃區交屋族必看。',
]);

echo "✅ 寫入 2 篇台中細清文\n";
echo "\n== 台中裝潢細清(reno-detail)頁 修後 ==\n";
$rg = $db->prepare("SELECT title, service_slug FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='taichung' AND (FIND_IN_SET('reno-detail',service_slug)>0 OR FIND_IN_SET('cleaning',service_slug)>0) ORDER BY (FIND_IN_SET('reno-detail',service_slug)>0) DESC, published_at DESC LIMIT 4");
$rg->execute();
$n=0; foreach ($rg as $r) { $n++; printf("  %d. %s (svc=%s)\n", $n, $r['title'], $r['service_slug']); }
echo "  → 共 {$n} 篇\n預覽：{$pReno}\n";
