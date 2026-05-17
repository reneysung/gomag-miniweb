# Hostinger hPanel 子網域設定 SOP

> 用途：新增 mini-site 客戶後，在 Hostinger hPanel 登記子網域 + 簽 SSL
> 適用：每加一個 `has_minisite=1` 的客戶都要做這一步
> 時間：第一次 5-10 分鐘、熟練後 2-3 分鐘
> 最後更新：2026-05-17

---

## 為什麼必須做這一步

Hostinger Premium 方案的 LiteSpeed vhost 會 **reject 未登記的子網域**（回 403），即使 DNS wildcard 已解析到主機 IP。

```
DNS 解析     ✅ wildcard *.gomag.com.tw → 145.79.14.161
hPanel vhost ❌ 沒登記 → LiteSpeed 拒絕（403）
程式 site/   ✅ 程式邏輯都對、但根本進不了
```

→ **hPanel 登記是必經步驟**，沒繞過去的 hack（除非升級到 Business 方案 + API）。

---

## 標準流程

### 1. 確認客戶在 admin 已建好

```
□ admin 新增客戶
□ 勾「啟用子網域小官網」(has_minisite=1)
□ 填基本資料：brand_name / industry / address / phone / hero / logo
□ 至少 1-2 個 services / cases
□ 主站預覽 /store/{slug} 看資料對
```

### 2. 進 Hostinger hPanel

```
URL: https://hpanel.hostinger.com/
帳號: reney.sung@gmail.com（或公司帳號）
```

### 3. 找到 gomag.com.tw 的 Subdomains

```
左側選單 → Domains
→ 找 gomag.com.tw 點進去
→ 上方分頁 → Subdomains
```

### 4. Create Subdomain

點「Create subdomain」按鈕，填：

| 欄位 | 值 | 說明 |
|---|---|---|
| **Subdomain** | `{slug}` | 例：rre / happysteakcyi |
| **Domain** | gomag.com.tw | 預設 |
| **Document Root** | `public_html` | ⚠️ **改掉預設的 `public_html/{slug}`**，必須指向 `public_html` 否則會建新空資料夾 |

點「Create」。

### 5. 啟用 SSL

建好後：
```
□ 找到剛建的子網域那一列
□ 點「SSL」按鈕（或進該子網域設定）
□ 啟用「Auto SSL」/「Let's Encrypt」（免費）
□ 啟用「Force HTTPS」
```

### 6. 等 SSL 簽好

```
□ 5-10 分鐘（背景自動簽）
□ 可在 SSL 頁面看狀態：Pending → Issued
```

### 7. 驗證

```bash
# 應該回 200 OK
curl -sI https://{slug}.gomag.com.tw/

# 瀏覽器訪問
https://{slug}.gomag.com.tw/
```

看到 mini-site 完整版 = 成功 ✅

### 8. 回 admin 標記完成

```
□ 進該客戶 settings.php
□ 找「✅ hPanel 子網域已設定」checkbox
□ 勾起來
□ 儲存
```

→ Dashboard 子網域待辦清單自動移除該客戶。

---

## 常見問題

### Q1：子網域訪問還是 403？
- 等 SSL 簽完（5-10 分鐘）
- Force HTTPS 沒勾的話會 HTTP 200 但 HTTPS 403
- 重新整理瀏覽器（Cmd+Shift+R 強制重整）

### Q2：訪問顯示「Index of /」目錄列表？
- Document Root 沒指對。建了 `public_html/{slug}` 空資料夾。
- 進該子網域設定改 Document Root 為 `public_html`

### Q3：訪問顯示「網站不存在或已停用」？
- 進到 site/index.php 了、但 DB 找不到該 slug
- 確認 `clients.subdomain` 或 `clients.slug` 對得上 hPanel 子網域名
- 確認 `is_active=1` 且 `has_minisite=1`

### Q4：SSL 一直 Pending？
- DNS 沒生效（一般 wildcard 已 OK 不會發生）
- Hostinger 自動 retry 24h，若超過時間還沒簽就開 Hostinger ticket

### Q5：要不要每加一個子網域都重複這流程？
- 是。Hostinger Premium 不支援 API + wildcard SSL。
- 客戶數 > 20 家後建議升級 Business 方案，加 API 後可寫腳本自動化（一鍵建子網域）。

---

## 升級簡化方向（規劃中、目前未做）

| 方案 | 工程 | 月成本差 |
|---|---|---|
| Cloudflare DNS + Universal SSL | 30 min 設定 | 0 元（仍需 hPanel 登記）|
| Hostinger Business 方案 + API | 1 hr 寫腳本 | +NT$ 90/月（自動化建子網域 + SSL）|
| VPS 自管 + wildcard vhost | 1-2 天搬遷 | 變動（VPS NT$ 200-400/月）|

詳見前面 session 討論記錄。

---

## 已知已設定的子網域（2026-05-17 狀態）

| 子網域 | 客戶 | hPanel | SSL |
|---|---|---|---|
| artru.gomag.com.tw | 亞筑室內設計 | ✅ | ✅ |
| fulldemo.gomag.com.tw | 展示清潔工坊 | ✅ | ✅ |
| fooddemo.gomag.com.tw | 築炎日式燒肉酒場 | ✅ | ✅ |
| designdemo.gomag.com.tw | 衡作室內設計事務所 | ✅ | ✅ |
| lanhung.gomag.com.tw | 聯漢室內設計工作室 | ✅ | ✅ |
| 062051129.gomag.com.tw | 旭浪清潔（CodeIgniter 舊系統） | ✅ | ✅ |
| **happysteakcyi.gomag.com.tw** | 歡樂牛排嘉義店 | **❌ 待設** | **❌** |
| **rre.gomag.com.tw** | 亞雷清潔團隊 | **❌ 待設** | **❌** |

* xusen.gomag.com.tw 已撤下（301 跳 062051129）
