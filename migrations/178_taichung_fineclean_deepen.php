<?php
// ============================================================
// Migration 178 — 台中裝潢細清深度化（補 177 漏掉的一城）
// ------------------------------------------------------------
// 177 的錨點是壓縮版 HTML；台中該篇有換行，故單獨處理（內容規格與其他 4 城一致）。
// 誠實：牧樂居家清潔 Places 查詢回「媽咪樂」屬不同商家 → 不掛評分。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/178_taichung_fineclean_deepen.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$slug='taichung-fine-clean-recommend';

$body = $db->query("SELECT body_html FROM guides WHERE slug='{$slug}'")->fetchColumn();
if(!$body){ echo "❌ 找不到\n"; exit(1); }
if(strpos($body,'class="cmp"')!==false){ echo "⏭ 已有比較表\n"; exit(0); }

$CSS = '<style>'
.'.lrb-g .cmp{margin:26px 0 8px;overflow-x:auto;}'
.'.lrb-g .cmp table{width:100%;border-collapse:collapse;font-family:\'Noto Sans TC\',sans-serif;font-size:.92rem;}'
.'.lrb-g .cmp th{background:#2b2724;color:#fff;font-weight:700;padding:10px 12px;text-align:left;white-space:nowrap;}'
.'.lrb-g .cmp td{border-bottom:1px solid var(--line);padding:10px 12px;color:var(--soft);vertical-align:top;}'
.'.lrb-g .cmp tr:nth-child(even) td{background:#fdfbf9;}'
.'.lrb-g .cmp td:first-child{font-weight:700;color:var(--ink);white-space:nowrap;}'
.'.lrb-g .cmp .rt{color:#b14a2f;font-weight:700;white-space:nowrap;}'
.'.lrb-g .cmp .na{color:var(--mut);}'
.'.lrb-g .cmp-cap{font-family:\'Noto Sans TC\',sans-serif;font-size:.82rem;color:var(--mut);margin:8px 0 0;line-height:1.7;}'
.'.lrb-g .local{font-family:\'Noto Sans TC\',sans-serif;background:#fff7f4;border:1px solid #f3d9cf;border-radius:14px;padding:18px 20px;margin:26px 0 8px;font-size:.95rem;line-height:1.9;color:var(--soft);}'
.'.lrb-g .local b{color:var(--ink);}'
.'</style>';

$rows=[
 ['亞雷清潔','北屯・中彰','裝潢細清・居家清潔','5.0（2）'],
 ['清潔大夫','西屯','裝潢細清專門','5.0（88）'],
 ['牧樂居家清潔','台中','居家清潔・定期',null],
 ['久承清潔環保','南區','清潔環保・廢棄物','3.9（7）'],
 ['釋廣清潔','西屯','裝潢細清・居家','4.9（83）'],
 ['潔管家企業社','北屯','退租裝潢細清・專項清洗','4.8（187）'],
 ['清潔特攻隊','潭子','裝潢細清・新建案粗細清','5.0（43）'],
];
$tr=''; $rated=0;
foreach($rows as $r){
  $rt = $r[3]!==null ? '<span class="rt">★ '.$r[3].'</span>' : '<span class="na">—</span>';
  if($r[3]!==null)$rated++;
  $tr.='<tr><td>'.$r[0].'</td><td>'.$r[1].'</td><td>'.$r[2].'</td><td>'.$rt.'</td></tr>';
}
$table='<div class="cmp"><table><thead><tr><th>店家</th><th>區域</th><th>專精</th><th>Google 評價</th></tr></thead><tbody>'.$tr.'</tbody></table></div>'
 .'<p class="cmp-cap">評價為 Google 商家公開資料（擷取自 '.date('Y/m/d').'），僅供參考、會隨時間變動；標「—」者為無法確認對應商家，故不列評分。</p>';
$local='<div class="local"><b>台中在地觀察</b>：台中的細清需求分兩塊——七期、北屯、水湳等重劃區的新成屋交屋細清，以及市區與屯區<b>透天厝</b>的整棟清潔。透天的坪數與樓層數會讓報價結構跟公寓完全不同（爬樓、外窗、頂樓），估價時要講清楚幾層、要不要清外窗與頂樓。新建案交屋潮集中時，熱門團隊檔期會排得很滿。</div>';

$anchor = "</ol>\n</div>\n\n<div class=\"lrb-card\">";
if(substr_count($body,$anchor)!==1){ echo "❌ 錨點命中 ".substr_count($body,$anchor)." 次\n"; exit(1); }
$body = str_replace($anchor, "</ol>\n</div>\n".$table.$local."\n<div class=\"lrb-card\">", $body);
$body = preg_replace('/<\/style>/','</style>'.$CSS,$body,1);

$db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$body,$slug]);
$n=$db->query("SELECT LENGTH(body_html) FROM guides WHERE slug='{$slug}'")->fetchColumn();
echo "✅ {$slug} 比較表 7 列(其中 {$rated} 家有真實評分) + 在地觀察  body={$n}B\n";
