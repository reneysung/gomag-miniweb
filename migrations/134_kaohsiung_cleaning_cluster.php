<?php
// ============================================================
// Migration 134 — 高雄清潔束 4 篇（gg- 範本、高雄在地化）→ 細清頁+清潔頁皆滿 4
// ------------------------------------------------------------
// 高雄 home-service 雖 8 篇，但清潔家族只 2（fine-clean懶人包 + 除霉防銹）。
// 細清頁=2、清潔頁=1。寫 4 篇清潔家族補滿兩頁：
//   1) 高雄裝潢細清指南（reno-detail,cleaning）
//   2) 粗清 vs 細清 高雄版（reno-detail,cleaning）
//   3) 高雄居家清潔指南（cleaning）
//   4) 高雄清潔公司怎麼挑（cleaning）
// 高雄在地化：三民/左營/苓雅/鼓山/楠梓/鳳山、港都濕熱、近海鹽害（鼓山/前鎮/小港）、
// 梅雨颱風黴菌、亞洲新灣區/美術館/農十六/巨蛋重劃區、台積電楠梓橋頭換屋潮。
// 語氣套保留人味鐵則（少金句/少破折號/少「不是X而是Y」）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/134_kaohsiung_cleaning_cluster.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$svgClean = 'https://www.gomag.com.tw/uploads/guides/covers/cleaning.svg';
$svgFine  = 'https://www.gomag.com.tw/uploads/guides/covers/fineclean.svg';
$hub   = 'https://www.gomag.com.tw/city/kaohsiung/home-service';
$pReno = 'https://www.gomag.com.tw/city/kaohsiung/home-service/reno-detail';
$pClean = 'https://www.gomag.com.tw/city/kaohsiung/home-service/cleaning';

$articles = [];

