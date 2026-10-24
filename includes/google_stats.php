<?php
// includes/google_stats.php — 從 Google Search Console 與 GA4 抓「每頁」數字（後台 admin/pages/page_stats.php 用）
//
// 認證：Google Cloud 服務帳戶 JSON 金鑰，放 webroot 外 ~/domains/gomag.com.tw/gomag-google-sa.json（mode 600）。
//       用 openssl 自己簽 JWT 換 access token，不需要裝 google 套件。
// 快取：每個日期區間一個 JSON 檔，放 webroot 外 ~/domains/gomag.com.tw/gomag-gstats-cache/，12 小時內不重抓。
//       不寫資料庫（主機 DB 有資源限流，大量寫入容易出事）。
// 設定：platform_settings 的 ga4_property_id（純數字）、gsc_site_url（預設 sc-domain:gomag.com.tw）。

const GSTATS_TTL = 12 * 3600;

function gstatsKeyPath(): string   { return dirname(__DIR__, 2) . '/gomag-google-sa.json'; }
function gstatsCacheDir(): string  { return dirname(__DIR__, 2) . '/gomag-gstats-cache'; }

/** 讀服務帳戶金鑰；沒有或格式不對回 null */
function gstatsServiceAccount(): ?array {
    $p = gstatsKeyPath();
    if (!is_file($p)) return null;
    $sa = json_decode((string)file_get_contents($p), true);
    return (is_array($sa) && !empty($sa['client_email']) && !empty($sa['private_key'])) ? $sa : null;
}

function gstatsB64url(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

function gstatsHttp(string $url, ?array $body = null, ?string $token = null, ?string $form = null): array {
    $ch = curl_init($url);
    $headers = [];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    if ($body !== null) { $headers[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
    if ($form !== null) { $headers[] = 'Content-Type: application/x-www-form-urlencoded'; curl_setopt($ch, CURLOPT_POSTFIELDS, $form); }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_HTTPHEADER => $headers]);
    $raw  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException('連不到 Google：' . $err);
    return [$code, json_decode((string)$raw, true) ?: []];
}

/** 服務帳戶換 access token */
function gstatsToken(array $sa, string $scope): string {
    $now = time();
    $head = gstatsB64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claim = gstatsB64url(json_encode([
        'iss' => $sa['client_email'], 'scope' => $scope,
        'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
    ]));
    if (!openssl_sign("$head.$claim", $sig, $sa['private_key'], 'sha256WithRSAEncryption')) {
        throw new RuntimeException('金鑰簽名失敗（金鑰檔可能損壞）');
    }
    [$code, $j] = gstatsHttp('https://oauth2.googleapis.com/token', null, null,
        http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => "$head.$claim." . gstatsB64url($sig)]));
    if ($code !== 200 || empty($j['access_token'])) {
        throw new RuntimeException('換通行證失敗：' . ($j['error_description'] ?? $j['error'] ?? "HTTP $code"));
    }
    return $j['access_token'];
}

/** 統一網址當 key：host 小寫、去 query/#、解 %XX、去尾斜線（首頁保留 /） */
function gstatsKey(string $host, string $path): string {
    $path = (string)preg_replace('/[?#].*$/', '', $path);
    $path = rawurldecode($path);
    if ($path === '') $path = '/';
    if ($path !== '/') $path = rtrim($path, '/');
    return strtolower($host) . $path;
}

/** 把 Google 的錯誤翻成要做什麼 */
function gstatsExplain(string $src, int $code, array $j): string {
    $msg = $j['error']['message'] ?? ("HTTP $code");
    if ($code === 403 && stripos($msg, 'has not been used') !== false) return "{$src}：Google Cloud 專案還沒啟用這支 API（{$msg}）";
    if ($code === 403) return "{$src}：服務帳戶還沒有讀取權限——要把服務帳戶 email 加進 $src 的使用者／存取權（{$msg}）";
    if ($code === 404) return "{$src}：找不到資源——檢查平台設定裡的編號／網址（{$msg}）";
    return "{$src}：$msg";
}

