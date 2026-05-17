# Hostinger hPanel 子網域設定 SOP

> 用途：新增 mini-site 客戶後，在 Hostinger hPanel 登記子網域 + 簽 SSL + symlink
> 適用：每加一個 `has_minisite=1` 的客戶都要做這一步
> 時間：第一次 10-15 分鐘、熟練後 5 分鐘
> 最後更新：2026-05-17（rre + happysteakcyi 實做後 + Business 升級）

---

## 為什麼必須做這一步

Hostinger LiteSpeed 對「未登記的子網域」回 **403**，即使 DNS wildcard 已解析。
此外 Hostinger 把每個子網域當「**獨立 website**」管理，會建獨立 docroot
（`domains/{sub}.gomag.com.tw/public_html/`）— 與我們系統不相容（我們所有 mini-site
共用 `domains/gomag.com.tw/public_html/site/`）。

→ 解法：**hPanel 加 website + SSH symlink**（兩步驟）。

---

## 方案資訊（2026-05-17 已升級）

- **目前方案**：Business Web Hosting
- **Website quota**：**50 個**（用完再付）
- **SSL**：每個 website 自動簽 Let's Encrypt（5-10 分鐘）
- **計費**：$5.99/mo（promotional 12 個月），**2027-05-17 自動續訂 $16.99/mo**
  - ⚠️ 行事曆設提醒 2027-04 cancel auto-renewal 或重新議價

---

## 標準流程

### Step 1：確認客戶在 admin 已建好

```
□ admin 新增客戶（subdomain = slug，例：rre）
□ 填基本資料：brand_name / industry / address / phone / hero / logo
□ 勾「啟用子網域小官網」(has_minisite=1)
□ 至少 1-2 個 services 讓 mini-site 不空
□ 主站預覽 /store/{slug} 看資料對
```

### Step 2：hPanel 新增 website

```
URL：https://hpanel.hostinger.com/websites

1. 右上紫色「新增網站」按鈕（或英文「Add website」）
2. 選單彈出 → 點「客製化 PHP/HTML 網站」（不要 WordPress / Builder）
3. 進「您想使用哪個網域或子網域？」表單
4. input 輸入完整子網域：{slug}.gomag.com.tw（例：rre.gomag.com.tw）
5. dropdown 等 2-3 秒 出現「{sub}.gomag.com.tw — 您擁有此網域嗎？請使用它。」
6. 點 該 link → input 鎖定 + ✓ 綠勾
7. 右下「下一個 →」
```

**會看到一個誤導頁面**「將您的網域指向 Hostinger」要改 nameserver。**忽略這頁，直接點右上 X 關掉**。

理由：Hostinger 認為 gomag.com.tw 是「外部 domain」（雖然實際上 DNS 在同帳號），這個 nameserver 流程是給「真的外部 domain」用的。我們的子網域**不需要改 NS**（gomag.com.tw 的 wildcard 已涵蓋）。

關掉後回 Websites 列表，會看到新 website 已在頂部。**SSL 自動在背景簽，約 5-10 分鐘**。

### Step 3：SSH 改 symlink（關鍵步驟）

Hostinger 會建 `domains/{sub}.gomag.com.tw/public_html/`（內含 default.php 預設頁）。
我們要把它**改成 symlink 指向 gomag.com.tw 主 docroot**：

```bash
ssh hostinger-gomag

SUB=rre  # 換成你新建的 slug
HOSTDIR=/home/u331306067/domains/$SUB.gomag.com.tw
MAIN=/home/u331306067/domains/gomag.com.tw/public_html

# 1. rename 原本的 public_html（留備份）
mv $HOSTDIR/public_html $HOSTDIR/public_html.hostinger-default-backup

# 2. 建 symlink → 主 docroot
ln -s $MAIN $HOSTDIR/public_html

# 3. 確認
ls -la $HOSTDIR/
# 應該看到：lrwxrwxrwx ... public_html -> /home/u331306067/domains/gomag.com.tw/public_html
```

### Step 4：等 SSL 簽好（5-10 分鐘）

第一次訪問可能 SSL warning：等 5-10 分鐘 Let's Encrypt 自動簽。

