<?php
// migrations/064_oishii_audit_real_content.php
// 把奧喜頁面所有編造的內容換成 banshin.com.tw 官網 + 公開部落客評論的真實素材
// 觸發：用戶要求「不能無中生有」
//
// 動作：
//   1. hero_stats：2022→2020（正確創立年）、刪除虛構的「60分」「24h」、改成可佐證的真實值
//   2. about_text：改寫成完全基於 banshin 官網「關於我們」的事實
//   3. opening_hours_json：補上官網 09:00-19:00
//   4. testimonials：刪除 3 條假評論（蔡/林/陳），改入 3 條真實部落客 quote（含 source_url）
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/064_oishii_audit_real_content.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;

echo "=== Migration 064: 奧喜真實內容（取代編造）===\n\n";

// ─── 1. hero_stats：四個全用 banshin 可佐證的事實 ──────────
$heroStats = json_encode([
    ['value' => '2020',  'label' => '創立年份'],     // 真：2020-12-05 遷店
    ['value' => '4 種',   'label' => '純粹原料'],     // 真：雞蛋、糖、麵粉、麥芽
    ['value' => '0',     'label' => '化學添加'],     // 真：banshin 強調「不添加化學馨香」
    ['value' => '5',     'label' => '規格選擇'],     // 真：5/8/12/16/24 片
], JSON_UNESCAPED_UNICODE);

// ─── 2. about_text：完全基於 banshin /about/ 頁的真實內容 ───
$aboutText = <<<HTML
<p>奧喜長崎蛋糕的「奧喜」，<strong>取自日文「おいしい」（好吃）</strong>，寓意「奧妙手藝傳千里、喜悅馨品頌萬戶」。</p>

<p>2020 年 12 月遷至台中市北屯區松竹路三段，秉持<strong>「誠信為本・踏實為重」</strong>的企業精神，以最單純的<strong>雞蛋、糖、麵粉、麥芽</strong>手工烘焙日式長崎蛋糕。</p>

<p>不採花俏虛飄之作法，<strong>更無添加任何有害身心健康之化學馨香成份</strong>。食材取其優質、價格取其平實，期盼「富者不嫌劣，貧者不厭貴」。</p>

<p style="font-size:.95em;color:var(--jm-muted);font-style:italic;">「天下無味美、更增一奇絕」 — 奧喜本舖</p>
HTML;

// ─── 3. opening_hours_json：官網 09:00-19:00 全週 ─────────
$openingHours = json_encode([
    ['day' => '週一', 'hours' => '09:00 – 19:00'],
    ['day' => '週二', 'hours' => '09:00 – 19:00'],
    ['day' => '週三', 'hours' => '09:00 – 19:00'],
    ['day' => '週四', 'hours' => '09:00 – 19:00'],
    ['day' => '週五', 'hours' => '09:00 – 19:00'],
    ['day' => '週六', 'hours' => '09:00 – 19:00'],
    ['day' => '週日', 'hours' => '09:00 – 19:00'],
], JSON_UNESCAPED_UNICODE);

$db->prepare("UPDATE clients SET hero_stats=?, about_text=?, opening_hours_json=?, updated_at=NOW() WHERE id=?")
   ->execute([$heroStats, $aboutText, $openingHours, OISHII_ID]);
echo "✅ 更新 hero_stats / about_text / opening_hours_json\n\n";

// ─── 4. 刪假評論、加 3 條真實部落客 quote ─────────────────
$deleted = $db->prepare("DELETE FROM testimonials WHERE client_id = ? AND source = 'demo'")->execute([OISHII_ID]);
$delCount = $db->prepare("SELECT ROW_COUNT()")->execute() ? null : null;
echo "✅ 刪除舊 demo testimonials\n";

// 真實部落客評論（已標 source='blog' + source_url，前台會顯示部落客分享）
$realReviews = [
    [
        'name'    => '飛天璇',
        'rating'  => 5,
        'content' => '吃起來很溼潤軟綿，而且蛋香氣很濃郁，和日本福砂屋的口感有點像。簡單耐吃的好味道，一條蛋糕三種吃法 — 熱吃、冷吃、放置 24 小時後再吃都不一樣。',
        'url'     => 'https://flyblog.cc/banshin-cake/',
    ],
    [
        'name'    => 'VIVIYU小世界',
        'rating'  => 5,
        'content' => '用來送禮也好看又大方，口感相當細緻。吃起來口感帶著濕潤不會太乾，綿密細緻。最推薦冰過後再品嚐。比起總店第二市場大排長隊，松竹路新店門口可停車方便許多。',
        'url'     => 'https://www.viviyu.com/archives/35364',
    ],
    [
        'name'    => '海綿飽飽',
        'rating'  => 5,
        'content' => '打開外盒的香氣好迷人，用鼻子聞就覺得很好吃。沒有煩人規矩，不用預約、還可現取貨，服務親切超加分。金色禮盒配紅色外帶袋，質感超好。',
        'url'     => 'https://hamibobo.tw/banshin/',
    ],
];

$stmt = $db->prepare("INSERT INTO testimonials (client_id, reviewer_name, rating, content, source, source_url, sort_order, is_active) VALUES (?, ?, ?, ?, 'blog', ?, ?, 1)");
$i = 1;
foreach ($realReviews as $r) {
    $stmt->execute([OISHII_ID, $r['name'], $r['rating'], $r['content'], $r['url'], $i++]);
}
echo "✅ 寫入 3 條真實部落客 quote（source='blog'，含 source_url）\n\n";

// ─── 驗證 ────────────────────────────────────────────────
echo "=== 驗證 ===\n";
$row = $db->prepare("SELECT brand_name, hero_stats, LEFT(about_text,80) AS about_excerpt, opening_hours_json FROM clients WHERE id = ?");
$row->execute([OISHII_ID]);
print_r($row->fetch());

echo "\n--- 評價 ---\n";
$tStmt = $db->prepare("SELECT reviewer_name, rating, source, source_url FROM testimonials WHERE client_id = ? ORDER BY sort_order");
$tStmt->execute([OISHII_ID]);
foreach ($tStmt->fetchAll() as $t) {
    printf("  %-15s ★%d  [%s] %s\n", $t['reviewer_name'], $t['rating'], $t['source'], $t['source_url']);
}

echo "\n✅ Migration 064 完成\n";
