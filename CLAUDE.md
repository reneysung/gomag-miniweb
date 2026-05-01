# 店家好口碑（gomag.com.tw）— Claude Code Handoff

## 專案概觀

PHP 主站 + miniweb 子網域 + 後台管理。約 200 家在地客戶。Hostinger Premium + MariaDB。

- **本機開發**：`/Users/songmingwei/Sites/localhost/miniweb/`（你正在的位置）
- **Production**：Hostinger，網域 `aqua-elephant-856571.hostingersite.com`，DNS 切換前是測試網域
- **正式網域**（DNS 切換後）：`www.gomag.com.tw`
- **本機測試**：MAMP（檢查 `/Applications/MAMP/htdocs/miniweb/` 是否同步或為符號連結）

## 環境變數判斷

`includes/config.php` 用 `IS_LOCAL` 常數區分。本機/production URL 自動切換。

## 重要架構

```
/index.php           首頁（含分類入口、縣市入口、精選店家）
/category.php        分類列表 (/category/{slug})
/city.php            縣市落地頁 (/city/{slug}) ← 新加
/store.php           店家行銷頁 (/store/{slug}) ← 含 placeholder UI
/sitemap.php         動態 sitemap.xml
/.htaccess           URL rewrite
/admin/              後台
/site/               子網域官網 (mini-site)
/main/layout_*       共用 head/foot
/includes/           config / helpers / google_reviews / seo_schema
```

## DB 重點 schema（clients 表）

```
id, slug, subdomain, brand_name, tagline, address, phone,
category_id, hero_image_path, has_minisite, external_website_url,
about_text, about_tags (JSON), hero_stats (JSON), business_hours,
google_place_id, google_maps_embed,
landing_extra_content (HTML, WYSIWYG),
store_meta_title, store_meta_desc, store_keywords, store_og_image,
is_active, is_placeholder ← 新加
```

## SEO 縣市落地頁（剛完成，未部署）

### 目的
針對「[縣市] + [服務]」這類 local search query 開專屬頁面。Google 排名顯著。

### 已實作
1. **`city.php`**（新）：模式 A 全縣市總覽 + 模式 B 個別縣市頁。含 `CollectionPage` + `ItemList` JSON-LD、在地內文（避 doorway pages 懲罰）、按分類分群店家
2. **`.htaccess`**：加 `/city` 與 `/city/{slug}` rewrite
3. **`sitemap.php`**：新增縣市頁 entry（≥3 家才上 sitemap）
4. **`store.php`**：
   - placeholder UI（`is_placeholder=1` 的客戶顯示「📋 資料整理中」橫幅）
   - 麵包屑加縣市（首頁 > 📍縣市 > 分類 > 店家）
   - JSON-LD 從 address 自動偵測 addressLocality（不再寫死台南市）
   - 舊 slug 301 redirect（目前 `docaroating → docar`）
   - landing_extra_content 渲染前自動補 `<img>` alt
5. **`index.php`**：首頁加「📍 依縣市瀏覽」區塊

### 縣市 slug 對映
```php
'tainan'    => '台南市',  'kaohsiung' => '高雄市',
'chiayi'    => '嘉義市',  'taichung'  => '台中市',
'taipei'    => '台北市',  'newtaipei' => '新北市',
'taoyuan'   => '桃園市',  'taitung'   => '台東縣',
'pingtung'  => '屏東縣',  'hsinchu'   => '新竹市',
'yilan'     => '宜蘭縣',  'hualien'   => '花蓮縣',
```

### 各縣市現況（DB 統計）
- 🟢 台南市 157 家（正式）
- 🟢 高雄市 18 家（正式）
- 🟢 嘉義市 5 家（正式）
- 🟢 台中市 5 家（正式 2 + placeholder 3：肉夯夯韓式燒肉、鬥牛士二鍋文心店、鬥牛士二鍋新時代店）
- 🔴 其他縣市 < 3 家，sitemap 不收錄

## 已修復的近期問題

1. **store.php 儲存後空白頁** — 根因：`hero_stats` / `about_tags` 欄位缺失。已加 schema、補渲染
2. **docaroating slug 拼錯** — 改成 `docar`，加 301 redirect
3. **WYSIWYG content 結構壞** — 空 H3、誤標 H3 已 SQL 清理
4. **177 家地址無縣市前綴** — 已批次補（先預設台南，再用電話區碼智能修正 23 家到正確縣市）

## 待辦（下次工作項目）

### 短期（一週內）
1. **部署縣市落地頁到 production**
   - 5 個檔案：`city.php`、`.htaccess`、`sitemap.php`、`store.php`、`index.php`
   - 部署方式：之前用 Hostinger File Manager API（POST `/api/resources/public_html/{file}?override=true`，需 JWT from `localStorage.jwt`）
   - 上 GSC 重新提交 sitemap
2. **本機測試**（如果 MAMP DB 沒有 placeholder 客戶資料，會看不到效果 → 可從 production 匯出 clients 表）
3. **後台加 placeholder 篩選**（admin/clients.php 加篩選器，方便管理）

### 中期（架構升級）
4. **Modular Blocks 架構導入** ⭐
   - 設計文件：`docs/MODULAR_BLOCKS_DESIGN.md`（已寫好，含 schema、migration、實作優先順序）
   - 核心概念：1 個通用 `stores` 表 + 1 個 `store_blocks` 表（5 種 type）替代每產業客製樣板
   - 12 個分類 → 5 個 block 排列組合，未來擴新分類零 schema 變動
   - 階段 1：建表 + 5 個 block partial
   - 階段 2：clients → stores 資料遷移
   - 階段 3：後台改造（form-{type}.php 動態載入）

### 後期
5. **Phase 7 旭浪搬遷**（暫停中，CodeIgniter 3 + PHP 8.3 不相容，需切 PHP 8.0）
6. **Phase 6 DNS 切換**（等旭浪 OK）
7. **觀察 24-48 小時 + 資安加固**

## Hostinger File Manager API（部署用）

從瀏覽器 console（已登入 File Manager 時）：
```js
const jwt = localStorage.getItem('jwt');
const prefix = location.pathname.split('/files/')[0]; // /3d17c7f3a4ce581b
// POST 創建/覆寫
fetch(prefix + '/api/resources/public_html/{path}?override=true', {
  method: 'POST',
  headers: { 'X-Auth': jwt, 'Content-Type': 'application/octet-stream' },
  body: bytes
})
// DELETE 刪除
fetch(prefix + '/api/resources/public_html/{path}', {
  method: 'DELETE', headers: { 'X-Auth': jwt }
})
```

## 慣用工作流

- **DB 修補/一次性 script** → 寫小 PHP，base64 上傳，URL 執行，自我刪除（`@unlink(__FILE__)`）
- **新功能開發** → 本機編輯 + MAMP 測 + 一次性部署（不要 hot-patch production）

## 風格慣例

- 中文字使用「台」不用「臺」（DB 內部統一過）
- 註解中文 OK
- 行內 style 大量使用（CSS class 為輔）
- 遵循 traditional-chinese-writing skill 的台灣繁體規範

## 客戶/品牌

- 主站：店家好口碑
- 域名：gomag.com.tw
- 老闆 reney.sung@gmail.com
