<?php
// admin/find_place.php — AJAX：依名稱+地址找 Google place_id
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/google_reviews.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');

if (!$name) {
    echo json_encode(['ok' => false, 'msg' => '缺少店名']);
    exit;
}

$apiKey = getPlatformSetting('google_maps_api_key', '');
if (!$apiKey) {
    echo json_encode(['ok' => false, 'msg' => '尚未設定 Google Maps API key（去平台設定填入）']);
    exit;
}

$placeId = findGooglePlaceId($name, $address);

if (!$placeId) {
    echo json_encode(['ok' => false, 'msg' => '找不到對應的 Google 店家']);
    exit;
}

echo json_encode(['ok' => true, 'place_id' => $placeId]);
