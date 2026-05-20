<?php
/**
 * migrations/011_fix_rre_content.php
 *
 * 修正亞雷清潔（rre, id=215）小官網內容：
 *   1. 補 tagline
 *   2. 修 minisite_meta_desc（原為 "12345677" 占位符）
 *   3. 刪 seo_settings(home)（讓系統 fallback 到 minisite_meta_title）
 *   4. 重整 about_text（h2 + p 拆開，原為整段塞 h2）
 *   5. 補 about_tags（客製 4 個賣點）
 *   6. INSERT 4 筆 services
 *
 * 圖片修正不在這支裡（等 AI 生圖後跑 012）
 *
 * 冪等：重跑會以新值 overwrite，不會重複 INSERT services（先 DELETE 再 INSERT）
 *
 * 用法：
 *   php migrations/011_fix_rre_content.php
 *   或 web: BASE_URL/migrations/011_fix_rre_content.php?key=fix-rre-2026
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

if (PHP_SAPI !== 'cli') {
    if (($_GET['key'] ?? '') !== 'fix-rre-2026') { http_response_code(403); die('forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}

$db = getDB();
function out(string $m): void { echo "$m\n"; if (PHP_SAPI !== 'cli') flush(); }

const CLIENT_ID = 215;
const SLUG = 'rre';

out("═══ Migration 011: 修正 rre 內容（亞雷清潔） ═══\n");

// ───────────────────────────────────────────────────────
// 0. 備份當前狀態到 _backups/
// ───────────────────────────────────────────────────────
$backupDir = __DIR__ . '/../_backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$ts = date('Ymd-His');
$backupFile = "$backupDir/rre_pre_011_$ts.json";

$snapshot = [];
$stmt = $db->prepare("SELECT * FROM clients WHERE id=?");
$stmt->execute([CLIENT_ID]);
$snapshot['client'] = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM seo_settings WHERE client_id=?");
$stmt->execute([CLIENT_ID]);
$snapshot['seo_settings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM services WHERE client_id=?");
$stmt->execute([CLIENT_ID]);
$snapshot['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

file_put_contents($backupFile, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
out("✓ 備份寫入：$backupFile");
out("  - client 欄位：" . count($snapshot['client'] ?? []));
out("  - seo_settings：" . count($snapshot['seo_settings']) . " 筆");
out("  - services：" . count($snapshot['services']) . " 筆\n");

// ───────────────────────────────────────────────────────
// 1. UPDATE clients
// ───────────────────────────────────────────────────────
$newTagline = '台中・彰化裝潢細清專科';

$newMinisiteMetaDesc = '亞雷清潔團隊提供台中、彰化裝潢後細清與新成屋入住前完整深層清潔。從天花板、油漆殘膠、矽利康、廚衛、地板封蠟一條龍處理，搬家前就讓家乾淨到能直接住進去。台中北屯實體店面，台中市區與彰化市、員林、和美一帶都可服務。';

$newAboutText = <<<HTML
<h2>不只做完細清就走</h2>
<p>我們把石材晶化、打蠟、地毯清洗都涵蓋進來，讓客戶交屋後仍能找同一組專業團隊維護。從新成屋的粉塵、木屑、油漆痕跡，到日後的地板保養、地毯深度清潔，一條龍服務、不轉包。</p>
<h3>主力服務</h3>
<ol>
<li><strong>裝潢細清（主推）</strong>：處理新家裝潢後留下的粉塵、木屑、油漆痕跡、玻璃磁磚漆點殘膠、廚衛設備灰塵。</li>
<li><strong>居家清潔</strong>：客廳、臥室、廚房油垢、浴室除霉與防滑、地面/窗戶/家具全面整理；單次大掃除或定期都接。</li>
<li><strong>洗地服務</strong>：專業洗地機 + 環保清潔劑，處理陳年汙漬、油漬積層、人流區黑斑。</li>
<li><strong>地板打蠟保養</strong>：適用木地板、塑膠地板、磁磚，增加保護層、提升光亮度、減少刮痕。</li>
<li><strong>地毯清洗</strong>：深層清潔技術，去除陳年污漬、異味、塵蟎、過敏源。</li>
<li><strong>地板晶化</strong>：大理石、花崗岩、拋光石英磚專業研磨 + 晶化，適合商辦、飯店、餐廳、門市。</li>
</ol>
HTML;

$newAboutTags = json_encode([
    '🏠 裝潢細清專科',
    '💎 石材晶化打蠟',
    '🔁 交屋後長期維護',
    '📋 免費到府估價',
], JSON_UNESCAPED_UNICODE);

$sql = "UPDATE clients SET
    tagline = :tagline,
    minisite_meta_desc = :minisite_meta_desc,
    about_text = :about_text,
    about_tags = :about_tags
    WHERE id = :id";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':tagline' => $newTagline,
    ':minisite_meta_desc' => $newMinisiteMetaDesc,
    ':about_text' => $newAboutText,
    ':about_tags' => $newAboutTags,
    ':id' => CLIENT_ID,
]);
out("✓ UPDATE clients：tagline / minisite_meta_desc / about_text / about_tags");

// ───────────────────────────────────────────────────────
// 2. DELETE seo_settings(home) — 讓 fallback 到 minisite_meta_title
// ───────────────────────────────────────────────────────
$stmt = $db->prepare("DELETE FROM seo_settings WHERE client_id=? AND page_key='home'");
$stmt->execute([CLIENT_ID]);
out("✓ DELETE seo_settings(home)：" . $stmt->rowCount() . " 筆（讓 title fallback 到 minisite_meta_title）");

// ───────────────────────────────────────────────────────
// 3. INSERT services（先清舊的）
// ───────────────────────────────────────────────────────
$db->prepare("DELETE FROM services WHERE client_id=?")->execute([CLIENT_ID]);

$services = [
    [
        'slug' => 'detail-cleaning',
        'name' => '裝潢細清',
        'short_desc' => '新家交屋後粉塵、木屑、油漆痕跡、玻璃磁磚漆點殘膠、廚衛灰塵一次清乾淨',
        'icon' => '🧹',
        'sort_order' => 1,
    ],
    [
        'slug' => 'stone-crystallization',
        'name' => '石材晶化',
        'short_desc' => '大理石、花崗岩、拋光石英磚專業研磨 + 晶化，恢復亮面光澤',
        'icon' => '💎',
        'sort_order' => 2,
    ],
    [
        'slug' => 'floor-waxing',
        'name' => '地板打蠟保養',
        'short_desc' => '木地板、塑膠地板、磁磚增加保護層、提升光亮度、減少刮痕',
        'icon' => '✨',
        'sort_order' => 3,
    ],
    [
        'slug' => 'carpet-cleaning',
        'name' => '地毯清洗',
        'short_desc' => '深層清潔技術，去除陳年污漬、異味、塵蟎、過敏源',
        'icon' => '🧼',
        'sort_order' => 4,
    ],
];

$insertSql = "INSERT INTO services (client_id, slug, name, short_desc, icon, sort_order, is_active)
              VALUES (?, ?, ?, ?, ?, ?, 1)";
$insertStmt = $db->prepare($insertSql);
foreach ($services as $svc) {
    $insertStmt->execute([
        CLIENT_ID,
        $svc['slug'],
        $svc['name'],
        $svc['short_desc'],
        $svc['icon'],
        $svc['sort_order'],
    ]);
}
out("✓ INSERT services：" . count($services) . " 筆\n");

// ───────────────────────────────────────────────────────
// 驗證
// ───────────────────────────────────────────────────────
out("═══ 驗證結果 ═══");
$check = $db->prepare("SELECT tagline, minisite_meta_desc, SUBSTRING(about_text,1,40) AS about_head FROM clients WHERE id=?");
$check->execute([CLIENT_ID]);
$row = $check->fetch(PDO::FETCH_ASSOC);
out("tagline:            " . $row['tagline']);
out("minisite_meta_desc: " . mb_substr($row['minisite_meta_desc'], 0, 50) . '…');
out("about_text 開頭:    " . $row['about_head']);

$seo = $db->prepare("SELECT COUNT(*) FROM seo_settings WHERE client_id=? AND page_key='home'");
$seo->execute([CLIENT_ID]);
out("seo_settings(home) 剩餘筆數: " . $seo->fetchColumn() . " (預期 0)");

$svc = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id=?");
$svc->execute([CLIENT_ID]);
out("services 筆數:      " . $svc->fetchColumn() . " (預期 4)");

out("\n✓ Migration 011 完成");
