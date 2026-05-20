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
        'fb_embed_enabled' => isset($_POST['fb_embed_enabled']) ? 1 : 0,
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
        <?php if ($f['name'] === 'fb_page_url'): ?>
          <div style="margin-top:10px; padding:14px 16px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
            <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; margin:0; font-weight:600;">
              <input type="checkbox" name="fb_embed_enabled" value="1"
                     <?= !empty($social['fb_embed_enabled']) ? 'checked' : '' ?>
                     style="margin-top:3px; transform:scale(1.2);">
              <span>
                📌 在 <code>/store/{slug}</code> 頁面最下方<strong>自動嵌入 FB 即時動態框</strong>
                <div style="font-weight:400; color:#475569; font-size:.85rem; margin-top:4px; line-height:1.5;">
                  勾起來後，你在 FB 粉專發任何貼文，店家頁面下方都會同步顯示。<br>
                  ⚠️ 會稍微拖慢頁面載入速度（多 1-2 秒）。新版 Meta 商業檔案有時無法載入，貼完請開頁面確認。
                </div>
              </span>
            </label>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary">💾 儲存社群連結</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
