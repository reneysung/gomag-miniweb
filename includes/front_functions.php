<?php
// ============================================================
// includes/front_functions.php  ─  前台共用函式（子網域版）
// ============================================================

/**
 * 偵測目前要顯示哪個客戶的子網域
 *
 * 優先順序：
 * 1. Apache 環境變數 SITE_SUB（.htaccess E=SITE_SUB:%1 設定）
 * 2. HTTP_HOST 直接解析（xusen.gomag.com.tw → xusen）
 * 3. GET 參數 ?sub=xusen（本機開發 fallback）
 * 4. GET 參數 ?slug=xulang（舊版 fallback）
 */
function getSubdomain(): string {
    // 1. Apache env var（正式環境，.htaccess 設定）
    $env = $_SERVER['REDIRECT_SITE_SUB']
        ?? $_SERVER['SITE_SUB']
        ?? '';
    if ($env) return strtolower(trim($env));

    // 2. 從 HTTP_HOST 解析
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (preg_match('/^([a-z0-9-]+)\.gomag\.com\.tw$/i', $host, $m)) {
        return strtolower($m[1]);
    }

    // 3. 本機開發：GET 參數
    return strtolower(trim($_GET['sub'] ?? $_GET['slug'] ?? ''));
}

/**
 * 產生前台頁面 URL
 *
 * 正式環境：https://xusen.gomag.com.tw/services
 * 本機環境：http://localhost:8888/miniweb/xusen/services
 */
function siteUrl(string $sub, string $page = ''): string {
    if (IS_LOCAL) {
        // 本機：用 query parameter，不需要 mod_rewrite
        if (!$page || $page === 'index') {
            return BASE_URL . '/site/index.php?sub=' . urlencode($sub);
        }
        return BASE_URL . '/site/' . $page . '.php?sub=' . urlencode($sub);
    }
    // 正式：子網域 + pretty URL
    $base = 'https://' . $sub . '.gomag.com.tw';
    if (!$page || $page === 'index') return $base . '/';
    return $base . '/' . $page;
}

/**
 * 依子網域載入完整前台資料
 * 查 clients.subdomain 欄位（新架構）
 * 如果找不到則 fallback 到 clients.slug（相容舊資料）
 */
function loadSiteData(string $sub): array {
    if (!$sub) return [];
    $db = getDB();

    // 先查 subdomain 欄位（新架構）
    $stmt = $db->prepare('SELECT * FROM clients WHERE subdomain=? AND is_active=1 LIMIT 1');
    $stmt->execute([$sub]);
    $client = $stmt->fetch();

    // fallback：查 slug（舊資料相容）
    if (!$client) {
        $stmt = $db->prepare('SELECT * FROM clients WHERE slug=? AND is_active=1 LIMIT 1');
        $stmt->execute([$sub]);
        $client = $stmt->fetch();
    }

    if (!$client) return [];
    $cid = (int)$client['id'];

    // 主題
    $theme = getActiveTheme($cid);

    // 社群
    $s = $db->prepare('SELECT * FROM client_social WHERE client_id=?');
    $s->execute([$cid]);
    $social = $s->fetch() ?: [];

    // 服務 + FAQ
    $svc = $db->prepare('SELECT * FROM services WHERE client_id=? AND is_active=1 ORDER BY sort_order,id');
    $svc->execute([$cid]);
    $services = $svc->fetchAll();

    foreach ($services as &$srv) {
        $fq = $db->prepare('SELECT * FROM service_faqs WHERE service_id=? AND client_id=? ORDER BY sort_order');
        $fq->execute([$srv['id'], $cid]);
        $srv['faqs'] = $fq->fetchAll();
    }
    unset($srv);

    // 案例
    $ca = $db->prepare('
        SELECT c.*, s.name AS svc_name
        FROM cases c
        LEFT JOIN services s ON c.service_id = s.id
        WHERE c.client_id=? AND c.is_active=1
        ORDER BY c.is_featured DESC, c.sort_order, c.id DESC
    ');
    $ca->execute([$cid]);
    $cases = $ca->fetchAll();

    // 評價
    $te = $db->prepare('SELECT t.*, s.name AS svc_name FROM testimonials t LEFT JOIN services s ON t.service_id=s.id WHERE t.client_id=? AND t.is_active=1 ORDER BY t.sort_order,t.id');
    $te->execute([$cid]);
    $testimonials = $te->fetchAll();

    // SEO
    $se = $db->prepare('SELECT * FROM seo_settings WHERE client_id=?');
    $se->execute([$cid]);
    $seo = [];
    foreach ($se->fetchAll() as $r) $seo[$r['page_key']] = $r;

    return compact('client','theme','social','services','cases','testimonials','seo');
}

/**
 * 輸出動態 CSS 變數
 */
function outputThemeCss(array $theme): void {
    echo '<style>:root{';
    echo '--c-primary:'     . $theme['color_primary'] . ';';
    echo '--c-accent:'      . $theme['color_accent']  . ';';
    echo '--c-bg:'          . $theme['color_bg']      . ';';
    echo '--c-text:'        . $theme['color_text']    . ';';
    echo '--c-light:'       . $theme['color_light']   . ';';
    $p = hexToRgb($theme['color_primary']);
    $a = hexToRgb($theme['color_accent']);
    echo '--c-primary-rgb:' . implode(',', $p) . ';';
    echo '--c-accent-rgb:'  . implode(',', $a) . ';';
    echo '}</style>';
}

function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

function getSeo(array $site, string $pageKey): array {
    return $site['seo'][$pageKey] ?? [];
}

/**
 * 取得案例的縮圖路徑
 * 如果 before_image/after_image 是資料夾，回傳第一張圖
 * 如果是單一檔案路徑，直接回傳
 */
function caseThumb(string $path): string {
    if (!$path) return '';
    $base = dirname(__DIR__); // /Applications/MAMP/htdocs/miniweb
    $full = $base . '/' . $path;
    if (is_dir($full)) {
        $imgs = glob($full . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        sort($imgs);
        return $imgs ? $path . '/' . basename($imgs[0]) : '';
    }
    return $path;
}
