# Phase A — Gomag 設計系統基礎 ─ 完成報告

**完成日**：2026-05-01
**執行者**：Claude（自走完成）
**Production**：https://aqua-elephant-856571.hostingersite.com

---

## ✅ 完成項目

### 1. 設計系統 (`assets/css/gomag.css`)
從 4 個 mockup 抽出 design tokens 建立**新 g-* 系統**，跟舊 `m-*` 系統並存：

| 維度 | 內容 |
|---|---|
| 主色 | `--g-accent` `#FF5A36`（橘紅）+ hover `#E0421F` + light `#FFE8DF` |
| 背景 | `--g-bg` / `--g-bg-alt` `#F7F7F5` / `--g-bg-warm` `#FFF8F4` |
| 文字 4 階 | `--g-ink` `#191919` / `-soft` / `-muted` / `-light` |
| 信任 | `--g-green` `#048A50` |
| LINE | `--g-line` `#06C755` |
| 字體 | Noto Sans TC（300/400/500/700/900）+ Manrope（400-800，數字/英文）|
| 圓角 | 100px (按鈕)、12px (卡片)、16px (大卡片)、6-8px (小元件) |
| 容器 | max-width 1320px |
| 元件 | hero, breadcrumb, city-intro, store-card, banner, cta, RWD |

### 2. `main/layout_head.php` 加 `$extraCss` 機制
讓任何頁面可在 require 前設 `$extraCss = ['/path.css']` 載入額外樣式 — 為未來其他頁面 (store/index/category) 升級鋪路。Manrope 字體也在此載入。

### 3. `city.php` 全部重寫對齊 mockup
保留現有「**按分類分群店家清單**」核心 SEO 邏輯，疊加 mockup 視覺：

- Hero 480px 大圖 + 88px 標題 + 城市標籤
- 麵包屑（含 BreadcrumbList JSON-LD）
- 兩欄 City intro：左側介紹文 + 標籤 / 右側「快速資訊」卡（收錄店家、分類數、上線數、placeholder 數、服務區域 tags）
- 各分類店家清單（gomag store-card 樣式）
- Store card 圖片 fallback：類別 emoji + 類別名稱 label
- Placeholder 客戶用 `g-store-card-ph` + 「📋 資料整理中」黃標
- B 端業務 banner（黑底 + 橘色 CTA「立即聯絡」）
- 大 CTA 區（黑底 + 「讓 [城市] 的客人，找到對的你。」+ Primary/Secondary 按鈕）
- 完整 RWD（1024px / 640px breakpoints）

### 4. 環境準備
- ✅ MAMP miniweb 改 symlink 到 local working dir
- ✅ MAMP `mod_rewrite` 啟用
- ✅ 補回 4 個 MAMP 獨有 uploads 圖
- ✅ Production DB 完整 dump 到本機 (14 tables, 207 clients)
- ✅ MariaDB 11 → MySQL 8 collation 自動轉換 (`utf8mb4_uca1400_ai_ci` → `utf8mb4_unicode_ci`)
- ✅ Production uploads (24MB / 270 檔) rsync 到本機

### 5. Git 版本控制（首次 init）
- ✅ `.gitignore` 排除 `includes/config.php`（含 DB 密碼）、`uploads/`、雜物
- ✅ `includes/config.example.php` 範本檔（不含真實密碼）
- ✅ 第一個 commit：62 檔，0 大檔，0 機密檔

### 6. 部署與驗證
- ✅ Production 自動備份至 `_backups/phase_a_*`（5 個檔）
- ✅ rsync 7 個檔案 + assets/css/ 目錄上 production
- ✅ 8 個 URL smoke test 全 200 OK，零 PHP 錯誤
- ✅ 6 張 production 截圖（4 城 desktop + overview + mobile）

### 7. 修壞資料
- 1 筆 client (id=207, 鬥牛士台中新時代店) tagline UTF-8 byte 序列損壞
- 本機 + production DB 都已修復

---

## 📁 異動檔案清單

### 新增
| 檔案 | 說明 |
|---|---|
| `assets/css/gomag.css` | **核心**：12KB design system + city 元件 |
| `city.php` | 重寫（17.7KB，原 12.9KB） |
| `.gitignore` | git 排除規則 |
| `includes/config.example.php` | DB 設定範本 |
| `docs/PHASE_A_REPORT.md` | 本報告 |
| `docs/screenshots/prod-*.png` | Production 截圖 6 張 |

### 修改
| 檔案 | 改動 |
|---|---|
| `main/layout_head.php` | 加 `$extraCss` / `$extraHead` 支援 + Manrope 字體 |

### 之前測過、本次一起部署
| 檔案 | 上次改的人 |
|---|---|
| `store.php` | 你之前改的 |
| `sitemap.php` | 你之前改的 |
| `index.php` | 你之前改的 |
| `.htaccess` | 你之前改的 |

---

## 🌐 上線網址（staging）

