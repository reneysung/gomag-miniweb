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
        <div class="footer-brand"><?= ($_hideEmoji ?? false) ? '' : (($_logoIcon ?? '🌊') . ' ') ?><?= h($client['brand_name']) ?></div>
        <div class="footer-tagline"><?= h($client['tagline'] ?? '') ?></div>
        <?php if ($phone): ?>
          <div class="footer-contact-item"><?= ($_hideEmoji ?? false) ? '' : '📞 ' ?><a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>"><?= h($phone) ?></a></div>
        <?php endif; ?>
        <?php if ($client['email']): ?>
          <div class="footer-contact-item"><?= ($_hideEmoji ?? false) ? '' : '📧 ' ?><a href="mailto:<?= h($client['email']) ?>"><?= h($client['email']) ?></a></div>
        <?php endif; ?>
        <?php if ($client['address']): ?>
          <div class="footer-contact-item"><?= ($_hideEmoji ?? false) ? '' : '📍 ' ?><?= h($client['address']) ?></div>
        <?php endif; ?>
        <div class="footer-social">
          <?php // 一律用品牌 SVG logo（emoji/文字會爆出圓圈，且第三方按鈕需自訂樣式） ?>
          <?php if ($fbUrl !== '#'): ?><a href="<?= h($fbUrl) ?>" target="_blank" aria-label="Facebook"><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.792-4.697 4.533-4.697 1.313 0 2.686.236 2.686.236v2.971H15.83c-1.491 0-1.956.93-1.956 1.886v2.264h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg></a><?php endif; ?>
          <?php if (!empty($social['instagram_url'])): ?><a href="<?= h($social['instagram_url']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.336 3.608 1.31.974.975 1.248 2.242 1.31 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.336 2.633-1.31 3.608-.975.974-2.242 1.248-3.608 1.31-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.336-3.608-1.31-.974-.975-1.248-2.242-1.31-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.336-2.633 1.31-3.608.975-.974 2.242-1.248 3.608-1.31 1.266-.058 1.646-.07 4.85-.07zM12 0C8.741 0 8.332.014 7.052.072 5.197.157 3.355.673 2.014 2.014.673 3.355.157 5.197.072 7.052.014 8.332 0 8.741 0 12s.014 3.668.072 4.948c.085 1.855.601 3.697 1.942 5.038 1.341 1.341 3.183 1.857 5.038 1.942C8.332 23.986 8.741 24 12 24s3.668-.014 4.948-.072c1.855-.085 3.697-.601 5.038-1.942 1.341-1.341 1.857-3.183 1.942-5.038.058-1.28.072-1.689.072-4.948s-.014-3.668-.072-4.948c-.085-1.855-.601-3.697-1.942-5.038C20.645.673 18.803.157 16.948.072 15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a><?php endif; ?>
          <?php if ($lineUrl): ?><a href="<?= h($lineUrl) ?>" target="_blank" aria-label="LINE"><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg></a><?php endif; ?>
        </div>
      </div>
      <div>
        <div class="footer-h">快速連結</div>
        <a class="footer-link" href="<?= siteUrl($slug) ?>"><?= ($_hideEmoji ?? false) ? '' : '🏠 ' ?>首頁</a>
        <?php if ($_showServices ?? true): ?>
        <a class="footer-link" href="<?= siteUrl($slug,'services') ?>"><?= ($_hideEmoji ?? false) ? '' : '🛠️ ' ?><?= h($_servicesLabel ?? '服務項目') ?></a>
        <?php endif; ?>
        <?php if ($_showCases ?? true): ?>
        <a class="footer-link" href="<?= siteUrl($slug,'cases') ?>"><?= ($_hideEmoji ?? false) ? '' : (($_casesIcon ?? '📸') . ' ') ?><?= h($_casesLabelOverride ?? $_casesLabel ?? '施工案例') ?></a>
        <?php endif; ?>
        <?php if ($_showTestimonials ?? true): ?>
        <a class="footer-link" href="<?= siteUrl($slug,'testimonials') ?>"><?= ($_hideEmoji ?? false) ? '' : '⭐ ' ?><?= h($_testimonialsLabel ?? '客戶好評') ?></a>
        <?php endif; ?>
        <a class="footer-link" href="<?= siteUrl($slug) ?>#contact"><?= ($_hideEmoji ?? false) ? '' : '📍 ' ?>聯絡我們</a>
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
    <?php if (!($_hideEmoji ?? false)): ?><span class="icon">🏠</span><?php endif; ?>首頁</a>
  <?php if ($_showServices ?? true): ?>
  <a href="<?= siteUrl($slug,'services') ?>" class="tab-item <?= $pageKey==='services'?'active':'' ?>">
    <?php if (!($_hideEmoji ?? false)): ?><span class="icon">🛠️</span><?php endif; ?><?= h(mb_substr($_servicesLabel ?? '服務', 0, 2)) ?></a>
  <?php endif; ?>
  <?php if ($_showCases ?? true): ?>
  <a href="<?= siteUrl($slug,'cases') ?>"    class="tab-item <?= $pageKey==='cases'?'active':'' ?>">
    <?php if (!($_hideEmoji ?? false)): ?><span class="icon"><?= $_casesIcon ?? '📸' ?></span><?php endif; ?><?= h(mb_substr($_casesLabelOverride ?? (($_isFood ?? false) ? '作品' : '案例'), 0, 2)) ?></a>
  <?php endif; ?>
  <?php if ($phone): ?>
  <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="tab-item accent">
    <?php if (!($_hideEmoji ?? false)): ?><span class="icon">📞</span><?php endif; ?>電話</a>
  <?php endif; ?>
  <?php if ($lineUrl): ?>
  <a href="<?= h($lineUrl) ?>" class="tab-item" target="_blank"<?= ($_hideEmoji ?? false) ? '' : ' style="color:#06c755"' ?>>
    <?php if (!($_hideEmoji ?? false)): ?><span class="icon">💬</span><?php endif; ?>LINE</a>
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
