<?php
// ============================================================
// Migration 160 — 攻略《嫁接睫毛怎麼挑》(苗栗美睫，緣子角度，教育文 B 案)
// ------------------------------------------------------------
// Reney 選 B 案：純教育文，用緣子(yenbrows) /store 的作品照(已在 gomag 圖床)、
// 中性 alt、**不提品牌、不放聯絡、不連客戶**。掛苗栗美睫子服務頁。
// service_slug=eyelash、city=miaoli、category=3。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/160_guide_miaoli_eyelash_howto.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$B = 'https://www.gomag.com.tw/uploads/brand';

$slug = 'miaoli-eyelash-how-to-choose';
$title = '嫁接睫毛怎麼挑？單根、濃密、捲度、材質，苗栗竹南頭份想做的看這篇';
$excerpt = '嫁接睫毛款式、捲度、材質差很多，做不好還會夾傷真睫。這篇把單根 vs 濃密、捲度顏色、材質保養、維持與卸除、怎麼挑美睫師講清楚，苗栗竹南頭份想做的先看。';
$metaTitle = '嫁接睫毛怎麼挑？單根 vs 濃密、捲度、維持卸除｜苗栗竹南頭份美睫';
$metaDesc = '嫁接睫毛怎麼選不傷真睫？單根與濃密（束感）差在哪、C/D/LC 捲度與顏色、材質保養、多久回補、怎麼卸除、怎麼挑美睫師，苗栗竹南頭份想做的一次看。';
$cover = $B . '/img_6a55a2848337f7.05573554.jpg';

$body = <<<HTML
<p class="gg-lead">嫁接睫毛做起來眼睛有神、省下每天夾睫毛刷睫毛膏的時間，是很多人固定回訪的保養。但款式、捲度、材質差很多，挑不對不只效果不如預期，做不好還可能夾傷自己的真睫毛。這篇把嫁接睫毛怎麼挑講清楚——單根 vs 濃密、捲度顏色、材質保養、維持與卸除、怎麼挑美睫師，苗栗竹南、頭份想做的先看。</p>

<figure><img src="{$B}/img_6a55a2848337f7.05573554.jpg" alt="日式單根嫁接自然款美睫作品特寫" loading="lazy"><figcaption>單根嫁接一根真睫接一根假睫，做出來自然、貼近裸妝。</figcaption></figure>

<h2>嫁接睫毛是什麼</h2>
<p>嫁接（接睫毛）是用專用膠，把假睫毛一根一根接在你的真睫毛上，跟以前那種「一整排種上去」不同。依接的方式與數量，分成「單根」和「濃密（束感、開花）」，效果和適合的人不一樣。</p>

<h2>單根 vs 濃密（束感）怎麼選</h2>
<div class="gg-cards">
  <div class="gg-card"><b>單根自然</b><span>一根真睫接一根假睫，妝感自然、貼近裸妝，適合上班、想低調或第一次做的人。</span></div>
  <div class="gg-card"><b>濃密／開花</b><span>一根真睫接多根假睫，妝感重、眼睛放大明顯，適合想要電眼效果、或本身睫毛稀疏的人。</span></div>
  <div class="gg-card"><b>束感款</b><span>刻意做出一束一束的排列，戴口罩也有存在感，是近年很受歡迎的風格。</span></div>
</div>
<figure><img src="{$B}/img_6a55a284839315.99607973.jpg" alt="束感睫毛作品，戴口罩也有存在感的電眼效果" loading="lazy"><figcaption>束感款排列整齊、存在感高；濃密度越高，對真睫的負擔也要越留意。</figcaption></figure>

<h2>捲度與顏色</h2>
<p>捲度決定睫毛翹的角度，顏色影響整體感覺，兩個都要配眼型與需求。</p>
<table class="gg-table">
  <thead><tr><th>常見捲度</th><th>感覺</th><th>適合</th></tr></thead>
  <tbody>
    <tr><td>C 捲</td><td>自然上揚</td><td>大多數人、日常</td></tr>
    <tr><td>D／CC 捲</td><td>翹度明顯、放大感強</td><td>想要電眼、單眼皮</td></tr>
    <tr><td>LC／M 捲</td><td>介於中間、自然帶翹</td><td>想自然又有神</td></tr>
  </tbody>
</table>
<p>顏色除了黑色，棕色系是近年熱門，看起來柔和、亞洲膚色好搭；也可以黑棕混搭做層次。</p>
<figure><img src="{$B}/img_6a55a28483ec29.74001348.jpg" alt="C 捲棕色日式單根嫁接搭配眼線款眼部特寫" loading="lazy"><figcaption>棕色系柔和、好搭；捲度與顏色都可依眼型客製。</figcaption></figure>

<h2>材質與保養</h2>
<p>睫毛與膠水的品質，直接關係到舒適度與維持。挑的時候可以問清楚睫毛材質、膠水是不是低敏、味道會不會刺激。做完的保養也影響持久度：</p>
<ul>
  <li><strong>前 4～6 小時避免碰水</strong>，讓膠完全固化。</li>
  <li><strong>少揉眼、少用油性眼部產品</strong>（卸妝油會溶膠）。</li>
  <li>可用睫毛<strong>定型液／睫毛雨衣</strong>保養、幫助定型。</li>
  <li>洗臉輕拍、用睫毛刷順一順，不要用力搓。</li>
