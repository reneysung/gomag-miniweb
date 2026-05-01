<?php
// admin/pages/clients.php  ─  客戶列表（Super Admin）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '所有客戶';
$db = getDB();

// ── 切換目前管理的客戶 ─────────────────────────────────────
if (isset($_GET['switch'])) {
    $switchId = (int)$_GET['switch'];
    if ($switchId) {
        $chk = $db->prepare('SELECT id FROM clients WHERE id=? AND is_active=1');
        $chk->execute([$switchId]);
        if ($chk->fetch()) {
            $_SESSION['viewing_client_id'] = $switchId;
            setFlash('success', '已切換至客戶 #' . $switchId);
        }
    } else {
        unset($_SESSION['viewing_client_id']);
    }
    redirect(BASE_URL . '/admin/index.php');
}

// ── 停用 / 啟用 ────────────────────────────────────────────
if (isset($_GET['toggle']) && (int)$_GET['toggle']) {
    $db->prepare('UPDATE clients SET is_active=1-is_active WHERE id=?')
       ->execute([(int)$_GET['toggle']]);
    redirect(BASE_URL . '/admin/pages/clients.php');
}

// 取得所有客戶 + 各項統計
$clients = $db->query("
    SELECT
        c.*,
        (SELECT COUNT(*) FROM services   WHERE client_id=c.id AND is_active=1) AS svc_cnt,
        (SELECT COUNT(*) FROM cases      WHERE client_id=c.id AND is_active=1) AS case_cnt,
        (SELECT COUNT(*) FROM testimonials WHERE client_id=c.id AND is_active=1) AS review_cnt,
        (SELECT COUNT(*) FROM contact_leads  WHERE client_id=c.id AND is_read=0) AS unread_cnt,
        (SELECT username FROM admin_users WHERE client_id=c.id AND role='client' LIMIT 1) AS mgr_account,
        ct.color_primary,
        ct.color_accent
    FROM clients c
    LEFT JOIN client_themes ct ON ct.client_id=c.id AND ct.is_active=1
    ORDER BY c.id
")->fetchAll();

$currentViewing = $_SESSION['viewing_client_id'] ?? null;

// 取得所有不重複的行業類別（用於篩選）
$industries = [];
foreach ($clients as $cl) {
    $ind = trim($cl['industry'] ?? '');
    if ($ind && !in_array($ind, $industries)) $industries[] = $ind;
}
sort($industries);

require_once __DIR__ . '/../includes/layout_head.php';
?>

<!-- 搜尋 & 篩選列 -->
<div style="margin-bottom:20px">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <!-- 搜尋框 -->
    <div style="flex:1;min-width:220px;position:relative">
      <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:1rem;color:var(--muted)">🔍</span>
      <input type="text" id="clientSearch" class="form-control"
             placeholder="搜尋客戶名稱、行業、電話、地址..."
             style="padding-left:38px;height:42px;font-size:.9rem"
             autocomplete="off">
    </div>
    <!-- 行業篩選 -->
    <select id="industryFilter" class="form-control" style="width:auto;min-width:150px;height:42px;font-size:.9rem">
      <option value="">全部行業</option>
      <?php foreach ($industries as $ind): ?>
      <option value="<?= h($ind) ?>"><?= h($ind) ?></option>
      <?php endforeach; ?>
    </select>
    <!-- 狀態篩選 -->
    <select id="statusFilter" class="form-control" style="width:auto;min-width:120px;height:42px;font-size:.9rem">
      <option value="">全部狀態</option>
      <option value="active">啟用中</option>
      <option value="inactive">已停用</option>
    </select>
    <!-- 排序 -->
    <select id="sortOrder" class="form-control" style="width:auto;min-width:140px;height:42px;font-size:.9rem">
      <option value="id-asc">建立順序 ↑</option>
      <option value="id-desc">建立順序 ↓</option>
      <option value="name-asc">名稱 A→Z</option>
      <option value="name-desc">名稱 Z→A</option>
      <option value="unread-desc">未讀最多</option>
    </select>
    <a href="<?= BASE_URL ?>/admin/pages/new_client.php" class="btn btn-primary btn-sm" style="height:42px;display:flex;align-items:center">➕ 新增客戶</a>
  </div>
  <!-- 結果計數 -->
  <div style="display:flex;align-items:center;gap:12px;margin-top:10px">
    <p style="color:var(--muted);font-size:.82rem" id="resultCount">共 <?= count($clients) ?> 個客戶</p>
    <button type="button" id="clearFilters" class="btn btn-ghost btn-sm" style="font-size:.78rem;display:none">✕ 清除篩選</button>
  </div>
</div>

<!-- 無結果提示 -->
<div id="noResults" style="display:none;text-align:center;padding:48px">
  <div style="font-size:3rem;margin-bottom:12px">🔍</div>
  <div style="font-weight:700;color:var(--text);margin-bottom:8px">找不到符合的客戶</div>
  <div style="font-size:.85rem;color:var(--muted)">試試其他關鍵字或清除篩選條件</div>
</div>

<div id="clientGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">
  <?php foreach ($clients as $cl): ?>
  <div class="card client-card"
       data-id="<?= $cl['id'] ?>"
       data-name="<?= h($cl['brand_name']) ?>"
       data-industry="<?= h($cl['industry'] ?? '') ?>"
       data-phone="<?= h($cl['phone'] ?? '') ?>"
       data-address="<?= h($cl['address'] ?? '') ?>"
       data-slug="<?= h($cl['slug']) ?>"
       data-active="<?= $cl['is_active'] ? '1' : '0' ?>"
       data-unread="<?= (int)$cl['unread_cnt'] ?>"
       style="<?= !$cl['is_active'] ? 'opacity:.6' : '' ?><?= $currentViewing == $cl['id'] ? ';border:2px solid var(--primary)' : '' ?>">

    <!-- 色彩頂部條 -->
    <div style="height:6px;background:linear-gradient(90deg, <?= h($cl['color_primary']??'#1c3d3d') ?>, <?= h($cl['color_accent']??'#c8922a') ?>)"></div>

    <div class="card-body">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:16px">
        <div>
          <div style="font-size:1.05rem;font-weight:800;color:var(--text)"><?= h($cl['brand_name']) ?></div>
          <div style="font-size:.8rem;color:var(--muted);margin-top:2px">
            <?= h($cl['industry']??'') ?>
            <?php if ($cl['phone']): ?>&nbsp;·&nbsp;<?= h($cl['phone']) ?><?php endif; ?>
          </div>
          <div style="font-size:.78rem;margin-top:4px">
            <code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:.75rem">
              /<?= h($cl['slug']) ?>/
            </code>
          </div>
        </div>
        <div>
          <?php if ($cl['is_active']): ?>
            <span class="badge badge-success">啟用</span>
          <?php else: ?>
            <span class="badge badge-danger">停用</span>
          <?php endif; ?>
          <?php if ($currentViewing == $cl['id']): ?>
            <div style="font-size:.7rem;color:var(--primary);font-weight:700;margin-top:4px">▶ 管理中</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 統計數字 -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:16px">
        <?php
        $stats = [
          ['🛠️','服務',$cl['svc_cnt']],
          ['📸','案例',$cl['case_cnt']],
          ['⭐','評價',$cl['review_cnt']],
          ['📬','未讀',$cl['unread_cnt']],
        ];
        foreach ($stats as $s): ?>
        <div style="text-align:center;background:var(--bg);border-radius:8px;padding:8px 4px">
          <div style="font-size:1rem"><?= $s[0] ?></div>
          <div style="font-size:1rem;font-weight:800;color:<?= $s[2]>0&&$s[1]==='未讀'?'var(--danger)':'var(--primary)' ?>"><?= $s[2] ?></div>
          <div style="font-size:.65rem;color:var(--muted)"><?= $s[1] ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- 管理員帳號 -->
      <?php if ($cl['mgr_account']): ?>
      <div style="font-size:.78rem;color:var(--muted);margin-bottom:12px">
        🔑 帳號：<strong><?= h($cl['mgr_account']) ?></strong>
      </div>
      <?php endif; ?>

      <!-- 操作按鈕 -->
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="?switch=<?= $cl['id'] ?>" class="btn btn-sm btn-primary" style="flex:1;justify-content:center">
          <?= $currentViewing==$cl['id'] ? '✅ 管理中' : '🎛️ 切換管理' ?>
        </a>
        <a href="<?= BASE_URL ?>/<?= h($cl['slug']) ?>/" target="_blank"
           class="btn btn-sm btn-ghost" title="開啟前台">🌐</a>
        <?php if ($cl['unread_cnt'] > 0): ?>
        <a href="<?= BASE_URL ?>/admin/pages/leads.php" class="btn btn-sm btn-accent" title="未讀詢問">
          📬 <?= $cl['unread_cnt'] ?>
        </a>
        <?php endif; ?>
        <a href="?toggle=<?= $cl['id'] ?>"
           class="btn btn-sm <?= $cl['is_active']?'btn-ghost':'btn-outline' ?>"
           onclick="return confirm('確定<?= $cl['is_active']?'停用':'啟用' ?>此客戶？')"
           title="<?= $cl['is_active']?'停用':'啟用' ?>">
          <?= $cl['is_active'] ? '⏸' : '▶' ?>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($clients)): ?>
