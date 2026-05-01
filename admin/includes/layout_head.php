<?php
// admin/includes/layout_head.php  ─  後台共用 Header + 側邊欄
// 使用方式：require_once __DIR__ . '/../includes/layout_head.php';
// 需在引入前定義 $pageTitle

$admin = currentAdmin();
$clientId = getCurrentClientId();
$clientData = $clientId ? getClientData($clientId) : [];
$flash = getFlash();

// 取得目前頁面 slug，用於側邊欄 active 狀態
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? '後台管理') ?> ─ 店家好口碑</title>

<!-- Quill WYSIWYG 編輯器（開源、免費、無浮水印） -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary:     #1c3d3d;
  --primary-dark:#14302f;
  --primary-light:#2a5252;
  --accent:      #c8922a;
  --sidebar-w:   240px;
  --header-h:    60px;
  --bg:          #f4f6f4;
  --white:       #ffffff;
  --text:        #1a2a1a;
  --muted:       #6b7c6b;
  --border:      #dde5dd;
  --danger:      #c0392b;
  --success:     #27ae60;
  --warning:     #f39c12;
  --radius:      10px;
  --shadow-sm:   0 1px 4px rgba(0,0,0,.08);
  --shadow:      0 4px 20px rgba(0,0,0,.1);
}

body {
  font-family: 'Noto Sans TC', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg);
  color: var(--text);
  font-size: .9rem;
  min-height: 100vh;
}

/* ─── Sidebar ─────────────────────────────────────────── */
.sidebar {
  position: fixed;
  top: 0; left: 0;
  width: var(--sidebar-w);
  height: 100vh;
  background: var(--primary);
  color: #fff;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  z-index: 100;
  transition: transform .25s;
}

.sidebar-logo {
  padding: 20px 20px 16px;
  border-bottom: 1px solid rgba(255,255,255,.1);
}
.sidebar-logo .logo-main {
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: .05em;
  display: flex;
  align-items: center;
  gap: 8px;
}
.sidebar-logo .logo-main span { font-size: 1.2rem; }
.sidebar-logo .logo-sub {
  font-size: .7rem;
  opacity: .6;
  margin-top: 2px;
  letter-spacing: .03em;
}

/* 客戶切換器 */
.client-badge {
  margin: 12px 14px;
  background: rgba(255,255,255,.1);
  border-radius: 8px;
  padding: 10px 12px;
  font-size: .8rem;
}
.client-badge .label { opacity: .7; font-size: .7rem; margin-bottom: 2px; }
.client-badge .name  { font-weight: 700; font-size: .9rem; }

/* 導航 */
.sidebar nav { padding: 8px 0; flex: 1; }
.nav-section {
  padding: 12px 16px 4px;
  font-size: .65rem;
  letter-spacing: .12em;
  text-transform: uppercase;
  opacity: .5;
  font-weight: 600;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 18px;
  color: rgba(255,255,255,.8);
  text-decoration: none;
  font-size: .875rem;
  font-weight: 500;
  border-left: 3px solid transparent;
  transition: all .15s;
  cursor: pointer;
}
.nav-item:hover {
  background: rgba(255,255,255,.08);
  color: #fff;
}
.nav-item.active {
  background: rgba(255,255,255,.12);
  color: #fff;
  border-left-color: var(--accent);
  font-weight: 700;
}
.nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,.1);
  font-size: .75rem;
  opacity: .6;
  text-align: center;
}

