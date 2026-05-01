-- ============================================================
-- 客戶小官網模板系統 - MySQL Schema
-- charset: utf8mb4  engine: InnoDB
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+08:00';

-- ------------------------------------------------------------
-- 0. 分類表（主站列表用）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(50)  NOT NULL UNIQUE COMMENT '分類名稱',
  `slug`        VARCHAR(50)  NOT NULL UNIQUE COMMENT 'URL slug',
  `icon`        VARCHAR(20)  DEFAULT NULL COMMENT 'emoji icon',
  `description` VARCHAR(200) DEFAULT NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 1. 客戶主表（多客戶支援）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subdomain`     VARCHAR(100) DEFAULT NULL UNIQUE COMMENT '子網域名稱（如：xusen）→ xusen.gomag.com.tw',
  `legacy_store_id` VARCHAR(20) DEFAULT NULL COMMENT '舊 gomag 店家編號（搬遷對照用，如 062051129）',
  `slug`          VARCHAR(80)  NOT NULL UNIQUE COMMENT '網址識別碼（舊版相容，同 subdomain）',
  `brand_name`    VARCHAR(100) NOT NULL COMMENT '品牌名稱',
  `tagline`       VARCHAR(200) DEFAULT NULL COMMENT '品牌標語',
  `industry`      VARCHAR(100) DEFAULT NULL COMMENT '業種（影響前台動態文案，如餐飲/服務業）',
  `category_id`   INT UNSIGNED DEFAULT NULL COMMENT '主站分類',
  `phone`         VARCHAR(30)  DEFAULT NULL,
  `business_hours` TEXT         DEFAULT NULL COMMENT 'JSON 格式營業時間',
  `email`         VARCHAR(100) DEFAULT NULL,
  `address`       VARCHAR(200) DEFAULT NULL,
  `external_website_url` VARCHAR(500) DEFAULT NULL COMMENT '客戶自有獨立官網 URL',
  `google_maps_embed` TEXT     DEFAULT NULL COMMENT 'Google Maps iframe src',
  `logo_path`     VARCHAR(300) DEFAULT NULL COMMENT 'Logo 圖片路徑',
  `hero_image_path` VARCHAR(300) DEFAULT NULL COMMENT 'Hero Banner 圖片路徑',
  `about_text`    TEXT         DEFAULT NULL COMMENT '關於我們內文',
  `landing_extra_content` MEDIUMTEXT DEFAULT NULL COMMENT '主站行銷頁延伸內容（富文字 HTML）',
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '是否啟用',
  `has_minisite`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '是否啟用子網域小官網（預設關閉）',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_category` (`category_id`),
  KEY `idx_minisite` (`has_minisite`),
  KEY `idx_legacy` (`legacy_store_id`),
  CONSTRAINT `fk_clients_category` FOREIGN KEY (`category_id`)
      REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. 主題顏色
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client_themes` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`      INT UNSIGNED NOT NULL,
  `theme_name`     VARCHAR(50)  NOT NULL DEFAULT '預設主題',
  `color_primary`  VARCHAR(10)  NOT NULL DEFAULT '#1c3d3d' COMMENT '主色',
  `color_accent`   VARCHAR(10)  NOT NULL DEFAULT '#c8922a' COMMENT '強調色',
  `color_bg`       VARCHAR(10)  NOT NULL DEFAULT '#ffffff' COMMENT '背景色',
  `color_text`     VARCHAR(10)  NOT NULL DEFAULT '#222222' COMMENT '主文字色',
  `color_light`    VARCHAR(10)  NOT NULL DEFAULT '#f5f5f0' COMMENT '淺背景色',
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6 套預設色票種子資料（先建表，INSERT 在 seed 段）

