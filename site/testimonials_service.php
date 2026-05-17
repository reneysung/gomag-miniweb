<?php
/**
 * site/testimonials_service.php
 * 服務業客戶好評頁（PrettyClean 風 — 評分 stats + 評論卡片）
 * 由 site/testimonials.php 在 !$isFood 時 require 進來
 */
$photos = servicePhotoSet();
$ts = $site['testimonials'] ?? [];
// 不再 hardcode 假評價 — DB 空 → 顯示「整理中」

// stats
$ratings = array_map(fn($t) => (int)($t['rating'] ?? 5), $ts);
$avgRating = $ratings ? round(array_sum($ratings) / count($ratings), 1) : 4.9;
$totalReviews = count($ts);
$fiveStarPct = $ratings ? round(count(array_filter($ratings, fn($r) => $r >= 5)) / count($ratings) * 100) : 95;

$featured = $ts[0] ?? null;
$rest = array_slice($ts, 1);

require __DIR__ . '/layout_head.php';
?>

<!-- ══ 1. Page Hero ═══════════════════════════════════════════════════ -->
<section class="prosvc-hero">
  <div class="prosvc-hero-bg" style="background-image:url('<?= h($photos['happy']) ?>');"></div>
  <div class="prosvc-hero-overlay"></div>
  <div class="prosvc-hero-content">
    <div class="prosvc-tag"><span class="prosvc-tag-dot"></span>客戶好評</div>
    <h1 class="prosvc-hero-title">真實口碑推薦</h1>
    <p class="prosvc-hero-sub">每一則都是客戶的親身體驗</p>
  </div>
</section>

<!-- ══ 2. 評分統計 stats ═══════════════════════════════════════════ -->
<section class="proRev-stats">
  <div class="proRev-stats-grid">
    <div class="proRev-stat animate-in">
      <div class="proRev-stat-num"><?= $avgRating ?></div>
      <div class="proRev-stat-stars">★★★★★</div>
      <div class="proRev-stat-label">平均評分</div>
    </div>
    <div class="proRev-stat animate-in delay-1">
      <div class="proRev-stat-num"><?= $totalReviews ?>+</div>
      <div class="proRev-stat-label">真實評價</div>
    </div>
    <div class="proRev-stat animate-in delay-2">
      <div class="proRev-stat-num"><?= $fiveStarPct ?>%</div>
      <div class="proRev-stat-label">五星好評</div>
    </div>
    <div class="proRev-stat animate-in delay-3">
      <div class="proRev-stat-num">98%</div>
      <div class="proRev-stat-label">回購推薦率</div>
    </div>
  </div>
</section>

