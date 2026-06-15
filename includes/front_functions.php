<?php
// ============================================================
// includes/front_functions.php  ─  前台共用函式（子網域版）
// ============================================================

/**
 * 同店多筆 → 已 301 到主檔的 slug。各 listing 頁（sitemap / category /
 * city / search）排除這些 slug 不再顯示。store.php $slug_redirects 使用
 * lowercased key 做比對；這裡用 DB 實際的 slug 大小寫。
 */
function getDuplicateSkipSlugs(): array {
    return [
        'Interiordesign72',   // 亞筑 → artru          (id=99  → 213)
        'Interiordesign214',  // 聯漢 → lanhung        (id=124 → 214)
        'modifiedcars3',      // 光點線 → modifiedcars (id=195 → 46)
        'gourmetrestaurant1', // 來道好食雞 → gourmetrestaurant2 (id=76 → 145)
        '065957487',          // 二鍋壽喜燒 → 062263168 (id=90 → 13)
        'docaroating',        // 鍍卡：拼錯修正 → docar
        'cleaningcompany5',   // 三峰清潔 → sanfengclean  (id=197 → 218)
        'xusen',              // 旭浪清潔 demo → 外部 062051129 舊官網（不是站內合併）
    ];
}

/**
 * 縣市 slug ↔ 中文全名對映的「唯一來源」（取代寫死在 city/sitemap/store/index 的 array）。
 * 讀 cities 表全部 row（不濾 is_active：這是路由對映，與「是否顯示城市介紹」無關）。
 * 回傳 [slug => full_name]，名→slug 由呼叫端 array_flip()。
 * 開新縣市 = cities 表新增一筆 row，不動程式碼。
 */
function getCityMap(): array {
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    try {
        foreach (getDB()->query("SELECT slug, full_name FROM cities ORDER BY sort_order, id") as $r) {
            $map[$r['slug']] = $r['full_name'];
        }
    } catch (\Throwable $e) {
        $map = [];  // cities 表還不存在時不致命
    }
    return $map;
}

/**
 * 從 address 推導縣市 slug（取代到處 address LIKE '台中市%' 的脆弱比對）。
 * 台/臺 正規化後對 cities 表的 full_name；非 12 對映縣市（雲林/彰化…）回 null。
 * 用於 migration backfill + admin 存檔時重算 clients.city_slug。
 */
function deriveCitySlug(string $address): ?string {
    $address = trim($address);
    if ($address === '') return null;
    static $nameToSlug = null;
    if ($nameToSlug === null) $nameToSlug = array_flip(getCityMap());
    // 去掉開頭雜訊再比對：郵遞區號（3-6 碼）、「台灣/臺灣」前綴
    // 例：「709台灣臺南市安南區…」→「臺南市安南區…」；保持開頭錨定避免誤判中間出現的城市名
    $address = preg_replace('/^\s*\d{3,6}\s*/u', '', $address);
    $address = preg_replace('/^\s*(台灣|臺灣)\s*/u', '', $address);
    $address = ltrim($address);
    $re = '/^(臺北市|台北市|新北市|桃園市|臺中市|台中市|臺南市|台南市|高雄市|基隆市|新竹市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義市|嘉義縣|屏東市|屏東縣|宜蘭市|宜蘭縣|花蓮市|花蓮縣|臺東市|台東市|臺東縣|台東縣|澎湖縣|金門縣|連江縣)/u';
    if (!preg_match($re, $address, $m)) return null;
    $name = str_replace('臺', '台', $m[1]);
    // 縣轄市歸所屬縣（屏東市屬屏東縣…cities 表只有縣名）
    $name = ['屏東市' => '屏東縣', '台東市' => '台東縣', '宜蘭市' => '宜蘭縣', '花蓮市' => '花蓮縣'][$name] ?? $name;
    return $nameToSlug[$name] ?? null;
}

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
    // 正式：子網域 + pretty URL（mini-site 走獨立白牌域名 wmf.com.tw）
    $base = 'https://' . $sub . '.' . MINISITE_DOMAIN;
    if (!$page || $page === 'index') return $base . '/';
    return $base . '/' . $page;
}

/**
 * 客戶公開 URL：依 has_minisite / external_website_url 決定卡片連結目的地
 * 主站列表頁（index / category / city）的客戶卡片用這個 helper 統一邏輯：
 *   1. has_minisite=1 → mini-site 首頁（直接連，省一跳，PageRank 直接傳遞）
 *   2. external_website_url 設了 → 外部官網
 *   3. fallback → 主站 /store/{sub}（has_minisite=0 客戶用）
 *
 * @param array $cl clients 表 row（需 has_minisite / external_website_url / subdomain / slug）
 */
