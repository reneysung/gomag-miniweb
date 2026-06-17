<?php
// ============================================================
// Migration 129 — 彰化清潔束另 2 篇升級成 gg- 範本（加長+結構化）
// ------------------------------------------------------------
// 接 128 旗艦範本（Reney 確認 OK）。把彰化清潔束剩下 2 篇也升級：
//   - changhua-cleaning-company-howto（清潔公司怎麼挑）→ 聚焦「評估/挑選/估價/簽約」
//   - changhua-rough-vs-detail-cleaning（粗清vs細清）→ 聚焦「對比」
// 三篇角度錯開（居家清潔指南=服務類型/行情；怎麼挑=評估流程；粗清vs細清=對比），避重複 doorway。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/129_changhua_cleaning_rewrite2.php
// 冪等：UPDATE by slug
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$hub   = 'https://www.gomag.com.tw/city/changhua/home-service';
$pClean = 'https://www.gomag.com.tw/city/changhua/home-service/cleaning';
$pReno  = 'https://www.gomag.com.tw/city/changhua/home-service/reno-detail';

// ── 1) 彰化清潔公司怎麼挑（評估/估價/簽約角度）──
$b1 = <<<HTML
<p class="gg-lead">在彰化找清潔公司，最怕的不是貴，是「花了錢清不到位、東西還被弄壞」。從<strong>彰化市、員林</strong>到<strong>鹿港、和美、二林</strong>，清潔業者品質落差很大——這篇教你用 6 個檢查點評估一家清潔公司值不值得託付，以及估價、簽約、付款該注意什麼。</p>

<h2>挑彰化清潔公司的 6 個檢查點</h2>
<div class="gg-cards">
  <div class="gg-card"><b>① 報價透明</b><span>按坪、按時還是按項目？含不含耗材、垃圾清運、搬挪家具？報價單列得越清楚越可靠。</span></div>
  <div class="gg-card"><b>② 服務範圍</b><span>有些只做彰化市、員林；二林、芳苑、大城等較遠區要先確認跑不跑、加不加車馬費。</span></div>
  <div class="gg-card"><b>③ 責任險</b><span>清潔時刮傷地板、打破物品誰負責？有投保責任險才有保障。</span></div>
  <div class="gg-card"><b>④ 自聘 vs 外包</b><span>自聘團隊品質較穩定、教育訓練一致；純外包人力良莠不齊。</span></div>
  <div class="gg-card"><b>⑤ 在地口碑</b><span>Google 評論看「分數＋評論數」雙指標，再問在地朋友，別只看單一高分。</span></div>
  <div class="gg-card"><b>⑥ 完工保固</b><span>不滿意是否免費重清？正規業者敢給驗收與補清承諾。</span></div>
</div>

<h2>從估價到完工，流程怎麼跑</h2>
<ol>
  <li><strong>到府／線上估價</strong>：說明坪數、屋型（透天幾層、含不含庭院頂樓）、要清範圍，取得書面報價。</li>
  <li><strong>確認報價單</strong>：項目、單價、含哪些、預計工時、人數都寫清楚。</li>
  <li><strong>排定時間</strong>：旺季（過年前、搬家潮）要早約。</li>
  <li><strong>現場保護與施作</strong>：地板鋪墊、家具覆蓋；過程可拍照記錄。</li>
  <li><strong>驗收付款</strong>：逐區檢查、滿意再付清，預付訂金不超過 30%。</li>
</ol>

<h2>收費方式怎麼看</h2>
<table class="gg-table">
  <thead><tr><th>計價方式</th><th>說明</th><th>適合</th></tr></thead>
  <tbody>
    <tr><td>按時（鐘點）</td><td>每小時計，多數每次最低 3 小時</td><td>日常維護、範圍小</td></tr>
    <tr><td>按坪</td><td>以坪數×單價，常見於空屋、細清</td><td>退租、裝潢後</td></tr>
    <tr><td>整案報價</td><td>看屋況一口價</td><td>大掃除、整戶</td></tr>
    <tr><td>月約（定期）</td><td>固定週期、單價較低</td><td>長期維護</td></tr>
  </tbody>
</table>

<div class="gg-warn">
  <b>⚠️ 這些是地雷</b>
  <p>報價明顯低於行情、不到府只給「均一價」、要求先付全額、講不清楚含哪些項目——這些常見後續加價或品質落差。<strong>低價不等於划算</strong>，清不乾淨還要再花一次。</p>
</div>

<div class="gg-tip">
  <b>💡 簽約／付款自保</b>
  <p>定期約先做一次單次試水溫，滿意再簽長約；任何口頭承諾（加做哪些、保固多久）盡量寫進報價單或合約；訂金不超過 30%。</p>
</div>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">彰化清潔公司一定要簽約嗎？</div>
  <div class="gg-a">不用。單次清潔可現約現做；只有定期（每週／雙週／每月）才簽月約，單價較划算。</div>
  <div class="gg-q">怎麼判斷報價合不合理？</div>
  <div class="gg-a">多問 2–3 家比較、看是否到府估價、報價單有沒有列清項目。彰化鐘點約 NT\$400–600／時、退租整戶約 NT\$5,000–15,000 可當基準。</div>
  <div class="gg-q">二林、芳苑這些較遠的區也有人來嗎？</div>
  <div class="gg-a">多數可以，但部分業者會加車馬費或設起清坪數，預約時先問清楚。</div>
  <div class="gg-q">清潔人員臨時換人會影響品質嗎？</div>
  <div class="gg-a">自聘團隊較有一致的教育訓練與品質；簽定期約可要求盡量固定班底。</div>
  <div class="gg-q">東西被弄壞怎麼辦？</div>
  <div class="gg-a">選有責任險的業者；施作前可拍照記錄，發現損壞當場反映、依險理賠。</div>
