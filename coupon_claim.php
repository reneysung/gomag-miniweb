<?php
// coupon_claim.php — 優惠券「領取/發碼」端點（第一批）
// 消費者在行銷頁點「加 LINE 領取優惠券」解鎖時，前端 fetch 這支：
//   - 驗證該店有啟用且未過期的優惠券
//   - 同一人(ip_hash)同店當日若已有未核銷的碼 → 沿用（避免一直發新碼）
//   - 否則發一組唯一碼、寫入 coupon_claims（= 領取數）
// 回傳 JSON：{ ok, code, display }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/config.php';

function jout(array $a): void { echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }

$slug = strtolower(trim($_POST['slug'] ?? $_GET['slug'] ?? ''));
if ($slug === '' || !preg_match('/^[a-z0-9_-]+$/', $slug)) {
    http_response_code(400); jout(['ok' => false, 'err' => 'bad_slug']);
}

$db = getDB();
$stmt = $db->prepare("SELECT id, coupon_enabled, coupon_title, coupon_expiry
                      FROM clients WHERE slug = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$slug]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c || empty($c['coupon_enabled'])) {
    http_response_code(404); jout(['ok' => false, 'err' => 'no_coupon']);
}
// 過期檢查
if (!empty($c['coupon_expiry']) && ($ts = strtotime($c['coupon_expiry'])) !== false
    && $ts < strtotime(date('Y-m-d'))) {
    jout(['ok' => false, 'err' => 'expired']);
}

$clientId = (int)$c['id'];
$ip   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip   = trim(explode(',', $ip)[0]);
$today = date('Y-m-d');
$ipHash = substr(sha1($ip . '|' . $today . '|' . $clientId), 0, 16);

// 同一人同店當日、未核銷 → 沿用既有碼（避免每次點都發新碼）
$reuse = $db->prepare("SELECT code FROM coupon_claims
    WHERE client_id = ? AND ip_hash = ? AND redeemed_at IS NULL AND DATE(claimed_at) = ?
    ORDER BY id DESC LIMIT 1");
$reuse->execute([$clientId, $ipHash, $today]);
if ($code = $reuse->fetchColumn()) {
    jout(['ok' => true, 'code' => $code, 'display' => substr($code, 0, 4) . '-' . substr($code, 4)]);
}

// 產生唯一碼（去除易混淆字元 0/O/1/I）
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$gen = function () use ($alphabet): string {
    $s = '';
    for ($i = 0; $i < 8; $i++) { $s .= $alphabet[random_int(0, strlen($alphabet) - 1)]; }
    return $s;
};

$ins = $db->prepare("INSERT INTO coupon_claims (client_id, code, coupon_title, ip_hash, claimed_at)
                     VALUES (?, ?, ?, ?, NOW())");
for ($try = 0; $try < 6; $try++) {
    $code = $gen();
    try {
        $ins->execute([$clientId, $code, $c['coupon_title'] ?? null, $ipHash]);
        jout(['ok' => true, 'code' => $code, 'display' => substr($code, 0, 4) . '-' . substr($code, 4)]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { continue; } // 唯一碼碰撞，重試
        http_response_code(500); jout(['ok' => false, 'err' => 'db']);
    }
}
http_response_code(500); jout(['ok' => false, 'err' => 'gen_failed']);
