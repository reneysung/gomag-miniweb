<?php
// admin/pages/seo.php  ─  SEO / AEO 設定（三頁 + OG + 結構化資料預覽）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$pageTitle = 'SEO / AEO 設定';
$clientId  = requireClientId();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $pages = ['home', 'services', 'cases'];
    foreach ($pages as $pk) {
        $data = [
            'meta_title' => trim($_POST[$pk . '_title'] ?? ''),
            'meta_desc'  => trim($_POST[$pk . '_desc'] ?? ''),
            'og_title'   => trim($_POST[$pk . '_og_title'] ?? ''),
        ];

        // OG Image 上傳
        $ogImagePath = null;
        if (!empty($_FILES[$pk . '_og_image']['name']) && $_FILES[$pk . '_og_image']['error'] === UPLOAD_ERR_OK) {
            $ogImagePath = uploadImage($_FILES[$pk . '_og_image'], 'seo');
        }

        $check = $db->prepare('SELECT id, og_image FROM seo_settings WHERE client_id=? AND page_key=?');
        $check->execute([$clientId, $pk]);
        $existing = $check->fetch();

        if ($existing) {
            $sql = 'UPDATE seo_settings SET meta_title=?,meta_desc=?,og_title=?';
            $params = [$data['meta_title'], $data['meta_desc'], $data['og_title']];
            if ($ogImagePath) {
                $sql .= ',og_image=?';
                $params[] = $ogImagePath;
            }
            $sql .= ' WHERE client_id=? AND page_key=?';
            $params[] = $clientId;
            $params[] = $pk;
            $db->prepare($sql)->execute($params);
        } else {
            $db->prepare('INSERT INTO seo_settings (client_id,page_key,meta_title,meta_desc,og_title,og_image) VALUES (?,?,?,?,?,?)')
               ->execute([$clientId, $pk, $data['meta_title'], $data['meta_desc'], $data['og_title'], $ogImagePath ?? '']);
        }
    }
    setFlash('success', 'SEO / AEO 設定已儲存！');
    redirect(BASE_URL . '/admin/pages/seo.php');
}

// 讀取現有 SEO
$seoData = [];
$rows = $db->prepare('SELECT * FROM seo_settings WHERE client_id=?');
$rows->execute([$clientId]);
foreach ($rows->fetchAll() as $r) $seoData[$r['page_key']] = $r;

// 讀取客戶資料（結構化資料預覽用）
$clientData = $db->prepare('SELECT * FROM clients WHERE id=?');
$clientData->execute([$clientId]);
$cl = $clientData->fetch();
$s1 = $db->prepare('SELECT COUNT(*) FROM services WHERE client_id=? AND is_active=1');
$s1->execute([$clientId]);
$svcCount = (int)$s1->fetchColumn();

$s2 = $db->prepare('SELECT COUNT(*) FROM service_faqs WHERE client_id=?');
$s2->execute([$clientId]);
$faqCount = (int)$s2->fetchColumn();

$s3 = $db->prepare('SELECT COUNT(*) FROM testimonials WHERE client_id=? AND is_active=1');
$s3->execute([$clientId]);
$reviewCount = (int)$s3->fetchColumn();

require_once __DIR__ . '/../includes/layout_head.php';

$pageLabels = ['home'=>'🏠 首頁', 'services'=>'🛠️ 服務頁', 'cases'=>'📸 案例頁'];
?>

