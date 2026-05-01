<?php
/**
 * upload.php — 客戶圖片批次上傳腳本
 *
 * 用法：php upload.php --client=ch --source=/path/to/客戶資料夾 [--dry-run] [--force]
 *
 * --client    客戶 slug（如 ch, xusen）
 * --source    圖片來源資料夾路徑
 * --dry-run   只掃描不實際上傳
 * --force     覆蓋已存在的圖片
 */

require_once __DIR__ . '/../../../includes/config.php';

// 解析參數
$opts = getopt('', ['client:', 'source:', 'dry-run', 'force']);
$slug      = $opts['client'] ?? '';
$sourceDir = rtrim($opts['source'] ?? '', '/');
$dryRun    = isset($opts['dry-run']);
$force     = isset($opts['force']);

if (!$slug || !$sourceDir) {
    die("用法：php upload.php --client=<slug> --source=<path> [--dry-run] [--force]\n");
}

if (!is_dir($sourceDir)) {
    die("來源資料夾不存在：{$sourceDir}\n");
}

$db = getDB();
$uploadBase = __DIR__ . '/../../../uploads/';

// 查詢客戶
$stmt = $db->prepare('SELECT id, slug, brand_name, logo_path, hero_image_path FROM clients WHERE slug=? OR subdomain=?');
$stmt->execute([$slug, $slug]);
$client = $stmt->fetch();
if (!$client) die("找不到客戶：{$slug}\n");

$cid = (int)$client['id'];
echo "🏢 客戶：{$client['brand_name']}（ID={$cid}, slug={$client['slug']}）\n";
echo $dryRun ? "📋 模擬模式（不會實際上傳）\n" : "🚀 正式上傳模式\n";
echo "────────────────────────────\n";

$results = ['success' => 0, 'skip' => 0, 'fail' => 0];

// ── Logo ──────────────────────────────────────────
$logoDir = $sourceDir . '/logo';
if (is_dir($logoDir)) {
    $imgs = glob($logoDir . '/*.{jpg,jpeg,png,webp,svg}', GLOB_BRACE);
    if ($imgs) {
        $src = $imgs[0]; // 取第一張
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $dest = "uploads/brand/logo_{$slug}.{$ext}";

        if ($client['logo_path'] && !$force) {
            echo "⏭ Logo：已存在（{$client['logo_path']}），跳過\n";
            $results['skip']++;
        } else {
            echo "📤 Logo：" . basename($src) . " → {$dest}\n";
            if (!$dryRun) {
                $fullDest = __DIR__ . '/../../../' . $dest;
                @mkdir(dirname($fullDest), 0755, true);
                if (copy($src, $fullDest)) {
                    $db->prepare('UPDATE clients SET logo_path=? WHERE id=?')->execute([$dest, $cid]);
                    echo "   ✅ 上傳成功\n";
                    $results['success']++;
                } else {
                    echo "   ❌ 複製失敗\n";
                    $results['fail']++;
                }
            } else {
                $results['success']++;
            }
        }
    }
}