-- ------------------------------------------------------------
-- 3. 社群連結
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client_social` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`    INT UNSIGNED NOT NULL UNIQUE,
  `line_url`     VARCHAR(300) DEFAULT NULL COMMENT 'LINE 官方帳號網址',
  `line_id`      VARCHAR(100) DEFAULT NULL COMMENT 'LINE ID / @名稱',
  `fb_page_url`  VARCHAR(300) DEFAULT NULL COMMENT 'FB 粉絲頁網址',
  `instagram_url` VARCHAR(300) DEFAULT NULL,
  `youtube_url`  VARCHAR(300) DEFAULT NULL,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. 服務項目
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`     INT UNSIGNED NOT NULL,
  `name`          VARCHAR(100) NOT NULL COMMENT '服務名稱',
  `slug`          VARCHAR(100) DEFAULT NULL COMMENT 'URL 識別碼',
  `short_desc`    VARCHAR(300) DEFAULT NULL COMMENT '簡短說明（首頁卡片用）',
  `full_desc`     TEXT         DEFAULT NULL COMMENT '完整說明（服務頁用）',
  `price_text`    VARCHAR(100) DEFAULT NULL COMMENT '價格文字，例如 NT$300/坪起',
  `icon`          VARCHAR(100) DEFAULT NULL COMMENT 'emoji 或 icon class',
  `image_path`    VARCHAR(300) DEFAULT NULL COMMENT '服務封面圖',
  `sort_order`    SMALLINT     NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. FAQ（掛在服務下）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_faqs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_id` INT UNSIGNED NOT NULL,
  `client_id`  INT UNSIGNED NOT NULL,
  `question`   VARCHAR(300) NOT NULL,
  `answer`     TEXT NOT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. 施工案例
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cases` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`      INT UNSIGNED NOT NULL,
  `service_id`     INT UNSIGNED DEFAULT NULL COMMENT '關聯服務（標籤分組用）',
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `location`       VARCHAR(100) DEFAULT NULL COMMENT '施工地點',
  `area_sqm`       DECIMAL(8,1) DEFAULT NULL COMMENT '坪數/面積',
  `before_image`   VARCHAR(300) DEFAULT NULL COMMENT '施工前圖片',
  `after_image`    VARCHAR(300) DEFAULT NULL COMMENT '施工後圖片',
  `sort_order`     SMALLINT NOT NULL DEFAULT 0,
  `is_featured`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '首頁精選',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. 客戶評價
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`   INT UNSIGNED NOT NULL,
  `reviewer_name`  VARCHAR(80) NOT NULL COMMENT '評論者名稱',
  `reviewer_avatar` VARCHAR(300) DEFAULT NULL COMMENT '頭像圖片路徑',
  `service_id`  INT UNSIGNED DEFAULT NULL COMMENT '評價的服務',
  `rating`      TINYINT NOT NULL DEFAULT 5 COMMENT '評分 1-5',
  `content`     TEXT NOT NULL COMMENT '評價內文',
  `source`      VARCHAR(50) DEFAULT 'google' COMMENT 'google / fb / line / other',
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. SEO 設定（每頁）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seo_settings` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`    INT UNSIGNED NOT NULL,
  `page_key`     VARCHAR(50) NOT NULL COMMENT 'home / services / cases',
  `meta_title`   VARCHAR(200) DEFAULT NULL,
  `meta_desc`    VARCHAR(500) DEFAULT NULL,
  `og_title`     VARCHAR(200) DEFAULT NULL,
  `og_image`     VARCHAR(300) DEFAULT NULL,
  UNIQUE KEY `uq_client_page` (`client_id`, `page_key`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. 後台管理帳號
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id`    INT UNSIGNED DEFAULT NULL COMMENT 'NULL = 超級管理員',
  `username`     VARCHAR(80) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'password_hash()',
  `display_name` VARCHAR(100) DEFAULT NULL,
  `role`         ENUM('super','client') NOT NULL DEFAULT 'client',
  `last_login`   DATETIME DEFAULT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA - 預設分類
-- ============================================================

