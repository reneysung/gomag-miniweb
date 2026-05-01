<?php
// admin/pages/themes.php  ─  主題顏色管理
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '主題顏色';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();

// 套用主題
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_id'])) {
    verifyCsrf();
    $themeId = (int)$_POST['activate_id'];
    $db->prepare('UPDATE client_themes SET is_active = 0 WHERE client_id = ?')->execute([$clientId]);
    $db->prepare('UPDATE client_themes SET is_active = 1 WHERE id = ? AND client_id = ?')->execute([$themeId, $clientId]);
    setFlash('success', '主題已切換！');
    redirect(BASE_URL . '/admin/pages/themes.php');
}

// 儲存自訂色彩
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_custom'])) {
    verifyCsrf();
    $themeId = (int)$_POST['theme_id'];
    $db->prepare('UPDATE client_themes SET color_primary=?,color_accent=?,color_bg=?,color_text=?,color_light=? WHERE id=? AND client_id=?')
       ->execute([
           $_POST['color_primary'], $_POST['color_accent'],
           $_POST['color_bg'],      $_POST['color_text'],
           $_POST['color_light'],   $themeId, $clientId
       ]);
    setFlash('success', '色彩已儲存！');
    redirect(BASE_URL . '/admin/pages/themes.php');
}

$themes = $db->prepare('SELECT * FROM client_themes WHERE client_id = ? ORDER BY id');
$themes->execute([$clientId]);
$themes = $themes->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div style="margin-bottom:20px;">
  <p style="color:var(--muted);font-size:.875rem;">選擇一套主題色，點擊「套用」立即生效。也可直接修改任一套的色碼做自訂調整。</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
  <?php foreach ($themes as $theme): ?>
  <div class="card" style="<?= $theme['is_active'] ? 'border:2px solid ' . $theme['color_primary'] : '' ?>">
    <!-- 色彩預覽 -->
    <div style="height:80px;background:<?= h($theme['color_primary']) ?>;
                border-radius:var(--radius) var(--radius) 0 0;position:relative;overflow:hidden;">
      <div style="position:absolute;right:0;bottom:0;width:50%;height:100%;
                  background:<?= h($theme['color_accent']) ?>;
                  clip-path:polygon(30% 0, 100% 0, 100% 100%, 0 100%);"></div>
      <div style="position:absolute;bottom:0;left:0;right:0;height:20px;
                  background:<?= h($theme['color_light']) ?>;"></div>
      <?php if ($theme['is_active']): ?>
      <div style="position:absolute;top:10px;left:10px;background:rgba(255,255,255,.9);
                  color:<?= h($theme['color_primary']) ?>;padding:3px 10px;border-radius:20px;
                  font-size:.75rem;font-weight:700;">✓ 套用中</div>
      <?php endif; ?>
    </div>

    <div class="card-body">
      <h3 style="font-size:.95rem;font-weight:700;margin-bottom:12px;">
        <?= h($theme['theme_name']) ?>
      </h3>

      <!-- 色票顯示 -->
      <div style="display:flex;gap:6px;margin-bottom:14px;">
        <?php foreach(['color_primary','color_accent','color_bg','color_light','color_text'] as $ck): ?>
        <div title="<?= h($ck) ?>: <?= h($theme[$ck]) ?>"
             style="width:28px;height:28px;border-radius:6px;background:<?= h($theme[$ck]) ?>;
                    border:1px solid rgba(0,0,0,.1);flex-shrink:0;cursor:default;"></div>
        <?php endforeach; ?>
      </div>

      <!-- 色碼小字 -->
      <div style="font-size:.7rem;color:var(--muted);margin-bottom:12px;line-height:1.8;">
        主色 <?= h($theme['color_primary']) ?>　強調 <?= h($theme['color_accent']) ?>
      </div>

      <!-- 展開自訂 -->
      <details>
        <summary style="font-size:.8rem;cursor:pointer;color:var(--primary);font-weight:600;margin-bottom:10px;">
          ✏️ 自訂色碼
        </summary>
        <form method="POST">
          <input type="hidden" name="_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="save_custom" value="1">
          <input type="hidden" name="theme_id" value="<?= $theme['id'] ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <?php
            $colorLabels = [
              'color_primary' => '主色',
              'color_accent'  => '強調色',
              'color_bg'      => '背景色',
              'color_text'    => '文字色',
              'color_light'   => '淺底色',
            ];
            foreach ($colorLabels as $ck => $cl): ?>
            <div>
              <label style="font-size:.7rem;font-weight:700;color:var(--muted);display:block;margin-bottom:2px;">
                <?= $cl ?>
              </label>
              <div style="display:flex;gap:4px;align-items:center;">
                <input type="color" name="<?= $ck ?>" value="<?= h($theme[$ck]) ?>"
                       style="width:32px;height:28px;border:1px solid var(--border);
                              border-radius:4px;cursor:pointer;padding:2px;">
                <input type="text" name="<?= $ck ?>_text" value="<?= h($theme[$ck]) ?>"
                       style="width:80px;font-size:.75rem;padding:4px 6px;
                              border:1px solid var(--border);border-radius:4px;"
                       oninput="syncColorInput(this)" data-target="<?= $ck ?>">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="submit" class="btn btn-sm btn-outline" style="width:100%;">💾 儲存色彩</button>
        </form>
      </details>

      <!-- 套用按鈕 -->
      <?php if (!$theme['is_active']): ?>
      <form method="POST" style="margin-top:12px;">
        <input type="hidden" name="_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="activate_id" value="<?= $theme['id'] ?>">
        <button type="submit" class="btn btn-primary" style="width:100%;">
          🎨 套用此主題
        </button>
      </form>
      <?php else: ?>
      <div class="btn btn-sm"
           style="width:100%;justify-content:center;margin-top:12px;
                  background:<?= h($theme['color_primary']) ?>1a;
                  color:<?= h($theme['color_primary']) ?>;border:none;
                  font-size:.8rem;font-weight:700;text-align:center;padding:8px;">
        ✅ 目前套用中
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
// 色碼文字輸入同步到 color picker
function syncColorInput(textInput) {
  const val = textInput.value.trim();
  const target = textInput.dataset.target;
  const picker = textInput.closest('form').querySelector('[name="' + target + '"]');
  if (picker && /^#[0-9a-fA-F]{6}$/.test(val)) {
    picker.value = val;
  }
}
// color picker 同步到文字輸入
document.querySelectorAll('input[type="color"]').forEach(picker => {
  picker.addEventListener('input', function() {
    const textInput = this.parentElement.querySelector('[data-target]');
    if (textInput) textInput.value = this.value;
  });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
