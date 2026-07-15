<?php
// ============================================================
// Migration 163 — #71 台南清潔公司推薦「深度升級」(照 #20 同法) + 旭浪 place_id 修正
// ------------------------------------------------------------
// A) 旭浪(#42) google_place_id 是壞資料(指向「大灣路581號」空地址、零評價；重搜又誤中廣達)
//    → 清空(不能亂存看似對的假 id)。等 Reney 提供旭浪真實 Google Maps 連結再手動補。
// B) #71 加深層(不重寫既有店家描述/客戶置入)：
//    1. 特色比較表(gg-table)  2. 深度知識段 <h2 id="know">(清潔挑選知識，自己寫不捏統計)
//    3. 真實 Google 評價：享清 4.9/100(Sarah 逐字)、廣達 5.0/330(胡珠萍 逐字)
//    4. 部落客體驗連結：旭浪/廣達/倍亮/臥龍閣(沿用 #20 真實部落客文，誠實標「部落客」)
//    5. FAQPage JSON-LD(既有 5 題)
// 全部針對性 str_replace，逐項 assert 命中才寫入。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/163_tainan_cleaning_deep_upgrade_and_xulang_place_fix.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

// ── A. 旭浪 place_id 清空 ──
$before = $db->query("SELECT google_place_id FROM clients WHERE id=42")->fetchColumn();
$db->prepare("UPDATE clients SET google_place_id='' WHERE id=42")->execute();
echo "A) 旭浪 place_id：[{$before}] → [] (已清空壞資料)\n\n";

// ── B. 取 #71 body ──
$body = $db->query("SELECT body_html FROM guides WHERE slug='tainan-cleaning-company-recommend'")->fetchColumn();
$orig = $body;
$steps = [];
function rep(&$body,&$steps,$label,$from,$to){
  $n = substr_count($body,$from);
  if($n!==1){ $steps[]="❌ {$label}：目標命中 {$n} 次(需=1)"; return; }
  $body = str_replace($from,$to,$body);
  $steps[]="✅ {$label}";
}

// B0. 前置 <style>（lrb-gr / lrb-blog，scoped .g-guide-body）
$style = '<style>'
.'.g-guide-body .lrb-gr{background:#fff;border:1px solid #eadfd9;border-left:3px solid #34A853;border-radius:10px;padding:11px 15px;margin:10px 0;}'
.'.g-guide-body .lrb-gr .h{font-size:.88rem;font-weight:700;color:#555;margin-bottom:5px;}'
.'.g-guide-body .lrb-gr .h .st{color:#FBBC04;letter-spacing:1px;}'
.'.g-guide-body .lrb-gr blockquote{margin:0;font-size:.94rem;line-height:1.85;color:#333;}'
.'.g-guide-body .lrb-gr cite{display:block;margin-top:6px;font-style:normal;font-size:.82rem;color:#888;}'
.'</style>' . "\n";
rep($body,$steps,'0 style 前置','<p>台南清潔公司怎麼挑？',$style.'<p>台南清潔公司怎麼挑？');

// B1. 比較表（插在 ※ 免責 <p> 之後、目錄之前）
$compare = <<<'H'
<h2 id="compare">🔍 先看懶人包｜7 家台南清潔一次比較</h2>
<table class="gg-table">
<thead><tr><th>店家</th><th>區域</th><th>最適合</th><th>口碑來源</th></tr></thead>
<tbody>
<tr><td>旭浪清潔</td><td>永康</td><td>宅修＋綜合居家</td><td>部落客實測</td></tr>
<tr><td>廣達清潔</td><td>北區</td><td>裝潢細清・交屋</td><td>Google 5.0（330）</td></tr>
<tr><td>倍亮清潔</td><td>新市</td><td>居家＋家電深洗</td><td>部落客實測</td></tr>
<tr><td>享清清潔</td><td>仁安八街</td><td>細清＋搬家一站式</td><td>Google 4.9（100）</td></tr>
<tr><td>臥龍閣清潔</td><td>新營溪北</td><td>定期維護・退租</td><td>部落客實測</td></tr>
<tr><td>沐沐清潔</td><td>安平</td><td>空屋／店面／辦公室</td><td>官網</td></tr>
<tr><td>尚撰清潔</td><td>安南</td><td>清潔＋除蟲消毒</td><td>店家頁</td></tr>
</tbody>
</table>


H;
rep($body,$steps,'1 比較表','<h2 id="toc">📖 文章目錄</h2>',$compare.'<h2 id="toc">📖 文章目錄</h2>');

