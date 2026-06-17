<?php
// ============================================================
// Migration 128 — 旗艦範本：重寫《彰化居家清潔指南》加長 + gg- 結構化排版
// ------------------------------------------------------------
// Reney 回饋：原文字數不夠、排版陽春。本檔示範新範本：
//   - 用 gomag.css 新加的 .g-guide-body gg- 元件（gg-lead/gg-cards/gg-table/
//     gg-tip/gg-warn/gg-faq）做結構化排版
//   - 內容大幅加長、彰化在地深化（各區差異、行情表、避雷、FAQ）
// 確認此範本 OK 後，套用到其他攻略 + 之後所有城市束。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/128_changhua_home_cleaning_rewrite.php
// 冪等：UPDATE by slug
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'changhua-home-cleaning-guide';
$hub   = 'https://www.gomag.com.tw/city/changhua/home-service';
$pClean = 'https://www.gomag.com.tw/city/changhua/home-service/cleaning';
$pReno  = 'https://www.gomag.com.tw/city/changhua/home-service/reno-detail';

$b = <<<HTML
<p class="gg-lead">彰化的居家清潔需求，從<strong>員林、彰化市</strong>的電梯大樓，到<strong>鹿港、和美、田中、二林、北斗</strong>滿街的透天厝與庄頭老宅，差異很大。「居家清潔」其實分成好幾種服務，計價與適合的情境都不同——挑對種類、講清楚屋況，才不會花了錢還清不到位。這篇把彰化常見的居家清潔類型、行情、各區差異與挑選眉角，一次講清楚。</p>

<h2>彰化居家清潔有哪些種類？</h2>
<p>先搞懂你要的是「日常維護」還是「深層清潔」，再對應服務類型：</p>
<div class="gg-cards">
  <div class="gg-card"><b>🧹 鐘點清潔</b><span>按小時計、單次彈性預約。上班族、小家庭日常維護首選，最常見。</span></div>
  <div class="gg-card"><b>🔁 定期清潔</b><span>每週／雙週／每月固定到府，簽月約單價較低，適合雙薪家庭、長輩同住。</span></div>
  <div class="gg-card"><b>🧽 大掃除</b><span>年終、節慶前的全屋深度整理，旺季搶手，過年前要早約。</span></div>
  <div class="gg-card"><b>📦 退租清潔</b><span>租屋點交前回復屋況、拿回押金，套房到整戶都有。</span></div>
  <div class="gg-card"><b>🏠 空屋／入厝清潔</b><span>新居入住前深層清潔，常與裝潢細清搭配。</span></div>
  <div class="gg-card"><b>✨ 裝潢後細清</b><span>裝潢完的粉塵殘膠專業清理，難度高、單價另計（<a href="{$pReno}">看彰化裝潢細清</a>）。</span></div>
</div>

<h2>彰化居家清潔行情參考</h2>
<p>清潔費用受坪數、屋況、樓層、難易度影響很大，以下為彰化常見區間，<strong>實際以現場估價為準</strong>：</p>
<table class="gg-table">
  <thead><tr><th>服務類型</th><th>計價方式</th><th>行情區間</th><th>適合情境</th></tr></thead>
  <tbody>
    <tr><td>鐘點清潔</td><td>每小時（多數每次最低 3 小時）</td><td>NT\$400–600 / 時</td><td>日常維護</td></tr>
    <tr><td>定期清潔</td><td>月約、每次單價較低</td><td>單次約打 8–9 折</td><td>固定週期維護</td></tr>
    <tr><td>退租／空屋</td><td>整案報價（看坪數）</td><td>套房 2,500–5,000；整戶 5,000–15,000</td><td>點交、入住前</td></tr>
    <tr><td>年終大掃除</td><td>整案報價</td><td>視坪數與項目</td><td>過年、節慶</td></tr>
    <tr><td>裝潢後細清</td><td>每坪</td><td>NT\$120–250 / 坪</td><td>裝潢完入住前</td></tr>
  </tbody>
</table>

<h2>彰化各區清潔需求差異</h2>
<p>彰化幅員廣、屋型差異大，不同區域找清潔要注意的點不一樣：</p>
<ul>
  <li><strong>彰化市、員林</strong>：電梯大樓與公寓多，以室內坪數計、單純好估，鐘點與定期需求最大、清潔公司也最密集。</li>
  <li><strong>鹿港、和美、田中、二林、北斗</strong>：<strong>透天厝比例高</strong>，樓層多、常有前後院與頂樓加蓋——這些都會墊高坪數與工時，估價時務必說明有幾層、含不含庭院頂樓。</li>
  <li><strong>溪湖、芳苑、大城、二林外圍</strong>：距離較遠，部分清潔公司會加車馬費或限定起清坪數，預約時先確認跑不跑。</li>
  <li><strong>八卦山沿線、新興重劃區（員林、彰化市）</strong>：新成屋交屋潮帶動空屋清潔與<a href="{$pReno}">裝潢細清</a>需求。</li>
