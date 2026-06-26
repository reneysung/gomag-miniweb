<?php
// ============================================================
// Migration 146 — SEO 改造 Round 1：深化 2 篇高曝光通用清潔文
// ------------------------------------------------------------
// GSC：regular-cleaning-frequency(141 曝光/pos21.7)、moving-cleaning-cost(76/pos17)
// 曝光最高但薄（1.8/2.2KB、純 h3/p、無行情表/FAQ）卡第 2-3 頁。
// 改：升 gg- 結構化元件、加行情表、加 FAQ + 內嵌 FAQPage JSON-LD、補內鏈、
//     refine meta_desc（補行情數字提 CTR）。人味鐵則：平實、無金句、無「不是X而是Y」。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/146_cleaning_generic_guides_deepen.php
// 冪等：UPDATE by id。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ───────── 1. regular-cleaning-frequency (#3) ─────────
$body3 = <<<'HTML'
<p class="gg-lead">請人定期到府打掃，最常卡住的就是「多久一次才划算」。叫太密集，錢花得兇；隔太久，又像沒清過。這篇從你家的型態跟計價方式回推，幫你抓一個剛好的頻率。</p>

<h2>定期清潔怎麼計價</h2>
<p>先搞懂三種收費方式，才好算哪種對你划算。</p>
<table class="gg-table">
  <thead><tr><th>方式</th><th>行情</th><th>適合誰</th></tr></thead>
  <tbody>
    <tr><td>鐘點制</td><td>每小時 NT$400–650，多數最低 3 小時</td><td>定點加強廚房浴室、時間彈性</td></tr>
    <tr><td>坪數／單次</td><td>每次約 NT$2,000–3,500，依坪數屋況</td><td>要全室清一遍</td></tr>
    <tr><td>包月／長約</td><td>固定頻率到府，單次單價比臨時叫低</td><td>需求穩定、想固定同一位</td></tr>
  </tbody>
</table>
<p>同樣是定期，鐘點適合「我自己會維持、只要人來補強重點」；包月適合「懶得每次找、希望品質穩定」。</p>

<h2>多久一次？看家庭型態</h2>
<p>頻率不是越勤越好，照實際使用強度抓才不浪費。</p>
<div class="gg-cards">
  <div class="gg-card"><b>雙薪、小宅、無小孩寵物</b><span>雙週一次多半夠。日常自己維持，定期做一次深度的。</span></div>
  <div class="gg-card"><b>有幼兒／寵物／過敏體質</b><span>建議每週一次。地板、毛屑、浴廁衛生需求高，間隔拉長很快又髒。</span></div>
  <div class="gg-card"><b>透天、大坪數</b><span>每週或雙週，依樓層分區輪流清，不必每次全棟一起。</span></div>
  <div class="gg-card"><b>單身、租屋小套房</b><span>每月一次，或用鐘點定點加強廚衛就好。</span></div>
  <div class="gg-card"><b>在家工作、常開伙</b><span>廚房油煙、垃圾量大，廚衛可拉到每週、其他區雙週。</span></div>
  <div class="gg-card"><b>長輩同住</b><span>每週一次較安心，地板止滑、浴廁防霉是重點。</span></div>
</div>

<h2>怎麼算划算</h2>
<p>最簡單的算法：把「自己清要花的時間 × 你一小時值多少」跟「清潔費用」擺一起比。多數雙薪家庭算下來，雙週一次的 CP 值最高。包月或長約如果能談到較低的單次單價、又能固定同一位清潔員（她熟你家、品質穩定、不用每次重講），長期通常比每次臨時叫划算。</p>
<div class="gg-tip"><b>💡 先試一次再決定</b><p>別一開始就簽長約。先單次叫一次，看做事細不細、合不合得來，再決定要不要包月。談長約時把單次單價、頻率、能不能指定同一人一起講定。</p></div>

