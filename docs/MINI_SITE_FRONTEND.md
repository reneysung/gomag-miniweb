# 小官網前台（site/）Inventory

> 客戶子網域官網 — 例：`docar.gomag.com.tw`、`https://aqua-elephant-856571.hostingersite.com/site/index.php?sub=docar`
>
> 跟主站行銷頁 `/store/{slug}` 不同 — 主站是「店家好口碑」品牌下的曝光頁，小官網是客戶**自己的網站**（has_minisite=1 才啟用）。

---

## 啟用邏輯

```
client.has_minisite=1  → 顯示 site/index.php
client.has_minisite=0  → 301 跳到 external_website_url 或 /store/{slug}
```

---

## 檔案清單

| 檔案 | 行數 | 用途 |
|---|---|---|
| `site/index.php` | 397 | **首頁** — Hero / 關於 / 服務項目 / 案例 / 評價 / FB / 聯絡 |
| `site/services.php` | 190 | **服務項目詳細頁**（從 services 表讀） |
| `site/cases.php` | 330 | **案例/作品集**（從 cases 表讀） |
| `site/testimonials.php` | 197 | **客戶好評**（從 testimonials 表讀） |
| `site/contact.php` | 76 | **聯絡表單 AJAX handler**（POST 收訊） |
| `site/layout_head.php` | 16KB | 共用 head + 導覽列 + CSS |
| `site/layout_foot.php` | 4KB | 共用 footer |

---

## 核心特色

### 1. 業種感知（dynamic copywriting）

```php
$isFood = str_contains($ind,'餐') || str_contains($ind,'食') || str_contains($ind,'料理') || str_contains($ind,'咖啡') || str_contains($ind,'甜點');

if ($isFood) {
    // 餐飲：「料理作品」、「FEATURED DISHES」
    $defaultHeroStats = [['4.5★','Google評分'],['429+','真實評價'],['10年','料理經驗'],['95%','回訪率']];
    $defaultAboutTags = ['🍽️ 進口頂級食材','👨‍🍳 專業主廚料理','🍷 免開瓶費','🎂 包場客製服務'];
} else {
    // 服務業：「施工案例」、「CASE STUDIES」
    $defaultHeroStats = [['500+','服務客戶'],['8年','專業經驗'],['98%','回購率'],['4.9★','Google評分']];
    $defaultAboutTags = ['🏆 專業認證技師','🌱 環保清潔用品','📋 免費到府估價','⏱ 準時守信到場'];
}
```

### 2. DB-first，預設值 fallback

```php
$heroStats = !empty($client['hero_stats']) ? json_decode($client['hero_stats'], true) : $defaultHeroStats;
$aboutTags = !empty($client['about_tags']) ? json_decode($client['about_tags'], true) : $defaultAboutTags;
```

DB 有就用 DB（後台填的），沒有就用業種預設值。

### 3. 共用資料載入

`includes/front_functions.php` 提供 `getSubdomain()` 和 `loadSiteData($sub)` — 一次抓出該客戶的：
- client（基本資料）
- services
- cases
- testimonials
- social（社群連結）
- gallery（如有）

---

## CSS 系統

小官網用 **`var(--c-*)`** 為主（跟主站的 `m-*` / `g-*` 不同）：

```css
--c-primary    /* 主色 */
--c-light      /* 淺色背景 */
--c-accent     /* 強調色 */
--c-text       /* 內文色 */
```

**注意**：主站最近導入 `g-` 設計系統（`assets/css/gomag.css`）。**未來可能要把小官網也統一到 g- 系統**（看下面待辦）。

---

## 各頁主要區塊

### `index.php`
```
<section class="hero">              ← 大標 + 統計 + CTA
<section class="section">           ← 關於我們（about_tags）
<section class="section">           ← 專業服務項目（services 前 6 筆）
<section class="section">           ← 招牌料理 / 精選案例（cases）
<section class="section">           ← 客戶評價（testimonials 前 3 筆）
<section>                           ← Facebook 區塊
<section>                           ← 聯絡 CTA
```

### `services.php`
- Loop services 表，每筆獨立 section（左右交錯排版）
- 底部固定 CTA banner

### `cases.php`
- Before / After 對比模式
- 餐飲：純圖（料理作品）
- 服務：Before/After（施工案例）

### `testimonials.php`
- 卡片式網格
- 5 星評價、評論者名字、日期

### `contact.php`
- 純 AJAX endpoint（POST 收訊）
- 寄 Email + 寫入 DB

---

## URL 結構

### Production（正式網域）
```
https://docar.gomag.com.tw/             → site/index.php?sub=docar
https://docar.gomag.com.tw/services     → site/services.php?sub=docar
https://docar.gomag.com.tw/cases        → site/cases.php?sub=docar
https://docar.gomag.com.tw/testimonials → site/testimonials.php?sub=docar
```

### Local（測試）
```
http://localhost/miniweb/site/index.php?sub=docar
```

子網域 routing 透過 `getSubdomain()` 從 `$_SERVER['HTTP_HOST']` 抓出 subdomain，再 LOAD 對應 client。本機沒子網域時用 `?sub=` query string。

---

## 跟主站行銷頁的差別

| 項目 | 主站 `/store/{slug}` | 小官網 `site/` |
|---|---|---|
| 品牌 | 店家好口碑 | 客戶自己 |
| 網址 | gomag.com.tw/store/docar | docar.gomag.com.tw |
| 啟用條件 | 所有 active 客戶 | has_minisite=1 |
| 設計系統 | m- / g- (新) | c- (舊) |
| 頁面數 | 1 頁（單頁式） | 5 頁（多頁站） |
| 用途 | SEO 集點頁 | 客戶獨立官網 |

---

## 待辦（未來改造項目）

1. **CSS 系統統一** — 把小官網從 `c-` 系統升級到 `g-` 系統（gomag.css）
2. **Modular Blocks 整合** — 小官網的 services/cases/testimonials 也改用 `store_blocks` 表（見 `MODULAR_BLOCKS_DESIGN.md`）
3. **行動版優化** — 部分區塊在手機上排版可優化
4. **效能** — 圖片可加 lazy load、cdn / responsive image
5. **SEO** — 補 og:image、breadcrumb schema、各內頁 meta description

---

## 想動手改前台時的入口檔案

| 改什麼 | 改哪個檔案 |
|---|---|
| 首頁 Hero | `site/index.php` line 72-118 |
| 關於我們 | `site/index.php` line 119-152 |
| 服務項目卡片 | `site/index.php` line 153-202 |
| 全站導覽列 + Logo | `site/layout_head.php` |
| Footer + 社群連結 | `site/layout_foot.php` |
| 業種預設文案 | `site/index.php` line 35-58 |
| 聯絡表單欄位 | `site/contact.php` |
