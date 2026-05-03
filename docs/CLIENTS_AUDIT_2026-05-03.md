# 店家好口碑 客戶資料完整度 Audit — 2026-05-03

**總客戶數**：209（is_active=1）

**評分標準**：11 個欄位，每填 1 個給 1 分。粗體 `**xxx**` = 必填欄位（基本展示需要）。

## 完整度分布

| 分數 | 客戶數 | 比例 |
|---:|---:|---:|
| 4/11 | 1 | 0.5% █ |
| 5/11 | 4 | 1.9% █ |
| 6/11 | 13 | 6.2% ███ |
| 7/11 | 41 | 19.6% ██████████ |
| 8/11 | 68 | 32.5% ████████████████ |
| 9/11 | 74 | 35.4% ██████████████████ |
| 10/11 | 8 | 3.8% ██ |

## 各欄位缺漏統計

| 欄位 | 缺漏數 | 缺漏率 | 影響 |
|---|---:|---:|---|
| 品牌標語 (`tagline`) | 175 | 83.7% | 中（前台 hero 副標） |
| 業種 (`industry`) | 15 | 7.2% | 中（決定模板 + 文字 fallback） |
| 主站分類 (`category_id`) | 0 | 0% | 高（主站列表分類入口） |
| 電話 (`phone`) | 21 | 10% | 高（聯絡 CTA、Local SEO） |
| Email (`email`) | 204 | 97.6% | 低（內部聯絡 lead 通知） |
| 地址 (`address`) | 18 | 8.6% | 高（Local SEO、Google 地圖） |
| 關於我們 (`about_text`) | 6 | 2.9% | 中（首頁 about 區） |
| Hero 圖 (`hero_image_path`) | 84 | 40.2% | 高（前台首頁主視覺） |
| 營業時間 (`business_hours`) | 88 | 42.1% | 中（Local SEO + 客戶資訊） |
| Google Place ID (`google_place_id`) | 9 | 4.3% | 中（自動拉 Google 評論 / 地圖嵌入） |

## 子表覆蓋率

| 子表 | 已填客戶數 | 覆蓋率 |
|---|---:|---:|
| services（服務項目） | 3 | 1.4% |
| testimonials（評價） | 6 | 2.9% |
| cases（案例） | 2 | 1% |
| store_blocks（modular） | 6 | 2.9% |
| client_social（社群） | 6 | 2.9% |
| seo_settings（SEO meta） | 7 | 3.3% |

## 缺最多資料的 Top 30（優先處理）

