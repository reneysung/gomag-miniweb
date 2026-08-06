<?php
/**
 * coupon_image.php — 收 client 端 canvas 產的優惠券 PNG，重新編碼存成「真實網址」的圖檔，回傳 URL。
 *
 * 為什麼：安卓瀏覽器對 data:image URI 長按常不給「儲存圖片」，也擋 <a download>。
 *   但對「真實 http 網址的圖」長按就能存。所以把 canvas 圖存成實體檔、給真網址。
 *
 * 安全：只收 PNG（檢查簽章）、限大小、用 GD 重新編碼消毒（去掉任何夾帶）、存進 uploads/coupons/（非執行）。
 */
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo json_encode(['ok'=>0,'msg'=>'POST only']); exit; }

$raw = (string)($_POST['png'] ?? '');
$raw = preg_replace('#^data:image/png;base64,#', '', $raw);
$bin = base64_decode($raw, true);
if ($bin === false || strlen($bin) < 100)      { echo json_encode(['ok'=>0,'msg'=>'bad data']); exit; }
if (strlen($bin) > 2000000)                    { echo json_encode(['ok'=>0,'msg'=>'too big']); exit; }
if (substr($bin, 0, 8) !== "\x89PNG\r\n\x1a\n"){ echo json_encode(['ok'=>0,'msg'=>'not png']); exit; }

if (!function_exists('imagecreatefromstring')) { echo json_encode(['ok'=>0,'msg'=>'no gd']); exit; }
$img = @imagecreatefromstring($bin);
if (!$img) { echo json_encode(['ok'=>0,'msg'=>'decode fail']); exit; }
$w = imagesx($img); $h = imagesy($img);
if ($w < 100 || $h < 100 || $w > 2000 || $h > 3000) { imagedestroy($img); echo json_encode(['ok'=>0,'msg'=>'bad size']); exit; }

$dir = __DIR__ . '/uploads/coupons';
if (!is_dir($dir)) @mkdir($dir, 0755, true);

// 清掉 2 天前的舊券圖，避免堆積
foreach (glob($dir . '/*.png') ?: [] as $old) {
    if (@filemtime($old) < time() - 2 * 86400) @unlink($old);
}

ob_start(); imagepng($img); $clean = ob_get_clean(); imagedestroy($img);
$name = sha1($clean . microtime(true) . mt_rand()) . '.png';
if (@file_put_contents($dir . '/' . $name, $clean) === false) { echo json_encode(['ok'=>0,'msg'=>'save fail']); exit; }

$base = (defined('IS_LOCAL') && IS_LOCAL) || (defined('IS_STAGING') && IS_STAGING)
      ? rtrim(BASE_URL, '/') : 'https://www.gomag.com.tw';
echo json_encode(['ok'=>1, 'url' => $base . '/uploads/coupons/' . $name], JSON_UNESCAPED_SLASHES);
