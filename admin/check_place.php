<?php
// admin/check_place.php — AJAX：給一個 place_id，回傳它實際抓到的 Google 店名/星等（後台防呆預覽）
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/google_reviews.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$pid = trim($_POST['place_id'] ?? '');
if ($pid === '') { echo json_encode(['ok' => false, 'msg' => '沒有 place_id']); exit; }

$apiKey = getPlatformSetting('google_maps_api_key', '');
if (!$apiKey) { echo json_encode(['ok' => false, 'msg' => '尚未設定 Google Maps API key']); exit; }

$b = getGooglePlaceBasic($pid);
if (!$b || $b['name'] === '') { echo json_encode(['ok' => false, 'msg' => '這個 place_id 查不到店家（可能失效或錯誤）']); exit; }

echo json_encode([
    'ok'      => true,
    'name'    => $b['name'],
    'rating'  => $b['rating'],
    'cnt'     => $b['cnt'],
    'address' => $b['address'],
], JSON_UNESCAPED_UNICODE);
