# Phase B — Modular Blocks 後端架構升級 ─ 完成報告

**完成日**：2026-05-01
**執行者**：Claude（自走完成）
**Production**：https://aqua-elephant-856571.hostingersite.com

---

## ✅ 完成項目

### 1. DB Schema（`migrations/003_modular_blocks.sql`）

新增 2 表（沿用現有 `clients` 表當 stores，避免大手術）：

| 表 | 用途 |
|---|---|
| `store_blocks` | 每筆 = 一個店家的一個區塊（type ENUM + JSON data + sort_order）|
| `category_block_suggestions` | 12 分類 → 5 種 block 對映（驅動後台 UI）|

**Seed**：12 分類全部對應好建議 block 順序：
- 餐飲美食：menu, portfolio, faq
- 居家服務：service, pricing, portfolio, faq
- 美容美髮：service, pricing, portfolio
- 汽車服務：service, portfolio, pricing, faq
- ...（共 12 條）

### 2. PHP Helper API（`includes/block_helpers.php`）

```php
getStoreBlocks(int $clientId): array          // 撈單店家全部 active blocks
getCategoryBlockSuggestions(int $catId): array // 後台新增下拉用
renderBlock(array $block): void               // 渲染單一 block
renderStoreBlocks(int $clientId): void        // 渲染全部 blocks
saveStoreBlock(...): int                      // 後台新增/編輯
deleteStoreBlock(int $id, int $clientId): void
clientHasBlocks(int $clientId): bool          // 雙寫期判斷
```

### 3. 5 個 Block Partial（`blocks/*.php`）

| Type | 適用 | 結構 |
|---|---|---|
| `service` | 汽車、居家、美容、教育、醫療 | `{ title, items:[{icon, name, short_desc, price_text, image}] }` |
| `menu` | 餐飲 | `{ title, groups:[{ name, items:[{name, price, desc, image, tag}] }] }` |
| `portfolio` | 通用 | `{ title, layout, items:[{image, title, desc, tags, is_large}] }` |
| `pricing` | 教育、醫療、汽車、健身 | `{ title, currency, items:[{name, price, unit, features:[], highlight}] }` |
| `faq` | 全分類萬用（含 FAQPage JSON-LD）| `{ title, items:[{q, a}] }` |

**完整 g-* CSS 樣式**（含 RWD），加在 `assets/css/gomag.css` 末尾（多 +280 行 CSS）。

### 4. 資料遷移（`migrations/004_migrate_to_blocks.php`）

冪等遷移腳本，雙寫期保留所有舊表：
- `services` → `store_blocks` (type=service)
- `service_faqs` → `store_blocks` (type=faq)
- `cases` → `store_blocks` (type=portfolio)

**遷移結果**（local + production 一致）：
| 來源 | 筆數 | 遷移後 block 數 |
|---|---|---|
| services (4 筆) | 1 個 client | 1 service block |
| service_faqs (6 筆) | 1 個 client | 1 faq block |
| cases (0 筆) | - | 0 portfolio blocks |
| testimonials (4 筆) | - | **保留獨立表**（評價不適合 block 化）|

**唯一受影響客戶：旭浪清潔（id=1）** → 2 個 blocks。其他 206 家完全沒影響。

### 5. `store.php` 雙寫期邏輯

```php
$useBlocks = !$isPlaceholder && clientHasBlocks($cid);

if ($useBlocks) {
    // 新系統：載 gomag.css，render store_blocks
    renderStoreBlocks($cid);
} elseif (!$isPlaceholder && $services) {
    // 舊系統：render services + cases（向後相容，未遷移客戶不受影響）
}
```

⭐ **零破壞性變更** — 沒有遷移到新系統的店家，繼續用舊邏輯渲染。

### 6. Production 部署

備份至 `_backups/phase_b_*`，rsync 上傳：

| 檔案 | 性質 |
|---|---|
| `blocks/_block_helpers.php` + 5 個 partial | 全新 |
| `includes/block_helpers.php` | 全新 |
| `migrations/003_modular_blocks.sql` | 全新 |
| `migrations/004_migrate_to_blocks.php` | 全新 |
| `store.php` | 修改（加雙寫期）|
| `assets/css/gomag.css` | 修改（加 280 行 block CSS）|

跑 production migrations 兩次：
- SQL migration 003 → 透過 SSH mysql CLI
- PHP migration 004 → 透過 web URL（CLI 沒 HTTP_HOST 會用本機 config）

---

## 🌐 上線網址（驗證）

| URL | 說明 | 結果 |
|---|---|---|
| https://aqua-elephant-856571.hostingersite.com/store/xusen | 旭浪 — 走新 blocks | ✅ service + faq 渲染 |
| https://aqua-elephant-856571.hostingersite.com/store/ch | 佳鋐 — 走舊 fallback（無 blocks）| ✅ |
| https://aqua-elephant-856571.hostingersite.com/store/docar | 鍍卡 — 走舊 fallback | ✅ |
| https://aqua-elephant-856571.hostingersite.com/store/taichung-rouhanghang | placeholder | ✅ 顯示「資料整理中」 |

