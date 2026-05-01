<?php
// includes/config.example.php
// ─── 複製此檔為 includes/config.php 後填入實際值 ───
// ─── config.php 已被 .gitignore 排除 ───

declare(strict_types=1);

// 環境判斷
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('IS_LOCAL',   str_contains($_host, 'localhost') || str_contains($_host, '127.0.0.1'));
define('IS_STAGING', str_contains($_host, 'hostingersite.com'));
define('IS_PROD',    !IS_LOCAL && !IS_STAGING);

define('DB_CHARSET', 'utf8mb4');

if (IS_LOCAL) {
    // ─── 本機 MAMP ─────────────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_PORT', '8889');
    define('DB_NAME', 'miniweb');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');           // ← 換成你的本機密碼
    define('BASE_URL',  'http://localhost:8888/miniweb');
    define('ADMIN_URL', 'http://localhost:8888/miniweb/admin');
} elseif (IS_STAGING) {
    // ─── Hostinger Staging ─────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'YOUR_DB_NAME');
    define('DB_USER', 'YOUR_DB_USER');
    define('DB_PASS', 'YOUR_DB_PASS');   // ← 從 Hostinger hPanel 取得
    define('BASE_URL',  'https://aqua-elephant-856571.hostingersite.com');
    define('ADMIN_URL', 'https://aqua-elephant-856571.hostingersite.com/admin');
} else {
    // ─── Production www.gomag.com.tw ──────────────────
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'YOUR_DB_NAME');
    define('DB_USER', 'YOUR_DB_USER');
    define('DB_PASS', 'YOUR_DB_PASS');   // ← 同上
    define('BASE_URL',  'https://www.gomag.com.tw');
    define('ADMIN_URL', 'https://www.gomag.com.tw/admin');
}

// PDO 連線
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
