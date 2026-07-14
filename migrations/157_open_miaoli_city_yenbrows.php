<?php
// ============================================================
// Migration 157 — 開啟苗栗縣(miaoli) + 新客戶 yenbrows 歸位
// ------------------------------------------------------------
// 新客戶 緣子美學工作室(#277 yenbrows，苗栗竹南美睫)，但苗栗不在 cities 表 → 沒開啟。
// 開城=純資料（front_functions.php:29「開新縣市=cities 表新增一筆 row，不動程式碼」）：
//   1. cities 加 miaoli row（full_name='苗栗縣' 讓 getCityMap/deriveCitySlug 都認得）。
//   2. yenbrows.city_slug='miaoli'（原本空）。
// 苗栗文案用公開地理（山線海線/客家/竹南頭份/三義大湖），不捏造。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/157_open_miaoli_city_yenbrows.php
// 冪等：slug 唯一 ON DUPLICATE / UPDATE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/front_functions.php';
$db = getDB();

// ── 1. cities 加苗栗 ──
$tagline = '苗栗在地商家推薦——山線海線、客家庄，從竹南頭份到三義大湖的口碑店家一次看。';
$intro = '<p>苗栗縣橫跨山線與海線：竹南、頭份鄰新竹科學園區生活圈、人口密集；苗栗市、公館、銅鑼是傳統山城；後龍、通霄、苑裡沿海；三義木雕、大湖草莓、南庄老街各有特色。生活圈分散，找在地店家時「離不離得近、口碑好不好」特別重要。這裡整理苗栗在地口碑商家，從美容美髮、居家服務到餐飲，看真實評價再決定。</p>';
$metaTitle = '苗栗在地商家推薦｜美容美髮、居家服務、餐飲口碑一次看｜店家好口碑';
$metaDesc = '苗栗縣在地店家推薦，涵蓋竹南、頭份、苗栗市、後龍、通霄、三義、大湖等地區，美容美髮、美睫、居家服務、餐飲等口碑商家，看真實評價找在地好店。';

$sortMax = (int)$db->query("SELECT COALESCE(MAX(sort_order),0) FROM cities")->fetchColumn();
$ins = $db->prepare("INSERT INTO cities (slug, name, full_name, tagline, intro, meta_title, meta_desc, is_active, sort_order, created_at, updated_at)
    VALUES ('miaoli','苗栗','苗栗縣',?,?,?,?,1,?,NOW(),NOW())
    ON DUPLICATE KEY UPDATE name='苗栗', full_name='苗栗縣',
      tagline=VALUES(tagline), intro=VALUES(intro), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1, updated_at=NOW()");
$ins->execute([$tagline,$intro,$metaTitle,$metaDesc,$sortMax+1]);
$c=$db->query("SELECT id,slug,name,full_name,is_active FROM cities WHERE slug='miaoli'")->fetch(PDO::FETCH_ASSOC);
echo "✅ cities 苗栗 #{$c['id']} slug={$c['slug']} full_name={$c['full_name']} active={$c['is_active']}\n";

// ── 2. yenbrows 歸位 ──
$db->prepare("UPDATE clients SET city_slug='miaoli' WHERE slug='yenbrows'")->execute();
$y=$db->query("SELECT id,brand_name,city_slug,category_id FROM clients WHERE slug='yenbrows'")->fetch(PDO::FETCH_ASSOC);
echo "✅ yenbrows #{$y['id']} {$y['brand_name']} city_slug={$y['city_slug']}\n";

// ── 3. 驗證：getCityMap / deriveCitySlug 認得苗栗 ──
$map=getCityMap();
echo "\ngetCityMap 有 miaoli？ ".(isset($map['miaoli'])?"✅ {$map['miaoli']}":'❌')."\n";
echo "deriveCitySlug('苗栗縣竹南鎮…') = ".(deriveCitySlug('苗栗縣竹南鎮大厝里國安街105號') ?: 'null')."\n";
$n=(int)$db->query("SELECT COUNT(*) FROM clients WHERE city_slug='miaoli' AND is_active=1")->fetchColumn();
echo "苗栗客戶數: {$n}\n";
echo "\n實頁： https://www.gomag.com.tw/city/miaoli\n";
