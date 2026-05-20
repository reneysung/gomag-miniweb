<?php
// ============================================================
// Migration 001 — 加分類表 + clients 行銷頁/外部官網/小官網開關
// ------------------------------------------------------------
// 用法：本機瀏覽器打開
//   http://localhost:8888/miniweb/migrations/001_add_categories_and_landing.php
// 跑完成功後，把整個 migrations 資料夾從正式主機刪除。
// 本機留著做歷史紀錄沒關係。
// ============================================================

require_once __DIR__ . '/../includes/config.php';

// 安全閥：正式環境必須加 ?confirm=YES 才會跑
if (!IS_LOCAL && ($_GET['confirm'] ?? '') !== 'YES') {
    http_response_code(403);
    die('正式環境請在網址後加 ?confirm=YES 才會執行');
}

header('Content-Type: text/plain; charset=utf-8');
echo "==================================================\n";
echo "Migration 001：加分類 + 行銷頁 + 小官網開關\n";
echo "==================================================\n\n";

$db = getDB();

// ---- 0. 已執行檢查（避免重複跑） -------------------------
$check = $db->query("SHOW COLUMNS FROM `clients` LIKE 'category_id'")->fetch();
if ($check) {
    echo "⚠️  已偵測到 clients.category_id 欄位，這個 migration 之前跑過了。\n";
    echo "如果你確定要再跑一次（測試用），請先手動下：\n";
    echo "  ALTER TABLE clients DROP FOREIGN KEY fk_clients_category;\n";
    echo "  ALTER TABLE clients DROP COLUMN category_id, DROP COLUMN external_website_url,\n";
    echo "    DROP COLUMN has_minisite, DROP COLUMN landing_extra_content,\n";
    echo "    DROP COLUMN business_hours, DROP COLUMN legacy_store_id;\n";
    echo "  DROP TABLE categories;\n";
    exit;
}

try {
    $db->beginTransaction();

    // ---- 1. 建立分類表 ---------------------------------
    echo "[1/4] 建立 categories 表...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
          `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `name`        VARCHAR(50)  NOT NULL UNIQUE COMMENT '分類名稱',
          `slug`        VARCHAR(50)  NOT NULL UNIQUE COMMENT 'URL slug',
          `icon`        VARCHAR(20)  DEFAULT NULL COMMENT 'emoji icon',
          `description` VARCHAR(200) DEFAULT NULL,
          `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
          `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
          `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY `idx_active_sort` (`is_active`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "    ✅ 完成\n\n";

    // ---- 2. 種子分類資料 -------------------------------
    echo "[2/4] 寫入預設 10 個分類...\n";
    $cats = [
        ['餐飲美食',  'food',         '🍽️', 10],
        ['居家服務',  'home-service', '🏠', 20],
        ['美容美髮',  'beauty',       '💅', 30],
        ['教育學習',  'education',    '📚', 40],
        ['健康醫療',  'health',       '⚕️', 50],
        ['運動休閒',  'sports',       '⚽', 60],
        ['零售購物',  'retail',       '🛍️', 70],
        ['專業服務',  'professional', '💼', 80],
        ['婚禮活動',  'wedding',      '💒', 90],
        ['其他',     'other',         '🏪', 999],
    ];
    $stmt = $db->prepare("
        INSERT IGNORE INTO `categories` (`name`, `slug`, `icon`, `sort_order`)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($cats as $c) {
        $stmt->execute($c);
        echo "    ✓ {$c[2]} {$c[0]}\n";
    }
    echo "\n";

    // ---- 3. clients 加新欄位 ---------------------------
    echo "[3/4] 在 clients 表新增 6 個欄位...\n";
    $db->exec("
        ALTER TABLE `clients`
          ADD COLUMN `legacy_store_id` VARCHAR(20) DEFAULT NULL
              COMMENT '舊 gomag 店家編號（搬遷對照用，如 062051129）'
              AFTER `subdomain`,
          ADD COLUMN `category_id` INT UNSIGNED DEFAULT NULL
              COMMENT '主站分類'
              AFTER `industry`,
          ADD COLUMN `business_hours` TEXT DEFAULT NULL
              COMMENT 'JSON 格式營業時間'
              AFTER `phone`,
          ADD COLUMN `external_website_url` VARCHAR(500) DEFAULT NULL
              COMMENT '客戶自有獨立官網 URL'
              AFTER `address`,
          ADD COLUMN `landing_extra_content` MEDIUMTEXT DEFAULT NULL
              COMMENT '主站行銷頁延伸內容（富文字 HTML）'
              AFTER `about_text`,
          ADD COLUMN `has_minisite` TINYINT(1) NOT NULL DEFAULT 0
              COMMENT '是否啟用子網域小官網（預設關閉）'
              AFTER `is_active`,
          ADD INDEX `idx_category` (`category_id`),
          ADD INDEX `idx_minisite` (`has_minisite`),
          ADD INDEX `idx_legacy` (`legacy_store_id`),
          ADD CONSTRAINT `fk_clients_category`
              FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
              ON DELETE SET NULL
    ");
    echo "    ✅ 完成（category_id / external_website_url / has_minisite /\n";
    echo "       landing_extra_content / business_hours / legacy_store_id）\n\n";

    // ---- 4. 既有資料補預設值 ---------------------------
    echo "[4/4] 既有資料補預設值...\n";
    // 旭浪 demo 已經有完整 mini-site 內容 → 啟用小官網保留測試案例
    $r = $db->prepare("UPDATE clients SET has_minisite=1, category_id=(SELECT id FROM categories WHERE slug='home-service') WHERE slug='xusen'");
    $r->execute();
    echo "    ✓ xusen（旭浪 demo）→ 啟用小官網 + 分類設為居家服務\n";

    // 佳鋐不鏽鋼示範客戶
    $r = $db->prepare("UPDATE clients SET has_minisite=1, category_id=(SELECT id FROM categories WHERE slug='professional') WHERE slug='ch'");
    $r->execute();
    if ($r->rowCount()) {
        echo "    ✓ ch（佳鋐 demo）→ 啟用小官網 + 分類設為專業服務\n";
    }
    echo "\n";

    $db->commit();
    echo "==================================================\n";
    echo "✅ Migration 001 全部完成\n";
    echo "==================================================\n\n";

    echo "驗證新結構：\n";
    $cols = $db->query("SHOW COLUMNS FROM clients")->fetchAll(PDO::FETCH_COLUMN);
    echo "clients 欄位：" . implode(', ', $cols) . "\n\n";

    $cnt = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    echo "categories 筆數：$cnt\n\n";

    echo "下一步：\n";
    echo "  1. 確認 phpMyAdmin 看 clients 表多了 6 欄、出現 categories 表\n";
    echo "  2. 跟 Claude 回報「Migration OK」\n";
    echo "  3. Claude 繼續寫後台 UI\n";

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo "❌ Migration 失敗：\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
