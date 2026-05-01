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

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">

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

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
