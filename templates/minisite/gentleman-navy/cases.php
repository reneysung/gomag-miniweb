<?php
/**
 * templates/minisite/gentleman-navy/cases.php
 * 紳藍質感案例頁 — 由 site/cases.php 在 layout_head 後 require
 *
 * 相簿模式（亞雷慣例）：before_image / after_image 指相簿資料夾內代表圖，
 * 點卡片進 /cases/{slug} 由 case_detail.php glob 整本相簿 + lightbox。
 * 分地區：location 含「台中」「彰化」分組；/cases/taichung、/cases/changhua
 * （.htaccess 既有規則 → $_GET['region']）可直達單一地區。
 */

$cases   = $site['cases'] ?? [];
$social  = $site['social'] ?? [];
$phone   = $client['phone'] ?? '';
$telHref = 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
$fbUrl   = $social['fb_page_url'] ?? '';

// ── 地區分組 ─────────────────────────────
$_regionOf = function (string $loc): string {
    if (str_contains($loc, '彰化')) return 'changhua';
    if (str_contains($loc, '台中') || str_contains($loc, '臺中')) return 'taichung';
    return 'other';
};
$_regionLabels = ['taichung' => '台中地區', 'changhua' => '彰化地區', 'other' => '其他地區'];

$_curRegion = preg_replace('/[^a-z]/', '', (string)($_GET['region'] ?? ''));
if (!isset($_regionLabels[$_curRegion])) $_curRegion = '';

$_grouped = [];
foreach ($cases as $c) $_grouped[$_regionOf((string)($c['location'] ?? ''))][] = $c;
// 固定顯示順序：台中 → 彰化 → 其他
$_grouped = array_replace(array_intersect_key(['taichung' => [], 'changhua' => [], 'other' => []], $_grouped), $_grouped);
if ($_curRegion) $_grouped = array_intersect_key($_grouped, [$_curRegion => 1]);

// ── 相簿工具 ─────────────────────────────
$_docroot = dirname(__DIR__, 3);
$_albumCount = function (array $c) use ($_docroot): int {
    $n = 0;
    foreach (['before_image', 'after_image'] as $k) {
        if (empty($c[$k])) continue;
        $dir = $_docroot . '/' . dirname($c[$k]);
        if (is_dir($dir)) $n += count(glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: []);
        else $n += 1;
    }
    return $n;
};
$_caseUrl = fn(array $c) => (IS_LOCAL || IS_STAGING)
    ? BASE_URL . '/site/case_detail.php?sub=' . urlencode($sub) . '&case_slug=' . urlencode((string)$c['slug'])
    : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/cases/' . rawurlencode((string)$c['slug']);
$_regionUrl = fn(string $r) => (IS_LOCAL || IS_STAGING)
    ? BASE_URL . '/site/cases.php?sub=' . urlencode($sub) . ($r ? '&region=' . $r : '')
    : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/cases' . ($r ? '/' . $r : '');
?>
<div class="gn">

<section class="gn-pagehero">
  <div class="gn-wrap">
    <div class="gn-breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a>　／　施工案例<?= $_curRegion ? '　／　' . h($_regionLabels[$_curRegion]) : '' ?></div>
    <div class="gn-eyebrow">Case Studies</div>
    <h2 class="gn-h2"><?= $_curRegion ? h($_regionLabels[$_curRegion]) . '施工案例' : '施工案例' ?></h2>
    <p class="gn-sub">每一個案例都是完整相簿紀錄，點進去看 Before／After</p>
    <!-- 地區切換 pills -->
    <div class="gn-region-pills">
      <a class="<?= $_curRegion === '' ? 'on' : '' ?>" href="<?= $_regionUrl('') ?>">全部</a>
      <a class="<?= $_curRegion === 'taichung' ? 'on' : '' ?>" href="<?= $_regionUrl('taichung') ?>">台中地區</a>
      <a class="<?= $_curRegion === 'changhua' ? 'on' : '' ?>" href="<?= $_regionUrl('changhua') ?>">彰化地區</a>
    </div>
  </div>
</section>

