-- ============================================================
-- Migration 020 — guides（攻略文內容層）
-- ------------------------------------------------------------
-- 對應 docs/IA_MULTI_CITY.md 落地順序第 5 步。
-- 攻略文是橫向內容層：補實質內文 + 互鏈（掛回 city_slug / category_id 與交叉頁互連）。
-- 與 per-client 的 articles（mini-site 專欄）不同 —— guides 是主站 topic-cluster 內容。
-- ============================================================

CREATE TABLE IF NOT EXISTS guides (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(120) UNIQUE NOT NULL,
  title        VARCHAR(255) NOT NULL,
  excerpt      VARCHAR(500) DEFAULT NULL          COMMENT '摘要（列表卡片 + meta fallback）',
  body_html    MEDIUMTEXT   DEFAULT NULL          COMMENT '正文 HTML',
  cover_image  VARCHAR(500) DEFAULT NULL,

  city_slug    VARCHAR(50)  DEFAULT NULL          COMMENT '掛回 cities.slug（互鏈交叉頁）',
  category_id  INT UNSIGNED DEFAULT NULL          COMMENT '掛回 categories.id',

  meta_title   VARCHAR(255) DEFAULT NULL,
  meta_desc    TEXT         DEFAULT NULL,
  status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at TIMESTAMP    NULL DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_status (status, published_at),
  INDEX idx_city (city_slug),
  INDEX idx_cat (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
