-- ============================================================
-- Migration 006 — cities 表（取代寫死在 city.php 的 PHP array）
-- ============================================================

CREATE TABLE IF NOT EXISTS cities (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(50)  UNIQUE NOT NULL  COMMENT 'URL slug (tainan, kaohsiung...)',
  name        VARCHAR(50)  NOT NULL          COMMENT '顯示用名稱（不含「市/縣」）',
  full_name   VARCHAR(50)  NOT NULL          COMMENT '完整名稱（台南市）',

  tagline     VARCHAR(255) DEFAULT NULL      COMMENT 'Hero 副標',
  intro       TEXT         DEFAULT NULL      COMMENT '城市介紹內文',
  highlights  JSON         DEFAULT NULL      COMMENT '亮點 tags 陣列',
  areas       JSON         DEFAULT NULL      COMMENT '主要服務區域陣列',
  hero_image  VARCHAR(500) DEFAULT NULL      COMMENT 'Hero 背景圖 URL（可外部）',

  meta_title  VARCHAR(255) DEFAULT NULL      COMMENT 'SEO title 覆寫',
  meta_desc   TEXT         DEFAULT NULL      COMMENT 'SEO description 覆寫',

  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order  INT          NOT NULL DEFAULT 0,

  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_slug (slug),
  INDEX idx_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed 4 個第一波城市（從 city.php $cityIntros 抽出）────
INSERT IGNORE INTO cities (slug, name, full_name, tagline, intro, highlights, areas, hero_image, is_active, sort_order) VALUES
('tainan', '台南', '台南市',
 '在地老店、隱藏巷弄、世代家業 — 台南口碑商家集合',
 '從中西區的老牌牛肉湯，到永康區的家庭式美甲，再到安平區的工廠直營汽車美容 — 台南店家最迷人的不是裝潢豪華，而是「做了一輩子」那份穩定。店家好口碑收錄了 150+ 家台南在地推薦，分類清楚、評價真實，讓你找店不踩雷。',
 JSON_ARRAY('在地老店比例高', '深夜營業選擇多', '價格親民', '家族經營口碑穩定'),
 JSON_ARRAY('中西區', '東區', '北區', '南區', '安平區', '安南區', '永康區', '仁德區', '歸仁區'),
 'https://images.unsplash.com/photo-1504609813442-a8924e83f76e?w=1800&q=80',
 1, 1
),
('kaohsiung', '高雄', '高雄市',
 '從楠梓到鳳山、左營到三民 — 高雄優質商家口碑指南',
 '高雄店家的特色是「敢做敢當」— 食材分量大、服務直率、價格透明。從鼓山的海鮮到三民的鍋物、左營的居家清潔到鳳山的車體美容，店家好口碑為高雄消費者整理了真實在地推薦清單。',
 JSON_ARRAY('用料實在', '服務熱情', '消費直率', '海港特色食材'),
 JSON_ARRAY('鼓山區', '三民區', '左營區', '鳳山區', '前金區', '苓雅區', '楠梓區', '小港區'),
 'https://images.unsplash.com/photo-1545569310-a1f5dabc8a36?w=1800&q=80',
 1, 2
),
('chiayi', '嘉義', '嘉義市',
 '雞肉飯不是只有那兩家 — 嘉義在地店家精選',
 '嘉義人挑店認真，老店一開三十年是常態。從東區的醫療診所，到西區的傳統餐廳、近郊的木地板師傅，店家好口碑為嘉義朋友整理了真正口碑相傳的推薦商家。',
 JSON_ARRAY('老店密度高', '在地師傅多', '物價合理', '步調慢但用心'),
 JSON_ARRAY('東區', '西區'),
 'https://images.unsplash.com/photo-1555992336-fb0d29498b13?w=1800&q=80',
 1, 3
),
('taichung', '台中', '台中市',
 '從南屯到東區、北屯到西屯 — 台中精選商家清單',
 '台中是台灣店家風格最多元的城市 — 文心路有平價吃到飽連鎖、復興路大魯閣有國際美食、南屯有設計風潮店、北屯有生活用品專賣。店家好口碑整理出台中各區口碑商家，從大眾消費到精緻服務都有。',
 JSON_ARRAY('風格多元', '連鎖品牌密度高', '商圈分布廣', '消費水準中段'),
 JSON_ARRAY('中區', '東區', '南區', '西區', '北區', '南屯區', '西屯區', '北屯區'),
 'https://images.unsplash.com/photo-1542358935821-e4e9f3f3c15d?w=1800&q=80',
 1, 4
);
