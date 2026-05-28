<?php
/**
 * templates/minisite/shooken-style/home.php
 * 松翁軒風小官網首頁 — 由 site/index.php 在 layout_head 後 require
 *
 * 收得到變數：$site, $client, $slug, $sub, $social, $phone, $lineUrl, $services, $cases, $testimonials, $seo
 *
 * 設計：一頁式（hero / about / products / process / visit / cta / footer mark）
 * 不編造任何內容 — 全引 banshin 認證事實 + 客戶 DB 既有資料
 */

// ── 撈產品（從 store_blocks service type 或 services 表）─────
$products = [];
$_db = getDB();
$_blocksRes = $_db->prepare("SELECT * FROM store_blocks WHERE client_id=? AND type='service' AND is_active=1 ORDER BY sort_order LIMIT 1");
$_blocksRes->execute([(int)$client['id']]);
if ($_block = $_blocksRes->fetch()) {
    $_data = is_array($_block['data']) ? $_block['data'] : (json_decode((string)$_block['data'], true) ?: []);
    foreach (($_data['items'] ?? []) as $it) {
        $products[] = [
            'name'  => $it['name'] ?? '',
            'desc'  => $it['short_desc'] ?? ($it['desc'] ?? ''),
            'price' => $it['price'] ?? '',
            'image' => !empty($it['image']) ? BASE_URL . '/' . htmlspecialchars($it['image']) : '',
        ];
        if (count($products) >= 4) break;
    }
    $_productsMoreNote = $_data['more_note'] ?? '';
}
if (empty($products) && !empty($services)) {
    foreach (array_slice($services, 0, 4) as $s) {
        $products[] = [
            'name'  => $s['name'] ?? '',
            'desc'  => $s['short_desc'] ?? '',
            'price' => $s['price_text'] ?? '',
            'image' => !empty($s['image_path']) ? BASE_URL . '/' . htmlspecialchars($s['image_path']) : '',
        ];
    }
    $_productsMoreNote = '';
}

// ── Hero 輪播圖 — 從 process_gallery + hero_image_path 抽 ───
$heroSlides = [];
if (!empty($client['hero_image_path'])) {
    $heroSlides[] = BASE_URL . '/' . htmlspecialchars($client['hero_image_path']);
}
if (!empty($client['process_gallery'])) {
    $_pg = is_array($client['process_gallery']) ? $client['process_gallery'] : json_decode((string)$client['process_gallery'], true);
    if (is_array($_pg)) {
        foreach ($_pg as $g) {
            if (!empty($g['image'])) {
                $heroSlides[] = BASE_URL . '/' . htmlspecialchars($g['image']);
                if (count($heroSlides) >= 4) break;
            }
        }
    }
}
$heroSlides = array_slice($heroSlides, 0, 4);

// ── 製作過程 ─────────────────────────────
$processGallery = [];
if (!empty($client['process_gallery'])) {
    $_pg = is_array($client['process_gallery']) ? $client['process_gallery'] : json_decode((string)$client['process_gallery'], true);
    if (is_array($_pg)) $processGallery = array_slice($_pg, 0, 4);
}

// ── 營業時間 ─────────────────────────────
$openingHours = [];
if (!empty($client['opening_hours_json'])) {
    $_oh = is_array($client['opening_hours_json']) ? $client['opening_hours_json'] : json_decode((string)$client['opening_hours_json'], true);
    if (is_array($_oh)) $openingHours = $_oh;
}

// ── 訂購 URL（客戶可後台填 order_url，否則 fallback 到外部官網） ──
$orderUrl = $client['order_url'] ?? $client['external_website_url'] ?? '';

$brandName = $client['brand_name'] ?? '';
$tagline   = $client['tagline'] ?? '';
$address   = $client['address'] ?? '';
$phone     = $client['phone'] ?? $phone ?? '';
$mapsEmbed = $client['google_maps_embed'] ?? '';
?>

