# 自架網站後台程式 — 功能規範（一套程式・三 tier）

> **一套後台程式，三個累加 tier**：基本（basic）⊂ 進階（advanced）⊂ 平台（platform）。
> 程式碼只有一套，各站掛哪一級就點亮哪些功能，開新站不 fork。
> **核心地基是 SEO**：這套後台存在的目的，是產出 SEO 正確的站。SEO 欄位在任何 tier、任何內容型別都是一級公民，由程式強制，不能繞過。
> 技術不限、只談功能。單一或少數內勤管理員使用，不做細緻角色權限（多租戶列在平台 tier）。
> 最後更新：2026-07-31

---

## 0. 架構總則

### 三 tier 對應站型

| tier | DB 值 | 對應站型 | 一句話 |
|---|---|---|---|
| 基本 | `basic` | 客戶官網（小官網）| 單一品牌，模板＋區塊組出來 |
| 進階 | `advanced` | 部落客內容站 | 大量文章、跨縣市跨主題、懶人包 |
| 平台 | `platform` | gomag 店家好口碑 | 多租戶、多店家、交叉頁 IA、搜尋分析 |

- 功能**累加**：低 tier 有的，高 tier 全繼承。
- 每個站一個 `site_tier` 欄位 + 細部功能 toggle。
- **模組程式碼永遠都在**，靠 tier + 開關決定該站看不看得到（符合「後台可調 > 寫死」）。
- 升級一個站＝改 tier + 點亮選單，不重寫。

### 讀法
- 級別：**●必備**／**○可選開關**／**✓繼承自低 tier**／**—不適用**。
- 每節結尾有「驗收條件」，能勾才算完成。

### 技術棧（定案・2026-07-31 校準版）

> 演進：① 先傾向沿用 PHP+Hostinger（沉沒成本）→ ② 從零發想改 headless（Astro + Supabase + 自建輕後台）→ ③ **本校準版**：把 CMS 換成 **Sanity（託管、免費）** 當基本/進階 tier 的後台，只有平台 tier 才自建 + Supabase。理由見下。既有站（gomag PHP、ivylife/漫途 CF+Supabase）**維持現狀不動**，此架構只給新站用。

**架構：headless（前後分離）。** 前台一律 SEO 最佳化；後台依 tier 分兩條路——多數站用託管 CMS 免自建，只有平台站自建。

| 層 | 選擇 | 為什麼 |
|---|---|---|
| 公開前台（所有 tier） | **Astro** on **Cloudflare Pages**；**基本 tier 走 SSG（純靜態）**、進階/平台走 SSR + 邊緣快取 | 最 SEO-first：head 全控、CWV 天生好。基本站內容穩定→靜態最快最耐（前台不碰後端，Sanity 閃一下網站照樣在線）；內容常更新的站→ SSR＝發佈即時、草稿可預覽 |
| 圖片（所有 tier） | **Cloudinary**（免費 tier） | 上傳即得任意尺寸/WebP，免自建壓縮 pipeline（避開 ivylife 圖片配額雷）|
| 後台 — 基本 / 進階 | **Sanity**（託管、免費 20 席 / 1 萬文件） | 免自建後台 UI、免跑伺服器（無 always-on 攻擊面）、內勤有專業編輯器 |
| 後台 — 平台（gomag） | **自建輕後台 + Supabase Pro** | 重關聯 join / 搜尋分析 / 多租戶市集，文件型 CMS 扛不動，Postgres 才對 |

**為什麼後台分兩條路（先前「不用 CMS」的修正）：**
- 先前否決 CMS 的主因是「自架 CMS 要跑 always-on 服務＝攻擊面」。但 **Sanity 是託管**，你不跑任何伺服器，那個顧慮不存在 → CMS 反而更安全、更省工。
- 基本/進階 tier 的客製需求（懶人包 builder、去重提示、SEO 預覽）用 **Sanity Studio 自訂元件**做；抓文改寫/rehost/去重/syndication 等**用腳本打 Sanity API**。客製工作流照樣保留，不必自建整個後台。
- 平台 tier（gomag）的關聯/分析太重，Sanity 文件模型彆扭，這才值得自建 + Supabase。