/** GSC 每頁：clicks / impressions / position（日期 YYYY-MM-DD） */
function gstatsFetchGsc(string $token, string $site, string $start, string $end): array {
    $out = []; $startRow = 0;
    do {
        [$code, $j] = gstatsHttp('https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($site) . '/searchAnalytics/query',
            ['startDate' => $start, 'endDate' => $end, 'dimensions' => ['page'], 'rowLimit' => 25000, 'startRow' => $startRow], $token);
        if ($code !== 200) throw new RuntimeException(gstatsExplain('Search Console', $code, $j));
        $rows = $j['rows'] ?? [];
        foreach ($rows as $r) {
            $u = parse_url($r['keys'][0] ?? '');
            if (empty($u['host'])) continue;
            $k = gstatsKey($u['host'], $u['path'] ?? '/');
            $o = $out[$k] ?? ['c' => 0, 'i' => 0, 'pw' => 0.0];
            $o['c'] += (int)$r['clicks']; $o['i'] += (int)$r['impressions']; $o['pw'] += (float)$r['position'] * (int)$r['impressions'];
            $out[$k] = $o;
        }
        $startRow += 25000;
    } while (count($rows) === 25000 && $startRow < 200000);
    return $out;
}

/** GA4 每頁：views / users */
function gstatsFetchGa4(string $token, string $propertyId, string $start, string $end): array {
    $out = []; $offset = 0;
    do {
        [$code, $j] = gstatsHttp('https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($propertyId) . ':runReport', [
            'dateRanges' => [['startDate' => $start, 'endDate' => $end]],
            'dimensions' => [['name' => 'hostName'], ['name' => 'pagePath']],
            'metrics'    => [['name' => 'screenPageViews'], ['name' => 'totalUsers']],
            'limit' => 100000, 'offset' => $offset,
        ], $token);
        if ($code !== 200) throw new RuntimeException(gstatsExplain('GA4', $code, $j));
        $rows = $j['rows'] ?? [];
        foreach ($rows as $r) {
            $k = gstatsKey($r['dimensionValues'][0]['value'] ?? '', $r['dimensionValues'][1]['value'] ?? '/');
            $o = $out[$k] ?? ['v' => 0, 'u' => 0];
            $o['v'] += (int)($r['metricValues'][0]['value'] ?? 0); $o['u'] += (int)($r['metricValues'][1]['value'] ?? 0);
            $out[$k] = $o;
        }
        $offset += 100000;
    } while ($offset < (int)($j['rowCount'] ?? 0) && $offset < 500000);
    return $out;
}

/**
 * 取某個區間（近 N 天）的每頁數字。12 小時內讀快取；$force=true 強制重抓。
 * 區間結束日＝台北時間前天（GSC 有 2 天延遲，GA4 用同一段才好對照）。
 * 回傳 ['start','end','fetched_at','gsc'=>map|null,'ga4'=>map|null,'errors'=>[]]
 */
function gstatsGet(int $days, bool $force = false): array {
    $dir = gstatsCacheDir();
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $file = "$dir/range_{$days}.json";
    if (!$force && is_file($file) && (time() - filemtime($file)) < GSTATS_TTL) {
        $c = json_decode((string)file_get_contents($file), true);
        if (is_array($c)) return $c;
    }

    $end   = date('Y-m-d', strtotime('-2 days'));
    $start = date('Y-m-d', strtotime($end . ' -' . ($days - 1) . ' days'));
    $res = ['start' => $start, 'end' => $end, 'fetched_at' => time(), 'gsc' => null, 'ga4' => null, 'errors' => []];

    $sa = gstatsServiceAccount();
    if (!$sa) { $res['errors'][] = '還沒放服務帳戶金鑰'; return $res; }

    try {
        $site = getPlatformSetting('gsc_site_url', 'sc-domain:gomag.com.tw') ?: 'sc-domain:gomag.com.tw';
        $res['gsc'] = gstatsFetchGsc(gstatsToken($sa, 'https://www.googleapis.com/auth/webmasters.readonly'), $site, $start, $end);
    } catch (Throwable $e) { $res['errors'][] = $e->getMessage(); }

    $prop = preg_replace('/\D/', '', getPlatformSetting('ga4_property_id', ''));
    if ($prop === '') {
        $res['errors'][] = 'GA4：平台設定還沒填「GA4 資源編號」';
    } else {
        try { $res['ga4'] = gstatsFetchGa4(gstatsToken($sa, 'https://www.googleapis.com/auth/analytics.readonly'), $prop, $start, $end); }
        catch (Throwable $e) { $res['errors'][] = $e->getMessage(); }
    }

    // 至少一邊成功才寫快取，避免把錯誤狀態快取 12 小時
    if ($res['gsc'] !== null || $res['ga4'] !== null) {
        @file_put_contents($file, json_encode($res, JSON_UNESCAPED_UNICODE), LOCK_EX);
        @chmod($file, 0600);
    }
    return $res;
}
