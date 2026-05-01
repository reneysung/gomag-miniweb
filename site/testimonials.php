<?php
// site/testimonials.php ─ 客戶好評前台頁面
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

$sub  = getSubdomain();
$slug = $sub;
$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在'); }

$pageKey = 'testimonials';
$client  = $site['client'];
$ind = $client['industry'] ?? '';
$isFood = str_contains($ind,'餐') || str_contains($ind,'食') || str_contains($ind,'料理') || str_contains($ind,'咖啡') || str_contains($ind,'甜點');

// 依服務分組
$groups = ['all' => ['label'=>'全部評價','items'=>[]]];
foreach ($site['services'] as $s) {
    $groups['svc_'.$s['id']] = ['label'=>$s['name'],'items'=>[]];
}
foreach ($site['testimonials'] as $t) {
    $groups['all']['items'][] = $t;
    if ($t['service_id'] && isset($groups['svc_'.$t['service_id']]))
        $groups['svc_'.$t['service_id']]['items'][] = $t;
}
$groups = array_filter($groups, fn($g)=>!empty($g['items']));

require __DIR__ . '/layout_head.php';
?>

<div class="page-banner">
  <div class="container"><h1>⭐ 客戶好評</h1><p>真實口碑推薦，每一則都是客戶的親身體驗</p></div>
</div>
<div class="container">
  <div class="breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a> › <span>客戶好評</span></div>
</div>

<!-- 篩選 Tab -->
<?php if (count($groups) > 1): ?>
<div style="background:var(--c-light);border-bottom:1px solid rgba(var(--c-primary-rgb),.08);position:sticky;top:66px;z-index:150">
  <div class="container">
    <div style="display:flex;gap:0;overflow-x:auto;scrollbar-width:none">
      <?php $first=true; foreach($groups as $key=>$group): ?>
      <button onclick="filterItems(this,'<?= h($key) ?>')"
        data-filter="<?= h($key) ?>"
        style="display:flex;align-items:center;gap:6px;padding:14px 20px;font-size:.88rem;font-weight:700;color:<?= $first?'var(--c-primary)':'#888' ?>;background:none;border:none;border-bottom:3px solid <?= $first?'var(--c-primary)':'transparent' ?>;cursor:pointer;white-space:nowrap;transition:all .2s"
        class="ftab <?= $first?'ftab-active':'' ?>">
        <?= h($group['label']) ?>
        <span style="background:<?= $first?'var(--c-primary)':'rgba(var(--c-primary-rgb),.1)' ?>;color:<?= $first?'#fff':'var(--c-primary)' ?>;font-size:.7rem;padding:1px 7px;border-radius:20px;font-weight:800"><?= count($group['items']) ?></span>
      </button>
      <?php $first=false; endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <span id="itemCount" style="font-size:.88rem;color:#888;font-weight:600"><?= count($site['testimonials']) ?> 則評價</span>
      <div id="pageInfo" style="font-size:.82rem;color:#aaa;font-weight:600"></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px" id="itemsGrid">
      <?php foreach($site['testimonials'] as $i=>$t): ?>
      <div class="ti animate-in delay-<?= min($i%4+1,4) ?>"
           data-svc="<?= $t['service_id'] ? 'svc_'.$t['service_id'] : '' ?>">
        <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);transition:transform .22s,box-shadow .22s"
             onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 12px 36px rgba(0,0,0,.14)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">

          <?php if(!empty($t['og_image'])): ?>
          <div style="position:relative;height:200px;overflow:hidden">
            <img src="<?= h($t['og_image']) ?>" style="width:100%;height:100%;object-fit:cover" loading="lazy" alt="口碑文章">
            <div style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.5);color:#fff;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:4px">口碑文章</div>
          </div>
          <?php endif; ?>

          <div style="padding:18px 20px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
              <div style="color:#f4a611;font-size:.95rem;letter-spacing:1px"><?= str_repeat('★', (int)($t['rating'] ?? 5)) ?></div>
              <?php if($t['svc_name']): ?>
                <span style="background:rgba(var(--c-primary-rgb),.08);color:var(--c-primary);font-size:.7rem;font-weight:700;padding:2px 10px;border-radius:12px"><?= h($t['svc_name']) ?></span>
              <?php endif; ?>
            </div>

            <p style="font-size:.88rem;color:#555;line-height:1.8;margin-bottom:14px;font-style:italic">"<?= h($t['content']) ?>"</p>

            <?php if(!empty($t['source_url'])): ?>
            <div style="padding-top:12px;border-top:1px solid #f0f0f0">
              <a href="<?= h($t['source_url']) ?>" target="_blank" rel="noopener" style="font-size:.78rem;color:var(--c-primary);text-decoration:none;font-weight:600">📖 閱讀全文 →</a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if(empty($site['testimonials'])): ?>
    <div style="text-align:center;padding:60px 0;color:#888">
      <div style="font-size:3rem;margin-bottom:16px">⭐</div>
      <div style="font-size:1rem;font-weight:600">客戶好評陸續新增中，敬請期待！</div>
    </div>
    <?php endif; ?>

    <!-- 分頁控制 -->
    <div id="pagination" style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:32px;margin-bottom:8px"></div>
  </div>