<div class="card" style="text-align:center;padding:48px">
  <div style="font-size:3rem;margin-bottom:16px">🏪</div>
  <div style="font-weight:700;margin-bottom:16px">尚無客戶</div>
  <a href="<?= BASE_URL ?>/admin/pages/new_client.php" class="btn btn-primary">➕ 新增第一個客戶</a>
</div>
<?php endif; ?>

<script>
(function(){
  const searchInput    = document.getElementById('clientSearch');
  const industrySelect = document.getElementById('industryFilter');
  const statusSelect   = document.getElementById('statusFilter');
  const sortSelect     = document.getElementById('sortOrder');
  const clearBtn       = document.getElementById('clearFilters');
  const resultCount    = document.getElementById('resultCount');
  const noResults      = document.getElementById('noResults');
  const grid           = document.getElementById('clientGrid');
  const cards          = Array.from(document.querySelectorAll('.client-card'));

  function applyFilters(){
    const keyword  = searchInput.value.trim().toLowerCase();
    const industry = industrySelect.value;
    const status   = statusSelect.value;
    let visible = 0;

    cards.forEach(card => {
      const name    = (card.dataset.name || '').toLowerCase();
      const ind     = card.dataset.industry || '';
      const phone   = (card.dataset.phone || '').toLowerCase();
      const address = (card.dataset.address || '').toLowerCase();
      const slug    = (card.dataset.slug || '').toLowerCase();
      const active  = card.dataset.active;

      let show = true;

      // 關鍵字搜尋
      if (keyword && !(name.includes(keyword) || ind.toLowerCase().includes(keyword) || phone.includes(keyword) || address.includes(keyword) || slug.includes(keyword))) {
        show = false;
      }
      // 行業篩選
      if (industry && ind !== industry) show = false;
      // 狀態篩選
      if (status === 'active' && active !== '1') show = false;
      if (status === 'inactive' && active !== '0') show = false;

      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    // 排序
    const sort = sortSelect.value;
    const sorted = cards.filter(c => c.style.display !== 'none');
    sorted.sort((a, b) => {
      switch(sort){
        case 'id-desc':    return parseInt(b.dataset.id) - parseInt(a.dataset.id);
        case 'name-asc':   return a.dataset.name.localeCompare(b.dataset.name, 'zh-Hant');
        case 'name-desc':  return b.dataset.name.localeCompare(a.dataset.name, 'zh-Hant');
        case 'unread-desc':return parseInt(b.dataset.unread) - parseInt(a.dataset.unread);
        default:           return parseInt(a.dataset.id) - parseInt(b.dataset.id);
      }
    });
    // 重新排列 DOM
    sorted.forEach(card => grid.appendChild(card));
    // 隱藏的也放回去
    cards.filter(c => c.style.display === 'none').forEach(card => grid.appendChild(card));

    // 更新計數
    const total = cards.length;
    resultCount.textContent = keyword || industry || status
      ? '找到 ' + visible + ' / ' + total + ' 個客戶'
      : '共 ' + total + ' 個客戶';

    // 無結果提示
    noResults.style.display = visible === 0 ? 'block' : 'none';
    grid.style.display = visible === 0 ? 'none' : '';

    // 清除按鈕
    clearBtn.style.display = (keyword || industry || status) ? '' : 'none';
  }

  searchInput.addEventListener('input', applyFilters);
  industrySelect.addEventListener('change', applyFilters);
  statusSelect.addEventListener('change', applyFilters);
  sortSelect.addEventListener('change', applyFilters);

  clearBtn.addEventListener('click', function(){
    searchInput.value = '';
    industrySelect.value = '';
    statusSelect.value = '';
    sortSelect.value = 'id-asc';
    applyFilters();
    searchInput.focus();
  });

  // 鍵盤快捷：Ctrl+K 或 Cmd+K 聚焦搜尋
  document.addEventListener('keydown', function(e){
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      searchInput.focus();
      searchInput.select();
    }
  });
})();
</script>
<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
