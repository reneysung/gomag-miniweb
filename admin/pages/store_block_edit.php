<?php
// admin/pages/store_block_edit.php  ─  區塊新增/編輯頁
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/block_helpers.php';
requireLogin();

$db = getDB();
$clientId = getCurrentClientId();
if (!$clientId) {
    setFlash('error', '請先選擇要管理的客戶');
    redirect(BASE_URL . '/admin/index.php');
}

$blockId = (int)($_GET['id'] ?? 0);
$isNew   = !empty($_GET['new']);
$type    = strtolower(trim($_GET['type'] ?? ''));

// 載入既有 block
$block = null;
if ($blockId) {
    $stmt = $db->prepare("SELECT * FROM store_blocks WHERE id=? AND client_id=?");
    $stmt->execute([$blockId, $clientId]);
    $block = $stmt->fetch();
    if (!$block) {
        setFlash('error', '找不到此區塊');
        redirect(BASE_URL . '/admin/pages/store_blocks.php');
    }
    $type = $block['type'];
} elseif (!$isNew || !in_array($type, BLOCK_TYPES, true)) {
    setFlash('error', '無效的請求');
    redirect(BASE_URL . '/admin/pages/store_blocks.php');
}

// ─── 檔案上傳 helper（用於 POST handler）─────────────────
/**
 * 處理 image_xxx_N 形式的上傳。失敗回 fallback path。
 */
function handleBlockImageUpload(string $fieldKey, string $fallbackPath, string $subdir = 'blocks'): string {
    if (empty($_FILES[$fieldKey]['name']) || $_FILES[$fieldKey]['error'] !== UPLOAD_ERR_OK) {
        return $fallbackPath;
    }
    $newPath = uploadImage($_FILES[$fieldKey], $subdir);
    return $newPath ?: $fallbackPath;
}

