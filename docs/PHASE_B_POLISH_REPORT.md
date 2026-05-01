# Phase B Polish ─ 圖片上傳 + 示範客戶 + 城市後台

**完成日**：2026-05-02
**執行者**：Claude（自走完成）
**Production**：https://aqua-elephant-856571.hostingersite.com

---

## ✅ 完成的 3 件事

### 1️⃣ 圖片上傳整合（admin form partials）

之前 admin 編輯 block 要手填圖片路徑，現在能直接從電腦上傳。

**改動**：
- `admin/forms/form-service.php` — 加 `<input type="file">` + 既有圖片預覽
- `admin/forms/form-portfolio.php` — 同上（portfolio 圖片必填）
- `admin/forms/form-menu.php` — 每個菜單品項可加圖（餐飲適用）
- `admin/pages/store_block_edit.php` — POST handler 加 `handleBlockImageUpload()` 處理：
  - input name 規格：`image_items_{idx}` 對應 `items[{idx}][image]`
  - menu 的更深 nesting：`image_groups_{gi}_items_{ii}`
  - 上傳成功 → 寫入 `uploads/{subdir}/{file}` 路徑到對應 image 欄位
  - 上傳失敗或沒選檔 → 保留原路徑

**子目錄**：service block 上傳到 `uploads/services/`、portfolio 到 `uploads/cases/`、menu 到 `uploads/menu/`

### 2️⃣ 3 個示範客戶 + 完整 demo blocks

`migrations/005_seed_demo_blocks.php` — 冪等 seed script，覆蓋 4 個分類示範：

| Client | 分類 | Blocks |
|---|---|---|
| #1 旭浪清潔 | 居家服務 | service (4) + faq (6) ← Phase B 主架構就有 |
| #10 歡樂牛排嘉義店 | 餐飲美食 | **menu (3 群組 9 品項)** + faq (4) |
| #18 濱緻車體美學 | 汽車服務 | service (4) + **pricing (3 方案)** + faq (5) |
| #3 綝綝美甲美睫紋繡學院 | 美容美髮 | service (4) + **pricing (3 方案)** + faq (4) |

**5 種 block type** 全部有真實 demo 資料可看（service、menu、pricing、faq；portfolio 因為 cases 表沒資料先 skip）。

### 3️⃣ 城市管理後台（取代寫死的 PHP array）

之前 4 個城市的 SEO 文案、Hero 圖、服務區域寫死在 `city.php` 裡的 PHP array → 要改文字得改程式碼。

**改動**：
- `migrations/006_create_cities.sql` — 建 `cities` 表（11 欄）+ seed 4 城資料
- `city.php` — 從 `$cityIntros = [...]` 寫死 → 改用 `SELECT FROM cities` 動態讀
  - 含 fallback：DB 連不上時不爆頁
- `admin/pages/cities.php` — 全新後台頁
  - 列表：4 城 + tagline + 店家數 + 啟用狀態
  - 編輯表單：tagline / intro / highlights（一行一個）/ areas（一行一個）/ hero_image / SEO 覆寫 / is_active
  - 含預覽前台連結
- `admin/includes/layout_head.php` — sidebar 加「🌏 城市管理 [NEW]」（只 super admin 可見）

---

## 🌐 Production 完整 smoke test（13 URL 全 ✅）

| URL | HTTP | |
|---|---|---|
| `/` | 200 | 首頁 |
| `/city` | 200 | 縣市總覽 |
| `/city/{tainan,kaohsiung,chiayi,taichung}` | 200 ×4 | 4 個城市頁（從 cities 表讀）|
| `/store/xusen` | 200 | 旭浪（service+faq blocks）|
| `/store/happysteakcyi` | 200 | 歡樂牛排（**menu+faq blocks**）|
| `/store/carbeauty2` | 200 | 濱緻車體（service+**pricing**+faq）|
| `/store/062281421` | 200 | 綝綝美甲（service+pricing+faq）|
| `/store/ch` | 200 | 佳鋐（無 blocks → 走舊 fallback）|
| `/admin/pages/cities.php` | 302→login | ✅ 安全 |
| `/admin/pages/store_blocks.php` | 302→login | ✅ 安全 |

---

## 🔑 怎麼用

### A. 編輯城市文案
1. 登入 admin → sidebar 點 **🌏 城市管理 [NEW]**
2. 點任一城市的「編輯」→ 改 tagline / intro / 亮點 / 服務區域 / Hero 圖
3. 儲存 → 立刻反映到 `/city/{slug}`

