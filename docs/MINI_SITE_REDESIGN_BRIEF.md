# 小官網 UI 重新設計 Brief

> 對象：site/ 下所有客戶獨立官網（如 docar.gomag.com.tw、xusen.gomag.com.tw）
> 設計系統：升級到 gomag.css（`--g-*` 系統），但客戶可自訂主色

---

## ⭐ 核心設計決定（已敲定）

| 項目 | 決定 |
|---|---|
| **設計系統** | 沿用 `gomag.css`，但客戶可選自家主色（DB `theme_color` 欄位 override `--g-accent`） |
| **頁面結構** | 維持 5 頁多頁站：首頁 / 服務項目 / 施工案例 / 客戶好評 / 聯絡 |
| **風格定位** | **在地小店風** — 溫度感、手寫感、質樸，像獨立咖啡店網站 |
| **必要元件** | Hero、老闆故事、服務卡片、案例、評價、價目表、FAQ、地圖、LINE 浮動、表單、FB/IG embed 全要 |

---

## 🎨 「在地小店風」設計細節

跟「品牌極簡風」「電商導購風」的差異：

| 元素 | 在地小店風（要做的） | 要避開的 |
|---|---|---|
| **字體** | 標題用 Klee One / 思源黑體 + 手寫感（Caveat 點綴） | 純 Sans-serif（太冰冷） |
| **排版** | 不對稱、隨性，有呼吸感 | Grid 完美對齊（太電商） |
| **配色** | 暖米色、紙黃、奶油白 + 客戶主色 | 純黑白（太極簡） |
| **圖片** | 自然光、生活感、店家實景 | 商攝棚拍（太銳利） |
| **文案** | 帶故事、口語、有人味 | 「業界領先」「卓越品質」 |
| **動效** | 輕微 fade、慢呼吸 | 視差滾動、3D 翻轉 |
| **質感** | 紙紋背景、手繪 icon、不規則框 | 玻璃毛玻璃、霓虹 |

**參考方向**：
- 鳴日咖啡 / 自家烘焙咖啡店官網
- 京都老舖網站（如一保堂茶舗）
- 台灣獨立書店網站（如閱讀書店、永楽座）
- 日本商家「俺の」「ふじ田」這類有歷史感的小店

**色票（暖色系，搭 g- 系統）**
```css
--g-bg-warm:    #FFF8F4   /* 已有，主背景 */
--g-paper:      #FAF6EE   /* 紙黃，新增 */
--g-cream:      #F5EDE0   /* 奶油色，新增 */
--g-handwrite:  Caveat, '楷體', cursive    /* 手寫字體 */
```

---

## 🌈 客戶主色客製機制（Q1 決定）

### DB 改動
```sql
ALTER TABLE clients ADD COLUMN theme_color VARCHAR(7) DEFAULT NULL COMMENT '客戶主色 hex（覆蓋 --g-accent）';
```

### 前端注入
`site/layout_head.php` 開頭：
```php
<?php if (!empty($client['theme_color'])): ?>
<style>
  :root {
    --g-accent: <?= h($client['theme_color']) ?>;
    --g-accent-hover: <?= darken_color($client['theme_color'], 10) ?>;
    --g-accent-light: <?= lighten_color($client['theme_color'], 40) ?>;
  }
</style>
<?php endif; ?>
```

需要寫 `lighten_color()` / `darken_color()` helper（HSL 操作）放 `includes/helpers.php`。

### 後台 UI
admin 加 color picker（HTML5 `<input type="color">`，免引第三方）。

### 預設色（業種建議）
```php
$defaultColors = [
    '居家清潔' => '#048A50',  // 綠 = 乾淨
    '汽車美容' => '#1E40AF',  // 深藍 = 專業
    '餐飲美食' => '#DC2626',  // 紅 = 食慾
    '美容美髮' => '#DB2777',  // 粉 = 美感
    '教育學習' => '#7C3AED',  // 紫 = 智識
];
```
客戶沒選時用業種預設。

---

## 目前狀態快照（http://localhost:8888/miniweb/site/index.php?sub=xusen）

實機截圖觀察到的問題：

