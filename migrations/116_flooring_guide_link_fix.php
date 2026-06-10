<?php
// ============================================================
// Migration 116 — 木地板通用指南 + 既有 city guide 補內鏈
// ------------------------------------------------------------
// A4 揭露 7 城 flooring 子服務頁全孤立。已有 6 篇 city flooring guide
// （台北/新北/桃園/新竹/台中/高雄 + 台中材質專文），但本身也沒鏈到
// 對應子服務頁。雙修：
//
// 1. 寫《2026 全台木地板選購保養指南》通用 guide 內鏈 7 城
// 2. 6 篇 city flooring guide 結尾統一 append 對應子服務頁內鏈
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/116_flooring_guide_link_fix.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = 'taiwan-flooring-guide-2026';
$title = '全台木地板選購與保養指南 2026：海島型／超耐磨／實木怎麼挑、行情、7 都比較';
$metaTitle = '全台木地板指南 2026｜海島型超耐磨實木怎麼挑｜7 都行情比較｜店家好口碑';
$metaDesc = '木地板怎麼挑？海島型、超耐磨、實木、SPC 石塑怎麼選？台北、新北、桃園、新竹、台中、台南、高雄 7 都木地板行情每坪 NT$2,500–8,000 區間、施工方式、保養 SOP 一篇看。';
$excerpt = '木地板選購、施工、保養全攻略 — 海島型、超耐磨、SPC 石塑、實木 4 種材質怎麼挑，台灣潮濕氣候下哪種最耐？台北、新北、桃園、新竹、台中、台南、高雄 7 都行情區間與在地施工眉角一篇整理。';
$cover = 'https://www.gomag.com.tw/uploads/guides/covers/flooring.svg';

$cities = [
    'taipei' => ['name'=>'台北', 'angle'=>'老公寓濕氣重、坪數小，海島型木地板與 SPC 石塑常見；信義/大安重劃區大坪數新成屋偏好超耐磨或實木。'],
    'newtaipei' => ['name'=>'新北', 'angle'=>'淡水/林口/三重重劃區新成屋多，超耐磨木地板需求高；汐止/烏來山區濕氣重，海島型或 SPC 較適合。'],
    'taoyuan' => ['name'=>'桃園', 'angle'=>'青埔、藝文特區、中壢重劃區大坪數新成屋多，超耐磨與實木選擇豐富；機場線粉塵多要考慮易清潔款式。'],
    'hsinchu' => ['name'=>'新竹', 'angle'=>'竹北高鐵特區竹科族新成屋偏好高品質超耐磨；湖口竹東客家庄頭傳統老屋翻修常配海島型。'],
    'taichung' => ['name'=>'台中', 'angle'=>'七期豪宅愛實木與高階超耐磨；北屯/西屯/南屯重劃區家庭客超耐磨主流；台中盆地氣候相對乾燥，實木表現比北部佳。'],
    'tainan' => ['name'=>'台南', 'angle'=>'府城老屋翻修常配海島型（耐潮）；東區/永康新成屋與重劃區走超耐磨主流；安平/安南近海要選有抗鹽害漆面。'],
    'kaohsiung' => ['name'=>'高雄', 'angle'=>'港都濕熱年中無休、近海鹽害問題，海島型與 SPC 石塑為首選；巨蛋/美術館重劃區新成屋走超耐磨。'],
];

$body  = "<p>木地板是新成屋與老屋翻修最常見的地材選擇，但「海島型、超耐磨、實木、SPC 石塑」各有適用場景、價差可達 3 倍。挑錯材質要拆掉重做，是裝潢中最容易踩雷的項目之一。這篇整理 <strong>4 種主流材質的差異</strong>、<strong>挑選與保養 SOP</strong>、<strong>7 都施工眉角</strong>，協助你避開常見地雷。</p>\n\n";

$body .= "<h2 id=\"toc\">📖 文章目錄</h2>\n<ol>\n";
$body .= "  <li><a href=\"#materials\">4 種木地板材質比較（海島型／超耐磨／實木／SPC 石塑）</a></li>\n";
$body .= "  <li><a href=\"#price\">行情：每坪 NT\$2,500–8,000 怎麼分</a></li>\n";
$body .= "  <li><a href=\"#cities\">7 都氣候與在地考量</a></li>\n";
$body .= "  <li><a href=\"#tips\">挑選 5 個重點</a></li>\n";
$body .= "  <li><a href=\"#care\">保養 SOP</a></li>\n";
$body .= "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"materials\">4 種木地板材質比較</h2>\n<ul>\n";
$body .= "  <li><strong>海島型木地板</strong>：多層結構、表層真木皮、抗潮性最佳，台灣海島氣候首選。每坪 NT\$3,000–6,000，使用壽命 10–20 年。</li>\n";
$body .= "  <li><strong>超耐磨木地板</strong>：表層高密度密集板、耐磨度 AC4–AC5，價格友善、家庭客主流。每坪 NT\$2,500–5,000，使用壽命 8–15 年，但抗潮中等。</li>\n";
$body .= "  <li><strong>實木地板</strong>：100% 原木、質感與保值性最高，但價格貴、需專業保養。每坪 NT\$5,000–12,000+，使用壽命 30+ 年。台灣潮濕氣候建議搭配除濕。</li>\n";
$body .= "  <li><strong>SPC 石塑地板</strong>：石粉 + 塑膠基材、防水 100%、廚衛也能鋪。每坪 NT\$2,000–4,500，使用壽命 10–15 年，質感比 PVC 高、比實木自然度低。</li>\n";
$body .= "</ul>\n\n";

