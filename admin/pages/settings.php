<?php
// admin/pages/settings.php  ─  基本資訊設定
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = '基本資訊設定';
$clientId  = getCurrentClientId() ?? 1;
$db = getDB();

// 儲存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fields = [
        'brand_name'             => trim($_POST['brand_name'] ?? ''),
        'tagline'                => trim($_POST['tagline'] ?? ''),
        'industry'               => trim($_POST['industry'] ?? ''),
        'category_id'            => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
        'phone'                  => trim($_POST['phone'] ?? ''),
        'business_hours'         => trim($_POST['business_hours'] ?? ''),
        'email'                  => trim($_POST['email'] ?? ''),
        'address'                => trim($_POST['address'] ?? ''),
        'external_website_url'   => trim($_POST['external_website_url'] ?? ''),
        'about_text'             => trim($_POST['about_text'] ?? ''),
        'landing_extra_content'  => trim($_POST['landing_extra_content'] ?? ''),
        'has_minisite'           => isset($_POST['has_minisite']) ? 1 : 0,
        'legacy_store_id'        => trim($_POST['legacy_store_id'] ?? ''),
        'google_maps_embed'      => trim($_POST['google_maps_embed'] ?? ''),
        'google_place_id'        => trim($_POST['google_place_id'] ?? ''),
        // SEO 欄位
        'store_meta_title'       => trim($_POST['store_meta_title'] ?? ''),
        'store_meta_desc'        => trim($_POST['store_meta_desc'] ?? ''),
        'store_keywords'         => trim($_POST['store_keywords'] ?? ''),
        'store_og_image'         => trim($_POST['store_og_image'] ?? ''),
    ];

    // Logo 上傳
    if (!empty($_FILES['logo']['name'])) {
        $path = uploadImage($_FILES['logo'], 'brand');
        if ($path) $fields['logo_path'] = $path;
    }
    // Hero 圖片上傳
    if (!empty($_FILES['hero_image']['name'])) {
        $path = uploadImage($_FILES['hero_image'], 'brand');
        if ($path) $fields['hero_image_path'] = $path;
    }

    // Hero Stats（JSON）
    $heroStats = [];
    $statValues = $_POST['stat_value'] ?? [];
    $statLabels = $_POST['stat_label'] ?? [];
    for ($i = 0; $i < count($statValues); $i++) {
        $v = trim($statValues[$i] ?? '');
        $l = trim($statLabels[$i] ?? '');
        if ($v && $l) $heroStats[] = ['value' => $v, 'label' => $l];
    }
    if ($heroStats) {
        $fields['hero_stats'] = json_encode($heroStats, JSON_UNESCAPED_UNICODE);
    }

    // About Tags（JSON）
    $aboutTags = [];
    $tagInputs = $_POST['about_tag'] ?? [];
    foreach ($tagInputs as $tag) {
        $t = trim($tag);
        if ($t) $aboutTags[] = $t;
    }
    if ($aboutTags) {
        $fields['about_tags'] = json_encode($aboutTags, JSON_UNESCAPED_UNICODE);
    }

    $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($fields)));
    $stmt = $db->prepare("UPDATE clients SET $sets WHERE id = :id");
    $stmt->execute(array_merge($fields, ['id' => $clientId]));

    setFlash('success', '✅ 基本資訊已儲存！');
    redirect(BASE_URL . '/admin/pages/settings.php?saved=1');
}

$client = getClientData($clientId);

