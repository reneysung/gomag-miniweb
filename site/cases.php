<?php
// site/cases.php ─ 施工案例（相簿模式）
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

header('Cache-Control: public, max-age=300, must-revalidate');

$sub  = getSubdomain();
$slug = $sub;
$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在'); }

$pageKey  = 'cases';
$client   = $site['client'];

// 模板系統 sub-page 路由
require_once __DIR__ . '/../includes/minisite_template_loader.php';
$_mr = minisiteSubpageRoute('cases', $sub, $client);
if (!empty($_mr['redirect'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . $_mr['redirect'], true, 302);
    exit;
}
if (!empty($_mr['render'])) {
    require __DIR__ . '/layout_head.php';
    require $_mr['render'];
    require __DIR__ . '/layout_foot.php';
    exit;
}

$social   = $site['social'] ?? [];
$services = $site['services'] ?? [];
$phone    = $client['phone'] ?? '';

$lineUrl = '';
if (!empty($social['line_url']) && filter_var($social['line_url'], FILTER_VALIDATE_URL)) {
    $lineUrl = $social['line_url'];
} elseif (!empty($social['line_id'])) {
    $rawId = ltrim(trim($social['line_id']), '@');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $rawId)) $lineUrl = 'https://line.me/R/ti/p/@' . $rawId;
}

