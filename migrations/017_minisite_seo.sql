-- Migration 017 — mini-site 獨立 SEO 欄位
-- 原本 mini-site title / description 是自動組（brand_name｜tagline / strip_tags(about_text)）
-- 客戶想自訂時沒地方填。加 3 個欄位讓 admin 編輯。
-- 對應主站 store_meta_* 欄位（後者已存在、用於 /store/{slug}）

ALTER TABLE clients
  ADD COLUMN minisite_meta_title VARCHAR(120) NULL
    COMMENT 'Mini-site 首頁 <title> 自訂（空 = 自動組 brand_name｜tagline）' AFTER store_og_image,
  ADD COLUMN minisite_meta_desc  VARCHAR(300) NULL
    COMMENT 'Mini-site 首頁 meta description 自訂（空 = 自動用 about_text 150 字）' AFTER minisite_meta_title,
  ADD COLUMN minisite_og_image   VARCHAR(300) NULL
    COMMENT 'Mini-site 社群分享圖（空 = 用 hero_image_path）' AFTER minisite_meta_desc;
