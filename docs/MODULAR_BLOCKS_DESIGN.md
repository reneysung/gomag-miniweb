# Modular Blocks 架構設計（v1）

> **目的**：用「核心欄位 + 模組化區塊」替代每個產業寫一套樣板的舊思路。
> 一張通用主表 + 一張區塊表 + 5 個 block partial 檔案，全站所有產業共用。

---

## 一、架構總覽

```
[stores]            ← 通用主表，所有店家共用 ~20 個欄位
   ↓ 1:N
[store_blocks]      ← 產業特化內容，每筆一個區塊（type + JSON data）
                      type ∈ {service, menu, portfolio, pricing, faq}
```

前端：
```php
foreach ($store->blocks as $block) {
    include __DIR__ . "/blocks/{$block->type}.php";
}
```

後台：
```
選分類 → 系統帶入該分類建議區塊 → 老闆只看到要填的欄位 → 存檔
```

---

## 二、DB Schema

### 主表 `stores`（通用核心，~20 欄位）

> 對應現有 `clients` 表，新平台可考慮重命名或併入。

```sql
CREATE TABLE stores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- 基本識別
  slug          VARCHAR(64) UNIQUE NOT NULL,       -- URL slug
  subdomain     VARCHAR(64) UNIQUE,                -- mini-site 子網域（同 slug）
  category_id   INT UNSIGNED NOT NULL,             -- 分類
  
  -- 顯示用
  name          VARCHAR(120) NOT NULL,             -- 店名
  tagline       VARCHAR(255),                      -- slogan
  about_text    TEXT,                              -- 關於我們
  owner_intro   TEXT,                              -- 老闆/創辦人簡介
  
  -- 聯絡資訊
  address       VARCHAR(255),                      -- 完整地址（含縣市前綴）
  phone         VARCHAR(40),
  line_id       VARCHAR(80),
  website_url   VARCHAR(255),                      -- 外部官網
  hours         TEXT,                              -- 營業時間（多行字串）
  
  -- 媒體
  hero_image    VARCHAR(255),                      -- Hero 主圖路徑
  photos        JSON,                              -- 額外照片陣列 ["path1","path2"]
  
  -- 商業資訊
  average_spend INT,                               -- 平均消費（NULL 表不顯示）
  
  -- Google
  google_place_id VARCHAR(80),
  google_maps_embed TEXT,
  rating          DECIMAL(2,1),                    -- 0.0 ~ 5.0
  google_review_count INT DEFAULT 0,
  
  -- 地理座標（給地圖用）
  lat DECIMAL(10,7),
  lng DECIMAL(10,7),
  
  -- 狀態旗標
  is_active      TINYINT(1) DEFAULT 1,
  is_placeholder TINYINT(1) DEFAULT 0,             -- 資料整理中
  is_verified    TINYINT(1) DEFAULT 0,             -- 已驗證
  has_minisite   TINYINT(1) DEFAULT 0,             -- 啟用子網域
  
  -- SEO
  meta_title    VARCHAR(255),
  meta_desc     VARCHAR(500),
  meta_keywords VARCHAR(255),
  og_image      VARCHAR(255),
  
  -- 時間戳
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_category (category_id),
  INDEX idx_slug (slug),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### 區塊表 `store_blocks`

```sql
CREATE TABLE store_blocks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id   INT UNSIGNED NOT NULL,
  type       ENUM('service','menu','portfolio','pricing','faq') NOT NULL,
  data       JSON NOT NULL,                       -- 區塊內容（每種 type 結構不同）
  sort_order INT DEFAULT 0,
  is_active  TINYINT(1) DEFAULT 1,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_store_sort (store_id, sort_order, is_active),
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);
```

### 分類建議區塊對映表 `category_block_suggestions`（驅動後台 UI）

```sql
CREATE TABLE category_block_suggestions (
  category_id INT UNSIGNED NOT NULL,
  block_type  ENUM('service','menu','portfolio','pricing','faq') NOT NULL,
  is_required TINYINT(1) DEFAULT 0,                -- 必填
  default_sort INT DEFAULT 0,                      -- 預設排序
  PRIMARY KEY (category_id, block_type),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

---

## 三、5 種 Block 的 JSON 結構

### 1. `service`（服務項目）
```json
{
  "title": "服務項目",                    
  "items": [
    {
      "icon": "🛡",
      "name": "XPEL 頂級漆面保護膜",
      "short_desc": "全車犀牛皮包覆，10年保固",
      "price_text": "$38,000 起",
      "image": "uploads/services/xpel.jpg"
    }
  ]
}
```
**適用**：汽車美容、居家服務、美容美髮、教育（課程）、醫療

### 2. `menu`（菜單）
```json
{
  "title": "菜單",
  "groups": [
    {
      "name": "前菜",
      "items": [
        {"name": "凱薩沙拉", "price": 280, "desc": "羅馬生菜+帕馬森起司"},
        {"name": "炸花枝圈", "price": 220, "desc": ""}
      ]
    },
    {
      "name": "主餐",
      "items": [...]
    }
  ]
}
```
**適用**：餐飲美食

### 3. `portfolio`（作品/氛圍/案例）
```json
{
  "title": "作品集",
  "layout": "grid",                              
  "items": [
    {
      "image": "uploads/portfolio/case1.jpg",
      "title": "白色 Tesla Model 3 全車包膜",
      "desc": "工時 3 天，使用 XPEL Ultimate Plus",
      "tags": ["XPEL", "Tesla"]
    }
  ]
}
```
**適用**：所有需要視覺呈現的產業（汽車、美髮、設計、餐廳氛圍照）

### 4. `pricing`（價目表）
```json
{
  "title": "價目表",
  "currency": "TWD",
  "items": [
    {
      "name": "標準包膜",
      "price": 38000,
      "unit": "整車",
      "features": [
        "XPEL Ultimate Plus 漆面保護膜",
        "10 年原廠保固",
        "無塵施工環境"
      ],
      "highlight": false
    },
    {
      "name": "頂級包膜（推薦）",
      "price": 58000,
      "unit": "整車",
      "features": [...],
      "highlight": true
    }
  ]
}
```
**適用**：教育補習班、醫療、汽車美容、健身房等需要明確價格的產業

### 5. `faq`（常見問題）
```json
{
  "title": "常見問題",
  "items": [
    {"q": "包膜會傷原廠漆嗎？", "a": "不會。XPEL 使用..."},
    {"q": "保固範圍？", "a": "..."}
  ]
}
```
**適用**：所有產業（萬用區塊）

---

## 四、分類 → 建議區塊對映

```php
// seed: category_block_suggestions
$suggestions = [
    '餐飲美食'   => ['menu', 'portfolio', 'faq'],
    '居家服務'   => ['service', 'pricing', 'portfolio', 'faq'],
    '美容美髮'   => ['service', 'pricing', 'portfolio'],
    '汽車服務'   => ['service', 'portfolio', 'pricing', 'faq'],
    '教育學習'   => ['service', 'pricing', 'faq'],
    '醫療診所'   => ['service', 'faq'],
    '婚禮活動'   => ['service', 'portfolio', 'pricing'],
    '休閒旅宿'   => ['service', 'portfolio', 'pricing', 'faq'],
    '專業諮詢'   => ['service', 'pricing', 'faq'],
    '購物零售'   => ['portfolio', 'pricing'],
    '寵物服務'   => ['service', 'pricing', 'faq'],
    '其他'       => ['service', 'faq'],
];
```

12 個分類 → 5 個 block 排列組合就夠。

---

## 五、檔案結構

```
miniweb/
├── store.php                    ← 主行銷頁，loop blocks
├── city.php                     ← 縣市落地頁
├── category.php                 ← 分類列表頁
│
├── blocks/                      ← 5 個 block partial（前端渲染）
│   ├── service.php
│   ├── menu.php
│   ├── portfolio.php
│   ├── pricing.php
│   └── faq.php
│
├── admin/
│   ├── stores.php               ← 店家列表
│   ├── store-edit.php           ← 編輯主表欄位
│   └── store-blocks.php         ← 編輯該店的 blocks
│       └── (依 block.type 動態載入對應表單 form-{type}.php)
│
├── admin/forms/                 ← 5 個 block 編輯表單
│   ├── form-service.php
│   ├── form-menu.php
│   ├── form-portfolio.php
│   ├── form-pricing.php
│   └── form-faq.php
│
├── includes/
│   ├── block_helpers.php        ← getStoreBlocks($id), renderBlock($block)
│   └── ...
│
└── migrations/
    ├── 010_create_stores.sql
    ├── 011_create_store_blocks.sql
    ├── 012_seed_block_suggestions.sql
    └── 013_migrate_clients_to_stores.sql
```

---

## 六、前端渲染邏輯

### `store.php`（簡化後）
```php
$store = getStore($slug);
$blocks = getStoreBlocks($store->id);
?>

<!-- Hero / About / 通用區塊 -->
<?php require 'partials/store-hero.php'; ?>
<?php require 'partials/store-about.php'; ?>

<!-- 動態 blocks -->
<?php foreach ($blocks as $block): ?>
  <?php
    $blockData = json_decode($block['data'], true);
    $blockType = $block['type'];
    require __DIR__ . "/blocks/{$blockType}.php";
  ?>
<?php endforeach; ?>

<!-- 通用尾巴 -->
<?php require 'partials/store-map.php'; ?>
<?php require 'partials/store-reviews.php'; ?>
```

### 範例 `blocks/menu.php`
```php
<section class="m-section block-menu">
  <h2 class="m-section-title"><?= h($blockData['title'] ?? '菜單') ?></h2>
  <?php foreach ($blockData['groups'] as $group): ?>
    <div class="menu-group">
      <h3><?= h($group['name']) ?></h3>
      <ul>
        <?php foreach ($group['items'] as $item): ?>
        <li>
          <span class="name"><?= h($item['name']) ?></span>
          <span class="price">$<?= number_format($item['price']) ?></span>
          <?php if (!empty($item['desc'])): ?>
          <p class="desc"><?= h($item['desc']) ?></p>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>
</section>
```

---

## 七、後台流程

### 新增店家
1. **Step 1**：填基本資料（store 主表欄位 — 名稱、地址、電話、分類）
2. **Step 2**：選分類 → 系統依 `category_block_suggestions` 帶入建議 block 清單
3. **Step 3**：依序填每個 block 的內容（用對應的 `form-{type}.php`）
4. **Step 4**：預覽 → 儲存

### 後台 UI 概念
```
┌─────────────────────────────────┐
│ 編輯店家：鍍卡 Do Car            │
├─────────────────────────────────┤
│ [基本資料]  [區塊管理]  [SEO]    │
├─────────────────────────────────┤
│ 區塊管理                          │
│ ┌─────────────────────────────┐ │
│ │ ✅ 服務項目（service）   ⋮  │ │ ← 點 ⋮ 編輯/刪除/上下移動
│ │ ✅ 作品集（portfolio）   ⋮  │ │
│ │ ✅ FAQ（faq）           ⋮  │ │
│ │                              │ │
│ │ + 新增區塊 ▾                 │ │ ← 下拉只顯示該分類建議的
│ │   - 價目表（pricing）        │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

---

## 八、資料遷移策略（現有 → 新架構）

現有：`clients` + `services` + `cases` + `testimonials` + `landing_extra_content`

### 階段 1：併行（不破壞現有）
- 建 `stores` + `store_blocks` 新表
- 寫 `migrate_clients_to_stores.php`：
  - `clients` → `stores`（欄位對映）
  - `services` → `store_blocks` (type=service)
  - `cases` → `store_blocks` (type=portfolio)
  - `landing_extra_content` 內已有的 H3 結構 → 解析為對應 block（半自動）
  - `testimonials` 維持獨立表（評價不適合放 block）

### 階段 2：雙寫（新舊並存，灰度測試）
- 後台同時寫兩邊
- 前端切換到讀新表（feature flag）
- 觀察 1-2 週

### 階段 3：切換 + 清理
- 確認新表穩定後，舊 services/cases 表凍結（保留資料）
- 三個月後刪除舊表

---

## 九、Migration 腳本骨架

```php
// migrations/013_migrate_clients_to_stores.php
<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$db->beginTransaction();
try {
    // 1. clients → stores
    $clients = $db->query("SELECT * FROM clients")->fetchAll();
    foreach ($clients as $c) {
        $db->prepare("INSERT INTO stores (slug, subdomain, category_id, name, tagline, ...) VALUES (?, ?, ...)")
           ->execute([$c['slug'], $c['subdomain'], $c['category_id'], $c['brand_name'], $c['tagline'], ...]);
        $newId = $db->lastInsertId();
        
        // 2. services → store_blocks (type=service)
        $services = $db->prepare("SELECT * FROM services WHERE client_id=?");
        $services->execute([$c['id']]);
        $items = [];
        foreach ($services->fetchAll() as $s) {
            $items[] = ['icon' => $s['icon'], 'name' => $s['name'], 'short_desc' => $s['short_desc'], 'price_text' => $s['price_text']];
        }
        if ($items) {
            $db->prepare("INSERT INTO store_blocks (store_id, type, data, sort_order) VALUES (?, 'service', ?, 1)")
               ->execute([$newId, json_encode(['title' => '服務項目', 'items' => $items])]);
        }
        
        // 3. cases → store_blocks (type=portfolio)
        // ... 同上邏輯
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

---

## 十、實作優先順序（建議給 Claude Code）

1. **建表** — 跑 migrations 010, 011, 012
2. **建 5 個 block partial**（`blocks/*.php`）+ 預設 CSS 樣式
3. **改 store.php** — 改用 loop blocks 渲染（保留舊邏輯做 fallback）
4. **跑 migration 013** — clients → stores 資料遷移
5. **驗證前台** — 至少 5 家不同分類的店家頁能正常渲染
6. **後台改造** — store-blocks.php + 5 個 form-{type}.php
7. **觀察 1-2 週** — 雙寫穩定後，把舊 store.php 路徑導向新版
8. **清理舊表**（services, cases）

---

## 十一、開放性問題（給 Claude Code 開工前釐清）

1. **`testimonials` 要不要也 block 化**？（評價有獨立排程、認證機制，建議獨立表）
2. **landing_extra_content（WYSIWYG HTML）保留嗎**？— 建議保留為「自由內文」block 之外的補充
3. **Block 是否支援多語系**？（中文 vs 英文，未來擴張）
4. **後台是否要 drag-and-drop 排序**？（用 JS sortablejs）
5. **預設新分類時，建議區塊是固定的還是可動態調整**？

---

## 十二、效能考量

- `stores` JOIN `store_blocks` 一次抓出（一個 store 通常 2-5 個 blocks）
- JSON 欄位用 MySQL 5.7+ 的 JSON 型別（支援索引）
- 前端 cache：Hero 圖、blocks 結果可 file cache 或 Redis（後期需要）

---

**結論**：這個架構讓「12 個分類」變成「1 主表 + 5 block 排列組合」，後端工作量大幅減少，未來擴張新分類只是 seed 一筆 `category_block_suggestions`，零 schema 變動。