// ── 1) 高雄裝潢細清指南 ──
$articles[] = ['slug'=>'kaohsiung-reno-detail-guide', 'svc'=>'reno-detail,cleaning', 'cover'=>$svgFine,
  'title'=>'高雄裝潢細清指南：流程、行情、各區重點、怎麼挑團隊',
  'excerpt'=>'高雄裝潢細清怎麼做？亞洲新灣區、楠梓台積電換屋與美術館重劃區交屋潮必看：細清清什麼、每坪 NT$120–250 行情、各區重點與挑團隊，入住前一篇看懂。',
  'meta_title'=>'高雄裝潢細清指南｜流程行情各區怎麼挑｜店家好口碑',
  'meta_desc'=>'高雄裝潢細清指南：細清清什麼、每坪 NT$120–250 行情、三民/左營/鼓山/楠梓各區重點與港都鹽害提醒、挑團隊與避雷，高雄交屋入住前必看。',
  'body'=> <<<HTML
<p class="gg-lead">高雄這幾年交屋潮一波接一波，亞洲新灣區、美術館特區、農十六、巨蛋周邊的新成屋，加上楠梓、橋頭因台積電帶動的換屋，裝潢後的細清需求很穩。裝潢完留下的粉塵、矽利康殘膠、玻璃水痕，一般打掃清不掉。這篇講高雄裝潢細清在清什麼、行情多少、各區要注意什麼，以及怎麼挑團隊。</p>

<h2>高雄裝潢細清在清什麼</h2>
<p>細清是裝潢完、入住前的深層清潔，目標是清完當天就能舒服住進去。</p>
<div class="gg-cards">
  <div class="gg-card"><b>🌫 全室除塵</b><span>天花板、牆面、層板、踢腳板的裝潢粉塵，連空調出風口都清。</span></div>
  <div class="gg-card"><b>🧴 矽利康殘膠</b><span>窗框、廚衛、櫃體接縫的矽利康與標籤殘膠。</span></div>
  <div class="gg-card"><b>🪟 玻璃水痕</b><span>窗戶、淋浴拉門、鏡面的油漆點與水痕。</span></div>
  <div class="gg-card"><b>🗄 櫃體內部</b><span>系統櫃、抽屜、五金軌道裡的木屑粉塵。</span></div>
  <div class="gg-card"><b>🧹 地板處理</b><span>木地板、磁磚的施工汙漬清理與初步養護。</span></div>
  <div class="gg-card"><b>🚽 廚衛細清</b><span>新衛浴、廚具的保護膜、矽利康與水垢前處理。</span></div>
</div>

<h2>高雄裝潢細清行情</h2>
<p>細清以坪數計價，櫃體與玻璃越多越貴。以下為高雄常見區間，實際以現場估價為準。</p>
<table class="gg-table">
  <thead><tr><th>坪數／房型</th><th>常見行情</th><th>備註</th></tr></thead>
  <tbody>
    <tr><td>每坪單價</td><td>NT\$120–250 / 坪</td><td>櫃體、玻璃多偏高</td></tr>
    <tr><td>套房／小宅（10–20 坪）</td><td>約 NT\$3,000–8,000</td><td>—</td></tr>
    <tr><td>2–3 房（25–40 坪）</td><td>約 NT\$8,000–20,000</td><td>亞灣、農十六重劃區常見</td></tr>
    <tr><td>透天／大坪數（40 坪+）</td><td>NT\$20,000 起</td><td>樓層多另計</td></tr>
  </tbody>
</table>

<h2>高雄各區細清重點</h2>
<ul>
  <li><strong>亞洲新灣區、苓雅、前鎮</strong>：新大樓與大面玻璃多，細清重玻璃水痕；前鎮、小港靠海，窗框五金容易卡鹽，清完順便檢查。</li>
  <li><strong>三民、左營、鼓山（農十六、美術館）</strong>：新成屋交屋量大，2–3 房標準格局細清需求集中，旺季要早約。</li>
  <li><strong>楠梓、橋頭</strong>：台積電帶動換屋與新建案，交屋細清需求成長快。</li>
  <li><strong>鳳山、岡山</strong>：透天與新案並存，透天樓層多、坪數大，估價要說清楚。</li>
</ul>
<div class="gg-tip">
  <b>💡 高雄在地提醒</b>
  <p>高雄濕熱、近海區鹽分重，細清後若空關，金屬件容易生鏽、角落容易回潮，建議入住前再做、做完盡快通風除濕。交屋旺季團隊很滿，提早兩三週預約。</p>
</div>

<h2>怎麼挑高雄細清團隊</h2>
<ol>
  <li><strong>先確認是細清不是粗清</strong>：報價含不含玻璃、櫃內、矽利康殘膠（看<a href="{$pReno}">粗清 vs 細清</a>）。</li>
  <li><strong>有沒有拆裝、損壞保固</strong>：細清會動到五金與玻璃，損壞責任要講清楚。</li>
  <li><strong>現場估價、報價透明</strong>：每坪單價、含哪些、工時人數寫清楚。</li>
  <li><strong>完工驗收</strong>：逐區檢查，不滿意能不能補清。</li>
  <li><strong>在地口碑</strong>：看 Google 評論分數和評論數，設計師、統包推薦也可參考。</li>
</ol>
<div class="gg-warn">
  <b>⚠️ 避雷</b>
  <p>不到府估價只給均一價、報價特別低的，常是用粗清的料收細清的錢，或現場再加價。訂金不超過三成，驗收滿意再付清。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">高雄裝潢細清一坪多少錢？</div>
  <div class="gg-a">約 NT\$120–250／坪，櫃體和玻璃多會偏高。2–3 房整戶常見 NT\$8,000–20,000，實際看現場估價。</div>
  <div class="gg-q">建商交屋會清，還要自己找細清嗎？</div>
  <div class="gg-a">建商多半只做粗清，清完還是有粉塵，要乾淨入住通常得自己另外找細清。</div>
  <div class="gg-q">近海的房子細清要注意什麼？</div>
  <div class="gg-a">鼓山、前鎮、小港一帶鹽分重，細清後檢查窗框五金有沒有卡鹽、生鏽，並保持通風除濕。</div>
  <div class="gg-q">細清和一般居家清潔差在哪？</div>
  <div class="gg-a"><a href="{$pClean}">一般清潔</a>是日常維護；細清專處理裝潢後粉塵殘膠，工具藥水不同，費用約 1.5 到 2 倍。</div>
</div>

<p>找高雄細清團隊看 <a href="{$pReno}"><strong>高雄裝潢細清</strong></a>，日常清潔看 <a href="{$pClean}">高雄清潔公司</a>，更多服務看 <a href="{$hub}">高雄居家服務</a>。</p>
HTML];

