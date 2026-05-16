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
        // SEO schema 細欄位（旭森風格 — 拆 PostalAddress + geo + opening hours）
        'address_street'         => trim($_POST['address_street'] ?? '') ?: null,
        'address_district'       => trim($_POST['address_district'] ?? '') ?: null,
        'address_region'         => trim($_POST['address_region'] ?? '') ?: null,
        'postal_code'            => trim($_POST['postal_code'] ?? '') ?: null,
        'latitude'               => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude'              => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        'opening_hours_json'     => trim($_POST['opening_hours_json'] ?? '') ?: null,
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
        // Phase C: Owner block
        'owner_name'             => trim($_POST['owner_name'] ?? ''),
        'owner_intro'            => trim($_POST['owner_intro'] ?? ''),
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
    // Phase C: Owner avatar 上傳
    if (!empty($_FILES['owner_avatar']['name'])) {
        $path = uploadImage($_FILES['owner_avatar'], 'brand');
        if ($path) $fields['owner_avatar'] = $path;
    }
    // Phase C: Photo gallery 多檔上傳
    $existingPhotos = !empty($_POST['existing_photos']) ? json_decode($_POST['existing_photos'], true) : [];
    if (!is_array($existingPhotos)) $existingPhotos = [];
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $i => $name) {
            if (!$name || $_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $f = ['name' => $name, 'type' => $_FILES['photos']['type'][$i], 'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                  'size' => $_FILES['photos']['size'][$i], 'error' => $_FILES['photos']['error'][$i]];
            $p = uploadImage($f, 'brand');
            if ($p) $existingPhotos[] = $p;
        }
    }
    $fields['photos'] = $existingPhotos ? json_encode(array_values(array_unique($existingPhotos)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

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
        <label>地址（顯示用，完整字串）</label>
        <input type="text" name="address" class="form-control"
               value="<?= h($client['address']) ?>"
               placeholder="例：嘉義市西區中山路一段 123 號">
        <div class="hint">前台直接顯示用。下面 SEO 細欄位選填，填了 schema.org PostalAddress 才完整、有助 Google 本地搜尋。</div>
      </div>
    </div>
  </div>

  <!-- SEO Local Business 細欄位 -->
  <div class="card">
    <div class="card-header"><h2>📍 SEO 本地商家欄位（schema.org）</h2></div>
    <div class="card-body">
      <div class="hint" style="margin-bottom:16px;">
        填了下面欄位，JSON-LD 會輸出完整 <code>PostalAddress</code> + <code>GeoCoordinates</code> + <code>OpeningHoursSpecification</code>。
        Google 本地搜尋、知識面板、Maps 連動會更完整。沒填則沿用上方「地址」單一字串。
      </div>
      <div class="grid-2">
        <div class="form-group-admin">
          <label>街道地址</label>
          <input type="text" name="address_street" class="form-control"
                 value="<?= h($client['address_street'] ?? '') ?>"
                 placeholder="例：中山路一段 123 號">
          <div class="hint">不含縣市/區，只填街道+號</div>
        </div>
        <div class="form-group-admin">
          <label>郵遞區號</label>
          <input type="text" name="postal_code" class="form-control"
                 value="<?= h($client['postal_code'] ?? '') ?>"
                 placeholder="例：600" maxlength="10">
        </div>
        <div class="form-group-admin">
          <label>行政區</label>
          <input type="text" name="address_district" class="form-control"
                 value="<?= h($client['address_district'] ?? '') ?>"
                 placeholder="例：西區 / 永康區">
        </div>
        <div class="form-group-admin">
          <label>縣市</label>
          <input type="text" name="address_region" class="form-control"
                 value="<?= h($client['address_region'] ?? '') ?>"
                 placeholder="例：嘉義市 / 台南市">
        </div>
        <div class="form-group-admin">
          <label>緯度 (Latitude)</label>
          <input type="text" name="latitude" class="form-control"
                 value="<?= h($client['latitude'] ?? '') ?>"
                 placeholder="例：23.4810">
          <div class="hint">Google Maps 點店家 → 分享 → 從 URL 抽 @23.481,120.456 的第一個數字</div>
        </div>
        <div class="form-group-admin">
          <label>經度 (Longitude)</label>
          <input type="text" name="longitude" class="form-control"
                 value="<?= h($client['longitude'] ?? '') ?>"
                 placeholder="例：120.4496">
        </div>
      </div>
      <div class="form-group-admin">
        <label>營業時間（JSON）</label>
        <textarea name="opening_hours_json" class="form-control" rows="3"
                  placeholder='{"mon":"09:00-18:00","tue":"09:00-18:00","wed":"09:00-18:00","thu":"09:00-18:00","fri":"09:00-18:00","sat":"09:00-18:00","sun":"closed"}'><?= h($client['opening_hours_json'] ?? '') ?></textarea>
        <div class="hint">
          格式：<code>{"mon":"09:00-18:00","sun":"closed"}</code>。
          7 天 key 用 mon/tue/wed/thu/fri/sat/sun，值用 <code>09:00-18:00</code> 或 <code>closed</code>。
          <a href="javascript:void(0)" onclick="document.querySelector('[name=opening_hours_json]').value='{&quot;mon&quot;:&quot;09:00-18:00&quot;,&quot;tue&quot;:&quot;09:00-18:00&quot;,&quot;wed&quot;:&quot;09:00-18:00&quot;,&quot;thu&quot;:&quot;09:00-18:00&quot;,&quot;fri&quot;:&quot;09:00-18:00&quot;,&quot;sat&quot;:&quot;09:00-18:00&quot;,&quot;sun&quot;:&quot;closed&quot;}'">套用週一到六 9-18、週日公休</a>
        </div>
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

<!-- Phase C: 經營者 Owner Block -->
<div class="card" style="margin-bottom:20px; border:1.5px solid #FF5A36;">
  <div class="card-header"><h2>👤 經營者資訊（Owner Block）</h2></div>
  <div class="card-body">
    <p class="hint" style="margin-bottom:14px;">顯示在新版店家頁「關於」上方，含圓形頭像 + 名字 + 一行介紹。</p>
    <div style="display:grid; grid-template-columns: 100px 1fr; gap:16px; align-items:start;">
      <div>
        <?php if (!empty($client['owner_avatar'])): ?>
        <img src="<?= BASE_URL ?>/<?= h($client['owner_avatar']) ?>" alt="" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid #FF5A36;">
        <?php else: ?>
        <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#FF5A36,#E0421F); color:#fff; display:grid; place-items:center; font-size:32px; font-weight:800;"><?= h(mb_substr($client['owner_name'] ?? $client['brand_name'] ?? '?', 0, 1)) ?></div>
        <?php endif; ?>
      </div>
      <div style="display:grid; gap:10px;">
        <div class="form-group-admin" style="margin:0;">
          <label>經營者姓名</label>
          <input type="text" name="owner_name" class="form-control" value="<?= h($client['owner_name'] ?? '') ?>" placeholder="例：蘇老闆 / 主廚 Eric">
        </div>
        <div class="form-group-admin" style="margin:0;">
          <label>一行介紹</label>
          <input type="text" name="owner_intro" class="form-control" value="<?= h($client['owner_intro'] ?? '') ?>" placeholder="例：日本學藝歸國 · 開業 8 年">
        </div>
        <div class="form-group-admin" style="margin:0;">
          <label>頭像（可選）</label>
          <input type="file" name="owner_avatar" accept="image/*">
          <div class="hint">不上傳則用名字第一字 + 橘漸層 fallback</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Phase C: Photo Gallery -->
<?php $existingPhotos = !empty($client['photos']) ? (json_decode($client['photos'], true) ?: []) : []; ?>
<div class="card" style="margin-bottom:20px; border:1.5px solid #FF5A36;">
  <div class="card-header"><h2>📷 照片集（Photo Gallery）</h2></div>
  <div class="card-body">
    <p class="hint" style="margin-bottom:14px;">顯示在新版店家頁 Hero 之後，最多前 5 張會以 Airbnb 樣式 grid 呈現。</p>
    <input type="hidden" name="existing_photos" value='<?= h(json_encode($existingPhotos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
    <?php if ($existingPhotos): ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:8px; margin-bottom:14px;">
      <?php foreach ($existingPhotos as $p): ?>
      <div style="aspect-ratio:1; border-radius:8px; overflow:hidden; background:url('<?= BASE_URL ?>/<?= h($p) ?>') center/cover #eee; border:1px solid #ddd;"></div>
      <?php endforeach; ?>
    </div>
    <p class="hint" style="font-size:.8rem; color:#888;">⚠️ 目前有 <?= count($existingPhotos) ?> 張。新上傳會「累加」到後面（不會取代）。如需清空請聯絡開發者。</p>
    <?php endif; ?>
    <div class="form-group-admin" style="margin-top:14px;">
      <label>上傳新照片（可多選）</label>
      <input type="file" name="photos[]" accept="image/*" multiple>
      <div class="hint">建議 4:3 或 1:1 比例，每張 5MB 以下</div>
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
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <h2>🎯 主站行銷頁延伸內容（一頁式）</h2>
    <div style="display:inline-flex;gap:0;border:1.5px solid var(--border);border-radius:8px;overflow:hidden">
      <button type="button" id="modeRich"
        style="padding:6px 14px;background:#fff;border:none;cursor:pointer;font-size:.78rem;font-weight:700;color:var(--muted);transition:all .15s">
        ✏️ 富文本
      </button>
      <button type="button" id="modeRaw"
        style="padding:6px 14px;background:var(--ink,#1c3d3d);color:#fff;border:none;cursor:pointer;font-size:.78rem;font-weight:700">
        &lt;/&gt; HTML 原始碼
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="form-group-admin">
      <textarea name="landing_extra_content" id="landingTextarea" rows="18"
        style="width:100%;padding:14px;border:1.5px solid var(--border);border-radius:7px;font-family:'SF Mono','Menlo','Consolas',monospace;font-size:.78rem;line-height:1.55;background:#fafbfc;tab-size:2"
        placeholder="<!-- 貼 HTML 程式碼 / 一頁式 layout 程式碼 -->&#10;&#10;<section class=&quot;hero&quot; style=&quot;background:#1a1a1a;color:#fff;padding:60px 20px;text-align:center;border-radius:10px&quot;>&#10;  <h2 style=&quot;font-size:2rem;font-weight:900;margin-bottom:10px&quot;>專業團隊・10 年經驗</h2>&#10;  <p>台南在地・5,000+ 位滿意客戶</p>&#10;</section>"
      ><?= h($client['landing_extra_content'] ?? '') ?></textarea>
      <div class="hint" id="landingHint" style="margin-top:8px;padding:10px 14px;background:#fff8e1;border:1px solid #ffd54f;border-radius:6px;color:#5d4037">
        <strong>HTML 原始碼模式</strong>（目前模式）<br>
        ✅ 直接貼一頁式 HTML、含 inline style / 自訂 class / Grid / Flex 都可。<br>
        ✅ 圖片用 <code>&lt;img src="https://..."&gt;</code>（會自動補 alt）。<br>
        ⚠️ HTML 寫錯只影響此區塊不會破壞整頁。<br>
        顯示在 <code><?= h(IS_LOCAL ? BASE_URL.'/store.php?sub='.($client['subdomain'] ?? 'xxx') : 'https://www.gomag.com.tw/store/'.($client['subdomain'] ?? 'xxx')) ?></code>
      </div>
    </div>
    <details style="margin-top:14px">
      <summary style="cursor:pointer;font-weight:700;color:var(--accent);font-size:.9rem">📋 範本：5 種一頁式區塊（點開複製）</summary>
      <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <button type="button" class="snippet-btn" data-snippet="hero" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;text-align:left;font-size:.8rem">🎬 Hero 大標 + CTA</button>
        <button type="button" class="snippet-btn" data-snippet="features" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;text-align:left;font-size:.8rem">✨ 4 格特色卡</button>
        <button type="button" class="snippet-btn" data-snippet="gallery" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;text-align:left;font-size:.8rem">🖼️ 圖片 Gallery</button>
        <button type="button" class="snippet-btn" data-snippet="faq" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;text-align:left;font-size:.8rem">❓ FAQ 折疊區</button>
        <button type="button" class="snippet-btn" data-snippet="cta" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;text-align:left;font-size:.8rem">📞 CTA 聯絡區</button>
        <button type="button" class="snippet-btn" data-snippet="quote" style="padding:8px;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;text-align:left;font-size:.8rem">💬 客戶見證引用</button>
      </div>
      <div class="hint" style="margin-top:10px">點任一範本 → 自動加到下方 textarea 末尾</div>
    </details>
  </div>
</div>

<script>
// === 一頁式範本（HTML snippets）===
const SNIPPETS = {
  hero: `\n<section style="background:linear-gradient(135deg,#1a1a1a,#2c2c2c);color:#fff;padding:60px 30px;text-align:center;border-radius:12px;margin:24px 0">\n  <div style="font-size:.7rem;letter-spacing:.2em;opacity:.7;margin-bottom:12px">EXPERIENCE</div>\n  <h2 style="font-size:2rem;font-weight:900;margin-bottom:14px;line-height:1.3">專業團隊・10 年經驗</h2>\n  <p style="opacity:.85;font-size:1rem;margin-bottom:24px">台南在地・5,000+ 位滿意客戶</p>\n  <a href="tel:0612345678" style="display:inline-block;background:#FF5A36;color:#fff;padding:14px 32px;border-radius:50px;font-weight:700;text-decoration:none">📞 立即洽詢</a>\n</section>\n`,
  features: `\n<section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:24px 0">\n  <div style="padding:24px;background:#fff;border:1px solid #e5e5e5;border-radius:10px;text-align:center"><div style="font-size:2rem;margin-bottom:10px">⭐</div><h3 style="font-weight:800;margin-bottom:6px">10 年經驗</h3><p style="color:#666;font-size:.85rem">在地深耕</p></div>\n  <div style="padding:24px;background:#fff;border:1px solid #e5e5e5;border-radius:10px;text-align:center"><div style="font-size:2rem;margin-bottom:10px">🛡️</div><h3 style="font-weight:800;margin-bottom:6px">7 天保固</h3><p style="color:#666;font-size:.85rem">不滿意免費複工</p></div>\n  <div style="padding:24px;background:#fff;border:1px solid #e5e5e5;border-radius:10px;text-align:center"><div style="font-size:2rem;margin-bottom:10px">💯</div><h3 style="font-weight:800;margin-bottom:6px">5000+ 客戶</h3><p style="color:#666;font-size:.85rem">滿意推薦</p></div>\n  <div style="padding:24px;background:#fff;border:1px solid #e5e5e5;border-radius:10px;text-align:center"><div style="font-size:2rem;margin-bottom:10px">🌿</div><h3 style="font-weight:800;margin-bottom:6px">環保用品</h3><p style="color:#666;font-size:.85rem">SGS 認證</p></div>\n</section>\n`,
  gallery: `\n<section style="margin:24px 0">\n  <h2 style="font-size:1.4rem;font-weight:900;margin-bottom:16px;text-align:center">作品集</h2>\n  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px">\n    <img src="https://picsum.photos/400/300?1" style="width:100%;height:240px;object-fit:cover;border-radius:8px">\n    <img src="https://picsum.photos/400/300?2" style="width:100%;height:240px;object-fit:cover;border-radius:8px">\n    <img src="https://picsum.photos/400/300?3" style="width:100%;height:240px;object-fit:cover;border-radius:8px">\n  </div>\n</section>\n`,
  faq: `\n<section style="margin:24px 0">\n  <h2 style="font-size:1.4rem;font-weight:900;margin-bottom:16px;text-align:center">常見問題</h2>\n  <details style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:14px 18px;margin-bottom:8px"><summary style="font-weight:700;cursor:pointer">有提供保固嗎？</summary><div style="margin-top:10px;color:#666;line-height:1.7">所有服務 7 天滿意保固，不滿意 24 小時內回覆免費複工。</div></details>\n  <details style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:14px 18px;margin-bottom:8px"><summary style="font-weight:700;cursor:pointer">需要自備清潔用品嗎？</summary><div style="margin-top:10px;color:#666;line-height:1.7">不需要！所有清潔劑、機具皆自備，使用 SGS 環保認證產品。</div></details>\n</section>\n`,
  cta: `\n<section style="background:#FF5A36;color:#fff;padding:48px 24px;text-align:center;border-radius:12px;margin:24px 0">\n  <h2 style="font-size:1.6rem;font-weight:900;margin-bottom:10px">立即預約・免費估價</h2>\n  <p style="opacity:.9;margin-bottom:24px">填表 24 小時內專人回覆</p>\n  <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap">\n    <a href="tel:0612345678" style="background:#fff;color:#FF5A36;padding:14px 28px;border-radius:50px;font-weight:800;text-decoration:none">📞 06-1234-5678</a>\n    <a href="https://line.me/R/ti/p/@xxx" style="background:#06c755;color:#fff;padding:14px 28px;border-radius:50px;font-weight:800;text-decoration:none">💬 LINE 諮詢</a>\n  </div>\n</section>\n`,
  quote: `\n<blockquote style="background:#f8f9fa;border-left:4px solid #FF5A36;padding:24px 28px;margin:24px 0;border-radius:0 8px 8px 0">\n  <p style="font-size:1.05rem;line-height:1.8;color:#333;margin-bottom:14px">"師傅準時、有禮貌，洗到像新的一樣！廚房以前那層黃漬完全消失。下次大掃除一定再找。"</p>\n  <footer style="color:#888;font-size:.85rem">— 林小姐 台南東區・⭐⭐⭐⭐⭐</footer>\n</blockquote>\n`,
};

document.querySelectorAll('.snippet-btn').forEach(b => {
  b.addEventListener('click', () => {
    const ta = document.getElementById('landingTextarea');
    ta.value += SNIPPETS[b.dataset.snippet] || '';
    ta.scrollTop = ta.scrollHeight;
    ta.focus();
  });
});

// 模式切換（純 UI，未來可串 Quill init）
const modeRich = document.getElementById('modeRich');
const modeRaw  = document.getElementById('modeRaw');
const hint     = document.getElementById('landingHint');
const ta       = document.getElementById('landingTextarea');

modeRaw.addEventListener('click', () => {
  modeRaw.style.background = 'var(--ink,#1c3d3d)'; modeRaw.style.color = '#fff';
  modeRich.style.background = '#fff'; modeRich.style.color = 'var(--muted)';
  ta.style.fontFamily = "'SF Mono','Menlo','Consolas',monospace";
  ta.style.fontSize = '.78rem';
  ta.style.background = '#fafbfc';
  hint.innerHTML = '<strong>HTML 原始碼模式</strong>（目前模式）<br>✅ 直接貼一頁式 HTML、含 inline style / 自訂 class / Grid / Flex 都可。<br>✅ 圖片用 <code>&lt;img src="https://..."&gt;</code>（會自動補 alt）。<br>⚠️ HTML 寫錯只影響此區塊不會破壞整頁。';
  hint.style.background = '#fff8e1'; hint.style.borderColor = '#ffd54f'; hint.style.color = '#5d4037';
});
modeRich.addEventListener('click', () => {
  alert('富文本模式由 Quill 接管，但會 strip 複雜 HTML（無 section/inline style）。\n\n建議：用「HTML 原始碼模式」貼一頁式內容比較自由。\n\n如真要切換到富文本，請存檔後重整頁面（會抓回欄位內容）。');
});
</script>

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
