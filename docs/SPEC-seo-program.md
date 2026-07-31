# SEO 程式規範（核心地基）

> 這是自架網站後台程式的骨幹規格，展開自 [SPEC-selfhosted-admin.md](SPEC-selfhosted-admin.md) 第 1 章。
> 三個 tier（basic / advanced / platform）都建在這份上面。
> **鐵則：在這後台裡，建不出一個 SEO 不合格的頁。** SEO 欄位由程式強制輸出，不是「記得填」。
> 技術不限，但欄位、fallback、輸出契約寫到可直接照做。
> 最後更新：2026-07-31

---

## 1. SEO 資料模型（每張內容表內建的欄位）

任何「會變成一個對外 URL」的內容表（文章、店家、案例、分類、懶人包、交叉頁…）都內建以下欄位。**欄位一定存在，可為空；空值走 fallback，但不可從資料表拿掉。**

| 欄位 | 型別 | 必填 | 空值時 fallback | 說明 |
|---|---|---|---|---|
| `slug` | string | ● | 由標題產生（見 §3）| 網址片段，同 scope 內唯一 |
| `seo_title` | string(≤60 建議) | ○ | `{標題} — {品牌/站名}` | `<title>`，跟畫面 H1 脫鉤 |
| `seo_description` | string(≤160 建議) | ○ | 內文前 ~150 字去標籤 | meta description |
| `canonical_override` | string(url) | ○ | 自算 self-canonical（見 §2.3）| 只有要指別頁時才填 |
| `og_image` | string(url) | ○ | 封面圖 → 站台預設分享圖 | `og:image`，建議 1200×630 |
| `og_type` | enum | ○ | `article` 或 `website`（依頁型）| Open Graph 型別 |
| `noindex` | bool | ● | `false` | true＝不收錄、不進 sitemap |
| `nofollow` | bool | ○ | `false` | 少用，通常整頁不 nofollow |
| `jsonld_type` | enum | ● | 依頁型預設（見 §5）| 決定吐哪種結構化資料 |
| `updated_at` | datetime | ● | — | 驅動 sitemap lastmod；改寫必更 |
| `published_at` | datetime | ○ | `created_at` | 顯示日期 + Article datePublished（見 §9 日期紀律）|
| `focus_keyword` | string | ○ | null | 進階 tier 選用，做重複/密度檢查 |

**設計原則**
- Fallback 在**輸出層**算，不寫回 DB（DB 存空值，代表「用預設」）。這樣改預設邏輯全站生效。
- `seo_title` / `seo_description` 的 fallback 要能被**站台層級模板**設定（例如整站 title 尾綴）。

---

## 2. 每頁輸出契約（head 一定吐什麼）

每個對外頁面 render 時，`<head>` 一定包含以下。這是程式的硬契約，不是選配。

### 2.1 基本 meta
```html
<title>{resolve_title()}</title>
<meta name="description" content="{resolve_description()}">
<link rel="canonical" href="{resolve_canonical()}">
<meta name="robots" content="{noindex?'noindex':'index'},{nofollow?'nofollow':'follow'}">
```

### 2.2 Open Graph + Twitter
```html
<meta property="og:type" content="{og_type}">
<meta property="og:title" content="{resolve_title()}">
<meta property="og:description" content="{resolve_description()}">
<meta property="og:image" content="{resolve_og_image()}">
<meta property="og:url" content="{canonical}">
<meta property="og:site_name" content="{site_name}">
<meta name="twitter:card" content="summary_large_image">
```

### 2.3 canonical 計算順序（resolve_canonical）
1. 有 `canonical_override` → 用它。
2. 否則 → `BASE_URL + 該頁 route`（self-canonical）。
3. **城市/語系變體**：變體頁 canonical 指向「主頁」還是「自己」由 tier 規則決定：
   - 平台 tier 的 `/store/{slug}/{city}` 城市變體 → 指向 `baseCanonicalCity`（反自我競食）。
   - 一般內容頁 → self。
4. **一律用同一個 BASE_URL**（含 www 與否統一），避免 canonical 與內鏈不一致多跳一次 301。

