<?php
/**
 * site/services_service.php
 * 服務業項目頁（PrettyClean 風 — 亮色照片 + 卡片網格 + 信任 pillars）
 * 由 site/services.php 在 !$isFood 時 require 進來
 *
 * 上文已備好：$site, $sub, $client, $services, $lineUrl, $phone
 */
$photos = servicePhotoSet();
$svcList = $services ?? [];  // 不再 hardcode 假資料 — DB 空 → 顯示「整理中」

$svcPhotoMap = [
    '裝潢' => $photos['living'], '細清' => $photos['living'],
    '居家' => $photos['kitchen'], '深度' => $photos['kitchen'],
    '搬遷' => $photos['bathroom'], '搬家' => $photos['bathroom'],
    '玻璃' => $photos['window'], '窗' => $photos['window'],
    '冷氣' => $photos['detail'], '空調' => $photos['detail'],
];

require __DIR__ . '/layout_head.php';
?>

<!-- ══ 1. Page Hero ═══════════════════════════════════════════════════ -->
<section class="prosvc-hero">
  <div class="prosvc-hero-bg" style="background-image:url('<?= h($photos['hero']) ?>');"></div>
  <div class="prosvc-hero-overlay"></div>
  <div class="prosvc-hero-content">
    <div class="prosvc-tag">
      <span class="prosvc-tag-dot"></span>
      <?= h($client['industry'] ?? '專業服務') ?>
    </div>
    <h1 class="prosvc-hero-title">服務項目</h1>
    <p class="prosvc-hero-sub">每項服務由專業技師執行，使用環保清潔劑，安全無毒</p>
  </div>
</section>

<!-- ══ 2. 服務項目 Grid ═════════════════════════════════════════════ -->
<section class="prosvc-grid-section">
  <?php if (empty($svcList)): ?>
    <div style="max-width:600px;margin:60px auto;padding:48px 24px;text-align:center;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.06)">
      <div style="font-size:3rem;margin-bottom:14px">🛠️</div>
      <h3 style="font-size:1.2rem;font-weight:800;color:var(--g-ink);margin-bottom:8px">服務項目整理中</h3>
      <p style="color:#888;font-size:.95rem;margin-bottom:24px">歡迎來電或 LINE 諮詢，我們將為您詳細說明服務內容。</p>
      <?php if($phone): ?>
        <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn btn-primary" style="margin:4px">📞 <?= h($phone) ?></a>
      <?php endif; ?>
      <?php if($lineUrl): ?>
        <a href="<?= h($lineUrl) ?>" target="_blank" class="btn btn-accent" style="margin:4px">💬 LINE 諮詢</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="prosvc-grid">
    <?php foreach ($svcList as $i => $svc):
      // 圖：DB image_path > 名字關鍵字 match > default photo
      $img = !empty($svc['image_path']) ? BASE_URL . '/' . $svc['image_path'] : null;
      if (!$img) {
        foreach ($svcPhotoMap as $kw => $url) {
          if (str_contains($svc['name'], $kw)) { $img = $url; break; }
        }
        $img = $img ?: ($svc['__photo'] ?? $photos['hero']);
      }
    ?>
    <article class="prosvc-card animate-in delay-<?= min(($i % 4) + 1, 4) ?>">
      <div class="prosvc-card-img" style="background-image:url('<?= h($img) ?>');">
        <span class="prosvc-card-num"><?= str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) ?></span>
      </div>
      <div class="prosvc-card-meta">
        <?php $svcDetailUrl = !empty($svc['slug']) ? (siteUrl($sub, 'services') . '/' . rawurlencode($svc['slug'])) : null; ?>
        <h3 class="prosvc-card-name">
          <?php if ($svcDetailUrl): ?>
            <a href="<?= h($svcDetailUrl) ?>" style="color:inherit;text-decoration:none"><?= h($svc['name']) ?></a>
          <?php else: ?>
            <?= h($svc['name']) ?>
          <?php endif; ?>
        </h3>
        <p class="prosvc-card-desc"><?= h($svc['short_desc'] ?? '') ?></p>
        <?php if ($svcDetailUrl): ?>
        <a href="<?= h($svcDetailUrl) ?>" class="prosvc-card-link" style="margin-bottom:8px">
          了解更多 <span>→</span>
        </a>
        <?php endif; ?>
        <?php if($lineUrl): ?>
        <a href="<?= h($lineUrl) ?>" class="prosvc-card-link" target="_blank">
          詢問報價 <span>→</span>
        </a>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; /* svcList empty */ ?>
</section>

