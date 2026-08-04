<?php
/**
 * 182_show_google_reviews_toggle.php
 * 店家頁「顯示 Google 評價」開關（2026-08-04，Reney 拍板 A 案）
 *
 * 背景：google_reviews.php 的 getGoogleReviews() 早就寫好（含 24h 快取），
 *   API key 也設了、204 家填了 place_id，但 store.php 從沒接上顯示端 →
 *   後台「填 place_id 會顯示 Google 評價」是過時的謊。
 *
 * 本次接上顯示端。但 204 家已填 place_id，若「填了就顯示」會一次全爆
 *   （含未檢查的負評）→ Reney 要求「我可以選擇」。故加獨立開關，預設 0（關）：
 *   place_id 有值 + show_google_reviews=1 才顯示。逐店檢查過評價 OK 再開。
 *
 * 跑法：HTTP_HOST=www.gomag.com.tw php migrations/182_show_google_reviews_toggle.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$has = $db->query("SHOW COLUMNS FROM clients LIKE 'show_google_reviews'")->fetch();
if ($has) {
    echo "show_google_reviews 已存在，略過。\n";
} else {
    $db->exec("ALTER TABLE clients
        ADD COLUMN show_google_reviews TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '店家頁是否顯示 Google 真實評價（需同時有 google_place_id）' AFTER google_place_id");
    echo "✅ 已新增 clients.show_google_reviews（預設 0）\n";
}

$on = (int)$db->query("SELECT COUNT(*) FROM clients WHERE show_google_reviews=1")->fetchColumn();
$pid = (int)$db->query("SELECT COUNT(*) FROM clients WHERE COALESCE(google_place_id,'')<>''")->fetchColumn();
echo "目前：已填 place_id {$pid} 家、已開顯示 {$on} 家。\n";
echo "（預設全關，逐店檢查評價 OK 再開）\n";
