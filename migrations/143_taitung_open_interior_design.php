<?php
// ============================================================
// Migration 143 — 打開台東 + 補台東室內設計內容頁（Reney 2026-06-22 指定 A）
// ------------------------------------------------------------
// 背景：聯漢室內設計工程公司(#124, city_slug=taitung) 是台東唯一客戶、已標室內設計，
//   但 cities.taitung is_active=0（<3家被關）+ 整列空 → /city/taitung 不列店、
//   /city/taitung/professional/interior-design 無內容 404 → 聯漢台東主站看不到。
// 做法：
//   1. cities.taitung 補在地文案（tagline/intro/highlights/areas/meta）+ is_active=1
//   2. 新增 geo_category_pages：台東 × 專業服務(cat8) × interior-design 內容頁
//      → 台東在地差異化（民宿包棟/移居老屋翻新/自地自建/跨縣施工），不照搬高雄
//   行情沿用全台通用業界範圍（設計費每坪 NT$3,000–8,000 等，已在高雄頁上線）
//   + 台東特有「跨縣施工運輸」中性提醒，不捏造台東專屬數字。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/143_taitung_open_interior_design.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$intro = <<<'HTML'
<p>台東室內設計的需求跟六都不太一樣。這裡以觀光民宿、移居族（二地居、退休移居）的老屋翻新，以及透天自地自建的室內配置為主；少有大型新成屋重劃區。台東在地的設計團隊數量有限，不少屋主會找高雄、花蓮的跨區團隊承接，所以「跨縣施工」怎麼算，是台東案子要先談清楚的一環。</p>
<p>台東常見的室內設計／裝修需求大致分四類：</p>
<ul>
<li><strong>民宿／包棟空間設計</strong>：動線、房型配置與海岸或縱谷的在地風格，要兼顧住宿體驗和日後營運維護。</li>
<li><strong>移居族老屋／二地居翻新</strong>：老屋的管線、防水、結構整理，搭配適合慢生活的格局與採光。</li>
<li><strong>自地自建室內配置</strong>：透天或自建宅在規劃階段就把格局、收納、採光一次安排好。</li>
<li><strong>退休宅／無障礙</strong>：安全動線、好整理的建材，住得久也住得安心。</li>
</ul>
<p>行情上，設計費每坪約 NT$3,000–8,000；全案新成屋連工帶料每坪約 NT$4–8 萬；老屋翻新每坪約 NT$6–12 萬，屋況落差大、務必現場勘估。台東要特別留意的是<strong>跨縣施工成本</strong>——在地工班與建材選擇較少，跨區團隊的材料運輸、工班住宿與監工往返常會另計，報價時請對方把這部分白紙黑字列清楚。</p>
<p>挑台東室內設計，建議看三件事：①<strong>報價是否拆清楚</strong>（設計費、監工費、工程款，跨區運輸另列）；②<strong>合約與分期驗收</strong>是否明確（分階段驗收付款較有保障）；③<strong>看實際完工案例與跨區施工經驗</strong>。下方為店家好口碑收錄的台東在地室內設計團隊。</p>
HTML;

