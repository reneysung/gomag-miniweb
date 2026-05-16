<?php
// Migration 014 — cases 表加 slug 欄位 + 回填現有資料
// 旭森風格 /cases/detail/{slug}：slug 從 title 衍生（中文 URL 形式）

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDB();

// 1. 建欄位（IF NOT EXISTS 不能用，故 try/catch）
try {
    $db->exec("ALTER TABLE cases
      ADD COLUMN slug VARCHAR(200) NULL COMMENT '案例 URL slug（如：台南東區獨棟住宅）' AFTER title,
      ADD INDEX idx_cases_slug (client_id, slug)");
    echo "✓ cases.slug 欄位已建立\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "  cases.slug 已存在，跳過建欄位\n";
    } else {
        throw $e;
    }
}

// 2. 回填 slug（從 title 衍生）— 跟 services 同邏輯
$rows = $db->query("SELECT id, client_id, title, slug FROM cases WHERE is_active=1 AND (slug IS NULL OR slug='')")->fetchAll(PDO::FETCH_ASSOC);
echo "找到 " . count($rows) . " 個 case 需要回填 slug\n\n";

$upd = $db->prepare("UPDATE cases SET slug=? WHERE id=?");
$existSlugs = [];

foreach ($rows as $r) {
    $title = trim($r['title']);
    if (!$title) continue;

    // slug 衍生（移 ・ · 標點等）
    $base = preg_replace('/[\s\x{3000}・·、，,。．\.（）()【】「」"\'#&\/\\\\?]+/u', '-', $title);
    $base = preg_replace('/-+/', '-', $base);
    $base = trim($base, '-');
    if (!$base) $base = 'case-' . $r['id'];

    // 同 client 衝突檢查
    $candidate = $base;
    $n = 2;
    $check = $db->prepare("SELECT id FROM cases WHERE client_id=? AND slug=? AND id!=? LIMIT 1");
    while (true) {
        if (isset($existSlugs[$r['client_id'] . '|' . $candidate])) {
            $candidate = $base . '-' . $n++;
            continue;
        }
        $check->execute([$r['client_id'], $candidate, $r['id']]);
        if (!$check->fetch()) break;
        $candidate = $base . '-' . $n++;
    }
    $existSlugs[$r['client_id'] . '|' . $candidate] = true;

    $upd->execute([$candidate, $r['id']]);
    echo "  id={$r['id']} client_id={$r['client_id']} title='{$title}' → slug='{$candidate}'\n";
}

echo "\n完成。\n";