<h2>找定期清潔，先問清楚這幾件</h2>
<ol>
  <li><strong>固定派同一人還是輪替</strong>：要品質穩定就指定同一位。</li>
  <li><strong>每次的標準範圍</strong>：哪些每次都做、哪些定期才做，列清楚。</li>
  <li><strong>臨時取消／改期規則</strong>：提前多久通知不收費。</li>
  <li><strong>機具藥劑誰準備</strong>：自備還是要你出。</li>
  <li><strong>鑰匙與信任</strong>：固定到府要留鑰匙的話，找有制度、能簽約的團隊較放心。</li>
</ol>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">定期清潔多久一次最划算？</div>
  <div class="gg-a">多數雙薪、無幼兒寵物的家庭，雙週一次的 CP 值最高；有幼兒、寵物或過敏體質建議每週一次；單身小套房每月一次或鐘點加強即可。</div>
  <div class="gg-q">包月一定比鐘點便宜嗎？</div>
  <div class="gg-a">單次單價通常比臨時叫低，但要你需求夠穩定、用得到那個頻率才划算；用不到的頻率包月反而浪費，先評估實際需要。</div>
  <div class="gg-q">鐘點制最低要幾小時？</div>
  <div class="gg-a">多數業者每次最低 3 小時起，每小時約 NT$400–650。只清廚房浴室這種定點加強，用鐘點較彈性。</div>
  <div class="gg-q">可以指定固定同一位清潔員嗎？</div>
  <div class="gg-a">多數包月／長約可以，下訂前先確認。固定同一人她熟悉你家動線跟要求，品質比較穩。</div>
  <div class="gg-q">定期清潔包含哪些？</div>
  <div class="gg-a">一般含地板、浴廁、廚房、桌面、倒垃圾等全室基本清潔；玻璃外窗、冷氣拆洗、除垢打蠟多半另計，下訂前把每次的標準範圍問清楚。</div>
</div>

<p>想找能長期配合、口碑穩的在地清潔，可從各縣市清潔頁看店家好口碑收錄的商家，例如 <a href="https://www.gomag.com.tw/city/tainan/home-service/cleaning">台南清潔公司</a>、<a href="https://www.gomag.com.tw/city/taichung/home-service/cleaning">台中清潔公司</a>、<a href="https://www.gomag.com.tw/city/kaohsiung/home-service/cleaning">高雄清潔公司</a>。搬家或退租的一次性清潔費用怎麼算，另見 <a href="https://www.gomag.com.tw/guide/moving-cleaning-cost">搬家退租清潔費用</a>。</p>
HTML;

$faq3 = [
  ['定期清潔多久一次最划算？','多數雙薪、無幼兒寵物的家庭，雙週一次的 CP 值最高；有幼兒、寵物或過敏體質建議每週一次；單身小套房每月一次或鐘點加強即可。'],
  ['包月一定比鐘點便宜嗎？','單次單價通常比臨時叫低，但要你需求夠穩定、用得到那個頻率才划算；用不到的頻率包月反而浪費。'],
  ['鐘點制最低要幾小時？','多數業者每次最低 3 小時起，每小時約 NT$400–650。只清廚房浴室這種定點加強用鐘點較彈性。'],
  ['可以指定固定同一位清潔員嗎？','多數包月／長約可以，下訂前先確認。固定同一人熟悉你家動線跟要求，品質比較穩。'],
  ['定期清潔包含哪些？','一般含地板、浴廁、廚房、桌面、倒垃圾等全室基本清潔；玻璃外窗、冷氣拆洗、除垢打蠟多半另計。'],
];
$meta3 = '定期居家清潔多久一次划算？鐘點每小時 NT$400–650、坪數單次、包月長約怎麼選，依雙薪、有寵物幼兒、透天大坪數抓合適頻率，附計價表與 FAQ。';

// ───────── 2. moving-cleaning-cost (#2) ─────────
$body2 = <<<'HTML'
<p class="gg-lead">搬家前後、租約到期，常要來一次一次性的深度清潔。但「搬家清潔」「退租清潔」到底怎麼報價，哪些含、哪些另計，沒先問清楚很容易被追加。這篇把計價邏輯跟行情講白，讓你抓預算不踩雷。</p>

