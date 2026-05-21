<?php
// ============================================================
// Migration 038 — 婚宴餐廳（banquet）3 城交叉頁 + 關鍵字改名
// ------------------------------------------------------------
// banquet 關鍵字名「宴會餐廳」→「婚宴餐廳」（聚焦婚宴搜尋）。
// 開 台中(0)/台南(4)/高雄(0) 婚宴餐廳頁，各城場地熱區差異化。
//
// 跑法：HTTP_HOST=...hostingersite.com php migrations/038_wedding_banquet_3cities.php
// 冪等：UPDATE 名稱；geo 以 (city,cat,service) upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$fid = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();

// 關鍵字改名
$db->prepare("UPDATE service_keywords SET name='婚宴餐廳' WHERE category_id=? AND slug='banquet'")->execute([$fid]);
echo "KW：banquet name → 婚宴餐廳\n";

$types = <<<'HTML'
<ul>
<li><strong>宴會餐廳／合菜</strong>：彈性桌數、菜色豐富，每桌（10 人）約 NT$8,000–15,000。</li>
<li><strong>婚宴會館／宴會廳</strong>：有舞台、音響、新娘房與婚禮流程支援，每桌約 NT$12,000–25,000。</li>
<li><strong>飯店婚宴</strong>：場地高檔、服務完整，每桌約 NT$15,000–35,000+。</li>
<li><strong>辦桌外燴（總舖師）</strong>：到指定場地現辦、菜色澎湃，每桌約 NT$8,000–18,000。</li>
</ul>
HTML;

function faqs(string $city, string $districts, string $special): array {
    return [
        ['q' => "{$city}婚宴一桌多少錢？", 'a' => '合菜／宴會餐廳每桌（10 人）約 NT$8,000–15,000、婚宴會館 NT$12,000–25,000、飯店婚宴 NT$15,000–35,000+、辦桌 NT$8,000–18,000。'],
        ['q' => '婚宴會館和餐廳辦婚宴差在哪？', 'a' => '婚宴會館有舞台、音響、新娘房、婚禮流程支援；一般餐廳／合菜彈性、平價，但婚禮設備較少。'],
        ['q' => '辦桌還是餐廳婚宴好？', 'a' => '辦桌（外燴）有人情味、菜色澎湃、可在自宅／活動中心辦；餐廳／會館場地舒適免張羅。看人數與場合。'],
        ['q' => '婚宴要提前多久訂？', 'a' => '好日子（農民曆好日、週末）檔期搶手，建議提前 3–6 個月訂，熱門會館更早。'],
        ['q' => "{$city}哪些區婚宴場地多？", 'a' => $districts],
        ['q' => '一桌通常幾人？', 'a' => '婚宴多以 10 人／桌計，可依賓客數抓桌數，並預留 1–2 桌彈性。'],
        ['q' => '訂婚宴要確認哪些？', 'a' => '桌數低消、菜色（可否試菜）、是否含服務人力／場地佈置／音響、加桌規則、訂金與退訂條款。'],
        ['q' => "{$city}婚宴有什麼在地特色？", 'a' => $special],
        ['q' => '入厝、尾牙、壽宴也可以訂嗎？', 'a' => '可以，宴會餐廳與會館多承接各式宴客，依人數與預算配菜。'],
        ['q' => '怎麼挑婚宴餐廳？', 'a' => '看實際宴會案例與在地口碑、試菜、場地與服務、檔期與合約，避免只看價格。'],
    ];
}

$pages = [
    'tainan' => [
        'city' => '台南',
        'lead' => '台南婚宴宴客文化興盛，從府城傳統辦桌、海鮮宴會館到婚宴會館、飯店婚宴都有。',
        'area' => '區域上，中西區、東區、永康、安平宴會餐廳多；大型婚宴會館與飯店集中東區、永康，傳統辦桌則全區可到府。',
        'districts' => '中西區、東區、永康、安平宴會餐廳多；大型會館與飯店集中東區、永康。',
        'special' => '台南近海，海鮮宴會館主打新鮮海產（龍蝦、螃蟹、魚），加上府城辦桌文化，是在地婚宴特色。',
        'closing' => '下方為店家好口碑收錄的台南在地婚宴宴會餐廳。',
    ],
    'taichung' => [
        'city' => '台中',
        'lead' => '台中婚宴宴客選擇多，從市區宴會餐廳、大型婚宴會館到飯店婚宴都有，七期、北屯一帶高端宴會廳尤其受歡迎。',
        'area' => '區域上，西屯（七期）、北屯、南屯、西區宴會場地多；大型婚宴會館與飯店集中七期、北屯，停車與交通方便是台中選場重點。',
        'districts' => '西屯（七期）、北屯、南屯、西區宴會場地多；大型會館與飯店集中七期、北屯。',
        'special' => '台中宴會廳風格多元、規模大，七期一帶高端婚宴會館選擇豐富，停車與交通便利是優勢。',
        'closing' => '歡迎在地優質婚宴宴會餐廳上架，讓更多台中新人找到。',
    ],
    'kaohsiung' => [
        'city' => '高雄',
        'lead' => '高雄婚宴宴客場地多，從宴會餐廳、婚宴會館到飯店婚宴都有，巨蛋、漢神商圈與美術館特區一帶宴會場地集中。',
        'area' => '區域上，三民、左營、苓雅、鼓山宴會場地多；大型會館與飯店集中巨蛋、漢神商圈、美術館特區，交通與停車方便。',
        'districts' => '三民、左營、苓雅、鼓山多；大型會館與飯店集中巨蛋、漢神商圈、美術館特區。',
        'special' => '高雄近海，海鮮婚宴是特色；巨蛋、漢神商圈大型宴會廳規模大、適合大型婚宴。',
        'closing' => '歡迎在地優質婚宴宴會餐廳上架，讓更多高雄新人找到。',
    ],
];

$up = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active)
    VALUES (?, ?, 'banquet', '婚宴餐廳', ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE service_name='婚宴餐廳', intro_html=VALUES(intro_html), faqs=VALUES(faqs), meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), is_active=1");

foreach ($pages as $slug => $p) {
    $intro = "<p>{$p['lead']}先想好場合與賓客人數——是婚宴喜宴、入厝、尾牙還是壽宴謝師宴，再挑場地與菜色。</p>\n"
           . "<p>{$p['city']}常見的婚宴宴客類型大致分四類：</p>\n{$types}\n"
           . "<p>{$p['area']}</p>\n"
           . "<p>挑{$p['city']}婚宴餐廳，建議看三件事：①<strong>場地容納桌數、停車與交通</strong>；②<strong>菜色試菜、是否含服務人力與場地佈置</strong>；③<strong>檔期</strong>（好日子搶手，建議提前 3–6 個月訂）。{$p['closing']}</p>";
    $mt = "{$p['city']}婚宴餐廳推薦｜婚宴會館・喜宴・宴會餐廳";
    $md = "{$p['city']}婚宴餐廳怎麼挑？宴會餐廳、婚宴會館、飯店婚宴、辦桌的每桌行情、場地與訂席重點，加上店家好口碑收錄的{$p['city']}在地婚宴宴會餐廳。";
    $up->execute([$slug, $fid, $intro, json_encode(faqs($p['city'], $p['districts'], $p['special']), JSON_UNESCAPED_UNICODE), $mt, $md]);
    printf("PAGE：%s婚宴餐廳 intro %d 字\n", $p['city'], mb_strlen(strip_tags($intro)));
}
echo "完成。\n";
