<?php
// admin/index.php  ─  後台 Dashboard v2
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$pageTitle = 'Dashboard';
$clientId  = getCurrentClientId() ?? 1;
$admin     = currentAdmin();
$db        = getDB();

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

$counts = [];
foreach (['services','cases','testimonials'] as $t) {
    $s = $db->prepare("SELECT COUNT(*) FROM `$t` WHERE client_id=? AND is_active=1");
    $s->execute([$clientId]);
    $counts[$t] = (int)$s->fetchColumn();
}
$s1 = $db->prepare('SELECT COUNT(*) FROM contact_leads WHERE client_id=? AND is_read=0');
$s1->execute([$clientId]);
$unread = (int)$s1->fetchColumn();
$s2 = $db->prepare('SELECT COUNT(*) FROM contact_leads WHERE client_id=?');
$s2->execute([$clientId]);
$totalLeads = (int)$s2->fetchColumn();

$client = getClientData($clientId);

$recentLeads = $db->prepare('SELECT * FROM contact_leads WHERE client_id=? ORDER BY created_at DESC LIMIT 5');
$recentLeads->execute([$clientId]);
$recentLeads = $recentLeads->fetchAll();

$recentCases = $db->prepare('SELECT c.*,s.name AS svc_name FROM cases c LEFT JOIN services s ON c.service_id=s.id WHERE c.client_id=? ORDER BY c.created_at DESC LIMIT 5');
$recentCases->execute([$clientId]);
$recentCases = $recentCases->fetchAll();

