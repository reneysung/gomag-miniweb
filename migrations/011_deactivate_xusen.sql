-- Migration 011 — 撤下 xusen demo（與 062051129 同店重複）
-- xusen = 旭浪清潔，新系統 demo；062051129 = 旭浪舊系統獨立 docroot 官網
-- 兩者同店，xusen 全面下架，主站 /store/xusen 與 xusen 子網域皆 301 → 062051129
-- 配套程式：
--   - includes/front_functions.php  getDuplicateSkipSlugs() 加 'xusen'
--   - store.php                      external 301 → 062051129.gomag.com.tw
--   - .htaccess                      xusen 子網域 301 → 062051129 子網域

UPDATE clients
  SET is_active = 0,
      has_minisite = 0,
      is_featured = 0
WHERE slug = 'xusen';
