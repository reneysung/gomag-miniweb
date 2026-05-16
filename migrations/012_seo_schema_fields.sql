-- Migration 012 — 加 SEO schema 強化欄位（參考旭森 Organization 完整版）
-- 目的：讓 LocalBusiness JSON-LD 能輸出完整 PostalAddress / GeoCoordinates / openingHours
-- 客戶可在 admin 補資料（未填的欄位 schema 會自動跳過、向後相容）

ALTER TABLE clients
  ADD COLUMN latitude       DECIMAL(10,7) NULL     COMMENT '緯度（Google Maps lat）' AFTER address,
  ADD COLUMN longitude      DECIMAL(10,7) NULL     COMMENT '經度（Google Maps lng）' AFTER latitude,
  ADD COLUMN address_street VARCHAR(200)  NULL     COMMENT '街道地址（如：正南六街82號）— PostalAddress.streetAddress' AFTER longitude,
  ADD COLUMN address_district VARCHAR(50) NULL     COMMENT '行政區（如：永康區）— PostalAddress.addressLocality' AFTER address_street,
  ADD COLUMN address_region VARCHAR(50)   NULL     COMMENT '縣市（如：台南市）— PostalAddress.addressRegion' AFTER address_district,
  ADD COLUMN postal_code    VARCHAR(10)   NULL     COMMENT '郵遞區號（如：710）— PostalAddress.postalCode' AFTER address_region,
  ADD COLUMN opening_hours_json JSON      NULL     COMMENT '營業時間 JSON：{"mon":"09:00-18:00","tue":...,"sun":"closed"}' AFTER postal_code;