// 載入所有分類給下拉選單
$categories = $db->query("SELECT id, name, icon, slug FROM categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll();

// 預覽 URL（依環境正確切換）
$previewSub = $client['subdomain'] ?? $client['slug'];

// 行銷頁：LOCAL/STAGING 用 path-based，PROD 用 pretty URL
if (IS_PROD) {
    $previewStoreUrl = 'https://www.gomag.com.tw/store/' . urlencode($previewSub);
    $previewMiniUrl  = 'https://' . urlencode($previewSub) . '.gomag.com.tw/';
} else {
    // LOCAL: 用 query string；STAGING: 用 .htaccess pretty URL（同主機）
    $previewStoreUrl = IS_LOCAL
        ? BASE_URL . '/store.php?sub=' . urlencode($previewSub)
        : BASE_URL . '/store/' . urlencode($previewSub);
    // mini-site：staging 沒有 wildcard 子網域，永遠用 path-based
    $previewMiniUrl = BASE_URL . '/site/index.php?sub=' . urlencode($previewSub);
}

require_once __DIR__ . '/../includes/layout_head.php';
?>

<!-- ═══════ 頂端快捷預覽列 ═══════ -->
<div style="background:linear-gradient(135deg,#0f766e,#0d5c63);color:#fff;padding:16px 20px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:.95rem;font-weight:700;">編輯中：<?= h($client['brand_name'] ?? '─') ?></div>
    <div style="font-size:.8rem;opacity:.85;margin-top:2px;">改完按下方「💾 儲存」後立刻按「🌐 預覽」看效果</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="<?= h($previewStoreUrl) ?>" target="_blank" rel="noopener"
       style="background:#fbbf24;color:#0f172a;padding:10px 18px;border-radius:8px;font-weight:700;font-size:.9rem;text-decoration:none;">
      📢 預覽行銷頁
    </a>
    <?php if (!empty($client['has_minisite'])): ?>
    <a href="<?= h($previewMiniUrl) ?>" target="_blank" rel="noopener"
       style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);color:#fff;padding:10px 18px;border-radius:8px;font-weight:700;font-size:.9rem;text-decoration:none;">
      🌐 預覽小官網
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['saved'])): ?>
<div style="background:#dcfce7;border:1.5px solid #16a34a;border-radius:8px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
  <div style="color:#166534;font-weight:700;">✅ 已儲存！馬上查看：</div>
  <div style="display:flex;gap:8px;">
    <a href="<?= h($previewStoreUrl) ?>" target="_blank" rel="noopener"
       style="background:#16a34a;color:#fff;padding:8px 16px;border-radius:6px;font-weight:600;font-size:.85rem;text-decoration:none;">
      📢 開啟行銷頁
    </a>
    <?php if (!empty($client['has_minisite'])): ?>
    <a href="<?= h($previewMiniUrl) ?>" target="_blank" rel="noopener"
       style="background:#0891b2;color:#fff;padding:8px 16px;border-radius:6px;font-weight:600;font-size:.85rem;text-decoration:none;">
      🌐 開啟小官網
    </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="_token" value="<?= csrfToken() ?>">

