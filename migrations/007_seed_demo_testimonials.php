<?php
/**
 * migrations/007_seed_demo_testimonials.php
 *
 * 為 3 個示範客戶 (歡樂牛排 / 濱緻 / 綝綝) 建立 testimonials，
 * 讓他們的店家頁也能顯示「Reviews Summary 五星分布」區塊。
 *
 * 旭浪 (#1) 已有 4 筆 testimonials。
 *
 * 冪等：重跑會 DELETE 該 client 已有的 testimonials 再重灌
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

if (PHP_SAPI !== 'cli') {
    if (($_GET['key'] ?? '') !== 'seed-demo-2026') { http_response_code(403); die('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

$db = getDB();
function out(string $msg): void { echo "$msg\n"; if (PHP_SAPI !== 'cli') flush(); }

$demoTestimonials = [
    // ─── #10 歡樂牛排嘉義店（餐飲）─────────────────
    10 => [
        ['name' => '陳小姐', 'rating' => 5, 'content' => '安格斯沙朗牛排五分熟剛剛好，肉質鮮嫩多汁。飲料吧自助無限續杯，整體 CP 值很高，下次還會再去。'],
        ['name' => '王先生', 'rating' => 5, 'content' => '一家四口週末聚餐很開心。兒童餐有玩具小朋友超愛，上菜速度也很快，服務生態度親切。'],
        ['name' => 'Eric Lin', 'rating' => 4, 'content' => '丁骨牛排 32oz 真的很大塊兩個人吃剛好。唯一可改進是飲料吧的咖啡有點淡，其他都很滿意。'],
        ['name' => '林媽媽', 'rating' => 5, 'content' => '附近用餐首選，平日生意也很穩。雞腿排煎得很酥脆又多汁，焗烤義大利麵起司份量很大方。'],
        ['name' => '張先生', 'rating' => 4, 'content' => '價位中等好接受，環境舒適適合慶生家庭聚餐。預約建議提早 2-3 天，週末很滿。'],
    ],

    // ─── #18 濱緻車體美學（汽車）─────────────────
    18 => [
        ['name' => 'Tony Wu',  'rating' => 5, 'content' => 'Tesla Model 3 全車包 XPEL Ultimate Plus，3 天工期一氣呵成。完工後鏡面超亮，老闆很有耐心解釋每個細節。'],
        ['name' => '李大哥',    'rating' => 5, 'content' => '比較過台南幾家包膜，最後選濱緻是因為老闆親自施工不像有些店外包學徒。BMW M3 鍍膜後光澤超驚艷。'],
        ['name' => 'Coffee Hsu', 'rating' => 5, 'content' => '深度漆面美容做得很細，引擎室、輪胎細節都顧到。10 年保固也讓人安心，推薦給北高雄朋友。'],
        ['name' => '黃先生',    'rating' => 4, 'content' => '隔熱紙施工專業，3M 進口確實有差。價格透明刷卡有 0 利率分期很方便。'],
        ['name' => 'Ken',     'rating' => 5, 'content' => '車主之光！RAV4 來這邊處理刮痕，老闆親自評估報價合理。施工後完全看不出原本的痕跡。'],
        ['name' => '林老闆',    'rating' => 5, 'content' => '經銷商都把客人車送這邊處理，技術跟態度都到位。免費年度檢視服務也是業界少見。'],
    ],

    // ─── #3 綝綝美甲美睫紋繡學院（美容）──────────
    3 => [
        ['name' => '小綠',   'rating' => 5, 'content' => '光療美甲做得很細很自然，師傅真的很有耐心。推薦進階款式 1500 NT 性價比超高，還可以選貼片造型。'],
        ['name' => 'May Chen', 'rating' => 5, 'content' => '6D 美睫嫁接超讚，自然濃密款式我的最愛。維持期約 3-4 週，比之前在別家做的還持久。'],
        ['name' => '王小姐',  'rating' => 4, 'content' => '飄霧眉做完很自然漂亮，1 年多了還很穩定，淡退也均勻沒色塊。'],
        ['name' => 'Vivian',  'rating' => 5, 'content' => '報名美甲乙級證照班，老師很用心一對一教學。同學都考過真的很有效率。'],
        ['name' => '阿樺',   'rating' => 4, 'content' => '環境乾淨整潔，工具消毒都很到位。價格合理推薦給想做光療美甲的姐妹。'],
    ],
];

out("═══ Migration 007: Seed demo testimonials ═══\n");
$inserted = 0;
foreach ($demoTestimonials as $cid => $rows) {
    // 該 client 已有 testimonials → DELETE 重建
    $del = $db->prepare("DELETE FROM testimonials WHERE client_id=?");
    $del->execute([$cid]);

    $name = $db->prepare("SELECT brand_name FROM clients WHERE id=?");
    $name->execute([$cid]);
    $brand = $name->fetchColumn() ?: "(client #$cid)";
    out("─ #$cid $brand ─");

    $stmt = $db->prepare("INSERT INTO testimonials (client_id, reviewer_name, rating, content, source, sort_order, is_active) VALUES (?, ?, ?, ?, 'demo', ?, 1)");
    foreach ($rows as $i => $r) {
        $stmt->execute([$cid, $r['name'], $r['rating'], $r['content'], $i + 1]);
        $inserted++;
    }
    out("  ✅ 加入 " . count($rows) . " 筆 testimonials\n");
}

out("═══════════════════════════════════════");
out("Migration 007 complete. Total inserted: $inserted");
out("─ 各 client testimonials 數：");
foreach ([1, 3, 10, 18] as $cid) {
    $cnt = $db->prepare("SELECT COUNT(*), AVG(rating) FROM testimonials WHERE client_id=? AND is_active=1");
    $cnt->execute([$cid]);
    $row = $cnt->fetch(PDO::FETCH_NUM);
    $name = $db->prepare("SELECT brand_name FROM clients WHERE id=?");
    $name->execute([$cid]);
    out("  #$cid {$name->fetchColumn()}: {$row[0]} 筆，平均 " . number_format((float)$row[1], 2) . " 顆星");
}
