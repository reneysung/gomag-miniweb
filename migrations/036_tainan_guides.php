<?php
// ============================================================
// Migration 036 — 台南在地攻略文 3 篇（city=tainan 專屬主題）
// ------------------------------------------------------------
// 台南獨有主題（老屋翻新/交屋驗屋/辦桌），掛 city_slug=tainan + 對應分類，
// 出現在台南對應分類各頁的「相關攻略」區。不可複製到別城 → 無 doorway。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/036_tainan_guides.php
// 冪等：以 slug upsert（保留首次 published_at）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();
$foodId = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();

$guides = [];

$guides[] = [
    'slug' => 'tainan-old-house-renovation', 'cat' => $homeId,
    'title' => '台南老屋翻新全攻略：結構、管線、漏水到預算一次看',
    'excerpt' => '台南老屋多，翻新前必懂的結構安全、管線更新、漏水壁癌與預算抓法，避免買到「翻新地雷」。',
    'meta_title' => '台南老屋翻新全攻略｜結構管線漏水與預算一次看｜店家好口碑',
    'meta_desc' => '台南老屋翻新前必懂的結構安全、管線更新、漏水壁癌、流程與預算抓法，府城老屋翻新避雷指南。',
    'body' => <<<'HTML'
<p>台南老城區（中西區、北區）老屋、老透天比例高，不少人買老屋翻新或承租老宅整理。但老屋翻新跟新成屋裝修是兩回事——結構、管線、防水沒處理好，住進去問題不斷。這篇把台南老屋翻新該注意的重點一次說清楚。</p>
<h3>翻新前先確認三件事</h3>
<ul>
<li><strong>結構安全</strong>：屋齡 30 年以上、海砂屋疑慮、樑柱裂縫、鋼筋外露要先評估，必要時找結構技師。</li>
<li><strong>管線老化</strong>：老屋電線多為舊規格、管徑不足，全屋更新水電是翻新重點，別只做表面。</li>
<li><strong>漏水與壁癌</strong>：台南潮濕＋老屋防水老化，頂樓、外牆、浴室是漏水熱區，要先抓源頭再施工。</li>
</ul>
<h3>翻新流程</h3>
<p>丈量勘估 → 設計規劃 → 拆除 → 結構／防水／管線 → 泥作木作 → 油漆 → 設備安裝 → 清潔驗收。老屋因屋況落差大，務必現場勘估後報價，別只看每坪均價。</p>
<h3>預算怎麼抓</h3>
<p>老屋翻新（連工帶料）每坪約 NT$6–12 萬，視屋況（管線、防水、結構、格局變動）落差大；只做局部（廚衛、油漆、地板）則彈性大。建議預留 10–15% 因應拆除後才發現的隱蔽問題（老屋常有）。</p>
<h3>台南老屋翻新特別要留意</h3>
<ul>
<li>中西區老屋常有<strong>狹長街屋</strong>格局，規劃天井／採光罩改善通風採光是常見手法。</li>
<li>近海（安平、四草）濕氣重，防水與防鏽要加強。</li>
<li>巷弄老屋<strong>進料與廢料清運</strong>動線受限，先確認工班經驗。</li>
</ul>
<h3>挑老屋翻新團隊</h3>
<p>找有台南老屋實績的設計／統包，看是否會先做結構與漏水評估、報價是否拆清楚（設計／監工／工程款）、合約與分階段付款是否明確。可參考本站台南室內設計與相關商家。</p>
HTML,
];

