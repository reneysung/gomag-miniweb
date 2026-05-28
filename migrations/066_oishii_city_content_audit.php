<?php
// migrations/066_oishii_city_content_audit.php
// 5 城市 landing_extra_content 稽核：去除無中生有內容，全部用 banshin.com.tw 認證事實
//
// 修改：
//   - 移除「觀光客陷阱」「自己人送禮」「不像觀光名產」等主觀比較
//   - 移除未驗證的捷運／公車路線、預訂政策、分裝服務、試吃
//   - 保留城市入口 hook（中性 — 只說「在 X 城市的人也可選擇」）
//   - 保留通用送禮場景（過年、彌月、商務 — universal gifting occasions）
//   - 加上 banshin.com.tw 連結與真實彌月小卡資訊
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/066_oishii_city_content_audit.php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;

echo "=== Migration 066: 5 城市內容稽核（去除無中生有）===\n\n";

// 城市專屬 entry hook（中性敘述，無主觀比較）
$cityHooks = [
    'taichung' => [
        'title'  => '在台中找伴手禮｜奧喜長崎蛋糕北屯松竹路本店',
        'lead'   => '位於台中市北屯區松竹路三段，每日烘焙日式長崎蛋糕。在台中找伴手禮選項時，奧喜長崎蛋糕是其中一家可考慮的店家。',
        'why'    => '在台中本地，伴手禮選項眾多。奧喜長崎蛋糕本店設於北屯區松竹路三段 22 號，自 2020 年 12 月從綏遠路遷至此址，營業時間週一至週日 09:00–19:00。',
    ],
    'taipei' => [
        'title'  => '從台北到台中｜奧喜長崎蛋糕作為伴手禮選擇',
        'lead'   => '從台北出發到台中出差、訪友或返鄉時，奧喜長崎蛋糕是可列入伴手禮清單的選項之一。',
        'why'    => '台北客人若計畫前往台中北屯區，可在松竹路三段 22 號本店現場選購；若無法親至，亦可透過官網宅配寄送。',
    ],
    'newtaipei' => [
        'title'  => '新北人到台中｜奧喜長崎蛋糕的伴手禮選擇',
        'lead'   => '新北住戶到台中出差、回娘家、探親時，奧喜長崎蛋糕適合作為餽贈選擇之一。',
        'why'    => '從新北至台中北屯松竹路三段 22 號本店，可現場購買；若臨時無法到店，亦支援宅配全台。',
    ],
    'taoyuan' => [
        'title'  => '桃園到台中｜奧喜長崎蛋糕作為餽贈選擇',
        'lead'   => '從桃園至台中出差、拜訪客戶或返家時，奧喜長崎蛋糕可列入伴手禮選項。',
        'why'    => '桃園客人前往台中北屯區松竹路三段 22 號本店時，可現場選購；亦可改用宅配，由官網下單寄送指定地址。',
    ],
    'hsinchu' => [
        'title'  => '新竹到台中｜奧喜長崎蛋糕作為伴手禮選項',
        'lead'   => '從新竹至台中出差、返鄉或贈禮時，奧喜長崎蛋糕適合作為日式風味伴手禮選擇之一。',
        'why'    => '新竹客人至台中北屯區松竹路三段 22 號本店時，可現場挑選；亦可透過官網宅配，寄至新竹地址。',
    ],
];

