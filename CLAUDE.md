# 店家好口碑（gomag.com.tw）— Claude Code Handoff

> **最後更新**：2026-05-20（多縣市 IA 落地 1–6 完成並上線正式站）

## 專案概觀

PHP 主站 + miniweb 子網域 + 後台管理。207 家在地客戶。Hostinger Premium + MariaDB。

- **本機開發**：`/Users/songmingwei/Sites/localhost/miniweb/`
- **MAMP**：`/Applications/MAMP/htdocs/miniweb` → symlink 到上面
- **Staging（測試區）**：`https://aqua-elephant-856571.hostingersite.com` — 平常先部署/驗證這裡
- **Production（正式公開站，已上線）**：`https://www.gomag.com.tw`
- ⚠️ staging 與正式站是**同帳號下兩個獨立 docroot、共用同一個 DB**，兩份程式碼會各自分歧。部署＝staging 先驗 → 再 promote 到正式站（見下「部署紀律」）。

## SSH / Production 操作

```
Host: 145.79.14.161
Port: 65002
User: u331306067
Pass: <已移除明文，請 reset 後存到密碼管理器 / 1Password；勿再寫回此檔>
Web root (staging): /home/u331306067/domains/aqua-elephant-856571.hostingersite.com/public_html
Web root (正式站 www.gomag.com.tw): /home/u331306067/domains/gomag.com.tw/public_html   ← 含舊系統 062051129/ 子資料夾（勿動）
```

部署用 rsync 即可。Backups 自動存在 `_backups/` 子目錄。

**SSH 改走 key auth**（明文密碼已移除，勿再寫回）：
```
ssh -i ~/.ssh/id_ed25519_hostinger -o IdentitiesOnly=yes -p 65002 u331306067@145.79.14.161
```

### 部署紀律（2026-05-20 更新）
1. **兩個 docroot、同一個 DB**：staging（aqua-elephant）與正式站（gomag.com.tw）是兩份獨立程式碼、共用同一 DB → migration 跑一次兩邊都生效，promote 只需搬「程式碼」。
2. **流程**：本機 `main`（== staging）→ 部署 staging 驗證 → diff 正式站 vs staging → 備份正式站 → promote（覆蓋 app 檔）。**不碰** config.php / 舊系統 `062051129/` / `upload/` / `_backups/` / `_logs/`。
3. **部署前務必 diff**：staging 與正式站會各自分歧，直接整檔覆蓋會弄丟對方獨有的東西（教訓：`.htaccess` 正式站有 `/cases/(taichung|changhua)` 規則 staging 沒有 → 要外科手術只加新規則）。歷史見 memory `gomag-repo-divergence`、`gomag-deploy-infra`。
4. **覆蓋前先備份**到該 docroot 的 `_backups/<name>_YYYYMMDD-HHMMSS/`。
5. **box 上跑 DB CLI 腳本要前綴 `HTTP_HOST`**（staging 用 `aqua-elephant-856571.hostingersite.com`、正式站用 `www.gomag.com.tw`），否則 config.php 當成 local → 連 root/root 失敗。

## 環境變數判斷

`includes/config.php` 用 `IS_LOCAL` / `IS_STAGING` / `IS_PROD` 三段切換。
- 本機：MAMP localhost:8889 / root / root
- Staging / Prod：u331306067_miniweb / `<已移除明文，見 server 端 includes/config.php>`
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

**GitHub**：`reneysung/gomag-miniweb`（private）— `git push origin {branch}`
- main 分支可能落後當前工作分支，要看 `claude/sleepy-diffie-87db30` 才有最新

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
/_logs/              gitignored, 應用層日誌（目前只有 search.log）
/analytics/          搜尋分析 endpoint
  search.php         ?key={SECRET}&days=7&format=md|json — 讀 search.log 出報告
  index.php          直接 403（防 dir listing）
