<?php
/**
 * includes/geo_page_clients.php ─ 主題頁「手動勾選店家」讀寫
 *
 * 見 migrations/176_geo_page_clients.php 的緣由說明。
 * 前台規則：有勾選就照勾的跑；沒勾選回傳空陣列，呼叫端自行 fallback 到原本的自動 JOIN。
 *
 * 所有函式都對「表還沒建」容錯（回傳空 / 0），避免 migration 還沒跑就整頁掛掉。
 */

/**
 * 後台用：讀出某主題頁已勾選的店（含未啟用的店，後台要看得到）。
 * 回傳 [client_id => ['sort_order'=>int, 'blurb'=>string, 'is_highlight'=>int]]
 */
function getGeoPagePicks(PDO $db, int $geoPageId): array {
    if ($geoPageId <= 0) return [];
    try {
        $st = $db->prepare("SELECT client_id, sort_order, blurb, is_highlight
                            FROM geo_page_clients WHERE geo_page_id = ? ORDER BY sort_order, id");
        $st->execute([$geoPageId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[(int)$r['client_id']] = [
                'sort_order'   => (int)$r['sort_order'],
                'blurb'        => (string)($r['blurb'] ?? ''),
                'is_highlight' => (int)$r['is_highlight'],
            ];
        }
        return $out;
    } catch (\Throwable $e) { return []; }
}

/**
 * 前台用：讀出某主題頁要顯示的店家（照後台排的順序），已 join clients + 跨縣變體。
 * 只回啟用中的店。沒勾選 → 回傳 []（呼叫端 fallback）。
 *
 * @param string $cols   要撈的 clients 欄位（沿用 citycat.php 的 $cols，前綴 cl.）
 * @param string $citySlug 用來 join client_city_pages 取變體地址/電話
 */
function getGeoPageCuratedClients(PDO $db, int $geoPageId, string $cols, string $citySlug): array {
    if ($geoPageId <= 0) return [];
    try {
        $sql = "
            SELECT $cols,
                   gpc.blurb, gpc.is_highlight, gpc.sort_order,
                   (ccp.client_id IS NOT NULL) AS via_variant,
                   ccp.address AS variant_address, ccp.phone AS variant_phone
            FROM geo_page_clients gpc
            JOIN clients cl ON cl.id = gpc.client_id AND cl.is_active = 1
            LEFT JOIN client_city_pages ccp
                 ON ccp.client_id = cl.id AND ccp.city_slug = ? AND ccp.is_active = 1
            WHERE gpc.geo_page_id = ?
            ORDER BY gpc.sort_order, gpc.id
        ";
        $st = $db->prepare($sql);
        $st->execute([$citySlug, $geoPageId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) { return []; }
}

/**
 * 後台用：候選店家清單 — 該城市地址的店 OR 在該城有啟用行銷頁變體的店。
 * 附 `suggested` 旗標：有標到本頁子服務關鍵字的店（含同義 page_slug 折入）＝系統建議。
 */
function getGeoPageCandidates(PDO $db, string $citySlug, int $categoryId, string $serviceSlug): array {
    try {
        $st = $db->prepare("
            SELECT cl.id, cl.brand_name, cl.slug, cl.category_id, cl.is_placeholder, cl.city_slug,
                   (ccp.client_id IS NOT NULL) AS via_variant,
                   EXISTS(
                     SELECT 1 FROM client_service_keywords csk
                     JOIN service_keywords sk ON sk.id = csk.service_keyword_id
                          AND sk.category_id = :cat AND sk.is_active = 1
                     WHERE csk.client_id = cl.id
                       AND COALESCE(NULLIF(sk.page_slug, ''), sk.slug) = :svc
                   ) AS suggested
            FROM clients cl
            LEFT JOIN client_city_pages ccp
                 ON ccp.client_id = cl.id AND ccp.city_slug = :city AND ccp.is_active = 1
            WHERE cl.is_active = 1
              AND (cl.city_slug = :city2 OR ccp.client_id IS NOT NULL)
            ORDER BY suggested DESC, cl.is_placeholder ASC, cl.brand_name
        ");
        $st->execute([
            'cat' => $categoryId, 'svc' => $serviceSlug,
            'city' => $citySlug, 'city2' => $citySlug,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) { return []; }
}

/**
 * 後台用：整批覆寫某主題頁的勾選。
 * $picks = [['client_id'=>int, 'blurb'=>string, 'is_highlight'=>0|1], ...]（陣列順序即顯示順序）
 * 回傳實際寫入筆數。整包在 transaction 內：要嘛全成功，要嘛維持原樣。
 */
function saveGeoPagePicks(PDO $db, int $geoPageId, array $picks): int {
    if ($geoPageId <= 0) return 0;

    // 去重（同一家只留第一次出現）＋ 過濾不存在／未啟用的 client
    $clean = [];
    foreach ($picks as $p) {
        $cid = (int)($p['client_id'] ?? 0);
        if ($cid <= 0 || isset($clean[$cid])) continue;
        $clean[$cid] = [
            'blurb'        => trim((string)($p['blurb'] ?? '')),
            'is_highlight' => !empty($p['is_highlight']) ? 1 : 0,
        ];
    }
    if ($clean) {
        $ph = implode(',', array_fill(0, count($clean), '?'));
        $chk = $db->prepare("SELECT id FROM clients WHERE id IN ($ph) AND is_active = 1");
        $chk->execute(array_keys($clean));
        $valid = array_map('intval', $chk->fetchAll(PDO::FETCH_COLUMN));
        $clean = array_intersect_key($clean, array_flip($valid));
    }

    $own = !$db->inTransaction();
    if ($own) $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM geo_page_clients WHERE geo_page_id = ?")->execute([$geoPageId]);
        if ($clean) {
            $ins = $db->prepare("INSERT INTO geo_page_clients
                (geo_page_id, client_id, sort_order, blurb, is_highlight) VALUES (?, ?, ?, ?, ?)");
            $i = 0;
            foreach ($clean as $cid => $v) {
                $ins->execute([$geoPageId, $cid, $i++, $v['blurb'] !== '' ? $v['blurb'] : null, $v['is_highlight']]);
            }
        }
        if ($own) $db->commit();
        return count($clean);
    } catch (\Throwable $e) {
        if ($own && $db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
