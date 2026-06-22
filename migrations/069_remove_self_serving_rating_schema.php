<?php
// migrations/069_remove_self_serving_rating_schema.php
// 修 GSC 警告「必須指定 ratingCount 或 reviewCount」
// 影響 2 客戶（landing_extra_content 內 inline JSON-LD 含 aggregateRating
// 但只有 ratingValue + bestRating，缺 ratingCount/reviewCount）：
//   - id=199 cosmetic (名緻美顏SPA生活舘) - 4.9 星
//   - id=??? oastpork (鉄人燒肉) - 4.8 星
//
// 按 CLAUDE.md 原則「對 Google 宣稱星等屬 self-serving，不放 schema」
// 整段刪 aggregateRating（保留其他 LocalBusiness/DaySpa/Restaurant schema）
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/069_remove_self_serving_rating_schema.php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

echo "=== Migration 069: 砍 self-serving aggregateRating schema ===\n\n";

$targets = $db->query("
    SELECT id, slug, brand_name FROM clients
    WHERE landing_extra_content LIKE '%aggregateRating%'
      AND is_active = 1
")->fetchAll();

echo "影響客戶數：" . count($targets) . "\n";
foreach ($targets as $c) printf("  - id=%d slug=%s name=%s\n", $c['id'], $c['slug'], $c['brand_name']);
echo "\n";

// regex 砍 aggregateRating 段（pretty-printed 跟 minified 兩種格式都要處理）
// 1. pretty: "aggregateRating": {\n    ...\n  },?
// 2. minified: "aggregateRating":{...},?
$patterns = [
    // pretty-printed（cosmetic）：用 multiline + greedy 但限制只到下一個 closing brace
    '/,?\s*"aggregateRating"\s*:\s*\{[^}]*\}\s*,?/s',
];

$upd = $db->prepare("UPDATE clients SET landing_extra_content = ? WHERE id = ?");

foreach ($targets as $c) {
    $before = $db->prepare("SELECT landing_extra_content FROM clients WHERE id = ?");
    $before->execute([$c['id']]);
    $content = $before->fetchColumn();
    $lenBefore = strlen($content);

    foreach ($patterns as $pat) {
        $content = preg_replace($pat, '', $content);
    }
    // 清理可能殘留的雙 comma 或 comma 緊鄰 brace
    $content = preg_replace('/,\s*,/', ',', $content);
    $content = preg_replace('/,(\s*[}\]])/', '$1', $content);

    $lenAfter = strlen($content);
    $diff = $lenBefore - $lenAfter;

    if ($diff > 0) {
        $upd->execute([$content, $c['id']]);
        printf("  ✓ %s: 刪 %d bytes（%d → %d）\n", $c['slug'], $diff, $lenBefore, $lenAfter);
    } else {
        printf("  ⚠ %s: 沒匹配到 aggregateRating（可能 regex 沒對到）\n", $c['slug']);
    }
}

echo "\n=== 驗證 ===\n";
$still = (int)$db->query("SELECT COUNT(*) FROM clients WHERE landing_extra_content LIKE '%aggregateRating%' AND is_active=1")->fetchColumn();
echo "仍含 aggregateRating 的客戶：$still 個\n";

echo "\n✅ Migration 069 完成\n";
echo "後續：GSC 上「驗證修正後的項目」按一下，Google 會在 2-7 天內重新爬\n";
