<?php
// migrations/068_oishii_taichung_souvenir_seo.php
// 奧喜小官網 SEO 強化：以「台中伴手禮」為主關鍵字
//   - clients.minisite_meta_title + minisite_meta_desc 加入「台中伴手禮」
//   - seo_settings 表 per-page 設 4 個獨家 title/desc/og_title
//     (home / services / cases / testimonials)
//
// CLI only: HTTP_HOST=www.gomag.com.tw php migrations/068_oishii_taichung_souvenir_seo.php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
const OISHII_ID = 220;

echo "=== Migration 068: 奧喜「台中伴手禮」SEO 強化 ===\n\n";

// ── 1. UPDATE clients.minisite_meta_title / minisite_meta_desc ────
$miniTitle = '台中伴手禮推薦｜奧喜長崎蛋糕・北屯松竹路日式長崎蛋糕本店';
$miniDesc  = '台中伴手禮首選 — 奧喜長崎蛋糕，2020 年自綏遠路遷至北屯松竹路三段 22 號。最單純雞蛋、糖、麵粉、麥芽手工現烤日式長崎蛋糕，不添加化學馨香。彌月禮盒、商務拜訪、節慶送禮首選，全台宅配。';

$db->prepare("UPDATE clients SET minisite_meta_title=?, minisite_meta_desc=? WHERE id=?")
   ->execute([$miniTitle, $miniDesc, OISHII_ID]);
echo "✓ Updated clients.minisite_meta_title + minisite_meta_desc\n\n";

// ── 2. 寫 seo_settings 4 個 per-page ─────────────────────
$pages = [
    'home' => [
        'meta_title' => '台中伴手禮推薦｜奧喜長崎蛋糕・北屯松竹路日式長崎蛋糕本店',
        'meta_desc'  => '台中伴手禮首選 — 奧喜長崎蛋糕北屯松竹路本店。四種純粹原料（雞蛋、糖、麵粉、麥芽）手工現烤，不添加化學馨香。彌月禮盒、商務拜訪首選，宅配全台。',
        'og_title'   => '奧喜長崎蛋糕｜台中伴手禮・日式長崎蛋糕本店',
    ],
    'services' => [
        'meta_title' => '商品一覽｜奧喜長崎蛋糕・台中伴手禮 7 種規格 NT$ 135 起',
        'meta_desc'  => '奧喜長崎蛋糕完整商品：5/8/12/16/24 片長崎蛋糕、奧喜燒、小巧禮盒。NT$ 135 至 NT$ 560 多種規格滿足個人嚐鮮、家庭分享、商務送禮、彌月禮盒需求。台中伴手禮首選。',
        'og_title'   => '奧喜長崎蛋糕商品一覽｜台中伴手禮 7 規格',
    ],
    'cases' => [
        'meta_title' => '關於奧喜長崎蛋糕｜2020 北屯本舖・台中伴手禮老舖品牌故事',
        'meta_desc'  => '奧喜長崎蛋糕品牌故事：「奧喜」取自日文「おいしい」，2020 年 12 月自綏遠路遷至台中北屯松竹路三段 22 號。誠信為本、踏實為重的職人精神，四種純粹原料、不添加化學馨香的台中伴手禮老舖。',
        'og_title'   => '關於奧喜長崎蛋糕｜台中伴手禮老舖',
    ],
    'testimonials' => [
        'meta_title' => '顧客分享｜奧喜長崎蛋糕・台中伴手禮真實評價・部落客分享',
        'meta_desc'  => '奧喜長崎蛋糕真實顧客分享 — 飛天璇、VIVIYU、海綿飽飽等公開部落格文章評價。台中伴手禮口碑驗證，吃過的真實體驗。閱讀原文連結至各部落客原始文章。',
        'og_title'   => '奧喜長崎蛋糕顧客分享｜台中伴手禮真實評價',
    ],
];

// 先清舊的，再寫新的（idempotent）
$db->prepare("DELETE FROM seo_settings WHERE client_id=?")->execute([OISHII_ID]);

$ins = $db->prepare("INSERT INTO seo_settings (client_id, page_key, meta_title, meta_desc, og_title) VALUES (?, ?, ?, ?, ?)");
foreach ($pages as $pk => $p) {
    $ins->execute([OISHII_ID, $pk, $p['meta_title'], $p['meta_desc'], $p['og_title']]);
    printf("  ✓ %-15s %d 字\n", $pk, mb_strlen($p['meta_title']));
}

echo "\n=== 驗證 ===\n";
$r = $db->prepare("SELECT page_key, LEFT(meta_title, 60) AS title FROM seo_settings WHERE client_id=? ORDER BY id");
$r->execute([OISHII_ID]);
foreach ($r->fetchAll() as $row) printf("  %-15s %s\n", $row['page_key'], $row['title']);

echo "\n✅ Migration 068 完成\n";
