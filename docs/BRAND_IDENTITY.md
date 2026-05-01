# Brand Identity Brief — 口碑製造所 × 店家好口碑

> 任務：為夠創意有限公司旗下兩個對外並存品牌設計 logo 與 brand identity。
> 版本：v2（基於既有「夠創意簡報」和雙品牌並存決定重寫）

---

## 一、品牌結構（已敲定）

```
夠創意有限公司（隱形法人，僅出現在合約/發票/法務文件）
   ↓ 對外不露出
   
[ 兩個對外並存品牌 ]
   
口碑製造所                       店家好口碑
（B 端公司主品牌）                （C 端產品）
官網、名片、提案、簡報             gomag.com.tw
給「商家」看                      給「消費者」用
```

**對應比喻**：
- 口碑製造所 ≈ Anthropic（公司，賣服務給企業）
- 店家好口碑 ≈ Claude.ai（產品，給一般人用）
- 夠創意 ≈ 公司登記名（內部、法務、不對外）

---

## 二、口碑製造所定位（B 端公司主品牌）

### 業務範圍（從現有夠創意簡報整理）

口碑製造所承接夠創意所有業務：

| 服務線 | 內容 |
|---|---|
| **口碑內容行銷**（主力） | 心得分享文、SEO 關鍵字文章、客戶訪談 |
| **全平台佈局** | FB / IG / 小紅書 / YouTube / 抖音 / Threads / Dcard / Mobile01 |
| **網站架設** | 品牌官網、電商、小官網（接 gomag 平台）|
| **平面設計** | 名片、DM、海報、包裝 |
| **印刷服務** | 各式印刷一手包辦 |
| **客戶曝光通路** | 把服務客戶上架到「店家好口碑」(gomag.com.tw)，C 端搜尋曝光 |

### 客群
- 在地店家（5 人以下小型）
- 中型企業
- B2B + B2C + 全產業

### 定位 keyword
**職人 ・ 用心 ・ 製造口碑 ・ 真實 ・ 不誇張**

### 品牌調性
| 要的 | 避開的 |
|---|---|
| 像京都老舖匠人 | 像科技新創 SaaS |
| 「我們慢慢幫你打磨」 | 「快速增加曝光」「成長 hack」 |
| 印章、招牌、工坊 | 螢光、漸層、3D |
| 沉穩黑、深褐、米色 | 螢光黃、霓虹粉、深藍 navy |
| 宋體、楷書 | 黑體粗字、英文 sans-serif |

### slogan 候選（可選一個）
1. 「為店家，製造看得見的口碑」
2. 「好店家，值得被看見」
3. 「把好店家做給世界看」
4. 「真實聲音，匠心製造」（呼應夠創意簡報「真實的聲音，比廣告更有力量」）
5. 「口耳相傳的時代，我們替你製造」

---

## 三、店家好口碑定位（C 端產品）

### 服務本質
**消費者找店的口碑平台** — 依縣市、依分類，看真實口碑找在地店家。

### 客群
**消費者**（找店家的一般人，沒在意是誰做的後台）

### 定位 keyword
**在地 ・ 真實 ・ 推薦 ・ 鄰居 ・ 溫度**

### 品牌調性（已敲定，沿用 gomag.css）
| 要的 | 避開的 |
|---|---|
| 像鄰居推薦 | 像評分網站（Yelp、Foursquare）|
| 暖手寫感 | 冰冷網路風 |
| 在地店家照片 | 商攝棚拍 |
| 暖橘 + 米黃 | 純黑白 |

### slogan 候選
1. 「在地人推薦的，才是真的」
2. 「找好店，問鄰居就對了」
3. 「真實口碑，在地推薦」
4. 「你家附近的好店，都在這」

---

## 四、視覺呼應線索（顯性呼應，C 流程）

兩個 logo 看得出是兄弟，但定位不同。共用三個 DNA：

### DNA 1：同款主字型
**兩品牌標題都用 思源宋體 Heavy（Noto Serif TC 900）**

### DNA 2：同款裝飾元素 — 「印章感」
兩個 logo 都有「印章氛圍」，但形狀不同：
- 口碑製造所：**方形印章** ▢（穩重、職人）
- 店家好口碑：**圓形印章** ◯（親切、溫暖）

### DNA 3：同色家族，不同基調
兩品牌都用「米色基底」，但強調色不同：

