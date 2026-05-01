<?php
// ============================================================
// includes/helpers.php  ─  通用輔助函式
// ============================================================

// ── 平台設定 helper（讀 platform_settings 表） ────
function getPlatformSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = [];
            $db = getDB();
            // 表可能還不存在（migration 002 前）
            $rs = $db->query("SHOW TABLES LIKE 'platform_settings'");
            if ($rs && $rs->fetch()) {
                foreach ($db->query("SELECT setting_key, setting_value FROM platform_settings") as $r) {
                    $cache[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Exception $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function setPlatformSetting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

// HTML 安全輸出
function h(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// 安全重導向
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

// Flash 訊息
function setFlash(string $type, string $message): void {
    startAdminSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// 圖片上傳處理
function uploadImage(array $file, string $subdir = 'services'): string|false {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxSize) return false;
    if (!in_array($file['type'], $allowedTypes)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $targetDir = UPLOAD_DIR . $subdir . '/';
    $targetPath = $targetDir . $filename;

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . $subdir . '/' . $filename;
    }
    return false;
}

// 刪除圖片檔案
function deleteImage(string $path): void {
    $fullPath = __DIR__ . '/../' . ltrim($path, '/');
    if (file_exists($fullPath)) unlink($fullPath);
}

// 取得客戶目前啟用主題
function getActiveTheme(int $clientId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM client_themes WHERE client_id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$clientId]);
    $theme = $stmt->fetch();
    return $theme ?: [
        'color_primary' => '#1c3d3d',
        'color_accent'  => '#c8922a',
        'color_bg'      => '#ffffff',
        'color_text'    => '#222222',
        'color_light'   => '#f5f5f0',
    ];
}

// 取得客戶所有資料（含社群、主題）
function getClientData(int $clientId): array {
    $db = getDB();
    $client = $db->prepare('SELECT * FROM clients WHERE id = ? AND is_active = 1');
    $client->execute([$clientId]);
    $data = $client->fetch();
    if (!$data) return [];

    $social = $db->prepare('SELECT * FROM client_social WHERE client_id = ?');
    $social->execute([$clientId]);
    $data['social'] = $social->fetch() ?: [];

    $data['theme'] = getActiveTheme($clientId);
    return $data;
}

// CSRF Token
function csrfToken(): string {
    startAdminSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    startAdminSession();
    $token = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF 驗證失敗');
    }
}

// 星星評分 HTML
function renderStars(int $rating): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $rating ? '★' : '☆';
    }
    return $html;
}

// 從 URL 抓取 OG Image
function fetchOgImage(string $url): string {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return '';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: Mozilla/5.0 (compatible; MiniWeb/1.0)\r\n",
            'follow_location' => true,
            'max_redirects' => 3,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $html = @file_get_contents($url, false, $ctx, 0, 100000);
    if (!$html) return '';
    // 嘗試抓 og:image
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
        return $m[1];
    }
    return '';
}

// 分頁輔助
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = (int)ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    return ['total' => $total, 'per_page' => $perPage, 'current' => $currentPage, 'total_pages' => $totalPages, 'offset' => $offset];
}
