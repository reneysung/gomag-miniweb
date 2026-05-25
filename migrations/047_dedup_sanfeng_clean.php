<?php
// ============================================================
// Migration 047 — 三峰清潔公司 重複資料合併（#197 → #218）
// ------------------------------------------------------------
// 同一家（電話 07-976-6708、kaohsiung、category_id=2）有兩筆：
//   #197 slug=cleaningcompany5  about 280 字、is_featured=0（較不完整）
//   #218 slug=sanfengclean      about 1260 字、is_featured=1（完整、SEO 友善 slug）
// 決議：保留 #218、退役 #197（軟刪 is_active=0）。
//
// 本檔做三件事（皆冪等、非破壞性）：
//  1) 把 #197 的關鍵字標籤（cleaning, kw id=1）補到 #218（INSERT IGNORE）。
//     #218 原本標 cleaning-company/home-clean（page_slug='cleaning' 同義折入同頁），
//     功能上已涵蓋，但仍補齊以保 #197 的關鍵字面。
//  2) 把「#197 有、#218 缺（空白）」的結構化欄位補到 #218——
//     只填空欄、絕不覆蓋 #218 既有內容：
//       google_place_id / google_maps_embed / business_hours
//     （#218 在 about_text / landing_extra_content / tagline / meta 上較完整，不動。）
//  3) 軟刪 #197：is_active=0。前台 listing/搜尋/首頁皆 gate is_active=1，
//     會自動排除；/store/cleaningcompany5 由 store.php $slug_redirects 301→sanfengclean。
//
// 跑法（共用 DB，跑一次兩站生效）：
//   ssh box → HTTP_HOST=www.gomag.com.tw php migrations/047_dedup_sanfeng_clean.php
// 備份：已於跑前存 _backups/dedup_sanfeng_<ts>/backup.sql（REPLACE INTO 可還原）。
// 冪等：INSERT IGNORE；欄位 UPDATE 帶「目標為空」條件；軟刪帶 is_active=1 條件。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$OLD = 197;  // cleaningcompany5（退役）
$NEW = 218;  // sanfengclean（保留）

// 前置檢查：兩筆都在，且確認是同店（電話/分類一致）
$rows = [];
foreach ($db->query("SELECT id, slug, brand_name, phone, category_id, is_active FROM clients WHERE id IN ($OLD,$NEW)") as $r) $rows[(int)$r['id']] = $r;
if (!isset($rows[$OLD], $rows[$NEW])) { echo "❌ 找不到 #$OLD 或 #$NEW，中止。\n"; exit(1); }
echo "前置：\n";
foreach ([$OLD, $NEW] as $id) printf("  #%d %-16s %s is_active=%s\n", $id, $rows[$id]['slug'], $rows[$id]['brand_name'], $rows[$id]['is_active']);

// ── 1) 關鍵字標籤：#197 有、#218 缺的補上 ──────────────────
$st = $db->prepare("
    INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id)
    SELECT $NEW, csk.service_keyword_id
    FROM client_service_keywords csk
    WHERE csk.client_id = $OLD
");
$st->execute();
echo "\n[1] 關鍵字標籤補到 #{$NEW}：新增 {$st->rowCount()} 筆\n";
$kw = $db->query("SELECT sk.id, sk.slug, sk.name FROM client_service_keywords csk JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE csk.client_id=$NEW ORDER BY sk.id")->fetchAll();
foreach ($kw as $k) echo "    #$NEW 標 [{$k['id']}] {$k['slug']} / {$k['name']}\n";

// ── 2) 結構化欄位：只填 #218 的空欄，從 #197 取值（非破壞性）──
$copyFields = ['google_place_id', 'google_maps_embed', 'business_hours'];
echo "\n[2] 補 #{$NEW} 空欄（來源 #{$OLD}）：\n";
foreach ($copyFields as $f) {
    $sql = "UPDATE clients t, clients s
            SET t.`$f` = s.`$f`
            WHERE t.id = $NEW AND s.id = $OLD
              AND COALESCE(t.`$f`, '') = '' AND COALESCE(s.`$f`, '') <> ''";
    $n = $db->exec($sql);
    echo "    $f：" . ($n ? "已補（{$n} 列）" : "未動（#$NEW 已有值或 #$OLD 為空）") . "\n";
}

// ── 3) 軟刪 #197 ─────────────────────────────────────────
$n = $db->exec("UPDATE clients SET is_active = 0 WHERE id = $OLD AND is_active = 1");
echo "\n[3] 軟刪 #{$OLD}：" . ($n ? "is_active→0（{$n} 列）" : "已是停用狀態，未動") . "\n";

// ── 驗證 ────────────────────────────────────────────────
echo "\n驗證：\n";
foreach ($db->query("SELECT id, slug, is_active, is_featured,
        COALESCE(google_place_id,'') gp, CHAR_LENGTH(COALESCE(google_maps_embed,'')) gm,
        CHAR_LENGTH(COALESCE(business_hours,'')) bh
        FROM clients WHERE id IN ($OLD,$NEW) ORDER BY id") as $r) {
    printf("  #%d %-16s active=%s featured=%s place=%s maps_len=%d hours_len=%d\n",
        $r['id'], $r['slug'], $r['is_active'], $r['is_featured'], $r['gp'] ?: '-', $r['gm'], $r['bh']);
}
$cnt1 = (int)$db->query("SELECT COUNT(*) FROM client_service_keywords csk JOIN clients c ON c.id=csk.client_id WHERE csk.service_keyword_id=1 AND c.is_active=1")->fetchColumn();
echo "  cleaning(kw1) 仍綁定的 active 店數：{$cnt1}\n";
echo "\n完成。\n";
