<?php
// migrations/065_clients_process_gallery.php
// 加 clients.process_gallery 欄位（JSON）— 製作過程圖庫
// 給 japan-minimal 等模板可選用；其他客戶留空不影響
//
// 同時 seed 奧喜（id=220）的 4 張製作過程圖
// 圖片來源：奧喜官網 banshin.com.tw 相片集錦「蛋糕製作過程」相簿
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/065_clients_process_gallery.php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;

echo "=== Migration 065: clients.process_gallery ===\n\n";

// ─── 1. 加 column（idempotent）───────────────────────
$col = $db->query("SHOW COLUMNS FROM clients LIKE 'process_gallery'")->fetch();
if ($col) {
    echo "✓ process_gallery 已存在，跳過 ALTER\n";
} else {
    $db->exec("ALTER TABLE clients ADD COLUMN process_gallery LONGTEXT NULL AFTER photos");
    echo "✓ 已加 clients.process_gallery (JSON LONGTEXT)\n";
}

// ─── 2. Seed 奧喜 4 張製作過程圖 ─────────────────────
// caption 只寫圖中可見的物理事實，不編造製程細節
$processGallery = json_encode([
    [
        'image'   => 'uploads/clients/oishii/oishii-process-1-syrup.jpg',
        'caption' => '麥芽糖漿',
    ],
    [
        'image'   => 'uploads/clients/oishii/oishii-process-2-bake.jpg',
        'caption' => '木盒烘焙',
    ],
    [
        'image'   => 'uploads/clients/oishii/oishii-process-3-fresh.jpg',
        'caption' => '剛出爐',
    ],
    [
        'image'   => 'uploads/clients/oishii/oishii-process-4-slices.jpg',
        'caption' => '切片',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$db->prepare("UPDATE clients SET process_gallery = ?, updated_at = NOW() WHERE id = ?")
   ->execute([$processGallery, OISHII_ID]);

echo "✓ 寫入奧喜 4 張製作過程圖\n\n";

// 驗證
$row = $db->prepare("SELECT slug, process_gallery FROM clients WHERE id = ?");
$row->execute([OISHII_ID]);
$r = $row->fetch();
echo "Slug: {$r['slug']}\n";
$pg = json_decode($r['process_gallery'], true);
echo "Gallery items:\n";
foreach ($pg as $i => $g) {
    printf("  %d. %s  →  %s\n", $i + 1, $g['caption'] ?? '', $g['image'] ?? '');
}
echo "\n✅ Migration 065 完成\n";
