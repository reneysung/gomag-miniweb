# gomag 多縣市 IA 設計

> **建立**：2026-05-20
> **狀態**：設計確認、尚未實作
> **緣起**：對照 `xusen.com.tw`、`sanfengclean.com` 兩份深度 SEO 報告，歸納出在地服務站的「多縣市」致勝結構，套用到 gomag 主站（目前只有台南有密度）。

## 核心原則

網站架構對應的是**搜尋需求（demand）**，不是現有客戶名單（supply）。

- 需求面（哪裡的人在找什麼服務）是固定且完整的 → 撐起骨架。
- 客戶（店家）只是「庫存」，慢慢把格子填滿，**不是地基**。

這就是 sanfeng 沒有當地分店也能在台中排名的原因：頁面內容跟查詢意圖相關就能排，跟你在當地有沒有店無關。代價是——**只要做成模板薄頁就會被當 doorway 懲罰**。

## 形狀：兩條座標軸 + 兩種填充物

```
縣市 (geo) ─┐
            ├──► 城市×產業（交叉點＝主力長尾頁，例：台中清潔）──► 客戶（店家頁）
產業 (cat) ─┘
                         ▲
                      攻略文（內容層：橫向貫穿，補實質內文 + 互鏈）
```

精準寫法：**（縣市 × 產業）+ 攻略文 → 客戶**。
攻略文不是夾在產業與客戶之間的一層，而是橫向內容層。

---

## 1. URL 結構

| URL | 說明 | 現況 |
|---|---|---|
| `/` | 首頁 | ✅ 有 |
| `/city` | 縣市總覽 | ✅ 有 |
| `/city/{city}` | 縣市頁（台中） | ✅ 有 |
| `/category/{cat}` | 全台分類頁（清潔） | ✅ 有 |
| `/city/{city}/{cat}` | **城市×產業交叉頁（台中清潔）★ 主力長尾** | ❌ 新增 |
| `/guide` | 攻略文總覽 | ❌ 新增 |
| `/guide/{slug}` | 攻略文（台中清潔公司怎麼選） | ❌ 新增 |
| `/store/{slug}` | 店家頁 | ✅ 有 |

**決策（2026-05-20 確認）**：交叉頁走 `/city/{city}/{cat}`（geo 當父層）。
理由：麵包屑乾淨（首頁 › 台中 › 清潔）、複用既有 `/city` 命名空間、與 city.php 現有的分類分組邏輯最一致。
**一個 cell 只能有一條正規網址** — 不要同時開 `/category/{cat}/{city}`，避免重複內容。

---

## 2. 資料模型

### 新增 2 張表

```sql
-- 交叉頁的「內容」：讓它不薄、能脫離庫存排名
geo_category_pages
  id, city_slug, category_id,
  intro_html, faqs(JSON), hero_image,
  meta_title, meta_desc, is_active, updated_at
  UNIQUE(city_slug, category_id)

-- 攻略文層
guides
  id, slug(UNIQUE), title, excerpt, body_html, cover_image,
  city_slug(nullable), category_id(nullable),   -- 掛回矩陣 + 互鏈
  meta_title, meta_desc, status(draft/published),
  published_at, updated_at
```

### 改既有

- **`cities` 表（已存在）** → 升級成 slug↔縣市名的**唯一來源**。
  目前 slug↔中文名對映寫死在 3 個地方：`city.php` 的縣市清單、`city.php` 的 `$cityMap`、`sitemap.php` 的 `$cityNameToSlug`，都只有 12 個。收斂後「開新縣市」= 新增一筆 `cities` row，不動 code。
- **`clients`（可選、為規模化）** → 加正規化的 `city_slug` 欄位（從 address 推導一次），取代到處 `address LIKE '台中市%'` 的脆弱比對。

---

## 3. 上架閘門規則（程序化 SEO ↔ doorway 的分界線）

| 頁面 | 渲染 | **索引** | 進 sitemap |
|---|---|---|---|
| `/city/{city}` | ≥1 店或有 intro，否則 404 | **≥3 真實店** | ≥3 真實店 |
| `/city/{city}/{cat}` ★ | 有內容 or ≥1 店 | **有 geo_category 內容 OR ≥3 真實店** | 同索引條件 |
| `/category/{cat}` | 永遠 | 永遠 | 是 |
| `/guide/{slug}` | published | published | published |
| `/store/{slug}` | active | active **且非 placeholder** | active 且非 placeholder 且非重複 |

兩個重點：

1. **★ 交叉頁**：「有寫內容」或「有 ≥3 店」其一就上線——這就是「不管有沒有客戶」都能跨縣市的關鍵。
   - 內容撐排名 = sanfeng 模式（沒店也能卡位 + 招商）
   - 庫存撐排名 = 台南現況
   - 兩者皆空 → noindex，不產頁。
2. **placeholder 店家**目前被 `sitemap.php`（92–103 行）連同一般店家放進 sitemap，**沒過濾** → 要改成 noindex + 移出 sitemap。對齊兩份報告對「placeholder／薄頁扣信任」的警告。

`<3 家` 閘門目前同時存在於 `city.php`（93 行）與 `sitemap.php`（79 行），是現有的 doorway 防線，保留。

---

## 4. 內部連結規則（讓矩陣變「圖」而非孤島）

- `/city/台中` → 各交叉頁卡片（台中清潔、台中美髮…）
  ★ 現在只是 `#cat-{slug}` 頁內錨點（city.php 433–466 行），要升級成交叉頁**真連結**。
- `/city/台中/清潔` → 店家清單 +「看全台清潔」(`/category/清潔`) + 回父頁 `/city/台中` + 相關攻略文
- `/guide/{slug}` → 對應交叉頁 + B2B 招商 CTA
- `/store/{slug}` → 回所屬交叉頁與縣市

---

## 5. 落地順序（風險低 → 高）

1. **placeholder 店家 noindex + 移出 sitemap**（快、修漏洞、對齊報告）
2. **cities 表收斂成唯一來源**（解鎖 >12 縣市的前置）
3. **交叉頁 `/city/{city}/{cat}` + `geo_category_pages` 表**（核心）
4. **city.php 分類卡片**從錨點升級成交叉頁連結 + sitemap 收錄交叉頁
5. **guides 表 + `/guide`**（內容層）
6.（規模化再做）`clients.city_slug` 正規化

---

## 參考來源

- `xusen.com.tw SEO 深度研究報告.pdf`（旭森環境清潔，台南清潔，gomag 示範客戶 ID 1）
- `sanfengclean.com SEO 深度研究分析報告.pdf`（三峰清潔，台中/高雄）
- 兩份共同結論：在地長尾 URL + 標準化服務知識頁 + 內容集群（topic cluster）+ 全站導覽當內鏈主幹 + 評論雙軌 + 薄頁/placeholder 是 P0 信任殺手。
