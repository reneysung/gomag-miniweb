<?php
// admin/pages/geo_category.php  ─  交叉頁內容管理（geo_category_pages）
// 對應前台 /city/{city}/{cat}（citycat.php）；讓「沒店也想卡位」的城市×產業手寫內文跑 sanfeng 模式
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/front_functions.php';  // getCityMap()
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('error', '只有 Super Admin 可管理交叉頁內容');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '交叉頁內容';
$db = getDB();

$cityMap = getCityMap();  // slug => full_name
$cats = $db->query("SELECT id, slug, name, icon FROM categories WHERE is_active=1 ORDER BY sort_order, id")->fetchAll();
$catById = [];
foreach ($cats as $c) $catById[(int)$c['id']] = $c;

// ─── 編輯模式 ─────────────────────────────────────────────
$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM geo_category_pages WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

// ─── POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'add') {
        $cs  = trim($_POST['city_slug'] ?? '');
        $cid = (int)($_POST['category_id'] ?? 0);
        if (!isset($cityMap[$cs]) || !isset($catById[$cid])) {
            setFlash('error', '城市或分類無效');
            redirect(BASE_URL . '/admin/pages/geo_category.php');
        }
        try {
            $ins = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, is_active) VALUES (?, ?, 1)");
            $ins->execute([$cs, $cid]);
            setFlash('success', '✅ 已建立，開始編輯內容');
            redirect(BASE_URL . '/admin/pages/geo_category.php?edit=' . $db->lastInsertId());
        } catch (PDOException $e) {
            $ex = $db->prepare("SELECT id FROM geo_category_pages WHERE city_slug = ? AND category_id = ?");
            $ex->execute([$cs, $cid]);
            $exId = (int)$ex->fetchColumn();
            setFlash('error', '此城市×分類已存在，直接編輯');
            redirect(BASE_URL . '/admin/pages/geo_category.php' . ($exId ? '?edit=' . $exId : ''));
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        // FAQ：每行「問題 ||| 答案」
        $faqs = [];
        foreach (explode("\n", $_POST['faqs_text'] ?? '') as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $p = explode('|||', $line, 2);
            $q = trim($p[0]);
            $a = trim($p[1] ?? '');
            if ($q !== '') $faqs[] = ['q' => $q, 'a' => $a];
        }
        $fields = [
            'intro_html' => trim($_POST['intro_html'] ?? ''),
            'faqs'       => json_encode($faqs, JSON_UNESCAPED_UNICODE),
            'hero_image' => trim($_POST['hero_image'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_desc'  => trim($_POST['meta_desc']  ?? ''),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($fields)));
        $stmt = $db->prepare("UPDATE geo_category_pages SET $sets WHERE id = :id");
        $stmt->execute(array_merge($fields, ['id' => $id]));
        setFlash('success', '✅ 交叉頁內容已儲存');
        redirect(BASE_URL . '/admin/pages/geo_category.php');
    }
}

// ─── 列表 ─────────────────────────────────────────────────
$rows = $db->query("SELECT * FROM geo_category_pages ORDER BY city_slug, category_id")->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="page-header">
  <div>
    <h1>🧭 交叉頁內容</h1>
    <p style="color:var(--muted); margin-top:6px;">編輯（城市×產業）交叉頁的在地內文 + FAQ — 對應前台 <code>/city/{city}/{cat}</code>。有內容即可索引上線（即使該城該類沒店）。</p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<?php if ($editing):
    $eCat = $catById[(int)$editing['category_id']] ?? null;
    $eCityName = $cityMap[$editing['city_slug']] ?? $editing['city_slug'];
    $eFaqs = json_decode($editing['faqs'] ?? '[]', true) ?: [];
    $faqsText = implode("\n", array_map(fn($f) => ($f['q'] ?? '') . ' ||| ' . ($f['a'] ?? ''), $eFaqs));
?>
<!-- ════════ 編輯表單 ════════ -->
<form method="POST">
  <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">

  <div class="card">
    <div class="card-header">
      <h2>📝 <?= h($eCityName) ?> × <?= h($eCat['name'] ?? '?') ?>　<code>/city/<?= h($editing['city_slug']) ?>/<?= h($eCat['slug'] ?? '') ?></code></h2>
    </div>
    <div class="card-body">

      <div class="form-group">
        <label>在地內文（intro_html，可用 HTML）</label>
        <textarea name="intro_html" class="form-control" rows="8" placeholder="&lt;p&gt;台中清潔怎麼挑？先看…&lt;/p&gt;"><?= h($editing['intro_html']) ?></textarea>
        <small style="color:var(--muted);">撐排名 / 避 doorway。可用 &lt;p&gt;&lt;ul&gt;&lt;strong&gt; 等基本標籤（super admin 受信任，直接輸出）。</small>
      </div>

      <div class="form-group">
        <label>FAQ（每行一題，格式：<code>問題 ||| 答案</code>）</label>
        <textarea name="faqs_text" class="form-control" rows="6" placeholder="台中清潔費用大概多少？ ||| 一般居家清潔約 NT$2,000 起，依坪數與屋況調整。"><?= h($faqsText) ?></textarea>
        <small style="color:var(--muted);">會輸出成 FAQ 區塊 + FAQPage 結構化資料（rich snippet）。</small>
      </div>

      <div class="form-group">
        <label>Hero 背景圖 URL（可空）</label>
        <input type="url" name="hero_image" value="<?= h($editing['hero_image']) ?>" class="form-control" placeholder="https://...">
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
        <label><input type="checkbox" name="is_active" value="1" <?= $editing['is_active'] ? 'checked' : '' ?>> ☑ 啟用（內容生效於前台）</label>
      </div>
    </div>
  </div>

  <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg">💾 儲存</button>
    <a href="<?= BASE_URL ?>/admin/pages/geo_category.php" class="btn btn-ghost btn-lg">取消</a>
    <a href="<?= BASE_URL ?>/city/<?= h($editing['city_slug']) ?>/<?= h($eCat['slug'] ?? '') ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg" style="margin-left:auto;">🔍 預覽前台</a>
  </div>
</form>

<?php else: ?>
<!-- ════════ 新增 ════════ -->
<div class="card">
  <div class="card-header"><h2>➕ 新增交叉頁內容</h2></div>
  <div class="card-body">
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
      <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-group" style="margin:0;">
        <label>城市</label>
        <select name="city_slug" class="form-control" required>
          <?php foreach ($cityMap as $cs => $cn): ?>
          <option value="<?= h($cs) ?>"><?= h($cn) ?> (<?= h($cs) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label>分類</label>
        <select name="category_id" class="form-control" required>
          <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= h($c['icon']) ?> <?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">建立並編輯</button>
    </form>
  </div>
</div>

<!-- ════════ 列表 ════════ -->
<div class="card" style="margin-top:20px;">
  <div class="card-header"><h2>📋 已建立（<?= count($rows) ?>）</h2></div>
  <div class="card-body" style="padding:0;">
    <?php if (!$rows): ?>
    <div style="padding:24px; color:var(--muted);">尚無交叉頁內容。用上方表單建立第一筆，即可為「沒店但想卡位」的城市×產業手寫內文。</div>
    <?php else: ?>
    <table class="table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:var(--bg); border-bottom:1px solid var(--border);">
          <th style="padding:12px; text-align:left;">城市 × 分類</th>
          <th style="padding:12px; text-align:center; width:90px;">有內文</th>
          <th style="padding:12px; text-align:center; width:80px;">FAQ</th>
          <th style="padding:12px; text-align:center; width:90px;">狀態</th>
          <th style="padding:12px; text-align:right; width:200px;">操作</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $rCat  = $catById[(int)$r['category_id']] ?? null;
        $rCity = $cityMap[$r['city_slug']] ?? $r['city_slug'];
        $rFaqs = json_decode($r['faqs'] ?? '[]', true) ?: [];
        $hasIntro = trim((string)$r['intro_html']) !== '';
      ?>
      <tr style="border-bottom:1px solid var(--border);<?= !$r['is_active'] ? ' opacity:.5' : '' ?>">
        <td style="padding:12px;">
          <div style="font-weight:700;"><?= h($rCity) ?> × <?= h($rCat['name'] ?? '?') ?></div>
          <div style="font-size:.78rem; color:var(--muted);"><code>/city/<?= h($r['city_slug']) ?>/<?= h($rCat['slug'] ?? '') ?></code></div>
        </td>
        <td style="padding:12px; text-align:center;"><?= $hasIntro ? '✅' : '—' ?></td>
        <td style="padding:12px; text-align:center;"><?= count($rFaqs) ?: '—' ?></td>
        <td style="padding:12px; text-align:center;">
          <?php if ($r['is_active']): ?>
          <span style="background:#E8F5EE; color:#048A50; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">啟用</span>
          <?php else: ?>
          <span style="background:#fce; color:#a44; padding:4px 10px; border-radius:100px; font-size:.75rem; font-weight:700;">停用</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px; text-align:right; white-space:nowrap;">
          <a href="<?= BASE_URL ?>/city/<?= h($r['city_slug']) ?>/<?= h($rCat['slug'] ?? '') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">🔍 預覽</a>
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
