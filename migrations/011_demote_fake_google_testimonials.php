<?php
/**
 * migrations/011_demote_fake_google_testimonials.php
 *
 * 把 testimonials 表裡 source='google' 的資料降級成 is_active=0 + source='demo'。
 *
 * 背景：_build_client 建站時會塞 3 筆假評價並標 source='google'，
 *       seo_schema.php 會把它輸出成 publisher=Google 的 Review JSON-LD
 *       （假評價偽稱來自 Google → 違反 Google 評論政策）。
 *       真實 Google 評價走 store.php 的 live API、不存進 testimonials 表，
 *       所以表裡所有 source='google' 都是假種子，可安全降級。
 *
 * 非破壞性：只改 is_active / source，不 DELETE，可還原。
 * 冪等：重跑無副作用。
 *
 * 跑法：php migrations/011_demote_fake_google_testimonials.php
 *       或 https://.../migrations/011_demote_fake_google_testimonials.php?key=demote-google-2026
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

if (PHP_SAPI !== 'cli') {
    if (($_GET['key'] ?? '') !== 'demote-google-2026') { http_response_code(403); die('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

$db = getDB();
function out(string $msg): void { echo "$msg\n"; if (PHP_SAPI !== 'cli') flush(); }

out("═══ Migration 011: 降級假 Google 評價 ═══\n");

// 1) 先列出將被影響的資料（確認用）
$sel = $db->query("
    SELECT t.id, t.client_id, c.brand_name, t.reviewer_name, t.rating, t.is_active,
           LEFT(t.content, 30) AS snippet
    FROM testimonials t
    LEFT JOIN clients c ON c.id = t.client_id
    WHERE t.source = 'google'
    ORDER BY t.client_id, t.id
");
$rows = $sel->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    out("沒有 source='google' 的 testimonials，無需處理。");
    return;
}

out("找到 " . count($rows) . " 筆 source='google' testimonials（將降級為 is_active=0, source='demo'）：");
foreach ($rows as $r) {
    out(sprintf(
        "  #%d  client %d %s  ★%d  active=%d  「%s…」",
        $r['id'], $r['client_id'], (string)$r['brand_name'],
        (int)$r['rating'], (int)$r['is_active'], (string)$r['snippet']
    ));
}

// 2) 降級
$upd = $db->prepare("UPDATE testimonials SET is_active = 0, source = 'demo' WHERE source = 'google'");
$upd->execute();

out("\n✅ 已降級 " . $upd->rowCount() . " 筆。");
out("（真實 Google 評價在 store.php 走 live API，未受影響。）");
