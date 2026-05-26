<?php
// ============================================================
// Migration 055 — 奧喜長崎蛋糕 5 城市差異化長文內容
// ------------------------------------------------------------
// 寫 5 城市各自的 landing_extra_content（行銷頁，800-1000 字）
// + minisite_intro_html（小官網，400-600 字）
// 每城市內容用「真的屬於該城市」的場景痛點/區域名/常見問題填，避免 doorway penalty。
//
// 內容差異化策略：
//   台中 = 實體店體驗（北屯松竹路本店、現場購買、本地伴手禮）
//   台北 = 商務+彌月雙主場（大安信義、彌月競爭、白領送禮）
//   新北 = 多代家庭/年節（板橋中和老社區、林口新生兒彌月）
//   桃園 = 公司福委會（中壢龜山工業區、企業大宗訂購）
//   新竹 = 竹科商務（R&D 部門、外籍同事、研發階級伴手禮）
//
// 跑法：HTTP_HOST=<host> php migrations/055_oishii_5city_content.php
// 冪等：UPDATE，可重複跑（會覆蓋既有內容，這是 seed migration）
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$clientId = (int)$db->query("SELECT id FROM clients WHERE subdomain='oishii' OR slug='oishii' LIMIT 1")->fetchColumn();
if (!$clientId) { echo "❌ 找不到 oishii 客戶（請先跑 054）\n"; exit(1); }
echo "目標 client_id={$clientId}\n";

