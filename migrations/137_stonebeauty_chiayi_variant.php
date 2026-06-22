<?php
// ============================================================
// Migration 137 — 旭浪清潔 嘉義城市行銷頁變體 /store/stonebeauty/chiayi
// ------------------------------------------------------------
// 旭浪 (id=42、台南永康基地) 已標 cleaning/home-clean/reno-detail 關鍵字。
// 開嘉義變體（跨縣承接型）：
//   - citycat 店家清單會吃變體（city_slug OR client_city_pages）→ 旭浪自動補上
//     嘉義清潔頁 / 嘉義裝潢細清頁 的 0 店缺口（真客戶，優於 placeholder）。
//   - landing 用「跨縣型範本」角度（為什麼嘉義屋主找台南旭浪跨區 / 跨區流程 /
//     嘉義適合情境 / 報價工期 Q&A），不照搬台南頁換地名（避 doorway 兄弟頁）。
//   - 不能無中生有：只用旭浪可驗證事實（台南永康基地、服務項目、報價透明賣點、
//     主頁 areaServed 已列嘉義縣/市）＋ 嘉義公開地理。不捏造嘉義據點/案例/交通費數字。
//   - 地址用縣市名表服務範圍（沿用 rre/changhua 跨縣承接慣例，無實體據點）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/137_stonebeauty_chiayi_variant.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='stonebeauty'")->fetchColumn();
if (!$cid) { fwrite(STDERR, "找不到 stonebeauty\n"); exit(1); }

$metaTitle = '嘉義清潔公司・嘉義裝潢細清推薦｜旭浪清潔 台南團隊跨區承接';
$metaDesc  = '嘉義清潔公司、嘉義裝潢細清找旭浪清潔。台南永康起家的專業細清團隊跨區服務嘉義市與嘉義縣，新居入住、建案交屋細清、老宅翻新、火災善後都接。人手足、報價透明、驗收完成才算交件。0977-340-903。';
$keywords  = '嘉義清潔公司,嘉義裝潢細清,嘉義細清推薦,嘉義居家清潔,嘉義建案細清,旭浪清潔嘉義';
$address   = '嘉義市・嘉義縣（台南永康基地跨區服務）';