| Score | ID | Subdomain | 品牌 | 分類 | 缺欄位 |
|---:|---:|---|---|---|---|
| 4/11 | 207 | `taichung-bullfight-xinshidai` | 鬥牛士二鍋 台中新時代店 | 餐飲美食 | industry / **phone** / email / about / hero_img ... |
| 5/11 | 108 | `063120606` | 京賀日語 | 教育學習 | tagline / **phone** / email / **address** / hero_img ... |
| 5/11 | 154 | `interior-renovation-01` | 德恩室內裝修設計有限公司 | 居家服務 | tagline / **phone** / email / hero_img / business_hours ... |
| 5/11 | 206 | `taichung-bullfight-wenxin` | 鬥牛士二鍋 台中文心店 | 餐飲美食 | industry / email / about / hero_img / business_hours ... |
| 5/11 | 205 | `taichung-rouhanghang` | 肉夯夯韓式燒肉吃到飽 | 餐飲美食 | industry / email / about / hero_img / business_hours ... |
| 6/11 | 35 | `062762020` | 指愛美學 美甲美睫批發中心 | 美容美髮 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 107 | `062951342` | 呂老師 資優家教團隊 | 教育學習 | **phone** / email / **address** / hero_img / business_hours |
| 6/11 | 12 | `baking1` | 貝格緹烘焙 / 生酮 / 蛋糕 / 麵包 / 咖啡 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 116 | `cheapfry` | 九九平價熱炒/日式料理燒烤 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 59 | `dessert01` | 莉莉安手作甜品/客製化生日蛋糕 | 餐飲美食 | tagline / **phone** / email / hero_img / business_hours |
| 6/11 | 91 | `dinnerdelivery` | 小狀元膳食外送-宅配晚餐 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 105 | `eel` | 宝鰻道地鰻魚飯/日式定食-南紡東平店 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 114 | `foodwholesale1` | 三原冷凍食品專賣店/金華店 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 115 | `foodwholesale2` | 金和肉品食品專賣店 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 158 | `glutinousricesausage01` | 古早味小區．腸將君 | 餐飲美食 | tagline / **phone** / email / **address** / hero_img |
| 6/11 | 76 | `gourmetrestaurant1` | 來道好食雞 台南美食 中式餐廳 | 餐飲美食 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 119 | `hairdressing` | our hair salon 美髮沙龍 | 美容美髮 | tagline / **phone** / email / **address** / business_hours |
| 6/11 | 19 | `spicyhotpotallyoucaneat` | XM 麻辣火鍋吃到飽 | 餐飲美食 | tagline / **phone** / email / **address** / hero_img |
| 7/11 | 80 | `062610957` | 天生好命 寵物造型 spa 沙龍 | 美容美髮 | tagline / email / hero_img / business_hours |
| 7/11 | 79 | `062756085` | 吾友宅修興業有限公司 | 居家服務 | tagline / email / hero_img / business_hours |
| 7/11 | 33 | `0988399199` | 日上汽車駕訓班（教練張書豪） | 汽車服務 | tagline / email / hero_img / business_hours |
| 7/11 | 30 | `13VillaMOTEL` | 永華春汽車旅館/商務飯店 | 旅宿住宿 | tagline / email / hero_img / business_hours |
| 7/11 | 124 | `Interiordesign214` | 聯漢室內設計工程公司 | 居家服務 | tagline / email / about / business_hours |
| 7/11 | 137 | `PCO1` | 尚撰 除蟲消毒清潔有限公司 | 居家服務 | tagline / email / **address** / business_hours |
| 7/11 | 188 | `airconditioner2` | 冰點變頻空調 | 居家服務 | tagline / email / hero_img / business_hours |
| 7/11 | 109 | `bedding` | 小工紡寢飾寢具工廠 | 其他 | tagline / industry / email / business_hours |
| 7/11 | 57 | `beefhotpot01` | 牛老大涮牛肉 | 餐飲美食 | tagline / email / hero_img / business_hours |
| 7/11 | 190 | `carwrap11` | 台灣進強車體包膜 | 汽車服務 | tagline / email / hero_img / business_hours |
| 7/11 | 47 | `carwrap2` | 星城車體鍍膜包膜 (台南店) | 汽車服務 | tagline / **phone** / email / **address** |
| 7/11 | 194 | `chinesefood2` | 仙香成都 | 餐飲美食 | tagline / email / hero_img / business_hours |

## 全部客戶清單（按 score 由低到高）

