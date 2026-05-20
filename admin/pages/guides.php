<?php
// admin/pages/guides.php  ─  攻略文管理（guides，IA 第 5 步）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/front_functions.php';  // getCityMap()
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('error', '只有 Super Admin 可管理攻略文');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '攻略文';
$db = getDB();
$cityMap = getCityMap();
$cats = $db->query("SELECT id, slug, name, icon FROM categories WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();

$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM guides WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $slug  = strtolower(trim($_POST['slug'] ?? ''));
        if ($title === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            setFlash('error', '標題必填、slug 限小寫英數與連字號');
            redirect(BASE_URL . '/admin/pages/guides.php');
        }
        try {
            $ins = $db->prepare("INSERT INTO guides (slug, title, status) VALUES (?, ?, 'draft')");
            $ins->execute([$slug, $title]);
            setFlash('success', '✅ 已建立草稿，開始編輯');
            redirect(BASE_URL . '/admin/pages/guides.php?edit=' . $db->lastInsertId());
        } catch (PDOException $e) {
            setFlash('error', 'slug 已存在，請換一個');
            redirect(BASE_URL . '/admin/pages/guides.php');
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        // 第一次發布時補 published_at
        $cur = $db->prepare("SELECT published_at FROM guides WHERE id = ?");
        $cur->execute([$id]);
        $curPub = $cur->fetchColumn();
        $publishedAt = $curPub;
        if ($status === 'published' && !$curPub) $publishedAt = date('Y-m-d H:i:s');
        if ($status === 'draft') $publishedAt = null;

        $fields = [
            'title'       => trim($_POST['title'] ?? ''),
            'excerpt'     => trim($_POST['excerpt'] ?? ''),
            'body_html'   => trim($_POST['body_html'] ?? ''),
            'cover_image' => trim($_POST['cover_image'] ?? ''),
            'city_slug'   => (isset($_POST['city_slug']) && isset($cityMap[$_POST['city_slug']])) ? $_POST['city_slug'] : null,
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'meta_title'  => trim($_POST['meta_title'] ?? ''),
            'meta_desc'   => trim($_POST['meta_desc'] ?? ''),
            'status'      => $status,
            'published_at'=> $publishedAt,
        ];
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($fields)));
        $stmt = $db->prepare("UPDATE guides SET $sets WHERE id = :id");
        $stmt->execute(array_merge($fields, ['id' => $id]));
        setFlash('success', '✅ 攻略文已儲存');
        redirect(BASE_URL . '/admin/pages/guides.php');
    }
}

$rows = $db->query("SELECT id, slug, title, status, city_slug, category_id, published_at FROM guides ORDER BY (status='published') DESC, COALESCE(published_at, created_at) DESC")->fetchAll();
$catById = [];
foreach ($cats as $c) $catById[(int)$c['id']] = $c;

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="page-header">
  <div>
    <h1>📖 攻略文</h1>
    <p style="color:var(--muted); margin-top:6px;">主站 topic-cluster 內容層 — 對應前台 <code>/guide</code>、<code>/guide/{slug}</code>。掛 城市/分類 可與交叉頁互鏈。</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<?php if ($editing):
    $eFaqCat = $editing['category_id'] ? (int)$editing['category_id'] : 0;