$body .= "<h2 id=\"price\">行情：每坪 NT\$2,500–8,000 怎麼分</h2>\n<ul>\n";
$body .= "  <li><strong>NT\$2,500–3,500/坪</strong>：基本款超耐磨或 SPC，連工帶料、適合預算有限或租屋裝修。</li>\n";
$body .= "  <li><strong>NT\$3,500–5,000/坪</strong>：中階海島型或 AC5 超耐磨、主流家庭選擇、含基礎踢腳板。</li>\n";
$body .= "  <li><strong>NT\$5,000–6,500/坪</strong>：高階海島型（厚表板）或進口品牌超耐磨、長保固。</li>\n";
$body .= "  <li><strong>NT\$6,500–10,000+/坪</strong>：實木或高端進口超耐磨，含特殊鋪法（人字拼、魚骨拼）。</li>\n";
$body .= "</ul>\n";
$body .= "<p><strong>含什麼／不含什麼要問清楚</strong>：踢腳板、收邊條、家具搬移、舊地板拆除、垃圾清運、隔音棉、防潮布是否含？</p>\n\n";

$body .= "<h2 id=\"cities\">7 都氣候與在地考量</h2>\n";
$body .= "<p>台灣 7 都氣候與住宅型態差異大，挑材質要看你的城市與屋型。</p>\n\n";
foreach ($cities as $citySlug => $info) {
    $body .= "<h3>{$info['name']}</h3>\n";
    $body .= "<p>{$info['angle']}</p>\n";
    $body .= "<p><strong>{$info['name']}在地木地板與相關居家服務</strong>："
        . "<a href=\"/city/{$citySlug}/home-service/flooring\">{$info['name']}木地板</a>｜"
        . "<a href=\"/city/{$citySlug}/home-service\">{$info['name']}居家服務</a></p>\n\n";
}

$body .= "<h2 id=\"tips\">挑選 5 個重點</h2>\n<ol>\n";
$body .= "  <li><strong>看材質適性</strong>：北部濕氣重選海島型或 SPC；中南部相對乾燥可選超耐磨；近海／浴室選 SPC。</li>\n";
$body .= "  <li><strong>看耐磨度等級</strong>：超耐磨選 AC4（家用）或 AC5（商用/重磨損家庭）；甲醛等級選 F4★/E0（最低）。</li>\n";
$body .= "  <li><strong>看實品打樣</strong>：燈光下看色差、手摸表面紋路、近看接縫密合度；至少 30×30 cm 樣品判斷。</li>\n";
$body .= "  <li><strong>看保固範圍</strong>：海島型表板保固 5–10 年、超耐磨耐磨保固 8–15 年、實木 10 年+；保固「材料 vs 工程」要分開看。</li>\n";
$body .= "  <li><strong>看施工 SOP</strong>：先除濕、底材整平、防潮布、伸縮縫、踢腳板收尾。整套 SOP 走完比省工省料重要。</li>\n";
$body .= "</ol>\n\n";

$body .= "<h2 id=\"care\">保養 SOP</h2>\n<ul>\n";
$body .= "  <li><strong>日常</strong>：吸塵器或軟毛掃帚，每週 1–2 次；勿用濕拖把直接拖。</li>\n";
$body .= "  <li><strong>濕拖</strong>：擰乾到「不滴水」程度、配合木地板專用清潔劑、季節除濕。</li>\n";
$body .= "  <li><strong>防潮</strong>：梅雨季與颱風後務必開除濕機，相對濕度維持 50–60%。</li>\n";
$body .= "  <li><strong>修補</strong>：表面刮痕用修補蠟、深層損傷需專業整片更換、實木可整面重新磨平上漆。</li>\n";
$body .= "</ul>\n\n";

