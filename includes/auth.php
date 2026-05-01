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
            'secure'   => false,   // 本機 http；正式環境改 true
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

function loginAdmin(string $username, string $password): bool {
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
        return true;
    }
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
