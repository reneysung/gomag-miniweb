# Demo Polish ─ 4 個示範店家頁面完整對齊新設計

**完成日**：2026-05-02
**執行者**：Claude（自走完成）
**Production**：https://aqua-elephant-856571.hostingersite.com

---

## ✅ 完成 4 件事

### 1️⃣ Portfolio block demo data（5/5 type 完整）

加 `migrations/005_seed_demo_blocks.php` 補濱緻車體 (id=18) 的 portfolio block：
- 4 個案例（白色 Tesla Model 3 / 寶藍 BMW M3 / 銀色 Toyota RAV4 / 深度漆面美容）
- 第一張用 `is_large=true`（佔 2 格寬度，Airbnb-style 大圖）
- 含 tags（XPEL / Tesla / BMW 等）

→ 5 種 block type **全部都有真實 demo 資料**：service / menu / portfolio / pricing / faq

### 2️⃣ 清掉 4 示範客戶的 landing_extra_content

之前 happysteakcyi / carbeauty2 / 062281421 三個 client 各有 10-22KB 的 raw HTML 在 `landing_extra_content`，會在新 blocks 上方先呈現一大段亂排的圖片，視覺重複難看。

**做法**：
- 安全備份至 `_backups/landing_extra_demo_clients_*.sql`（50KB 完整內容）
- migration 005 加 `UPDATE clients SET landing_extra_content = NULL WHERE id IN (1,3,10,18)`
- 本機 + production 都跑

→ 4 個示範頁現在**只用 blocks 系統**，視覺乾淨。

### 3️⃣ 全新 g-store-hero 樣式（gomag tokens 對齊）

加 `assets/css/gomag.css` 約 +180 行新元件：
- `.g-store-hero` — 大區塊 hero（左 1.4fr 文字 / 右 1fr 圖）
- `.g-store-hero-title` — 44px 標題 + Manrope 字距
- `.g-store-hero-cat-pill` — 分類膠囊
- `.g-store-hero-rating` — 星級評分
- `.g-store-btn` 三色 CTA：primary（橘紅 #FF5A36）/ secondary（黑）/ outline
- `.g-store-hero-stats` — 數字面板（含分隔線 + Manrope 字體）
- `.g-store-hero-info` — 聯絡資訊條列
- `.g-store-hero-image` — 4:3 比例 + 16px 圓角 + soft shadow
- 完整 RWD（1024px / 640px breakpoints）

### 4️⃣ store.php 條件式套用新 hero

```php
<?php if ($useBlocks): ?>
  <!-- gomag g-store-hero 新樣式 -->
<?php else: ?>
  <!-- 舊 m-* 樣式（203 個未啟用 blocks 客戶不受影響）-->
<?php endif; ?>
```

⭐ **零破壞性變更**：未啟用 blocks 的 203 個客戶頁面外觀不變。

---

## 🌐 看效果（強烈建議用真實瀏覽器看）

| 客戶 | URL | demo 重點 |
|---|---|---|
| 旭浪清潔 | https://aqua-elephant-856571.hostingersite.com/store/xusen | service + faq |
| 歡樂牛排 | https://aqua-elephant-856571.hostingersite.com/store/happysteakcyi | **menu** 3 群組 9 品項（精緻菜單視覺）|
| 濱緻車體 | https://aqua-elephant-856571.hostingersite.com/store/carbeauty2 | service + **portfolio** 4 案例 + **pricing** 3 方案 + faq |
| 綝綝美甲 | https://aqua-elephant-856571.hostingersite.com/store/062281421 | service + pricing + faq |
| 佳鋐不鏽鋼 | https://aqua-elephant-856571.hostingersite.com/store/ch | **舊樣式對照**（沒 blocks）|

⚠️ **CDN cache 一週**：第一次開可能載到舊 CSS，加 `?v=1` 或硬重整。

---

## 📊 Production smoke test

```
✅ /store/xusen              → 200 (12 g-store-hero 元件 / 56 g-block 元件)
✅ /store/happysteakcyi      → 200 (7 / 86)
✅ /store/carbeauty2         → 200 (7 / 107) ← portfolio block 加進去
✅ /store/062281421          → 200 (7 / 65)
✅ /store/ch                 → 200 (0 / 0)  ← 舊樣式正確 fallback
```

---

## 📁 異動檔案清單

