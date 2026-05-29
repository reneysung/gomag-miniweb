<?php
/**
 * templates/minisite/shooken-style/testimonials.php  ─  顧客分享
 * URL: oishii.gomag.com.tw/testimonials
 *
 * 內容：真實部落客 quote 卡片 + 原文連結
 * 資料來源：testimonials 表（migration 064 寫入的真實部落客評論）
 */

$brandName = $client['brand_name'] ?? '';

// 撈評價（從 site loadSiteData 已過濾 demo，但我們直接撈含 blog source）
$_db = getDB();
$_t = $_db->prepare("SELECT * FROM testimonials WHERE client_id=? AND is_active=1 ORDER BY sort_order, id LIMIT 12");
$_t->execute([(int)$client['id']]);
$reviews = $_t->fetchAll();

$bgImg = !empty($client['hero_image_path']) ? BASE_URL . '/' . htmlspecialchars($client['hero_image_path']) : '';
?>

<div class="ss-page">

  <!-- Hero（中型） -->
  <section class="ss-subpage-hero">
    <div class="ss-container ss-subpage-hero-inner">
      <div class="ss-h2-en">VOICES</div>
      <h1 class="ss-h2">顧客分享</h1>
      <span class="ss-h2-line"></span>
      <p class="ss-subpage-lead">部落客與顧客的真實體驗</p>
    </div>
  </section>

  <!-- Reviews 列表 -->
  <section class="ss-section ss-section--cream">
    <div class="ss-container">

      <?php if (empty($reviews)): ?>
      <p class="ss-about-body">尚無分享。</p>

      <?php else: ?>
      <div class="ss-reviews-list">
        <?php foreach ($reviews as $r): ?>
        <?php
          $_src   = $r['source'] ?? '';
          $_url   = $r['source_url'] ?? '';
          $_label = ['blog' => '部落客分享', 'google' => 'Google 評論', 'fb' => 'FB 評論', 'ig' => 'IG 分享', 'demo' => '範例'][$_src] ?? '';
        ?>
        <article class="ss-review-card">
          <header class="ss-review-header">
            <div>
              <h3 class="ss-review-author"><?= htmlspecialchars($r['reviewer_name'] ?? '匿名') ?></h3>
              <?php if ($_label): ?>
              <div class="ss-review-source"><?= htmlspecialchars($_label) ?></div>
              <?php endif; ?>
            </div>
            <?php if (!empty($r['rating']) && (int)$r['rating'] >= 4): ?>
            <div class="ss-review-rating"><?= str_repeat('★', (int)$r['rating']) ?></div>
            <?php endif; ?>
          </header>

          <p class="ss-review-text"><?= htmlspecialchars(mb_strimwidth(strip_tags($r['content'] ?? ''), 0, 280, '…')) ?></p>

          <?php if ($_url): ?>
          <a class="ss-review-link" href="<?= htmlspecialchars($_url) ?>" target="_blank" rel="noopener nofollow">閱讀原文 ↗</a>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>

      <p class="ss-reviews-disclaimer">
        以上分享來源自獨立部落客之公開文章，未經任何贊助或合作。
      </p>
      <?php endif; ?>

    </div>
  </section>

  <!-- CTA 回首頁／訂購 -->
  <section class="ss-cta">
    <h2>歡迎親自到店體驗</h2>
    <p>松竹路本店週一至週日 09:00–19:00 營業。</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a class="ss-btn ss-btn--solid" href="https://www.banshin.com.tw/product/" target="_blank" rel="noopener">前往奧喜官網訂購 →</a>
      <?php if (!empty($client['phone'])): ?>
      <a class="ss-btn" href="tel:<?= htmlspecialchars($client['phone']) ?>"><?= htmlspecialchars($client['phone']) ?></a>
      <?php endif; ?>
    </div>
  </section>

</div>
