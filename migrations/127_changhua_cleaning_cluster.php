<?php
// ============================================================
// Migration 127 — 彰化「清潔家族」攻略 3 篇（補滿彰化清潔標籤頁 4 篇）
// ------------------------------------------------------------
// 模型（Reney 2026-06-17）：每個子服務標籤頁要 4 篇攻略；先寫專屬主題，
// 不足 4 篇撈「同家族（清潔相關）」補滿。
// 彰化清潔家族現有 1 篇（changhua-renovation-cleaning-guide＝裝修老屋清潔）。
// 本檔補 3 篇資訊型（不置入客戶、可現在寫）：
//   1) 彰化清潔公司怎麼挑（cleaning）
//   2) 彰化居家清潔指南：鐘點/定期/退租/空屋（cleaning）
//   3) 粗清 vs 細清差在哪（cleaning,reno-detail）← 細清頁也共用
// 全部彰化在地化（員林/彰化市/鹿港/和美/田中/二林/溪湖/北斗、八卦山、彰濱），
// 避樣板 doorway。行情用區間＋「以實際估價為準」。
// 《清潔公司推薦 N 家》懶人包＝要客戶名單，不在本檔。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/127_changhua_cleaning_cluster.php
// 冪等：INSERT … ON DUPLICATE KEY UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cover = 'https://www.gomag.com.tw/uploads/guides/covers/cleaning.svg';
$hub   = 'https://www.gomag.com.tw/city/changhua/home-service';
$pClean = 'https://www.gomag.com.tw/city/changhua/home-service/cleaning';
$pReno  = 'https://www.gomag.com.tw/city/changhua/home-service/reno-detail';

$articles = [];

// ── 1) 彰化清潔公司怎麼挑 ──
$b  = "<p>彰化從<strong>彰化市、員林</strong>的住宅大樓，到<strong>鹿港、和美、田中、二林、溪湖、北斗</strong>的透天厝與庄頭老宅，居家清潔需求差異很大。找彰化清潔公司前，先搞懂怎麼挑，才不會花了錢還清不乾淨、甚至東西被弄壞。</p>\n";
$b .= "<h2>彰化清潔公司提供哪些服務？</h2>\n<ul>\n";
$b .= "<li><strong>鐘點居家清潔</strong>：按小時計、定期或單次，日常維護首選。</li>\n";
$b .= "<li><strong>大掃除／年終清潔</strong>：過年前旺季，建議提早 1 個月預約。</li>\n";
$b .= "<li><strong>退租／空屋清潔</strong>：搬家前後、租屋點交。</li>\n";
$b .= "<li><strong>裝潢後細清</strong>：新成屋、中古翻修後的粉塵殘膠清理（難度高、單價另計，見<a href=\"{$pReno}\">彰化裝潢細清</a>）。</li>\n";
$b .= "<li><strong>特殊清潔</strong>：水塔、外牆、地板打蠟、石材美容、除塵蟎。</li>\n</ul>\n";
$b .= "<h2>挑彰化清潔公司的 5 個重點</h2>\n<ol>\n";
$b .= "<li><strong>報價透明</strong>：先問清楚按坪、按時還是按項目，含不含耗材、垃圾清運。最好是<strong>免費到府估價</strong>。</li>\n";
$b .= "<li><strong>服務範圍對得上</strong>：有些只做彰化市、員林一帶；二林、芳苑、大城等較遠區要先確認跑不跑、加不加車馬費。</li>\n";
$b .= "<li><strong>有沒有責任險</strong>：清潔過程萬一刮傷地板、打破東西，有保險才有保障。</li>\n";
$b .= "<li><strong>人員是自聘還是外包</strong>：自聘團隊品質較穩定，外包人力良莠不齊。</li>\n";
$b .= "<li><strong>在地口碑</strong>：看 Google 評論（分數＋評論數雙指標）、問在地朋友，避免只比低價。</li>\n</ol>\n";
$b .= "<h2>彰化清潔行情參考</h2>\n<p>鐘點清潔每小時約 NT\$400–600（多數每次最低 3 小時）；退租／空屋整戶約 NT\$5,000–15,000 視坪數；裝潢後細清每坪約 NT\$120–250。<em>實際以現場估價為準。</em></p>\n";
$b .= "<h2>常見問題</h2>\n";
$b .= "<h3>彰化清潔公司一定要簽約嗎？</h3>\n<p>不一定。單次清潔可現約現做；定期（每週/雙週/每月）簽月約單價較划算。</p>\n";
$b .= "<h3>員林、二林這些區也有到府清潔嗎？</h3>\n<p>有。彰化市、員林清潔公司最密集，二林、芳苑、大城等較遠區多數仍可到府、但建議提前預約並確認車馬費。</p>\n";
$b .= "<h3>過年大掃除什麼時候約？</h3>\n<p>11 月起就開始搶，12 月到過年前 2 週最滿，建議 11 月中前預約。</p>\n";
$b .= "<p>更多彰化居家服務見 <a href=\"{$hub}\">彰化居家服務</a>；找清潔團隊見 <a href=\"{$pClean}\">彰化清潔公司</a>。</p>\n";
$articles[] = [
    'slug' => 'changhua-cleaning-company-howto', 'svc' => 'cleaning',
    'title' => '彰化清潔公司怎麼挑？服務範圍、收費方式、挑選 5 重點',
    'excerpt' => '彰化清潔公司怎麼選？從彰化市、員林到鹿港、二林的服務範圍與車馬費、報價透明、責任險、自聘 vs 外包、在地口碑，加上鐘點/退租/細清行情，挑對不踩雷。',
    'meta_title' => '彰化清潔公司怎麼挑｜服務範圍收費挑選重點｜店家好口碑',
    'meta_desc' => '彰化清潔公司怎麼選？服務類型（鐘點/大掃除/退租/裝潢細清）、挑選 5 重點（報價透明/服務範圍/責任險/自聘/口碑）與行情，彰化市、員林、鹿港、二林住戶必看。',
    'body' => $b,
];

