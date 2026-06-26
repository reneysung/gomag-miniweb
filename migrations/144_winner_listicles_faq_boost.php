<?php
// ============================================================
// Migration 144 — SEO 改造 Round 1(#1)：4 篇贏家懶人包補 FAQ + 群組內鏈
// ------------------------------------------------------------
// 對象：台北/新竹/高雄/台中 裝潢細清懶人包（GSC pos 8–10、已卡第 1 頁底）。
// 它們已 10–12KB、有客戶置入+編輯筆記+城市子分類內鏈，唯獨缺 FAQ。
// 補：① 城市專屬 FAQ（每城不同、非樣板）+ FAQPage JSON-LD；
//     ② 延伸閱讀補「存活的」群組攻略內鏈（通用粗清vs細清 / 定期清潔 / 搬家退租；
//        台中另加台中冷氣差異化版）—— 不連已 noindex 的城市重複頁。
// 不灌肥客戶卡、不加新客戶置入（要加先問 Reney）。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/144_winner_listicles_faq_boost.php
// 冪等：marker <!-- winner-boost-v1 --> 防重跑。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$commonLinks = '<a href="/guide/cleaning-detail-vs-basic">粗清 vs 細清差在哪</a>、<a href="/guide/regular-cleaning-frequency">定期清潔多久一次划算</a>、<a href="/guide/moving-cleaning-cost">搬家退租清潔費用怎麼算</a>';

$data = [
  'taipei-fine-clean-recommend' => [
    'h' => '台北裝潢細清 常見問題',
    'links' => $commonLinks . '。',
    'faq' => [
      ['台北裝潢細清一坪多少？','看屋況與坪數，裝潢後細清每坪約 NT$500–900、整戶常見 NT$15,000–40,000；老公寓翻新殘料多、電梯大樓新成屋保護膜殘膠多，價格依現場估。'],
      ['老公寓翻新後的細清，跟新成屋有差嗎？','有。台北老公寓多，翻新後常有舊漆、水泥塊、舊木作粉塵，工序比新成屋複雜；下訂前讓師傅看屋況才估得準。'],
      ['牆面、玻璃的保護膜殘膠清得掉嗎？','可以。新成屋窗框、衛浴常留保護膜殘膠，專業細清會用專用藥劑與工具處理，不傷材質。'],
      ['電梯大樓細清要先跟管委會報備嗎？','多數大樓要。施工時段、電梯保護、垃圾清運常有規定，預約時先問清楚、請業者配合大樓規範。'],
      ['小坪數套房、老公寓怎麼計價？','小坪數常以最低消計，台北寸土寸金、屋況差異大，建議到府或視訊估價，把含哪些項目寫清楚。'],
    ],
  ],
  'hsinchu-fine-clean-recommend' => [
    'h' => '新竹裝潢細清 常見問題',
    'links' => $commonLinks . '。',
    'faq' => [
      ['竹北高鐵特區、關埔新成屋交屋細清行情？','裝潢後細清每坪約 NT$500–900、整戶視坪數與粉塵量 NT$15,000–40,000；竹北、關埔新成屋多，建案交屋潮時建議提早預約。'],
      ['竹科雙薪家庭，細清＋定期清潔可以一起談嗎？','可以。多數團隊裝潢細清做完可接續定期居家清潔，竹科雙薪家庭常這樣安排，一次談好頻率與單價較省事。'],
      ['交屋前的細清要抓多久時間？','看坪數與屋況，一般整戶約一天；趕入住或建案集中交屋期，務必提早卡時間。'],
      ['透天跟電梯大樓細清差別？','竹北透天多，樓層多、含樓梯與頂樓，工時與報價比平層高；估價時把樓層與範圍講清楚。'],
      ['細清完多久可以入住？','正規細清完當天即可入住，業者會做完工驗收，確認粉塵、玻璃、廚衛都到位。'],
    ],
  ],
  'kaohsiung-fine-clean-recommend' => [
    'h' => '高雄裝潢細清 常見問題',
    'links' => $commonLinks . '。',
    'faq' => [
      ['高雄裝潢細清行情多少？','裝潢後細清每坪約 NT$500–900、整戶常見 NT$15,000–40,000；農16、美術館、亞灣等重劃區新成屋多，依屋況與坪數現場估。'],
      ['北高雄重劃區新成屋交屋細清重點？','重點在粉塵、矽利康殘膠、玻璃水痕與櫃內五金；建案集中交屋期人手吃緊，建議提早約。'],
      ['高雄近海濕氣重，細清後要注意防霉嗎？','建議。濕氣重的區域衛浴、牆角易發霉，細清後保持通風，必要時可請業者一併做除霉處理。'],
      ['透天細清樓層怎麼算？','高雄透天多，依樓層數與含不含庭院、頂樓報價，估價時說清楚屋況才準。'],
      ['裝潢細清跟一般清潔差在哪？','細清處理裝潢後的粉塵、殘膠、玻璃水痕、櫃內五金，工具藥劑與工序都不同，做完當天能入住，跟日常打掃不是同一回事。'],
    ],
  ],
  'taichung-fine-clean-recommend' => [
    'h' => '台中裝潢細清 常見問題',
    'links' => $commonLinks . '；冷氣清洗另見 <a href="/guide/taichung-aircon-clean-guide">台中冷氣清洗指南</a>。',
    'faq' => [
      ['台中裝潢細清行情多少？','裝潢後細清每坪約 NT$500–900、整戶常見 NT$15,000–40,000；七期、北屯機捷、烏日高鐵重劃區新成屋多，依屋況現場估。'],
      ['七期重劃區的吊隱式天花，細清要注意什麼？','台中新成屋吊隱式多，出風口、天花維修孔內的粉塵要拆開清，工序比壁掛費工，找有經驗的團隊先說明屋型。'],
      ['交屋細清要多久、含驗收複清嗎？','整戶一般約一天；下訂前確認是否含「驗收複清」（不滿意再補清），台中不少團隊有提供。'],
      ['烏日高鐵、太平重劃區新成屋細清重點？','重點是粉塵、矽利康殘膠、玻璃水痕與櫃內五金；建案集中交屋期建議提早預約卡時間。'],
      ['細清跟粗清差在哪？','粗清是清大型廢料與表面，細清是入住前的深層清潔（殘膠、水痕、五金、死角）。差別與費用見「粗清 vs 細清」那篇。'],
    ],
  ],
];

