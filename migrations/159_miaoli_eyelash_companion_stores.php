<?php
// ============================================================
// Migration 159 — 苗栗美睫陪襯店 ×2（湊到 3 家）
// ------------------------------------------------------------
// 苗栗美睫子服務頁原只緣子(yenbrows,竹南)1 家 → 補 2 家真實在地陪襯 placeholder：
//   YJ．悅己美學(竹南)、忻楠美學院(頭份)。NAP 取自公開搜尋結果、tagline 只轉述公開服務。
//   is_placeholder=1（薄頁 noindex、排正式客戶後）。標 eyelash 掛上苗栗美睫頁。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/159_miaoli_eyelash_companion_stores.php
// 冪等：slug 查重。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$stores = [
  [
    'slug'=>'miaoli-yj-beauty','brand'=>'YJ．悅己美學',
    'tagline'=>'苗栗竹南美學工作室，日式與新中式睫毛嫁接、漫畫風接睫，搭配凝膠美甲，全預約制。',
    'phone'=>'0903-470-272','address'=>'苗栗縣竹南鎮大埔一街376號',
  ],
  [
    'slug'=>'miaoli-xinnan-beauty','brand'=>'忻楠美學院',
    'tagline'=>'苗栗頭份美學院，美睫、美甲、美容與美睫教學，UV光美睫、韓式霧眉一站服務。',
    'phone'=>'','address'=>'苗栗縣頭份市建國路二段111號2樓',
  ],
];
$ins=$db->prepare("INSERT INTO clients (subdomain,slug,brand_name,tagline,industry,category_id,phone,address,city_slug,is_active,has_minisite,store_template,minisite_template,is_placeholder)
    VALUES (?,?,?,?, '美睫工作室', 3, ?, ?, 'miaoli', 1, 0, '_default', '_default', 1)");
$tag=$db->prepare("INSERT IGNORE INTO client_service_keywords (client_id,service_keyword_id) VALUES (?, 60)"); // eyelash
foreach($stores as $s){
  $ex=$db->prepare("SELECT id FROM clients WHERE slug=?"); $ex->execute([$s['slug']]); $cid=$ex->fetchColumn();
  if($cid){ echo "  ⏭ 已存在 #{$cid} {$s['brand']}\n"; }
  else{ $ins->execute([$s['slug'],$s['slug'],$s['brand'],$s['tagline'],$s['phone'],$s['address']]); $cid=(int)$db->lastInsertId(); echo "  ✅ #{$cid} {$s['brand']}\n"; }
  $tag->execute([$cid]);
}
$n=$db->query("SELECT cl.brand_name,cl.is_placeholder FROM clients cl JOIN client_service_keywords csk ON csk.client_id=cl.id JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE COALESCE(NULLIF(sk.page_slug,''),sk.slug)='eyelash' AND cl.is_active=1 AND cl.city_slug='miaoli' GROUP BY cl.id")->fetchAll(PDO::FETCH_ASSOC);
echo "\n苗栗美睫頁店家 ".count($n)." 家：".implode('、',array_map(fn($x)=>$x['brand_name'].($x['is_placeholder']?'(ph)':''),$n))."\n";
echo "\n實頁： https://www.gomag.com.tw/city/miaoli/beauty/eyelash\n";