// ── 地區案例頁 /cases/{region}（台中 / 彰化 SEO 著陸頁）────────────────
// 由 .htaccess 規則帶入 ?region=taichung|changhua。下游模板共用 $site['cases']。
$caseAllRegions  = caseRegionsPresent($site['cases'] ?? []);  // 過濾前的全部地區（給切換列）
$caseRegion      = strtolower(trim($_GET['region'] ?? ''));
$caseRegionLabel = '';                 // 模板判斷是否地區模式：'' = 全部案例
$caseHeroTitle   = '';                 // 地區模式 hero H1（給 cases_service.php 用）
$caseHeroSub     = '';
if ($caseRegion !== '') {
    $regionMap = caseRegionMap();
    if (!isset($regionMap[$caseRegion])) { http_response_code(404); die('找不到該地區'); }
    $caseRegionLabel = $regionMap[$caseRegion]['label'];

    // 只留該地區案例
    $site['cases'] = array_values(array_filter(
        $site['cases'] ?? [],
        fn($c) => caseRegionSlug($c['location'] ?? '') === $caseRegion
    ));

    // 業務名詞：清潔公司 → 清潔（去掉法人/組織尾綴）
    $bizNoun = preg_replace('/(股份有限公司|有限公司|公司|工作室|事務所|工坊|團隊|商行|企業社)$/u', '', (string)($client['industry'] ?? ''));
    if ($bizNoun === '') $bizNoun = '清潔';
    $brand = $client['brand_name'] ?? '';

    // SEO 覆寫（layout_head 透過 getSeo($site,'cases') 讀）
    $seoTitle = "{$caseRegionLabel}{$bizNoun}案例｜{$brand}";
    $seoDesc  = "{$brand}{$caseRegionLabel}地區{$bizNoun}施工實績：裝潢細清、石材晶化、地毯清洗、地板打蠟保養，真實 Before／After 對比案例參考。免費到府估價。";

    // og:image 優先：seo_settings.cases.og_image > 該地區第一筆案例的 after_image > 客戶 hero_image_path
    $_regionOg = $site['seo']['cases']['og_image'] ?? '';
    if (!$_regionOg && !empty($site['cases'])) {
        foreach ($site['cases'] as $_rc) {
            if (!empty($_rc['after_image'])) { $_regionOg = $_rc['after_image']; break; }
            if (!empty($_rc['before_image'])) { $_regionOg = $_rc['before_image']; break; }
        }
    }
    if (!$_regionOg && !empty($client['hero_image_path'])) $_regionOg = $client['hero_image_path'];
    if ($_regionOg && !str_starts_with($_regionOg, 'http')) $_regionOg = BASE_URL . '/' . $_regionOg;

    $site['seo']['cases'] = [
        'meta_title' => $seoTitle,
        'meta_desc'  => $seoDesc,
        'og_title'   => $seoTitle,
        'og_image'   => $_regionOg,
    ];

    // canonical 指向地區頁本身
    $canonicalUrl = (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/site/cases.php?sub=' . urlencode($sub) . '&region=' . urlencode($caseRegion)
        : siteUrl($sub, 'cases') . '/' . $caseRegion;

    $caseHeroTitle = "{$caseRegionLabel}{$bizNoun}案例";
    $caseHeroSub   = "{$brand} · {$caseRegionLabel}地區施工實績，真實 Before／After";
}

$ind = $client['industry'] ?? '';
$isFood = (bool)preg_match('/(餐|食|料理|咖啡|甜點|甜品|烘焙|燒肉|牛排|火鍋|鍋物|壽司|麵|飯|披薩|拉麵|烤|飲料|手搖|茶飲|甜|蛋糕|麵包|食坊|食堂|宵夜)/u', $ind);
$isDesign = !$isFood && (bool)preg_match('/(室內設計|室內裝修|室內裝潢|空間設計|空間規劃|裝潢設計|建築設計|景觀設計|商空設計|店面設計|室內空間)/u', $ind);

if ($isFood) {
    require __DIR__ . '/cases_food.php';
    return;
}

if ($isDesign) {
    require __DIR__ . '/cases_design.php';
    return;
}

// 非食物業 → PrettyClean 風新版
require __DIR__ . '/cases_service.php';
return;
// === 以下舊版保留作 fallback ===

// Helper: scan folder for images
function scanPhotos(string $path): array {
    $base = dirname(__DIR__); // /Applications/MAMP/htdocs/miniweb
    $full = $base . '/' . $path;
    if (!is_dir($full)) return [];
    $imgs = [];
    foreach (glob($full . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
        $imgs[] = $path . '/' . basename($f);
    }
    sort($imgs);
    return $imgs;
}

// Enrich cases with photo arrays
foreach ($site['cases'] as &$c) {
    $c['before_photos'] = ($c['before_image'] && is_dir(dirname(__DIR__) . '/' . $c['before_image']))
        ? scanPhotos($c['before_image'])
        : ($c['before_image'] ? [$c['before_image']] : []);
    $c['after_photos'] = ($c['after_image'] && is_dir(dirname(__DIR__) . '/' . $c['after_image']))
        ? scanPhotos($c['after_image'])
        : ($c['after_image'] ? [$c['after_image']] : []);
    $c['total_photos'] = count($c['before_photos']) + count($c['after_photos']);
}
unset($c);

// 依服務分組
$groups = ['all' => ['label'=>'全部案例','cases'=>[]]];
foreach ($site['services'] as $s) {
    $groups['svc_'.$s['id']] = ['label'=>$s['name'],'cases'=>[]];
}
foreach ($site['cases'] as $c) {
    $groups['all']['cases'][] = $c;
    if ($c['service_id'] && isset($groups['svc_'.$c['service_id']]))
        $groups['svc_'.$c['service_id']]['cases'][] = $c;
}
$groups = array_filter($groups, fn($g)=>!empty($g['cases']));

$ind = $client['industry'] ?? '';
$isFood = str_contains($ind,'餐') || str_contains($ind,'食') || str_contains($ind,'料理') || str_contains($ind,'咖啡') || str_contains($ind,'甜點');

require __DIR__ . '/layout_head.php';
?>

<div class="page-banner">
  <div class="container">
    <?php if($isFood): ?>
      <h1>🍽️ 料理作品</h1><p>主廚精選，每一道都是暖心之作</p>
    <?php else: ?>
      <h1>📸 施工案例</h1><p>真實 Before / After 呈現，每個案例都是我們的驕傲</p>
    <?php endif; ?>
  </div>
</div>
<div class="container">
  <div class="breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a> › <span><?= $isFood ? '料理作品' : '施工案例' ?></span></div>
</div>

<!-- 篩選 Tab -->
<div style="background:var(--g-bg-alt);border-bottom:1px solid rgba(var(--g-ink-rgb),.08);position:sticky;top:66px;z-index:150">
  <div class="container">
    <div style="display:flex;gap:0;overflow-x:auto;scrollbar-width:none">
      <?php $first=true; foreach($groups as $key=>$group): ?>
      <button onclick="filterCases(this,'<?= h($key) ?>')"
        data-filter="<?= h($key) ?>"
        style="display:flex;align-items:center;gap:6px;padding:14px 20px;font-size:.88rem;font-weight:700;color:<?= $first?'var(--g-ink)':'#888' ?>;background:none;border:none;border-bottom:3px solid <?= $first?'var(--g-ink)':'transparent' ?>;cursor:pointer;white-space:nowrap;transition:all .2s"
        class="ftab <?= $first?'ftab-active':'' ?>">
        <?= h($group['label']) ?>
        <span style="background:<?= $first?'var(--g-ink)':'rgba(var(--g-ink-rgb),.1)' ?>;color:<?= $first?'#fff':'var(--g-ink)' ?>;font-size:.7rem;padding:1px 7px;border-radius:20px;font-weight:800"><?= count($group['cases']) ?></span>
      </button>
      <?php $first=false; endforeach; ?>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <span id="caseCount" style="font-size:.88rem;color:#888;font-weight:600"><?= count($site['cases']) ?> <?= $isFood ? '道料理' : '個案例' ?></span>
      <div id="pageInfo" style="font-size:.82rem;color:#aaa;font-weight:600"></div>
    </div>

    <!-- 案例列表 -->
    <?php foreach($site['cases'] as $i=>$c): ?>
    <div class="ci animate-in delay-<?= min($i%4+1,4) ?>"
         data-svc="<?= $c['service_id'] ? 'svc_'.$c['service_id'] : '' ?>"
         style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.07);margin-bottom:32px">

      <!-- 案例標題列 -->
      <div style="padding:20px 24px 0;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <h3 style="font-size:1.1rem;font-weight:800;color:var(--g-ink-soft);margin:0"><?= h($c['title']) ?></h3>
        <?php if($c['svc_name']): ?>
          <span style="background:rgba(var(--g-ink-rgb),.08);color:var(--g-ink);font-size:.72rem;font-weight:700;padding:3px 12px;border-radius:12px"><?= h($c['svc_name']) ?></span>
        <?php endif; ?>
        <?php if($c['is_featured']): ?>
          <span style="background:rgba(var(--g-accent-rgb),.12);color:var(--g-accent);font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:12px">⭐ 精選</span>
        <?php endif; ?>
      </div>

      <!-- 案例資訊 -->
      <div style="padding:8px 24px 16px;display:flex;gap:16px;font-size:.82rem;color:#888;flex-wrap:wrap">
        <?php if($c['location']): ?><span>📍 <?= h($c['location']) ?></span><?php endif; ?>
        <?php if($c['area_sqm'] && !$isFood): ?><span>📐 <?= $c['area_sqm'] ?>坪</span><?php endif; ?>
        <?php if($c['total_photos']): ?><span>🖼️ <?= $c['total_photos'] ?> 張照片</span><?php endif; ?>
        <?php if($c['description']): ?><span style="color:#666"><?= h($c['description']) ?></span><?php endif; ?>
      </div>

      <?php if($c['before_photos'] || $c['after_photos']): ?>
      <!-- Before/After 相簿 -->
      <div style="padding:0 24px 24px">

        <?php if($c['before_photos']): ?>
        <div style="margin-bottom:16px">
          <div style="font-size:.78rem;font-weight:800;color:#999;letter-spacing:.1em;margin-bottom:10px;display:flex;align-items:center;gap:6px">
            <span style="display:inline-block;background:rgba(0,0,0,.6);color:#fff;padding:2px 10px;border-radius:4px;font-size:.7rem">BEFORE</span>
            施工前 · <?= count($c['before_photos']) ?> 張
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px">
            <?php foreach($c['before_photos'] as $pi=>$photo): ?>
            <div onclick="openLightbox(<?= $i ?>, 'before', <?= $pi ?>)" style="cursor:pointer;border-radius:12px;overflow:hidden;aspect-ratio:4/3;position:relative;background:#f0f0f0">
              <img src="<?= BASE_URL.'/'.h($photo) ?>" style="width:100%;height:100%;object-fit:cover;transition:transform .3s" loading="lazy"
                   onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''" alt="施工前">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if($c['after_photos']): ?>
        <div>
          <div style="font-size:.78rem;font-weight:800;color:#999;letter-spacing:.1em;margin-bottom:10px;display:flex;align-items:center;gap:6px">
            <span style="display:inline-block;background:var(--g-accent);color:#fff;padding:2px 10px;border-radius:4px;font-size:.7rem">AFTER</span>
            施工後 · <?= count($c['after_photos']) ?> 張
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px">
            <?php foreach($c['after_photos'] as $pi=>$photo): ?>
            <div onclick="openLightbox(<?= $i ?>, 'after', <?= $pi ?>)" style="cursor:pointer;border-radius:12px;overflow:hidden;aspect-ratio:4/3;position:relative;background:#f0f0f0">
              <img src="<?= BASE_URL.'/'.h($photo) ?>" style="width:100%;height:100%;object-fit:cover;transition:transform .3s" loading="lazy"
                   onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''" alt="施工後">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
      <?php else: ?>
      <div style="padding:0 24px 24px">
        <div style="height:100px;background:var(--g-bg-alt);display:flex;align-items:center;justify-content:center;color:#aaa;font-size:.9rem;border-radius:12px">📷 照片準備中</div>
      </div>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <?php if(empty($site['cases'])): ?>
    <div style="text-align:center;padding:60px 0;color:#888">
      <div style="font-size:3rem;margin-bottom:16px">📷</div>
      <div style="font-size:1rem;font-weight:600">案例陸續更新中，敬請期待！</div>
    </div>
    <?php endif; ?>

    <!-- 分頁控制 -->
    <div id="pagination" style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:32px;margin-bottom:8px"></div>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox(event)" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.92);align-items:center;justify-content:center;padding:20px">
  <button onclick="closeLightbox()" style="position:absolute;top:16px;right:20px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;z-index:10001">✕</button>
  <button id="lbPrev" onclick="lbNav(-1);event.stopPropagation()" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.8rem;width:48px;height:48px;border-radius:50%;cursor:pointer;z-index:10001">‹</button>
  <button id="lbNext" onclick="lbNav(1);event.stopPropagation()" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.8rem;width:48px;height:48px;border-radius:50%;cursor:pointer;z-index:10001">›</button>
  <img id="lbImg" src="" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:8px" alt="">
  <div id="lbCaption" style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.8);font-size:.85rem;font-weight:600;background:rgba(0,0,0,.5);padding:6px 16px;border-radius:20px"></div>