### 2.4 JSON-LD
- 依 `jsonld_type` 注入對應 `<script type="application/ld+json">`（見 §5）。
- 有麵包屑的頁**額外**注入 BreadcrumbList（可與主型別並存）。

---

## 3. Slug 規範

- **格式**：小寫、`a-z0-9-`、連字號分隔，無尾斜線、無底線。
- **唯一 scope**：同一內容型別內唯一（或全站唯一，看路由設計）；衝突時尾綴 `-2`。
- **產生**：中文標題 → 拼音/羅馬化或人工指定英文 slug（不要用流水號當網址）。
- **不可隨意改**：slug 是網址身分。**一旦發布，改 slug 必同時建 301**（見 §4）。後台改 slug 時跳提醒。

---

## 4. 301 轉址表與規則

### 4.1 資料表
```
redirects(
  id, from_path, to_path,
  status_code   -- 301 預設 / 302 少數暫時
  reason,       -- slug_change / unpublish / manual / merge
  created_at
)
```
- `from_path` 唯一索引。
- 命中優先於一般路由（在路由最前面查表）。

### 4.2 何時自動建
- **改 slug**：舊 path → 新 path，reason=slug_change。
- **下架 / 刪除**：若該頁有替代（分類頁/相似頁）→ 建 301 到替代；否則回 410/404（別硬導首頁，會被視為軟 404）。
- **合併重複**（進階 tier 去重）：被下架那篇 → 301 到保留篇。

### 4.3 轉址鏈收斂
- 建新轉址時，若 `to_path` 本身又是某條 redirect 的 `from_path`，**收斂成一步**（A→B、B→C 直接寫 A→C），避免 301 鏈。

### 4.4 尾斜線規則（踩過的雷）
- trailing-slash 正規化**只限內容路由**（如 `/article/`、`/category/`），**不要全站套**——靜態目錄（`/cms/`、`/assets/`）與 CDN 原生加斜線會互打成無限重定向。

---

## 5. JSON-LD 型別規範（實際欄位 + 來源）

`jsonld_type` 決定注入哪種。每種列必填/建議欄位與資料來源。

### 5.1 Article（文章 / 單店開箱文）
| 欄位 | 來源 |
|---|---|
| headline | seo_title 或標題 |
| description | seo_description |
| image | og_image / 封面 |
| datePublished | `published_at`（真實日期，見 §9）|
| dateModified | `updated_at` |
| author | 作者人設（進階 tier）/ 站名 |
| publisher | 站台 Organization（含 logo）|
| mainEntityOfPage | canonical |

### 5.2 LocalBusiness（品牌官網首頁 / 關於）
| 欄位 | 來源 |
|---|---|
| name | 品牌名 |
| image / logo | 品牌設定 |
| telephone | 品牌設定聯絡 |
| address (PostalAddress) | 地址 |
| geo (lat/lng) | 地圖座標（有才放）|
| openingHours | 營業時間 |
| url | canonical |
| sameAs[] | 社群連結 |
- **無中生有守則**：沒有的評分 `aggregateRating` **不捏造**。有真實 API 評分才放。

### 5.3 ItemList / CollectionPage（懶人包 / 分類頁 / 縣市頁）
| 欄位 | 來源 |
|---|---|
| name | 清單標題 |
| itemListElement[] | 每項 position + url + name（串到的單篇/店家）|
| numberOfItems | 項目數 |

### 5.4 FAQPage（FAQ 區塊）
- `mainEntity[]`：每題 `Question{name}` + `acceptedAnswer{Answer.text}`。
- 只放**頁面上真的看得到**的 FAQ（Google 政策：隱藏 FAQ 不合格）。

### 5.5 BreadcrumbList（任何有麵包屑的頁）
- `itemListElement[]`：每層 position + name + item(url)。層數依 tier（官網淺、平台城市›分類›子服務›店家最深）。

### 5.6 Organization（站台層級，publisher 用）
- 站台一份：name / url / logo / sameAs。被 Article.publisher 引用。

---

## 6. Sitemap 規範

- **動態產生**，收錄條件（全部成立才收）：
  1. 狀態＝已發布
  2. `noindex = false`
  3. 非薄頁（清單頁項目數 ≥ 門檻，例如平台城市頁店家 < 3 不收）