</ul>
<figure><img src="{$B}/img_6a55a28483c0d4.50744857.jpg" alt="含保養成分的睫毛定型液（睫毛雨衣）" loading="lazy"><figcaption>睫毛雨衣／定型液可幫助定型、延長維持。</figcaption></figure>

<h2>維持多久、多久回補</h2>
<p>真睫毛有自己的生長週期，會自然代謝掉，嫁接的睫毛也會跟著掉。一般<strong>每 3～4 週回補一次</strong>，把掉的補回來、維持濃密度。想長期維持，固定回補比每次重做划算，也對真睫比較好。</p>

<h2>卸除要用專用的、別硬拔</h2>
<p>不想接了、或要重做，一定要用<strong>專用卸除液</strong>由美睫師卸，不要自己硬拔——硬拔會連真睫一起扯掉。正規工作室都有提供卸除服務。</p>

<h2>怎麼挑美睫師／工作室</h2>
<ol>
  <li><strong>看實際作品照</strong>：單根有沒有隔開、排列整不整齊，比修圖美照重要。</li>
  <li><strong>單根是否黏成撮</strong>：黏成一撮會夾住多根真睫、一起被拉掉，容易傷睫。</li>
  <li><strong>膠水與材質</strong>：低敏膠、味道不刺激，眼睛容易過敏更要問。</li>
  <li><strong>環境與衛生</strong>：器具消毒、一人一套、鑷子清潔。</li>
  <li><strong>卸除與回補</strong>：有沒有提供專用卸除、回補價怎麼算。</li>
  <li><strong>預約制</strong>：多數工作室採預約，熱門時段要提早約。</li>
</ol>

<div class="gg-warn"><b>⚠️ 傷真睫的兩個常見狀況</b><p>一是單根沒隔開、黏成撮，真睫被硬拉；二是自己手癢硬拔。想保住真睫毛，選會單根隔開的美睫師、卸除交給專業。</p></div>

<div class="gg-tip"><b>💡 苗栗竹南、頭份想做</b><p>竹南、頭份鄰竹科生活圈、美睫工作室選擇多。先看美睫師的實際作品照與評價，多數是預約制、一人一套服務，假日與下班時段較滿，建議提早 3～5 天預約。</p></div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">嫁接睫毛會傷真睫毛嗎？</div>
  <div class="gg-a">正規做法會單根隔開、不黏成撮、用低敏膠，卸除用專用卸除液，對真睫負擔較小。傷睫多半來自黏成撮硬拉、或自己硬拔。</div>
  <div class="gg-q">嫁接睫毛多久回補一次？</div>
  <div class="gg-a">一般每 3～4 週回補一次，依真睫生長週期與保養習慣而定。固定回補比每次重做划算、也較護睫。</div>
  <div class="gg-q">單根和濃密（束感）怎麼選？</div>
  <div class="gg-a">單根自然、適合日常與第一次做；濃密／束感妝感重、放大感明顯，適合想電眼效果或睫毛稀疏的人。可依眼型請美睫師建議。</div>
  <div class="gg-q">做完可以碰水、洗臉嗎？</div>
  <div class="gg-a">前 4～6 小時避免碰水讓膠固化；之後洗臉輕拍、避免揉眼與油性卸妝品，可用睫毛刷順一順維持。</div>
  <div class="gg-q">不想接了怎麼卸？</div>
  <div class="gg-a">要用專用卸除液由美睫師卸，不要自己硬拔，硬拔會連真睫一起扯掉。</div>
  <div class="gg-q">苗栗哪一區美睫選擇最多？</div>
  <div class="gg-a">竹南、頭份因鄰竹科生活圈、人口密集，美睫工作室最多；苗栗市一帶也有在地店家。</div>
</div>

<p>想看苗栗在地的美睫工作室，可到 <a href="https://www.gomag.com.tw/city/miaoli/beauty/eyelash">苗栗美睫</a> 頁；更多苗栗在地商家見 <a href="https://www.gomag.com.tw/city/miaoli">苗栗店家推薦</a>。</p>
HTML;

$faqs = [
 ['嫁接睫毛會傷真睫毛嗎？','正規做法會單根隔開、不黏成撮、用低敏膠，卸除用專用卸除液，對真睫負擔較小。傷睫多半來自黏成撮硬拉或自己硬拔。'],
 ['嫁接睫毛多久回補一次？','一般每 3～4 週回補一次，依真睫生長週期與保養習慣而定，固定回補比每次重做划算。'],
 ['單根和濃密（束感）怎麼選？','單根自然、適合日常與第一次做；濃密／束感妝感重、放大感明顯，適合想電眼或睫毛稀疏的人。'],
 ['做完可以碰水、洗臉嗎？','前 4～6 小時避免碰水讓膠固化；之後洗臉輕拍、避免揉眼與油性卸妝品。'],
 ['不想接了怎麼卸？','要用專用卸除液由美睫師卸，不要自己硬拔，硬拔會連真睫一起扯掉。'],
 ['苗栗哪一區美睫選擇最多？','竹南、頭份因鄰竹科生活圈、人口密集，美睫工作室最多；苗栗市一帶也有在地店家。'],
];
$faqLd=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$faqLd['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($faqLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$g=$db->prepare("INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
    VALUES (?,?,?,?, 'miaoli', 3, 'eyelash', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),service_slug=VALUES(service_slug),
      meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),cover_image=VALUES(cover_image),status='published'");
$g->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug} body={$r['bl']}B gg-=".substr_count($body,'gg-')." 圖=".substr_count($body,'<img')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
