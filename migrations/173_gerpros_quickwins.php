<?php
// ============================================================
// Migration 173 — Gerpros 德藝緻(#286) 4 項收尾（Reney「都做」）
// ------------------------------------------------------------
// 1) 補 place_id：ChIJl-AjTEZ3bjQR9zW4K1v_UKs（3 種查法同一筆、店名+地址完全吻合才存）
// 2) 結構修正：chaonaimodiban(超耐磨地板) 折進 flooring(木地板) 避免近義 doorway
// 3) 關鍵字補標：xitonggui(系統櫃)、chuju/kitchen(廚具)、chaonaimodiban → 多子服務頁曝光
// 4) 加進 #98 台南木地板推薦：列為第 5 家（保留 Reney 既有排序，不動前 4 家）+ 比較表列 + 真實 Google 評價
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/173_gerpros_quickwins.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$CID = 286;
$PID = 'ChIJl-AjTEZ3bjQR9zW4K1v_UKs';

// ── 1. place_id ──
$before = $db->query("SELECT COALESCE(NULLIF(google_place_id,''),'(空)') FROM clients WHERE id={$CID}")->fetchColumn();
$db->prepare("UPDATE clients SET google_place_id=? WHERE id=?")->execute([$PID,$CID]);
echo "1) place_id：{$before} → {$PID}\n";

// ── 2. chaonaimodiban 折進 flooring ──
$db->exec("UPDATE service_keywords SET page_slug='flooring' WHERE slug='chaonaimodiban' AND category_id=2");
foreach($db->query("SELECT slug,name,page_slug FROM service_keywords WHERE slug IN ('flooring','chaonaimodiban') AND category_id=2") as $r)
  echo "2) {$r['slug']}({$r['name']}) page_slug=[{$r['page_slug']}]\n";

// ── 3. 關鍵字補標 ──
$want = ['chaonaimodiban','xitonggui','chuju','kitchen'];
$ins = $db->prepare("INSERT IGNORE INTO client_service_keywords (client_id,service_keyword_id) VALUES (?,?)");
foreach($want as $slug){
  $kid = $db->query("SELECT id FROM service_keywords WHERE slug='{$slug}' AND category_id=2 LIMIT 1")->fetchColumn();
  if(!$kid){ echo "3) ⚠ 找不到關鍵字 {$slug}\n"; continue; }
  $ins->execute([$CID,$kid]);
  echo "3) 已標 {$slug} (kid={$kid})\n";
}
$tags=$db->query("SELECT GROUP_CONCAT(sk.slug) FROM client_service_keywords csk JOIN service_keywords sk ON sk.id=csk.service_keyword_id WHERE csk.client_id={$CID}")->fetchColumn();
echo "   目前標籤: {$tags}\n";

// ── 4. 加進 #98 台南木地板推薦（第 5 家，不動前 4 家順序）──
$body = $db->query("SELECT body_html FROM guides WHERE slug='tainan-flooring-recommend'")->fetchColumn();
$orig = $body;
$steps=[];
function rep(&$b,&$s,$label,$from,$to){
  if(substr_count($b,$from)!==1){ $s[]="❌ {$label}(命中".substr_count($b,$from).")"; return; }
  $b=str_replace($from,$to,$b); $s[]="✅ {$label}";
}
// 4a 導言 4→5 家
rep($body,$steps,'4a 導言家數','再嚴選 <strong>4 家台南在地地板行家</strong>','再嚴選 <strong>5 家台南在地地板行家</strong>');
// 4b 比較表標題 4→5
rep($body,$steps,'4b 比較表標題','<h2 id="compare">先看懶人包｜4 家台南地板行家一次比較</h2>','<h2 id="compare">先看懶人包｜5 家台南地板行家一次比較</h2>');
// 4c 比較表加列（接在安居後）
rep($body,$steps,'4c 比較表列',
 '<tr><td>安居木地板</td><td>東區東橋</td><td>超耐磨・SPC</td><td>4.9（36）</td></tr>',
 '<tr><td>安居木地板</td><td>東區東橋</td><td>超耐磨・SPC</td><td>4.9（36）</td></tr>'."\n".'<tr><td>Gerpros 德藝緻</td><td>中西區</td><td>Kronospan 總代理・歐洲進口板材</td><td>4.9（33）</td></tr>');
// 4d 新增第 5 家區塊（插在「台南挑木地板：4 個重點」之前）
$sec = '<h2 id="s5">5. Gerpros 德藝緻建材｜中西區　Kronospan 總代理・歐洲進口板材</h2>'."\n"
.'<p>跟前面幾家地板行不同，德藝緻是歐洲建材進口商——Kronospan 台灣總代理，在中西區開旗艦展示門市。除了超耐磨木地板，系統板材、德國 PINO 廚具、義大利壁布也能一次選齊；展間有<strong>實體大板</strong>可以看整片花色與拼接效果，不用只憑小樣品決定。從丈量、選材到施工一條龍，適合想一次把地板與櫃體建材配齊、或想看大板再下決定的人。</p>'."\n"
.'<div class="si"><b>地址</b>：台南市中西區中華西路二段 596-2 號　｜　<b>電話</b>：<a href="tel:062953300">06-295-3300</a>　｜　<b>完整介紹</b>：<a href="https://www.gomag.com.tw/store/ronodesign">看 Gerpros 德藝緻建材店家頁</a></div>'."\n"
.'<div class="gr"><div class="h"><span class="st">★★★★★</span> Google 4.9（33 則評論）</div><blockquote>「感謝邱先生服務，讓我在年前就把地板做好，從丈量到施工不到兩週！工班師父施工也很迅速專業」</blockquote><cite>— Google 評論・陳里歐</cite></div>'."\n\n";
rep($body,$steps,'4d 第5家區塊','<h2 id="tips">台南挑木地板：4 個重點</h2>',$sec.'<h2 id="tips">台南挑木地板：4 個重點</h2>');

echo "\n4) #98 加入 Gerpros：\n"; foreach($steps as $s) echo "   {$s}\n";
$fail=count(array_filter($steps,fn($s)=>str_starts_with($s,'❌')));
if($fail || $body===$orig){ echo "   ⚠️ 有 {$fail} 步未命中，#98 未寫入（前 3 項已生效）\n"; exit(1); }
$db->prepare("UPDATE guides SET body_html=? WHERE slug='tainan-flooring-recommend'")->execute([$body]);
// excerpt 補上 Gerpros
$db->exec("UPDATE guides SET excerpt=REPLACE(excerpt,'嚴選昇揚、安居、快步、亞拓等台南在地地板行家','嚴選快步、亞拓、昇揚、安居、Gerpros 德藝緻等台南在地地板行家') WHERE slug='tainan-flooring-recommend'");
$r=$db->query("SELECT LENGTH(body_html) bl FROM guides WHERE slug='tainan-flooring-recommend'")->fetch();
echo "   ✅ #98 body={$r['bl']}B\n";
echo "\n驗證： https://www.gomag.com.tw/guide/tainan-flooring-recommend\n";
