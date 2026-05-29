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
- main 與 origin/main 同步，最新工作直接 commit 到 main（2026-05-27 收斂完成）

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

### 子服務 IA 新增表（2026-05-21，見「子服務 IA」段）
- `geo_category_pages` — 城市×分類×**子服務**交叉頁內容（intro_html + faqs JSON + meta + `service_slug`/`service_name`）。`service_slug=''`＝大分類樞紐層；填值＝子服務頁。unique key `(city_slug, category_id, service_slug)`。
- `service_keywords` — 關鍵字池（`category_id, slug, name, page_slug, sort_order, is_active`，注意排序欄是 sort_order）。`page_slug=''`＝獨立頁；填值＝同義詞折進該頁（避 doorway）。
- `client_service_keywords` — 店家 ⇄ 關鍵字 多對多（店家標 ~3 組）。
- `guides` — 攻略文（`slug, title, body_html, city_slug, category_id, status, published_at`）；城市專屬優先。
- `clients.city_slug` — 從地址 deriveCitySlug() 推導（2026-05-21 修郵遞區號/台灣前綴 bug，21 家歸位）。

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

### 各縣市現況（2026-05-21 city_slug 修正後）
- 🟢 台南市 171 家（正式；修 city_slug 前顯示 155）
- 🟢 高雄市 23 家
- 🟢 嘉義市 8 家
- 🟢 台中市 5 家
- 🟡 屏東 2 / 台北 1 / 桃園 1 / 新北 1 / 台東 1（< 3 家，sitemap 城市頁不收錄）
- 彰化＝內容型縣市（0 店，靠交叉頁內容撐）

### 含
- `CollectionPage` + `ItemList` JSON-LD
- `BreadcrumbList` JSON-LD
- 在地內文（避 doorway pages 懲罰）— 從 cities 表讀
- 按分類分群店家清單（核心 SEO 邏輯）
- B 端 banner + 大 CTA

## 子服務 IA（2026-05-21 大改版，migrations 022–043）

**核心概念**：4 層 IA — 城市 › 大分類 › **子服務（關鍵字）** › 店家。
大分類（居家服務）太籠統，SEO 關鍵字在子服務層（清潔/木地板/水電…）。消費者也搜「台南清潔」「台中木地板」而非「台南居家服務」。

### URL / 路由（.htaccess）
```
/city/{c}                       city.php       縣市頁
/city/{c}/{cat}                 citycat.php    大分類「樞紐頁」（列子服務標籤 + 該分類店家）
/city/{c}/{cat}/{svc}           citycat.php?svc=  子服務頁（內文+FAQ+店家+攻略）
/guide /guide/{slug}            guide.php      攻略文
```

### citycat.php 行為
- `svc` 空＝樞紐頁（大分類列店）；`svc` 設＝子服務頁。子服務頁需有 geo 內容列，否則 404。
- **子服務頁店家清單＝依關鍵字標籤 JOIN**（`client_service_keywords` + `service_keywords`，含同義 `page_slug` 折入），不是大分類過濾。
- **0 店空狀態**：子服務頁有內容但 0 店 → 顯示 B2B「歡迎上架」（自動切換）。
- **攻略查詢**：`(category 相符或通用) AND (city 相符或通用)`，城市專屬優先（避免跨分類外溢）。
- 麵包屑 5 層、JSON-LD（CollectionPage/ItemList/BreadcrumbList/FAQPage）依層級切換。

### 後台
- `admin/pages/geo_category.php` — 交叉頁內容（加子服務 slug/名稱欄）
- `admin/pages/service_keywords.php` — ⭐ 關鍵字池 per 分類 CRUD（page_slug 同義詞）
- `admin/pages/settings.php` — 店家編輯加「服務關鍵字」勾選（軟上限 3）→ client_service_keywords
- `admin/pages/guides.php` — 攻略文 CRUD

