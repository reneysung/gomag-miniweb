<?php
// ============================================================
// Migration 138 — 旭浪 台南主頁/嘉義變體 反競食（de-cannibalization）
// ------------------------------------------------------------
// 問題：/store/stonebeauty（台南主頁）的 landing_extra_content 其實是一份
//   完整 HTML（自帶 <title>/og/JSON-LD/<h1>），標題列大量主打「嘉義裝潢細清/
//   嘉義清潔公司」，跟專做嘉義的 /store/stonebeauty/chiayi 變體互搶同一組詞
//   （同站內部競食，canonical 各自獨立不會合併 → Google 訊號分散）。
//
// 做法（只收頭部詞、留據實陳述）：
//   A. 台南主頁 landing：把 H1 / 內嵌 title+og / meta desc / JSON-LD description /
//      各段落小標 裡「嘉義」當主打詞的地方收斂回純台南。
//      ★ 保留不動（這些是事實，非搶詞）：JSON-LD areaServed 嘉義縣/市、
//        服務範圍表格「台南市・嘉義縣市・高雄市」、內文「嘉義各鄉鎮也跑」、
//        真實部落客文章標題與外連錨文（不能竄改他人文章標題）。
//   B. 嘉義變體：hero 由 <h2> 升 <h1>（連同 .xlcy-hero CSS 選擇器），
//      讓嘉義頁有一個帶關鍵字的 H1（原本 H1 只有品牌名「旭浪清潔公司」）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/138_stonebeauty_tainan_chiayi_decannibalize.php
// 冪等：純 str_replace，找不到舊字串就跳過並 warn；重跑安全。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='stonebeauty'")->fetchColumn();
if (!$cid) { fwrite(STDERR, "找不到 stonebeauty\n"); exit(1); }

// 大欄位分塊讀（繞過 PDO 大欄位讀取問題）
function readBig(PDO $db, string $sql, array $args): string {
    $lenSql = preg_replace('/^SELECT\s+(\S+)/i', 'SELECT LENGTH($1)', $sql, 1);
    $len = (int)(function() use ($db, $lenSql, $args){ $s=$db->prepare($lenSql); $s->execute($args); return $s->fetchColumn(); })();
    $out = '';
    for ($i = 0; $i < $len; $i += 4000) {
        $s = $db->prepare(preg_replace('/^SELECT\s+(\S+)/i', 'SELECT SUBSTRING($1,?,4000)', $sql, 1));
        $s->execute(array_merge([$i + 1], $args));
        $out .= (string)$s->fetchColumn();
    }
    return $out;
}

function applyPairs(string $html, array $pairs): array {
    $log = [];
    foreach ($pairs as $p) {
        [$old, $new, $exp] = [$p[0], $p[1], $p[2] ?? 1];
        $cnt = 0;
        $html = str_replace($old, $new, $html, $cnt);
        $tag = $cnt === $exp ? '✅' : ($cnt === 0 ? '⏭ ' : '⚠️ ');
        $log[] = sprintf("  %s [%d/%d] %s", $tag, $cnt, $exp, mb_strimwidth($old, 0, 34, '…'));
    }
    return [$html, $log];
}

// ════════════════════════════════════════════
// A. 台南主頁 landing
// ════════════════════════════════════════════
echo "═══ A. 台南主頁 landing 反競食 ═══\n";
$land = readBig($db, "SELECT landing_extra_content FROM clients WHERE id=?", [$cid]);
$before = ['嘉義' => substr_count($land, '嘉義'), '台南' => substr_count($land, '台南'), 'len' => strlen($land)];

