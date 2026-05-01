-- ============================================================
-- migrate_v2.sql  ─  現有資料庫升級腳本
-- 執行對象：已匯入過 schema.sql 的資料庫
-- 執行方式：phpMyAdmin → 選擇 miniweb → SQL → 貼上執行
-- ============================================================

-- 1. clients 加入 subdomain 欄位（若已存在則跳過）
ALTER TABLE `clients`
  ADD COLUMN IF NOT EXISTS `subdomain` VARCHAR(100) DEFAULT NULL
    COMMENT '子網域名稱（如：xusen）→ xusen.gomag.com.tw'
    AFTER `id`;

-- 加唯一索引（若不存在）
SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE table_schema = DATABASE()
  AND table_name = 'clients'
  AND index_name = 'subdomain'
);

SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE `clients` ADD UNIQUE INDEX `subdomain` (`subdomain`)',
  'SELECT "index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 把現有客戶的 slug 複製到 subdomain（若 subdomain 尚空）
UPDATE `clients` SET `subdomain` = `slug` WHERE `subdomain` IS NULL OR `subdomain` = '';

-- 3. 把旭浪清潔的 subdomain 更新為 xusen，slug 也改成 xusen
UPDATE `clients` SET
  `subdomain` = 'xusen',
  `slug`      = 'xusen'
WHERE `slug` IN ('xulang', 'xusen') LIMIT 1;

-- 4. 確認結果
SELECT id, subdomain, slug, brand_name, is_active FROM `clients`;
