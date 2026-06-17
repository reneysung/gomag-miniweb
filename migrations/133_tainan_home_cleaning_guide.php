<?php
// ============================================================
// Migration 133 — 台南清潔頁補第 4 篇《台南居家清潔指南》
// ------------------------------------------------------------
// 台南清潔頁現 3 篇（懶人包 + 2 篇細清向），偏細清。補 1 篇日常維護角度湊滿 4：
//   台南居家清潔指南：鐘點/定期/退租/空屋/行情/各區（cleaning）
// 台南在地化：中西區/東區成大/永康/南區/安平安南/溪北新營後壁、租屋退租、長夏灰塵。
// 語氣套保留人味鐵則。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/133_tainan_home_cleaning_guide.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cover = 'https://www.gomag.com.tw/uploads/guides/covers/cleaning.svg';
$hub   = 'https://www.gomag.com.tw/city/tainan/home-service';
$pClean = 'https://www.gomag.com.tw/city/tainan/home-service/cleaning';
$pReno  = 'https://www.gomag.com.tw/city/tainan/home-service/reno-detail';

$b = <<<HTML
<p class="gg-lead">台南居家清潔的需求很分散，從中西區、東區的租屋退租，到永康、南區的家庭定期打掃，再到安平、安南的海邊住宅，每種情況適合的服務都不一樣。「居家清潔」其實分成好幾種，先搞懂類型和計價，再把屋況講清楚，找起來才不會花冤枉錢。</p>

<h2>台南居家清潔有哪些種類</h2>
<div class="gg-cards">
  <div class="gg-card"><b>🧹 鐘點清潔</b><span>按小時計、單次彈性預約，上班族日常維護首選。</span></div>
  <div class="gg-card"><b>🔁 定期清潔</b><span>每週、雙週或每月固定到府，簽月約單價較低，雙薪家庭和長輩同住適合。</span></div>
  <div class="gg-card"><b>🧽 大掃除</b><span>過年、節慶前的全屋深度整理，旺季要早點約。</span></div>
  <div class="gg-card"><b>📦 退租清潔</b><span>租屋點交前回復屋況、順利拿回押金，成大周邊學生季需求大。</span></div>
  <div class="gg-card"><b>🏠 空屋／入厝清潔</b><span>新居入住前的深層清潔，常和裝潢細清搭配。</span></div>
  <div class="gg-card"><b>✨ 裝潢後細清</b><span>裝潢粉塵殘膠的專業清理（看<a href="{$pReno}">台南裝潢細清</a>）。</span></div>
</div>

<h2>台南居家清潔行情</h2>
<table class="gg-table">
  <thead><tr><th>服務類型</th><th>計價方式</th><th>行情區間</th><th>適合</th></tr></thead>
  <tbody>
    <tr><td>鐘點清潔</td><td>每小時（每次多最低 3 小時）</td><td>NT\$400–600 / 時</td><td>日常維護</td></tr>
    <tr><td>定期清潔</td><td>月約、單價較低</td><td>單次約打 8–9 折</td><td>固定週期</td></tr>
    <tr><td>退租／空屋</td><td>整案（看坪數）</td><td>套房 2,500–5,000；整戶 5,000–15,000</td><td>點交、入住前</td></tr>
    <tr><td>年終大掃除</td><td>整案報價</td><td>視坪數與項目</td><td>過年節慶</td></tr>
    <tr><td>裝潢後細清</td><td>每坪</td><td>NT\$120–250 / 坪</td><td>裝潢後入住前</td></tr>
  </tbody>
</table>
<p>實際以現場估價為準。</p>

<h2>台南各區清潔需求差異</h2>
<ul>
  <li><strong>中西區、東區（成大商圈）</strong>：租屋與套房多，退租清潔、單次清潔需求高，學生季點交潮要早約。</li>
  <li><strong>永康、南區</strong>：家庭住宅多，定期清潔與大掃除是主力。</li>
  <li><strong>安平、安南</strong>：靠海、鹽分高，紗窗、五金容易卡鹽塵，定期清潔可順便顧。</li>
  <li><strong>溪北（新營、鹽水、後壁、柳營）</strong>：透天多、樓層與庭院影響坪數，找在地團隊省車馬費。</li>
