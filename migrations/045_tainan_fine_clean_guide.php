<?php
// ============================================================
// Migration 045 — 台南裝潢細清 7 家懶人包（攻略文 / 圖文版）
// ------------------------------------------------------------
// city=tainan, category=home-service(2)。圖文式「店家好口碑攻略」：
// 3 家 gomag 客戶（旭浪/廣達/享清）+ 4 家在地口碑（倍亮/臥龍閣/沐沐/彭婉如）。
// 每家連到不同部落客/官方來源；文末導流台南清潔子服務頁。
// 圖片轉存 gomag 自家圖床 uploads/guides/tainan-fine-clean/（避防盜連），
//   圖檔需 rsync 到 prod + staging 兩 docroot。
// body_html 內嵌 scoped <style>（.lrb- 前綴避免撞 gomag.css）；h1 不重複（hero 已有 title）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/045_tainan_fine_clean_guide.php
// 冪等：以 slug upsert（保留首次 published_at）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$slug       = 'tainan-fine-clean-recommend';
$title      = '台南裝潢細清推薦｜2026 嚴選 7 家在地口碑';
$excerpt    = '新成屋交屋、中古翻修、退租後的細清怎麼選？店家好口碑精選 7 家台南在地口碑團隊，從裝潢後細清、交屋驗收到冷氣水塔專項深洗一次看。';
$metaTitle  = '台南裝潢細清推薦｜2026 嚴選 7 家在地口碑｜店家好口碑';
$metaDesc   = '台南裝潢細清推薦 2026！新成屋交屋、中古翻修、退租後的細清這樣選——店家好口碑精選 7 家台南在地口碑團隊（廣達、享清、臥龍閣、旭浪…），從裝潢後細清、交屋驗收到冷氣水塔專項深洗一次看。';
$cover      = 'https://www.gomag.com.tw/uploads/guides/tainan-fine-clean/1-xulang.jpg';

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
.lrb-guide .lrb-card{width:100%;border-radius:12px;margin:14px 0 6px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;background:#faf6f4;border:1px solid #f0e3dd;text-align:center;padding:42px 20px;box-sizing:border-box;}
.lrb-guide .lrb-card .tc-kicker{font-family:'Noto Sans TC',sans-serif;font-size:.72rem;letter-spacing:3px;color:#FF5A36;font-weight:700;}
.lrb-guide .lrb-card .tc-name{font-weight:900;font-size:1.5rem;color:#1a1a1a;letter-spacing:1px;}
.lrb-guide .lrb-card .tc-tag{font-family:'Noto Sans TC',sans-serif;font-size:.95rem;color:#555;letter-spacing:2px;}
.lrb-guide .lrb-closing{font-size:1.02rem;}
.lrb-guide .lrb-cta{background:#fff5f2;border:1px solid #FF5A36;border-radius:12px;padding:18px 20px;margin:30px 0 0;text-align:center;font-family:'Noto Sans TC',sans-serif;}
.lrb-guide .lrb-cta a{display:inline-block;margin-top:8px;background:#FF5A36;color:#fff;padding:11px 26px;border-radius:999px;font-weight:700;text-decoration:none;}
</style>
<div class="lrb-guide">
<p>裝潢完工、新成屋交屋、中古屋翻修後，滿屋的粉塵、矽利康殘膠、油漆點與施工髒污，自己怎麼擦都擦不完——這時就需要專業的「裝潢細清」。台南找細清，最怕「報價不清、邊邊角角沒顧到」。這次店家好口碑彙整在地口碑與實際體驗，精選 <strong>7 家台南清潔團隊</strong>，從裝潢後細清、交屋驗收到退租、定期居家都涵蓋，帶你依需求一次看完。</p>

<div class="lrb-tip"><b>編輯小提醒｜挑台南裝潢細清看 5 點</b><br>
① 先分需求（裝潢後細清／交屋驗收／退租／定期居家）　② 到府或視訊估價、報價透明（坪數×單價或包項）　③ 細節到不到位（踢腳板、矽利康縫、插座周圍、門框上方、玻璃水漬）　④ 在地口碑與實際案例照　⑤ 是否含驗收複清。</div>

<h2 class="lrb-shop"><span class="no">#1</span>旭浪清潔公司・綜合居家清潔</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/guides/tainan-fine-clean/1-xulang.jpg" alt="旭浪清潔公司｜台南綜合居家清潔" loading="lazy">
<p>旭浪是不少台南家庭的口袋名單，主打綜合居家與宅修清潔，裝潢後、入厝前的全室清理也能一手包辦。最適合想找一個固定窗口、從交屋細清到日後定期打掃都長期配合的家庭。</p>
<div class="lrb-meta"><b>服務區域</b>：台南　｜　<b>專精</b>：綜合居家・宅修・入厝細清</div>
<p class="lrb-src">🔗 <a href="https://decgo.pixnet.net/blog/posts/13334808671" target="_blank" rel="noopener">看實際體驗心得（部落客 走跳台南人）→</a></p>

<h2 class="lrb-shop"><span class="no">#2</span>廣達清潔公司・裝潢細清專門</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/guides/tainan-fine-clean/2-guangda.jpg" alt="廣達清潔公司｜台南裝潢細清" loading="lazy">
<p>新成屋交屋、中古翻修、辦公室與廠房交屋的「細清」首選。制度化團隊、現場估價透明，連踢腳板縫隙、插座周圍、門框上方這些容易被忽略的角落都顧得到，玻璃與浴室磁磚接縫也處理得相當細膩。</p>
<div class="lrb-meta"><b>地址</b>：台南市中華北路二段283號　｜　<b>電話</b>：06-2523773　｜　<b>專精</b>：裝潢細清・交屋・辦公室廠房</div>
<p class="lrb-src">🔗 <a href="https://novgo11.pixnet.net/blog/posts/13362503946" target="_blank" rel="noopener">看實際體驗心得（部落客 艾薇。到處走）→</a></p>

<h2 class="lrb-shop"><span class="no">#3</span>倍亮清潔・專項深洗一條龍</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/guides/tainan-fine-clean/3-beiliang.jpg" alt="倍亮清潔｜台南床墊污漬深度清洗" loading="lazy">
<p>裝潢後若還想把冷氣、水塔、抽油煙機、洗衣機內桶一起深洗，倍亮的「一條龍」深度清洗很到位。廁所水垢、床墊污漬、浴廁防霉防滑也都接——細清完再把這些專項一次解決，入住更安心。</p>
<div class="lrb-meta"><b>服務區域</b>：台南　｜　<b>專精</b>：專項清洗（冷氣／水塔／抽油煙機）・浴廁水垢防霉</div>
<p class="lrb-src">🔗 <a href="https://aronrandom.pixnet.net/blog/posts/4044248430" target="_blank" rel="noopener">看實際體驗心得（部落客 史丹利樂福）→</a></p>

<h2 class="lrb-shop"><span class="no">#4</span>享清清潔工程・細清／搬家一站式</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/clients/legacy/legacy_cleaningcompany2.jpg" alt="享清清潔工程｜台南裝潢細清搬家" loading="lazy">
<p>裝潢細清、居家清潔、搬家搬運一站包辦，最適合「裝潢完要細清、東西又要搬」的人。從入厝前的全室細清到搬家整理都能配合，省去到處找不同師傅的麻煩。</p>
<div class="lrb-meta"><b>地址</b>：台南市仁安八街5號　｜　<b>電話</b>：0975-721-796　｜　<b>專精</b>：居家・裝潢細清・搬家</div>
<p class="lrb-src">🔗 <a href="https://www.facebook.com/TNH.EnjoyClean/" target="_blank" rel="noopener">看享清清潔 FB 粉專 →</a></p>

<h2 class="lrb-shop"><span class="no">#5</span>臥龍閣清潔・新營口碑團隊</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/guides/tainan-fine-clean/5-wolongge.jpg" alt="臥龍閣清潔｜台南新營裝潢後細清退租" loading="lazy">
<p>新營一帶找裝潢後細清，臥龍閣是在地口碑之選。裝潢後細清、退租、廠房到定期居家都接，還有除塵蟎水洗、地板打蠟、石材美容鍍膜。人手與設備齊全，連大面積、卡了多年的頑垢也能漂亮收尾。</p>
<div class="lrb-meta"><b>服務區域</b>：台南新營一帶　｜　<b>專精</b>：裝潢細清・退租・定期居家・除塵蟎・石材鍍膜</div>
<p class="lrb-src">🔗 <a href="https://augo08.pixnet.net/blog/posts/13344508764" target="_blank" rel="noopener">看實際體驗心得（部落客 艾瑪娘的小日子）→</a></p>

<h2 class="lrb-shop"><span class="no">#6</span>沐沐清潔・大台南專業團隊</h2>
<img class="lrb-thumb" src="https://www.gomag.com.tw/uploads/guides/tainan-fine-clean/6-mumu.jpg" alt="沐沐清潔｜大台南裝潢空屋細清" loading="lazy">
<p>服務涵蓋大台南的清潔團隊，裝潢、空屋、店面、辦公室、租屋套房都做，員工定期受訓、溝通順暢。裝潢後與空屋入厝前的細清，找它格外省心。</p>
<div class="lrb-meta"><b>服務區域</b>：大台南　｜　<b>電話</b>：06-2990076　｜　<b>專精</b>：裝潢・空屋・套房清潔</div>
<p class="lrb-src">🔗 <a href="https://www.mumuclean.com.tw/products-1.html" target="_blank" rel="noopener">看沐沐清潔官網服務項目 →</a></p>

<h2 class="lrb-shop"><span class="no">#7</span>彭婉如文教基金會・制度化家事服務</h2>
<div class="lrb-card">
  <div class="tc-kicker">NPO ｜ 培訓派遣</div>
  <div class="tc-name">彭婉如文教基金會</div>
  <div class="tc-tag">制度化家事服務・長期穩定安心</div>
</div>
<p>細清入住之後，想要制度健全、長期穩定的家事維護，彭婉如文教基金會的家事管理是老牌之選。家事服務員經過培訓與派遣，制度與保障完整，最適合需要定期、講求安心的家庭。</p>
<div class="lrb-meta"><b>服務範圍</b>：台南／全台　｜　<b>專精</b>：家事管理・居家清潔（NPO 培訓派遣）</div>
<p class="lrb-src">🔗 <a href="https://www.pwr.org.tw" target="_blank" rel="noopener">看彭婉如文教基金會 官網 →</a></p>

<h2 class="lrb-shop">編輯小結｜怎麼挑最對味</h2>
<p class="lrb-closing">裝潢後與交屋細清，<strong>廣達、享清、臥龍閣、旭浪</strong> 專精度高；裝潢後想連冷氣、水塔等一起深洗，<strong>倍亮、沐沐</strong> 很全面；入住後想要長期穩定的家事維護，<strong>彭婉如</strong> 是安心之選。無論選哪一家，記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，台南找裝潢細清就不踩雷。</p>

<div class="lrb-cta">想看更多台南在地裝潢細清／清潔商家、一次比較？<br>
<a href="https://www.gomag.com.tw/city/tainan/home-service/cleaning" target="_blank" rel="noopener">店家好口碑・台南清潔／裝潢細清專頁 →</a></div>
</div>
HTML;

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :cover, 'tainan', :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            cover_image=VALUES(cover_image), city_slug='tainan', category_id=VALUES(category_id),
            meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
$stmt->execute([':slug'=>$slug, ':title'=>$title, ':excerpt'=>$excerpt, ':body'=>$body, ':cover'=>$cover,
    ':cat'=>$homeId, ':mt'=>$metaTitle, ':md'=>$metaDesc]);
printf("UPSERT %-30s body %d 字\n", $slug, mb_strlen(strip_tags($body)));
echo "published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
