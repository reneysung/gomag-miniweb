<?php
// blocks/portfolio.php — type=portfolio 渲染（作品/案例/氛圍照）
// JSON 結構：{ title, layout?, items:[{image, title, desc, tags:[], is_large}] }
require __DIR__ . '/_block_helpers.php';

$title  = $blockData['title']  ?? '作品集';
$items  = $blockData['items']  ?? [];
$layout = $blockData['layout'] ?? 'grid';
if (!$items) return;
?>
<section class="g-section g-block g-block-portfolio">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($title) ?></h2>
    </div>
  </div>
  <div class="g-block-portfolio-grid g-block-portfolio-<?= h($layout) ?>">
    <?php foreach ($items as $item):
      $isLarge = !empty($item['is_large']);
    ?>
    <div class="g-block-portfolio-item<?= $isLarge ? ' is-large' : '' ?>">
      <?php if (!empty($item['image'])): ?>
      <div class="g-block-portfolio-img" style="background-image:url('<?= h(BASE_URL . '/' . ltrim($item['image'], '/')) ?>');">
      </div>
      <?php endif; ?>
      <?php if (!empty($item['title']) || !empty($item['desc']) || !empty($item['tags'])): ?>
      <div class="g-block-portfolio-overlay">
        <?php if (!empty($item['tags'])): ?>
        <div class="g-block-portfolio-tags">
          <?php foreach ($item['tags'] as $tag): ?>
          <span class="g-block-portfolio-tag"><?= h($tag) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($item['title'])): ?>
        <div class="g-block-portfolio-title"><?= h($item['title']) ?></div>
        <?php endif; ?>
        <?php if (!empty($item['desc'])): ?>
        <div class="g-block-portfolio-desc"><?= h($item['desc']) ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
