<?php
// ============================================================
// Migration 136 — 嘉義清潔束補 3 篇（gg- 範本、嘉義在地化）→ 清潔頁+細清頁滿 4
// ------------------------------------------------------------
// 嘉義清潔家族現有 1 篇（嘉義透天老屋清潔，cleaning,reno-detail）。補 3 篇：
//   1) 嘉義清潔公司怎麼挑（cleaning）— 評估/估價/簽約
//   2) 嘉義居家清潔指南（cleaning）— 服務類型/行情/各區
//   3) 粗清 vs 細清 嘉義版（reno-detail,cleaning）— 對比
// 嘉義在地化：嘉義市東/西區老屋透天、太保高鐵特區、朴子/民雄/大林/新港/中埔、南部夏熱灰塵。
// 語氣套保留人味鐵則。
// ⚠️ 嘉義清潔家族 0 店 → 攻略補滿但店家清單空，排名仍需補 placeholder（另案）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/136_chiayi_cleaning_cluster.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$svgClean = 'https://www.gomag.com.tw/uploads/guides/covers/cleaning.svg';
$svgFine  = 'https://www.gomag.com.tw/uploads/guides/covers/fineclean.svg';
$hub   = 'https://www.gomag.com.tw/city/chiayi/home-service';
$pClean = 'https://www.gomag.com.tw/city/chiayi/home-service/cleaning';
$pReno  = 'https://www.gomag.com.tw/city/chiayi/home-service/reno-detail';

$articles = [];

// ── 1) 嘉義清潔公司怎麼挑 ──
$articles[] = ['slug'=>'chiayi-cleaning-company-howto', 'svc'=>'cleaning', 'cover'=>$svgClean,
  'title'=>'嘉義清潔公司怎麼挑？服務範圍、收費方式、挑選 6 重點',
  'excerpt'=>'嘉義清潔公司怎麼選？6 檢查點（報價透明/服務範圍/責任險/自聘/口碑/保固）、估價到完工流程、收費方式與避雷，嘉義市區與朴子、民雄、大林住戶必看。',
  'meta_title'=>'嘉義清潔公司怎麼挑｜服務範圍收費挑選重點｜店家好口碑',
  'meta_desc'=>'嘉義清潔公司怎麼選？挑選 6 重點、估價流程、收費方式表與地雷提醒，嘉義市東西區、太保、朴子、民雄、大林住戶找清潔不踩雷。',
  'body'=> <<<HTML
<p class="gg-lead">嘉義找清潔，從嘉義市東區、西區的住宅，到太保高鐵特區的新成屋，再到朴子、民雄、大林的透天厝，需求差很多。最怕花了錢清不到位、東西還被弄壞。這篇用 6 個檢查點教你評估一家嘉義清潔公司值不值得託付，以及估價、簽約、付款要注意什麼。</p>

<h2>挑嘉義清潔公司的 6 個檢查點</h2>
<div class="gg-cards">
  <div class="gg-card"><b>① 報價透明</b><span>按坪、按時還是按項目？含不含耗材、垃圾清運、搬挪家具？列得越清楚越可靠。</span></div>
  <div class="gg-card"><b>② 服務範圍</b><span>有些主打嘉義市區；朴子、布袋、義竹、中埔、阿里山等較遠區要先確認跑不跑、加不加車馬費。</span></div>
  <div class="gg-card"><b>③ 責任險</b><span>刮傷地板、打破物品有保險才有保障。</span></div>
  <div class="gg-card"><b>④ 自聘 vs 外包</b><span>自聘團隊品質較穩，純外包良莠不齊。</span></div>
  <div class="gg-card"><b>⑤ 在地口碑</b><span>Google 評論看分數和評論數，問在地朋友也準。</span></div>
  <div class="gg-card"><b>⑥ 完工保固</b><span>不滿意能否免費重清？正規業者敢給驗收承諾。</span></div>
</div>

<h2>從估價到完工，流程怎麼跑</h2>
<ol>
  <li><strong>到府或線上估價</strong>：說明坪數、屋型（透天幾層、含不含庭院頂樓）、要清範圍，取得書面報價。</li>
  <li><strong>確認報價單</strong>：項目、單價、含哪些、工時人數都寫清楚。</li>
  <li><strong>排定時間</strong>：過年前、搬家旺季要早約。</li>
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
  <div class="gg-q">嘉義清潔公司一定要簽約嗎？</div>
  <div class="gg-a">不用。單次現約現做；只有定期才簽月約、單價較划算。</div>
  <div class="gg-q">怎麼判斷報價合不合理？</div>
  <div class="gg-a">多問 2 到 3 家、看是否到府估價、報價單列不列清項目。嘉義鐘點約 NT\$400–600／時、退租整戶約 NT\$5,000–15,000 可當基準。</div>
  <div class="gg-q">朴子、大林、布袋這些區也有人來嗎？</div>
  <div class="gg-a">多數可以，部分業者會加車馬費或設起清坪數，預約時先問。</div>
  <div class="gg-q">東西被弄壞怎麼辦？</div>
  <div class="gg-a">選有責任險的業者；施作前拍照記錄，發現損壞當場反映、依險理賠。</div>
</div>

<p>看嘉義在地清潔團隊看 <a href="{$pClean}"><strong>嘉義清潔公司</strong></a>，裝潢後入住看 <a href="{$pReno}">嘉義裝潢細清</a>，更多服務看 <a href="{$hub}">嘉義居家服務</a>。</p>
HTML];

