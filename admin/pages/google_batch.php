<?php
// admin/pages/google_batch.php — 批次找 Google place_id（Super Admin only）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/google_reviews.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('danger', '權限不足');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = 'Google 評價批次設定';
$db = getDB();

// 確保 google_place_id 欄位存在
try {
    $db->exec("ALTER TABLE clients ADD COLUMN google_place_id VARCHAR(200) DEFAULT NULL AFTER google_maps_embed");
} catch (PDOException $e) { /* 已存在 */ }

$apiKey = getPlatformSetting('google_maps_api_key', '');

// 處理批次執行（POST + ?run=1）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['run'] ?? '') === '1') {
    verifyCsrf();
    set_time_limit(600);

    if (!$apiKey) {
        setFlash('danger', '請先在「平台設定」填入 Google Maps API key');
        redirect(BASE_URL . '/admin/pages/google_batch.php');
    }

    // 撈出沒 place_id 的活躍客戶
    $rows = $db->query("
        SELECT id, brand_name, address
        FROM clients
        WHERE is_active=1 AND (google_place_id IS NULL OR google_place_id = '')
        ORDER BY id
    ")->fetchAll();

    header('Content-Type: text/plain; charset=utf-8');
    echo "===== 批次找 Google place_id =====\n\n";
    echo "待處理：" . count($rows) . " 家\n\n";

    $update = $db->prepare("UPDATE clients SET google_place_id=? WHERE id=?");
    $found = 0; $miss = 0; $i = 0;

    foreach ($rows as $r) {
        $i++;
        $placeId = findGooglePlaceId($r['brand_name'], $r['address'] ?? '');
        if ($placeId) {
            $update->execute([$placeId, $r['id']]);
            echo "  ✅ [$i/" . count($rows) . "] {$r['brand_name']} → $placeId\n";
            $found++;
        } else {
            echo "  ❌ [$i/" . count($rows) . "] {$r['brand_name']}（找不到）\n";
            $miss++;
        }
        usleep(200000); // 0.2 秒間隔，避免速率限制
        flush();
    }

    echo "\n===== 完成 =====\n";
    echo "找到：$found 家\n";
    echo "找不到：$miss 家\n";
    exit;
}

// 統計
$total = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_active=1")->fetchColumn();
$withPlaceId = (int)$db->query("SELECT COUNT(*) FROM clients WHERE is_active=1 AND google_place_id IS NOT NULL AND google_place_id != ''")->fetchColumn();
$pending = $total - $withPlaceId;

require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>🌟 Google 評價自動配對</h2></div>
  <div class="card-body">

    <?php if (!$apiKey): ?>
    <div style="background:#fdf2f8; border-left:4px solid var(--danger); padding:14px; border-radius:6px; margin-bottom:16px;">
      ⚠️ 還沒設定 Google Maps API key。請先到
      <a href="<?= BASE_URL ?>/admin/pages/platform.php" style="color:var(--danger); font-weight:700;">平台設定</a>
      填入金鑰。
    </div>
    <?php else: ?>
    <div style="background:#eafaf1; border-left:4px solid #16a34a; padding:14px; border-radius:6px; margin-bottom:16px;">
      ✅ API key 已設定
    </div>
    <?php endif; ?>

    <h3 style="margin-bottom:12px;">📊 進度</h3>
    <div style="background:var(--bg); border-radius:8px; overflow:hidden; height:24px; margin-bottom:8px;">
      <div style="background:var(--primary); height:100%; width:<?= $total ? round($withPlaceId*100/$total) : 0 ?>%; transition:width .3s;"></div>
    </div>
    <p style="color:var(--muted); font-size:.85rem;">
      已配對 <?= $withPlaceId ?> / <?= $total ?> 家（待處理 <?= $pending ?> 家）
    </p>

    <hr style="margin:24px 0; border:none; border-top:1px solid var(--border);">

    <h3 style="margin-bottom:12px;">🚀 批次執行</h3>
    <p style="color:var(--muted); font-size:.9rem; line-height:1.6;">
      系統會用「店名 + 地址」呼叫 Google Places Search 找出對應的 place_id，自動寫入。
      速率：每秒 5 家，預估 <?= ceil($pending / 5) ?> 秒完成。
    </p>
    <p style="color:var(--muted); font-size:.85rem;">
      費用估計：<?= $pending ?> 次 Find Place 呼叫 ≈ US$<?= round($pending * 0.017, 2) ?>（在 Google 每月 $200 免費額度內）
    </p>

    <form method="POST" action="?run=1" target="_blank" onsubmit="return confirm('確定執行？跑完後分頁顯示結果，可以關掉。')">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <button type="submit" class="btn btn-accent" <?= !$apiKey || !$pending ? 'disabled' : '' ?>>
        🔍 開始批次找 place_id（<?= $pending ?> 家）
      </button>
      <?php if (!$pending && $total > 0): ?>
      <span style="margin-left:12px; color:var(--success); font-weight:600;">✅ 全部都已配對</span>
      <?php endif; ?>
    </form>

  </div>
</div>

<div class="card">
  <div class="card-header"><h2>💡 使用流程</h2></div>
  <div class="card-body" style="line-height:1.8; font-size:.9rem;">
    <ol style="padding-left:24px;">
      <li>到 <strong>平台設定</strong> 填入 Google Maps API key</li>
      <li>回來這頁按「批次找 place_id」，系統自動配對 <?= $pending ?> 家</li>
      <li>個別客戶頁可在「店家設定」手動修改 place_id</li>
      <li>店家頁面會自動顯示 Google 評分（24 小時快取，不會吃掉 quota）</li>
    </ol>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
