<?php
// admin/pages/social.php  ─  社群連結設定
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '社群連結設定';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'line_url'    => trim($_POST['line_url'] ?? ''),
        'line_id'     => trim($_POST['line_id'] ?? ''),
        'fb_page_url' => trim($_POST['fb_page_url'] ?? ''),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'youtube_url' => trim($_POST['youtube_url'] ?? ''),
    ];
    // UPSERT
    $check = $db->prepare('SELECT id FROM client_social WHERE client_id=?');
    $check->execute([$clientId]);
    if ($check->fetch()) {
        $sets = implode(',', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
        $db->prepare("UPDATE client_social SET $sets WHERE client_id=:client_id")
           ->execute(array_merge($data, ['client_id' => $clientId]));
    } else {
        $data['client_id'] = $clientId;
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $db->prepare("INSERT INTO client_social ($cols) VALUES ($vals)")->execute($data);
    }
    setFlash('success', '社群連結已儲存！');
    redirect(BASE_URL . '/admin/pages/social.php');
}

$social = $db->prepare('SELECT * FROM client_social WHERE client_id=?');
$social->execute([$clientId]);
$social = $social->fetch() ?: [];

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="card" style="max-width:640px;">
  <div class="card-header"><h2>📱 社群連結設定</h2></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <?php
      $fields = [
        ['name'=>'line_url',      'label'=>'LINE 官方帳號網址',  'icon'=>'💬', 'placeholder'=>'https://line.me/R/ti/p/@xxx'],
        ['name'=>'line_id',       'label'=>'LINE ID / @名稱',    'icon'=>'🆔', 'placeholder'=>'@旭浪清潔'],
        ['name'=>'fb_page_url',   'label'=>'Facebook 粉絲頁網址','icon'=>'📘', 'placeholder'=>'https://www.facebook.com/...'],
        ['name'=>'instagram_url', 'label'=>'Instagram 網址',     'icon'=>'📸', 'placeholder'=>'https://www.instagram.com/...'],
        ['name'=>'youtube_url',   'label'=>'YouTube 頻道網址',   'icon'=>'▶️', 'placeholder'=>'https://www.youtube.com/...'],
      ];
      foreach ($fields as $f): ?>
      <div class="form-group-admin">
        <label><?= $f['icon'] ?> <?= $f['label'] ?></label>
        <input type="text" name="<?= $f['name'] ?>" class="form-control"
               value="<?= h($social[$f['name']] ?? '') ?>" placeholder="<?= h($f['placeholder']) ?>">
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary">💾 儲存社群連結</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