**成本階梯：**
| tier | 後台 / 資料 | 月成本 |
|---|---|---|
| 基本 · 客戶官網 | Sanity 免費（一站一專案）+ Cloudinary 免費 + CF Pages | **~$0/站** |
| 進階 · 部落客站 | 同上（文章 < 1 萬篇皆免費）| **~$0/站**（破萬篇或要重關聯才轉 Supabase）|
| 平台 · gomag | 自建 + Supabase Pro（可多站共用一專案）| ~$25/月 |

- Sanity **每站一個專案**（各吃自己免費額度）；**別在一專案開多 dataset**（加購 dataset $999/月）。
- Supabase 免費只有 2 專案且閒置一週暫停，**不適合多小站**，故只留給平台 tier 且上 Pro。

**四目標怎麼命中：**
- **SEO（核心）**：Astro SSR + 邊緣＝結構化 HTML、極速、head 全控；SEO 欄位定義在 Sanity schema（程式強制），前台輸出契約見 [SPEC-seo-program.md](SPEC-seo-program.md)。
- **好開發**：TypeScript 整棧；基本/進階免自建後台，工時省最多。
- **內勤好上手**：Sanity Studio 現成專業編輯器 + 自訂元件貼工作流。
- **難攻擊 / 難綁架**：託管服務無 always-on 主機要補丁；前台無憑證；認證用 Sanity/Supabase 內建（不自寫登入）。

**唯一取捨**：Sanity 內容住其雲端（文件型），但可整包 API/JSON 匯出、資產拿得回來（非鎖死）。換得基本/進階兩 tier 免自建、近乎零成本。

**可靠性與備份（釐清「免費=有風險」的誤解）：**
- 「閒置暫停 / 無備份 / 資源吃緊」是 **Supabase 免費 tier 專屬**問題，**不是免費的通則**。基本/進階用的 CF Pages + Sanity + Cloudinary 免費方案都是**正式營運級**：
  - **不暫停**：三者免費都不會因閒置停掉（Supabase 會，是因為它跑專屬 Postgres compute）。
  - **資源**：免費額度（Sanity 100 萬 API/月、100GB 流量）對小站綽綽有餘。
  - **前台更耐**：基本 tier 靜態化後，公開網站是 CF 邊緣的靜態檔，**後端閃一下網站照樣在線**。
- **備份做法**：Sanity 內建版本歷史（每次存檔留版），但免費保留期較短 → **另設定期匯出 dataset 的 routine**（存一份內容 JSON）當正式備份。免費、加一條雲端 routine 即可。
- **判準心智模型**（別再誤以為「看容量」）：
  - **Sanity 免費 vs 付費** → 看**文件數**（< 1 萬免費）+ 要不要排程功能。
  - **Supabase 免費 vs Pro** → 看**是不是要可靠的正式營運**（備份 / 不暫停 / 扛流量），**跟資料量幾乎無關**（gomag 資料其實很小、純看容量免費也夠，但正式營收站不該吊在免費 tier）。

---

## 1. SEO 程式規範 【核心地基・所有 tier 共用】★

> **完整實作級規格見 [SPEC-seo-program.md](SPEC-seo-program.md)**（欄位型別、fallback 鏈、每種 JSON-LD 欄位、301 轉址表、孤立頁偵測 query）。本節是摘要。
> 這是整套後台的骨幹。以下每一條都是**程式層強制**，不是「記得填」。
> 原則：**在這後台裡建不出一個 SEO 不合格的頁。**

### 1.1 每頁必出（缺一不可，程式擋）
任何對外頁面在輸出時，一定要有：
- **title**：每頁唯一，跟畫面上的 H1 脫鉤（可各自設）。
- **meta description**：每頁唯一。
- **canonical**：預設 self-canonical；有變體時指向主頁。
- **描述性 slug**：可讀網址，不用流水號。
- **JSON-LD 結構化資料**：依頁型自動切換（見 1.3 表）。
- **Open Graph**：`og:title` / `og:description` / `og:image`（貼到 LINE／FB 的縮圖）。
- **正確標題階層**：一頁一個 H1，H2/H3 有序。