</section>

<section style="background:var(--c-primary);padding:56px 0;text-align:center;color:#fff">
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
  return [...document.querySelectorAll('.ti')].filter(el =>
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

  document.querySelectorAll('.ti').forEach(el => el.style.display = 'none');
  items.forEach((el, i) => {
    el.style.display = (i >= start && i < end) ? '' : 'none';
  });

  document.getElementById('itemCount').textContent = total + ' 則評價';
  document.getElementById('pageInfo').textContent = total > PER_PAGE ? '第 ' + currentPage + ' / ' + pages + ' 頁' : '';

  const box = document.getElementById('pagination');
  if (pages <= 1) { box.innerHTML = ''; return; }
  let html = '';
  const btnBase = 'display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:10px;font-size:.85rem;font-weight:700;cursor:pointer;border:none;transition:all .2s;';
  html += '<button onclick="goPage(' + (currentPage-1) + ')" style="' + btnBase + 'background:' + (currentPage>1?'rgba(var(--c-primary-rgb),.08)':'#f0f0f0') + ';color:' + (currentPage>1?'var(--c-primary)':'#ccc') + '" ' + (currentPage<=1?'disabled':'') + '>‹</button>';
  for (let p = 1; p <= pages; p++) {
    const active = p === currentPage;
    html += '<button onclick="goPage(' + p + ')" style="' + btnBase + 'background:' + (active?'var(--c-primary)':'rgba(var(--c-primary-rgb),.06)') + ';color:' + (active?'#fff':'var(--c-primary)') + '">' + p + '</button>';
  }
  html += '<button onclick="goPage(' + (currentPage+1) + ')" style="' + btnBase + 'background:' + (currentPage<pages?'rgba(var(--c-primary-rgb),.08)':'#f0f0f0') + ';color:' + (currentPage<pages?'var(--c-primary)':'#ccc') + '" ' + (currentPage>=pages?'disabled':'') + '>›</button>';
  box.innerHTML = html;
}

function goPage(p) {
  const pages = Math.max(1, Math.ceil(getFiltered().length / PER_PAGE));
  if (p < 1 || p > pages) return;
  currentPage = p;
  renderPage();
  window.scrollTo({top: document.querySelector('.section').offsetTop - 80, behavior:'smooth'});
}

function filterItems(btn, filter) {
  document.querySelectorAll('.ftab').forEach(b => {
    b.style.color='#888'; b.style.borderBottomColor='transparent';
    b.querySelector('span').style.background='rgba(var(--c-primary-rgb),.1)';
    b.querySelector('span').style.color='var(--c-primary)';
    b.classList.remove('ftab-active');
  });
  btn.style.color='var(--c-primary)'; btn.style.borderBottomColor='var(--c-primary)';
  btn.querySelector('span').style.background='var(--c-primary)';
  btn.querySelector('span').style.color='#fff';
  btn.classList.add('ftab-active');

  currentFilter = filter;
  currentPage = 1;
  renderPage();
}

renderPage();
</script>

<?php require __DIR__ . '/layout_foot.php'; ?>
