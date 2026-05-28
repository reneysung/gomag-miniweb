<?php
/**
 * japan-minimal/render.php — 和風老舖視覺
 *
 * 接收 store.php scope 變數：
 *   $client / $blocks / $services / $testimonials / $similarStores
 *   $cityVariant / $cityVariantList / $heroStats / $heroHighlights / $useBlocks
 *   $hours / $googleReviews / $_breadcrumbCity / $_breadcrumbCitySlug
 *
 * 注意：layout_head.php 已執行，body 開頭已就緒；末段 store.php 會接 layout_foot.php
 */

declare(strict_types=1);

// ── 載入 japan-minimal 需要但 store.php 未填的變數 ─────
// store.php 用 renderStoreBlocks() 直接 echo，不填 $blocks；testimonials 預設過濾 demo
if (!isset($blocks) || !is_array($blocks)) {
    require_once __DIR__ . '/../../../includes/block_helpers.php';
    $blocks = function_exists('getStoreBlocks') ? getStoreBlocks((int)($client['id'] ?? 0)) : [];
}
// 重撈 testimonials（含 demo source，japan-minimal 模板自己負責標示）
if (isset($db) && !empty($client['id'])) {
    $_t = $db->prepare("SELECT * FROM testimonials WHERE client_id=? AND is_active=1 ORDER BY sort_order, id LIMIT 6");
    $_t->execute([(int)$client['id']]);
    $testimonials = $_t->fetchAll();
}

// ── 拉資料（用 store.php 已準備的變數，並補預設值）─────
$brandName = $client['brand_name'] ?? '';
$tagline   = $client['tagline'] ?? '';
$industry  = $client['industry'] ?? '';
$address   = $client['address'] ?? '';
$phone     = $client['phone'] ?? '';
$lineUrl   = $client['line_url'] ?? '';
$mapsEmbed = $client['google_maps_embed'] ?? '';
$heroImg   = !empty($client['hero_image_path']) ? BASE_URL . '/' . h($client['hero_image_path']) : '';
$logoImg   = !empty($client['logo_path']) ? BASE_URL . '/' . h($client['logo_path']) : '';
$aboutHtml = trim((string)($client['about_text'] ?? ''));

// 城市變體：判斷是否處於 city variant 頁
$isCityPage = !empty($cityVariant);
$cityLabel  = $isCityPage ? ($cityVariant['city_label'] ?? '') : '';

// 評價：取前 3 條（japan-minimal 故意精簡）
$topReviews = array_slice($testimonials ?? [], 0, 3);

// 拉 services／blocks 當「看板商品」
$products = [];
$productsTitle    = '看板商品';
$productsSubtitle = '每日限量現烤，預約取貨；商務、彌月、節慶禮盒皆有提供。';
$productsMoreNote = '';
if (!empty($blocks)) {
    foreach ($blocks as $b) {
        if (in_array($b['type'] ?? '', ['service', 'menu', 'pricing'], true)) {
            $data = is_array($b['data'] ?? null) ? $b['data'] : (json_decode((string)($b['data'] ?? ''), true) ?: []);
            if (!empty($data['title']))     $productsTitle    = $data['title'];
            if (!empty($data['subtitle']))  $productsSubtitle = $data['subtitle'];
            if (!empty($data['more_note'])) $productsMoreNote = $data['more_note'];
            foreach (($data['items'] ?? []) as $it) {
                $products[] = [
                    'name'  => $it['name'] ?? '',
                    'desc'  => $it['short_desc'] ?? ($it['desc'] ?? ''),
                    'price' => $it['price'] ?? '',
                    'image' => !empty($it['image']) ? BASE_URL . '/' . h($it['image']) : '',
                ];
                if (count($products) >= 4) break 2;
            }
        }
    }
}
if (empty($products) && !empty($services)) {
    foreach (array_slice($services, 0, 4) as $s) {
        $products[] = [
            'name'  => $s['name'] ?? '',
            'desc'  => $s['short_desc'] ?? '',
            'price' => $s['price_text'] ?? '',
            'image' => !empty($s['image_path']) ? BASE_URL . '/' . h($s['image_path']) : '',
        ];
    }
}

