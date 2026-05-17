-- Migration 016 — clients 加 hPanel 子網域已設旗標
-- 用途：admin Dashboard 列出「has_minisite=1 但 hPanel 未設」的待辦清單
-- 自動偵測不可靠（Hostinger HTTP/2 PROTOCOL_ERROR）→ 改用手動標記

ALTER TABLE clients
  ADD COLUMN subdomain_provisioned TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'hPanel 是否已設子網域 + SSL（手動標記）' AFTER has_minisite,
  ADD COLUMN subdomain_provisioned_at DATETIME NULL
    COMMENT '設定完成日期' AFTER subdomain_provisioned;

-- 已知已設好的子網域回填（artru / fulldemo / fooddemo / designdemo / lanhung 已驗 200 OK）
UPDATE clients SET subdomain_provisioned=1, subdomain_provisioned_at=NOW()
  WHERE slug IN ('artru', 'fulldemo', 'fooddemo', 'designdemo', 'lanhung');
