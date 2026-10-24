<?php
// admin/pages/click_stats.php — 店家頁按鈕點擊統計（只有 super 看得到）
// 資料來源：click_events（/track_click.php 寫入，店家頁 store.php 的監聽器送來）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/front_functions.php';
requireLogin();

if ((currentAdmin()['role'] ?? '') !== 'super') { http_response_code(403); exit('只有系統管理員可以看這頁'); }

$pageTitle = '按鈕點擊統計';
$db = getDB();

$ranges = [7 => '近 7 天', 30 => '近 30 天', 90 => '近 90 天', 0 => '全部'];
$days = isset($_GET['d']) && array_key_exists((int)$_GET['d'], $ranges) ? (int)$_GET['d'] : 30;
$sources = ['' => '全部', 'store' => '店家頁', 'mini' => '小官網'];
$src = isset($_GET['s']) && array_key_exists((string)$_GET['s'], $sources) ? (string)$_GET['s'] : '';
// created_at 是 DB 時鐘（UTC），區間也用 DB 的 NOW() 算，避免跟 PHP 台北時間差 8 小時
$since = $days ? ($days * 86400) : 100 * 365 * 86400;

// 欄位順序＝報表顯示順序
$cols = [
    'phone'    => '📞 電話',
    'line'     => '💬 LINE',
    'map'      => '🗺️ 導航',
    'coupon'   => '🎟️ 優惠券',
    'social'   => '📘 社群',
    'order'    => '🛵 外送',
    'website'  => '🌐 官網',
    'minisite' => '🏠 小官網',
];
$sumSql = implode(', ', array_map(fn($k) => "SUM(e.action = '{$k}') AS `{$k}`", array_keys($cols)));

// 來源條件在 PHP 決定（SQL 裡寫 ? = '' 會撞 collation 衝突 1267）
$srcSql = $src !== '' ? 'AND e.source = ?' : '';
$stmt = $db->prepare("SELECT c.id, c.slug, c.brand_name, c.city_slug, {$sumSql}, COUNT(*) AS total
                      FROM click_events e
                      JOIN clients c ON c.id = e.client_id
                      WHERE e.created_at >= NOW() - INTERVAL ? SECOND
                        {$srcSql}
                      GROUP BY c.id
                      ORDER BY total DESC, c.id DESC");
$stmt->execute($src !== '' ? [$since, $src] : [$since]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tot = array_fill_keys(array_keys($cols), 0); $grand = 0;
foreach ($rows as $r) { foreach ($cols as $k => $_) $tot[$k] += (int)$r[$k]; $grand += (int)$r['total']; }

$firstAt  = $db->query("SELECT MIN(created_at) FROM click_events")->fetchColumn();
$activeN  = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_active = 1 AND COALESCE(is_placeholder, 0) = 0")->fetchColumn();
$cityName = getCityMap();

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="card" style="margin-bottom:20px; border-left:4px solid #FF5A36;">
  <div class="card-header"><h2>📈 按鈕點擊統計</h2></div>
  <div class="card-body">
    <p style="color:#666; margin:0 0 14px;">
      客人在店家頁（/store/…）和小官網上按了哪些按鈕。同一人 10 秒內重複按同一顆只算一次；店家頁的頁首、頁尾和 Google 評價區不算（那是平台的，不是店家的）。小官網從 2026-10-24 開始記錄。
      <?= $firstAt ? '從 <b>' . h(date('Y-m-d', strtotime($firstAt . ' UTC'))) . '</b> 開始記錄。' : '還沒有任何記錄（剛上線，等客人點）。' ?>
    </p>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
      <?php foreach ($ranges as $d => $label): ?>
        <a href="?d=<?= $d ?>&amp;s=<?= h($src) ?>" style="padding:6px 14px; border-radius:999px; text-decoration:none; font-size:.88rem;
           <?= $d === $days ? 'background:#FF5A36; color:#fff; font-weight:700;' : 'background:#f4f5f7; color:#444;' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <span style="width:1px; background:#e5e7eb; margin:0 6px;"></span>
      <?php foreach ($sources as $sk => $label): ?>
        <a href="?d=<?= $days ?>&amp;s=<?= h($sk) ?>" style="padding:6px 14px; border-radius:999px; text-decoration:none; font-size:.88rem;
           <?= $sk === $src ? 'background:#1e293b; color:#fff; font-weight:700;' : 'background:#f4f5f7; color:#444;' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px;">
      <div style="flex:1; min-width:110px; background:#fff6f3; border-radius:12px; padding:12px; text-align:center;">
        <div style="font-size:1.8rem; font-weight:900; color:#FF5A36;"><?= $grand ?></div>
        <div style="font-size:.82rem; color:#888;">總點擊</div>
      </div>
      <div style="flex:1; min-width:110px; background:#f4f5f7; border-radius:12px; padding:12px; text-align:center;">
        <div style="font-size:1.8rem; font-weight:900; color:#333;"><?= count($rows) ?> <span style="font-size:.9rem; color:#888; font-weight:600;">/ <?= $activeN ?></span></div>
        <div style="font-size:.82rem; color:#888;">有被點的店家 / 啟用店家</div>
      </div>
      <?php foreach (['phone', 'line', 'map'] as $k): ?>
      <div style="flex:1; min-width:110px; background:#f4f5f7; border-radius:12px; padding:12px; text-align:center;">
        <div style="font-size:1.8rem; font-weight:900; color:#333;"><?= $tot[$k] ?></div>
        <div style="font-size:.82rem; color:#888;"><?= $cols[$k] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (!$rows): ?>
      <p style="color:#888; text-align:center; padding:30px 0;">這段期間還沒有點擊記錄。</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:.9rem;">
        <thead>
          <tr style="background:#f4f5f7; text-align:left;">
            <th style="padding:10px;">店家</th>
            <th style="padding:10px;">城市</th>
            <?php foreach ($cols as $label): ?><th style="padding:10px; text-align:right; white-space:nowrap;"><?= $label ?></th><?php endforeach; ?>
            <th style="padding:10px; text-align:right;">合計</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid #eee;">
            <td style="padding:10px;">
              <a href="<?= BASE_URL ?>/store/<?= h($r['slug']) ?>" target="_blank" rel="noopener" style="color:#1a1a1a; font-weight:700; text-decoration:none;"><?= h($r['brand_name']) ?></a>
            </td>
            <td style="padding:10px; color:#666; white-space:nowrap;"><?= h($cityName[$r['city_slug']] ?? ($r['city_slug'] ?: '—')) ?></td>
            <?php foreach ($cols as $k => $_): $v = (int)$r[$k]; ?>
              <td style="padding:10px; text-align:right; <?= $v ? 'font-weight:700;' : 'color:#ccc;' ?>"><?= $v ?: '·' ?></td>
            <?php endforeach; ?>
            <td style="padding:10px; text-align:right; font-weight:900; color:#FF5A36;"><?= (int)$r['total'] ?></td>
          </tr>
          <?php endforeach; ?>
          <tr style="background:#fafafa; font-weight:800;">
            <td style="padding:10px;" colspan="2">合計</td>
            <?php foreach ($cols as $k => $_): ?><td style="padding:10px; text-align:right;"><?= $tot[$k] ?></td><?php endforeach; ?>
            <td style="padding:10px; text-align:right; color:#FF5A36;"><?= $grand ?></td>
          </tr>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
