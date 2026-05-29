<?php
/**
 * templates/minisite/shooken-style/cases.php  ─  關於奧喜
 * URL: oishii.gomag.com.tw/cases
 *
 * 內容：完整品牌故事（おいしい命名 + 2020 遷店 + 4 原料 + 不化學添加）
 * 全部來自 banshin.com.tw/about/ 認證資料
 */

$brandName = $client['brand_name'] ?? '';

// 製作過程圖（共用 process_gallery）
$processGallery = [];
if (!empty($client['process_gallery'])) {
    $_pg = is_array($client['process_gallery']) ? $client['process_gallery'] : json_decode((string)$client['process_gallery'], true);
    if (is_array($_pg)) $processGallery = array_slice($_pg, 0, 4);
}

$ingredients = [
    ['name' => '雞　蛋', 'en' => 'EGG',    'desc' => '蛋香為長崎蛋糕的靈魂。新鮮雞蛋是綿密口感的根本。'],
    ['name' => '砂　糖', 'en' => 'SUGAR',  'desc' => '純粹甜度，不加蜂蜜，保留蛋與麥芽的本味。'],
    ['name' => '麵　粉', 'en' => 'FLOUR',  'desc' => '低筋麵粉手工拌入，撐起鬆軟濕潤的蛋糕體。'],
    ['name' => '麥　芽', 'en' => 'MALT',   'desc' => '麥芽糖漿是奧喜口感的靈魂，賦予醇厚回甘。'],
];
?>

<div class="ss-page">

  <!-- Hero（中型）-->
  <section class="ss-subpage-hero">
    <div class="ss-container ss-subpage-hero-inner">
      <div class="ss-h2-en">ABOUT</div>
      <h1 class="ss-h2">關於奧喜</h1>
      <span class="ss-h2-line"></span>
      <p class="ss-subpage-lead">奧妙手藝傳千里　喜悅馨品頌萬戶</p>
    </div>
  </section>

  <!-- 命名由來 -->
  <section class="ss-section ss-section--cream">
    <div class="ss-container">
      <div class="ss-h2-en">NAMING</div>
      <h2 class="ss-h2">關於「奧喜」</h2>
      <span class="ss-h2-line"></span>
      <div class="ss-about-body">
        <p>奧喜長崎蛋糕的「奧喜」，<strong>取自日文「おいしい」</strong>，意為「好吃」。</p>
        <p>品牌寓意為「<strong>奧</strong>妙手藝傳千里、<strong>喜</strong>悅馨品頌萬戶」。每一塊蛋糕都期盼承載這份手藝與喜悅，送到每一位顧客手中。</p>
      </div>
    </div>
  </section>

  <!-- 遷店歷史 -->
  <section class="ss-section">
    <div class="ss-container">
      <div class="ss-h2-en">SINCE 2020</div>
      <h2 class="ss-h2">2020 年 ・ 遷至松竹路</h2>
      <span class="ss-h2-line"></span>
      <div class="ss-about-body">
        <p>2020 年 12 月，奧喜長崎蛋糕從綏遠路遷至<strong>台中市北屯區松竹路三段 22 號</strong>，鄰近梅川路。</p>
        <p>新址寬敞、停車方便，店面設計沉穩典雅，期盼提供顧客更舒適的選購體驗。</p>
      </div>
    </div>
  </section>

  <!-- 4 原料介紹 -->
  <section class="ss-section ss-section--cream">
    <div class="ss-container">
      <div class="ss-h2-en">PURE INGREDIENTS</div>
      <h2 class="ss-h2">四種純粹原料</h2>
      <span class="ss-h2-line"></span>
      <p class="ss-about-body" style="margin-bottom:48px;">不採花俏虛飄之作法，更<strong>無添加任何有害身心健康之化學馨香成份</strong>。</p>

      <div class="ss-ingredients">
        <?php foreach ($ingredients as $ing): ?>
        <div class="ss-ingredient">
          <div class="ss-ingredient-en"><?= htmlspecialchars($ing['en']) ?></div>
          <h3 class="ss-ingredient-name"><?= htmlspecialchars($ing['name']) ?></h3>
          <p class="ss-ingredient-desc"><?= htmlspecialchars($ing['desc']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 製作過程 -->
  <?php if (!empty($processGallery)): ?>
  <section class="ss-section ss-section--brown">
    <div class="ss-container">
      <div class="ss-h2-en" style="color:var(--ss-gold)">PROCESS</div>
      <h2 class="ss-h2" style="color:var(--ss-ivory)">蛋糕製作過程</h2>
      <span class="ss-h2-line"></span>
      <p class="ss-process-quote">「蛋糕每日手工製作，每一個步驟都須十分細心，堅持最好。」</p>
      <div class="ss-process-gallery">
        <?php foreach ($processGallery as $g): ?>
        <?php if (empty($g['image'])) continue; ?>
        <figure>
          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($g['image']) ?>" alt="<?= htmlspecialchars($g['caption'] ?? '製作過程') ?>" loading="lazy">
          <?php if (!empty($g['caption'])): ?><figcaption><?= htmlspecialchars($g['caption']) ?></figcaption><?php endif; ?>
        </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- 經營理念 -->
  <section class="ss-section">
    <div class="ss-container">
      <div class="ss-h2-en">PHILOSOPHY</div>
      <h2 class="ss-h2">經營理念</h2>
      <span class="ss-h2-line"></span>
      <div class="ss-about-body">
        <p style="font-size:1.5rem;font-family:'Noto Serif TC',serif;color:var(--ss-brown);letter-spacing:.3em;text-align:center;margin-bottom:24px;">
          誠信為本　踏實為重
        </p>
        <p>「食材取其優質，價格取其平實」，期盼<strong>富者不嫌劣，貧者不厭貴</strong>。</p>
        <p class="ss-about-quote">「天下無味美　更增一奇絕」 — 奧喜本舖</p>
      </div>
    </div>
  </section>

</div>
