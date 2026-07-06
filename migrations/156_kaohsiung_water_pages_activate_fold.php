<?php
// ============================================================
// Migration 156 — 高雄淨水器/軟水子服務頁 啟用+加厚 + 折碎關鍵字
// ------------------------------------------------------------
// 配合 3 篇淨水器攻略上線：把高雄淨水器(#108)、軟水(#109) geo 頁從薄草稿(507/474B)
// 加厚成高雄在地內容並啟用；把碎掉的關鍵字（全戶淨水/RO純水機/廚下淨水器）折進
// 「淨水器」主頁避 doorway，軟水維持獨立。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/156_kaohsiung_water_pages_activate_fold.php
// 冪等：UPDATE / page_slug set。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 1. 折碎關鍵字：全戶淨水/RO純水機/廚下淨水器 → 折進 jingshuiqi（軟水維持獨立）──
echo "═══ 1. 折關鍵字 → jingshuiqi ═══\n";
$db->exec("UPDATE service_keywords SET page_slug='jingshuiqi' WHERE slug IN ('quanhujingshui','ro-chunshuiji','chuxiajingshuiqi')");
foreach($db->query("SELECT slug,name,page_slug FROM service_keywords WHERE slug IN ('jingshuiqi','ruanshui','quanhujingshui','ro-chunshuiji','chuxiajingshuiqi') ORDER BY id") as $r)
  echo "  {$r['slug']} ({$r['name']}) page_slug=[{$r['page_slug']}]\n";

