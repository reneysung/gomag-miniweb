<?php
/**
 * 164_orphan_clients_city_homing.php
 * 孤兒店家城市歸位（2026-07-15 城市健檢第一波）
 *
 * 問題：3 家真客戶 city_slug 為空 → 不出現在任何城市頁／子服務頁：
 *   #222 聞香閣高雄外燴辦桌 — 地址欄寫「服務區域：大高雄…」，deriveCitySlug 推不出來。
 *        它已有「辦桌外燴」等標籤，補 city_slug=kaohsiung 後，
 *        /city/kaohsiung/food/banquet-catering 空殼頁（0 店）即自動有店承接。
 *   #40  張家牛肉麵（雲林土庫）、#75 歡樂牛排雲林店 — 雲林根本不在 cities 表。
 *        補 cities 列（yunlin）讓 /city/yunlin 能渲染，再把兩家歸位。
 *
 * 雲林文案走保守事實描述（不無中生有、不誇飾）；2 家 < 3 家門檻，
 * 依 sitemap 既有規則城市頁暫不進 sitemap（等店數或內容到位），頁面本身照常可訪。
 * 冪等。
 *
 * 跑法：HTTP_HOST=www.gomag.com.tw php migrations/164_orphan_clients_city_homing.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── 1. 聞香閣 #222 → 高雄 ──
$st = $db->prepare("UPDATE clients SET city_slug='kaohsiung' WHERE id=222 AND COALESCE(city_slug,'')=''");
$st->execute();
echo ($st->rowCount() ? "✅" : "↷ 已歸位，略過") . " #222 聞香閣 → kaohsiung\n";

// ── 2. cities 補雲林列（不存在才建）──
$exists = $db->query("SELECT id, is_active FROM cities WHERE slug='yunlin'")->fetch(PDO::FETCH_ASSOC);
if (!$exists) {
    $maxSort = (int)$db->query("SELECT COALESCE(MAX(sort_order),0) FROM cities")->fetchColumn();
    $ins = $db->prepare("INSERT INTO cities (slug, name, full_name, tagline, intro, meta_title, meta_desc, is_active, sort_order)
                         VALUES ('yunlin', '雲林', '雲林縣', ?, ?, ?, ?, 1, ?)");
    $ins->execute([
        '雲林在地美食與生活好店',
        '雲林是農業大縣，斗六、虎尾、土庫等鄉鎮都有經營多年的在地老店。我們陸續收錄雲林的口碑店家，方便你按分類查找、直接聯絡。',
        '雲林在地店家推薦｜店家好口碑',
        '收錄雲林斗六、虎尾、土庫等地的在地口碑店家：美食小吃、生活服務。真實店家資訊與聯絡方式。',
        $maxSort + 1,
    ]);
    echo "✅ cities 新增 yunlin（is_active=1, sort_order=" . ($maxSort + 1) . "）\n";
} else {
    echo "↷ cities 已有 yunlin（id={$exists['id']}），略過建列\n";
}

// ── 3. 雲林兩家歸位 ──
$st = $db->prepare("UPDATE clients SET city_slug='yunlin' WHERE id IN (40,75) AND COALESCE(city_slug,'')=''");
$st->execute();
echo "✅ 雲林兩家歸位（更新 {$st->rowCount()} 家）\n";

// ── 驗證 ──
echo "\n=== 驗證 ===\n";
foreach ($db->query("SELECT id, brand_name, city_slug FROM clients WHERE id IN (40,75,222)") as $r)
    echo "  #{$r['id']} {$r['brand_name']} → city_slug={$r['city_slug']}\n";

$q = $db->prepare("
    SELECT COUNT(DISTINCT cl.id) FROM clients cl
    JOIN client_service_keywords csk ON csk.client_id=cl.id
    JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE cl.is_active=1 AND (sk.slug='banquet-catering' OR sk.page_slug='banquet-catering')
      AND (cl.city_slug='kaohsiung' OR EXISTS (SELECT 1 FROM client_city_pages p WHERE p.client_id=cl.id AND p.city_slug='kaohsiung' AND p.is_active=1))
");
$q->execute();
echo "  高雄辦桌外燴子服務頁店家數：" . $q->fetchColumn() . "（修正前 0）\n";

$orphans = $db->query("SELECT COUNT(*) FROM clients WHERE is_active=1 AND COALESCE(city_slug,'')=''")->fetchColumn();
echo "  剩餘無城市歸屬店家：{$orphans} 家\n";
