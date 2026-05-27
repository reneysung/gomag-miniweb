<?php
// ============================================================
// Migration 052 — 台南居酒屋懶人包（圖文版 guide / 精緻卡片版型）
// ------------------------------------------------------------
// city=tainan, category=food(1)。3 家 gomag 客戶（原佃/おかん/小椿，連 gomag 店家頁）
// + 4 家在地口碑 Google 補（衫樽/失態/聚聚/芙蓉，連部落客體驗文）。
// 沿用 050/051 精緻卡片版型（scoped .lrb-g + lrb- 前綴）。
// 杉樽/聚聚/芙蓉 有實照，失態圖被防盜連→band；客戶 3 家全 band。
// CTA → /city/tainan/food/japanese（居酒屋折進日料頁）。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/052_tainan_izakaya_guide.php
// 冪等：slug upsert。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
$fid = (int)$db->query("SELECT id FROM categories WHERE slug='food' LIMIT 1")->fetchColumn();
$IMG = 'https://www.gomag.com.tw/uploads/guides/';

$STYLE = <<<'CSS'
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
CSS;

function lrbCard(array $c): string {
    [$no,$name,$area,$tags,$desc,$info,$src,$srcText,$img] = $c + [8=>null];
    $top = $img
        ? '<img class="lrb-pic" src="' . $img . '" alt="' . $name . '｜台南居酒屋" loading="lazy">'
        : '<div class="lrb-band"><span class="bn">' . $name . '</span></div>';
    $tagH = ''; foreach ($tags as $t) $tagH .= '<span>' . $t . '</span>';
    $infoH = ''; foreach ($info as $i) $infoH .= '<span>' . $i . '</span>';
    return '<div class="lrb-card">' . $top
        . '<div class="lrb-body">'
        . '<div class="lrb-head"><span class="lrb-no">' . $no . '</span><span class="lrb-nm">' . $name . '</span><span class="lrb-area">' . $area . '</span></div>'
        . '<div class="lrb-tags">' . $tagH . '</div>'
        . '<p class="lrb-desc">' . $desc . '</p>'
        . '<div class="lrb-info">' . $infoH . '</div>'
        . '<p class="lrb-src"><a href="' . $src . '" target="_blank" rel="noopener">' . $srcText . '</a></p>'
        . '</div></div>';
}

$cards = [
  ['01','原佃燒烤居酒屋','台南・東區',['燒肉燒烤','居酒屋風','深夜小聚'],
    '東區裕農路一帶在地居酒屋，主打燒肉燒烤與居酒屋小酌氛圍，朋友聚會、下班後二攤都對味。',
    ['<b>電話</b>　0931-550-995','<b>地址</b>　台南市東區裕農路621巷62-10號','<b>專精</b>　燒肉燒烤・日式居酒屋'],
    'https://www.gomag.com.tw/store/izakaya2','看原佃店家頁與評價 →', null],
  ['02','おかんとO KAN Do 大眾酒場','台南・中西區',['大眾酒場','日式居酒屋','保安路'],
    '中西區保安路上的日式大眾酒場，輕鬆隨興的居酒屋氛圍，下班後想小酌一杯、跟朋友隨意聚聚的口袋名單。',
    ['<b>電話</b>　06-2236-221','<b>地址</b>　台南市中西區保安路8號','<b>專精</b>　日式居酒屋・大眾酒場'],
    'https://www.gomag.com.tw/store/izakaya1','看おかん店家頁與評價 →', null],
  ['03','小椿食堂 日式料理居酒屋','台南・東區',['日式料理','居酒屋','東寧路巷弄'],
    '東寧路巷內的日式料理居酒屋，主打家庭式日料＋小酌，環境溫馨、適合三五好友想安靜吃喝聊天的場合。',
    ['<b>電話</b>　06-2232-598','<b>地址</b>　台南市東區東寧路120巷11號','<b>專精</b>　日式料理・居酒屋'],
    'https://www.gomag.com.tw/store/beerhall01','看小椿店家頁與評價 →', null],
  ['04','衫樽串燒居酒屋','台南・中西區',['老宅木屋','串燒炸物','深夜營業'],
    '海安路一帶的老宅木屋日式居酒屋，串燒、炸物、丼飯、關東煮一應俱全，從傍晚開到深夜，氛圍濃濃日式風情。',
    ['<b>電話</b>　06-2213-751','<b>地址</b>　台南市中西區忠明街10號','<b>專精</b>　串燒・炸物・丼飯・關東煮'],
    'https://lyes.tw/shanzun-%E8%A1%AB%E6%A8%BD%E4%B8%B2%E7%87%92/','看實際體驗心得（部落客 涼子是也）→', $IMG.'tainan-izakaya/4-shanzun.jpg'],
  ['05','失態炭火手作 Shitai YAKI TORI','台南・北區',['備長炭','氣派質感','串燒海鮮'],
    '北區海安路上的氣派質感居酒屋，使用備長炭手作串燒、無煙無臭，提供海鮮與獨創料理，適合稍微正式一點的聚會。',
    ['<b>電話</b>　06-3583-288','<b>地址</b>　台南市北區海安路三段735號','<b>專精</b>　備長炭串燒・海鮮'],
    'https://travelearth195.com/shitai/','看實際體驗心得（部落客 1000CC 去旅行）→', null],
  ['06','聚聚台南店・燒鳥専門おちば屋','台南・安平',['大阪燒鳥專門','鹽烤醬烤','日本道地'],
    '來自大阪的雞肉串燒專門店，店長赴日學藝、完整復刻日本燒鳥的鹽烤與醬烤技術與獨家醬汁，安平想吃道地燒鳥就找它。',
    ['<b>電話</b>　06-299-2546','<b>地址</b>　台南市安平區建平路528號','<b>專精</b>　雞肉串燒・大阪燒鳥'],
    'https://tiyama.net/jujutainan/','看實際體驗心得（部落客 緹雅瑪）→', $IMG.'tainan-izakaya/6-juju.jpg'],
  ['07','芙蓉鳥燒','台南・中西區',['赤崁樓旁','老字號十年+','日本人愛來'],
    '赤崁樓旁老字號日式居酒屋，老闆赴日學料理、經營十多年，連日本人都會來小酌，座位不多務必提早訂位。',
    ['<b>電話</b>　06-2210-271','<b>地址</b>　台南市中西區赤嵌街31號','<b>專精</b>　日式串燒・燒鳥・清酒'],
    'https://decing.tw/tainan-hasuyak/','看實際體驗心得（部落客 熱血玩台南）→', $IMG.'tainan-izakaya/7-furong.jpg'],
];

