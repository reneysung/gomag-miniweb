<?php
/**
 * migrations/004_migrate_to_blocks.php
 *
 * 把現有 services + service_faqs 表的資料，
 * 遷移成 store_blocks 的 type=service / type=faq 紀錄。
 *
 * 雙寫策略：
 *   - 舊表完全保留（不 DROP、不清資料）
 *   - 新表為主，舊表 fallback（store.php 改造後）
 *
 * 執行方式：
 *   php migrations/004_migrate_to_blocks.php
 *   或瀏覽：BASE_URL/migrations/004_migrate_to_blocks.php?key=xxx
 *
 * 冪等：重跑會 DELETE 該 client 已有的 service/faq blocks 再重灌
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/block_helpers.php';

// CLI / web 兩用
$isWeb = PHP_SAPI !== 'cli';
if ($isWeb) {
    // 防誤觸：必須帶 key
    if (($_GET['key'] ?? '') !== 'migrate-blocks-2026') {
        http_response_code(403);
        die('forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$db = getDB();

function out(string $msg): void {
    echo $msg . "\n";
    if (PHP_SAPI !== 'cli') flush();
}

out("═══ Migration 004: services + service_faqs → store_blocks ═══\n");

// ─── Step 1: services → service block ────────────────────
$services = $db->query("
    SELECT s.client_id, s.id AS service_id, s.name, s.short_desc, s.full_desc,
           s.price_text, s.icon, s.image_path, s.sort_order
    FROM services s
    WHERE s.is_active = 1
    ORDER BY s.client_id, s.sort_order
")->fetchAll();

out("Found " . count($services) . " active services.");

// 按 client 分組
$serviceByClient = [];
foreach ($services as $s) {
    $serviceByClient[$s['client_id']][] = [
        'name'       => $s['name'],
        'short_desc' => $s['short_desc'] ?: '',
        'price_text' => $s['price_text'] ?: '',
        'icon'       => $s['icon'] ?: '',
        'image'      => $s['image_path'] ?: '',
    ];
}

$serviceBlocksCreated = 0;
foreach ($serviceByClient as $clientId => $items) {
    // 移除該 client 既有 service blocks（冪等）
    $del = $db->prepare("DELETE FROM store_blocks WHERE client_id=? AND type='service'");
    $del->execute([$clientId]);

    saveStoreBlock(
        clientId: (int)$clientId,
        type: 'service',
        data: ['title' => '核心服務', 'items' => $items],
        sortOrder: 10
    );
    $serviceBlocksCreated++;
}
out("✅ Created $serviceBlocksCreated service blocks.\n");

// ─── Step 2: service_faqs → faq block ────────────────────
$faqs = $db->query("
    SELECT client_id, question, answer, sort_order
    FROM service_faqs
    ORDER BY client_id, sort_order
")->fetchAll();

out("Found " . count($faqs) . " FAQs.");

$faqByClient = [];
foreach ($faqs as $f) {
    $faqByClient[$f['client_id']][] = [
        'q' => $f['question'],
        'a' => $f['answer'],
    ];
}

$faqBlocksCreated = 0;
foreach ($faqByClient as $clientId => $items) {
    $del = $db->prepare("DELETE FROM store_blocks WHERE client_id=? AND type='faq'");
    $del->execute([$clientId]);

    saveStoreBlock(
        clientId: (int)$clientId,
        type: 'faq',
        data: ['title' => '常見問題', 'items' => $items],
        sortOrder: 50
    );
    $faqBlocksCreated++;
}
out("✅ Created $faqBlocksCreated faq blocks.\n");

// ─── Step 3: cases → portfolio block（如果有資料）────────
$cases = $db->query("
    SELECT client_id, title, description, location, before_image, after_image, sort_order
    FROM cases
    WHERE is_active = 1
    ORDER BY client_id, sort_order
")->fetchAll();

out("Found " . count($cases) . " cases.");

$caseByClient = [];
foreach ($cases as $c) {
    // 用 after_image 當主圖；before_image 放 desc
    $img = $c['after_image'] ?: $c['before_image'];
    $caseByClient[$c['client_id']][] = [
        'image' => $img ?: '',
        'title' => $c['title'],
        'desc'  => $c['description'] ?: ($c['location'] ? "📍 {$c['location']}" : ''),
        'tags'  => $c['location'] ? [$c['location']] : [],
    ];
}

$portfolioBlocksCreated = 0;
foreach ($caseByClient as $clientId => $items) {
    $del = $db->prepare("DELETE FROM store_blocks WHERE client_id=? AND type='portfolio'");
    $del->execute([$clientId]);

    saveStoreBlock(
        clientId: (int)$clientId,
        type: 'portfolio',
        data: ['title' => '作品集', 'layout' => 'grid', 'items' => $items],
        sortOrder: 30
    );
    $portfolioBlocksCreated++;
}
out("✅ Created $portfolioBlocksCreated portfolio blocks.\n");

// ─── Summary ─────────────────────────────────────────────
$total = $db->query("SELECT COUNT(*) FROM store_blocks")->fetchColumn();
out("═══════════════════════════════════════════════════════");
out("Migration complete. store_blocks 總筆數: $total");
out("─ service blocks: " . $db->query("SELECT COUNT(*) FROM store_blocks WHERE type='service'")->fetchColumn());
out("─ menu blocks:    " . $db->query("SELECT COUNT(*) FROM store_blocks WHERE type='menu'")->fetchColumn());
out("─ portfolio blocks:" . $db->query("SELECT COUNT(*) FROM store_blocks WHERE type='portfolio'")->fetchColumn());
out("─ pricing blocks: " . $db->query("SELECT COUNT(*) FROM store_blocks WHERE type='pricing'")->fetchColumn());
out("─ faq blocks:     " . $db->query("SELECT COUNT(*) FROM store_blocks WHERE type='faq'")->fetchColumn());
