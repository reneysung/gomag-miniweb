<?php
// ============================================================
// Migration 162 — #20 台南裝潢細清：店家標題設計加強 + 廣達真實 Google 評價
// ------------------------------------------------------------
// 1) 店家名 h2.lrb-shop 太像內文 → 改：橘紅數字徽章 + 放大 1.5rem + 橘紅底線(店家卡)
//    區段標題(比較表/知識/小結/FAQ 無 .no)用中性底線，:has(.no) 區分。
// 2) 廣達(cleaningcompany4)真實 Google 評價 5.0/330 → 摘一則「逐字」真實評論掛卡上。
//    ⚠️ 旭浪 place_id 失效(指向空地址、重搜誤中廣達)→ 拿不到真評價 → 不放(不能無中生有)。
// 針對性 str_replace，不整篇重寫。跑法：
//   HTTP_HOST=www.gomag.com.tw php migrations/162_tainan_fineclean_heading_google_review.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$row = $db->query("SELECT body_html FROM guides WHERE slug='tainan-fine-clean-recommend'")->fetch(PDO::FETCH_ASSOC);
$body = $row['body_html'];
$orig = $body;

// ── 1a. 替換舊標題 CSS ──
$oldH = '.lrb-guide h2.lrb-shop{font-size:1.32rem;font-weight:900;color:#1a1a1a;margin:42px 0 10px;padding-bottom:8px;border-bottom:1px solid #eee;}
.lrb-guide h2.lrb-shop .no{color:#FF5A36;margin-right:4px;}';
$newH = '.lrb-guide h2.lrb-shop{display:flex;align-items:center;gap:11px;font-size:1.5rem;font-weight:900;color:#1a1a1a;margin:50px 0 14px;padding:0 0 12px;border-bottom:2px solid #f0ddd6;}
.lrb-guide h2.lrb-shop:has(.no){border-bottom-color:#FF5A36;}
.lrb-guide h2.lrb-shop .no{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;background:#FF5A36;color:#fff;border-radius:9px;font-size:1.05rem;font-weight:900;padding:0 9px;box-shadow:0 2px 8px rgba(255,90,54,.28);}
.lrb-guide .lrb-gr{background:#fff;border:1px solid #eadfd9;border-left:3px solid #34A853;border-radius:10px;padding:12px 16px;margin:12px 0;font-family:\'Noto Sans TC\',sans-serif;}
.lrb-guide .lrb-gr-head{font-size:.9rem;color:#555;font-weight:700;margin-bottom:6px;}
.lrb-guide .lrb-gr-head .st{color:#FBBC04;letter-spacing:1px;}
.lrb-guide .lrb-gr-q{margin:0;font-size:.96rem;line-height:1.85;color:#333;}
.lrb-guide .lrb-gr-q cite{display:block;margin-top:7px;font-style:normal;font-size:.85rem;color:#888;}';
$body = str_replace($oldH, $newH, $body);

// ── 1b. 廣達卡插入真實 Google 評價（逐字摘錄，attribution 到 Google + 評論者）──
$guangdaMeta = '<div class="lrb-meta"><b>地址</b>：台南市中華北路二段283號　｜　<b>電話</b>：06-2523773　｜　<b>專精</b>：裝潢細清・交屋・辦公室廠房</div>';
$grBlock = $guangdaMeta . "\n" .
'<div class="lrb-gr"><div class="lrb-gr-head"><span class="st">★★★★★</span> Google 5.0（330 則評論）</div>' .
'<blockquote class="lrb-gr-q">「服務態度很好。清潔效率極佳，非常有團隊效率，價格公道……打掃人員非常準時，早上7點30分就抵達指定地點，非常讚」<cite>— Google 評論・蔡孟軒</cite></blockquote></div>';
$body = str_replace($guangdaMeta, $grBlock, $body);

// ── 檢查 & 寫回 ──
$okH  = strpos($body,'border-radius:9px;font-size:1.05rem') !== false;
$okGR = strpos($body,'Google 5.0（330 則評論）') !== false;
if (!$okH || !$okGR || $body === $orig) {
  echo "⚠️ 替換未全中：heading=".($okH?'OK':'MISS')." review=".($okGR?'OK':'MISS')." changed=".($body!==$orig?'Y':'N')."\n";
  echo "（未寫入，請檢查目標字串）\n"; exit(1);
}
$db->prepare("UPDATE guides SET body_html=? WHERE slug='tainan-fine-clean-recommend'")->execute([$body]);
$r=$db->query("SELECT LENGTH(body_html) bl FROM guides WHERE slug='tainan-fine-clean-recommend'")->fetch();
echo "✅ 標題徽章+廣達真實 Google 評價已上　body={$r['bl']}B\n";
echo "驗證： https://www.gomag.com.tw/guide/tainan-fine-clean-recommend\n";