// ── 2) 粗清 vs 細清（高雄版）──
$articles[] = ['slug'=>'kaohsiung-rough-vs-detail-cleaning', 'svc'=>'reno-detail,cleaning', 'cover'=>$svgFine,
  'title'=>'粗清 vs 細清差在哪？高雄交屋族該選哪種、費用怎麼抓',
  'excerpt'=>'高雄交屋，建商說的清潔是粗清還是細清？兩者對比、該選哪種、費用怎麼抓不被當細清收，亞灣、楠梓、農十六重劃區交屋族入住前必看。',
  'meta_title'=>'粗清vs細清差在哪｜高雄交屋怎麼選費用｜店家好口碑',
  'meta_desc'=>'粗清（清廢料）和細清（深層可入住）差在哪？高雄交屋該選哪種、費用每坪 NT$120–250 怎麼抓不被當細清收，亞洲新灣區、楠梓、農十六交屋族必看。',
  'body'=> <<<HTML
<p class="gg-lead">高雄交屋時，建商常說「會幫您清潔」，結果搬進去才發現滿屋粉塵、窗框一堆矽利康，因為那通常是「粗清」不是「細清」。這兩種差很多，搞錯可能用粗清的料被收細清的錢。這篇用高雄交屋的實際情況說清楚。</p>

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
  <div class="gg-card"><b>🧱 粗清</b><span>清掉裝潢留下的大型廢料、板材邊角、包裝和明顯垃圾，讓空間進得了場。高雄交屋時建商或工班順手做的多半是這個。</span></div>
  <div class="gg-card"><b>✨ 細清</b><span>逐一處理粉塵、矽利康殘膠、玻璃水痕，連櫃內、五金軌道、燈具縫隙都清乾淨，做完當天就能搬進去住。</span></div>
</div>

<h2>高雄交屋族該選哪種</h2>
<p>亞洲新灣區、農十六、楠梓重劃區交屋要直接入住的，選細清，多數人要的就是這個。如果之後還要再進場做二次工程或追加裝修，先粗清即可。簡單記：要住人就要細清。</p>

<div class="gg-warn">
  <b>⚠️ 費用怎麼抓才不會被當細清收</b>
  <p>細清每坪約 NT\$120–250，2–3 房整戶約 NT\$8,000–20,000，明顯高於一般打掃。下訂前問三件事：報的是粗清還是細清、含不含玻璃和櫃內和矽利康殘膠、完工驗收標準。實際以現場估價為準。</p>
</div>

<h2>高雄在地提醒</h2>
<p>高雄新建案集中交屋的時段（亞灣、農十六、楠梓台積電宅），細清團隊會很滿，提早兩三週預約。港都濕熱、近海鹽分重，交屋後空關容易回潮、金屬件生鏽，建議入住前再清、做完盡快通風除濕。</p>

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

<p>找高雄細清團隊看 <a href="{$pReno}"><strong>高雄裝潢細清</strong></a>，日常清潔看 <a href="{$pClean}">高雄清潔公司</a>，更多服務看 <a href="{$hub}">高雄居家服務</a>。</p>
HTML];

