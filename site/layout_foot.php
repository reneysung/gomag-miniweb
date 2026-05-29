<?php
// site/layout_foot.php
$phone   = $site['client']['phone']       ?? '';
$social  = $site['social'] ?? [];
// LINE URL 正規化
$lineUrl = '';
if (!empty($social['line_url']) && filter_var($social['line_url'], FILTER_VALIDATE_URL)) {
    $lineUrl = $social['line_url'];
} elseif (!empty($social['line_id'])) {
    $rawId = ltrim(trim($social['line_id']), '@');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $rawId)) $lineUrl = 'https://line.me/R/ti/p/@' . $rawId;
}
$fbUrl   = $social['fb_page_url'] ?? '#';
$client  = $site['client'];
?>

<!-- ── Footer ─────────────────────────────────── -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand"><?= $_logoIcon ?? '🌊' ?> <?= h($client['brand_name']) ?></div>
        <div class="footer-tagline"><?= h($client['tagline'] ?? '') ?></div>
        <?php if ($phone): ?>
          <div class="footer-contact-item">📞 <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>"><?= h($phone) ?></a></div>
        <?php endif; ?>
        <?php if ($client['email']): ?>
          <div class="footer-contact-item">📧 <a href="mailto:<?= h($client['email']) ?>"><?= h($client['email']) ?></a></div>
        <?php endif; ?>
        <?php if ($client['address']): ?>
          <div class="footer-contact-item">📍 <?= h($client['address']) ?></div>
        <?php endif; ?>
        <div class="footer-social">
          <?php if ($fbUrl !== '#'): ?><a href="<?= h($fbUrl) ?>" target="_blank">📘</a><?php endif; ?>
          <?php if ($lineUrl): ?><a href="<?= h($lineUrl) ?>" target="_blank">💬</a><?php endif; ?>
        </div>
      </div>
      <div>
        <div class="footer-h">快速連結</div>
        <a class="footer-link" href="<?= siteUrl($slug) ?>">🏠 首頁</a>
        <?php if ($_showServices ?? true): ?>
        <a class="footer-link" href="<?= siteUrl($slug,'services') ?>">🛠️ <?= h($_servicesLabel ?? '服務項目') ?></a>
        <?php endif; ?>
        <?php if ($_showCases ?? true): ?>
        <a class="footer-link" href="<?= siteUrl($slug,'cases') ?>"><?= $_casesIcon ?? '📸' ?> <?= h($_casesLabelOverride ?? $_casesLabel ?? '施工案例') ?></a>
        <?php endif; ?>
        <?php if ($_showTestimonials ?? true): ?>
        <a class="footer-link" href="<?= siteUrl($slug,'testimonials') ?>">⭐ <?= h($_testimonialsLabel ?? '客戶好評') ?></a>
        <?php endif; ?>
        <a class="footer-link" href="<?= siteUrl($slug) ?>#contact">📍 聯絡我們</a>
      </div>
      <?php if (($_showServices ?? true) && !empty($site['services'])): ?>
      <div>
        <div class="footer-h"><?= h($_servicesLabel ?? '服務項目') ?></div>
        <?php foreach (array_slice($site['services'], 0, 5) as $svc): ?>
          <a class="footer-link" href="<?= siteUrl($slug,'services') ?>#svc-<?= $svc['id'] ?>"><?= h($svc['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-bottom">
      © <?= date('Y') ?> <?= h($client['brand_name']) ?> · 版權所有
      &nbsp;|&nbsp; 網站由 <a href="https://wmf.com.tw" target="_blank" rel="noopener" style="color:rgba(255,255,255,.85);text-decoration:underline;text-underline-offset:3px;">口碑製造所</a> 製作
    </div>
  </div>
</footer>

<!-- ── 底部 Tab Bar ─────────────────────────────── -->
<nav class="bottom-tabbar">
  <a href="<?= siteUrl($slug) ?>"            class="tab-item <?= $pageKey==='home'?'active':'' ?>">
    <span class="icon">🏠</span>首頁</a>
  <?php if ($_showServices ?? true): ?>
  <a href="<?= siteUrl($slug,'services') ?>" class="tab-item <?= $pageKey==='services'?'active':'' ?>">
    <span class="icon">🛠️</span><?= h(mb_substr($_servicesLabel ?? '服務', 0, 2)) ?></a>
  <?php endif; ?>
  <?php if ($_showCases ?? true): ?>
  <a href="<?= siteUrl($slug,'cases') ?>"    class="tab-item <?= $pageKey==='cases'?'active':'' ?>">
    <span class="icon"><?= $_casesIcon ?? '📸' ?></span><?= h(mb_substr($_casesLabelOverride ?? (($_isFood ?? false) ? '作品' : '案例'), 0, 2)) ?></a>
  <?php endif; ?>
  <?php if ($phone): ?>
  <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="tab-item accent">
    <span class="icon">📞</span>電話</a>
  <?php endif; ?>
  <?php if ($lineUrl): ?>
  <a href="<?= h($lineUrl) ?>" class="tab-item" target="_blank" style="color:#06c755">
    <span class="icon">💬</span>LINE</a>
  <?php endif; ?>
</nav>

<script>
function toggleMobileMenu(){ document.getElementById('mobileMenu').classList.toggle('open'); }
function closeMobileMenu(){ document.getElementById('mobileMenu').classList.remove('open'); }
document.addEventListener('click',function(e){
  const m=document.getElementById('mobileMenu'),b=document.querySelector('.nav-toggle');
  if(m&&!m.contains(e.target)&&b&&!b.contains(e.target)) m.classList.remove('open');
});
const obs=new IntersectionObserver((es)=>{
  es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}});
},{threshold:0.1,rootMargin:'0px 0px -40px 0px'});
document.querySelectorAll('.animate-in').forEach(el=>obs.observe(el));
</script>
</body>
</html>
