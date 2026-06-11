<?php
/**
 * templates/minisite/gentleman-navy/testimonials.php
 * 紳藍質感評價頁 — 由 site/testimonials.php 在 layout_head 後 require
 * 0 筆評價＝優雅空狀態（不顯示任何虛構統計）
 */

$testimonials = $site['testimonials'] ?? [];
$social  = $site['social'] ?? [];
$phone   = $client['phone'] ?? '';
$telHref = 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
$fbUrl   = $social['fb_page_url'] ?? '';
?>
<div class="gn">

<section class="gn-pagehero">
  <div class="gn-wrap">
    <div class="gn-breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a>　／　客戶好評</div>
    <div class="gn-eyebrow">Testimonials</div>
    <h2 class="gn-h2">客戶好評</h2>
    <p class="gn-sub">每一則回饋，都是真實的服務紀錄</p>
  </div>
</section>

<section class="gn-section" style="padding-top:48px">
  <div class="gn-wrap">
    <?php if ($testimonials): ?>
    <div class="gn-review-grid" style="margin-top:0">
      <?php foreach ($testimonials as $t): ?>
      <div class="gn-review-card">
        <div class="gn-review-stars">★★★★★</div>
        <p class="gn-review-text">「<?= h($t['content']) ?>」</p>
        <div class="gn-review-name">—　<strong><?= h($t['reviewer_name']) ?></strong><?= !empty($t['svc_name']) ? '　・　' . h($t['svc_name']) : '' ?></div>
        <?php if (!empty($t['source_url'])): ?>
        <a class="gn-review-link" href="<?= h($t['source_url']) ?>" target="_blank" rel="noopener nofollow ugc">閱讀全文 →</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="gn-empty" style="margin-top:0">
      <div class="gn-empty-mark">—</div>
      <h3 class="gn-empty-title">好評整理中</h3>
      <p class="gn-empty-desc">客戶回饋正陸續整理上架，歡迎成為下一位滿意的客戶。</p>
      <?php if ($phone): ?><a class="gn-btn gn-btn-solid" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即預約</a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="gn-cta">
  <div class="gn-wrap">
    <h2 class="gn-cta-title">下一位滿意的客戶，就是您</h2>
    <p class="gn-cta-sub"><?= preg_match('/冷氣|空調/u', $client['industry'] ?? '') ? '來電或私訊我們，拍照即可快速估價' : '歡迎來電洽詢，專人為您服務' ?></p>
    <div class="gn-cta-actions">
      <?php if ($phone): ?><a class="gn-btn gn-btn-gold" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即來電</a><?php endif; ?>
      <?php if ($fbUrl): ?><a class="gn-btn gn-btn-light" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">Facebook 私訊</a><?php endif; ?>
    </div>
  </div>
</section>

</div>