function faqLd(array $faqs): string {
    $ld = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
    foreach ($faqs as $f) $ld['mainEntity'][] = ['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
    return "\n<script type=\"application/ld+json\">" . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}

foreach ($data as $slug => $d) {
    $len = (int)$db->query("SELECT LENGTH(body_html) FROM guides WHERE slug='{$slug}'")->fetchColumn();
    if (!$len) { echo "⚠ 找不到 {$slug}\n"; continue; }
    $body = '';
    for ($i=0;$i<$len;$i+=4000){ $st=$db->prepare("SELECT SUBSTRING(body_html,?,4000) FROM guides WHERE slug='{$slug}'"); $st->execute([$i+1]); $body.=(string)$st->fetchColumn(); }
    if (strpos($body, 'winner-boost-v1') !== false) { echo "⏭ {$slug} 已補過\n"; continue; }

    $faqHtml = "\n<!-- winner-boost-v1 -->\n<h2>{$d['h']}</h2>\n<div class=\"gg-faq\">\n";
    foreach ($d['faq'] as $f) $faqHtml .= "  <div class=\"gg-q\">{$f[0]}</div>\n  <div class=\"gg-a\">{$f[1]}</div>\n";
    $faqHtml .= "</div>\n<p>延伸閱讀：{$d['links']}</p>\n" . faqLd($d['faq']);

    $new = $body . $faqHtml;
    $u = $db->prepare("UPDATE guides SET body_html=? WHERE slug=?");
    $u->execute([$new, $slug]);
    $r = $db->query("SELECT LENGTH(body_html) bl FROM guides WHERE slug='{$slug}'")->fetch(PDO::FETCH_ASSOC);
    echo "✅ {$slug}  +FAQ{" . count($d['faq']) . "題}  body {$len}→{$r['bl']}B\n";
}
echo "\n驗證：\n";
foreach (array_keys($data) as $s) echo "  https://www.gomag.com.tw/guide/{$s}\n";
