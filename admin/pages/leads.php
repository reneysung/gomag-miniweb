<?php
// admin/pages/leads.php  ─  聯絡詢問管理
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '客戶詢問列表';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();

// 確保表存在
$db->exec("CREATE TABLE IF NOT EXISTS `contact_leads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `contact` VARCHAR(200) NOT NULL,
  `service` VARCHAR(100) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `ip` VARCHAR(50) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 標記已讀
if (isset($_GET['read']) && (int)$_GET['read']) {
    $db->prepare('UPDATE contact_leads SET is_read=1 WHERE id=? AND client_id=?')
       ->execute([(int)$_GET['read'], $clientId]);
    redirect(BASE_URL . '/admin/pages/leads.php');
}
// 刪除
if (isset($_GET['delete']) && (int)$_GET['delete']) {
    $db->prepare('DELETE FROM contact_leads WHERE id=? AND client_id=?')
       ->execute([(int)$_GET['delete'], $clientId]);
    setFlash('success', '已刪除詢問記錄。');
    redirect(BASE_URL . '/admin/pages/leads.php');
}
// 全部標已讀
if (isset($_GET['readall'])) {
    $db->prepare('UPDATE contact_leads SET is_read=1 WHERE client_id=?')->execute([$clientId]);
    redirect(BASE_URL . '/admin/pages/leads.php');
}

$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$_t = $db->prepare('SELECT COUNT(*) FROM contact_leads WHERE client_id=?');
$_t->execute([$clientId]);
$total = (int)$_t->fetchColumn();

$_u = $db->prepare('SELECT COUNT(*) FROM contact_leads WHERE client_id=? AND is_read=0');
$_u->execute([$clientId]);
$unread = (int)$_u->fetchColumn();
$offset  = ($page - 1) * $perPage;
$totalPages = (int)ceil($total / $perPage);

$leads = $db->prepare('SELECT * FROM contact_leads WHERE client_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?');
$leads->bindValue(1, $clientId, PDO::PARAM_INT);
$leads->bindValue(2, $perPage,  PDO::PARAM_INT);
$leads->bindValue(3, $offset,   PDO::PARAM_INT);
$leads->execute();
$leads = $leads->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <?php if($unread>0): ?>
    <span class="badge badge-danger" style="font-size:.9rem;padding:4px 12px">🔔 <?= $unread ?> 則未讀</span>
    <?php endif; ?>
  </div>
  <?php if($unread>0): ?>
    <a href="?readall=1" class="btn btn-sm btn-ghost">✅ 全部標已讀</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header">
    <h2>📬 客戶詢問記錄（共 <?= $total ?> 筆）</h2>
  </div>
  <?php if(empty($leads)): ?>
    <div class="card-body" style="text-align:center;color:var(--muted);padding:40px">
      <div style="font-size:3rem;margin-bottom:12px">📭</div>
      <div>尚無詢問記錄</div>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>狀態</th><th>姓名</th><th>聯絡方式</th><th>詢問服務</th><th>訊息摘要</th><th>時間</th><th width="110">操作</th></tr>
      </thead>
      <tbody>
        <?php foreach($leads as $lead): ?>
        <tr style="<?= !$lead['is_read'] ? 'background:#fffdf0' : '' ?>">
          <td>
            <?php if(!$lead['is_read']): ?>
              <span class="badge badge-warning">未讀</span>
            <?php else: ?>
              <span class="badge badge-muted">已讀</span>
            <?php endif; ?>
          </td>
          <td><strong><?= h($lead['name']) ?></strong></td>
          <td>
            <a href="<?= filter_var($lead['contact'],FILTER_VALIDATE_EMAIL)?'mailto:':'tel:' ?><?= h($lead['contact']) ?>"
               style="color:var(--primary)">
              <?= h($lead['contact']) ?>
            </a>
          </td>
          <td><?= h($lead['service']??'─') ?></td>
          <td style="max-width:200px;font-size:.85rem">
            <span title="<?= h($lead['message']) ?>"><?= h(mb_strimwidth($lead['message'],0,50,'…')) ?></span>
          </td>
          <td style="color:var(--muted);font-size:.8rem;white-space:nowrap">
            <?= date('m/d H:i',strtotime($lead['created_at'])) ?>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <?php if(!$lead['is_read']): ?>
                <a href="?read=<?= $lead['id'] ?>" class="btn btn-sm btn-ghost" title="標已讀">✓</a>
              <?php endif; ?>
              <!-- 展開詳情 -->
              <button onclick="toggleDetail('d<?= $lead['id'] ?>')" class="btn btn-sm btn-outline">📄</button>
              <button onclick="if(confirm('確定刪除？'))location.href='?delete=<?= $lead['id'] ?>'" class="btn btn-sm btn-danger">🗑</button>
            </div>
          </td>
        </tr>
        <!-- 詳情展開列 -->
        <tr id="d<?= $lead['id'] ?>" style="display:none;background:#fafcff">
          <td colspan="7" style="padding:16px 20px">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:12px;font-size:.85rem">
              <div><strong>姓名：</strong><?= h($lead['name']) ?></div>
              <div><strong>聯絡：</strong><?= h($lead['contact']) ?></div>
              <div><strong>詢問服務：</strong><?= h($lead['service']??'未指定') ?></div>
            </div>
            <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:14px;font-size:.9rem;line-height:1.8;white-space:pre-wrap"><?= h($lead['message']) ?></div>
            <div style="margin-top:8px;font-size:.75rem;color:var(--muted)">IP：<?= h($lead['ip']??'') ?> · 時間：<?= $lead['created_at'] ?></div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- 分頁 -->
  <?php if($totalPages>1): ?>
  <div class="card-body" style="display:flex;gap:8px;justify-content:center">
    <?php for($p=1;$p<=$totalPages;$p++): ?>
      <a href="?p=<?= $p ?>" class="btn btn-sm <?= $p===$page?'btn-primary':'btn-ghost' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
function toggleDetail(id){
  const row=document.getElementById(id);
  row.style.display = row.style.display==='none' ? '' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
