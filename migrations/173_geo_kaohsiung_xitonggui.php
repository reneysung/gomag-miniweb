<?php
/**
 * 173_geo_kaohsiung_xitonggui.php
 * 建立「高雄 × 居家服務 × 系統櫃」交叉頁內容（2026-07-23，Reney 核可稿）
 *
 * 背景：九十度 #14（高雄小港，自有工廠）已標 sk#153 xitonggui，
 *       但 /city/kaohsiung/home-service/xitonggui 無 geo 內容列 → 404，
 *       等於標了關鍵字卻沒有頁面承接。
 *
 * 差異化（避 doorway，遵 feedback-no-templated-multicity-articles）：
 *   - 台南系統櫃走「板材／封邊／五金」知識路線 → 高雄不重複該角度。
 *   - 高雄既有「系統廚具」頁已吃掉廚具／檯面／動線 → 本頁亦避開。
 *   - 本頁切點＝高雄產業實情：小港、仁武工業腹地使「工廠直營」成為常見選項，
 *     與品牌經銷、全室設計公司形成三種做法；並帶入重劃區（農十六／美術館／亞灣，
 *     新成屋整體規劃）vs 老屋翻新（鳳山／三民／前鎮，局部更換）的實際差異。
 *
 * 行情數字：Reney 指示上網查、參考性質即可。2026-07-23 多來源交叉比對
 *   （PRO360、序正、美麗安、woodliving 等）：
 *     系統衣櫃 基本款 7,000–10,000／尺、中階 9,000–13,000、高階 12,000–18,000
 *     系統電視櫃 2,500–3,500／尺（高身展示櫃 4,800–6,000）
 *     板材常見等級 E1V313（台貼板）／E0V313（如 Egger 進口板）
 *     成本結構 板材約 4 成、門片約 3 成、五金與配件約 3 成
 *   文中以「大致」「約」表述，不宣稱精準報價。
 *
 * 冪等：以 (city_slug, category_id, service_slug) 判斷，存在則更新。
 * 跑法：HTTP_HOST=www.gomag.com.tw php migrations/173_geo_kaohsiung_xitonggui.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

const CAT_HOME = 2;            // 居家服務
const CITY     = 'kaohsiung';
const SVC      = 'xitonggui';
const SVC_NAME = '系統櫃';

$intro = <<<'HTML'
<p>高雄找系統櫃，跟北部最大的不同是：<strong>這裡有不少自己開工廠的廠商</strong>。小港、仁武一帶的工業腹地，讓「工廠直營」成為常見選項，跟純設計公司、品牌經銷商是三種不同做法，價格結構與服務範圍差很多。</p>
<p>三種做法怎麼選：</p>
<ul>
<li><strong>工廠直營</strong>：自有生產線、少一層轉手，補料、修改、追加櫃體通常較快。適合需求明確、預算敏感的人。</li>
<li><strong>品牌經銷</strong>：板材來源穩定、有固定保固年限，左營、楠梓、鳳山都有門市。</li>
<li><strong>全室設計公司</strong>：含 3D 圖面、燈光與動線規劃，適合新成屋整體裝修或老屋翻新全案，總價較高。</li>
</ul>
<p>高雄的區域差異也很實際：農十六、美術館、亞灣這些重劃區以新成屋整體規劃為主，常是全室系統櫃一次到位；鳳山、三民、前鎮的老屋翻新則多為局部更換——換一面衣櫃、加一組電視櫃，這時工廠直營的彈性比較有優勢。</p>
<p>報價要問清楚三件事：①<strong>一尺包含什麼</strong>（櫃身、門片、五金是否分開計，有無含搬運、安裝與矽利康收邊）；②<strong>追加料怎麼算</strong>（不滿一尺是否進位、特殊尺寸與造型加價）；③<strong>丈量到安裝要多久</strong>（自有工廠通常較快，但交屋旺季一樣要排）。</p>
<p>系統櫃以「尺」計價，一尺約 30 公分，<strong>不滿一尺多半仍以一尺計</strong>——規劃時盡量抓 30 公分的倍數，才不會白花錢。</p>
HTML;

$faqs = [
    ['q' => '高雄系統櫃一尺多少錢？',
     'a' => '全台行情大致：系統衣櫃基本款每尺約 NT$7,000–10,000（塑合板＋一般五金）、中階約 9,000–13,000、高階約 12,000–18,000；系統電視櫃每尺約 NT$2,500–3,500，高身展示櫃約 4,800–6,000。以上僅供參考，實際依板材等級、門片材質與五金品牌差異很大，比價前要確認是同板材、同五金、同封邊，否則不同東西沒得比。'],
    ['q' => '工廠直營和設計公司差在哪？',
     'a' => '工廠直營少一層轉手，補料與修改通常較快，適合需求明確者；設計公司含 3D 圖與整體規劃，適合新成屋或老屋翻新全案。常見價差多來自服務範圍，而非板材本身。'],
    ['q' => '不滿一尺怎麼算？',
     'a' => '系統櫃多以尺計價（一尺約 30 公分），不滿一尺通常以一尺計。規劃時抓 30 公分的倍數比較不浪費。'],
    ['q' => '系統櫃報價裡，錢花在哪？',
     'a' => '大致是板材約四成、門片約三成、五金與收納配件合計約三成。板材常見等級為 E1V313（多數台貼板）或 E0V313（如 Egger 進口板），等級與甲醛釋放量直接反映在價格上。'],
    ['q' => '高雄靠海，系統櫃會受潮嗎？',
     'a' => '沿海地區與一樓、外牆側的櫃體建議選防潮型板材，封邊要確實；浴櫃則常用發泡板等防水材質。'],
    ['q' => '老屋翻新可以只換部分櫃體嗎？',
     'a' => '可以，局部更換在高雄老屋翻新很常見。要注意新舊櫃體的板材色差與尺寸銜接，現場丈量時先講清楚哪些保留、哪些更換。'],
];

$metaTitle = '高雄系統櫃推薦｜工廠直營 vs 設計公司怎麼選｜店家好口碑';
$metaDesc  = '高雄系統櫃怎麼找？工廠直營、品牌經銷、全室設計三種做法差在哪，一尺報價含什麼、追加料怎麼算，左營楠梓鳳山小港在地系統櫃廠商一次看。';

$exists = $db->prepare("SELECT id FROM geo_category_pages WHERE city_slug=? AND category_id=? AND service_slug=?");
$exists->execute([CITY, CAT_HOME, SVC]);
$id = $exists->fetchColumn();

if ($id) {
    $db->prepare("UPDATE geo_category_pages SET service_name=?, intro_html=?, faqs=?, meta_title=?, meta_desc=?, is_active=1 WHERE id=?")
       ->execute([SVC_NAME, $intro, json_encode($faqs, JSON_UNESCAPED_UNICODE), $metaTitle, $metaDesc, $id]);
    echo "✅ 已更新既有交叉頁 #{$id}\n";
} else {
    $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
                  VALUES (?,?,?,?,?,?,?,?,1)")
       ->execute([CITY, CAT_HOME, SVC, SVC_NAME, $intro, json_encode($faqs, JSON_UNESCAPED_UNICODE), $metaTitle, $metaDesc]);
    $id = (int)$db->lastInsertId();
    echo "✅ 已建立交叉頁 #{$id}\n";
}

// ── 驗證 ──
$r = $db->query("SELECT city_slug, service_slug, service_name, CHAR_LENGTH(intro_html) L, JSON_LENGTH(faqs) F, is_active FROM geo_category_pages WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
echo "  /{$r['city_slug']}/{$r['service_slug']}（{$r['service_name']}）intro={$r['L']} 字、FAQ={$r['F']} 題、啟用={$r['is_active']}\n";

$q = $db->prepare("
    SELECT cl.id, cl.brand_name FROM clients cl
    JOIN client_service_keywords csk ON csk.client_id=cl.id
    JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE cl.is_active=1 AND (sk.slug=? OR sk.page_slug=?)
      AND (cl.city_slug=? OR EXISTS (SELECT 1 FROM client_city_pages p WHERE p.client_id=cl.id AND p.city_slug=? AND p.is_active=1))");
$q->execute([SVC, SVC, CITY, CITY]);
$stores = $q->fetchAll(PDO::FETCH_ASSOC);
echo "  承接店家 " . count($stores) . " 家：";
foreach ($stores as $s) echo "#{$s['id']} {$s['brand_name']}  ";
echo "\n";