<!-- ══ 3. 為什麼選我們 — 4 信任 pillars ═══════════════════════════════ -->
<section class="prosvc-pillars">
  <div class="prosvc-pillars-head">
    <div class="prosvc-eyebrow">WHY CHOOSE US</div>
    <h2 class="prosvc-section-title">為什麼選擇我們</h2>
  </div>
  <div class="prosvc-pillars-grid">
    <div class="prosvc-pillar animate-in delay-1">
      <div class="prosvc-pillar-icon">🏆</div>
      <h3 class="prosvc-pillar-title">專業認證技師</h3>
      <p class="prosvc-pillar-desc">每位技師都通過專業培訓與背景檢核，確保服務品質一致</p>
    </div>
    <div class="prosvc-pillar animate-in delay-2">
      <div class="prosvc-pillar-icon">🌱</div>
      <h3 class="prosvc-pillar-title">環保清潔用品</h3>
      <p class="prosvc-pillar-desc">使用通過認證的環保清潔劑，對家人、寵物與環境都安心</p>
    </div>
    <div class="prosvc-pillar animate-in delay-3">
      <div class="prosvc-pillar-icon">📋</div>
      <h3 class="prosvc-pillar-title">免費到府估價</h3>
      <p class="prosvc-pillar-desc">先看現場、再報價，依實際狀況提供最合適的服務方案</p>
    </div>
    <div class="prosvc-pillar animate-in delay-4">
      <div class="prosvc-pillar-icon">⏱️</div>
      <h3 class="prosvc-pillar-title">準時守信到場</h3>
      <p class="prosvc-pillar-desc">約定時間絕不遲到，服務完成後再次確認，讓您安心交付</p>
    </div>
  </div>
</section>

<!-- ══ 4. CTA ═════════════════════════════════════════════════════════ -->
<section class="prosvc-cta">
  <div class="prosvc-cta-bg" style="background-image:url('<?= h($photos['team']) ?>');"></div>
  <div class="prosvc-cta-overlay"></div>
  <div class="prosvc-cta-inner">
    <h2 class="prosvc-cta-title">準備好讓家煥然一新了嗎？</h2>
    <p class="prosvc-cta-sub">免費到府估價，專人為您說明服務內容</p>
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
/* ════════════════════════════════════════════════════════════════
 * 服務業內頁專屬樣式（PrettyClean 風）
 * ════════════════════════════════════════════════════════════════ */
.prosvc-eyebrow {
  font-size: .8rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  color: var(--g-accent);
  text-transform: uppercase;
  margin-bottom: 12px;
}
.prosvc-section-title {
  font-family: 'Noto Sans TC', sans-serif;
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--g-ink);
  text-align: center;
  margin: 0;
}

/* 1. Hero */
.prosvc-hero { position: relative; min-height: 56vh; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--g-ink); }
.prosvc-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.65; }
.prosvc-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.5) 100%); }
.prosvc-hero-content { position: relative; z-index: 2; text-align: center; padding: 100px 20px 80px; max-width: 760px; }
.prosvc-tag {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,0.95);
  color: var(--g-ink);
  padding: 8px 18px;
  border-radius: 100px;
  font-size: .82rem; font-weight: 700;
  letter-spacing: 0.04em;
  margin-bottom: 24px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.prosvc-tag-dot {
  width: 6px; height: 6px;
  background: var(--g-accent); border-radius: 50%;
  animation: prosvc-pulse 2s infinite;
}
@keyframes prosvc-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(var(--g-accent-rgb), 0.6); } 50% { box-shadow: 0 0 0 6px rgba(var(--g-accent-rgb), 0); } }
.prosvc-hero-title {
  font-size: clamp(2.6rem, 6.5vw, 4.4rem);
  font-weight: 900;
  letter-spacing: -0.04em;
  line-height: 1;
  color: #fff;
  margin: 0 0 18px;
  text-shadow: 0 4px 30px rgba(0,0,0,0.4);
}
.prosvc-hero-sub {
  font-size: clamp(1rem, 1.5vw, 1.2rem);
  color: rgba(255,255,255,0.9);
  margin: 0;
}

