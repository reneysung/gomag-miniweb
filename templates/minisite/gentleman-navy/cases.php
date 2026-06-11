<?php
/**
 * templates/minisite/gentleman-navy/cases.php
 * 紳藍質感案例頁 — 由 site/cases.php 在 layout_head 後 require
 */

$cases   = $site['cases'] ?? [];
$social  = $site['social'] ?? [];
$phone   = $client['phone'] ?? '';
$telHref = 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
$fbUrl   = $social['fb_page_url'] ?? '';
?>
<div class="gn">

<section class="gn-pagehero">
  <div class="gn-wrap">
    <div class="gn-breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a>　／　施工案例</div>
    <div class="gn-eyebrow">Case Studies</div>
    <h2 class="gn-h2">施工案例</h2>
    <p class="gn-sub">真實 Before／After，效果看得見</p>
  </div>
</section>

<section class="gn-section" style="padding-top:48px">
  <div class="gn-wrap">
    <?php if ($cases): ?>
    <div class="gn-case-grid" style="margin-top:0">
      <?php foreach ($cases as $c): ?>
      <div class="gn-case-card">
        <?php $b = !empty($c['before_image']) ? BASE_URL . '/' . h($c['before_image']) : '';
              $a = !empty($c['after_image'])  ? BASE_URL . '/' . h($c['after_image'])  : ''; ?>
        <?php if ($b && $a): ?>
        <div class="gn-case-imgs">
          <img class="gn-before" src="<?= $b ?>" alt="<?= h($c['title']) ?>・施作前" loading="lazy">
          <img class="gn-after" src="<?= $a ?>" alt="<?= h($c['title']) ?>・施作後" loading="lazy">
          <div class="gn-case-split"></div>
          <span class="gn-case-tag gn-tb">BEFORE</span><span class="gn-case-tag gn-ta">AFTER</span>
        </div>
        <?php elseif ($a ?: $b): ?>
        <img class="gn-case-single" src="<?= $a ?: $b ?>" alt="<?= h($c['title']) ?>" loading="lazy">
        <?php endif; ?>
        <div class="gn-case-body">
          <h3 class="gn-case-title"><?= h($c['title']) ?></h3>
          <div class="gn-case-meta"><?= h(trim(($c['svc_name'] ?? '') . (!empty($c['location']) ? '　' . $c['location'] : ''))) ?></div>
          <?php if (!empty($c['description'])): ?>
          <p style="font-size:.86rem;color:var(--gn-muted);line-height:1.9;margin:10px 0 0"><?= h(mb_strimwidth(strip_tags($c['description']), 0, 90, '…')) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="gn-empty" style="margin-top:0">
      <div class="gn-empty-mark">—</div>
      <h3 class="gn-empty-title">案例整理中</h3>
      <p class="gn-empty-desc">實際施工案例正陸續整理上架<?= $fbUrl ? '，最新案例可先到 Facebook 查看' : '' ?>。</p>
      <?php if ($fbUrl): ?><a class="gn-btn gn-btn-ghost" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">前往 Facebook 看案例</a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="gn-cta">
  <div class="gn-wrap">
    <h2 class="gn-cta-title">想讓家裡也煥然一新嗎？</h2>
    <p class="gn-cta-sub"><?= preg_match('/冷氣|空調/u', $client['industry'] ?? '') ? '來電或私訊我們，拍照即可快速估價' : '歡迎來電洽詢，專人為您服務' ?></p>
    <div class="gn-cta-actions">
      <?php if ($phone): ?><a class="gn-btn gn-btn-gold" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即來電</a><?php endif; ?>
      <?php if ($fbUrl): ?><a class="gn-btn gn-btn-light" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">Facebook 私訊</a><?php endif; ?>
    </div>
  </div>
</section>

</div>
