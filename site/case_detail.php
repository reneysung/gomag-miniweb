<?php
// site/case_detail.php — Mini-site 案例詳細頁 /cases/{slug}
// 旭森風格：每案例獨立 URL + Article schema + before/after 對比
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';
require_once __DIR__ . '/../includes/front_functions.php';

header('Cache-Control: public, max-age=300, must-revalidate');

$sub = getSubdomain();
if (!$sub) { http_response_code(404); die('找不到網站'); }

$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在或已停用'); }
if (empty($site['client']['has_minisite'])) { http_response_code(404); die('未啟用小官網'); }

$caseSlug = trim($_GET['case_slug'] ?? $_GET['c'] ?? '');
if (!$caseSlug) { http_response_code(404); die('未指定案例'); }

// 從 loadSiteData 撈出的 cases array 找
$case = null;
foreach ($site['cases'] as $c) {
    if (!empty($c['slug']) && $c['slug'] === $caseSlug) { $case = $c; break; }
}
if (!$case) { http_response_code(404); die('找不到該案例'); }

$slug    = $sub;
$pageKey = 'case_detail';
$client  = $site['client'];
$social  = $site['social'];
$phone   = $client['phone'] ?? '';

$lineUrl = '';
if (!empty($social['line_url']) && filter_var($social['line_url'], FILTER_VALIDATE_URL)) {
    $lineUrl = $social['line_url'];
} elseif (!empty($social['line_id'])) {
    $rawId = ltrim(trim($social['line_id']), '@');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $rawId)) $lineUrl = 'https://line.me/R/ti/p/@' . $rawId;
}

// 城市
$city = $client['address_region'] ?? '';
if (!$city && !empty($client['address'])) {
    if (preg_match('/^(臺?[北中南東]市|新北市|桃園市|基隆市|新竹[市縣]|苗栗縣|彰化縣|南投縣|雲林縣|嘉義[市縣]|屏東縣|宜蘭縣|花蓮縣|臺?東縣|澎湖縣|金門縣|連江縣)/u', $client['address'], $m)) {
        $city = str_replace('臺', '台', $m[1]);
    }
}

// ── SEO meta ──
// title 模板：{案例 title}｜{品牌名}（title 本身已含地點/特色）
$metaTitle = "{$case['title']}｜{$client['brand_name']}";
$metaDesc  = !empty($case['description'])
    ? mb_strimwidth(strip_tags($case['description']), 0, 150, '…')
    : "{$case['title']} — {$client['brand_name']}施工案例" . ($case['location'] ? "（{$case['location']}）" : '');

// canonical
if (IS_LOCAL || IS_STAGING) {
    $canonicalUrl = BASE_URL . '/site/case_detail.php?sub=' . urlencode($sub) . '&case_slug=' . urlencode($caseSlug);
} else {
    $canonicalUrl = 'https://' . $sub . '.' . MINISITE_DOMAIN . '/cases/' . urlencode($caseSlug);
}

// before/after 圖（單檔 path 或資料夾 → caseThumb()）
$beforeImg = !empty($case['before_image']) ? caseThumb($case['before_image']) : '';
$afterImg  = !empty($case['after_image'])  ? caseThumb($case['after_image'])  : '';
$ogImage = $afterImg ? BASE_URL . '/' . $afterImg : ($beforeImg ? BASE_URL . '/' . $beforeImg : ($client['hero_image_path'] ? BASE_URL . '/' . $client['hero_image_path'] : ''));

// 相關案例（其他 3 個案例，排除自己）
$relatedCases = array_filter($site['cases'], fn($c) => ($c['slug'] ?? '') !== $caseSlug);
$relatedCases = array_slice($relatedCases, 0, 3);

$seo = [
    'meta_title' => $metaTitle,
    'meta_desc'  => $metaDesc,
    'og_image'   => $ogImage,
    'og_title'   => $metaTitle,
];
$site['seo']['case_detail'] = $seo;
$site['_current_case'] = $case;

require __DIR__ . '/layout_head.php';
?>

<!-- Hero（案例大圖） -->
<section style="background:linear-gradient(135deg,var(--g-ink) 0%,rgba(var(--g-ink-rgb),.85) 100%);color:#fff;padding:80px 0 70px;position:relative;overflow:hidden">
  <?php if ($ogImage): ?>
    <div style="position:absolute;inset:0;background:url('<?= h($ogImage) ?>') center/cover;opacity:.22;"></div>
  <?php endif; ?>
  <div class="container" style="position:relative;text-align:center">
    <nav class="breadcrumb" style="color:rgba(255,255,255,.7);margin-bottom:18px;font-size:.85rem;justify-content:center;display:flex">
      <a href="<?= siteUrl($sub) ?>" style="color:#fff">首頁</a>
      <span style="margin:0 6px"> / </span>
      <a href="<?= siteUrl($sub, 'cases') ?>" style="color:#fff">施工案例</a>
      <span style="margin:0 6px"> / </span>
      <span style="opacity:.85"><?= h($case['title']) ?></span>
    </nav>
    <h1 style="font-size:clamp(1.7rem,4vw,2.6rem);font-weight:900;line-height:1.35;margin-bottom:14px;letter-spacing:.02em">
      <?= h($case['title']) ?>
    </h1>
    <div style="display:inline-flex;flex-wrap:wrap;gap:14px;font-size:.9rem;opacity:.85;justify-content:center">
      <?php if (!empty($case['location'])): ?><span>📍 <?= h($case['location']) ?></span><?php endif; ?>
      <?php if (!empty($case['area_sqm'])): ?><span>📐 <?= h($case['area_sqm']) ?> 坪</span><?php endif; ?>
      <?php if (!empty($case['svc_name'])): ?><span>🛠️ <?= h($case['svc_name']) ?></span><?php endif; ?>
    </div>
  </div>
