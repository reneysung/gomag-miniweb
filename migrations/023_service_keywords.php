<?php
// ============================================================
// Migration 023 — 服務關鍵字系統（店家 ⇄ 關鍵字 多對多）
// ------------------------------------------------------------
// service_keywords        : 關鍵字池（掛大分類底下）。page_slug 空=自己是頁；
//                           填=同義詞，前台折進該 slug 的頁（避 doorway）。
// client_service_keywords : 店家 ⇄ 關鍵字（多對多，店家預設標 ~3 組）。
// seed 居家服務關鍵字池 + 依店名自動標 48 家（人工再微調）。
//
// 跑法（本機）：php migrations/023_service_keywords.php
//   （box：HTTP_HOST=...hostingersite.com php migrations/023_service_keywords.php）
// 冪等：CREATE IF NOT EXISTS、INSERT IGNORE。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 1. 建表 ───────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS service_keywords (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL COMMENT 'categories.id（關鍵字所屬大分類）',
    slug VARCHAR(60) NOT NULL COMMENT '網址/識別用 a-z0-9-',
    name VARCHAR(60) NOT NULL COMMENT '顯示名（清潔）',
    page_slug VARCHAR(60) NOT NULL DEFAULT '' COMMENT '空=自己就是頁；填=同義詞折進此 slug 的頁',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cat_slug (category_id, slug),
    KEY idx_cat (category_id),
    KEY idx_page (category_id, page_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "TABLE：service_keywords OK\n";

$db->exec("CREATE TABLE IF NOT EXISTS client_service_keywords (
    client_id INT UNSIGNED NOT NULL,
    service_keyword_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_id, service_keyword_id),
    KEY idx_kw (service_keyword_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "TABLE：client_service_keywords OK\n";

// ── 2. seed 居家服務關鍵字池 ──────────────────────────────
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
if (!$homeId) { echo "找不到 home-service，停。\n"; exit(1); }

// page_slug='' → 自己是獨立頁（意圖不同）；page_slug 設值 → 同義詞折進該頁
$pool = [
    // slug,             name,        page_slug,  sort
    ['cleaning',         '清潔',       '',         10],
    ['interior-design',  '室內設計',   '',         20],
    ['flooring',         '木地板',     '',         30],
    ['painting',         '油漆防水',   '',         40],
    ['pest-control',     '除蟲消毒',   '',         50],
    ['kitchen',          '系統廚具',   '',         60],
    ['curtains',         '窗簾',       '',         70],
    ['aircon',           '冷氣空調',   '',         80],
    ['furniture',        '沙發床墊',   '',         90],
    ['security',         '監視器弱電', '',        100],
    // 清潔同義詞（只當標籤，折進 cleaning 頁，不開 URL）
    ['cleaning-company', '清潔公司',   'cleaning', 11],
    ['home-clean',       '居家清潔',   'cleaning', 12],
    ['reno-detail',      '裝潢細清',   'cleaning', 13],
];
$insKw = $db->prepare("INSERT IGNORE INTO service_keywords (category_id, slug, name, page_slug, sort_order) VALUES (?,?,?,?,?)");
$n = 0;
foreach ($pool as $p) { $insKw->execute([$homeId, $p[0], $p[1], $p[2], $p[3]]); $n += $insKw->rowCount(); }
echo "SEED：居家服務關鍵字池新增 {$n} 筆（共 " . count($pool) . " 個，重複略過）\n";

// slug → id 對照
$kwId = [];
foreach ($db->query("SELECT id, slug FROM service_keywords WHERE category_id={$homeId}") as $r) $kwId[$r['slug']] = (int)$r['id'];

// ── 3. 依店名自動標 48 家（substring 比對；一家可多標）──────
// 規則：關鍵字 slug => [店名要含的字...]
$rules = [
    'cleaning'        => ['清潔', '清洗', '打掃', '細清', '大掃除'],
    'interior-design' => ['室內設計', '室內裝修', '裝修', '裝潢', '統包', '空間', '設計工作室', '系統設計', '製作所', '空間規劃'],
    'flooring'        => ['木地板', '地板'],
    'painting'        => ['油漆', '防水', '抓漏', '批土'],
    'pest-control'    => ['除蟲', '消毒', '病媒', '防治'],
    'kitchen'         => ['系統廚具', '廚具', '櫥櫃', '系統櫃', 'Takara', 'standard'],
    'curtains'        => ['窗簾'],
    'aircon'          => ['冷氣', '空調', '變頻'],
    'furniture'       => ['沙發', '床墊', '傢俱', '傢具', '寢具'],
    'security'        => ['監視器', '弱電', '監控', '門禁'],
];
$stores = $db->query("SELECT cl.id, cl.brand_name FROM clients cl JOIN categories c ON cl.category_id=c.id WHERE c.slug='home-service' AND cl.is_active=1")->fetchAll();
$insTag = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
$tagged = 0; $tagCount = 0; $untagged = [];
foreach ($stores as $s) {
    $name = (string)$s['brand_name'];
    $hit = [];
    foreach ($rules as $kwSlug => $needles) {
        foreach ($needles as $nd) {
            if (mb_strpos($name, $nd) !== false) { $hit[$kwSlug] = true; break; }
        }
    }
    if (!$hit) { $untagged[] = $s['id'] . ' ' . mb_substr($name, 0, 14); continue; }
    foreach (array_keys($hit) as $kwSlug) {
        if (!isset($kwId[$kwSlug])) continue;
        $insTag->execute([(int)$s['id'], $kwId[$kwSlug]]);
        $tagCount++;
    }
    $tagged++;
}
echo "AUTO-TAG：{$tagged}/" . count($stores) . " 家標到（共 {$tagCount} 個標籤），" . count($untagged) . " 家未命中需人工：\n";
foreach ($untagged as $u) echo "    · {$u}\n";

// ── 4. 現況：每個關鍵字頁的店數 ───────────────────────────
echo "\n各關鍵字（含同義折入）對應店數：\n";
$q = $db->query("SELECT sk.slug, sk.name, sk.page_slug,
        (SELECT COUNT(DISTINCT csk.client_id) FROM client_service_keywords csk
         JOIN service_keywords s2 ON s2.id=csk.service_keyword_id
         WHERE s2.category_id={$homeId} AND (s2.slug = COALESCE(NULLIF(sk.page_slug,''), sk.slug) OR s2.page_slug = COALESCE(NULLIF(sk.page_slug,''), sk.slug))
        ) AS pgcnt
    FROM service_keywords sk WHERE sk.category_id={$homeId} ORDER BY sk.sort_order");
foreach ($q as $r) {
    $type = $r['page_slug'] === '' ? "頁" : "標籤→{$r['page_slug']}";
    printf("  %-16s %-10s [%s]  該頁店數=%d\n", $r['slug'], $r['name'], $type, $r['pgcnt']);
}
