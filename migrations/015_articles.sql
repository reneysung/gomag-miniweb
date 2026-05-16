-- Migration 015 — articles 表（Phase 7 部落格/專欄）
-- 旭森參考 /column/detail/{slug}：每客戶可寫長文 SEO 內容
-- Article schema 給 Google rich result，搶「{服務} 怎麼選 / 多少錢 / 推薦」這類資訊型搜尋

CREATE TABLE IF NOT EXISTS articles (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id     INT UNSIGNED NOT NULL,
  slug          VARCHAR(200) NOT NULL                 COMMENT 'URL slug（中文 OK）',
  title         VARCHAR(200) NOT NULL,
  summary       VARCHAR(300) NULL                     COMMENT '摘要 / meta description（150 字內）',
  cover_image   VARCHAR(300) NULL                     COMMENT '封面圖路徑',
  content       MEDIUMTEXT   NULL                     COMMENT '主文（HTML / Quill 輸出）',
  category      VARCHAR(50)  NULL                     COMMENT '分類標籤（如：知識文 / 案例分享 / 服務介紹）',
  tags_json     JSON         NULL                     COMMENT 'tags array，給 keywords schema 用',
  sort_order    SMALLINT     NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  published_at  DATETIME     NULL                     COMMENT '發布時間（不填 = 用 created_at）',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_client_slug (client_id, slug),
  KEY idx_client_active (client_id, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
