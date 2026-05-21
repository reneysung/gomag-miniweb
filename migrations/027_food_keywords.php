<?php
// ============================================================
// Migration 027 — 餐飲美食 服務關鍵字池 + 自動標 81 家（Phase A）
// ------------------------------------------------------------
// 沿用 023 建好的 service_keywords / client_service_keywords 兩表，
// seed 餐飲美食關鍵字池（14 獨立頁 + 同義詞），依店名自動標。
// 表結構不動；只 seed + tag。內容頁之後再開（Phase B）。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/027_food_keywords.php
// 冪等：INSERT IGNORE 關鍵字、INSERT IGNORE 標籤。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();
if (!$foodId) { echo "找不到 food\n"; exit(1); }

// page_slug='' → 獨立頁；填值 → 同義詞折進該頁
$pool = [
    // 獨立頁（不同料理意圖）
    ['hotpot',    '火鍋',     '',        10],
    ['yakiniku',  '燒肉燒烤', '',        20],
    ['steak',     '牛排',     '',        30],
    ['japanese',  '日式料理', '',        40],
    ['korean',    '韓式料理', '',        50],
    ['seafood',   '海鮮料理', '',        60],
    ['noodles',   '麵食',     '',        70],
    ['dessert',   '甜點烘焙', '',        80],
    ['cafe',      '咖啡飲料', '',        90],
    ['western',   '西餐餐酒館', '',     100],
    ['banquet',   '宴會餐廳', '',       110],
    ['vegetarian','素食',     '',       120],
    ['buffet',    '吃到飽',   '',       130],
    ['chinese',   '中式熱炒', '',       140],
    // 同義詞（折進主頁）
    ['shabu',          '涮涮鍋',   'hotpot',   11],
    ['sukiyaki',       '壽喜燒',   'hotpot',   12],
    ['mala-hotpot',    '麻辣鍋',   'hotpot',   13],
    ['lamb-pot',       '羊肉爐',   'hotpot',   14],
    ['ramen',          '拉麵',     'japanese', 41],
    ['donburi',        '丼飯',     'japanese', 42],
    ['izakaya',        '居酒屋',   'japanese', 43],
    ['sushi',          '壽司',     'japanese', 44],
    ['beef-noodle',    '牛肉麵',   'noodles',  71],
    ['bakery',         '烘焙',     'dessert',  81],
    ['banquet-catering','辦桌外燴','banquet',  111],
    ['sichuan',        '川菜',     'chinese',  141],
    ['stir-fry',       '熱炒',     'chinese',  142],
];
$insKw = $db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)");
$n = 0;
foreach ($pool as $p) { $insKw->execute([$foodId, $p[0], $p[1], $p[2], $p[3]]); $n += $insKw->rowCount(); }
echo "SEED：餐飲關鍵字池新增 {$n} 筆（共 " . count($pool) . " 個）\n";

$kwId = [];
foreach ($db->query("SELECT id, slug FROM service_keywords WHERE category_id={$foodId}") as $r) $kwId[$r['slug']] = (int)$r['id'];

// 自動標規則：店名含這些字 → 標該關鍵字（一店可多標）
$rules = [
    'hotpot'     => ['火鍋', '鍋物', '涮涮', '壽喜燒', '麻辣鍋', '鍋燒', '羊肉爐', '乾鍋', '小火鍋', '涮牛肉', '麻奶鍋', '聚門鍋'],
    'yakiniku'   => ['燒肉', '燒烤', '炭火', '烤肉', '烤魚', '串燒'],
    'steak'      => ['牛排'],
    'japanese'   => ['日式', '日本料理', '丼', '拉麵', '居酒屋', '壽司', '定食', '鰻魚', '清酒', '壽喜燒'],
    'korean'     => ['韓式', '韓湘', '韓國'],
    'seafood'    => ['海鮮', '海產', '鮮魚'],
    'noodles'    => ['牛肉麵', '麵食', '麵館', '燕餃'],
    'dessert'    => ['烘焙', '蛋糕', '甜品', '甜點', '點心', '乳酪'],
    'cafe'       => ['咖啡', 'café', 'CAFÉ', 'CAFE', '紅茶'],
    'western'    => ['西餐', '餐酒', '法小館', '英式', '酒場', 'Bar', 'BAR'],
    'banquet'    => ['宴會', '辦桌', '外燴', '喜粵', '總舖', '宴會式場'],
    'vegetarian' => ['素食', '蔬食', '蔬上', '仙桃'],
    'buffet'     => ['吃到飽'],
    'chinese'    => ['川菜', '熱炒', '台菜', '粥', '成都', '麻辣鴨血', '腸將', '中式', '燴飯'],
];

$stores = $db->query("SELECT cl.id, cl.brand_name FROM clients cl JOIN categories c ON cl.category_id=c.id WHERE c.slug='food' AND cl.is_active=1 AND COALESCE(cl.is_placeholder,0)=0")->fetchAll();
$insTag = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
$tagged = 0; $tagCount = 0; $untagged = [];
foreach ($stores as $s) {
    $name = (string)$s['brand_name'];
    $hit = [];
    foreach ($rules as $kwSlug => $needles) {
        foreach ($needles as $nd) {
            if (mb_stripos($name, $nd) !== false) { $hit[$kwSlug] = true; break; }
        }
    }
    if (!$hit) { $untagged[] = $s['id'] . ' ' . mb_substr($name, 0, 16); continue; }
    foreach (array_keys($hit) as $kwSlug) {
        if (!isset($kwId[$kwSlug])) continue;
        $insTag->execute([(int)$s['id'], $kwId[$kwSlug]]);
        $tagCount++;
    }
    $tagged++;
}
echo "AUTO-TAG：{$tagged}/" . count($stores) . " 家標到（共 {$tagCount} 標籤），" . count($untagged) . " 家未命中需人工：\n";
foreach ($untagged as $u) echo "    · {$u}\n";

echo "\n各關鍵字（含同義折入）店數：\n";
$q = $db->query("SELECT sk.slug, sk.name, sk.page_slug,
        (SELECT COUNT(DISTINCT csk.client_id) FROM client_service_keywords csk
         JOIN service_keywords s2 ON s2.id=csk.service_keyword_id
         WHERE s2.category_id={$foodId} AND (s2.slug = COALESCE(NULLIF(sk.page_slug,''), sk.slug) OR s2.page_slug = COALESCE(NULLIF(sk.page_slug,''), sk.slug))
        ) AS pgcnt
    FROM service_keywords sk WHERE sk.category_id={$foodId} AND sk.page_slug='' ORDER BY sk.sort_order");
foreach ($q as $r) printf("  %-12s %-10s 該頁店數=%d\n", $r['slug'], $r['name'], $r['pgcnt']);