### 🔴 立即要修的 Bug
1. **animate-in 動畫卡死** — 所有 `.animate-in.delay-*` 元素停在 opacity:0
   - Hero「旭浪清潔」主標不見
   - 各 section title「OUR SERVICES / 客戶真實評價 / CONTACT」灰透明
   - 多個 CTA 按鈕透明難辨識
   - **Fallback 方案**：先讓 animate-in 預設 visible，動畫只是錦上添花

### 🟡 視覺設計問題
1. Hero 過空：沒背景圖、沒明確 CTA、墨綠色佔滿整個視窗
2. 配色用 `--c-*` 系統，跟主站 `--g-*` 不一致
3. 服務卡片視覺平淡（icon 過大、配色冷）
4. 缺信任元素（評分、案例數、年資數字）
5. 缺手機優先設計感

### 🟢 保留的好元素
- 導覽列（簡潔、Logo 配色 OK）
- 浮動 LINE 按鈕位置（右下角）
- 服務分類 icon + 價格標籤的設計
- 評價卡片排版

---

## Phase 1：修 bug + 系統升級（優先做）

### 1.1 修 animate-in
找出 site/layout_head.php / index.php 等檔案中：
```html
<element class="animate-in delay-1">
```
對應的 CSS 應該長這樣（猜測）：
```css
.animate-in { opacity: 0; transform: translateY(20px); }
.animate-in.in-view { opacity: 1; transform: none; }
```

但 IntersectionObserver 沒觸發 `in-view` 加上去。**修復方案二選一**：
- A. 讓 animate-in 預設 `opacity: 1`（取消入場動畫）
- B. 用 CSS-only 動畫（@keyframes + animation-delay），不靠 JS

推薦 B，現代瀏覽器都支援。

### 1.2 CSS 系統升級

```diff
- var(--c-primary)   → var(--g-ink)
- var(--c-light)     → var(--g-bg-alt)
- var(--c-accent)    → var(--g-accent)
- var(--c-text)      → var(--g-ink-soft)
- var(--c-text-muted) → var(--g-ink-muted)
- var(--c-border)    → var(--g-border)
```

在 `site/layout_head.php` 引入：
```html
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/gomag.css">
```

site 目錄下既有的 inline `<style>` 全部換成 g- class。

### 1.3 影響檔案
- `site/index.php`
- `site/services.php`
- `site/cases.php`
- `site/testimonials.php`
- `site/layout_head.php`（新增 gomag.css 引入）
- `site/layout_foot.php`

---

## Phase 2：UI Redesign

### 2.1 設計風格定位

**目標形象**：在地專業店家的官方網站
- 不要太「品牌極簡風」（顯得冷漠不接地氣）
- 不要太「夜市熱炒風」（顯得不專業）
- **平衡點**：乾淨、溫暖、有人味、CTA 強

**參考方向**：
- 日本商家網站（用心、細節、信任）
- Apple Local Stores 在地頁面（簡潔但豐富）
- 吉野家官網的服務型店家風格（明確、有溫度）

---

### 2.2 新版 Hero 設計

```
┌──────────────────────────────────────────────┐
│  [深色覆蓋圖] 全屏背景圖                        │
│                                                │
│   🏷️ TAG（業種：居家清潔）                      │
│                                                │
│   旭浪清潔                          ← 大標題     │
│   台南最值得信賴的專業清潔                       │
│                                                │
│   ★ 4.9    8 年    500+    98%                │
│   評分     經驗    客戶     回購率              │
│                                                │
│   [💬 LINE 預約]  [📞 06-205-1129]            │
│                                                │
│   📍 台南市永康區　🕐 週一-週六 09-21          │
└──────────────────────────────────────────────┘
```

關鍵元素：
- 全屏 Hero（高度 70vh，手機 60vh）
- 真實背景圖（從 client.hero_image_path 讀，沒有就用業種預設圖）
- 暗色 overlay（30-50% 黑底，文字白色）
- 4 個信任數字（hero_stats）
- 雙 CTA（LINE + 電話）
- 底部資訊列（地址 + 營業時間）

---

### 2.3 新增區塊

