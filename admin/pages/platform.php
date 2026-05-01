<?php
// admin/pages/platform.php — 平台全域設定（Super Admin only）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if (currentAdmin()['role'] !== 'super') {
    setFlash('danger', '權限不足');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '平台設定';

// 儲存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $keys = [
        'platform_name', 'platform_tagline', 'main_meta_title', 'main_meta_desc',
        'main_og_image', 'ga_tracking_id', 'gtm_id', 'search_console_verification',
        'contact_email', 'contact_phone', 'footer_text',
        'google_maps_api_key',
    ];
    foreach ($keys as $k) {
        setPlatformSetting($k, trim($_POST[$k] ?? ''));
    }
    setFlash('success', '✅ 平台設定已儲存');
    redirect(BASE_URL . '/admin/pages/platform.php');
}

// 讀目前值
$s = function($k, $d='') { return getPlatformSetting($k, $d); };

require_once __DIR__ . '/../includes/layout_head.php';
?>

<form method="POST">
<input type="hidden" name="_token" value="<?= csrfToken() ?>">

<!-- 平台基本資訊 -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>🏢 平台基本資訊</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>平台名稱</label>
      <input type="text" name="platform_name" class="form-control" value="<?= h($s('platform_name', '店家好口碑')) ?>">
    </div>
    <div class="form-group-admin">
      <label>平台 Tagline</label>
      <input type="text" name="platform_tagline" class="form-control" value="<?= h($s('platform_tagline')) ?>">
    </div>
    <div class="form-group-admin">
      <label>客服 Email</label>
      <input type="email" name="contact_email" class="form-control" value="<?= h($s('contact_email')) ?>">
    </div>
    <div class="form-group-admin">
      <label>客服電話</label>
      <input type="text" name="contact_phone" class="form-control" value="<?= h($s('contact_phone')) ?>">
    </div>
    <div class="form-group-admin">
      <label>Footer 文字（可選）</label>
      <textarea name="footer_text" class="form-control" rows="2"><?= h($s('footer_text')) ?></textarea>
    </div>
  </div>
</div>

<!-- 主站 SEO -->
<div class="card" style="margin-bottom:20px; border-left:4px solid #16a34a;">
  <div class="card-header"><h2>🔍 主站 SEO 設定</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>首頁 Meta Title</label>
      <input type="text" name="main_meta_title" class="form-control" maxlength="60"
             value="<?= h($s('main_meta_title')) ?>">
      <div class="hint">主站首頁、分類頁等預設 title。50-60 字</div>
    </div>
    <div class="form-group-admin">
      <label>首頁 Meta Description</label>
      <textarea name="main_meta_desc" class="form-control" rows="2" maxlength="160"><?= h($s('main_meta_desc')) ?></textarea>
      <div class="hint">120-160 字</div>
    </div>
    <div class="form-group-admin">
      <label>主站分享圖（OG Image）</label>
      <input type="text" name="main_og_image" class="form-control"
             placeholder="https://... 或 /assets/images/og.jpg"
             value="<?= h($s('main_og_image')) ?>">
      <div class="hint">FB/LINE 分享主站連結時顯示的圖。建議 1200×630</div>
    </div>
  </div>
</div>

<!-- 流量追蹤 -->
<div class="card" style="margin-bottom:20px; border-left:4px solid #2563eb;">
  <div class="card-header"><h2>📊 流量追蹤 & SEO 工具</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>Google Analytics 4 追蹤 ID</label>
      <input type="text" name="ga_tracking_id" class="form-control"
             placeholder="G-XXXXXXXXXX"
             value="<?= h($s('ga_tracking_id')) ?>">
      <div class="hint">在 GA4 後台「資料串流」可以找到。填入後自動嵌入所有頁面</div>
    </div>
    <div class="form-group-admin">
      <label>Google Tag Manager 容器 ID（可選）</label>
      <input type="text" name="gtm_id" class="form-control"
             placeholder="GTM-XXXXXXX"
             value="<?= h($s('gtm_id')) ?>">
      <div class="hint">如果用 GTM 管理多個追蹤碼填這裡（GA、Pixel 等）</div>
    </div>
    <div class="form-group-admin">
      <label>Google Search Console 驗證碼</label>
      <input type="text" name="search_console_verification" class="form-control"
             placeholder="驗證 metatag 的 content 部分"
             value="<?= h($s('search_console_verification')) ?>">
      <div class="hint">在 GSC「擁有權驗證 → HTML 標籤」拿到的字串。填入後 GSC 即可驗證</div>
    </div>
  </div>
</div>

<!-- Google Maps API -->
<div class="card" style="margin-bottom:20px; border-left:4px solid var(--accent);">
  <div class="card-header"><h2>🌟 Google Maps 評價串接</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>Google Maps API Key</label>
      <input type="text" name="google_maps_api_key" class="form-control"
             placeholder="AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
             value="<?= h($s('google_maps_api_key')) ?>">
      <div class="hint">
        申請：<a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> →
        建專案 → 啟用 <code>Places API (New)</code> → 建立 API 金鑰。
        建議限制：HTTP 來源網域 <code>*.gomag.com.tw/*</code>
      </div>
    </div>
    <div style="background:var(--bg); padding:12px; border-radius:6px; font-size:.85rem; line-height:1.7;">
      📌 <strong>填入金鑰後：</strong>到
      <a href="<?= BASE_URL ?>/admin/pages/google_batch.php" style="color:var(--primary); font-weight:700;">Google 評價批次設定</a>
      自動找出所有店家的 place_id，店家頁就會顯示真實 Google 評分。
    </div>
  </div>
</div>

<button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:1rem;">💾 儲存平台設定</button>
</form>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
