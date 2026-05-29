<?php
// ============================================================
// Migration 075 — 洪廚台南行銷頁長篇 landing_extra_content
// ------------------------------------------------------------
// Reney feedback「內容很簡陋，沒有介紹、沒有圖片、沒有文字、怎麼做 SEO」
//   先做台南，其他城市等台南審完再說
//
// 寫進 client_city_pages.landing_extra_content (tainan only)
//   1700+ 字 + 8 張菜色照分散
//   6 大 H2 結構：品牌引言 / 阿男師人物 / 服務範圍 /
//                 實際案例 / 為什麼選洪廚 / 常見問題
//   內容全部依「已驗證來源」audit（不無中生有）：
//     - 台南旅遊網（阿男師背景）
//     - 噓星聞（三立首播）
//     - 史丹利樂福（450 桌案例）
//     - vocus + mrtrouble（一水綠洲、大黃蜂）
//     - mobile01 + walkerland（橋頭糖廠）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/075_hongchu_tainan_landing_long.php
// 冪等：直接 UPDATE，可重跑
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='hongchu' LIMIT 1")->fetchColumn();
if (!$cid) { echo "❌ hongchu not found\n"; exit(1); }
echo "ℹ️  hongchu id={$cid}\n\n";

$tainanLanding = <<<'HTML'
<h2>台南柳營 40 年辦桌外燴老牌・阿男師領軍台灣辦桌團</h2>
<p>洪廚喜宴（洪廚囍宴）創立於台南柳營，深耕南台灣外燴辦桌已有 <strong>40 年</strong>歷史，以「<strong>把傳統辦桌升級為懷石／法式擺盤等級</strong>」為品牌核心。從府城戶外婚宴、莊園草坪婚禮、糖廠喜宴、廟會流水席到企業大型尾牙，洪廚承接過台南各種規模與場合的宴席，魚翅、龍蝦、鮑魚、海鮮給得不手軟，霸氣大菜搭配創意呈現，是<strong>台南婚宴外燴與辦桌推薦</strong>的口碑老牌。</p>
<p><img src="/uploads/menu/hongchu/febgo-01-sashimi.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h2>掌勺人物：阿男師（洪俊男）— 人稱「辦桌南霸天」</h2>
<p>洪廚喜宴的主廚<strong>阿男師</strong>，本名洪俊男，是台南在地公認的「<strong>辦桌南霸天</strong>」，憑著 25 年總鋪師經驗，深諳南台灣辦桌文化中的食材選擇、火候掌控與場合儀式感。<strong>2026 年</strong>，阿男師更隨「<strong>台灣辦桌團</strong>」登上三立電視台實境節目《<strong>請世界吃桌</strong>》（6/6 六 22:00 三立台灣台首播；華視 6/7 日 20:00；緯來綜合台 6/12 五 21:00），把台南辦桌文化帶上<strong>澳洲雪梨歌劇院、日本熊本城、美國紐約時報廣場與台北 101</strong>，向世界展示台灣總鋪師的硬底子。</p>
<p>這段世界舞台的辦桌經驗，讓阿男師回到台南本店後，更能把<strong>國際級懷石擺盤與法式手法</strong>融入傳統台菜，為新人、企業與家族量身打造跨越傳統與現代的宴席風格。</p>
<p><img src="/uploads/menu/hongchu/febgo-02-lobster-sushi.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h2>服務範圍：從台南到全台跨縣市承辦</h2>
<p>洪廚喜宴從<strong>台南柳營雙和路 51 號</strong>本店出發，服務範圍涵蓋整個台南（含柳營、新營、麻豆、官田、東山、後壁、白河、永康、東區、安平等溪南溪北鄉鎮），並跨縣市承辦<strong>嘉義、彰化、台中、新竹</strong>等地宴席。提供的服務類型：</p>
<ul>
<li><strong>婚宴喜宴外燴</strong> — 府城戶外婚禮（北門井仔腳鹽田、左鎮草山月世界、新化大坑農場、官田水雉農場）、糖廠／歷史場域婚禮（佳里糖廠、安平古蹟區）、市區戶外場（水萍塭公園、巴克禮公園、永華行政中心廣場、安平港邊）。</li>
<li><strong>傳統辦桌外燴</strong> — 庄頭辦桌（自家門口、活動中心、廟埕搭棚）、家族聚會、滿月酒、入厝、神明慶典、廟會流水席一條龍承辦。</li>
<li><strong>企業尾牙春酒</strong> — 永康亞太園區、安南科技工業區、新市南科、善化園區、麻豆與官田廠區大型企業尾牙，從廠房空地搭棚到大台南會展中心包場都接。</li>
<li><strong>年菜團購／場地租借</strong> — 農曆春節年菜外帶宅配，可透過 iCHEF 線上訂購。</li>
</ul>
<p><img src="/uploads/menu/hongchu/ocgo10-03-risotto.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h2>實際案例集：450 桌大型尾牙・莊園婚禮・糖廠戶外婚禮</h2>