INSERT INTO `categories` (`name`, `slug`, `icon`, `sort_order`) VALUES
  ('餐飲美食',  'food',         '🍽️', 10),
  ('居家服務',  'home-service', '🏠', 20),
  ('美容美髮',  'beauty',       '💅', 30),
  ('教育學習',  'education',    '📚', 40),
  ('健康醫療',  'health',       '⚕️', 50),
  ('運動休閒',  'sports',       '⚽', 60),
  ('零售購物',  'retail',       '🛍️', 70),
  ('專業服務',  'professional', '💼', 80),
  ('婚禮活動',  'wedding',      '💒', 90),
  ('其他',     'other',         '🏪', 999);

-- ============================================================
-- SEED DATA - 旭浪清潔示範客戶
-- ============================================================

-- 客戶主資料（subdomain=xusen，啟用小官網，分類=居家服務）
INSERT INTO `clients`
  (`subdomain`, `slug`, `brand_name`, `tagline`, `industry`, `category_id`, `has_minisite`, `phone`, `address`, `about_text`)
VALUES (
  'xusen',
  'xusen',
  '旭浪清潔',
  '專業到位，潔淨生活每一天',
  '居家清潔',
  (SELECT id FROM categories WHERE slug='home-service'),
  1,
  '06-205-1129',
  '台南市永康區',
  '旭浪清潔成立於台南，專注提供裝潢後細清、居家深度清潔、搬遷清潔及辦公室清潔等全方位服務。我們以細心、專業、負責的態度，為每一位客戶打造潔淨舒適的生活空間。'
);

-- 取得剛建立的 client_id（示範用 id=1）

-- 主題顏色（旭浪深林綠）
INSERT INTO `client_themes` (`client_id`, `theme_name`, `color_primary`, `color_accent`, `color_bg`, `color_text`, `color_light`, `is_active`)
VALUES
  (1, '深林綠（預設）',  '#1c3d3d', '#c8922a', '#ffffff', '#222222', '#f0f4f2', 1),
  (1, '海軍藍',          '#1a2e4a', '#e8943a', '#ffffff', '#1a1a2e', '#eef2f7', 0),
  (1, '磚紅暖系',        '#7d2d2d', '#d4a853', '#ffffff', '#2d1a1a', '#fdf5f0', 0),
  (1, '炭黑極簡',        '#1a1a1a', '#ff6b35', '#ffffff', '#111111', '#f5f5f5', 0),
  (1, '紫羅蘭',          '#3d1f5e', '#f0c040', '#ffffff', '#1a0a2e', '#f5f0fa', 0),
  (1, '湖水藍',          '#0d5c63', '#f4a261', '#ffffff', '#0a3040', '#e8f5f6', 0);

-- 社群連結
INSERT INTO `client_social` (`client_id`, `line_url`, `line_id`, `fb_page_url`)
VALUES (1, 'https://line.me/R/ti/p/@旭浪清潔', '@旭浪清潔', 'https://www.facebook.com/share/1Ceachfna5/?mibextid=wwXIfr');

-- 服務項目
INSERT INTO `services` (`client_id`, `name`, `slug`, `short_desc`, `full_desc`, `price_text`, `icon`, `sort_order`)
VALUES
  (1, '裝潢後細清', 'post-renovation', '裝潢完工後的精細清潔，去除粉塵、矽利康、油漆殘跡，讓新家煥然一新。', '裝潢完工後往往遺留大量粉塵、矽利康殘膠、油漆點及建材碎屑。旭浪裝潢後細清團隊使用專業工具與清潔劑，針對地板、門窗框、衛浴磁磚縫隙進行細部清潔，確保每個角落都達到完美標準。', 'NT$300/坪起', '🏗️', 1),
  (1, '居家深度清潔', 'deep-clean', '針對廚房、衛浴、窗軌等容易積污區域進行全面深層清潔。', '一般日常清潔難以去除的頑固污垢，包含油垢、水垢、霉斑。旭浪深度清潔服務針對廚房抽油煙機、爐具、衛浴磁磚、窗軌等重點區域深度處理，還原居家潔淨本色。', 'NT$150/坪起', '🏠', 2),
  (1, '搬遷清潔', 'moving-clean', '搬入前或搬出後的全屋清潔，確保乾淨完整交接。', '搬家前後的全屋清潔服務，包含舊屋的徹底打掃及新屋入住前的全面消毒清潔，讓您輕鬆搬入理想新居。', 'NT$200/坪起', '📦', 3),
  (1, '辦公室清潔', 'office-clean', '辦公環境定期維護清潔，打造健康整潔的工作場域。', '針對辦公空間提供定期或一次性清潔服務，包含地板清潔打蠟、辦公桌椅擦拭、洗手間深度清潔及玻璃擦拭，維持專業整潔的工作環境。', 'NT$100/坪起', '🏢', 4);

