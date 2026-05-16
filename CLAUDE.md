# 店家好口碑（gomag.com.tw）— Claude Code Handoff

> **最後更新**：2026-05-16（Phase 1-6B 旭森風 SEO 全面改造完成）

## 專案概觀

PHP 主站 + mini-site 子網域 + 後台管理。207 家在地客戶。Hostinger Premium + MariaDB。

- **本機開發**：`/Users/songmingwei/Sites/localhost/miniweb/`
- **MAMP**：`/Applications/MAMP/htdocs/miniweb` → symlink 到上面
- **Staging**：`https://aqua-elephant-856571.hostingersite.com`（aqua-elephant docroot）
- **Production**：`https://www.gomag.com.tw`（gomag.com.tw 獨立 docroot，**已正式上線**）
- **GSC**：Domain Property `gomag.com.tw` 已驗證（TXT token `I70v7Z8E_1A7n_K5KqI1UVBBK4Fpy-GeDIxGwn09Dbc`），涵蓋所有子網域

## SSH / Production 操作

SSH 用 key auth（config alias `hostinger-gomag` 已設定在 `~/.ssh/config`）：

```
Host hostinger-gomag
  HostName 145.79.14.161
  Port 65002
  User u331306067
  IdentityFile ~/.ssh/id_ed25519_hostinger
```

**主機上有 3 個獨立 docroot**：
1. `/home/u331306067/domains/gomag.com.tw/public_html/`  ← 正式 prod ⭐
2. `/home/u331306067/domains/aqua-elephant-856571.hostingersite.com/public_html/`  ← staging
3. `/home/u331306067/domains/writer.wmf.com.tw/public_html/`  ← 其他

**DB 是兩 docroot 共享**（u331306067_miniweb / `Mw2026_K8sP3zXq!`）。
**部署流程**：先 rsync staging 驗證 → 再 rsync prod docroot → 兩 docroot 都備份在 `_backups/`。

### 內嵌 docroot：062051129/

主 docroot 底下有 `062051129/`（CodeIgniter 子系統）= 旭浪清潔舊官網，
被 wildcard subdomain `062051129.gomag.com.tw` 路由到。完全獨立的 CI 框架，
不走 mini-site 系統。已有 SEO schema 強化（Phase 4）。

## 環境變數判斷

`includes/config.php` 用 `IS_LOCAL` / `IS_STAGING` / `IS_PROD` 三段切換。
- 本機：MAMP localhost:8889 / root / root
- Staging / Prod：u331306067_miniweb / `Mw2026_K8sP3zXq!`
- ⚠️ `includes/config.php` 已 .gitignore（含密碼），有 `config.example.php` 範本

## Git 狀態

`git log --oneline -20` 看最新。**GitHub**：`reneysung/gomag-miniweb`（private）— `git push origin {branch}`。
main 分支可能落後當前工作分支，看 `claude/*` 系列才是最新。

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

## DB 架構

### 主表（207 客戶）
- `clients` — 既有，沿用為 stores
  - **2026-05-16 新增 7 欄**（SEO schema）：`latitude`, `longitude`, `address_street`, `address_district`, `address_region`, `postal_code`, `opening_hours_json`
- `categories` — 12 分類
- `testimonials` — 評價
- `services` — 服務（每個 client N 個）⭐ 含 `slug` 欄位（2026-05-16 回填中文 slug）
- `cases` — 案例 ⭐ 含 `slug` 欄位（2026-05-16 加 migration 014）
- `service_faqs` — 服務 FAQ
- `client_social` — 社群（FB/IG/YT/LINE）

### Modular Blocks 系統表
- `store_blocks` — 通用區塊（5 type ENUM + JSON data + sort_order）
- `category_block_suggestions` — 12 分類 → block 建議對映 (31 條)
- `cities` — 4 城 SEO 文案（取代 city.php 寫死 array）

### 5 個 demo 客戶（has_minisite=1，2026-05-16 現況）
| ID  | Slug          | 品牌                  | 分類       | 資料源 |
|---  |---            |---                    |---        |---     |
| 210 | fulldemo      | 展示清潔工坊          | 居家服務   | services(3) + cases(2) |
| 211 | fooddemo      | 築炎日式燒肉酒場      | 餐飲美食   | services(3) + cases(2) |
| 212 | designdemo    | 衡作室內設計事務所    | 室內設計   | services(4) + cases(2) |
| 213 | artru         | 亞筑室內設計有限公司  | 室內設計   | services(4) + cases(2) |
| 214 | lanhung       | 聯漢室內設計工作室    | 室內設計   | services(6) + cases(2) |

加 happysteakcyi (id=10) 還在運作 has_minisite=1，但走 modular blocks（menu + faq blocks，不是 services 表）。

**xusen (id=1) 已 2026-05-16 撤下**：`is_active=0`、`has_minisite=0`、加進 `getDuplicateSkipSlugs()`。
xusen.gomag.com.tw 與 /store/xusen 都 301 到 062051129.gomag.com.tw（旭浪舊系統）。

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
| **SEO 1** | **xusen 撤下 + 5 項 SEO 必修**（duplicate canonical / cache / sitemap-by-host / schema image / force www） | `99ab085` `a4ccd70` `5655169` `fddd5c9` |
| **SEO 1.5** | **Admin UI + Migration 012 (lat/lng/PostalAddress 4 欄/opening_hours_json)** | `085a261` |
| **SEO 2** | **Mini-site `/services/{slug}` × 20 頁** (Service schema + 3 層 Breadcrumb) | `a304373` |
| **SEO 3** | **Mini-site `/cases/{slug}` × 10 頁** (Article schema, migration 014 加 cases.slug) | `8d29d97` |
| **SEO 4** | **旭浪 062051129 CodeIgniter schema 升級** (WebSite/LocalBusiness 完整/Organization) | `36b0681`（doc）+ 直接改 062051129/application/views/all_head.php |
| **SEO 5** | **主站 Organization + WebSite SearchAction + /search pretty URL** | `16bd2c1` |
| **SEO 6B** | **餐飲 Restaurant + Menu schema + 全 client 自動換 schema 子型** | `2a4014f` |

