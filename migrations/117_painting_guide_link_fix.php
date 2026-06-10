<?php
// ============================================================
// Migration 117 — 油漆防水通用指南 + 既有 12 篇相關 guide 補內鏈
// ------------------------------------------------------------
// 6 城 painting (油漆防水) 子服務頁全孤立。已有 12 篇 waterproof/exterior-waterproof
// guide 但都沒鏈到 painting 子服務頁。雙修：
//
// 1. 寫《2026 全台油漆翻新與防水抓漏指南》通用 guide 內鏈 6 都 painting
// 2. 既有 12 篇 painting 相關 guide 結尾統一 append 對應子服務內鏈
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/117_painting_guide_link_fix.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'taiwan-painting-waterproof-guide-2026';
$title = '全台油漆翻新與防水抓漏指南 2026：室內粉刷、外牆抓漏、行情、6 都比較';
$metaTitle = '全台油漆防水指南 2026｜室內粉刷外牆抓漏行情｜6 都比較｜店家好口碑';
$metaDesc = '油漆翻新與防水抓漏怎麼挑？室內粉刷每坪行情、外牆抓漏、屋頂防水、壁癌處理 SOP，台北、新北、台中、台南、高雄、屏東 6 都氣候差異與在地施工眉角。';
$excerpt = '油漆翻新、室內粉刷、外牆抓漏、屋頂防水、壁癌處理全攻略 — 從補漆到全屋翻新預算怎麼抓、防水材質怎麼挑、6 都氣候差異與在地施工眉角一篇看。';
$cover = 'https://www.gomag.com.tw/uploads/guides/covers/waterproof.svg';

$cities = [
    'taipei' => ['name'=>'台北', 'angle'=>'老公寓外牆磁磚剝落、屋頂滲水、頂樓加蓋抓漏需求高；信義/大安重劃區家庭客以室內粉刷翻新為主。'],
    'newtaipei' => ['name'=>'新北', 'angle'=>'淡水/汐止/烏來山區濕氣重、外牆滲水嚴重；林口/板橋/中和/三重重劃區新成屋以室內補漆為主；老公寓頂樓加蓋抓漏年年要做。'],
    'taichung' => ['name'=>'台中', 'angle'=>'七期豪宅以室內整面翻新為主；北屯/南屯/西屯家庭客補漆需求穩；中部相對乾燥但夏季暴雨易造成外牆滲水。'],
    'tainan' => ['name'=>'台南', 'angle'=>'府城老屋翻修以室內粉刷+壁癌處理為主；安平/安南近海有鹽害腐蝕問題；新營/後壁鄉間透天屋頂防水年年要顧。'],
    'kaohsiung' => ['name'=>'高雄', 'angle'=>'港都濕熱年中無休、外牆鹽害嚴重；鼓山/前鎮/小港近海更需要抗鹽害漆面；左營/巨蛋重劃區新成屋以室內補漆為主。'],
    'pingtung' => ['name'=>'屏東', 'angle'=>'透天厝多、屋頂防水年年要顧；恆春半島強日照與颱風期外牆壓力大、需要抗 UV 漆面；山區竹田/萬丹有壁癌問題。'],
];

$body  = "<p>油漆翻新與防水抓漏是台灣居家最常見的維護需求。台灣的氣候條件——梅雨、颱風、夏季暴雨、冬季東北季風、近海鹽害——讓房屋的「皮膚」每 3-5 年就需要做一次大檢查。這篇整理 <strong>室內粉刷與外牆防水的差異</strong>、<strong>行情區間</strong>、<strong>壁癌處理 SOP</strong>、<strong>6 都氣候與在地考量</strong>，協助你挑對工法、避開常見地雷。</p>\n\n";

$body .= "<h2 id=\"toc\">📖 文章目錄</h2>\n<ol>\n";
$body .= "  <li><a href=\"#types\">5 種主要服務（室內粉刷/外牆抓漏/屋頂防水/壁癌/浴室）</a></li>\n";
$body .= "  <li><a href=\"#price\">行情區間：每坪 NT\$800–2,500（油漆）／每坪 NT\$1,500–4,000（防水）</a></li>\n";
$body .= "  <li><a href=\"#cities\">6 都氣候差異與在地考量</a></li>\n";
$body .= "  <li><a href=\"#tips\">挑選 5 個重點</a></li>\n";
$body .= "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"types\">5 種主要服務</h2>\n<ul>\n";
$body .= "  <li><strong>室內粉刷</strong>：整面或局部補漆、批土、底漆、面漆 2-3 道。每坪 NT\$800–2,500，視批土程度與漆種。</li>\n";
$body .= "  <li><strong>外牆抓漏</strong>：外牆磁磚／RC 滲水點檢測、灌注 PU 發泡、整面塗布彈性水泥／壓克力防水。每坪 NT\$2,500–4,000。</li>\n";
$body .= "  <li><strong>屋頂防水</strong>：水泥粉光底層 + 防水層（彈性水泥／瀝青／PU／EPDM 卷材）+ 表面保護層。每坪 NT\$1,500–3,500，看材質。</li>\n";
$body .= "  <li><strong>壁癌處理</strong>：拆除受損批土 / 油漆、找漏水源、滲透型防水液 + 透氣性面材、含環境除濕。每坪 NT\$2,000–4,500。</li>\n";
$body .= "  <li><strong>浴室防水</strong>：磁磚拆除前需做防水層整圈包覆（牆面到天花、地面到牆裙），整套含拆翻新 NT\$30,000–80,000/間。</li>\n";
$body .= "</ul>\n\n";

