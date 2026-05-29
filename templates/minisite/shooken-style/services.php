<?php
/**
 * templates/minisite/shooken-style/services.php  ─  商品一覽
 * URL: oishii.gomag.com.tw/services
 *
 * 內容：7 個真實 SKU（5/8/12/16/24 片 + 奧喜燒 + 小巧禮盒）
 * 全部來自 banshin.com.tw/product/ 認證資料
 */

$brandName = $client['brand_name'] ?? '';

// 完整 SKU 清單（從 banshin.com.tw/product/ 抓的真實資料）
$allSkus = [
    [
        'name'  => '長崎蛋糕・5 片',
        'price' => 'NT$ 135',
        'usage' => '入門嘗鮮',
        'image' => 'uploads/clients/oishii/p1-castella-5p.jpg',
        'desc'  => '濃郁蛋香、麥芽醇厚，鬆軟濕潤綿密。適合一人份品嘗。',
    ],
    [
        'name'  => '長崎蛋糕・8 片',
        'price' => 'NT$ 225',
        'usage' => '小家庭',
        'image' => 'uploads/clients/oishii/p5-castella-8p.jpg',
        'desc'  => '8 片包裝，適合小家庭分享或下午茶聚會。',
    ],
    [
        'name'  => '長崎蛋糕・12 片',
        'price' => 'NT$ 288',
        'usage' => '經典分享',
        'image' => 'uploads/clients/oishii/p2-castella-12p.jpg',
        'desc'  => '最受歡迎尺寸。商務、家用兩相宜，CP 值最佳。',
    ],
    [
        'name'  => '長崎蛋糕・16 片',
        'price' => 'NT$ 350',
        'usage' => '商務送禮',
        'image' => 'uploads/clients/oishii/p6-castella-16p.jpg',
        'desc'  => '中型商務送禮、團體分享適用。',
    ],
    [
        'name'  => '長崎蛋糕・24 片',
        'price' => 'NT$ 560',
        'usage' => '大型聚會',
        'image' => 'uploads/clients/oishii/p7-castella-24p.jpg',
        'desc'  => '大家庭聚會、企業團拜首選。',
    ],
    [
        'name'  => '奧喜燒',
        'price' => 'NT$ 135',
        'usage' => '茶點良伴',
        'image' => 'uploads/clients/oishii/p3-oishii-yaki.jpg',
        'desc'  => '本店蛋糕二次烘烤製成的蛋糕餅乾，香酥脆爽。',
    ],
    [
        'name'  => '小巧禮盒',
        'price' => 'NT$ 280',
        'usage' => '送禮首選',
        'image' => 'uploads/clients/oishii/p4-mini-giftbox.jpg',
        'desc'  => '長崎蛋糕＋奧喜燒組合包，送禮、商務拜訪首選。',
    ],
];

$orderUrl = 'https://www.banshin.com.tw/product/';
?>

<div class="ss-page">

  <!-- Hero（中型，比首頁矮）-->
  <section class="ss-subpage-hero">
    <div class="ss-container ss-subpage-hero-inner">
      <div class="ss-h2-en">PRODUCTS</div>
      <h1 class="ss-h2">商品一覽</h1>
      <span class="ss-h2-line"></span>
      <p class="ss-subpage-lead">奧喜長崎蛋糕所有規格與包裝・每日新鮮手作</p>
    </div>
  </section>

  <!-- 7 SKU grid（3 列） -->
  <section class="ss-section">
    <div class="ss-container">
      <div class="ss-sku-grid">
        <?php foreach ($allSkus as $sku): ?>
        <article class="ss-sku">
          <?php if ($sku['image']): ?>
          <img class="ss-sku-img" src="<?= BASE_URL ?>/<?= htmlspecialchars($sku['image']) ?>" alt="<?= htmlspecialchars($sku['name']) ?>" loading="lazy">
          <?php else: ?>
          <div class="ss-sku-img ss-sku-img--placeholder">
            <span><?= htmlspecialchars(mb_substr($sku['name'], 0, 5)) ?></span>
          </div>
          <?php endif; ?>
          <div class="ss-sku-body">
            <div class="ss-sku-usage"><?= htmlspecialchars($sku['usage']) ?></div>
            <h3 class="ss-sku-name"><?= htmlspecialchars($sku['name']) ?></h3>
            <p class="ss-sku-desc"><?= htmlspecialchars($sku['desc']) ?></p>
            <div class="ss-sku-price"><?= htmlspecialchars($sku['price']) ?></div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 訂購 CTA -->
  <section class="ss-cta">
    <h2>所有規格皆可線上訂購</h2>
    <p>當日預訂、現場自取、線上宅配皆可。彌月禮盒滿 NT$ 6,000 享免費寶寶照小卡（10 工作天）。</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a class="ss-btn ss-btn--solid" href="<?= $orderUrl ?>" target="_blank" rel="noopener">前往奧喜官網訂購 →</a>
      <?php if (!empty($client['phone'])): ?>
      <a class="ss-btn" href="tel:<?= htmlspecialchars($client['phone']) ?>">電話訂購　<?= htmlspecialchars($client['phone']) ?></a>
      <?php endif; ?>
    </div>
  </section>

</div>
