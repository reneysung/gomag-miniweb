<?php
/**
 * tools/generate_favicons.php
 * 將 mini-site 客戶的 logo 縮成 192×192 PNG 方形 favicon。
 * 用法（在 prod / staging box 上跑）：
 *   cd /home/u331306067/domains/gomag.com.tw/public_html
 *   HTTP_HOST=www.gomag.com.tw php tools/generate_favicons.php [--dry-run] [--force]
 *
 * 規則：
 *   - 只處理 has_minisite=1 AND is_active=1 客戶
 *   - logo_path 為空 → 跳過（log）
 *   - 已存在 favicon-{slug}.png → 跳過（除非 --force）
 *   - center-crop 成方形 → resize 192×192 → 存成 PNG
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'www.gomag.com.tw';
chdir(__DIR__ . '/..');
require_once 'includes/config.php';

$opts = array_flip(array_slice($argv, 1));
$DRY = isset($opts['--dry-run']);
$FORCE = isset($opts['--force']);
$TARGET = 192;  // Google 推薦 favicon ≥ 48×48；192×192 也 cover apple-touch-icon

$db = getDB();
$rows = $db->query("SELECT id, slug, subdomain, brand_name, logo_path FROM clients WHERE is_active=1 AND has_minisite=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

echo "── favicon batch generator ──\n";
echo "TARGET: {$TARGET}×{$TARGET} PNG\n";
echo "MODE: " . ($DRY ? "DRY-RUN" : "WRITE") . ($FORCE ? " + FORCE" : "") . "\n";
echo "客戶總數（has_minisite=1）: " . count($rows) . "\n\n";

$skip_no_logo = $skip_existed = $made = $err = 0;

foreach ($rows as $c) {
    $slug = $c['subdomain'] ?: $c['slug'];
    $brand = $c['brand_name'];
    $logo = $c['logo_path'];

    if (!$logo) {
        echo "⏭  [{$c['id']}] {$slug} ({$brand}) — 無 logo_path，跳過\n";
        $skip_no_logo++;
        continue;
    }

    $src = __DIR__ . '/../' . $logo;
    if (!is_file($src)) {
        echo "❌ [{$c['id']}] {$slug} — logo file 不存在: {$logo}\n";
        $err++;
        continue;
    }

    $dstRel = "uploads/brand/favicon-{$slug}.png";
    $dst = __DIR__ . '/../' . $dstRel;

    if (is_file($dst) && !$FORCE) {
        echo "⏭  [{$c['id']}] {$slug} — favicon 已存在 (--force 可覆蓋)\n";
        $skip_existed++;
        continue;
    }

    if ($DRY) {
        echo "🟡 [{$c['id']}] {$slug} — 會產 {$dstRel} (from {$logo})\n";
        $made++;
        continue;
    }

    // 用 GD 讀 source、center-crop 成方形、resize 192×192、存 PNG
    $info = @getimagesize($src);
    if (!$info) { echo "❌ [{$c['id']}] {$slug} — 無法讀圖\n"; $err++; continue; }
    [$w, $h, $type] = $info;
    $srcImg = match($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_GIF  => @imagecreatefromgif($src),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default => false,
    };
    if (!$srcImg) { echo "❌ [{$c['id']}] {$slug} — 不支援圖檔類型\n"; $err++; continue; }

    // center crop
    $side = min($w, $h);
    $sx = (int)(($w - $side) / 2);
    $sy = (int)(($h - $side) / 2);

    $dstImg = imagecreatetruecolor($TARGET, $TARGET);
    imagealphablending($dstImg, false);
    imagesavealpha($dstImg, true);
    $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
    imagefilledrectangle($dstImg, 0, 0, $TARGET, $TARGET, $transparent);

    imagecopyresampled($dstImg, $srcImg, 0, 0, $sx, $sy, $TARGET, $TARGET, $side, $side);

    if (!is_dir(dirname($dst))) @mkdir(dirname($dst), 0755, true);
    if (imagepng($dstImg, $dst, 6)) {
        @chmod($dst, 0644);
        echo "✅ [{$c['id']}] {$slug} — 產出 {$dstRel} (從 {$w}×{$h} crop+resize 到 {$TARGET}²)\n";
        $made++;
    } else {
        echo "❌ [{$c['id']}] {$slug} — 寫檔失敗\n";
        $err++;
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);
}

echo "\n── 結果 ──\n";
echo "新增/覆蓋: {$made}\n";
echo "跳過(無 logo): {$skip_no_logo}\n";
echo "跳過(已存在): {$skip_existed}\n";
echo "錯誤: {$err}\n";
