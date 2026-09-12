<?php
// coupon_line_callback.php ─ LINE 授權導回：換 token → 取 userId → 發碼 → push 到 LINE
require_once __DIR__ . '/includes/line_coupon.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('gomag_coupon');
    session_start();
}
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$OA_URL  = 'https://line.me/R/ti/p/@316swndx';           // 口碑製造所 OA（消費者用）
$slug    = (string)($_SESSION['coupon_line_slug'] ?? '');
$backUrl = $slug !== '' ? rtrim(BASE_URL, '/') . '/store/' . rawurlencode($slug) : rtrim(BASE_URL, '/');

// 狀態：ok / not_friend / error
$status = 'error'; $errMsg = '';
$brand = ''; $title = ''; $expiry = ''; $display = ''; $qrUrl = ''; $storeLineUrl = '';

do {
    if (!lineCouponEnabled()) { $errMsg = '優惠券 LINE 功能尚未開通。'; break; }
    if (isset($_GET['error'])) { $errMsg = '你取消了授權，未領取優惠券。'; break; }

    $code  = (string)($_GET['code'] ?? '');
    $state = (string)($_GET['state'] ?? '');
    $sState = (string)($_SESSION['coupon_line_state'] ?? '');
    if ($code === '' || $state === '' || $sState === '' || !hash_equals($sState, $state)) {
        $errMsg = '連結已失效，請回店家頁重新領取。'; break;
    }
    unset($_SESSION['coupon_line_state']); // 一次性

    $tok = lineExchangeToken($code);
    if (!$tok) { $errMsg = 'LINE 驗證失敗，請重新領取。'; break; }
    $profile = lineGetProfile($tok['access_token']);
    if (!$profile) { $errMsg = '無法取得你的 LINE 資料，請重新領取。'; break; }
    $userId = (string)$profile['userId'];

    $db = getDB();
    [$ok, $codeOrErr, $client] = claimCouponForLineUser($db, $slug, $userId);
    if (!$ok) {
        $map = ['no_coupon'=>'這家店目前沒有可領取的優惠券。','expired'=>'優惠券已過期。','bad_slug'=>'參數錯誤。'];
        $errMsg = $map[$codeOrErr] ?? '發券失敗，請稍後再試。'; break;
    }
    $couponCode = $codeOrErr;

    // 店家自己的 LINE（放進推播訊息的預約按鈕）
    try {
        $ss = $db->prepare("SELECT line_url FROM client_social WHERE client_id = ? LIMIT 1");
        $ss->execute([(int)$client['id']]);
        $storeLineUrl = trim((string)($ss->fetchColumn() ?: ''));
    } catch (Exception $e) { $storeLineUrl = ''; }

    $brand   = (string)($client['brand_name'] ?? '本店');
    $title   = (string)($client['coupon_title'] ?? '優惠券');
    $expiry  = trim((string)($client['coupon_expiry'] ?? ''));
    $display = strlen($couponCode) === 8 ? substr($couponCode,0,4).'-'.substr($couponCode,4) : $couponCode;
    $redeemUrl = rtrim(BASE_URL,'/').'/coupon_redeem.php?c='.rawurlencode($couponCode);
    $qrUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=8&data='.rawurlencode($redeemUrl);

    $pushed = linePushCoupon($userId, $client, $couponCode, $storeLineUrl);
    $status = $pushed ? 'ok' : 'not_friend';
} while (false);
?>
<!doctype html>
<html lang="zh-Hant"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>優惠券｜店家好口碑</title>
<style>
  *{box-sizing:border-box;} body{margin:0;font-family:"Noto Sans TC",system-ui,sans-serif;background:#f4f5f7;color:#1a1a1a;}
  .wrap{max-width:420px;margin:0 auto;padding:32px 18px 60px;}
  .card{background:#fff;border-radius:18px;padding:28px 22px;box-shadow:0 8px 30px rgba(0,0,0,.10);text-align:center;}
  .big{font-size:1.4rem;font-weight:900;margin:0 0 6px;}
  .sub{font-size:.95rem;color:#666;margin:0 0 20px;line-height:1.6;}
  .voucher{background:linear-gradient(160deg,#FF5A36,#ff7a52);color:#fff;border-radius:16px;padding:22px 18px;margin:0 0 18px;}
  .voucher .b{font-size:.9rem;font-weight:700;opacity:.92;}
  .voucher .t{font-size:1.3rem;font-weight:900;margin:6px 0 12px;line-height:1.3;}
  .code{background:rgba(255,255,255,.96);color:#FF5A36;border:2px dashed #FF5A36;border-radius:12px;padding:12px;}
  .code .l{display:block;font-size:.7rem;font-weight:700;color:#a33;letter-spacing:1px;}
  .code .v{display:block;font-size:1.5rem;font-weight:900;letter-spacing:2px;}
  .qr{display:block;width:150px;height:150px;background:#fff;border-radius:10px;padding:8px;margin:12px auto 0;}
  .exp{font-size:.85rem;opacity:.92;margin-top:10px;}
  .btn{display:block;width:100%;padding:15px;border-radius:12px;font-weight:800;font-size:1.05rem;text-decoration:none;margin-top:12px;border:none;cursor:pointer;}
  .btn-line{background:#06C755;color:#fff;box-shadow:0 6px 18px rgba(6,199,85,.4);}
  .btn-ghost{background:#eee;color:#333;}
  .note{font-size:.85rem;color:#8a6d63;line-height:1.6;margin-top:14px;}
</style></head>
<body><div class="wrap"><div class="card">
<?php if ($status === 'ok'): ?>
  <div style="font-size:2.6rem">✅</div>
  <h1 class="big">已傳到你的 LINE！</h1>
  <p class="sub">打開 LINE 就能看到這張優惠券，隨時打開出示給店家，不會不見。</p>
  <div class="voucher">
    <div class="b"><?= esc($brand) ?></div>
    <div class="t"><?= esc($title) ?></div>
    <div class="code"><span class="l">核銷碼</span><span class="v"><?= esc($display) ?></span></div>
    <?php if ($expiry !== ''): ?><div class="exp">有效期限至 <?= esc($expiry) ?></div><?php endif; ?>
  </div>
  <a class="btn btn-line" href="<?= esc($OA_URL) ?>">📲 打開 LINE 查看優惠券</a>
  <a class="btn btn-ghost" href="<?= esc($backUrl) ?>">← 回店家頁</a>
<?php elseif ($status === 'not_friend'): ?>
  <div style="font-size:2.6rem">🎁</div>
  <h1 class="big">優惠券領取成功</h1>
  <p class="sub">（尚未加入好友，無法傳進 LINE）<br>請直接截圖保存這張券，出示給店家。</p>
  <div class="voucher">
    <div class="b"><?= esc($brand) ?></div>
    <div class="t"><?= esc($title) ?></div>
    <div class="code"><span class="l">核銷碼（出示給店家）</span><span class="v"><?= esc($display) ?></span></div>
    <?php if ($qrUrl !== ''): ?><img class="qr" src="<?= esc($qrUrl) ?>" alt="優惠券 QR"><?php endif; ?>
    <?php if ($expiry !== ''): ?><div class="exp">有效期限至 <?= esc($expiry) ?></div><?php endif; ?>
  </div>
  <p class="note">📸 直接截圖這張券即可保存。想讓券自動進 LINE：加入「店家好口碑」官方帳號好友後再領一次。</p>
  <a class="btn btn-line" href="<?= esc($OA_URL) ?>">加入官方帳號好友</a>
  <a class="btn btn-ghost" href="<?= esc($backUrl) ?>">← 回店家頁</a>
<?php else: ?>
  <div style="font-size:2.6rem">😅</div>
  <h1 class="big">沒領到優惠券</h1>
  <p class="sub"><?= esc($errMsg ?: '發生錯誤，請重新領取。') ?></p>
  <a class="btn btn-ghost" href="<?= esc($backUrl) ?>">← 回店家頁重新領取</a>
<?php endif; ?>
</div></div></body></html>