<h2>搬家清潔 vs 退租清潔，需求不一樣</h2>
<div class="gg-cards">
  <div class="gg-card"><b>搬家清潔</b><span>搬進新居前的全室清潔，或舊居搬空後的整理，目標是讓空間到「可以入住」的乾淨度。</span></div>
  <div class="gg-card"><b>退租清潔</b><span>租約到期要還房東，目標是恢復到可驗收的狀態，對照租約點交標準跟入住時屋況，避免被扣押金。</span></div>
</div>

<h2>搬家退租清潔行情</h2>
<p>實際看屋況、坪數與髒污程度，這是抓預算的參考區間。</p>
<table class="gg-table">
  <thead><tr><th>類型</th><th>計價</th><th>參考行情</th></tr></thead>
  <tbody>
    <tr><td>空屋深度清潔</td><td>坪數計</td><td>每坪約 NT$100–200，含廚衛除垢、玻璃外窗者較高</td></tr>
    <tr><td>一次性大掃除</td><td>單次</td><td>約 NT$3,000 起，依坪數屋況浮動</td></tr>
    <tr><td>套房／雅房</td><td>件計或最低消</td><td>小坪數常以最低消計</td></tr>
  </tbody>
</table>
<p>屋況越差（長年油垢、養寵物、菸垢、霉斑）越貴。報價多數以坪數為主，先講清楚屋況才估得準。</p>

<h2>哪些常常另計，先問</h2>
<div class="gg-cards">
  <div class="gg-card"><b>玻璃外窗、高處</b><span>需要吊掛或高梯的外窗、燈具、天花常另計。</span></div>
  <div class="gg-card"><b>垃圾與大型廢棄物</b><span>清運費多半另算，丟舊家具沙發要先報。</span></div>
  <div class="gg-card"><b>冷氣、油煙機深洗</b><span>內機拆洗、油煙機深度清洗算加購。</span></div>
  <div class="gg-card"><b>頑垢加強</b><span>水垢、霉斑、地板打蠟這類需要多花工的另計。</span></div>
</div>

<h2>退租清潔：保住押金的重點</h2>
<ol>
  <li><strong>清潔前先拍照存證</strong>屋況，留下還原前後對比。</li>
  <li><strong>對照入住點交清單</strong>，只清「該還原」的部分，不必過度加購。</li>
  <li>請業者<strong>列出含／不含項目</strong>，清完<strong>再拍一次</strong>給房東。</li>
  <li>房東若指定清潔公司或標準，先確認<strong>費用由誰負擔</strong>，寫進協議。</li>
</ol>

<div class="gg-warn"><b>⚠️ 最容易被追加的地方</b><p>「一坪多少」聽起來便宜，但沒講清楚含不含玻璃外窗、垃圾清運、冷氣油煙機，到現場很容易一項一項加上去。下訂前一定要白紙黑字列含／不含、幾人幾工時。</p></div>

<div class="gg-tip"><b>💡 旺季要早約</b><p>畢業季、年前是搬遷旺季，越接近越難排。退租有交屋期限的話，建議提前 1–2 週預約卡時間。</p></div>