</div>

<p>看彰化在地清潔團隊見 <a href="{$pClean}"><strong>彰化清潔公司</strong></a>；裝潢後入住見 <a href="{$pReno}">彰化裝潢細清</a>；更多服務見 <a href="{$hub}">彰化居家服務</a>。</p>
HTML;

// ── 2) 粗清 vs 細清（對比角度）──
$b2 = <<<HTML
<p class="gg-lead">裝潢完、要入住前，常聽到「粗清」和「細清」——很多人以為一樣，結果<strong>用粗清的料、被收細清的錢</strong>，或是以為清完能入住、實際還是滿屋粉塵。這篇用彰化裝潢後清潔的實況，一次說清楚兩者差在哪、該選哪種、費用怎麼抓。</p>

<h2>粗清 vs 細清，一張表看懂</h2>
<table class="gg-table">
  <thead><tr><th>項目</th><th>粗清（初步清潔）</th><th>細清（裝潢後深層清潔）</th></tr></thead>
  <tbody>
    <tr><td>目的</td><td>能進場</td><td>能入住</td></tr>
    <tr><td>誰做</td><td>工班／統包完工時</td><td>專業細清團隊</td></tr>
    <tr><td>內容</td><td>大型廢料、木屑、包裝、明顯垃圾</td><td>粉塵、矽利康殘膠、油漆點、玻璃水痕、櫃內、軌道縫隙</td></tr>
    <tr><td>工具藥水</td><td>一般掃具</td><td>專業吸塵、除膠、玻璃處理</td></tr>
    <tr><td>費用</td><td>低（常含在工程）</td><td>每坪 NT\$120–250</td></tr>
    <tr><td>做完能入住？</td><td>不行，還是髒</td><td>可以，當天就舒服入住</td></tr>
  </tbody>
</table>

<h2>各自實際做什麼</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧱 粗清</b><span>清掉裝潢留下的大型廢料、板材邊角、包裝紙箱、明顯垃圾，目的是讓空間「清得出來、進得了場」，通常由工班或統包在完工時順手做。</span></div>
  <div class="gg-card"><b>✨ 細清</b><span>逐一處理粉塵、矽利康殘膠、油漆滴點、玻璃水痕，連櫃子內部、五金軌道、燈具縫隙都清，做完當天就能搬進去住。</span></div>
</div>

<h2>裝潢後該選哪種？</h2>
<p>想直接入住、要乾淨到位 → <strong>細清</strong>（多數屋主交屋要的是這個）。若只是清場、之後還要再進場施工 → 粗清即可。簡單記：<strong>「要住人就要細清」</strong>。</p>

<div class="gg-warn">
  <b>⚠️ 費用怎麼抓才不被當細清收</b>
  <p>細清每坪約 NT\$120–250（整戶視坪數與粉塵量約 NT\$15,000–40,000），明顯高於一般打掃。下訂前務必問：① 報的是粗清還是細清；② 含不含玻璃、櫃內、矽利康殘膠；③ 完工驗收標準。避免「用粗清的料、收細清的錢」。<em>實際以現場估價為準。</em></p>
</div>

<h2>彰化在地提醒</h2>
<p>彰化<strong>新建案交屋潮</strong>（員林、彰化市重劃區）與透天自地自建多，細清需求集中在交屋前。旺季（建案集中交屋）要提前排，並確認師傅有「拆裝／損壞保固」，玻璃、櫃體多的案子工時也較長。</p>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">粗清可以自己做嗎？</div>
  <div class="gg-a">大型垃圾可以自己清；但粉塵與殘膠的細清建議交專業，自己做容易刮傷材質、又清不乾淨。</div>
  <div class="gg-q">細清要做多久？</div>
  <div class="gg-a">整戶視坪數約半天到一天，玻璃、櫃體多會更久。</div>
  <div class="gg-q">建商說交屋會清，那是細清嗎？</div>
  <div class="gg-a">多半只是粗清（清場），不等於可入住的細清。要乾淨入住通常得自己另找細清。</div>
  <div class="gg-q">細清和一般居家清潔一樣嗎？</div>
  <div class="gg-a">不一樣。<a href="{$pClean}">一般清潔</a>是日常維護；細清專處理裝潢後粉塵殘膠，工具藥水不同、費用約 1.5–2 倍。</div>
</div>

<p>找彰化細清團隊見 <a href="{$pReno}"><strong>彰化裝潢細清</strong></a>；日常清潔見 <a href="{$pClean}">彰化清潔公司</a>；更多服務見 <a href="{$hub}">彰化居家服務</a>。</p>
HTML;

$upd = $db->prepare("UPDATE guides SET body_html=?, updated_at=NOW() WHERE slug=?");
foreach ([['changhua-cleaning-company-howto', $b1], ['changhua-rough-vs-detail-cleaning', $b2]] as [$slug, $body]) {
    $upd->execute([$body, $slug]);
    $row = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug=" . $db->quote($slug))->fetch(PDO::FETCH_ASSOC);
    printf("✅ #%s %s → %d bytes\n", $row['id'], $slug, $row['l']);
}
echo "\n彰化清潔束 3 篇皆已升級 gg- 範本。\n";
echo "預覽：https://www.gomag.com.tw/city/changhua/home-service/cleaning\n";
