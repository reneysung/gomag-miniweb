<?php
// admin/pages/store_blocks.php  ─  Modular Blocks 主列表
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/block_helpers.php';
requireLogin();

$pageTitle = '區塊管理';
$db = getDB();
$clientId = getCurrentClientId();
if (!$clientId) {
    setFlash('error', '請先選擇要管理的客戶');
    redirect(BASE_URL . '/admin/index.php');
}

// 取當前 client 資料 + 分類
$client = $db->prepare("SELECT cl.*, c.name AS cat_name, c.icon AS cat_icon, c.id AS cat_id FROM clients cl LEFT JOIN categories c ON cl.category_id=c.id WHERE cl.id=?");
$client->execute([$clientId]);
$client = $client->fetch();
if (!$client) { redirect(BASE_URL . '/admin/index.php'); }

// ─── Action: 排序變更（上移/下移）──────────────────────
if (isset($_GET['move']) && isset($_GET['id'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['t'] ?? '')) { http_response_code(403); die('CSRF'); }
    $bid = (int)$_GET['id'];
    $dir = $_GET['move'] === 'up' ? -1 : 1;
    $cur = $db->prepare("SELECT sort_order FROM store_blocks WHERE id=? AND client_id=?");
    $cur->execute([$bid, $clientId]);
    $curSort = (int)$cur->fetchColumn();
    // 找目標 row（上面 / 下面那一個）
    $cmp = $dir === -1 ? '<' : '>';
    $ord = $dir === -1 ? 'DESC' : 'ASC';
    $tgt = $db->prepare("SELECT id, sort_order FROM store_blocks WHERE client_id=? AND sort_order $cmp ? ORDER BY sort_order $ord LIMIT 1");
    $tgt->execute([$clientId, $curSort]);
    $tgtRow = $tgt->fetch();
    if ($tgtRow) {
        $db->beginTransaction();
        $db->prepare("UPDATE store_blocks SET sort_order=? WHERE id=?")->execute([$tgtRow['sort_order'], $bid]);
        $db->prepare("UPDATE store_blocks SET sort_order=? WHERE id=?")->execute([$curSort, $tgtRow['id']]);
        $db->commit();
        setFlash('success', '排序已調整');
    }
    redirect(BASE_URL . '/admin/pages/store_blocks.php');
}

// ─── Action: toggle is_active ──────────────────────────
if (isset($_GET['toggle'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['t'] ?? '')) { http_response_code(403); die('CSRF'); }
    $bid = (int)$_GET['toggle'];
    $db->prepare("UPDATE store_blocks SET is_active = 1 - is_active WHERE id=? AND client_id=?")->execute([$bid, $clientId]);
    setFlash('success', '狀態已切換');
    redirect(BASE_URL . '/admin/pages/store_blocks.php');
}

// ─── Action: delete ─────────────────────────────────────
if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['t'] ?? '')) { http_response_code(403); die('CSRF'); }
    deleteStoreBlock((int)$_GET['delete'], $clientId);
    setFlash('success', '區塊已刪除');
    redirect(BASE_URL . '/admin/pages/store_blocks.php');
}