// ─── POST: 儲存 ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // 取出舊區塊的圖檔路徑（替換 / 移除時要刪檔）
    $_oldBlockData = ($block && !empty($block['data'])) ? (json_decode($block['data'], true) ?: []) : [];
    $_oldImagePaths = collectBlockImagePaths($_oldBlockData);

    $title = trim($_POST['title'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    // 統一處理：不同 block type 有不同 data 結構
    $data = ['title' => $title];

    switch ($type) {
        case 'service':
            $items = [];
            foreach (($_POST['items'] ?? []) as $idx => $row) {
                $name = trim($row['name'] ?? '');
                if (!$name) continue;
                // 處理上傳：input name = image_items_{idx}
                $imagePath = handleBlockImageUpload("image_items_$idx", trim($row['image'] ?? ''), 'services');
                $items[] = [
                    'icon'       => trim($row['icon'] ?? ''),
                    'name'       => $name,
                    'short_desc' => trim($row['short_desc'] ?? ''),
                    'price_text' => trim($row['price_text'] ?? ''),
                    'image'      => $imagePath,
                ];
            }
            $data['items'] = $items;
            break;

        case 'menu':
            $groups = [];
            foreach (($_POST['groups'] ?? []) as $gi => $g) {
                $gName = trim($g['name'] ?? '');
                if (!$gName) continue;
                $items = [];
                foreach (($g['items'] ?? []) as $ii => $it) {
                    $iName = trim($it['name'] ?? '');
                    if (!$iName) continue;
                    // input name = image_groups_{gi}_items_{ii}
                    $imagePath = handleBlockImageUpload("image_groups_{$gi}_items_{$ii}", trim($it['image'] ?? ''), 'menu');
                    $items[] = [
                        'name'  => $iName,
                        'price' => $it['price'] !== '' ? (int)$it['price'] : null,
                        'desc'  => trim($it['desc'] ?? ''),
                        'tag'   => trim($it['tag'] ?? ''),
                        'image' => $imagePath,
                    ];
                }
                if ($items) $groups[] = ['name' => $gName, 'items' => $items];
            }
            $data['groups'] = $groups;
            break;

        case 'portfolio':
            $data['layout'] = trim($_POST['layout'] ?? 'grid');
            $items = [];
            foreach (($_POST['items'] ?? []) as $idx => $row) {
                $img = handleBlockImageUpload("image_items_$idx", trim($row['image'] ?? ''), 'cases');
                if (!$img) continue;
                $tagsText = trim($row['tags_text'] ?? '');
                $tags = $tagsText ? array_map('trim', explode(',', $tagsText)) : [];
                $items[] = [
                    'image'    => $img,
                    'title'    => trim($row['title'] ?? ''),
                    'desc'     => trim($row['desc'] ?? ''),
                    'tags'     => array_values(array_filter($tags)),
                    'is_large' => !empty($row['is_large']),
                ];
            }
            $data['items'] = $items;
            break;

        case 'pricing':
            $data['currency'] = trim($_POST['currency'] ?? 'TWD');
            $items = [];
            foreach (($_POST['items'] ?? []) as $row) {
                $name = trim($row['name'] ?? '');
                if (!$name) continue;
                $featuresText = trim($row['features_text'] ?? '');
                $features = $featuresText ? array_filter(array_map('trim', explode("\n", $featuresText))) : [];
                $items[] = [
                    'name'      => $name,
                    'price'     => trim($row['price'] ?? ''),
                    'unit'      => trim($row['unit'] ?? ''),
                    'features'  => array_values($features),
                    'highlight' => !empty($row['highlight']),
                ];
            }
            $data['items'] = $items;
            break;

        case 'faq':
            $items = [];
            foreach (($_POST['items'] ?? []) as $row) {
                $q = trim($row['q'] ?? '');
                $a = trim($row['a'] ?? '');
                if (!$q || !$a) continue;
                $items[] = ['q' => $q, 'a' => $a];
            }
            $data['items'] = $items;
            break;
    }

    $savedId = saveStoreBlock($clientId, $type, $data, $sortOrder, $blockId ?: null);

    // 對比新舊圖路徑，刪掉被移除/替換的舊圖檔
    $_newImagePaths = collectBlockImagePaths($data);
    $_removedImages = array_diff($_oldImagePaths, $_newImagePaths);
    foreach ($_removedImages as $_p) {
        if ($_p) deleteImage($_p);
    }

    setFlash('success', '✅ 區塊已儲存');
    redirect(BASE_URL . '/admin/pages/store_blocks.php');
}

// ─── 準備 render ────────────────────────────────────────
$blockData = $block ? (json_decode($block['data'], true) ?: []) : [];

$typeMeta = [
    'service'   => ['icon' => '🛠️', 'name' => '服務項目'],
    'menu'      => ['icon' => '🍽️', 'name' => '菜單'],
    'portfolio' => ['icon' => '📸', 'name' => '作品集'],
    'pricing'   => ['icon' => '💰', 'name' => '價目表'],
    'faq'       => ['icon' => '❓', 'name' => '常見問題'],
];
$meta = $typeMeta[$type];

$pageTitle = ($block ? '編輯' : '新增') . '區塊：' . $meta['name'];
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="page-header">
  <div>
    <a href="<?= BASE_URL ?>/admin/pages/store_blocks.php" style="color:var(--muted); font-size:.85rem; text-decoration:none;">← 返回區塊管理</a>
    <h1><?= h($meta['icon']) ?> <?= h($pageTitle) ?></h1>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<style>
.blk-split { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:24px; align-items:start; }
.blk-preview-pane { position:sticky; top:20px; }
.blk-preview-head { font-size:.85rem; color:var(--muted); padding:6px 0 10px; display:flex; align-items:center; gap:8px; }
.blk-preview-frame-wrap { border:1.5px solid var(--border); border-radius:8px; overflow:hidden; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.04); }
#blk-preview { width:100%; height:560px; border:0; display:block; background:#fff; }
.blk-preview-status { font-size:.72rem; color:var(--muted); display:inline-block; min-width:60px; }
@media (max-width:1100px) { .blk-split { grid-template-columns:1fr; } .blk-preview-pane { position:static; } }
</style>

<div class="blk-split">
  <div>
