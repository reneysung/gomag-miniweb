<?php
// ============================================================
// Migration 131 — 台中清潔束補 2 篇（gg- 範本、台中在地化）→ 台中清潔頁滿 4
// ------------------------------------------------------------
// 台中清潔頁原靠 130 的 2 篇細清文(標 cleaning)撐＝2 篇。補 2 篇清潔專屬（角度錯開）：
//   1) 台中居家清潔指南：鐘點/定期/退租/空屋/行情/各區（cleaning）
//   2) 台中清潔公司怎麼挑：評估/估價/簽約/避雷（cleaning）
// 台中在地化：北屯/西屯/南屯/七期/中區/大里太平/烏日、盆地氣候、中科台積電換屋族。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/131_taichung_cleaning_cluster.php
// 冪等：INSERT … ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cover = 'https://www.gomag.com.tw/uploads/guides/covers/cleaning.svg';
$hub   = 'https://www.gomag.com.tw/city/taichung/home-service';
$pClean = 'https://www.gomag.com.tw/city/taichung/home-service/cleaning';
$pReno  = 'https://www.gomag.com.tw/city/taichung/home-service/reno-detail';

// ── 1) 台中居家清潔指南 ──
$b1 = <<<HTML
<p class="gg-lead">台中居家清潔需求從<strong>七期、北屯、西屯</strong>的電梯大樓，到<strong>大里、太平、烏日、霧峰</strong>的透天厝差很大。再加上中科、台積電帶動的換屋族與大量重劃區新成屋，「居家清潔」其實分好幾種——挑對種類、講清楚屋況才划算。這篇把台中常見的居家清潔類型、行情與各區眉角講清楚。</p>

<h2>台中居家清潔有哪些種類？</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧹 鐘點清潔</b><span>按小時、彈性預約，上班族日常維護首選。</span></div>
  <div class="gg-card"><b>🔁 定期清潔</b><span>每週／雙週／每月固定，月約單價較低，雙薪家庭、長輩同住適合。</span></div>
  <div class="gg-card"><b>🧽 大掃除</b><span>年終、節慶前全屋深度整理，旺季要早約。</span></div>
  <div class="gg-card"><b>📦 退租清潔</b><span>租屋點交前回復屋況、拿回押金。</span></div>
  <div class="gg-card"><b>🏠 空屋／入厝清潔</b><span>新居入住前深層清潔，常與裝潢細清搭配。</span></div>
  <div class="gg-card"><b>✨ 裝潢後細清</b><span>裝潢粉塵殘膠專業清理（<a href="{$pReno}">看台中裝潢細清</a>）。</span></div>
</div>

<h2>台中居家清潔行情參考</h2>
<table class="gg-table">
  <thead><tr><th>服務類型</th><th>計價方式</th><th>行情區間</th><th>適合</th></tr></thead>
  <tbody>
    <tr><td>鐘點清潔</td><td>每小時（每次多最低 3 小時）</td><td>NT\$450–650 / 時</td><td>日常維護</td></tr>
    <tr><td>定期清潔</td><td>月約、單價較低</td><td>單次約打 8–9 折</td><td>固定週期</td></tr>
    <tr><td>退租／空屋</td><td>整案（看坪數）</td><td>套房 3,000–6,000；整戶 6,000–18,000</td><td>點交、入住前</td></tr>
    <tr><td>年終大掃除</td><td>整案報價</td><td>視坪數項目</td><td>過年節慶</td></tr>
    <tr><td>裝潢後細清</td><td>每坪</td><td>NT\$120–250 / 坪</td><td>裝潢後入住前</td></tr>
  </tbody>
</table>
<p><em>台中行情略高於彰化、與雙北相近；實際以現場估價為準。</em></p>

<h2>台中各區清潔需求差異</h2>
<ul>
  <li><strong>七期、市政特區</strong>：豪宅大坪數，定期清潔與細緻維護需求高，找有高端案經驗團隊。</li>
  <li><strong>北屯（廍子、機捷）、西屯</strong>：新成屋與電梯大樓最多，鐘點、定期、入厝清潔需求集中。</li>
  <li><strong>南屯（文心路、五期）、中區</strong>：新舊交雜，中古翻修與老屋清潔多。</li>
  <li><strong>大里、太平、烏日、霧峰</strong>：透天比例高，樓層、前後院、頂樓加蓋影響坪數，估價要說清楚。</li>
