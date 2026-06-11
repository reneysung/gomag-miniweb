<?php
// ============================================================
// Migration 121 — 冷淨億點清淨科技（#251 iruardro）小官網內容種子
// ------------------------------------------------------------
// 內容來源：該客戶行銷頁 landing_extra_content（已核可文案），
// 依「不能無中生有」鐵則，不新增任何未經查證的事實／數字／評分。
//
// 種入：
//   - services ×6（家用空調／洗衣機／商用空調／年約包套／社區團洗／完工數據報告）
//   - service_faqs ×6（答案全部出自行銷頁的保固／流程／藥水說明）
//   - seo_settings：補 home/services/cases meta_desc + 新增 testimonials row
//   - clients：hero_stats（全真實事實，不放假 Google 評分）/ about_tags /
//              owner_name+intro / minisite_meta_title+desc
//   - has_minisite 不在此翻開（等 hPanel 子網域開通後再翻）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/121_iruardro_minisite.php
// 冪等：services/service_faqs 先 DELETE 再 INSERT；其他 UPDATE / UPSERT
// ============================================================
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cid = (int)$db->query("SELECT id FROM clients WHERE subdomain='iruardro' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ iruardro not found\n"; exit(1); }
echo "client_id={$cid}\n";

$db->beginTransaction();
try {
    // ── 1. services（重種，冪等）─────────────────────────
    $db->prepare("DELETE FROM service_faqs WHERE client_id=?")->execute([$cid]);
    $db->prepare("DELETE FROM services WHERE client_id=?")->execute([$cid]);

    $services = [
        // [slug, name, icon, short_desc, full_desc]
        ['home-aircon', '家用空調清洗', '❄️',
         '分離式室內機等家用冷氣，全機拆卸深層清洗，把藏在裡面的霉味與灰塵洗乾淨。',
         "冷氣用久了，藏在裡面的灰塵、黴菌會跟著風一起吹出來，對家裡有小孩、長輩的家庭特別不友善。\n\n施工四階段：\n1. 前置作業——室內機檢測、環境保護、清洗前測溫測風\n2. 開始清洗——全機拆卸、噴專用無毒藥水、高壓水槍清洗、裝機復原\n3. 完工檢測——清洗後測溫測風、環境復原、當面點交\n4. 服務報告——完工 2–3 個工作天內，把清洗前後照片與溫度、風速整理成報告給你"],
        ['washing-machine', '洗衣機清洗', '🧺',
         '洗衣機內槽長期累積的污垢與黴菌，拆洗後讓衣物洗得更安心。',
         "洗衣機內槽是看不到的衛生死角，長期累積的污垢與黴菌會沾回洗好的衣物上。\n\n到府拆洗內槽，把累積的污垢與黴菌清乾淨，讓全家人的衣物洗得更安心。"],
        ['commercial-aircon', '商用空調清洗', '🏢',
         '店面、辦公室、營業場所的空調設備清洗，維持室內空氣品質與顧客體驗。',
         "店面、辦公室、營業場所的空調長時間運轉，更容易累積髒污、影響空氣品質與顧客體驗。\n\n依標準作業流程到府施工，清洗前後皆測溫測風，完工附數據報告，讓營業空間的空氣品質看得見。"],
        ['annual-plan', '年約包套方案', '📅',
         '固定保養需求的家庭與店家，年約包套更省心，定期維持冷氣效能。',
         "有固定保養需求的家庭與店家，可選擇年約包套方案。\n\n定期清洗保養，維持冷氣效能，不必每次重新詢價、約時間，更省心。詳細方案內容歡迎來電或私訊洽詢。"],
        ['community-group', '社區團洗優惠', '🏘️',
         '同社區、同棟一起預約，享團洗優惠，鄰居一起把冷氣洗乾淨。',
         "同社區、同棟住戶一起預約清洗，即可享團洗優惠。\n\n鄰居揪團一起把冷氣洗乾淨，技師同日到場施工，省下的費用大家分。歡迎管委會或住戶代表與我們聯繫。"],
        ['work-report', '完工數據報告', '📊',
         '每次清洗都附前後對比照與風量、溫度數據，效果看得見。',
         "我們把「洗乾淨」變成看得到的數據：\n\n．清洗前——先測出風口溫度與風量\n．清洗後——再測一次同點位數據\n．完工 2–3 個工作天內，連同清洗前後對比照整理成報告提供給你\n\n效果不用我們說，數字會說話。"],
    ];
    $ins = $db->prepare("INSERT INTO services (client_id,name,slug,short_desc,full_desc,price_text,icon,sort_order,is_active) VALUES (?,?,?,?,?,'',?,?,1)");
    $svcIds = [];
    foreach ($services as $i => [$slug, $name, $icon, $sd, $fd]) {
        $ins->execute([$cid, $name, $slug, $sd, $fd, $icon, $i]);
        $svcIds[$slug] = (int)$db->lastInsertId();
    }
    echo "✓ services ×" . count($services) . "\n";

    // ── 2. service_faqs（答案全部出自行銷頁文案）──────────
    $faqs = [
        ['home-aircon', '清洗用的藥水安全嗎？家裡有小孩、寵物可以洗嗎？',
         '我們施工使用專用無毒藥水，搭配高壓水槍清洗。家裡有小孩、長輩或寵物的家庭也能比較放心。'],
        ['home-aircon', '清洗完有保固嗎？保固範圍是什麼？',
         '提供 30 日完工保固：完工 30 日內施工機型若出現故障、異音、滲水，通知我們即派技師到場檢測，可歸咎於清洗的無條件維修；非清洗造成的問題，也會協助找師傅評估。'],
        ['home-aircon', '怎麼預約？流程是什麼？',
         '透過 Facebook、IG、Threads、電話或官方 LINE 與我們聯繫，拍室內機照片供技師估價，確認方案與時間後安排施工。當日 2–3 位技師準時到場，依標準流程施工，完工測試與你當面核對無誤後才付清款項。'],
        ['home-aircon', '完工報告什麼時候拿到？內容有什麼？',
         '完工後 2–3 個工作天內提供：清洗前後對比照，加上出風口風量、溫度數據，整理成完工報告供你參閱。'],
        ['commercial-aircon', '營業場所可以約非營業時間施工嗎？',
         '可以，預約時告知營業時間，我們會與你確認施工地點與時段後再安排技師到場。'],
        ['community-group', '社區團洗要怎麼揪？',
         '同社區、同棟一起預約即可享團洗優惠，歡迎管委會或住戶代表先與我們聯繫，確認戶數與機型後報價、安排同日施工。'],
    ];
    $insF = $db->prepare("INSERT INTO service_faqs (service_id,client_id,question,answer,sort_order) VALUES (?,?,?,?,?)");
    foreach ($faqs as $i => [$slug, $q, $a]) {
        $insF->execute([$svcIds[$slug], $cid, $q, $a, $i]);
    }
    echo "✓ service_faqs ×" . count($faqs) . "\n";

    // ── 3. seo_settings ──────────────────────────────────
    $seo = [
        'home' => ['台中冷氣清洗｜冷淨億點清淨科技 無毒到府．完工附報告',
            '台中冷氣清洗推薦冷淨億點清淨科技。家用空調、洗衣機、商用空調到府清洗，全機拆卸＋專用無毒藥水＋高壓水槍，完工附清洗前後對比照與風量溫度報告，30 日完工保固。服務台中全區，預約 0988-154194。'],
        'services' => ['服務項目｜冷淨億點清淨科技 台中到府冷氣清洗',
            '家用空調清洗、洗衣機清洗、商用空調清洗，另有年約包套與社區團洗優惠。全機拆卸＋專用無毒藥水＋高壓水槍，完工附風量溫度數據報告。'],
        'cases' => ['施工案例｜冷淨億點清淨科技',
            '冷淨億點台中到府清洗實績：清洗前後對比照與風量溫度數據，效果看得見。'],
        'testimonials' => ['客戶好評｜冷淨億點清淨科技',
            '台中冷氣清洗客戶真實回饋。'],
    ];
    $up = $db->prepare("INSERT INTO seo_settings (client_id,page_key,meta_title,meta_desc) VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc)");
    foreach ($seo as $pk => [$t, $d]) $up->execute([$cid, $pk, $t, $d]);
    echo "✓ seo_settings ×" . count($seo) . "\n";

    // ── 4. clients：hero_stats / about_tags / owner / minisite meta ──
    // hero_stats 全部是行銷頁寫明的事實；不含假 Google 評分
    // （配合 site/index.php 信任帶調整：第 4 格不含★就不顯示「Google 評分」項）
    $heroStats = json_encode([
        ['value' => '全機',   'label' => '拆卸深層清洗'],
        ['value' => '30日',   'label' => '完工保固'],
        ['value' => '2–3位',  'label' => '技師到府施工'],
        ['value' => '3日內',  'label' => '出具完工報告'],
    ], JSON_UNESCAPED_UNICODE);
    $aboutTags = json_encode([
        '🌿 專用無毒藥水', '💦 全機拆卸高壓清洗', '📊 完工附數據報告', '🛡️ 30 日完工保固',
    ], JSON_UNESCAPED_UNICODE);
    $ownerIntro = '與房子打交道多年，深知每間房子都是男女主人的心頭肉。懂空間，更懂你在乎的乾淨——從懂屋主開始，做到讓你安心、放心。冷淨億點，用經驗與細心照顧每個家庭。';

    $db->prepare("UPDATE clients SET
            hero_stats=?, about_tags=?,
            owner_name='姚佑樺', owner_intro=?,
            minisite_meta_title='台中冷氣清洗｜冷淨億點清淨科技 無毒到府．完工附報告',
            minisite_meta_desc=?
        WHERE id=?")
       ->execute([$heroStats, $aboutTags, $ownerIntro, $seo['home'][1], $cid]);
    echo "✓ clients 欄位更新（hero_stats / about_tags / owner / minisite meta）\n";

    $db->commit();
    echo "\n✅ Migration 121 完成（has_minisite 未翻開，等子網域開通）\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "❌ " . $e->getMessage() . "\n";
    exit(1);
}
