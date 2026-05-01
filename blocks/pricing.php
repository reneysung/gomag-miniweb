<?php
// blocks/pricing.php — type=pricing 渲染（價目表）
// JSON 結構：{ title, currency?, items:[{name, price, unit, features:[], highlight}] }
require __DIR__ . '/_block_helpers.php';

$title    = $blockData['title']    ?? '價目表';
$items    = $blockData['items']    ?? [];
$currency = $blockData['currency'] ?? 'TWD';
$symbol   = $currency === 'TWD' ? 'NT$' : '$';
if (!$items) return;
?>
<section class="g-section g-block g-block-pricing">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($title) ?></h2>
    </div>
  </div>
  <div class="g-block-pricing-grid">
    <?php foreach ($items as $item):
      $highlight = !empty($item['highlight']);
    ?>
    <div class="g-block-pricing-card<?= $highlight ? ' is-highlight' : '' ?>">
      <?php if ($highlight): ?>
      <div class="g-block-pricing-badge">推薦</div>
      <?php endif; ?>
      <h3 class="g-block-pricing-name"><?= h($item['name'] ?? '') ?></h3>
      <div class="g-block-pricing-price">
        <span class="g-block-pricing-symbol"><?= h($symbol) ?></span>
        <span class="g-block-pricing-amount"><?= is_numeric($item['price'] ?? '') ? number_format((int)$item['price']) : h($item['price'] ?? '') ?></span>
        <?php if (!empty($item['unit'])): ?>
        <span class="g-block-pricing-unit"><?= h($item['unit']) ?></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($item['features']) && is_array($item['features'])): ?>
      <ul class="g-block-pricing-features">
        <?php foreach ($item['features'] as $f): ?>
        <li><?= h($f) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if (!empty($item['desc'])): ?>
      <p class="g-block-pricing-desc"><?= h($item['desc']) ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