// ── 3) 高雄居家清潔指南 ──
$articles[] = ['slug'=>'kaohsiung-home-cleaning-guide', 'svc'=>'cleaning', 'cover'=>$svgClean,
  'title'=>'高雄居家清潔指南：鐘點、定期、退租、空屋清潔行情一次看',
  'excerpt'=>'高雄居家清潔怎麼選？鐘點、定期、退租、空屋 4 種類型計價與行情，加上三民/左營/鼓山/鳳山各區眉角，港都濕熱、近海鹽分的在地清潔建議。',
  'meta_title'=>'高雄居家清潔指南｜鐘點定期退租空屋行情｜店家好口碑',
  'meta_desc'=>'高雄居家清潔 4 種類型（鐘點/定期/退租/空屋）計價與行情、三民/左營/鼓山/鳳山各區眉角、港都濕熱黴菌提醒與挑選不踩雷，高雄住戶適用。',
  'body'=> <<<HTML
<p class="gg-lead">高雄居家清潔的需求很多元，從三民、左營的大樓定期打掃，到鼓山、苓雅的租屋退租，再到鳳山、岡山的透天大掃除，每種情況適合的服務都不一樣。先搞懂類型和計價，把屋況講清楚，找起來才划算。</p>

<h2>高雄居家清潔有哪些種類</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧹 鐘點清潔</b><span>按小時、單次彈性預約，上班族日常維護首選。</span></div>
  <div class="gg-card"><b>🔁 定期清潔</b><span>每週、雙週或每月固定到府，簽月約單價較低。</span></div>
  <div class="gg-card"><b>🧽 大掃除</b><span>過年、節慶前全屋深度整理，旺季要早約。</span></div>
  <div class="gg-card"><b>📦 退租清潔</b><span>租屋點交前回復屋況、拿回押金。</span></div>
  <div class="gg-card"><b>🏠 空屋／入厝清潔</b><span>新居入住前深層清潔，常與裝潢細清搭配。</span></div>
  <div class="gg-card"><b>✨ 裝潢後細清</b><span>裝潢粉塵殘膠專業清理（看<a href="{$pReno}">高雄裝潢細清</a>）。</span></div>
</div>

<h2>高雄居家清潔行情</h2>
<table class="gg-table">
  <thead><tr><th>服務類型</th><th>計價方式</th><th>行情區間</th><th>適合</th></tr></thead>
  <tbody>
    <tr><td>鐘點清潔</td><td>每小時（每次多最低 3 小時）</td><td>NT\$400–600 / 時</td><td>日常維護</td></tr>
    <tr><td>定期清潔</td><td>月約、單價較低</td><td>單次約打 8–9 折</td><td>固定週期</td></tr>
    <tr><td>退租／空屋</td><td>整案（看坪數）</td><td>套房 2,500–5,000；整戶 5,000–15,000</td><td>點交、入住前</td></tr>
    <tr><td>年終大掃除</td><td>整案報價</td><td>視坪數與項目</td><td>過年節慶</td></tr>
    <tr><td>裝潢後細清</td><td>每坪</td><td>NT\$120–250 / 坪</td><td>裝潢後入住前</td></tr>
  </tbody>
</table>
<p>實際以現場估價為準。</p>

<h2>高雄各區清潔需求差異</h2>
<ul>
  <li><strong>三民、左營、苓雅</strong>：大樓與公寓多，鐘點和定期清潔需求最大，清潔公司也最密集。</li>
  <li><strong>鼓山（美術館、農十六）、前鎮、小港</strong>：靠海、鹽分高，紗窗、五金、外推窗容易卡鹽塵，定期清潔可順便顧。</li>
  <li><strong>鳳山、岡山、橋頭</strong>：透天比例高，樓層、前後院、頂樓影響坪數，估價要說清楚。</li>
  <li><strong>楠梓</strong>：台積電換屋族多，入厝、空屋清潔需求增加。</li>
</ul>
<div class="gg-tip">
  <b>💡 高雄在地提醒</b>
  <p>高雄濕熱、梅雨和颱風季容易長黴，浴室、衣櫃、牆角的除霉可以納入定期清潔。近海住戶建議拉高清潔頻率，順便處理鹽塵。</p>
</div>

<h2>怎麼挑才不踩雷</h2>
<ul>
  <li>找有免費到府估價的，報價單列清楚含哪些（耗材、垃圾清運、搬挪家具）。</li>
  <li>確認人員自聘、有責任險。</li>
  <li>定期約先做一次單次試試看，滿意再簽長約。</li>
</ul>
<div class="gg-warn">
  <b>⚠️ 避雷</b>
  <p>報價遠低於行情、不到府只給均一價、要求先付全額的要小心，常會後續加價或品質不到位。訂金不超過三成。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">鐘點和定期清潔差在哪？</div>
  <div class="gg-a">鐘點是單次按時、彈性；定期是固定週期簽約、單價較低，而且通常是同一組人比較熟悉你家環境。</div>
  <div class="gg-q">高雄近海的房子清潔要注意什麼？</div>
  <div class="gg-a">鼓山、前鎮、小港一帶鹽分重，紗窗、五金、外推窗容易卡鹽塵，定期清潔時可一起處理。</div>
  <div class="gg-q">高雄透天清潔比較貴嗎？</div>
  <div class="gg-a">會。鳳山、岡山透天多，樓層、前後院、頂樓都增加工時，估價要說明完整屋況。</div>
  <div class="gg-q">居家清潔和裝潢細清一樣嗎？</div>
  <div class="gg-a">不一樣。一般清潔是日常維護；<a href="{$pReno}">裝潢細清</a>專處理裝潢後粉塵殘膠，費用約 1.5 到 2 倍。</div>
  <div class="gg-q">過年大掃除什麼時候約？</div>
  <div class="gg-a">11 月起就開始搶，建議 11 月中前預約卡位。</div>
</div>

<p>找高雄在地清潔團隊看 <a href="{$pClean}"><strong>高雄清潔公司</strong></a>，裝潢後入住看 <a href="{$pReno}">高雄裝潢細清</a>，更多服務看 <a href="{$hub}">高雄居家服務</a>。</p>
HTML];

