<?php
/**
 * main/layout_foot.php
 * 主站共用 Footer（Phase F：g-* 設計系統）
 */
$contactEmail = getPlatformSetting('contact_email', 'contact@gomag.com.tw');
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
        <li><a href="mailto:<?= h($contactEmail) ?>">合作上架</a></li>
      </ul>
    </div>

    <div class="g-site-footer-col">
      <h4>聯絡</h4>
      <ul>
        <li><a href="mailto:<?= h($contactEmail) ?>"><?= h($contactEmail) ?></a></li>
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

</body>
</html>
