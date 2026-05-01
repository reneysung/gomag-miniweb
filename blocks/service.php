<?php
// blocks/service.php — type=service 渲染
// JSON 結構：{ title, items:[{icon, name, short_desc, price_text, image}] }
require __DIR__ . '/_block_helpers.php';

$title = $blockData['title'] ?? '核心服務';
$items = $blockData['items'] ?? [];
if (!$items) return;
?>
<section class="g-section g-block g-block-service">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($title) ?></h2>
    </div>
  </div>
  <div class="g-block-service-grid">
    <?php foreach ($items as $item): ?>
    <div class="g-block-service-item">
      <?php if (!empty($item['image'])): ?>
      <div class="g-block-service-img" style="background-image:url('<?= h(BASE_URL . '/' . ltrim($item['image'], '/')) ?>');"></div>
      <?php elseif (!empty($item['icon'])): ?>
      <div class="g-block-service-icon"><?= h($item['icon']) ?></div>
      <?php endif; ?>
      <div class="g-block-service-body">
        <h3 class="g-block-service-name"><?= h($item['name'] ?? '') ?></h3>
        <?php if (!empty($item['short_desc'])): ?>
        <p class="g-block-service-desc"><?= h($item['short_desc']) ?></p>
        <?php endif; ?>
        <?php if (!empty($item['price_text'])): ?>
        <div class="g-block-service-price"><?= h($item['price_text']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
