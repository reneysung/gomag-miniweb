<?php
// ============================================================
// Migration 048 — 屏東 / 台東 補缺口：清潔公司交叉頁 + 在地攻略
// ------------------------------------------------------------
// 這兩城原本無交叉頁也無攻略。各開 1 個「清潔公司」(cleaning) 子服務內容頁
// （0 店、內容撐排名，sanfeng 模式）+ 1 篇城市差異化攻略，攻略 CTA 連該城清潔頁。
// 城市角度刻意差異化避 doorway：屏東＝透天厝/農業縣/老宅；台東＝民宿/移居/慢活。
// 行情用「非都會」較低範圍 + 強調依坪數/屋況、偏遠鄉鎮車馬費（數字待 Reney 確認）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/048_pingtung_taitung_cleaning.php
// 冪等：geo upsert（unique city+cat+svc）、guide slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$h = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

// ---------- 交叉頁內容 ----------
$ptIntro = <<<'HTML'
<p>屏東以屏東市為生活中心，潮州、東港、內埔、長治一帶人口集中，透天厝與自地自建住宅比例高，加上農業縣腹地大，清潔需求從透天厝定期打掃、搬家退租到老宅與三合院大掃除都有。</p>
<p>屏東常見的清潔需求：</p>
<ul>
<li><strong>透天厝定期清潔</strong>：屏東透天多、樓層高，定期到府打掃樓梯、廚衛與大面積地板很實際，依坪數樓層估價。</li>
<li><strong>搬家／退租大掃除</strong>：屏東市租屋、學區套房退租的一次性深度清潔。</li>
<li><strong>老宅／三合院大掃除</strong>：過年、祭祖前的大清，農村老宅常見。</li>
<li><strong>裝潢後細清</strong>：屏東市與竹田、長治新建案交屋後的粉塵、殘膠清理。</li>
</ul>
<p>屏東腹地大、部分鄉鎮較偏遠，建議先確認業者服務範圍與是否收車馬費，並到府或視訊估價、白紙黑字列出含哪些項目，再決定。</p>
<p>歡迎屏東在地優質清潔業者上架，讓更多屏東住戶找到。</p>
HTML;

$ttIntro = <<<'HTML'
<p>台東以台東市為中心，成功、關山、池上、鹿野一帶聚落分散，加上民宿、移居與退休族多，清潔需求很有地方特色——除了一般居家定期、搬家退租，還有民宿換房清潔、移居老宅整理與海景宅空屋清潔。</p>
<p>台東常見的清潔需求：</p>
<ul>
<li><strong>民宿／包棟換房清潔</strong>：旅遊旺季的退房清潔與布巾整理，台東民宿密集特別需要。</li>
<li><strong>移居族老宅整理</strong>：移居台東買老屋、整理閒置老宅，入住前的深度清潔。</li>
<li><strong>定期居家與搬家退租</strong>：台東市與學區套房的日常與退租清潔。</li>
<li><strong>裝潢後細清</strong>：移居族老屋翻新或新建透天交屋後的粉塵、殘膠清理。</li>
</ul>
<p>台東幅員廣、清潔師傅相對少，旺季與偏遠鄉鎮建議提前預約、先確認服務範圍與車馬費，並到府或視訊估價。</p>
<p>歡迎台東在地優質清潔業者上架，讓更多台東住戶與民宿找到。</p>
HTML;

