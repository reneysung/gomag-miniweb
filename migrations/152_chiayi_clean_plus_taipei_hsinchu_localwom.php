<?php
// ============================================================
// Migration 152 — 嘉義清潔補 1 家(三福) + 台北/新竹懶人包加「其他在地口碑」
// ------------------------------------------------------------
// A. 嘉義清潔 placeholder +1：三福清潔（NAP 官網核對）→ 嘉義清潔 2→3 家。
// B. 台北/新竹裝潢細清懶人包：客戶卡只有 2 家，依懶人包格式「Google 補真實在地」
//    加一段「其他在地口碑」——**只列名+公開特色、不外連、不捏造、標明非本站收錄**。
//    真實業者取自公開搜尋結果。插在 FAQ(winner-boost-v1) 之前。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/152_chiayi_clean_plus_taipei_hsinchu_localwom.php
// 冪等：slug 查重 + marker <!-- local-wom-v1 --> 防重跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ───── A. 嘉義 三福清潔 placeholder ─────
echo "═══ A. 嘉義清潔 +三福 ═══\n";
$dup = $db->query("SELECT id FROM clients WHERE brand_name LIKE '%三福%清潔%'")->fetchColumn();
if ($dup) {
    echo "  ⏭ 已有 三福（#{$dup}）\n";
} else {
    $ex = $db->prepare("SELECT id FROM clients WHERE slug='chiayi-sanfu-clean'");
    $ex->execute();
    if ($cid = $ex->fetchColumn()) {
        echo "  ⏭ slug 已存在 #{$cid}\n";
    } else {
        $db->prepare("INSERT INTO clients
            (subdomain, slug, brand_name, tagline, industry, category_id, phone, address, city_slug,
             is_active, has_minisite, store_template, minisite_template, is_placeholder)
            VALUES (?,?,?,?, '清潔服務', 2, ?, ?, 'chiayi', 1, 0, '_default', '_default', 1)")
            ->execute(['chiayi-sanfu-clean','chiayi-sanfu-clean','三福清潔公司',
                '嘉義市西區清潔公司，服務嘉義縣市、雲林與台南，房屋清洗、地毯磁磚外牆水塔清洗、地板打蠟、木質地板保養、石材研磨拋光。',
                '05-2311415','嘉義市西區成功街85號']);
        $cid = (int)$db->lastInsertId();
        echo "  ✅ #{$cid} 三福清潔公司\n";
    }
    $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, 1)")->execute([$cid]); // cleaning
}
$n = $db->query("SELECT COUNT(DISTINCT cl.id) FROM clients cl JOIN client_service_keywords csk ON csk.client_id=cl.id JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE COALESCE(NULLIF(sk.page_slug,''),sk.slug)='cleaning' AND cl.is_active=1 AND cl.city_slug='chiayi'")->fetchColumn();
echo "  嘉義清潔(主檔 city=chiayi) 現 {$n} 家\n\n";

// ───── B. 台北/新竹 其他在地口碑（Google 補真實、不外連）─────
echo "═══ B. 台北/新竹懶人包 +其他在地口碑 ═══\n";
$blocks = [
  'taipei-fine-clean-recommend' => "<!-- local-wom-v1 -->\n<h2>台北其他裝潢細清在地口碑</h2>\n<p>以下為網路公開口碑整理，非本站收錄商家，資料以各店公開資訊為準：</p>\n<ul>\n  <li><strong>家事達清潔</strong>：裝潢細清、空屋清潔，主打驗收滿意才收費。</li>\n  <li><strong>佐沐清潔</strong>：累積數千場裝潢細清實務經驗。</li>\n  <li><strong>家必潔</strong>：專營台北、新北裝潢細清，網友推薦。</li>\n  <li><strong>御用清潔</strong>：裝潢細清，細清流程與費用說明清楚。</li>\n  <li><strong>潔客森清潔</strong>：裝潢後細清，並提供清潔時機建議。</li>\n</ul>\n",
  'hsinchu-fine-clean-recommend' => "<!-- local-wom-v1 -->\n<h2>新竹其他裝潢細清在地口碑</h2>\n<p>以下為網路公開口碑整理，非本站收錄商家，資料以各店公開資訊為準：</p>\n<ul>\n  <li><strong>清潔醫生</strong>：新竹／竹北裝潢細清，地板、玻璃、門框殘膠清除，免費到府估價。</li>\n  <li><strong>愛家家事</strong>：竹北在地，裝潢細清、搬家清潔、空屋清潔。</li>\n  <li><strong>潔昇清潔</strong>：工地交屋清潔、裝潢清潔、住家大掃除。</li>\n  <li><strong>愛幫忙清潔</strong>：新成屋、整修後空間全面打掃。</li>\n  <li><strong>管家太太</strong>：大新竹、竹北，裝潢清潔與家事服務。</li>\n</ul>\n",
];
foreach ($blocks as $slug => $block) {
    $len = (int)$db->query("SELECT LENGTH(body_html) FROM guides WHERE slug='{$slug}'")->fetchColumn();
    if (!$len) { echo "  ⚠ 找不到 {$slug}\n"; continue; }
    $body = '';
    for ($i=0;$i<$len;$i+=4000){ $st=$db->prepare("SELECT SUBSTRING(body_html,?,4000) FROM guides WHERE slug='{$slug}'"); $st->execute([$i+1]); $body.=(string)$st->fetchColumn(); }
    if (strpos($body,'local-wom-v1')!==false) { echo "  ⏭ {$slug} 已加過\n"; continue; }
    if (strpos($body,'<!-- winner-boost-v1 -->')===false) { echo "  ⚠ {$slug} 無 winner-boost-v1 錨點，改用結尾 append\n"; $new=$body.$block; }
    else { $new = str_replace('<!-- winner-boost-v1 -->', $block."<!-- winner-boost-v1 -->", $body); }
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$new, $slug]);
    echo "  ✅ {$slug} +其他在地口碑（body {$len}→".strlen($new)."B）\n";
}
echo "\n實頁驗證：\n  https://www.gomag.com.tw/city/chiayi/home-service/cleaning\n  https://www.gomag.com.tw/guide/taipei-fine-clean-recommend\n  https://www.gomag.com.tw/guide/hsinchu-fine-clean-recommend\n";
