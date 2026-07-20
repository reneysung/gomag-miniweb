<?php
// ============================================================
// includes/auth.php  ─  Session / 登入驗證輔助
// ============================================================

require_once __DIR__ . '/config.php';

function startAdminSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => !IS_LOCAL,  // 正式站 HTTPS-only cookie；本機 MAMP http 不強制
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function requireLogin(): void {
    startAdminSession();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    // 更新最後活動時間
    $_SESSION['last_activity'] = time();
}

function isLoggedIn(): bool {
    startAdminSession();
    return !empty($_SESSION['admin_id']);
}

function currentAdmin(): array {
    return $_SESSION['admin'] ?? [];
}

// ─── Login rate limit ───
// 同 IP 15 分鐘內失敗 5 次 → 自動鎖 15 分鐘
// 日誌：_logs/login_fail.log（TSV: ts \t ip_hash \t username \t success(0/1)）
const LOGIN_FAIL_WINDOW = 900;   // 15 分鐘
const LOGIN_FAIL_LIMIT  = 5;

function _loginGetIp(): string {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '';
    return trim(explode(',', $ip)[0]);
}

function _loginIpHash(string $ip): string {
    return $ip === '' ? '-' : substr(sha1($ip . '|' . date('Y-m-d')), 0, 10);
}

function isLoginLocked(): bool {
    $ip = _loginGetIp();
    if ($ip === '') return false;
    $ipHash = _loginIpHash($ip);

    $logFile = __DIR__ . '/../_logs/login_fail.log';
    if (!is_file($logFile)) return false;

    // 讀最後 ~32KB（最近事件）即足，避免大檔全讀
    $size = filesize($logFile);
    $offset = max(0, $size - 32768);
    $f = @fopen($logFile, 'r');
    if (!$f) return false;
    if ($offset > 0) {
        fseek($f, $offset);
        fgets($f);  // discard partial first line
    }

    $threshold = time() - LOGIN_FAIL_WINDOW;
    $fails = 0;
    while (($line = fgets($f)) !== false) {
        $parts = explode("\t", trim($line));
        if (count($parts) < 4) continue;
        $ts = strtotime($parts[0]);
        if ($ts === false || $ts < $threshold) continue;
        if ($parts[1] !== $ipHash) continue;
        if ($parts[3] === '0') $fails++;
    }
    fclose($f);

    return $fails >= LOGIN_FAIL_LIMIT;
}

function _logLoginAttempt(string $username, bool $success): void {
    $ipHash = _loginIpHash(_loginGetIp());
    $uSafe = preg_replace('/[\t\r\n]+/', ' ', $username);

    $logDir = __DIR__ . '/../_logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    if (!is_dir($logDir)) return;

    $line = implode("\t", [date('c'), $ipHash, $uSafe, $success ? '1' : '0']) . "\n";
    @file_put_contents($logDir . '/login_fail.log', $line, FILE_APPEND | LOCK_EX);
}

function loginAdmin(string $username, string $password): bool {
    // Rate limit 先擋（避免攻擊者用對密碼 sneak 進來）
    if (isLoginLocked()) {
        _logLoginAttempt($username, false);
        return false;
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // 更新最後登入時間
        $db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_client_id'] = $user['client_id'];
        $_SESSION['admin'] = [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'display_name' => $user['display_name'],
            'role'         => $user['role'],
            'client_id'    => $user['client_id'],
        ];
        _logLoginAttempt($username, true);
        return true;
    }
    _logLoginAttempt($username, false);
    return false;
}

function logoutAdmin(): void {
    startAdminSession();
    $_SESSION = [];
    session_destroy();
}

// 取得當前後台客戶 ID（super admin 可切換）
function getCurrentClientId(): ?int {
    $admin = currentAdmin();
    if ($admin['role'] === 'super') {
        return isset($_SESSION['viewing_client_id']) ? (int)$_SESSION['viewing_client_id'] : null;
    }
    return $admin['client_id'] ? (int)$admin['client_id'] : null;
}

/**
 * 取得目前編輯中的客戶 id；沒選客戶就導回客戶列表。
 *
 * 2026-07-20 加：原本各頁寫 `getCurrentClientId() ?? 1`，
 * 一旦 super admin 把 viewing_client_id 清掉（clients.php 的「全部客戶」），
 * 存檔會「安靜地」寫進客戶 #1（旭森）。改成明確擋下並提示。
 */
function requireClientId(): int {
    $id = getCurrentClientId();
    if (!$id) {
        setFlash('warning', '尚未選擇要編輯的客戶，請先從客戶列表點選一家。');
        redirect(BASE_URL . '/admin/pages/clients.php');
    }
    return (int)$id;
}

/**
 * 防「跨分頁改到別家」：表單送出時比對它當初是為哪一家開的。
 * 不一致代表使用者在另一個分頁切換過客戶 → 擋下不寫入。
 */
function assertFormClient(int $currentClientId, string $backUrl): void {
    $posted = (int)($_POST['_client_id'] ?? 0);
    if ($posted && $posted !== $currentClientId) {
        setFlash('danger', '⚠️ 這個表單是為別的客戶開啟的（你可能在另一個分頁切換過客戶）。為避免寫錯資料，本次「沒有」儲存。請重新整理此頁後再編輯。');
        redirect($backUrl);
    }
}