</div>

<section style="background:var(--g-ink);padding:56px 0;text-align:center;color:#fff">
  <div class="container animate-in">
    <h2 style="font-size:1.8rem;font-weight:900;margin-bottom:12px"><?= $isFood ? '想品嚐主廚的暖心料理嗎？' : '想讓您的空間也煥然一新嗎？' ?></h2>
    <p style="opacity:.8;margin-bottom:28px"><?= $isFood ? '歡迎來電訂位或 LINE 預約' : '免費估價，台南到府服務' ?></p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <?php $lu=$site['social']['line_url']??'#'; if($lu!=='#'): ?>
        <a href="<?= h($lu) ?>" class="btn" style="background:#06c755;border-color:#06c755;color:#fff" target="_blank">💬 LINE <?= $isFood ? '立即訂位' : '免費估價' ?></a>
      <?php endif; ?>
      <?php if($client['phone']): ?>
        <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)">📞 <?= h($client['phone']) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
// ── 分頁 + 篩選
const PER_PAGE = 6;
let currentFilter = 'all';
let currentPage = 1;

function getFiltered() {
  return [...document.querySelectorAll('.ci')].filter(el =>
    currentFilter === 'all' || el.dataset.svc === currentFilter
  );
}

function renderPage() {
  const items = getFiltered();
  const total = items.length;
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));
  if (currentPage > pages) currentPage = pages;
  const start = (currentPage - 1) * PER_PAGE;
  const end = start + PER_PAGE;

  // 先全部隱藏
  document.querySelectorAll('.ci').forEach(el => el.style.display = 'none');
  // 只顯示當前頁
  items.forEach((el, i) => {
    el.style.display = (i >= start && i < end) ? '' : 'none';
  });

  document.getElementById('caseCount').textContent = total + ' 個案例';
  document.getElementById('pageInfo').textContent = total > PER_PAGE ? '第 ' + currentPage + ' / ' + pages + ' 頁' : '';

  // 繪製分頁按鈕
  const box = document.getElementById('pagination');
  if (pages <= 1) { box.innerHTML = ''; return; }
  let html = '';
  const btnBase = 'display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;border:none;transition:all .2s;';
  // Prev
  html += '<button onclick="goPage(' + (currentPage-1) + ')" style="' + btnBase + 'background:' + (currentPage>1?'rgba(var(--g-ink-rgb),.08)':'#f0f0f0') + ';color:' + (currentPage>1?'var(--g-ink)':'#ccc') + '" ' + (currentPage<=1?'disabled':'') + '>‹</button>';
  for (let p = 1; p <= pages; p++) {
    const active = p === currentPage;
    html += '<button onclick="goPage(' + p + ')" style="' + btnBase + 'background:' + (active?'var(--g-ink)':'rgba(var(--g-ink-rgb),.06)') + ';color:' + (active?'#fff':'var(--g-ink)') + '">' + p + '</button>';
  }
  // Next
  html += '<button onclick="goPage(' + (currentPage+1) + ')" style="' + btnBase + 'background:' + (currentPage<pages?'rgba(var(--g-ink-rgb),.08)':'#f0f0f0') + ';color:' + (currentPage<pages?'var(--g-ink)':'#ccc') + '" ' + (currentPage>=pages?'disabled':'') + '>›</button>';
  box.innerHTML = html;
}

