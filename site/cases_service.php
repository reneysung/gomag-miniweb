<?php
/**
 * site/cases_service.php
 * 服務業案例頁（PrettyClean 風 — Before/After 對比 + 卡片網格）
 * 由 site/cases.php 在 !$isFood 時 require 進來
 */
$photos = servicePhotoSet();
$cases = $site['cases'] ?? [];

// DB 有 cases 才 render — 不再 hardcode 假案例（避免 admin 找不到資料源）
$works = [];
if (!empty($cases)) {
    $works = array_map(function($c) use ($photos) {
        $before = !empty($c['before_image']) ? BASE_URL . '/' . $c['before_image'] : $photos['before'];
        $after  = !empty($c['after_image'])  ? BASE_URL . '/' . $c['after_image']  : $photos['after'];
        return [
            'title' => $c['title'] ?? '施工案例',
            'desc' => $c['description'] ?? '',
            'location' => $c['location'] ?? '',
            'area' => $c['area_sqm'] ?? '',
            'before' => $before,
            'after' => $after,
            'slug' => $c['slug'] ?? '',
        ];
    }, array_slice($cases, 0, 9));
}

$featured = $works[0] ?? null;
$rest = array_slice($works, 1);

require __DIR__ . '/layout_head.php';
?>

<!-- ══ 1. Page Hero ═══════════════════════════════════════════════════ -->
<section class="prosvc-hero">
  <div class="prosvc-hero-bg" style="background-image:url('<?= h($photos['after']) ?>');"></div>
  <div class="prosvc-hero-overlay"></div>
  <div class="prosvc-hero-content">
    <div class="prosvc-tag">
      <span class="prosvc-tag-dot"></span>
      <?= !empty($caseRegionLabel) ? h($caseRegionLabel) . '施工案例' : '施工案例' ?>
    </div>
    <h1 class="prosvc-hero-title"><?= !empty($caseHeroTitle) ? h($caseHeroTitle) : '真實 Before / After' ?></h1>
    <p class="prosvc-hero-sub"><?= !empty($caseHeroSub) ? h($caseHeroSub) : '每個案例都是我們的驕傲，讓成果說話' ?></p>
  </div>
</section>

<!-- ══ 地區切換列（台中 / 彰化）— 內部連結 + UX ════════════════════════ -->
<?php if (!empty($caseAllRegions) && count($caseAllRegions) > 1):
  $regionMap = caseRegionMap();
  $allCasesUrl = siteUrl($sub, 'cases');
?>
<nav class="proCase-region-nav" aria-label="依地區瀏覽案例">
  <a href="<?= h($allCasesUrl) ?>" class="proCase-region-link <?= empty($caseRegionLabel) ? 'is-active' : '' ?>">全部</a>
  <?php foreach ($caseAllRegions as $rSlug): ?>
    <a href="<?= h($allCasesUrl . '/' . $rSlug) ?>"
       class="proCase-region-link <?= ($caseRegion ?? '') === $rSlug ? 'is-active' : '' ?>">
      <?= h($regionMap[$rSlug]['label']) ?>
    </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<!-- 空狀態：DB 沒 cases 顯示「整理中」訊息（避免假資料） -->
<?php if (empty($works)): ?>
<section style="padding:80px 20px">
  <div style="max-width:600px;margin:0 auto;padding:48px 24px;text-align:center;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.06)">
    <div style="font-size:3rem;margin-bottom:14px">📸</div>
    <h3 style="font-size:1.2rem;font-weight:800;color:var(--g-ink);margin-bottom:8px">施工案例整理中</h3>
    <p style="color:#888;font-size:.95rem;margin-bottom:24px">歡迎來電或 LINE 諮詢，我們將提供合適的服務說明與案例參考。</p>
    <?php if (!empty($client['phone'])): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="btn btn-primary" style="margin:4px">📞 <?= h($client['phone']) ?></a>
    <?php endif; ?>
    <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" target="_blank" class="btn btn-accent" style="margin:4px">💬 LINE 諮詢</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 2. Featured Case — 大 Before/After 對比 ═══════════════════════ -->
