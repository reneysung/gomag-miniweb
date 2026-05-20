<?php
// admin/pages/cities.php  ─  城市管理（4 城 SEO 文案編輯）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('error', '只有 Super Admin 可管理城市');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '城市管理';
$db = getDB();

// 編輯模式
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM cities WHERE id=?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

// POST 儲存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $cid = (int)($_POST['id'] ?? 0);
    $highlights = array_values(array_filter(array_map('trim', explode("\n", $_POST['highlights_text'] ?? ''))));
    $areas      = array_values(array_filter(array_map('trim', explode("\n", $_POST['areas_text']      ?? ''))));

    $fields = [
        'tagline'    => trim($_POST['tagline'] ?? ''),
        'intro'      => trim($_POST['intro']   ?? ''),
        'highlights' => json_encode($highlights, JSON_UNESCAPED_UNICODE),
        'areas'      => json_encode($areas,      JSON_UNESCAPED_UNICODE),
        'hero_image' => trim($_POST['hero_image'] ?? ''),
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_desc'  => trim($_POST['meta_desc']  ?? ''),
        'is_active'  => isset($_POST['is_active']) ? 1 : 0,
    ];

    $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($fields)));
    $stmt = $db->prepare("UPDATE cities SET $sets WHERE id = :id");
    $stmt->execute(array_merge($fields, ['id' => $cid]));
    setFlash('success', '✅ 城市資料已儲存');
    redirect(BASE_URL . '/admin/pages/cities.php');
}

// 全部城市清單 + 各城市店家數
$cities = $db->query("SELECT * FROM cities ORDER BY sort_order, id")->fetchAll();
foreach ($cities as &$c) {
    $cnt = $db->prepare("SELECT COUNT(*) FROM clients WHERE is_active=1 AND city_slug = ?");
    $cnt->execute([$c['slug']]);
    $c['store_count'] = (int)$cnt->fetchColumn();
    $c['highlights_arr'] = json_decode($c['highlights'] ?? '[]', true) ?: [];
    $c['areas_arr']      = json_decode($c['areas']      ?? '[]', true) ?: [];
}
unset($c);

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="page-header">
  <div>
    <h1>🌏 城市管理</h1>
    <p style="color:var(--muted); margin-top:6px;">編輯 4 個城市的 SEO 文案、Hero 圖、服務區域 — 對應前台 <code>/city/{slug}</code></p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<?php if ($editing): ?>
