# 店家好口碑 — 程式未來規劃結論

> 建立 2026-07-26。來源：與 Reney 討論後整理的技術走向結論。
> 分「已定案 / 傾向 / 待驗證」三層。與 [SITE_ARCHITECTURE_PLAN.md](SITE_ARCHITECTURE_PLAN.md)、[THEME_LIST.md](THEME_LIST.md) 同一系列。
> 首頁實際 build：[gomag-homepage-v4-themes.html](gomag-homepage-v4-themes.html)（本 repo）。brief（gomag-home-brief.md）產於 claude.ai，尚未進本 repo。

## 一、網域與站點分工(已定案)

| 站點 | 網域 | 角色 |
|---|---|---|
| 主站(店家好口碑) | gomag.com.tw | 消費者端,主題為主的在地服務指南 |
| 商家介紹頁 | gomag.com.tw/store/xxx | 線上型錄,圖示按鈕,主題文的落點 |
| 客戶小官網 | 口碑網域 wmf.com.tw | 客戶官方存在,多頁,LocalBusiness url 指向這 |
| 作品集 | 口碑網域 | 製作公司 B2B 品牌與案例 |

同一客戶會有商家介紹頁 + 小官網,靠人稱/意圖/內容差異化避免自我蠶食。LocalBusiness 兩邊都放,但 `url` 都填小官網。

## 二、架構決策(傾向,未動工)

**維持子網域,不改子目錄** —— 因為你賣的是「客戶擁有一個像獨立網站的東西」,子目錄摧毀這個價值主張。

**遷不遷移:傾向現在建新架構、凍結舊系統客戶數** —— 你的論點成立:建新架構是固定成本,拖延不會變便宜,只會讓更多客戶被塞進舊系統。做法是新客戶走新架構、舊客戶留 Hostinger 自然退場,不做大爆炸搬家。

**技術棧方向** —— 往你已驗證的 Cloudflare Pages + Supabase 靠攏(IvyLife、JourneyLift 已在跑)。

## 三、技術驗證(已驗證 2026-07-26,官方文件佐證)

> 🔴 **前提修正**:原本寫「免費/Pro/Business 無法把 `*.domain` 設 proxied,那是 Enterprise」——**已不成立**。CF DNS 文件現明寫「Customers on **all plans** can create and proxy wildcard DNS records.」(以前確實 Enterprise-only,CF 近期放寬)。這條翻案讓原本擔心的兩個繞道(逐筆建 DNS / For SaaS)都不需要。

**Q1:proxied wildcard 可行嗎?一個新客戶幾秒?**
✅ 可行,一個新客戶 **0 秒、0 API 呼叫、0 成本**。`*.gomag.com.tw` 一條 wildcard 記錄涵蓋所有子網域,新增客戶只要 DB INSERT 一筆(subdomain 欄),`newclient.gomag.com.tw` 立即解析。不用打 CF API,那段後台流程可砍掉。

**Q2:Cloudflare for SaaS 每個 hostname 多少錢?**
✅ 前 100 個免費、超過每個 **$0.10/月**(Free/Pro/Business 皆可,PAYG 上限 5 萬)。**但本場景不需要它** —— For SaaS 是給「客戶帶自己買的網域」用的(`www.客戶品牌.com`→你的 app)。`xxx.gomag.com.tw` 用 wildcard 即可,一毛不付。只有未來要支援客戶自有網域才會用到 For SaaS。

**Q3:Pages 怎麼接子網域——Pages 自訂網域 vs dispatch Worker?**
✅ **都不用 Pages 自訂網域,用 Worker wildcard route。** Pages 自訂網域每 project 上限 100/250/500(免費/Pro/Business)且**不支援 wildcard**,207+ 客戶會撞上限還要逐一註冊;Workers 文件直接建議「超過 100 個自訂網域改用 wildcard route」。CF 官方多租戶範例就是一個 Worker 掛 `*/*` route、讀 Host header 查租戶。

### 建議架構(比原規劃簡化一個量級)

```
*.gomag.com.tw  (一條 proxied wildcard DNS，免費)
        ↓
一個 Worker，route = *.gomag.com.tw/*
        ↓
讀 Host header 取 subdomain → 查 Supabase → 渲染該客戶頁面
        ↓
新增客戶 = DB INSERT 一筆。不碰 DNS、不碰 CF API、不逐一部署。
```

- **SSL**:免費 Universal SSL 涵蓋 apex + 一層 wildcard,`client.gomag.com.tw` 自動 HTTPS 免費。
- **成本**:DNS/wildcard 免費、Worker 免費方案 10 萬 req/日,到量升 Workers Paid $5/月無上限。**非 per-client 收費**。
- **價值主張**:每家有自己的子網域 + 自己的 HTTPS,「擁有獨立網站」保留。

### 唯一待實測(10 分鐘,非半天)

proxied wildcard 的免費 Universal SSL 憑證簽發後,`隨便一個.gomag.com.tw` 是否真的 valid(理論上涵蓋,一層 wildcard 的 DCV 偶有眉角)。開一條 wildcard 記錄 + curl 一個沒建過的子網域看憑證即知,不用寫程式。

### 佐證來源
- [CF DNS — Wildcard records](https://developers.cloudflare.com/dns/manage-dns-records/reference/wildcard-dns-records/)（all plans can proxy wildcard）
- [Pages — Limits](https://developers.cloudflare.com/pages/platform/limits/)（自訂網域 100/250/500、不支援 wildcard）
- [Workers — Limits](https://developers.cloudflare.com/workers/platform/limits/)（>100 自訂網域改用 wildcard route）
- [CF for SaaS — Worker as origin](https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/start/advanced-settings/worker-as-origin/)（`*/*` route 多租戶範例）
- [CF for SaaS — 2025-05 PAYG 更新](https://developers.cloudflare.com/changelog/2025-05-19-paygo-updates/)（custom hostname 定價/上限）

## 四、首頁(規劃完成,搬 Claude Code 建置)

已產出完整 brief(`gomag-home-brief.md`)。核心:主題為主、非店家目錄、全台。九節結構、情境分區、脆用 oEmbed、影片後台貼連結、Google 評價放店家頁不放首頁。**視覺在 Claude Code 本機即時預覽做,不在對話盲改。**

## 五、內容產製流程(進行中,獨立於上面)

- repulab-writer 知識層驗證已收尾,下一步把「讀 DB→prompt→白話寫→稽核表」折進 writer 專案指令當固定步驟。
- content-guard **第 13 項 NAP 驗證**規則已寫(`content-guard-13-nap.md`),待併入 skill。
- Kimi 評估結論:可考慮接入,但店家資料一律不可信,NAP 必須實查覆蓋。

## 六、優先順序

1. **先驗架構三題**(半天,擋住後面所有決定)
2. **首頁搬 Claude Code 開工**(手機版主題牆一節先做到精緻)
3. **content-guard 第 13 項併進 skill**(止血,防未驗證 NAP 繼續發出去)
4. 地區頁內容架構
5. 架構遷移正式動工(驗證有答案後)
