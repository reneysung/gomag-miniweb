<?php
// ============================================================
// Migration 169 — 新攻略《台南火鍋推薦》(美食型；無知識段) + 修貍偷聚門亂碼地址
// ------------------------------------------------------------
// Reney 指定 6 家(依序)：牧鍋#87 / XM#19 / 二鍋#13 / 鍋醬#74 / 貍偷聚門#134 / 如潮#266(無評價只放介紹)
// 美食型不套深度知識段 → 招牌/價位/類型比較表 + 真實 Google 評價 + 用餐小提醒 + FAQ。
// category_id=1(餐飲)、service_slug='hotpot'、cover food.svg。geo#11 台南火鍋已 active(不用開)。
// 順手修 #134 貍偷聚門 DB 地址(亂碼「台南市貍偷聚門鍋物」→ Google 實際 海安路二段90號)。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/169_guide_tainan_hotpot_recommend.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 修 #134 亂碼地址 ──
$oldAddr=$db->query("SELECT address FROM clients WHERE id=134")->fetchColumn();
$db->prepare("UPDATE clients SET address=? WHERE id=134")->execute(['台南市中西區海安路二段90號']);
echo "修 #134 貍偷聚門地址：[{$oldAddr}] → [台南市中西區海安路二段90號]\n\n";

$slug='tainan-hotpot-recommend';
$title='台南火鍋推薦 2026｜嚴選 6 家在地鍋物｜吃到飽、頂級牛鍋、平價個人鍋一次看';
$excerpt='台南火鍋吃哪家？店家好口碑嚴選牧鍋、XM 麻辣鍋、二鍋、鍋醬、貍偷聚門、如潮沙茶爐 6 家台南在地鍋物，從麻辣海鮮吃到飽、頂級熟成牛鍋、平價個人小火鍋到鮮奶鍋、沙茶爐一次看，附招牌、價位帶與真實 Google 評價。';
$metaTitle='台南火鍋推薦 2026｜吃到飽/頂級牛鍋/平價個人鍋｜在地嚴選 6 家';
$metaDesc='台南火鍋推薦哪幾家？麻辣海鮮吃到飽（XM）、頂級熟成牛鍋（牧鍋）、壽喜燒吃到飽（二鍋）、平價小火鍋（鍋醬）、鮮奶鍋（貍偷聚門）、沙茶爐（如潮），永康、東區、中西區、南區在地鍋物與真實評價一次看。';
$cover='https://www.gomag.com.tw/uploads/guides/covers/food.svg';

