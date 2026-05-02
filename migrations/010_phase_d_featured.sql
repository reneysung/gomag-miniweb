-- Migration 010 — Phase D: 加 is_featured 欄位 (本週熱門用)
ALTER TABLE clients
  ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 COMMENT '本週熱門（人工標記，城市頁 hero 區用）'
  AFTER is_placeholder,
  ADD INDEX idx_featured (is_featured, is_active);

-- 4 demo 客戶設 hot
UPDATE clients SET is_featured = 1 WHERE id IN (1, 3, 10, 18);