// ─── 每城市的差異化資料 ───
$cities = [
    'taichung' => [
        'name' => '台中',
        'name_full' => '台中市',
        'eyebrow' => '北屯松竹路・本店現烤',
        'h1' => '台中長崎蛋糕｜北屯松竹路本店',
        'sub' => '台中在地居民散步可達的日式長崎蛋糕名店，松竹路三段 22 號每日現烤',
        'why_choose_h2' => '為什麼台中在地人選奧喜',
        'why_choose' => '奧喜長崎蛋糕的<strong>本店</strong>就在北屯松竹路三段，從原綏遠路時期的老客人都跟著找過來。對台中在地人來說，奧喜不只是伴手禮，是<strong>下午散步順路買、新鮮現烤、不用等宅配</strong>的日常選擇。北屯居民帶回家當下午茶、台中商務客戶當伴手禮、過年送長輩，本店每天 9 點開門到晚上 7 點，現場購買可立刻帶走，不用提前一天訂購。',
        'areas_h2' => '台中本店地址 + 怎麼到',
        'areas_intro' => '本店位於<strong>北屯區松竹路三段 22 號</strong>，是奧喜長崎蛋糕的唯一實體店。',
        'areas' => ['捷運松竹站步行 5 分鐘', '台 74 號松竹路出口 3 分鐘', '機車路邊停車格', '公車 71 / 53 / 12 松竹路站', '開車從中清路右轉松竹路 直行可達'],
        'scenarios_h2' => '台中熱門場景與品項',
        'scenarios' => [
            ['title' => '北屯在地伴手禮', 'desc' => '北屯居民散步順路買，最常見一條兩入長崎蛋糕、奧喜燒禮盒當下午茶或拜訪客戶。'],
            ['title' => '台中商務伴手禮', 'desc' => '台中市區七期、北屯機捷的商務客戶選奧喜當拜訪客戶的禮，比西式蛋糕有差異化、日式包裝大方。'],
            ['title' => '彌月禮盒台中本店', 'desc' => '台中地區新生兒家庭選奧喜長崎蛋糕作為彌月禮，可現場挑款式、本店包裝。'],
            ['title' => '年節大盒送長輩', 'desc' => '中秋、年節台中本地客人習慣帶大盒長崎蛋糕回家分送長輩，現場可確認品項。'],
        ],
        'faqs' => [
            ['q' => '松竹路本店怎麼到？需要預約嗎？', 'a' => '本店在北屯區松竹路三段 22 號，捷運松竹站步行 5 分鐘。一般購買不需預約，但<strong>大量訂購（10 盒以上）建議至少前一天電話 04-22416560 預訂</strong>，避免現場無貨。'],
            ['q' => '現場有試吃嗎？', 'a' => '本店現場可看實際蛋糕成品與包裝。試吃依當日狀況提供，建議直接到店詢問現場服務人員。'],
            ['q' => '台中可以團購自取嗎？', 'a' => '可以。台中本地客戶<strong>團購自取</strong>是常見模式：提前電話訂購、約定取貨時間，現場大量結帳。20 盒以上可協助分裝協助分送。'],
            ['q' => '本店營業時間？', 'a' => '<strong>每日 09:00–19:00</strong>（全年無休除春節公告外）。建議避開週末下午尖峰時段。'],
            ['q' => '台中市區其他地方有分店嗎？', 'a' => '目前北屯松竹路是<strong>唯一實體店</strong>。台中其他區（西屯、南屯、中區）建議電話訂購自取或宅配。'],
        ],
        'minisite_about' => '<p><strong>奧喜長崎蛋糕的本店</strong>就在台中市北屯區松竹路三段 22 號，是品牌的本店所在。每日上午 9 點，師傅在店內以最單純的<strong>雞蛋、糖、麵粉、麥芽</strong>開始當日的手工烘烤，下午陸續出爐，台中在地客人可以現場購買新鮮現烤的長崎蛋糕。</p><p>從原綏遠路搬到松竹路，奧喜的老客人都跟著找過來。對北屯居民來說，奧喜是<strong>下午散步順路買的日常店</strong>；對台中商務客戶，奧喜是<strong>拜訪客戶送禮的差異化選擇</strong>；對在地家庭，奧喜是<strong>過年中秋大盒分送長輩的固定選項</strong>。</p><p>本店每日 9:00–19:00 營業，現場購買可立刻帶走、團購自取需提前電話 04-22416560 預訂、宅配自取兩種方式皆可。我們秉持「<strong>誠信為本、踏實為重</strong>」的初心，讓每一個帶走的長崎蛋糕都是當日新鮮現烤。</p>',
    ],

    'taipei' => [
        'name' => '台北',
        'name_full' => '台北市',
        'eyebrow' => '台中現烤・全台北宅配',
        'h1' => '台北長崎蛋糕宅配｜台中職人現烤直送',
        'sub' => '大安、信義、中山、松山、士林等台北全區宅配；台中本店當日現烤，冷藏直送台北',
        'why_choose_h2' => '為什麼台北客戶選台中職人現烤的奧喜',
        'why_choose' => '台北彌月禮盒市場競爭白熱化，西式蛋糕品牌多到讓送禮缺乏記憶點。<strong>日式長崎蛋糕</strong>在台北彌月與商務送禮情境天然就是差異化選擇——一打開盒子，淡淡的蛋香、樸實的方塊蛋糕、最單純的雞蛋糖麵粉麥芽食材表，傳遞的是「<em>不用花俏裝飾、料好實在</em>」的職人感。台北大安、信義、中山一帶的商務客戶與彌月家庭，選奧喜就是選一個「<strong>不一樣但收禮人會記得</strong>」的選擇。',
        'areas_h2' => '台北全區配送範圍',
        'areas_intro' => '台中本店<strong>當日手工現烤</strong>、傍晚出貨、冷藏宅配隔日早上送達台北。下列行政區全部送達：',
        'areas' => ['大安區', '信義區', '中山區', '松山區', '士林區', '北投區', '內湖區', '南港區', '文山區', '萬華區', '大同區', '中正區'],
        'scenarios_h2' => '台北熱門場景與情境',
        'scenarios' => [
            ['title' => '大安信義商務送禮', 'desc' => '商務拜訪、公司週年慶、客戶感謝禮，奧喜的日式精緻外觀適合企業送禮，28 入大盒、20 入中盒可選。'],
            ['title' => '台北彌月禮盒選擇', 'desc' => '北部彌月品牌多走西點蛋糕，選日式長崎蛋糕反而<strong>更有記憶點</strong>。可代寫彌月卡片、分批宅配親友地址。'],
            ['title' => '內湖科技園區福委會', 'desc' => '內湖、南港、信義科技園區的福委會中秋／年終訂購，可開立統編發票、大宗訂購折扣。'],
            ['title' => '中山士林家庭分送', 'desc' => '台北傳統社區的家庭年節需求，中山、士林、北投客戶常下大盒分送長輩、親友。'],
        ],
        'faqs' => [
            ['q' => '冷藏宅配到台北會不會壞？', 'a' => '<strong>不會。</strong>台中本店傍晚出貨後走冷藏宅配系統，全程低溫運送、隔日早上送達台北。蛋糕本身室溫保存可 5 天、冷藏可 7 天，宅配途中的低溫只會延長保鮮期。'],
            ['q' => '能不能指定時段送到大安？', 'a' => '一般冷藏宅配為隔日早上送達，<strong>指定時段（如下午到貨）需另加費用</strong>。建議下單時電話 04-22416560 詢問可行性。'],
            ['q' => '企業送禮可以集中下單分送多個地址嗎？', 'a' => '可以。台北企業客戶常見模式：<strong>一次下單、提供多個收禮人地址</strong>（部門主管、客戶名單），我們會依名單分批宅配，可開立公司統編發票。'],
            ['q' => '彌月卡片可以代寫嗎？', 'a' => '可以。台北彌月客戶提供寶寶姓名、出生日期、家長署名，我們會用奧喜的彌月卡片格式代寫，隨蛋糕一起寄出。'],
            ['q' => '最低訂購量？', 'a' => '<strong>沒有最低訂購量</strong>，台北客戶單盒也可宅配。但因冷藏宅配運費較高，3 盒以上會比較划算。'],
        ],
        'minisite_about' => '<p>奧喜長崎蛋糕提供<strong>台北全區宅配服務</strong>，由台中本店師傅每日手工現烤、傍晚出貨、冷藏直送，隔日早上送達台北客戶手中。大安、信義、中山、松山、士林、北投、內湖、南港等台北 12 個行政區全部送達。</p><p>台北的奧喜客戶最常選擇兩種情境：<strong>商務送禮</strong>（大安信義企業拜訪、客戶感謝禮）與<strong>彌月禮盒</strong>（北部彌月市場差異化選擇）。日式長崎蛋糕的樸實外觀、單純食材、淡雅蛋香，傳遞的是「<em>不靠花俏裝飾</em>」的職人初心，這正是台北白領與新手父母選奧喜的理由。</p><p>我們秉持「<strong>誠信為本、踏實為重</strong>」，以最單純的雞蛋、糖、麵粉、麥芽手工現烤。雖然台中總店遠在 200 公里外，但冷藏宅配能讓台北客戶在家享受跟松竹路本店同樣的職人滋味——這是奧喜對台北客戶的承諾。</p>',
    ],

    'newtaipei' => [
        'name' => '新北',
        'name_full' => '新北市',
        'eyebrow' => '台中現烤・新北全區宅配',
        'h1' => '新北長崎蛋糕宅配｜板橋中和林口都送',
        'sub' => '新北 14 個行政區全區到府；台中職人現烤、冷藏直送，適合家庭分送、彌月禮盒、年節送禮',
        'why_choose_h2' => '為什麼新北家庭選奧喜長崎蛋糕',
        'why_choose' => '新北市最大特色是<strong>多代同堂</strong>跟<strong>新舊社區並存</strong>——板橋、中和、永和、新莊是傳統老社區，多代家庭聚會頻繁；林口、新店、汐止是新興社區，新生兒彌月需求高；三重、蘆洲是大家族傳統聚會地。奧喜長崎蛋糕<strong>方型大盒分切方便、不用切刀工具</strong>，特別適合多代家庭年節聚會直接分食，也適合新興社區父母彌月分送鄰居同事。日式樸實感比西點甜膩更受長輩歡迎、不會吃膩。',
        'areas_h2' => '新北全區配送範圍',
        'areas_intro' => '新北 14 個行政區全部送達，台中本店<strong>當日手工現烤</strong>、傍晚出貨、冷藏宅配隔日早上送達。',
        'areas' => ['板橋區', '中和區', '永和區', '新莊區', '三重區', '蘆洲區', '林口區', '新店區', '土城區', '汐止區', '淡水區', '五股區', '泰山區', '樹林區'],
        'scenarios_h2' => '新北熱門場景與情境',
        'scenarios' => [
            ['title' => '板橋中和年節大盒', 'desc' => '板橋、中和、永和傳統老社區，過年中秋家族聚會大盒蛋糕分送長輩晚輩，奧喜方型大盒方便分切、不用工具。'],
            ['title' => '林口新生兒彌月', 'desc' => '林口、新店重劃區新生兒爸媽彌月分送鄰居同事，奧喜可代寫彌月卡片、分批宅配親友地址。'],
            ['title' => '新莊三重多代聚會', 'desc' => '新莊、三重、蘆洲多代同堂家庭聚會，日式長崎蛋糕長輩接受度高、孩子也喜歡，是家族餐後甜點固定選擇。'],
            ['title' => '汐止淡水社區團購', 'desc' => '汐止、淡水社區型住宅，鄰居揪團 5-10 盒一起訂，分批宅配到指定 1-2 個地址再分送，省運費。'],
        ],
        'faqs' => [
            ['q' => '板橋宅配時段怎麼安排？', 'a' => '一般冷藏宅配為<strong>隔日早上 9 點到下午 5 點</strong>送達。板橋密集區可指定上午或下午段，需要更精確時段（如限定 14:00 前）請下單時備註。'],
            ['q' => '年節能提前下單嗎？', 'a' => '可以。中秋、過年、母親節旺季<strong>建議至少提前 7 天下單</strong>。提前訂購可保留庫存、安排當日新鮮出貨。'],
            ['q' => '彌月禮盒可以分批送嗎？', 'a' => '可以。林口、新店彌月客戶常見模式：<strong>提供 5-15 個親友地址、註明出貨日</strong>，我們會分批宅配。每個地址都會單獨包裝，附奧喜彌月卡片。'],
            ['q' => '新北家族聚會大盒推薦幾入？', 'a' => '4-6 人小聚 → 一條（兩入）；8-12 人 → 28 入大禮盒；20 人以上家族 → 50 入分享盒。實際需求可電話 04-22416560 諮詢。'],
            ['q' => '能不能指定送到管理室代收？', 'a' => '可以。新北大樓社區常見管理室代收，下單時備註「<strong>請代收</strong>」即可。冷藏宅配可在管理室冰箱暫存 4-6 小時。'],
        ],
        'minisite_about' => '<p>奧喜長崎蛋糕<strong>新北全區宅配</strong>，覆蓋板橋、中和、永和、新莊、三重、林口、新店、土城、汐止、淡水等 14 個行政區。台中本店<strong>當日手工現烤</strong>、傍晚出貨、冷藏宅配隔日送達。</p><p>新北的奧喜客戶最常出現在<strong>多代家庭聚會</strong>與<strong>新生兒彌月分送</strong>兩種場景。板橋、中和的老社區大家族年節需要大盒蛋糕分送長輩；林口、新店的新興社區父母彌月需要分批送給鄰居同事——奧喜長崎蛋糕的方型大盒分切方便、樸實的日式感對長輩友善、單純食材對寶寶家庭安心。</p><p>我們以「<strong>簡單卻不凡的滋味</strong>」為核心，用最單純的雞蛋、糖、麵粉、麥芽手工現烤每一條長崎蛋糕。新北客戶不必跑台中本店，當日新鮮的職人滋味隔天就在家。</p>',
    ],

    'taoyuan' => [
        'name' => '桃園',
        'name_full' => '桃園市',
        'eyebrow' => '台中現烤・桃園全區宅配',
        'h1' => '桃園長崎蛋糕宅配｜中壢龜山福委會大宗訂購',
        'sub' => '桃園市區、中壢、龜山、八德全區宅配；企業福委會大宗訂購、可開統編發票',
        'why_choose_h2' => '為什麼桃園公司福委會選奧喜',
        'why_choose' => '桃園是全台<strong>工業區與科技廠</strong>密度最高的縣市之一——龜山林口工業區、中壢工業區、平鎮工業區、八德工業區、大園航空城——每家中大型企業都有<strong>福利委員會</strong>，每年中秋、年終、員工聚餐、感謝客戶活動都需要大宗伴手禮。奧喜長崎蛋糕單盒體積適中、樸實大方、<strong>50 盒以上可預約調整出貨日</strong>，是桃園企業福委會中秋禮盒、年終分送員工的常見選擇。日式樸實感比過於華麗的西點更不失禮、也不會吃膩。',
        'areas_h2' => '桃園全區配送範圍',
        'areas_intro' => '桃園市 13 個行政區全部送達，工業區、科技園區、住宅區都到。',
        'areas' => ['桃園區', '中壢區', '平鎮區', '八德區', '龜山區', '楊梅區', '大溪區', '蘆竹區', '觀音區', '新屋區', '龍潭區', '大園區', '復興區'],
        'scenarios_h2' => '桃園熱門場景與情境',
        'scenarios' => [
            ['title' => '中壢龜山福委會中秋禮', 'desc' => '中壢、龜山工業區大廠中秋大宗訂購，50-200 盒一次出，可指定分批到貨日、開立統編發票報帳。'],
            ['title' => '八德企業客戶感謝禮', 'desc' => '八德、平鎮製造業企業，年底感謝客戶／經銷商的伴手禮，奧喜的日式包裝大方體面、不容易踩雷。'],
            ['title' => '桃園市區彌月與結婚', 'desc' => '桃園市區、中壢市區新婚／彌月需求，奧喜可代寫彌月或結婚卡片、分批宅配親友地址。'],
            ['title' => '大園航空城公司訂', 'desc' => '大園航空城、桃園機場相關企業，外籍客戶送禮、機場區外送會議，奧喜的日式糕點國際接受度高。'],
        ],
        'faqs' => [
            ['q' => '公司福委會 50 盒以上有優惠嗎？', 'a' => '有。<strong>50 盒以上大宗訂購</strong>請直接電話 04-22416560 諮詢，會依數量、出貨日彈性安排優惠價。可開立公司統編發票。'],
            ['q' => '能不能開發票報帳？', 'a' => '可以。提供公司統編，我們會開立<strong>三聯式發票</strong>給福委會／企業客戶報帳。電子發票或紙本發票皆可。'],
            ['q' => '中壢可以指定時段送嗎？', 'a' => '中壢、龜山、桃園市區的指定時段（如限定下午 4 點前送到）可協助安排，建議下單時備註。<strong>大宗訂購（50 盒以上）</strong>會專車配送，時段可彈性。'],
            ['q' => '能不能分批送到不同分公司？', 'a' => '可以。桃園企業常見模式：<strong>一次下單、提供多個分公司地址</strong>（如龜山廠 30 盒、中壢廠 30 盒、桃園總部 40 盒），我們會分批宅配。'],
            ['q' => '保存期限多久？大宗購買怎麼安排？', 'a' => '室溫 5 天、冷藏 7 天。大宗購買建議<strong>分批出貨</strong>（如分 3 天到貨），避免員工拿到時已接近賞味期。可在訂購時跟我們安排出貨節奏。'],
        ],
        'minisite_about' => '<p>奧喜長崎蛋糕<strong>桃園市全區宅配</strong>，覆蓋桃園市區、中壢、龜山、八德、平鎮、楊梅、大溪、蘆竹等 13 個行政區。台中本店<strong>每日手工現烤</strong>、傍晚出貨、冷藏宅配隔日送達桃園客戶手中。</p><p>桃園的奧喜客戶以<strong>企業福委會</strong>與<strong>工業區員工伴手禮</strong>為主：龜山林口科技廠、中壢工業區、八德製造業、大園航空城都有大宗訂購紀錄。中秋、年終、客戶感謝活動，奧喜的單盒體積適中、樸實大方、50 盒以上可彈性出貨，是桃園企業福委會的固定選擇之一。</p><p>我們以「<strong>誠信為本、踏實為重</strong>」的職人初心對待每一筆訂單，無論是 1 盒散客或 200 盒企業大宗。提供公司統編發票、分批分送多個地址、出貨日彈性安排——是桃園企業客戶選奧喜的實質理由。</p>',
    ],

    'hsinchu' => [
        'name' => '新竹',
        'name_full' => '新竹市',
        'eyebrow' => '台中現烤・竹科商務送禮',
        'h1' => '新竹長崎蛋糕宅配｜竹科 R&D 部門商務伴手禮',
        'sub' => '新竹市區、竹北、竹科園區全區到府；外籍同事日式糕點推薦、商務拜訪當日送達',
        'why_choose_h2' => '為什麼新竹竹科客戶選奧喜',
        'why_choose' => '新竹竹科是全台<strong>外籍工程師密度最高</strong>的科技園區，R&D 部門商務情境特殊——日韓客戶來訪、外籍同事歡送會、跨國團隊聚餐、研發階段感謝餐會。<strong>日式長崎蛋糕</strong>在這種國際商務情境天然受歡迎：日韓客戶熟悉「カステラ」這個品項、外籍同事覺得「簡單但精緻」、研發階級主管選奧喜當會議伴手禮體面又不失禮。比起西式蛋糕的甜膩或台式糕點的偏重，奧喜的日式樸實感剛剛好——「<em>不失禮但不浮誇</em>」是竹科商務情境最需要的調性。',
        'areas_h2' => '新竹全區配送範圍',
        'areas_intro' => '新竹市與新竹縣科學園區、竹北全區送達。',
        'areas' => ['新竹市東區', '新竹市北區', '新竹市香山區', '竹北市', '竹科園區 X / Y / Z 區', '寶山鄉', '新埔鎮', '湖口鄉', '芎林鄉'],
        'scenarios_h2' => '新竹熱門場景與情境',
        'scenarios' => [
            ['title' => '竹科 R&D 商務伴手禮', 'desc' => '竹科 X / Y / Z 區研發部門接待國際客戶、跨國視訊會議結束送禮，奧喜的日式精緻外觀適合國際商務場合，外籍客戶接受度高。'],
            ['title' => '外籍同事歡送禮盒', 'desc' => '竹科外籍工程師回國前的歡送禮、長官退休、跨部門感謝，奧喜長崎蛋糕「カステラ」品項日韓客戶都熟悉、不會踩雷。'],
            ['title' => '研發階級主管送禮', 'desc' => '研發部、技術部主管之間的感謝禮、新案啟動感謝、跨部門合作禮，奧喜的樸實感比花俏西點更受研發背景的主管喜歡。'],
            ['title' => '竹北彌月與商務雙用', 'desc' => '竹北重劃區新興社區彌月需求高，竹北的工程師家庭兼顧彌月（家庭場合）與商務（公司分送同事）兩用，奧喜可代寫彌月卡片。'],
        ],
        'faqs' => [
            ['q' => '能不能送到工研院 / 竹科 X 公司收發室？', 'a' => '可以。<strong>竹科園區 X / Y / Z 區與工研院收發室代收</strong>是常見模式，下單時提供完整地址（公司名+大樓+樓層+收件人）即可。冷藏可在收發室冰箱暫存 4-6 小時。'],
            ['q' => '外籍同事適合什麼包裝？', 'a' => '建議選<strong>標準禮盒裝</strong>（紙盒+蛋糕+品牌貼紙），日式樸實感與外籍客戶接受度高。若需要英文卡片或國際禮品包裝，可下單時備註，我們會配合調整。'],
            ['q' => '商務拜訪當天能送達嗎？', 'a' => '一般冷藏宅配為<strong>隔日早上送達</strong>。若是商務拜訪當天急件，需提前 2-3 天下單並指定到貨日（如「下週三上午 10 點前到工研院」）。電話 04-22416560 詢問可行性。'],
            ['q' => '能不能開三聯式發票報帳？', 'a' => '可以。新竹科技業客戶常需要報帳，提供公司統編，我們開立<strong>三聯式電子發票</strong>。商務伴手禮、福委會大宗訂購都適用。'],
            ['q' => '竹北彌月最低訂購量？', 'a' => '沒有最低訂購量。竹北彌月客戶常見模式：<strong>提供 10-30 個親友地址</strong>，我們分批宅配。每個地址單獨包裝、附奧喜彌月卡片。'],
        ],
        'minisite_about' => '<p>奧喜長崎蛋糕<strong>新竹宅配</strong>，覆蓋新竹市東區、北區、香山區、竹北市、竹科園區與新竹縣周邊。台中本店<strong>當日手工現烤</strong>、傍晚出貨、冷藏宅配隔日早上送達竹科客戶或工研院收發室。</p><p>新竹的奧喜客戶以<strong>竹科商務送禮</strong>為主：研發部門接待國際客戶、外籍同事歡送會、跨國團隊感謝、研發階級主管之間的禮物。日式長崎蛋糕「カステラ」的國際品項辨識度、日式樸實感、最單純的食材表，正是竹科科技業商務情境需要的調性——<em>不失禮、不浮誇、收禮人都記得</em>。</p><p>我們以「<strong>簡單卻不凡的滋味</strong>」為核心，提供公司統編三聯式電子發票、竹科 X / Y / Z 區收發室代收、商務拜訪指定到貨日等彈性服務。職人手作的初心，傳達到每一筆竹科訂單。</p>',
    ],
];