$allClients = [];
$pendingSubdomains = [];
if ($admin['role'] === 'super') {
    $allClients = $db->query("
        SELECT c.id,c.slug,c.brand_name,c.is_active,
          (SELECT COUNT(*) FROM contact_leads WHERE client_id=c.id AND is_read=0) AS unread,
          ct.color_primary
        FROM clients c
        LEFT JOIN client_themes ct ON ct.client_id=c.id AND ct.is_active=1
        ORDER BY c.id LIMIT 8
    ")->fetchAll();

    // 子網域待辦：has_minisite=1 但 hPanel 還沒設
    $pendingSubdomains = $db->query("
        SELECT id, slug, subdomain, brand_name, has_minisite, subdomain_provisioned, created_at, updated_at
        FROM clients
        WHERE is_active=1 AND has_minisite=1 AND COALESCE(subdomain_provisioned, 0) = 0
        ORDER BY id DESC
    ")->fetchAll();
}

require_once __DIR__ . '/includes/layout_head.php';
?>

<!-- 歡迎橫幅 -->
<div style="background:linear-gradient(135deg,var(--primary) 0%,#2a5252 100%);border-radius:var(--radius);padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:.85rem;opacity:.75;margin-bottom:6px">🌟 歡迎回來，<?= h($admin['display_name']??$admin['username']) ?></div>
    <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:4px"><?= h($client['brand_name']??'─') ?></h1>
    <div style="font-size:.85rem;opacity:.8"><?= h($client['tagline']??'') ?></div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php if ($admin['role']==='super'): ?>
    <a href="<?= BASE_URL ?>/admin/pages/clients.php"
       style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:.8rem;font-weight:600;transition:background .2s"
       onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
      🏪 切換客戶
    </a>
    <?php endif; ?>
    <?php if ($client):
      $_sub = $client['subdomain'] ?? $client['slug'];
      if (!empty($client['has_minisite'])) {
          $_topUrl = IS_PROD
              ? 'https://' . urlencode($_sub) . '.' . MINISITE_DOMAIN . '/'
              : BASE_URL . '/site/index.php?sub=' . urlencode($_sub);
          $_topLabel = '🌐 前往小官網';
      } else {
          if (IS_PROD)        $_topUrl = 'https://www.gomag.com.tw/store/' . urlencode($_sub);
          elseif (IS_LOCAL)   $_topUrl = BASE_URL . '/store.php?sub=' . urlencode($_sub);
          else                $_topUrl = BASE_URL . '/store/' . urlencode($_sub);
          $_topLabel = '📢 前往行銷頁';
      }
    ?>
    <a href="<?= h($_topUrl) ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:.8rem;font-weight:600;transition:background .2s"
       onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
      <?= $_topLabel ?>
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($admin['role'] === 'super' && !empty($pendingSubdomains)): ?>
<!-- ═══════ 子網域待辦清單（super 限定） ═══════ -->
<div style="background:#fff3e0;border:1.5px solid #ffb74d;border-radius:14px;padding:20px 24px;margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <h2 style="font-size:1.05rem;font-weight:800;color:#e65100;margin:0">
      ⚠️ Hostinger hPanel 子網域待設定（<?= count($pendingSubdomains) ?> 個）
    </h2>
    <a href="https://hpanel.hostinger.com/" target="_blank" style="background:#fff;border:1px solid #ffb74d;color:#e65100;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:.85rem;font-weight:700">🔗 開 hPanel</a>
  </div>
  <div style="font-size:.85rem;color:#5d4037;margin-bottom:14px;line-height:1.6">
    <strong>💡 最簡流程</strong>：
    <ol style="margin:6px 0 0 18px;line-height:1.7">
      <li>Chrome 登入 <a href="https://hpanel.hostinger.com/" target="_blank">Hostinger hPanel</a>（保持 Claude extension 連線）</li>
      <li>跟 Claude 說「幫我建 {slug} 子網域」</li>
      <li>Claude 5 分鐘自動完成（hPanel 點選 + SSH symlink + DB 標記）</li>
    </ol>
  </div>
  <div style="background:#fff;border-radius:8px;overflow:hidden;border:1px solid #ffe0b2">
    <table style="width:100%;font-size:.88rem;border-collapse:collapse">
      <thead style="background:#fafafa">
        <tr style="border-bottom:1px solid #ffe0b2">
          <th style="text-align:left;padding:10px 14px;font-weight:700;color:#666">客戶</th>
          <th style="text-align:left;padding:10px 14px;font-weight:700;color:#666">建議子網域</th>
          <th style="text-align:left;padding:10px 14px;font-weight:700;color:#666">建立日期</th>
          <th style="text-align:right;padding:10px 14px;font-weight:700;color:#666">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingSubdomains as $p):
          $_psub = $p['subdomain'] ?: $p['slug'];
        ?>
        <tr style="border-top:1px solid #fff3e0">
          <td style="padding:10px 14px"><strong><?= h($p['brand_name']) ?></strong> <span style="color:#999;font-size:.8em">#<?= $p['id'] ?></span></td>
          <td style="padding:10px 14px"><code><?= h($_psub) ?>.gomag.com.tw</code></td>
          <td style="padding:10px 14px;color:#888;font-size:.85em"><?= date('Y/m/d', strtotime($p['created_at'])) ?></td>
          <td style="padding:10px 14px;text-align:right;white-space:nowrap">
            <button type="button" onclick="navigator.clipboard.writeText('<?= h($_psub) ?>').then(()=>this.textContent='✓ 已複製')"
                    style="padding:4px 10px;background:#fff;border:1px solid #ccc;border-radius:5px;cursor:pointer;font-size:.78rem;margin-right:4px">📋 複製</button>
            <a href="<?= BASE_URL ?>/admin/pages/settings.php?client_id=<?= $p['id'] ?>"
               style="padding:5px 12px;background:#e65100;color:#fff;border-radius:5px;text-decoration:none;font-size:.78rem;font-weight:700">設定 →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <details style="margin-top:14px;font-size:.85rem">
    <summary style="cursor:pointer;color:#5d4037;font-weight:700">📖 手動 SOP（Claude 不在或想自己做時用）</summary>
    <ol style="margin-top:10px;padding-left:24px;line-height:1.9;color:#5d4037">
      <li>登入 <a href="https://hpanel.hostinger.com/websites" target="_blank">hPanel Websites</a></li>
      <li>右上紫色「<strong>新增網站</strong>」→ 選「<strong>客製化 PHP/HTML 網站</strong>」</li>
      <li>輸入完整子網域（例 <code>{slug}.gomag.com.tw</code>）→ dropdown 點「<strong>Use it</strong>」→「<strong>下一個</strong>」</li>
      <li>跑出「將您的網域指向 Hostinger」改 NS 頁 → <strong>⚠️ 直接點右上 X 關掉</strong>（不要改 NS）</li>
      <li>SSH 進主機，把 website docroot 改 symlink：
        <pre style="margin:6px 0;padding:10px;background:#fff8e1;border-radius:4px;font-size:.82em;overflow-x:auto;">SUB={slug}
mv ~/domains/$SUB.gomag.com.tw/public_html ~/domains/$SUB.gomag.com.tw/public_html.bak
ln -s ~/domains/gomag.com.tw/public_html ~/domains/$SUB.gomag.com.tw/public_html</pre>
      </li>
      <li>等 5-10 分鐘 Let's Encrypt SSL 自動簽好</li>
      <li>瀏覽器訪問 <code>https://{slug}.gomag.com.tw/</code> 看到 mini-site → 回該客戶 settings 勾「hPanel 子網域已設定」</li>
    </ol>
    <p style="margin-top:8px;color:#888;font-size:.82em">📖 詳細 SOP：<code>docs/HPANEL_SUBDOMAIN_SOP.md</code></p>
  </details>
</div>
<?php endif; ?>

<!-- ═══════ 編輯入口（行銷頁 vs 小官網）═══════ -->
<?php if ($client): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">

  <!-- 行銷頁編輯（所有客戶都有）-->
  <a href="<?= BASE_URL ?>/admin/pages/settings.php" style="text-decoration:none;color:inherit">
    <div style="background:#fff;border:2px solid var(--accent);border-radius:var(--radius);padding:20px;transition:all .2s;cursor:pointer;height:100%"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(200,146,42,.2)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
        <div style="font-size:2rem">📢</div>
        <div>
          <div style="font-weight:800;font-size:1.05rem;color:var(--primary)">編輯行銷頁</div>
          <div style="font-size:.8rem;color:var(--muted)">主站 /store/<?= h($client['slug']??'') ?> 的呈現</div>
        </div>
      </div>
      <div style="font-size:.8rem;color:var(--muted);line-height:1.6;padding-top:10px;border-top:1px solid var(--border);margin-top:10px">
        基本資料 · Logo / Hero 圖 · 關於我們 · 行銷文案 · 聯絡資訊 · <strong style="color:#16a34a">SEO 設定</strong> · 營業時間 · Google Maps
      </div>
    </div>
  </a>

  <!-- 小官網編輯（has_minisite=1 才顯示）-->
  <?php if (!empty($client['has_minisite'])): ?>
  <a href="<?= BASE_URL ?>/admin/pages/services.php" style="text-decoration:none;color:inherit">
    <div style="background:#fff;border:2px solid var(--primary);border-radius:var(--radius);padding:20px;transition:all .2s;cursor:pointer;height:100%"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(28,61,61,.2)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
        <div style="font-size:2rem">🌐</div>
        <div>
          <div style="font-weight:800;font-size:1.05rem;color:var(--primary)">編輯小官網</div>
          <div style="font-size:.8rem;color:var(--muted)"><?= h($client['slug']??'') ?>.gomag.com.tw 完整官網</div>
        </div>
      </div>
      <div style="font-size:.8rem;color:var(--muted);line-height:1.6;padding-top:10px;border-top:1px solid var(--border);margin-top:10px">
        服務項目 · 施工案例 · 客戶評價 · FAQ · 主題顏色 · 社群連結
      </div>
    </div>
  </a>
  <?php else: ?>
  <a href="<?= BASE_URL ?>/admin/pages/settings.php" style="text-decoration:none;color:inherit">
    <div style="background:#fafafa;border:2px dashed var(--border);border-radius:var(--radius);padding:20px;text-align:center;cursor:pointer;height:100%;display:flex;flex-direction:column;justify-content:center"
         title="點擊去基本資訊頁勾選「啟用子網域小官網」">
      <div style="font-size:2rem;opacity:.4;margin-bottom:6px">🌐</div>
      <div style="font-weight:700;color:var(--muted);font-size:.9rem">小官網未啟用</div>
      <div style="font-size:.75rem;color:var(--muted);margin-top:4px">基本資訊頁勾選「啟用子網域小官網」即可建立完整官網</div>
    </div>
  </a>
  <?php endif; ?>

</div>
<?php endif; ?>

<!-- 統計卡片 -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:14px;margin-bottom:24px">
  <?php
  $cards = [
    ['🛠️','服務項目',$counts['services'], 'pages/services.php','#1c3d3d'],
    ['📸','施工案例',$counts['cases'],    'pages/cases.php',   '#c8922a'],
    ['⭐','客戶評價',$counts['testimonials'],'pages/testimonials.php','#2980b9'],
    ['📬','未讀詢問',$unread,             'pages/leads.php',   $unread>0?'#e74c3c':'#95a5a6'],
    ['📊','全部詢問',$totalLeads,         'pages/leads.php',   '#8e44ad'],
  ];
  foreach ($cards as $c): ?>
  <a href="<?= BASE_URL ?>/admin/<?= $c[3] ?>" style="text-decoration:none">
    <div class="card" style="padding:16px;display:flex;align-items:center;gap:12px;transition:transform .15s,box-shadow .15s"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 24px rgba(0,0,0,.12)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
      <div style="width:44px;height:44px;background:<?= $c[4] ?>1a;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0"><?= $c[0] ?></div>
      <div>
        <div style="font-size:1.65rem;font-weight:800;color:<?= $c[4] ?>;line-height:1"><?= $c[2] ?></div>
        <div style="font-size:.75rem;color:var(--muted);margin-top:2px"><?= $c[1] ?></div>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<!-- 主區塊 -->
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;margin-bottom:20px">

  <!-- 最新詢問 -->
  <div class="card">
    <div class="card-header">
      <h2>📬 最新詢問
        <?php if ($unread): ?>
        <span style="background:#e74c3c;color:#fff;font-size:.7rem;padding:2px 8px;border-radius:20px;margin-left:6px;font-weight:800"><?= $unread ?> 未讀</span>
        <?php endif; ?>
      </h2>
      <a href="<?= BASE_URL ?>/admin/pages/leads.php" class="btn btn-sm btn-ghost">查看全部</a>
    </div>
    <?php if (empty($recentLeads)): ?>
    <div class="card-body" style="text-align:center;color:var(--muted);padding:28px">
      <div style="font-size:2.5rem;margin-bottom:8px">📭</div>尚無詢問記錄
    </div>
    <?php else: ?>
    <?php foreach ($recentLeads as $lead): ?>
    <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border);<?= !$lead['is_read']?'background:#fffdf0':'' ?>">
      <div style="width:34px;height:34px;background:<?= !$lead['is_read']?'#e74c3c':'var(--muted)' ?>;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0">
        <?= mb_substr($lead['name'],0,1) ?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:1px">
          <strong style="font-size:.875rem"><?= h($lead['name']) ?></strong>
          <?php if (!$lead['is_read']): ?><span class="badge badge-warning" style="font-size:.65rem">未讀</span><?php endif; ?>
        </div>
        <div style="font-size:.78rem;color:var(--muted)"><?= h($lead['contact']) ?></div>
        <div style="font-size:.78rem;color:#666;margin-top:2px"><?= h(mb_strimwidth($lead['message'],0,48,'…')) ?></div>
      </div>
      <div style="font-size:.72rem;color:var(--muted);white-space:nowrap;text-align:right;flex-shrink:0">
        <?= date('m/d',strtotime($lead['created_at'])) ?><br>
        <span style="font-size:.7rem"><?= date('H:i',strtotime($lead['created_at'])) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- 右欄 -->
  <div style="display:flex;flex-direction:column;gap:16px">
    <!-- 快速操作 -->
    <div class="card">
      <div class="card-header"><h2>⚡ 快速操作</h2></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <a href="<?= BASE_URL ?>/admin/pages/services.php?action=add" class="btn btn-sm btn-outline" style="justify-content:center">➕ 新增服務</a>
          <a href="<?= BASE_URL ?>/admin/pages/cases.php?action=add"    class="btn btn-sm btn-outline" style="justify-content:center">📷 新增案例</a>
          <a href="<?= BASE_URL ?>/admin/pages/testimonials.php?action=add" class="btn btn-sm btn-outline" style="justify-content:center">✍️ 新增評價</a>
          <a href="<?= BASE_URL ?>/admin/pages/faqs.php"                class="btn btn-sm btn-outline" style="justify-content:center">❓ FAQ</a>
          <a href="<?= BASE_URL ?>/admin/pages/settings.php"            class="btn btn-sm btn-ghost"   style="justify-content:center">⚙️ 基本設定</a>
          <a href="<?= BASE_URL ?>/admin/pages/themes.php"              class="btn btn-sm btn-accent"  style="justify-content:center">🎨 換主題</a>
        </div>
      </div>
    </div>
    <!-- 基本資訊速覽 -->
    <?php if ($client): ?>
    <div class="card">
      <div class="card-header">
        <h2>📋 客戶資訊</h2>
        <a href="<?= BASE_URL ?>/admin/pages/settings.php" class="btn btn-sm btn-ghost">編輯</a>
      </div>
      <div class="card-body" style="padding:10px 16px">
        <?php foreach ([['📞',$client['phone']??'─'],['📍',$client['address']??'─'],['🏭',$client['industry']??'─']] as $r): ?>
        <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border);font-size:.84rem">
          <span><?= $r[0] ?></span><span style="color:var(--text)"><?= h($r[1]) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Super Admin：客戶快覽 -->
