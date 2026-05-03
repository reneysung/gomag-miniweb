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
    // 本機 + Hostinger staging（沒有真實子網域 DNS）→ 都走 query 模式
    // 只有 prod gomag.com.tw 才走子網域 + pretty URL
    if (IS_LOCAL || IS_STAGING) {
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
    // Phase 1.2：site/* 升級到 g-* token，仍保留 per-client 客製色機制
    // 同時輸出舊 --c-* 跟新 --g-* 供向後相容
    $p = hexToRgb($theme['color_primary']);
    $a = hexToRgb($theme['color_accent']);
    $pRgb = implode(',', $p);
    $aRgb = implode(',', $a);
    echo '<style>:root{';
    // 舊 --c-* token（向後相容，未來可移除）
    echo '--c-primary:' . $theme['color_primary'] . ';';
    echo '--c-accent:'  . $theme['color_accent']  . ';';
    echo '--c-bg:'      . $theme['color_bg']      . ';';
    echo '--c-text:'    . $theme['color_text']    . ';';
    echo '--c-light:'   . $theme['color_light']   . ';';
    echo '--c-primary-rgb:' . $pRgb . ';';
    echo '--c-accent-rgb:'  . $aRgb . ';';
    // 新 --g-* token override gomag.css 預設（讓 g-* 元件套客戶色）
    echo '--g-ink:'      . $theme['color_primary'] . ';';
    echo '--g-accent:'   . $theme['color_accent']  . ';';
    echo '--g-bg:'       . $theme['color_bg']      . ';';
    echo '--g-bg-alt:'   . $theme['color_light']   . ';';
    echo '--g-ink-soft:' . $theme['color_text']    . ';';
    echo '--g-ink-rgb:'  . $pRgb . ';';
    echo '--g-accent-rgb:' . $aRgb . ';';
    echo '}</style>';
}

/**
 * 業種預設 hero 背景圖 — 沒設 client.hero_image_path 時 fallback
 * 來源：Unsplash 免費商用圖（可未來換成自家 CDN）
 */
function industryDefaultHero(string $industry): string {
    $map = [
        '餐|食|料理|咖啡|甜點|烘焙'  => 'photo-1517248135467-4c7edcad34c4',  // food spread
        '清潔|居家|裝潢|裝修|地板|系統|家具' => 'photo-1581578731548-c64695cc6952',  // cleaning
        '美容|美髮|美睫|美甲|按摩|spa' => 'photo-1521590832167-7bcbfaa6381f',  // salon
        '汽車|機車|車體|包膜|保養'   => 'photo-1492144534655-ae79c964c9d7',  // auto
        '旅館|民宿|飯店|住宿|motel'  => 'photo-1542314831-068cd1dbfeeb',     // hotel
        '教育|教學|補習|學|語言|家教'   => 'photo-1503676260728-1c00da094a0b',  // education
        '醫|診所|健康|長照'         => 'photo-1559329007-40df8a9345d8',     // clinic
        '零售|購物|批發|商品'        => 'photo-1481437156560-3205f6a55735',  // shop
        '工程|寬頻|網路|資訊|專業|企管'  => 'photo-1554224155-6726b3ff858f',  // office/professional
        '婚禮|活動|宴會'           => 'photo-1519741497674-611481863552',  // wedding
        '運動|健身|休閒'           => 'photo-1517649763962-0c623066013b',  // sports
    ];
    foreach ($map as $pattern => $unsplashId) {
        if (preg_match('/(' . $pattern . ')/u', $industry)) {
            return "https://images.unsplash.com/{$unsplashId}?w=1800&q=80&auto=format&fit=crop";
        }
    }
    return 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1800&q=80&auto=format&fit=crop';
}

/**
 * 餐飲業攝影集 — 給 mini-site 食品店家鋪滿 atmospheric 照片用
 * 來源：Unsplash 免費商用，curated steakhouse/restaurant photography
 */
function foodPhotoSet(): array {
    $base = 'https://images.unsplash.com/';
    $params = '?w=1400&q=85&auto=format&fit=crop';
    // 統一牛排館主題（無咖哩、無彩色甜點，避免風格跳 tone）
    return [
        'hero'     => $base . 'photo-1600891964599-f61ba0e24092' . $params,  // 烤牛排 hero
        'interior' => $base . 'photo-1414235077428-338989a2e8c0' . $params,  // 暗色餐廳內裝
        'fire'     => $base . 'photo-1544025162-d76694265947' . $params,     // 炭火烤肋排
        'sliced'   => $base . 'photo-1558030006-450675393462' . $params,     // 切片牛排
        'meat'     => $base . 'photo-1607528048834-30bff58fbb87' . $params,  // 生肉備料
        'wine'     => $base . 'photo-1510812431401-41d2bd2722f3' . $params,  // 紅酒
        'chef'     => $base . 'photo-1577106263724-2c8e03bfe9cf' . $params,  // 主廚備餐
        'table'    => $base . 'photo-1517248135467-4c7edcad34c4' . $params,  // 餐桌擺設
        // legacy 別名（向後相容）
        'plating'  => $base . 'photo-1558030006-450675393462' . $params,
        'closeup'  => $base . 'photo-1558030006-450675393462' . $params,
        'cocktail' => $base . 'photo-1510812431401-41d2bd2722f3' . $params,
        'dessert'  => $base . 'photo-1577106263724-2c8e03bfe9cf' . $params,
        'side'     => $base . 'photo-1607528048834-30bff58fbb87' . $params,
    ];
}

/**
 * 服務業攝影集 — 給清潔/裝修/汽美/居家服務 mini-site 用
 * Unsplash 免費商用，curated cleaning/home service photography
 */
function servicePhotoSet(): array {
    $base = 'https://images.unsplash.com/';
    $params = '?w=1400&q=85&auto=format&fit=crop';
    return [
        'hero'      => $base . 'photo-1581578731548-c64695cc6952' . $params,  // 清潔噴霧
        'living'    => $base . 'photo-1416879595882-3373a0480b5b' . $params,  // 整潔客廳
        'kitchen'   => $base . 'photo-1556909114-f6e7ad7d3136' . $params,     // 乾淨廚房
        'bathroom'  => $base . 'photo-1552321554-5fefe8c9ef14' . $params,     // 整齊浴室
        'team'      => $base . 'photo-1604147706283-d7119b5b822c' . $params,  // 服務團隊
        'tools'     => $base . 'photo-1527515637462-cff94eecc1ac' . $params,  // 清潔工具
        'window'    => $base . 'photo-1527515545081-5db817172677' . $params,  // 擦窗
        'before'    => $base . 'photo-1558618666-fcd25c85cd64' . $params,     // before 髒亂
        'after'     => $base . 'photo-1493809842364-78817add7ffb' . $params,  // after 整潔
        'detail'    => $base . 'photo-1527515637462-cff94eecc1ac' . $params,  // 細節
        'happy'     => $base . 'photo-1582719471384-894fbb16e074' . $params,  // 滿意客戶
        'workshop'  => $base . 'photo-1581578731548-c64695cc6952' . $params,  // 施工
    ];
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