<!-- ══ 3. Featured Review ════════════════════════════════════════════ -->
<?php if (empty($ts)): ?>
<section style="padding:80px 20px">
  <div style="max-width:600px;margin:0 auto;padding:48px 24px;text-align:center;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.06)">
    <div style="font-size:3rem;margin-bottom:14px">⭐</div>
    <h3 style="font-size:1.2rem;font-weight:800;color:var(--g-ink);margin-bottom:8px">客戶評價整理中</h3>
    <p style="color:#888;font-size:.95rem;margin-bottom:24px">最新好評正在收集中，歡迎成為下一個滿意客戶。</p>
    <?php if (!empty($client['phone'])): ?>
      <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="btn btn-primary" style="margin:4px">📞 <?= h($client['phone']) ?></a>
    <?php endif; ?>
    <?php if($lineUrl ?? null): ?>
      <a href="<?= h($lineUrl) ?>" target="_blank" class="btn btn-accent" style="margin:4px">💬 LINE 諮詢</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="proRev-feature">
  <div class="proRev-feature-inner">
    <div class="proRev-feature-quote">"</div>
    <div class="proRev-feature-stars">★★★★★</div>
    <p class="proRev-feature-text"><?= h($featured['content']) ?></p>
    <div class="proRev-feature-author">
      <div class="proRev-feature-avatar"><?= h(mb_substr($featured['reviewer_name'], 0, 1)) ?></div>
      <div>
        <div class="proRev-feature-name">— <?= h($featured['reviewer_name']) ?></div>
        <?php if (!empty($featured['service_name'])): ?>
        <div class="proRev-feature-svc"><?= h($featured['service_name']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 4. Review Grid ═════════════════════════════════════════════════ -->
<?php if (!empty($rest)): ?>
<section class="proRev-grid-section">
  <div class="proRev-grid-head">
    <div class="prosvc-eyebrow">MORE REVIEWS</div>
    <h2 class="prosvc-section-title">更多客戶分享</h2>
  </div>
  <div class="proRev-grid">
    <?php foreach ($rest as $i => $t): ?>
    <article class="proRev-card animate-in delay-<?= min(($i % 4) + 1, 4) ?>">
      <div class="proRev-card-stars">★★★★★</div>
      <p class="proRev-card-text">「<?= h($t['content']) ?>」</p>
      <div class="proRev-card-author">
        <div class="proRev-card-avatar"><?= h(mb_substr($t['reviewer_name'], 0, 1)) ?></div>
        <div>
          <div class="proRev-card-name"><?= h($t['reviewer_name']) ?></div>
          <?php $_svcName = $t['svc_name'] ?? $t['service_name'] ?? ''; ?>
          <?php if ($_svcName): ?>
            <?php if (!empty($t['svc_slug'])): $_svcUrl = siteUrl($sub ?? $slug, 'services') . '/' . rawurlencode($t['svc_slug']); ?>
              <div class="proRev-card-svc"><a href="<?= h($_svcUrl) ?>" style="color:inherit;text-decoration:none;border-bottom:1px dashed currentColor"><?= h($_svcName) ?></a></div>
            <?php else: ?>
              <div class="proRev-card-svc"><?= h($_svcName) ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══ 5. CTA ═════════════════════════════════════════════════════════ -->
<section class="prosvc-cta">
  <div class="prosvc-cta-bg" style="background-image:url('<?= h($photos['team']) ?>');"></div>
  <div class="prosvc-cta-overlay"></div>
  <div class="prosvc-cta-inner">
    <h2 class="prosvc-cta-title">下一個滿意的客戶<br>就是您</h2>
    <p class="prosvc-cta-sub">免費到府估價，專人為您服務</p>
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
/* 共用 prosvc-* 同 services_service.php */
.prosvc-eyebrow { font-size: .8rem; font-weight: 700; letter-spacing: 0.18em; color: var(--g-accent); text-transform: uppercase; margin-bottom: 12px; }
.prosvc-section-title { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 900; letter-spacing: -0.02em; color: var(--g-ink); text-align: center; margin: 0; }
.prosvc-hero { position: relative; min-height: 54vh; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--g-ink); }
.prosvc-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.65; }
.prosvc-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.4), rgba(0,0,0,0.5)); }
.prosvc-hero-content { position: relative; z-index: 2; text-align: center; padding: 100px 20px 80px; max-width: 760px; color: #fff; }
.prosvc-tag { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.95); color: var(--g-ink); padding: 8px 18px; border-radius: 100px; font-size: .82rem; font-weight: 700; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
.prosvc-tag-dot { width: 6px; height: 6px; background: var(--g-accent); border-radius: 50%; }
.prosvc-hero-title { font-size: clamp(2.4rem, 6vw, 4rem); font-weight: 900; letter-spacing: -0.04em; line-height: 1; color: #fff; margin: 0 0 18px; text-shadow: 0 4px 30px rgba(0,0,0,0.4); }
.prosvc-hero-sub { font-size: clamp(1rem, 1.5vw, 1.2rem); color: rgba(255,255,255,0.9); }

/* Stats */
.proRev-stats { background: white; padding: 70px 20px; }
.proRev-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; max-width: 1180px; margin: 0 auto; }
.proRev-stat { text-align: center; padding: 24px 16px; background: var(--g-bg-alt); border-radius: 16px; }
.proRev-stat-num { font-family: var(--g-font-num, 'Manrope', sans-serif); font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 900; color: var(--g-accent); line-height: 1; letter-spacing: -0.03em; }
.proRev-stat-stars { color: #f4b53b; font-size: 1rem; letter-spacing: 2px; margin-top: 6px; }
.proRev-stat-label { font-size: .85rem; font-weight: 600; color: rgba(var(--g-ink-rgb), 0.65); margin-top: 8px; }

/* Featured Review */
.proRev-feature { background: var(--g-bg); padding: 100px 20px; text-align: center; }
.proRev-feature-inner { max-width: 780px; margin: 0 auto; }
.proRev-feature-quote { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 6rem; color: var(--g-accent); opacity: 0.4; line-height: 0.7; margin-bottom: 8px; }
.proRev-feature-stars { color: #f4b53b; font-size: 1.2rem; letter-spacing: 5px; margin-bottom: 24px; text-shadow: 0 0 16px rgba(244,181,59,0.35); }
.proRev-feature-text { font-size: clamp(1.3rem, 2.2vw, 1.7rem); line-height: 1.7; color: var(--g-ink); margin: 0 0 32px; font-weight: 500; }
.proRev-feature-author { display: inline-flex; align-items: center; gap: 14px; }
.proRev-feature-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(var(--g-accent-rgb), 0.15); color: var(--g-accent); display: grid; place-items: center; font-weight: 800; font-size: 1.3rem; }
.proRev-feature-name { font-weight: 800; color: var(--g-ink); font-size: 1.05rem; text-align: left; }
.proRev-feature-svc { font-size: .85rem; color: rgba(var(--g-ink-rgb), 0.55); text-align: left; margin-top: 2px; }

/* Grid */
.proRev-grid-section { background: var(--g-bg-alt); padding: 90px 0; }
.proRev-grid-head { text-align: center; margin-bottom: 50px; padding: 0 20px; }
.proRev-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.proRev-card { background: white; padding: 32px 28px; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); display: flex; flex-direction: column; transition: transform .3s ease, box-shadow .3s ease; }
.proRev-card:hover { transform: translateY(-3px); box-shadow: 0 18px 36px -10px rgba(0,0,0,0.16); }
.proRev-card-stars { color: #f4b53b; font-size: .95rem; letter-spacing: 3px; margin-bottom: 14px; }
.proRev-card-text { font-size: 1rem; line-height: 1.7; color: rgba(var(--g-ink-rgb), 0.85); margin: 0 0 22px; flex: 1; font-weight: 500; }
.proRev-card-author { display: flex; align-items: center; gap: 12px; padding-top: 18px; border-top: 1px solid rgba(var(--g-ink-rgb), 0.08); }
.proRev-card-avatar { width: 42px; height: 42px; border-radius: 50%; background: rgba(var(--g-accent-rgb), 0.12); color: var(--g-accent); display: grid; place-items: center; font-weight: 800; font-size: 1.05rem; flex-shrink: 0; }
.proRev-card-name { font-weight: 800; color: var(--g-ink); font-size: .95rem; }
.proRev-card-svc { font-size: .78rem; color: rgba(var(--g-ink-rgb), 0.55); margin-top: 2px; }

/* CTA — same as services_service */
.prosvc-cta { position: relative; min-height: 380px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.prosvc-cta-bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
.prosvc-cta-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(var(--g-ink-rgb), 0.88), rgba(var(--g-ink-rgb), 0.7)); }
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
  .proRev-stats-grid { grid-template-columns: repeat(2, 1fr); }
  .proRev-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
  .prosvc-hero { min-height: 44vh; }
  .prosvc-hero-content { padding: 80px 16px 60px; }
  .proRev-feature { padding: 70px 20px; }
  .proRev-grid { grid-template-columns: 1fr; }
  .proRev-grid-section { padding: 60px 0; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
