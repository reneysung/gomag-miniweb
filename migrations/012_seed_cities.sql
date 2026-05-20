-- ============================================================
-- Migration 012 — 補齊 cities 表的 slug↔縣市名對映（收斂成唯一來源）
-- ------------------------------------------------------------
-- 背景：slug↔中文名原本寫死在 city.php / sitemap.php / store.php /
--       index.php 四處（各 12 筆）。改成由 cities 表單一來源後，
--       表內必須含全部 12 筆，否則切換會讓未 seed 的縣市 404。
--       006 已 seed 4 筆（台南/高雄/嘉義/台中，有 intro 內容），
--       這裡補進其餘 8 筆。
--
-- is_active=0：這 8 筆只作「路由對映」用，尚無城市介紹文案。
--       cities.is_active 的語意是「是否顯示城市介紹區塊」，設 0 可避免
--       冒出空白 intro 區塊；getCityMap() 讀全部 row（不濾 is_active），
--       所以路由仍涵蓋這 12 縣市。
--
-- slug 為 UNIQUE，INSERT IGNORE 可重複執行不出錯。
-- ============================================================

INSERT IGNORE INTO cities (slug, name, full_name, is_active, sort_order) VALUES
('taipei',    '台北', '台北市', 0, 5),
('newtaipei', '新北', '新北市', 0, 6),
('taoyuan',   '桃園', '桃園市', 0, 7),
('taitung',   '台東', '台東縣', 0, 8),
('pingtung',  '屏東', '屏東縣', 0, 9),
('hsinchu',   '新竹', '新竹市', 0, 10),
('yilan',     '宜蘭', '宜蘭縣', 0, 11),
('hualien',   '花蓮', '花蓮縣', 0, 12);
