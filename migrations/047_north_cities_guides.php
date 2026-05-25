<?php
// ============================================================
// Migration 047 — 北部 4 城在地主題攻略（補缺口：有清潔/裝潢細清子服務頁卻 0 攻略）
// ------------------------------------------------------------
// 台北/新北/桃園/新竹 各 1 篇城市差異化主題文（純文字、不列店家 → 免客戶置入、無圖）。
// city_slug + category=home-service(2)，互鏈到該城清潔/裝潢細清子服務頁。
// 各城獨有角度（台北老公寓退租 / 新北重劃區交屋 / 桃園青埔移居 / 新竹竹科竹北）避 doorway。
// 行情只用範圍性敘述、不寫死數字（守內容紀律；數字待 Reney 人工確認再補）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/047_north_cities_guides.php
// 冪等：以 slug upsert（保留首次 published_at）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$guides = [];

$guides[] = [
  'slug'=>'taipei-move-out-fine-clean-guide', 'city'=>'taipei', 'cat'=>$homeId,
  'title'=>'台北退租清潔與裝潢細清指南：老公寓、套房、電梯大樓怎麼挑',
  'excerpt'=>'台北租屋族多、老公寓與小坪數套房密集，退租清潔與裝潢後細清怎麼準備、押金不被扣、行情怎麼抓，一篇看懂。',
  'meta_title'=>'台北退租清潔與裝潢細清指南｜老公寓套房怎麼挑｜店家好口碑',
  'meta_desc'=>'台北退租清潔、裝潢後細清怎麼準備？老公寓、小坪數套房、電梯大樓的清潔重點、行情與挑選技巧，台北租屋族與新居族必看。',
  'body'=> <<<'HTML'
<p>台北租屋人口多、老公寓與小坪數套房密集，加上電梯大樓管理嚴格，「退租清潔」與「裝潢後細清」是台北人最常遇到的兩種清潔需求。這篇把台北的清潔眉角一次說清楚。</p>
<h3>退租清潔：押金能不能全拿回的關鍵</h3>
<ul>
<li>租約常要求「回復原狀」，重點在<strong>廚衛水垢、油污、牆面與地板</strong>，這些最容易被房東扣押金。</li>
<li>老公寓常見<strong>窗框灰塵、紗窗、磁磚縫</strong>卡髒，退租前細清一次比較保險。</li>
<li>套房／雅房坪數小但「麻雀雖小」，冰箱、冷氣、衛浴都要顧到。</li>
</ul>
<h3>裝潢後細清：台北老屋翻新與新成屋都需要</h3>
<p>台北老屋翻新比例高，裝潢完的<strong>粉塵、矽利康殘膠、油漆點</strong>得靠專業細清；電梯大樓新成屋交屋前也要全室細清才入住。系統櫃內部、踢腳板、窗軌、玻璃水漬是常被忽略的角落。</p>
<h3>台北清潔行情怎麼抓</h3>
<p>退租／一般居家清潔常以<strong>坪數或時數</strong>計；裝潢後細清因粉塵重、工序多，單價較高。台北老公寓無電梯、搬運動線受限，報價也會反映工時。實際依坪數、屋況與樓層落差大，建議先到府或視訊估價、把含哪些項目白紙黑字寫清楚。</p>
<h3>挑台北清潔公司 4 重點</h3>
<ul>
<li>報價透明、含哪些項目寫清楚。</li>
<li>有電梯大樓／老公寓的實際案例。</li>
<li>人手與設備（工業吸塵、除塵）足夠。</li>
<li>是否含驗收複清。</li>
</ul>
<p>想找台北在地清潔／裝潢細清商家，可參考本站台北清潔頁。</p>
HTML,
];

$guides[] = [
  'slug'=>'newtaipei-handover-fine-clean-guide', 'city'=>'newtaipei', 'cat'=>$homeId,
  'title'=>'新北新成屋交屋細清指南：林口、三重、板橋重劃區必看',
  'excerpt'=>'新北重劃區新成屋交屋潮，交屋前裝潢細清怎麼準備、檢查哪些、行情怎麼抓，林口、板橋、三重、新莊、淡水新居族一篇搞定。',
  'meta_title'=>'新北新成屋交屋細清指南｜林口板橋重劃區必看｜店家好口碑',
  'meta_desc'=>'新北重劃區新成屋交屋前裝潢細清怎麼準備、清潔重點、行情與挑選技巧，林口、板橋、三重、新莊、淡水新居族必看。',
  'body'=> <<<'HTML'
<p>新北重劃區建案多——林口、三重、板橋江翠、新莊副都心、淡水新市鎮交屋潮持續。新成屋交屋前的「裝潢後細清」做得好，入住才舒服。這篇說清楚新北交屋細清怎麼準備。</p>
<h3>新成屋為什麼一定要細清</h3>
<ul>
<li>建商完工會留下<strong>水泥粉塵、矽利康殘膠、保護貼殘膠、油漆點</strong>，肉眼看似乾淨，其實縫隙都是灰。</li>
<li>系統櫃內部、踢腳板、窗軌、廚衛矽利康縫是細清重點。</li>
</ul>
<h3>新北重劃區的在地眉角</h3>
<ul>
<li>大樓新案多，<strong>搬運與電梯預約、清潔廢料清運</strong>要先確認。</li>
<li>近水區域（淡水、八里）濕氣重，浴廁防霉一起處理較省事。</li>
</ul>
<h3>交屋細清行情</h3>
<p>裝潢後細清多以<strong>坪數×單價</strong>或包項計，因粉塵重、工序多，單價高於一般居家清潔，實際依坪數與屋況。建議現場估價、把含哪些寫清楚，並可搭配交屋驗屋一起安排，省一次到府。</p>
<h3>挑細清團隊重點</h3>
<p>看是否有重劃區新成屋案例、報價透明、設備（工業吸塵、除塵蟎）齊全、是否含驗收複清。想找新北在地裝潢細清商家，可參考本站新北清潔頁。</p>
HTML,
];