$faqs = [
  ['q'=>'台東室內設計費怎麼算？','a'=>'設計費每坪約 NT$3,000–8,000，有些以總工程款比例計或「設計監工綁工程」。台東若找跨區團隊，材料運輸與監工往返常另計，簽約前先問清楚。'],
  ['q'=>'台東在地設計團隊少，找高雄或花蓮跨區可以嗎？','a'=>'可以，台東不少案子就是跨區承接。重點是把跨區的材料運費、工班住宿、監工往返怎麼計問清楚並寫進報價，避免事後爭議。'],
  ['q'=>'民宿或包棟空間設計要注意什麼？','a'=>'除了好看，更要顧營運：房型與動線配置、清潔維護的方便性、在地風格的呈現，住宿體驗和日後管理一起想。'],
  ['q'=>'移居台東的老屋翻新每坪多少？','a'=>'約 NT$6–12 萬，因屋況（管線、防水、結構、壁癌）落差大，務必現場勘估後再報價，別只看每坪均價。'],
  ['q'=>'自地自建要不要找室內設計師？','a'=>'建議規劃階段就介入，格局、採光、收納與動線一次到位，比蓋好再改省成本，也比較不會留下難用的空間。'],
  ['q'=>'跨縣施工的運輸成本怎麼抓？','a'=>'材料運費、工班住宿、監工往返都會反映在報價。請團隊在估價單把跨區費用獨立列出，比較不同團隊時才公平。'],
  ['q'=>'設計到完工大概多久？','a'=>'民宿或全案約 2–4 個月；老屋翻新因工序多常 3–6 個月。跨區施工要把材料物流與工班排程的時間一起算進去。'],
  ['q'=>'簽約要注意什麼？','a'=>'確認工程範圍、建材表、分期付款與驗收節點、跨區費用計算、保固範圍與年限，避免只有口頭承諾。'],
];

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 備份
    $bk = [
        'city' => $db->query("SELECT * FROM cities WHERE slug='taitung'")->fetch(PDO::FETCH_ASSOC),
        'geo_exists' => $db->query("SELECT COUNT(*) FROM geo_category_pages WHERE city_slug='taitung' AND service_slug='interior-design'")->fetchColumn(),
    ];
    @mkdir(__DIR__ . '/../_backups', 0755, true);
    file_put_contents(__DIR__ . '/../_backups/taitung_open_' . date('Ymd-His') . '.json', json_encode($bk, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $db->beginTransaction();

    // 1. cities.taitung 補文案 + 啟用
    $db->prepare("UPDATE cities SET tagline=?, intro=?, highlights=?, areas=?, meta_title=?, meta_desc=?, is_active=1, updated_at=NOW() WHERE slug='taitung'")->execute([
        '從台東市到縱谷、海岸到知本 — 台東在地店家口碑指南',
        '台東步調慢、在地老店多。從台東市的生活圈，到縱谷的池上、關山，海岸線的都蘭、成功，加上知本溫泉與觀光民宿經濟，店家好口碑為台東在地居民與移居族整理真實的在地推薦清單。',
        json_encode(['在地老店多','慢生活步調','觀光民宿經濟','移居二地居風潮'], JSON_UNESCAPED_UNICODE),
        json_encode(['台東市','知本','都蘭','池上','關山','成功','鹿野','太麻里'], JSON_UNESCAPED_UNICODE),
        '台東在地店家推薦｜店家好口碑',
        '台東縣在地好店口碑指南：台東市、知本、都蘭、池上、關山的在地商家推薦，從室內設計到生活服務，移居台東與在地生活都適用。',
    ]);

    // 2. 台東室內設計內容頁（專業服務 cat8）
    $exists = $db->query("SELECT id FROM geo_category_pages WHERE city_slug='taitung' AND category_id=8 AND service_slug='interior-design'")->fetchColumn();
    if ($exists) {
        $db->prepare("UPDATE geo_category_pages SET service_name=?, meta_title=?, meta_desc=?, intro_html=?, faqs=?, is_active=1, updated_at=NOW() WHERE id=?")
           ->execute(['室內設計','台東室內設計推薦｜民宿包棟・移居老屋翻新・自地自建','台東室內設計怎麼找？民宿包棟設計、移居族老屋翻新、自地自建配置的流程與挑設計師重點，跨縣施工運輸須知，加上店家好口碑收錄的台東在地室內設計團隊。',$intro,json_encode($faqs,JSON_UNESCAPED_UNICODE),$exists]);
        echo "更新既有 geo (id=$exists)\n";
    } else {
        $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, meta_title, meta_desc, intro_html, faqs, is_active, created_at, updated_at) VALUES ('taitung',8,'interior-design','室內設計',?,?,?,?,1,NOW(),NOW())")
           ->execute(['台東室內設計推薦｜民宿包棟・移居老屋翻新・自地自建','台東室內設計怎麼找？民宿包棟設計、移居族老屋翻新、自地自建配置的流程與挑設計師重點，跨縣施工運輸須知，加上店家好口碑收錄的台東在地室內設計團隊。',$intro,json_encode($faqs,JSON_UNESCAPED_UNICODE)]);
        echo "新增 geo (id=" . $db->lastInsertId() . ")\n";
    }

    $db->commit();
    echo "✅ 完成\n";
    echo "  cities.taitung is_active：" . $db->query("SELECT is_active FROM cities WHERE slug='taitung'")->fetchColumn() . "\n";
    echo "  台東室內設計內容頁：" . $db->query("SELECT COUNT(*) FROM geo_category_pages WHERE city_slug='taitung' AND category_id=8 AND service_slug='interior-design' AND is_active=1")->fetchColumn() . " 篇\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
