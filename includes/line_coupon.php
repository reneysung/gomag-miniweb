<?php
// ============================================================
// includes/line_coupon.php ─ 消費者「把優惠券存到 LINE」共用函式
//
// 用 LINE Login（OAuth）認出消費者 → Messaging API push 把券推進他的 LINE。
// OA：口碑製造所（@316swndx，消費者用）。※內部合約通知是另一個 OA，勿混。
//
// 金鑰放 webroot 外的 gomag-secrets.php（與 DB 密碼同一套），本檔不含明文：
//   $GOMAG_LINE_LOGIN_ID     = '2011570318';                        // LINE Login channel ID
//   $GOMAG_LINE_LOGIN_SECRET = '...';                               // LINE Login channel secret
//   $GOMAG_LINE_PUSH_TOKEN   = '...';                               // 口碑製造所 Messaging API long-lived token
// ============================================================

require_once __DIR__ . '/config.php';

// 從 config.php 已 include 的 gomag-secrets.php 取值（該檔在全域設變數）
if (!defined('LINE_LOGIN_ID')) {
    define('LINE_LOGIN_ID',     (string)($GLOBALS['GOMAG_LINE_LOGIN_ID']     ?? ''));
    define('LINE_LOGIN_SECRET', (string)($GLOBALS['GOMAG_LINE_LOGIN_SECRET'] ?? ''));
    define('LINE_PUSH_TOKEN',   (string)($GLOBALS['GOMAG_LINE_PUSH_TOKEN']   ?? ''));
}

/** 功能是否已設定齊全（沒設就不顯示「存到 LINE」按鈕、端點也擋下） */
function lineCouponEnabled(): bool {
    return LINE_LOGIN_ID !== '' && LINE_LOGIN_SECRET !== '' && LINE_PUSH_TOKEN !== '';
}

/** 本站的 callback 網址（必須與 LINE Login 後台登記的完全一致） */
function lineCouponRedirectUri(): string {
    return rtrim(BASE_URL, '/') . '/coupon_line_callback.php';
}

/** 組 LINE Login 授權網址；$state 供 callback 驗證（防 CSRF） */
function lineCouponLoginUrl(string $slug, string $state): string {
    $params = [
        'response_type' => 'code',
        'client_id'     => LINE_LOGIN_ID,
        'redirect_uri'  => lineCouponRedirectUri(),
        'state'         => $state,
        'scope'         => 'profile openid',
        // 消費者授權時一併顯示「加入好友」（push 要對方是好友才送得到）
        'bot_prompt'    => 'aggressive',
    ];
    return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
}

