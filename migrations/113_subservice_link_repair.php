<?php
// ============================================================
// Migration 113 — 子服務頁內鏈孤立度修復（A4 全套）
// ------------------------------------------------------------
// 起因：/city/tainan/home-service/reno-detail 與 /kaohsiung/... 都未被 Google 收錄。
// 診斷：技術 OK、sitemap 有、內容夠，但站內 guide **0 內鏈**到 reno-detail。
//
// A. 12 篇細清 guide 結尾統一補對應城市子服務頁內鏈
// B. 通用攻略加 6 都子服務頁內鏈（與 A 同處理）
// C. 補 tainan + kaohsiung 的 home-service 樞紐頁 geo row（仿 110 模式）
// A4. 系統性盤點全站子服務頁內鏈狀況
//
// 冪等性：appended section 用 marker `<!-- related-services-block -->` 防重跑
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/113_subservice_link_repair.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cityNames = [
    'taipei' => '台北', 'newtaipei' => '新北', 'taoyuan' => '桃園',
    'hsinchu' => '新竹', 'taichung' => '台中', 'changhua' => '彰化',
    'chiayi' => '嘉義', 'tainan' => '台南', 'kaohsiung' => '高雄',
    'pingtung' => '屏東', 'yilan' => '宜蘭', 'hualien' => '花蓮', 'taitung' => '台東',
];

// ════════════════════════════════════════════
// A4. 系統性盤點：所有子服務頁的內鏈狀況
// ════════════════════════════════════════════
echo "═══ A4. 全站子服務頁內鏈孤立度盤點 ═══\n";
$subs = $db->query("SELECT city_slug, category_id, service_slug, service_name
    FROM geo_category_pages
    WHERE service_slug <> '' AND is_active = 1
    ORDER BY category_id, city_slug, service_slug")->fetchAll(PDO::FETCH_ASSOC);
$catMap = $db->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

$isolated = [];
foreach ($subs as $s) {
    $url = "/city/{$s['city_slug']}/{$catMap[$s['category_id']]}/{$s['service_slug']}";
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%{$url}%"]);
    $n = (int)$st->fetchColumn();
    if ($n === 0) {
        $isolated[] = ['url' => $url, 'name' => "{$s['city_slug']} {$s['service_name']}"];
    }
}
printf("  總子服務頁：%d 個｜0 內鏈孤立：%d 個（%.0f%%）\n",
    count($subs), count($isolated), count($subs) > 0 ? 100 * count($isolated) / count($subs) : 0);
echo "  孤立頁清單：\n";
foreach (array_slice($isolated, 0, 30) as $i) {
    printf("    %s（%s）\n", $i['url'], $i['name']);
}
if (count($isolated) > 30) echo "    ...（還有 " . (count($isolated) - 30) . " 個）\n";

// ════════════════════════════════════════════
// A + B. 12 篇細清 guide 補對應城市內鏈
// ════════════════════════════════════════════
echo "\n═══ A+B. 補 12 篇細清 guide 內鏈 ═══\n";
$marker = '<!-- related-services-block-v1 -->';

$guides = [
    ['slug' => 'cleaning-detail-vs-basic', 'cities' => ['taipei','newtaipei','taoyuan','taichung','hsinchu','tainan','kaohsiung'], 'note' => '通用'],
    ['slug' => 'tainan-fine-clean-recommend', 'cities' => ['tainan']],
    ['slug' => 'kaohsiung-fine-clean-recommend', 'cities' => ['kaohsiung']],
    ['slug' => 'taipei-fine-clean-recommend', 'cities' => ['taipei']],
    ['slug' => 'newtaipei-fine-clean-recommend', 'cities' => ['newtaipei']],
    ['slug' => 'taoyuan-fine-clean-recommend', 'cities' => ['taoyuan']],
    ['slug' => 'taichung-fine-clean-recommend', 'cities' => ['taichung']],
    ['slug' => 'hsinchu-fine-clean-recommend', 'cities' => ['hsinchu']],
    ['slug' => 'taipei-move-out-fine-clean-guide', 'cities' => ['taipei']],
    ['slug' => 'newtaipei-handover-fine-clean-guide', 'cities' => ['newtaipei']],
    ['slug' => 'taoyuan-new-house-fine-clean-guide', 'cities' => ['taoyuan']],
    ['slug' => 'hsinchu-tech-fine-clean-guide', 'cities' => ['hsinchu']],
];

$updated = 0; $skipped = 0;
foreach ($guides as $g) {
    $row = $db->prepare("SELECT body_html FROM guides WHERE slug=?");
    $row->execute([$g['slug']]);
    $body = $row->fetchColumn();
    if (!$body) { echo "  ❌ 找不到 {$g['slug']}\n"; continue; }
    if (strpos($body, $marker) !== false) { $skipped++; echo "  ⏭  skip {$g['slug']}（已有 marker）\n"; continue; }

    $isGeneric = count($g['cities']) > 1;
    $title = $isGeneric ? "延伸閱讀：6 都裝潢細清與清潔服務" : "延伸閱讀：{$cityNames[$g['cities'][0]]} 居家服務子分類";
    $intro = $isGeneric
        ? "<p>找對應城市的在地裝潢細清、清潔公司與冷氣清洗服務：</p>"
        : "<p>找{$cityNames[$g['cities'][0]]}在地相關居家服務：</p>";

    $appendHtml = "\n\n{$marker}\n<h2 id=\"related-services\">{$title}</h2>\n{$intro}\n<ul>\n";
    foreach ($g['cities'] as $c) {
        $cn = $cityNames[$c];
        $appendHtml .= "  <li><strong>{$cn}</strong>："
            . "<a href=\"/city/{$c}/home-service/reno-detail\">{$cn}裝潢細清</a>｜"
            . "<a href=\"/city/{$c}/home-service/cleaning\">{$cn}清潔公司</a>｜"
            . "<a href=\"/city/{$c}/home-service/aircon-clean\">{$cn}冷氣清洗</a>"
            . "</li>\n";
    }
    $appendHtml .= "</ul>\n";
    $appendHtml .= "<p>更多居家服務見 <a href=\"/city/{$g['cities'][0]}/home-service\">{$cityNames[$g['cities'][0]]}居家服務</a> 樞紐頁。</p>\n";

    $newBody = $body . $appendHtml;
    $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$newBody, $g['slug']]);
    $updated++;
    $note = $g['note'] ?? $cityNames[$g['cities'][0]];
    echo "  ✅ {$g['slug']}（{$note}）→ 補 " . count($g['cities']) . " 城內鏈\n";
}
echo "  小計：updated={$updated}、skipped={$skipped}\n";