// ── 2) 彰化居家清潔指南 ──
$b  = "<p>彰化居家清潔不是只有「打掃」一種。鐘點、定期、退租、空屋各有適用情境與計價方式，挑對才划算。這篇把彰化常見的居家清潔類型、行情與在地眉角講清楚。</p>\n";
$b .= "<h2>彰化居家清潔 4 種常見類型</h2>\n<ul>\n";
$b .= "<li><strong>鐘點清潔</strong>：按小時、彈性，適合上班族日常維護。每小時約 NT\$400–600，多數每次最低 3 小時。</li>\n";
$b .= "<li><strong>定期清潔</strong>：每週/雙週/每月固定，簽月約單價較低，適合雙薪家庭、長輩同住。</li>\n";
$b .= "<li><strong>退租清潔</strong>：租屋點交前回復屋況，套房約 NT\$2,500–5,000、整戶 NT\$5,000–15,000。</li>\n";
$b .= "<li><strong>空屋／入厝清潔</strong>：新居入住前深層清潔，常與<a href=\"{$pReno}\">裝潢細清</a>搭配。</li>\n</ul>\n";
$b .= "<h2>彰化在地要注意</h2>\n<p>彰化<strong>透天厝比例高</strong>（鹿港、和美、田中、二林、北斗一帶尤其多），樓層多、前後院、頂樓加蓋都會影響坪數與報價，估價時要講清楚有幾層、含不含庭院與頂樓。員林、彰化市的電梯大樓則以室內坪數計較單純。</p>\n";
$b .= "<h2>怎麼挑才不踩雷</h2>\n<ul>\n";
$b .= "<li>免費到府估價、報價單列清楚含哪些（耗材、垃圾清運、搬挪家具）。</li>\n";
$b .= "<li>確認人員自聘、有責任險。</li>\n";
$b .= "<li>定期約先做一次單次試水溫，滿意再簽長約。</li>\n</ul>\n";
$b .= "<h2>常見問題</h2>\n";
$b .= "<h3>鐘點和定期差在哪？</h3>\n<p>鐘點＝單次按時；定期＝固定週期簽約、單價較低且有同一組人較熟悉你家。</p>\n";
$b .= "<h3>透天厝清潔比較貴嗎？</h3>\n<p>會。樓層、樓梯、前後院、頂樓都增加工時，彰化透天多，估價務必說明完整屋況。</p>\n";
$b .= "<h3>清潔和裝潢細清一樣嗎？</h3>\n<p>不一樣。一般清潔是日常維護；<a href=\"{$pReno}\">裝潢細清</a>專處理裝潢後粉塵殘膠，工具藥水不同、費用約 1.5–2 倍。</p>\n";
$b .= "<p>更多見 <a href=\"{$hub}\">彰化居家服務</a>、<a href=\"{$pClean}\">彰化清潔公司</a>。</p>\n";
$articles[] = [
    'slug' => 'changhua-home-cleaning-guide', 'svc' => 'cleaning',
    'title' => '彰化居家清潔指南：鐘點、定期、退租、空屋清潔行情一次看',
    'excerpt' => '彰化居家清潔怎麼選？鐘點、定期、退租、空屋 4 種類型的計價與適用情境，加上彰化透天厝多的在地報價眉角與挑選重點，員林、鹿港、和美住戶必看。',
    'meta_title' => '彰化居家清潔指南｜鐘點定期退租空屋行情｜店家好口碑',
    'meta_desc' => '彰化居家清潔 4 種類型（鐘點/定期/退租/空屋）計價與行情、彰化透天厝報價眉角、挑選不踩雷重點，彰化市、員林、鹿港、和美、田中住戶適用。',
    'body' => $b,
];

