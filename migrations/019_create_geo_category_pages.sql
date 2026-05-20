-- ============================================================
-- Migration 019 — geo_category_pages（城市×產業交叉頁內容）
-- ------------------------------------------------------------
-- 對應 docs/IA_MULTI_CITY.md 落地順序第 3 步。
-- 每個（city_slug × category_id）交叉點存可選的在地內文 + FAQ + meta，
-- 讓交叉頁「沒客戶也能靠內容排名」（sanfeng 模式）。
-- 內容為空時，交叉頁靠庫存（≥3 真實店）決定是否索引。
-- ============================================================

CREATE TABLE IF NOT EXISTS geo_category_pages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  city_slug   VARCHAR(50)  NOT NULL              COMMENT 'cities.slug（台中=taichung）',
  category_id INT UNSIGNED NOT NULL              COMMENT 'categories.id',

  intro_html  TEXT         DEFAULT NULL          COMMENT '在地內文（HTML）— 撐排名、避 doorway',
  faqs        JSON         DEFAULT NULL          COMMENT 'FAQ 陣列 [{q,a},...]',
  hero_image  VARCHAR(500) DEFAULT NULL          COMMENT 'Hero 背景圖 URL',

  meta_title  VARCHAR(255) DEFAULT NULL          COMMENT 'SEO title 覆寫',
  meta_desc   TEXT         DEFAULT NULL          COMMENT 'SEO description 覆寫',

  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_city_cat (city_slug, category_id),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