</ul>

<h2>怎麼挑才不踩雷？5 個重點</h2>
<ol>
  <li><strong>免費到府估價</strong>：屋況用講的不準，正規業者會到府或看照片報價，報價單列清楚含哪些（耗材、垃圾清運、搬挪家具）。</li>
  <li><strong>確認服務範圍與車馬費</strong>：二林、芳苑、大城等較遠區，先問跑不跑、加不加費。</li>
  <li><strong>有沒有責任險</strong>：清潔過程萬一刮傷地板、打破物品，有保險才有保障。</li>
  <li><strong>人員自聘 vs 外包</strong>：自聘團隊品質較穩，外包人力良莠不齊。</li>
  <li><strong>在地口碑</strong>：看 Google 評論「分數＋評論數」雙指標，再問在地朋友，別只比低價。</li>
</ol>

<div class="gg-warn">
  <b>⚠️ 避雷提醒</b>
  <p>報價明顯低於行情、要求先付全額、不到府估價只給「均一價」的，要特別小心——常見後續加價或品質不到位。<strong>預付訂金不超過 30%</strong>、完工驗收滿意再付清。</p>
</div>

<h2>預約前先想清楚 3 件事</h2>
<div class="gg-tip">
  <b>💡 講越清楚，報價越準、越不會有糾紛</b>
  <p>① <strong>坪數與屋型</strong>（幾坪、透天幾層、含不含庭院頂樓）；② <strong>要清的範圍</strong>（全屋／指定區域／含不含廚房浴室重點）；③ <strong>時間</strong>（單次還是定期、有沒有指定日期、過年旺季要早約）。</p>
</div>

<h2>常見問題 FAQ</h2>
<div class="gg-faq">
  <div class="gg-q">鐘點和定期清潔差在哪？</div>
  <div class="gg-a">鐘點＝單次按時計、彈性；定期＝固定週期簽約、單價較低，且通常是同一組人較熟悉你家環境。先單次試一次滿意再簽定期最穩。</div>
  <div class="gg-q">彰化透天厝清潔會比較貴嗎？</div>
  <div class="gg-a">會。樓層、樓梯、前後院、頂樓加蓋都增加工時。彰化透天多，估價時務必說明完整屋況，避免現場才加價。</div>
  <div class="gg-q">居家清潔和裝潢細清一樣嗎？</div>
  <div class="gg-a">不一樣。一般清潔是日常維護；<a href="{$pReno}">裝潢細清</a>專處理裝潢後的粉塵、矽利康殘膠、玻璃水痕，工具藥水不同、費用約一般清潔的 1.5–2 倍。</div>
  <div class="gg-q">員林、二林這些區也有到府清潔嗎？</div>
  <div class="gg-a">有。彰化市、員林最密集；二林、芳苑、大城等較遠區多數仍可到府，但建議提前預約並確認車馬費。</div>
  <div class="gg-q">過年大掃除什麼時候預約？</div>
  <div class="gg-a">11 月起就開始搶，12 月到過年前 2 週最滿，建議 11 月中前就預約卡位。</div>
  <div class="gg-q">退租清潔一定要找清潔公司嗎？</div>
  <div class="gg-a">不一定，但租約常要求「回復原狀」，專業退租清潔較能通過房東驗收、順利拿回押金，CP 值通常划算。</div>
</div>

<p>找彰化在地清潔團隊見 <a href="{$pClean}"><strong>彰化清潔公司</strong></a>；裝潢後入住見 <a href="{$pReno}">彰化裝潢細清</a>；更多服務見 <a href="{$hub}">彰化居家服務</a>。</p>
HTML;

$db->prepare("UPDATE guides SET body_html=?, updated_at=NOW() WHERE slug=?")->execute([$b, $slug]);
$row = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug=" . $db->quote($slug))->fetch(PDO::FETCH_ASSOC);
printf("✅ 重寫 #%s %s → body %d bytes（原 2266）\n", $row['id'], $slug, $row['l']);
echo "預覽：https://www.gomag.com.tw/guide/{$slug}\n";
