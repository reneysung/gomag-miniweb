<?php
// site/contact.php  ─  聯絡表單 AJAX 處理器
// 路由：POST /{slug}/contact

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

header('Content-Type: application/json; charset=utf-8');

// 這是聯絡表單的 POST 處理端點，不是給人瀏覽的頁面。
// 用瀏覽器直接開(GET)→ 跳該客戶 LINE（沒設則跳首頁）。
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $sub = getSubdomain();
    $_target = $sub ? siteUrl($sub) : (BASE_URL . '/');  // fallback：首頁
    if ($sub) {
        try {
            $_site = loadSiteData($sub);
            if ($_site && !empty($_site['social'])) {
                $_s = $_site['social'];
                $_lu = '';
                if (!empty($_s['line_url']) && filter_var($_s['line_url'], FILTER_VALIDATE_URL)) {
                    $_lu = $_s['line_url'];
                } elseif (!empty($_s['line_id'])) {
                    $_rid = ltrim(trim($_s['line_id']), '@');
                    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $_rid)) {
                        $_lu = 'https://line.me/R/ti/p/@' . $_rid;
                    }
                }
                if ($_lu) $_target = $_lu;
            }
        } catch (Exception $e) { /* fallback 已設首頁 */ }
    }
    header('Location: ' . $_target, true, 302);
    exit;
}

$sub = $_GET['slug'] ?? trim($_POST['slug'] ?? '');
$site = loadSiteData($sub);
if (!$site) {
    echo json_encode(['ok'=>false,'msg'=>'網站不存在']);
    exit;
}

$client = $site['client'];

// 簡易頻率限制（Session）
session_start();
$now = time();
$key = 'contact_last_' . $sub;
if (isset($_SESSION[$key]) && ($now - $_SESSION[$key]) < 60) {
    echo json_encode(['ok'=>false,'msg'=>'請稍候再試（每 60 秒限送一次）']);
    exit;
}
$_SESSION[$key] = $now;

// 取輸入
$name    = trim($_POST['name']    ?? '');
$contact = trim($_POST['contact'] ?? '');   // 電話或 Email
$message = trim($_POST['message'] ?? '');
$service = trim($_POST['service'] ?? '');

// 驗證
$errors = [];
if (mb_strlen($name) < 2)       $errors[] = '請填寫姓名（至少 2 字）';
if (!$contact)                   $errors[] = '請填寫聯絡方式（電話或 Email）';
if (mb_strlen($message) < 10)   $errors[] = '請填寫詢問內容（至少 10 字）';

if ($errors) {
    echo json_encode(['ok'=>false,'msg'=>implode('、',$errors)]);
    exit;
}

// ── 儲存到 DB ────────────────────────────────────
$db = getDB();

// 確保 contact_leads 表存在（懶惰建立）
$db->exec("CREATE TABLE IF NOT EXISTS `contact_leads` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`  INT UNSIGNED NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `contact`    VARCHAR(200) NOT NULL,
  `service`    VARCHAR(100) DEFAULT NULL,
  `message`    TEXT NOT NULL,
  `ip`         VARCHAR(50) DEFAULT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$db->prepare("INSERT INTO contact_leads (client_id,name,contact,service,message,ip) VALUES (?,?,?,?,?,?)")
   ->execute([$client['id'], $name, $contact, $service, $message, $_SERVER['REMOTE_ADDR'] ?? '']);

// ── 寄送 Email 通知 ──────────────────────────────
if ($client['email']) {
    $subject = "【{$client['brand_name']}】新諮詢訊息 - {$name}";
    $body    = "您有一封新的客戶詢問訊息：\n\n"
             . "姓名：{$name}\n"
             . "聯絡：{$contact}\n"
             . "服務：" . ($service ?: '未指定') . "\n"
             . "訊息：\n{$message}\n\n"
             . "------\n收到時間：" . date('Y-m-d H:i') . "\n網站：" . BASE_URL . "/{$sub}/";

    $headers = "From: noreply@" . parse_url(BASE_URL, PHP_URL_HOST) . "\r\n"
             . "Reply-To: {$contact}\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($client['email'], '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $headers);
}

echo json_encode(['ok'=>true,'msg'=>"感謝您的詢問，{$client['brand_name']}收到後將盡快與您聯繫！"]);
