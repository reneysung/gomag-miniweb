<?php
// ============================================================
// Migration 177 — 五城裝潢細清深度化（台北/台中/新竹/桃園/新北）
// ------------------------------------------------------------
// GSC 診斷：這 5 篇卡在 9–11 名（第一頁底），缺台南/高雄致勝的「真實 Google 評價 + 比較表」。
// ⚠️ 反樣板稅設計：不複製同一套知識段（那會讓兄弟頁更像）。改為：
//    ① 各城「自己店家的真實 Google 評分」比較表（資料天生不同）
//    ② 各城獨有的在地觀察（竹科交屋潮/北市老公寓無電梯/中市重劃區透天…）
//    ③ 知識內容連到既有通用文，不在 5 城重複貼
// ⚠️ 誠實：Places API 文字搜尋配對錯誤者(清吉→浣熊、牧樂→媽咪樂、紅龍→鋐隆、永創→禾秝、
//    WeClean→E享家)與查無者(佳世潔/佐沐/傑淨) 一律不掛評分，顯示「—」。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/177_five_cities_fineclean_deepen.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

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

// 每城：[店名, 區域, 專精, 評分(null=未驗證)]
$data = [
 'hsinchu' => [
   'rows'=>[
     ['清潔醫生','北區/竹北','裝潢後細清・新屋入住','4.8（401）'],
     ['傑淨清潔公司','新豐','交屋細清・死角清潔',null],
     ['愛幫忙清潔公司','竹北','新成屋細清・去殘膠','4.6（75）'],
     ['立捷環保清潔','竹北/湖口','裝潢交屋細清','4.8（63）'],
     ['愛家家事服務','竹北','裝潢細清・即刻入住','4.8（195）'],
     ['均禾 House Clean','新竹縣市・苗栗','裝潢細清・洗地機','4.9（151）'],
     ['管家太太','大新竹・竹北','裝潢清潔・空屋・專項','4.7（146）'],
   ],
   'local'=>'<b>新竹在地觀察</b>：竹科帶動竹北高鐵特區、關埔重劃區的新成屋交屋潮，細清需求高度集中在「交屋前後」這段時間，熱門團隊的假日與交屋旺季檔期常常滿檔，建議一確定交屋日就先預約。另外竹科雙薪家庭多，不少業者同時提供「細清＋後續定期清潔」的組合，若你本來就打算長期請人打掃，可以一次談，通常比分開找划算。',
 ],
 'taipei' => [
   'rows'=>[
     ['御用清潔公司','萬華・大台北','裝潢細清・空屋全屋','5.0（223）'],
     ['文潔清潔公司','內湖・大台北','裝潢場後細清・退租','4.9（54）'],
     ['清吉清潔公司','大台北','裝潢細清・居家清潔',null],
     ['家必潔專業清潔','中正・大台北','裝潢細清・家電清洗','4.8（440）'],
     ['家適美居家清潔','南港・大台北','居家清潔・裝潢細清','4.9（526）'],
     ['愛潔王居家清潔','中正・大台北','居家與辦公室清潔','4.6（120）'],
     ['信義居家清潔','信義・大台北','居家清潔・細清','4.3（138）'],
   ],
   'local'=>'<b>台北在地觀察</b>：台北老公寓比例高，<b>有沒有電梯直接影響報價</b>——無電梯的樓層搬運費、廢棄物搬下樓的工資常另計，估價時一定要先講清楚樓層與有無電梯。另外市區坪數普遍小，單坪單價會比外縣市高，但總價未必；垃圾與裝潢廢棄物的清運在台北也有較嚴格的規定，記得問清楚清運是否包含、怎麼計費。',
 ],
 'taichung' => [
   'rows'=>[
     ['亞雷清潔','北屯・中彰','裝潢細清・居家清潔','5.0（2）'],
     ['清潔大夫','西屯','裝潢細清專門','5.0（88）'],
     ['牧樂居家清潔','台中','居家清潔・定期',null],
     ['久承清潔環保','南區','清潔環保・廢棄物','3.9（7）'],
     ['釋廣清潔','西屯','裝潢細清・居家','4.9（83）'],
     ['潔管家企業社','北屯','退租裝潢細清・專項清洗','4.8（187）'],
     ['清潔特攻隊','潭子','裝潢細清・新建案粗細清','5.0（43）'],
   ],
   'local'=>'<b>台中在地觀察</b>：台中的細清需求分兩塊——七期、北屯、水湳等重劃區的新成屋交屋細清，以及市區與屯區<b>透天厝</b>的整棟清潔。透天的坪數與樓層數會讓報價結構跟公寓完全不同（爬樓、外窗、頂樓），估價時要講清楚幾層、要不要清外窗與頂樓。新建案交屋潮集中時，熱門團隊檔期會排得很滿。',
 ],
 'taoyuan' => [
   'rows'=>[
     ['沐悅專業細緻清潔','桃園區','裝潢細緻清潔','5.0（146）'],
     ['紅龍清潔公司','桃園','裝潢細清・居家',null],
     ['潔暘國際','八德','清潔工程','3.3（4）'],
     ['廖媽媽清潔企業社','楊梅','居家清潔團隊','4.2（37）'],
     ['鑫億專業清潔公司','桃園區','專業清潔・細清','4.9（186）'],
     ['佳世潔清潔公司','桃園','清潔服務',null],
     ['WeClean 威清','桃園','居家清潔',null],
   ],
   'local'=>'<b>桃園在地觀察</b>：青埔、中路、經國等重劃區這幾年新成屋交屋量大，細清需求高度集中在交屋潮；中壢、平鎮一帶則是老屋翻新與租屋退租清潔較多。桃園幅員大、南北距離長，<b>跨區可能會收車馬費</b>，找離案場近的團隊通常比較划算，估價時直接問「到我這個區有沒有額外收費」。',
 ],
 'newtaipei' => [
   'rows'=>[
     ['永創清潔公司','新北','裝潢細清・清潔工程',null],
     ['立寶清潔工程','板橋','裝潢細清・退租清運','4.9（29）'],
     ['佐沐清潔管理','新北','清潔管理',null],
     ['家事達家事服務','三重','裝潢清潔・空屋・廠房','4.8（123）'],
     ['鷹翰裝潢細清','中和','裝潢細清專門','4.8（49）'],
     ['愛潔淨居家清潔','大台北','專業居家清潔','4.6（498）'],
     ['康庭家事管理','板橋','家事管理・居家清潔','4.6（245）'],
   ],
   'local'=>'<b>新北在地觀察</b>：新北同時存在兩種很不一樣的需求——三重、蘆洲、板橋一帶的<b>老屋翻新後細清</b>（粉塵重、常伴隨管線與泥作工程），以及林口、新莊副都心、三重重劃區的<b>新成屋交屋細清</b>。前者要找處理得了重度粉塵與殘料的團隊，後者重點在收邊細節與驗收。找的時候先講清楚你是哪一種，報價與工時差很多。',
 ],
];

