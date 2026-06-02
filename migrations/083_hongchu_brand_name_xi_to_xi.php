<?php
// ============================================================
// Migration 083 — 統一洪廚品牌名「洪廚喜宴」→「洪廚囍宴」
// ------------------------------------------------------------
// Reney 對稿：跟 FB 官方粉專、痞客邦業配文一致，用「囍宴」傳統雙喜寫法
//
// 全 DB 22 處：
//   - clients.brand_name (1) + about_text (1)
//   - client_city_pages 4 城 × meta+landing+reviews (16)
//   - store_blocks faq data (1)
//   - testimonials 3 條 quote 內容 (3)
//
// 邊界決定：testimonials 3 條原本是部落客寫的 quote（原文用「喜宴」），
// 統一改成「囍宴」會微調引用、優先品牌一致性。
// 如果你想保留 quote 原貌可以再開 migration 改回。
//
// 不改：URL hongchu-banquet.com、slug hongchu、subdomain hongchu、
//       FB URL（本來就是「囍宴」）、service_keywords slug
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/083_hongchu_brand_name_xi_to_xi.php
// 冪等：REPLACE 第二次跑就 0 處要改，可安全重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }

$find = '洪廚喜宴';
$repl = '洪廚囍宴';

// ─── A. clients 主檔（2 處）────────────────────────────────
$db->prepare("UPDATE clients SET
    brand_name      = REPLACE(brand_name, ?, ?),
    about_text      = REPLACE(about_text, ?, ?),
    tagline         = REPLACE(tagline, ?, ?),
    industry        = REPLACE(industry, ?, ?),
    store_meta_title = REPLACE(store_meta_title, ?, ?),
    store_meta_desc  = REPLACE(store_meta_desc, ?, ?),
    store_keywords   = REPLACE(store_keywords, ?, ?),
    top_banner_html  = REPLACE(top_banner_html, ?, ?)
    WHERE id = ?")->execute([
    $find, $repl, $find, $repl, $find, $repl, $find, $repl,
    $find, $repl, $find, $repl, $find, $repl, $find, $repl, $cid
]);
echo "✅ A. clients 主檔 8 欄位 REPLACE 完成\n";

// ─── B. client_city_pages 4 城（16 處）────────────────────
$db->prepare("UPDATE client_city_pages SET
    store_meta_title       = REPLACE(store_meta_title, ?, ?),
    store_meta_desc        = REPLACE(store_meta_desc, ?, ?),
    store_keywords         = REPLACE(store_keywords, ?, ?),
    landing_extra_content  = REPLACE(landing_extra_content, ?, ?),
    external_reviews_json  = REPLACE(external_reviews_json, ?, ?),
    minisite_meta_title    = REPLACE(minisite_meta_title, ?, ?),
    minisite_meta_desc     = REPLACE(minisite_meta_desc, ?, ?),
    minisite_intro_html    = REPLACE(minisite_intro_html, ?, ?)
    WHERE client_id = ?")->execute([
    $find, $repl, $find, $repl, $find, $repl, $find, $repl,
    $find, $repl, $find, $repl, $find, $repl, $find, $repl, $cid
]);
echo "✅ B. client_city_pages 4 城 8 欄位 REPLACE 完成\n";

// ─── C. store_blocks faq（1 處）────────────────────────────
$db->prepare("UPDATE store_blocks SET data = REPLACE(data, ?, ?) WHERE client_id = ?")
   ->execute([$find, $repl, $cid]);
echo "✅ C. store_blocks data REPLACE 完成\n";

// ─── D. testimonials（3 處 — 微調引用）────────────────────
$db->prepare("UPDATE testimonials SET content = REPLACE(content, ?, ?) WHERE client_id = ?")
   ->execute([$find, $repl, $cid]);
echo "✅ D. testimonials content REPLACE 完成（注意：3 條原 quote 從「喜」改「囍」）\n";

// ─── 驗證：再次 grep ──────────────────────────────────────
echo "\n=== 驗證：剩餘「洪廚喜宴」字串數 ===\n";

$leftover = 0;
$cols_clients = ['brand_name', 'tagline', 'industry', 'about_text', 'store_meta_title', 'store_meta_desc', 'store_keywords', 'top_banner_html'];
foreach ($cols_clients as $col) {
    $v = $db->query("SELECT $col FROM clients WHERE id=$cid")->fetchColumn();
    $n = mb_substr_count($v ?? '', $find);
    $leftover += $n;
    if ($n > 0) echo "  ⚠️ clients.$col: $n 處\n";
}

$rs = $db->query("SELECT city_slug, store_meta_title, store_meta_desc, store_keywords, landing_extra_content, external_reviews_json FROM client_city_pages WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rs as $r) {
    foreach (['store_meta_title','store_meta_desc','store_keywords','landing_extra_content','external_reviews_json'] as $col) {
        $n = mb_substr_count($r[$col] ?? '', $find);
        $leftover += $n;
        if ($n > 0) echo "  ⚠️ {$r['city_slug']}/$col: $n 處\n";
    }
}

$rs = $db->query("SELECT id, data FROM store_blocks WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rs as $r) {
    $n = mb_substr_count($r['data'] ?? '', $find);
    $leftover += $n;
    if ($n > 0) echo "  ⚠️ store_blocks id={$r['id']}: $n 處\n";
}

$rs = $db->query("SELECT id, content FROM testimonials WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rs as $r) {
    $n = mb_substr_count($r['content'] ?? '', $find);
    $leftover += $n;
    if ($n > 0) echo "  ⚠️ testimonial id={$r['id']}: $n 處\n";
}

if ($leftover === 0) echo "✅ 剩餘 0 處（全部 22 處 → 0）\n";
else echo "⚠️ 還有 {$leftover} 處沒清，請檢查\n";

echo "\n品牌名統一為「洪廚囍宴」完成。\n";
echo "未動：hongchu URL slug / subdomain / hongchu-banquet.com 官網拼音。\n";
