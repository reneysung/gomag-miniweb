<?php
// migrations/009_seed_owner_photos.php — 為 4 demo 客戶 seed owner + photos
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (PHP_SAPI !== 'cli' && ($_GET['key'] ?? '') !== 'seed-demo-2026') { http_response_code(403); die('forbidden'); }
if (PHP_SAPI !== 'cli') header('Content-Type: text/plain; charset=utf-8');
$db = getDB();
function out(string $msg): void { echo "$msg\n"; if (PHP_SAPI !== 'cli') flush(); }

// 用既有 production uploads 的圖（已 sync 到本機）— 之後客戶自己改
$demoData = [
    1 => [
        'owner_name'  => '蘇老闆',
        'owner_intro' => '在地 8 年清潔老師傅 · 手把手品管每個案場',
        'photos'      => ['uploads/brand/img_69db954235aa82.95498875.jpg', 'uploads/brand/img_69db954235bff6.34041380.jpg'],
    ],
    3 => [
        'owner_name'  => '綝綝老師',
        'owner_intro' => '15 年美甲經驗 · 韓國取經師資 · 乙級證照講師',
        'photos'      => ['uploads/brand/img_69e1a936d732a7.82223476.jpg'],
    ],
    10 => [
        'owner_name'  => '主廚 Eric',
        'owner_intro' => '美式餐飲 12 年 · 嘉義在地分店長',
        'photos'      => ['uploads/clients/legacy/legacy_happysteakcyi.jpg'],
    ],
    18 => [
        'owner_name'  => '濱緻 老闆',
        'owner_intro' => 'XPEL 認證技師 · 親自施工 20 年 · 不外包',
        'photos'      => [
            'uploads/brand/img_69f3072142bc82.04289871.jpg',
            'uploads/clients/legacy/legacy_carbeauty2.jpg',
        ],
    ],
];

foreach ($demoData as $cid => $d) {
    $stmt = $db->prepare("UPDATE clients SET owner_name=?, owner_intro=?, photos=? WHERE id=?");
    $stmt->execute([$d['owner_name'], $d['owner_intro'], json_encode($d['photos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $cid]);
    $name = $db->prepare("SELECT brand_name FROM clients WHERE id=?");
    $name->execute([$cid]);
    out("  ✅ #$cid {$name->fetchColumn()} → owner='{$d['owner_name']}', " . count($d['photos']) . " photos");
}
out("\nDone.");
