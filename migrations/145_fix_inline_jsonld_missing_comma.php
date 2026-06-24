<?php
// ============================================================
// Migration 145 — 修 landing_extra_content 內嵌 JSON-LD「缺少逗號」(GSC WNC-10030322)
// ------------------------------------------------------------
// GSC 2026-06-23 報「gomag.com.tw 無法剖析的結構化資料：缺少「,」或「}」」。
// 掃描發現 6 客戶的 landing inline JSON-LD 都同一 bug：屬性間漏逗號
// （值結尾 } / ] / " 後直接接 "屬性": 卻無逗號）。多為手寫/編輯時掉逗號，
// cosmetic(199) 是 mig 069 移除 aggregateRating 時留下。
// 修法：對每個 <script ld+json> 區塊，正則補「值結尾後接屬性名卻沒逗號」處，
//   補完 json_decode 重驗，**驗過才回寫**（驗不過則跳過、報出待人工）。
// 安全：只動 ld+json 區塊內、且只在 json 原本壞掉時補；補完仍壞就不存。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/145_fix_inline_jsonld_missing_comma.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// 補逗號：值結尾(} ] ")後接空白換行再接 "key": → 中間補逗號
function fixCommas(string $json): string {
    return preg_replace('/([}\]"])(\s*\n\s*)("(?:@?[A-Za-z][\w@]*)"\s*:)/u', '$1,$2$3', $json);
}
// 處理一段 landing：找所有 ld+json 區塊，壞的補逗號重驗，回傳 [新landing, 修了幾段, 仍壞幾段]
function processLanding(string $html): array {
    $fixed = 0; $stillBad = 0;
    $out = preg_replace_callback('#(<script[^>]*type=["\']application/ld\+json["\'][^>]*>)(.*?)(</script>)#is',
        function ($m) use (&$fixed, &$stillBad) {
            $body = $m[2];
            json_decode(trim($body));
            if (json_last_error() === JSON_ERROR_NONE) return $m[0]; // 本來就好，不動
            $cand = fixCommas($body);
            json_decode(trim($cand));
            if (json_last_error() === JSON_ERROR_NONE) { $fixed++; return $m[1] . $cand . $m[3]; }
            $stillBad++; return $m[0]; // 補不好，原樣留待人工
        }, $html);
    return [$out, $fixed, $stillBad];
}

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $bk = ['clients' => [], 'ccp' => []];
    $db->beginTransaction();

    // clients
    $rows = $db->query("SELECT id, slug, landing_extra_content c FROM clients WHERE landing_extra_content LIKE '%ld+json%' OR landing_extra_content LIKE '%\"@context%'")->fetchAll(PDO::FETCH_ASSOC);
    $upC = $db->prepare("UPDATE clients SET landing_extra_content=? WHERE id=?");
    foreach ($rows as $r) {
        [$new, $f, $s] = processLanding($r['c']);
        if ($f > 0) {
            $bk['clients'][$r['id']] = $r['c'];
            $upC->execute([$new, $r['id']]);
            echo "✅ clients {$r['id']} {$r['slug']}：補 $f 段" . ($s ? "，仍壞 $s 段⚠️" : "") . "\n";
        } elseif ($s > 0) {
            echo "⚠️ clients {$r['id']} {$r['slug']}：$s 段補不好，待人工\n";
        }
    }

    // client_city_pages
    $rows = $db->query("SELECT id, city_slug, client_id, landing_extra_content c FROM client_city_pages WHERE landing_extra_content LIKE '%ld+json%' OR landing_extra_content LIKE '%\"@context%'")->fetchAll(PDO::FETCH_ASSOC);
    $upV = $db->prepare("UPDATE client_city_pages SET landing_extra_content=? WHERE id=?");
    foreach ($rows as $r) {
        [$new, $f, $s] = processLanding($r['c']);
        if ($f > 0) {
            $bk['ccp'][$r['id']] = $r['c'];
            $upV->execute([$new, $r['id']]);
            echo "✅ ccp {$r['id']} client={$r['client_id']} {$r['city_slug']}：補 $f 段" . ($s ? "，仍壞 $s 段⚠️" : "") . "\n";
        } elseif ($s > 0) {
            echo "⚠️ ccp {$r['id']} {$r['city_slug']}：$s 段補不好，待人工\n";
        }
    }

    @mkdir(__DIR__ . '/../_backups', 0755, true);
    file_put_contents(__DIR__ . '/../_backups/inline_jsonld_fix_' . date('Ymd-His') . '.json', json_encode($bk, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $db->commit();
    echo "\n完成。備份 _backups/inline_jsonld_fix_*.json\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
