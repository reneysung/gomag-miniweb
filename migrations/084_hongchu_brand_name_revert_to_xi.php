<?php
// ============================================================
// Migration 084 — 統一洪廚品牌名「洪廚囍宴」→「洪廚喜宴」
// ------------------------------------------------------------
// Reney 對稿：「是這個喜！」— 上次我選錯字，要用「喜」不是「囍」
// 把 prod 全部 32 處「洪廚囍宴」改成「洪廚喜宴」
//
// 跟 083 鏡像反過來：
//   - clients 8 欄位 + 4 城 client_city_pages 8 欄位
//   - store_blocks faq + testimonials
//   - 但這次 32 處（比 083 多 10 處，因為另一對話也加了「囍宴」到 title）
//
// 不改：
//   - URL slug hongchu / subdomain / 官網 hongchu-banquet.com
//   - client_social.fb_page_url（FB page 名稱是 FB 那邊的、我們改連結
//     文字但實際還是連到「洪廚囍宴-720924347985691」這個 page）
//   - service_keywords slug
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/084_hongchu_brand_name_revert_to_xi.php
// 冪等：REPLACE 第二次跑 0 處要改、可安全重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }

$find = '洪廚囍宴';
$repl = '洪廚喜宴';

// ─── A. clients 主檔 ───────────────────────────────────────
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
echo "✅ A. clients 8 欄位 REPLACE 完成\n";

// ─── B. client_city_pages 4 城 ─────────────────────────────
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

// ─── C. store_blocks ──────────────────────────────────────
$db->prepare("UPDATE store_blocks SET data = REPLACE(data, ?, ?) WHERE client_id = ?")
   ->execute([$find, $repl, $cid]);
echo "✅ C. store_blocks data REPLACE 完成\n";

// ─── D. testimonials ──────────────────────────────────────
$db->prepare("UPDATE testimonials SET content = REPLACE(content, ?, ?) WHERE client_id = ?")
   ->execute([$find, $repl, $cid]);
echo "✅ D. testimonials content REPLACE 完成（3 條 quote 微調）\n";

// ─── 驗證 ──────────────────────────────────────────────────
echo "\n=== 驗證：剩餘「洪廚囍宴」字串數 ===\n";
$leftover = 0;
foreach (['brand_name','tagline','industry','about_text','store_meta_title','store_meta_desc','store_keywords','top_banner_html'] as $col) {
    $v = $db->query("SELECT $col FROM clients WHERE id=$cid")->fetchColumn();
    $n = mb_substr_count($v ?? '', $find);
    $leftover += $n;
    if ($n > 0) echo "  ⚠️ clients.$col: $n\n";
}
$rs = $db->query("SELECT city_slug, store_meta_title, store_meta_desc, store_keywords, landing_extra_content, external_reviews_json, minisite_meta_title, minisite_meta_desc, minisite_intro_html FROM client_city_pages WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rs as $r) {
    foreach (['store_meta_title','store_meta_desc','store_keywords','landing_extra_content','external_reviews_json','minisite_meta_title','minisite_meta_desc','minisite_intro_html'] as $col) {
        $n = mb_substr_count($r[$col] ?? '', $find);
        $leftover += $n;
        if ($n > 0) echo "  ⚠️ {$r['city_slug']}.$col: $n\n";
    }
}
foreach ($db->query("SELECT id, data FROM store_blocks WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $n = mb_substr_count($r['data'] ?? '', $find);
    $leftover += $n;
    if ($n > 0) echo "  ⚠️ store_blocks id={$r['id']}: $n\n";
}
foreach ($db->query("SELECT id, content FROM testimonials WHERE client_id=$cid")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $n = mb_substr_count($r['content'] ?? '', $find);
    $leftover += $n;
    if ($n > 0) echo "  ⚠️ testimonial id={$r['id']}: $n\n";
}

if ($leftover === 0) echo "✅ 剩餘 0 處（全部 32 處 → 0）\n";
else echo "⚠️ 還有 {$leftover} 處沒清\n";

echo "\n品牌名統一為「洪廚喜宴」完成。\n";
echo "未動：fb_page_url（FB 那邊的 page 名）/ slug hongchu / 官網拼音。\n";