$body .= "<h2 id=\"price\">行情區間</h2>\n<ul>\n";
$body .= "  <li><strong>NT\$800–1,200/坪</strong>：基本款室內粉刷（單色面漆 2 道，無大量批土），適合補漆翻新。</li>\n";
$body .= "  <li><strong>NT\$1,200–1,800/坪</strong>：中階室內粉刷含批土平整，主流家庭選擇。</li>\n";
$body .= "  <li><strong>NT\$1,800–2,500/坪</strong>：高階室內粉刷含特殊漆（防潮/抗菌/低 VOC），環境敏感家庭。</li>\n";
$body .= "  <li><strong>NT\$2,500–3,500/坪</strong>：屋頂防水中階（彈性水泥兩道），住家屋頂主流。</li>\n";
$body .= "  <li><strong>NT\$3,500–5,000/坪</strong>：高階屋頂防水（EPDM 卷材或 PU 防水）含長保固。</li>\n";
$body .= "</ul>\n";
$body .= "<p><strong>含什麼／不含什麼必問</strong>：家具搬移、地面保護、垃圾清運、底漆道數、面漆道數、保固範圍（材料 vs 工程）、漆種品牌（得利、虹牌、ICI、本土）。</p>\n\n";

$body .= "<h2 id=\"cities\">6 都氣候差異與在地考量</h2>\n";
$body .= "<p>台灣 6 都氣候、住宅型態、滲水模式差異大，挑工法要看城市。</p>\n\n";
foreach ($cities as $citySlug => $info) {
    $body .= "<h3>{$info['name']}</h3>\n";
    $body .= "<p>{$info['angle']}</p>\n";
    $body .= "<p><strong>{$info['name']}在地油漆防水與相關居家服務</strong>："
        . "<a href=\"/city/{$citySlug}/home-service/painting\">{$info['name']}油漆防水</a>｜"
        . "<a href=\"/city/{$citySlug}/home-service\">{$info['name']}居家服務</a></p>\n\n";
}

$body .= "<h2 id=\"tips\">挑選 5 個重點</h2>\n<ol>\n";
$body .= "  <li><strong>看在地經驗</strong>：每個城市的滲水模式不同（北部頂樓加蓋、中南部外牆鹽害、屏東颱風）。找在地師傅，他們知道這城市怎麼漏。</li>\n";
$body .= "  <li><strong>看是否「先找漏水源」</strong>：滲水修錯點沒用！正規業者會用紅外線、水管打壓、滲水追蹤液找源頭、不只塗表面。</li>\n";
$body .= "  <li><strong>看保固分項</strong>：「材料保固」「工程保固」「滲漏保固」分別保多久？正規業者外牆滲漏保 1–5 年、屋頂防水 3–10 年。</li>\n";
$body .= "  <li><strong>看漆種與環保等級</strong>：室內漆選 F4★/E0（低甲醛）、有家裡有過敏或寵物選低 VOC；外牆漆選抗 UV、抗污自潔款。</li>\n";
$body .= "  <li><strong>看施工 SOP</strong>：底材處理、批土打磨、底漆、面漆 2-3 道、收邊保護。省工省料的就會省這些。</li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"faq\">常見問題 FAQ</h2>\n";
$body .= "<h3>Q1：油漆多久要重做？</h3>\n";
$body .= "<p>室內每 5–8 年補漆翻新；外牆每 5–10 年（看氣候與漆種）；屋頂防水每 5–8 年；浴室防水隨整間翻新做（10–15 年）。</p>\n";
$body .= "<h3>Q2：壁癌是怎麼形成的、能根治嗎？</h3>\n";
$body .= "<p>壁癌主因是「水分長期滲入水泥導致鹽分結晶」。要根治必須找出水源（外牆滲水、管線漏水、結露），單純表面油漆會反覆復發。完整 SOP：找源頭 → 拆除受損面 → 滲透型防水液 → 透氣性面材 → 環境除濕。</p>\n";
$body .= "<h3>Q3：外牆抓漏要拆磁磚嗎？</h3>\n";
$body .= "<p>看狀況。表面細微裂縫可灌注 PU 不必拆磁磚；磁磚大面剝落或整面滲水需拆除後做整面防水層。後者每坪費用是前者 3–5 倍。</p>\n";
$body .= "<h3>Q4：頂樓加蓋怎麼防水？</h3>\n";
$body .= "<p>頂樓加蓋的問題不只屋頂、還有四周接縫。完整防水：頂樓平面塗布、女兒牆（圍欄）四周向下捲材、新舊銜接面 PU 灌注。便宜做法（只塗頂樓表面）半年就重漏。</p>\n";
$body .= "<h3>Q5：浴室翻新要拆到底嗎？</h3>\n";
$body .= "<p>看屋齡與漏水狀況。10 年以下且無漏水 → 可只換磁磚保留防水層；10 年以上或漏水 → 拆到 RC 重做整圈防水。後者貴但避免將來重漏。</p>\n";
$body .= "<h3>Q6：低 VOC 漆值得多花錢嗎？</h3>\n";
$body .= "<p>家裡有小孩、孕婦、過敏體質、寵物 → 強烈建議低 VOC（揮發性有機物）。一般漆與低 VOC 漆價差約 30-50%，但室內空氣質量差異大、施工後 1-2 週可立即入住（一般漆需通風 1 個月）。</p>\n\n";

