<?php
// ============================================================
// Migration 050 — 台中裝潢細清懶人包（圖文版 guide / 升級「精緻編輯卡片」版型）
// ------------------------------------------------------------
// city=taichung, category=home-service(2)。#1 亞雷清潔為 gomag 客戶（台中北屯），
// 其餘 6 家 Google 在地口碑（皆查證、未捏造）。每家連各自來源、CTA 導台中清潔頁。
// 新版型：每家一張卡片（大襯線編號+店名+標籤+資訊+延伸閱讀）；有好圖放 16:9 圖、
// 無好圖用深色品牌標題帶 → 卡片一致。scoped <style> 全 .lrb-g 作用域 + lrb- 前綴避撞 gomag.css。
// 圖轉存 uploads/guides/taichung-fine-clean/（rsync prod+staging）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/050_taichung_fine_clean_guide.php
// 冪等：slug upsert（保留首次 published_at）。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$homeId = (int)$db->query("SELECT id FROM categories WHERE slug='home-service' LIMIT 1")->fetchColumn();

$slug      = 'taichung-fine-clean-recommend';
$title     = '台中裝潢細清懶人包｜2026 嚴選 7 家在地口碑';
$excerpt   = '新成屋交屋、重劃區入厝後的細清怎麼選？店家好口碑精選 7 家台中在地口碑團隊（亞雷、清潔大夫、牧樂…），從裝潢後細清、交屋驗收到居家定期一次看。';
$metaTitle = '台中裝潢細清懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑';
$metaDesc  = '台中裝潢細清推薦 2026！七期、北屯機捷、烏日高鐵重劃區新成屋交屋後的細清這樣選——店家好口碑精選 7 家台中在地口碑團隊，從裝潢後細清、交屋驗收到居家定期一次看。';
$cover     = 'https://www.gomag.com.tw/uploads/guides/taichung-fine-clean/1-aleire.jpg';