### 1.2 SEO 當資料模型（欄位是一級公民）
- 每一種內容資料表都內建 SEO 欄位：`seo_title`、`seo_description`、`slug`、`canonical`、`og_image`、`noindex`、`jsonld_type`。
- 沒填時有**合理 fallback**（例如 seo_title 空 → 用標題 + 品牌名），但欄位本身一定存在，能被覆寫。
- SEO 欄位跟著**每頁的編輯畫面**一起填（不另開一頁），避免漏。

### 1.3 JSON-LD 型別對照
| 頁型 | 結構化資料 |
|---|---|
| 品牌官網首頁 / 關於 | LocalBusiness / Organization |
| 文章 / 單店開箱文 | Article |
| 懶人包 / 分類清單 / 縣市頁 | ItemList / CollectionPage |
| FAQ 區塊 | FAQPage |
| 任何有麵包屑的頁 | BreadcrumbList |

### 1.4 站台層級
- **動態 sitemap.xml**：只收「已發布 + 夠份量」的頁（薄頁不收，例如清單頁項目數過少）；含圖片。
- **robots.txt** 可編。
- **BASE_URL 一致**：內部連結與 canonical 走同一個 BASE_URL（含 www 與否要統一），少一次 301。

### 1.5 轉址與索引控制
- **301 轉址管理**：改 slug、砍頁時自動建 301（舊網址 → 新網址），避免累積 404。後台可手動維護轉址表。
- **noindex 單頁開關**：感謝頁、測試頁、薄頁能設不被收錄。
- **草稿不外流**：草稿狀態不對外、不進 sitemap、不被內鏈。

### 1.6 內部連結（SEO 的一環）
- 內容之間要能互相連結（清單 ↔ 單篇、相關文章）。
- **孤立頁偵測**：沒有任何內鏈指入的已發布頁要能被抓出來（進階 tier 起強制，基本 tier 建議）。孤立＝不易被收錄。

### 1.7 內容紀律（防內容農場懲罰，寫進規範）
- **差異化**：別「只換地名」量產樣板頁（GSC 實證：樣板頁整城 0 曝光）。新主題先 1 篇做透看收錄，再擴。
- **真實日期**：顯示日期用真實日期，不要全站同一個 import 日（內容農場紅旗）。
- **標題照原文**：用給定的關鍵字原文，不擅自加減字。
- **不能無中生有**：沒有的評分、統計、事實不捏造；沒 Google 評分的客戶用信任帶，不做假數字。

### 1.8 SEO 驗收條件
- [ ] 隨機抽 3 頁，title / description / canonical / og:image 都不同且正確。
- [ ] 改一頁 slug 後，舊網址自動 301 到新網址。
- [ ] 設 noindex 的頁不出現在 sitemap、原始碼有 noindex。
- [ ] sitemap 打得開、不含草稿與薄頁。
- [ ] 結構化資料測試工具驗首頁與一篇內文都能過。
- [ ] 全站沒有孤立的已發布頁（進階 tier 起）。

---

## 2. 共用核心（非 SEO 底層）【所有 tier】

### 2.1 登入與安全 ●
- 帳密登入、密碼雜湊；登入失敗**速率限制**。
- Session cookie `Secure` + `HttpOnly` + `SameSite`；登出、逾時失效。
- **後台網址不走好猜路徑**（不要單純 `/admin`）。
- 敏感檔（設定/密碼/secret、備份、日誌、migration）**移出 webroot 或 deny-all**；封鎖危險副檔名執行、移除洩漏版本 header。

**驗收**：錯密碼會鎖／猜路徑進不去／抓 config・_backups・_logs・migrations 都 403／secret 沒進 git。

### 2.2 內容 CRUD 通用機制 ●
所有內容型別共用同一套：
- 列表：搜尋、篩狀態、排序、分頁。
- 新增 / 編輯 / 刪除；刪除優先用「下架（狀態切換）」避免誤刪。
- 草稿 / 發布。
- `sort_order` + 拖拉排序。
- `created_at` / `updated_at`；**任何後台或腳本改寫都要自帶 `updated_at`**。
- 富文本 + 貼 HTML；**注意主機 WAF**：貼大段含 iframe 的 HTML 可能被擋成 `Forbidden`，需要時走 base64 夾帶繞過。

