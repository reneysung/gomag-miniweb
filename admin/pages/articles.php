<?php
// admin/pages/articles.php — 專欄文章 CRUD（Phase 7）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '專欄文章管理';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── DELETE ────────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    $row = $db->prepare('SELECT cover_image FROM articles WHERE id=? AND client_id=?');
    $row->execute([$editId, $clientId]);
    $r = $row->fetch();
    if ($r) {
        if ($r['cover_image']) deleteImage($r['cover_image']);
        $db->prepare('DELETE FROM articles WHERE id=? AND client_id=?')->execute([$editId, $clientId]);
        setFlash('success', '文章已刪除。');
    }
    redirect(BASE_URL . '/admin/pages/articles.php');
}

// ── TOGGLE ─────────────────────────────────────────────────
if ($action === 'toggle' && $editId) {
    $db->prepare('UPDATE articles SET is_active=1-is_active WHERE id=? AND client_id=?')
       ->execute([$editId, $clientId]);
    redirect(BASE_URL . '/admin/pages/articles.php');
}

// ── SAVE ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $slug = trim($_POST['slug'] ?? '');
    if (!$slug) {
        // slug 自動衍生（用 cases / services 同邏輯）
        $base = preg_replace('/[\s\x{3000}・·、，,。．\.（）()【】「」"\'#&\/\\\\?]+/u', '-', trim($_POST['title'] ?? ''));
        $base = preg_replace('/-+/', '-', $base);
        $slug = trim($base, '-') ?: 'article-' . time();
    }

    $tags = trim($_POST['tags'] ?? '');
    $tagsJson = $tags ? json_encode(array_values(array_filter(array_map('trim', preg_split('/[,，、]/u', $tags)))), JSON_UNESCAPED_UNICODE) : null;

    $publishedAt = trim($_POST['published_at'] ?? '');
    $publishedAt = $publishedAt ?: null;

    $data = [
        'client_id'    => $clientId,
        'slug'         => $slug,
        'title'        => trim($_POST['title'] ?? ''),
        'summary'      => trim($_POST['summary'] ?? '') ?: null,
        'content'      => trim($_POST['content'] ?? '') ?: null,
        'category'     => trim($_POST['category'] ?? '') ?: null,
        'tags_json'    => $tagsJson,
        'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        'published_at' => $publishedAt,
    ];

    // 封面上傳
    if (!empty($_FILES['cover_image']['name'])) {
        $path = uploadImage($_FILES['cover_image'], 'article');
        if ($path) $data['cover_image'] = $path;
    } elseif (!empty($_POST['cover_image_existing'])) {
        $data['cover_image'] = $_POST['cover_image_existing'];
    }

    if ($editId) {
        // 唯一性檢查
        $chk = $db->prepare("SELECT id FROM articles WHERE client_id=? AND slug=? AND id!=? LIMIT 1");
        $chk->execute([$clientId, $slug, $editId]);
        if ($chk->fetch()) { setFlash('error', "slug「{$slug}」已被使用，請改一個。"); redirect(BASE_URL . '/admin/pages/articles.php?action=edit&id=' . $editId); }
        $sets = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
        $db->prepare("UPDATE articles SET $sets WHERE id=:id AND client_id=:cid")
           ->execute(array_merge($data, ['id' => $editId, 'cid' => $clientId]));
        setFlash('success', '已更新「' . $data['title'] . '」');
    } else {
        $chk = $db->prepare("SELECT id FROM articles WHERE client_id=? AND slug=? LIMIT 1");
        $chk->execute([$clientId, $slug]);
        if ($chk->fetch()) { setFlash('error', "slug「{$slug}」已被使用，請改一個。"); redirect(BASE_URL . '/admin/pages/articles.php?action=create'); }
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $db->prepare("INSERT INTO articles ($cols) VALUES ($vals)")->execute($data);
        setFlash('success', '已新增「' . $data['title'] . '」');
    }
    redirect(BASE_URL . '/admin/pages/articles.php');
}