</ul>
<div class="gg-tip">
  <b>💡 台南在地提醒</b>
  <p>台南夏天長、灰塵多，定期清潔的頻率可以拉高一點。府城透天比例高，估價時記得說明樓層、有沒有含庭院和頂樓，報價才會準。</p>
</div>

<h2>怎麼挑才不踩雷</h2>
<ul>
  <li>找有免費到府估價的，報價單列清楚含哪些（耗材、垃圾清運、搬挪家具）。</li>
  <li>確認人員是自聘、有責任險。</li>
  <li>定期約先做一次單次試試看，滿意再簽長約。</li>
</ul>
<div class="gg-warn">
  <b>⚠️ 避雷</b>
  <p>報價遠低於行情、不到府只給均一價、要求先付全額的要小心，常會後續加價或品質不到位。訂金不超過三成。</p>
</div>

<h2>常見問題</h2>
<div class="gg-faq">
  <div class="gg-q">鐘點和定期清潔差在哪？</div>
  <div class="gg-a">鐘點是單次按時、彈性；定期是固定週期簽約、單價較低，而且通常是同一組人比較熟悉你家環境。</div>
  <div class="gg-q">成大附近退租清潔怎麼算？</div>
  <div class="gg-a">套房約 NT\$2,500–5,000，看坪數與屋況。學生季點交潮人多，建議提早預約。</div>
  <div class="gg-q">台南透天清潔比較貴嗎？</div>
  <div class="gg-a">會。府城透天多，樓層、樓梯、庭院、頂樓都增加工時，估價要說明完整屋況。</div>
  <div class="gg-q">居家清潔和裝潢細清一樣嗎？</div>
  <div class="gg-a">不一樣。一般清潔是日常維護；<a href="{$pReno}">裝潢細清</a>專處理裝潢後的粉塵殘膠，費用約 1.5 到 2 倍。</div>
  <div class="gg-q">過年大掃除什麼時候約？</div>
  <div class="gg-a">11 月起就開始搶，建議 11 月中前預約卡位。</div>
</div>

<p>找台南在地清潔團隊看 <a href="{$pClean}"><strong>台南清潔公司</strong></a>，裝潢後入住看 <a href="{$pReno}">台南裝潢細清</a>，更多服務看 <a href="{$hub}">台南居家服務</a>。</p>
HTML;

$gs = $db->prepare("INSERT INTO guides (slug, title, excerpt, body_html, city_slug, category_id, service_slug, cover_image, meta_title, meta_desc, status, published_at)
    VALUES (?, ?, ?, ?, 'tainan', 2, 'cleaning', ?, ?, ?, 'published', NOW())
    ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
        service_slug='cleaning', cover_image=VALUES(cover_image),
        meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc), status='published', published_at=COALESCE(published_at, NOW())");
$gs->execute([
    'tainan-home-cleaning-guide',
    '台南居家清潔指南：鐘點、定期、退租、空屋清潔行情一次看',
    '台南居家清潔怎麼選？鐘點、定期、退租、空屋 4 種類型計價與行情，加上中西區/東區成大/永康/安平/溪北各區眉角，台南長夏灰塵多、透天多的在地建議。',
    $b, $cover,
    '台南居家清潔指南｜鐘點定期退租空屋行情｜店家好口碑',
    '台南居家清潔 4 種類型（鐘點/定期/退租/空屋）計價與行情、中西區/東區成大/永康/安平/溪北各區眉角、挑選不踩雷，台南租屋族與家庭住戶適用。',
]);

echo "✅ 寫入 台南居家清潔指南\n";
echo "\n== 台南清潔(cleaning)頁 修後 ==\n";
$rg = $db->prepare("SELECT title, service_slug FROM guides WHERE status='published' AND (category_id=2 OR category_id IS NULL) AND city_slug='tainan' AND FIND_IN_SET('cleaning',service_slug)>0 ORDER BY published_at DESC LIMIT 4");
$rg->execute();
$n=0; foreach ($rg as $r) { $n++; printf("  %d. %s (svc=%s)\n", $n, $r['title'], $r['service_slug']); }
echo "  → 共 {$n} 篇\n";
