<?php
// ============================================================
// Migration 062 — 加 order_url / top_banner_html / top_banner_until
// ------------------------------------------------------------
// schema 變更（所有 207 客戶通用）：
//   clients.order_url        VARCHAR(500) — 線上訂購連結（iCHEF/官方訂購頁）
//   clients.top_banner_html  TEXT         — 頁面頂部橫條 banner HTML（可含 <a>）
//   clients.top_banner_until DATE         — banner 自動下架日期；NULL=永久
//
// 同時把 hongchu (id=221) 的：
//   1. about_text 換成自然敘事 v2（Reney 確認過不要等號式直白介紹）
//   2. order_url 填 iCHEF 訂購頁
//   3. top_banner_html 填 6/6 三立《請世界吃桌》首播宣傳
//   4. top_banner_until 設 2026-06-30（首播檔期過完自動下架）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/062_add_order_url_and_top_banner.php
// 冪等：ADD COLUMN IF NOT EXISTS（MariaDB 10.0.2+ 支援）
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ─── 1. 加欄位 ──────────────────────────────────────────────
$alters = [
    "ADD COLUMN IF NOT EXISTS order_url VARCHAR(500) DEFAULT NULL COMMENT '線上訂購/外送/iCHEF 連結' AFTER external_website_url",
    "ADD COLUMN IF NOT EXISTS top_banner_html TEXT DEFAULT NULL COMMENT '頁面頂部橫條 banner HTML' AFTER tagline",
    "ADD COLUMN IF NOT EXISTS top_banner_until DATE DEFAULT NULL COMMENT 'banner 自動下架日期；NULL=永久' AFTER top_banner_html",
];
foreach ($alters as $a) {
    try {
        $db->exec("ALTER TABLE clients {$a}");
        echo "✅ ALTER: " . substr($a, 0, 80) . "...\n";
    } catch (\Throwable $e) {
        echo "ℹ️  skip: " . $e->getMessage() . "\n";
    }
}

// ─── 2. 取 hongchu id ──────────────────────────────────────
$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "\nℹ️  hongchu client_id={$cid}\n\n";

// ─── 3. about_text v2（自然敘事，不等號式介紹）──────────
$aboutV2 = <<<HTML
<p>洪廚喜宴（洪廚囍宴），台南柳營深耕 40 年的辦桌外燴團隊。
傳統台菜辦桌升級為<strong>懷石／法式擺盤</strong>等級的精緻宴席，
魚翅、龍蝦、鮑魚、海鮮給得不手軟，霸氣大菜搭配創意呈現——
<em>讓台菜變得更吸睛、更有氣勢</em>。</p>

<p>掌勺的<strong>阿男師</strong>，是台南在地公認的「<strong>辦桌南霸天</strong>」，
憑著 25 年總鋪師經驗，2026 年隨「台灣辦桌團」登上三立
<strong>《請世界吃桌》</strong>實境節目，
把台南辦桌文化帶上雪梨歌劇院、日本熊本城、紐約時報廣場與台北 101。</p>

<p>服務範圍：<strong>婚宴喜宴／辦桌外燴／企業尾牙春酒／年菜團購／場地租借</strong>。
從台南柳營本店為據點，跨縣市承接嘉義、彰化、台中、新竹等地宴席。</p>
HTML;

// ─── 4. top_banner_html：6/6 首播宣傳 ──────────────────────
// 不外連電視台官網（避免站外導流），帶內部錨點 #booking / tel
$bannerHtml = <<<HTML
<div class="g-top-banner">
  <span class="g-top-banner-tag">📺 6/6（六）22:00 三立台灣台首播</span>
  <span class="g-top-banner-text"><strong>《請世界吃桌》</strong>阿男師領軍台灣辦桌團登上世界舞台</span>
  <a href="tel:06-6223925" class="g-top-banner-cta">立即預約 →</a>
</div>
HTML;

$upd = $db->prepare("UPDATE clients SET
    about_text       = ?,
    order_url        = ?,
    top_banner_html  = ?,
    top_banner_until = ?
    WHERE id = ?");
$upd->execute([
    $aboutV2,
    'https://shop.ichefpos.com/store/r_dS7CGE/ordering',
    $bannerHtml,
    '2026-06-30',
    $cid,
]);
echo "✅ hongchu 更新完成 ({$upd->rowCount()} row)\n";
echo "   about_text → v2 自然敘事版\n";
echo "   order_url  → iCHEF\n";
echo "   banner     → 6/6 首播 banner（至 2026-06-30）\n";

echo "\n=== 完成。記得部署 store.php（含 banner + CTA 渲染邏輯）===\n";
