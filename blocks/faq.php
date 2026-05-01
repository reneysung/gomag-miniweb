<?php
// blocks/faq.php — type=faq 渲染（常見問題）
// JSON 結構：{ title, items:[{q, a}] }
require __DIR__ . '/_block_helpers.php';

$title = $blockData['title'] ?? '常見問題';
$items = $blockData['items'] ?? [];
if (!$items) return;

// JSON-LD FAQPage schema（SEO 加分）
$faqLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => array_map(fn($i) => [
        '@type' => 'Question',
        'name'  => $i['q'] ?? '',
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['a'] ?? ''],
    ], $items),
];
?>
<section class="g-section g-block g-block-faq">
  <div class="g-section-head">
    <div>
      <h2 class="g-section-title"><?= h($title) ?></h2>
    </div>
  </div>
  <div class="g-block-faq-list">
    <?php foreach ($items as $idx => $item): ?>
    <details class="g-block-faq-item"<?= $idx === 0 ? ' open' : '' ?>>
      <summary class="g-block-faq-q">
        <span class="g-block-faq-q-text"><?= h($item['q'] ?? '') ?></span>
        <span class="g-block-faq-toggle">+</span>
      </summary>
      <div class="g-block-faq-a">
        <?= nl2br(h($item['a'] ?? '')) ?>
      </div>
    </details>
    <?php endforeach; ?>
  </div>
</section>
<script type="application/ld+json"><?= json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