// 信賴點 — 客戶後台設定 hero_highlights 優先；沒設則整段不顯示（不用 hardcoded 通用文案）
$trustItems = [];
if (!empty($heroHighlights) && is_array($heroHighlights)) {
    foreach (array_slice($heroHighlights, 0, 5) as $hh) {
        if (is_string($hh)) {
            $trustItems[] = ['title' => $hh, 'desc' => ''];
        } elseif (is_array($hh)) {
            $trustItems[] = ['title' => $hh['title'] ?? '', 'desc' => $hh['desc'] ?? ''];
        }
    }
}

// 製程步驟：只在 blocks 有 'process' type 或客戶後台明確設定才顯示
// （不再用 hardcoded 通用文案，避免無中生有）
$processSteps = [];
if (!empty($blocks)) {
    foreach ($blocks as $b) {
        if (($b['type'] ?? '') === 'portfolio') {  // 借用 portfolio 結構展示製程
            $data = is_array($b['data'] ?? null) ? $b['data'] : (json_decode((string)($b['data'] ?? ''), true) ?: []);
            if (!empty($data['is_process'])) {
                foreach (($data['items'] ?? []) as $it) {
                    $processSteps[] = ['name' => $it['name'] ?? '', 'desc' => $it['desc'] ?? ''];
                }
            }
        }
    }
}

// breadcrumb city（從 store.php 算好的）
$bcCity     = $_breadcrumbCity ?? null;
$bcCitySlug = $_breadcrumbCitySlug ?? null;

// 城市變體 URL 產生器（與 store.php 一致）
function jmCityUrl(array $client, string $citySlug): string {
    return (IS_LOCAL || IS_STAGING)
        ? BASE_URL . '/store.php?sub=' . urlencode((string)$client['slug']) . '&city=' . urlencode($citySlug)
        : 'https://www.gomag.com.tw/store/' . urlencode((string)$client['slug']) . '/' . urlencode($citySlug);
}
?>