$body = <<<'HTML'
<style>
.lrb-g{--ink:#211e1b;--soft:#574f49;--mut:#938a82;--acc:#FF5A36;--line:#ece6e0;--cardbg:#fdfbf9;font-family:'Noto Serif TC','Songti TC',serif;color:var(--ink);line-height:1.95;}
.lrb-g p{margin:0 0 16px;}
.lrb-g .lrb-lead{font-size:1.07rem;color:var(--soft);}
.lrb-g .lrb-rule{display:flex;align-items:center;gap:14px;margin:34px 0 6px;}
.lrb-g .lrb-rule::before,.lrb-g .lrb-rule::after{content:"";height:1px;background:var(--line);flex:1;}
.lrb-g .lrb-rule span{font-family:'Noto Sans TC',sans-serif;font-size:.74rem;letter-spacing:.24em;color:var(--mut);}
.lrb-g .lrb-note{font-family:'Noto Sans TC',sans-serif;background:var(--cardbg);border:1px solid var(--line);border-radius:14px;padding:20px 22px;margin:26px 0 8px;}
.lrb-g .lrb-note h3{font-family:'Noto Serif TC',serif;font-size:1.06rem;margin:0 0 10px;letter-spacing:1.5px;}
.lrb-g .lrb-note ol{margin:0;padding-left:1.25em;font-size:.94rem;color:var(--soft);line-height:1.95;}
.lrb-g .lrb-card{border:1px solid var(--line);border-radius:18px;background:var(--cardbg);margin:32px 0;overflow:hidden;box-shadow:0 14px 34px -26px rgba(60,40,30,.5);}
.lrb-g .lrb-pic{width:100%;height:228px;object-fit:cover;display:block;background:#efeae6;}
.lrb-g .lrb-band{height:124px;background:linear-gradient(120deg,#2b2724 0%,#4b423c 60%,#5d5048 100%);display:flex;align-items:center;justify-content:center;position:relative;}
.lrb-g .lrb-band::after{content:"店家好口碑・精選";position:absolute;bottom:11px;font-family:'Noto Sans TC',sans-serif;font-size:.62rem;letter-spacing:.3em;color:rgba(255,255,255,.4);}
.lrb-g .lrb-band .bn{font-family:'Noto Serif TC',serif;color:#fff;font-size:1.5rem;font-weight:900;letter-spacing:4px;}
.lrb-g .lrb-body{padding:22px 24px 24px;}
.lrb-g .lrb-head{display:flex;align-items:baseline;gap:12px;}
.lrb-g .lrb-no{font-family:'Noto Serif TC',serif;font-size:2rem;font-weight:900;color:var(--acc);line-height:.9;}
.lrb-g .lrb-nm{font-size:1.26rem;font-weight:900;letter-spacing:.5px;}
.lrb-g .lrb-area{margin-left:auto;font-family:'Noto Sans TC',sans-serif;font-size:.73rem;color:var(--mut);border:1px solid var(--line);border-radius:999px;padding:3px 11px;white-space:nowrap;}
.lrb-g .lrb-tags{font-family:'Noto Sans TC',sans-serif;margin:13px 0 14px;display:flex;flex-wrap:wrap;gap:7px;}
.lrb-g .lrb-tags span{font-size:.75rem;color:#b14a2f;background:#fdeee9;border-radius:999px;padding:4px 11px;}
.lrb-g .lrb-desc{font-size:1.01rem;color:#3f3a36;margin:0 0 16px;line-height:1.9;}
.lrb-g .lrb-info{font-family:'Noto Sans TC',sans-serif;display:flex;flex-wrap:wrap;gap:6px 24px;border-top:1px solid var(--line);padding-top:14px;font-size:.9rem;color:var(--soft);}
.lrb-g .lrb-info b{color:var(--ink);font-weight:700;}
.lrb-g .lrb-src{margin:14px 0 0;}
.lrb-g .lrb-src a{font-family:'Noto Sans TC',sans-serif;font-size:.9rem;color:var(--acc);font-weight:700;text-decoration:none;}
.lrb-g .lrb-sum{font-size:1.3rem;font-weight:900;margin:42px 0 8px;letter-spacing:1px;}
.lrb-g .lrb-closing{font-size:1.04rem;color:var(--soft);}
.lrb-g .lrb-cta{border:1px solid var(--acc);background:#fff7f4;border-radius:16px;padding:24px;margin:34px 0 0;text-align:center;font-family:'Noto Sans TC',sans-serif;}
.lrb-g .lrb-cta p{margin:0 0 14px;color:var(--soft);font-size:.98rem;}
.lrb-g .lrb-cta a{display:inline-block;background:var(--acc);color:#fff;padding:12px 28px;border-radius:999px;font-weight:700;text-decoration:none;letter-spacing:1px;}
</style>
<div class="lrb-g">
<p class="lrb-lead">台中是中台灣建案重鎮——七期、單元二、北屯機捷特區、太平與烏日高鐵特區重劃區一案接一案，新成屋交屋潮持續。裝潢完工後滿屋的粉塵、矽利康殘膠與油漆點，得靠專業「裝潢細清」才能達到可入住的乾淨。這次店家好口碑彙整台中在地口碑，精選 <strong>7 家清潔團隊</strong>，從裝潢後細清、交屋驗收到居家定期一次看完。</p>

<div class="lrb-rule"><span>編輯精選 ・ 7 家</span></div>

<div class="lrb-note">
  <h3>編輯筆記｜挑台中裝潢細清看 5 點</h3>
  <ol>
    <li>先分需求：裝潢後細清／交屋驗收／退租／定期居家。</li>
    <li>到府或視訊估價、報價透明（坪數 × 單價或包項）。</li>
    <li>細節到不到位：踢腳板、矽利康縫、插座周圍、門框上方、燈溝窗軌、玻璃水漬。</li>
    <li>看在地口碑與實際案例照。</li>
    <li>是否含驗收複清（不滿意再補清）。</li>
  </ol>
</div>

<div class="lrb-card">
  <img class="lrb-pic" src="https://www.gomag.com.tw/uploads/guides/taichung-fine-clean/1-aleire.jpg" alt="亞雷清潔｜台中北屯裝潢後細清成果" loading="lazy">
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">01</span><span class="lrb-nm">亞雷清潔</span><span class="lrb-area">台中・北屯</span></div>
    <div class="lrb-tags"><span>環保清潔劑</span><span>客製化方案</span><span>入住級乾淨</span></div>
    <p class="lrb-desc">台中北屯在地團隊，專業師傅搭配環保清潔劑與客製化方案，把建材粉塵、油漆痕跡、玻璃與水泥殘點等工程殘留物清乾淨，連機坑都鑽進去清，裝潢後達到「可入住」等級。</p>
    <div class="lrb-info"><span><b>電話</b>　0926-655-333（雷先生）</span><span><b>專精</b>　裝潢後細清・客製化・入住級</span></div>
    <p class="lrb-src"><a href="https://novgo11.pixnet.net/blog/posts/13361812921" target="_blank" rel="noopener">看實際體驗心得（部落客 艾薇。到處走）→</a></p>
  </div>
</div>

<div class="lrb-card">
  <div class="lrb-band"><span class="bn">清潔大夫</span></div>
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">02</span><span class="lrb-nm">清潔大夫</span><span class="lrb-area">台中・彰化・南投</span></div>
    <div class="lrb-tags"><span>日式工法</span><span>師傅受訓 3–4 年</span><span>除霉去垢</span></div>
    <p class="lrb-desc">師傅經 3–4 年訓練，擅長細部除霉去垢，採日式清潔工法，從燈具、窗軌到衛浴接縫都仔細處理，完工成果令人眼睛一亮。</p>
    <div class="lrb-info"><span><b>電話</b>　04-2463-2483</span><span><b>專精</b>　裝潢細清・空屋・細部除霉</span></div>
    <p class="lrb-src"><a href="https://www.dr-cleaning.com/" target="_blank" rel="noopener">看清潔大夫官網服務 →</a></p>
  </div>
</div>

<div class="lrb-card">
  <img class="lrb-pic" src="https://www.gomag.com.tw/uploads/guides/taichung-fine-clean/3-mule.jpg" alt="牧樂居家清潔｜台中裝潢後細清成果" loading="lazy">
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">03</span><span class="lrb-nm">牧樂居家清潔</span><span class="lrb-area">台中・南投・彰化・苗栗</span></div>
    <div class="lrb-tags"><span>職業認證</span><span>無毒清潔劑</span><span>居家友善</span></div>
    <p class="lrb-desc">員工經職業認證、嚴格把關，使用安全無毒清潔劑，裝潢後細清與居家清潔都做，特別適合有小孩、寵物、講究健康的家庭。</p>
    <div class="lrb-info"><span><b>電話</b>　0909-509-278</span><span><b>專精</b>　裝潢細清・居家・無毒清潔</span></div>
    <p class="lrb-src"><a href="https://mlclean.com.tw/" target="_blank" rel="noopener">看牧樂清潔官網服務 →</a></p>
  </div>
</div>

<div class="lrb-card">
  <div class="lrb-band"><span class="bn">久承清潔環保</span></div>
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">04</span><span class="lrb-nm">久承清潔環保</span><span class="lrb-area">台中・彰投・雲苗</span></div>
    <div class="lrb-tags"><span>交屋清潔</span><span>除膠・水泥殘留</span><span>廠辦也接</span></div>
    <p class="lrb-desc">主打裝潢後細清與交屋清潔，專處理除膠、水泥殘留，連辦公室洗地打蠟、廠房清潔都接，交屋前一次到位。</p>
    <div class="lrb-info"><span><b>電話</b>　0912-600-007</span><span><b>專精</b>　交屋細清・除膠水泥・廠辦</span></div>
    <p class="lrb-src"><a href="https://www.jioucheng.com.tw/about-us.html" target="_blank" rel="noopener">看久承清潔官網服務 →</a></p>
  </div>
</div>

<div class="lrb-card">
  <img class="lrb-pic" src="https://www.gomag.com.tw/uploads/guides/taichung-fine-clean/5-shiguang.jpg" alt="釋廣清潔｜台中裝潢後細清成果" loading="lazy">
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">05</span><span class="lrb-nm">釋廣清潔</span><span class="lrb-area">台中及中部</span></div>
    <div class="lrb-tags"><span>隱藏粉塵深層</span><span>燈溝・窗軌</span><span>入住前全檢</span></div>
    <p class="lrb-desc">專注「看不見的地方」——燈溝、窗軌、櫥櫃內部的隱藏粉塵深層清潔，入住前再做一次全屋檢查，確保乾淨度。</p>
    <div class="lrb-info"><span><b>電話</b>　0985-818-232</span><span><b>專精</b>　裝潢後細清・隱藏粉塵・空屋</span></div>
    <p class="lrb-src"><a href="https://www.clean-no1.com.tw/clear.html" target="_blank" rel="noopener">看釋廣清潔官網服務 →</a></p>
  </div>
</div>

<div class="lrb-card">
  <div class="lrb-band"><span class="bn">潔管家企業社</span></div>
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">06</span><span class="lrb-nm">潔管家企業社</span><span class="lrb-area">大台中</span></div>
    <div class="lrb-tags"><span>三道式清潔</span><span>網友推薦</span><span>細節到位</span></div>
    <p class="lrb-desc">大台中網友推薦度高，採「三道式清潔」——先除塵、後除膠、再擦拭，櫃體、玻璃、窗框等細節都顧到。</p>
    <div class="lrb-info"><span><b>電話</b>　0905-757-766</span><span><b>專精</b>　裝潢清潔・三道式工序</span></div>
    <p class="lrb-src"><a href="https://www.jiehousekeeper.com/news/view/3" target="_blank" rel="noopener">看潔管家官網服務 →</a></p>
  </div>
</div>

<div class="lrb-card">
  <div class="lrb-band"><span class="bn">清潔特攻隊</span></div>
  <div class="lrb-body">
    <div class="lrb-head"><span class="lrb-no">07</span><span class="lrb-nm">清潔特攻隊</span><span class="lrb-area">台中・潭子</span></div>
    <div class="lrb-tags"><span>透明報價</span><span>陪同驗收</span><span>建材保護</span></div>
    <p class="lrb-desc">主打透明報價、專業工具、陪同驗收與建材保護，售後服務完整，適合第一次找裝潢細清、最怕被亂加價的屋主。</p>
    <div class="lrb-info"><span><b>電話</b>　0916-776-761</span><span><b>專精</b>　裝潢細清・陪同驗收・售後</span></div>
    <p class="lrb-src"><a href="https://www.super-cleaning-team.com.tw/blog/txgdetailedclean" target="_blank" rel="noopener">看清潔特攻隊官網服務 →</a></p>
  </div>
</div>

<h2 class="lrb-sum">編輯小結｜怎麼挑最對味</h2>
<p class="lrb-closing">重劃區交屋細清，<strong>亞雷、釋廣、久承</strong> 專精度高；講究工法與健康，<strong>清潔大夫、牧樂</strong> 細緻；想要透明報價與陪同驗收，<strong>潔管家、清潔特攻隊</strong> 安心。無論選哪一家，記得先到府或視訊估價、把含哪些細清項目白紙黑字寫清楚，台中找裝潢細清就不踩雷。</p>

<div class="lrb-cta">
  <p>想看更多台中在地裝潢細清／清潔商家、一次比較？</p>
  <a href="https://www.gomag.com.tw/city/taichung/home-service/cleaning" target="_blank" rel="noopener">店家好口碑・台中清潔／裝潢細清專頁 →</a>
</div>
</div>
HTML;

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :cover, 'taichung', :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            cover_image=VALUES(cover_image), city_slug='taichung', category_id=VALUES(category_id),
            meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
$stmt->execute([':slug'=>$slug, ':title'=>$title, ':excerpt'=>$excerpt, ':body'=>$body, ':cover'=>$cover,
    ':cat'=>$homeId, ':mt'=>$metaTitle, ':md'=>$metaDesc]);
printf("UPSERT %-32s body %d 字\n", $slug, mb_strlen(strip_tags($body)));
echo "published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