<?php if ($admin['role']==='super' && $allClients): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <h2>🏪 客戶概覽</h2>
    <div style="display:flex;gap:8px">
      <a href="<?= BASE_URL ?>/admin/pages/new_client.php" class="btn btn-sm btn-primary">➕ 新增客戶</a>
      <a href="<?= BASE_URL ?>/admin/pages/clients.php" class="btn btn-sm btn-ghost">查看全部</a>
    </div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:0">
    <?php foreach ($allClients as $cl): ?>
    <a href="<?= BASE_URL ?>/admin/pages/clients.php?switch=<?= $cl['id'] ?>"
       style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background .15s"
       onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
      <div style="width:30px;height:30px;border-radius:8px;background:<?= h($cl['color_primary']??'#1c3d3d') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:800;flex-shrink:0">
        <?= mb_substr($cl['brand_name'],0,1) ?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.83rem;font-weight:700;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= h($cl['brand_name']) ?></div>
        <div style="font-size:.68rem;color:var(--muted)">/<?= h($cl['slug']) ?></div>
      </div>
      <?php if ($cl['unread']>0): ?><span style="background:#e74c3c;color:#fff;font-size:.62rem;font-weight:800;padding:1px 5px;border-radius:20px;flex-shrink:0"><?= $cl['unread'] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- 最近案例 -->
<?php if ($recentCases): ?>
<div class="card">
  <div class="card-header">
    <h2>🕐 最近新增案例</h2>
    <a href="<?= BASE_URL ?>/admin/pages/cases.php" class="btn btn-sm btn-ghost">查看全部</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>案例名稱</th><th>服務</th><th>地點</th><th>精選</th><th>時間</th><th width="80">操作</th></tr></thead>
      <tbody>
        <?php foreach ($recentCases as $c): ?>
        <tr>
          <td><strong><?= h($c['title']) ?></strong></td>
          <td style="font-size:.85rem"><?= h($c['svc_name']??'─') ?></td>
          <td style="font-size:.85rem"><?= h($c['location']??'─') ?></td>
          <td><?= $c['is_featured']?'<span class="badge badge-warning">⭐精選</span>':'<span class="badge badge-muted">一般</span>' ?></td>
          <td style="color:var(--muted);font-size:.8rem"><?= date('m/d H:i',strtotime($c['created_at'])) ?></td>
          <td><a href="<?= BASE_URL ?>/admin/pages/cases.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-ghost">✏️</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/layout_foot.php'; ?>