- 每筆：`loc`(canonical)、`lastmod`(`updated_at`)、圖片 `image:image`（封面）。
- 量大時分 sitemap index（每檔 ≤ 50000 筆 / 50MB）。
- 草稿、noindex、被 301 的舊 path **一律不進**。

---

## 7. robots.txt

- 允許正常內容路徑；擋後台、search endpoint、`_backups`/`_logs` 之類。
- 指向 sitemap。
- **Cloudflare Content Signals 踩雷**：CF 會自動在 robots.txt 塞 Content-Signal 註解；若自管 robots 要留意別被覆蓋 / 重複（見對應 CF 操作記錄）。

---

## 8. 內部連結 / 孤立頁偵測

### 8.1 定義
- **孤立頁**＝狀態已發布、但沒有任何**站內**其他已發布頁連入。孤立＝爬蟲難到達＝不易收錄。

### 8.2 偵測（示意）
```sql
-- 已發布頁 中，不在「任何內容 body/關聯 的連結目標集合」裡的
SELECT p.id, p.slug FROM pages p
WHERE p.status='published' AND p.noindex=0
  AND p.id NOT IN (SELECT target_page_id FROM internal_links);
```
- 內鏈來源：文章 body 解析出的站內連結 + 結構化關聯（懶人包 items、相關文章、分類歸屬）。
- **進階 tier 起強制**跑；基本 tier 建議。報告列出孤立頁清單。

### 8.3 修復配方（平台/內容站實證）
- 通用清單頁/樞紐頁打向多篇；懶人包 ↔ 單篇雙向；地理/同義詞頁折入主頁避 doorway。

---

## 9. 內容紀律 gate（程式 + 流程把關）

| 紀律 | 把關點 |
|---|---|
| **差異化**（別只換地名量產樣板）| 新主題先 1 篇；量產同模板多城前先看 GSC 收錄。程式難擋，列為發布前 checklist |
| **真實日期**（禁全站同 import 日）| `published_at` 用真實日期驅動顯示 + Article.datePublished；回填腳本自帶 `updated_at` |
| **標題照原文**（不自加減字）| 後台標題欄不自動加「推薦」等詞；要加人工加 |
| **不能無中生有**（禁假評分/統計）| JSON-LD 不放捏造 aggregateRating；信任帶不寫假數字 |

---

## 10. BASE_URL / canonical 一致性

- 全站一個 `BASE_URL` 常數（含協定 + 是否 www）。
- canonical、sitemap loc、OG url、內部絕對連結**全走它**。
- prod 若決定用 www，設定就補 www，避免「內鏈無 www → 301 → www」多一跳。

---

## 11. 驗收 / 自動化檢查

- [ ] **唯一性**：抽 N 頁，title / description / canonical / og:image 互不重複且正確。
- [ ] **轉址**：改一頁 slug → 舊 URL 301 到新 URL；轉址無鏈（A 不會 →B→C）。
- [ ] **noindex**：設 noindex 的頁不在 sitemap、原始碼 robots meta 有 noindex。
- [ ] **sitemap**：打得開、不含草稿/noindex/薄頁；lastmod 對得上 updated_at。
- [ ] **結構化資料**：首頁（LocalBusiness）+ 一篇內文（Article）+ 一個懶人包（ItemList）用 Google 測試工具都過。
- [ ] **孤立頁**：偵測報告 0 筆（進階 tier 起）。
- [ ] **404 健檢**：抓站內死連結（可搭 seo-health-check skill）。
- [ ] **BASE_URL**：canonical 與內鏈同 host，無多餘 301。

---

## 附錄：輸出契約檢查清單（每種頁型 render 前自檢）

任何頁型上線前，對照這 8 點：
1. title 唯一且 ≤ 建議長度
2. description 唯一
3. canonical 正確（self 或指對主頁）
4. robots meta 對（該收就 index、該擋就 noindex）
5. OG 四件（title/desc/image/url）齊
6. JSON-LD 型別對、欄位不捏造
7. 有麵包屑就有 BreadcrumbList
8. 進 sitemap 的條件成立
