<?php
// ============================================================
// Migration 125 — 冷淨億點（#251）移除「完工數據報告」改 LINE 相簿交付
// ------------------------------------------------------------
// Reney 指示（客戶決定）：報告太花時間不做了，改成「洗前洗後出風口溫度差異
// ＋清洗前後照片對比，當日上傳 LINE 相簿」。同步全站文案 + 四方吹案例改
// 真實 before/after 相簿（cassette-wuqi/before|after，已上傳）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/125_iruardro_drop_report.php
// 冪等：全 UPDATE / UPSERT
// ============================================================
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cid = (int)$db->query("SELECT id FROM clients WHERE subdomain='iruardro' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ iruardro not found\n"; exit(1); }

$db->beginTransaction();
try {
    // ── 1. clients：tagline / hero_stats / about_tags / minisite meta ──
    $tagline = '台中到府冷氣清洗品牌，主打無毒藥水＋全機拆卸高壓清洗，洗前洗後測溫對比、當日上傳 LINE 相簿，30 日保固。';
    $heroStats = json_encode([
        ['value' => '全機',  'label' => '拆卸深層清洗'],
        ['value' => '30日',  'label' => '完工保固'],
        ['value' => '2–3位', 'label' => '技師到府施工'],
        ['value' => '當日',  'label' => '上傳 LINE 相簿'],
    ], JSON_UNESCAPED_UNICODE);
    $aboutTags = json_encode([
        '🌿 專用無毒藥水', '💦 全機拆卸高壓清洗', '💬 當日上傳 LINE 相簿', '🛡️ 30 日完工保固',
    ], JSON_UNESCAPED_UNICODE);
    $miniTitle = '台中洗冷氣推薦｜冷淨億點清淨科技：無毒冷氣清洗・洗前洗後測溫對比';
    $miniDesc  = '台中洗冷氣找冷淨億點清淨科技：家用空調、商用空調、洗衣機到府清洗，全機拆卸＋專用無毒藥水＋高壓水槍，洗前洗後測出風口溫度差異、連同前後對比照當日上傳 LINE 相簿，30 日保固。服務台中全區，預約 0988-154194。';
    $db->prepare("UPDATE clients SET tagline=?, hero_stats=?, about_tags=?, minisite_meta_title=?, minisite_meta_desc=? WHERE id=?")
       ->execute([$tagline, $heroStats, $aboutTags, $miniTitle, $miniDesc, $cid]);
    echo "✓ clients tagline/hero_stats/about_tags/minisite meta\n";

    // ── 2. services ──────────────────────────────────
    // 2a. home-aircon 第 4 階段：服務報告 → 當日 LINE 相簿
    $homeFull = "冷氣用久了，藏在裡面的灰塵、黴菌會跟著風一起吹出來，對家裡有小孩、長輩的家庭特別不友善。\n\n施工四階段：\n1. 前置作業——室內機檢測、環境保護、清洗前測出風口溫度\n2. 開始清洗——全機拆卸、噴專用無毒藥水、高壓水槍清洗、裝機復原\n3. 完工檢測——清洗後再測一次出風口溫度、環境復原、當面點交\n4. 當日交付——把洗前洗後的溫度差異與清洗前後對比照，當天上傳到 LINE 相簿給你";
    $db->prepare("UPDATE services SET full_desc=? WHERE client_id=? AND slug='home-aircon'")->execute([$homeFull, $cid]);

    // 2b. commercial-aircon：完工附數據報告 → LINE 相簿
    $commFull = "店面、辦公室、營業場所的空調長時間運轉，更容易累積髒污、影響空氣品質與顧客體驗。\n\n依標準作業流程到府施工，清洗前後皆測出風口溫度，連同前後對比照當天上傳到 LINE 相簿，營業空間的空氣品質看得見。";
    $db->prepare("UPDATE services SET full_desc=? WHERE client_id=? AND slug='commercial-aircon'")->execute([$commFull, $cid]);

    // 2c. work-report：完工數據報告 → 洗前洗後對比相簿（slug 不動，保 URL）
    $wrName  = '洗前洗後對比相簿';
    $wrShort = '清洗前後各測一次出風口溫度，連同前後對比照當日上傳 LINE 相簿';
    $wrFull  = "我們不做事後才寄的報告，當天就讓你看見成果：\n\n．清洗前——先測一次出風口溫度\n．清洗後——同點位再測一次，溫度差異一目了然\n．當天就把洗前洗後的溫度與清洗前後對比照，上傳到 LINE 相簿給你\n\n效果不用我們說，溫度差異與乾淨程度，你自己看得見。";
    $db->prepare("UPDATE services SET name=?, short_desc=?, full_desc=? WHERE client_id=? AND slug='work-report'")
       ->execute([$wrName, $wrShort, $wrFull, $cid]);
    echo "✓ services home/commercial/work-report\n";

    // ── 3. FAQ 33：完工報告 → 怎麼確認洗乾淨 ─────────
    $db->prepare("UPDATE service_faqs SET question=?, answer=? WHERE id=33 AND client_id=?")
       ->execute([
           '洗完怎麼確認真的有洗乾淨？',
           '我們在清洗前後各測一次出風口溫度，連同清洗前後對比照，當天就上傳到 LINE 相簿給你——溫度差異與乾淨程度，你自己看得見，不用等報告。',
           $cid,
       ]);
    echo "✓ FAQ 33\n";

    // ── 4. seo_settings：title/desc 去報告 ───────────
    $seo = [
        'home' => ['台中洗冷氣推薦｜冷淨億點清淨科技：無毒冷氣清洗・洗前洗後測溫對比',
            '台中洗冷氣找冷淨億點清淨科技：家用空調、商用空調、洗衣機到府清洗，全機拆卸＋專用無毒藥水＋高壓水槍，洗前洗後測出風口溫度差異、前後對比照當日上傳 LINE 相簿，30 日保固。服務台中全區，預約 0988-154194。'],
        'services' => ['台中冷氣清洗服務項目｜家用空調・商用空調・洗衣機｜冷淨億點',
            '台中冷氣清洗服務：家用空調、洗衣機、商用空調到府清洗，另有年約包套與社區團洗優惠。全機拆卸＋專用無毒藥水，洗前洗後測溫對比、當日上傳 LINE 相簿。'],
        'testimonials' => ['台中冷氣清洗評價｜客戶好評與真實回饋｜冷淨億點清淨科技',
            '台中冷氣清洗客戶評價：冷淨億點到府清洗真實回饋陸續整理中，無毒藥水、洗前洗後測溫對比、30 日保固。'],
    ];
    $up = $db->prepare("INSERT INTO seo_settings (client_id,page_key,meta_title,meta_desc) VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc)");
    foreach ($seo as $pk => [$t, $d]) $up->execute([$cid, $pk, $t, $d]);
    echo "✓ seo_settings ×" . count($seo) . "\n";

    // ── 5. 四方吹案例 → 真實 before/after 相簿 ────────
    $db->prepare("UPDATE cases SET
            before_image='uploads/clients/iruardro/cases-albums/cassette-wuqi/before/01.jpg',
            after_image ='uploads/clients/iruardro/cases-albums/cassette-wuqi/after/01.jpg',
            description ='營業場所四方吹（嵌入式）空調定期清洗：拆下面板與風輪，把卡在裡面的霉斑與油塵徹底洗淨，清洗前後對比一目了然。營業時間外施工不影響生意。'
        WHERE client_id=? AND slug='cassette-wuqi'")->execute([$cid]);
    echo "✓ 四方吹案例改 before/after 相簿\n";

    $db->commit();
    echo "\n✅ Migration 125 完成\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "❌ " . $e->getMessage() . "\n";
    exit(1);
}