</ul>
<div class="gg-tip">
  <b>💡 台中在地提醒</b>
  <p>台中盆地夏季悶熱、灰塵重，定期清潔頻率可拉高；中科、台積電換屋族多，搬家旺季（寒暑假、年底）清潔團隊滿，提早預約。</p>
</div>

<h2>怎麼挑才不踩雷</h2>
<ul>
  <li>免費到府估價、報價單列清楚含哪些（耗材、垃圾清運、搬挪家具）。</li>
  <li>確認人員自聘、有責任險。</li>
  <li>定期約先做一次單次試水溫，滿意再簽長約。</li>
</ul>
<div class="gg-warn">
  <b>⚠️ 避雷</b>
  <p>報價遠低於行情、不到府只給均一價、要求先付全額的要小心，常見後續加價或品質不到位。訂金不超過 30%。</p>
</div>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">台中鐘點清潔一小時多少？</div>
  <div class="gg-a">約 NT\$450–650／時，多數每次最低 3 小時，台中行情略高於中南部、與雙北相近。</div>
  <div class="gg-q">鐘點和定期差在哪？</div>
  <div class="gg-a">鐘點＝單次按時、彈性；定期＝固定週期簽約、單價較低，且同一組人較熟悉你家。</div>
  <div class="gg-q">台中透天清潔比較貴嗎？</div>
  <div class="gg-a">會。大里、太平、烏日透天多，樓層、前後院、頂樓都增加工時，估價要說明完整屋況。</div>
  <div class="gg-q">居家清潔和裝潢細清一樣嗎？</div>
  <div class="gg-a">不一樣。一般清潔是日常維護；<a href="{$pReno}">裝潢細清</a>專處理裝潢後粉塵殘膠，費用約 1.5–2 倍。</div>
  <div class="gg-q">過年大掃除什麼時候約？</div>
  <div class="gg-a">11 月起開始搶，建議 11 月中前預約卡位。</div>
</div>

<p>找台中在地清潔團隊見 <a href="{$pClean}"><strong>台中清潔公司</strong></a>；裝潢後入住見 <a href="{$pReno}">台中裝潢細清</a>；更多服務見 <a href="{$hub}">台中居家服務</a>。</p>
HTML;

// ── 2) 台中清潔公司怎麼挑 ──
$b2 = <<<HTML
<p class="gg-lead">台中清潔公司多，品質落差也大。最怕花了錢清不到位、東西還被弄壞。這篇教你用 6 個檢查點評估一家台中清潔公司值不值得託付，以及估價、簽約、付款該注意什麼。</p>

<h2>挑台中清潔公司的 6 個檢查點</h2>
<div class="gg-cards">
  <div class="gg-card"><b>① 報價透明</b><span>按坪、按時還是按項目？含不含耗材、垃圾清運、搬挪家具？列得越清楚越可靠。</span></div>
  <div class="gg-card"><b>② 服務範圍</b><span>有些主打市區（七期/北屯/西屯）；大里、太平、烏日、霧峰、外埔等要先確認跑不跑、加不加車馬費。</span></div>
  <div class="gg-card"><b>③ 責任險</b><span>刮傷地板、打破物品有保險才有保障。</span></div>
  <div class="gg-card"><b>④ 自聘 vs 外包</b><span>自聘團隊品質較穩，純外包良莠不齊。</span></div>
  <div class="gg-card"><b>⑤ 在地口碑</b><span>Google 評論看分數＋評論數雙指標，別只看單一高分。</span></div>
  <div class="gg-card"><b>⑥ 完工保固</b><span>不滿意能否免費重清？正規業者敢給驗收承諾。</span></div>
</div>

