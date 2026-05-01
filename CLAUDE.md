# 店家好口碑（gomag.com.tw）— Claude Code Handoff

> **最後更新**：2026-05-02（Phase A + B + Polish 完成）

## 專案概觀

PHP 主站 + miniweb 子網域 + 後台管理。207 家在地客戶。Hostinger Premium + MariaDB。

- **本機開發**：`/Users/songmingwei/Sites/localhost/miniweb/`
- **MAMP**：`/Applications/MAMP/htdocs/miniweb` → symlink 到上面
- **Production (staging)**：`https://aqua-elephant-856571.hostingersite.com`
- **正式網域**（DNS 切換後）：`www.gomag.com.tw`
- **目前還在 demo 階段，尚未掛正式網域**

## SSH / Production 操作

```
Host: 145.79.14.161
Port: 65002
User: u331306067
Pass: !fX5vlQhlt9jIgi1   ← Reney 設的，做完事後建議 reset
Web root: /home/u331306067/domains/aqua-elephant-856571.hostingersite.com/public_html
```

部署用 rsync 即可。Backups 自動存在 `_backups/` 子目錄。

## 環境變數判斷

`includes/config.php` 用 `IS_LOCAL` / `IS_STAGING` / `IS_PROD` 三段切換。
- 本機：MAMP localhost:8889 / root / root
- Staging / Prod：u331306067_miniweb / `Mw2026_K8sP3zXq!`
- ⚠️ `includes/config.php` 已 .gitignore（含密碼），有 `config.example.php` 範本

## Git 狀態

```
9 commits 已上：
08c89e2 feat: Similar stores + 多 testimonials + 舊 admin deprecated banner
c1b5600 feat: Sticky sidebar 聯絡卡 + Reviews summary 五星分布條
3eda95c fix: CSS cache busting (?v={mtime})
3d59ec3 Demo Polish: 4 個示範店家頁完整對齊新設計
526724f Phase B Polish: 圖片上傳 + 示範客戶 + 城市後台
48f2252 Phase B 後台: Modular Blocks Admin UI
8b106a4 Phase B: Modular Blocks 後端架構升級
06c7b4c docs: Phase A completion report + production screenshots
c7cf5e3 Initial commit + Phase A: gomag design system foundation
```

工作樹乾淨。沒 push remote（沒設 git remote）。

## 重要架構

```
/index.php           首頁（含分類入口、縣市入口、精選店家）
/category.php        分類列表 (/category/{slug})
/city.php            縣市落地頁 (/city/{slug})  ⭐ 從 cities 表 DB 驅動
/store.php           店家頁 ⭐ 雙寫期：useBlocks 客戶用 g-* 新樣式 / 其他用 m-*
/sitemap.php         動態 sitemap.xml
/.htaccess           URL rewrite
/admin/              後台
  /pages/store_blocks.php    ⭐ 區塊管理主列表 (新)
  /pages/store_block_edit.php ⭐ 編輯區塊（5 form partials）
  /pages/cities.php          ⭐ 城市管理（取代寫死 array）
  /pages/services.php / cases.php / faqs.php  ← 加 deprecated banner
  /forms/form-{type}.php     ⭐ 5 種 block 表單
  /includes/deprecated_banner.php  ⭐ 舊 admin 引導
/blocks/             ⭐ 5 個 partial: service / menu / portfolio / pricing / faq
/includes/
  block_helpers.php  ⭐ getStoreBlocks / renderBlock / saveStoreBlock
  config.php         ⚠️ gitignored (含密碼)
  config.example.php   範本
/main/layout_*       共用 head/foot ⭐ extraCss + cache busting
/assets/css/gomag.css ⭐ 新設計系統 (g-* prefix, 1278 行 / 32KB)
/migrations/         ⭐ 003-007 已跑（建表 + seed + data 遷移）
/_backups/           gitignored, 含 landing_extra_demo_clients_*.sql
```

## DB 架構

### 主表（207 客戶）
- `clients` — 既有，沿用為 stores（不另建表）
- `categories` — 12 分類
- `testimonials` — 評價（4 demo 共 20 筆）

### 新增表（Phase B）
- `store_blocks` — 通用區塊（5 type ENUM + JSON data + sort_order）
- `category_block_suggestions` — 12 分類 → block 建議對映 (31 條)
- `cities` — 4 城 SEO 文案（取代 city.php 寫死 array）

### 4 個示範客戶（已遷移到 blocks）
| ID | Slug | 分類 | Blocks |
|---|---|---|---|
| 1 | xusen | 居家服務 | service + faq |
| 3 | 062281421 | 美容美髮 | service + pricing + faq |
| 10 | happysteakcyi | 餐飲美食 | menu + faq |
| 18 | carbeauty2 | 汽車服務 | service + portfolio + pricing + faq |

其他 203 家走舊 services/cases/faqs fallback，視覺不變。

## SEO 縣市落地頁（Phase A 完成）

### URL & 對映
```
/city                  全縣市總覽
/city/{slug}           單一縣市頁

slug 對映 (寫死在 city.php $cityMap)：
tainan / kaohsiung / chiayi / taichung / taipei / newtaipei /
taoyuan / taitung / pingtung / hsinchu / yilan / hualien
```