| Score | ID | Subdomain | 品牌 | 分類 | 業種 | 缺欄位 | 子表 |
|---:|---:|---|---|---|---|---|---|
| 4 | 207 | `taichung-bullfight-xinshidai` | 鬥牛士二鍋 台中新時代店 | 餐飲美食 | - | industry / **phone** / email / about / hero_img / business_hours / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 5 | 108 | `063120606` | 京賀日語 | 教育學習 | 補習班 | tagline / **phone** / email / **address** / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 5 | 154 | `interior-renovation-01` | 德恩室內裝修設計有限公司 | 居家服務 | 室內設計 | tagline / **phone** / email / hero_img / business_hours / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 5 | 206 | `taichung-bullfight-wenxin` | 鬥牛士二鍋 台中文心店 | 餐飲美食 | - | industry / email / about / hero_img / business_hours / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 5 | 205 | `taichung-rouhanghang` | 肉夯夯韓式燒肉吃到飽 | 餐飲美食 | - | industry / email / about / hero_img / business_hours / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 6 | 35 | `062762020` | 指愛美學 美甲美睫批發中心 | 美容美髮 | 美甲教學 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 107 | `062951342` | 呂老師 資優家教團隊 | 教育學習 | 補習班 | **phone** / email / **address** / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 12 | `baking1` | 貝格緹烘焙 / 生酮 / 蛋糕 / 麵包 / 咖啡 | 餐飲美食 | 麵包烘焙坊 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 116 | `cheapfry` | 九九平價熱炒/日式料理燒烤 | 餐飲美食 | 中式料理 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 59 | `dessert01` | 莉莉安手作甜品/客製化生日蛋糕 | 餐飲美食 | 甜品工作室 | tagline / **phone** / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 91 | `dinnerdelivery` | 小狀元膳食外送-宅配晚餐 | 餐飲美食 | 便當餐盒 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 105 | `eel` | 宝鰻道地鰻魚飯/日式定食-南紡東平店 | 餐飲美食 | 日式料理 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 114 | `foodwholesale1` | 三原冷凍食品專賣店/金華店 | 餐飲美食 | 肉品批發零售 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 115 | `foodwholesale2` | 金和肉品食品專賣店 | 餐飲美食 | 肉品批發零售 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 158 | `glutinousricesausage01` | 古早味小區．腸將君 | 餐飲美食 | 小吃 | tagline / **phone** / email / **address** / hero_img | svc:0/testi:0/case:0/blk:0 |
| 6 | 76 | `gourmetrestaurant1` | 來道好食雞 台南美食 中式餐廳 | 餐飲美食 | 中式料理 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 119 | `hairdressing` | our hair salon 美髮沙龍 | 美容美髮 | 美髮 | tagline / **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 6 | 19 | `spicyhotpotallyoucaneat` | XM 麻辣火鍋吃到飽 | 餐飲美食 | 麻辣火鍋吃到飽 | tagline / **phone** / email / **address** / hero_img | svc:0/testi:0/case:0/blk:0 |
| 7 | 80 | `062610957` | 天生好命 寵物造型 spa 沙龍 | 美容美髮 | 寵物美容 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 79 | `062756085` | 吾友宅修興業有限公司 | 居家服務 | 居家修繕 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 33 | `0988399199` | 日上汽車駕訓班（教練張書豪） | 汽車服務 | 汽機車駕訓班 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 30 | `13VillaMOTEL` | 永華春汽車旅館/商務飯店 | 旅宿住宿 | 旅館飯店 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 124 | `Interiordesign214` | 聯漢室內設計工程公司 | 居家服務 | 室內設計 | tagline / email / about / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 137 | `PCO1` | 尚撰 除蟲消毒清潔有限公司 | 居家服務 | 除蟲消毒清潔有限公司 | tagline / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 188 | `airconditioner2` | 冰點變頻空調 | 居家服務 | 居家修繕 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 109 | `bedding` | 小工紡寢飾寢具工廠 | 其他 | - | tagline / industry / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 57 | `beefhotpot01` | 牛老大涮牛肉 | 餐飲美食 | 火鍋 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 190 | `carwrap11` | 台灣進強車體包膜 | 汽車服務 | 汽車包膜 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 47 | `carwrap2` | 星城車體鍍膜包膜 (台南店) | 汽車服務 | 汽車包膜 | tagline / **phone** / email / **address** | svc:0/testi:0/case:0/blk:0 |
| 7 | 194 | `chinesefood2` | 仙香成都 | 餐飲美食 | 麵食館 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 175 | `chinesemedicine3` | 仲春堂中醫診所 | 健康醫療 | 中藥行 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 178 | `chinesemedicine4` | 寳文中醫(寶文中醫)診所 | 健康醫療 | 中醫診所 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 183 | `cleaningcompany4` | 廣達清潔企業社 | 居家服務 | 居家清潔 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 197 | `cleaningcompany5` | 三峰清潔公司 | 居家服務 | 居家清潔 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 77 | `compoundrestaurant1` | 寶煲餐飲 （粥、湯、燴飯、冰品） | 餐飲美食 | 異國料理 | tagline / **phone** / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 8 | `eat9` | 逐鹿炭火燒肉吃到飽 | 餐飲美食 | 中式料理 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 98 | `engineering` | 富川專業統包宅修工程團隊 | 居家服務 | 統包宅修工程 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 184 | `exoticrestaurant1` | 貓的法小館 | 餐飲美食 | 異國料理 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 68 | `furniture02` | 坐又銘 手工沙發 舒眠床墊 | 居家服務 | 傢俱 | tagline / **phone** / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 7 | 166 | `home-inspection01` | 李好房 | 居家服務 | 驗屋 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 171 | `hotpot01` | 老胡記 | 餐飲美食 | 火鍋 | tagline / email / hero_img / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 7 | 134 | `hotpot4` | 貍偷聚門鍋物 | 餐飲美食 | 火鍋 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 179 | `interiorrenovation2` | 明日喆居室內裝修有限公司 | 居家服務 | 室內設計 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 151 | `interiorrenovation5` | 歐印系統設計 | 居家服務 | 室內設計 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 51 | `massage2` | 足松足湯養生會館 | 美容美髮 | 腳底按摩 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 50 | `massage3` | 鄧老師養生館 | 美容美髮 | 養身館 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 49 | `massage6` | 湯悅麗緻足體養生館 | 美容美髮 | 腳底按摩 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 23 | `motel1` | 清水漾汽車旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 20 | `motel10` | 湖美時尚汽車旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 29 | `motel2` | 夏閣汽車旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 28 | `motel3` | 綠驛精品商務旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 25 | `motel4` | 歐薇汽車旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 27 | `motel5` | 假日汽車旅館旗艦店 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 26 | `motel6` | 激點情境旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 24 | `motel7` | 媜13汽車旅館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 21 | `motel8` | 台南湖水岸人文休閒會館 | 其他 | - | industry / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 201 | `network2` | 中嘉寬頻蔡專員台南專區 | 居家服務 | 第四台網路 | tagline / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 127 | `phonefix` | 宅瘋修手機維修教學中心 | 教育學習 | 補習班 | **phone** / email / **address** / business_hours | svc:0/testi:0/case:0/blk:0 |
| 7 | 152 | `woodenfloor4` | 佳翔木地板 | 居家服務 | 木地板 | tagline / email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 101 | `062051540` | Sheer AIRE席愛爾 精品家電工廠直營 | 居家服務 | 3C家電 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 45 | `062132726` | QUICK-STEP快步地板－富崧興業有限公司(南區經銷商) | 居家服務 | 木地板 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 13 | `062263168` | 二鍋 壽喜燒/火鍋吃到飽 | 餐飲美食 | 火鍋 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 118 | `062589568` | 極禾楓肉舖專賣店 | 餐飲美食 | 肉品批發零售 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 6 | `062702399` | 台灣大廚 宴會式場 | 餐飲美食 | 宴會辦桌 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 113 | `062706977` | 亞拓窗簾/電動窗簾/塑膠地板 | 居家服務 | 居家裝潢 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 94 | `062714222` | 福泉食味外燴辦桌商行（總舖師） | 餐飲美食 | 宴會辦桌 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 81 | `062728061` | 安安抓漏工程 | 居家服務 | 居家修繕 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 48 | `062796037` | 亞力山大戶外教學教育休閒農場 | 旅宿住宿 | 休閒園區 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 4 | `062988777` | 台南朵菈美睫/除毛形象館 | 美容美髮 | 美容SPA | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 87 | `063023888` | 牧鍋 MooPot 頂級熟成牛鍋物 | 餐飲美食 | 火鍋 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 82 | `063133213` | 作愚行 | 專業服務 | 廣告設計 | email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 90 | `065957487` | 二鍋 壽喜燒/火鍋吃到飽 | 餐飲美食 | 火鍋 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 93 | `0915976190` | 香橙料理食坊 | 餐飲美食 | 中式料理 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 52 | `0927585895` | CH Luxury 惟翎 ART | 健康醫療 | 醫學美容 | email / about / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 84 | `0981787769` | 夠創意網頁設計公司 | 專業服務 | 網站設計 | email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 111 | `Coating` | 匠車體專業汽車鍍膜/包膜 | 汽車服務 | 汽車包膜 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 181 | `Donburi2` | 汍樂滿盛燒肉丼-高雄明仁店 | 餐飲美食 | 丼飯 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 125 | `Interiordesign38` | 麥田室內設計有限公司 | 居家服務 | 室內設計 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 99 | `Interiordesign72` | 亞筑室內設計 | 居家服務 | 室內設計 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 67 | `Shengyangwoodenfloor` | 昇揚木地板 | 居家服務 | 木地板 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 186 | `airconditioningcleaning` | 涼拾冷氣清潔 | 居家服務 | 居家清潔 | email / hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 133 | `beverageshop2` | G colour金色魔法紅茶 | 餐飲美食 | 飲料輕食 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 182 | `bistro2` | Cest Bon Bar Restaurant 高雄餐酒館 | 餐飲美食 | 餐酒館 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 180 | `bistro3` | 貝納餐酒館 | 餐飲美食 | 餐酒館 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 160 | `butchershop` | 豐隆士多 | 餐飲美食 | 肉品批發零售 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 88 | `carbeauty` | 七號車庫 | 汽車服務 | 汽車鍍膜 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 18 | `carbeauty2` | 濱緻車體美學 汽車美容鍍膜 | 汽車服務 | 汽車鍍膜 | tagline / email / business_hours | svc:0/testi:6/case:0/blk:4 |
| 8 | 60 | `carbeauty7` | 岫舫車體美研 | 汽車服務 | 汽車鍍膜 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 189 | `carwrap10` | 方程式汽車包膜 | 汽車服務 | 汽車包膜 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 185 | `carwrap9` | 凱朔車業 | 汽車服務 | 汽車包膜 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 66 | `ch` | 佳鋐不鏽鋼 | 居家服務 | 居家修繕 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 132 | `chinyunyun` | 琴芸韻音樂教學中心 | 教育學習 | 教學中心 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 165 | `coffee01` | CREMA CAFÉ 葵瑪咖啡 | 餐飲美食 | 早午餐 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 150 | `compoundrestaurant2` | ㄎㄨㄥˋ湯極品鍋物·燒烤 | 餐飲美食 | 火鍋 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 170 | `furniture01` | 吉豐傢俱 | 居家服務 | 傢俱 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 169 | `glasses01` | 伍佰眼鏡-臨安店 | 零售購物 | 眼鏡行 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 145 | `gourmetrestaurant2` | 來道好食雞 嘉義美食 中式餐廳 | 餐飲美食 | 中式料理 | tagline / email / **address** | svc:0/testi:0/case:0/blk:0 |
| 8 | 16 | `grilledfish1` | 魚老鐵・烤魚 燒烤美食餐廳 | 餐飲美食 | 中式料理 | tagline / email / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 8 | 100 | `homecleaning` | 天天沙發床墊居家清洗 | 居家服務 | 居家清潔 | tagline / email / google_place_id | svc:0/testi:0/case:0/blk:0 |
| 8 | 187 | `homeinspection` | 鼎創科技驗屋 | 居家服務 | 高雄宅修 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 15 | `homerepair` | 胖匠宅修防水油漆工程 | 居家服務 | 居家修繕 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 120 | `homestay` | 樂遊親子民宿會館 | 旅宿住宿 | 民宿 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 191 | `hotpot5` | 滿足麻奶鍋 | 餐飲美食 | 火鍋 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 74 | `hotpotjohn` | 鍋醬平價小火鍋 | 餐飲美食 | 火鍋 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 44 | `interior-renovation-02` | 全境空間規劃 | 居家服務 | 室內設計 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 192 | `interiorrenovation6` | 一新室內設計 | 居家服務 | 室內設計 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 193 | `interiorrenovation7` | 久玳空間製作所 | 居家服務 | 室內設計 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 196 | `interiorrenovation9` | 麥斯室內設計 | 居家服務 | 室內設計 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 172 | `koreafoods01` | 韓湘辣年糕-五妃店 | 餐飲美食 | 韓式料理 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 129 | `larchotel` | 南科贊美酒店 | 旅宿住宿 | 旅館飯店 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 126 | `legalservice` | 邑元聯合法律事務所 | 專業服務 | 律師事務所 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 138 | `lighting1` | 雀爾斯燈飾精品 義大利水晶燈 | 居家服務 | 燈飾 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 198 | `liquorstore1` | SAKAKURA清酒專賣 | 餐飲美食 | 餐酒館 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 149 | `mattress3` | 早睡找寢 床墊專賣 | 其他 | - | tagline / industry / email | svc:0/testi:0/case:0/blk:0 |
| 8 | 37 | `mattress4` | 采藝寢具x名匠床墊 台南寢具床墊專賣 | 其他 | - | tagline / industry / email | svc:0/testi:0/case:0/blk:0 |
| 8 | 148 | `photography2` | 克林姆與安淇拉的攝影棚 | 專業服務 | 攝影師 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 177 | `power` | 藏鋒監視器科技 | 居家服務 | 弱電工程 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 200 | `power2` | 藏鋒弱電工程 | 居家服務 | 弱電工程 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 174 | `steak01` | 嘟嘟牛排（永康） | 餐飲美食 | 牛排 | tagline / email / about | svc:0/testi:0/case:0/blk:0 |
| 8 | 42 | `stonebeauty` | 旭浪石材美容 | 其他 | 石材美容 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 14 | `systemcabinet` | 九十度系統廚具櫥櫃 | 居家服務 | 系統櫥櫃廚具 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 22 | `tainanhotel` | 青舍商旅 | 旅宿住宿 | 旅館飯店 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 173 | `vegetariandiet01` | 仙桃素 | 餐飲美食 | 高雄素食餐廳 | tagline / email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 8 | 97 | `waterproofpaint` | 振宏防水油漆工程 | 居家服務 | 居家修繕 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 53 | `westernfood01` | 木易MUYI Kitchen | 餐飲美食 | 異國料理 | tagline / **phone** / email | svc:0/testi:0/case:0/blk:0 |
| 8 | 92 | `westernrestaurant` | 暖心時光 西餐廳 | 餐飲美食 | 異國料理 | tagline / email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 8 | 1 | `xusen` | 旭浪清潔 | 居家服務 | 居家清潔 | email / hero_img / business_hours | svc:4/testi:4/case:0/blk:2 |
| 9 | 64 | `062130009` | 郭健軍皮膚科診所 | 健康醫療 | 醫學美容 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 83 | `062133729` | 作愚行 廣告 ｜設計 | 印刷 | 專業服務 | 廣告設計 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 61 | `062143456` | 杭信行中藥材公司 | 健康醫療 | 中醫診所 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 34 | `062211549` | 指愛美麗 藝術美甲沙龍 | 美容美髮 | 美甲美睫 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 3 | `062281421` | 綝綝美甲美睫紋繡學院 | 美容美髮 | 美甲美睫 | tagline / email | svc:0/testi:5/case:0/blk:3 |
| 9 | 43 | `062379988` | 博仕醫事檢驗所 | 健康醫療 | 醫事檢驗 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 32 | `062607675` | 華豐窗簾精品名店 | 居家服務 | 居家裝潢 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 102 | `062739437` | 嘉寶 珠寶/鑽石銀樓 | 專業服務 | 珠寶銀樓 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 103 | `062791668` | 東京城家具工廠直營 | 其他 | 台南傢具工廠 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 73 | `0628222982` | 湯村足體養生會館 | 美容美髮 | 足體按摩 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 9 | `062905768` | Lady  MaMa 私房點心(住宅商店) | 餐飲美食 | 火鍋 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 5 | `065050356` | VLY美甲美睫材料店 | 美容美髮 | 美甲美睫 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 95 | `066223925` | 喜粵樓宴會餐廳 | 餐飲美食 | 宴會辦桌 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 7 | `075916633` | 二鍋壽喜燒/涮涮鍋火鍋吃到飽【高雄楠梓店】 | 餐飲美食 | 火鍋 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 31 | `0955139404` | 豪工房輕檔車 | 汽車服務 | 汽車改裝 | email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 9 | 11 | `0976179401` | 活跳跳海鮮粥 | 餐飲美食 | 小吃 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 136 | `BetterLife` | 安居木地板 | 居家服務 | 木地板 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 157 | `HandPulledNoodle01` | 鶴昇町拉麵 | 餐飲美食 | 日式料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 156 | `Human-agency01` | 強格人力仲介 | 專業服務 | 外勞仲介 | email / business_hours | svc:0/testi:0/case:0/blk:0 |
| 9 | 96 | `Kaohsiung` | 二鍋壽喜燒/涮涮鍋火鍋吃到飽【高雄大順店】 | 餐飲美食 | 火鍋 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 69 | `KoreanBBQ1` | 豬頭妹韓式燒肉 吃到飽 | 餐飲美食 | 韓式料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 78 | `PCO2` | 瑞容除蟲有限公司 (病媒防治) PCO | 居家服務 | 除蟲消毒清潔有限公司 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 56 | `Sichuancuisine01` | La時尚川菜 | 餐飲美食 | 中式料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 144 | `audio1` | 潘氏音響 電子音響專賣 | 零售購物 | 樂器行 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 41 | `audio2` | 鴻運音響中心 電子音響專賣 | 零售購物 | 樂器行 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 110 | `beauty` | 暐恩極緻美學沙龍 | 美容美髮 | 美容SPA | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 40 | `beef-noodle-soup1` | 張家牛肉麵 土庫美食推薦 | 餐飲美食 | 牛肉麵 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 54 | `beerhall01` | 小椿食堂 日式料理居酒屋 | 餐飲美食 | 居酒屋 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 39 | `bistro1` | 亞芙英式餐廳The Artful Dodger 手工漢堡 炸魚薯條 調酒 | 餐飲美食 | 異國料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 106 | `bread` | 麥園烘焙坊 | 餐飲美食 | 麵包烘焙坊 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 140 | `buxiban1` | 華鴻文理補習班 安親班 課後輔導 | 教育學習 | 補習班 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 55 | `car02` | Hy合業車體包膜 | 汽車服務 | 汽車包膜 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 135 | `carbeauty3` | 佑鑫車體鍍膜美容 | 汽車服務 | 汽車鍍膜 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 147 | `carbeauty4` | SPOTCO尚膜漆面校正 汽車美容鍍膜 | 汽車服務 | 汽車鍍膜 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 155 | `carbeauty5` | 奧圖菲車體工藝 | 汽車服務 | 汽車美容 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 159 | `carbeauty6` | Force 弗珥仕·車體包膜 | 汽車服務 | 汽車包膜 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 72 | `carmedia` | 鑫旺專業汽車音響改裝 | 汽車服務 | 汽車改裝 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 161 | `catering` | 潮州聞香閣宴會餐廳 | 餐飲美食 | 宴會辦桌 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 38 | `chinesefood1` | 五村燕餃 手工福州燕餃 乾鍋、小火鍋 | 餐飲美食 | 中式料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 142 | `cleaningcompany2` | 享清清潔工程企業社 居家清潔 裝潢細清 搬家搬運 | 居家服務 | 居家清潔 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 199 | `cosmetic` | 名緻美顏SPA生活館 | 美容美髮 | 美容SPA | email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 9 | 117 | `curry1` | 品作咖哩 手作咖哩專賣 日式咖哩 | 餐飲美食 | 咖哩 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 2 | `demo1` | demo1 美食示範店 | 餐飲美食 | 餐飲美食 | hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 9 | 85 | `donburi1` | 花火日本料理 丼飯專賣 | 餐飲美食 | 日式料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 122 | `eatmeat` | 好好吃肉韓式烤肉x火鍋 吃到飽 | 餐飲美食 | 韓式料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 121 | `engelhard` | 安格高鈣乳酪蛋糕 | 餐飲美食 | 麵包烘焙坊 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 63 | `filf` | 小資美顏館 | 美容美髮 | 美容SPA | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 128 | `flowergoodmoon` | 花好月圓 台南金華店 | 餐飲美食 | 飲料輕食 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 202 | `flowtest` | 設計測試店家 | 專業服務 | 設計 | hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 9 | 143 | `glasses1` | Up精品眼鏡-雷朋專賣 合格驗光所 東區眼鏡 | 零售購物 | 眼鏡行 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 164 | `glasses2` | 達生眼鏡 | 零售購物 | 眼鏡行 | email / hero_img | svc:0/testi:0/case:0/blk:0 |
| 9 | 17 | `grillmeat` | 初云職人燒肉 | 餐飲美食 | 碳烤 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 10 | `happysteakcyi` | 歡樂牛排嘉義店 | 餐飲美食 | 牛排 | tagline / email | svc:0/testi:5/case:0/blk:2 |
| 9 | 75 | `happysteakyun` | 歡樂牛排雲林店 | 餐飲美食 | 牛排 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 58 | `hotpotnoodles01` | 海霸海鮮鍋燒/手工湯包/海鮮粥 | 餐飲美食 | 鍋燒意麵 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 36 | `izakaya1` | おかんとO KAN Do 大眾酒場 日式居酒屋 | 餐飲美食 | 居酒屋 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 176 | `izakaya2` | 原佃燒烤居酒屋 | 餐飲美食 | 碳烤 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 89 | `kitchenware` | Takara standard日本寶廚-台南展銷中心-優派 | 居家服務 | 室內設計 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 65 | `lwbeef` | 六味真湯清燉牛肉麵 | 餐飲美食 | 麵食館 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 123 | `magicbeefsteak` | 魔力牛牛排館 | 餐飲美食 | 牛排 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 112 | `massage01` | 足夏養生館 | 美容美髮 | 腳底按摩 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 46 | `modifiedcars` | 光點線專業汽車大燈 | 汽車服務 | 汽車改裝 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 195 | `modifiedcars3` | 光點線專業汽車大燈 | 汽車服務 | 汽車改裝 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 139 | `muttonhotpot1` | 阿堂鮮蔬羊肉爐 國產努比亞溫體羊 | 餐飲美食 | 羊肉爐 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 62 | `osteopathy1` | 元強仙骨微調 | 美容美髮 | 整骨推拿 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 167 | `pastry01` | 饗福園北方麵食館 | 餐飲美食 | 麵食館 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 203 | `reney` | 設計測試店家1 | 餐飲美食 | 餐飲美食 | hero_img / business_hours | svc:0/testi:0/case:0/blk:0 |
| 9 | 71 | `roastmeat` | 肉吧·RouBar x 燒肉專門店 | 餐飲美食 | 燒肉 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 163 | `steak` | 鬥牛士石燒牛排 Hot Stone Steak | 餐飲美食 | 牛排 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 141 | `steak1` | 嬉遊饌蒸烤牛排 平價牛排 雞腿排 | 餐飲美食 | 牛排 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 70 | `steakhouse` | 丹妮牛排 | 餐飲美食 | 牛排 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 130 | `systemfurniture` | 高森系統廚具傢具 | 居家服務 | 室內設計 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 104 | `vegetarian-diet01` | 蔬上食 | 餐飲美食 | 蔬食料理 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 9 | 86 | `weddingbanquet` | 玄饌海鮮宴會館 | 餐飲美食 | 宴會辦桌 | tagline / email | svc:0/testi:0/case:0/blk:0 |
| 10 | 162 | `Donburi` | 揪丼 | 餐飲美食 | 日式料理 | email | svc:0/testi:0/case:0/blk:0 |
| 10 | 146 | `carrepair1` | 興泰汽車保養廠｜汽車驗車廠｜ | 汽車服務 | 汽機車維修 | email | svc:0/testi:0/case:0/blk:0 |
| 10 | 131 | `chinesemedicine` | 健恩中醫診所 | 健康醫療 | 醫學美容 | email | svc:0/testi:0/case:0/blk:0 |
| 10 | 204 | `docar` | 鍍卡 Do Car【汽車包膜】 | 汽車服務 | 汽車 | email | svc:0/testi:0/case:0/blk:0 |
| 10 | 210 | `fooddemo` | 築炎日式燒肉酒場 | 餐飲美食 | 日式燒肉・居酒屋料理 | google_place_id | svc:3/testi:4/case:2/blk:4 |
| 10 | 209 | `fulldemo` | 展示清潔工坊 | 居家服務 | 居家清潔 | google_place_id | svc:3/testi:4/case:2/blk:4 |
| 10 | 168 | `spicy01` | 3鼎紅 麻辣鴨血臭豆腐 台南南區大同店 | 餐飲美食 | 麻辣鴨血臭豆腐 | email | svc:0/testi:0/case:0/blk:0 |
| 10 | 153 | `tainanmattress` | 歐菲床墊 | 居家服務 | 台南床墊門市 | email | svc:0/testi:0/case:0/blk:0 |