</section>

<!-- Before / After 相簿（掃 before/after 資料夾，各最多 15 張）+ 點圖放大 lightbox -->
<?php
$_docroot      = dirname(__DIR__);
$_beforeFolder = !empty($case['before_image']) ? $_docroot . '/' . dirname($case['before_image']) : '';
$_afterFolder  = !empty($case['after_image'])  ? $_docroot . '/' . dirname($case['after_image'])  : '';
$_beforeImgs = ($_beforeFolder && is_dir($_beforeFolder)) ? (glob($_beforeFolder . '/*.{jpg,jpeg,JPG,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE) ?: []) : [];
$_afterImgs  = ($_afterFolder  && is_dir($_afterFolder))  ? (glob($_afterFolder  . '/*.{jpg,jpeg,JPG,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE) ?: []) : [];
sort($_beforeImgs); sort($_afterImgs);
$_beforeImgs = array_slice($_beforeImgs, 0, 15);
$_afterImgs  = array_slice($_afterImgs,  0, 15);
$_relBefore = array_map(fn($f) => ltrim(substr($f, strlen($_docroot)), '/'), $_beforeImgs);
$_relAfter  = array_map(fn($f) => ltrim(substr($f, strlen($_docroot)), '/'), $_afterImgs);
?>
<?php if ($_relBefore || $_relAfter): ?>
<section class="section">
  <div class="container">
    <?php // 只有單一相簿（無前後分類，例如完工照或客戶自製對比拼圖）→ 標題不寫 Before/After ?>
    <h2 style="font-size:1.4rem;font-weight:900;margin-bottom:24px;color:var(--g-ink);text-align:center"><?= $_relBefore ? 'Before / After 對比相簿' : '施工相簿' ?></h2>

    <?php if ($_relBefore): ?>
    <h3 style="font-size:1rem;font-weight:700;color:var(--g-ink);margin:8px 0 12px;display:flex;align-items:center;gap:8px">📷 施作前 <span style="font-size:.78rem;color:#888;font-weight:500">(<?= count($_relBefore) ?> 張)</span></h3>
    <div class="case-album" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:32px">
      <?php foreach ($_relBefore as $_p): ?>
      <a class="case-album-item" href="<?= BASE_URL . '/' . h($_p) ?>" data-lb style="display:block;aspect-ratio:4/3;overflow:hidden;border-radius:10px;background:#f5f5f5">
        <img src="<?= BASE_URL . '/' . h($_p) ?>" loading="lazy" alt="<?= h($case['title']) ?>・施作前" style="width:100%;height:100%;object-fit:cover;transition:transform .3s">
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($_relAfter): ?>
    <h3 style="font-size:1rem;font-weight:700;color:var(--g-accent);margin:8px 0 12px;display:flex;align-items:center;gap:8px"><?= $_relBefore ? '✨ 施作後' : '📷 施工紀錄' ?> <span style="font-size:.78rem;color:#888;font-weight:500">(<?= count($_relAfter) ?> 張)</span></h3>
    <div class="case-album" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:8px">
      <?php foreach ($_relAfter as $_p): ?>
      <a class="case-album-item" href="<?= BASE_URL . '/' . h($_p) ?>" data-lb style="display:block;aspect-ratio:4/3;overflow:hidden;border-radius:10px;background:#f5f5f5">
        <img src="<?= BASE_URL . '/' . h($_p) ?>" loading="lazy" alt="<?= h($case['title']) ?>・施作後" style="width:100%;height:100%;object-fit:cover;transition:transform .3s">
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<style>
.case-album-item:hover img{transform:scale(1.05)}
@media (max-width:780px){.case-album{grid-template-columns:repeat(2,1fr)!important}}
@media (max-width:480px){.case-album{grid-template-columns:1fr!important}}
.lb-overlay{position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;display:none;align-items:center;justify-content:center}
.lb-overlay.open{display:flex}
.lb-overlay img{max-width:95vw;max-height:90vh;object-fit:contain}
.lb-overlay button{position:absolute;background:rgba(255,255,255,.15);color:#fff;border:none;width:48px;height:48px;border-radius:50%;cursor:pointer;font-size:1.6rem;line-height:1;display:flex;align-items:center;justify-content:center}
.lb-overlay button:hover{background:rgba(255,255,255,.28)}
.lb-close{top:18px;right:18px}.lb-prev{left:18px;top:50%;transform:translateY(-50%)}.lb-next{right:18px;top:50%;transform:translateY(-50%)}
.lb-counter{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.5);color:#fff;padding:4px 14px;border-radius:100px;font-size:.85rem}
</style>
<div class="lb-overlay" id="caseLightbox" role="dialog" aria-modal="true">
  <button class="lb-close" aria-label="關閉">✕</button>
  <button class="lb-prev" aria-label="上一張">‹</button>
  <img id="lbImg" alt="">
  <button class="lb-next" aria-label="下一張">›</button>
  <div class="lb-counter" id="lbCounter"></div>
</div>
<script>
(function(){
  var items=document.querySelectorAll('.case-album-item[data-lb]');
  if(!items.length) return;
  var overlay=document.getElementById('caseLightbox'),img=document.getElementById('lbImg'),counter=document.getElementById('lbCounter');
  var urls=Array.prototype.map.call(items,function(a){return a.href;});
  var alts=Array.prototype.map.call(items,function(a){var m=a.querySelector('img');return m?m.alt:'';});
  var idx=0;
  function show(i){idx=(i+urls.length)%urls.length;img.src=urls[idx];img.alt=alts[idx]||'';counter.textContent=(idx+1)+' / '+urls.length;overlay.classList.add('open');document.body.style.overflow='hidden';}
  function close(){overlay.classList.remove('open');document.body.style.overflow='';}
  items.forEach(function(a,i){a.addEventListener('click',function(e){e.preventDefault();show(i);});});
  overlay.querySelector('.lb-close').addEventListener('click',close);
  overlay.querySelector('.lb-prev').addEventListener('click',function(e){e.stopPropagation();show(idx-1);});
  overlay.querySelector('.lb-next').addEventListener('click',function(e){e.stopPropagation();show(idx+1);});
  overlay.addEventListener('click',function(e){if(e.target===overlay)close();});
  document.addEventListener('keydown',function(e){if(!overlay.classList.contains('open'))return;if(e.key==='Escape')close();else if(e.key==='ArrowLeft')show(idx-1);else if(e.key==='ArrowRight')show(idx+1);});
  var sx=0;
  overlay.addEventListener('touchstart',function(e){sx=e.changedTouches[0].screenX;});
  overlay.addEventListener('touchend',function(e){var dx=e.changedTouches[0].screenX-sx;if(Math.abs(dx)>40)show(idx+(dx<0?1:-1));});
})();
</script>
<?php endif; ?>

<!-- 案例描述 -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div style="max-width:720px;margin:0 auto">
      <?php if (!empty($case['description'])): ?>
      <div class="rich-content" style="font-size:1.02rem;line-height:1.85;color:var(--g-ink-soft)">
        <?= nl2br(h($case['description'])) ?>
      </div>
      <?php else: ?>
      <p style="text-align:center;color:#888">案例詳情整理中，歡迎來電諮詢。</p>
      <?php endif; ?>

      <!-- CTA -->
      <div style="margin-top:48px;padding:32px;background:var(--g-bg-alt);border-radius:14px;text-align:center">
        <h3 style="font-size:1.2rem;font-weight:800;margin-bottom:8px;color:var(--g-ink)">想要類似的成果嗎？</h3>
        <p style="color:#666;margin-bottom:20px"><?= h($city) ?>地區免費丈量、現場估價、無壓力諮詢</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
          <?php if ($phone): ?>
            <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn btn-primary">📞 <?= h($phone) ?></a>
          <?php endif; ?>
          <?php if ($lineUrl): ?>
            <a href="<?= h($lineUrl) ?>" target="_blank" class="btn btn-accent">💬 LINE 諮詢</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 相關案例 -->
<?php if ($relatedCases): ?>
<section class="section" style="background:var(--g-bg-alt);padding:64px 0">
  <div class="container">
    <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:24px;color:var(--g-ink);text-align:center">其他案例</h2>
    <div class="grid-3" style="gap:20px">
      <?php foreach ($relatedCases as $rc):
        $rcImg = !empty($rc['after_image']) ? caseThumb($rc['after_image']) : (!empty($rc['before_image']) ? caseThumb($rc['before_image']) : '');
      ?>
        <a href="<?= siteUrl($sub, 'cases') ?>/<?= rawurlencode($rc['slug'] ?? '') ?>" style="display:block;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);transition:all .2s;color:var(--g-ink);text-decoration:none">
          <?php if ($rcImg): ?>
            <div style="aspect-ratio:4/3;background:url('<?= BASE_URL . '/' . h($rcImg) ?>') center/cover"></div>
          <?php endif; ?>
          <div style="padding:18px">
            <div style="font-weight:700;font-size:1rem;margin-bottom:6px;line-height:1.4"><?= h($rc['title']) ?></div>
            <?php if (!empty($rc['location'])): ?>
              <div style="font-size:.85rem;color:#888">📍 <?= h($rc['location']) ?></div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/layout_foot.php'; ?>
