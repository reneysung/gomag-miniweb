<?php
// admin/login.php  ─  後台登入頁
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

startAdminSession();

// 已登入則直接跳轉
if (isLoggedIn()) {
    redirect(BASE_URL . '/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '請填寫帳號與密碼';
    } elseif (isLoginLocked()) {
        $error = '嘗試次數過多，請 15 分鐘後再試';
    } elseif (!loginAdmin($username, $password)) {
        $error = '帳號或密碼錯誤，請重新確認';
    } else {
        redirect(BASE_URL . '/admin/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>後台登入 ─ 客戶小官網管理系統</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary: #1c3d3d;
  --accent:  #c8922a;
  --bg:      #f4f6f4;
  --white:   #ffffff;
  --text:    #1a2a1a;
  --muted:   #6b7c6b;
  --border:  #dde5dd;
  --danger:  #c0392b;
  --radius:  12px;
  --shadow:  0 8px 40px rgba(28,61,61,.12);
}

body {
  font-family: 'Noto Sans TC', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

/* 背景幾何裝飾 */
body::before {
  content: '';
  position: fixed;
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(28,61,61,.08) 0%, transparent 70%);
  top: -200px; right: -200px;
  border-radius: 50%;
  pointer-events: none;
}
body::after {
  content: '';
  position: fixed;
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(200,146,42,.06) 0%, transparent 70%);
  bottom: -100px; left: -100px;
  border-radius: 50%;
  pointer-events: none;
}

.login-wrap {
  width: 100%;
  max-width: 440px;
  position: relative;
  z-index: 1;
}

/* 品牌頂部 */
.login-brand {
  text-align: center;
  margin-bottom: 32px;
}
.brand-icon {
  width: 64px; height: 64px;
  background: var(--primary);
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  box-shadow: 0 4px 20px rgba(28,61,61,.3);
}
.brand-icon svg { width: 34px; height: 34px; fill: none; stroke: #fff; stroke-width: 1.5; }
.login-brand h1 { font-size: 1.3rem; font-weight: 700; color: var(--text); letter-spacing: .05em; }
.login-brand p  { font-size: .85rem; color: var(--muted); margin-top: 4px; }

/* 卡片 */
.login-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 40px;
  border: 1px solid var(--border);
}

.login-card h2 {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 28px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--bg);
}

/* 錯誤訊息 */
.alert-error {
  background: #fef0ef;
  border: 1px solid #f5c6c2;
  color: var(--danger);
  border-radius: 8px;
  padding: 12px 16px;
  font-size: .875rem;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.alert-error::before { content: '⚠'; font-size: 1rem; }

/* 表單 */
.form-group { margin-bottom: 20px; }
.form-group label {
  display: block;
  font-size: .875rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
}

.input-wrap {
  position: relative;
}
.input-wrap .icon {
  position: absolute;
  left: 14px; top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 1rem;
  pointer-events: none;
}

.form-group input {
  width: 100%;
  padding: 12px 14px 12px 42px;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  font-size: 1rem;
  color: var(--text);
  background: var(--bg);
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
.form-group input:focus {
  border-color: var(--primary);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(28,61,61,.1);
}

/* 登入按鈕 */
.btn-login {
  width: 100%;
  padding: 13px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: .08em;
  cursor: pointer;
  margin-top: 8px;
  transition: background .2s, transform .1s, box-shadow .2s;
  position: relative;
  overflow: hidden;
}
.btn-login::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0);
  transition: background .2s;
}
.btn-login:hover { background: #14302f; box-shadow: 0 4px 16px rgba(28,61,61,.3); }
.btn-login:active { transform: scale(.98); }

/* 提示資訊 */
.login-hint {
  margin-top: 20px;
  padding: 12px 16px;
  background: #f0f7f0;
  border-radius: 8px;
  font-size: .8rem;
  color: var(--muted);
  line-height: 1.6;
}
.login-hint strong { color: var(--primary); }

.login-footer {
  text-align: center;
  margin-top: 24px;
  font-size: .8rem;
  color: var(--muted);
}

/* 顯示密碼切換 */
.toggle-pw {
  position: absolute;
  right: 14px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none;
  cursor: pointer;
  color: var(--muted);
  font-size: .95rem;
  padding: 2px;
}
.toggle-pw:hover { color: var(--primary); }
</style>
</head>
<body>

<div class="login-wrap">
  <div class="login-brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/>
        <rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/>
        <circle cx="17.5" cy="17.5" r="3.5"/>
      </svg>
    </div>
    <h1>客戶小官網管理系統</h1>
    <p>Mini Website Template Manager</p>
  </div>

  <div class="login-card">
    <h2>🔐 後台登入</h2>

    <?php if ($error): ?>
      <div class="alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="username">管理員帳號</label>
        <div class="input-wrap">
          <span class="icon">👤</span>
          <input
            type="text"
            id="username"
            name="username"
            value="<?= h($_POST['username'] ?? '') ?>"
            placeholder="請輸入帳號"
            autocomplete="username"
            required
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">密碼</label>
        <div class="input-wrap">
          <span class="icon">🔑</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="請輸入密碼"
            autocomplete="current-password"
            required
          >
          <button type="button" class="toggle-pw" onclick="togglePassword()" title="顯示/隱藏密碼">
            👁
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login">登 入</button>
    </form>

    <div class="login-hint">
      <strong>測試帳號：</strong>xulang　／　<strong>密碼：</strong>admin1234<br>
      （正式部署後請立即變更密碼）
    </div>
  </div>

  <div class="login-footer">
    © 2025 店家好口碑 · Repulab &nbsp;|&nbsp; 客戶小官網系統 v1.0
  </div>
</div>

<script>
function togglePassword() {
  const pw = document.getElementById('password');
  pw.type = pw.type === 'password' ? 'text' : 'password';
}
// 防止重複提交
document.querySelector('form').addEventListener('submit', function() {
  const btn = this.querySelector('.btn-login');
  btn.textContent = '登入中…';
  btn.disabled = true;
});
</script>
</body>
</html>
