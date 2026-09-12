<?php
// coupon_line_start.php ─ 消費者按「存到我的 LINE」→ 導去 LINE 授權
// 流程：驗 slug → 產 state 存 session → 302 到 LINE Login authorize
require_once __DIR__ . '/includes/line_coupon.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('gomag_coupon');
    session_start();
}

$slug = strtolower(trim($_GET['slug'] ?? ''));

if (!lineCouponEnabled()) { http_response_code(503); exit('優惠券 LINE 功能尚未開通'); }
if ($slug === '' || !preg_match('/^[a-z0-9_-]+$/', $slug)) { http_response_code(400); exit('參數錯誤'); }

$state = bin2hex(random_bytes(16));
$_SESSION['coupon_line_state'] = $state;
$_SESSION['coupon_line_slug']  = $slug;

header('Location: ' . lineCouponLoginUrl($slug, $state), true, 302);
exit;