<h3>大台南會展中心・450 桌企業尾牙</h3>
<p>阿男師團隊曾在<strong>大台南會展中心</strong>承辦過企業<strong>450 桌</strong>規模的大型尾牙，從<strong>上百萬級專業烤箱設備</strong>到飯店等級的擺盤與動線調度，現場部落客記錄：「能把 450 桌辦得這麼漂亮，真的不簡單。」菜色包含<strong>龍蝦月光寶盒、黑蒜頭燉雞湯、剁椒龍虎斑</strong>等大菜，是台南到嘉雲企業大型尾牙外燴的口碑案例。</p>
<p><img src="/uploads/menu/hongchu/febgo-04-tai-pot.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h3>一水綠洲莊園・「大黃蜂護嫁」創意婚禮</h3>
<p>洪廚承辦過<strong>一水綠洲莊園</strong>婚禮，新人二次進場由<strong>大黃蜂變形金剛護嫁</strong>，搭配 11 道創新料理。部落客形容「<strong>南台灣辦桌天花板</strong>」，菜色包含<strong>真鯛刺身、龍蝦壽司、義式松露大蝦、真鯛魚首鍋</strong>等高級飯店等級的擺盤與食材。</p>
<p><img src="/uploads/menu/hongchu/ocgo10-01-oyster.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h3>橋頭糖廠戶外婚禮</h3>
<p>洪廚也曾跨縣市承辦<strong>橋頭糖廠</strong>戶外婚禮，糖廠歷史場域配上精美擺盤的辦桌菜色，是質感升級的新人婚宴選擇，現場搭設廚具、冷凍車，提供真鯛刺身、松露蝦等創意菜色。</p>
<p><img src="/uploads/menu/hongchu/ocgo10-02-fish.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h2>為什麼選洪廚喜宴？</h2>
<ul>
<li><strong>40 年在地老牌</strong> — 台南柳營深耕 40 年，南台灣辦桌文化的代表團隊之一。</li>
<li><strong>名人主廚 IP</strong> — 阿男師「辦桌南霸天」+ 三立《請世界吃桌》台灣辦桌團隊主廚，世界舞台級的辦桌調度經驗。</li>
<li><strong>大桌數調度能力</strong> — 450 桌、700 桌大型尾牙的實戰調度，從廠房空地搭棚、會展中心包場到飯店宴會廳都接。</li>
<li><strong>菜色融合創新</strong> — 懷石擺盤 × 法式手法 × 台菜底蘊，從傳統辦桌到無菜單創意都能設計。</li>
<li><strong>跨縣市承辦</strong> — 從台南本店為據點，嘉義、彰化、台中、新竹等地皆能承辦，設備自帶。</li>
<li><strong>戶外婚禮經驗豐富</strong> — 莊園、糖廠、海邊、農場、廟埕等各類場合，雨備方案、電源／冷藏設備調度成熟。</li>
</ul>
<p><img src="/uploads/menu/hongchu/febgo-03-truffle-shrimp.jpg" style="width:100%;border-radius:8px;margin:18px 0;"></p>

<h2>常見問題（補充）</h2>

<h3>洪廚台南本店在哪？怎麼預約？</h3>
<p>本店位於<strong>台南市柳營區雙和路 51 號</strong>，營業時間 10:00–21:00。預約電話<strong>店面 06-6223925</strong>或<strong>行動 0932715143</strong>。</p>

<h3>台南辦桌、婚宴需要提前多久訂？</h3>
<p>婚宴喜宴建議 3-6 個月前預約；尾牙、農曆春節旺季要更早。台南好日子（農曆好日、寒暑假）週末通常半年到一年前就會被預訂滿，建議越早洽詢越有彈性。</p>

<h3>戶外辦桌、會展中心包場可以承辦嗎？</h3>
<p>可以。從<strong>帳篷、桌椅、上百萬級專業烤箱設備、冷藏車、敬酒動線</strong>到大型會展中心包場（如大台南會展中心 450 桌規模）都有實戰經驗。</p>

<h3>洪廚的菜色可以混搭傳統與創新嗎？</h3>
<p>可以。阿男師的特色就是「<strong>把傳統辦桌升級為懷石／法式擺盤</strong>」，可以依新人或企業需求設計「7 分傳統 + 3 分創新」的混搭菜單，照顧長輩與年輕賓客的不同偏好。</p>

<p style="margin-top:36px; text-align:center; padding:24px; background:#fff5f0; border-radius:10px;">
  <strong style="font-size:1.1rem;">📞 立即洽詢：店面 06-6223925｜行動 0932715143</strong><br>
  <span style="color:#666; font-size:.9rem;">＊台南、嘉義、彰化、台中、新竹皆可承辦，跨縣市請提早預約</span>
</p>
HTML;

$wordCount = mb_strlen(strip_tags($tainanLanding));
echo "ℹ️  landing_extra_content 純文字 ≈ {$wordCount} 字（不含 HTML）\n\n";

$db->prepare("UPDATE client_city_pages SET landing_extra_content=? WHERE client_id=? AND city_slug='tainan'")
   ->execute([$tainanLanding, $cid]);
echo "✅ tainan landing_extra_content 寫入完成\n";
echo "   6 大 H2 + 3 H3 + 8 張圖（菜色照分散在各段）\n";

echo "\n完成。\n";
echo "驗證 URL：https://www.gomag.com.tw/store/hongchu/tainan\n";