// ── LIST / EDIT VIEW ───────────────────────────────────────
$item = null;
if ($action === 'edit' && $editId) {
    $st = $db->prepare('SELECT * FROM articles WHERE id=? AND client_id=?');
    $st->execute([$editId, $clientId]);
    $item = $st->fetch(PDO::FETCH_ASSOC);
    if (!$item) redirect(BASE_URL . '/admin/pages/articles.php');
}

$articles = [];
if ($action === 'list') {
    $st = $db->prepare("SELECT id, slug, title, summary, category, cover_image, is_active, sort_order, published_at, created_at, updated_at
                        FROM articles WHERE client_id=? ORDER BY COALESCE(published_at, created_at) DESC, sort_order ASC");
    $st->execute([$clientId]);
    $articles = $st->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="content-wrap">
  <?php echo flashHtml(); ?>

  <?php if ($action === 'list'): ?>

    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
      <div>
        <h1 style="font-size:1.6rem;font-weight:900;margin-bottom:6px;">📝 專欄文章管理</h1>
        <p style="color:#666;font-size:.9rem">寫專業知識文、案例分享，搶長尾關鍵字 SEO 流量</p>
      </div>
      <a href="?action=create" class="btn btn-primary">+ 新增文章</a>
    </div>

    <?php if (empty($articles)): ?>
      <div class="empty-state" style="text-align:center;padding:80px 20px;background:#fff;border-radius:12px;border:1px dashed #ccc">
        <div style="font-size:3rem;margin-bottom:14px">📝</div>
        <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:6px">還沒有任何文章</h3>
        <p style="color:#888;margin-bottom:20px">寫第一篇知識文，幫網站搶長尾關鍵字流量</p>
        <a href="?action=create" class="btn btn-primary">+ 新增文章</a>
      </div>
    <?php else: ?>
      <table class="data-table" style="width:100%;background:#fff;border-collapse:collapse;border-radius:10px;overflow:hidden">
        <thead>
          <tr>
            <th style="text-align:left;padding:14px;background:#f8f8f8;font-size:.85rem;font-weight:700">標題</th>
            <th style="text-align:left;padding:14px;background:#f8f8f8;font-size:.85rem;font-weight:700">分類</th>
            <th style="text-align:left;padding:14px;background:#f8f8f8;font-size:.85rem;font-weight:700">日期</th>
            <th style="text-align:center;padding:14px;background:#f8f8f8;font-size:.85rem;font-weight:700">狀態</th>
            <th style="text-align:right;padding:14px;background:#f8f8f8;font-size:.85rem;font-weight:700">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($articles as $a): ?>
          <tr style="border-top:1px solid #eee">
            <td style="padding:14px">
              <div style="font-weight:700"><?= h($a['title']) ?></div>
              <div style="font-size:.75rem;color:#999;margin-top:2px">/<?= h($a['slug']) ?></div>
            </td>
            <td style="padding:14px;font-size:.85rem;color:#666"><?= h($a['category'] ?? '—') ?></td>
            <td style="padding:14px;font-size:.85rem;color:#666"><?= date('Y/m/d', strtotime($a['published_at'] ?: $a['created_at'])) ?></td>
            <td style="padding:14px;text-align:center">
              <a href="?action=toggle&id=<?= $a['id'] ?>" style="text-decoration:none">
                <?php if ($a['is_active']): ?>
                  <span style="display:inline-block;padding:3px 10px;background:#d4edda;color:#155724;border-radius:20px;font-size:.75rem;font-weight:700">已發布</span>
                <?php else: ?>
                  <span style="display:inline-block;padding:3px 10px;background:#f1f1f1;color:#888;border-radius:20px;font-size:.75rem;font-weight:700">草稿</span>
                <?php endif; ?>
              </a>
            </td>
            <td style="padding:14px;text-align:right">
              <a href="?action=edit&id=<?= $a['id'] ?>" class="btn btn-sm" style="margin-right:4px">編輯</a>
              <a href="?action=delete&id=<?= $a['id'] ?>" onclick="return confirm('確定刪除?')" class="btn btn-sm btn-danger">刪除</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  <?php else: /* create / edit */ ?>

    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
      <h1 style="font-size:1.6rem;font-weight:900">
        <?= $editId ? '✏️ 編輯文章' : '➕ 新增文章' ?>
      </h1>
      <a href="<?= BASE_URL ?>/admin/pages/articles.php" class="btn">← 返回列表</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="card" style="background:#fff;padding:32px;border-radius:12px">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">

      <div class="form-group-admin">
        <label>標題 <span style="color:#c00">*</span></label>
        <input type="text" name="title" required class="form-control"
               value="<?= h($item['title'] ?? '') ?>"
               placeholder="例：裝潢後細清要不要做？什麼時候做最划算？">
      </div>

      <div class="grid-2" style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <div class="form-group-admin">
          <label>URL slug</label>
          <input type="text" name="slug" class="form-control"
                 value="<?= h($item['slug'] ?? '') ?>"
                 placeholder="留空自動從標題衍生">
          <div class="hint">中文 OK，URL 會自動 percent-encode。例：「裝潢後細清-選購指南」</div>
        </div>
        <div class="form-group-admin">
          <label>分類標籤</label>
          <input type="text" name="category" class="form-control"
                 value="<?= h($item['category'] ?? '') ?>"
                 placeholder="例：知識文 / 案例分享">
        </div>
      </div>

      <div class="form-group-admin">
        <label>摘要（meta description）</label>
        <textarea name="summary" rows="2" class="form-control" maxlength="300"
                  placeholder="一句話摘要，會出現在 Google 搜尋結果（150 字內）"><?= h($item['summary'] ?? '') ?></textarea>
      </div>

      <div class="form-group-admin">
        <label>封面圖</label>
        <?php if (!empty($item['cover_image'])): ?>
          <input type="hidden" name="cover_image_existing" value="<?= h($item['cover_image']) ?>">
          <img src="<?= BASE_URL . '/' . h($item['cover_image']) ?>" style="max-height:120px;border-radius:8px;margin-bottom:8px">
        <?php endif; ?>
        <input type="file" name="cover_image" accept="image/*" class="form-control">
        <div class="hint">建議比例 16:10，1200×750px 以上</div>
      </div>

      <div class="form-group-admin">
        <label>主文 <span style="color:#c00">*</span></label>
        <textarea name="content" rows="14" class="form-control wysiwyg" required
                  placeholder="輸入文章主文..."><?= h($item['content'] ?? '') ?></textarea>
        <div class="hint">建議至少 800-1500 字（避免 thin content）。富文字編輯器，支援標題、清單、圖片、引用等。</div>
      </div>

      <div class="grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
        <div class="form-group-admin">
          <label>tags（逗號分隔）</label>
          <?php $tags = !empty($item['tags_json']) ? implode(', ', json_decode($item['tags_json'], true) ?: []) : ''; ?>
          <input type="text" name="tags" class="form-control"
                 value="<?= h($tags) ?>"
                 placeholder="如：裝潢清潔, 入住前細清">
        </div>
        <div class="form-group-admin">
          <label>發布時間</label>
          <input type="datetime-local" name="published_at" class="form-control"
                 value="<?= !empty($item['published_at']) ? date('Y-m-d\TH:i', strtotime($item['published_at'])) : '' ?>">
          <div class="hint">留空 = 用 created_at</div>
        </div>
        <div class="form-group-admin">
          <label>排序值</label>
          <input type="number" name="sort_order" class="form-control"
                 value="<?= h($item['sort_order'] ?? 0) ?>" placeholder="0">
        </div>
      </div>

      <div class="form-group-admin">
        <label>
          <input type="checkbox" name="is_active" value="1" <?= (!isset($item) || $item['is_active']) ? 'checked' : '' ?>>
          已發布（顯示在前台）
        </label>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;padding-top:20px;border-top:1px solid #eee">
        <a href="<?= BASE_URL ?>/admin/pages/articles.php" class="btn">取消</a>
        <button type="submit" class="btn btn-primary"><?= $editId ? '更新' : '建立' ?> 文章</button>
      </div>
    </form>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