$ptFaqs = [
  ['q'=>'屏東居家清潔一次多少錢？','a'=>'定期清潔每次約 NT$1,800–3,000、鐘點約每小時 NT$400–600（非都會行情較低），依坪數、樓層與屋況；透天樓層多會反映工時。'],
  ['q'=>'屏東哪些區有到府清潔？','a'=>'屏東市、潮州、東港、內埔、長治一帶最密集；較偏遠鄉鎮建議先確認服務範圍與車馬費。'],
  ['q'=>'透天厝清潔怎麼算？','a'=>'多依坪數與樓層數估，樓梯、外牆、頂樓加蓋等是否含要先問清楚。'],
  ['q'=>'搬家退租大掃除多少錢？','a'=>'一次性深度清潔依坪數與屋況估；退租建議對照租約點交標準，請業者列含／不含清單。'],
  ['q'=>'老宅、三合院大掃除也接嗎？','a'=>'多數接，年前與祭祖旺季建議提前預約；陳年油污、神明廳清潔可先說明需求。'],
  ['q'=>'怎麼避免事後追加費用？','a'=>'到府或視訊估價，白紙黑字列含／不含項目（外窗、垃圾清運、冷氣、車馬費）再下訂。'],
];
$ttFaqs = [
  ['q'=>'台東居家清潔一次多少錢？','a'=>'定期清潔每次約 NT$1,800–3,200、鐘點約每小時 NT$400–650，偏遠地區或含車馬費會略高，依坪數與屋況。'],
  ['q'=>'台東民宿換房清潔怎麼算？','a'=>'多以間數或坪數計，旺季需求大建議與業者談固定配合，確認布巾、備品整理是否含。'],
  ['q'=>'移居族整理老宅清潔要注意什麼？','a'=>'閒置老宅常有灰塵、霉味與蟲害，建議先到府勘估，安排深度清潔與除霉。'],
  ['q'=>'台東哪些區有到府清潔？','a'=>'台東市、卑南最密集，成功、關山、池上等較分散，偏遠地區先確認服務範圍與車馬費。'],
  ['q'=>'搬家退租清潔怎麼算？','a'=>'依坪數與屋況估；退租對照租約點交標準，請業者先列含／不含清單。'],
  ['q'=>'台東清潔要提前多久預約？','a'=>'師傅相對少、旅遊旺季民宿需求集中，建議提前預約，偏遠鄉鎮更早。'],
];

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, ?, 'cleaning', '清潔公司', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name=VALUES(service_name), intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");
$up->execute(['pingtung', $h, $ptIntro, json_encode($ptFaqs, JSON_UNESCAPED_UNICODE),
  '屏東清潔公司推薦｜透天厝定期清潔・搬家退租・老宅大掃除',
  '屏東清潔公司怎麼選？透天厝定期清潔、搬家退租大掃除、老宅與裝潢後細清的行情與挑選重點，加上店家好口碑收錄的屏東在地清潔商家。']);
$up->execute(['taitung', $h, $ttIntro, json_encode($ttFaqs, JSON_UNESCAPED_UNICODE),
  '台東清潔公司推薦｜居家定期・民宿換房・移居老宅整理',
  '台東清潔公司怎麼選？居家定期清潔、民宿換房清潔、移居老宅整理與搬家退租的行情與挑選重點，加上店家好口碑收錄的台東在地清潔商家。']);
echo "PAGE：屏東清潔公司 + 台東清潔公司\n";

