<?php
// blocks/menu.php — type=menu 渲染（餐飲分類）
// JSON 結構：{ title, groups:[{ name, items:[{name, price, desc, image}] }] }
require __DIR__ . '/_block_helpers.php';

$title  = $blockData['title']  ?? '招牌菜單';
$groups = $blockData['groups'] ?? [];
if (!$groups) return;
?>
<section class="g-section g-block g-block-menu">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($title) ?></h2>
    </div>
  </div>
  <?php foreach ($groups as $group): ?>
  <div class="g-block-menu-group">
    <?php if (!empty($group['name'])): ?>
    <h3 class="g-block-menu-group-name"><?= h($group['name']) ?></h3>
    <?php endif; ?>
    <ul class="g-block-menu-list">
      <?php foreach (($group['items'] ?? []) as $item): ?>
      <li class="g-block-menu-item">
        <?php if (!empty($item['image'])): ?>
        <div class="g-block-menu-img" style="background-image:url('<?= h(BASE_URL . '/' . ltrim($item['image'], '/')) ?>');"></div>
        <?php endif; ?>
        <div class="g-block-menu-body">
          <div class="g-block-menu-row">
            <span class="g-block-menu-name">
              <?= h($item['name'] ?? '') ?>
              <?php if (!empty($item['tag'])): ?>
              <span class="g-block-menu-tag"><?= h($item['tag']) ?></span>
              <?php endif; ?>
            </span>
            <?php if (isset($item['price'])): ?>
            <span class="g-block-menu-price">
              $<?= is_numeric($item['price']) ? number_format((int)$item['price']) : h($item['price']) ?>
              <?php if (!empty($item['price_unit'])): ?>
              <span class="g-block-menu-unit"><?= h($item['price_unit']) ?></span>
              <?php endif; ?>
            </span>
            <?php endif; ?>
          </div>
          <?php if (!empty($item['desc'])): ?>
          <div class="g-block-menu-desc"><?= h($item['desc']) ?></div>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
</section>