$body .= "<h2 id=\"faq\">常見問題 FAQ</h2>\n";
$body .= "<h3>Q1：海島型 vs 超耐磨哪個好？</h3>\n";
$body .= "<p>看氣候與預算。海島型抗潮、表層真木皮、質感佳，適合北部濕氣重區或追求自然感；超耐磨耐磨度高、價格友善、家庭客主流。如果有寵物或小孩，超耐磨 AC5 抗刮抗濕表現夠用。</p>\n";
$body .= "<h3>Q2：實木地板真的會發霉嗎？</h3>\n";
$body .= "<p>會。台灣潮濕氣候下，實木如沒配合除濕機與防潮處理，2–3 年內可能發霉、變形、翹曲。實木建議用在乾燥地區（中部）或配備全室除濕系統。</p>\n";
$body .= "<h3>Q3：SPC 石塑跟 PVC 卷材差在哪？</h3>\n";
$body .= "<p>SPC 是石粉壓製、質感與耐磨度高於 PVC，可拼接成片；PVC 卷材整捲鋪設、價格更低但接縫多、不耐家具壓痕。SPC 約是 PVC 2 倍價但質感差很多。</p>\n";
$body .= "<h3>Q4：超耐磨可以鋪在浴室嗎？</h3>\n";
$body .= "<p>不建議。超耐磨基材是 HDF（高密度密集板），遇水會膨脹變形。浴室、廚房濕區建議用 SPC 石塑或磁磚。</p>\n";
$body .= "<h3>Q5：木地板要拆舊地板嗎？</h3>\n";
$body .= "<p>看原地板狀況。原為磁磚地板且平整 → 可直接鋪上；原為老舊木地板或不平整 → 需先拆除整平，會增加 NT\$500–1,500/坪 拆運費。</p>\n";
$body .= "<h3>Q6：施工後多久可以走？</h3>\n";
$body .= "<p>無膠扣鎖系統當天即可走，但 24 小時內輕踩；膠合系統建議 48 小時後再放家具。</p>\n\n";

$body .= "<h2>結尾：怎麼用這份清單</h2>\n";
$body .= "<p>木地板挑選關鍵：① 看你所在城市的氣候、② 看屋型（新成屋 vs 老屋翻修）、③ 看預算、④ 看耐磨需求（寵物/小孩）。下方按 7 都列出店家好口碑收錄的在地木地板施工子分類頁，可直接點進去看在地施工團隊。</p>\n";

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

// 6 篇 city flooring guide append 內鏈
$marker = '<!-- related-services-block-v1 -->';
$cityGuides = [
    ['slug' => 'taipei-flooring-guide', 'city' => 'taipei'],
    ['slug' => 'newtaipei-flooring-guide', 'city' => 'newtaipei'],
    ['slug' => 'taoyuan-flooring-guide', 'city' => 'taoyuan'],
    ['slug' => 'hsinchu-flooring-guide', 'city' => 'hsinchu'],
    ['slug' => 'taichung-flooring-material-guide', 'city' => 'taichung'],
    ['slug' => 'kaohsiung-flooring-guide', 'city' => 'kaohsiung'],
];
echo "\n>> 6 篇 city flooring guide append 對應子服務內鏈\n";
foreach ($cityGuides as $g) {
    $row = $db->prepare("SELECT body_html FROM guides WHERE slug=?");
    $row->execute([$g['slug']]);
    $body = $row->fetchColumn();
    if (!$body) { echo "  ❌ 找不到 {$g['slug']}\n"; continue; }
    if (strpos($body, $marker) !== false) { echo "  ⏭ skip {$g['slug']}（已有 marker）\n"; continue; }

    $cn = $cities[$g['city']]['name'];
    $append = "\n\n{$marker}\n<h2 id=\"related-services\">延伸閱讀：{$cn} 居家服務子分類</h2>\n";
    $append .= "<p>找{$cn}在地相關居家服務：</p>\n<ul>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/flooring\">{$cn}木地板</a></li>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/painting\">{$cn}油漆防水</a></li>\n";
    $append .= "  <li><a href=\"/city/{$g['city']}/home-service/cleaning\">{$cn}清潔公司</a></li>\n";
    $append .= "</ul>\n<p>更多服務見 <a href=\"/city/{$g['city']}/home-service\">{$cn}居家服務</a> 樞紐頁。</p>\n";
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$body . $append, $g['slug']]);
    echo "  ✅ {$g['slug']}\n";
}

// 驗證
echo "\n═══ 7 城 flooring 子服務頁 內鏈數 ═══\n";
foreach (['taipei','newtaipei','taoyuan','hsinchu','taichung','tainan','kaohsiung'] as $c) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$c}/home-service/flooring%"]);
    $n = (int)$st->fetchColumn();
    $flag = $n == 0 ? '🔴' : '🟢';
    printf("  %s %s flooring → %d 篇 guide 內鏈\n", $flag, $c, $n);
}
echo "\n預覽：https://www.gomag.com.tw/guide/{$slug}\n";
