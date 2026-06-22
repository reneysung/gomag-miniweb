<?php
// ============================================================
// Migration 144 — 聯漢(lanhung #214) 台東城市行銷頁變體（Reney 2026-06-22 選 B）
// ------------------------------------------------------------
// 背景：聯漢台東主站看不到，因台東記錄(124, slug Interiordesign214)早被當成
//   lanhung(214) 的重複、加進 getDuplicateSkipSlugs() 隱藏。Reney 選 B：
//   不取消隱藏 124，改幫維護中的 lanhung(214) 開台東變體（跨縣承接型，
//   同麥田台南/旭浪嘉義模式）→ 聯漢透過變體出現在台東目錄頁，卡片連到
//   /store/lanhung/taitung（台東角度行銷頁），台東客人導向維護中的記錄。
//
// lanhung(214) 已標 kw185(專業·室內設計) → 開台東變體後，citycat 子服務頁
//   「city_slug=taitung OR 有台東變體」條件即把它列上 /city/taitung/professional/interior-design。
// 跨縣承接：brand_name=NULL 沿用主檔、filter_cases_by_region=0 保留高雄案例展示實力、
//   address 寫服務範圍（避免卡片顯示高雄老家地址，即麥田踩過的坑）。
// 不能無中生有：只用聯漢可驗證事實(高雄基地、跨區服務台東、室內設計項目) + 台東公開地理。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/144_lanhung_taitung_store_variant.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$landing = <<<'HTML'
<style>
.lhtt{font-family:"Noto Sans TC",sans-serif;color:#2b2620;line-height:1.8;}
.lhtt h2{font-size:1.3rem;font-weight:800;color:#1d2b3a;margin:24px 0 10px;border-left:5px solid #ada17e;padding-left:12px;}
.lhtt h3{font-size:1.08rem;font-weight:700;color:#1d2b3a;margin:20px 0 8px;}
.lhtt p{margin:0 0 14px;}
.lhtt ul{margin:0 0 16px;padding-left:20px;}
.lhtt li{margin:0 0 6px;}
</style>
<div class="lhtt">
<h2>聯漢室內設計・跨區承接台東</h2>
<p>聯漢室內設計工作室以高雄為基地，長期跨區承接台東的室內設計與裝修案。台東在地的設計團隊選擇有限，從台東市到都蘭、知本與縱谷一帶的屋主，常需要找熟悉跨區施工的團隊。聯漢把材料調度、工班住宿與監工往返的流程整理清楚，讓跨縣施工不會變成屋主的麻煩。</p>
<h3>台東常見的承接類型</h3>
<ul>
<li><strong>民宿／包棟空間設計</strong>：動線與房型配置兼顧住宿體驗和日後營運維護。</li>
<li><strong>移居族老屋／二地居翻新</strong>：管線、防水、結構整理搭配適合慢生活的格局。</li>
<li><strong>自地自建室內配置</strong>：規劃階段就把格局、採光、收納一次安排好。</li>
</ul>
<p>跨區承接的報價會把設計費、工程款，與跨縣運輸、工班住宿分開列清楚，先談好再動工。台東的案子歡迎先線上或電話討論需求與現況，再安排現場勘估。</p>
</div>
HTML;

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cid = (int)$db->query("SELECT id FROM clients WHERE slug='lanhung'")->fetchColumn();
    if ($cid !== 214) { fwrite(STDERR, "lanhung id 非 214（=$cid），請確認\n"); }

    $exists = $db->query("SELECT id FROM client_city_pages WHERE client_id=$cid AND city_slug='taitung'")->fetchColumn();
    if ($exists) { echo "已有台東變體(id=$exists)，跳過\n"; exit; }

    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO client_city_pages
        (client_id, city_slug, city_label, brand_name, store_meta_title, store_meta_desc, store_keywords,
         address, landing_extra_content, filter_cases_by_region, is_active, sort_order, created_at)
        VALUES (?, 'taitung', '台東', NULL, ?, ?, ?, ?, ?, 0, 1, 0, NOW())");
    $stmt->execute([
        $cid,
        '台東室內設計推薦｜聯漢室內設計・台東民宿與移居宅・高雄團隊跨區承接',
        '台東室內設計找聯漢。高雄聯漢室內設計工作室跨區承接台東：民宿包棟、移居族老屋翻新、自地自建室內配置，跨縣施工流程清楚、報價透明。',
        '台東室內設計,台東民宿設計,台東老屋翻新,聯漢室內設計,台東裝潢,跨區室內設計',
        '高雄聯漢・跨區承接台東',
        $landing,
    ]);
    $vid = $db->lastInsertId();
    $db->commit();

    echo "✅ 已建 lanhung 台東變體 (id=$vid)\n";
    echo "  /city/taitung/professional/interior-design 應列出聯漢（via 變體）\n";
    echo "  /store/lanhung/taitung 為台東角度行銷頁\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
