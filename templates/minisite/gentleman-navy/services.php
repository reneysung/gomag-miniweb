<?php
/**
 * templates/minisite/gentleman-navy/services.php
 * 紳藍質感服務頁 — 由 site/services.php 在 layout_head 後 require
 * 收得到變數：$site, $client, $sub, $slug, $pageKey（其餘自取）
 */

$services = $site['services'] ?? [];
$social   = $site['social'] ?? [];
$phone    = $client['phone'] ?? '';
$telHref  = 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
$fbUrl    = $social['fb_page_url'] ?? '';
$gnIsAircon = (bool)preg_match('/冷氣|空調/u', $client['industry'] ?? '');
?>
<div class="gn">

<section class="gn-pagehero">
  <div class="gn-wrap">
    <div class="gn-breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a>　／　服務項目</div>
    <div class="gn-eyebrow">Our Services</div>
    <h2 class="gn-h2">服務項目</h2>
    <p class="gn-sub"><?= $gnIsAircon ? '到府施工、標準作業流程，清洗前後數據看得見' : '每一項服務，都以同樣的標準完成' ?></p>
  </div>
</section>

<section class="gn-section" style="padding-top:40px">
  <div class="gn-wrap">
    <div class="gn-svc-rows">
      <?php foreach ($services as $i => $svc): ?>
      <div class="gn-svc-row" id="svc-<?= (int)$svc['id'] ?>">
        <div class="gn-svc-row-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
        <div>
          <h3 class="gn-svc-row-name"><?= h($svc['name']) ?></h3>
          <?php if (!empty($svc['short_desc'])): ?>
          <div class="gn-svc-row-short"><?= h($svc['short_desc']) ?></div>
          <?php endif; ?>
          <?php if (!empty($svc['full_desc'])): ?>
          <div class="gn-svc-row-full"><?= h($svc['full_desc']) ?></div>
          <?php endif; ?>
          <?php if (!empty($svc['price_text'])): ?>
          <div class="gn-svc-price"><?= h($svc['price_text']) ?></div>
          <?php endif; ?>
          <?php if (!empty($svc['faqs'])): ?>
          <div class="gn-faq">
            <?php foreach ($svc['faqs'] as $fq): ?>
            <details>
              <summary><?= h($fq['question']) ?></summary>
              <div class="gn-faq-a"><?= nl2br(h($fq['answer'])) ?></div>
            </details>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (!$services): ?>
    <div class="gn-empty">
      <div class="gn-empty-mark">—</div>
      <h3 class="gn-empty-title">服務內容整理中</h3>
      <p class="gn-empty-desc">歡迎來電洽詢最新服務內容。</p>
      <?php if ($phone): ?><a class="gn-btn gn-btn-solid" href="<?= h($telHref) ?>"><?= h($phone) ?></a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="gn-cta">
  <div class="gn-wrap">
    <h2 class="gn-cta-title">想預約服務嗎？</h2>
    <p class="gn-cta-sub"><?= $gnIsAircon ? '來電或私訊我們，拍照即可快速估價' : '歡迎來電洽詢，專人為您服務' ?></p>
    <div class="gn-cta-actions">
      <?php if ($phone): ?><a class="gn-btn gn-btn-gold" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即來電</a><?php endif; ?>
      <?php if ($fbUrl): ?><a class="gn-btn gn-btn-light" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">Facebook 私訊</a><?php endif; ?>
    </div>
  </div>
</section>

</div>