// ── 3) 粗清 vs 細清 ──
$b  = "<p>裝潢完、要入住前，常聽到「粗清」和「細清」，到底差在哪？選錯不只白花錢，還可能被當細清收費。這篇用彰化裝潢後清潔的實際情況講清楚兩者差異。</p>\n";
$b .= "<h2>粗清 vs 細清差在哪</h2>\n<ul>\n";
$b .= "<li><strong>粗清（初步清潔）</strong>：清掉裝潢大型廢料、木屑、包裝、明顯垃圾，通常由<strong>工班或統包</strong>在完工時做，目的是「能進場」，不是「能入住」。</li>\n";
$b .= "<li><strong>細清（裝潢後深層清潔）</strong>：粉塵、矽利康殘膠、油漆點、玻璃水痕、櫃內、軌道縫隙逐一處理，做完<strong>當天就能舒服入住</strong>。由專業細清團隊執行、工具藥水專門。</li>\n</ul>\n";
$b .= "<h2>裝潢後該選哪種？</h2>\n<p>想直接入住、要乾淨到位 → <strong>細清</strong>。若只是清場、之後還要再進場施工 → 粗清即可。多數屋主交屋入住要的是<strong>細清</strong>。</p>\n";
$b .= "<h2>費用怎麼抓才不被當細清收</h2>\n<p>細清每坪約 NT\$120–250（整戶視坪數與粉塵量約 NT\$15,000–40,000），明顯高於一般打掃。下訂前問清楚：①報的是粗清還是細清；②含不含玻璃、櫃內、矽利康殘膠；③完工驗收標準。避免「用粗清的料、收細清的錢」。<em>實際以現場估價為準。</em></p>\n";
$b .= "<h2>彰化在地提醒</h2>\n<p>彰化新建案交屋潮（員林、彰化市重劃區）與透天自地自建多，細清需求集中在交屋前。旺季（建案集中交屋）要提前排，並確認師傅有「拆裝/損壞保固」。</p>\n";
$b .= "<h2>常見問題</h2>\n";
$b .= "<h3>粗清可以自己做嗎？</h3>\n<p>大型垃圾可以；但粉塵與殘膠的細清建議交專業，自己做容易刮傷與清不乾淨。</p>\n";
$b .= "<h3>細清要做多久？</h3>\n<p>整戶視坪數約半天到一天，玻璃、櫃體多會更久。</p>\n";
$b .= "<p>找彰化細清團隊見 <a href=\"{$pReno}\">彰化裝潢細清</a>；日常清潔見 <a href=\"{$pClean}\">彰化清潔公司</a>。</p>\n";
$articles[] = [
    'slug' => 'changhua-rough-vs-detail-cleaning', 'svc' => 'cleaning,reno-detail',
    'title' => '粗清 vs 細清差在哪？彰化裝潢後清潔該選哪種、費用怎麼抓',
    'excerpt' => '裝潢後的粗清和細清差在哪？粗清清廢料、細清能入住，費用怎麼抓才不被當細清收，加上彰化交屋潮在地提醒，裝潢後入住前必看。',
    'meta_title' => '粗清vs細清差在哪｜彰化裝潢後清潔怎麼選費用｜店家好口碑',
    'meta_desc' => '粗清（清廢料）vs 細清（深層、可入住）差在哪？裝潢後該選哪種、費用每坪 NT$120–250 怎麼抓不被當細清收，彰化員林、重劃區交屋潮在地提醒。',
    'body' => $b,
];

// 寫入
$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'changhua', 2, ?, ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        city_slug='changhua', category_id=2, service_slug=VALUES(service_slug), cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");
foreach ($articles as $a) {
    $gs->execute([$a['slug'], $a['title'], $a['excerpt'], $a['body'], $a['svc'], $cover, $a['meta_title'], $a['meta_desc']]);
    $row = $db->query("SELECT id, LENGTH(body_html) l FROM guides WHERE slug=" . $db->quote($a['slug']))->fetch(PDO::FETCH_ASSOC);
    printf("  ✅ #%s %s (%dB, svc=%s)\n", $row['id'], $a['slug'], $row['l'], $a['svc']);
}

echo "\n== 彰化清潔家族攻略現況 ==\n";
foreach ($db->query("SELECT title, service_slug FROM guides WHERE city_slug='changhua' AND category_id=2 AND status='published' ORDER BY published_at DESC") as $r) {
    printf("  ・%s (svc=%s)\n", $r['title'], $r['service_slug']);
}
echo "\n完成。\n";