// ════════════════════════════════════════════
// C. tainan + kaohsiung home-service 樞紐頁 geo row
// ════════════════════════════════════════════
echo "\n═══ C. 補 home-service 樞紐頁 geo row（tainan + kh）═══\n";

$hubData = [
    'tainan' => [
        'meta_title' => '台南居家服務推薦 2026｜清潔・細清・冷氣清洗・系統櫃｜店家好口碑',
        'meta_desc' => '台南居家服務推薦哪幾家？涵蓋清潔公司、裝潢細清、冷氣清洗、系統櫃、室內設計、油漆防水、除蟲、驗屋等子分類，府城中西區、東區、永康、安平在地口碑商家收錄。',
        'intro' => <<<HTML
<p>台南居家服務涵蓋面廣，從<strong>中西區、東區、永康、安平</strong>等住宅密集區的日常清潔、新成屋與重劃區的裝潢後細清、夏季旺季的冷氣清洗，到室內設計、系統櫃、油漆防水、除蟲消毒、驗屋等全方位居家需求。</p>

<p><strong>台南居家服務怎麼挑？</strong>看你今天要做什麼：</p>

<ul>
  <li>日常維護 → <a href="/city/tainan/home-service/cleaning">台南清潔公司</a>、<a href="/city/tainan/home-service/aircon-clean">台南冷氣清洗</a></li>
  <li>新成屋／裝潢後 → <a href="/city/tainan/home-service/reno-detail">台南裝潢細清</a>、<a href="/city/tainan/home-service/handover-inspection">台南交屋驗屋</a></li>
  <li>客變裝修 → <a href="/city/tainan/home-service/interior-design">台南室內設計</a>、<a href="/city/tainan/home-service/system-cabinet">台南系統櫃</a>、<a href="/city/tainan/home-service/paint-waterproof">台南油漆防水</a></li>
</ul>

<p>挑台南居家服務商家的 3 個重點：① <strong>看在地口碑</strong>（Google 評論 ≥ 4.3、評論數 ≥ 50）；② <strong>看服務範圍</strong>（是否含府城東區、永康、新化、新營等較遠區域）；③ <strong>看報價透明度</strong>（含/不含搬挪家具、垃圾清運、藥水）。</p>
HTML,
        'faqs' => [
            ['q' => '台南居家服務行情大概多少？',
             'a' => '日常清潔每小時 NT$400–600；裝潢後細清每坪 NT$300–500（整戶 NT$15,000–40,000 視坪數）；冷氣清洗每台基本 NT$1,400–2,400、全機拆洗 NT$2,400–4,000。'],
            ['q' => '台南清潔公司跟裝潢細清差在哪？',
             'a' => '清潔公司多做日常維護、退租、辦公室、社區大樓；裝潢細清專做新成屋／裝潢後的粉塵、矽利康殘膠、玻璃水痕等高難度清理，工具與藥水都不同，費用約清潔公司的 1.5–2 倍。'],
            ['q' => '台南旺季什麼時候？',
             'a' => '清潔公司 6 月（搬家潮）、11–12 月（過年大掃除）旺；冷氣清洗 4–8 月旺；裝潢細清新建案交屋潮（每季均有）。旺季要提前 2–4 週預約。'],
            ['q' => '台南哪些區服務需求最大？',
             'a' => '東區、中西區、永康、南區（人口密、住宅多）為主力區，安平、安南有觀光住宿與海邊鹽害需求，新營、麻豆、新化等外圍區師傅較少要早預約。'],
            ['q' => '一通電話可以同時叫多個服務嗎？',
             'a' => '部分業者「一站式」可同時派清潔＋冷氣清洗＋系統櫃，省事但要確認每項都是專業師傅、不是外包品質參差。價格上「打包」未必比較便宜，先個別比價再決定。'],
            ['q' => '怎麼判斷不是黑店？',
             'a' => '① 完整公司資訊（統編、地址、電話三件齊）；② 報價單明列項目與保固；③ 在地口碑（Google 評論、本站收錄商家清單交叉驗證）；④ 不要先付全額，預付訂金不超過 30%。'],
        ],
    ],
    'kaohsiung' => [
        'meta_title' => '高雄居家服務推薦 2026｜清潔・細清・冷氣清洗・系統櫃｜店家好口碑',
        'meta_desc' => '高雄居家服務推薦哪幾家？涵蓋清潔公司、裝潢細清、冷氣清洗、系統櫃、室內設計、油漆防水、除蟲、驗屋等子分類，三民、左營、苓雅、鼓山、楠梓、鳳山在地口碑商家收錄。',
        'intro' => <<<HTML
<p>高雄居家服務涵蓋面廣，從<strong>三民、左營、苓雅、鼓山、楠梓、鳳山</strong>等核心區的日常清潔、巨蛋與美術館重劃區的新成屋裝潢細清、港都濕熱長夏的冷氣清洗，到室內設計、系統櫃、油漆防水、除蟲消毒、驗屋等全方位居家需求。</p>

<p><strong>高雄居家服務怎麼挑？</strong>看你今天要做什麼：</p>

<ul>
  <li>日常維護 → <a href="/city/kaohsiung/home-service/cleaning">高雄清潔公司</a>、<a href="/city/kaohsiung/home-service/aircon-clean">高雄冷氣清洗</a></li>
  <li>新成屋／裝潢後 → <a href="/city/kaohsiung/home-service/reno-detail">高雄裝潢細清</a>、<a href="/city/kaohsiung/home-service/handover-inspection">高雄交屋驗屋</a></li>
  <li>客變裝修 → <a href="/city/kaohsiung/home-service/interior-design">高雄室內設計</a>、<a href="/city/kaohsiung/home-service/system-cabinet">高雄系統櫃</a>、<a href="/city/kaohsiung/home-service/paint-waterproof">高雄油漆防水</a></li>
</ul>

<p>挑高雄居家服務商家的 3 個重點：① <strong>看在地口碑</strong>（Google 評論 ≥ 4.3、評論數 ≥ 50）；② <strong>看服務範圍</strong>（是否含鳳山、林園、岡山、橋頭等較遠區域）；③ <strong>看報價透明度</strong>（含/不含搬挪家具、垃圾清運、藥水成分）。</p>
HTML,
        'faqs' => [
            ['q' => '高雄居家服務行情大概多少？',
             'a' => '日常清潔每小時 NT$400–650；裝潢後細清每坪 NT$300–500（整戶 NT$15,000–40,000）；冷氣清洗每台基本 NT$1,400–2,400、全機拆洗 NT$2,400–4,000。港都濕熱、冷氣使用率高，要找鹽害經驗的師傅。'],
            ['q' => '高雄清潔公司跟裝潢細清差在哪？',
             'a' => '清潔公司多做日常維護、退租、辦公室、社區大樓；裝潢細清專做新成屋／裝潢後的粉塵、矽利康殘膠、玻璃水痕等，工具與藥水都不同，費用約清潔公司的 1.5–2 倍。'],
            ['q' => '高雄旺季什麼時候？',
             'a' => '清潔公司 6 月（搬家潮）、11–12 月（過年大掃除）旺；冷氣清洗 3–9 月旺（高雄夏季更長）；裝潢細清新建案交屋潮（巨蛋、美術館、亞洲新灣區持續推案）。旺季要提前 2–4 週預約。'],
            ['q' => '高雄哪些區服務需求最大？',
             'a' => '三民、左營、苓雅、前金、鼓山（核心市區），鳳山、楠梓（人口密住宅多），鳳山、林園、岡山、橋頭、路竹屬外圍要早預約。鼓山、前鎮、小港近海有鹽害要找有經驗的師傅。'],
            ['q' => '港都濕熱怎麼處理？',
             'a' => '黴菌防治建議每 1–2 年做一次（梅雨季與颱風後最容易長黴）；冷氣排水盤建議與清洗一起做藥水殺菌；近海住戶可加 1 次/年 不鏽鋼/金屬鹽害清洗。'],
            ['q' => '怎麼判斷不是黑店？',
             'a' => '① 完整公司資訊（統編、地址、電話三件齊）；② 報價單明列項目與保固；③ 在地口碑（Google 評論、本站收錄商家清單交叉驗證）；④ 不要先付全額，預付訂金不超過 30%。'],
        ],
    ],
];

