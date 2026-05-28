<?php
// admin-unlock.php  ─  Admin URL 隱藏：設定通行 cookie 後導向 login
// 對應 .htaccess 的 ^gomag-key-{TOKEN}/?$ rewrite
// 本檔放在 webroot（不在 /admin/ 下）以避開 /admin/* 的 404 規則

require_once __DIR__ . '/includes/config.php';

// Token 必須與 .htaccess 內的 RewriteCond 一致
const ADMIN_UNLOCK_TOKEN = 'j250267986q2';

setcookie('gomag_admin_unlock', ADMIN_UNLOCK_TOKEN, [
    'expires'  => time() + 30 * 86400,  // 30 天
    'path'     => '/',
    'secure'   => !IS_LOCAL,             // 正式站 HTTPS-only
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Location: ' . BASE_URL . '/admin/login.php');
exit;