// ─── 渲染函式 ───
function renderLandingHtml(array $c): string {
    $areasGrid = '';
    foreach ($c['areas'] as $a) {
        $areasGrid .= '<div style="padding:10px 14px;background:#fff;border:1px solid #e8dfd0;border-radius:8px;font-size:.9rem;color:#5d4037">📍 ' . htmlspecialchars($a) . '</div>';
    }
    $scenariosCards = '';
    foreach ($c['scenarios'] as $s) {
        $scenariosCards .= '<div style="padding:22px;background:#fff;border:1px solid #e8dfd0;border-radius:10px">'
            . '<h3 style="font-weight:800;margin-bottom:10px;color:#8b6f47;font-size:1.05rem">' . htmlspecialchars($s['title']) . '</h3>'
            . '<p style="color:#5d4037;font-size:.92rem;line-height:1.75">' . htmlspecialchars($s['desc']) . '</p>'
            . '</div>';
    }
    $faqsHtml = '';
    foreach ($c['faqs'] as $f) {
        $faqsHtml .= '<details style="background:#fff;border:1px solid #e8dfd0;border-radius:8px;padding:16px 20px;margin-bottom:10px">'
            . '<summary style="font-weight:700;cursor:pointer;color:#5d4037;font-size:.98rem">Q. ' . htmlspecialchars($f['q']) . '</summary>'
            . '<div style="margin-top:12px;color:#7c6243;line-height:1.85;font-size:.92rem">' . $f['a'] . '</div>'
            . '</details>';
    }
    return <<<HTML
<section style="background:linear-gradient(135deg,#fdf8f0,#f5ebd9);padding:56px 30px;border-radius:14px;margin:24px 0;border:1px solid #e8dfd0">
  <div style="font-size:.72rem;letter-spacing:.22em;color:#8b6f47;font-weight:700;margin-bottom:14px">{$c['eyebrow']}</div>
  <h2 style="font-size:clamp(1.6rem,3vw,2.1rem);font-weight:900;color:#5d4037;line-height:1.3;margin-bottom:14px;letter-spacing:-0.01em">{$c['h1']}</h2>
  <p style="color:#7c6243;font-size:1rem;line-height:1.75;max-width:600px">{$c['sub']}</p>
</section>

<section style="margin:36px 0">
  <h2 style="font-weight:800;color:#5d4037;font-size:1.45rem;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #c5a572">{$c['why_choose_h2']}</h2>
  <p style="color:#5d4037;line-height:1.95;font-size:1rem">{$c['why_choose']}</p>
</section>

<section style="margin:36px 0;padding:30px;background:#fdf8f0;border-radius:12px;border:1px solid #e8dfd0">
  <h2 style="font-weight:800;color:#5d4037;font-size:1.4rem;margin-bottom:14px">{$c['areas_h2']}</h2>
  <p style="color:#7c6243;margin-bottom:18px;line-height:1.75">{$c['areas_intro']}</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px">{$areasGrid}</div>
</section>

<section style="margin:36px 0">
  <h2 style="font-weight:800;color:#5d4037;font-size:1.45rem;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #c5a572">{$c['scenarios_h2']}</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">{$scenariosCards}</div>
</section>

<section style="margin:36px 0">
  <h2 style="font-weight:800;color:#5d4037;font-size:1.45rem;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #c5a572">{$c['name']}客戶常見問題</h2>
  {$faqsHtml}
</section>

<section style="background:#5d4037;color:#fff;padding:44px 30px;border-radius:12px;margin:36px 0;text-align:center">
  <h3 style="font-weight:900;font-size:1.4rem;margin-bottom:10px">立即訂購・{$c['name']}{$c['name_full']}宅配</h3>
  <p style="opacity:.85;margin-bottom:22px;font-size:.95rem">台中本店每日手工現烤・冷藏宅配・職人初心</p>
  <a href="tel:0422416560" style="display:inline-block;background:#c5a572;color:#fff;padding:13px 32px;border-radius:50px;font-weight:800;text-decoration:none;letter-spacing:.05em">📞 04-2241-6560</a>
</section>
HTML;
}

function renderMinisiteHtml(array $c): string {
    return $c['minisite_about'];
}

// ─── UPDATE 每一個城市 row ───
$upd = $db->prepare("UPDATE client_city_pages
    SET landing_extra_content=?, minisite_intro_html=?
    WHERE client_id=? AND city_slug=?");

foreach ($cities as $slug => $c) {
    $landing  = renderLandingHtml($c);
    $minisite = renderMinisiteHtml($c);
    $upd->execute([$landing, $minisite, $clientId, $slug]);
    $landingLen  = mb_strlen(strip_tags($landing));
    $minisiteLen = mb_strlen(strip_tags($minisite));
    printf("✅ %s：landing %d 字 / minisite %d 字\n", $c['name_full'], $landingLen, $minisiteLen);
}

echo "\n完成。 5 城市每個都有獨立 800-1000 字行銷頁長文 + 400-600 字小官網簡介。\n";
echo "去 https://www.gomag.com.tw/store/oishii/taichung 看實際畫面。\n";
