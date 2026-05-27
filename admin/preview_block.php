<?php
// admin/preview_block.php  ─  區塊即時預覽 endpoint
// 接收 store_block_edit.php 表單資料 → 用 renderBlock() 渲染回 HTML(不寫 DB、不存圖)
// 回傳完整 HTML 文件（含 gomag.css）給 iframe.srcdoc 使用
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/block_helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only');
}

$type = strtolower(trim($_POST['type'] ?? ''));
if (!in_array($type, BLOCK_TYPES, true)) {
    http_response_code(400);
    exit('invalid type');
}

// 依 type 重組 $data（鏡像 store_block_edit.php 的 POST 處理邏輯，
// 但不處理檔案上傳——預覽用既有 image path 即可）
$data = ['title' => trim($_POST['title'] ?? '')];

switch ($type) {
    case 'service':
        $items = [];
        foreach (($_POST['items'] ?? []) as $row) {
            $name = trim($row['name'] ?? '');
            if (!$name) continue;
            $items[] = [
                'icon'       => trim($row['icon'] ?? ''),
                'name'       => $name,
                'short_desc' => trim($row['short_desc'] ?? ''),
                'price_text' => trim($row['price_text'] ?? ''),
                'image'      => trim($row['image'] ?? ''),
            ];
        }
        $data['items'] = $items;
        break;

    case 'menu':
        $groups = [];
        foreach (($_POST['groups'] ?? []) as $g) {
            $gName = trim($g['name'] ?? '');
            if (!$gName) continue;
            $items = [];
            foreach (($g['items'] ?? []) as $it) {
                $iName = trim($it['name'] ?? '');
                if (!$iName) continue;
                $items[] = [
                    'name'  => $iName,
                    'price' => ($it['price'] ?? '') !== '' ? (int)$it['price'] : null,
                    'desc'  => trim($it['desc'] ?? ''),
                    'tag'   => trim($it['tag'] ?? ''),
                    'image' => trim($it['image'] ?? ''),
                ];
            }
            if ($items) $groups[] = ['name' => $gName, 'items' => $items];
        }
        $data['groups'] = $groups;
        break;

    case 'portfolio':
        $data['layout'] = trim($_POST['layout'] ?? 'grid');
        $items = [];
        foreach (($_POST['items'] ?? []) as $row) {
            $img = trim($row['image'] ?? '');
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

// 渲染 block 並補上完整 HTML 框架（含 gomag.css）
ob_start();
renderBlock([
    'id'         => 0,
    'type'       => $type,
    'data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
    'sort_order' => 0,
    'is_active'  => 1,
]);
$blockHtml = ob_get_clean();

$cssUrl  = BASE_URL . '/assets/css/gomag.css?v=' . (file_exists(__DIR__ . '/../assets/css/gomag.css') ? filemtime(__DIR__ . '/../assets/css/gomag.css') : '1');
$emptyHint = trim(strip_tags($blockHtml)) === ''
    ? '<div style="padding:60px 24px; text-align:center; color:#aaa; font-family:Noto Sans TC, sans-serif;">（填入內容後此區會即時顯示預覽）</div>'
    : '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= h($cssUrl) ?>">
<style>
  body { margin:0; padding:20px; font-family:'Noto Sans TC', sans-serif; background:#fff; color:#1c1c1c; }
  a { pointer-events:none; }
  /* 預覽時禁用 hover 效果 / 動畫，避免分心 */
</style>
</head>
<body>
<?= $emptyHint ?>
<?= $blockHtml ?>
</body>
</html>