<div class="ss-page">

  <!-- ═══════ Hero（大圖輪播）═══════ -->
  <?php if (!empty($heroSlides)): ?>
  <section class="ss-hero">
    <div class="ss-hero-slides">
      <?php foreach ($heroSlides as $i => $img): ?>
      <div class="ss-hero-slide <?= $i === 0 ? 'active' : '' ?>" style="background-image:url('<?= $img ?>')"></div>
      <?php endforeach; ?>
    </div>
    <div class="ss-hero-overlay">
      <div class="ss-hero-overlay-inner">
        <div class="ss-hero-slogan">天下無味美　更增一奇絕</div>
        <div class="ss-hero-slogan-sub">NAGASAKI CASTELLA · SINCE 2020</div>
      </div>
    </div>
    <?php if (count($heroSlides) > 1): ?>
    <div class="ss-hero-dots">
      <?php foreach ($heroSlides as $i => $_): ?>
      <button class="ss-hero-dot <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="切到第 <?= $i+1 ?> 張"></button>
      <?php endforeach; ?>
    </div>
    <script>
      (function(){
        const slides = document.querySelectorAll('.ss-hero-slide');
        const dots   = document.querySelectorAll('.ss-hero-dot');
        let idx = 0;
        function show(i){
          slides.forEach((s,k)=>s.classList.toggle('active', k===i));
          dots.forEach((d,k)=>d.classList.toggle('active', k===i));
          idx = i;
        }
        dots.forEach((d,k)=>d.addEventListener('click', ()=>show(k)));
        setInterval(()=>show((idx+1) % slides.length), 5000);
      })();
    </script>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ═══════ 關於本舖 ═══════ -->
  <?php if (!empty($client['about_text'])): ?>
  <section class="ss-section ss-section--cream">
    <div class="ss-container">
      <div class="ss-h2-en">ABOUT</div>
      <h2 class="ss-h2"><?= htmlspecialchars($brandName) ?></h2>
      <span class="ss-h2-line"></span>

      <div class="ss-about-body">
        <?= $client['about_text'] /* trusted HTML — 後台儲存 */ ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 看板商品 ═══════ -->
  <?php if (!empty($products)): ?>
  <section class="ss-section">
    <div class="ss-container">
      <div class="ss-h2-en">PRODUCTS</div>
      <h2 class="ss-h2">看板商品</h2>
      <span class="ss-h2-line"></span>

      <div class="ss-products">
        <?php foreach ($products as $p): ?>
        <article class="ss-product">
          <?php if ($p['image']): ?>
          <img class="ss-product-img" src="<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
          <?php else: ?>
          <div class="ss-product-img" style="display:flex;align-items:center;justify-content:center;color:var(--ss-muted);">圖片準備中</div>
          <?php endif; ?>
          <div class="ss-product-body">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <?php if ($p['desc']): ?>
            <p class="ss-product-desc"><?= htmlspecialchars(mb_strimwidth($p['desc'], 0, 40, '…')) ?></p>
            <?php endif; ?>
            <?php if ($p['price']): ?>
            <div class="ss-product-price"><?= htmlspecialchars($p['price']) ?></div>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($_productsMoreNote)): ?>
      <p class="ss-products-more"><?= htmlspecialchars($_productsMoreNote) ?></p>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 製作過程（深色底 + 引文）═══════ -->
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
          <?php if (!empty($g['caption'])): ?>
          <figcaption><?= htmlspecialchars($g['caption']) ?></figcaption>
          <?php endif; ?>
        </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 門市資訊 ═══════ -->
  <section class="ss-section">
    <div class="ss-container">
      <div class="ss-h2-en">VISIT</div>
      <h2 class="ss-h2">本店資訊</h2>
      <span class="ss-h2-line"></span>

      <div class="ss-visit">
        <dl class="ss-visit-info">
          <?php if ($address): ?>
          <dt>Address</dt>
          <dd><?= htmlspecialchars($address) ?></dd>
          <?php endif; ?>

          <?php if ($phone): ?>
          <dt>Phone</dt>
          <dd><a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a></dd>
          <?php endif; ?>

          <?php if (!empty($openingHours)): ?>
          <dt>Hours</dt>
          <dd>
            <?php
              // 簡化顯示：如果全週同時段，顯示「週一至週日 09:00–19:00」
              $_uniqHours = array_unique(array_column($openingHours, 'hours'));
              if (count($_uniqHours) === 1) {
                  echo '週一至週日　' . htmlspecialchars($_uniqHours[0]);
              } else {
                  foreach ($openingHours as $h) {
                      if (is_array($h)) echo htmlspecialchars(($h['day'] ?? '') . ' ' . ($h['hours'] ?? '')) . '<br>';
                  }
              }
            ?>
          </dd>
          <?php endif; ?>
        </dl>

        <?php if ($mapsEmbed): ?>
        <div class="ss-visit-map">
          <?= $mapsEmbed /* trusted from admin */ ?>
        </div>
        <?php else: ?>
        <div class="ss-visit-map" style="aspect-ratio:4/3;background:var(--ss-ivory);display:flex;align-items:center;justify-content:center;color:var(--ss-muted);font-size:.9rem;">
          地圖整理中
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ═══════ CTA ═══════ -->
  <section class="ss-cta">
    <h2>線上訂購・宅配全台</h2>
    <p>當日預訂、現場自取、線上宅配皆可。</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <?php if ($orderUrl): ?>
      <a class="ss-btn ss-btn--solid" href="<?= htmlspecialchars($orderUrl) ?>" target="_blank" rel="noopener">前往線上訂購 →</a>
      <?php endif; ?>
      <?php if ($phone): ?>
      <a class="ss-btn" href="tel:<?= htmlspecialchars($phone) ?>">電話訂購　<?= htmlspecialchars($phone) ?></a>
      <?php endif; ?>
    </div>
  </section>

  <!-- ═══════ Footer mark ═══════ -->
  <div class="ss-foot-mark">
    <div class="ss-foot-mark-brand"><?= htmlspecialchars($brandName) ?></div>
    <div class="ss-foot-mark-en">NAGASAKI CASTELLA · TAICHUNG</div>
  </div>

</div>