<div class="jm-page">

  <!-- ═══════ Breadcrumb ═══════ -->
  <nav class="jm-breadcrumb" aria-label="breadcrumb">
    <div class="jm-container">
      <a href="<?= BASE_URL ?>/">首頁</a>
      <span class="sep">›</span>
      <?php if ($bcCity && $bcCitySlug): ?>
        <a href="<?= BASE_URL ?>/city/<?= h($bcCitySlug) ?>"><?= h($bcCity) ?></a>
        <span class="sep">›</span>
      <?php endif; ?>
      <?php if ($isCityPage): ?>
        <a href="<?= BASE_URL ?>/store/<?= h($client['slug']) ?>"><?= h($brandName) ?></a>
        <span class="sep">›</span>
        <span class="here"><?= h($cityLabel) ?></span>
      <?php else: ?>
        <span class="here"><?= h($brandName) ?></span>
      <?php endif; ?>
    </div>
  </nav>

  <!-- ═══════ Hero ═══════ -->
  <section class="jm-hero" <?= $heroImg ? 'style="background-image:url(\'' . $heroImg . '\')"' : '' ?>>
    <div class="jm-hero-inner">
      <div class="jm-container">
        <?php if ($industry || $isCityPage): ?>
        <div class="jm-hero-badge">
          <?= $isCityPage ? h($cityLabel) . '・' : '' ?><?= h($industry ?: '日式長崎蛋糕') ?>
        </div>
        <?php endif; ?>

        <h1>
          <?= h($brandName) ?>
          <span class="en">NAGASAKI CASTELLA · SINCE 2020</span>
        </h1>

        <?php if ($tagline): ?>
        <p class="jm-hero-sub"><?= h($tagline) ?></p>
        <?php elseif ($aboutHtml): ?>
        <p class="jm-hero-sub"><?= h(mb_strimwidth(strip_tags($aboutHtml), 0, 100, '…')) ?></p>
        <?php endif; ?>

        <?php if (!empty($heroStats) && is_array($heroStats)): ?>
        <div class="jm-hero-meta">
          <?php foreach (array_slice($heroStats, 0, 4) as $stat): ?>
          <?php if (!is_array($stat)) continue; ?>
          <div class="jm-hero-meta-item">
            <strong><?= h($stat['value'] ?? '') ?></strong>
            <?= h($stat['label'] ?? '') ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ═══════ 直書印章列（客戶可自訂；若無預設用品牌名）═══════ -->
  <?php
    // 印章內容由 client.hero_highlights 簡短版提供；無則隱藏整段
    $_stampWords = [];
    if (!empty($heroHighlights) && is_array($heroHighlights)) {
        foreach (array_slice($heroHighlights, 0, 3) as $hh) {
            $w = is_array($hh) ? ($hh['title'] ?? '') : (string)$hh;
            if ($w !== '') $_stampWords[] = mb_substr($w, 0, 4);  // 印章限 4 字內
        }
    }
  ?>
  <?php if (!empty($_stampWords)): ?>
  <section class="jm-section jm-section--tight">
    <div class="jm-container">
      <div class="jm-stamp-row">
        <?php foreach ($_stampWords as $w): ?>
        <div class="jm-stamp"><?= h($w) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ Story 故事 ═══════ -->
  <?php if ($aboutHtml): ?>
  <section class="jm-section jm-section--cream">
    <div class="jm-container">
      <div class="jm-h2-en">OUR STORY</div>
      <h2 class="jm-h2">關於本舖</h2>
      <p class="jm-h2-sub">秉持日本長崎本舖工法，純手工製作每一塊蛋糕。</p>

      <div class="jm-story">
        <div class="jm-story-vertical">職人之心　始於初心</div>
        <div class="jm-story-body"><?= $aboutHtml /* 已是 HTML（後台儲存）*/ ?></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 城市深度內容（只在 city variant 頁面顯示）═══════ -->
  <?php if ($isCityPage && !empty($client['landing_extra_content'])): ?>
  <section class="jm-section jm-section--cream jm-city-deep">
    <div class="jm-container">
      <?= $client['landing_extra_content'] /* trusted HTML 已由 admin 後台儲存 */ ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 看板商品 ═══════ -->
  <?php if (!empty($products)): ?>
  <section class="jm-section">
    <div class="jm-container">
      <div class="jm-h2-en">SIGNATURE PRODUCTS</div>
      <h2 class="jm-h2"><?= h($productsTitle) ?></h2>
      <p class="jm-h2-sub"><?= h($productsSubtitle) ?></p>

      <div class="jm-products">
        <?php foreach ($products as $p): ?>
        <article class="jm-product">
          <?php if ($p['image']): ?>
          <img class="jm-product-img" src="<?= $p['image'] ?>" alt="<?= h($p['name']) ?>" loading="lazy">
          <?php else: ?>
          <div class="jm-product-img" style="display:flex;align-items:center;justify-content:center;color:var(--jm-muted);font-size:.85rem;">圖片準備中</div>
          <?php endif; ?>
          <div class="jm-product-body">
            <h3><?= h($p['name']) ?></h3>
            <?php if ($p['desc']): ?>
            <p class="jm-product-desc"><?= h(mb_strimwidth($p['desc'], 0, 60, '…')) ?></p>
            <?php endif; ?>
            <?php if ($p['price']): ?>
            <div class="jm-product-price"><?= h($p['price']) ?></div>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <?php if ($productsMoreNote): ?>
      <p style="text-align:center;margin-top:32px;font-size:.92rem;color:var(--jm-muted);letter-spacing:.05em;">
        <?= h($productsMoreNote) ?>
      </p>
      <?php endif; ?>

      <div style="text-align:center;margin-top:36px;">
        <a class="jm-btn jm-btn-gold" href="https://www.banshin.com.tw/product/" target="_blank" rel="noopener" style="background:var(--jm-red);color:#fff;border-color:var(--jm-red);">
          前往奧喜官網訂購 ↗
        </a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 製作過程 gallery（圖片來自客戶 process_gallery 設定）═══════ -->
  <?php
    // 從 client.process_gallery JSON 讀取（陣列 of {image, caption}）；無則隱藏整段
    $_processGallery = [];
    if (!empty($client['process_gallery'])) {
        $_pg = is_array($client['process_gallery']) ? $client['process_gallery'] : json_decode((string)$client['process_gallery'], true);
        if (is_array($_pg)) $_processGallery = $_pg;
    }
  ?>
  <?php if (!empty($_processGallery)): ?>
  <section class="jm-section jm-section--cream">
    <div class="jm-container">
      <div class="jm-h2-en">PRODUCTION PROCESS</div>
      <h2 class="jm-h2">蛋糕製作過程</h2>

      <p class="jm-process-quote">
        蛋糕每日手工製作，每一個步驟都須十分細心，堅持最好。
        <span class="jm-process-cite">— 奧喜本舖</span>
      </p>

      <div class="jm-process-gallery">
        <?php foreach (array_slice($_processGallery, 0, 4) as $pg): ?>
        <?php if (empty($pg['image'])) continue; ?>
        <figure>
          <img src="<?= BASE_URL ?>/<?= h($pg['image']) ?>" alt="<?= h($pg['caption'] ?? '製作過程') ?>" loading="lazy">
          <?php if (!empty($pg['caption'])): ?>
          <figcaption><?= h($pg['caption']) ?></figcaption>
          <?php endif; ?>
        </figure>
        <?php endforeach; ?>
      </div>

      <p style="text-align:center;margin-top:24px;font-size:.9rem;color:var(--jm-muted);">
        更多製作照片：<a href="https://www.banshin.com.tw/album/" target="_blank" rel="noopener" style="color:var(--jm-red);text-decoration:underline;text-decoration-style:dotted;">奧喜官網相片集錦 ↗</a>
      </p>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 製程步驟（只在客戶提供時顯示）═══════ -->
  <?php if (!empty($processSteps)): ?>
  <section class="jm-section jm-section--ink">
    <div class="jm-container">
      <div class="jm-h2-en" style="color:var(--jm-gold-hot)">CRAFTSMANSHIP</div>
      <h2 class="jm-h2" style="color:#fff">手作製程</h2>

      <div class="jm-process">
        <?php foreach ($processSteps as $i => $step): ?>
        <div class="jm-process-step">
          <div class="jm-process-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
          <h4 style="color:#fff"><?= h($step['name']) ?></h4>
          <?php if (!empty($step['desc'])): ?>
          <p style="color:rgba(248,239,232,.7)"><?= h($step['desc']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 信賴項目（只在客戶提供 hero_highlights 時顯示）═══════ -->
  <?php if (!empty($trustItems)): ?>
  <section class="jm-section">
    <div class="jm-container">
      <div class="jm-h2-en">WHY CHOOSE US</div>
      <h2 class="jm-h2">本舖堅持</h2>

      <div class="jm-trust">
        <?php foreach ($trustItems as $t): ?>
        <div class="jm-trust-item">
          <h4><?= h($t['title']) ?></h4>
          <?php if (!empty($t['desc'])): ?>
          <p><?= h($t['desc']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 客戶評價 ═══════ -->
  <?php if (!empty($topReviews)): ?>
  <section class="jm-section jm-section--cream">
    <div class="jm-container">
      <div class="jm-h2-en">CUSTOMER VOICE</div>
      <h2 class="jm-h2">顧客回饋</h2>

      <div class="jm-reviews">
        <?php foreach ($topReviews as $r): ?>
        <article class="jm-review">
          <p class="jm-review-text"><?= h(mb_strimwidth(strip_tags($r['content'] ?? ''), 0, 160, '…')) ?></p>
          <div class="jm-review-author">
            <strong><?= h($r['reviewer_name'] ?? '匿名顧客') ?></strong>
            <?php
              $_src = $r['source'] ?? '';
              $_url = $r['source_url'] ?? '';
              $_label = ['blog' => '部落客分享', 'google' => 'Google 評論', 'fb' => 'FB 評論', 'ig' => 'IG 分享'][$_src] ?? '';
            ?>
            <?php if (!empty($r['rating']) && (int)$r['rating'] >= 4): ?>
              ・<span style="color:var(--jm-gold)"><?= str_repeat('★', (int)$r['rating']) ?></span>
            <?php endif; ?>
            <?php if ($_label): ?>
              ・<?php if ($_url): ?><a href="<?= h($_url) ?>" target="_blank" rel="noopener nofollow" style="color:var(--jm-muted);text-decoration:underline;text-decoration-style:dotted;"><?= h($_label) ?> ↗</a><?php else: ?><?= h($_label) ?><?php endif; ?>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════ 門市資訊 + 多城市配送 ═══════ -->
  <section class="jm-section">
    <div class="jm-container">
      <div class="jm-h2-en">VISIT & DELIVERY</div>
      <h2 class="jm-h2">本店資訊・配送服務</h2>

      <div class="jm-locations">
        <!-- 本店 -->
        <div class="jm-store-card">
          <h3>北屯松竹路本店</h3>
          <dl>
            <?php if ($address): ?>
            <dt>地址</dt>
            <dd><?= h($address) ?></dd>
            <?php endif; ?>
            <?php if ($phone): ?>
            <dt>電話</dt>
            <dd><a href="tel:<?= h($phone) ?>"><?= h($phone) ?></a></dd>
            <?php endif; ?>
            <?php if (!empty($hours) && is_array($hours)): ?>
            <dt>營業時間</dt>
            <dd>
              <?php foreach ($hours as $h): ?>
                <?php if (is_array($h)) echo h(($h['day'] ?? '') . ' ' . ($h['hours'] ?? '')) . '<br>'; ?>
              <?php endforeach; ?>
            </dd>
            <?php endif; ?>
          </dl>
        </div>

        <!-- 配送城市 grid -->
        <div>
          <p style="font-size:1rem;color:var(--jm-ink-soft);line-height:1.8;margin:0 0 16px 0;">
            <strong style="color:var(--jm-red)">線上宅配</strong>與門市自取皆可。<br>
            點選您所在的城市，閱讀在地配送說明。
          </p>

          <div style="margin:16px 0 24px;">
            <a href="https://www.banshin.com.tw/product/" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:var(--jm-red);color:#fff;text-decoration:none;font-family:'Noto Serif TC',serif;font-weight:700;border-radius:4px;letter-spacing:.05em;transition:background .2s;">
              立即線上訂購 ↗
            </a>
            <span style="display:block;margin-top:8px;font-size:.82rem;color:var(--jm-muted);">
              連結至奧喜官網（banshin.com.tw）
            </span>
          </div>
          <?php if (!empty($cityVariantList)): ?>
          <div class="jm-cities">
            <?php foreach ($cityVariantList as $cv): ?>
            <?php
              $cvSlug = $cv['city_slug'] ?? '';
              $cvLabel = $cv['city_label'] ?? '';
              $isCurrent = $isCityPage && $cityVariant['city_slug'] === $cvSlug;
              $cls = 'jm-city-pill' . ($isCurrent ? ' jm-city-pill--current' : '');
            ?>
            <a class="<?= $cls ?>" href="<?= jmCityUrl($client, $cvSlug) ?>">
              <?= h($cvLabel) ?>
              <span class="en"><?= h(strtoupper($cvSlug)) ?></span>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($mapsEmbed): ?>
      <div style="margin-top:48px;border:1px solid var(--jm-line);overflow:hidden;border-radius:4px;">
        <?= $mapsEmbed /* trusted from admin */ ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ═══════ CTA ═══════ -->
  <section class="jm-cta">
    <div class="jm-cta-inner">
      <h2>歡迎來電或到店選購</h2>
      <p>線上宅配與門市自取皆可，詳細品項與最新資訊請參考<a href="https://www.banshin.com.tw" target="_blank" rel="noopener" style="color:var(--jm-gold-hot);text-decoration:underline;">奧喜官網</a>。</p>
      <div class="jm-cta-buttons">
        <?php if ($phone): ?>
        <a class="jm-btn jm-btn-gold" href="tel:<?= h($phone) ?>">📞 <?= h($phone) ?></a>
        <?php endif; ?>
        <?php if ($lineUrl): ?>
        <a class="jm-btn jm-btn-ghost" href="<?= h($lineUrl) ?>" target="_blank" rel="noopener">💬 LINE 諮詢</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ═══════ 收尾 brand mark ═══════ -->
  <div class="jm-foot-mark">
    <div class="jm-foot-mark-cn"><?= h($brandName) ?></div>
    <div class="jm-foot-mark-en">NAGASAKI CASTELLA · SINCE 2020</div>
  </div>

</div>