// ---------- 攻略 ----------
$guides = [];
$guides[] = [
  'slug'=>'pingtung-townhouse-cleaning-guide', 'city'=>'pingtung', 'cat'=>$h,
  'title'=>'屏東透天厝清潔指南：定期打掃、搬家退租、老宅大掃除怎麼挑',
  'excerpt'=>'屏東透天厝多、農業縣腹地大，定期清潔、搬家退租與老宅大掃除怎麼準備、行情怎麼抓、偏遠鄉鎮要注意什麼，一篇看懂。',
  'meta_title'=>'屏東透天厝清潔指南｜定期打掃搬家退租老宅大掃除｜店家好口碑',
  'meta_desc'=>'屏東透天厝定期清潔、搬家退租、老宅與三合院大掃除怎麼準備、行情與挑選重點，農業縣腹地大、偏遠鄉鎮車馬費要先確認，屏東住戶必看。',
  'body'=> <<<'HTML'
<p>屏東以屏東市為生活中心，潮州、東港、內埔、長治人口集中，透天厝與自地自建比例高、農業縣腹地大，清潔需求跟都會大樓很不一樣——以透天厝定期打掃、搬家退租、老宅與三合院大掃除為主。這篇說清楚屏東清潔怎麼準備。</p>
<h3>透天厝清潔：屏東最常見的需求</h3>
<ul>
<li>透天樓層多，<strong>樓梯、扶手、大面積地板與頂樓加蓋</strong>是工時重點，報價多依坪數與樓層數。</li>
<li>外牆、騎樓、庭院是否含要先問清楚，避免認知落差。</li>
</ul>
<h3>搬家退租與老宅大掃除</h3>
<ul>
<li>屏東市租屋、學區套房退租，建議對照租約點交標準，請業者先列含／不含清單。</li>
<li>農村老宅、三合院過年與祭祖前的大清，陳年油污、神明廳可先說明需求。</li>
</ul>
<h3>屏東在地要注意：腹地大、車馬費</h3>
<p>屏東幅員廣、部分鄉鎮較偏遠，<strong>先確認業者服務範圍與是否收車馬費</strong>很重要。行情屬非都會、通常較都會低，但偏遠地區可能含車馬費，實際以坪數、屋況與到府估價為準。</p>
<h3>挑屏東清潔公司重點</h3>
<p>看報價方式（坪數／樓層／鐘點、含不含外窗與垃圾清運）、是否到府或視訊估價、在地口碑與案例，並把含哪些項目白紙黑字寫清楚。想找屏東在地清潔商家，可參考本站屏東清潔頁。</p>
HTML,
];
$guides[] = [
  'slug'=>'taitung-homestay-relocation-cleaning-guide', 'city'=>'taitung', 'cat'=>$h,
  'title'=>'台東民宿與移居族清潔指南：換房清潔、老宅整理、空屋細清',
  'excerpt'=>'台東民宿密集、移居退休族多，民宿換房清潔、移居老宅整理與空屋細清怎麼找、行情與在地注意事項，一篇看懂。',
  'meta_title'=>'台東民宿與移居族清潔指南｜換房清潔老宅整理空屋細清｜店家好口碑',
  'meta_desc'=>'台東民宿換房清潔、移居族老宅整理、空屋細清與搬家退租怎麼準備、行情與在地注意事項，師傅少、旺季偏遠要提前預約，台東住戶與民宿必看。',
  'body'=> <<<'HTML'
<p>台東以台東市為中心，成功、關山、池上、鹿野聚落分散，民宿、移居與退休族多，清潔需求很有地方特色——除了一般居家定期與搬家退租，更有民宿換房清潔、移居老宅整理與海景宅空屋清潔。這篇說清楚台東清潔怎麼準備。</p>
<h3>民宿／包棟換房清潔</h3>
<ul>
<li>旅遊旺季的退房清潔、布巾與備品整理，台東民宿密集特別需要，建議談<strong>固定配合</strong>較穩定。</li>
<li>確認換房清潔含哪些（布巾、備品、浴廁、公共區）避免旺季手忙腳亂。</li>
</ul>
<h3>移居族老宅整理與空屋細清</h3>
<ul>
<li>移居台東買老屋、整理閒置老宅，常有<strong>灰塵、霉味與蟲害</strong>，建議先到府勘估，安排深度清潔與除霉。</li>
<li>新建透天或老屋翻新交屋後的粉塵、殘膠細清，入住前一次處理。</li>
</ul>
<h3>台東在地要注意：師傅少、要早約</h3>
<p>台東幅員廣、清潔師傅相對少，旺季與偏遠鄉鎮<strong>務必提前預約</strong>，並先確認服務範圍與車馬費。行情依坪數、屋況與地點，偏遠或含車馬費會略高，以到府估價為準。</p>
<h3>挑台東清潔重點</h3>
<p>看報價方式與含哪些項目、是否到府或視訊估價、在地口碑與案例（民宿可看換房合作經驗）。想找台東在地清潔商家，可參考本站台東清潔頁。</p>
HTML,
];

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :city, :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug=VALUES(city_slug), category_id=VALUES(category_id), meta_title=VALUES(meta_title),
            meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
foreach ($guides as $g) {
    $stmt->execute([':slug'=>$g['slug'], ':title'=>$g['title'], ':excerpt'=>$g['excerpt'], ':body'=>$g['body'],
        ':city'=>$g['city'], ':cat'=>$g['cat'], ':mt'=>$g['meta_title'], ':md'=>$g['meta_desc']]);
    printf("GUIDE：%-42s body %d 字\n", $g['slug'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