?>
<form method="POST">
  <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
  <div class="card">
    <div class="card-header"><h2>📝 編輯：<code>/guide/<?= h($editing['slug']) ?></code></h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>標題</label>
        <input type="text" name="title" value="<?= h($editing['title']) ?>" class="form-control" required>
      </div>
      <div class="form-group">
        <label>摘要（excerpt）</label>
        <textarea name="excerpt" class="form-control" rows="2" maxlength="500"><?= h($editing['excerpt']) ?></textarea>
        <small style="color:var(--muted);">列表卡片 + meta description fallback</small>
      </div>
      <div class="form-group">
        <label>正文（body_html，可用 HTML）</label>
        <textarea name="body_html" class="form-control" rows="14"><?= h($editing['body_html']) ?></textarea>
        <small style="color:var(--muted);">super admin 受信任，直接輸出。可用 &lt;h2&gt;&lt;p&gt;&lt;ul&gt;&lt;a&gt; 等。</small>
      </div>
      <div class="form-group">
        <label>封面圖 URL（可空）</label>
        <input type="url" name="cover_image" value="<?= h($editing['cover_image']) ?>" class="form-control" placeholder="https://...">
      </div>
      <div style="display:flex; gap:14px; flex-wrap:wrap;">
        <div class="form-group" style="flex:1; min-width:200px;">
          <label>掛城市（互鏈交叉頁，可空）</label>
          <select name="city_slug" class="form-control">
            <option value="">—（不掛）</option>
            <?php foreach ($cityMap as $cs => $cn): ?>
            <option value="<?= h($cs) ?>" <?= $editing['city_slug'] === $cs ? 'selected' : '' ?>><?= h($cn) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:1; min-width:200px;">
          <label>掛分類（互鏈交叉頁，可空）</label>
          <select name="category_id" class="form-control">
            <option value="">—（不掛）</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $eFaqCat === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['icon']) ?> <?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>SEO Meta Title（可空）</label>
        <input type="text" name="meta_title" value="<?= h($editing['meta_title']) ?>" class="form-control" placeholder="留空=用標題">
      </div>
      <div class="form-group">
        <label>SEO Meta Description（可空）</label>
        <textarea name="meta_desc" class="form-control" rows="2"><?= h($editing['meta_desc']) ?></textarea>
      </div>
      <div class="form-group">
        <label>狀態</label>
        <select name="status" class="form-control">
          <option value="draft" <?= $editing['status'] === 'draft' ? 'selected' : '' ?>>草稿（draft）— 不上線</option>
          <option value="published" <?= $editing['status'] === 'published' ? 'selected' : '' ?>>發布（published）— 上線可索引</option>
        </select>
      </div>
    </div>
  </div>
  <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg">💾 儲存</button>
    <a href="<?= BASE_URL ?>/admin/pages/guides.php" class="btn btn-ghost btn-lg">取消</a>
    <a href="<?= BASE_URL ?>/guide/<?= h($editing['slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg" style="margin-left:auto;">🔍 預覽前台</a>
  </div>
</form>

<?php else: ?>
<div class="card">
  <div class="card-header"><h2>➕ 新增攻略文</h2></div>
  <div class="card-body">
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
      <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-group" style="margin:0; flex:2; min-width:240px;">
        <label>標題</label>
        <input type="text" name="title" class="form-control" required placeholder="台中清潔公司怎麼選？5 個重點">
      </div>
      <div class="form-group" style="margin:0; flex:1; min-width:180px;">
        <label>slug（網址）</label>
        <input type="text" name="slug" class="form-control" required pattern="[a-z0-9-]+" placeholder="taichung-cleaning-guide">
      </div>
      <button type="submit" class="btn btn-primary">建立草稿</button>
    </form>
  </div>
</div>

<div class="card" style="margin-top:20px;">
  <div class="card-header"><h2>📋 全部攻略文（<?= count($rows) ?>）</h2></div>
  <div class="card-body" style="padding:0;">
    <?php if (!$rows): ?>
    <div style="padding:24px; color:var(--muted);">尚無攻略文。用上方表單建立第一篇。</div>
    <?php else: ?>
    <table class="table" style="width:100%; border-collapse:collapse;">
      <thead><tr style="background:var(--bg); border-bottom:1px solid var(--border);">
        <th style="padding:12px; text-align:left;">標題</th>
        <th style="padding:12px; text-align:left; width:160px;">掛點</th>
        <th style="padding:12px; text-align:center; width:90px;">狀態</th>
        <th style="padding:12px; text-align:right; width:200px;">操作</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $mount = [];
        if ($r['city_slug'] && isset($cityMap[$r['city_slug']])) $mount[] = $cityMap[$r['city_slug']];
        if ($r['category_id'] && isset($catById[(int)$r['category_id']])) $mount[] = $catById[(int)$r['category_id']]['name'];
      ?>
      <tr style="border-bottom:1px solid var(--border);<?= $r['status'] !== 'published' ? ' opacity:.6' : '' ?>">
        <td style="padding:12px;">
          <div style="font-weight:700;"><?= h($r['title']) ?></div>
          <div style="font-size:.78rem; color:var(--muted);"><code>/guide/<?= h($r['slug']) ?></code></div>
        </td>
        <td style="padding:12px; font-size:.85rem; color:var(--muted);"><?= $mount ? h(implode('・', $mount)) : '—' ?></td>
        <td style="padding:12px; text-align:center;">
          <?php if ($r['status'] === 'published'): ?>
          <span style="background:#E8F5EE; color:#048A50; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">已發布</span>
          <?php else: ?>
          <span style="background:#eee; color:#888; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">草稿</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:right; white-space:nowrap;">
          <a href="<?= BASE_URL ?>/guide/<?= h($r['slug']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">🔍 預覽</a>
          <a href="?edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-primary">編輯</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