$landing = <<<'HTML'
<style>
.xlcy-wrap{font-family:"Noto Sans TC",sans-serif;color:#2b2620;}
.xlcy-hero{background:linear-gradient(135deg,#1d2b3a,#2e4156);color:#fff;border-radius:14px;padding:34px 26px;margin:6px 0 30px;}
.xlcy-hero .tag{display:inline-block;background:rgba(255,255,255,.16);color:#fff;font-size:.82rem;padding:4px 12px;border-radius:999px;margin-bottom:14px;}
.xlcy-hero h2{color:#fff;font-size:1.55rem;font-weight:900;line-height:1.35;margin:0 0 12px;border:0;padding:0;}
.xlcy-hero p{color:#e8edf3;margin:0 0 18px;font-size:1rem;}
.xlcy-cta{display:inline-block;background:#ff8a3d;color:#1d2b3a;font-weight:800;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:1.02rem;}
.xlcy-wrap h2{font-size:1.32rem;font-weight:900;color:#1d2b3a;margin:34px 0 8px;padding-left:12px;border-left:5px solid #ff8a3d;line-height:1.4;}
.xlcy-wrap h2+.sub{color:#7a726a;margin:0 0 16px;font-size:.95rem;}
.xlcy-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0;}
.xlcy-card{background:#f7f5f1;border:1px solid #e7e2da;border-radius:10px;padding:16px 16px;}
.xlcy-card b{display:block;color:#1d2b3a;font-size:1.02rem;margin-bottom:5px;}
.xlcy-card span{color:#5f574e;font-size:.93rem;line-height:1.7;}
.xlcy-flow{counter-reset:s;margin:16px 0;padding:0;list-style:none;}
.xlcy-flow li{position:relative;padding:8px 0 14px 44px;border-left:2px solid #e7e2da;margin-left:14px;}
.xlcy-flow li:last-child{border-left-color:transparent;}
.xlcy-flow li::before{counter-increment:s;content:counter(s);position:absolute;left:-15px;top:6px;width:28px;height:28px;background:#1d2b3a;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;}
.xlcy-flow li b{color:#1d2b3a;}
.xlcy-qa{margin:8px 0;}
.xlcy-qa .q{font-weight:800;color:#1d2b3a;margin:16px 0 4px;}
.xlcy-qa .a{color:#4f483f;margin:0;}
.xlcy-note{background:#fff7ef;border:1px solid #ffd9b8;border-radius:10px;padding:14px 16px;color:#6b4a28;font-size:.93rem;margin:16px 0;}
.xlcy-links{margin:26px 0 4px;padding:16px;background:#f1f4f7;border-radius:10px;font-size:.95rem;}
.xlcy-links a{color:#1d6fb8;font-weight:700;}
@media(max-width:600px){.xlcy-grid{grid-template-columns:1fr;}.xlcy-hero h2{font-size:1.32rem;}}
</style>

<div class="xlcy-wrap">

  <div class="xlcy-hero">
    <span class="tag">台南永康基地 · 跨區服務嘉義</span>
    <h2>嘉義裝潢細清・嘉義清潔公司，旭浪清潔台南團隊跨區承接</h2>
    <p>新居入住、建案交屋細清、老宅翻新、火災善後，旭浪都接。我們在台南永康起家，現在把同一組人手與分工帶到嘉義市與嘉義縣。報價透明、驗收完成才算交件。</p>
    <a class="xlcy-cta" href="tel:0977340903">📞 撥打 0977-340-903 預約場勘</a>
  </div>

  <h2>為什麼嘉義的屋主、建商會找台南的旭浪跨區</h2>
  <p class="sub">不是離得近就一定合適，找對人手規模和細清經驗更實在。</p>
  <p>嘉義在地專做「裝潢後細清」的大型團隊不算多。遇到整層、整棟交屋這種趕時間又要顧細節的案子，常常卡在人手不夠。旭浪在台南做裝潢細清累積了一套分工，從粉塵、矽利康殘膠到玻璃水痕，每個環節有人盯。台南永康到嘉義走國道車程不長，跨區承接嘉義對我們是日常範圍內的事。</p>
  <div class="xlcy-grid">
    <div class="xlcy-card"><b>人手足、排得動</b><span>建案整棟交屋、趕入住期，需要的是能一次調足人力的團隊，不是一兩個人慢慢清。</span></div>
    <div class="xlcy-card"><b>專做細清</b><span>裝潢後的粉塵殘膠跟一般打掃不同，工序、工具、保護都不一樣，做熟才清得乾淨又不傷材質。</span></div>
    <div class="xlcy-card"><b>報價透明</b><span>場勘後依屋況與坪數報價，含哪些寫清楚，不到現場不亂開均一價。</span></div>
    <div class="xlcy-card"><b>驗收才交件</b><span>清完一起走一遍，櫃內、軌道、玻璃逐項看過，滿意才算完成。</span></div>
  </div>

  <h2>台南到嘉義，跨區服務怎麼進行</h2>
  <p class="sub">流程跟在台南一樣，只是場勘與施作排到嘉義。</p>
  <ol class="xlcy-flow">
    <li><b>電話或 LINE 說明屋況</b>：地點在嘉義哪一區、屋型（透天幾層、新成屋或老宅）、要清的範圍，先抓個方向。</li>
    <li><b>跨區場勘估價</b>：到嘉義現場看屋況，給書面報價，項目與單價列清楚。</li>
    <li><b>確認與排班</b>：談定後排定日期，建案交屋、過年旺季建議提早。</li>
    <li><b>進場施作</b>：地板與家具先做保護，再依細清工序逐區清理。</li>
    <li><b>驗收交件</b>：一起逐項檢查，確認乾淨可入住才收尾。</li>
  </ol>

  <h2>嘉義哪些情況適合找旭浪</h2>
  <p class="sub">嘉義市區到周邊鄉鎮，這幾種狀況我們最常接。</p>
  <div class="xlcy-grid">
    <div class="xlcy-card"><b>太保高鐵特區新成屋</b><span>建案交屋、新居入住前的全屋細清，趕入住期可調足人手。</span></div>
    <div class="xlcy-card"><b>嘉義市東區、西區老宅翻新</b><span>老屋翻修後的舊木作、複雜格局細清，做過老宅比較知道怎麼保護舊建材。</span></div>
    <div class="xlcy-card"><b>朴子、民雄、大林透天</b><span>透天樓層多、含庭院頂樓，估價時說清楚屋況報價才準。</span></div>
    <div class="xlcy-card"><b>建案整棟交屋細清</b><span>整層、整棟交屋的規模案，人手與分工是關鍵。</span></div>
    <div class="xlcy-card"><b>廠房、辦公室</b><span>裝修後進駐前的大面積清理。</span></div>
    <div class="xlcy-card"><b>火災、水損善後</b><span>火災煙燻、淹水後的特殊清潔與復原。</span></div>
  </div>

  <h2>跨區服務常見問題</h2>
  <div class="xlcy-qa">
    <p class="q">跨區到嘉義會不會比較貴？</p>
    <p class="a">費用主要看屋況、坪數和清潔範圍，場勘後報價會列清楚含哪些，現場確認再施作，不會先報個模糊價再加。</p>
    <p class="q">只清一間套房、一層樓也接嗎？</p>
    <p class="a">可以，看實際範圍。整棟建案交屋我們也做，大小案都能談。</p>
    <p class="q">多久能排到？</p>
    <p class="a">一般案子彈性安排；建案集中交屋期和過年前較滿，建議提早預約卡時間。</p>
    <p class="q">細清和一般打掃差在哪？</p>
    <p class="a">細清是裝潢後的深層清理，處理粉塵、矽利康殘膠、玻璃水痕、櫃內五金，做完當天能入住，跟日常打掃不是同一回事。</p>
  </div>

  <div class="xlcy-note">旭浪清潔以台南永康為基地，嘉義為跨區服務範圍。以上為服務內容說明，實際費用與工期以現場場勘估價為準。</div>

  <div class="xlcy-links">
    📍 想多看嘉義在地清潔資訊：
    <a href="https://www.gomag.com.tw/city/chiayi/home-service/cleaning">嘉義清潔公司</a> ·
    <a href="https://www.gomag.com.tw/city/chiayi/home-service/reno-detail">嘉義裝潢細清</a> ·
    <a href="https://www.gomag.com.tw/city/chiayi/home-service">嘉義居家服務總覽</a>
  </div>

</div>
HTML;

$ins = $db->prepare("INSERT INTO client_city_pages
    (client_id, city_slug, city_label, address, store_meta_title, store_meta_desc, store_keywords, landing_extra_content, filter_cases_by_region, is_active, sort_order, created_at)
    VALUES (?, 'chiayi', '嘉義', ?, ?, ?, ?, ?, 0, 1, 0, NOW())
    ON DUPLICATE KEY UPDATE
        city_label=VALUES(city_label), address=VALUES(address),
        store_meta_title=VALUES(store_meta_title), store_meta_desc=VALUES(store_meta_desc),
        store_keywords=VALUES(store_keywords), landing_extra_content=VALUES(landing_extra_content),
        filter_cases_by_region=0, is_active=1");
$ins->execute([$cid, $address, $metaTitle, $metaDesc, $keywords, $landing]);

$row = $db->query("SELECT id, city_slug, city_label, LENGTH(landing_extra_content) lc, is_active FROM client_city_pages WHERE client_id=$cid AND city_slug='chiayi'")->fetch(PDO::FETCH_ASSOC);
echo "✅ 旭浪嘉義變體 #{$row['id']}  city={$row['city_label']}  landing={$row['lc']}B  active={$row['is_active']}\n";
echo "   URL: https://www.gomag.com.tw/store/stonebeauty/chiayi\n";

// 驗證 citycat 嘉義清潔/細清頁會不會列到旭浪
foreach ([['嘉義清潔','cleaning'],['嘉義裝潢細清','reno-detail']] as [$lbl,$svc]) {
    $q = $db->prepare("SELECT cl.brand_name
        FROM clients cl
        JOIN client_service_keywords csk ON csk.client_id = cl.id
        JOIN service_keywords sk ON sk.id = csk.service_keyword_id
        LEFT JOIN client_city_pages ccp ON ccp.client_id = cl.id AND ccp.city_slug='chiayi' AND ccp.is_active=1
        WHERE cl.is_active=1 AND cl.category_id=2 AND sk.slug=?
          AND (cl.city_slug='chiayi' OR ccp.client_id IS NOT NULL)
        GROUP BY cl.id");
    $q->execute([$svc]);
    $names = $q->fetchAll(PDO::FETCH_COLUMN);
    echo "\n== {$lbl}頁 店家清單 ==\n  " . ($names ? implode("、", $names) : "(空)") . "\n";
}