### B. 編輯店家 blocks（含上傳圖片）
1. 登入 admin → 切換到目標客戶（左下方 Switch）
2. sidebar 點 **📦 區塊管理 [NEW]**
3. 點「+ 新增區塊」→ 選 type
4. 編輯時：
   - **圖片欄位**：左邊是既有路徑（可手填）+ 下面 `<input type="file">`（上傳取代）
   - 上傳新圖會自動存到 `uploads/{subdir}/img_xxx.jpg` 並更新路徑
5. 儲存 → 前台 `/store/{slug}` 立刻看到效果

### C. Demo 給業務團隊看
4 個示範客戶完整 demo 5 種 block type（少 portfolio）：
- 旭浪 → service + faq（純服務型）
- 歡樂牛排 → menu + faq（餐飲型）
- 濱緻車體 → service + pricing + faq（含價格方案）
- 綝綝美甲 → service + pricing + faq（含價格方案）

---

## 📁 異動檔案清單

### 新增
| 檔案 | 大小 |
|---|---|
| `admin/pages/cities.php` | 7 KB |
| `migrations/005_seed_demo_blocks.php` | 7 KB |
| `migrations/006_create_cities.sql` | 4 KB |
| `docs/PHASE_B_POLISH_REPORT.md` | 本檔 |
| `docs/screenshots/local-store-{...}-blocks.png` | 4 張示範截圖 |

### 修改
| 檔案 | 改動 |
|---|---|
| `admin/forms/form-service.php` | 加圖片上傳 + 預覽 |
| `admin/forms/form-portfolio.php` | 加圖片上傳 + 預覽 |
| `admin/forms/form-menu.php` | 每菜單品項加圖片上傳 |
| `admin/pages/store_block_edit.php` | POST handler 處理 $_FILES |
| `admin/includes/layout_head.php` | sidebar 加「城市管理」入口 |
| `city.php` | $cityIntros 改從 cities 表讀 |

---

## 📊 數字

| 維度 | 數字 |
|---|---|
| 新檔 | 5 個 |
| 修改檔 | 6 個 |
| 新增 DB 表 | 1 個 (`cities`) |
| 已遷移店家 | 4 / 207（從 1 → 4，分布 4 個分類）|
| Production smoke test | 13 URL 全 ✅ |
| Migration 跑 | 005 (web) + 006 (SQL via SSH) |
| Git 預備 commits | 1 個 |

---

## 🚧 還沒做（可選後續）

| 工程 | 時間 | 重要性 |
|---|---|---|
| Drag-drop block 排序 | 1-2 hr | 🟡 體驗 |
| **block 預覽 iframe**（編輯時即時看效果）| 4-6 hr | 🔴 重要 |
| Phase C：store.php 視覺對齊 mockup（photo gallery、owner block...）| 5-7 天 | 🔴 大改 |
| Phase D：city.php 加本週熱門 / 真實口碑 / 最新加入 | 3-5 天 | 🟡 |
| 標舊 admin（services/cases/faqs）為 deprecated | 30 min | 🟡 |

---

## 🟡 已知議題

1. **landing_extra_content 跟 blocks 並存** — 有 22KB raw HTML 的客戶（如 happysteakcyi、carbeauty2），他們的店家頁會「先看到舊 raw 內容」「再看到新 blocks」，視覺重複。建議業務逐家把舊 landing_extra_content 內容移到 block 後清掉。
2. **沒有 cities 新增 / 刪除** — slug 寫死在 `city.php` 對映表，要新增城市需改程式碼（避免誤刪）
3. **menu / pricing block 的 form 沒做 RWD 嚴格優化** — 後台主要桌機操作

---

**Phase B Polish 完成。** 結合 Phase A + Phase B + 後台 + Polish，現在的店家好口碑系統具備：

- ✅ 新品牌設計系統（gomag g-* CSS）
- ✅ 城市落地頁（4 城 + 動態 SEO 文案）
- ✅ Modular Blocks 後端（5 種 type、雙寫期、向後相容）
- ✅ Modular Blocks 後台（CRUD UI + 圖片上傳）
- ✅ 城市管理後台（取代 hard-code）
- ✅ 4 個示範客戶 demo 用

**下一輪建議**：Phase C（store.php 視覺對齊 mockup，最大視覺工程）或繼續打磨 admin（drag-drop / 預覽 iframe）。