```css
/* 共用 */
--shared-cream:  #F5EDE0   /* 米奶色，兩品牌都用做底 */
--shared-paper:  #FAF6EE   /* 紙黃，輔助 */

/* 口碑製造所（沉穩）*/
--km-ink:        #1A1A1A   /* 墨黑（主）*/
--km-bronze:     #3D2817   /* 深褐（強調）*/
--km-stamp:      #8B0000   /* 印泥紅（小面積點綴）*/

/* 店家好口碑（溫暖）*/
--g-accent:      #FF5A36   /* 暖橘（主，已有）*/
--g-rose:        #C84545   /* 鄰家紅 */
--g-ink:         #191919   /* 墨黑（同款，但用較少）*/
```

兩邊放在一起時，米色基底會讓人覺得「同一家公司」，但黑色 vs 橘色的主色差異能識別「這是兩個品牌」。

---

## 五、口碑製造所 Logo 方向（職人 + 極簡）

### 方向 A：方框印章 ⭐ 我推薦
```
┌────────┐
│  口 碑 │
│  製造所 │
└────────┘
```
- 像招牌、像印章、像工坊門牌
- 純宋體字 + 細線方框
- 黑白為主

### 方向 B：書法墨痕
```
口 碑
製造所  ─── （一道墨色橫槓）
```
- 用書法字體，下方有墨色橫槓
- 像信件落款、像畫家署名

### 方向 C：極簡 wordmark
```
口碑製造所
KÔBI MAKER
```
- 純字體 logo，無裝飾
- 中文 + 羅馬拼音對照

### 字體
- **主字**：Noto Serif TC Heavy（思源宋體）或 源樣明體
- **副字**：Noto Sans TC Regular
- **數字／英文**：Cormorant Garamond 或 Fraunces

### 應用場景
- 名片、官網、簡報（簡報重做，內容保留但視覺換）
- Email 簽名
- 提案文件
- B 端業務溝通用 PPT 模板

---

## 六、店家好口碑 Logo 方向（在地溫暖 + 手寫）

### 方向 A：圓形印章 + 大字 ⭐ 我推薦
```
   ╭────╮
  │  好  │     ← 大「好」字（像贊同章）
   ╰────╯
   店家好口碑   ← wordmark
```
- 圓印章感（呼應口碑製造所的方框，但是圓）
- 中央大字
- 暖橘配色

### 方向 B：手寫 wordmark + 大拇指
```
店家好口碑 👍
```
- 手寫字體
- 後面接 emoji 或 SVG 大拇指

### 方向 C：對話框
```
   ╭───╮
  ⟨ 好店⟩
   ╰─╯
   店家好口碑
```
- 對話框造型（呼應「口耳相傳」）

### 字體
- **主字**：Noto Serif TC Heavy（與口碑製造所同款 → 家族感）
- **手寫副字**：Klee One / Caveat（英文）
- **wordmark 變體**：jf open 粉圓（圓潤親切）

### 應用場景
- gomag.com.tw 網站 header
- 各店家小官網的 footer「by 店家好口碑」
- 行動版 favicon、社群頭像

---

## 七、簡報 / 既有資產轉換策略

夠創意簡報（深藍 navy + 螢光黃橘漸層）的處理方式：

| 元素 | 怎麼辦 |
|---|---|
| **內容**（業務介紹、四步驟、平台佈局、定價） | ✅ 保留，所有 slide 文字繼續用 |
| **品牌名** | 「夠創意有限公司」 → 換成「口碑製造所」 |
| **slogan** | 「讓我們成為您的最強業務員」可保留或換成口碑製造所 slogan |
| **視覺**（深藍 + 螢光） | ❌ 重做成「米色 + 墨黑 + 印章感」職人風 |
| **配色變數** | navy/yellow/orange/pink → cream/ink/bronze/stamp-red |
| **「✦」星形裝飾** | 換成印章方框 ▢ 或 圓 ◯ |

### 重做後的簡報主視覺示意
```
舊：深藍 navy 底 + 螢光黃 wordmark + 漸層
新：米色背景 + 墨黑宋體 wordmark + 紅色小印章「製」
```

---

## 八、Logo 製作流程（給 Claude Code）

### Step 1：HTML mockup 探索
```
docs/logo-mockups/
├── km-A-frame-stamp.html       ← 口碑製造所 方框印章
├── km-B-calligraphy.html       ← 口碑製造所 書法墨痕
├── km-C-minimal.html           ← 口碑製造所 極簡 wordmark
├── gomag-A-circle-stamp.html   ← 店家好口碑 圓印章
├── gomag-B-handwrite.html      ← 店家好口碑 手寫
└── gomag-C-bubble.html         ← 店家好口碑 對話框
```

