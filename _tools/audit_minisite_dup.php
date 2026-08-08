<?php
// gomag 小官網↔行銷頁重複風險稽核
// 風險 = has_minisite=1 且 store 頁沒差異化 landing 且走標準模板 且仍會輸出 about/services
// 註：小官網子網域要 DNS 上線(公開可爬)才會真的變成重複來源；未上線=潛在風險
require __DIR__ . '/includes/config.php';
$db = getDB();
$rows = $db->query("
  SELECT id, slug, brand_name,
    CHAR_LENGTH(COALESCE(landing_extra_content, '')) AS ll,
    COALESCE(store_template, '') AS tpl,
    COALESCE(subdomain, slug) AS sub,
    CHAR_LENGTH(COALESCE(about_text, '')) AS al,
    (SELECT COUNT(*) FROM services s WHERE s.client_id = clients.id AND s.is_active = 1) AS svc,
    (SELECT COUNT(*) FROM store_blocks b WHERE b.client_id = clients.id) AS blk
  FROM clients WHERE has_minisite = 1 ORDER BY ll
")->fetchAll(PDO::FETCH_ASSOC);

$risk = [];
printf("%-3s %-5s %-14s %-8s %-14s %-6s %-4s %-7s %s\n",
       '', 'id', 'slug', 'landing', 'template', 'about', 'svc', 'blocks', '小官網子網域');
echo str_repeat('─', 92) . "\n";
foreach ($rows as $r) {
    $custom = ($r['tpl'] !== '' && $r['tpl'] !== '_default');
    $rendersShared = (!$custom && ($r['al'] > 0 || $r['svc'] > 0));
    $bad = ($r['ll'] == 0 && $rendersShared);   // 空 landing + 會輸出共用內容 = 重複風險
    // 探子網域是否公開上線（Google 爬得到才是真重複來源）
    $ch = curl_init("https://{$r['sub']}.gomag.com.tw/");
    curl_setopt_array($ch, [CURLOPT_NOBODY=>true, CURLOPT_TIMEOUT=>6, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $live = ($code >= 200 && $code < 400);
    if ($bad) $risk[] = ['slug'=>$r['slug'], 'live'=>$live, 'code'=>$code];
    printf("%-3s %-5s %-14s %-8s %-14s %-6s %-4s %-7s %s\n",
        $bad ? '⚠️' : '  ', $r['id'], mb_strimwidth($r['slug'],0,14), $r['ll'],
        $r['tpl'] ?: '(default)', $r['al'], $r['svc'], $r['blk'],
        ($live ? "🟢 上線($code)" : "⚪ 未上線($code)"));
}
echo "\n";
if (!$risk) { echo "✅ 無重複風險客戶\n"; }
else {
    echo "⚠️ 重複風險客戶 " . count($risk) . " 家：\n";
    foreach ($risk as $x) {
        echo "   - {$x['slug']}：" . ($x['live']
            ? "小官網已上線 → 現在就在重複，該補 landing_extra_content"
            : "小官網尚未上線(HTTP {$x['code']}) → 潛在，等子網域上線前補 landing 即可") . "\n";
    }
}