// ── Hero Banner ───────────────────────────────────
$heroDir = $sourceDir . '/hero';
if (is_dir($heroDir)) {
    $imgs = glob($heroDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    if ($imgs) {
        $src = $imgs[0];
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $dest = "uploads/brand/hero_{$slug}.{$ext}";

        if ($client['hero_image_path'] && !$force) {
            echo "⏭ Hero：已存在（{$client['hero_image_path']}），跳過\n";
            $results['skip']++;
        } else {
            echo "📤 Hero：" . basename($src) . " → {$dest}\n";
            if (!$dryRun) {
                $fullDest = __DIR__ . '/../../../' . $dest;
                @mkdir(dirname($fullDest), 0755, true);
                if (copy($src, $fullDest)) {
                    $db->prepare('UPDATE clients SET hero_image_path=? WHERE id=?')->execute([$dest, $cid]);
                    echo "   ✅ 上傳成功\n";
                    $results['success']++;
                } else {
                    echo "   ❌ 複製失敗\n";
                    $results['fail']++;
                }
            } else {
                $results['success']++;
            }
        }
    }
}

// ── 服務項目圖 ────────────────────────────────────
$svcDir = $sourceDir . '/services';
if (is_dir($svcDir)) {
    $svcStmt = $db->prepare('SELECT id, name, image_path FROM services WHERE client_id=? AND is_active=1');
    $svcStmt->execute([$cid]);
    $services = $svcStmt->fetchAll();

    $imgs = glob($svcDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($imgs as $src) {
        $filename = pathinfo($src, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));

        // 模糊比對服務名稱
        $matched = null;
        foreach ($services as $svc) {
            if (mb_stripos($svc['name'], $filename) !== false || mb_stripos($filename, $svc['name']) !== false) {
                $matched = $svc;
                break;
            }
        }

        if (!$matched) {
            echo "⚠️ 服務圖：{$filename}.{$ext} 找不到對應服務，跳過\n";
            $results['skip']++;
            continue;
        }

        if ($matched['image_path'] && !$force) {
            echo "⏭ 服務圖（{$matched['name']}）：已有圖片，跳過\n";
            $results['skip']++;
            continue;
        }

        $dest = "uploads/services/svc_{$cid}_{$matched['id']}.{$ext}";
        echo "📤 服務圖（{$matched['name']}）：{$filename}.{$ext} → {$dest}\n";

        if (!$dryRun) {
            $fullDest = __DIR__ . '/../../../' . $dest;
            @mkdir(dirname($fullDest), 0755, true);
            if (copy($src, $fullDest)) {
                $db->prepare('UPDATE services SET image_path=? WHERE id=?')->execute([$dest, $matched['id']]);
                echo "   ✅ 上傳成功\n";
                $results['success']++;
            } else {
                echo "   ❌ 複製失敗\n";
                $results['fail']++;
            }
        } else {
            $results['success']++;
        }
    }
}

// ── 施工案例 ──────────────────────────────────────
$caseDir = $sourceDir . '/cases';
if (is_dir($caseDir)) {
    $imgs = glob($caseDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);

    // 分組：依檔名前綴分組 before/after
    $groups = [];
    foreach ($imgs as $src) {
        $filename = pathinfo($src, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));

        // 嘗試解析：案例名_before / 案例名_after
        if (preg_match('/^(.+?)[-_]?(before|after)[-_]?(\d*)$/iu', $filename, $m)) {
            $groupName = trim($m[1], '-_ ');
            $type = strtolower($m[2]);
            $groups[$groupName][$type][] = $src;
        } else {
            // 無法辨識的放入 unmatched
            $groups['_unmatched'][] = $src;
        }
    }

    foreach ($groups as $name => $files) {
        if ($name === '_unmatched') {
            echo "⚠️ 案例照：以下檔案無法辨識 before/after：\n";
            foreach ($files as $f) echo "   - " . basename($f) . "\n";
            $results['skip'] += count($files);
            continue;
        }

        $beforeSrc = $files['before'][0] ?? null;
        $afterSrc  = $files['after'][0]  ?? null;

        echo "📤 案例（{$name}）：";
        if ($beforeSrc) echo "before=" . basename($beforeSrc) . " ";
        if ($afterSrc)  echo "after=" . basename($afterSrc);
        echo "\n";

        if (!$dryRun) {
            $beforeDest = null;
            $afterDest  = null;

            if ($beforeSrc) {
                $ext = strtolower(pathinfo($beforeSrc, PATHINFO_EXTENSION));
                $beforeDest = "uploads/cases/case_{$cid}_{$name}_before.{$ext}";
                $full = __DIR__ . '/../../../' . $beforeDest;
                @mkdir(dirname($full), 0755, true);
                copy($beforeSrc, $full);
            }
            if ($afterSrc) {
                $ext = strtolower(pathinfo($afterSrc, PATHINFO_EXTENSION));
                $afterDest = "uploads/cases/case_{$cid}_{$name}_after.{$ext}";
                $full = __DIR__ . '/../../../' . $afterDest;
                @mkdir(dirname($full), 0755, true);
                copy($afterSrc, $full);
            }

            // 檢查 DB 是否已有此案例
            $chk = $db->prepare('SELECT id FROM cases WHERE client_id=? AND title=?');
            $chk->execute([$cid, $name]);
            $existing = $chk->fetch();

            if ($existing) {
                $updates = [];
                $params = [];
                if ($beforeDest) { $updates[] = 'before_image=?'; $params[] = $beforeDest; }
                if ($afterDest)  { $updates[] = 'after_image=?';  $params[] = $afterDest; }
                $params[] = $existing['id'];
                $db->prepare('UPDATE cases SET ' . implode(',', $updates) . ' WHERE id=?')->execute($params);
                echo "   ✅ 更新現有案例\n";
            } else {
                $db->prepare('INSERT INTO cases (client_id, title, before_image, after_image, is_active, is_featured) VALUES (?,?,?,?,1,0)')
                   ->execute([$cid, $name, $beforeDest, $afterDest]);
                echo "   ✅ 新增案例\n";
            }
            $results['success']++;
        } else {
            $results['success']++;
        }
    }
}

// ── 結果摘要 ──────────────────────────────────────
echo "\n════════════════════════════\n";
echo "📊 上傳結果：✅ {$results['success']} 成功 ⏭ {$results['skip']} 跳過 ❌ {$results['fail']} 失敗\n";
if ($dryRun) echo "（模擬模式，未實際上傳）\n";
echo "前台預覽：" . BASE_URL . "/site/index.php?sub={$slug}\n";