```

## 應用層日誌

**為什麼自寫**：Hostinger Premium shared 沒給 access log（`~/.logs/` 是空的），用 server log grep 是條死路。改用 PHP 自寫 TSV，更乾淨。

`_logs/search.log` 格式（每行 6 欄 tab 分隔）：
```
{ISO8601 ts}\t{城市中文名}\t{slug}\t{q 關鍵字}\t{result_count}\t{ip_hash}
```
- `ip_hash` 是 `sha1(ip + 當日日期)` 前 10 char — 跨日就匿名，同日同人可去重
- city.php 的 `?q=` 觸發即 `file_put_contents(..., FILE_APPEND | LOCK_EX)`

**讀取**：透過 `analytics/search.php?key=...` HTTP endpoint（secret 寫死在檔案 const 裡）。

## 週分析 Routine（雲端）

- Routine ID: `trig_016eD7EhaW3degQzWrRzaNgD`
- 名稱：「gomag 週搜尋分析」
- 週期：`0 1 * * 1`（每週一 UTC 01:00 = 台北 09:00）
- 行為：curl analytics endpoint → 把 markdown 報告 + 行動建議貼在 routines page
- 檢視：https://claude.ai/code/routines/trig_016eD7EhaW3degQzWrRzaNgD
- secret key 寫在 routine prompt 裡 — rotate 時要同時改 `analytics/search.php` 的 `SEARCH_LOG_SECRET` 跟 routine prompt

## 每日內容 Routine（雲端，2026-05-20 建）

- Routine ID: `trig_018NsW31y3EZmHXcBB9KMax9`
- 名稱：「gomag 每日交叉頁內容草稿」
- 週期：`0 1 * * *`（每天 UTC 01:00 = 台北 09:00）
- 行為：照內建優先清單產 **1 篇** sanfeng 城市×分類在地內容**草稿**（intro_html + 5 FAQ + meta），貼在 routines page；**不寫 DB、不部署**。
- Reney 流程：複製 → 後台「🧭 交叉頁內容」建立該城市×分類 → 貼上 → 改行情 → 啟用。
- 刻意「草稿不自動上線」：避 Google scaled-content 懲罰 + 行情數字要人工確認；重質不重量、一天 1 篇。
- 檢視：https://claude.ai/code/routines/trig_018NsW31y3EZmHXcBB9KMax9

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
  Store Hero / Store Aside Sticky / Reviews Block / Similar Stores /
  Cat Pill Nav (sticky) / Hero Search / Search Banner / Explore Cards
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
| C | Photo Gallery 5 格 + Owner Block + SSH key auth | git 54724e8 |
| C admin | settings.php 加 Owner Block + Photo Gallery 編輯 UI | git 91a2c44 |
| D Day 1 | city.php 加 本週熱門 / 最新加入 / 真實口碑 | git 06450aa |
| D Day 2 | city.php hero 600px + 依分類探索 cards + 分類 anchor | git da15ffb |
| D Day 3 | sticky 分類 nav + hero 搜尋列 + ?q= 過濾 + 滾動 active pill | git d432b4a |
| D Day 3.1 | 應用層搜尋日誌 + analytics endpoint + GitHub repo + 週分析 routine | git 076e84c |

## 對齊 mockup 進度

`docs/gomag-store-detail.html` 的元素：
- ✅ Hero
- ✅ 5 種 Modular Blocks
- ✅ Sticky sidebar 聯絡卡
- ✅ Reviews Summary + 五星分布
- ✅ Similar Stores
- ✅ Photo gallery 5 格 (Phase C)
- ✅ Owner block (Phase C)

**整體對齊度 100%**（store 頁）／ city 頁約 98%（含 hero 大圖、依分類探索、本週熱門、最新加入、真實口碑）

## 待辦（明天可選）

按時間從少到多排：

| 工程 | 時間 | 備註 |
|---|---|---|
| Drag-drop 排序（admin block list 用 SortableJS）| 1-2 hr | UX 加分 |
| Block 即時預覽 iframe（編輯不用儲存就能看效果）| 4-6 hr | 高 UX |
| 人工遷移更多客戶到 blocks | per-client 5-15 min | 增加 demo |
| 把 main 分支推進到工作分支 | 5 min | 目前 main 落後（沒走 PR review 流程，可直接 merge） |
| 全站搜尋（跨城市）`/search.php?q=` | 1 天 | 現在只有城市內搜尋，全站搜尋是自然延伸 |
| 熱門搜尋詞變 SEO 著陸頁 | 視 routine 報告 | 等累積數週 data 再評估 |
| 完全沒結果關鍵字 → redirect 到分類頁 | 視 routine 報告 | 同上 |
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
