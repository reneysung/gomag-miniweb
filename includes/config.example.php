<?php
// includes/config.example.php
// ─── 複製此檔為 includes/config.php（已被 .gitignore 排除）───
//
// 安全模式（loader）：staging/prod 的 DB 密碼放在 webroot 外
//   /home/{user}/domains/{domain}/gomag-secrets.php
//     <?php $GOMAG_SECRET_DB_PASS = '...';
//   mode 600
// 本檔不含明文密碼。

declare(strict_types=1);

// ── 環境偵測 ─────────────────────────────────────────────────
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('IS_LOCAL',   str_contains($_host, 'localhost') || str_contains($_host, '127.0.0.1'));
define('IS_STAGING', str_contains($_host, 'hostingersite.com'));
define('IS_PROD',    !IS_LOCAL && !IS_STAGING);

define('DB_CHARSET', 'utf8mb4');

// ── 載入 webroot 外秘密檔（staging/prod 必要）─────────────────
$_secretFile = __DIR__ . '/../../gomag-secrets.php';
$GOMAG_SECRET_DB_PASS = null;
if (is_file($_secretFile)) {
    include $_secretFile;
}

if (IS_LOCAL) {
    // ── 本機 MAMP ─────────────────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_PORT', '8889');
    define('DB_NAME', 'miniweb');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');           // ← 換成你的本機密碼
    define('BASE_URL',  'http://localhost:8888/miniweb');
    define('ADMIN_URL', 'http://localhost:8888/miniweb/admin');
} else {
    // ── Staging / Production（共用 Hostinger MySQL）─────────
    if (empty($GOMAG_SECRET_DB_PASS)) {
        http_response_code(500);
        die('設定錯誤：找不到 webroot 外的 gomag-secrets.php');
    }
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'YOUR_DB_NAME');           // ← 從 Hostinger hPanel 取得
    define('DB_USER', 'YOUR_DB_USER');           // ← 同上
    define('DB_PASS', $GOMAG_SECRET_DB_PASS);    // ← 從 webroot 外 secret 檔讀
    if (IS_STAGING) {
        define('BASE_URL',  'https://' . $_host);
        define('ADMIN_URL', 'https://' . $_host . '/admin');
    } else {
        define('BASE_URL',  'https://www.gomag.com.tw');
        define('ADMIN_URL', 'https://www.gomag.com.tw/admin');
    }
}

// 子網域 mini-site 域名
define('MINISITE_DOMAIN', 'gomag.com.tw');

// 清理 loader 暫存變數
unset($GOMAG_SECRET_DB_PASS, $_secretFile);

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