function clientPublicUrl(array $cl): string {
    $sub = $cl['subdomain'] ?: $cl['slug'];
    // 1. mini-site 優先
    if (!empty($cl['has_minisite'])) {
        return (IS_LOCAL || IS_STAGING)
            ? BASE_URL . '/site/index.php?sub=' . urlencode($sub)
            : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/';
    }
    // 2. 外部官網
    if (!empty($cl['external_website_url']) && filter_var($cl['external_website_url'], FILTER_VALIDATE_URL)) {
        return $cl['external_website_url'];
    }
    // 3. 主站 store.php
    return (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/store.php?sub=' . urlencode($sub)
        : 'https://www.gomag.com.tw/store/' . urlencode($sub);
}

/**
 * 主站行銷頁 URL（always /store/{slug}）— 給 directory 卡片用
 * 設計策略：主站目錄 / 縣市頁 / 分類頁 / 搜尋頁的卡片連結，永遠指向行銷頁。
 * 小官網（mini-site）是獨立品牌入口，只有打網址或從 Google 進來的人才會看到。
 *
 * 跟 clientPublicUrl 的差別：本 helper 不管 has_minisite 一律回 /store/{slug}。
 */
function clientStoreUrl(array $cl, string $cityVariantSlug = ''): string {
    $sub = $cl['subdomain'] ?: $cl['slug'];
    if (IS_LOCAL || IS_STAGING) {
        $u = BASE_URL . '/store.php?sub=' . urlencode($sub);
        if ($cityVariantSlug !== '') $u .= '&city=' . urlencode($cityVariantSlug);
        return $u;
    }
    $u = 'https://www.gomag.com.tw/store/' . urlencode($sub);
    if ($cityVariantSlug !== '') $u .= '/' . urlencode($cityVariantSlug);
    return $u;
}

/**
 * 店家卡圖 g-store-img 的 inline style：contain 客戶(Logo) → 完整顯示+白底，否則填滿(cover)。
 * $imgUrl 須為已可直接用的網址（呼叫端自行接 BASE_URL / h()）。
 */
function gStoreImgStyle(string $imgUrl, ?string $fit = 'cover'): string {
    if ($imgUrl === '') return '';
    $style = "background-image:url('" . $imgUrl . "')";
    if (($fit ?: 'cover') === 'contain') {
        $style .= ';background-size:contain;background-color:#fff';
    }
    return ' style="' . $style . '"';
}

/**
 * 圖片網址容錯：值若已是絕對網址(http/https)直接回傳，否則接 BASE_URL。
 * 根治「hero_image_path 存成絕對網址 → 又接 BASE_URL → 雙網址壞圖」。
 */
function mediaUrl(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    return BASE_URL . '/' . ltrim($path, '/');
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
    $te = $db->prepare("SELECT t.*, s.name AS svc_name, s.slug AS svc_slug FROM testimonials t LEFT JOIN services s ON t.service_id=s.id WHERE t.client_id=? AND t.is_active=1 AND COALESCE(t.source,'') <> 'demo' ORDER BY t.sort_order,t.id");
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

/**
 * 案例地區頁對映 — mini-site /cases/{region} 用
 * key = URL slug；prefix = case.location 開頭字串（DB 統一用「台」不用「臺」）
 * 加新地區：這裡加一筆 + .htaccess 的 (taichung|changhua) allowlist 同步加。
 */
function caseRegionMap(): array {
    return [
        'taichung'  => ['label' => '台中', 'prefix' => '台中'],
        'changhua'  => ['label' => '彰化', 'prefix' => '彰化'],
        'kaohsiung' => ['label' => '高雄', 'prefix' => '高雄'],
        'taitung'   => ['label' => '台東', 'prefix' => '台東'],
    ];
}

/** 從 case location 推斷地區 slug；無對映回 '' */
function caseRegionSlug(string $location): string {
    $location = trim($location);
    if ($location === '') return '';
    foreach (caseRegionMap() as $slug => $r) {
        if (mb_strpos($location, $r['prefix']) === 0) return $slug;
    }
    return '';
}

/**
 * 一組案例中實際出現的地區 slug（依 caseRegionMap 順序），給切換列 / sitemap 用
 * @param array $cases loadSiteData()['cases']
 * @return string[] 例 ['taichung','changhua']
 */
function caseRegionsPresent(array $cases): array {
    $present = [];
    foreach ($cases as $c) {
        $r = caseRegionSlug($c['location'] ?? '');
        if ($r && !in_array($r, $present, true)) $present[] = $r;
    }
    // 依 caseRegionMap 宣告順序排序
    return array_values(array_filter(array_keys(caseRegionMap()), fn($s) => in_array($s, $present, true)));
}
