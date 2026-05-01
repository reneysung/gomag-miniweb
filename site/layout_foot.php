<?php
// site/layout_foot.php
$phone   = $site['client']['phone']       ?? '';
$lineUrl = $site['social']['line_url']    ?? '#';
$fbUrl   = $site['social']['fb_page_url'] ?? '#';
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
          <?php if ($lineUrl !== '#'): ?><a href="<?= h($lineUrl) ?>" target="_blank">💬</a><?php endif; ?>
        </div>
      </div>
      <div>
        <div class="footer-h">快速連結</div>
        <a class="footer-link" href="<?= siteUrl($slug) ?>">🏠 首頁</a>
        <a class="footer-link" href="<?= siteUrl($slug,'services') ?>">🛠️ 服務項目</a>
        <a class="footer-link" href="<?= siteUrl($slug,'cases') ?>"><?= $_casesIcon ?? '📸' ?> <?= $_casesLabel ?? '施工案例' ?></a>
        <a class="footer-link" href="<?= siteUrl($slug) ?>#contact">📍 聯絡我們</a>
      </div>
      <div>
        <div class="footer-h">服務項目</div>
        <?php foreach (array_slice($site['services'],0,5) as $svc): ?>
          <a class="footer-link" href="<?= siteUrl($slug,'services') ?>#svc-<?= $svc['id'] ?>"><?= h($svc['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="footer-bottom">
      © <?= date('Y') ?> <?= h($client['brand_name']) ?> · 版權所有
      &nbsp;|&nbsp; 網站由 <a href="https://repulab.tw" style="color:rgba(255,255,255,.6)">店家好口碑</a> 製作
    </div>
  </div>
</footer>

<!-- ── 底部 Tab Bar ─────────────────────────────── -->
<nav class="bottom-tabbar">
  <a href="<?= siteUrl($slug) ?>"            class="tab-item <?= $pageKey==='home'?'active':'' ?>">
    <span class="icon">🏠</span>首頁</a>
  <a href="<?= siteUrl($slug,'services') ?>" class="tab-item <?= $pageKey==='services'?'active':'' ?>">
    <span class="icon">🛠️</span>服務</a>
  <a href="<?= siteUrl($slug,'cases') ?>"    class="tab-item <?= $pageKey==='cases'?'active':'' ?>">
    <span class="icon"><?= $_casesIcon ?? '📸' ?></span><?= ($_isFood ?? false) ? '作品' : '案例' ?></a>
  <?php if ($phone): ?>
  <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="tab-item accent">
    <span class="icon">📞</span>電話</a>
  <?php endif; ?>
  <?php if ($lineUrl !== '#'): ?>
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
