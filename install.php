<?php
/**
 * install.php  ─  一鍵部署精靈
 * 用法：上傳整包到 cPanel 後，瀏覽器開啟 /install.php
 * 完成後請立即刪除此檔案！
 */

// 安全：若 installed.lock 存在則拒絕執行
if (file_exists(__DIR__ . '/installed.lock')) {
    die('<div style="font-family:sans-serif;padding:40px;max-width:500px;margin:auto;text-align:center">
        <div style="font-size:3rem">🔒</div>
        <h2>已完成安裝</h2>
        <p style="color:#666">此安裝精靈已被鎖定。若需重新安裝請刪除 <code>installed.lock</code>。</p>
    </div>');
}

$step   = (int)($_GET['step'] ?? 1);
$errors = [];
$msg    = '';

// ── STEP 2：測試 DB 連線 ─────────────────────────────────
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host    = trim($_POST['db_host'] ?? 'localhost');
    $name    = trim($_POST['db_name'] ?? '');
    $user    = trim($_POST['db_user'] ?? '');
    $pass    = $_POST['db_pass'] ?? '';
    $baseUrl = rtrim(trim($_POST['base_url'] ?? ''), '/');

    if (!$name || !$user) {
        $errors[] = '請填寫資料庫名稱與帳號';
        $step = 1;
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            // 更新 config.php
            $configPath = __DIR__ . '/includes/config.php';
            $config = file_get_contents($configPath);
            $config = preg_replace("/define\('DB_HOST',\s*'[^']*'\)/",   "define('DB_HOST', '$host')",    $config);
            $config = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/",   "define('DB_NAME', '$name')",    $config);
            $config = preg_replace("/define\('DB_USER',\s*'[^']*'\)/",   "define('DB_USER', '$user')",    $config);
            $config = preg_replace("/define\('DB_PASS',\s*'[^']*'\)/",   "define('DB_PASS', '$pass')",    $config);
            $config = preg_replace("/define\('BASE_URL',\s*'http[^']*'\)/", "define('BASE_URL', '$baseUrl')", $config);
            file_put_contents($configPath, $config);
            $msg = 'DB 連線成功！config.php 已更新。';
        } catch (PDOException $e) {
            $errors[] = 'DB 連線失敗：' . $e->getMessage();
            $step = 1;
        }
    }
}

// ── STEP 3：執行 SQL ─────────────────────────────────────
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/config.php';
    try {
        $pdo = getDB();
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        // 拆成個別語句執行
        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $stmt) {
            if ($stmt) $pdo->exec($stmt);
        }
        // 建立 installed.lock
        file_put_contents(__DIR__ . '/installed.lock', date('Y-m-d H:i:s'));
        $msg = '資料表建立完成！';
        $step = 4;
    } catch (Exception $e) {
        $errors[] = 'SQL 執行失敗：' . $e->getMessage();
        $step = 2;
    }
}