$ok=0; $fail=0;
foreach($data as $city=>$cfg){
  $slug = $city.'-fine-clean-recommend';
  $body = $db->query("SELECT body_html FROM guides WHERE slug='{$slug}'")->fetchColumn();
  if(!$body){ echo "❌ {$slug} 找不到\n"; $fail++; continue; }
  if(strpos($body,'class="cmp"')!==false){ echo "⏭ {$slug} 已有比較表，跳過\n"; continue; }

  // 建表
  $rated = 0;
  $tr='';
  foreach($cfg['rows'] as $r){
    $rt = $r[3]!==null ? '<span class="rt">★ '.$r[3].'</span>' : '<span class="na">—</span>';
    if($r[3]!==null) $rated++;
    $tr .= '<tr><td>'.$r[0].'</td><td>'.$r[1].'</td><td>'.$r[2].'</td><td>'.$rt.'</td></tr>';
  }
  $table = '<div class="cmp"><table><thead><tr><th>店家</th><th>區域</th><th>專精</th><th>Google 評價</th></tr></thead><tbody>'.$tr.'</tbody></table></div>'
    .'<p class="cmp-cap">評價為 Google 商家公開資料（擷取自 '.date('Y/m/d').'），僅供參考、會隨時間變動；標「—」者為無法確認對應商家，故不列評分。</p>';
  $local = '<div class="local">'.$cfg['local'].'</div>';

  // 插在「編輯筆記」之後、第一張店家卡之前
  $anchor = '</ol></div><div class="lrb-card">';
  if(substr_count($body,$anchor)!==1){ echo "❌ {$slug} 錨點命中 ".substr_count($body,$anchor)." 次\n"; $fail++; continue; }
  $body = str_replace($anchor, '</ol></div>'.$table.$local.'<div class="lrb-card">', $body);
  // 加 CSS（接在原 <style> 之後）
  $body = preg_replace('/<\/style>/', '</style>'.$CSS, $body, 1);

  $db->prepare("UPDATE guides SET body_html=? WHERE slug=?")->execute([$body,$slug]);
  $n=$db->query("SELECT LENGTH(body_html) FROM guides WHERE slug='{$slug}'")->fetchColumn();
  echo "✅ {$slug}  比較表 7 列(其中 {$rated} 家有真實評分) + 在地觀察  body={$n}B\n";
  $ok++;
}
echo "\n完成 {$ok} 城，失敗 {$fail}\n";