<!-- AEO 結構化資料狀態 -->
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--success)">
  <div class="card-header"><h2>🤖 AEO 結構化資料狀態</h2></div>
  <div class="card-body">
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:16px">
      系統已自動根據您的資料產生 JSON-LD 結構化標記，讓 Google 與 AI 搜尋引擎讀取。以下是目前狀態：
    </p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px">
      <?php
      $checks = [
          ['LocalBusiness', $cl ? true : false, '品牌、電話、地址'],
          ['Service', $svcCount > 0, $svcCount . ' 個服務項目'],
          ['FAQPage', $faqCount > 0, $faqCount . ' 則 FAQ'],
          ['AggregateRating', $reviewCount > 0, $reviewCount . ' 則評價'],
          ['BreadcrumbList', true, '自動產生'],
          ['Canonical URL', true, '自動產生'],
          ['Sitemap.xml', true, '<a href="'.BASE_URL.'/sitemap.php" target="_blank">查看</a>'],
          ['OG Tags', true, '自動產生'],
      ];
      foreach ($checks as $chk): ?>
      <div style="background:var(--bg);border-radius:8px;padding:12px;border:1px solid var(--border)">
        <div style="font-size:.75rem;font-weight:700;color:<?= $chk[1] ? 'var(--success)' : 'var(--warning)' ?>;margin-bottom:4px">
          <?= $chk[1] ? '✅' : '⚠️' ?> <?= $chk[0] ?>
        </div>
        <div style="font-size:.72rem;color:var(--muted)"><?= $chk[2] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div style="margin-bottom:16px;">
  <p style="color:var(--muted);font-size:.875rem;">
    SEO Title 建議 30-60 字；Meta Description 建議 70-160 字；OG Title 留空則與 Meta Title 相同。
  </p>
</div>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="_token" value="<?= csrfToken() ?>">
  <?php foreach ($pageLabels as $pk => $pl): $s = $seoData[$pk] ?? []; ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h2><?= $pl ?></h2></div>
    <div class="card-body">
      <div class="form-group-admin">
        <label>Meta Title</label>
        <input type="text" name="<?= $pk ?>_title" class="form-control"
               value="<?= h($s['meta_title'] ?? '') ?>"
               placeholder="品牌名｜服務重點關鍵字"
               oninput="updateCounter(this,'<?= $pk ?>_title_cnt',30,60)">
        <div class="hint">
          字數：<span id="<?= $pk ?>_title_cnt"><?= mb_strlen($s['meta_title'] ?? '') ?></span> / 建議 30-60
        </div>
      </div>
      <div class="form-group-admin">
        <label>Meta Description</label>
        <textarea name="<?= $pk ?>_desc" class="form-control" rows="3"
                  oninput="updateCounter(this,'<?= $pk ?>_desc_cnt',70,160)"><?= h($s['meta_desc'] ?? '') ?></textarea>
        <div class="hint">
          字數：<span id="<?= $pk ?>_desc_cnt"><?= mb_strlen($s['meta_desc'] ?? '') ?></span> / 建議 70-160
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:8px">
        <div class="form-group-admin">
          <label>OG Title（社群分享標題）</label>
          <input type="text" name="<?= $pk ?>_og_title" class="form-control"
                 value="<?= h($s['og_title'] ?? '') ?>"
                 placeholder="留空則使用 Meta Title">
        </div>
        <div class="form-group-admin">
          <label>OG Image（社群分享圖片）</label>
          <?php if (!empty($s['og_image'])): ?>
            <div style="margin-bottom:8px">
              <img src="<?= BASE_URL . '/' . h($s['og_image']) ?>" style="max-width:200px;max-height:100px;border-radius:6px;border:1px solid var(--border)">
            </div>
          <?php endif; ?>
          <input type="file" name="<?= $pk ?>_og_image" accept="image/*"
                 style="font-size:.85rem">
          <div class="hint">建議 1200×630px，JPG 或 PNG</div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <button type="submit" class="btn btn-primary">💾 儲存 SEO / AEO 設定</button>
</form>

<script>
function updateCounter(el, counterId, min, max) {
  const len = el.value.length;
  const counter = document.getElementById(counterId);
  if (!counter) return;
  counter.textContent = len;
  counter.style.color = len < min ? 'var(--warning)' : len > max ? 'var(--danger)' : 'var(--success)';
}
// 初始化顏色
document.querySelectorAll('[oninput]').forEach(el => el.dispatchEvent(new Event('input')));
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