---

## 🚧 還沒做（建議下一步順序）

### 🥇 後台「店家編輯」加「區塊管理」分頁（1-2 天）

目前 admin 介面**沒有對接 store_blocks 表**。實際運作是：
- ✅ 既有 services / cases / faqs 後台還能編輯（資料寫到舊表）
- ❌ 修改不會自動同步到 store_blocks（即不會反映到前台 blocks 區）
- 🔄 需要管理員「重跑 migration 004」才能把改動同步

**短期 workaround**：在 admin/clients 加一個「重建 blocks」按鈕，呼叫 migration 004 重跑

**長期解法**：寫真正的 store_blocks CRUD（`admin/pages/store_blocks.php`）+ 5 個 form-{type}.php 表單，編輯時直接寫 store_blocks，不再經過舊表

### 🥈 寫 5 個 admin/forms/form-*.php

對應 5 種 block 各一個編輯 UI：
- `form-service.php` — 服務項目陣列（icon + name + desc + price + image）
- `form-menu.php` — 多群組菜單
- `form-portfolio.php` — 作品網格（含 is_large flag 大圖控制）
- `form-pricing.php` — 價目表（含 highlight flag 推薦方案）
- `form-faq.php` — FAQ 陣列（q+a）

### 🥉 雙寫期 → 切換期 → 清理期

3 階段時程（建議 1-2 週後）：
1. 雙寫期（現在）：舊表保留，新表為主，舊客戶 fallback
2. 切換期：所有客戶資料都遷移到 store_blocks，admin/services 改唯讀
3. 清理期（觀察 1 個月後）：舊表凍結，下下次再考慮 DROP

---

## 📊 數字總結

| 維度 | 數字 |
|---|---|
| 新表 | 2 (`store_blocks`, `category_block_suggestions`) |
| Block types | 5 (service / menu / portfolio / pricing / faq) |
| Block partial 檔 | 5 + 1 helper |
| Helper API | 6 個 function |
| CSS 增加 | +280 行（gomag.css 從 12KB → 20KB）|
| 已遷移店家 | 1 / 207 (旭浪試點) |
| Production smoke test | 4 個 store 頁全 200 OK |
| 部署檔案 | 11 個（7 PHP + 1 CSS + 2 migration + 1 helper）|

---

## 🔧 後續操作備忘

### 重跑遷移（增量同步）

```bash
# Local
php migrations/004_migrate_to_blocks.php

# Production
curl 'https://aqua-elephant-856571.hostingersite.com/migrations/004_migrate_to_blocks.php?key=migrate-blocks-2026'
```

### 看某店家有哪些 blocks

```bash
# Local
mysql -h 127.0.0.1 -P 8889 -u root -proot miniweb \
  -e "SELECT id, type, sort_order, JSON_EXTRACT(data, '\$.title') AS title FROM store_blocks WHERE client_id=1;"

# Production (via SSH)
ssh -p 65002 u331306067@145.79.14.161 \
  "mysql -h localhost -u u331306067_miniweb -p... u331306067_miniweb -e 'SELECT ...'"
```

### 強制某店家用新系統（後台改造完前的緊急手段）

直接 INSERT 一筆 store_blocks 即可，前台 store.php 自動切換到新邏輯。

### 緊急回滾

刪除某 client 的 blocks → 自動 fallback 到舊系統：
```sql
DELETE FROM store_blocks WHERE client_id = ?;
```
舊 services / faqs 表完全保留，零資料遺失。

---

## 🟡 已知議題

1. **後台仍寫舊表** — admin/services.php 編輯後不會反映到 store_blocks（要等後台改造）
2. **menu / pricing block 沒有現成資料來源** — 餐飲店要用 menu block 需要先有人手動建（或寫 menu 後台）
3. **portfolio (cases) 表是空的** — 沒有資料可遷移
4. **CDN cache 1 週** — Hostinger 有 7 天 cache-control，CSS 改動可能 cache busting 才看得到（瀏覽器加 `?v=` query string）

---

## 📋 Phase B 完成清單

- ✅ 2 表建好 + seed 12 分類 → 31 條 suggestions
- ✅ 5 個 block partial 寫好（含 JSON-LD FAQPage）
- ✅ 6 個 helper function 完整
- ✅ 280 行 g-block-* CSS（含 RWD）
- ✅ Migration 雙寫期執行成功（local + prod 一致）
- ✅ store.php 雙寫期邏輯（零破壞性變更）
- ✅ Production 部署 + smoke test
- ✅ 截圖 3 張驗證
- ⏸ 後台改造（**留下一輪**）
- ⏸ 5 個 form-{type}.php（**留下一輪**）

---

**Phase B 完成。** 旭浪清潔的店家頁已經完全跑在新 blocks 系統上，其他 206 家保持不變（向後相容）。下一輪重點建議：**做後台改造，讓內勤/業務能直接編輯 blocks**。