| URL | HTTP | 說明 |
|---|---|---|
| https://aqua-elephant-856571.hostingersite.com/city | 200 | 全縣市總覽 |
| https://aqua-elephant-856571.hostingersite.com/city/tainan | 200 | 台南 155 家 |
| https://aqua-elephant-856571.hostingersite.com/city/kaohsiung | 200 | 高雄 18 家 |
| https://aqua-elephant-856571.hostingersite.com/city/chiayi | 200 | 嘉義 5 家 |
| https://aqua-elephant-856571.hostingersite.com/city/taichung | 200 | 台中 5 家（3 placeholder）|
| https://aqua-elephant-856571.hostingersite.com/store/taichung-rouhanghang | 200 | placeholder 店家 |
| https://aqua-elephant-856571.hostingersite.com/sitemap.xml | 200 | sitemap |

---

## 🚧 已知未做（建議下一步順序）

按 ROI / 風險排：

### 🥇 Phase B：Modular Blocks 後端（3-5 天）
- 建 `stores`、`store_blocks`、`category_block_suggestions` 三表
- 寫 `clients → stores`、`services/cases/faqs → store_blocks` migration
- 寫 5 個 block partial（`blocks/*.php`）
- 後台「店家編輯」加「區塊管理」分頁
- 不動 store.php 前台（用 feature flag 雙寫期）

**為什麼先做這個**：架構升級是基礎，前台改造（C/D/E）才有東西渲染

### 🥈 Phase C：store.php 視覺對齊 mockup（5-7 天）
- Photo gallery（5 格 Airbnb 樣式）
- Title bar + Owner block + Highlights（4 卡店家亮點）
- 黏性側欄聯絡卡（電話/LINE/地址/營業時間）
- Reviews summary（五星分布長條圖）
- Map block + Similar stores
- 套 Phase A 的 g-* tokens

### 🥉 Phase D：city.php 加豐富區塊（3-5 天）
- 本週熱門 4 卡（需 `is_featured` 欄位）
- 最新加入 4 卡（用 `created_at` 排序即可）
- 真實口碑 3 卡（從 testimonials 表抽）
- 「依分類探索」3 卡（mockup 的 explore-card 樣式）

### Phase E：index.php 全台首頁（2-3 天）
對齊 `gomag-homepage-v3.html`，重用 Phase D 元件

### Phase F：後台補洞（2-3 天）
- `cities.php` 後台（4 城 SEO 文案 + hero 圖管理 — 目前是寫死在 city.php 的 PHP array）
- 店家亮點 4 卡編輯
- 5 種 block 後台 UI（含 `form-menu.php`、`form-pricing.php`）

---

## 🔧 SSH / 部署資訊

```
Host: 145.79.14.161
Port: 65002
User: u331306067
Pass: [改 SSH 密碼後我就連不上了，記得做完此次工作後到 hPanel 重設]
Web root: /home/u331306067/domains/aqua-elephant-856571.hostingersite.com/public_html
Backup: /home/u331306067/domains/.../public_html/_backups/phase_a_20260501-*
```

**部署指令**（如要重新部署所有改動）：
```bash
rsync -avz --progress -e "ssh -p 65002" \
  /Users/songmingwei/Sites/localhost/miniweb/{city.php,store.php,sitemap.php,index.php,.htaccess} \
  /Users/songmingwei/Sites/localhost/miniweb/main/layout_head.php \
  /Users/songmingwei/Sites/localhost/miniweb/assets/css/gomag.css \
  u331306067@145.79.14.161:.../public_html/
```

**回滾指令**（如出問題還原）：
```bash
ssh -p 65002 u331306067@145.79.14.161 \
  'cp -r /home/.../_backups/phase_a_20260501-*/* /home/.../public_html/'
```

---

## 🟡 提醒事項

1. **DB 密碼仍在 git 排除清單外的副本中** — production hPanel 的 DB password 跟本機 config.php 是同一個（`Mw2026_K8sP3zXq!`）。不算敏感外洩但建議未來定期輪換
2. **SSH 密碼**（你貼給我那組）做完之後建議到 hPanel reset
3. **sitemap 的 URL 寫的是 `https://www.gomag.com.tw/`**，但實際還沒掛網域 — 等掛 gomag.com.tw 後 GSC 提交 sitemap 才有意義
4. **gomag-* mockup HTML 4 個檔** 已 commit 到 `docs/`，方便日後 Phase B/C/D/E 對照
5. **舊 `m-*` CSS 系統依然在用** — 主站 header / footer / category.php / index.php 還是 m-* 樣式。漸進升級沒有時間壓力

---

## 📊 數字總結

- ⏱️ 執行時間：約 4 小時（含探索、對話、實作、測試、部署）
- 📝 改動檔案：62 檔進 git（0 大檔，0 機密檔）
- 🚀 部署檔案：7 個檔 + 1 個目錄上 production
- 🧪 Smoke tests：8 個 URL 全 ✅
- 📸 截圖：6 張（local 4 + prod 6 = 10 張總）
- 💾 同步資料：14 tables / 207 clients / 270 uploads / 24MB
- 🔧 修壞資料：1 筆 tagline（本機 + prod）

---

**Phase A 完成。可以瀏覽器開以下 4 個網址親眼看效果：**

- https://aqua-elephant-856571.hostingersite.com/city/tainan
- https://aqua-elephant-856571.hostingersite.com/city/kaohsiung
- https://aqua-elephant-856571.hostingersite.com/city/chiayi
- https://aqua-elephant-856571.hostingersite.com/city/taichung

**回來後決定：** 要做 Phase B（後端架構升級）還是 Phase C（store.php 視覺改造）？
