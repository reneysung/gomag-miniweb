<?php
// admin/pages/banners.php — 首頁 Banner 管理（Super Admin only）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('danger', '權限不足');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '首頁 Banner 管理';
$db = getDB();

// ─── 自動建表 ─────────────────────────────────────────
$db->exec("
CREATE TABLE IF NOT EXISTS banners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image_path VARCHAR(255) NOT NULL,
  title VARCHAR(150) DEFAULT NULL,
  subtitle VARCHAR(255) DEFAULT NULL,
  link_url VARCHAR(500) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ─── 處理動作 ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // 新增 banner
    if ($action === 'create') {
        $title    = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $linkUrl  = trim($_POST['link_url'] ?? '');

        // 處理圖片上傳
        if (empty($_FILES['image']['tmp_name'])) {
            setFlash('error', '請選擇要上傳的圖片');
            redirect(BASE_URL . '/admin/pages/banners.php');
        }

        $upDir = __DIR__ . '/../../uploads/banners';
        if (!is_dir($upDir)) @mkdir($upDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            setFlash('error', '只接受 jpg/png/webp/gif 格式');
            redirect(BASE_URL . '/admin/pages/banners.php');
        }

        $fname = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest  = $upDir . '/' . $fname;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            setFlash('error', '檔案上傳失敗');
            redirect(BASE_URL . '/admin/pages/banners.php');
        }

        $relPath = 'uploads/banners/' . $fname;

        // 取目前最大 sort_order
        $maxSort = (int)$db->query("SELECT COALESCE(MAX(sort_order),0) FROM banners")->fetchColumn();

        $stmt = $db->prepare("
            INSERT INTO banners (image_path, title, subtitle, link_url, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$relPath, $title ?: null, $subtitle ?: null, $linkUrl ?: null, $maxSort + 10]);
        setFlash('success', '✅ Banner 已新增');
        redirect(BASE_URL . '/admin/pages/banners.php');
    }

    // 更新 banner（標題/副標/連結/啟用）
    if ($action === 'update') {
        $id       = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $linkUrl  = trim($_POST['link_url'] ?? '');
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        $stmt = $db->prepare("
            UPDATE banners
               SET title=?, subtitle=?, link_url=?, is_active=?
             WHERE id=?
        ");
        $stmt->execute([$title ?: null, $subtitle ?: null, $linkUrl ?: null, $isActive, $id]);
        setFlash('success', '✅ Banner 已更新');
        redirect(BASE_URL . '/admin/pages/banners.php');
    }

    // 刪除 banner
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $db->prepare("SELECT image_path FROM banners WHERE id=?");
        $row->execute([$id]);
        $b = $row->fetch();
        if ($b) {
            $f = __DIR__ . '/../../' . $b['image_path'];
            if (file_exists($f)) @unlink($f);
            $db->prepare("DELETE FROM banners WHERE id=?")->execute([$id]);
            setFlash('success', '🗑️ Banner 已刪除');
        }
        redirect(BASE_URL . '/admin/pages/banners.php');
    }

    // 重新排序
    if ($action === 'reorder') {
        $orders = $_POST['order'] ?? [];
        if (is_array($orders)) {
            $stmt = $db->prepare("UPDATE banners SET sort_order=? WHERE id=?");
            foreach ($orders as $idx => $bid) {
                $stmt->execute([($idx + 1) * 10, (int)$bid]);
            }
        }
        // AJAX 回應
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}