$guides[] = [
    'slug' => 'tainan-handover-inspection-guide', 'cat' => $homeId,
    'title' => '台南新成屋交屋驗屋懶人包：南科交屋潮必看',
    'excerpt' => '台南南科、永康、安平新成屋交屋潮，交屋前驗屋怎麼準備、檢查哪些、抓到問題怎麼處理，一篇搞定。',
    'meta_title' => '台南交屋驗屋懶人包｜南科永康新成屋交屋必看｜店家好口碑',
    'meta_desc' => '台南新成屋交屋前驗屋怎麼準備、檢查哪些、抓到瑕疵怎麼向建商求償，南科永康安平交屋潮必看。',
    'body' => <<<'HTML'
<p>台南近年新案多，南科（台積電擴廠帶動）、永康、安平交屋潮持續。交屋是買房最後一關，但很多人簽收後才發現瑕疵、求償無門。這篇教你台南交屋驗屋怎麼準備。</p>
<h3>交屋前先準備</h3>
<ul>
<li>對照<strong>合約與建材表</strong>：確認建材品牌、設備型號與合約一致。</li>
<li>準備<strong>驗屋工具或找專業驗屋</strong>：捲尺、水平儀、插座測試筆，或請第三方驗屋師（含紅外線、水分計）。</li>
<li>挑<strong>白天天氣好</strong>的時段驗，採光與漏水較易看出。</li>
</ul>
<h3>驗屋檢查重點</h3>
<ul>
<li><strong>漏水防水</strong>：窗框、外牆、浴室、陽台、頂樓滲漏（台南潮濕，這項最關鍵）。</li>
<li><strong>泥作磁磚</strong>：地壁磁磚空心、裂縫、洩水坡度。</li>
<li><strong>門窗水電</strong>：門窗氣密、開關順暢、插座迴路、給排水、糞管測試。</li>
<li><strong>客變確認</strong>：客變項目是否如實施作。</li>
</ul>
<h3>驗出問題怎麼辦</h3>
<p>把瑕疵拍照、列清單，<strong>交屋前</strong>向建商提出修繕（簽收後較難主張），修繕後安排<strong>複驗</strong>確認。重大瑕疵未改善前，先別簽點交同意書。</p>
<h3>台南驗屋行情</h3>
<p>找專業驗屋：標準／實品屋每戶約 NT$5,000–12,000、毛胚屋 NT$8,000–15,000+（或每坪約 NT$200–400），多含一次複驗。南科、永康交屋旺季驗屋師滿檔，建議提前 1–2 週預約。可參考本站台南驗屋頁。</p>
HTML,
];

$guides[] = [
    'slug' => 'tainan-banquet-catering-guide', 'cat' => $foodId,
    'title' => '台南辦桌總舖師宴客指南：婚宴、入厝桌菜怎麼訂',
    'excerpt' => '台南辦桌文化盛，婚宴、入厝、尾牙怎麼找總舖師、桌菜怎麼配、預算怎麼抓，府城宴客一次搞懂。',
    'meta_title' => '台南辦桌總舖師宴客指南｜婚宴入厝桌菜怎麼訂｜店家好口碑',
    'meta_desc' => '台南辦桌文化盛，總舖師外燴怎麼找、桌菜怎麼配、每桌行情與挑選重點，府城婚宴入厝尾牙宴客必看。',
    'body' => <<<'HTML'
<p>台南是辦桌文化重鎮，婚宴、入厝、滿月、尾牙、神明生，找總舖師「辦桌」仍是府城人的首選。但辦桌怎麼訂、桌菜怎麼配、預算怎麼抓，第一次辦的人常一頭霧水。這篇說清楚。</p>
<h3>辦桌 vs 餐廳宴客</h3>
<p>辦桌（外燴）由總舖師到指定場地（自宅、廟埕、活動中心）現場辦，菜色澎湃、有人情味、單價常較餐廳實惠；餐廳宴會則場地舒適、免張羅場地。看場合與人數選。</p>
<h3>怎麼訂辦桌</h3>
<ul>
<li><strong>確認日期、地點、桌數</strong>：旺日（好日子、年底）總舖師檔期搶手，建議提前 1–3 個月。</li>
<li><strong>談菜單與預算</strong>：以「桌」計，可依預算請總舖師配菜（海鮮、佛跳牆、烤乳豬等澎湃菜）。</li>
<li><strong>確認包含項目</strong>：桌椅碗盤、人力服務、場地清潔、是否含酒水飲料。</li>
</ul>
<h3>台南辦桌行情</h3>
<p>每桌（10 人）常見約 NT$6,000–15,000+，依菜色等級（海鮮、食材）與服務而定，婚宴喜慶較高。確認是否含服務人力、餐具與清潔，避免事後追加。</p>
<h3>挑總舖師</h3>
<p>看實際辦桌案例與在地口碑、菜色是否現做、食材新鮮度、服務人力是否足夠，並把菜單、桌數、含哪些寫進確認單。可參考本站台南宴會餐廳與相關商家。</p>
HTML,
];

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, 'tainan', :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            city_slug='tainan', category_id=VALUES(category_id), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
foreach ($guides as $g) {
    $stmt->execute([':slug'=>$g['slug'], ':title'=>$g['title'], ':excerpt'=>$g['excerpt'], ':body'=>$g['body'], ':cat'=>$g['cat'], ':mt'=>$g['meta_title'], ':md'=>$g['meta_desc']]);
    printf("UPSERT %-34s body %4d 字\n", $g['slug'], mb_strlen(strip_tags($g['body'])));
}
echo "完成。published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