<section class="gn-section" style="padding-top:40px">
  <div class="gn-wrap">
    <?php $_hasAny = false; ?>
    <?php foreach ($_grouped as $_rk => $_rcases): if (!$_rcases) continue; $_hasAny = true; ?>
    <div class="gn-region-block">
      <?php if (!$_curRegion): ?>
      <div class="gn-region-head">
        <h3 class="gn-region-title"><?= h($_regionLabels[$_rk]) ?></h3>
        <span class="gn-region-count"><?= count($_rcases) ?> 個案例</span>
      </div>
      <?php endif; ?>
      <div class="gn-case-grid" style="margin-top:0">
        <?php foreach ($_rcases as $c): ?>
        <?php $b = !empty($c['before_image']) ? BASE_URL . '/' . h($c['before_image']) : '';
              $a = !empty($c['after_image'])  ? BASE_URL . '/' . h($c['after_image'])  : '';
              $_n = $_albumCount($c); ?>
        <a class="gn-case-card" href="<?= $_caseUrl($c) ?>" style="text-decoration:none;color:inherit;position:relative;display:block">
          <?php if ($b && $a): ?>
          <div class="gn-case-imgs">
            <img class="gn-before" src="<?= $b ?>" alt="<?= h($c['title']) ?>・施作前" loading="lazy">
            <img class="gn-after" src="<?= $a ?>" alt="<?= h($c['title']) ?>・施作後" loading="lazy">
            <div class="gn-case-split"></div>
            <span class="gn-case-tag gn-tb">BEFORE</span><span class="gn-case-tag gn-ta">AFTER</span>
          </div>
          <?php elseif ($a ?: $b): ?>
          <img class="gn-case-single" src="<?= $a ?: $b ?>" alt="<?= h($c['title']) ?>" loading="lazy">
          <?php endif; ?>
          <?php if ($_n > 1): ?><span class="gn-album-badge">📷 <?= $_n ?> 張</span><?php endif; ?>
          <div class="gn-case-body">
            <h3 class="gn-case-title"><?= h($c['title']) ?></h3>
            <div class="gn-case-meta"><?= h(trim(($c['svc_name'] ?? '') . (!empty($c['location']) ? '　' . $c['location'] : ''))) ?></div>
            <?php if (!empty($c['description'])): ?>
            <p style="font-size:.86rem;color:var(--gn-muted);line-height:1.9;margin:10px 0 0"><?= h(mb_strimwidth(strip_tags($c['description']), 0, 80, '…')) ?></p>
            <?php endif; ?>
            <span class="gn-svc-more" style="margin-top:14px">看完整相簿 →</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (!$_hasAny): ?>
    <div class="gn-empty" style="margin-top:0">
      <div class="gn-empty-mark">—</div>
      <h3 class="gn-empty-title">案例整理中</h3>
      <p class="gn-empty-desc">實際施工案例正陸續整理上架<?= $fbUrl ? '，最新案例可先到 Facebook 查看' : '' ?>。</p>
      <?php if ($fbUrl): ?><a class="gn-btn gn-btn-ghost" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">前往 Facebook 看案例</a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<style>
.gn-region-pills { display: flex; gap: 10px; margin-top: 26px; flex-wrap: wrap; }
.gn-region-pills a {
  font-family: "Noto Serif TC", serif; font-size: .88rem; letter-spacing: .14em;
  color: rgba(255,255,255,.75); text-decoration: none;
  border: 1px solid rgba(255,255,255,.35); padding: 8px 22px; transition: all .25s;
}
.gn-region-pills a:hover { color: #fff; border-color: #fff; }
.gn-region-pills a.on { background: var(--gn-gold); border-color: var(--gn-gold); color: var(--gn-navy); font-weight: 700; }
.gn-region-block { margin-bottom: 64px; }
.gn-region-block:last-child { margin-bottom: 0; }
.gn-region-head { display: flex; align-items: baseline; gap: 14px; border-bottom: 1px solid var(--gn-line); padding-bottom: 14px; margin-bottom: 30px; }
.gn-region-title { font-family: "Noto Serif TC", serif; font-size: 1.4rem; font-weight: 700; letter-spacing: .14em; color: var(--gn-navy); margin: 0; }
.gn-region-title::before { content: ''; display: inline-block; width: 10px; height: 10px; background: var(--gn-gold); margin-right: 12px; }
.gn-region-count { font-size: .82rem; color: var(--gn-muted); letter-spacing: .1em; }
.gn-album-badge {
  position: absolute; top: 10px; right: 10px; z-index: 4;
  background: rgba(10,33,56,.78); color: #fff;
  font-size: .72rem; letter-spacing: .08em; padding: 4px 10px;
}
.gn-case-single { width: 100%; height: 240px; object-fit: cover; object-position: top; display: block; }
</style>

<section class="gn-cta">
  <div class="gn-wrap">
    <h2 class="gn-cta-title">想讓家裡也煥然一新嗎？</h2>
    <p class="gn-cta-sub"><?= preg_match('/冷氣|空調/u', $client['industry'] ?? '') ? '來電或私訊我們，拍照即可快速估價' : '歡迎來電洽詢，專人為您服務' ?></p>
    <div class="gn-cta-actions">
      <?php if ($phone): ?><a class="gn-btn gn-btn-gold" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即來電</a><?php endif; ?>
      <?php if ($fbUrl): ?><a class="gn-btn gn-btn-light" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">Facebook 私訊</a><?php endif; ?>
    </div>
  </div>
</section>

</div>
