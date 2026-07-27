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

## 三、必須先做的技術驗證(擋路的三題)

動工前先花半天驗這三件,沒答案別開發:

1. 免費/Pro/Business 方案無法把 `*.domain` 設成 proxied(那是 Enterprise)。用 **Cloudflare API 自動建 proxied DNS 記錄**繞過,寫進後台新增客戶流程——實際可行嗎?一個新客戶幾秒?
2. 或走 **Cloudflare for SaaS custom hostnames**,每個 hostname 實際多少錢?(以你每客戶月收算單價)
3. Cloudflare Pages 怎麼接子網域——Pages 自訂網域,還是前面掛 dispatch Worker?

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