<form method="POST" enctype="multipart/form-data" id="blk-form">
  <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
  <input type="hidden" name="type" value="<?= h($type) ?>">

  <div class="card">
    <div class="card-header"><h2><?= h($meta['icon']) ?> <?= h($meta['name']) ?> 區塊內容</h2></div>
    <div class="card-body">
      <?php
      // 動態載入對應 type 的 form partial
      $formPath = __DIR__ . '/../forms/form-' . $type . '.php';
      if (is_file($formPath)) {
          include $formPath;
      } else {
          echo '<div class="alert alert-error">找不到 ' . h($type) . ' 表單</div>';
      }
      ?>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <div class="card-header"><h2>⚙️ 顯示設定</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>排序順序</label>
        <input type="number" name="sort_order" value="<?= h($block['sort_order'] ?? 10) ?>" class="form-control" style="max-width:200px;">
        <small style="color:var(--muted);">數字越小越前面。建議：service=10, portfolio=30, pricing=40, faq=50, menu=20</small>
      </div>
    </div>
  </div>

  <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg">💾 儲存區塊</button>
    <a href="<?= BASE_URL ?>/admin/pages/store_blocks.php" class="btn btn-ghost btn-lg">取消</a>
  </div>
</form>
  </div><!-- /left form column -->

  <aside class="blk-preview-pane">
    <div class="blk-preview-head">
      <span>👁️ 即時預覽（編輯時自動更新）</span>
      <span class="blk-preview-status" id="blk-preview-status"></span>
    </div>
    <div class="blk-preview-frame-wrap">
      <iframe id="blk-preview" title="區塊即時預覽" loading="lazy"></iframe>
    </div>
    <div style="margin-top:8px; font-size:.72rem; color:var(--muted); line-height:1.55;">
      預覽用既有的圖片路徑（剛上傳但還沒存的圖片不會出現，存檔後再回來看完整效果）。
    </div>
  </aside>
</div><!-- /.blk-split -->

<script>
// Block 即時預覽：表單變動 → POST 到 /admin/preview_block.php → iframe.srcdoc
(function() {
  var form   = document.getElementById('blk-form');
  var frame  = document.getElementById('blk-preview');
  var status = document.getElementById('blk-preview-status');
  if (!form || !frame) return;

  var debounceTimer = null;
  var inFlight = false;
  var pendingRetry = false;

  function setStatus(text, color) {
    if (!status) return;
    status.textContent = text;
    status.style.color = color || 'var(--muted)';
  }

  function refresh() {
    if (inFlight) { pendingRetry = true; return; }
    inFlight = true;
    setStatus('• 更新中…', '#888');
    var fd = new FormData(form);
    // 預覽不需要 file uploads（保留路徑就好），剃除以免請求過大
    var lean = new FormData();
    for (var pair of fd.entries()) {
      var v = pair[1];
      if (v instanceof File) continue;
      lean.append(pair[0], v);
    }
    fetch('<?= BASE_URL ?>/admin/preview_block.php', {
      method: 'POST',
      body: lean,
      credentials: 'same-origin'
    })
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
    .then(function(html) {
      frame.srcdoc = html;
      setStatus('✓ 已同步', '#0a8');
      setTimeout(function() { setStatus(''); }, 1500);
    })
    .catch(function(e) {
      setStatus('⚠ 預覽失敗：' + e.message, '#c33');
    })
    .finally(function() {
      inFlight = false;
      if (pendingRetry) { pendingRetry = false; refresh(); }
    });
  }

  function schedule() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(refresh, 450);
  }

  form.addEventListener('input', schedule);
  form.addEventListener('change', schedule);
  // 動態新增 row（service items / menu groups 等）時也觸發
  document.addEventListener('click', function(e) {
    var t = e.target;
    if (t && (t.matches('.btn-add-item, .btn-remove-item, .btn-add-group, .btn-remove-group, button[type=button][data-blk-action]'))) {
      setTimeout(schedule, 50);
    }
  });

  // 初次載入即渲染
  refresh();
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