### 修改（4 個）
| 檔案 | 改動 |
|---|---|
| `assets/css/gomag.css` | +180 行 g-store-hero 元件 + RWD（24 KB → 32 KB）|
| `store.php` | Hero 區條件式分流：blocks 客戶用 g-* / 其他用 m-* |
| `migrations/005_seed_demo_blocks.php` | 加 portfolio block + 清空 landing_extra_content |
| `.gitignore` | 加 `_backups/` 排除 |

### 新增
| 檔案 | 說明 |
|---|---|
| `docs/DEMO_POLISH_REPORT.md` | 本報告 |
| `docs/screenshots/v3-store-{...}.png` | 5 張本機驗證截圖 |
| `docs/screenshots/prod-store-{...}-final.png` | 4 張 production 最終截圖 |

### 備份（在 .gitignore，不進 git）
| 檔案 | |
|---|---|
| `_backups/landing_extra_demo_clients_*.sql` | 50KB，含 3 client 原始 landing_extra_content，需要還原時直接 import |

---

## 📊 數字總結

| 維度 | 數字 |
|---|---|
| Block types 完整 demo | **5 / 5**（service, menu, portfolio, pricing, faq）|
| 4 個示範客戶 blocks 總數 | 11 (旭浪 2 + 綝綝 3 + 牛排 2 + 濱緻 4) |
| 新 CSS 元件 | g-store-hero 全套（10+ class）|
| 異動檔案 | 4 修改 + 9 新增 |
| Production smoke test | 5 URL 全 ✅ |
| Git commit | 1 個 |

---

## 🎯 demo 給業務團隊的台詞範本

> 我們做了一套全新的「店家頁系統」，能涵蓋 12 個分類的所有需求：
>
> - **餐飲店**就用「菜單」block（看 https://...com/store/happysteakcyi 的歡樂牛排）
> - **汽車服務**用「服務 + 案例 + 價目表」三 block 組合（看濱緻車體 carbeauty2 — 4 卡服務 + 4 大圖案例 + 3 方案推薦）
> - **美容業**用「服務 + 價目表」（看綝綝美甲 062281421）
> - **居家服務**用「服務 + 常見問題」（看旭浪清潔 xusen）
>
> 後台一鍵切換 block 類型，圖片可直接上傳，文字所見即所得。
> 連我們業務員到店家現場就能用筆電/平板填好，**店主零學習成本**。

---

## ⏭️ 還沒做（建議下一輪）

| 工程 | 時間 | 重要性 |
|---|---|---|
| **block 即時預覽 iframe**（編輯不用儲存就能看效果）| 4-6 hr | 🔴 |
| Drag-drop 排序（取代 ↑↓ 按鈕）| 1-2 hr | 🟡 |
| Phase C：full mockup（photo gallery 5 格 / owner block / sticky sidebar / reviews summary）| 5-7 天 | 🟡 demo 已夠看 |
| Phase D：city.php 加本週熱門 / 真實口碑 / 最新加入 | 3-5 天 | 🟡 |
| 標舊 admin（services/cases/faqs）為 deprecated | 30 min | 🟡 |
| **逐家把舊 landing_extra_content 內容遷到 blocks**（人工） | per-client | 🔴 大規模需要 |

---

## 🟢 整套系統現況

到 Phase B Polish + Demo Polish 為止，店家好口碑 (gomag) 已具備：

```
[前台]
✅ gomag 設計系統（橘紅 #FF5A36 + 4 階文字 + Noto Sans TC + Manrope）
✅ 4 城市落地頁（DB 驅動 SEO 文案）
✅ 店家頁兩種樣式：blocks 客戶用 g-store-hero / 其他用 m-*
✅ 5 種 block type 完整實作 + 真實 demo 資料

[後端]
✅ store_blocks 通用表（取代散落的 services/cases/faqs）
✅ category_block_suggestions（12 分類 → block 建議對映）
✅ cities 表（4 城 SEO 文案 DB 化）
✅ block_helpers.php API（render / save / delete）

[後台]
✅ 區塊管理（store_blocks list + edit + 上下移 + 啟用/停用 + 刪除）
✅ 5 種 block 表單（service / menu / portfolio / pricing / faq）
✅ 圖片上傳整合（每個 form 內建 <input type="file">）
✅ 城市管理（4 城 SEO 文案編輯）
✅ Sidebar 「📦 區塊管理 [NEW]」「🌏 城市管理 [NEW]」入口

[資料]
✅ 4 個示範客戶完整 demo 資料（橫跨 4 大分類）
✅ 207 家既有客戶向後相容（舊樣式繼續運作）
```

**整套系統已可 demo 給業務團隊看**。