$pairsA = [
    // 內嵌 <title> + og:title（×2）
    ['台南清潔公司・嘉義清潔公司推薦｜旭浪清潔・台南裝潢細清專業團隊',
     '台南清潔公司・台南裝潢細清推薦｜旭浪清潔專業團隊', 2],
    // meta description + og:description（×2）
    ['台南清潔公司、嘉義清潔公司就找旭浪清潔！台南裝潢細清、嘉義裝潢細清專業團隊，台南細清推薦人手夠、速度快、價格漂亮。新居入住、建案細清、老宅翻新、火災善後都接，台南永康基地、台南嘉義跨區 24 小時服務。0977-340-903。',
     '台南清潔公司就找旭浪清潔！台南裝潢細清專業團隊，台南細清推薦人手夠、速度快、價格漂亮。新居入住、建案細清、老宅翻新、火災善後都接，台南永康基地、24 小時服務。0977-340-903。', 2],
    // JSON-LD description（保留 areaServed 嘉義，不動）
    ['台南清潔公司、嘉義清潔公司推薦，旭浪清潔台南嘉義在地團隊。台南裝潢細清、嘉義裝潢細清、新居入住、建案細清、老宅翻新、火災善後特殊清潔，人手夠、速度快、價格漂亮。',
     '台南清潔公司推薦，旭浪清潔台南永康在地團隊。台南裝潢細清、新居入住、建案細清、老宅翻新、火災善後特殊清潔，人手夠、速度快、價格漂亮。', 1],
    // hero badge
    ['台南永康 · 24 小時服務 · 台南嘉義跨區', '台南永康 · 24 小時服務 · 台南在地團隊', 1],
    // H1
    ['<h1>台南裝潢細清・嘉義裝潢細清｜旭浪清潔台南嘉義在地團隊</h1>',
     '<h1>台南裝潢細清・台南清潔公司推薦｜旭浪清潔台南永康在地團隊</h1>', 1],
    // story 段
    ['我們做的是台南裝潢細清、嘉義裝潢細清——也就是', '我們做的是台南裝潢細清——也就是', 1],
    // services 小標
    ['台南清潔公司 / 嘉義清潔公司一條龍承接', '台南清潔公司・台南裝潢細清一條龍承接', 1],
    // gallery 小標（只收地名；「用餐」殘留另列回報，不在此動）
    ['部落客實地用餐拍攝｜嘉義裝潢細清實戰', '部落客實地用餐拍攝｜台南裝潢細清實戰', 1],
    // gallery 首圖 alt
    ['alt="嘉義裝潢細清前 雜物未清狀態 旭浪清潔"', 'alt="台南裝潢細清前 雜物未清狀態 旭浪清潔"', 1],
    // reasons 小標
    ['台南嘉義清潔市場少見的整合條件', '台南清潔市場少見的整合條件', 1],
    // reason 04 h3（內文「嘉義縣市、高雄都跑」保留不動）
    ['<h3>24 小時接洽 + 台南嘉義跨區</h3>', '<h3>24 小時接洽 + 台南全區服務</h3>', 1],
    // service card 內文
    ['台南嘉義家庭、租屋與套房都接', '台南在地家庭、租屋與套房都接', 1],
    // info 小標（服務範圍表格 台南市・嘉義縣市・高雄市 保留不動）
    ['24 小時接洽，台南嘉義各區都跑', '24 小時接洽，台南各區都跑', 1],
    // contact CTA
    ['台南嘉義跨區、24 小時接洽', '台南在地服務、24 小時接洽', 1],
];

[$landNew, $logA] = applyPairs($land, $pairsA);
echo implode("\n", $logA) . "\n";

if ($landNew !== $land) {
    @mkdir(__DIR__ . '/../_backups', 0775, true);
    file_put_contents(__DIR__ . '/../_backups/stonebeauty_landing_pre138_' . date('Ymd-His') . '.html', $land);
    $u = $db->prepare("UPDATE clients SET landing_extra_content=? WHERE id=?");
    $u->execute([$landNew, $cid]);
    echo "  → 已寫回（備份已存 _backups/）\n";
} else {
    echo "  → 無變更（可能已套用過）\n";
}
$after = substr_count(readBig($db, "SELECT landing_extra_content FROM clients WHERE id=?", [$cid]), '嘉義');
echo "  嘉義 出現次數：{$before['嘉義']} → {$after}（保留 areaServed/服務範圍/內文據實/真實文章標題）\n\n";

// ════════════════════════════════════════════
// B. 嘉義變體 hero h2 → h1
// ════════════════════════════════════════════
echo "═══ B. 嘉義變體 hero 升 H1 ═══\n";
$cland = readBig($db, "SELECT landing_extra_content FROM client_city_pages WHERE client_id=? AND city_slug='chiayi'", [$cid]);
if ($cland === '') {
    echo "  ⏭ 無嘉義變體 landing，略過\n";
} else {
    $pairsB = [
        ['.xlcy-hero h2{', '.xlcy-hero h1{', 1],                       // CSS 選擇器跟著改
        ['<h2>嘉義裝潢細清・嘉義清潔公司，旭浪清潔台南團隊跨區承接</h2>',
         '<h1>嘉義裝潢細清・嘉義清潔公司，旭浪清潔台南團隊跨區承接</h1>', 1],
    ];
    [$clandNew, $logB] = applyPairs($cland, $pairsB);
    echo implode("\n", $logB) . "\n";
    if ($clandNew !== $cland) {
        $u = $db->prepare("UPDATE client_city_pages SET landing_extra_content=? WHERE client_id=? AND city_slug='chiayi'");
        $u->execute([$clandNew, $cid]);
        echo "  → 已寫回\n";
    } else {
        echo "  → 無變更（可能已套用過）\n";
    }
}

echo "\n驗證：\n  https://www.gomag.com.tw/store/stonebeauty\n  https://www.gomag.com.tw/store/stonebeauty/chiayi\n";