<!-- ════════ 編輯表單 ════════ -->
<form method="POST">
  <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
  <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">

  <div class="card">
    <div class="card-header">
      <h2>📝 編輯：<?= h($editing['full_name']) ?>（slug: <code><?= h($editing['slug']) ?></code>）</h2>
    </div>
    <div class="card-body">

      <div class="form-group">
        <label>Hero 副標（Tagline）</label>
        <input type="text" name="tagline" value="<?= h($editing['tagline']) ?>" class="form-control" placeholder="在地老店、隱藏巷弄、世代家業 — 台南口碑商家集合">
        <small style="color:var(--muted);">顯示在 Hero 大圖下方的一行小字</small>
      </div>

      <div class="form-group">
        <label>城市介紹內文（Intro）</label>
        <textarea name="intro" class="form-control" rows="6"><?= h($editing['intro']) ?></textarea>
        <small style="color:var(--muted);">顯示在 Hero 下方的兩欄區（左側內文）— 用於 SEO + 避免 doorway page 懲罰</small>
      </div>

      <div class="form-group">
        <label>亮點 Highlights（一行一個）</label>
        <textarea name="highlights_text" class="form-control" rows="5" placeholder="在地老店比例高&#10;深夜營業選擇多&#10;價格親民"><?= h(implode("\n", $editing['highlights_arr'] = json_decode($editing['highlights'] ?? '[]', true) ?: [])) ?></textarea>
        <small style="color:var(--muted);">內文下方的橘色標籤（建議 3-5 個）</small>
      </div>

      <div class="form-group">
        <label>主要服務區域（一行一個）</label>
        <textarea name="areas_text" class="form-control" rows="5" placeholder="中西區&#10;東區&#10;北區"><?= h(implode("\n", $editing['areas_arr'] = json_decode($editing['areas'] ?? '[]', true) ?: [])) ?></textarea>
        <small style="color:var(--muted);">右側「快速資訊」卡的服務區域 tags</small>
      </div>

      <div class="form-group">
        <label>Hero 背景圖 URL</label>
        <input type="url" name="hero_image" value="<?= h($editing['hero_image']) ?>" class="form-control" placeholder="https://...">
        <small style="color:var(--muted);">建議 1800×1200 以上，可用 Unsplash 等外部圖庫</small>
      </div>

      <div class="form-group">
        <label>SEO Meta Title（覆寫，可空）</label>
        <input type="text" name="meta_title" value="<?= h($editing['meta_title']) ?>" class="form-control" placeholder="留空=自動產生">
      </div>
      <div class="form-group">
        <label>SEO Meta Description（覆寫，可空）</label>
        <textarea name="meta_desc" class="form-control" rows="2"><?= h($editing['meta_desc']) ?></textarea>
      </div>

      <div class="form-group">
        <label>
          <input type="checkbox" name="is_active" value="1" <?= $editing['is_active'] ? 'checked' : '' ?>>
          ☑ 啟用（顯示於前台）
        </label>
      </div>
    </div>
  </div>

  <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg">💾 儲存</button>
    <a href="<?= BASE_URL ?>/admin/pages/cities.php" class="btn btn-ghost btn-lg">取消</a>
    <a href="<?= BASE_URL ?>/city/<?= h($editing['slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg" style="margin-left:auto;">🔍 預覽前台</a>
  </div>
</form>

<?php else: ?>
<!-- ════════ 列表 ════════ -->
<div class="card">
  <div class="card-header"><h2>📋 城市列表（<?= count($cities) ?>）</h2></div>
  <div class="card-body" style="padding:0;">
    <table class="table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:var(--bg); border-bottom:1px solid var(--border);">
          <th style="padding:12px; text-align:left;">城市</th>
          <th style="padding:12px; text-align:left;">Tagline</th>
          <th style="padding:12px; text-align:center; width:100px;">店家數</th>
          <th style="padding:12px; text-align:center; width:90px;">狀態</th>
          <th style="padding:12px; text-align:right; width:240px;">操作</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($cities as $c): ?>
      <tr style="border-bottom:1px solid var(--border);<?= !$c['is_active'] ? ' opacity:.5' : '' ?>">
        <td style="padding:12px;">
          <div style="font-weight:700; font-size:1.05rem;">📍 <?= h($c['full_name']) ?></div>
          <div style="font-size:.78rem; color:var(--muted);"><code><?= h($c['slug']) ?></code></div>
        </td>
        <td style="padding:12px; max-width:340px;"><?= h($c['tagline']) ?></td>
        <td style="padding:12px; text-align:center; font-family:monospace; font-weight:700;">
          <?= $c['store_count'] ?>
          <?php if ($c['store_count'] < 3): ?>
          <div style="font-size:.7rem; color:var(--warning);">少於 3 家</div>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:center;">
          <?php if ($c['is_active']): ?>
          <span style="background:#E8F5EE; color:#048A50; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">啟用</span>
          <?php else: ?>
          <span style="background:#fce; color:#a44; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">停用</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:right; white-space:nowrap;">
          <a href="<?= BASE_URL ?>/city/<?= h($c['slug']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">🔍 預覽</a>
          <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-primary">編輯</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="alert alert-info" style="margin-top:20px;">
  💡 <strong>提示</strong>：城市列表無法新增/刪除（slug 寫死在 city.php 對映表）。要新增城市請聯絡開發者。
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
