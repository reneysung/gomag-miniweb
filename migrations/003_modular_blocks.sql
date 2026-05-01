-- ============================================================
-- Migration 003 — Modular Blocks 架構
-- ============================================================
-- 目的：用「核心 clients 表 + 通用 store_blocks」取代每產業客製樣板
-- 5 種 block type 共用，未來新分類零 schema 變動
--
-- 策略：
--   - 沿用 clients 表（語意上 client = store）
--   - 新增 2 表：store_blocks + category_block_suggestions
--   - 舊 services / cases / service_faqs 表保留（雙寫期 fallback）
--   - testimonials 表獨立保留（評價不適合 block 化）
-- ============================================================

-- ─── 1. store_blocks ──────────────────────────────────────
-- 每筆 = 一個店家的一個區塊（type + JSON data）
CREATE TABLE IF NOT EXISTS store_blocks (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id   INT UNSIGNED NOT NULL                 COMMENT 'FK clients.id（沿用 client 詞彙）',
  type        ENUM('service','menu','portfolio','pricing','faq') NOT NULL,
  data        JSON NOT NULL                         COMMENT '區塊內容，每種 type 結構不同',
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,

  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_client_sort (client_id, sort_order, is_active),
  INDEX idx_type (type),
  CONSTRAINT fk_store_blocks_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 2. category_block_suggestions ────────────────────────
-- 驅動後台 UI：選某分類時，建議帶入哪幾個 block
CREATE TABLE IF NOT EXISTS category_block_suggestions (
  category_id   INT UNSIGNED NOT NULL,
  block_type    ENUM('service','menu','portfolio','pricing','faq') NOT NULL,
  is_required   TINYINT(1) NOT NULL DEFAULT 0,
  default_sort  INT NOT NULL DEFAULT 0,

  PRIMARY KEY (category_id, block_type),
  CONSTRAINT fk_cbs_category
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 3. seed category_block_suggestions ──────────────────
-- 12 分類 → 5 種 block 排列組合
-- 對應 categories 表的 slug

INSERT IGNORE INTO category_block_suggestions (category_id, block_type, is_required, default_sort)
SELECT c.id, t.block_type, t.is_required, t.default_sort
FROM categories c
JOIN (
  -- 餐飲美食：菜單為主
  SELECT 'food' AS slug, 'menu'      AS block_type, 1 AS is_required, 1 AS default_sort UNION ALL
  SELECT 'food',         'portfolio',                  0,                                2 UNION ALL
  SELECT 'food',         'faq',                        0,                                3 UNION ALL

  -- 居家服務：服務 + 報價 + 案例
  SELECT 'home-service', 'service',                    1,                                1 UNION ALL
  SELECT 'home-service', 'pricing',                    0,                                2 UNION ALL
  SELECT 'home-service', 'portfolio',                  0,                                3 UNION ALL
  SELECT 'home-service', 'faq',                        0,                                4 UNION ALL

  -- 美容美髮
  SELECT 'beauty',       'service',                    1,                                1 UNION ALL
  SELECT 'beauty',       'pricing',                    0,                                2 UNION ALL
  SELECT 'beauty',       'portfolio',                  0,                                3 UNION ALL

  -- 汽車服務
  SELECT 'auto-service', 'service',                    1,                                1 UNION ALL
  SELECT 'auto-service', 'portfolio',                  0,                                2 UNION ALL
  SELECT 'auto-service', 'pricing',                    0,                                3 UNION ALL
  SELECT 'auto-service', 'faq',                        0,                                4 UNION ALL

  -- 教育學習
  SELECT 'education',    'service',                    1,                                1 UNION ALL
  SELECT 'education',    'pricing',                    0,                                2 UNION ALL
  SELECT 'education',    'faq',                        0,                                3 UNION ALL

  -- 健康醫療
  SELECT 'health',       'service',                    1,                                1 UNION ALL
  SELECT 'health',       'faq',                        0,                                2 UNION ALL

  -- 婚禮活動
  SELECT 'wedding',      'service',                    0,                                1 UNION ALL
  SELECT 'wedding',      'portfolio',                  1,                                2 UNION ALL
  SELECT 'wedding',      'pricing',                    0,                                3 UNION ALL

  -- 旅宿住宿
  SELECT 'lodging',      'service',                    0,                                1 UNION ALL
  SELECT 'lodging',      'portfolio',                  1,                                2 UNION ALL
  SELECT 'lodging',      'pricing',                    0,                                3 UNION ALL
  SELECT 'lodging',      'faq',                        0,                                4 UNION ALL

  -- 專業服務
  SELECT 'professional', 'service',                    1,                                1 UNION ALL
  SELECT 'professional', 'pricing',                    0,                                2 UNION ALL
  SELECT 'professional', 'faq',                        0,                                3 UNION ALL

  -- 運動休閒
  SELECT 'sports',       'service',                    0,                                1 UNION ALL
  SELECT 'sports',       'pricing',                    0,                                2 UNION ALL
  SELECT 'sports',       'faq',                        0,                                3 UNION ALL

  -- 零售購物
  SELECT 'retail',       'portfolio',                  1,                                1 UNION ALL
  SELECT 'retail',       'pricing',                    0,                                2 UNION ALL

  -- 其他（萬用）
  SELECT 'other',        'service',                    0,                                1 UNION ALL
  SELECT 'other',        'faq',                        0,                                2
) t ON c.slug = t.slug;
