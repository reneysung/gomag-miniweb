<?php
// ============================================================
// Migration 094 — 5 都汽車旅館 seed 資料（25 家，無客戶、純目錄）
// ------------------------------------------------------------
// 台北/新北/桃園/台中/高雄 各 5 家真實汽車旅館，皆 Google 查證、未捏造。
// 基本資訊：店面圖/名稱/地址/電話/官網。無描述、no minisite。
// 全標 motel(#124)，is_active=1，is_placeholder=0 → 前台正常列出。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/094_motel_seed_5cities.php
// 冪等：ON DUPLICATE KEY UPDATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$MOTEL_KW = 124;
$IMG_BASE = 'https://www.gomag.com.tw/uploads/clients/motels/';

// [slug, brand_name, city_slug, address, phone, website, image_filename]
$rows = [
  ['motel-taipei-1','薇閣精品旅館 大直館','taipei','台北市中山區敬業三路11號','02-8502-7000','https://dazhi.we-go.com.tw/','motel-taipei-1.jpg'],
  ['motel-taipei-2','Tango MOTEL 天豪屋','taipei','台北市信義區忠孝東路五段297號3-5F','02-2528-2262','https://www.tangohouse.com.tw/','motel-taipei-2.jpg'],
  ['motel-taipei-3','馥麗精品旅館','taipei','台北市內湖區內湖路一段325號','02-2658-6167','https://www.neihuelegancehotel.com.tw/','motel-taipei-3.jpg'],
  ['motel-taipei-4','雅柏精緻汽車旅館','taipei','台北市松山區南京東路五段325號','02-2764-5858','http://www.alpermotel.com.tw/','motel-taipei-4.jpg'],
  ['motel-taipei-5','沃客汽車旅館 Walker Motel','taipei','台北市北投區承德路七段236號','02-2822-6666','https://www.facebook.com/walkmotel/','motel-taipei-5.jpg'],
  ['motel-newtaipei-1','美麗殿精品旅館 中和館','newtaipei','新北市中和區板南路487號','02-2222-6868','https://motel.merryday.com.tw/','motel-newtaipei-1.jpg'],
  ['motel-newtaipei-2','三重江月行館 JMoon Villa','newtaipei','新北市三重區重新路五段596號','02-8511-0998','https://jmoonvilla.com.tw/','motel-newtaipei-2.jpg'],
  ['motel-newtaipei-3','挪威森林板橋旗艦館','newtaipei','新北市板橋區和平路14巷3號','02-8951-3311','http://banqiao.villa-group.com.tw/','motel-newtaipei-3.jpg'],
  ['motel-newtaipei-4','歐悅連鎖精品汽車旅館 林口館','newtaipei','新北市林口區文化三路二段115巷163號','02-2608-6611','https://www.ohyamotel.com/','motel-newtaipei-4.jpg'],
  ['motel-newtaipei-5','愛莉亞汽車旅館','newtaipei','新北市三峽區中山路169巷10號','02-2674-1133','https://aliamotel.com.tw/','motel-newtaipei-5.jpg'],
  ['motel-taoyuan-1','天鵝湖汽車旅館','taoyuan','桃園市龜山區萬壽路一段379號','02-8200-2000','http://www.swanlake-motel.com/','motel-taoyuan-1.jpg'],
  ['motel-taoyuan-2','168motel 中壢2館','taoyuan','桃園市中壢區中美路二段160號','03-427-6333','https://168motelzhonglisecondbranch.168inn.com.tw/zh-tw','motel-taoyuan-2.jpg'],
  ['motel-taoyuan-3','六星旅館 桃園館','taoyuan','桃園市桃園區經國路721號','03-346-7668','https://sixstartaoyuan.168inn.com.tw/zh-tw','motel-taoyuan-3.jpg'],
  ['motel-taoyuan-4','和陽行館 Villa Bon Moment','taoyuan','桃園市大園區領航南路四段303號','03-287-8555','https://www.villabonmoment.com/','motel-taoyuan-4.jpg'],
  ['motel-taoyuan-5','風閣時尚精品旅館 Vogue Motel','taoyuan','桃園市楊梅區新農街245巷17號','03-478-8686','https://www.voguemotel.com.tw/','motel-taoyuan-5.jpg'],
  ['motel-taichung-1','鳥人創意旅店','taichung','台中市北屯區經貿三路二段55號','04-2426-2777','http://www.bmmotel.com/','motel-taichung-1.jpg'],
  ['motel-taichung-2','水雲端旗艦概念旅館','taichung','台中市西屯區青海南街159號','04-2452-5678','https://www.icloudhotel.com.tw/','motel-taichung-2.jpg'],
  ['motel-taichung-3','紫禁城主題會館','taichung','台中市南屯區大墩六街488號','04-2386-9999','https://www.fcmotel.com/','motel-taichung-3.jpg'],
  ['motel-taichung-4','雲平精品旅館','taichung','台中市太平區新興路106號','04-2392-5555','http://www.yunping-motel.com.tw/zh-tw','motel-taichung-4.jpg'],
  ['motel-taichung-5','雲月精品會館','taichung','台中市大里區勝利二路100號','04-2481-5688','http://www.kumotsukimotel.com.tw/zh-tw','motel-taichung-5.jpg'],
  ['motel-kaohsiung-1','凱莉都精品旅店','kaohsiung','高雄市楠梓區大學七街236號','07-591-8988','https://www.kellymotel.com.tw/','motel-kaohsiung-1.jpg'],
  ['motel-kaohsiung-2','盈春汽車旅館','kaohsiung','高雄市仁武區安樂一街368號','07-374-1379','https://www.yingchun.com.tw/','motel-kaohsiung-2.jpg'],
  ['motel-kaohsiung-3','御宿汽車旅館 明誠館','kaohsiung','高雄市鼓山區龍勝路333號','07-550-5501','https://www.royal-group.com.tw/location_page?id=21&type=2&cur=1','motel-kaohsiung-3.jpg'],
  ['motel-kaohsiung-4','歐閣精品汽車旅館 簡愛館','kaohsiung','高雄市鳳山區錦田路161號','07-841-9999','https://830058.ouge-motel.com/','motel-kaohsiung-4.jpg'],
  ['motel-kaohsiung-5','雅博泊特汽車旅館','kaohsiung','高雄市鳥松區忠誠路105號','07-732-3300','http://www.harportmotel.com/','motel-kaohsiung-5.jpg'],
];