$upsert = $db->prepare("INSERT INTO geo_category_pages
    (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, 2, '', '', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE
        intro_html=VALUES(intro_html), faqs=VALUES(faqs),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");

foreach ($hubData as $city => $d) {
    $upsert->execute([$city, $d['intro'], json_encode($d['faqs'], JSON_UNESCAPED_UNICODE), $d['meta_title'], $d['meta_desc']]);
    echo "  ✅ {$city} home-service 樞紐頁 geo row 已寫\n";
}

// ════════════════════════════════════════════
// 驗證：reno-detail 內鏈數 + 樞紐頁
// ════════════════════════════════════════════
echo "\n═══ 驗證 ═══\n";
foreach (['tainan', 'kaohsiung'] as $c) {
    $st = $db->prepare("SELECT COUNT(*) FROM guides WHERE body_html LIKE ? AND status='published'");
    $st->execute(["%/city/{$c}/home-service/reno-detail%"]);
    echo "  {$c} reno-detail 內鏈數：" . (int)$st->fetchColumn() . " 篇 guide\n";
}
foreach (['tainan', 'kaohsiung'] as $c) {
    $row = $db->query("SELECT meta_title FROM geo_category_pages WHERE city_slug='{$c}' AND category_id=2 AND service_slug=''")->fetch(PDO::FETCH_ASSOC);
    echo "  {$c} home-service 樞紐 meta_title: " . ($row['meta_title'] ?? '(無 row)') . "\n";
}

echo "\n完成。下一步去 GSC 對下列 URL 按「要求索引」：\n";
echo "  https://www.gomag.com.tw/city/tainan/home-service/reno-detail\n";
echo "  https://www.gomag.com.tw/city/kaohsiung/home-service/reno-detail\n";
echo "  https://www.gomag.com.tw/city/tainan/home-service\n";
echo "  https://www.gomag.com.tw/city/kaohsiung/home-service\n";
