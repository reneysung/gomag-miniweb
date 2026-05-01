<?php
// admin/upload_handler.php — TinyMCE 圖片上傳接收器
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'no file']);
    exit;
}

$file = $_FILES['file'];

// 驗證
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
if (!in_array($file['type'], $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => '不允許的檔案類型']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => '檔案超過 5MB']);
    exit;
}

// 存到 uploads/content/{client_id}/
$clientId = getCurrentClientId() ?? 0;
$dir = __DIR__ . '/../uploads/content/' . $clientId;
if (!is_dir($dir)) @mkdir($dir, 0755, true);

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$ext = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? $ext : 'jpg';
$fname = uniqid('img_') . '.' . $ext;
$dest = $dir . '/' . $fname;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => '存檔失敗']);
    exit;
}

// 回傳 URL（TinyMCE 標準格式）
$url = BASE_URL . '/uploads/content/' . $clientId . '/' . $fname;
echo json_encode(['location' => $url]);