$cityName = '台南';
$tip = '<div class="lrb-note"><h3>編輯筆記｜挑'.$cityName.'居酒屋看 5 點</h3><ol>'
  . '<li>先分情境：個人小酌／二三人小聚／團體包桌／喜慶宴客。</li>'
  . '<li>訂位文化重：熱門店常滿、旺日週末務必提早訂位。</li>'
  . '<li>串燒類型與酒類選擇：純燒鳥／串燒+炸物+丼飯／清酒梅酒選項，依口味挑。</li>'
  . '<li>座位形式：吧台位（單人或小聚）vs 桌椅（多人），先問清楚。</li>'
  . '<li>看真實體驗（在地部落客＋日本人會不會來），氛圍最準。</li>'
  . '</ol></div>';

$cardsH = ''; foreach ($cards as $c) $cardsH .= lrbCard($c);

$lead = '台南夜晚，找個老宅日式居酒屋小酌一杯、來點現烤串燒，是府城生活最對味的時光。從中西區海安路、保安路的老宅居酒屋，到東區裕農路、安平的燒鳥專門店，這次店家好口碑彙整台南在地口碑，精選 <strong>7 家居酒屋</strong>，無論朋友聚會、深夜食堂或一個人想喝點清酒，都找得到對味的去處。';
$closing = '想要老宅日式氛圍找 <strong>衫樽、芙蓉、おかん</strong>；現烤燒鳥首選 <strong>聚聚、失態</strong>；燒烤居酒屋風格選 <strong>原佃、小椿</strong>。台南居酒屋多集中在中西區與東區，記得提早訂位、確認禁菸區與座位形式。府城深夜，總有一家對味的居酒屋等你。';

$body = $STYLE
  . '<div class="lrb-g">'
  . '<p class="lrb-lead">' . $lead . '</p>'
  . '<div class="lrb-rule"><span>編輯精選 ・ 7 家</span></div>'
  . $tip . $cardsH
  . '<h2 class="lrb-sum">編輯小結｜怎麼挑最對味</h2>'
  . '<p class="lrb-closing">' . $closing . '</p>'
  . '<div class="lrb-cta"><p>想看更多台南在地日料／居酒屋商家、一次比較？</p>'
  . '<a href="https://www.gomag.com.tw/city/tainan/food/japanese" target="_blank" rel="noopener">店家好口碑・台南日料／居酒屋專頁 →</a></div>'
  . '</div>';

$slug = 'tainan-izakaya-recommend';
$title = '台南居酒屋懶人包｜2026 嚴選 7 家在地口碑';
$excerpt = '台南夜晚找居酒屋小酌？店家好口碑精選 7 家台南在地居酒屋（日式串燒／燒鳥／老宅木屋），府城聚會、深夜食堂一次看。';
$metaTitle = '台南居酒屋懶人包｜2026 嚴選 7 家在地口碑｜店家好口碑';
$metaDesc = '台南居酒屋推薦 2026！中西區、東區、安平、北區深夜居酒屋這樣挑——店家好口碑精選 7 家台南在地居酒屋（原佃／おかん／小椿／衫樽／失態／聚聚／芙蓉），日式串燒、燒鳥、老宅木屋一次看。';
$cover = $IMG . 'tainan-izakaya/4-shanzun.jpg';

$sql = "INSERT INTO guides (slug, title, excerpt, body_html, cover_image, city_slug, category_id, meta_title, meta_desc, status, published_at)
        VALUES (:slug, :title, :excerpt, :body, :cover, 'tainan', :cat, :mt, :md, 'published', NOW())
        ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt), body_html=VALUES(body_html),
            cover_image=VALUES(cover_image), city_slug='tainan', category_id=VALUES(category_id),
            meta_title=VALUES(meta_title), meta_desc=VALUES(meta_desc),
            status='published', published_at=COALESCE(published_at, NOW())";
$stmt = $db->prepare($sql);
$stmt->execute([':slug'=>$slug, ':title'=>$title, ':excerpt'=>$excerpt, ':body'=>$body, ':cover'=>$cover,
    ':cat'=>$fid, ':mt'=>$metaTitle, ':md'=>$metaDesc]);
printf("UPSERT %-30s body %d 字\n", $slug, mb_strlen(strip_tags($body)));
echo "published 攻略文總數：" . $db->query("SELECT COUNT(*) FROM guides WHERE status='published'")->fetchColumn() . "\n";
