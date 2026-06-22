<?php
// ============================================================
// Migration 140 — 旭浪台南主頁：部落客卡連結文字「完整食記」→「完整心得」
// ------------------------------------------------------------
// 接續 139（Reney 授權）：剩 3 張部落客卡的連結文字「看 XXX 完整食記 →」是餐飲
// 模板殘留（食記＝吃的），清潔頁不合適 → 改「完整心得」。
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/140_stonebeauty_blogcard_text.php
// 冪等：str_replace，重跑安全。
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
$html = str_replace('完整食記', '完整心得', $html, $cnt);
echo "「完整食記」→「完整心得」：" . ($cnt ? "✅ 改 {$cnt} 處" : "⏭ 未找到（可能已改）") . "\n";

if ($html !== $orig) {
    @mkdir(__DIR__ . '/../_backups', 0775, true);
    file_put_contents(__DIR__ . '/../_backups/stonebeauty_landing_pre140_' . date('Ymd-His') . '.html', $orig);
    $u = $db->prepare("UPDATE clients SET landing_extra_content=? WHERE id=?");
    $u->execute([$html, $cid]);
    echo "→ 已寫回（備份已存）。殘留『食記』：" . substr_count($html, '食記') . "\n";
} else {
    echo "→ 無變更\n";
}
echo "驗證： https://www.gomag.com.tw/store/stonebeauty\n";