**A. 老闆／創辦人故事**（在「關於」之後）
```
┌──────────────────────┬─────────────────┐
│ [老闆照片]            │ "在台南做清潔已經  │
│                       │ 8 年了，這份工作   │
│                       │ 看似簡單，但每個   │
│                       │ 細節都馬虎不得..."│
│                       │                   │
│                       │ — 旭浪清潔 王老闆 │
└──────────────────────┴─────────────────┘
```
DB 加 `owner_name`、`owner_intro`、`owner_avatar` 三欄位，沒填就不顯示這區塊。

**B. Google 評分強化**（取代或補充現有 testimonials）
```
┌─────────────────────────────────────┐
│      ⭐ Google 評價 4.9 / 5.0       │
│      [大字]                          │
│      共 127 則評價                   │
│                                       │
│      [評價 Card x 3]                 │
│                                       │
│      [在 Google 看更多 →]            │
└─────────────────────────────────────┘
```
從 `google_reviews.php` 拉真實 Google 評論，比 testimonials 表更可信。

**C. 預約時段選擇**（在 CTA 之前）
```
┌─────────────────────────────────────┐
│      🕐 立即預約                      │
│                                       │
│      [今天 5/2]  [明天 5/3]  [更多]  │
│       下午 3 個時段可選               │
│                                       │
│      或直接 [LINE 預約 →]            │
└─────────────────────────────────────┘
```
即使沒做時段管理系統，先放假按鈕導到 LINE，視覺上有完整服務感。

---

### 2.4 業種變化強化

現有的 `$isFood` 切換只改文案，視覺一樣。要加：

**餐飲業**
- Hero 用菜餚特寫圖
- 服務區塊改菜單卡片風（價格醒目、附簡短描述）
- 案例改料理 portfolio（grid + lightbox）
- 加菜單下載 PDF 連結

**服務業（清潔/汽美/居家）**
- Hero 用施工現場 / 老闆團隊圖
- 服務區塊保留卡片風格但加「適用情境」副標
- 案例強化 Before/After 對比
- 加估價計算器（簡單的：坪數 × 單價）

---

### 2.5 響應式設計準則

**Breakpoints**
- Mobile：< 640px（一欄）
- Tablet：640-1024px（雙欄）
- Desktop：> 1024px（三欄或四欄）

**手機優先**
- Hero 必看：標題、評分、雙 CTA（不要被砍）
- 服務卡片：手機改 carousel（左右滑）
- 評價：手機改一欄滑動

**Touch target**
- 最小 48×48px（Material Design）
- LINE 浮動按鈕：64×64px

---

## Phase 3：可選進階（後期）

1. **多語系**：日文/英文版（給觀光區業者）
2. **AI 助理**：「找你需要的服務」輸入框（接 GPT API）
3. **個人化推薦**：「最近其他客戶也預約了...」
4. **線上付款**：定金 LINE Pay
5. **顧客系統**：登入後看訂單、回購折扣

---

## 開工前要釐清的事

1. **Owner 故事區要不要做**？要做的話 DB 要加欄位
2. **是否動 layout_head.php 的導覽列**？還是只動內容區
3. **業種偵測邏輯**：目前用 `$client['industry']` 字串匹配，是否要改用 categories 表的 `slug`
4. **Hero 預設圖庫**：每個業種要 1-3 張高品質預設圖（從 Unsplash 或購買）
5. **Mobile-first 程度**：要從零做手機版還是先 desktop 再響應式

---

## 實作優先順序

1. **Day 1**：Phase 1.1 修 animate-in bug（馬上能看到改善）
2. **Day 2**：Phase 1.2 CSS 系統升級到 g-（基礎工程）
3. **Day 3-4**：Phase 2.2 新 Hero 重做
4. **Day 5**：Phase 2.3.A Owner 故事區
5. **Day 6**：Phase 2.3.B Google 評分強化
6. **Day 7-8**：Phase 2.4 業種視覺差異化
7. **Day 9**：Phase 2.5 響應式檢查
8. **Day 10**：QA + 部署

---

## 帶到 Claude Code 的開頭指令範例

```
讀 CLAUDE.md、docs/MINI_SITE_FRONTEND.md、docs/MINI_SITE_REDESIGN_BRIEF.md。

先做 Phase 1.1：找出 animate-in 動畫卡死的原因，修好它。
完成後讓我看 http://localhost:8888/miniweb/site/index.php?sub=xusen
確認效果，再進 Phase 1.2。
```
