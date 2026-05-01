# Phase B 後台 — Modular Blocks Admin UI ─ 完成報告

**完成日**：2026-05-01
**執行者**：Claude（自走完成）
**Production**：https://aqua-elephant-856571.hostingersite.com/admin/pages/store_blocks.php

---

## ✅ 完成項目

### 1. 主列表頁 `admin/pages/store_blocks.php`

功能：
- 顯示當前客戶（透過 `getCurrentClientId()` 取得）的全部 `store_blocks`，依 `sort_order`
- 每列顯示：序號 / type icon + 標題 / 內容項數 / 啟用狀態 / 操作按鈕
- 操作：**上移 ↑** / **下移 ↓** / **啟用/停用 ⏸▶** / **編輯** / **刪除**（含 confirm dialog）
- 上方「+ 新增區塊」區：依 `category_block_suggestions` 顯示**該分類建議的 block types**，附「必填」標記
- 「看全部 5 種 type」展開區（任何分類都能用）
- 上方狀態卡：顯示新系統「已啟用 ✓」或「尚未啟用」

### 2. 編輯頁 `admin/pages/store_block_edit.php`

- `?id=xxx` 編輯既有 / `?new=1&type=xxx` 新增
- POST handler 統一處理 5 種 type 的 data 結構（service / menu / portfolio / pricing / faq）
- 自動把 form 欄位 → 對應 JSON 結構 → 寫入 `store_blocks.data`
- 用 `saveStoreBlock()` helper（同 Phase B 主架構）
- 含 CSRF 驗證
- 依 `?type=` 動態 `include` 對應 form partial

### 3. 5 個 Form Partial（`admin/forms/form-{type}.php`）

| Partial | 功能 |
|---|---|
| `form-service.php` | 動態增減服務項目（icon + name + desc + price + image）|
| `form-menu.php` | **多群組 + 多項目**（前菜/主餐/甜點...）每群獨立 add/remove |
| `form-portfolio.php` | 作品 grid，含 `is_large` 大圖標記、tags 逗號分隔 |
| `form-pricing.php` | 方案表，含 `highlight` 推薦標記、features 多行特色 |
| `form-faq.php` | Q+A 動態增減 |

**所有表單**：
- 用 `<template>` + JS 動態 `add/removeItem()`
- 統一 `name="items[N][field]"` 陣列風格
- 對應 PHP POST handler 自動整理成 JSON 結構

### 4. Sidebar 加入口（`admin/includes/layout_head.php`）

在「內容管理」區段最前面加：
```
📦 區塊管理 [NEW] ← 紅色 NEW badge
🛠️ 服務項目（舊）  ← 加「（舊）」標示
```

### 5. 安全加固（`admin/forms/.htaccess`）

```
Deny from all
```
- 防止直接 HTTP 訪問 form partial（會 leak 內部結構）
- 內部 `include` 仍正常運作

---

## 🌐 上線網址

| URL | HTTP | 說明 |
|---|---|---|
| `/admin/pages/store_blocks.php` | 302 → login（未登入正確行為）| 區塊主列表 |
| `/admin/pages/store_block_edit.php?id=1` | 302 → login | 編輯既有 service block |
| `/admin/pages/store_block_edit.php?new=1&type=menu` | 302 → login | 新增菜單 block |
| `/admin/forms/form-service.php` | **403 Forbidden** ✅ | 直接訪問被阻擋 |

**登入後流程**：
1. Sidebar 點「📦 區塊管理 [NEW]」
2. 看當前客戶的全部 blocks
3. 點「+ 新增區塊」選擇 type（依分類建議）→ 進編輯頁
4. 填好內容 → 儲存
5. 立刻反映到 `/store/{slug}` 前台

---

## 🧪 本機測試結果

```
✅ admin/pages/store_blocks.php                  → 200 (旭浪 2 個 blocks 正確列出)
✅ admin/pages/store_block_edit.php?id=1         → 200 (service 4 items 正確載入)
✅ admin/pages/store_block_edit.php?new=1&type=menu  → 200 (menu form 顯示)
✅ admin/pages/store_block_edit.php?new=1&type=portfolio → 200 (portfolio form 顯示)
✅ Sidebar nav 顯示「區塊管理 [NEW]」
✅ 「服務項目」加「（舊）」字樣
```

---

