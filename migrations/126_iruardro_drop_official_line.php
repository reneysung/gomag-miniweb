<?php
// ============================================================
// Migration 126 — 冷淨億點（#251）移除預約 FAQ 的「官方 LINE」聯絡管道
// ------------------------------------------------------------
// Reney 告知：官方 LINE（LINE 官方帳號）已取消。FAQ32「怎麼預約」原列
// 「Facebook、IG、Threads、電話或官方 LINE」→ 移除官方 LINE。
// 注意：FAQ33 與服務文案的「當日上傳 LINE 相簿」是照片交付（共享相簿），
// 不受影響，保留。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/126_iruardro_drop_official_line.php
// 冪等：UPDATE 固定字串
// ============================================================
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cid = (int)$db->query("SELECT id FROM clients WHERE subdomain='iruardro' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ iruardro not found\n"; exit(1); }

$newAnswer = '透過 Facebook、IG、Threads 或電話與我們聯繫，拍室內機照片供技師估價，確認方案與時間後安排施工。當日 2–3 位技師準時到場，依標準流程施工，完工測試與你當面核對無誤後才付清款項。';

$st = $db->prepare("UPDATE service_faqs SET answer=? WHERE id=32 AND client_id=? AND question LIKE '%預約%'");
$st->execute([$newAnswer, $cid]);
echo "✓ FAQ32 影響列數 = " . $st->rowCount() . "\n";
echo "✅ Migration 126 完成（官方 LINE 已從預約管道移除）\n";