```bash
# 驗證 SSL + 200 OK
curl -sI https://$SUB.gomag.com.tw/ | head -3

# 應看到：
# HTTP/1.1 200 OK
# Connection: Keep-Alive
# Cache-Control: public, max-age=300, must-revalidate  ← 我們的 mini-site header
```

### Step 5：admin 標記完成

```
□ 進 https://www.gomag.com.tw/admin/pages/settings.php?client_id={id}
□ 找「✅ hPanel 子網域已設定」綠色框 → 勾 ☑
□ 儲存
```

→ Dashboard 「子網域待辦」widget 自動移除該客戶。

---

## 常見問題

### Q1：訪問 `{sub}.gomag.com.tw` 看到「Index of /」或「PHP info」？
- symlink 沒做 / 做錯。重做 Step 3。
- 確認 `ls -la $HOSTDIR/public_html` 結果是 `lrwxrwxrwx ... -> ...gomag.com.tw/public_html`

### Q2：SSL 一直 Pending / 看到 SSL Warning？
- 第一次簽要 5-10 分鐘。重新整理瀏覽器（Cmd+Shift+R）
- 超過 30 分鐘還沒好：進 hPanel → Websites → 該 website → Dashboard 看 SSL 狀態

### Q3：訪問顯示「網站不存在或已停用」？
- 走進了 site/index.php、但 DB 沒這 slug
- 確認 admin 的 `subdomain` 或 `slug` 跟 hPanel 子網域名一致
- 確認 `is_active=1` 且 `has_minisite=1`

### Q4：「升級到企業級網站託管」彈窗？
- Website quota 用完了。Business 方案上限 50 個（2026-05-17 前是 Premium 3 個）。
- 若超過 50 → 升級「企業創業版」（雲端方案）or 刪掉不用的 website。

### Q5：hPanel 為什麼跑 nameserver 流程？是不是要照做？
- **不要照做**！gomag.com.tw 是 Hostinger 同帳號管理的域名，Hostinger UI 邏輯誤把它當外部 domain。
- 直接關掉 nameserver 流程，回 Websites 列表會看到 website 已建好。

### Q6：未來新增客戶能不能自動化（不用手動點 hPanel）？
- Business 方案理論上有 API access，可寫腳本：
  - `admin save with has_minisite=1` → 觸發 hook → 呼叫 Hostinger API → 自動建 website + symlink
- 但 Hostinger 公開 API 不一定支援「建 website」。要先 PoC 驗證。
- 目前手動 5 分鐘/客戶可接受。

---

## 已知已設定的子網域（2026-05-17 狀態）

| 子網域 | 客戶 | hPanel | SSL | symlink |
|---|---|---|---|---|
| artru.gomag.com.tw | 亞筑室內設計 | ✅ | ✅ | ✅ |
| fulldemo.gomag.com.tw | 展示清潔工坊 | ✅ | ✅ | ✅ |
| fooddemo.gomag.com.tw | 築炎日式燒肉酒場 | ✅ | ✅ | ✅ |
| designdemo.gomag.com.tw | 衡作室內設計事務所 | ✅ | ✅ | ✅ |
| lanhung.gomag.com.tw | 聯漢室內設計工作室 | ✅ | ✅ | ✅ |
| happysteakcyi.gomag.com.tw | 歡樂牛排嘉義店 | ✅ | ✅ | ✅（2026-05-17 補建） |
| rre.gomag.com.tw | 亞雷清潔團隊 | ✅ | ✅ | ✅（2026-05-17 新建） |
| 062051129.gomag.com.tw | 旭浪清潔（CodeIgniter 舊系統）| ✅ | ✅ | — 獨立 docroot |

* xusen.gomag.com.tw 已撤下（301 跳 062051129）

---

## 歷史紀錄

| 日期 | 變更 |
|---|---|
| 2026-05-17 | 初版（基於 rre + happysteakcyi 實做經驗）|
| 2026-05-17 | 升級 Premium → Business 方案（50 websites quota）|
| 2026-05-17 | 發現 Hostinger Premium 沒有傳統 Subdomains 分頁、必須走「Add website」+ symlink 流程 |