## 📁 異動檔案清單

### 新增（7 檔）
| 檔案 | 大小 |
|---|---|
| `admin/pages/store_blocks.php` | 11 KB |
| `admin/pages/store_block_edit.php` | 7 KB |
| `admin/forms/form-service.php` | 5 KB |
| `admin/forms/form-menu.php` | 6 KB |
| `admin/forms/form-portfolio.php` | 5 KB |
| `admin/forms/form-pricing.php` | 5 KB |
| `admin/forms/form-faq.php` | 3 KB |
| `admin/forms/.htaccess` | 100 B（安全）|

### 修改（1 檔）
| 檔案 | 改動 |
|---|---|
| `admin/includes/layout_head.php` | sidebar 加「區塊管理」 nav |

---

## 🚧 還沒做（Phase B 後續可選）

### 已知不足

1. **沒有 drag-drop 排序** — 用 ↑↓ 按鈕，每次點都會 reload
   - 改善：加 SortableJS（npm 一個 lib）+ AJAX endpoint 即時更新 `sort_order`
   - 工程：1-2 小時

2. **沒有圖片上傳** — Portfolio / service 的 image 欄位要手填路徑
   - 改善：加圖片上傳按鈕（呼叫既有 `uploadImage()` helper）
   - 工程：2-3 小時（要加到所有 form partial）

3. **沒有預覽功能** — 編輯時看不到預覽，要儲存後才能去前台看
   - 改善：加「即時預覽」iframe，POST 到一個 preview endpoint
   - 工程：4-6 小時

4. **舊 services / cases / faqs 後台還在跑** — 編輯舊表不會反映到 store_blocks
   - 短期 workaround：admin 加一個「重建 blocks」按鈕呼叫 migration 004
   - 長期：把舊 admin 頁標 deprecated 引導到新 store_blocks.php

5. **批次操作** — 沒有「複製到其他客戶」「批次匯入」之類工具
   - 之後針對連鎖店家可加

---

## 📊 數字總結

| 維度 | 數字 |
|---|---|
| 新檔 | 8 個（7 PHP + 1 .htaccess）|
| 修改檔 | 1 個 |
| Form partial | 5 個（service/menu/portfolio/pricing/faq）|
| Admin URL | 2 個（list + edit）|
| Production smoke test | 4 URL 全 ✅（含 403 安全測）|
| 部署檔案 | 9 個 |

---

## 🔧 後續操作備忘

### 從零測試新 admin 流程

1. 登入 `/admin/login.php` 用 superadmin 帳號
2. 在 dashboard sidebar 點「📦 區塊管理 [NEW]」
3. 第一次會 redirect 到 dashboard（因為沒選客戶）→ 去客戶清單選一個
4. 切回區塊管理 → 看到該客戶的 blocks
5. 點「+ 新增區塊」→ 選 type → 進編輯頁 → 填內容 → 儲存
6. 開新分頁訪問 `/store/{slug}` 看效果

### Production 上跑「重建 blocks」工具（同步舊資料）

```bash
curl 'https://aqua-elephant-856571.hostingersite.com/migrations/004_migrate_to_blocks.php?key=migrate-blocks-2026'
```

### 緊急回滾（如新 admin 有 bug）

備份位置：
```
/_backups/phase_b_admin_*/admin/includes/layout_head.php
```

只還原 sidebar 即可（移除 nav 入口），新檔留著不動就不會被觸發。

---

## 🟡 已知議題

1. **新 admin UI 樣式 inline** — 沒抽到 admin.css，未來要重構時應該抽出
2. **沒有 admin 截圖** — 因為 Chrome headless 帶 cookie 截圖比較繁瑣，等用戶實機驗證
3. **superadmin 必須先「切換客戶」** — 透過 `/admin/pages/clients.php?switch=ID`，否則 store_blocks 拒絕（Phase F 可加自動 fallback 到 #1）

---

**Phase B 後台完成。** 結合 Phase B 主架構，現在已具備完整的 Modular Blocks 流程：

```
後台編輯 → store_blocks 表 → block_helpers.php → 前台 store.php → 5 個 block partial → g-block-* CSS → 視覺呈現
```

下一輪建議重點：**Phase C（store.php 視覺對齊 mockup）** 或 **打磨 admin（drag-drop / 圖片上傳）**。