// ── STEP 4：設定超級管理員密碼 ──────────────────────────
if ($step === 5 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/helpers.php';
    $newPw = $_POST['super_pw'] ?? '';
    $cliPw = $_POST['client_pw'] ?? '';
    if (strlen($newPw) < 8 || strlen($cliPw) < 8) {
        $errors[] = '密碼至少需要 8 個字元';
        $step = 4;
    } else {
        $pdo = getDB();
        $pdo->prepare("UPDATE admin_users SET password_hash=? WHERE username='superadmin'")
            ->execute([password_hash($newPw, PASSWORD_BCRYPT)]);
        $pdo->prepare("UPDATE admin_users SET password_hash=? WHERE username='xulang'")
            ->execute([password_hash($cliPw, PASSWORD_BCRYPT)]);
        $msg = '密碼設定完成！';
        $step = 6;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>安裝精靈 ─ 客戶小官網系統</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Noto Sans TC',sans-serif;background:#f4f6f4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.wrap{width:100%;max-width:560px}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-size:1.4rem;font-weight:800;color:#1c3d3d;margin-top:12px}
.logo p{font-size:.85rem;color:#888;margin-top:4px}
.card{background:#fff;border-radius:14px;box-shadow:0 6px 30px rgba(0,0,0,.1);border:1px solid #e8eee8;padding:32px}
.steps{display:flex;gap:0;margin-bottom:28px;position:relative}
.steps::before{content:'';position:absolute;top:14px;left:14px;right:14px;height:2px;background:#e0e0e0;z-index:0}
.step-dot{flex:1;text-align:center;position:relative;z-index:1}
.dot{width:28px;height:28px;border-radius:50%;border:2px solid #e0e0e0;background:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;font-size:.75rem;font-weight:800;transition:all .2s}
.dot.done{background:#1c3d3d;border-color:#1c3d3d;color:#fff}
.dot.active{background:#c8922a;border-color:#c8922a;color:#fff}
.step-label{font-size:.68rem;color:#999;font-weight:600}
.step-label.active{color:#c8922a}
h2{font-size:1.1rem;font-weight:800;color:#1c3d3d;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f4f0}
label{display:block;font-size:.8rem;font-weight:700;color:#333;margin-bottom:5px;margin-top:14px}
input{width:100%;padding:10px 12px;border:1.5px solid #dde5dd;border-radius:8px;font-size:.9rem;outline:none;transition:border .2s;background:#f9fbf9}
input:focus{border-color:#1c3d3d;background:#fff}
.hint{font-size:.75rem;color:#999;margin-top:4px}
.btn{display:block;width:100%;padding:12px;background:#1c3d3d;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:20px;transition:filter .2s}
.btn:hover{filter:brightness(1.1)}
.btn-accent{background:#c8922a}
.error{background:#fef0ef;border:1px solid #f5c6c2;color:#c0392b;border-radius:8px;padding:12px 14px;font-size:.85rem;margin-bottom:16px}
.success{background:#eafaf1;border:1px solid #a9dfbf;color:#1e8449;border-radius:8px;padding:12px 14px;font-size:.85rem;margin-bottom:16px}
.info{background:#ebf5fb;border:1px solid #aed6f1;color:#2471a3;border-radius:8px;padding:12px 14px;font-size:.85rem;margin-bottom:16px;line-height:1.7}
.final-links{display:flex;flex-direction:column;gap:10px;margin-top:16px}
.final-links a{display:block;padding:12px;border-radius:8px;font-weight:700;text-align:center;text-decoration:none}
.final-links .link-admin{background:#1c3d3d;color:#fff}
.final-links .link-site{background:#f0f4f0;color:#1c3d3d;border:1px solid #dde5dd}
code{background:#f0f4f0;padding:2px 6px;border-radius:4px;font-size:.85em}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div style="width:56px;height:56px;background:#1c3d3d;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:1.6rem">🏪</div>
    <h1>客戶小官網安裝精靈</h1>
    <p>Mini Website Template System</p>
  </div>

  <!-- 步驟列 -->
  <div class="steps">
    <?php $stepLabels=[1=>'環境檢查',2=>'DB設定',3=>'建立資料表',4=>'設定密碼',5=>'完成']; ?>
    <?php foreach ($stepLabels as $n=>$label): ?>
    <div class="step-dot">
      <div class="dot <?= $n<$step?'done':($n===$step?'active':'') ?>"><?= $n<$step?'✓':$n ?></div>
      <div class="step-label <?= $n===$step?'active':'' ?>"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <?php if ($errors): ?>
    <div class="error"><?= implode('<br>',$errors) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
    <div class="success">✅ <?= h($msg) ?></div>
    <?php endif; ?>

    <?php if ($step <= 1): ?>
    <!-- ── Step 1：環境檢查 ── -->
    <h2>① 環境檢查</h2>
    <?php
    $checks = [
      'PHP 版本 ≥ 8.0' => PHP_MAJOR_VERSION >= 8,
      'PDO MySQL 擴充' => extension_loaded('pdo_mysql'),
      'GD 圖片處理'     => extension_loaded('gd'),
      'mbstring'       => extension_loaded('mbstring'),
      'uploads/ 可寫入'=> is_writable(__DIR__.'/uploads') || @mkdir(__DIR__.'/uploads',0755,true),
      'includes/config.php 可寫入' => is_writable(__DIR__.'/includes/config.php'),
    ];
    $allPassed = true;
    foreach ($checks as $label=>$ok):
      if (!$ok) $allPassed = false;
    ?>
    <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f0f0f0;font-size:.875rem">
      <span style="font-size:1rem"><?= $ok?'✅':'❌' ?></span>
      <span style="<?= $ok?'':'color:#c0392b;font-weight:700' ?>"><?= $label ?></span>
      <span style="margin-left:auto;font-size:.75rem;color:<?= $ok?'#27ae60':'#c0392b' ?>;font-weight:700"><?= $ok?'通過':'未通過' ?></span>
    </div>
    <?php endforeach; ?>

    <?php if (!$allPassed): ?>
    <div class="error" style="margin-top:16px">請修正以上問題後再繼續安裝。</div>
    <?php else: ?>
    <form method="POST" action="?step=2">
      <button type="submit" class="btn">下一步：設定資料庫 →</button>
    </form>
    <?php endif; ?>

    <?php elseif ($step === 2): ?>
    <!-- ── Step 2：DB 設定 ── -->
    <h2>② 資料庫設定</h2>
    <div class="info">
      請先在 cPanel → MySQL 資料庫 建立好資料庫和帳號，再填入以下資訊。
    </div>
    <form method="POST" action="?step=2">
      <label>資料庫主機</label>
      <input name="db_host" value="localhost" placeholder="localhost">
      <label>資料庫名稱 *</label>
      <input name="db_name" required placeholder="例：username_miniweb">
      <label>資料庫帳號 *</label>
      <input name="db_user" required placeholder="例：username_dbuser">
      <label>資料庫密碼</label>
      <input name="db_pass" type="password" placeholder="資料庫密碼">
      <label>網站根網址</label>
      <input name="base_url" value="https://<?= h($_SERVER['HTTP_HOST']??'yourdomain.com') ?>" placeholder="https://yourdomain.com">
      <div class="hint">結尾不加斜線「/」</div>
      <button type="submit" class="btn">測試連線並儲存 →</button>
    </form>

    <?php elseif ($step === 3): ?>
    <!-- ── Step 3：建立資料表 ── -->
    <h2>③ 建立資料表</h2>
    <div class="info">
      即將執行 <code>schema.sql</code>，建立所有資料表並插入旭浪清潔示範資料。<br>
      ⚠️ 若資料表已存在（重新安裝），資料表結構不會被清除，但 INSERT 可能失敗（可忽略）。
    </div>
    <form method="POST" action="?step=3">
      <button type="submit" class="btn btn-accent">執行 SQL，建立資料表 →</button>
    </form>

    <?php elseif ($step === 4): ?>
    <!-- ── Step 4：設定密碼 ── -->
    <h2>④ 設定管理員密碼</h2>
    <div class="info">
      請設定初始密碼，取代 schema.sql 的預設值。<br>
      建立後請妥善記錄，密碼以 bcrypt 加密儲存無法還原。
    </div>
    <form method="POST" action="?step=5">
      <label>超級管理員（superadmin）密碼</label>
      <input type="password" name="super_pw" required minlength="8" placeholder="至少 8 字">
      <label>旭浪清潔管理員（xulang）密碼</label>
      <input type="password" name="client_pw" required minlength="8" placeholder="至少 8 字">
      <button type="submit" class="btn">設定密碼，完成安裝 →</button>
    </form>

    <?php elseif ($step === 6): ?>
    <!-- ── Step 6：完成 ── -->
    <h2>🎉 安裝完成！</h2>
    <div class="success" style="font-size:.95rem;line-height:1.8">
      所有設定已完成！<code>installed.lock</code> 已建立，安裝精靈已鎖定。<br>
      <strong>請立即刪除 <code>install.php</code></strong> 以防安全風險。
    </div>
    <div class="final-links">
      <a href="admin/login.php" class="link-admin">🔐 前往後台登入</a>
      <a href="xulang/" class="link-site">🌐 旭浪清潔示範網站</a>
    </div>
    <div style="margin-top:20px;padding:14px;background:#fef9e7;border-radius:8px;font-size:.8rem;color:#7d6608;line-height:1.7">
      <strong>⚠️ 安裝後必做事項：</strong><br>
      1. 刪除根目錄的 <code>install.php</code><br>
      2. 後台 → 基本資訊 → 上傳 Logo 和 Hero 圖片<br>
      3. 後台 → 主題顏色 → 確認主題色正確<br>
      4. 後台 → 服務項目 → 上傳服務圖片<br>
      5. 後台 → 施工案例 → 新增真實案例
    </div>
    <?php endif; ?>
  </div>

  <div style="text-align:center;margin-top:20px;font-size:.75rem;color:#aaa">
    店家好口碑 · Repulab · Mini Website System v1.0
  </div>
</div>
</body>
</html>
