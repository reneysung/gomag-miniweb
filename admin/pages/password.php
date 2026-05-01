<?php
// admin/pages/password.php  ─  變更密碼
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '變更密碼';
$db = getDB();
$admin = currentAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $row = $db->prepare('SELECT password_hash FROM admin_users WHERE id=?');
    $row->execute([$admin['id']]);
    $row = $row->fetch();

    if (!password_verify($current, $row['password_hash'])) {
        setFlash('danger', '目前密碼不正確。');
    } elseif (strlen($new) < 8) {
        setFlash('error', '新密碼至少需要 8 個字元。');
    } elseif ($new !== $confirm) {
        setFlash('error', '新密碼與確認密碼不一致。');
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $db->prepare('UPDATE admin_users SET password_hash=? WHERE id=?')->execute([$hash, $admin['id']]);
        setFlash('success', '密碼已成功變更！');
        redirect(BASE_URL . '/admin/pages/password.php');
    }
}

require_once __DIR__ . '/../includes/layout_head.php';
?>
<div class="card" style="max-width:480px;">
  <div class="card-header"><h2>🔐 變更密碼</h2></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <div class="form-group-admin">
        <label>目前密碼</label>
        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
      </div>
      <div class="form-group-admin">
        <label>新密碼（至少 8 字元）</label>
        <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
      </div>
      <div class="form-group-admin">
        <label>確認新密碼</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary">🔒 確認變更</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
