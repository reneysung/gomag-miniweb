<?php
/**
 * main/layout_foot.php
 * 主站共用 Footer（Phase F：g-* 設計系統）
 */
$contactPhone = getPlatformSetting('contact_phone', '');
?>
</main>

<footer class="g-site-footer">
  <div class="g-site-footer-inner">
    <div class="g-site-footer-brand">
      <a class="g-site-footer-logo" href="<?= BASE_URL ?>/">
        <span class="g-site-logo-mark">店</span>
        <span>店家好口碑</span>
      </a>
      <p class="g-site-footer-desc">全台在地店家平台，匯集優質商家，提供完整服務資訊與真實口碑。</p>
    </div>

    <div class="g-site-footer-col">
      <h4>瀏覽</h4>
      <ul>
        <li><a href="<?= BASE_URL ?>/">首頁</a></li>
        <li><a href="<?= BASE_URL ?>/category.php">所有分類</a></li>
        <li><a href="<?= BASE_URL ?>/city.php">縣市瀏覽</a></li>
        <li><a href="<?= BASE_URL ?>/search.php">搜尋店家</a></li>
      </ul>
    </div>

    <div class="g-site-footer-col">
      <h4>店家專區</h4>
      <ul>
        <li><a href="https://lin.ee/N0z1eq9" target="_blank" rel="noopener">合作上架</a></li>
      </ul>
    </div>

    <div class="g-site-footer-col">
      <h4>聯絡</h4>
      <ul>
        <li><a href="https://lin.ee/N0z1eq9" class="g-line-btn g-line-btn--sm" target="_blank" rel="noopener">
          <svg class="g-line-btn-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.486 2 2 5.797 2 10.49c0 4.205 3.56 7.726 8.37 8.392.326.07.77.215.882.494.1.252.066.647.033.901l-.143.857c-.043.253-.2.99.868.54 1.07-.45 5.766-3.397 7.866-5.814C21.46 14.166 22 12.42 22 10.49 22 5.797 17.514 2 12 2z"/></svg>
          <span>加入 LINE 好友</span>
        </a></li>
        <?php if ($contactPhone): ?>
        <li><a href="tel:<?= h($contactPhone) ?>"><?= h($contactPhone) ?></a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="g-site-footer-bottom">
    <span>© <?= date('Y') ?> 店家好口碑・全台在地店家平台</span>
    <span><a href="<?= BASE_URL ?>/sitemap.xml">Sitemap</a></span>
  </div>
</footer>

<!-- GA4 轉換事件：電話 / LINE / 地圖 點擊（委派監聽，只在 gtag 存在時送出；未裝 GA 不影響） -->
<script>
(function () {
  if (typeof window.gtag !== 'function') return;
  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a') : null;
    if (!a) return;
    var href = (a.getAttribute('href') || '').trim();
    if (!href) return;
    if (/^tel:/i.test(href)) {
      gtag('event', 'phone_click', { phone_number: href.replace(/^tel:/i, ''), page_path: location.pathname });
    } else if (/(?:line\.me|lin\.ee)/i.test(href)) {
      gtag('event', 'line_click', { page_path: location.pathname });
    } else if (/google\.[^\/]*\/maps|maps\.app\.goo|goo\.gl\/maps/i.test(href)) {
      gtag('event', 'map_click', { page_path: location.pathname });
    }
  }, true);
})();
</script>

</body>
</html>