// ── 2) 嘉義居家清潔指南 ──
$articles[] = ['slug'=>'chiayi-home-cleaning-guide', 'svc'=>'cleaning', 'cover'=>$svgClean,
  'title'=>'嘉義居家清潔指南：鐘點、定期、退租、空屋清潔行情一次看',
  'excerpt'=>'嘉義居家清潔怎麼選？鐘點、定期、退租、空屋 4 種類型計價與行情，加上嘉義市區與朴子、民雄各區眉角，嘉義透天多、夏天灰塵重的在地建議。',
  'meta_title'=>'嘉義居家清潔指南｜鐘點定期退租空屋行情｜店家好口碑',
  'meta_desc'=>'嘉義居家清潔 4 種類型（鐘點/定期/退租/空屋）計價與行情、嘉義市/太保/朴子/民雄各區眉角、挑選不踩雷，嘉義住戶與透天族適用。',
  'body'=> <<<HTML
<p class="gg-lead">嘉義的居家清潔需求很分散，從嘉義市東區、西區的大樓與公寓，到太保、朴子、民雄的透天厝，每種情況適合的服務都不一樣。先搞懂類型和計價，把屋況講清楚，找起來才划算。</p>

<h2>嘉義居家清潔有哪些種類</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧹 鐘點清潔</b><span>按小時、單次彈性預約，上班族日常維護首選。</span></div>
  <div class="gg-card"><b>🔁 定期清潔</b><span>每週、雙週或每月固定到府，簽月約單價較低。</span></div>
  <div class="gg-card"><b>🧽 大掃除</b><span>過年、節慶前全屋深度整理，旺季要早約。</span></div>
  <div class="gg-card"><b>📦 退租清潔</b><span>租屋點交前回復屋況、拿回押金。</span></div>
  <div class="gg-card"><b>🏠 空屋／入厝清潔</b><span>新居入住前深層清潔，常與裝潢細清搭配。</span></div>
  <div class="gg-card"><b>✨ 裝潢後細清</b><span>裝潢粉塵殘膠專業清理（看<a href="{$pReno}">嘉義裝潢細清</a>）。</span></div>
</div>

<h2>嘉義居家清潔行情</h2>
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

<h2>嘉義各區清潔需求差異</h2>
<ul>
  <li><strong>嘉義市東區、西區</strong>：大樓、公寓與老屋並存，鐘點和定期清潔需求最大，清潔公司也最集中。</li>
  <li><strong>太保（高鐵特區）</strong>：新成屋交屋帶動入厝、空屋清潔需求。</li>
  <li><strong>朴子、民雄、大林、新港</strong>：透天比例高，樓層、前後院、頂樓影響坪數，估價要說清楚。</li>
  <li><strong>中埔、竹崎、番路（近阿里山）</strong>：距離較遠，預約前先確認服務範圍與車馬費。</li>
</ul>
<div class="gg-tip">
  <b>💡 嘉義在地提醒</b>
  <p>嘉義夏天熱、灰塵多，定期清潔頻率可以拉高一點。嘉義市與周邊鄉鎮透天多，估價時記得說明樓層、有沒有含庭院和頂樓，報價才會準。</p>
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
  <div class="gg-q">嘉義透天清潔比較貴嗎？</div>
  <div class="gg-a">會。朴子、民雄、大林透天多，樓層、前後院、頂樓都增加工時，估價要說明完整屋況。</div>
  <div class="gg-q">居家清潔和裝潢細清一樣嗎？</div>
  <div class="gg-a">不一樣。一般清潔是日常維護；<a href="{$pReno}">裝潢細清</a>專處理裝潢後粉塵殘膠，費用約 1.5 到 2 倍。</div>
  <div class="gg-q">過年大掃除什麼時候約？</div>
  <div class="gg-a">11 月起就開始搶，建議 11 月中前預約卡位。</div>
</div>

<p>找嘉義在地清潔團隊看 <a href="{$pClean}"><strong>嘉義清潔公司</strong></a>，裝潢後入住看 <a href="{$pReno}">嘉義裝潢細清</a>，更多服務看 <a href="{$hub}">嘉義居家服務</a>。</p>
HTML];

