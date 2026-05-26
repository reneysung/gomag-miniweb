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

    $fields = [
        'client_id'              => $clientId,
        'city_slug'              => $citySlug,
        'city_label'             => $cityLabel ?: $citySlug,
        'brand_name'             => trim($_POST['brand_name'] ?? '') ?: null,
        'store_meta_title'       => trim($_POST['store_meta_title'] ?? '') ?: null,
        'store_meta_desc'        => trim($_POST['store_meta_desc'] ?? '') ?: null,
        'store_keywords'         => trim($_POST['store_keywords'] ?? '') ?: null,
        'landing_extra_content'  => trim($_POST['landing_extra_content'] ?? '') ?: null,
        'store_og_image'         => trim($_POST['store_og_image'] ?? '') ?: null,
        'hero_image_path'        => $heroPath ?: null,
        'filter_cases_by_region' => !empty($_POST['filter_cases_by_region']) ? 1 : 0,
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
      預覽 URL：<code>/store/<?= h($sub) ?>/{city}</code>（儲存後可在列表預覽）
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
    </div>
  </div>

  <!-- SEO 覆寫 -->
  <div class="card" style="margin-top:20px;">
    <div class="card-header"><h2>🔍 SEO 覆寫（留空 = 用主檔）</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>品牌名稱（顯示在此城市頁）</label>
        <input type="text" name="brand_name" class="form-control" maxlength="120"
               placeholder="留空 = 用主檔「<?= h($client['brand_name']) ?>」"
               value="<?= h($row['brand_name'] ?? '') ?>">
        <div class="hint">想讓城市頁標示「亞雷台中清潔團隊」之類更貼近在地的品牌名時填這裡。</div>
      </div>

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

  <!-- Hero / 內容覆寫 -->
  <div class="card" style="margin-top:20px;">
    <div class="card-header"><h2>🖼️ Hero 圖 + 一頁式延伸內容</h2></div>
    <div class="card-body">
      <div class="form-group">
        <label>Hero 圖（此城市專用）</label>
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
    </div>
  </div>

  <div class="form-actions" style="margin-top:24px; display:flex; gap:10px;">
    <button type="submit" class="btn btn-primary btn-lg">💾 儲存城市變體</button>
    <a href="<?= BASE_URL ?>/admin/pages/store_cities.php" class="btn btn-ghost btn-lg">取消</a>
    <?php if ($row): ?>
      <a href="<?= BASE_URL ?>/store/<?= h($sub) ?>/<?= h($row['city_slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg" style="margin-left:auto;">🌐 預覽此城市頁</a>
    <?php endif; ?>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