// ── 2. 高雄淨水器 geo #108 加厚+啟用 ──
echo "\n═══ 2. 高雄淨水器 geo #108 ═══\n";
$intro108 = <<<'H'
<p>高雄自來水主要來自高屏溪與澄清湖系統，水質偏硬、含餘氯，雨季原水濁度變化大，加上部分老社區管線與屋頂水塔久未清洗，實際到水龍頭的水常比想像中需要處理。依用途，淨水大致分成幾種：</p>
<ul>
<li><strong>廚下 RO 純水機</strong>：逆滲透除雜質、重金屬、細菌，做飲用與煮飯的水。</li>
<li><strong>全戶淨水</strong>：裝在進水處，除氯、泥沙、雜質，顧全家用水。</li>
<li><strong>全戶軟水／瑞士水抑垢</strong>：對付鈣鎂造成的水垢（軟水靠加鹽軟化、瑞士水走免鹽抑垢）。</li>
<li><strong>前置過濾（大胖）</strong>：10 吋、20 吋前置濾除泥沙餘氯，常與上述搭配。</li>
</ul>
<p>挑高雄淨水器三個重點：①<strong>濾心是不是「國際通規」</strong>（10"/20"，避免被綁機賣濾心、之後好買好換）；②<strong>認證</strong>（NSF、SGS、WQA）；③<strong>屋型與售後</strong>（透天走全戶、大樓多廚下，換濾心與保養方不方便）。飲用、用水、除垢分開規劃，別買錯類型。</p>
H;
$faq108 = json_encode([
 ['q'=>'高雄淨水器要裝哪一種？','a'=>'看用途：喝的水裝廚下 RO；全家用水除氯雜質裝全戶淨水；對付水垢裝全戶軟水或瑞士水抑垢。常見是廚下 RO ＋前置過濾，需要再加全戶淨軟水。'],
 ['q'=>'高雄的水需要裝淨水器嗎？','a'=>'高雄水偏硬、有餘氯，雨季濁度變化大。要不要裝看需求，但飲用加裝 RO、用水加前置或全戶淨水，是不少高雄家庭的作法。'],
 ['q'=>'淨水器最怕什麼？','a'=>'「綁機賣濾心」——特殊規格濾心只能跟原廠買、被綁一輩子。選國際通用規格（10"/20"）濾心的機型，換濾心選擇多、成本好控制。'],
 ['q'=>'RO、淨水、軟水差在哪？','a'=>'RO 除雜質做飲用；淨水除氯雜質顧用水；軟水/抑垢對付水垢。處理的東西不同，通常搭配使用。'],
 ['q'=>'透天和大樓裝法一樣嗎？','a'=>'不一樣。透天多走全戶（水塔端）；大樓空間小，常以廚下 RO 加窄身或免鹽桶的機型為主。'],
 ['q'=>'濾心多久換？','a'=>'看水質、用量與濾材種類；通用規格濾心的好處是到期換的選擇多、成本好抓，依機型說明定期更換。'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$db->prepare("UPDATE geo_category_pages SET intro_html=?, faqs=?, is_active=1 WHERE id=108")->execute([$intro108,$faq108]);
$r=$db->query("SELECT LENGTH(intro_html) il,LENGTH(faqs) fl,is_active FROM geo_category_pages WHERE id=108")->fetch(PDO::FETCH_ASSOC);
echo "  #108 intro={$r['il']}B faqs={$r['fl']}B active={$r['is_active']}\n";

// ── 3. 高雄軟水 geo #109 加厚+啟用 ──
echo "\n═══ 3. 高雄軟水 geo #109 ═══\n";
$intro109 = <<<'H'
<p>高雄自來水硬度普遍比北部高一些，鈣鎂多，長期在玻璃、磁磚、熱水器、管線與蓮蓬頭上結成白垢，也影響家電效率。想對付水垢，高雄常見兩種作法：</p>
<ul>
<li><strong>傳統離子交換軟水</strong>：用樹脂把鈣鎂換掉、真正軟化，需要定期加再生鹽、也會產生再生廢水，機身含鹽桶較大。</li>
<li><strong>瑞士水抑垢系統</strong>：不軟化，改變鈣鎂結晶讓它不易結垢，免加鹽、免耗材、免插電、體積小，大樓也好裝。</li>
</ul>
<p>挑選重點：①<strong>加鹽還是免鹽</strong>（耗鹽、再生廢水要不要在意）；②<strong>屋型與位置</strong>（高雄透天多在頂樓水塔端、大樓走進水處或局部）；③<strong>耗材與售後</strong>（樹脂/濾料更換、到府保養）。提醒：軟水與抑垢處理的是「用水」，飲用水仍建議另接 RO 或淨水。</p>
H;
$faq109 = json_encode([
 ['q'=>'高雄的水很硬嗎？要裝軟水嗎？','a'=>'高雄水硬度普遍比北部高，水垢、白垢、家電結垢明顯。在意水垢與家電壽命的家庭多半有感，要不要裝可先做水質檢測。'],
 ['q'=>'傳統軟水和瑞士水差在哪？','a'=>'傳統軟水用樹脂軟化、要加鹽再生、有再生廢水；瑞士水是抑垢、免鹽免耗材免插電、體積小。作法與維護不同。'],
 ['q'=>'軟水可以直接喝嗎？','a'=>'不建議。軟水/抑垢顧的是全家用水的水垢，飲用水請另接 RO 或淨水設備。'],
 ['q'=>'軟水一定要加鹽嗎？','a'=>'傳統離子交換軟水要加再生鹽；瑞士水這類抑垢系統則免加鹽、免耗材。看你選哪種。'],
 ['q'=>'透天和大樓裝法一樣嗎？','a'=>'不一樣。高雄透天多在頂樓水塔端；大樓空間小，免鹽桶的抑垢系統或窄身軟水機比較好安排。'],
 ['q'=>'高雄軟水行情多少？','a'=>'依機型、水量與屋況差異大，系統加安裝常見數萬元起跳，免鹽或進口更高，以現場場勘估價為準。'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$db->prepare("UPDATE geo_category_pages SET intro_html=?, faqs=?, is_active=1 WHERE id=109")->execute([$intro109,$faq109]);
$r=$db->query("SELECT LENGTH(intro_html) il,LENGTH(faqs) fl,is_active FROM geo_category_pages WHERE id=109")->fetch(PDO::FETCH_ASSOC);
echo "  #109 intro={$r['il']}B faqs={$r['fl']}B active={$r['is_active']}\n";

// ── 4. 驗證：淨水器/軟水頁 掛了哪些店家 + 攻略 ──
echo "\n═══ 4. 驗證 掛頁 ═══\n";
foreach(['jingshuiqi'=>8,'ruanshui'=>8] as $svc=>$_){
  $stores=$db->query("SELECT cl.brand_name FROM clients cl JOIN client_service_keywords csk ON csk.client_id=cl.id JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE COALESCE(NULLIF(sk.page_slug,''),sk.slug)='$svc' AND cl.is_active=1 AND cl.city_slug='kaohsiung' GROUP BY cl.id")->fetchAll(PDO::FETCH_COLUMN);
  $guides=$db->query("SELECT slug FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='kaohsiung' AND FIND_IN_SET('$svc',service_slug)>0")->fetchAll(PDO::FETCH_COLUMN);
  echo "  [$svc] 店家: ".(implode('、',$stores)?:'(無)')."\n         攻略: ".(implode('、',$guides)?:'(無)')."\n";
}
echo "\n實頁：\n  https://www.gomag.com.tw/city/kaohsiung/home-service/jingshuiqi\n  https://www.gomag.com.tw/city/kaohsiung/home-service/ruanshui\n";
