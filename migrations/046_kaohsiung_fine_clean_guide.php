<?php
// ============================================================
// Migration 046 — 高雄裝潢細清 7 家懶人包（攻略文 / 圖文版）
// ------------------------------------------------------------
// city=kaohsiung, category=home-service(2)。圖文式「店家好口碑攻略」。
// 1 家 gomag 客戶（三峰，#197/#218 重複待併）+ 6 家在地口碑。
// 每家連到不同部落客/官方來源；文末導流高雄清潔子服務頁。
// 三峰/明潔/66 用各自連結文章的「設計標題封面」(lrb-thumb-full 完整顯示)；
// 萬有用細清前後實照(lrb-thumb)；CALLmesis/家事幫/樂家庭 連結無可用橫幅圖 → 文字卡。
// 圖轉存 gomag 圖床 uploads/guides/kaohsiung-fine-clean/（rsync prod+staging）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/046_kaohsiung_fine_clean_guide.php
// 冪等：以 slug upsert（保留首次 published_at）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$slug       = 'kaohsiung-fine-clean-recommend';
$title      = '高雄裝潢細清推薦｜2026 嚴選 7 家在地口碑';
$excerpt    = '新成屋交屋、中古翻修、退租後的細清怎麼選？店家好口碑精選 7 家高雄在地口碑團隊，從裝潢後細清、交屋驗收到退租、定期居家一次看。';
$metaTitle  = '高雄裝潢細清推薦｜2026 嚴選 7 家在地口碑｜店家好口碑';
$metaDesc   = '高雄裝潢細清推薦 2026！新成屋交屋、中古翻修、退租後的細清這樣選——店家好口碑精選 7 家高雄在地口碑團隊（三峰、明潔、66、萬有…），從裝潢後細清、交屋驗收到專項深洗一次看。';
$cover      = '';

