<?php
/**
 * 172_cabinet_tags_quanjing_gaosen.php
 * 全境 #44、高森 #130 標為「系統櫃」（2026-07-23，Reney 指定）
 *
 * 現況：兩家實際做系統櫃，但只掛「室內設計」相關標籤，
 *       /city/tainan/home-service/xitonggui（台南系統櫃）找不到它們。
 *
 * 動作：
 *   A. 兩家補上 sk#153 xitonggui（系統櫃，居家服務）。
 *   B. 全境 #44 拿掉殘留的 sk#2（居家服務版室內設計）——
 *      2026-06 已將室內設計統一歸「專業服務」(sk#185，migration 142)，
 *      #2 為當時未清乾淨的殘留；全境仍保有 #185，前台顯示不受影響。
 *
 * 未動（Reney 已確認維持）：兩家主分類（全境=居家服務、高森=專業服務，
 *   它們確實也做室內設計）；重複關鍵字 sk#6 kitchen / sk#154 xitongchuju 另案處理。
 * 冪等。
 *
 * 跑法：HTTP_HOST=www.gomag.com.tw php migrations/172_cabinet_tags_quanjing_gaosen.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

const KW_CABINET = 153;   // xitonggui 系統櫃（居家服務）
const KW_OLD_ID  = 2;     // interior-design 室內設計（居家服務，已被 professional 版取代）

// 防呆：關鍵字必須存在且啟用
$kw = $db->prepare("SELECT slug, name, is_active FROM service_keywords WHERE id=?");
$kw->execute([KW_CABINET]);
$k = $kw->fetch(PDO::FETCH_ASSOC);
if (!$k || !$k['is_active']) { exit("❌ 關鍵字 #" . KW_CABINET . " 不存在或未啟用，中止\n"); }
echo "使用關鍵字 #" . KW_CABINET . " {$k['slug']}（{$k['name']}）\n\n";

// A. 補系統櫃標籤
$add = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id, service_keyword_id) VALUES (?, ?)");
foreach ([44 => '全境空間規劃', 130 => '高森系統空間室內設計'] as $cid => $name) {
    $add->execute([$cid, KW_CABINET]);
    echo ($add->rowCount() ? "✅ #{$cid} {$name} → 已加系統櫃標籤\n" : "↷ #{$cid} {$name} 已有系統櫃標籤\n");
}

// B. 全境清掉殘留的居家版室內設計
$del = $db->prepare("DELETE FROM client_service_keywords WHERE client_id=44 AND service_keyword_id=?");
$del->execute([KW_OLD_ID]);
echo ($del->rowCount() ? "✅ #44 全境 → 已移除殘留的 sk#2（居家版室內設計）\n" : "↷ #44 全境 無 sk#2，略過\n");

// ── 驗證 ──
echo "\n=== 兩家現有標籤 ===\n";
$chk = $db->prepare("SELECT sk.id, sk.slug, sk.name, c.name AS cat
                     FROM client_service_keywords csk
                     JOIN service_keywords sk ON sk.id = csk.service_keyword_id
                     JOIN categories c ON c.id = sk.category_id
                     WHERE csk.client_id = ? ORDER BY sk.id");
foreach ([44, 130] as $cid) {
    $nm = $db->query("SELECT brand_name FROM clients WHERE id=$cid")->fetchColumn();
    echo "  #{$cid} {$nm}\n";
    $chk->execute([$cid]);
    foreach ($chk as $t) printf("      #%-4d %-18s %-10s [%s]\n", $t['id'], $t['slug'], $t['name'], $t['cat']);
}

echo "\n=== 台南系統櫃子服務頁店家數 ===\n";
$q = $db->prepare("
    SELECT COUNT(DISTINCT cl.id) FROM clients cl
    JOIN client_service_keywords csk ON csk.client_id = cl.id
    JOIN service_keywords sk ON sk.id = csk.service_keyword_id
    WHERE cl.is_active = 1 AND (sk.slug = 'xitonggui' OR sk.page_slug = 'xitonggui')
      AND (cl.city_slug = 'tainan' OR EXISTS (
            SELECT 1 FROM client_city_pages p WHERE p.client_id = cl.id AND p.city_slug = 'tainan' AND p.is_active = 1))");
$q->execute();
echo "  /city/tainan/home-service/xitonggui → " . $q->fetchColumn() . " 家\n";

$geo = $db->query("SELECT COUNT(*) FROM geo_category_pages WHERE city_slug='tainan' AND service_slug='xitonggui' AND is_active=1")->fetchColumn();
echo "  該頁 geo 內容列：" . ($geo ? "有（頁面可正常顯示）" : "❗無 — 子服務頁需有內容列才會渲染，否則 404") . "\n";