// ── 3) 粗清 vs 細清（嘉義版）──
$articles[] = ['slug'=>'chiayi-rough-vs-detail-cleaning', 'svc'=>'reno-detail,cleaning', 'cover'=>$svgFine,
  'title'=>'粗清 vs 細清差在哪？嘉義裝潢後清潔該選哪種、費用怎麼抓',
  'excerpt'=>'嘉義裝潢後的粗清和細清差在哪？粗清清廢料、細清能入住，費用怎麼抓才不被當細清收，太保高鐵特區與嘉義市老屋交屋族入住前必看。',
  'meta_title'=>'粗清vs細清差在哪｜嘉義裝潢後清潔怎麼選費用｜店家好口碑',
  'meta_desc'=>'粗清（清廢料）和細清（深層可入住）差在哪？嘉義裝潢後該選哪種、費用每坪 NT$120–250 怎麼抓不被當細清收，太保高鐵特區與嘉義市老屋交屋族必看。',
  'body'=> <<<HTML
<p class="gg-lead">嘉義裝潢完、要入住前，常聽到「粗清」和「細清」。很多人以為一樣，結果用粗清的料被收細清的錢，或以為清完能住、實際還是滿屋粉塵。這篇用嘉義裝潢後清潔的實況，一次說清楚兩者差在哪、該選哪種、費用怎麼抓。</p>

<h2>粗清和細清，一張表看懂</h2>
<table class="gg-table">
  <thead><tr><th>項目</th><th>粗清（初步清潔）</th><th>細清（裝潢後深層）</th></tr></thead>
  <tbody>
    <tr><td>目的</td><td>能進場</td><td>能入住</td></tr>
    <tr><td>誰做</td><td>工班、統包</td><td>專業細清團隊</td></tr>
    <tr><td>內容</td><td>大型廢料、木屑、垃圾</td><td>粉塵、矽利康殘膠、玻璃水痕、櫃內、軌道</td></tr>
    <tr><td>費用</td><td>低（常含在工程裡）</td><td>每坪 NT\$120–250</td></tr>
    <tr><td>清完能入住嗎</td><td>不行，還是髒</td><td>可以，當天就能住</td></tr>
  </tbody>
</table>

<h2>各自實際在做什麼</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧱 粗清</b><span>清掉裝潢留下的大型廢料、板材邊角、包裝和明顯垃圾，讓空間進得了場。完工時工班順手做的多半是這個。</span></div>
  <div class="gg-card"><b>✨ 細清</b><span>逐一處理粉塵、矽利康殘膠、玻璃水痕，連櫃內、五金軌道、燈具縫隙都清乾淨，做完當天就能搬進去住。</span></div>
</div>

<h2>嘉義交屋族該選哪種</h2>
<p>太保高鐵特區、嘉義市新成屋交屋要直接入住的，選細清，多數人要的就是這個。如果之後還要再進場做二次工程或追加裝修，先粗清即可。簡單記：要住人就要細清。</p>

<div class="gg-warn">
  <b>⚠️ 費用怎麼抓才不會被當細清收</b>
  <p>細清每坪約 NT\$120–250，2–3 房整戶約 NT\$8,000–20,000，明顯高於一般打掃。下訂前問三件事：報的是粗清還是細清、含不含玻璃和櫃內和矽利康殘膠、完工驗收標準。實際以現場估價為準。</p>
</div>

<h2>嘉義在地提醒</h2>
<p>嘉義市與周邊老屋翻修多，老屋細清常有舊木作與較複雜格局，工時較長，找有老屋經驗的團隊比較放心。太保高鐵特區新案集中交屋時，細清團隊會比較滿，提早預約。嘉義夏天熱、灰塵多，交屋後空關會再積塵，建議入住前再清。</p>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">建商說交屋會清潔，那是細清嗎？</div>
  <div class="gg-a">多半只是粗清（清場），不等於可以入住的細清。要乾淨入住通常得自己另外找細清。</div>
  <div class="gg-q">粗清可以自己做嗎？</div>
  <div class="gg-a">大型垃圾可以自己處理，粉塵和殘膠的細清建議交給專業，自己做容易刮傷材質又清不乾淨。</div>
  <div class="gg-q">細清要做多久？</div>
  <div class="gg-a">整戶看坪數約半天到一天，玻璃和櫃體多會更久。</div>
  <div class="gg-q">嘉義老屋翻修後的細清要注意什麼？</div>
  <div class="gg-a">老屋常有舊木作、石材與較複雜格局，細清要懂保護舊建材、工時也較長，建議找有老屋經驗的團隊。</div>
</div>

<p>找嘉義細清團隊看 <a href="{$pReno}"><strong>嘉義裝潢細清</strong></a>，日常清潔看 <a href="{$pClean}">嘉義清潔公司</a>，更多服務看 <a href="{$hub}">嘉義居家服務</a>。</p>
HTML];

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'chiayi', 2, ?, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug=VALUES(service_slug), cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");
foreach ($articles as $a) {
    $gs->execute([$a['slug'], $a['title'], $a['excerpt'], $a['body'], $a['svc'], $a['cover'], $a['meta_title'], $a['meta_desc']]);
    $row = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug=" . $db->quote($a['slug']))->fetch(PDO::FETCH_ASSOC);
    printf("  ✅ #%s %s (%dB, svc=%s)\n", $row['id'], $a['slug'], $row['l'], $a['svc']);
}

foreach ([['嘉義清潔','cleaning'],['嘉義裝潢細清','reno-detail']] as [$lbl,$svc]) {
    if ($svc==='reno-detail') $cond="(FIND_IN_SET('reno-detail',service_slug)>0 OR FIND_IN_SET('cleaning',service_slug)>0) ORDER BY (FIND_IN_SET('reno-detail',service_slug)>0) DESC, published_at DESC";
    else $cond="FIND_IN_SET('cleaning',service_slug)>0 ORDER BY published_at DESC";
    echo "\n== {$lbl}頁 修後 ==\n";
    $rg=$db->query("SELECT title FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='chiayi' AND $cond LIMIT 4");
    $n=0; foreach($rg as $r){$n++; printf("  %d. %s\n",$n,$r['title']);} echo "  → 共 {$n} 篇\n";
}