$guides[] = [
  'slug'=>'taoyuan-new-house-fine-clean-guide', 'city'=>'taoyuan', 'cat'=>$homeId,
  'title'=>'桃園新成屋裝潢細清指南：青埔、藝文特區、中壢移居族必看',
  'excerpt'=>'桃園青埔高鐵特區、藝文特區、中壢新成屋多，移居族交屋裝潢細清怎麼準備、行情怎麼抓，一篇看懂。',
  'meta_title'=>'桃園新成屋裝潢細清指南｜青埔藝文特區移居族必看｜店家好口碑',
  'meta_desc'=>'桃園青埔、藝文特區、中壢新成屋交屋前裝潢細清怎麼準備、清潔重點與行情，桃園移居族與新居族必看。',
  'body'=> <<<'HTML'
<p>桃園是雙北移居族首選，青埔高鐵特區、中路與藝文特區、中壢與龜山林口交界建案密集。新成屋交屋前的裝潢細清，是入住前的最後一哩。這篇說清楚桃園新成屋細清怎麼準備。</p>
<h3>新成屋細清重點</h3>
<ul>
<li>清除<strong>裝潢粉塵、殘膠、油漆與水泥點</strong>，系統櫃內部、窗軌、踢腳板深層除塵。</li>
<li>玻璃、衛浴磁磚與矽利康縫的水漬與殘料一起處理。</li>
</ul>
<h3>桃園移居族的在地考量</h3>
<ul>
<li>青埔等重劃區<strong>大坪數透天與電梯大樓</strong>都有，坪數大、細清工時長，務必先估價。</li>
<li>不少屋主在雙北上班，<strong>遠端溝通與假日施作</strong>需求高，挑配合度高的團隊。</li>
</ul>
<h3>行情與挑選</h3>
<p>裝潢後細清以坪數×單價或包項計，依坪數與屋況落差大。挑選看在地新成屋案例、報價透明、設備齊全、是否含驗收複清。想找桃園在地裝潢細清商家，可參考本站桃園清潔頁。</p>
HTML,
];

$guides[] = [
  'slug'=>'hsinchu-tech-fine-clean-guide', 'city'=>'hsinchu', 'cat'=>$homeId,
  'title'=>'新竹竹科族裝潢細清與定期清潔指南：竹北重劃區新成屋必看',
  'excerpt'=>'新竹竹科科技人、竹北重劃區新成屋多，裝潢細清與定期居家清潔怎麼挑、行情怎麼抓，雙薪忙碌族一篇看懂。',
  'meta_title'=>'新竹竹科裝潢細清與定期清潔指南｜竹北重劃區必看｜店家好口碑',
  'meta_desc'=>'新竹竹科族、竹北重劃區新成屋裝潢細清與定期居家清潔怎麼準備、清潔重點與行情，新竹雙薪忙碌家庭必看。',
  'body'=> <<<'HTML'
<p>新竹因竹科聚集大量科技人，竹北高鐵特區、關埔重劃區新成屋一棟接一棟，加上雙薪家庭忙碌，「裝潢後細清」與「定期居家清潔」需求都很高。這篇一次說清楚。</p>
<h3>新成屋裝潢細清</h3>
<ul>
<li>交屋前清除<strong>粉塵、殘膠、油漆與水泥</strong>，系統櫃內部、窗軌、踢腳板、矽利康縫深清。</li>
<li>竹北大樓新案多，搬運與電梯預約、廢料清運先確認。</li>
</ul>
<h3>定期居家清潔：忙碌雙薪的解方</h3>
<p>竹科雙薪家庭常週末也想休息，把廚衛、地板、除塵交給專業的<strong>定期清潔（週／雙週／月）</strong>，CP 值高。固定窗口、固定人員，品質較穩定。</p>
<h3>行情與挑選</h3>
<p>裝潢後細清以坪數計、單價較高；定期居家以坪數或時數計。挑選看在地竹北新成屋案例、報價透明、人員固定與培訓、是否含驗收複清。想找新竹在地裝潢細清／清潔商家，可參考本站新竹清潔頁。</p>
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
    printf("UPSERT %-36s body %4d 字\n", $g['slug'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