// 取得當前 client 全部 blocks（含 inactive）
$blocks = $db->prepare("
    SELECT id, type, data, sort_order, is_active, created_at, updated_at
    FROM store_blocks
    WHERE client_id = ?
    ORDER BY sort_order ASC, id ASC
");
$blocks->execute([$clientId]);
$blocks = $blocks->fetchAll();
foreach ($blocks as &$b) {
    $b['data_decoded'] = json_decode($b['data'], true) ?: [];
}
unset($b);

// 取建議 block types
$suggestions = $client['cat_id'] ? getCategoryBlockSuggestions((int)$client['cat_id']) : [];
$suggestedTypes = array_column($suggestions, 'block_type');
$existingTypes = array_column($blocks, 'type');
$availableNewTypes = BLOCK_TYPES; // 全部 5 種都可以新增（不限於 suggestion）

// Block type 中文 + icon 對映
$typeMeta = [
    'service'   => ['icon' => '🛠️', 'name' => '服務項目', 'desc' => '列出店家提供的服務（含 icon、價格）'],
    'menu'      => ['icon' => '🍽️', 'name' => '菜單',     'desc' => '餐飲分類菜單（多群組 + 價格）'],
    'portfolio' => ['icon' => '📸', 'name' => '作品集',   'desc' => '案例 / 作品 / 氛圍照網格'],
    'pricing'   => ['icon' => '💰', 'name' => '價目表',   'desc' => '方案價格表（含推薦標記）'],
    'faq'       => ['icon' => '❓', 'name' => '常見問題', 'desc' => '折疊式 Q&A（自動加 FAQPage SEO）'],
];

require_once __DIR__ . '/../includes/layout_head.php';
$csrfToken = csrfToken();
?>

<div class="page-header">
  <div>
    <h1>📦 區塊管理</h1>
    <p style="color:var(--muted); margin-top:6px;">
      管理 <strong><?= h($client['brand_name']) ?></strong> 的店家頁區塊
      <?php if ($client['cat_name']): ?>
        · 分類：<?= h($client['cat_icon']) ?> <?= h($client['cat_name']) ?>
      <?php endif; ?>
    </p>
  </div>
  <div>
    <a class="btn btn-ghost" href="<?= BASE_URL ?>/store/<?= h($client['subdomain'] ?: $client['slug']) ?>" target="_blank" rel="noopener">
      🔍 預覽店家頁
    </a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<!-- 提示卡：新系統說明 -->
<div class="card" style="background:linear-gradient(135deg,#FFF8F4,#FFE8DF); border-color:#FF5A36;">
  <div class="card-body" style="display:flex; align-items:center; gap:16px;">
    <div style="font-size:2.5rem;">📦</div>
    <div style="flex:1;">
      <h3 style="margin:0 0 4px;">新系統 — Modular Blocks</h3>
      <p style="margin:0; font-size:.85rem; color:var(--muted);">
        建立區塊後，店家頁將切換為新版渲染（取代舊「服務項目」「FAQ」「案例」分散管理）。
        舊資料仍保留為 fallback。
      </p>
    </div>
    <?php if (count($blocks) === 0): ?>
    <span class="badge" style="background:#fff; color:#FF5A36; font-weight:700; padding:8px 14px; border-radius:100px;">尚未啟用</span>
    <?php else: ?>
    <span class="badge" style="background:#048A50; color:#fff; font-weight:700; padding:8px 14px; border-radius:100px;">✓ 已啟用</span>
    <?php endif; ?>
  </div>
</div>

<!-- 新增區塊區（建議 + 全部） -->
<div class="card">
  <div class="card-header"><h2>➕ 新增區塊</h2></div>
  <div class="card-body">
    <?php if ($suggestions): ?>
    <p style="margin:0 0 12px; color:var(--muted); font-size:.85rem;">
      💡 依分類「<?= h($client['cat_name'] ?? '其他') ?>」建議使用：
    </p>
    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px;">
      <?php foreach ($suggestions as $s):
        $t = $s['block_type'];
        $exists = in_array($t, $existingTypes, true);
        $meta = $typeMeta[$t];
      ?>
      <a href="<?= BASE_URL ?>/admin/pages/store_block_edit.php?new=1&type=<?= h($t) ?>"
         class="btn <?= $exists ? 'btn-ghost' : 'btn-primary' ?>"
         <?= $exists ? 'style="opacity:.55"' : '' ?>>
        <?= h($meta['icon']) ?> <?= h($meta['name']) ?>
        <?php if ($s['is_required']): ?><span style="font-size:.7rem; margin-left:4px; opacity:.7;">必填</span><?php endif; ?>
        <?php if ($exists): ?><span style="font-size:.75rem; margin-left:6px;">（已存在）</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <details style="margin-top:8px;">
      <summary style="cursor:pointer; color:var(--muted); font-size:.85rem;">看全部 5 種 block type</summary>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:10px; margin-top:14px;">
        <?php foreach ($typeMeta as $t => $meta): ?>
        <a href="<?= BASE_URL ?>/admin/pages/store_block_edit.php?new=1&type=<?= h($t) ?>"
           style="display:block; padding:14px; border:1px solid var(--border); border-radius:var(--radius); text-decoration:none; color:var(--text); transition:all .15s;"
           onmouseover="this.style.borderColor='var(--primary)'; this.style.background='var(--bg)';"
           onmouseout="this.style.borderColor='var(--border)'; this.style.background='';">
          <div style="font-size:1.4rem; margin-bottom:6px;"><?= h($meta['icon']) ?></div>
          <div style="font-weight:700;"><?= h($meta['name']) ?></div>
          <div style="font-size:.75rem; color:var(--muted); margin-top:4px;"><?= h($meta['desc']) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </details>
  </div>
</div>

<!-- 既有區塊列表 -->
<div class="card" style="margin-top:20px;">
  <div class="card-header"><h2>📋 目前區塊（<?= count($blocks) ?>）</h2></div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($blocks)): ?>
    <div style="padding:40px; text-align:center; color:var(--muted);">
      <div style="font-size:3rem; margin-bottom:10px;">📦</div>
      <p>尚未建立任何區塊。從上方「新增區塊」開始。</p>
    </div>
    <?php else: ?>
    <table class="table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:var(--bg); border-bottom:1px solid var(--border);">
          <th style="padding:12px; text-align:center; width:60px;">#</th>
          <th style="padding:12px; text-align:left;">區塊</th>
          <th style="padding:12px; text-align:center; width:100px;">內容數</th>
          <th style="padding:12px; text-align:center; width:90px;">狀態</th>
          <th style="padding:12px; text-align:right; width:280px;">操作</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($blocks as $i => $b):
        $meta = $typeMeta[$b['type']] ?? ['icon' => '📦', 'name' => $b['type']];
        $title = $b['data_decoded']['title'] ?? $meta['name'];
        // 算內容數
        $itemCount = 0;
        if ($b['type'] === 'menu') {
          foreach (($b['data_decoded']['groups'] ?? []) as $g) $itemCount += count($g['items'] ?? []);
        } else {
          $itemCount = count($b['data_decoded']['items'] ?? []);
        }
      ?>
      <tr style="border-bottom:1px solid var(--border);<?= !$b['is_active'] ? ' opacity:.55' : '' ?>">
        <td style="padding:12px; text-align:center; font-family:monospace; color:var(--muted);"><?= $b['sort_order'] ?></td>
        <td style="padding:12px;">
          <div style="font-size:1.6rem; float:left; margin-right:12px;"><?= h($meta['icon']) ?></div>
          <div>
            <div style="font-weight:700; margin-bottom:2px;"><?= h($title) ?></div>
            <div style="font-size:.78rem; color:var(--muted);">
              <code style="background:var(--bg); padding:1px 6px; border-radius:3px;"><?= h($b['type']) ?></code>
              · 更新於 <?= h(date('m/d H:i', strtotime($b['updated_at']))) ?>
            </div>
          </div>
        </td>
        <td style="padding:12px; text-align:center; font-family:monospace;"><?= $itemCount ?></td>
        <td style="padding:12px; text-align:center;">
          <?php if ($b['is_active']): ?>
          <span style="background:#E8F5EE; color:#048A50; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">啟用</span>
          <?php else: ?>
          <span style="background:#fce; color:#a44; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">停用</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:right; white-space:nowrap;">
          <a href="?move=up&id=<?= $b['id'] ?>&t=<?= h($csrfToken) ?>" class="btn btn-sm btn-ghost" title="上移" <?= $i === 0 ? 'style="opacity:.3; pointer-events:none;"' : '' ?>>↑</a>
          <a href="?move=down&id=<?= $b['id'] ?>&t=<?= h($csrfToken) ?>" class="btn btn-sm btn-ghost" title="下移" <?= $i === count($blocks)-1 ? 'style="opacity:.3; pointer-events:none;"' : '' ?>>↓</a>
          <a href="?toggle=<?= $b['id'] ?>&t=<?= h($csrfToken) ?>" class="btn btn-sm btn-ghost" title="切換啟用">
            <?= $b['is_active'] ? '⏸' : '▶' ?>
          </a>
          <a href="<?= BASE_URL ?>/admin/pages/store_block_edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-primary">編輯</a>
          <a href="?delete=<?= $b['id'] ?>&t=<?= h($csrfToken) ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('確定刪除此區塊？此動作不可復原。')">刪除</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
