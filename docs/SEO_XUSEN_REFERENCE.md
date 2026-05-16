# 旭森風格 SEO 架構參考 SOP

> 參考來源：[www.xusen.com.tw](https://www.xusen.com.tw)（旭森環境清潔）
> 適用對象：gomag.com.tw 主站、mini-site 平台、各客戶獨立官網（含 062051129 旭浪）
> 整理日期：2026-05-16

## 為什麼參考旭森

旭森是台南清潔業在地網站、SEO 做得很完整。對「裝潢後細清 台南」「居家大掃除 台南」這類長尾詞排名表現好。值得借鏡的不是設計，而是**架構**。

---

## 一、URL 結構（最重要 ⭐）

### 旭森做法

| 頁面類型 | URL Pattern | 數量 | SEO 目的 |
|---|---|---|---|
| 首頁 | `/` | 1 | 品牌詞 |
| 服務列表 | `/cleaning`, `/storage`, `/about`, `/question`, `/contact` | 5 | 抓大類詞 |
| **服務詳細頁** ⭐ | `/cleaning/detail/{服務名稱}-{城市}` | **15** | **精準長尾詞** |
| **案例詳細頁** ⭐ | `/cases/detail/{地點}-{服務項目}-案例` | **11** | **長尾詞 + 地區詞** |
| 部落格 | `/column/detail/{文章標題}` | 3 | 知識詞、AEO |
| 章節錨點 | `URL##小節名稱` | 多個 | 站內 navigation + AEO |

實例：
- `/cleaning/detail/裝潢後細清-台南`
- `/cleaning/detail/冷氣清洗-掛洗-台南`
- `/cases/detail/台南東區-住家深度清潔-廁所復原-廚房去油-案例`

### 我們現況

| 頁面 | URL | 問題 |
|---|---|---|
| Mini-site | `{sub}.gomag.com.tw/services` | **所有服務擠一頁** |
| Mini-site | `{sub}.gomag.com.tw/cases` | **所有案例擠一頁** |
| 主站 | `/store/{slug}` | 一個客戶只有一頁 |

→ 沒辦法在「裝潢後細清 台南」「冷氣清洗 台南」等長尾詞競爭。

### 改造方向（Backlog，獨立 session）

```
{sub}.gomag.com.tw/services/{service-slug}  ← 新增
{sub}.gomag.com.tw/cases/{case-slug}        ← 新增
```

**所需工程**：
- DB：`services` 表加 `slug` 欄位（migration）
- DB：`cases` 表加 `slug` 欄位
- 新增 `site/service_detail.php`、`site/case_detail.php`
- `.htaccess` 加 routing
- `sitemap.php` 加各 detail URL
- 列表頁加 `<a href>` 內部連結
- Service / Article schema
- 服務頁要有 800-1500 字實質內容（避免 thin content）

---

## 二、JSON-LD Schema 結構（次重要）

### 旭森首頁有 3 個 schema

#### 1. WebSite + SearchAction（給 Google Sitelinks Searchbox）

```jsonc
{
  "@type": "WebSite",
  "url": "https://www.xusen.com.tw",
  "name": "旭森環境清潔",
  "description": "...",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://www.xusen.com.tw/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
```

#### 2. BreadcrumbList

#### 3. Organization（完整版）

```jsonc
{
  "@type": "Organization",
  "name": "旭森環境清潔",
  "logo": { "@type": "ImageObject", "url": "...", "width": "450", "height": "450" },
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "customer service",
    "telephone": "+88662436661",
    "email": "..."
  },
  "brand": { "@type": "Brand", "name": "...", "logo": {...} },
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "正南六街82號",
    "addressLocality": "永康區",
    "addressRegion": "台南市",
    "postalCode": "710",
    "addressCountry": "TW"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "23.037085",
    "longitude": "120.228744"
  },
  "openingHours": [
    "Monday 09:00-18:00",
    "Tuesday 09:00-18:00",
    ...
  ],
  "sameAs": ["FB url", "Threads url", "IG url"]
}
```

### 我們現況差距

| 旭森有 | 我們有 | 差距 |
|---|---|---|
| WebSite + SearchAction | ❌ | 完全缺 |
| Organization 完整版 | LocalBusiness 半完整 | PostalAddress 沒拆、沒 geo、沒 openingHours、沒 ContactPoint 物件 |
| `image` width/height | image only | rich result 可能少了完整渲染 |
| brand 嵌套 | ❌ | 完全缺 |

### 改造方向

DB 層加欄位：
```sql
ALTER TABLE clients ADD COLUMN latitude DECIMAL(10,7);
ALTER TABLE clients ADD COLUMN longitude DECIMAL(10,7);
ALTER TABLE clients ADD COLUMN address_street VARCHAR(200);
ALTER TABLE clients ADD COLUMN address_district VARCHAR(50);
ALTER TABLE clients ADD COLUMN postal_code VARCHAR(10);
ALTER TABLE clients ADD COLUMN opening_hours_json JSON;
```

`includes/seo_schema.php` 加：
- PostalAddress 拆 streetAddress / addressLocality / addressRegion / postalCode / addressCountry
- 有 lat/lng 時加 GeoCoordinates
- 有 opening_hours_json 時加 openingHours array
- ContactPoint 物件包 phone + email
- WebSite + SearchAction（如果該站有搜尋功能）

---

## 三、HEAD 區塊技術細節

### 旭森有的我們缺的

```html
<meta name="keywords" content="台南清潔公司,台南裝潢後細清,..."> <!-- 6 個 long-tail -->
<meta name="google-site-verification" content="...">  <!-- 我們用 DNS TXT 一樣有效 -->
<meta name="facebook-domain-verification" content="..."> <!-- 缺 -->
<link rel="alternate" hreflang="zh-Hant" href="..."> <!-- 缺 -->
<link rel="sitemap" type="application/xml" href="/sitemap.xml" title="Sitemap"> <!-- 缺 -->
<link rel="apple-touch-icon" href="..."> <!-- 缺 -->
```

### 改造方向

`site/layout_head.php` 跟 `main/layout_head.php` 都要加：
1. `<link rel="alternate" hreflang="zh-Hant" href="{canonical}">`
2. `<link rel="sitemap" type="application/xml" href="/sitemap.xml" title="Sitemap">`
3. `<link rel="apple-touch-icon">`（有 favicon 就好）
4. Facebook domain verification（用戶要去 Meta Business Suite 拿 token）

---

## 四、實作優先順序（按 ROI）

### Phase 1 — 立即可做（影響所有 mini-site）✅ 今天完成
- DB migration 加欄位
- `seo_schema.php` PostalAddress 拆欄位、加 geo / openingHours / WebSite / SearchAction
- layout_head 加 hreflang / link rel sitemap

### Phase 2 — Mini-site 服務詳細頁（需要獨立 session 4-6 hr）⏳ Backlog
- DB services 加 slug
- `site/service_detail.php`
- `.htaccess` routing
- 列表頁加連結
- sitemap 加新 URL
- Service schema

### Phase 3 — Mini-site 案例詳細頁（4-6 hr）⏳ Backlog
- 同 Phase 2 邏輯，給 cases
- Article schema

### Phase 4 — 旭浪 062051129 CodeIgniter 改造 ⏳ Backlog
- 它已經有大部分 SEO 元素（title/desc/canonical/schema），補完 Organization 細欄位 + WebSite SearchAction + hreflang
- CodeIgniter 框架要先研究，避免改壞線上

### Phase 5 — 主站擴充 ⏳ Backlog
- 加 WebSite + SearchAction（主站 /search.php 已有搜尋功能）
- 主站 Organization schema（目前似乎只有單頁 schema）

---

## 五、文案範本（給 Phase 2 服務詳細頁）

**Title 模板**：
```
{服務名稱}-{城市}｜{品牌名}
例：裝潢後細清-台南｜旭浪專業清潔公司
```

**Meta description 模板（150 字內）**：
```
{城市}{服務}首選 {品牌名}。{核心賣點 1}、{核心賣點 2}、{核心賣點 3}。
{現場估價/到府服務/品質保證}，{聯絡電話}立即諮詢。
```

**H1 模板**：
```
{服務名稱} - {城市}
副標：{一句話的服務 tagline}
```

**內容結構**（每服務頁建議 800-1500 字）：
```
H2 服務說明 / 適用對象
H2 作業流程 (id="作業流程"，配合 URL ##作業流程 錨點)
H2 包含項目 (item list)
H2 價格與時間 (FAQ 三選一：價格、流程、保固)
H2 客戶案例（連結到 cases/detail/）
H2 常見問題（FAQ schema）
CTA：立即預約 / LINE 諮詢
```

---

## 六、給用戶（Reney）的待辦

- [ ] 在 GSC submit sitemap：`https://www.gomag.com.tw/sitemap.xml`
- [ ] 在 GSC URL Inspection 抓 `https://happysteakcyi.gomag.com.tw/` 看 index 狀態
- [ ] 去 Meta Business Suite 拿 Facebook domain verification token
- [ ] 拿到 token 後填到 DB（建議加 `facebook_domain_token` 欄位）
- [ ] 收集已上線客戶的：經緯度（Google Maps 上點店家 → 「分享」→ URL 抽 lat/lng）、營業時間、完整地址（含郵遞區號）
- [ ] 補 4 個 demo 客戶的 `has_minisite=1`（目前只有 happysteakcyi 開著）

---

## 七、文件版本

| 日期 | 變更 |
|---|---|
| 2026-05-16 | 初版（從 Gmail 草稿成品化）|