**驗收**：列表能篩搜翻頁／存檔後前台立刻對得上（含時間）／貼含 iframe 長 HTML 能存能顯示。

### 2.3 媒體與圖片 ●
- 上傳當下**自動壓縮 / 轉檔（WebP 優先）**，不原圖直上。
- 產生縮圖；每張可填 alt、描述性檔名；內文圖 lazy load。
- 圖床策略：大量圖走物件儲存（R2 / Supabase / 站內圖床），支援把外部圖 rehost。封面固定比例（如 1200×630）。

**驗收**：上傳 5MB 原圖存下來是小檔／列表有縮圖不載大圖／每張前台圖有 alt。

### 2.4 Migrations ●
- `migrations/00X_描述`，編號遞增，**只能 CLI 跑或帶 secret key**。
- 一次性修補也走 migration，不在正式站手動下 SQL；腳本改寫要自帶 `updated_at`。

**驗收**：空 DB 依序跑完得到完整 schema／migration 對外網址打不開。

### 2.5 部署與環境 ●
- 旗標分三段：**本機 / staging / production**，各自 DB 與 BASE_URL；設定檔 gitignore + `config.example`。
- 紀律：本機 → **先 staging 驗** → **部署前 diff** → **覆蓋前備份** `_backups/<name>_時間/` → promote；不碰 config / upload / _backups / _logs。
- CSS/JS 用 `?v={mtime}` cache busting。
- 子網域上線（Hostinger）：hPanel 建子網域**勾 Use public_html directory 免 symlink**。

**驗收**：staging 驗過才上正式站／覆蓋前有備份／改 CSS 前台立刻更新。

### 2.6 交付前驗收鐵律 ●
- **交客戶前一定用瀏覽器真的登入後台**，走客戶那條路徑：編輯 → 存檔 → 傳圖，親手做過一遍。curl／替身不算。
- 沒看到實際結果就是沒發生過；產出後立刻驗證存在；長流程先寫 log 再讀。

### 2.7 對稿與內容修改鐵則 ●
- **只改指定的部分**，沒被點名的一個字都不碰。
- 不擅自「順便加東西 / 順便修別的」；發現別的問題先列出來問，點頭才動。
- 改完固定格式回報：已改哪些（原本 → 改成）、其餘維持原樣、另發現待決定。

### 2.8 SEO 健檢儀表板 ●（成效與 SEO 營運）
把 [SPEC-seo-program.md](SPEC-seo-program.md) §11 的檢查做成**即時面板**：缺 title/desc/canonical、缺 alt、重複 title、孤立頁、死連結、noindex 誤設、sitemap 落差。一頁看全站 SEO 體質，紅燈可點到問題頁。**這是「SEO 不合格建不出來」的可視化。**

**驗收**：新建一頁故意缺 meta → 面板即時標紅並連到該頁。

### 2.9 版本歷史 / 修訂 ●（內容生產）
每次存檔留版本（誰、何時、改了什麼），可 diff、可回滾。**落實對稿鐵則的安全網**：改壞了一鍵還原上一版，不用爭辯。

**驗收**：一篇存三次 → 看得到三版、能回滾到第一版。

### 2.10 圖片 AI 輔助 ○（內容生產）
上傳時**自動產 alt 建議**（可改）、可生成 OG 封面。省人工又補 SEO（alt 是 §1.1 必出欄位）。

**驗收**：上傳無 alt 圖 → 系統給 alt 建議、存檔後前台有 alt。

---

## 3. 基本 tier（`basic` ＝客戶官網）

繼承第 1、2 章全部，再加：

### 3.1 版型 / 模板系統 ●
- 客戶資料表帶 `minisite_template`；每套模板一目錄 `templates/minisite/{slug}/`，與內容脫鉤。
- 模板附 `meta.json`：pages 對應表（nav 客製）、`hide_emoji` 旗標、主題色。
- 後台**選模板**（卡片式 + 推薦），換模板不動內容。主題色 / 深淺**每家先跟客戶討論**。
- **設計系統優先**：動頁面前先做一頁 styleguide 核可再照做；繁中大標防糊（宣告字重、`font-synthesis:none`、別要求超過字型字重、正常 CJK 字距行高）。

