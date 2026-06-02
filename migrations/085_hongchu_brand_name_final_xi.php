<?php
// ============================================================
// Migration 085 — 洪廚品牌名最終定案「洪廚喜宴」→「洪廚囍宴」
// ------------------------------------------------------------
// Reney 對稿：「錯！是這個囍」— 確認要用「囍」雙喜寫法
// 084 把全部改成「喜」，現在 reverse 回「囍」
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/085_hongchu_brand_name_final_xi.php
// 冪等：REPLACE 第二次跑 0 處要改、可安全重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }

$find = '洪廚喜宴';
$repl = '洪廚囍宴';

// A. clients 8 欄位
$db->prepare("UPDATE clients SET
    brand_name       = REPLACE(brand_name, ?, ?),
    tagline          = REPLACE(tagline, ?, ?),
    industry         = REPLACE(industry, ?, ?),
    about_text       = REPLACE(about_text, ?, ?),
    store_meta_title = REPLACE(store_meta_title, ?, ?),
    store_meta_desc  = REPLACE(store_meta_desc, ?, ?),
    store_keywords   = REPLACE(store_keywords, ?, ?),
    top_banner_html  = REPLACE(top_banner_html, ?, ?)
    WHERE id = ?")->execute([
    $find, $repl, $find, $repl, $find, $repl, $find, $repl,
    $find, $repl, $find, $repl, $find, $repl, $find, $repl, $cid
]);
echo "✅ A. clients 8 欄位\n";

// B. client_city_pages 4 城 8 欄位
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
echo "✅ B. client_city_pages 4 城 8 欄位\n";

// C. store_blocks
$db->prepare("UPDATE store_blocks SET data = REPLACE(data, ?, ?) WHERE client_id = ?")
   ->execute([$find, $repl, $cid]);
echo "✅ C. store_blocks data\n";

// D. testimonials
$db->prepare("UPDATE testimonials SET content = REPLACE(content, ?, ?) WHERE client_id = ?")
   ->execute([$find, $repl, $cid]);
echo "✅ D. testimonials content\n";

// 驗證
echo "\n=== 驗證 ===\n";
$leftover = 0;
foreach (['brand_name','tagline','industry','about_text','store_meta_title','store_meta_desc','store_keywords','top_banner_html'] as $col) {
    $v = $db->query("SELECT $col FROM clients WHERE id=$cid")->fetchColumn();
    $n = mb_substr_count($v ?? '', $find);
    if ($n) { echo "  ⚠️ clients.$col: $n\n"; $leftover += $n; }
}
foreach ($db->query("SELECT city_slug, store_meta_title, store_meta_desc, store_keywords, landing_extra_content, external_reviews_json, minisite_meta_title, minisite_meta_desc, minisite_intro_html FROM client_city_pages WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    foreach (['store_meta_title','store_meta_desc','store_keywords','landing_extra_content','external_reviews_json','minisite_meta_title','minisite_meta_desc','minisite_intro_html'] as $col) {
        $n = mb_substr_count($r[$col] ?? '', $find);
        if ($n) { echo "  ⚠️ {$r['city_slug']}.$col: $n\n"; $leftover += $n; }
    }
}
foreach ($db->query("SELECT id, data FROM store_blocks WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $n = mb_substr_count($r['data'] ?? '', $find);
    if ($n) { echo "  ⚠️ store_blocks id={$r['id']}: $n\n"; $leftover += $n; }
}
foreach ($db->query("SELECT id, content FROM testimonials WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $n = mb_substr_count($r['content'] ?? '', $find);
    if ($n) { echo "  ⚠️ testimonial id={$r['id']}: $n\n"; $leftover += $n; }
}
echo $leftover === 0 ? "✅ 剩餘 0 處「洪廚喜宴」、全部統一為「洪廚囍宴」\n" : "⚠️ 還有 {$leftover} 處未清\n";
