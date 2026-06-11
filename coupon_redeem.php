<?php
// coupon_redeem.php — 店家優惠券核銷頁（第二批）
// QR 編碼 /coupon_redeem.php?c={code}；店家掃碼或手動輸碼 → 確認核銷（標記 redeemed_at，防重複用）。
// 需登入；店家只能核銷自己店的券，super 可核任一店。
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireLogin();

$db      = getDB();
$admin   = currentAdmin();
$isSuper = ($admin['role'] ?? '') === 'super';
$myClient = getCurrentClientId();

$normCode = fn($s) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$s));
$dash     = fn($c) => strlen($c) === 8 ? substr($c, 0, 4) . '-' . substr($c, 4) : $c;

$code  = $normCode($_POST['code'] ?? $_GET['c'] ?? '');
$claim = null; $flash = null; $flashType = '';

$lookup = $db->prepare("SELECT cc.*, c.brand_name FROM coupon_claims cc
                        JOIN clients c ON c.id = cc.client_id WHERE cc.code = ? LIMIT 1");

if ($code !== '') {
    $lookup->execute([$code]);
    $claim = $lookup->fetch(PDO::FETCH_ASSOC);
    if (!$claim) {
        $flash = '查無此核銷碼：' . h($dash($code)); $flashType = 'err';
    } elseif (!$isSuper && (int)$claim['client_id'] !== (int)$myClient) {
        $claim = null; $flash = '這張券不屬於你的店家，無法核銷。'; $flashType = 'err';
    }
}

// 確認核銷
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $claim) {
    verifyCsrf();
    if (!empty($claim['redeemed_at'])) {
        $flash = '此券已於 ' . h($claim['redeemed_at']) . ' 核銷過，不能重複使用。'; $flashType = 'warn';
    } else {
        $note = trim(($admin['display_name'] ?? '') ?: ($admin['username'] ?? '')) . ' #' . ($admin['id'] ?? '');
        $db->prepare("UPDATE coupon_claims SET redeemed_at = NOW(), redeemed_note = ? WHERE id = ? AND redeemed_at IS NULL")
           ->execute([mb_substr($note, 0, 120), (int)$claim['id']]);
        $lookup->execute([$code]); $claim = $lookup->fetch(PDO::FETCH_ASSOC);
        $flash = '核銷成功！'; $flashType = 'ok';
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>優惠券核銷｜店家好口碑</title>
<style>
  *{box-sizing:border-box;}
  body{margin:0;font-family:"Noto Sans TC",system-ui,sans-serif;background:#f4f5f7;color:#1a1a1a;}
  .wrap{max-width:440px;margin:0 auto;padding:24px 16px 60px;}
  h1{font-size:1.4rem;text-align:center;margin:8px 0 18px;}
  .codeform{display:flex;gap:8px;margin-bottom:16px;}
  .codeform input{flex:1;padding:13px 14px;border:2px solid #d0d3d8;border-radius:10px;font-size:1.1rem;letter-spacing:2px;text-transform:uppercase;}
  .codeform button{padding:0 18px;border:none;border-radius:10px;background:#FF5A36;color:#fff;font-weight:800;font-size:1rem;cursor:pointer;}
  .flash{padding:13px 15px;border-radius:10px;font-weight:700;margin-bottom:16px;text-align:center;}
  .flash.ok{background:#dcfce7;color:#166534;}
  .flash.warn{background:#fef3c7;color:#92400e;}
  .flash.err{background:#fee2e2;color:#b91c1c;}
  .card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 4px 18px rgba(0,0,0,.08);text-align:center;border:2px solid #FF5A36;}
  .card.used{border-color:#9ca3af;opacity:.85;}
  .card .store{font-size:.95rem;color:#666;font-weight:700;}
  .card .title{font-size:1.5rem;font-weight:900;margin:6px 0 12px;}
  .card .code{font-size:1.7rem;font-weight:900;color:#FF5A36;letter-spacing:3px;background:#fff6f3;border:2px dashed #FF5A36;border-radius:10px;padding:10px;}
  .card.used .code{color:#6b7280;border-color:#cbd5e1;background:#f3f4f6;}
  .card .meta{font-size:.85rem;color:#888;margin-top:10px;}
  .redeem-btn{width:100%;margin-top:16px;padding:15px;border:none;border-radius:12px;background:#16a34a;color:#fff;font-size:1.15rem;font-weight:900;cursor:pointer;box-shadow:0 4px 14px rgba(22,163,74,.35);}
  .redeem-btn:active{transform:translateY(1px);}
  .status.used{margin-top:14px;font-size:1.1rem;font-weight:900;color:#6b7280;}
  .status.used small{display:block;font-size:.8rem;font-weight:500;margin-top:4px;}
  .back{text-align:center;margin-top:24px;}
  .back a{color:#888;text-decoration:none;font-size:.9rem;}
</style>
</head>
<body>
  <div class="wrap">
    <h1>🎟️ 優惠券核銷</h1>
    <form method="get" class="codeform">
      <input type="text" name="c" value="<?= h($dash($code)) ?>" placeholder="輸入核銷碼 例 XQPQ-PFJL"
             autocapitalize="characters" autocomplete="off" inputmode="text">
      <button type="submit">查詢</button>
    </form>

    <?php if ($flash): ?><div class="flash <?= $flashType ?>"><?= $flash ?></div><?php endif; ?>

    <?php if ($claim): ?>
    <div class="card <?= !empty($claim['redeemed_at']) ? 'used' : '' ?>">
      <div class="store"><?= h($claim['brand_name']) ?></div>
      <div class="title"><?= h($claim['coupon_title'] ?: '優惠券') ?></div>
      <div class="code"><?= h($dash($claim['code'])) ?></div>
      <div class="meta">領取時間：<?= h($claim['claimed_at']) ?></div>
      <?php if (!empty($claim['redeemed_at'])): ?>
        <div class="status used">✓ 已核銷<small><?= h($claim['redeemed_at']) ?>　<?= h($claim['redeemed_note']) ?></small></div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="code" value="<?= h($claim['code']) ?>">
          <button type="submit" name="confirm" value="1" class="redeem-btn">確認核銷（標記已使用）</button>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <p class="back"><a href="<?= BASE_URL ?>/admin/pages/coupon_stats.php">📊 優惠券統計</a>　·　<a href="<?= BASE_URL ?>/admin/index.php">回後台</a></p>
  </div>
</body>
</html>