/* ─── Top Header ──────────────────────────────────────── */
.topbar {
  position: fixed;
  top: 0;
  left: var(--sidebar-w);
  right: 0;
  height: var(--header-h);
  background: var(--white);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  z-index: 90;
  box-shadow: var(--shadow-sm);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.topbar-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
}
.breadcrumb {
  font-size: .8rem;
  color: var(--muted);
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-view-site {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: .8rem;
  font-weight: 600;
  color: var(--primary);
  text-decoration: none;
  transition: all .15s;
}
.btn-view-site:hover {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}

.admin-avatar {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.avatar-circle {
  width: 34px; height: 34px;
  background: var(--primary);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  font-weight: 700;
}
.admin-name { font-size: .85rem; font-weight: 600; }

/* ─── Main Content ────────────────────────────────────── */
.main-wrap {
  margin-left: var(--sidebar-w);
  padding-top: var(--header-h);
  min-height: 100vh;
}
.main-content {
  padding: 28px;
  max-width: 1200px;
}

/* ─── Flash 訊息 ──────────────────────────────────────── */
.flash {
  padding: 12px 18px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: .875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
}
.flash-success { background: #eafaf1; border: 1px solid #a9dfbf; color: #1e8449; }
.flash-error   { background: #fef9e7; border: 1px solid #f9e79f; color: #7d6608; }
.flash-danger  { background: #fdf2f8; border: 1px solid #f1948a; color: var(--danger); }

/* ─── 通用元件 ────────────────────────────────────────── */
.card {
  background: var(--white);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.card-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.card-header h2 { font-size: 1rem; font-weight: 700; }
.card-body { padding: 20px; }

/* 按鈕 */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 7px;
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  border: 1.5px solid transparent;
  text-decoration: none;
  transition: all .15s;
  white-space: nowrap;
}
.btn-primary   { background: var(--primary); color: #fff; border-color: var(--primary); }
.btn-primary:hover { background: var(--primary-dark); }
.btn-accent    { background: var(--accent); color: #fff; border-color: var(--accent); }
.btn-accent:hover { filter: brightness(1.1); }
.btn-outline   { background: transparent; color: var(--primary); border-color: var(--primary); }
.btn-outline:hover { background: var(--primary); color: #fff; }
.btn-danger    { background: var(--danger); color: #fff; border-color: var(--danger); }
.btn-danger:hover { filter: brightness(1.1); }
.btn-sm        { padding: 5px 10px; font-size: .8rem; }
.btn-ghost     { background: transparent; color: var(--muted); border-color: var(--border); }
.btn-ghost:hover { background: var(--bg); color: var(--text); }

/* 表格 */
.table-wrap { overflow-x: auto; }
table.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td {
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid var(--border);
  font-size: .875rem;
}
.data-table th {
  background: var(--bg);
  font-weight: 700;
  font-size: .8rem;
  letter-spacing: .04em;
  color: var(--muted);
  text-transform: uppercase;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #fafcfa; }

/* 表單 */
.form-row { display: grid; gap: 20px; margin-bottom: 20px; }
.form-row.cols-2 { grid-template-columns: 1fr 1fr; }
.form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.form-group-admin { margin-bottom: 18px; }
.form-group-admin label {
  display: block;
  font-size: .8rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
  letter-spacing: .02em;
}
.form-group-admin .hint { font-size: .75rem; color: var(--muted); margin-top: 4px; }

.form-control {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid var(--border);
  border-radius: 7px;
  font-size: .9rem;
  color: var(--text);
  background: var(--bg);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.form-control:focus {
  border-color: var(--primary);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(28,61,61,.08);
}
textarea.form-control { resize: vertical; min-height: 90px; }

/* 狀態 badge */
.badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: .75rem;
  font-weight: 600;
  letter-spacing: .02em;
}
.badge-success { background: #eafaf1; color: #1e8449; }
.badge-warning { background: #fef9e7; color: #7d6608; }
.badge-danger  { background: #fdf2f8; color: var(--danger); }
.badge-muted   { background: var(--bg); color: var(--muted); }

/* 圖片預覽 */
.img-preview {
  width: 64px; height: 64px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid var(--border);
}

/* 行動版側邊欄切換 */
.sidebar-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.4rem;
  cursor: pointer;
  color: var(--primary);
}

@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .topbar { left: 0; }
  .main-wrap { margin-left: 0; }
  .sidebar-toggle { display: block; }
  .form-row.cols-2, .form-row.cols-3 { grid-template-columns: 1fr; }
  .main-content { padding: 16px; }
}
</style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-main"><span>🏪</span> 店家好口碑</div>
    <div class="logo-sub">Mini Website Manager</div>
  </div>

  <?php if ($clientData): ?>
  <div class="client-badge">
    <div class="label">目前管理客戶</div>
    <div class="name">📋 <?= h($clientData['brand_name']) ?></div>
  </div>
  <?php endif; ?>

  <nav>
    <div class="nav-section">總覽</div>
    <a class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/index.php">
      <span class="icon">📊</span> Dashboard
    </a>
    <?php if (currentAdmin()['role'] === 'super'): ?>
    <a class="nav-item <?= $currentPage === 'new_client' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/new_client.php">
      <span class="icon">➕</span> 新增客戶
    </a>
    <?php endif; ?>

    <div class="nav-section">網站設定</div>
    <a class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/settings.php">
      <span class="icon">⚙️</span> 基本資訊
    </a>
    <a class="nav-item <?= $currentPage === 'themes' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/themes.php">
      <span class="icon">🎨</span> 主題顏色
    </a>
    <a class="nav-item <?= $currentPage === 'social' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/social.php">
      <span class="icon">📱</span> 社群連結
    </a>
    <a class="nav-item <?= $currentPage === 'seo' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/seo.php">
      <span class="icon">🔍</span> SEO 設定
    </a>

    <div class="nav-section">內容管理</div>
    <a class="nav-item <?= in_array($currentPage, ['store_blocks', 'store_block_edit']) ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/store_blocks.php">
      <span class="icon">📦</span> 區塊管理
      <span style="margin-left:auto; background:#FF5A36; color:#fff; font-size:.6rem; font-weight:800; padding:1px 6px; border-radius:20px;">NEW</span>
    </a>
    <?php if (($admin['role'] ?? '') === 'super'): ?>
    <a class="nav-item <?= $currentPage === 'cities' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/cities.php">
      <span class="icon">🌏</span> 城市管理
      <span style="margin-left:auto; background:#FF5A36; color:#fff; font-size:.6rem; font-weight:800; padding:1px 6px; border-radius:20px;">NEW</span>
    </a>
    <?php endif; ?>
    <a class="nav-item <?= in_array($currentPage, ['services', 'service_edit']) ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/services.php">
      <span class="icon">🛠️</span> 服務項目（舊）
    </a>
    <a class="nav-item <?= $currentPage === 'faqs' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/faqs.php">
      <span class="icon">❓</span> FAQ 管理
    </a>
    <a class="nav-item <?= in_array($currentPage, ['cases', 'case_edit']) ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/cases.php">
      <span class="icon">📸</span> 施工案例
    </a>
    <a class="nav-item <?= $currentPage === 'testimonials' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/testimonials.php">
      <span class="icon">⭐</span> 客戶評價
    </a>

    <a class="nav-item <?= $currentPage === 'leads' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/leads.php">
      <span class="icon">📬</span> 客戶詢問
      <?php
      // 未讀詢問 badge
      if ($clientId) {
        try {
          $db2 = getDB();
          $db2->exec("CREATE TABLE IF NOT EXISTS contact_leads (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, client_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, contact VARCHAR(200) NOT NULL, service VARCHAR(100) DEFAULT NULL, message TEXT NOT NULL, ip VARCHAR(50) DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
          $unreadStmt = $db2->prepare('SELECT COUNT(*) FROM contact_leads WHERE client_id=? AND is_read=0');
          $unreadStmt->execute([$clientId]);
          $unreadCount = (int)$unreadStmt->fetchColumn();
          if ($unreadCount > 0) echo '<span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:800;padding:1px 7px;border-radius:20px">' . $unreadCount . '</span>';
        } catch(Exception $e) {}
      }
      ?>
    </a>

    <?php if (currentAdmin()['role'] === 'super'): ?>
    <div class="nav-section">系統管理</div>
    <a class="nav-item <?= $currentPage === 'clients' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/clients.php">
      <span class="icon">🏪</span> 所有客戶
    </a>
    <a class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/categories.php">
      <span class="icon">📂</span> 分類管理
    </a>
    <a class="nav-item <?= $currentPage === 'banners' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/banners.php">
      <span class="icon">🎴</span> 首頁 Banner
    </a>
    <a class="nav-item <?= $currentPage === 'google_batch' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/google_batch.php">
      <span class="icon">🌟</span> Google 評價
    </a>
    <a class="nav-item <?= $currentPage === 'platform' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pages/platform.php">
      <span class="icon">⚙️</span> 平台設定
    </a>
    <?php endif; ?>

    <div class="nav-section">其他</div>
    <a class="nav-item" href="<?= BASE_URL ?>/admin/pages/password.php">
      <span class="icon">🔐</span> 變更密碼
    </a>
    <a class="nav-item" href="<?= BASE_URL ?>/admin/logout.php"
       onclick="return confirm('確定要登出嗎？')">
      <span class="icon">🚪</span> 登出
    </a>
  </nav>

  <div class="sidebar-footer">v1.0 · 店家好口碑</div>
</aside>

<!-- ── Top Bar ─────────────────────────────────────────── -->
<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
    <div>
      <div class="topbar-title"><?= h($pageTitle ?? 'Dashboard') ?></div>
    </div>
  </div>
  <div class="topbar-right">
    <?php if ($clientData):
      // 依 has_minisite + 環境智能選預覽目的地
      $_sub = $clientData['subdomain'] ?? $clientData['slug'];
      if (!empty($clientData['has_minisite'])) {
          // mini-site：PROD 用子網域，LOCAL/STAGING 用 path
          $_previewUrl = IS_PROD
              ? 'https://' . urlencode($_sub) . '.gomag.com.tw/'
              : BASE_URL . '/site/index.php?sub=' . urlencode($_sub);
          $_previewLabel = '🌐 預覽小官網';
      } else {
          // 行銷頁：staging 用 pretty URL（.htaccess rewrite）
          if (IS_PROD) {
              $_previewUrl = 'https://www.gomag.com.tw/store/' . urlencode($_sub);
          } elseif (IS_LOCAL) {
              $_previewUrl = BASE_URL . '/store.php?sub=' . urlencode($_sub);
          } else {
              $_previewUrl = BASE_URL . '/store/' . urlencode($_sub);
          }
          $_previewLabel = '📢 預覽行銷頁';
      }
    ?>
    <a class="btn-view-site" href="<?= h($_previewUrl) ?>" target="_blank">
      <?= $_previewLabel ?>
    </a>
    <?php endif; ?>
    <div class="admin-avatar">
      <div class="avatar-circle"><?= mb_substr($admin['display_name'] ?? $admin['username'], 0, 1) ?></div>
      <span class="admin-name"><?= h($admin['display_name'] ?? $admin['username']) ?></span>
    </div>
  </div>
</header>

<!-- ── Main Wrap ───────────────────────────────────────── -->
<div class="main-wrap">
<div class="main-content">

<?php if ($flash): ?>
<div class="flash flash-<?= h($flash['type']) ?>">
  <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '⚠️' : '❌') ?>
  <?= h($flash['message']) ?>
</div>
<?php endif; ?>