<!-- ═══════ 平台設定（主站分類、小官網開關、外部官網）═══════ -->
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--accent);">
  <div class="card-header">
    <h2>🌐 平台設定（主站列表 & 子網域）</h2>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <!-- 分類 -->
      <div class="form-group-admin">
        <label>主站分類 *</label>
        <select name="category_id" class="form-control">
          <option value="">— 請選擇分類 —</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"
            <?= ($client['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
            <?= h($cat['icon']) ?> <?= h($cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div class="hint">主站 www.gomag.com.tw 列表頁的分類</div>
      </div>

      <!-- 舊 gomag 編號 -->
      <div class="form-group-admin">
        <label>舊 gomag 店家編號（搬遷對照用）</label>
        <input type="text" name="legacy_store_id" class="form-control"
               placeholder="例：062051129"
               value="<?= h($client['legacy_store_id'] ?? '') ?>">
        <div class="hint">如有舊網址 SEO，填這裡方便對照（沒有可空）</div>
      </div>
    </div>

    <!-- 小官網開關 -->
    <div class="form-group-admin" style="background:var(--bg);padding:14px;border-radius:8px;border:1.5px solid var(--border);margin-top:10px;">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;">
        <input type="checkbox" name="has_minisite" value="1"
               <?= !empty($client['has_minisite']) ? 'checked' : '' ?>
               style="width:20px;height:20px;cursor:pointer;">
        <span>啟用子網域小官網（xxx.gomag.com.tw 完整 mini-site）</span>
      </label>
      <div class="hint" style="margin-top:6px;margin-left:30px;">
        ☑ 啟用 → 子網域顯示完整 mini-site（含服務、案例、評價）<br>
        ☐ 不啟用 → 子網域自動跳到外部官網或主站行銷頁
      </div>
    </div>

    <!-- 外部官網 -->
    <div class="form-group-admin" style="margin-top:10px;">
      <label>客戶自有獨立官網 URL（可選）</label>
      <input type="url" name="external_website_url" class="form-control"
             placeholder="https://xulang-cleaning.com.tw"
             value="<?= h($client['external_website_url'] ?? '') ?>">
      <div class="hint">如果客戶已有獨立官網，填這裡。主站行銷頁會顯示「前往官網」按鈕指過去。</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

  <!-- 基本資料 -->
  <div class="card">
    <div class="card-header"><h2>🏢 品牌資料</h2></div>
    <div class="card-body">
      <div class="form-group-admin">
        <label>品牌名稱 *</label>
        <input type="text" name="brand_name" class="form-control" required
               value="<?= h($client['brand_name']) ?>">
      </div>
      <div class="form-group-admin">
        <label>品牌標語</label>
        <input type="text" name="tagline" class="form-control"
               value="<?= h($client['tagline']) ?>" placeholder="例：專業到位，潔淨生活每一天">
      </div>
      <div class="form-group-admin">
        <label>業種 / 行業類別</label>
        <input type="text" name="industry" class="form-control"
               value="<?= h($client['industry']) ?>" placeholder="例：居家清潔">
      </div>
      <div class="form-group-admin">
        <label>聯絡電話</label>
        <input type="text" name="phone" class="form-control"
               value="<?= h($client['phone']) ?>">
      </div>
      <div class="form-group-admin">
        <label>Email</label>
        <input type="email" name="email" class="form-control"
               value="<?= h($client['email']) ?>">
      </div>
      <div class="form-group-admin">
        <label>地址</label>
        <input type="text" name="address" class="form-control"
               value="<?= h($client['address']) ?>">
      </div>
    </div>
  </div>

  <!-- 圖片上傳 -->
  <div class="card">
    <div class="card-header"><h2>🖼️ 品牌圖片</h2></div>
    <div class="card-body">
      <div class="form-group-admin">
        <label>品牌 Logo</label>
        <?php if ($client['logo_path']): ?>
          <div style="margin-bottom:8px;">
            <img src="<?= BASE_URL . '/' . h($client['logo_path']) ?>"
                 class="img-preview" style="width:auto;height:60px;object-fit:contain;">
          </div>
        <?php endif; ?>
        <img id="logo_preview" style="display:none;max-height:60px;margin-bottom:8px;" alt="預覽">
        <input type="file" name="logo" class="form-control" accept="image/*"
               onchange="previewImage(this,'logo_preview')">
        <div class="hint">建議尺寸：300×100px，PNG 透明背景</div>
      </div>

      <div class="form-group-admin">
        <label>Hero Banner 主圖</label>
        <?php if ($client['hero_image_path']): ?>
          <div style="margin-bottom:8px;">
            <img src="<?= BASE_URL . '/' . h($client['hero_image_path']) ?>"
                 class="img-preview" style="width:100%;height:80px;object-fit:cover;">
          </div>
        <?php endif; ?>
        <img id="hero_preview" style="display:none;width:100%;max-height:80px;object-fit:cover;margin-bottom:8px;border-radius:6px;" alt="預覽">
        <input type="file" name="hero_image" class="form-control" accept="image/*"
               onchange="previewImage(this,'hero_preview')">
        <div class="hint">建議尺寸：1920×800px，JPG</div>
      </div>
    </div>
  </div>

</div>

<!-- Hero 統計數字 -->
<?php
$currentStats = !empty($client['hero_stats']) ? json_decode($client['hero_stats'], true) : [
    ['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>'']
];
// 確保至少 4 組
while (count($currentStats) < 4) $currentStats[] = ['value'=>'','label'=>''];
$currentTags = !empty($client['about_tags']) ? json_decode($client['about_tags'], true) : ['','','',''];
while (count($currentTags) < 4) $currentTags[] = '';
?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>📊 首頁 Hero 統計數字</h2></div>
  <div class="card-body">
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:16px">
      首頁大圖區塊下方的 4 組數字，例如「20年 / 施工經驗」「1000+ / 完工案場」。留空則使用業種預設值。
    </p>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
      <?php foreach ($currentStats as $i => $st): ?>
      <div style="background:var(--bg);border-radius:10px;padding:14px;border:1.5px solid var(--border)">
        <div class="form-group-admin" style="margin-bottom:8px">
          <label style="font-size:.75rem">數值 <?= $i+1 ?></label>
          <input type="text" name="stat_value[]" class="form-control" placeholder="20年"
                 value="<?= h($st['value'] ?? '') ?>" style="font-weight:800;font-size:1.1rem;text-align:center">
        </div>
        <div class="form-group-admin" style="margin-bottom:0">
          <label style="font-size:.75rem">標籤</label>
          <input type="text" name="stat_label[]" class="form-control" placeholder="施工經驗"
                 value="<?= h($st['label'] ?? '') ?>" style="text-align:center;font-size:.85rem">
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- 關於我們標籤 -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>🏷️ 關於我們 — 亮點標籤</h2></div>
  <div class="card-body">
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:16px">
      「關於我們」區塊下方的標籤（含 emoji），例如「🏭 工廠直營價格實惠」。留空則使用業種預設值。
    </p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <?php foreach ($currentTags as $i => $tag): ?>
      <input type="text" name="about_tag[]" class="form-control" placeholder="🏭 工廠直營價格實惠"
             value="<?= h($tag) ?>">
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- 營業時間 -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>🕐 營業時間</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>營業時間（純文字描述即可）</label>
      <textarea name="business_hours" class="form-control" rows="3" placeholder="例：週一～週六 09:00-18:00&#10;週日公休"><?= h($client['business_hours'] ?? '') ?></textarea>
      <div class="hint">會顯示在主站行銷頁的「營業資訊」區塊</div>
    </div>
  </div>
</div>

<!-- 關於我們 -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>📝 關於我們</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>關於我們文字內容</label>
      <textarea name="about_text" class="form-control wysiwyg" rows="5"><?= h($client['about_text']) ?></textarea>
      <div class="hint">行銷頁與小官網「關於我們」區塊的內容。可以加圖片、表格、強調等。</div>
    </div>
  </div>
</div>

<!-- 主站行銷頁延伸內容 -->
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--accent);">
  <div class="card-header"><h2>🎯 主站行銷頁延伸內容</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>主站行銷頁額外內容</label>
      <textarea name="landing_extra_content" class="form-control wysiwyg" rows="10"><?= h($client['landing_extra_content'] ?? '') ?></textarea>
      <div class="hint">
        顯示在主站 <code><?= h(IS_LOCAL ? BASE_URL.'/store.php?sub='.($client['subdomain'] ?? 'xxx') : 'https://www.gomag.com.tw/store/'.($client['subdomain'] ?? 'xxx')) ?></code> 的延伸介紹區。<br>
        ✅ 支援文字格式、貼圖、貼影片、表格、超連結。直接拖拉或貼上圖片就會上傳。
      </div>
    </div>
  </div>
</div>

<!-- ═══════ SEO 設定（行銷頁專用）═══════ -->
<div class="card" style="margin-bottom:20px; border-left:4px solid #16a34a;">
  <div class="card-header"><h2>🔍 行銷頁 SEO 設定</h2></div>
  <div class="card-body">
    <p style="font-size:.85rem; color:var(--muted); margin-bottom:14px;">
      這些設定影響此店家在 Google 搜尋的呈現。留空則自動產生。
    </p>

    <div class="form-group-admin">
      <label>Meta Title（搜尋結果顯示的標題）</label>
      <input type="text" name="store_meta_title" class="form-control" maxlength="60"
             placeholder="<?= h($client['brand_name'] ?? '店家名') ?>｜台南OOO推薦 | 標語"
             value="<?= h($client['store_meta_title'] ?? '') ?>">
      <div class="hint">建議 50-60 字，含品牌名 + 關鍵字。<strong>留空會自動用「店名｜tagline」</strong></div>
    </div>

    <div class="form-group-admin">
      <label>Meta Description（搜尋結果顯示的兩行說明）</label>
      <textarea name="store_meta_desc" class="form-control" rows="2" maxlength="160"
                placeholder="一句話介紹這家店的特色、地點、服務範圍 — 約 120-160 字"><?= h($client['store_meta_desc'] ?? '') ?></textarea>
      <div class="hint">建議 120-160 字。Google 會在搜尋結果用這段字</div>
    </div>

    <div class="form-group-admin">
      <label>關鍵字（用半形逗號分隔）</label>
      <input type="text" name="store_keywords" class="form-control"
             placeholder="台南火鍋, 永康區聚餐, 平價吃到飽"
             value="<?= h($client['store_keywords'] ?? '') ?>">
      <div class="hint">3-7 個店家相關關鍵字。Google 用得不多但 AI 搜尋用</div>
    </div>

    <div class="form-group-admin">
      <label>分享圖片 URL（FB / LINE 貼文預覽圖）</label>
      <input type="text" name="store_og_image" class="form-control"
             placeholder="https://... 或 uploads/brand/xxx.jpg"
             value="<?= h($client['store_og_image'] ?? '') ?>">
      <div class="hint">建議 1200×630 px。留空會用 Hero 圖</div>
    </div>
  </div>
</div>

<!-- Google 地圖 -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h2>🗺️ Google 地圖嵌入</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>Google Maps embed src 網址</label>
      <input type="text" name="google_maps_embed" class="form-control"
             value="<?= h($client['google_maps_embed']) ?>"
             placeholder="https://www.google.com/maps/embed?pb=...">
      <div class="hint">
        前往 Google Maps → 分享 → 嵌入地圖 → 複製 src="..." 裡面的網址
      </div>
    </div>
    <?php if ($client['google_maps_embed']): ?>
    <div style="margin-top:12px;">
      <iframe src="<?= h($client['google_maps_embed']) ?>"
              width="100%" height="200" style="border:0;border-radius:8px;" allowfullscreen loading="lazy"></iframe>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Google 評價串接 -->
<div class="card" style="margin-bottom:20px; border-left:4px solid var(--accent);">
  <div class="card-header"><h2>🌟 Google 評價</h2></div>
  <div class="card-body">
    <div class="form-group-admin">
      <label>Google Place ID</label>
      <div style="display:flex; gap:8px;">
        <input type="text" name="google_place_id" id="google_place_id" class="form-control"
               value="<?= h($client['google_place_id'] ?? '') ?>"
               placeholder="ChIJxxxxxxxxxxxxxxxxxx" style="flex:1;">
        <button type="button" class="btn btn-accent" onclick="findGooglePlace()">🔍 自動搜尋</button>
      </div>
      <div class="hint" id="google-find-status">
        填入 Place ID 後，店家頁會自動顯示 Google 真實評分跟評論。
        按「自動搜尋」會用「店名 + 地址」呼叫 Google 找出對應的 place_id。
      </div>
    </div>
  </div>
</div>

<script>
function findGooglePlace() {
  const status = document.getElementById('google-find-status');
  const input = document.getElementById('google_place_id');
  status.innerHTML = '🔍 搜尋中...';
  status.style.color = 'var(--muted)';

  const fd = new FormData();
  fd.append('name', '<?= h(addslashes($client['brand_name'] ?? '')) ?>');
  fd.append('address', '<?= h(addslashes($client['address'] ?? '')) ?>');

  fetch('<?= BASE_URL ?>/admin/find_place.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        input.value = d.place_id;
        status.innerHTML = '✅ 找到了！記得按「儲存設定」';
        status.style.color = 'var(--success)';
      } else {
        status.innerHTML = '❌ ' + (d.msg || '失敗');
        status.style.color = 'var(--danger)';
      }
    })
    .catch(e => {
      status.innerHTML = '❌ 網路錯誤';
      status.style.color = 'var(--danger)';
    });
}
</script>

<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
  <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:1rem;">💾 儲存設定</button>
  <a href="<?= h($previewStoreUrl) ?>" target="_blank" rel="noopener"
     class="btn btn-accent" style="padding:12px 22px;">
    📢 預覽行銷頁
  </a>
  <?php if (!empty($client['has_minisite'])): ?>
  <a href="<?= h($previewMiniUrl) ?>" target="_blank" rel="noopener"
     class="btn btn-outline" style="padding:12px 22px;">
    🌐 預覽小官網
  </a>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-ghost" style="margin-left:auto;">取消</a>
</div>
<div style="font-size:.8rem;color:var(--muted);margin-top:8px;">
  💡 預覽會在新分頁開啟，先儲存再預覽看到的才是最新內容。
</div>

</form>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
