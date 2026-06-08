<?php
// ============================================================
// Migration 110 — /city/tainan/food 樞紐頁 SEO 強化
// ------------------------------------------------------------
// 寫入 geo_category_pages 樞紐 row（city=tainan, category=1, service_slug=''），
// 強化 meta_title/meta_desc/intro_html/faqs 收「台南美食推薦」這個大關鍵字。
//
// 注意：H1「台南餐飲美食」是 citycat.php 寫死，這次不動程式只動 DB。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/110_tainan_food_hub_seo.php
// 冪等：INSERT ... ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$intro = <<<HTML
<p>台南是台灣美食之都，從<strong>府城中西區</strong>的老店小吃、<strong>東區成大商圈</strong>的學生餐廳與聚餐火鍋、<strong>永康與南區</strong>的家庭客平價餐廳、<strong>安平與安南</strong>的海鮮料理，到<strong>新營與後壁</strong>的庄頭辦桌、辦桌外燴文化，每個區域都有自己的飲食地圖。</p>

<p><strong>台南美食推薦怎麼挑？</strong>看你今天要做什麼：</p>

<ul>
  <li>聚餐熱鬧 → <a href="/city/tainan/food/hotpot">台南火鍋</a>、<a href="/city/tainan/food/yakiniku">台南燒肉燒烤</a>、<a href="/city/tainan/food/chinese">台南中式熱炒</a></li>
  <li>家庭日常 → <a href="/city/tainan/food/steak">台南牛排</a>、<a href="/city/tainan/food/japanese">台南日式料理</a>、<a href="/city/tainan/food/western">台南西餐餐酒館</a></li>
  <li>婚宴尾牙 → <a href="/city/tainan/food/banquet">台南婚宴餐廳</a>、<a href="/city/tainan/food/banquet-catering">台南辦桌外燴</a>、<a href="/city/tainan/food/wedding-banquet">喜宴婚宴外燴</a>、<a href="/city/tainan/food/year-end-party">尾牙春酒外燴</a></li>
</ul>

<p>挑台南餐廳的 3 個重點：① <strong>看在地口碑</strong>（Google 評論 ≥ 4.3、評論數 ≥ 100 較穩）、② <strong>看分區屬性</strong>（中西區偏老店傳統、東區偏聚餐與學生客、安平偏觀光與海鮮）、③ <strong>看是不是當日預約得到</strong>（熱門店週末常滿、提早電話確認）。下方為店家好口碑收錄的台南在地餐飲商家清單。</p>
HTML;

$faqs = json_encode([
    ['q' => '台南美食有哪些區域代表性料理？',
     'a' => '中西區以府城老店小吃為主（牛肉湯、虱目魚、肉燥飯、碗粿）；東區成大商圈聚餐火鍋、燒肉、學生餐廳密集；永康與南區家庭餐廳、平價牛排多；安平、安南有新鮮海鮮料理與蚵仔煎；新營與後壁保留庄頭辦桌、桌菜外燴文化。'],
    ['q' => '台南婚宴喜宴餐廳怎麼選？',
     'a' => '預算高、賓客多選婚宴會館或飯店宴會廳；預算實在、要澎湃菜色選辦桌總舖師外燴。台南辦桌文化深厚，桌菜行情每桌約 NT$8,000–18,000，建議 1–3 個月前預訂。'],
    ['q' => '台南聚餐火鍋燒肉推薦哪個區？',
     'a' => '東區成大、北區與中西區海安路一帶火鍋與燒肉密度最高，學生族群與聚餐客都適合。永康與南區則平價家庭聚餐選擇多，價格友善。'],
    ['q' => '台南日式料理在哪？',
     'a' => '中西區國華街、海安路一帶有壽司、拉麵、丼飯名店；東區成大商圈居酒屋密集；永康有平價日式定食連鎖店。挑日式料理看食材新鮮度與廚師資歷。'],
    ['q' => '台南公司尾牙春酒外燴行情？',
     'a' => '台南尾牙外燴每桌 NT$8,000–15,000 是常見區間，10 桌以上總舖師會出動完整廚房團隊。建議 11 月起就預訂，避開過年前最後 2 週爆量期。'],
    ['q' => '台南餐廳怎麼判斷在地口碑？',
     'a' => '看 Google 評論「分數 + 評論數」雙指標：分數 ≥ 4.3 且評論數 ≥ 100 通常較穩定；只看分數不看評論數會踩雷（少評論的高分不算數）。再對照在地論壇（PTT/Dcard 台南板）與本站收錄商家清單交叉驗證。'],
], JSON_UNESCAPED_UNICODE);

$metaTitle = '台南美食推薦 2026｜中西區/東區/永康/安平餐廳精選｜店家好口碑';
$metaDesc  = '台南美食推薦哪幾家？府城中西區、東區、永康、安平在地餐廳精選，火鍋、燒肉、牛排、日式、中式熱炒、婚宴餐廳、辦桌外燴一次看，店家好口碑收錄台南在地口碑商家。';

$stmt = $db->prepare("INSERT INTO geo_category_pages
    (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES ('tainan', 1, '', '', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE
        intro_html=VALUES(intro_html),
        faqs=VALUES(faqs),
        meta_title=VALUES(meta_title),
        meta_desc=VALUES(meta_desc),
        is_active=1");
$stmt->execute([$intro, $faqs, $metaTitle, $metaDesc]);
echo ">> UPSERT geo_category_pages: tainan / food / 樞紐頁\n";

// 驗證
echo "\n== 驗證 ==\n";
$row = $db->query("SELECT meta_title, meta_desc, LEFT(intro_html, 100) intro_preview, faqs, is_active
    FROM geo_category_pages WHERE city_slug='tainan' AND category_id=1 AND service_slug=''")->fetch(PDO::FETCH_ASSOC);
echo "  meta_title: {$row['meta_title']}\n";
echo "  meta_desc:  " . mb_substr($row['meta_desc'], 0, 80) . "...\n";
echo "  intro:      {$row['intro_preview']}...\n";
echo "  faqs 數:    " . count(json_decode($row['faqs'], true) ?: []) . " 條\n";
echo "  is_active:  {$row['is_active']}\n";

echo "\n完成。預覽：https://www.gomag.com.tw/city/tainan/food\n";
