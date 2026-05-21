<?php
// admin/pages/service_keywords.php  ─  服務關鍵字池（service_keywords）
// 每個大分類底下的子服務/關鍵字。page_slug 空=自己是獨立頁；填=同義詞折進該 slug 的頁。
// 店家在「基本資訊設定」勾選這些關鍵字 → 決定出現在哪些 /city/{city}/{cat}/{svc} 子服務頁。
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('error', '只有 Super Admin 可管理服務關鍵字');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '服務關鍵字';
$db = getDB();

$cats = $db->query("SELECT id, slug, name, icon FROM categories WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$catById = [];
foreach ($cats as $c) $catById[(int)$c['id']] = $c;

$normSlug = fn($s) => preg_replace('/[^a-z0-9-]/', '', strtolower(trim($s)));

// ─── POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $cid  = (int)($_POST['category_id'] ?? 0);
        $slug = $normSlug($_POST['slug'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $page = $normSlug($_POST['page_slug'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        if (!isset($catById[$cid]) || $slug === '' || $name === '') {
            setFlash('error', '分類 / slug / 名稱必填');
            redirect(BASE_URL . '/admin/pages/service_keywords.php');
        }
        try {
            $ins = $db->prepare("INSERT INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)");
            $ins->execute([$cid, $slug, $name, $page, $sort]);
            setFlash('success', "✅ 已新增關鍵字「{$name}」");
        } catch (PDOException $e) {
            setFlash('error', '此分類底下已有相同 slug');
        }
        redirect(BASE_URL . '/admin/pages/service_keywords.php?cat=' . $cid);
    }

    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $page = $normSlug($_POST['page_slug'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $act  = isset($_POST['is_active']) ? 1 : 0;
        $u = $db->prepare("UPDATE service_keywords SET name=?, page_slug=?, sort_order=?, is_active=? WHERE id=?");
        $u->execute([$name, $page, $sort, $act, $id]);
        setFlash('success', '✅ 已儲存');
        redirect(BASE_URL . '/admin/pages/service_keywords.php?cat=' . (int)($_POST['category_id'] ?? 0));
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // 同時清掉店家標籤
        $db->prepare("DELETE FROM client_service_keywords WHERE service_keyword_id=?")->execute([$id]);
        $db->prepare("DELETE FROM service_keywords WHERE id=?")->execute([$id]);
        setFlash('success', '🗑️ 已刪除關鍵字與其店家標籤');
        redirect(BASE_URL . '/admin/pages/service_keywords.php?cat=' . (int)($_POST['category_id'] ?? 0));
    }
}

// ─── 目前選的分類（預設第一個有資料的，否則居家服務）──────
$activeCat = (int)($_GET['cat'] ?? 0);
if (!$activeCat) {
    $activeCat = (int)($db->query("SELECT category_id FROM service_keywords ORDER BY category_id LIMIT 1")->fetchColumn() ?: 0);
    if (!$activeCat) $activeCat = (int)($db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn() ?: ($cats[0]['id'] ?? 0));
}

// 每分類關鍵字數
$catCounts = [];
foreach ($db->query("SELECT category_id, COUNT(*) n FROM service_keywords GROUP BY category_id") as $r) $catCounts[(int)$r['category_id']] = (int)$r['n'];

// 目前分類的關鍵字 + 各自店數
$rows = [];
if ($activeCat) {
    $st = $db->prepare("SELECT sk.*,
        (SELECT COUNT(*) FROM client_service_keywords csk WHERE csk.service_keyword_id = sk.id) AS store_count
        FROM service_keywords sk WHERE sk.category_id = ? ORDER BY sk.sort_order, sk.id");
    $st->execute([$activeCat]);
    $rows = $st->fetchAll();
}

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="page-header">
  <div>
    <h1>🏷️ 服務關鍵字</h1>
    <p style="color:var(--muted); margin-top:6px;">每個大分類底下的子服務/關鍵字。<strong>page_slug 留空</strong>＝自己是獨立頁（意圖不同，如清潔/木地板）；<strong>page_slug 填值</strong>＝同義詞，折進該 slug 的頁（如「清潔公司」folder 進 cleaning），<strong>不另開網址</strong>避 doorway。店家在「基本資訊設定」勾這些 → 決定出現在哪些子服務頁。</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<!-- 分類切換 -->
<div class="card" style="margin-bottom:16px;">
  <div class="card-body" style="display:flex; flex-wrap:wrap; gap:8px;">
    <?php foreach ($cats as $c): $on = (int)$c['id'] === $activeCat; ?>
    <a href="?cat=<?= (int)$c['id'] ?>" class="btn btn-sm <?= $on ? 'btn-primary' : 'btn-ghost' ?>">
      <?= h($c['icon']) ?> <?= h($c['name']) ?>
      <?php if (!empty($catCounts[(int)$c['id']])): ?><span style="opacity:.7;">(<?= $catCounts[(int)$c['id']] ?>)</span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- 新增 -->
<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><h2>➕ 新增關鍵字到「<?= h($catById[$activeCat]['name'] ?? '?') ?>」</h2></div>
  <div class="card-body">
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
      <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="category_id" value="<?= $activeCat ?>">
      <div class="form-group" style="margin:0;">
        <label>名稱</label>
        <input type="text" name="name" class="form-control" placeholder="例 清潔" required style="width:130px;">
      </div>
      <div class="form-group" style="margin:0;">
        <label>slug</label>
        <input type="text" name="slug" class="form-control" placeholder="例 cleaning" required style="width:150px;">
      </div>
      <div class="form-group" style="margin:0;">
        <label>page_slug（同義折入用，可空）</label>
        <input type="text" name="page_slug" class="form-control" placeholder="留空=自己一頁" style="width:160px;">
      </div>
      <div class="form-group" style="margin:0;">
        <label>排序</label>
        <input type="number" name="sort_order" class="form-control" value="0" style="width:80px;">
      </div>
      <button type="submit" class="btn btn-primary">新增</button>
    </form>
  </div>
</div>

<!-- 列表（每列兩個並排表單：存 / 刪，避免 form 巢在 table 內的無效 HTML）-->
<div class="card">
  <div class="card-header"><h2>📋 「<?= h($catById[$activeCat]['name'] ?? '?') ?>」關鍵字（<?= count($rows) ?>）</h2></div>
  <div class="card-body">
    <?php if (!$rows): ?>
    <div style="padding:8px; color:var(--muted);">此分類尚無關鍵字，用上方新增。</div>
    <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:8px;">
      <?php foreach ($rows as $r): $isSyn = $r['page_slug'] !== ''; ?>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 12px; border:1px solid var(--border); border-radius:8px;<?= !$r['is_active'] ? ' opacity:.5;' : '' ?>">
        <form method="POST" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex:1; margin:0;">
          <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="category_id" value="<?= $activeCat ?>">
          <input type="text" name="name" value="<?= h($r['name']) ?>" class="form-control" style="width:130px;" title="名稱">
          <code style="font-size:.78rem; color:var(--muted); min-width:90px;"><?= h($r['slug']) ?></code>
          <?php if ($isSyn): ?>
          <span style="font-size:.75rem; color:var(--muted);">同義→</span>
          <input type="text" name="page_slug" value="<?= h($r['page_slug']) ?>" class="form-control" style="width:110px;" title="折進哪個頁的 slug">
          <?php else: ?>
          <span style="background:#E8F0FF; color:#2456c4; padding:3px 8px; border-radius:100px; font-size:.72rem; font-weight:700;">獨立頁</span>
          <input type="text" name="page_slug" value="" class="form-control" style="width:110px;" placeholder="(空=獨立頁)" title="填 slug 則變同義詞">
          <?php endif; ?>
          <span style="font-size:.78rem; color:var(--muted);">店數 <?= (int)$r['store_count'] ?></span>
          <label style="font-size:.78rem;">排序 <input type="number" name="sort_order" value="<?= (int)$r['sort_order'] ?>" class="form-control" style="width:60px; display:inline-block;"></label>
          <label style="font-size:.78rem;"><input type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>> 啟用</label>
          <button type="submit" class="btn btn-sm btn-primary">💾 存</button>
        </form>
        <form method="POST" style="margin:0;" onsubmit="return confirm('刪除「<?= h($r['name']) ?>」？會一併移除所有店家對它的標籤。');">
          <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="category_id" value="<?= $activeCat ?>">
          <button type="submit" class="btn btn-sm btn-ghost" style="color:#c0392b;">🗑️</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