<?php if ($featured): ?>
<section class="proCase-feature">
  <div class="proCase-feature-head">
    <div class="prosvc-eyebrow">FEATURED CASE</div>
    <h2 class="prosvc-section-title"><?= h($featured['title']) ?></h2>
    <?php if ($featured['desc']): ?>
    <p class="proCase-feature-desc"><?= h($featured['desc']) ?></p>
    <?php endif; ?>
    <?php if ($featured['location'] || $featured['area']): ?>
    <div class="proCase-feature-meta">
      <?php if ($featured['location']): ?><span>📍 <?= h($featured['location']) ?></span><?php endif; ?>
      <?php if ($featured['area']): ?><span>📐 <?= h($featured['area']) ?> 坪</span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="proCase-ba" id="proCaseFeaturedBa">
    <div class="proCase-ba-after"  style="background-image:url('<?= h($featured['after']) ?>');"></div>
    <div class="proCase-ba-before" style="background-image:url('<?= h($featured['before']) ?>');" id="proCaseFeaturedBefore"></div>
    <div class="proCase-ba-handle" id="proCaseFeaturedHandle">
      <span class="proCase-ba-arrows">⇄</span>
    </div>
    <div class="proCase-ba-label proCase-ba-label-before">BEFORE</div>
    <div class="proCase-ba-label proCase-ba-label-after">AFTER</div>
  </div>
</section>

<script>
(function() {
  const ba = document.getElementById('proCaseFeaturedBa');
  const before = document.getElementById('proCaseFeaturedBefore');
  const handle = document.getElementById('proCaseFeaturedHandle');
  if (!ba || !before || !handle) return;
  let dragging = false;
  function move(e) {
    if (!dragging) return;
    const rect = ba.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const pct = Math.max(0, Math.min(100, (x / rect.width) * 100));
    before.style.clipPath = `inset(0 ${100 - pct}% 0 0)`;
    handle.style.left = pct + '%';
  }
  handle.addEventListener('mousedown', () => dragging = true);
  handle.addEventListener('touchstart', () => dragging = true);
  window.addEventListener('mouseup', () => dragging = false);
  window.addEventListener('touchend', () => dragging = false);
  window.addEventListener('mousemove', move);
  window.addEventListener('touchmove', move);
})();
</script>
<?php endif; ?>

<!-- ══ 3. Case Grid ═══════════════════════════════════════════════════ -->
<?php if (!empty($rest)): ?>
<section class="proCase-grid-section">
  <div class="proCase-grid-head">
    <div class="prosvc-eyebrow">MORE CASES</div>
    <h2 class="prosvc-section-title">更多案例</h2>
  </div>
  <div class="proCase-grid">
    <?php foreach ($rest as $i => $c): ?>
    <article class="proCase-card animate-in delay-<?= min(($i % 4) + 1, 4) ?>">
      <div class="proCase-card-imgs">
        <div class="proCase-card-before" style="background-image:url('<?= h($c['before']) ?>');">
          <span class="proCase-mini-label">BEFORE</span>
        </div>
        <div class="proCase-card-after" style="background-image:url('<?= h($c['after']) ?>');">
          <span class="proCase-mini-label proCase-mini-label-after">AFTER</span>
        </div>
      </div>
      <div class="proCase-card-meta">
        <?php $caseDetailUrl = !empty($c['slug']) ? (siteUrl($sub, 'cases') . '/' . rawurlencode($c['slug'])) : null; ?>
        <h3 class="proCase-card-title">
          <?php if ($caseDetailUrl): ?><a href="<?= h($caseDetailUrl) ?>" style="color:inherit;text-decoration:none"><?= h($c['title']) ?></a>
          <?php else: ?><?= h($c['title']) ?><?php endif; ?>
        </h3>
        <?php if ($c['desc']): ?>
        <p class="proCase-card-desc"><?= h($c['desc']) ?></p>
        <?php endif; ?>
        <?php if ($c['location'] || $c['area']): ?>
        <div class="proCase-card-meta-line">
          <?php if ($c['location']): ?><span>📍 <?= h($c['location']) ?></span><?php endif; ?>
          <?php if ($c['area']): ?><span>📐 <?= h($c['area']) ?> 坪</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($caseDetailUrl): ?>
          <div style="margin-top:10px"><a href="<?= h($caseDetailUrl) ?>" style="font-size:.85rem;color:var(--g-accent);text-decoration:none;font-weight:700">查看案例 →</a></div>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 4. CTA ═════════════════════════════════════════════════════════ -->