$ins = $db->prepare("INSERT INTO clients (slug, brand_name, city_slug, category_id, address, phone, external_website_url, hero_image_path, is_active, has_minisite, is_placeholder)
    VALUES (?, ?, ?, 12, ?, ?, ?, ?, 1, 0, 0)
    ON DUPLICATE KEY UPDATE brand_name=VALUES(brand_name), city_slug=VALUES(city_slug), category_id=12,
      address=VALUES(address), phone=VALUES(phone), external_website_url=VALUES(external_website_url),
      hero_image_path=VALUES(hero_image_path), is_active=1, has_minisite=0, is_placeholder=0");
$tag = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
$inserted = 0; $tagged = 0;
foreach ($rows as $r) {
    $ins->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $IMG_BASE . $r[6]]);
    $cid = $db->query("SELECT id FROM clients WHERE slug=" . $db->quote($r[0]))->fetchColumn();
    $tag->execute([$cid, $MOTEL_KW]);
    $inserted++;
    if ($cid) $tagged++;
    printf("  ✓ %s (#%d) %s\n", $r[0], $cid, $r[1]);
}
echo "\n完成。INSERT/UPDATE {$inserted} 家、TAG {$tagged} 家\n";

echo "\n== 各城市汽車旅館數 ==\n";
foreach ($db->query("SELECT c.city_slug, COUNT(*) cnt FROM clients c JOIN client_service_keywords csk ON csk.client_id=c.id WHERE csk.service_keyword_id={$MOTEL_KW} AND c.is_active=1 AND c.is_placeholder=0 GROUP BY c.city_slug") as $r) {
    echo "  {$r['city_slug']}: {$r['cnt']} 家\n";
}