function goPage(p) {
  const pages = Math.max(1, Math.ceil(getFiltered().length / PER_PAGE));
  if (p < 1 || p > pages) return;
  currentPage = p;
  renderPage();
  window.scrollTo({top: document.querySelector('.section').offsetTop - 80, behavior:'smooth'});
}

function filterCases(btn, filter) {
  document.querySelectorAll('.ftab').forEach(b => {
    b.style.color='#888'; b.style.borderBottomColor='transparent';
    b.querySelector('span').style.background='rgba(var(--g-ink-rgb),.1)';
    b.querySelector('span').style.color='var(--g-ink)';
    b.classList.remove('ftab-active');
  });
  btn.style.color='var(--g-ink)'; btn.style.borderBottomColor='var(--g-ink)';
  btn.querySelector('span').style.background='var(--g-ink)';
  btn.querySelector('span').style.color='#fff';
  btn.classList.add('ftab-active');

  currentFilter = filter;
  currentPage = 1;
  renderPage();
}

// 初始化分頁
renderPage();

// ── Lightbox
const casePhotos = <?= json_encode(array_values(array_map(function($c){
    return ['before'=>$c['before_photos'],'after'=>$c['after_photos'],'title'=>$c['title']];
}, $site['cases']))) ?>;

let lbState = {caseIdx:0, type:'before', photoIdx:0};