<h2>怎麼抓才不被追加</h2>
<p>搬家、退租清潔跟屋況高度相關，務必<strong>到府或視訊估價</strong>，別只憑電話報個均一價。估價時把含／不含、幾人幾工時、能不能當天完成、驗收不過是否免費補清都問清楚、寫下來。</p>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">退租清潔費用大概多少？</div>
  <div class="gg-a">空屋深度清潔每坪約 NT$100–200，一次性大掃除約 NT$3,000 起，套房常以最低消計。實際看坪數、屋況與含哪些項目，到府估最準。</div>
  <div class="gg-q">退租一定要請清潔公司嗎？</div>
  <div class="gg-a">不一定，看租約跟房東要求，重點是恢復到入住時屋況、對照點交清單。自己清得乾淨也行；屋況差或趕時間，找有退租經驗的業者較省事、也較不會被扣押金。</div>
  <div class="gg-q">搬家清潔跟退租清潔差在哪？</div>
  <div class="gg-a">搬家清潔重點是新居達到可入住的乾淨度；退租清潔重點是恢復可驗收狀態、對照點交標準保押金。需求不同，跟業者講清楚是哪一種。</div>
  <div class="gg-q">怎麼避免被追加費用？</div>
  <div class="gg-a">到府或視訊估價、白紙黑字列含／不含與幾人幾工時，先問清楚玻璃外窗、垃圾清運、冷氣油煙機這些常另計的項目。</div>
  <div class="gg-q">要提前多久預約？</div>
  <div class="gg-a">平常 3–5 天，畢業季與年前旺季建議提前 1–2 週，退租有期限更要早約。</div>
</div>

<p>想找有退租、空屋清潔經驗的在地業者，可從各縣市清潔頁看店家好口碑收錄的商家，例如 <a href="https://www.gomag.com.tw/city/tainan/home-service/cleaning">台南清潔公司</a>、<a href="https://www.gomag.com.tw/city/taichung/home-service/cleaning">台中清潔公司</a>、<a href="https://www.gomag.com.tw/city/kaohsiung/home-service/cleaning">高雄清潔公司</a>。想算定期清潔多久一次划算，另見 <a href="https://www.gomag.com.tw/guide/regular-cleaning-frequency">定期居家清潔頻率</a>。</p>
HTML;

$faq2 = [
  ['退租清潔費用大概多少？','空屋深度清潔每坪約 NT$100–200，一次性大掃除約 NT$3,000 起，套房常以最低消計。實際看坪數、屋況與含哪些項目，到府估最準。'],
  ['退租一定要請清潔公司嗎？','不一定，看租約跟房東要求，重點是恢復到入住時屋況、對照點交清單。屋況差或趕時間，找有退租經驗的業者較省事、也較不會被扣押金。'],
  ['搬家清潔跟退租清潔差在哪？','搬家清潔重點是新居達到可入住的乾淨度；退租清潔重點是恢復可驗收狀態、對照點交標準保押金。'],
  ['怎麼避免被追加費用？','到府或視訊估價、白紙黑字列含／不含與幾人幾工時，先問清楚玻璃外窗、垃圾清運、冷氣油煙機這些常另計的項目。'],
  ['搬家退租清潔要提前多久預約？','平常 3–5 天，畢業季與年前旺季建議提前 1–2 週，退租有期限更要早約。'],
];
$meta2 = '搬家、退租清潔費用怎麼算？空屋每坪 NT$100–200、大掃除 NT$3,000 起，哪些另計、退租怎麼保押金不被追加，一篇看懂行情與 FAQ。';

// ───────── 套用 ─────────
function faqLdScript(array $faqs): string {
    $ld = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
    foreach ($faqs as $f) $ld['mainEntity'][] = ['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
    return "\n<script type=\"application/ld+json\">" . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}

foreach ([
    ['slug'=>'regular-cleaning-frequency','body'=>$body3,'faq'=>$faq3,'meta'=>$meta3],
    ['slug'=>'moving-cleaning-cost','body'=>$body2,'faq'=>$faq2,'meta'=>$meta2],
] as $g) {
    $full = $g['body'] . faqLdScript($g['faq']);
    $u = $db->prepare("UPDATE guides SET body_html=?, meta_desc=? WHERE slug=?");
    $u->execute([$full, $g['meta'], $g['slug']]);
    $r = $db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$g['slug']}'")->fetch(PDO::FETCH_ASSOC);
    echo "✅ #{$r['id']} {$g['slug']} body={$r['bl']}B  gg-=" . substr_count($g['body'],'gg-') . "  FAQ=" . count($g['faq']) . "\n";
}
echo "\n驗證：\n  https://www.gomag.com.tw/guide/regular-cleaning-frequency\n  https://www.gomag.com.tw/guide/moving-cleaning-cost\n";
