<?php
/**
 * 176_geo_page_clients.php
 * 主題頁「手動勾選店家 + 每家推薦理由」（2026-07-24，Reney 拍板）
 *
 * 為什麼：
 *   原本主題頁（citycat.php 子服務頁）列店是自動 JOIN 關鍵字標籤，
 *   排序寫死 `is_placeholder ASC, cl.id DESC` → 排第一名的是「最晚建立的那家」。
 *   而且標到的全部都會列出來（不能只留 5 家），每家底下也沒有推薦理由。
 *   → 清單頁只有 655 字，打不過競品 4,500 字。
 *
 * 這張表讓後台可以：勾選要放哪幾家、自己排順序、每家寫一段推薦理由。
 *
 * blurb 刻意存在「這一列」而不是讀 clients.description：
 *   同一家店會出現在多個主題（洪廚同時在辦桌／尾牙外燴／喜宴外燴），
 *   共用簡介會讓三頁長一樣 → 又變樣板。
 *
 * 前台行為：有勾選 → 照勾的跑；沒勾選 → 維持原本的自動 JOIN（現有 33 頁不會壞）。
 *
 * 跑法：
 *   HTTP_HOST=www.gomag.com.tw php migrations/176_geo_page_clients.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$exists = $db->query("SHOW TABLES LIKE 'geo_page_clients'")->fetchColumn();
if ($exists) {
    echo "geo_page_clients 已存在，略過建表。\n";
} else {
    $db->exec("
        CREATE TABLE geo_page_clients (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            geo_page_id   INT UNSIGNED NOT NULL,
            client_id     INT UNSIGNED NOT NULL,
            sort_order    INT NOT NULL DEFAULT 0,
            blurb         TEXT NULL COMMENT '這家店在這個主題的推薦理由（每個主題各寫一份）',
            is_highlight  TINYINT(1) NOT NULL DEFAULT 0 COMMENT '標「編輯精選」',
            created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_page_client (geo_page_id, client_id),
            KEY idx_page_sort (geo_page_id, sort_order),
            CONSTRAINT fk_gpc_page   FOREIGN KEY (geo_page_id) REFERENCES geo_category_pages(id) ON DELETE CASCADE,
            CONSTRAINT fk_gpc_client FOREIGN KEY (client_id)   REFERENCES clients(id)            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='主題頁手動勾選的店家＋每家推薦理由'
    ");
    echo "✅ 已建立 geo_page_clients\n";
}

$n = (int)$db->query("SELECT COUNT(*) FROM geo_page_clients")->fetchColumn();
echo "目前勾選筆數：{$n}\n";
echo "（0 筆＝所有主題頁維持原本的自動列店，前台行為不變）\n";