<section class="prosvc-cta">
  <div class="prosvc-cta-bg" style="background-image:url('<?= h($photos['team']) ?>');"></div>
  <div class="prosvc-cta-overlay"></div>
  <div class="prosvc-cta-inner">
    <h2 class="prosvc-cta-title">想讓您的空間也煥然一新嗎？</h2>
    <p class="prosvc-cta-sub"><?= preg_match('/冷氣|空調/u', $client['industry'] ?? '') ? '來電或私訊我們，拍照即可快速估價' : '免費到府估價，專人為您說明' ?></p>
    <div class="prosvc-cta-actions">
      <?php if($lineUrl): ?>
      <a href="<?= h($lineUrl) ?>" class="prosvc-btn prosvc-btn-primary" target="_blank">免費估價諮詢 →</a>
      <?php endif; ?>
      <?php if($phone): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="prosvc-btn prosvc-btn-secondary">📞 <?= h($phone) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
/* 共用樣式重用 services_service.php 的 prosvc-* */
.prosvc-eyebrow { font-size: .8rem; font-weight: 700; letter-spacing: 0.18em; color: var(--g-accent); text-transform: uppercase; margin-bottom: 12px; }
.prosvc-section-title { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 900; letter-spacing: -0.02em; color: var(--g-ink); text-align: center; margin: 0; }
.prosvc-hero { position: relative; min-height: 56vh; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--g-ink); }
.prosvc-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.65; }
.prosvc-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.4), rgba(0,0,0,0.5)); }
.prosvc-hero-content { position: relative; z-index: 2; text-align: center; padding: 100px 20px 80px; max-width: 760px; color: #fff; }
.prosvc-tag { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.95); color: var(--g-ink); padding: 8px 18px; border-radius: 100px; font-size: .82rem; font-weight: 700; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
.prosvc-tag-dot { width: 6px; height: 6px; background: var(--g-accent); border-radius: 50%; }
.prosvc-hero-title { font-size: clamp(2.4rem, 6vw, 4rem); font-weight: 900; letter-spacing: -0.04em; line-height: 1; color: #fff; margin: 0 0 18px; text-shadow: 0 4px 30px rgba(0,0,0,0.4); }
.prosvc-hero-sub { font-size: clamp(1rem, 1.5vw, 1.2rem); color: rgba(255,255,255,0.9); }

/* 地區切換列 */
.proCase-region-nav { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; padding: 28px 20px 4px; max-width: 1180px; margin: 0 auto; }
.proCase-region-link { display: inline-flex; align-items: center; padding: 9px 22px; border-radius: 100px; font-size: .92rem; font-weight: 700; text-decoration: none; color: var(--g-ink); background: var(--g-bg-alt); border: 1.5px solid rgba(var(--g-ink-rgb), 0.1); transition: all .2s ease; }
.proCase-region-link:hover { border-color: var(--g-accent); color: var(--g-accent); }
.proCase-region-link.is-active { background: var(--g-ink); color: #fff; border-color: var(--g-ink); }

/* Featured Before/After */
.proCase-feature { padding: 90px 0 80px; max-width: 1180px; margin: 0 auto; }
.proCase-feature-head { text-align: center; padding: 0 20px; margin-bottom: 40px; }
.proCase-feature-desc { font-size: 1.05rem; color: rgba(var(--g-ink-rgb), 0.7); max-width: 720px; margin: 16px auto 14px; line-height: 1.7; }
.proCase-feature-meta { display: inline-flex; gap: 18px; font-size: .92rem; color: rgba(var(--g-ink-rgb), 0.6); flex-wrap: wrap; justify-content: center; }
.proCase-ba {
  position: relative;
  aspect-ratio: 16/9;
  margin: 0 20px;
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 30px 60px -20px rgba(0,0,0,0.25);
  cursor: ew-resize;
  user-select: none;
  background: var(--g-bg-alt);
}
.proCase-ba-after, .proCase-ba-before { position: absolute; inset: 0; background-size: cover; background-position: center; }
.proCase-ba-before { clip-path: inset(0 50% 0 0); transition: clip-path .05s linear; }
.proCase-ba-handle {
  position: absolute;
  top: 0; bottom: 0; left: 50%;
  width: 4px;
  background: white;
  box-shadow: 0 0 20px rgba(0,0,0,0.4);
  transform: translateX(-50%);
  z-index: 3;
}
.proCase-ba-handle::before, .proCase-ba-handle::after {
  content: '';
  position: absolute;
  left: 50%; top: 50%;
  transform: translate(-50%, -50%);
  width: 56px; height: 56px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.proCase-ba-handle::after { width: 0; height: 0; box-shadow: none; }
.proCase-ba-arrows {
  position: absolute;
  left: 50%; top: 50%;
  transform: translate(-50%, -50%);
  z-index: 2;
  color: var(--g-ink);
  font-size: 1.4rem;
  font-weight: 800;
}
.proCase-ba-label {
  position: absolute;
  top: 20px;
  z-index: 2;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 6px 14px;
  border-radius: 100px;
  font-size: .72rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  backdrop-filter: blur(6px);
}
.proCase-ba-label-before { left: 20px; }
.proCase-ba-label-after { right: 20px; background: var(--g-accent); }

/* Case Grid */
.proCase-grid-section { background: var(--g-bg-alt); padding: 90px 0; }
.proCase-grid-head { text-align: center; margin-bottom: 50px; padding: 0 20px; }
.proCase-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 20px;
}
.proCase-card {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  transition: transform .3s ease, box-shadow .3s ease;
}
.proCase-card:hover { transform: translateY(-3px); box-shadow: 0 18px 36px -10px rgba(0,0,0,0.18); }
.proCase-card-imgs { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; aspect-ratio: 16/9; }
.proCase-card-before, .proCase-card-after { position: relative; background-size: cover; background-position: center; }
.proCase-mini-label {
  position: absolute;
  top: 12px; left: 12px;
  background: rgba(0,0,0,0.6);
  color: white;
  padding: 4px 10px;
  border-radius: 100px;
  font-size: .65rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  backdrop-filter: blur(6px);
}
.proCase-mini-label-after { background: var(--g-accent); }
.proCase-card-meta { padding: 22px 24px 24px; }
.proCase-card-title { font-size: 1.15rem; font-weight: 800; color: var(--g-ink); margin: 0 0 8px; }
.proCase-card-desc { font-size: .9rem; line-height: 1.65; color: rgba(var(--g-ink-rgb), 0.65); margin: 0 0 12px; }
.proCase-card-meta-line { display: flex; gap: 12px; font-size: .82rem; color: rgba(var(--g-ink-rgb), 0.55); flex-wrap: wrap; }

/* CTA — 同 services_service */
.prosvc-cta { position: relative; min-height: 380px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.prosvc-cta-bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
.prosvc-cta-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(var(--g-ink-rgb), 0.88) 0%, rgba(var(--g-ink-rgb), 0.7) 100%); }
.prosvc-cta-inner { position: relative; z-index: 2; text-align: center; padding: 80px 20px; max-width: 720px; color: #fff; }
.prosvc-cta-title { font-size: clamp(2rem, 4.5vw, 2.8rem); font-weight: 900; letter-spacing: -0.02em; line-height: 1.15; color: #fff; margin: 0 0 16px; }
.prosvc-cta-sub { font-size: 1.1rem; color: rgba(255,255,255,0.88); margin: 0 0 32px; }
.prosvc-cta-actions { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
.prosvc-btn { display: inline-flex; align-items: center; gap: 8px; padding: 16px 32px; border-radius: 100px; font-size: 1rem; font-weight: 700; text-decoration: none; transition: all .25s ease; white-space: nowrap; }
.prosvc-btn-primary { background: var(--g-accent); color: #fff; box-shadow: 0 12px 30px -8px rgba(var(--g-accent-rgb), 0.65); }
.prosvc-btn-primary:hover { transform: translateY(-2px); filter: brightness(1.05); }
.prosvc-btn-secondary { background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); color: #fff; border: 1.5px solid rgba(255,255,255,0.4); }

/* RWD */
@media (max-width: 900px) {
  .proCase-grid { grid-template-columns: repeat(2, 1fr); }
  .proCase-ba { aspect-ratio: 4/3; }
}
@media (max-width: 540px) {
  .prosvc-hero { min-height: 46vh; }
  .prosvc-hero-content { padding: 80px 16px 60px; }
  .proCase-grid { grid-template-columns: 1fr; }
  .proCase-feature { padding: 60px 0; }
  .proCase-grid-section { padding: 60px 0; }
  .proCase-ba-handle::before { width: 44px; height: 44px; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
