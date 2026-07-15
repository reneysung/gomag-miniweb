<?php
// ============================================================
// Migration 166 — #98 台南木地板：店家排序調整（只換順序，內文不動）
// ------------------------------------------------------------
// Reney：快步第一、亞拓第二、昇揚第三、安居第四。
// 程式化重排：比較表 4 列重排 + 4 個店家區塊重排並重編號(id/1.2.3.4)，其餘一字不動。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/166_tainan_flooring_reorder.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$body = $db->query("SELECT body_html FROM guides WHERE slug='tainan-flooring-recommend'")->fetchColumn();
$orig = $body;

// ── 1. 比較表 4 列重排（str_replace 精確）──
$oldRows =
'<tr><td>昇揚木地板</td><td>東區東門路</td><td>超耐磨・海島型・實木</td><td>5.0（51）</td></tr>'."\n".
'<tr><td>安居木地板</td><td>東區東橋</td><td>超耐磨・SPC</td><td>4.9（36）</td></tr>'."\n".
'<tr><td>快步地板（富崧）</td><td>安平</td><td>QuickStep 卡扣免膠超耐磨</td><td>4.8（25）</td></tr>'."\n".
'<tr><td>亞拓</td><td>仁德</td><td>塑膠地板・窗簾（地板窗簾一起）</td><td>4.8（126）</td></tr>';
$newRows =
'<tr><td>快步地板（富崧）</td><td>安平</td><td>QuickStep 卡扣免膠超耐磨</td><td>4.8（25）</td></tr>'."\n".
'<tr><td>亞拓</td><td>仁德</td><td>塑膠地板・窗簾（地板窗簾一起）</td><td>4.8（126）</td></tr>'."\n".
'<tr><td>昇揚木地板</td><td>東區東門路</td><td>超耐磨・海島型・實木</td><td>5.0（51）</td></tr>'."\n".
'<tr><td>安居木地板</td><td>東區東橋</td><td>超耐磨・SPC</td><td>4.9（36）</td></tr>';
if(strpos($body,$oldRows)===false){ echo "❌ 比較表列未命中，abort\n"; exit(1); }
$body = str_replace($oldRows,$newRows,$body);

// ── 2. 店家區塊重排 + 重編號 ──
$p1 = strpos($body,'<h2 id="s1">');
$p2 = strpos($body,'<h2 id="tips">');
if($p1===false||$p2===false||$p2<$p1){ echo "❌ 店家區/tips 邊界找不到，abort\n"; exit(1); }
$region = substr($body,$p1,$p2-$p1);
preg_match_all('/(<h2 id="s\d+">.*?)(?=<h2 id="s\d+">|\z)/s',$region,$m);
$blocks = $m[1];                        // 目前順序：0昇揚 1安居 2快步 3亞拓
if(count($blocks)!==4){ echo "❌ 抓到 ".count($blocks)." 個店家區(需4)，abort\n"; exit(1); }
$newOrder = [2,3,0,1];                   // 快步 亞拓 昇揚 安居
$rebuilt = '';
foreach($newOrder as $i=>$idx){
  $pos = $i+1;
  $blk = preg_replace('/^<h2 id="s\d+">\s*\d+\.\s*/', "<h2 id=\"s{$pos}\">{$pos}. ", $blocks[$idx], 1);
  $rebuilt .= $blk;
}
$body = str_replace($region,$rebuilt,$body);

// ── 驗證新順序 & 寫入 ──
$ok = strpos($body,'<h2 id="s1">1. 快步')!==false
   && strpos($body,'<h2 id="s2">2. 亞拓')!==false
   && strpos($body,'<h2 id="s3">3. 昇揚')!==false
   && strpos($body,'<h2 id="s4">4. 安居')!==false;
if(!$ok || $body===$orig){ echo "⚠️ 新順序驗證失敗，未寫入\n"; exit(1); }
$db->prepare("UPDATE guides SET body_html=? WHERE slug='tainan-flooring-recommend'")->execute([$body]);
echo "✅ 排序已改：快步→亞拓→昇揚→安居（比較表＋店家區同步，內文未動）body=".strlen($body)."B\n";
echo "驗證： https://www.gomag.com.tw/guide/tainan-flooring-recommend\n";