// ─── 撈出所有 banner ──────────────────────────────────
$banners = $db->query("
    SELECT * FROM banners
    ORDER BY sort_order ASC, id ASC
")->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
?>

<!-- 頂部說明 -->
<div class="card" style="margin-bottom:20px; border-left:4px solid var(--accent);">
  <div class="card-header"><h2>📢 首頁 Banner 輪播管理</h2></div>
  <div class="card-body">
    <p style="color:var(--muted); font-size:.9rem; margin-bottom:8px;">
      上傳的 Banner 會顯示在首頁 Hero 區（取代預設漸層背景）。可上傳多張，會自動輪播。
    </p>
    <ul style="color:var(--muted); font-size:.85rem; padding-left:20px; line-height:1.8;">
      <li>建議尺寸：<strong>1920×600</strong>（寬螢幕）或 <strong>1600×500</strong></li>
      <li>檔案格式：JPG / PNG / WEBP / GIF，大小建議 &lt; 500KB</li>
      <li>標題、副標、連結為選填，可只上傳純圖片</li>
      <li>拖曳卡片可調整輪播順序</li>
    </ul>
  </div>
</div>

<!-- 新增 Banner 表單 -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header"><h2>➕ 新增 Banner</h2></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="create">

      <div class="form-group-admin">
        <label>Banner 圖片 <span style="color:var(--danger)">*</span></label>
        <input type="file" name="image" class="form-control" accept="image/*" required>
        <div class="hint">建議 1920×600 / JPG 或 PNG / 不超過 2MB</div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>主標題（選填）</label>
          <input type="text" name="title" class="form-control" maxlength="100"
                 placeholder="例：找台南好店家">
        </div>
        <div class="form-group-admin">
          <label>副標題（選填）</label>
          <input type="text" name="subtitle" class="form-control" maxlength="200"
                 placeholder="例：就上店家好口碑">
        </div>
      </div>

      <div class="form-group-admin">
        <label>點擊連結（選填）</label>
        <input type="url" name="link_url" class="form-control"
               placeholder="https://... 或 /category/food">
        <div class="hint">點擊整張 banner 跳轉的 URL；留空則不可點擊</div>
      </div>

      <button type="submit" class="btn btn-primary">📤 上傳並新增</button>
    </form>
  </div>
</div>

<!-- 已有的 Banner 列表 -->
<div class="card">
  <div class="card-header">
    <h2>🎴 目前 Banner（共 <?= count($banners) ?> 張）</h2>
  </div>
  <div class="card-body">
    <?php if (empty($banners)): ?>
      <p style="text-align:center; padding:40px; color:var(--muted);">
        尚無 Banner，請從上方新增。<br>
        <small>沒有 Banner 時，首頁會顯示預設漸層背景。</small>
      </p>
    <?php else: ?>
      <div id="banner-list" style="display:grid; gap:16px;">
        <?php foreach ($banners as $b): ?>
        <div class="banner-row" data-id="<?= $b['id'] ?>"
             style="background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:14px; display:grid; grid-template-columns:auto 1fr; gap:16px; align-items:start;">

          <!-- 拖曳手柄 + 圖片預覽 -->
          <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
            <span class="drag-handle" style="cursor:move; font-size:1.4rem; color:var(--muted); user-select:none;">⋮⋮</span>
            <img src="<?= BASE_URL ?>/<?= h($b['image_path']) ?>"
                 alt="banner"
                 style="width:240px; height:90px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">
            <small style="color:var(--muted); font-size:.7rem;">順序：<?= $b['sort_order'] ?></small>
          </div>

          <!-- 編輯表單 -->
          <form method="POST" style="display:grid; gap:10px;">
            <input type="hidden" name="_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $b['id'] ?>">

            <div class="form-row cols-2" style="margin:0; gap:10px;">
              <div>
                <label style="font-size:.75rem; color:var(--muted); display:block; margin-bottom:3px;">主標題</label>
                <input type="text" name="title" class="form-control" value="<?= h($b['title']) ?>" maxlength="100">
              </div>
              <div>
                <label style="font-size:.75rem; color:var(--muted); display:block; margin-bottom:3px;">副標題</label>
                <input type="text" name="subtitle" class="form-control" value="<?= h($b['subtitle']) ?>" maxlength="200">
              </div>
            </div>

            <div>
              <label style="font-size:.75rem; color:var(--muted); display:block; margin-bottom:3px;">點擊連結</label>
              <input type="url" name="link_url" class="form-control" value="<?= h($b['link_url']) ?>"
                     placeholder="https://...">
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
              <label style="display:flex; align-items:center; gap:6px; font-size:.85rem; cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" <?= $b['is_active'] ? 'checked' : '' ?>>
                啟用此 Banner
              </label>

              <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">💾 儲存</button>
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="if(confirm('確定刪除此 Banner？此操作不可恢復')) deleteBanner(<?= $b['id'] ?>)">
                  🗑️ 刪除
                </button>
              </div>
            </div>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- 隱藏的刪除表單 -->
<form id="delete-form" method="POST" style="display:none;">
  <input type="hidden" name="_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="delete-id">
</form>

<script>
function deleteBanner(id) {
  document.getElementById('delete-id').value = id;
  document.getElementById('delete-form').submit();
}

// ─── 拖曳排序 ───────────────────────────────────────
(function() {
  const list = document.getElementById('banner-list');
  if (!list) return;

  let dragRow = null;

  list.querySelectorAll('.banner-row').forEach(row => {
    row.draggable = true;
    row.addEventListener('dragstart', e => {
      dragRow = row;
      row.style.opacity = '0.4';
    });
    row.addEventListener('dragend', e => {
      row.style.opacity = '';
      saveOrder();
    });
    row.addEventListener('dragover', e => {
      e.preventDefault();
      const target = e.currentTarget;
      if (target === dragRow) return;
      const rect = target.getBoundingClientRect();
      const after = (e.clientY - rect.top) > rect.height / 2;
      list.insertBefore(dragRow, after ? target.nextSibling : target);
    });
  });

  function saveOrder() {
    const ids = [...list.querySelectorAll('.banner-row')].map(r => r.dataset.id);
    const fd  = new FormData();
    fd.append('_token', '<?= csrfToken() ?>');
    fd.append('action', 'reorder');
    ids.forEach(id => fd.append('order[]', id));
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => { if (d.ok) console.log('排序已儲存'); });
  }
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