// B2. 目錄加 2 項（比較表/知識）
rep($body,$steps,'2 目錄補項',
  "<ol>\n  <li><a href=\"#s1\">第 1 家｜旭浪清潔公司（宅修清潔・綜合居家清潔）</a></li>",
  "<ol>\n  <li><a href=\"#compare\">7 家台南清潔一次比較</a></li>\n  <li><a href=\"#know\">先懂再挑：台南找清潔要知道的事</a></li>\n  <li><a href=\"#s1\">第 1 家｜旭浪清潔公司（宅修清潔・綜合居家清潔）</a></li>");

// B3. 深度知識段（插在目錄 </ol> 之後、#s1 之前）
$know = <<<'H'
<h2 id="know">🧠 先懂再挑：台南找清潔要知道的事</h2>
<p>「清潔公司」其實涵蓋很多種服務，找錯類型會白花錢。先分清楚需求，再看報價與資格，才不會踩雷。</p>
<h3>四種清潔，找的團隊不一樣</h3>
<ul>
<li><strong>鐘點居家清潔</strong>：日常維護、定期打掃，以「小時」計，適合上班族、雙薪家庭固定請人。</li>
<li><strong>裝潢後細清</strong>：處理粉塵、矽利康殘膠、玻璃水痕，工具藥劑與工序都不同，一定要找「細清專門」。</li>
<li><strong>退租／空屋清潔</strong>：交屋點交、恢復原狀，重點在角落、廚衛、油污，常要配合房東驗收時間。</li>
<li><strong>專項深洗</strong>：冷氣、水塔、抽油煙機、洗衣機內槽，屬於拆洗，跟表面打掃是兩回事。</li>
</ul>
<h3>報價要問清楚「含什麼」</h3>
<p>同樣一句「一次多少錢」，含不含耗材、垃圾清運、家具搬移、玻璃外窗、高處作業，差很多。到府或視訊估價、白紙黑字列含／不含的優先；只報一個籠統數字、到現場才一項項加價的要小心。</p>
<h3>看資格與保障</h3>
<p>問清楚是否開發票、有沒有保險（清潔過程萬一損壞才有保障）、人員是否受訓。要簽定期約前先試一次單次清潔，滿意再綁月約。</p>
<div class="lrb-gr" style="border-left-color:#FF5A36"><div class="h">⚠️ 最容易踩的雷</div><blockquote>報「一坪／一小時多少」卻沒說含不含玻璃、垃圾清運、家電深洗，到現場才加價；或沒有驗收複清，做完發現角落沒清也叫不回來。下訂前把含／不含、幾人幾工時、驗收不過是否免費補清寫下來。</blockquote></div>


H;
rep($body,$steps,'3 深度知識',
  "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n</ol>\n\n<h2 id=\"s1\">◈ 第 1 家",
  "  <li><a href=\"#faq\">常見問題 FAQ</a></li>\n</ol>\n\n".$know."<h2 id=\"s1\">◈ 第 1 家");

// B4. 旭浪(#s1) 部落客體驗
rep($body,$steps,'4 旭浪部落客',
  '  <li>🔗 完整介紹與口碑評價：<a href="https://www.gomag.com.tw/store/stonebeauty">看旭浪清潔公司店家頁</a></li>'."\n".'</ul>',
  '  <li>🔗 完整介紹與口碑評價：<a href="https://www.gomag.com.tw/store/stonebeauty">看旭浪清潔公司店家頁</a></li>'."\n".'  <li>🧑‍💻 部落客實際體驗：<a href="https://decgo.pixnet.net/blog/posts/13334808671" target="_blank" rel="noopener">走跳台南人 的體驗心得 →</a></li>'."\n".'</ul>');

// B5. 廣達(#s2) 部落客 + Google 評價
rep($body,$steps,'5 廣達部落客+Google',
  '  <li>🔗 完整介紹與口碑評價：<a href="https://www.gomag.com.tw/store/cleaningcompany4">看廣達清潔公司店家頁</a></li>'."\n".'</ul>',
  '  <li>🔗 完整介紹與口碑評價：<a href="https://www.gomag.com.tw/store/cleaningcompany4">看廣達清潔公司店家頁</a></li>'."\n"
  .'  <li>🧑‍💻 部落客實際體驗：<a href="https://novgo11.pixnet.net/blog/posts/13362503946" target="_blank" rel="noopener">艾薇。到處走 的體驗心得 →</a></li>'."\n".'</ul>'."\n"
  .'<div class="lrb-gr"><div class="h"><span class="st">★★★★★</span> Google 5.0（330 則評論）</div><blockquote>「清潔一定要找廣達……重點是清理的很乾淨，又沒什麼刺鼻味道，好讓我可以順利交給房東，連房仲都說清理的這麼乾淨。」</blockquote><cite>— Google 評論・胡珠萍</cite></div>');