// ── 4) 高雄清潔公司怎麼挑 ──
$articles[] = ['slug'=>'kaohsiung-cleaning-company-howto', 'svc'=>'cleaning', 'cover'=>$svgClean,
  'title'=>'高雄清潔公司怎麼挑？服務範圍、收費方式、挑選 6 重點',
  'excerpt'=>'高雄清潔公司怎麼選？6 檢查點（報價透明/服務範圍/責任險/自聘/口碑/保固）、估價到完工流程、收費方式與避雷，三民、左營、鼓山、鳳山住戶必看。',
  'meta_title'=>'高雄清潔公司怎麼挑｜服務範圍收費挑選重點｜店家好口碑',
  'meta_desc'=>'高雄清潔公司怎麼選？挑選 6 重點、估價流程、收費方式表與地雷提醒，三民、左營、鼓山、苓雅、鳳山、楠梓住戶找清潔不踩雷。',
  'body'=> <<<HTML
<p class="gg-lead">高雄清潔公司很多，品質落差也大。最怕花了錢清不到位、東西還被弄壞。這篇用 6 個檢查點教你評估一家高雄清潔公司值不值得託付，以及估價、簽約、付款要注意什麼。</p>

<h2>挑高雄清潔公司的 6 個檢查點</h2>
<div class="gg-cards">
  <div class="gg-card"><b>① 報價透明</b><span>按坪、按時還是按項目？含不含耗材、垃圾清運、搬挪家具？列得越清楚越可靠。</span></div>
  <div class="gg-card"><b>② 服務範圍</b><span>有些主打市區（三民/左營/苓雅）；鳳山、岡山、橋頭、林園、路竹等要先確認跑不跑、加不加車馬費。</span></div>
  <div class="gg-card"><b>③ 責任險</b><span>刮傷地板、打破物品有保險才有保障。</span></div>
  <div class="gg-card"><b>④ 自聘 vs 外包</b><span>自聘團隊品質較穩，純外包良莠不齊。</span></div>
  <div class="gg-card"><b>⑤ 在地口碑</b><span>Google 評論看分數和評論數，別只看單一高分。</span></div>
  <div class="gg-card"><b>⑥ 完工保固</b><span>不滿意能否免費重清？正規業者敢給驗收承諾。</span></div>
</div>

<h2>從估價到完工，流程怎麼跑</h2>
<ol>
  <li><strong>到府或線上估價</strong>：說明坪數、屋型（透天幾層、含不含庭院頂樓）、要清範圍，取得書面報價。</li>
  <li><strong>確認報價單</strong>：項目、單價、含哪些、工時人數都寫清楚。</li>
  <li><strong>排定時間</strong>：搬家旺季、過年前要早約。</li>
  <li><strong>現場保護與施作</strong>：地板鋪墊、家具覆蓋，過程可拍照。</li>
  <li><strong>驗收付款</strong>：逐區檢查、滿意再付清，訂金不超過三成。</li>
</ol>

<h2>收費方式怎麼看</h2>
<table class="gg-table">
  <thead><tr><th>計價方式</th><th>說明</th><th>適合</th></tr></thead>
  <tbody>
    <tr><td>按時（鐘點）</td><td>每小時、每次多最低 3 小時</td><td>日常維護、範圍小</td></tr>
    <tr><td>按坪</td><td>坪數×單價</td><td>退租、空屋、細清</td></tr>
    <tr><td>整案報價</td><td>看屋況一口價</td><td>大掃除、整戶</td></tr>
    <tr><td>月約（定期）</td><td>固定週期、單價較低</td><td>長期維護</td></tr>
  </tbody>
</table>

<div class="gg-warn">
  <b>⚠️ 這些是地雷</b>
  <p>報價明顯低於行情、不到府只給均一價、要求先付全額、講不清含哪些，常會後續加價或品質落差。低價不等於划算，清不乾淨還要再花一次。</p>
</div>
<div class="gg-tip">
  <b>💡 簽約與付款自保</b>
  <p>定期約先單次試水溫再簽長約；口頭承諾盡量寫進報價單或合約；訂金不超過三成。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">高雄清潔公司一定要簽約嗎？</div>
  <div class="gg-a">不用。單次現約現做；只有定期才簽月約、單價較划算。</div>
  <div class="gg-q">怎麼判斷報價合不合理？</div>
  <div class="gg-a">多問 2 到 3 家、看是否到府估價、報價單列不列清項目。高雄鐘點約 NT\$400–600／時、退租整戶約 NT\$5,000–15,000 可當基準。</div>
  <div class="gg-q">鳳山、岡山、林園這些區也有人來嗎？</div>
  <div class="gg-a">多數可以，部分業者會加車馬費或設起清坪數，預約時先問。</div>
  <div class="gg-q">東西被弄壞怎麼辦？</div>
  <div class="gg-a">選有責任險的業者；施作前拍照記錄，發現損壞當場反映、依險理賠。</div>
</div>

<p>看高雄在地清潔團隊看 <a href="{$pClean}"><strong>高雄清潔公司</strong></a>，裝潢後入住看 <a href="{$pReno}">高雄裝潢細清</a>，更多服務看 <a href="{$hub}">高雄居家服務</a>。</p>
HTML];

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'kaohsiung', 2, ?, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug=VALUES(service_slug), cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");
foreach ($articles as $a) {
    $gs->execute([$a['slug'], $a['title'], $a['excerpt'], $a['body'], $a['svc'], $a['cover'], $a['meta_title'], $a['meta_desc']]);
    $row = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug=" . $db->quote($a['slug']))->fetch(PDO::FETCH_ASSOC);
    printf("  ✅ #%s %s (%dB, svc=%s)\n", $row['id'], $a['slug'], $row['l'], $a['svc']);
}

foreach ([['高雄裝潢細清','reno-detail'],['高雄清潔','cleaning']] as [$lbl,$svc]) {
    if ($svc==='reno-detail') $cond="(FIND_IN_SET('reno-detail',service_slug)>0 OR FIND_IN_SET('cleaning',service_slug)>0) ORDER BY (FIND_IN_SET('reno-detail',service_slug)>0) DESC, published_at DESC";
    else $cond="FIND_IN_SET('cleaning',service_slug)>0 ORDER BY published_at DESC";
    echo "\n== {$lbl}頁 修後 ==\n";
    $rg=$db->query("SELECT title FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='kaohsiung' AND $cond LIMIT 4");
    $n=0; foreach($rg as $r){$n++; printf("  %d. %s\n",$n,$r['title']);} echo "  → 共 {$n} 篇\n";
}