function openLightbox(caseIdx, type, photoIdx) {
  lbState = {caseIdx, type, photoIdx};
  updateLightbox();
  document.getElementById('lightbox').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeLightbox(e) {
  if (e && e.target !== e.currentTarget) return;
  document.getElementById('lightbox').style.display = 'none';
  document.body.style.overflow = '';
}
function updateLightbox() {
  const c = casePhotos[lbState.caseIdx];
  const photos = c[lbState.type];
  const base = '<?= BASE_URL ?>/';
  document.getElementById('lbImg').src = base + photos[lbState.photoIdx];
  const label = lbState.type === 'before' ? '施工前' : '施工後';
  document.getElementById('lbCaption').textContent = c.title + ' · ' + label + ' (' + (lbState.photoIdx+1) + '/' + photos.length + ')';
}
function lbNav(dir) {
  event.stopPropagation();
  const c = casePhotos[lbState.caseIdx];
  const photos = c[lbState.type];
  let idx = lbState.photoIdx + dir;
  if (idx < 0) {
    if (lbState.type === 'after' && c.before.length) {
      lbState.type = 'before'; lbState.photoIdx = c.before.length - 1;
    } else return;
  } else if (idx >= photos.length) {
    if (lbState.type === 'before' && c.after.length) {
      lbState.type = 'after'; lbState.photoIdx = 0;
    } else return;
  } else {
    lbState.photoIdx = idx;
  }
  updateLightbox();
}
document.addEventListener('keydown', function(e) {
  if (document.getElementById('lightbox').style.display !== 'flex') return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft') lbNav(-1);
  if (e.key === 'ArrowRight') lbNav(1);
});
</script>

<?php require __DIR__ . '/layout_foot.php'; ?>
