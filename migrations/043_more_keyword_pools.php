<?php
// ============================================================
// Migration 043 — 5 類服務關鍵字池 seed
// ------------------------------------------------------------
// 補齊 美容美髮 / 專業服務 / 教育學習 / 旅宿住宿 / 零售購物 的
// service_keywords 關鍵字池，讓店家編輯頁可直接勾選（不必先去填池）。
// 只新增「可勾選選項」+ 讓店家可被標籤；不會自動產生對外 SEO 頁
// （子服務頁要另有 geo 內容才會出現，否則 404）。也不自動標店家。
//
// 跑法：HTTP_HOST=<host> php migrations/043_more_keyword_pools.php
// 冪等：INSERT IGNORE（slug 對 category_id 唯一），可重複跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// page_slug='' → 獨立頁；填值 → 同義詞折進該主頁 slug
$pools = [
    'beauty' => [
        ['hair-salon',        '美髮沙龍', '',             10],
        ['nail',              '美甲',     '',             20],
        ['eyelash',           '美睫',     '',             30],
        ['makeup-tattoo',     '紋繡霧眉', '',             40],
        ['facial',            '臉部護膚', '',             50],
        ['spa',               '美容SPA',  '',             60],
        ['waxing',            '除毛',     '',             70],
        ['bridal-makeup',     '新娘秘書', '',             80],
        ['haircut',           '剪髮',     'hair-salon',   11],
        ['hair-color',        '染髮',     'hair-salon',   12],
        ['perm',              '燙髮',     'hair-salon',   13],
        ['scalp-care',        '頭皮護理', 'hair-salon',   14],
        ['gel-nail',          '光療指甲', 'nail',         21],
        ['eyebrow-embroidery','飄眉',     'makeup-tattoo',41],
    ],
    'professional' => [
        ['accounting',  '會計記帳', '',           10],
        ['legal',       '法律諮詢', '',           20],
        ['land-agent',  '代書地政', '',           30],
        ['insurance',   '保險規劃', '',           40],
        ['ad-design',   '廣告設計', '',           50],
        ['photography', '商業攝影', '',           60],
        ['printing',    '印刷',     '',           70],
        ['detective',   '徵信',     '',           80],
        ['bookkeeping', '記帳士',   'accounting', 11],
        ['lawyer',      '律師',     'legal',      21],
        ['notary',      '公證',     'legal',      22],
        ['marketing',   '行銷',     'ad-design',  51],
    ],
    'education' => [
        ['cram-school', '升學補習班', '',           10],
        ['english',     '美語英語',   '',           20],
        ['talent-class','才藝班',     '',           30],
        ['music',       '音樂教室',   '',           40],
        ['daycare',     '安親課輔',   '',           50],
        ['art-class',   '美術畫室',   '',           60],
        ['tutoring',    '家教',       'cram-school',11],
        ['math-cram',   '數理補習',   'cram-school',12],
        ['piano',       '鋼琴',       'music',      41],
        ['afterschool', '課後輔導',   'daycare',    51],
    ],
    'lodging' => [
        ['bnb',             '民宿',     '',       10],
        ['hotel',           '飯店旅館', '',       20],
        ['villa',           '包棟Villa','',       30],
        ['camping',         '露營區',   '',       40],
        ['homestay-family', '親子民宿', '',       50],
        ['guesthouse',      '旅館',     'hotel',  21],
        ['whole-house',     '包棟',     'villa',  31],
        ['glamping',        '豪華露營', 'camping',41],
    ],
    'retail' => [
        ['apparel',           '服飾',     '',                10],
        ['bedding-furniture', '寢具家具', '',                20],
        ['hardware',          '五金百貨', '',                30],
        ['gift',              '禮品伴手禮','',               40],
        ['pet-supplies',      '寵物用品', '',                50],
        ['baby-supplies',     '嬰幼用品', '',                60],
        ['clothing',          '服飾店',   'apparel',         11],
        ['furniture',         '家具',     'bedding-furniture',21],
        ['souvenir',          '伴手禮',   'gift',            41],
    ],
];

$insKw = $db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)");

foreach ($pools as $catSlug => $pool) {
    $catId = (int)$db->query("SELECT id FROM categories WHERE slug=" . $db->quote($catSlug) . " LIMIT 1")->fetchColumn();
    if (!$catId) { echo "⚠️  找不到分類 {$catSlug}，略過\n"; continue; }
    $added = 0;
    foreach ($pool as $p) {
        $insKw->execute([$catId, $p[0], $p[1], $p[2], $p[3]]);
        $added += $insKw->rowCount();
    }
    echo "✅ {$catSlug}（id={$catId}）：新增 {$added} 筆（池共 " . count($pool) . " 個）\n";
}

echo "\n=== 各分類關鍵字池現況 ===\n";
foreach ($db->query("
    SELECT c.name, c.slug, COUNT(sk.id) kw
    FROM categories c LEFT JOIN service_keywords sk ON sk.category_id=c.id AND sk.is_active=1
    WHERE c.is_active=1 GROUP BY c.id ORDER BY c.sort_order
")->fetchAll() as $r) {
    printf("  %-12s %-14s %d\n", $r['name'], $r['slug'], $r['kw']);
}
echo "\n完成。\n";
