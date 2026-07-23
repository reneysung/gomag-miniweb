<?php
// ============================================================
// Migration 170 — 高雄全戶軟水攻略 #91：換上蕎藝真實安裝照片（5 張）
// ------------------------------------------------------------
// Reney 提供 Google Drive「SEO文章」真實蕎藝全戶軟水安裝照 → 取代原示意圖。
// 忠實對應（不硬塞舊圖說）：整體配置/師傅填濾材/陽台配管/自動再生控制器/主機+前置過濾。
// 圖已壓縮上傳兩 docroot uploads/guides/kaohsiung-soft-water/。此檔只改 body 的 5 figure。
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/170_soft_water_real_photos.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$B = 'https://www.gomag.com.tw/uploads/guides/kaohsiung-soft-water';

$body = $db->query("SELECT body_html FROM guides WHERE slug='kaohsiung-whole-house-soft-water'")->fetchColumn();
$orig = $body;

$pairs = [
 // 1. 導言：整體配置
 ['<figure><img src="'.$B.'/kh-softwater-install.webp" alt="高雄全戶淨軟水系統安裝實景" loading="lazy"><figcaption>全戶軟水裝在水進屋前的總管或水塔端，處理全家的水。</figcaption></figure>',
  '<figure><img src="'.$B.'/kh-softwater-system.jpg" alt="高雄透天全戶軟水系統整體安裝配置" loading="lazy"><figcaption>全戶軟水裝在水進屋前的進水端，處理全家的水。</figcaption></figure>'],
 // 2. 師傅施工（沿用 technician 檔名，只換 alt/caption 對應填濾材照）
 ['<figure><img src="'.$B.'/kh-softwater-technician.jpg" alt="淨水師傅到高雄透天頂樓安裝全戶軟水系統" loading="lazy"><figcaption>全戶軟水多在透天頂樓水塔端施工，找有經驗、能到府場勘的安裝團隊比較安心。</figcaption></figure>',
  '<figure><img src="'.$B.'/kh-softwater-technician.jpg" alt="淨水師傅在高雄現場為全戶軟水系統填充濾材" loading="lazy"><figcaption>全戶軟水由專業師傅到府施工，找有經驗、能場勘的安裝團隊比較安心。</figcaption></figure>'],
 // 3. 配管配置
 ['<figure><img src="'.$B.'/kh-softwater-rooftop-tank.webp" alt="高雄透天頂樓水塔的軟水管線配置" loading="lazy"><figcaption>高雄透天多在頂樓水塔端配置，安裝前要看水塔位置與管線走向。</figcaption></figure>',
  '<figure><img src="'.$B.'/kh-softwater-piping.jpg" alt="高雄透天陽台的全戶軟水系統配管配置" loading="lazy"><figcaption>全戶軟水沿進水端配管，安裝前要看好水源位置與管線走向。</figcaption></figure>'],
 // 4. 自動再生控制器（完美對應再生段）
 ['<figure><img src="'.$B.'/kh-softwater-regen-salt.jpg" alt="離子交換式軟水系統的再生鹽添加槽" loading="lazy"><figcaption>傳統軟水靠再生鹽定期再生；選機型時先問清楚耗鹽、廢水與保養方式。</figcaption></figure>',
  '<figure><img src="'.$B.'/kh-softwater-controller.jpg" alt="全戶軟水系統的自動再生控制器" loading="lazy"><figcaption>離子交換軟水機靠定期再生維持效果；選機型先問清楚再生方式、耗材、廢水與保養。</figcaption></figure>'],
 // 5. 主機+前置過濾（原水質測試格，無對應照 → 改主機+前置）
 ['<figure><img src="'.$B.'/kh-softwater-water-test.jpg" alt="全戶軟水安裝前後的水質測試對比" loading="lazy"><figcaption>安裝前後可做水質測試對比，確認硬度真的有下降。</figcaption></figure>',
  '<figure><img src="'.$B.'/kh-softwater-unit.jpg" alt="高雄全戶軟水主機與前置過濾器安裝實景" loading="lazy"><figcaption>全戶軟水主機常搭配前置過濾，先擋泥沙雜質再軟化全家用水。</figcaption></figure>'],
];

$fail = 0;
foreach ($pairs as $i => $p) {
  if (strpos($body, $p[0]) === false) { echo "❌ 圖 ".($i+1)." 舊 figure 未命中\n"; $fail++; continue; }
  $body = str_replace($p[0], $p[1], $body);
  echo "✅ 圖 ".($i+1)." 已替換\n";
}
if ($fail || $body === $orig) { echo "\n⚠️ 有 {$fail} 張未命中，未寫入。\n"; exit(1); }

$db->prepare("UPDATE guides SET body_html=? WHERE slug='kaohsiung-whole-house-soft-water'")->execute([$body]);
echo "\n✅ 已更新 body（5 張真實蕎藝照）body=".strlen($body)."B\n";
echo "驗證： https://www.gomag.com.tw/guide/kaohsiung-whole-house-soft-water\n";