### 已建關鍵字池（9 分類，migrations 023/027/030/032/043）
- 居家服務：清潔/室內設計/木地板/油漆防水/除蟲/系統廚具/窗簾/冷氣/沙發床墊/監視器弱電/驗屋
- 餐飲美食：火鍋/燒肉/牛排/日式/韓式/海鮮/麵食/甜點/咖啡/西餐/婚宴餐廳/素食/吃到飽/中式（+同義詞）
- 健康醫療：中醫診所/皮膚科/醫學美容/醫事檢驗
- 汽車服務：包膜/鍍膜/汽車美容/保養維修/音響/大燈/駕訓
- 美容美髮：美髮/美甲/美睫/紋繡霧眉/臉部護膚/SPA/除毛/新娘秘書（+剪染燙/光療/飄眉同義詞）
- 專業服務：會計記帳/法律/代書地政/保險/廣告設計/商業攝影/印刷/徵信
- 教育學習：升學補習/美語/才藝/音樂/安親課輔/美術/家教/數理
- 旅宿住宿：民宿/飯店旅館/包棟Villa/露營區
- 零售購物：（043 已建池）
- 關鍵字池只是建好「可標」，內容頁（geo_category_pages）要另寫；目前已寫內容的子服務頁集中居家/餐飲/健康/汽車。

### 現況（2026-05-21）
- 子服務內容頁 ~33（台南最多：居家5+餐飲6+中醫1+婚宴；高雄餐飲3+室內設計+婚宴+驗屋；台中清潔/木地板/室內設計/驗屋/婚宴；嘉義包膜/鍍膜；彰化清潔）。
- 攻略文 19 篇（3 通用 + 16 城市專屬），全進 sitemap。
- **內容紀律**：每篇/每頁城市在地差異化（地名/重劃區/行情）避 doorway；行情數字人工確認；0 店靠內容撐排名（sanfeng）。**重質不重量、觀察 Google 收錄再續灌**。
- BASE_URL 修正：prod config.php BASE_URL 補 www（內部連結與 canonical 一致，少一跳 301）。

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
| **後台安全 v2** | session secure cookie + Admin URL 隱藏 (magic cookie) + login rate limit + DB 密碼輪替 + secrets 移出 webroot | git 8268448 |
| **視覺模板 Phase 3** | `clients.store_template` 欄位 + `templates/store/<slug>/` 架構 + `template_loader.php` + 後台模板選擇 UI（卡片＋推薦） | git b79f95d + 0c3753f |
| **japan-minimal 模板** | 朱紅金箔米白「和風老舖」第一個客製模板（奧喜 id=220 在用），含 8 sections + city variant 整合 + process_gallery 製作圖庫 | git 0c3753f |
| **奧喜內容稽核** | 「不能無中生有」原則確立；主頁 + 5 城市內頁全部用 banshin.com.tw 認證事實重寫；testimonials 改真實部落客 quote 含 source_url | git 9eb75f8 |

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
| ~~Drag-drop 排序~~ | ✅ git b249942 | 已完成 |
| ~~Block 即時預覽 iframe~~ | ✅ git 8ee401a | 已完成 |
| 人工遷移更多客戶到 blocks | per-client 5-15 min | 增加 demo |
| ~~main 分支推進~~ | ✅ 2026-05-27 收斂完成 | |
| 全站搜尋（跨城市）`/search.php?q=` | 1 天 | 現在只有城市內搜尋，全站搜尋是自然延伸 |
| 熱門搜尋詞變 SEO 著陸頁 | 視 routine 報告 | 等累積數週 data 再評估 |
| 完全沒結果關鍵字 → redirect 到分類頁 | 視 routine 報告 | 同上 |
| Phase 6 DNS 切換到 gomag.com.tw | 視旭浪而定 | 等你決定時機 |
| **奧喜小官網 oishii.gomag.com.tw 客製版型** | 0.5-1 天 | 等 Reney 找日本網站設計參考 |
| **220 家客戶內容稽核（無中生有）** | 2.5 hr + 客戶等待 | 等客戶投訴/GSC 警示再啟動，高風險清單已記在 memory |

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