// B6. 倍亮(#s3) 部落客（用電話 0938286652 定位，唯一）
rep($body,$steps,'6 倍亮部落客',
  '  <li>📞 電話：<a href="tel:0938286652">0938-286-652</a></li>'."\n".'  <li>💬 網路在地口碑團隊</li>',
  '  <li>📞 電話：<a href="tel:0938286652">0938-286-652</a></li>'."\n".'  <li>🧑‍💻 部落客實際體驗：<a href="https://aronrandom.pixnet.net/blog/posts/4044248430" target="_blank" rel="noopener">史丹利樂福 的體驗心得 →</a></li>'."\n".'  <li>💬 網路在地口碑團隊</li>');

// B7. 享清(#s4) Google 評價
rep($body,$steps,'7 享清Google',
  '  <li>🔗 完整介紹與口碑評價：<a href="https://www.gomag.com.tw/store/cleaningcompany2">看享清清潔工程企業社店家頁</a></li>'."\n".'</ul>',
  '  <li>🔗 完整介紹與口碑評價：<a href="https://www.gomag.com.tw/store/cleaningcompany2">看享清清潔工程企業社店家頁</a></li>'."\n".'</ul>'."\n"
  .'<div class="lrb-gr"><div class="h"><span class="st">★★★★★</span> Google 4.9（100 則評論）</div><blockquote>「新家裝潢後細清：清潔後煥然一新！很感謝團隊細心仔細的清潔每一處粉塵，尤其是所有的木工櫃子都會拔下來擦拭內部空間……重點是價格經多方比較也很合理！」</blockquote><cite>— Google 評論・Sarah i</cite></div>');

// B8. 臥龍閣(#s5) 部落客（電話 0930546164 定位，唯一）
rep($body,$steps,'8 臥龍閣部落客',
  '  <li>📞 電話：<a href="tel:0930546164">0930-546-164</a></li>'."\n".'  <li>💬 網路在地口碑團隊</li>',
  '  <li>📞 電話：<a href="tel:0930546164">0930-546-164</a></li>'."\n".'  <li>🧑‍💻 部落客實際體驗：<a href="https://augo08.pixnet.net/blog/posts/13344508764" target="_blank" rel="noopener">艾瑪娘的小日子 的體驗心得 →</a></li>'."\n".'  <li>💬 網路在地口碑團隊</li>');

// B9. FAQPage JSON-LD（既有 5 題逐字）
$faqs=[
 ['台南清潔公司行情多少？','鐘點清潔每小時 NT$400–600；裝潢細清每坪 NT$500–900；退租清潔套房 NT$2,500–5,000。簽定期約通常比單次便宜。'],
 ['裝潢細清跟一般打掃差在哪？','細清專門處理裝潢後的粉塵、矽利康殘膠、玻璃水痕，工具與藥水都不同，費用約一般清潔 1.5–2 倍。'],
 ['過年大掃除要提前多久預約？','11 月就開始搶，12 月到過年前 2 週是爆量期，建議 11 月中前預約。'],
 ['新營、鹽水有在地清潔團隊嗎？','有，本文第 5 家臥龍閣就在新營，溪北地區（新營、鹽水、柳營、白河）可優先找在地團隊，省跨區車馬費。'],
 ['除蟲消毒可以跟清潔一起做嗎？','可以，本文第 7 家尚撰是清潔＋病媒防治雙專業，蟲害、黴菌、消毒可一次處理。'],
];
$ld=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach($faqs as $f)$ld['mainEntity'][]=['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]];
$body .= "\n<script type=\"application/ld+json\">".json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."</script>\n";
$steps[]='✅ 9 FAQPage JSON-LD';

// ── 報告 & 寫入 ──
echo "B) #71 深度升級：\n";
foreach($steps as $s) echo "   {$s}\n";
$fail = count(array_filter($steps,fn($s)=>str_starts_with($s,'❌')));
if($fail){ echo "\n⚠️ 有 {$fail} 步未命中，未寫入 body。請檢查目標字串。\n"; exit(1); }
$db->prepare("UPDATE guides SET body_html=? WHERE slug='tainan-cleaning-company-recommend'")->execute([$body]);
$r=$db->query("SELECT LENGTH(body_html) bl FROM guides WHERE slug='tainan-cleaning-company-recommend'")->fetch();
echo "\n✅ #71 body {".strlen($orig)."B → {$r['bl']}B}\n";
echo "驗證： https://www.gomag.com.tw/guide/tainan-cleaning-company-recommend\n";