📖 詳細 SOP：[`docs/SEO_XUSEN_REFERENCE.md`](docs/SEO_XUSEN_REFERENCE.md)

## SEO 架構（2026-05-16 後）

### URL pattern（per mini-site 子網域）
- `https://{sub}.gomag.com.tw/`         首頁（WebSite + SearchAction schema）
- `https://{sub}.gomag.com.tw/services`  服務列表
- `https://{sub}.gomag.com.tw/services/{slug}` ⭐ 服務詳細頁（Service + FAQPage schema）
- `https://{sub}.gomag.com.tw/cases`     案例列表
- `https://{sub}.gomag.com.tw/cases/{slug}`    ⭐ 案例詳細頁（Article schema）
- `https://{sub}.gomag.com.tw/testimonials`    客戶評價
- `https://{sub}.gomag.com.tw/sitemap.xml`     子網域專屬 sitemap（只列該子網域 URL）
- `https://{sub}.gomag.com.tw/robots.txt`      指向同 host sitemap

### Schema 自動換子型（依 industry 推斷）
| industry 含關鍵字 | @type |
|---|---|
| 餐 / 食 / 料理 / 牛排 / 火鍋 / 咖啡 | **Restaurant** + hasMenu |
| 美容 / 美甲 / 紋繡 | BeautySalon |
| 美髮 / 理髮 | HairSalon |
| 汽車 / 機車 / 烤漆 | AutomotiveBusiness |
| 室內設計 / 裝潢 / 清潔 / 水電 | HomeAndConstructionBusiness |
| 其他 | LocalBusiness |

### 主站
- 首頁：Organization + WebSite SearchAction（target `/search?q=`）
- 非首頁：Organization
- `/store/{slug}` 對 has_minisite=1 客戶 → canonical 指向 mini-site（避免 duplicate content）

### .htaccess routing 順序（重要）
1. Force www（non-www → www 301）
2. xusen.gomag.com.tw → 062051129 301
3. Mini-site 子網域路由（排除 www / 062051129 / xusen）
   - `/services/{slug}` → site/service_detail.php
   - `/cases/{slug}` → site/case_detail.php
   - `/services|cases|testimonials|contact` → site/{$1}.php
4. 主站 pretty URL（/store /category /city /search /sitemap.xml /robots.txt）

## 對齊 mockup 進度

`docs/gomag-store-detail.html` 的元素 100% 完成。

## 待辦（下次 session 可挑）

| 工程 | 時間 | 備註 |
|---|---|---|
| 確認旭浪電話、改 `062051129/application/views/all_head.php` 的 `$_xl_phone` | 5 min | hardcode 07-359-6601 待修 |
| 進 admin 幫 5 個 demo 填細欄位（lat/lng/街道/區/postal_code/opening_hours） | per client 5 min | 讓 PostalAddress / Geo / Hours schema 真正顯示 |
| Phase 7：部落格/專欄 `/column/{slug}` (旭森有) | 1.5-2 hr 架構 + 內容投資 | 內容型 SEO，長期戰略 |
| 旭浪 062051129 加 admin metadata model 欄位（電話/地址動態化） | 1 hr | 不再 hardcode，讓老闆能後台改 |
| Drag-drop 排序 / Block 即時預覽 | 1-6 hr | UX 加分 |
| 熱門搜尋詞變 SEO 著陸頁 | 視 routine 報告 | 等累積數週 data 再評估 |

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

- **DB 修補/一次性 script** → migrations/00X_*.php 或 .sql，PHP CLI 跑（PHP CLI 模式需要塞 `$_SERVER['HTTP_HOST']` 才能讓 config.php 走對的環境分支）
- **新功能開發** → 本機 MAMP 測 + 截圖驗證 → rsync staging（aqua-elephant）驗證 → rsync prod（gomag.com.tw）
- **兩 docroot 同步部署**：`rsync -avR ./{paths} hostinger-gomag:/home/u331306067/domains/{domain}/public_html/`
- **Cache 問題** → 已自動加 `?v={mtime}` 到 extraCss link，不用手動處理
- **Deploy 前** → mkdir _backups/{name}_YYYYMMDD-HHMMSS/ + cp 受影響檔案

### PHP CLI 跑 migration / 驗證 pattern

```php
<?php
$_SERVER['HTTP_HOST'] = 'www.gomag.com.tw';   // 或 '{sub}.gomag.com.tw' 測 mini-site
chdir('/home/u331306067/domains/gomag.com.tw/public_html');
require 'includes/config.php';
require 'includes/helpers.php';
$db = getDB();
// ...
```

## 風格慣例

- 中文字使用「台」不用「臺」（DB 內部統一過）
- 註解中文 OK
- 行內 style 大量使用（CSS class 為輔）— 但新 g-* 系統優先用 class
- 遵循 traditional-chinese-writing skill 的台灣繁體規範

## 客戶/品牌

- 主站：店家好口碑
- 域名：gomag.com.tw
- 老闆 reney.sung@gmail.com
