-- ============================================================
-- Migration 008 — Phase C: Owner block + Photo gallery
-- ============================================================
-- 為 store.php 對齊 mockup 加最後 2 個元素：
--   - Owner block（圓形頭像 + 經營者介紹）
--   - Photo gallery（5 格 Airbnb 樣式）
-- ============================================================

ALTER TABLE clients
  ADD COLUMN owner_name    VARCHAR(80)  DEFAULT NULL COMMENT '經營者姓名（顯示在 owner block）'         AFTER about_tags,
  ADD COLUMN owner_intro   VARCHAR(255) DEFAULT NULL COMMENT '經營者一行簡介（如：日本學藝歸國 · 開業 8 年）' AFTER owner_name,
  ADD COLUMN owner_avatar  VARCHAR(300) DEFAULT NULL COMMENT '經營者頭像路徑（NULL 用文字 initial fallback）'  AFTER owner_intro,
  ADD COLUMN photos        JSON         DEFAULT NULL COMMENT 'Photo gallery 圖片陣列 ["path1","path2",...]'    AFTER owner_avatar;
