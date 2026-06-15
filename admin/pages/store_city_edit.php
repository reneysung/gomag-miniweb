<?php
// admin/pages/store_city_edit.php  ─  行銷頁城市變體 新增/編輯
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$db = getDB();
$clientId = getCurrentClientId();
if (!$clientId) {
    setFlash('error', '請先選擇要管理的客戶');
    redirect(BASE_URL . '/admin/index.php');
}

$client = $db->prepare("SELECT id, slug, subdomain, brand_name FROM clients WHERE id=?");
$client->execute([$clientId]);
$client = $client->fetch();
if (!$client) { redirect(BASE_URL . '/admin/index.php'); }

$id = (int)($_GET['id'] ?? 0);
$row = null;
if ($id) {
    $s = $db->prepare("SELECT * FROM client_city_pages WHERE id=? AND client_id=?");
    $s->execute([$id, $clientId]);
    $row = $s->fetch();
    if (!$row) {
        setFlash('error', '找不到該城市變體');
        redirect(BASE_URL . '/admin/pages/store_cities.php');
    }
}

// 城市清單（含 is_active=0，方便為未來開站的城市先建內容）
$cities = $db->query("SELECT slug, name, full_name FROM cities ORDER BY sort_order, slug")->fetchAll();

// ─── POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $citySlug = strtolower(trim($_POST['city_slug'] ?? ''));
    $cityLabel = trim($_POST['city_label'] ?? '');
    if ($cityLabel === '') {
        foreach ($cities as $c) if ($c['slug'] === $citySlug) { $cityLabel = $c['name']; break; }
    }

    if ($citySlug === '' || !preg_match('/^[a-z][a-z0-9-]+$/', $citySlug)) {
        setFlash('error', '請選擇城市');
        redirect($_SERVER['REQUEST_URI']);
    }

    // hero 圖上傳
    $heroPath = trim($_POST['hero_image_path_existing'] ?? ($row['hero_image_path'] ?? ''));
    if (!empty($_FILES['hero_image']['name']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
        $p = uploadImage($_FILES['hero_image'], 'brand');
        if ($p) {
            if ($heroPath && $heroPath !== $p) deleteImage($heroPath);
            $heroPath = $p;
        }
    }

    // 網友分享 JSON 驗證（壞 JSON 直接拒存，顯示 flash 錯誤）
    $_extReviewsRaw = trim($_POST['external_reviews_json'] ?? '');
    $_extReviewsNormalized = null;
    if ($_extReviewsRaw !== '') {
        $_decoded = json_decode($_extReviewsRaw, true);
        if (!is_array($_decoded)) {
            setFlash('error', '網友分享 JSON 格式錯誤：' . (json_last_error_msg() ?: '不是有效的 JSON array'));
            redirect($_SERVER['REQUEST_URI']);
        }
        $_extReviewsNormalized = json_encode($_decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $fields = [
        'client_id'              => $clientId,
        'city_slug'              => $citySlug,
        'city_label'             => $cityLabel ?: $citySlug,
        'brand_name'             => trim($_POST['brand_name'] ?? '') ?: null,
        // 📍 本縣市聯絡資訊（空=用主檔）
        'address'                => trim($_POST['address'] ?? '') ?: null,
        'phone'                  => trim($_POST['phone'] ?? '') ?: null,
        'mobile_phone'           => trim($_POST['mobile_phone'] ?? '') ?: null,
        'business_hours'         => trim($_POST['business_hours'] ?? '') ?: null,
        'google_maps_embed'      => trim($_POST['google_maps_embed'] ?? '') ?: null,
        // 📢 行銷頁覆寫
        'store_meta_title'       => trim($_POST['store_meta_title'] ?? '') ?: null,
        'store_meta_desc'        => trim($_POST['store_meta_desc'] ?? '') ?: null,
        'store_keywords'         => trim($_POST['store_keywords'] ?? '') ?: null,
        'landing_extra_content'  => trim($_POST['landing_extra_content'] ?? '') ?: null,
        'external_reviews_json'  => $_extReviewsNormalized,
        'store_og_image'         => trim($_POST['store_og_image'] ?? '') ?: null,
        // 🌐 小官網覆寫
        'minisite_meta_title'    => trim($_POST['minisite_meta_title'] ?? '') ?: null,
        'minisite_meta_desc'     => trim($_POST['minisite_meta_desc'] ?? '') ?: null,
        'minisite_keywords'      => trim($_POST['minisite_keywords'] ?? '') ?: null,
        'minisite_og_image'      => trim($_POST['minisite_og_image'] ?? '') ?: null,
        'minisite_intro_html'    => trim($_POST['minisite_intro_html'] ?? '') ?: null,
        // 共用
        'hero_image_path'        => $heroPath ?: null,
        'filter_cases_by_region' => !empty($_POST['filter_cases_by_region']) ? 1 : 0,
        'hide_shared_sections'   => !empty($_POST['hide_shared_sections']) ? 1 : 0,
        'sort_order'             => (int)($_POST['sort_order'] ?? 0),
        'is_active'              => !empty($_POST['is_active']) ? 1 : 0,
    ];

    try {
        if ($row) {
            $set = implode(',', array_map(fn($k) => "$k=:$k", array_keys($fields)));
            $sql = "UPDATE client_city_pages SET $set WHERE id=:id AND client_id=:cid";
            $stmt = $db->prepare($sql);
            $fields['id'] = $row['id'];
            $fields['cid'] = $clientId;
            $stmt->execute($fields);
        } else {
            $cols = implode(',', array_keys($fields));
            $ph = ':' . implode(',:', array_keys($fields));
            $stmt = $db->prepare("INSERT INTO client_city_pages ($cols) VALUES ($ph)");
            $stmt->execute($fields);
        }
        setFlash('success', '✅ 已儲存城市變體');
        redirect(BASE_URL . '/admin/pages/store_cities.php');
    } catch (PDOException $e) {
        if ((int)$e->errorInfo[1] === 1062) {
            setFlash('error', '此客戶已存在「' . h($cityLabel ?: $citySlug) . '」的城市變體（同一客戶 + 同一城市只能一筆）');
        } else {
            setFlash('error', 'DB 錯誤：' . $e->getMessage());
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$sub = $client['subdomain'] ?: $client['slug'];
$pageTitle = ($row ? '編輯' : '新增') . '城市行銷頁：' . h($client['brand_name']);
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="page-header">
  <div>
    <a href="<?= BASE_URL ?>/admin/pages/store_cities.php" style="color:var(--muted); font-size:.85rem; text-decoration:none;">← 返回城市行銷頁列表</a>
    <h1>🗺️ <?= h($pageTitle) ?></h1>
    <p style="color:var(--muted); margin-top:6px; font-size:.9rem;">
      預覽 URL：
      📢 行銷頁 <code>https://www.gomag.com.tw/store/<?= h($sub) ?>/{city}</code>
      🌐 小官網 <code>https://<?= h($sub) ?>.gomag.com.tw/{city}</code>
    </p>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="_token" value="<?= h(csrfToken()) ?>">
  <input type="hidden" name="hero_image_path_existing" value="<?= h($row['hero_image_path'] ?? '') ?>">

  <!-- 城市選擇 + 顯示控制 -->
  <div class="card">
    <div class="card-header"><h2>🗺️ 城市 + 顯示</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>城市 *</label>
        <?php if ($row): ?>
          <input type="text" class="form-control" value="<?= h($row['city_label']) ?>（<?= h($row['city_slug']) ?>）" disabled style="max-width:300px;">
          <input type="hidden" name="city_slug" value="<?= h($row['city_slug']) ?>">
          <input type="hidden" name="city_label" value="<?= h($row['city_label']) ?>">
          <div class="hint">編輯時不能改城市；要換城市請刪除這筆後重新建立。</div>
        <?php else: ?>
          <select name="city_slug" class="form-control" required style="max-width:300px;" onchange="document.querySelector('[name=city_label]').value=this.options[this.selectedIndex].dataset.label">
            <option value="">-- 選擇城市 --</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= h($c['slug']) ?>" data-label="<?= h($c['name']) ?>"><?= h($c['name']) ?>（<?= h($c['slug']) ?>）</option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="city_label" value="">
          <div class="hint">城市清單來自 cities 表，新城市可到「城市管理」頁開。</div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= (!$row || $row['is_active']) ? 'checked' : '' ?>> 啟用</label>
        <div class="hint">停用時 /store/{slug}/{city} 會回 404，sitemap 也不列。</div>
      </div>

      <div class="form-group">
        <label>排序順序</label>
        <input type="number" name="sort_order" value="<?= h($row['sort_order'] ?? 0) ?>" class="form-control" style="max-width:200px;">
        <div class="hint">數字越小越前面（僅影響 sitemap 順序）。</div>
      </div>

      <div class="form-group">
        <label><input type="checkbox" name="filter_cases_by_region" value="1" <?= (!$row || $row['filter_cases_by_region']) ? 'checked' : '' ?>> 案例依城市篩選</label>
        <div class="hint">勾選後此城市頁只顯示 location 對應該城市的案例（自動依 location 前綴判斷）。預設勾。</div>
      </div>

      <div class="form-group">
        <label><input type="checkbox" name="hide_shared_sections" value="1" <?= (!empty($row['hide_shared_sections'])) ? 'checked' : '' ?>> 只顯示本頁自寫內容（隱藏共用區塊）</label>
        <div class="hint">勾選後本城市頁會藏掉「服務項目、精選菜色、評價、網友分享、相似店家」等從主檔來的共用區塊，只留 Hero ＋ 上方「行銷頁延伸內容(HTML)」＋ 右側聯絡卡 ＋ 城市切換。<strong>適合每城內容完全自寫、避免各城重複內容（doorway）的情況</strong>。預設不勾＝沿用主檔共用區塊。</div>
      </div>
    </div>
  </div>

  <!-- 共用:品牌覆寫(行銷頁+小官網同時用) -->
  <div class="card" style="margin-top:20px;">
    <div class="card-header"><h2>🔗 共用覆寫（行銷頁＋小官網都會用到）</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>品牌名稱（顯示在此城市頁）</label>
        <input type="text" name="brand_name" class="form-control" maxlength="120"
               placeholder="留空 = 用主檔「<?= h($client['brand_name']) ?>」"
               value="<?= h($row['brand_name'] ?? '') ?>">
        <div class="hint">想讓城市頁標示「奧喜長崎蛋糕 台中店」之類更貼近在地的品牌名時填這裡。行銷頁與小官網都會用此名稱。</div>
      </div>

      <div class="form-group" style="margin-top:16px; padding-top:16px; border-top:1px dashed #ccc;">
        <label style="font-weight:800;">📍 本縣市聯絡資訊（多縣市分點專用：留空＝用主檔的）</label>
        <div class="hint" style="margin-bottom:10px;">這家有多個縣市分點時，在這裡填「本縣市」的地址電話，<?= h($cityLabelDisplay ?? '') ?>行銷頁就顯示對應的，不再跟主檔共用。</div>
        <div style="display:grid; gap:10px;">
          <div>
            <label>地址</label>
            <input type="text" name="address" class="form-control" value="<?= h($row['address'] ?? '') ?>"
                   placeholder="留空 = 用主檔「<?= h($client['address'] ?? '') ?>」">
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div>
              <label>電話</label>
              <input type="text" name="phone" class="form-control" value="<?= h($row['phone'] ?? '') ?>"
                     placeholder="留空 = 用主檔「<?= h($client['phone'] ?? '') ?>」">
            </div>
            <div>
              <label>手機（可選）</label>
              <input type="text" name="mobile_phone" class="form-control" value="<?= h($row['mobile_phone'] ?? '') ?>"
                     placeholder="留空 = 用主檔">
            </div>
          </div>
          <div>
            <label>營業時間（可選）</label>
            <input type="text" name="business_hours" class="form-control" value="<?= h($row['business_hours'] ?? '') ?>"
                   placeholder="留空 = 用主檔">
          </div>
          <div>
            <label>Google 地圖嵌入碼／網址（可選）</label>
            <textarea name="google_maps_embed" class="form-control" rows="2"
                      placeholder="留空 = 用主檔"><?= h($row['google_maps_embed'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ Tab 切換 ═══ -->
  <div class="cv-tabs" role="tablist" style="display:flex; gap:0; margin-top:24px; border-bottom:2px solid var(--border);">
    <button type="button" class="cv-tab-btn cv-tab-btn-active" data-tab-target="store" role="tab"
            style="flex:1; padding:14px 18px; background:#fff; border:0; border-bottom:3px solid var(--accent,#FF5A36); border-radius:8px 8px 0 0; cursor:pointer; font-weight:800; color:#c2410c; font-size:1rem; transition:all .15s">
      📢 行銷頁覆寫
    </button>
    <button type="button" class="cv-tab-btn" data-tab-target="minisite" role="tab"
            style="flex:1; padding:14px 18px; background:transparent; border:0; border-bottom:3px solid transparent; cursor:pointer; font-weight:700; color:var(--muted); font-size:1rem; transition:all .15s">
      🌐 小官網覆寫
    </button>
  </div>

  <!-- 📢 行銷頁 Tab(預設顯示) -->
  <div data-tab-panel="store">

  <!-- 📢 行銷頁 SEO 覆寫 -->
  <div class="card" style="margin-top:20px; border-left:4px solid var(--accent);">
    <div class="card-header"><h2>📢 行銷頁 SEO 覆寫（/store/{slug}/{city}，留空 = 用主檔）</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>SEO 標題 (Meta Title)</label>
        <input type="text" name="store_meta_title" class="form-control" maxlength="300"
               placeholder="例：台中裝潢細清｜OOO 團隊｜不破壞為原則"
               value="<?= h($row['store_meta_title'] ?? '') ?>">
        <div class="hint">每個城市必須跟主頁、其他城市都不一樣，避免互蠶食。建議帶城市關鍵字。</div>
      </div>

      <div class="form-group">
        <label>SEO 描述 (Meta Description)</label>
        <textarea name="store_meta_desc" class="form-control" rows="3" maxlength="500"
                  placeholder="120-160 字。城市名 + 主力服務 + 在地區域 + 行動引導"><?= h($row['store_meta_desc'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>關鍵字（逗號分隔）</label>
        <input type="text" name="store_keywords" class="form-control" maxlength="500"
               placeholder="例：台中裝潢細清,台中清潔公司推薦,台中北屯清潔公司"
               value="<?= h($row['store_keywords'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>OG 分享圖路徑（uploads/...）</label>
        <input type="text" name="store_og_image" class="form-control"
               placeholder="留空 = 用主檔或下方 Hero 圖"
               value="<?= h($row['store_og_image'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- 📢 行銷頁 Hero + 一頁式 -->
  <div class="card" style="margin-top:20px; border-left:4px solid var(--accent);">
    <div class="card-header"><h2>📢 行銷頁 Hero + 一頁式延伸內容</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>Hero 圖（此城市專用，行銷頁＋小官網共用）</label>
        <?php if (!empty($row['hero_image_path'])): ?>
          <div style="margin-bottom:10px;">
            <img src="<?= BASE_URL . '/' . h($row['hero_image_path']) ?>" style="max-width:300px; border-radius:6px;">
            <div class="hint">目前的 hero：<code><?= h($row['hero_image_path']) ?></code></div>
          </div>
        <?php endif; ?>
        <input type="file" name="hero_image" class="form-control" accept="image/*">
        <div class="hint">留空 = 用主檔 hero。上傳新圖會自動取代。</div>
      </div>

      <div class="form-group">
        <label>一頁式延伸內容（HTML，覆寫主檔）</label>
        <textarea name="landing_extra_content" class="form-control" rows="12"
                  placeholder="留空 = 用主檔內容。填值 = 此城市頁專用內容（會完全取代主檔）"><?= h($row['landing_extra_content'] ?? '') ?></textarea>
        <div class="hint">想為城市頁寫獨立的在地內容（地名、行情、服務範圍）時填這裡。</div>
      </div>

      <div class="form-group" style="margin-top:16px; padding-top:16px; border-top:1px dashed #ccc;">
        <label>📰 網友／部落客分享（JSON array）</label>
        <textarea name="external_reviews_json" class="form-control" rows="10"
                  placeholder='[
  {
    "title": "文章標題",
    "url": "https://example.com/...",
    "source": "媒體或部落客名（顯示在橘色 tag）",
    "excerpt": "1-2 句摘要（可選）"
  }
]'
                  oninput="this.style.borderColor=''; try { if(this.value.trim()) JSON.parse(this.value); this.nextElementSibling.textContent='✅ JSON 格式正確'; this.nextElementSibling.style.color='#0a7'; } catch(e) { this.style.borderColor='#e53'; this.nextElementSibling.textContent='⚠️ '+e.message; this.nextElementSibling.style.color='#e53'; }"
                  style="font-family:Menlo,Monaco,monospace; font-size:.85rem;"><?= h($row['external_reviews_json'] ?? '') ?></textarea>
        <div class="hint" style="margin-top:4px;"><?= !empty($row['external_reviews_json']) ? '✅ 目前已存資料' : '留空＝不顯示「網友分享」section' ?></div>
        <div class="hint" style="margin-top:8px; line-height:1.6;">
          每篇文章是一個 object，必填 <code>title</code> + <code>url</code>，<code>source</code> 跟 <code>excerpt</code> 可選。<br>
          範例值（複製整個 array 改用）：
          <code style="display:block; background:#f5f5f5; padding:8px; margin-top:4px; border-radius:4px; white-space:pre; font-size:.78rem;">[{"title":"嘉義尾牙 450 桌開箱","url":"https://...","source":"史丹利樂福","excerpt":"龍蝦月光寶盒、黑蒜雞湯..."}]</code>
        </div>
      </div>
    </div>
  </div>

  </div><!-- /store panel -->

  <!-- 🌐 小官網 Tab(預設隱藏) -->
  <div data-tab-panel="minisite" style="display:none">

  <!-- 🌐 小官網 SEO 覆寫 -->
  <div class="card" style="margin-top:20px; border-left:4px solid #2563eb;">
    <div class="card-header"><h2>🌐 小官網 SEO 覆寫（{sub}.gomag.com.tw/{city}，留空 = 用主檔）</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>小官網 SEO 標題 (Meta Title)</label>
        <input type="text" name="minisite_meta_title" class="form-control" maxlength="300"
               placeholder="例：奧喜長崎蛋糕 台中店｜手工長崎蛋糕宅配・台中專送"
               value="<?= h($row['minisite_meta_title'] ?? '') ?>">
        <div class="hint">跟行銷頁標題寫不一樣！小官網是「品牌官網角度」，行銷頁是「關鍵字搜尋角度」，兩邊互補不互蠶食。</div>
      </div>

      <div class="form-group">
        <label>小官網 SEO 描述 (Meta Description)</label>
        <textarea name="minisite_meta_desc" class="form-control" rows="3" maxlength="500"
                  placeholder="120-160 字。城市 + 主力商品 + 在地特色 + 行動引導"><?= h($row['minisite_meta_desc'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>小官網 關鍵字（逗號分隔）</label>
        <input type="text" name="minisite_keywords" class="form-control" maxlength="500"
               placeholder="例：台中長崎蛋糕,台中伴手禮,北屯彌月禮盒"
               value="<?= h($row['minisite_keywords'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>小官網 OG 分享圖路徑（uploads/...）</label>
        <input type="text" name="minisite_og_image" class="form-control"
               placeholder="留空 = 用上方 Hero 圖或主檔"
               value="<?= h($row['minisite_og_image'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- 🌐 小官網 城市專屬內容（覆寫關於我們） -->
  <div class="card" style="margin-top:20px; border-left:4px solid #2563eb;">
    <div class="card-header"><h2>🌐 小官網 城市專屬內容（覆寫「關於我們」段，留空 = 用主檔）</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>城市專屬簡介（HTML/富文本）</label>
        <textarea name="minisite_intro_html" class="form-control wysiwyg" rows="10"
                  placeholder="留空 = 小官網該城市頁的「關於我們」用主檔內容。填值 = 小官網該城市頁專用內容（如：在地服務範圍、地區故事、城市限定品項）"><?= h($row['minisite_intro_html'] ?? '') ?></textarea>
        <div class="hint">這段會出現在小官網 {sub}.gomag.com.tw/{city} 頁的「關於我們」區段，差異化內容對 SEO 很重要——別 5 個城市寫一樣。</div>
      </div>
    </div>
  </div>

  </div><!-- /minisite panel -->

  <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg">💾 儲存城市變體</button>
    <a href="<?= BASE_URL ?>/admin/pages/store_cities.php" class="btn btn-ghost btn-lg">取消</a>
    <?php if ($row): ?>
      <a href="<?= BASE_URL ?>/store/<?= h($sub) ?>/<?= h($row['city_slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg" style="margin-left:auto;">📢 預覽行銷頁</a>
      <a href="https://<?= h($sub) ?>.gomag.com.tw/<?= h($row['city_slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg">🌐 預覽小官網</a>
    <?php endif; ?>
  </div>
</form>

<script>
// 行銷頁／小官網 tab 切換（純前端，所有欄位都在同一 form 一起送出）
(function() {
  var btns = document.querySelectorAll('.cv-tab-btn');
  var panels = document.querySelectorAll('[data-tab-panel]');
  btns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var target = btn.dataset.tabTarget;
      btns.forEach(function(b) {
        var on = b === btn;
        b.classList.toggle('cv-tab-btn-active', on);
        b.style.borderBottomColor = on ? (target === 'store' ? 'var(--accent,#FF5A36)' : '#2563eb') : 'transparent';
        b.style.color = on ? (target === 'store' ? '#c2410c' : '#1e40af') : 'var(--muted)';
        b.style.background = on ? '#fff' : 'transparent';
        b.style.fontWeight = on ? '800' : '700';
      });
      panels.forEach(function(p) {
        p.style.display = (p.dataset.tabPanel === target) ? '' : 'none';
      });
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
