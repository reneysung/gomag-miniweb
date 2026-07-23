<?php
// ============================================================
// Migration 175 — 開「台南系統櫃」子服務落地頁 + 新攻略③《系統櫃板材怎麼挑》
// ------------------------------------------------------------
// Reney 選「②③④全做、順便開頁」。xitonggui(系統櫃) 關鍵字已存在但台南無落地頁 → 開頁。
// ⚠️ 目前掛牌店家少(Gerpros 已標)，Reney 會再補系統櫃客戶；先以內容撐住頁面。
// 攻略③角度：板材(塑合板/密迪板/木心板)、甲醛、封邊、五金 → 與地板系列文完全不同主題。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/175_tainan_cabinet_page_and_guide.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$B = 'https://www.gomag.com.tw/uploads/guides/building-materials';

// ── 1. 開台南系統櫃 geo 落地頁 ──
$intro = <<<'H'
<p>台南找系統櫃，很多人比的是「一尺多少」，但真正決定用十年會不會後悔的，是<strong>板材、封邊與五金</strong>這三件事。系統櫃的櫃身多半用塑合板，門片可能是塑合板、密迪板或烤漆；封邊條的材質與貼合品質決定會不會吃水、爆邊；鉸鏈與滑軌則直接影響開闔手感與壽命。</p>
<p>挑台南系統櫃看幾件事：①<strong>板材品牌與等級</strong>（問清楚是哪家板、甲醛等級 F☆☆☆☆／E0／E1）；②<strong>封邊方式</strong>（PVC／ABS 封邊條、貼合是否平整）；③<strong>五金品牌</strong>（鉸鏈、滑軌是否列入報價）；④<strong>報價是否逐項</strong>（櫃體、門片、五金、facing 分開列）；⑤<strong>現場丈量與圖說</strong>。建議到有實體板材與門片樣品的展間，摸過封邊、開闔過五金再決定。</p>
H;
$faqs = json_encode([
 ['q'=>'台南系統櫃一尺多少錢？','a'=>'依板材等級、門片材質與五金品牌差異大，通常以「尺」報價並另計五金與特殊造型。比價前要確認是同板材、同五金、同封邊方式，否則不同東西沒得比。'],
 ['q'=>'系統櫃板材要看什麼？','a'=>'看板材品牌與甲醛等級（F☆☆☆☆／E0／E1）、是否為防潮型板材、板厚，以及封邊材質與貼合品質。這些比「一尺多少」更能決定耐用度。'],
 ['q'=>'系統櫃和木作櫃差在哪？','a'=>'系統櫃以工廠標準化板材與五金組裝，工期短、粉塵少、可拆移；木作櫃現場施作、造型自由度高但工期較長。兩者常混搭使用。'],
 ['q'=>'台南潮濕，系統櫃會受潮嗎？','a'=>'櫃身板材與封邊是關鍵。靠外牆、浴室旁或一樓的櫃體建議選防潮型板材、封邊要確實；浴櫃則常用發泡板等防水材質。'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active, created_at)
    VALUES ('tainan', 2, 'xitonggui', '系統櫃', ?, ?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE service_name='系統櫃', intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1")
  ->execute([$intro,$faqs,'台南系統櫃推薦｜板材、封邊、五金怎麼挑｜店家好口碑','台南系統櫃怎麼挑？塑合板與密迪板差在哪、甲醛等級怎麼看、封邊與五金為何是關鍵、報價要逐項列什麼，台南在地系統櫃與建材商家一次看。']);
$g=$db->query("SELECT id,is_active FROM geo_category_pages WHERE city_slug='tainan' AND category_id=2 AND service_slug='xitonggui'")->fetch(PDO::FETCH_ASSOC);
echo "1) 台南系統櫃 geo #{$g['id']} active={$g['is_active']}\n";

// ── 2. 攻略③ ──
$slug='system-cabinet-board-guide';
$title='系統櫃板材怎麼挑？塑合板、密迪板、甲醛等級與封邊一次看懂';
$excerpt='系統櫃報價都在比「一尺多少」，但真正決定用十年會不會後悔的是板材、封邊與五金。這篇拆解塑合板、密迪板、木心板差在哪、甲醛等級怎麼看、封邊為什麼是防潮關鍵、五金該不該指定品牌。';
$metaTitle='系統櫃板材怎麼挑｜塑合板 vs 密迪板、甲醛等級、封邊與五金';
$metaDesc='系統櫃板材怎麼選？塑合板、密迪板（MDF）、木心板、發泡板差在哪，甲醛 F☆☆☆☆／E0／E1 怎麼看，封邊材質與五金品牌為何影響壽命，報價要逐項列什麼，一篇看懂不被一尺多少帶著走。';
$cover=$B.'/cabinet-board-colors.jpg';

$body = <<<HTML
<style>
.g-guide-body figure{margin:2.2rem 0 2.8rem;}
.g-guide-body figure img{border-radius:12px;display:block;width:100%;}
.g-guide-body figcaption{margin-top:12px;padding:0 4px;font-size:.9rem;line-height:1.75;color:#8a8a8a;text-align:center;}
</style>
<p class="gg-lead">問系統櫃，十個有九個先問「一尺多少」。但同樣一尺，可以用完全不同等級的板材、封邊與五金做出來——價格差兩成，用十年後的差別可能是「還很好用」跟「門片爆邊、抽屜卡住」。這篇把板材、封邊、五金這三件真正決定壽命的事講清楚。</p>

<h2>系統櫃常見板材，差在哪</h2>
<div class="gg-cards">
  <div class="gg-card"><b>塑合板（粒片板）</b><span>系統櫃櫃身主流。木屑加膠高壓成型，結構穩定、不易變形、握釘力足，防潮型（俗稱綠板）更適合台灣氣候。</span></div>
  <div class="gg-card"><b>密迪板／MDF</b><span>中密度纖維板，質地細緻均勻，適合做造型門片與烤漆。缺點是較重、遇水膨脹風險高，濕區要留意。</span></div>
  <div class="gg-card"><b>木心板</b><span>中間實木條、上下夾板，握釘力好、承重佳，常見於木作與需要吃力的結構，價格較高。</span></div>
  <div class="gg-card"><b>發泡板（PVC）</b><span>完全防水、不怕潮，常用於浴櫃與濕區。質感與承重不如前幾種，多用在特定位置。</span></div>
</div>
<p>一般住宅的系統櫃，最常見是<strong>塑合板櫃身＋不同材質門片</strong>的組合。所以問板材時，要分開問「櫃身用什麼、門片用什麼」，不能只問一個答案。</p>

<figure><img src="{$B}/cabinet-board-colors.jpg" alt="系統板材的多種色系與飾面應用展示" loading="lazy"><figcaption>板材的飾面與色系選擇，直接決定櫃體完成後的質感走向。</figcaption></figure>

<h2>甲醛等級：櫃體比地板更該在意</h2>
<p>系統櫃的板材面積大、又在密閉空間裡（衣櫃、收納櫃），甲醛的影響比地板更直接。標示由嚴到寬大致是 <strong>F☆☆☆☆ → E0 → E1</strong>。有小孩、孕婦或過敏體質的家庭，通常會直接指定 F☆☆☆☆ 或 E0。</p>
<div class="gg-warn"><b>⚠️「低甲醛板」不是等級</b><p>要問的是具體等級與板材品牌，並請對方出示<strong>板材的原廠標示或檢測文件</strong>。只說「我們用的是好板」「符合國家標準」，等於沒有回答。</p></div>

<h2>封邊：最容易被忽略、卻最會出事的地方</h2>
<p>板材裁切後的斷面必須封邊，否則會吸水、膨脹、爆邊。封邊條常見 PVC 或 ABS 材質；貼合品質（有沒有溢膠、縫隙、翹起）比材質本身更關鍵。<strong>看樣品時用手摸封邊接縫</strong>，平順沒有段差的才是做得好的。濕區、靠外牆的櫃體更要確認封邊確實。</p>

<figure><img src="{$B}/cabinet-system-space.jpg" alt="住宅系統櫃與收納櫃體的整體規劃實景" loading="lazy"><figcaption>櫃體規劃要連同門片、把手與五金一起看，完成後的整體感才會一致。</figcaption></figure>

<h2>五金：開闔手感與壽命的關鍵</h2>
<p>鉸鏈與滑軌是每天都在用的東西，也是報價最容易被「省」掉的地方。要問清楚：<strong>五金品牌與型號、是否含緩衝（自動回歸）、抽屜滑軌的承重與全展／半展</strong>。有些報價把五金另計，比價時很容易被誤導——一定要確認報價含不含五金、含哪一級。</p>

<h2>報價要逐項，不能只有一個總價</h2>
<ol>
  <li><strong>櫃身板材</strong>：品牌、等級（甲醛）、是否防潮型、板厚。</li>
  <li><strong>門片</strong>：材質（塑合／密迪／烤漆／玻璃）、飾面、封邊方式。</li>
  <li><strong>五金</strong>：鉸鏈、滑軌品牌與型號、是否緩衝。</li>
  <li><strong>特殊件</strong>：拉籃、升降、轉角、燈條等另計項目。</li>
  <li><strong>施工與丈量</strong>：現場丈量、安裝、既有櫃體拆除是否含。</li>
</ol>

<div class="gg-tip"><b>💡 台南在地提醒</b><p>台南濕度高，靠外牆、後陽台旁與一樓的櫃體，建議直接指定<strong>防潮型板材</strong>並確認封邊品質。挑櫃前先到有實體板材與門片樣品的展間，摸封邊、開闔五金、看飾面在自然光下的顏色——這些在型錄上都看不出來。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">系統櫃的板材有哪幾種？該怎麼選？</div>
  <div class="gg-a">櫃身主流是塑合板（防潮型更適合台灣），門片可能用塑合板、密迪板（MDF，適合造型與烤漆）或玻璃；木心板承重與握釘力好、價格高；濕區浴櫃常用發泡板。問報價時要分開問櫃身與門片。</div>
  <div class="gg-q">系統櫃的甲醛等級要看什麼？</div>
  <div class="gg-a">由嚴到寬大致是 F☆☆☆☆、E0、E1。櫃體板材面積大又在密閉空間，比地板更該在意。要請對方出示板材原廠標示或檢測文件，只說「低甲醛」不算。</div>
  <div class="gg-q">封邊為什麼重要？</div>
  <div class="gg-a">板材斷面沒封好會吸水、膨脹、爆邊。封邊條常見 PVC／ABS，但貼合品質更關鍵。看樣品時用手摸接縫，平順無段差的才做得好，濕區與靠外牆處尤其要確認。</div>
  <div class="gg-q">五金要不要指定品牌？</div>
  <div class="gg-a">建議指定。鉸鏈與滑軌每天使用，直接影響手感與壽命。要問清楚品牌型號、是否含緩衝、滑軌承重與全展／半展，並確認報價是否已包含。</div>
  <div class="gg-q">系統櫃和木作櫃差在哪？</div>
  <div class="gg-a">系統櫃用工廠標準化板材與五金組裝，工期短、粉塵少、日後可拆移；木作櫃現場施作、造型自由度高但工期長、粉塵多。實務上常混搭：大面收納用系統櫃、特殊造型用木作。</div>
  <div class="gg-q">台南潮濕，系統櫃會受潮嗎？</div>
  <div class="gg-a">會，關鍵在板材與封邊。靠外牆、浴室旁與一樓的櫃體建議選防潮型板材、確認封邊確實；浴櫃可考慮發泡板等防水材質。</div>
</div>

<p>想看台南在地的系統櫃與建材商家，可到 <a href="https://www.gomag.com.tw/city/tainan/home-service/xitonggui"><strong>台南系統櫃</strong></a> 專頁。地板部分可延伸看 <a href="https://www.gomag.com.tw/guide/laminate-flooring-quality-guide">超耐磨木地板怎麼看品質</a>；裝潢整體規劃見 <a href="https://www.gomag.com.tw/guide/tainan-interior-design-recommend">台南室內設計推薦</a>。</p>
HTML;

$faqs2=[
 ['系統櫃的板材有哪幾種？該怎麼選？','櫃身主流是塑合板（防潮型更適合台灣），門片可能用塑合板、密迪板（MDF）或玻璃；木心板承重與握釘力好、價格高；濕區浴櫃常用發泡板。報價時要分開問櫃身與門片。'],
 ['系統櫃的甲醛等級要看什麼？','由嚴到寬大致是 F☆☆☆☆、E0、E1。櫃體板材面積大又在密閉空間，比地板更該在意。要請對方出示板材原廠標示或檢測文件。'],
 ['封邊為什麼重要？','板材斷面沒封好會吸水、膨脹、爆邊。封邊條常見 PVC／ABS，貼合品質更關鍵。看樣品時摸接縫，平順無段差才做得好。'],
 ['五金要不要指定品牌？','建議指定。鉸鏈與滑軌每天使用，直接影響手感與壽命。要問品牌型號、是否含緩衝、滑軌承重與全展／半展，並確認報價是否包含。'],
 ['系統櫃和木作櫃差在哪？','系統櫃用工廠標準化板材與五金組裝，工期短、粉塵少、可拆移；木作櫃現場施作、造型自由度高但工期長。實務上常混搭。'],
 ['台南潮濕，系統櫃會受潮嗎？','會，關鍵在板材與封邊。靠外牆、浴室旁與一樓的櫃體建議選防潮型板材、確認封邊確實；浴櫃可考慮發泡板。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs2 as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'tainan', 2, 'xitonggui', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=2,service_slug='xitonggui',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),cover_image=VALUES(cover_image),status='published'")
  ->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "2) ③ #{$r['id']} {$slug} body={$r['bl']}B 約".mb_strlen(strip_tags($body))."字 圖=".substr_count($body,'<img')."\n";
echo "\n實頁： https://www.gomag.com.tw/city/tainan/home-service/xitonggui\n";