$body = <<<'HTML'
<style>
.g-guide-body .si{background:#f7f4f2;border-radius:10px;padding:10px 15px;margin:11px 0;font-size:.93rem;line-height:1.95;color:#555;}
.g-guide-body .si b{color:#1a1a1a;}
.g-guide-body .si a{color:#FF5A36;font-weight:700;text-decoration:none;}
.g-guide-body .gr{background:#fff;border:1px solid #eadfd9;border-left:3px solid #34A853;border-radius:10px;padding:11px 15px;margin:11px 0;}
.g-guide-body .gr .h{font-size:.88rem;font-weight:700;color:#555;margin-bottom:5px;}
.g-guide-body .gr .h .st{color:#FBBC04;letter-spacing:1px;}
.g-guide-body .gr blockquote{margin:0;font-size:.94rem;line-height:1.85;color:#333;}
.g-guide-body .gr cite{display:block;margin-top:6px;font-style:normal;font-size:.82rem;color:#888;}
</style>
<p class="gg-lead">天氣一涼、或是三五好友、家庭聚餐，台南人第一個想到的常常是「吃鍋」。但台南火鍋選擇太多——想大口吃到飽、想吃頂級熟成牛、想找銅板價的個人小火鍋，還是想喝特色鮮奶湯底，適合的店完全不同。這篇店家好口碑嚴選 <strong>6 家台南在地鍋物</strong>，從永康的麻辣海鮮吃到飽、東區的頂級牛鍋，到南區的平價小火鍋與海安路的鮮奶鍋，帶你依需求一次挑對。</p>
<p><em>※ 名單為店家好口碑收錄之台南在地商家，資料以各店公開資訊與 Google 商家為準，價格與品項請以店家現場公告為主。</em></p>

<h2 id="compare">先看懶人包｜6 家台南火鍋一次比較</h2>
<table class="gg-table">
<thead><tr><th>店家</th><th>類型</th><th>價位帶</th><th>招牌</th><th>Google</th></tr></thead>
<tbody>
<tr><td>牧鍋 MooPot</td><td>頂級牛鍋・自助吧</td><td>中高價</td><td>極上霜降牛</td><td>4.8（4964）</td></tr>
<tr><td>XM 麻辣火鍋</td><td>麻辣海鮮吃到飽</td><td>吃到飽</td><td>海鮮無限吃</td><td>4.6（5673）</td></tr>
<tr><td>二鍋（鬥牛士）</td><td>壽喜燒/涮涮鍋吃到飽</td><td>平價吃到飽</td><td>分價位肉品</td><td>4.2（5478）</td></tr>
<tr><td>鍋醬</td><td>平價個人小火鍋</td><td>平價</td><td>多口味＋送冰淇淋</td><td>4.8（2503）</td></tr>
<tr><td>貍偷聚門鍋物</td><td>鮮奶鍋・老火煨湯</td><td>中價</td><td>玫瑰鮮奶鍋</td><td>4.8（787）</td></tr>
<tr><td>如潮沙茶爐</td><td>台式沙茶爐</td><td>平價</td><td>沙茶爐涮煮</td><td>在地口碑</td></tr>
</tbody>
</table>

<h2 id="s1">1. 牧鍋 MooPot｜東區裕農路　頂級熟成牛鍋・自助吧吃到飽</h2>
<p>想吃好一點的牛肉鍋，牧鍋是台南聚餐熱門選擇。主打頂級熟成牛、極上霜降，肉品單點、菜類火鍋料與飲料冰淇淋走自助吧吃到飽，空間寬敞、出餐快，還能免費換一顆干貝。價位偏中高，適合想吃好牛、家庭或朋友聚餐。</p>
<div class="si"><b>地址</b>：台南市東區裕農路 933 號　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/063023888">看牧鍋 MooPot 店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.8（4964 則評論）</div><blockquote>「空間寬敞，飲料吧、冰淇淋吧、蔬菜火鍋料區都非常豐富，極上霜降牛的肉質好吃，肥嫩適中，份量充足。芋泥丸推薦！」</blockquote><cite>— Google 評論・Sid</cite></div>

<h2 id="s2">2. XM 麻辣火鍋｜永康中華路　麻辣海鮮吃到飽</h2>
<p>永康經營超過 16 年的人氣麻辣火鍋吃到飽，主打高 CP 值、上百種食材——Prime 級霜降牛、櫻桃鴨、海鮮（蝦、蛤、蚵）通通無限吃，湯頭有麻辣、麻奶、藥膳可選，還有冰淇淋與飲料吧。是永康、台南在地人聚餐的熱門選擇，壽星另有優惠。</p>
<div class="si"><b>地址</b>：台南市永康區中華路 660 號　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/spicyhotpotallyoucaneat">看 XM 麻辣火鍋店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.6（5673 則評論）</div><blockquote>「CP 值十分高……花少少的錢就能實現蛤蜊、蚵仔自由，海鮮種類十分豐富，個人覺得比現切肉品好吃，蔬菜吧種類也蠻多的，整體而言是相當物超所值的火鍋店。」</blockquote><cite>— Google 評論・吳孟特</cite></div>

<h2 id="s3">3. 二鍋（鬥牛士）｜中西區中山路　壽喜燒/涮涮鍋吃到飽</h2>
<p>鬥牛士集團旗下的個人涮涮鍋、壽喜燒自助吃到飽，位在中西路中山路的百貨樓層。分價位點餐——不同價位提供不同等級肉品，最高價位還用機器人送餐，以吃到飽來說走平價路線，肉片厚度與品質不錯，飲料吧的紅茶系列很受好評。適合想平價吃到飽、逛街順路的人。</p>
<div class="si"><b>地址</b>：台南市中西區中山路 166 號 8 樓　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/062263168">看二鍋店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.2（5478 則評論）</div><blockquote>「這次來吃這間火鍋吃到飽真的很滿意，肉品選擇很多，湯頭還不錯，吃得很過癮……環境乾淨，座位舒適。另外一定要推薦他們的飲料，微糖紅茶喝起來茶香很順，不會太甜超解膩。」</blockquote><cite>— Google 評論・Angela</cite></div>

<h2 id="s4">4. 鍋醬平價小火鍋｜南區夏林路　平價個人鍋・多口味</h2>
<p>南區夏林路的平價個人小火鍋，銅板價卻不馬虎——麻辣鴨血鍋、壽喜燒、酸菜魚、沙茶鍋多種口味可選，湯頭真材實料，主餐還附一球冰淇淋，不吃火鍋料可換菜類。環境整潔、老闆好相處，是附近居民常回訪的平價之選。</p>
<div class="si"><b>地址</b>：台南市南區夏林路 1-5 號　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/hotpotjohn">看鍋醬平價小火鍋店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.8（2503 則評論）</div><blockquote>「依現在的物價來看，CP 值頗高呢！難得的是湯頭都是美味的——蒜味蛤蜊＋單點牛肉一盤！店家還隨主餐各送一球冰淇淋，用餐環境整潔，下次還會再回訪。」</blockquote><cite>— Google 評論・整天閒閒沒事做</cite></div>

<h2 id="s5">5. 貍偷聚門鍋物｜中西區海安路　鮮奶鍋・老火煨湯</h2>
<p>海安路的鍋物店，主打老火煨湯與特色鮮奶鍋——蒜頭牛奶鍋、玫瑰鮮奶鍋濃郁香甜，有機玫瑰花量給得足，湯底選擇多、加湯免費續。店內環境好拍、氣氛佳，還是寵物友善店家，適合朋友聚餐、想喝特色湯底的人。</p>
<div class="si"><b>地址</b>：台南市中西區海安路二段 90 號　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/hotpot4">看貍偷聚門鍋物店家頁</a></div>
<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.8（787 則評論）</div><blockquote>「玫瑰鮮奶鍋剛上鍋就香氣撲鼻而來，有機玫瑰花量給的很足，口味也很合牛奶鍋愛好者的喜好。價格適中，且湯底選擇也很多，是愛吃火鍋的人去處好選擇！」</blockquote><cite>— Google 評論・Clara Lin</cite></div>

<h2 id="s6">6. 如潮沙茶爐火鍋｜東區仁和路　台式沙茶爐</h2>
<p>台南東區仁和路的沙茶爐火鍋，走傳統台式沙茶爐路線——自己涮煮肉片與食材、沾特調沙茶醬，是很多台南人記憶中的老派吃鍋方式。想念沙茶爐涮煮、找銅板價在地小火鍋的人可以試試。</p>
<div class="si"><b>地址</b>：台南市東區仁和路 123 號　｜　<b>電話</b>：<a href="tel:062680200">06-268-0200</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/hotpot">看如潮沙茶爐店家頁</a></div>

<div class="gg-tip"><b>💡 台南火鍋怎麼挑</b><p>想大口吃到飽找 <strong>XM（麻辣海鮮）</strong>、<strong>二鍋（壽喜燒/涮涮鍋）</strong>；想吃好一點的牛找 <strong>牧鍋</strong>；預算有限、一人一鍋找 <strong>鍋醬</strong>、<strong>如潮沙茶爐</strong>；想喝特色湯底找 <strong>貍偷聚門（鮮奶鍋）</strong>。人氣店假日與尖峰時段建議先訂位。</p></div>

<h2 id="faq">常見問題</h2>
<div class="gg-faq">
<div class="gg-q">台南火鍋吃到飽推薦哪幾家？</div>
<div class="gg-a">想吃到飽可看 XM 麻辣火鍋（永康、海鮮無限吃）、二鍋（鬥牛士，壽喜燒/涮涮鍋、分價位）；想連頂級牛與自助吧一起，牧鍋 MooPot 走肉品單點＋自助吧吃到飽。</div>
<div class="gg-q">台南想吃好一點的牛肉鍋去哪？</div>
<div class="gg-a">牧鍋 MooPot 主打頂級熟成牛、極上霜降，自助吧（飲料、冰淇淋、蔬菜火鍋料）豐富，價位偏中高，適合聚餐想吃好牛。</div>
<div class="gg-q">台南平價個人小火鍋推薦？</div>
<div class="gg-a">鍋醬平價小火鍋（南區夏林路）平價、口味多、主餐送冰淇淋；如潮沙茶爐（東區仁和路）走傳統台式沙茶爐路線，都是銅板價的在地選擇。</div>
<div class="gg-q">台南有特色湯底的火鍋嗎？</div>
<div class="gg-a">貍偷聚門鍋物（中西區海安路）主打鮮奶鍋——蒜頭牛奶鍋、玫瑰鮮奶鍋，老火煨湯、湯底選擇多，還是寵物友善店家。</div>
<div class="gg-q">台南火鍋需要訂位嗎？</div>
<div class="gg-a">人氣店（牧鍋、XM、二鍋）假日與用餐尖峰常客滿，建議先訂位；平價個人鍋多為現場候位。</div>
<div class="gg-q">吃到飽火鍋有生日優惠嗎？</div>
<div class="gg-a">部分吃到飽店（如 XM 麻辣火鍋）有壽星優惠，實際方案與金額以各店現場公告為準。</div>
</div>

<p>想看更多台南在地火鍋／鍋物商家，可到 <a href="https://www.gomag.com.tw/city/tainan/food/hotpot"><strong>台南火鍋</strong></a> 專頁；也可延伸看 <a href="https://www.gomag.com.tw/city/tainan/food/yakiniku">台南燒肉</a>、<a href="https://www.gomag.com.tw/city/tainan/food">台南美食</a> 樞紐頁。</p>
HTML;

$faqs=[
 ['台南火鍋吃到飽推薦哪幾家？','想吃到飽可看 XM 麻辣火鍋（永康、海鮮無限吃）、二鍋（鬥牛士，壽喜燒/涮涮鍋、分價位）；想連頂級牛與自助吧一起，牧鍋 MooPot 走肉品單點加自助吧吃到飽。'],
 ['台南想吃好一點的牛肉鍋去哪？','牧鍋 MooPot 主打頂級熟成牛、極上霜降，自助吧（飲料、冰淇淋、蔬菜火鍋料）豐富，價位偏中高，適合聚餐想吃好牛。'],
 ['台南平價個人小火鍋推薦？','鍋醬平價小火鍋（南區夏林路）平價、口味多、主餐送冰淇淋；如潮沙茶爐（東區仁和路）走傳統台式沙茶爐路線，都是銅板價的在地選擇。'],
 ['台南有特色湯底的火鍋嗎？','貍偷聚門鍋物（中西區海安路）主打鮮奶鍋——蒜頭牛奶鍋、玫瑰鮮奶鍋，老火煨湯、湯底選擇多，還是寵物友善店家。'],
 ['台南火鍋需要訂位嗎？','人氣店（牧鍋、XM、二鍋）假日與用餐尖峰常客滿，建議先訂位；平價個人鍋多為現場候位。'],
 ['吃到飽火鍋有生日優惠嗎？','部分吃到飽店（如 XM 麻辣火鍋）有壽星優惠，實際方案與金額以各店現場公告為準。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";

$sql="INSERT INTO guides (slug,title,excerpt,body_html,city_slug,category_id,service_slug,meta_title,meta_desc,cover_image,status,published_at)
  VALUES (?,?,?,?, 'tainan', 1, 'hotpot', ?, ?, ?, 'published', NOW())
  ON DUPLICATE KEY UPDATE title=VALUES(title),excerpt=VALUES(excerpt),body_html=VALUES(body_html),
    category_id=1,service_slug='hotpot',meta_title=VALUES(meta_title),meta_desc=VALUES(meta_desc),
    cover_image=VALUES(cover_image),status='published'";
$db->prepare($sql)->execute([$slug,$title,$excerpt,$body,$metaTitle,$metaDesc,$cover]);
$r=$db->query("SELECT id,LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo "✅ #{$r['id']} {$slug}\n   body={$r['bl']}B 純文字約=".mb_strlen(strip_tags($body))."字 評價區=".substr_count($body,'<div class="gr">')." 店連結=".substr_count($body,'/store/')." FAQ=".count($faqs)."\n";
echo "驗證： https://www.gomag.com.tw/guide/{$slug}\n";