### 3.2 頁面區塊 Blocks ●
- 一頁掛多個 block，`type`（服務／菜單／作品集／價目／FAQ／照片牆／店主介紹）+ JSON + `sort_order`。
- 新增型別 + 拖拉排序 + 即時預覽。示範假區塊用 `hide_blocks` **藏不刪**（刪會翻版型）；照片牆只顯真檔。

### 3.3 品牌設定 ●
一頁放全站共用、改它不動程式：
- Logo / 品牌名；聯絡（電話 / **LINE** / 地址 / 地圖 / 營業時間 / 社群）；GA4 追蹤碼。
- **Footer 署名**：客戶官網寫**口碑製造所**、連 wmf.com.tw（不是「店家好口碑」）。

### 3.4 案例相簿 ●
- 案例 / 作品掛一組相簿、照片排序。照片更新匣慣例：客戶丟照片 → 壓縮上傳 → 建相簿；隱私命名先講清楚。

### 3.5 聯絡 / 轉換元件 ●
- 聯絡卡 / sticky 聯絡區。
- **自訂 LINE / FB 按鈕**（不貼第三方官方促銷圖，很醜）；LINE 綠 `#06C755`。

### 3.6 懶人包 ○（可選開關）
- 客戶官網偶爾想要「多店 / 多方案推薦清單」。與進階 tier 同一個 builder，基本 tier 用開關開啟。

**驗收**：切模板畫面換皮資料不掉／`hide_emoji` 開了 nav・footer 全無 emoji／手機點 LINE・電話直接觸發／沒填 LINE 不冒空按鈕。

---

## 4. 進階 tier（`advanced` ＝部落客內容站）

繼承基本 tier 全部，再加量體與內容工作流：

### 4.1 文章量體 CRUD + 作者人設 ●
- 文章 CRUD（分頁、批次、狀態）；作者 / 部落客人設（語氣、頭像、bio）。
- 相關文章推薦；發佈日期可編（回填真實日期，見 1.7）。

### 4.2 多維分類 ●
- **縣市**（台南/高雄/台中…）＋**主題/類別**（美食/汽車/居家/景點…）＋**標籤**。
- **地點 / 店家實體**：單店文引用的店家資料（名稱/地址/資訊）。

### 4.3 懶人包 / 推薦清單 builder ●
- 一篇懶人包串多篇單店文 / 多家店；每家**推薦理由 + 導流**。
- **客戶置入 vs 真實補位**標記（真實前幾名不外連、不捏造）。
- 與單店文**雙向內鏈**（防孤立，見 1.6）。

### 4.4 內容生產工作流 ●（你們的日常）
- 從痞客邦 / 舊站**抓文 → 差異化改寫**（共存非搬家）。
- 圖片大量 **rehost**（Supabase / R2）。
- **站內重複偵測**（同篇 import 兩次會互打；字元 n-gram 相似度）。
- **syndication**：新站先發、再發痞客邦。

### 4.5 分類頁 / 縣市頁 / 標籤頁 ●
- 各維度落地頁 + pagination；套 1.3 的 ItemList / CollectionPage。

### 4.6 內部連結健檢 ●
- 孤立頁偵測強制開啟（見 1.6）。

**驗收**：一篇懶人包能串 5 篇單店文並雙向連結／改寫後站內重複偵測抓得到近似篇／分類頁分頁正確／全站 0 孤立已發布頁。

### 4.7 電子報 ●（進階 tier 主打；基本 tier 以可選開關提供簡化版）

把一次性讀者轉成回訪。**名單自有、寄送外包**：訂閱者存 Supabase、寄送走 email API（Resend / Brevo 之類），key 只在 server 端。**不自架 SMTP 從主機發**（送達率 + 主機 IP 黑名單 + 攻擊面）。

**資料模型 `subscribers`**
| 欄位 | 說明 |
|---|---|
| email | 唯一 |
| status | `pending` / `confirmed` / `unsubscribed` |
| source | 訂閱來源（哪頁 / 哪篇）|
| confirm_token / unsub_token | 一次性 token |
| confirmed_at / created_at | 時間 |
| owner_id | 多租戶預留（平台 tier 用）|

