<?php
// admin/pages/faqs.php  ─  FAQ 管理（掛在服務項目下）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = 'FAQ 管理';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();

$action   = $_GET['action']     ?? 'list';
$editId   = (int)($_GET['id']   ?? 0);
$svcFilter = (int)($_GET['svc'] ?? 0);

// ── DELETE ────────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    $db->prepare('DELETE FROM service_faqs WHERE id=? AND client_id=?')
       ->execute([$editId, $clientId]);
    setFlash('success', 'FAQ 已刪除。');
    redirect(BASE_URL . '/admin/pages/faqs.php' . ($svcFilter ? "?svc=$svcFilter" : ''));
}

// ── SORT（拖曳後 AJAX 送來）────────────────────────────────
if ($action === 'sort' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $ids = json_decode(file_get_contents('php://input'), true)['ids'] ?? [];
    foreach ($ids as $order => $id) {
        $db->prepare('UPDATE service_faqs SET sort_order=? WHERE id=? AND client_id=?')
           ->execute([$order, (int)$id, $clientId]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── SAVE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'client_id'  => $clientId,
        'service_id' => (int)$_POST['service_id'],
        'question'   => trim($_POST['question'] ?? ''),
        'answer'     => trim($_POST['answer']   ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
    ];

    if (!$data['service_id'] || !$data['question'] || !$data['answer']) {
        setFlash('error', '請填寫所有必填欄位');
    } else {
        $saveId = (int)($_POST['edit_id'] ?? 0);
        if ($saveId) {
            $sets = implode(',', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
            $db->prepare("UPDATE service_faqs SET $sets WHERE id=:id AND client_id=:cid")
               ->execute(array_merge($data, ['id'=>$saveId,'cid'=>$clientId]));
            setFlash('success', 'FAQ 已更新！');
        } else {
            $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
            $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
            $db->prepare("INSERT INTO service_faqs ($cols) VALUES ($vals)")->execute($data);
            setFlash('success', 'FAQ 已新增！');
        }
    }
    $goSvc = (int)($_POST['service_id'] ?? 0);
    redirect(BASE_URL . '/admin/pages/faqs.php' . ($goSvc ? "?svc=$goSvc" : ''));
}

// 服務清單
$services = $db->prepare('SELECT id,name,icon FROM services WHERE client_id=? AND is_active=1 ORDER BY sort_order');
$services->execute([$clientId]);
$services = $services->fetchAll();

// 編輯資料
$editData = null;
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare('SELECT * FROM service_faqs WHERE id=? AND client_id=?');
    $stmt->execute([$editId, $clientId]);
    $editData = $stmt->fetch();
    if ($editData && !$svcFilter) $svcFilter = (int)$editData['service_id'];
}

// 列表（依服務篩選）
$listSql = 'SELECT f.*, s.name AS svc_name FROM service_faqs f LEFT JOIN services s ON f.service_id=s.id WHERE f.client_id=?';
$params  = [$clientId];
if ($svcFilter) { $listSql .= ' AND f.service_id=?'; $params[] = $svcFilter; }
$listSql .= ' ORDER BY f.service_id, f.sort_order, f.id';
$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$list = $listStmt->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">

<!-- ── 左側：新增/編輯表單 ────────────────────── -->
<div style="width:340px;flex-shrink:0">
  <div class="card">
    <div class="card-header">
      <h2><?= $editId ? '✏️ 編輯 FAQ' : '➕ 新增 FAQ' ?></h2>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_token"   value="<?= csrfToken() ?>">
        <input type="hidden" name="edit_id"  value="<?= h($editId) ?>">

        <div class="form-group-admin">
          <label>所屬服務 *</label>
          <select name="service_id" class="form-control" required>
            <option value="">── 選擇服務 ──</option>
            <?php foreach ($services as $svc): ?>
            <option value="<?= $svc['id'] ?>"
              <?= ($editData['service_id'] ?? $svcFilter) == $svc['id'] ? 'selected' : '' ?>>
              <?= h($svc['icon']??'') ?> <?= h($svc['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group-admin">
          <label>問題 *</label>
          <input type="text" name="question" class="form-control" required
                 value="<?= h($editData['question'] ?? '') ?>"
                 placeholder="例：費用如何計算？">
        </div>

        <div class="form-group-admin">
          <label>答案 *</label>
          <textarea name="answer" class="form-control" rows="5" required
                    placeholder="詳細回答..."><?= h($editData['answer'] ?? '') ?></textarea>
        </div>

        <div class="form-group-admin">
          <label>排序</label>
          <input type="number" name="sort_order" class="form-control" style="width:80px"
                 value="<?= h($editData['sort_order'] ?? 0) ?>">
        </div>

        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary">💾 儲存</button>
          <?php if ($editId): ?>
            <a href="<?= BASE_URL ?>/admin/pages/faqs.php<?= $svcFilter ? "?svc=$svcFilter" : '' ?>"
               class="btn btn-ghost">取消</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- 服務篩選快捷 -->
  <div class="card" style="margin-top:16px">
    <div class="card-header"><h2>🔍 依服務篩選</h2></div>
    <div class="card-body" style="padding:8px 0">
      <a href="<?= BASE_URL ?>/admin/pages/faqs.php"
         style="display:block;padding:9px 16px;font-size:.875rem;font-weight:<?= !$svcFilter?'700':'500' ?>;color:<?= !$svcFilter?'var(--primary)':'var(--text)' ?>;border-left:3px solid <?= !$svcFilter?'var(--accent)':'transparent' ?>">
        全部服務（<?= count($list) ?>）
      </a>
      <?php foreach ($services as $svc):
        $cnt = count(array_filter($list, fn($f) => $f['service_id'] == $svc['id']));
      ?>
      <a href="?svc=<?= $svc['id'] ?>"
         style="display:block;padding:9px 16px;font-size:.875rem;font-weight:<?= $svcFilter==$svc['id']?'700':'500' ?>;color:<?= $svcFilter==$svc['id']?'var(--primary)':'var(--text)' ?>;border-left:3px solid <?= $svcFilter==$svc['id']?'var(--accent)':'transparent' ?>">
        <?= h($svc['icon']??'') ?> <?= h($svc['name']) ?> <span style="color:#999;font-size:.8rem">(<?= $cnt ?>)</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── 右側：FAQ 列表 ─────────────────────────── -->
<div style="flex:1;min-width:0">
  <div class="card">
    <div class="card-header">
      <h2>❓ FAQ 列表
        <?php if ($svcFilter): ?>
          <span style="font-size:.8rem;color:var(--muted);font-weight:400">
            ─ <?= h(array_column($services,'name','id')[$svcFilter] ?? '') ?>
          </span>
        <?php endif; ?>
      </h2>
      <div style="font-size:.75rem;color:var(--muted)">
        可拖曳列調整順序
      </div>
    </div>
    <div class="table-wrap">
      <?php if (empty($list)): ?>
        <div style="text-align:center;padding:36px;color:var(--muted)">
          尚無 FAQ，請在左側新增
        </div>
      <?php else: ?>

      <?php
      // 依服務分組顯示
      $grouped = [];
      foreach ($list as $faq) {
          $grouped[$faq['service_id']][] = $faq;
      }
      ?>

      <?php foreach ($grouped as $svcId => $faqs): ?>
      <div style="border-bottom:2px solid var(--bg)">
        <div style="padding:10px 16px;background:var(--bg);font-size:.8rem;font-weight:700;color:var(--muted);letter-spacing:.05em">
          <?php $svcName = array_column($services,'name','id')[$svcId] ?? '未分類'; ?>
          📂 <?= h($svcName) ?> (<?= count($faqs) ?>)
        </div>
        <ul class="faq-sortable" data-svc="<?= $svcId ?>"
            style="list-style:none;margin:0;padding:0">
          <?php foreach ($faqs as $faq): ?>
          <li data-id="<?= $faq['id'] ?>"
              style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);cursor:grab">
            <div style="color:var(--muted);font-size:1.2rem;flex-shrink:0;margin-top:2px;cursor:grab" title="拖曳排序">⠿</div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:.9rem;margin-bottom:4px"><?= h($faq['question']) ?></div>
              <div style="font-size:.82rem;color:#666;line-height:1.6"><?= h(mb_strimwidth($faq['answer'],0,80,'…')) ?></div>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0">
              <a href="?action=edit&id=<?= $faq['id'] ?><?= $svcFilter?"&svc=$svcFilter":'' ?>"
                 class="btn btn-sm btn-ghost">✏️</a>
              <button onclick="if(confirm('確定刪除此 FAQ？'))location.href='?action=delete&id=<?= $faq['id'] ?><?= $svcFilter?"&svc=$svcFilter":'' ?>'"
                      class="btn btn-sm btn-danger">🗑</button>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<script>
// 簡易拖曳排序
let dragEl = null, dragSvc = null;

document.querySelectorAll('.faq-sortable li').forEach(li => {
  li.addEventListener('dragstart', e => {
    dragEl  = li;
    dragSvc = li.closest('.faq-sortable').dataset.svc;
    li.style.opacity = '.4';
  });
  li.addEventListener('dragend', () => {
    li.style.opacity = '';
    saveSortOrder(li.closest('.faq-sortable'));
  });
  li.addEventListener('dragover', e => {
    e.preventDefault();
    const ul = li.closest('.faq-sortable');
    if (dragEl && dragEl !== li && ul.dataset.svc === dragSvc) {
      const bounding = li.getBoundingClientRect();
      const offset   = e.clientY - bounding.top - bounding.height / 2;
      ul.insertBefore(dragEl, offset < 0 ? li : li.nextSibling);
    }
  });
  li.setAttribute('draggable', true);
});

async function saveSortOrder(ul) {
  const ids = [...ul.querySelectorAll('li')].map(li => li.dataset.id);
  await fetch('?action=sort', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids })
  });
}
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
