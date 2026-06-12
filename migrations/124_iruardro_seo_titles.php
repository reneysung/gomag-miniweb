<?php
// ============================================================
// Migration 124 — 冷淨億點（#251）全站 title 關鍵字佈局
// ------------------------------------------------------------
// 主力關鍵字：台中洗冷氣 / 台中冷氣清潔 / 台中冷氣清洗（Reney 指定）
// 佈局原則：每頁 title 唯一、自然帶入、三組字分散不堆砌：
//   home=台中洗冷氣+冷氣清洗、services=台中冷氣清洗、cases=台中冷氣清潔、
//   cases_taichung=台中洗冷氣案例、cases_changhua=彰化洗冷氣案例、
//   testimonials=台中冷氣清洗評價；案例標題加地區前綴（case_detail title 自然含地區）
// 搭配 site/cases.php 的 cases_{region} SEO 覆寫 hook（同批部署）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/124_iruardro_seo_titles.php
// 冪等：UPSERT / UPDATE
// ============================================================
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cid = (int)$db->query("SELECT id FROM clients WHERE subdomain='iruardro' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ iruardro not found\n"; exit(1); }

$db->beginTransaction();
try {
    // ── 1. seo_settings ──────────────────────────────
    $seo = [
        'home' => ['台中洗冷氣推薦｜冷淨億點清淨科技：無毒冷氣清洗・完工附數據報告',
            '台中洗冷氣找冷淨億點清淨科技：家用空調、商用空調、洗衣機到府清洗，全機拆卸＋專用無毒藥水＋高壓水槍，完工附前後對比照與風量溫度報告，30 日保固。服務台中全區，預約 0988-154194。'],
        'services' => ['台中冷氣清洗服務項目｜家用空調・商用空調・洗衣機｜冷淨億點',
            '台中冷氣清洗服務：家用空調、洗衣機、商用空調到府清洗，另有年約包套與社區團洗優惠。全機拆卸＋專用無毒藥水，完工附風量溫度數據報告。'],
        'cases' => ['台中冷氣清潔施工案例相簿｜Before／After 對比實錄｜冷淨億點',
            '台中冷氣清潔實際案例：吊隱式、窗型、分離式、四方吹與洗衣機拆洗，每案完整 Before／After 相簿紀錄，效果看得見。'],
        'cases_taichung' => ['台中洗冷氣施工案例｜西區・梧棲到府清洗實錄｜冷淨億點',
            '台中洗冷氣實際案例相簿：西區透天窗型冷氣、梧棲連鎖藥局四方吹、直立式洗衣機拆洗，完整施工紀錄與前後對比。'],
        'cases_changhua' => ['彰化洗冷氣施工案例｜伸港到府清洗實錄｜冷淨億點',
            '彰化洗冷氣實際案例相簿：伸港吊隱式冷氣深層清洗、透天分離式冷氣全機拆洗，完整施工紀錄與前後對比。'],
        'testimonials' => ['台中冷氣清洗評價｜客戶好評與真實回饋｜冷淨億點清淨科技',
            '台中冷氣清洗客戶評價：冷淨億點到府清洗真實回饋陸續整理中，無毒藥水、數據報告、30 日保固。'],
    ];
    $up = $db->prepare("INSERT INTO seo_settings (client_id,page_key,meta_title,meta_desc) VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc)");
    foreach ($seo as $pk => [$t, $d]) $up->execute([$cid, $pk, $t, $d]);
    echo "✓ seo_settings ×" . count($seo) . "\n";

    // ── 2. clients minisite meta（home fallback / og）──
    $db->prepare("UPDATE clients SET minisite_meta_title=?, minisite_meta_desc=? WHERE id=?")
       ->execute([$seo['home'][0], $seo['home'][1], $cid]);
    echo "✓ clients.minisite_meta_title/desc\n";

    // ── 3. 案例標題加地區前綴（case_detail title 自然帶地區）──
    $caseTitles = [
        'ceiling-shengang' => '彰化伸港・吊隱式冷氣深層清洗',
        'washer-vertical'  => '台中・直立式洗衣機內槽拆洗',
        'cassette-wuqi'    => '台中梧棲・連鎖藥局四方吹冷氣清洗',
        'window-west'      => '台中西區・透天窗型冷氣全機拆洗（三、四樓臥室）',
        'split-shengang'   => '彰化伸港・透天分離式冷氣全機拆洗（客廳＋辦公室＋臥室）',
    ];
    $uc = $db->prepare("UPDATE cases SET title=? WHERE client_id=? AND slug=?");
    foreach ($caseTitles as $cslug => $t) $uc->execute([$t, $cid, $cslug]);
    echo "✓ cases 標題地區前綴 ×" . count($caseTitles) . "\n";

    $db->commit();
    echo "\n✅ Migration 124 完成\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "❌ " . $e->getMessage() . "\n";
    exit(1);
}
