<?php
// track_click.php — 店家頁按鈕點擊記錄（navigator.sendBeacon 送進來，fire-and-forget）
// 只記白名單動作；同一人 10 秒內同店同動作只算一次；機器人不記。後台報表：admin/pages/click_stats.php
require_once __DIR__ . '/includes/config.php';

header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }

$actions = ['phone', 'line', 'map', 'coupon', 'social', 'order', 'website', 'minisite'];
$cid    = (int)($_POST['cid'] ?? 0);
$action = (string)($_POST['a'] ?? '');
$path   = mb_substr((string)($_POST['p'] ?? ''), 0, 255);
$source = ($_POST['s'] ?? '') === 'mini' ? 'mini' : 'store';   // store＝店家頁、mini＝小官網
$ua     = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

if ($cid <= 0 || !in_array($action, $actions, true) || $ua === ''
    || preg_match('/bot|crawl|spider|slurp|preview|headless/i', $ua)) {
    http_response_code(204); exit;
}

$ipHash = substr(sha1(($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d')), 0, 10);
$db = getDB();

$ok = $db->prepare("SELECT 1 FROM clients WHERE id = ? AND is_active = 1");
$ok->execute([$cid]);
if (!$ok->fetchColumn()) { http_response_code(204); exit; }

$dup = $db->prepare("SELECT 1 FROM click_events
                     WHERE client_id = ? AND action = ? AND source = ? AND ip_hash = ?
                       AND created_at > (NOW() - INTERVAL 10 SECOND) LIMIT 1");
$dup->execute([$cid, $action, $source, $ipHash]);
if (!$dup->fetchColumn()) {
    $db->prepare("INSERT INTO click_events (client_id, action, source, page_path, ip_hash) VALUES (?, ?, ?, ?, ?)")
       ->execute([$cid, $action, $source, $path, $ipHash]);
}
http_response_code(204);