/** 小工具：POST/GET 到 LINE API，回 [http_code, decoded_json|null] */
function lineHttp(string $url, ?array $post, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        // 若 headers 指定 application/json，$post 已是 json 字串包在 [0]
        if (isset($post['__json'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post['__json']);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body ? json_decode($body, true) : null];
}

/** 用授權碼換 access token（拿到後可查 profile） */
function lineExchangeToken(string $code): ?array {
    [$c, $j] = lineHttp('https://api.line.me/oauth2/v2.1/token', [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => lineCouponRedirectUri(),
        'client_id'     => LINE_LOGIN_ID,
        'client_secret' => LINE_LOGIN_SECRET,
    ], ['Content-Type: application/x-www-form-urlencoded']);
    return ($c === 200 && !empty($j['access_token'])) ? $j : null;
}

/** 用 user access token 取得 userId + 顯示名稱 */
function lineGetProfile(string $userAccessToken): ?array {
    [$c, $j] = lineHttp('https://api.line.me/v2/profile', null, [
        'Authorization: Bearer ' . $userAccessToken,
    ]);
    return ($c === 200 && !empty($j['userId'])) ? $j : null;
}

/**
 * 依 LINE userId 發（或沿用）一組核銷碼。與 coupon_claim.php 同表 coupon_claims，
 * dedup key 改用 userId（同一 LINE 用戶對同一店、未核銷 → 沿用同一碼）。
 * 回傳 [ok(bool), code|err, client(array)]
 */
function claimCouponForLineUser(PDO $db, string $slug, string $userId): array {
    $slug = strtolower(trim($slug));
    if ($slug === '' || !preg_match('/^[a-z0-9_-]+$/', $slug)) return [false, 'bad_slug', []];

    $st = $db->prepare("SELECT id, slug, brand_name, coupon_enabled, coupon_title, coupon_desc, coupon_expiry
                        FROM clients WHERE slug = ? AND is_active = 1 LIMIT 1");
    $st->execute([$slug]);
    $c = $st->fetch(PDO::FETCH_ASSOC);
    if (!$c || empty($c['coupon_enabled'])) return [false, 'no_coupon', []];
    if (!empty($c['coupon_expiry']) && ($ts = strtotime($c['coupon_expiry'])) !== false
        && $ts < strtotime(date('Y-m-d'))) return [false, 'expired', $c];

    $clientId = (int)$c['id'];
    // 用 userId 當 dedup key（存進 ip_hash 欄；加 line| 前綴便於辨識來源）
    $key = substr(sha1('line|' . $userId . '|' . $clientId), 0, 16);

    $reuse = $db->prepare("SELECT code FROM coupon_claims
        WHERE client_id = ? AND ip_hash = ? AND redeemed_at IS NULL ORDER BY id DESC LIMIT 1");
    $reuse->execute([$clientId, $key]);
    if ($code = $reuse->fetchColumn()) return [true, $code, $c];

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $gen = function () use ($alphabet): string {
        $s = ''; for ($i = 0; $i < 8; $i++) { $s .= $alphabet[random_int(0, strlen($alphabet) - 1)]; } return $s;
    };
    $ins = $db->prepare("INSERT INTO coupon_claims (client_id, code, coupon_title, ip_hash, claimed_at)
                         VALUES (?, ?, ?, ?, NOW())");
    for ($try = 0; $try < 6; $try++) {
        $code = $gen();
        try { $ins->execute([$clientId, $code, $c['coupon_title'] ?? null, $key]); return [true, $code, $c]; }
        catch (PDOException $e) { if ($e->getCode() === '23000') continue; return [false, 'db', $c]; }
    }
    return [false, 'gen_failed', $c];
}

/**
 * 把優惠券以 Flex 訊息 push 到消費者 LINE。
 * $client：claimCouponForLineUser 回傳的店家列；$code：核銷碼；$storeLineUrl：店家自己的 LINE（可空）
 * 回傳 true/false（push 是否成功；失敗時呼叫端仍會在畫面顯示券）
 */
function linePushCoupon(string $userId, array $client, string $code, string $storeLineUrl = ''): bool {
    if (!lineCouponEnabled()) return false;
    $brand   = (string)($client['brand_name'] ?? '本店');
    $title   = (string)($client['coupon_title'] ?? '優惠券');
    $expiry  = trim((string)($client['coupon_expiry'] ?? ''));
    $display = strlen($code) === 8 ? substr($code, 0, 4) . '-' . substr($code, 4) : $code;
    $redeemUrl = rtrim(BASE_URL, '/') . '/coupon_redeem.php?c=' . rawurlencode($code);
    $qrUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=12&data=' . rawurlencode($redeemUrl);

    $bodyContents = [
        ['type' => 'text', 'text' => $brand, 'size' => 'sm', 'color' => '#8a6d63', 'weight' => 'bold'],
        ['type' => 'text', 'text' => $title, 'size' => 'xl', 'weight' => 'bold', 'wrap' => true, 'color' => '#1a1a1a', 'margin' => 'sm'],
        ['type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'spacing' => 'xs',
         'backgroundColor' => '#FFF3EF', 'cornerRadius' => '10px', 'paddingAll' => '14px', 'contents' => [
            ['type' => 'text', 'text' => '核銷碼（出示給店家）', 'size' => 'xxs', 'color' => '#c0392b', 'align' => 'center', 'weight' => 'bold'],
            ['type' => 'text', 'text' => $display, 'size' => 'xxl', 'weight' => 'bold', 'color' => '#FF5A36', 'align' => 'center'],
        ]],
    ];
    if ($expiry !== '') {
        $bodyContents[] = ['type' => 'text', 'text' => '有效期限至 ' . $expiry, 'size' => 'xs', 'color' => '#999999', 'align' => 'center', 'margin' => 'md'];
    }
    $bodyContents[] = ['type' => 'text', 'text' => '結帳前出示此券給店家', 'size' => 'xs', 'color' => '#1a1a1a', 'align' => 'center', 'margin' => 'md', 'weight' => 'bold'];

    $bubble = [
        'type' => 'bubble',
        'hero' => [
            'type' => 'image', 'url' => $qrUrl, 'size' => 'full', 'aspectRatio' => '1:1', 'aspectMode' => 'fit',
            'backgroundColor' => '#FFFFFF',
        ],
        'body' => ['type' => 'box', 'layout' => 'vertical', 'contents' => $bodyContents],
    ];
    // 店家有填 LINE → 底部加「加店家 LINE 預約」按鈕
    if ($storeLineUrl !== '' && preg_match('#^https?://#', $storeLineUrl)) {
        $bubble['footer'] = ['type' => 'box', 'layout' => 'vertical', 'contents' => [
            ['type' => 'button', 'style' => 'primary', 'color' => '#06C755', 'height' => 'sm',
             'action' => ['type' => 'uri', 'label' => '💬 加店家 LINE 預約', 'uri' => $storeLineUrl]],
        ]];
    }

    $msg = ['type' => 'flex', 'altText' => '🎁 你的優惠券：' . $title . '（核銷碼 ' . $display . '）', 'contents' => $bubble];
    $payload = json_encode(['to' => $userId, 'messages' => [$msg]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    [$c, ] = lineHttp('https://api.line.me/v2/bot/message/push', ['__json' => $payload], [
        'Content-Type: application/json',
        'Authorization: Bearer ' . LINE_PUSH_TOKEN,
    ]);
    return $c === 200;
}