-- FAQ
INSERT INTO `service_faqs` (`service_id`, `client_id`, `question`, `answer`, `sort_order`)
VALUES
  (1, 1, '裝潢後細清通常需要幾天？', '依坪數而定，一般30坪住宅約需1～2個工作天。我們會在報價時確認施工時間。', 1),
  (1, 1, '清潔費用如何計算？', '以坪數計費，裝潢後細清 NT$300/坪起，實際費用依現場狀況（污染程度、特殊材質）報價。', 2),
  (2, 1, '深度清潔和一般清潔有什麼差別？', '一般清潔為表面打掃，深度清潔針對積年污垢、油垢、水垢進行專業溶解與刷洗，效果完全不同。', 1),
  (2, 1, '需要自備清潔用品嗎？', '不需要，我們自備所有專業清潔工具與藥劑，您只需確保當日可以進場即可。', 2),
  (3, 1, '搬遷清潔可以配合搬家公司時間嗎？', '可以，我們可依您的搬家時程彈性安排。建議提前3～5天預約以確保檔期。', 1),
  (4, 1, '辦公室清潔可以安排夜間或假日施工嗎？', '可以配合辦公室作息安排夜間或假日清潔，確保不影響正常營業。', 1);

-- 客戶評價
INSERT INTO `testimonials` (`client_id`, `service_id`, `reviewer_name`, `rating`, `content`, `source`, `sort_order`)
VALUES
  (1, 1, '王小姐', 5, '新家裝潢完請旭浪來細清，師傅非常仔細，連窗框縫隙都清得乾乾淨淨，搬進去直接住，超推薦！', 'google', 1),
  (1, 2, '陳先生', 5, '廚房油垢超嚴重，請了旭浪深度清潔，清完真的像新的一樣，師傅很專業也很有禮貌。', 'fb', 2),
  (1, 3, '林太太', 5, '搬家清潔服務很貼心，預約也很方便，當天準時到，效率很高，費用也合理。', 'google', 3),
  (1, 4, '張經理', 5, '公司辦公室交給旭浪定期清潔，每次都很滿意，員工環境整潔，工作效率也提升了！', 'google', 4);

-- SEO 設定
INSERT INTO `seo_settings` (`client_id`, `page_key`, `meta_title`, `meta_desc`)
VALUES
  (1, 'home',     '旭浪清潔｜台南專業清潔服務－裝潢後細清、居家深度清潔、搬遷清潔', '旭浪清潔提供台南裝潢後細清、居家深度清潔、搬遷清潔及辦公室清潔，專業到位、口碑保證，立即洽詢報價。'),
  (1, 'services', '服務項目｜旭浪清潔台南清潔公司', '查看旭浪清潔完整服務項目，包含裝潢後細清NT$300/坪起、居家深度清潔NT$150/坪起、搬遷清潔NT$200/坪起、辦公室清潔NT$100/坪起。'),
  (1, 'cases',    '施工案例｜旭浪清潔真實清潔前後對比', '旭浪清潔真實施工案例，Before/After 對比圖展示，讓您看見專業清潔的成果。');

-- 後台管理員帳號（密碼：admin1234）
INSERT INTO `admin_users` (`client_id`, `username`, `password_hash`, `display_name`, `role`)
VALUES
  (NULL, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系統管理員', 'super'),
  (1,    'xulang',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '旭浪清潔管理員', 'client');
-- 注意：上方 password_hash 為 password 字串的 bcrypt hash，請在正式環境重新產生