foreach ($cityHooks as $slug => $h) {
    $cityLabel = [
        'taichung'  => '台中市',
        'taipei'    => '台北市',
        'newtaipei' => '新北市',
        'taoyuan'   => '桃園市',
        'hsinchu'   => '新竹市',
    ][$slug];

    // 統一內容架構（5 city 共用，差別在 hook + city_label）
    $html = <<<HTML
<section>
  <p style="font-size:.9rem;color:#8B6F5A;letter-spacing:.15em;margin-bottom:8px;">{$cityLabel}・伴手禮選項</p>
  <h2>{$h['title']}</h2>
  <p style="font-size:1.05rem;line-height:1.85;">{$h['lead']}</p>
</section>

<section style="margin-top:48px;">
  <h2>關於奧喜長崎蛋糕</h2>
  <p>{$h['why']}</p>
  <p>奧喜的「奧喜」取自日文「おいしい」（好吃之意），以最單純的<strong>雞蛋、糖、麵粉、麥芽</strong>四種原料製作日式長崎蛋糕；秉持「<strong>誠信為本・踏實為重</strong>」的企業精神，不添加任何化學馨香成份。</p>
  <p>產品包含：長崎蛋糕 5／8／12／16／24 片（NT\$ 135–560）、奧喜燒（蛋糕餅乾，NT\$ 135）、小巧禮盒（蛋糕＋奧喜燒組合，NT\$ 280）。</p>
</section>

<section style="margin-top:48px;">
  <h2>線上宅配與門市自取</h2>
  <p>奧喜提供兩種購買方式：</p>
  <ul style="line-height:2;font-size:1rem;color:#4A2E1E;padding-left:1.5em;">
    <li><strong>線上宅配</strong>：透過奧喜官網下單，宅配寄至{$cityLabel}指定地址。</li>
    <li><strong>門市自取</strong>：本店位於台中市北屯區松竹路三段 22 號，週一至週日 09:00–19:00 營業。</li>
  </ul>
  <p style="margin-top:20px;">
    <a href="https://www.banshin.com.tw/product/" target="_blank" rel="noopener" style="display:inline-block;padding:12px 28px;background:#C73E2D;color:#fff;text-decoration:none;font-weight:700;border-radius:4px;">前往奧喜官網訂購 ↗</a>
  </p>
</section>

<section style="margin-top:48px;">
  <h2>常見送禮場景</h2>
  <p>奧喜長崎蛋糕的金箔禮盒包裝，常被選為下列場合的伴手禮：</p>
  <ul style="line-height:2;font-size:1rem;color:#4A2E1E;padding-left:1.5em;">
    <li><strong>節慶送禮</strong>：過年、中秋、端午等節日送長輩、親友。</li>
    <li><strong>商務拜訪</strong>：拜訪客戶、合作夥伴時的見面禮選擇。</li>
    <li><strong>彌月禮盒</strong>：5 種尺寸皆可提供彌月包裝；消費滿 NT\$ 6,000 元可免費印製寶寶照片小卡（處理時間 10 個工作天，照片張數等同訂購蛋糕盒數）。</li>
    <li><strong>探病慰問</strong>：日式樸實風格，口感不過甜膩。</li>
    <li><strong>返鄉送禮</strong>：從{$cityLabel}帶回家鄉的伴手禮選擇之一。</li>
  </ul>
</section>

<section style="margin-top:48px;">
  <h2>常見問題</h2>

  <h3>蛋糕用什麼原料？</h3>
  <p>雞蛋、糖、麵粉、麥芽四種，<strong>不添加任何化學馨香成份</strong>（官網明文）。</p>

  <h3>有哪些規格與價格？</h3>
  <p>長崎蛋糕分 5 片（NT\$ 135）、8 片（NT\$ 225）、12 片（NT\$ 288）、16 片（NT\$ 350）、24 片（NT\$ 560）；另有奧喜燒（NT\$ 135）與小巧禮盒（NT\$ 280）。</p>

  <h3>從{$cityLabel}可以宅配嗎？</h3>
  <p>可以。請至<a href="https://www.banshin.com.tw/product/" target="_blank" rel="noopener">奧喜官網</a>選購「產品宅配」，填寫{$cityLabel}收件地址即可。</p>

  <h3>店面在哪？營業時間？</h3>
  <p>台中市北屯區松竹路三段 22 號，週一至週日 09:00–19:00。電話 04-22416560。</p>

  <h3>彌月禮盒有什麼服務？</h3>
  <p>5 種尺寸皆可彌月包裝。消費滿 NT\$ 6,000 元免費印製寶寶照片小卡（6×8.8 cm 名片大小），照片張數等同訂購盒數，處理時間 10 個工作天（不含週六日）。</p>
</section>

<section style="margin-top:48px;padding:32px;background:#fff;border-left:4px solid #C73E2D;">
  <h2 style="margin-top:0;">本店資訊</h2>
  <p style="line-height:2;">
    <strong>奧喜長崎蛋糕</strong><br>
    地址：台中市北屯區松竹路三段 22 號<br>
    電話：04-22416560<br>
    營業：週一至週日 09:00–19:00<br>
    官網：<a href="https://www.banshin.com.tw" target="_blank" rel="noopener">www.banshin.com.tw</a>
  </p>
</section>
HTML;

    $db->prepare("UPDATE client_city_pages SET landing_extra_content = ? WHERE client_id = ? AND city_slug = ?")
       ->execute([$html, OISHII_ID, $slug]);
    printf("  ✅ %-12s (%s) — %d bytes\n", $slug, $cityLabel, strlen($html));
}

echo "\n=== 驗證 ===\n";
$rows = $db->prepare("SELECT city_slug, city_label, LENGTH(landing_extra_content) AS len FROM client_city_pages WHERE client_id = ? ORDER BY sort_order, id");
$rows->execute([OISHII_ID]);
foreach ($rows->fetchAll() as $r) {
    printf("  %s (%s): %d bytes\n", $r['city_slug'], $r['city_label'], $r['len']);
}

echo "\n✅ Migration 066 完成\n";