### 各縣市現況
- 🟢 台南市 155 家（正式）
- 🟢 高雄市 18 家（正式）
- 🟢 嘉義市 5 家（正式）
- 🟢 台中市 5 家（正式 2 + placeholder 3）
- 🔴 其他縣市 < 3 家，sitemap 不收錄

### 含
- `CollectionPage` + `ItemList` JSON-LD
- `BreadcrumbList` JSON-LD
- 在地內文（避 doorway pages 懲罰）— 從 cities 表讀
- 按分類分群店家清單（核心 SEO 邏輯）
- B 端 banner + 大 CTA

## 設計系統 gomag.css

跟舊 `m-*` 並存（不破壞舊頁面）：
- `--g-accent` `#FF5A36`（橘紅）
- `--g-ink` 4 階文字
- Noto Sans TC + Manrope（數字/英文）
- 完整元件：Hero / Section / Store Card / Banner / CTA / Block (5 種) /
  Store Hero / Store Aside Sticky / Reviews Block / Similar Stores
- 完整 RWD（1024 / 640 breakpoints）

`main/layout_head.php` 自動加 `?v={filemtime}` cache busting。

## 已完成 Phase 對照

| Phase | 內容 | 報告 |
|---|---|---|
| A | gomag 設計系統 + city.php 重做 + Git init | docs/PHASE_A_REPORT.md |
| B | Modular Blocks 後端（DB + helper + 5 partial）| docs/PHASE_B_REPORT.md |
| B 後台 | 區塊管理 admin CRUD UI | docs/PHASE_B_ADMIN_REPORT.md |
| B Polish | 圖片上傳 + 示範客戶 + cities 後台 | docs/PHASE_B_POLISH_REPORT.md |
| Demo Polish | 4 demo 對齊 + portfolio + 清舊 landing | docs/DEMO_POLISH_REPORT.md |
| Cache Fix | CSS ?v=mtime | git c1b5600 |
| Sticky+Reviews | 右側 sticky sidebar + 五星分布條 | git 3eda95c |
| Polish 3 | Similar stores + 多 testimonials + deprecated banner | git 08c89e2 |

## 對齊 mockup 進度

`docs/gomag-store-detail.html` 的元素：
- ✅ Hero
- ✅ 5 種 Modular Blocks
- ✅ Sticky sidebar 聯絡卡
- ✅ Reviews Summary + 五星分布
- ✅ Similar Stores
- ❌ Photo gallery 5 格 (Phase C)
- ❌ Owner block (Phase C，需新欄位 owner_intro / owner_avatar)

**整體對齊度 95%**

## 待辦（明天可選）

按時間從少到多排：

| 工程 | 時間 | 備註 |
|---|---|---|
| Drag-drop 排序（admin block list 用 SortableJS）| 1-2 hr | UX 加分 |
| Block 即時預覽 iframe（編輯不用儲存就能看效果）| 4-6 hr | 高 UX |
| 人工遷移更多客戶到 blocks | per-client 5-15 min | 增加 demo |
| **Phase D**: city.php 加本週熱門 / 真實口碑 / 最新加入 | 2-3 天 | city 頁更豐富 |
| **Phase C**: store.php photo gallery 5 格 + owner block | 3-5 天 | 95% → 100% mockup |
| Phase 6 DNS 切換到 gomag.com.tw | 視旭浪而定 | 等你決定時機 |

### 風險低的小事
- 標 `services.php`/`cases.php`/`faqs.php` 從 sidebar 隱藏（讓內勤一定走新 admin）
- Sitemap 新增 `/store/{slug}` 個別 URL
- Reviews summary 標 demo 資料（在 testimonials.source='demo' 那筆顯示「Demo 範例」）

## Hostinger File Manager API（備援部署用）

```js
const jwt = localStorage.getItem('jwt');
const prefix = location.pathname.split('/files/')[0];
fetch(prefix + '/api/resources/public_html/{path}?override=true', {
  method: 'POST',
  headers: { 'X-Auth': jwt, 'Content-Type': 'application/octet-stream' },
  body: bytes
})
```

但 SSH + rsync 比這個更穩，建議優先用 SSH。

## 慣用工作流

- **DB 修補/一次性 script** → migrations/00X_*.php，PHP CLI 跑或 web URL（含 `?key=` 防誤觸）
- **新功能開發** → 本機 MAMP 測 + 截圖驗證 + Production rsync 部署
- **Cache 問題** → 已自動加 `?v={mtime}` 到 extraCss link，不用手動處理
- **Deploy 前** → 自動備份至 `_backups/{name}_YYYYMMDD-HHMMSS/`

## 風格慣例

- 中文字使用「台」不用「臺」（DB 內部統一過）
- 註解中文 OK
- 行內 style 大量使用（CSS class 為輔）— 但新 g-* 系統優先用 class
- 遵循 traditional-chinese-writing skill 的台灣繁體規範

## 客戶/品牌

- 主站：店家好口碑
- 域名：gomag.com.tw
- 老闆 reney.sung@gmail.com
