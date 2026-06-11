<?php
// admin/pages/coupon_stats.php — 優惠券使用率報表（第二批）
// super：全部店家；店家：只看自己。每張券：領取數 / 核銷數 / 轉換率。
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '優惠券統計';
$db       = getDB();
$admin    = currentAdmin();
$isSuper  = ($admin['role'] ?? '') === 'super';
$myClient = getCurrentClientId();

$cond   = ['(c.coupon_enabled = 1 OR cc.id IS NOT NULL)'];
$params = [];
if (!$isSuper) { $cond[] = 'c.id = ?'; $params[] = (int)$myClient; }

$sql = "SELECT c.id, c.slug, c.brand_name, c.coupon_enabled, c.coupon_title,
               COUNT(cc.id) AS claims,
               COALESCE(SUM(cc.redeemed_at IS NOT NULL), 0) AS redeemed
        FROM clients c
        LEFT JOIN coupon_claims cc ON cc.client_id = c.id
        WHERE " . implode(' AND ', $cond) . "
        GROUP BY c.id
        ORDER BY claims DESC, c.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totClaims = 0; $totRedeemed = 0;
foreach ($rows as $r) { $totClaims += (int)$r['claims']; $totRedeemed += (int)$r['redeemed']; }
$rate = fn($r, $c) => $c > 0 ? round($r / $c * 100) . '%' : '—';

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="card" style="margin-bottom:20px; border-left:4px solid #FF5A36;">
  <div class="card-header"><h2>🎟️ 優惠券統計</h2></div>
  <div class="card-body">
    <p style="color:#666; margin:0 0 16px;">
      「領取」＝客人加 LINE 解鎖券（發出唯一碼）。「核銷」＝到店出示、店家在
      <a href="<?= BASE_URL ?>/coupon_redeem.php" style="font-weight:700; color:var(--primary,#FF5A36);">核銷頁</a>標記已用。
      轉換率＝核銷 ÷ 領取。
    </p>

    <div style="display:flex; gap:14px; flex-wrap:wrap; margin-bottom:18px;">
      <div style="flex:1; min-width:120px; background:#fff6f3; border-radius:12px; padding:14px; text-align:center;">
        <div style="font-size:1.9rem; font-weight:900; color:#FF5A36;"><?= $totClaims ?></div>
        <div style="font-size:.85rem; color:#888;">總領取</div>
      </div>
      <div style="flex:1; min-width:120px; background:#dcfce7; border-radius:12px; padding:14px; text-align:center;">
        <div style="font-size:1.9rem; font-weight:900; color:#166534;"><?= $totRedeemed ?></div>
        <div style="font-size:.85rem; color:#888;">總核銷</div>
      </div>
      <div style="flex:1; min-width:120px; background:#eff6ff; border-radius:12px; padding:14px; text-align:center;">
        <div style="font-size:1.9rem; font-weight:900; color:#2563eb;"><?= $rate($totRedeemed, $totClaims) ?></div>
        <div style="font-size:.85rem; color:#888;">整體轉換率</div>
      </div>
    </div>

    <?php if (!$rows): ?>
      <p style="color:#888; text-align:center; padding:30px 0;">目前還沒有啟用優惠券或領取記錄。</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; font-size:.92rem;">
        <thead>
          <tr style="background:#f4f5f7; text-align:left;">
            <th style="padding:10px;">店家</th>
            <th style="padding:10px;">優惠券</th>
            <th style="padding:10px; text-align:center;">狀態</th>
            <th style="padding:10px; text-align:right;">領取</th>
            <th style="padding:10px; text-align:right;">核銷</th>
            <th style="padding:10px; text-align:right;">轉換率</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid #eee;">
            <td style="padding:10px;">
              <a href="<?= BASE_URL ?>/store/<?= h($r['slug']) ?>" target="_blank" rel="noopener" style="color:#1a1a1a; font-weight:700; text-decoration:none;"><?= h($r['brand_name']) ?></a>
            </td>
            <td style="padding:10px; color:#555;"><?= h($r['coupon_title'] ?: '—') ?></td>
            <td style="padding:10px; text-align:center;">
              <?= !empty($r['coupon_enabled'])
                    ? '<span style="color:#16a34a; font-weight:700;">啟用中</span>'
                    : '<span style="color:#9ca3af;">已關閉</span>' ?>
            </td>
            <td style="padding:10px; text-align:right; font-weight:700;"><?= (int)$r['claims'] ?></td>
            <td style="padding:10px; text-align:right; font-weight:700; color:#166534;"><?= (int)$r['redeemed'] ?></td>
            <td style="padding:10px; text-align:right; font-weight:800; color:#2563eb;"><?= $rate((int)$r['redeemed'], (int)$r['claims']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
