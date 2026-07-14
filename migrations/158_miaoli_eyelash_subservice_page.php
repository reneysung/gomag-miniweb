<?php
// ============================================================
// Migration 158 — 苗栗美睫子服務頁 /city/miaoli/beauty/eyelash + 折關鍵字
// ------------------------------------------------------------
// 新客戶 yenbrows(緣子美學,竹南美睫,已標 eyelash)歸位苗栗後，開美睫子服務落地頁：
//   - geo_category_pages（miaoli, cat 3 美容美髮, service_slug=eyelash）苗栗在地內容+FAQ、active。
//   - 折碎關鍵字：接睫毛(jiejiemao)/嫁接睫毛(jiajiejiemao) → 折進 eyelash 避 doorway。
// 苗栗美睫需求脈絡用公開地理（竹南頭份鄰竹科生活圈、苗栗市），不捏造特定店家事實。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/158_miaoli_eyelash_subservice_page.php
// 冪等：unique key (city,cat,svc) upsert / page_slug set。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 1. 折關鍵字：接睫毛/嫁接睫毛 → eyelash ──
$db->exec("UPDATE service_keywords SET page_slug='eyelash' WHERE slug IN ('jiejiemao','jiajiejiemao')");
echo "折關鍵字 → eyelash：\n";
foreach($db->query("SELECT slug,name,page_slug FROM service_keywords WHERE slug IN ('eyelash','jiejiemao','jiajiejiemao') ORDER BY id") as $r)
  echo "  {$r['slug']} ({$r['name']}) page_slug=[{$r['page_slug']}]\n";

// ── 2. 苗栗美睫 geo 子服務頁 ──
$intro = <<<'H'
<p>苗栗的美睫需求集中在竹南、頭份——這兩地鄰新竹科學園區生活圈、人口多、上班族與媽媽客群密集，接睫毛、嫁接睫毛的店家也最多；苗栗市、後龍一帶則以在地熟客為主。常見的服務有單根嫁接、濃密（開花）款、日式自然款、韓式濃密款，部分工作室也開美睫教學課。</p>
<p>挑苗栗美睫看幾件事：①<strong>睫毛與膠水</strong>（材質、低敏膠、味道）；②<strong>單根有沒有隔開</strong>（黏成一撮容易夾傷真睫）；③<strong>維持與回補</strong>（一般 3～4 週回補）；④<strong>卸除方式</strong>（用專用卸除、不硬拔）；⑤<strong>環境與衛生</strong>（器具消毒、一人一套）。竹南、頭份選擇多，建議先看實際作品照與評價，預約制的工作室記得提早約。</p>
H;
$faqs = json_encode([
 ['q'=>'苗栗嫁接睫毛大概多少錢？','a'=>'依款式與根數而定，單根自然款與濃密開花款價位不同，多數工作室會分「新做」與「回補」價；竹南、頭份選擇多，建議先看價目與作品再約。'],
 ['q'=>'嫁接睫毛多久要回補？','a'=>'一般 3～4 週回補一次，依個人睫毛生長週期與保養習慣而定。想維持濃密度建議固定回補。'],
 ['q'=>'嫁接睫毛會傷到真睫毛嗎？','a'=>'正規做法會單根隔開、不黏成撮、用低敏膠，並以專用卸除液卸除，對真睫負擔較小；怕傷睫毛要避免硬拔與過重的款式。'],
 ['q'=>'單根、濃密（開花）怎麼選？','a'=>'單根自然、適合想低調的人；濃密開花一根接多根、妝感重、適合想要眼睛放大效果的人。可依眼型與需求請美睫師建議。'],
 ['q'=>'苗栗美睫在哪一區選擇最多？','a'=>'竹南、頭份因鄰竹科生活圈、人口密集，美睫工作室最多；苗栗市、後龍一帶也有在地店家。'],
 ['q'=>'美睫工作室都要預約嗎？','a'=>'多數是預約制、一人一套服務，熱門時段（假日、下班後）較滿，建議提前 3～5 天預約。'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$metaTitle = '苗栗美睫推薦｜竹南頭份嫁接睫毛、單根濃密、價位維持一次看';
$metaDesc = '苗栗美睫、嫁接睫毛找哪裡？竹南、頭份在地美睫工作室，單根自然、濃密開花、日式韓式款、價位與回補週期、怎麼挑不傷真睫，一次看。';

$db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active, created_at)
    VALUES ('miaoli', 3, 'eyelash', '美睫', ?, ?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE service_name='美睫', intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1")
    ->execute([$intro,$faqs,$metaTitle,$metaDesc]);
$r=$db->query("SELECT id,LENGTH(intro_html) il,LENGTH(faqs) fl,is_active FROM geo_category_pages WHERE city_slug='miaoli' AND category_id=3 AND service_slug='eyelash'")->fetch(PDO::FETCH_ASSOC);
echo "\n✅ 苗栗美睫 geo #{$r['id']} intro={$r['il']}B faqs={$r['fl']}B active={$r['is_active']}\n";

// ── 3. 驗證掛頁 ──
$stores=$db->query("SELECT cl.brand_name FROM clients cl JOIN client_service_keywords csk ON csk.client_id=cl.id JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE COALESCE(NULLIF(sk.page_slug,''),sk.slug)='eyelash' AND cl.is_active=1 AND cl.city_slug='miaoli' GROUP BY cl.id")->fetchAll(PDO::FETCH_COLUMN);
echo "苗栗美睫頁店家: ".(implode('、',$stores)?:'(無)')."\n";
echo "\n實頁： https://www.gomag.com.tw/city/miaoli/beauty/eyelash\n";