$body = <<<'HTML'
<style>
.lrb-guide{font-family:'Noto Serif TC','Songti TC',serif;color:#2c2c2c;}
.lrb-guide p{margin:0 0 16px;}
.lrb-guide .lrb-tip{background:#faf6f4;border-left:3px solid #FF5A36;padding:14px 18px;margin:24px 0;font-size:.97rem;font-family:'Noto Sans TC',sans-serif;line-height:1.85;}
.lrb-guide h2.lrb-shop{font-size:1.32rem;font-weight:900;color:#1a1a1a;margin:42px 0 10px;padding-bottom:8px;border-bottom:1px solid #eee;}
.lrb-guide h2.lrb-shop .no{color:#FF5A36;margin-right:4px;}
.lrb-guide .lrb-meta{background:#f7f4f2;border-radius:10px;padding:12px 16px;margin:12px 0;font-size:.95rem;font-family:'Noto Sans TC',sans-serif;line-height:1.9;color:#555;}
.lrb-guide .lrb-meta b{color:#1a1a1a;}
.lrb-guide .lrb-src a{color:#FF5A36;font-weight:700;font-family:'Noto Sans TC',sans-serif;text-decoration:none;}
.lrb-guide .lrb-thumb{width:100%;height:260px;object-fit:cover;border-radius:12px;margin:14px 0 6px;display:block;background:#f2efed;}
.lrb-guide .lrb-thumb-full{width:100%;height:auto;border-radius:12px;margin:14px 0 6px;display:block;}
.lrb-guide .lrb-card{width:100%;border-radius:12px;margin:14px 0 6px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;background:#faf6f4;border:1px solid #f0e3dd;text-align:center;padding:42px 20px;box-sizing:border-box;}
.lrb-guide .lrb-card .tc-kicker{font-family:'Noto Sans TC',sans-serif;font-size:.72rem;letter-spacing:3px;color:#FF5A36;font-weight:700;}
.lrb-guide .lrb-card .tc-name{font-weight:900;font-size:1.5rem;color:#1a1a1a;letter-spacing:1px;}
.lrb-guide .lrb-card .tc-tag{font-family:'Noto Sans TC',sans-serif;font-size:.95rem;color:#555;letter-spacing:2px;}
.lrb-guide .lrb-closing{font-size:1.02rem;}
.lrb-guide .lrb-cta{background:#fff5f2;border:1px solid #FF5A36;border-radius:12px;padding:18px 20px;margin:30px 0 0;text-align:center;font-family:'Noto Sans TC',sans-serif;}
.lrb-guide .lrb-cta a{display:inline-block;margin-top:8px;background:#FF5A36;color:#fff;padding:11px 26px;border-radius:999px;font-weight:700;text-decoration:none;}
</style>
<div class="lrb-guide">
<p>裝潢完工、新成屋交屋、中古屋翻修後，滿屋的粉塵、矽利康殘膠、油漆點與施工髒污，自己怎麼擦都擦不完——這時就需要專業的「裝潢細清」。高雄找細清，最怕「報價不清、邊邊角角沒顧到」。這次店家好口碑彙整在地口碑與實際體驗，精選 <strong>7 家高雄清潔團隊</strong>，從裝潢後細清、交屋驗收到退租、定期居家都涵蓋，帶你依需求一次看完。</p>

<div class="lrb-tip"><b>編輯小提醒｜挑高雄裝潢細清看 5 點</b><br>
① 先分需求（裝潢後細清／交屋驗收／退租／定期居家）　② 到府或視訊估價、報價透明（坪數×單價或包項）　③ 細節到不到位（踢腳板、矽利康縫、插座周圍、門框上方、玻璃水漬）　④ 在地口碑與實際案例照　⑤ 是否含驗收複清。</div>

<h2 class="lrb-shop"><span class="no">#1</span>三峰清潔・高雄在地口碑團隊</h2>
<img class="lrb-thumb-full" src="https://www.gomag.com.tw/uploads/guides/kaohsiung-fine-clean/1-sanfeng.jpg" alt="三峰清潔｜高雄裝潢細清退租居家" loading="lazy">
<p>高雄在地知名的清潔團隊，服務涵蓋高雄 38 區，裝潢細清、退租、居家定期、套房與家電清洗都接。想找一個長期配合、評價穩定的窗口，三峰是不少高雄人的口袋名單。</p>
<div class="lrb-meta"><b>服務區域</b>：高雄 38 區　｜　<b>電話</b>：07-976-6708　｜　<b>專精</b>：裝潢細清・退租・居家・家電清洗</div>
<p class="lrb-src">🔗 <a href="https://ja0982538938.pixnet.net/blog/posts/890487327365532180" target="_blank" rel="noopener">看實際體驗心得（部落客 我是藍小姐）→</a></p>

<h2 class="lrb-shop"><span class="no">#2</span>明潔專業清潔・楠梓退租細清</h2>
<img class="lrb-thumb-full" src="https://www.gomag.com.tw/uploads/guides/kaohsiung-fine-clean/2-mingjie.jpg" alt="明潔專業清潔｜高雄楠梓退租裝潢後細清" loading="lazy">
<p>主打退租與裝潢後清潔，3 房 2 廳一天就能整理到煥然一新，連冰箱內部、鏡面邊框這些小細節都顧到。楠梓一帶找退租／入厝細清，CP 值高。</p>
<div class="lrb-meta"><b>地址</b>：高雄市楠梓區久昌街224號　｜　<b>電話</b>：0979-026-423　｜　<b>專精</b>：退租・裝潢後細清・居家</div>
<p class="lrb-src">🔗 <a href="https://apgo04.pixnet.net/blog/posts/13335363113" target="_blank" rel="noopener">看實際體驗心得（部落客 芭比大媽生活誌）→</a></p>

<h2 class="lrb-shop"><span class="no">#3</span>66清潔・裝潢後細清培訓制團隊</h2>
<img class="lrb-thumb-full" src="https://www.gomag.com.tw/uploads/guides/kaohsiung-fine-clean/3-66.jpg" alt="66居家清潔｜高雄裝潢後細清" loading="lazy">
<p>主打裝潢後細清，專清粉塵、殘膠、油漆與水泥，採內部團隊培訓制（非臨時工派遣），品質較穩定。鳳山、左營、仁武都設有門市。</p>
<div class="lrb-meta"><b>門市</b>：鳳山／左營／仁武　｜　<b>電話</b>：0953-939-813　｜　<b>專精</b>：裝潢後細清・粉塵殘膠・浴廁深清</div>
<p class="lrb-src">🔗 <a href="https://www.stanleylove.com.tw/archives/3267" target="_blank" rel="noopener">看實際體驗心得（部落客 史丹利樂福）→</a></p>

<h2 class="lrb-shop"><span class="no">#4</span>CALLmesis 亞洲新灣區・新成屋細清</h2>
<div class="lrb-card">
  <div class="tc-kicker">高雄在地口碑</div>
  <div class="tc-name">CALLmesis 亞洲新灣區專業清潔</div>
  <div class="tc-tag">新成屋細清・無損去膠・石材保護</div>
</div>
<p>新成屋裝潢細清見長，主打 HEPA 吸塵深層拔塵、無損去膠與石材保護、老屋泥作善後，報價透明。亞洲新灣區、前鎮、苓雅一帶找新成屋細清可參考。</p>
<div class="lrb-meta"><b>服務區域</b>：高雄（亞洲新灣區一帶）　｜　<b>專精</b>：新成屋細清・無損去膠・石材保護・系統櫃拔塵</div>
<p class="lrb-src">🔗 <a href="https://callmesis.com.tw/services/post-renovation-cleaning/area/kaohsiung" target="_blank" rel="noopener">看 CALLmesis 高雄裝潢細清頁 →</a></p>

<h2 class="lrb-shop"><span class="no">#5</span>萬有清潔・交屋退屋一條龍</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/guides/kaohsiung-fine-clean/5-wanyou.jpg" alt="萬有清潔｜高雄裝潢後全室細清交屋" loading="lazy">
<p>裝潢後全室細清與交屋退屋清潔，從場勘規劃到細部清潔一條龍，打造潔淨無塵環境。新成屋交屋、退租找它省心。</p>
<div class="lrb-meta"><b>電話</b>：07-3958621／0800-084-568　｜　<b>專精</b>：裝潢後全室細清・交屋退屋・場勘規劃</div>
<p class="lrb-src">🔗 <a href="https://wyclean.com.tw" target="_blank" rel="noopener">看萬有清潔官網服務項目 →</a></p>

<h2 class="lrb-shop"><span class="no">#6</span>家事幫專業居家清潔・空屋細清</h2>
<div class="lrb-card">
  <div class="tc-kicker">高雄在地口碑</div>
  <div class="tc-name">家事幫專業居家清潔</div>
  <div class="tc-tag">裝潢細清・空屋・無毒清潔</div>
</div>
<p>裝潢細清與空屋清潔為主，主打無毒清潔、高品質環境。梓官一帶服務，想要入厝前把空屋細清乾淨、住得安心可找它。</p>
<div class="lrb-meta"><b>地址</b>：高雄市梓官區港七街59號　｜　<b>電話</b>：07-610-7325　｜　<b>專精</b>：裝潢細清・空屋・無毒清潔</div>
<p class="lrb-src">🔗 <a href="https://www.clearjustgood.com.tw/" target="_blank" rel="noopener">看家事幫官網服務項目 →</a></p>

<h2 class="lrb-shop"><span class="no">#7</span>樂家庭清潔・跨高雄台南屏東</h2>
<div class="lrb-card">
  <div class="tc-kicker">高雄・台南・屏東</div>
  <div class="tc-name">樂家庭清潔</div>
  <div class="tc-tag">裝潢細清・定期居家・鐘點</div>
</div>
<p>樂家庭居家清潔服務公司，服務跨高雄／台南／屏東，提供裝潢細清、定期居家與鐘點清潔，採環保清潔用品。需求多元、想用一個窗口跨區配合可考慮。</p>
<div class="lrb-meta"><b>服務區域</b>：高雄／台南／屏東　｜　<b>電話</b>：07-3655029　｜　<b>專精</b>：裝潢細清・定期居家・鐘點</div>
<p class="lrb-src">🔗 <a href="https://www.housework.com.tw/tw" target="_blank" rel="noopener">看樂家庭清潔官網服務項目 →</a></p>

<h2 class="lrb-shop">編輯小結｜怎麼挑最對味</h2>
<p class="lrb-closing">裝潢後與交屋細清，<strong>三峰、萬有、CALLmesis、66</strong> 專精度高；退租與入厝細清，<strong>明潔、家事幫</strong> 很實在；需求多元、想跨區一個窗口搞定，<strong>樂家庭</strong> 方便。無論選哪一家，記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，高雄找裝潢細清就不踩雷。</p>

<div class="lrb-cta">想看更多高雄在地裝潢細清／清潔商家、一次比較？<br>
<a href="https://www.gomag.com.tw/city/kaohsiung/home-service/cleaning" target="_blank" rel="noopener">店家好口碑・高雄清潔／裝潢細清專頁 →</a></div>
</div>
HTML;

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :cover, 'kaohsiung', :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            cover_image=VALUES(cover_image), city_slug='kaohsiung', category_id=VALUES(category_id),
            meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
$stmt->execute([':slug'=>$slug, ':title'=>$title, ':excerpt'=>$excerpt, ':body'=>$body, ':cover'=>$cover,
    ':cat'=>$homeId, ':mt'=>$metaTitle, ':md'=>$metaDesc]);
printf("UPSERT %-32s body %d 字\n", $slug, mb_strlen(strip_tags($body)));
echo "published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