$body .= "<h2>結尾：怎麼用這份清單</h2>\n";
$body .= "<p>油漆翻新與防水抓漏選對師傅關鍵：① 看在地經驗（每城滲水模式不同）、② 先找漏水源不是塗表面、③ 報價單分項透明、④ 看保固範圍、⑤ 環保漆優先。下方按 6 都列出店家好口碑收錄的在地油漆防水子分類頁。</p>\n";

// 寫入通用 guide
$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, cover_image, status, published_at)
    VALUES (?, ?, ?, ?, NULL, 2, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug=NULL, category_id=VALUES(category_id),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
        cover_image=VALUES(cover_image), status='published',
        published_at=COALESCE(published_at, NOW())");
$gs->execute([$slug, $title, $excerpt, $body, $metaTitle, $metaDesc, $cover]);
$row = $db->query("SELECT id, LENGTH(body_html) body_len FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
echo ">> 通用 guide id={$row['id']}, body={$row['body_len']} bytes\n";

// 既有 12 篇 painting/waterproof guide append painting 子服務內鏈
$marker = '<!-- related-services-block-v1 -->';
$cityGuides = [
    ['slug' => 'taipei-waterproof-guide', 'city' => 'taipei'],
    ['slug' => 'taipei-exterior-waterproof-guide', 'city' => 'taipei'],
    ['slug' => 'newtaipei-waterproof-guide', 'city' => 'newtaipei'],
    ['slug' => 'newtaipei-exterior-waterproof-guide', 'city' => 'newtaipei'],
    ['slug' => 'taichung-waterproof-guide', 'city' => 'taichung'],
    ['slug' => 'taichung-exterior-waterproof-guide', 'city' => 'taichung'],
    ['slug' => 'tainan-waterproof-guide', 'city' => 'tainan'],
    ['slug' => 'tainan-exterior-waterproof-guide', 'city' => 'tainan'],
    ['slug' => 'kaohsiung-waterproof-guide', 'city' => 'kaohsiung'],
    ['slug' => 'kaohsiung-exterior-waterproof-guide', 'city' => 'kaohsiung'],
    ['slug' => 'pingtung-waterproof-guide', 'city' => 'pingtung'],
    ['slug' => 'pingtung-exterior-waterproof-guide', 'city' => 'pingtung'],
];
echo "\n>> 12 篇 city waterproof guide append 對應 painting 內鏈\n";
foreach ($cityGuides as $g) {
    $row = $db->prepare("SELECT body_html FROM guides WHERE slug=?");
    $row->execute([$g['slug']]);
    $body = $row->fetchColumn();
    if (!$body) { echo "  ❌ 找不到 {$g['slug']}\n"; continue; }
    if (strpos($body, $marker) !== false) { echo "  ⏭ skip {$g['slug']}（已有 marker）\n"; continue; }

    $cn = $cities[$g['city']]['name'];
    $append = "\n\n{$marker}\n<h2 id=\"related-services\">延伸閱讀：{$cn} 居家服務子分類</h2>\n";
    $append .= "<p>找{$cn}在地相關居家服務：</p>\n<ul>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/painting\">{$cn}油漆防水</a></li>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/cleaning\">{$cn}清潔公司</a></li>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/flooring\">{$cn}木地板</a></li>\n";
    $append .= "</ul>\n<p>更多服務見 <a href=\"/city/{$g['city']}/home-service\">{$cn}居家服務</a> 樞紐頁。</p>\n";
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$body . $append, $g['slug']]);
    echo "  ✅ {$g['slug']}\n";
}

// 驗證
echo "\n═══ 6 城 painting 子服務頁 內鏈數 ═══\n";
foreach (['taipei','newtaipei','taichung','tainan','kaohsiung','pingtung'] as $c) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$c}/home-service/painting%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %s painting → %d 篇 guide 內鏈\n", $flag, $c, $n);
}
echo "\n預覽：https://www.gomag.com.tw/guide/{$slug}\n";
