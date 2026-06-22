<?php
// ============================================================
// Migration 139 — 旭浪台南主頁：移除嘉義部落客卡 + 修 gallery「用餐」殘字
// ------------------------------------------------------------
// 接續 138 的反競食收尾（Reney 授權處理）：
//   1. 「部落客真實分享」區的嘉義卡（鮪魚肚 BLOGGER：嘉義裝潢細清│細清前後
//      差異看得見│旭浪清潔公司 → beerbelly.com.tw/cleaning/）整張移除，
//      讓台南頁的部落客區全為台南（4 張 → 3 張，其餘 3 張本就台南）。
//      ★ 不竄改他人文章標題（無中生有原則）→ 用「移除」而非改字。
//   2. gallery 小標殘留「用餐」（餐飲模板複製殘字，清潔頁不該有）→ 修為「拍攝」。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/139_stonebeauty_tainan_blogcard_typo.php
// 冪等：找不到就跳過並 warn；重跑安全。
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$cid = (int)$db->query("SELECT id FROM clients WHERE slug='stonebeauty'")->fetchColumn();
if (!$cid) { fwrite(STDERR, "找不到 stonebeauty\n"); exit(1); }

$len = (int)$db->query("SELECT LENGTH(landing_extra_content) FROM clients WHERE id=$cid")->fetchColumn();
$html = '';
for ($i = 0; $i < $len; $i += 4000) {
    $s = $db->prepare("SELECT SUBSTRING(landing_extra_content,?,4000) FROM clients WHERE id=$cid");
    $s->execute([$i + 1]);
    $html .= (string)$s->fetchColumn();
}
$orig = $html;
$cards0 = substr_count($html, 'class="blog-card"');

// 1. 移除嘉義 BLOGGER 卡（regex：唯一 platform=BLOGGER 且含該嘉義 h3）
$html = preg_replace(
    '/\s*<div class="blog-card">\s*<span class="platform">BLOGGER<\/span>.*?嘉義裝潢細清│細清前後差異看得見.*?<\/div>/su',
    '',
    $html,
    1,
    $cntCard
);
echo "1. 移除嘉義部落客卡：" . ($cntCard ? "✅ 移除 {$cntCard} 張" : "⏭ 未找到（可能已移除）") . "\n";

// 2. gallery 小標「用餐」→「拍攝」
$html = str_replace('部落客實地用餐拍攝｜台南裝潢細清實戰', '部落客實地拍攝｜台南裝潢細清實戰', $html, $cntTypo);
echo "2. 修 gallery「用餐」殘字：" . ($cntTypo ? "✅ 改 {$cntTypo} 處" : "⏭ 未找到（可能已修）") . "\n";

if ($html !== $orig) {
    @mkdir(__DIR__ . '/../_backups', 0775, true);
    file_put_contents(__DIR__ . '/../_backups/stonebeauty_landing_pre139_' . date('Ymd-His') . '.html', $orig);
    $u = $db->prepare("UPDATE clients SET landing_extra_content=? WHERE id=?");
    $u->execute([$html, $cid]);
    $cards1 = substr_count($html, 'class="blog-card"');
    echo "→ 已寫回（備份已存）。blog-card {$cards0} → {$cards1}　嘉義整頁 " . substr_count($orig, '嘉義') . " → " . substr_count($html, '嘉義') . "\n";
} else {
    echo "→ 無變更（可能已套用過）\n";
}
echo "驗證： https://www.gomag.com.tw/store/stonebeauty\n";