每個 mockup 含：
- Logo 主體大顯
- 不同尺寸（240px / 80px / 32px）
- 黑白版
- 在 4 種背景的應用（米黃 / 米奶 / 黑底 / 白底）
- Wordmark 字距字級調校

### Step 2：你選方向（B 端 + C 端各一）

### Step 3：定稿 SVG
```
assets/logo/
├── koubei-maker-logo.svg
├── koubei-maker-logo-mono.svg
├── koubei-maker-mark.svg          ← 純印章（無 wordmark）
├── koubei-maker-favicon.svg
├── gomag-logo.svg
├── gomag-logo-mono.svg
├── gomag-mark.svg
└── gomag-favicon.svg
```

### Step 4：應用範例
```
docs/brand-applications/
├── km-businesscard.html       ← 口碑製造所 名片
├── km-website-header.html
├── km-presentation-cover.html  ← 取代既有夠創意簡報
├── gomag-website-header.html
└── gomag-social-cover.html
```

### Step 5：Brand Guidelines
```
docs/BRAND_GUIDELINES.md
- 兩品牌 logo 使用規範
- 不可使用方式
- 配色 do/don't
- 字體 hierarchy
```

---

## 九、新版業務簡報 — 重做計畫

把既有夠創意簡報（goucreative-presentation.html）改造成「口碑製造所版」：

```
docs/presentations/
└── koubei-maker-pitch.html
```

**內容保留**：
- 8 個 slide 結構不變
- 業務介紹、4 步驟、平台佈局、定價對比 全保留
- 部分文案調整（「夠創意」→「口碑製造所」）

**視覺重做**：
- navy 深藍 → cream 米色
- 螢光漸層 → 單色墨黑 + 印泥紅點綴
- 圓點裝飾 → 方框印章
- sans-serif 標題 → 思源宋體

---

## 十、給 Claude Code 的開頭指令

```
讀 docs/BRAND_IDENTITY.md（v2 雙品牌並存版）。

開始 Phase 品牌設計：

Phase 1：Logo 概念探索（HTML mockup）
在 docs/logo-mockups/ 下生成 6 個 HTML mockup：
- 口碑製造所 3 個方向（A 方框印章 / B 書法墨痕 / C 極簡）
- 店家好口碑 3 個方向（A 圓印章 / B 手寫 / C 對話框）

每個 mockup 包含：
- Logo 主體（240px）
- 不同尺寸（80px / 32px）
- 黑白版
- 4 種背景應用（米黃 / 米奶 / 黑底 / 白底）

風格嚴守：
- 口碑製造所：職人 + 極簡，墨黑 / 深褐 / 米色，宋體
- 店家好口碑：在地 + 暖手寫，暖橘 / 米黃，宋體 + 手寫副字
- 兩品牌都用思源宋體 Heavy 主字 + 米色基底（家族感）

完成後讓我看 6 個 mockup，我各選一個方向。

接著：
Phase 2：定稿 SVG
Phase 3：應用範例（名片、網站 header）
Phase 4：重做簡報（基於 docs/uploads/goucreative-presentation.html，
        套用口碑製造所新視覺）
```

---

## 十一、待釐清細節（給 Claude Code 開工前確認）

1. **slogan 各選一個**
   - 口碑製造所：1～5 哪一個？
   - 店家好口碑：1～4 哪一個？

2. **英文名**
   - 口碑製造所：KÔBI MAKER / Word of Mouth Atelier / GoodWord Studio?
   - 店家好口碑：GoMag（已有）/ Local Picks?

3. **印章感裝飾元素的具體形狀**
   - 方框 / 圓 / 雙圓 / 半月

4. **印泥紅小點綴是否要用**
   - 有：增加職人質感
   - 無：更極簡

5. **既有夠創意簡報的「✦」星形要換成什麼**
   - 印章 ▢
   - 圓點 ●
   - 方塊 ■
   - 其他

6. **網域 / 識別**
   - 口碑製造所要用什麼網域？（kobei.tw / koubei-maker.com / wom-atelier.com）
   - 還是直接放在 gomag.com.tw 子目錄（gomag.com.tw/maker）？

---

**結論**：兩個對外品牌「同款字 + 米色基底 + 印章感」串起家族，但用「方 vs 圓」「黑 vs 橘」「沉穩 vs 親切」做出區隔。夠創意退到法務幕後，舊簡報內容保留但視覺要重做成口碑製造所版。
