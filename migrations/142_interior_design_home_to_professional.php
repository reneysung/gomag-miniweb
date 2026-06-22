<?php
// ============================================================
// Migration 142 — 室內設計：從「居家服務」搬到「專業服務」（Reney 2026-06-22 指定）
// ------------------------------------------------------------
// 現況：室內設計同時掛兩分類 →
//   居家(cat2) kw2 slug=interior-design：有內容(台南/高雄/台中)、11 店、已索引（活）
//   專業(cat8) kw185 slug=shineisheji：無內容、7 店、404（死）
// Reney 決定室內設計屬「專業服務」→ 把活的那套搬到專業，舊網址 301。
// 兩邊都無同義詞(page_slug 折入)，0 店重疊，合併 18 店。
//
// 步驟：
//   1. 專業 kw185 slug shineisheji → interior-design（cat8 無此 slug，不撞 unique）
//   2. 3 篇 geo 內容(id 26/27/6) category_id 2→8（service_slug 維持 interior-design）
//   3. 11 家居家標的補標到 kw185（0 重疊，等於把兩邊合併到專業關鍵字 = 18 店）
//   4. 停用居家 kw2（從居家服務消失）
//   5. .htaccess 301 另外手動加（本檔只動 DB）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/142_interior_design_home_to_professional.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 備份受影響狀態
    $bk = [
        'kw2'   => $db->query("SELECT id,category_id,slug,name,is_active FROM service_keywords WHERE id=2")->fetch(PDO::FETCH_ASSOC),
        'kw185' => $db->query("SELECT id,category_id,slug,name,is_active FROM service_keywords WHERE id=185")->fetch(PDO::FETCH_ASSOC),
        'geo'   => $db->query("SELECT id,city_slug,category_id,service_slug FROM geo_category_pages WHERE id IN (26,27,6)")->fetchAll(PDO::FETCH_ASSOC),
        'kw2_clients' => $db->query("SELECT client_id FROM client_service_keywords WHERE service_keyword_id=2")->fetchAll(PDO::FETCH_COLUMN),
    ];
    $bkDir = __DIR__ . '/../_backups'; @mkdir($bkDir, 0755, true);
    file_put_contents($bkDir . '/interior_design_move_' . date('Ymd-His') . '.json', json_encode($bk, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $db->beginTransaction();

    // 1. 專業關鍵字改乾淨 slug
    $db->prepare("UPDATE service_keywords SET slug='interior-design' WHERE id=185 AND category_id=8")->execute();

    // 2. 3 篇內容頁搬到專業分類
    $db->prepare("UPDATE geo_category_pages SET category_id=8, updated_at=NOW() WHERE id IN (26,27,6)")->execute();

    // 3. 11 家居家標的補標到專業 kw185（排除已標的，避免重複）
    $ins = $db->prepare("
        INSERT INTO client_service_keywords (client_id, service_keyword_id)
        SELECT k.client_id, 185 FROM client_service_keywords k
        WHERE k.service_keyword_id=2
          AND k.client_id NOT IN (SELECT client_id FROM client_service_keywords WHERE service_keyword_id=185)
    ");
    $ins->execute();
    $added = $ins->rowCount();

    // 4. 停用居家 interior-design
    $db->prepare("UPDATE service_keywords SET is_active=0 WHERE id=2")->execute();

    $db->commit();

    // 驗證
    echo "✅ 完成\n";
    $k=$db->query("SELECT category_id,slug,name,is_active FROM service_keywords WHERE id=185")->fetch(PDO::FETCH_ASSOC);
    echo "  專業 kw185：cat={$k['category_id']} slug={$k['slug']} name={$k['name']} active={$k['is_active']}\n";
    echo "  居家 kw2 is_active：".$db->query("SELECT is_active FROM service_keywords WHERE id=2")->fetchColumn()." (應=0)\n";
    echo "  geo 內容已在 cat8：".$db->query("SELECT COUNT(*) FROM geo_category_pages WHERE category_id=8 AND service_slug='interior-design'")->fetchColumn()." 篇\n";
    echo "  補標 +{$added} 家\n";
    echo "  專業 室內設計 現在標的店家數：".$db->query("SELECT COUNT(DISTINCT cl.id) FROM clients cl JOIN client_service_keywords k ON k.client_id=cl.id WHERE k.service_keyword_id=185 AND cl.is_active=1")->fetchColumn()." 家（應=18）\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