**流程與元件**
- **訂閱表單**（前台）→ 寫入 `pending`。防濫用：honeypot 隱藏欄 + 速率限制。
- **雙重確認 double opt-in** ●：寄確認信，點 confirm_token 才轉 `confirmed`。**法規必備**（台灣個資法 / 反垃圾信）。
- **電子報編輯**（後台）：手寫一封，或**自動彙整最新文章**成週報（RSS-to-email 式 digest）。
- **發送**：只寄給 `confirmed`；透過 email API 批次送。
- **一鍵退訂** ●：每封信底部 unsub_token 連結 → 退訂頁 → 轉 `unsubscribed`。**法規必備**。
- **基本成效**：開信率 / 點擊率（ESP webhook 回寫）。

**與 SEO 地基的關係**：訂閱表單、確認頁、退訂頁都是功能頁，設 `noindex`（見 [SPEC-seo-program.md](SPEC-seo-program.md) §1.5），不進 sitemap。

**驗收**
- [ ] 訂閱 → 收到確認信 → 點了才變 confirmed；沒點的不會被寄正式報。
- [ ] 每封信有退訂連結，點了立即停寄。
- [ ] email API key 不在前台原始碼、只在 server 端。
- [ ] 訂閱表單擋得掉機器人連續灌假 email。
- [ ] 確認 / 退訂頁為 noindex、不在 sitemap。

### 4.8 GSC 整合面板 ●（成效與 SEO 營運）
拉 Search Console API 進後台：每頁的**曝光 / 點擊 / CTR / 平均排名 / 查詢詞**。直接看哪頁哪詞在動、哪些「已收錄未曝光」，餵內容決策。呼應內容紀律「1 篇做透再擴、看 GSC 收錄才擴」。

**驗收**：面板列得出前 N 頁的曝光/點擊與對應查詢詞。

### 4.9 內建流量分析 ○（成效與 SEO 營運）
自架隱私友善分析（Umami / Plausible 式）或拉 GA4 API：熱門內容、來源、趨勢。**GA4 + GSC 已夠時可不做**，故列可選。

**驗收**：（若做）看得到本週熱門頁與來源。

### 4.10 AEO / AI 搜尋優化 ●（成效與 SEO 營運）
被 AI 引用的優化：`llms.txt`、問答式結構、清楚可引用的段落、實體 / 事實標註。檢查頁面「好不好被 AI 摘」。2026 前瞻但實在（AI Overviews / ChatGPT / Perplexity 是新流量入口）。

**驗收**：站台有 `llms.txt`；一篇內容過 AEO 檢查（有可引用問答段）。

### 4.11 排程發佈 + 內容行事曆 ●（內容生產）
文章排定未來時間**自動上線**；月曆檢視整體排程。草稿 → 排程 → 自動發布。

**驗收**：排一篇明天發 → 今天不外顯，到時自動上線並進 sitemap。

### 4.12 AI 內容輔助 ○（內容生產）
後台內建產草稿 / 改寫，但**套人味 + 差異化規則、絕不自動上線**（避 scaled-content 懲罰，見 [SPEC-seo-program.md](SPEC-seo-program.md) §1.7）。只當助手，人工核可才發。

**驗收**：產出物是草稿狀態、需人工按發布；不繞過差異化紀律。

---

## 5. 平台 tier（`platform` ＝gomag）

繼承進階 tier 全部，再加多租戶與平台級 SEO IA：

### 5.1 多租戶 ●
- 多帳號、`owner_id` 隔離：列表 / 編輯 / 上傳只看得到自己的資料。
- （基本・進階 tier 已建議資料表預留 `owner_id`，到平台 tier 才啟用隔離邏輯。）

### 5.2 店家（clients）大量管理 ●
- 數百家店家 CRUD；店家 ⇄ 分類 / 城市 / 關鍵字關聯。

### 5.3 多城市 × 分類 × 子服務 IA（交叉頁）●
- 4 層：城市 › 大分類 › 子服務 › 店家。交叉頁內容（intro + FAQ + 店家清單）後台可編。

### 5.4 關鍵字池 + 同義詞折頁 ●
- per 分類維護子服務關鍵字；同義詞折進同一頁避免 doorway。店家標 ~3 組（軟上限）。

