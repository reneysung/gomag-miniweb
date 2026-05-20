# Migrations 資料夾

這裡放資料庫變更腳本。每個檔案是一次性的，跑過就結束。

## 怎麼跑

每個 migration 是一個 PHP 檔，本機只要在瀏覽器打開對應網址即可：

```
http://localhost:8888/miniweb/migrations/001_add_categories_and_landing.php
```

跑完會看到「✅ Migration 001 全部完成」字樣。

## 上線時注意

正式環境部署時，這整個 `migrations/` 資料夾**不要丟到伺服器**（或丟上去後跑完一次馬上刪除）。
原因：avoid 任何人在網路上隨意觸發資料庫變更。

正式環境如果一定要跑，要在網址後加 `?confirm=YES`：
```
https://gomag.com.tw/migrations/001_add_categories_and_landing.php?confirm=YES
```
跑完立刻刪除 `migrations/` 資料夾。

## 已存在的 migration

| 編號 | 檔案 | 說明 | 何時跑 |
|------|------|------|--------|
| 001 | 001_add_categories_and_landing.php | 加 categories 表 + clients 6 個新欄位（分類、行銷頁、外部官網、小官網開關、營業時間、舊店家編號） | Phase 1 開始時 |