<h2>從估價到完工，流程怎麼跑</h2>
<ol>
  <li><strong>到府／線上估價</strong>：說明坪數、屋型（透天幾層、含不含庭院頂樓）、要清範圍，取得書面報價。</li>
  <li><strong>確認報價單</strong>：項目、單價、含哪些、工時人數都寫清楚。</li>
  <li><strong>排定時間</strong>：搬家旺季（寒暑假、年底）、過年前要早約。</li>
  <li><strong>現場保護與施作</strong>：地板鋪墊、家具覆蓋，過程可拍照。</li>
  <li><strong>驗收付款</strong>：逐區檢查、滿意再付清，訂金不超過 30%。</li>
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
  <p>報價明顯低於行情、不到府只給均一價、要求先付全額、講不清含哪些——常見後續加價或品質落差。低價不等於划算，清不乾淨還要再花一次。</p>
</div>
<div class="gg-tip">
  <b>💡 簽約／付款自保</b>
  <p>定期約先單次試水溫再簽長約；口頭承諾盡量寫進報價單或合約；訂金不超過 30%。</p>
</div>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">台中清潔公司一定要簽約嗎？</div>
  <div class="gg-a">不用。單次現約現做；只有定期才簽月約、單價較划算。</div>
  <div class="gg-q">怎麼判斷報價合不合理？</div>
  <div class="gg-a">多問 2–3 家、看是否到府估價、報價單列不列清項目。台中鐘點約 NT\$450–650／時、退租整戶約 NT\$6,000–18,000 可當基準。</div>
  <div class="gg-q">大里、太平、烏日這些區也有人來嗎？</div>
  <div class="gg-a">多數可以，部分業者會加車馬費或設起清坪數，預約時先問。</div>
  <div class="gg-q">東西被弄壞怎麼辦？</div>
  <div class="gg-a">選有責任險的業者；施作前拍照記錄，發現損壞當場反映、依險理賠。</div>
</div>

<p>看台中在地清潔團隊見 <a href="{$pClean}"><strong>台中清潔公司</strong></a>；裝潢後入住見 <a href="{$pReno}">台中裝潢細清</a>；更多服務見 <a href="{$hub}">台中居家服務</a>。</p>
HTML;

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'taichung', 2, 'cleaning', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug='cleaning', cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");
$gs->execute([
    'taichung-home-cleaning-guide',
    '台中居家清潔指南：鐘點、定期、退租、空屋清潔行情一次看',
    '台中居家清潔怎麼選？鐘點、定期、退租、空屋 4 種類型計價與行情，加上七期/北屯/西屯/大里各區眉角，台中盆地灰塵重、換屋族多的在地建議。',
    $b1,
    $cover,
    '台中居家清潔指南｜鐘點定期退租空屋行情｜店家好口碑',
    '台中居家清潔 4 種類型（鐘點/定期/退租/空屋）計價與行情、七期/北屯/西屯/大里各區眉角、挑選不踩雷，台中換屋族與重劃區住戶適用。',
]);
$gs->execute([
    'taichung-cleaning-company-howto',
    '台中清潔公司怎麼挑？服務範圍、收費方式、挑選 6 重點',
    '台中清潔公司怎麼選？6 檢查點（報價透明/服務範圍/責任險/自聘/口碑/保固）、估價到完工流程、收費方式與避雷，七期、北屯、大里、太平住戶必看。',
    $b2,
    $cover,
    '台中清潔公司怎麼挑｜服務範圍收費挑選重點｜店家好口碑',
    '台中清潔公司怎麼選？挑選 6 重點、估價流程、收費方式表與地雷提醒，七期、北屯、西屯、大里、太平、烏日住戶找清潔不踩雷。',
]);

echo "✅ 寫入 2 篇台中清潔文\n";
echo "\n== 台中清潔(cleaning)頁 修後 ==\n";
$rg = $db->prepare("SELECT title, service_slug FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='taichung' AND FIND_IN_SET('cleaning',service_slug)>0 ORDER BY published_at DESC LIMIT 4");
$rg->execute();
$n=0; foreach ($rg as $r) { $n++; printf("  %d. %s (svc=%s)\n", $n, $r['title'], $r['service_slug']); }
echo "  → 共 {$n} 篇\n";