### 5.5 店家行銷頁 + 城市變體 ●
- store 行銷頁；`/store/{slug}/{city}` 城市變體，baseCanonical 反競食（見 1.1 canonical）。

### 5.6 城市搜尋日誌 + analytics ●
- 應用層 TSV 日誌（時間/城市/關鍵字/結果數/匿名 IP hash）；帶 secret key 的 analytics endpoint，不做目錄列表。

### 5.7 攻略文 guides + 內容 routine ●
- 攻略文 CRUD（城市專屬優先）；雲端 routine 產內容草稿（人工審核才上，避 scaled-content 懲罰）。

**驗收**：A 帳號看不到 B 帳號資料／子服務頁依關鍵字 JOIN 出店家／城市變體 canonical 指對不自我競食／搜尋觸發 log 有一行、endpoint 無 key 403。

---

## 附錄 A：tier 功能對照

（●必備／○可選開關／✓繼承／—不適用）

| 模組 | 基本 | 進階 | 平台 |
|---|:—:|:—:|:—:|
| **§1 SEO 程式規範（核心地基）** | ● | ✓ | ✓ |
| §2 登入安全 / CRUD / 媒體 / migration / 部署 / 驗收 / 對稿 | ● | ✓ | ✓ |
| §2.8–2.10 SEO 健檢儀表板 / 版本歷史 / 圖片 AI 輔助 | ● | ✓ | ✓ |
| §3 模板系統 / Blocks / 品牌設定 / 相簿 / 聯絡轉換 | ● | ✓ | ✓ |
| 懶人包 builder | ○ | ● | ✓ |
| §4 文章量體 / 多維分類 / 內容工作流 / 分類頁 / 內鏈健檢 | — | ● | ✓ |
| §4.7 電子報（名單自有 + email API 寄送）| ○ | ● | ✓ |
| §4.8–4.10 GSC 面板 / 流量分析 / AEO 優化 | — | ● | ✓ |
| §4.11–4.12 排程發佈+行事曆 / AI 內容輔助 | — | ● | ✓ |
| §5 多租戶 / 店家管理 / 交叉頁 IA / 關鍵字池 / 行銷頁 / 搜尋日誌 / guides | — | — | ● |

## 附錄 B：各 tier 主要資料表（示意）

- **共用**：`sites`(含 `site_tier`)、`admins`、`media`、`redirects`、`settings`。
- **基本**：`clients`(含 `minisite_template`)、`blocks`、`cases`、`gallery_photos`、`testimonials`。
- **進階**：`articles`(含作者)、`categories`(縣市/主題)、`tags`、`article_tags`、`places`、`listicles`、`listicle_items`。
- **平台**：`clients`(店家) 放大、`cities`、`geo_category_pages`、`service_keywords`、`client_service_keywords`、`guides`、`client_city_pages`、`search_log`。
- 每張內容表都內建 §1.2 的 SEO 欄位。多租戶表預留 `owner_id`。

## 附錄 C：開新站落地清單（依 tier）

**共用（每個站都要）**
1. [ ] 建站、設 `site_tier`
2. [ ] 登入安全 + secrets 移出 webroot（§2.1）
3. [ ] SEO 地基：每頁 SEO 欄位 + sitemap/robots/301/noindex/OG（§1）
4. [ ] 媒體上傳壓縮（§2.3）／migration（§2.4）／三段部署（§2.5）
5. [ ] 瀏覽器實測驗收（§2.6）

**基本 tier 再加**：選模板 + styleguide 核可（§3.1）→ Blocks（§3.2）→ 品牌設定含 footer 署名（§3.3）→ 相簿（§3.4）→ 聯絡按鈕（§3.5）

**進階 tier 再加**：文章 + 作者（§4.1）→ 多維分類（§4.2）→ 懶人包 builder（§4.3）→ 內容工作流（§4.4）→ 分類頁分頁（§4.5）→ 內鏈健檢（§4.6）

**平台 tier 再加**：多租戶隔離（§5.1）→ 店家管理（§5.2）→ 交叉頁 IA（§5.3）→ 關鍵字池（§5.4）→ 行銷頁城市變體（§5.5）→ 搜尋日誌（§5.6）→ guides（§5.7）
