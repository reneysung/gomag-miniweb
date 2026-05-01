-- ============================================================
-- Migration 002 — SEO 強化 + 平台設定表
-- ============================================================

-- 1. clients 表加行銷頁 SEO 欄位
ALTER TABLE clients
  ADD COLUMN store_meta_title VARCHAR(200) DEFAULT NULL COMMENT '行銷頁 meta title' AFTER landing_extra_content,
  ADD COLUMN store_meta_desc VARCHAR(500) DEFAULT NULL COMMENT '行銷頁 meta description' AFTER store_meta_title,
  ADD COLUMN store_og_image VARCHAR(300) DEFAULT NULL COMMENT '行銷頁 OG image' AFTER store_meta_desc,
  ADD COLUMN store_keywords VARCHAR(500) DEFAULT NULL COMMENT '行銷頁關鍵字（用逗號分隔）' AFTER store_og_image;

-- 2. 平台設定表（key-value）
CREATE TABLE IF NOT EXISTS platform_settings (
  setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
  setting_value TEXT,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 預設平台設定
INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES
  ('platform_name',      '店家好口碑'),
  ('platform_tagline',   '台南店家平台'),
  ('main_meta_title',    '店家好口碑｜台南店家平台 — 200+ 在地優質商家'),
  ('main_meta_desc',     '匯集台南優質店家：餐飲美食、居家服務、汽車服務、美容美髮、專業服務 — 一站式找對店家。'),
  ('main_og_image',      ''),
  ('ga_tracking_id',     ''),
  ('gtm_id',             ''),
  ('search_console_verification', ''),
  ('contact_email',      'contact@gomag.com.tw'),
  ('contact_phone',      ''),
  ('footer_text',        '');