/* 2. Services Grid */
.prosvc-grid-section { background: var(--g-bg); padding: 90px 0 80px; }
.prosvc-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 32px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 20px;
}
.prosvc-card {
  background: white;
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.06);
  transition: transform .35s cubic-bezier(.22,.61,.36,1), box-shadow .35s ease;
  display: flex; flex-direction: column;
}
.prosvc-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -12px rgba(0,0,0,0.18); }
.prosvc-card-img {
  position: relative;
  aspect-ratio: 16/10;
  background-size: cover; background-position: center;
}
.prosvc-card-num {
  position: absolute;
  top: 18px; left: 20px;
  background: rgba(255,255,255,0.95);
  color: var(--g-ink);
  width: 40px; height: 40px;
  border-radius: 50%;
  display: grid; place-items: center;
  font-family: var(--g-font-num, 'Manrope', sans-serif);
  font-weight: 800;
  font-size: .9rem;
}
.prosvc-card-meta { padding: 28px 30px 30px; flex: 1; display: flex; flex-direction: column; }
.prosvc-card-name {
  font-size: 1.45rem;
  font-weight: 800;
  color: var(--g-ink);
  margin: 0 0 12px;
  letter-spacing: -0.01em;
}
.prosvc-card-desc {
  font-size: .95rem;
  line-height: 1.75;
  color: rgba(var(--g-ink-rgb), 0.65);
  margin: 0 0 20px;
  flex: 1;
}
.prosvc-card-link {
  display: inline-flex; align-items: center; gap: 6px;
  color: var(--g-accent);
  font-weight: 700;
  font-size: .92rem;
  text-decoration: none;
  align-self: flex-start;
  letter-spacing: 0.02em;
}
.prosvc-card-link span { transition: transform .25s ease; }
.prosvc-card-link:hover span { transform: translateX(4px); }

/* 3. Pillars */
.prosvc-pillars { background: white; padding: 100px 0; }
.prosvc-pillars-head { text-align: center; margin-bottom: 60px; padding: 0 20px; }
.prosvc-pillars-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 28px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 20px;
}
.prosvc-pillar {
  text-align: center;
  padding: 36px 24px;
  background: var(--g-bg-alt);
  border-radius: 18px;
  transition: transform .3s ease;
}
.prosvc-pillar:hover { transform: translateY(-3px); }
.prosvc-pillar-icon {
  width: 64px; height: 64px;
  margin: 0 auto 16px;
  background: white;
  border-radius: 50%;
  display: grid; place-items: center;
  font-size: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.prosvc-pillar-title { font-size: 1.1rem; font-weight: 800; color: var(--g-ink); margin: 0 0 10px; }
.prosvc-pillar-desc { font-size: .88rem; line-height: 1.65; color: rgba(var(--g-ink-rgb), 0.62); margin: 0; }

/* 4. CTA */
.prosvc-cta { position: relative; min-height: 420px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.prosvc-cta-bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
.prosvc-cta-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(var(--g-ink-rgb), 0.88) 0%, rgba(var(--g-ink-rgb), 0.7) 100%); }
.prosvc-cta-inner { position: relative; z-index: 2; text-align: center; padding: 80px 20px; max-width: 720px; color: #fff; }
.prosvc-cta-title { font-size: clamp(2rem, 4.5vw, 3rem); font-weight: 900; letter-spacing: -0.02em; line-height: 1.15; color: #fff; margin: 0 0 16px; }
.prosvc-cta-sub { font-size: 1.1rem; color: rgba(255,255,255,0.88); margin: 0 0 32px; }
.prosvc-cta-actions { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
.prosvc-btn { display: inline-flex; align-items: center; gap: 8px; padding: 16px 32px; border-radius: 100px; font-size: 1rem; font-weight: 700; text-decoration: none; transition: all .25s ease; white-space: nowrap; }
.prosvc-btn-primary { background: var(--g-accent); color: #fff; box-shadow: 0 12px 30px -8px rgba(var(--g-accent-rgb), 0.65); }
.prosvc-btn-primary:hover { transform: translateY(-2px); filter: brightness(1.05); box-shadow: 0 18px 40px -10px rgba(var(--g-accent-rgb), 0.8); }
.prosvc-btn-secondary { background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); color: #fff; border: 1.5px solid rgba(255,255,255,0.4); }
.prosvc-btn-secondary:hover { background: rgba(255,255,255,0.25); }

/* RWD */
@media (max-width: 900px) {
  .prosvc-grid { grid-template-columns: 1fr; }
  .prosvc-pillars-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
  .prosvc-hero { min-height: 46vh; }
  .prosvc-hero-content { padding: 80px 16px 60px; }
  .prosvc-grid-section, .prosvc-pillars { padding: 60px 0; }
  .prosvc-pillars-grid { grid-template-columns: 1fr; gap: 18px; }
  .prosvc-card-meta { padding: 22px 22px 24px; }
}
</style>

<?php require __DIR__ . '/layout_foot.php'; ?>
