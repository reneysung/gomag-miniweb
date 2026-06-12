<?php
/**
 * templates/minisite/gentleman-navy/home.php
 * 紳藍質感首頁 — 由 site/index.php 在 layout_head 後 require
 *
 * 收得到變數：$site, $client, $slug, $sub, $social, $phone, $lineUrl,
 *            $heroStats, $aboutTags, $aboutStats
 * 內容紀律：不編造 — 全部吃 DB 既有資料（services / hero_stats / about_text / owner）
 * 唯四階段施工流程為該客戶行銷頁核可文案（同 services 表 full_desc 來源）
 */

$services     = $site['services'] ?? [];
$cases        = $site['cases'] ?? [];
$testimonials = $site['testimonials'] ?? [];
$phone        = $phone ?? ($client['phone'] ?? '');
$telHref      = 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
$fbUrl        = $social['fb_page_url'] ?? '';
$igUrl        = $social['instagram_url'] ?? '';
$heroImg      = !empty($client['hero_image_path']) ? BASE_URL . '/' . h($client['hero_image_path']) : '';

// hero 服務 checklist（最多 5 個）
$heroChecks = array_slice($services, 0, 5);

// 數據（hero_stats 已由 index.php 轉成 [[value,label],...]）
$gnStats = array_slice($heroStats ?? [], 0, 4);

// 是否冷氣/空調業（流程區只在此業種顯示 — 文案出自冷淨億點核可行銷頁）
$gnIsAircon = (bool)preg_match('/冷氣|空調/u', $client['industry'] ?? '');
?>
<div class="gn">

<!-- ══ Hero：滿版照片 + 半透明白卡 ══ -->
<section class="gn-hero">
  <?php if ($heroImg): ?><div class="gn-hero-bg" style="background-image:url('<?= $heroImg ?>')"></div><?php endif; ?>
  <div class="gn-hero-overlay"></div>
  <div class="gn-wrap" style="width:100%">
    <div class="gn-hero-card">
      <div class="gn-hero-tag"><?= h($client['industry'] ?? '專業服務') ?>
        <?php if (preg_match('/^(.{2,3}[市縣])/u', $client['address'] ?? '', $m)): ?>・<?= h($m[1]) ?><?php endif; ?>
      </div>
      <h1 class="gn-hero-title"><?= h($client['brand_name']) ?></h1>
      <?php if (!empty($client['tagline'])): ?>
      <p class="gn-hero-sub"><?= h($client['tagline']) ?></p>
      <?php endif; ?>
      <div class="gn-hero-divider"></div>
      <?php if ($heroChecks): ?>
      <div class="gn-hero-checks">
        <?php foreach ($heroChecks as $svc): ?>
        <a class="gn-hero-check" href="<?= siteUrl($sub, 'services') ?>#svc-<?= (int)$svc['id'] ?>">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10.5" stroke="currentColor" stroke-width="1.4"/><path d="M7.5 12.2l3 3 6-6.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= h($svc['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="gn-hero-actions">
        <?php if ($phone): ?><a class="gn-btn gn-btn-solid" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即預約</a><?php endif; ?>
        <?php if ($fbUrl): ?><a class="gn-btn gn-btn-ghost" href="<?= h($fbUrl) ?>" target="_blank" rel="noopener">看實際案例</a><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ══ 數據帶 ══ -->
<?php if ($gnStats): ?>
<section class="gn-stats">
  <div class="gn-wrap">
    <div class="gn-stats-grid">
      <?php foreach ($gnStats as $s): ?>
      <div class="gn-stat">
        <div class="gn-stat-num"><?= h($s[0] ?? '') ?></div>
        <div class="gn-stat-label"><?= h($s[1] ?? '') ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ About：深藍分屏 ══ -->
<section class="gn-about">
  <div class="gn-about-panel">
    <div class="gn-eyebrow">About Us</div>
    <h2 class="gn-h2">關於<?= h($client['brand_name']) ?></h2>
    <div class="gn-about-text">
      <?php
      $_about = $client['about_text'] ?? '';
      if ($_about !== '' && strip_tags($_about) !== $_about) echo $_about;
      else echo nl2br(h($_about));
      ?>
    </div>
    <?php if (!empty($aboutTags)): ?>
    <div class="gn-about-tags">
      <?php foreach ($aboutTags as $t): ?>
      <span class="gn-about-tag"><?= h(preg_replace('/^[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\s]+/u', '', $t)) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="gn-about-visual" <?php if ($heroImg): ?>style="background-image:url('<?= $heroImg ?>')"<?php endif; ?>></div>
</section>

<!-- ══ 服務 ══ -->
<?php if ($services): ?>
<section class="gn-section gn-section-mist">
  <div class="gn-wrap">
    <div class="gn-eyebrow gn-center" style="justify-content:center">Our Services</div>
    <h2 class="gn-h2" style="text-align:center">服務項目</h2>
    <p class="gn-sub" style="text-align:center"><?= $gnIsAircon ? '到府施工、標準作業流程，清洗前後數據看得見' : '每一項服務，都以同樣的標準完成' ?></p>
    <div class="gn-svc-grid">
      <?php foreach ($services as $i => $svc): ?>
      <a class="gn-svc-card" href="<?= siteUrl($sub, 'services') ?>#svc-<?= (int)$svc['id'] ?>">
        <div class="gn-svc-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
        <h3 class="gn-svc-name"><?= h($svc['name']) ?></h3>
        <p class="gn-svc-desc"><?= h($svc['short_desc'] ?? '') ?></p>
        <span class="gn-svc-more">了解詳情 →</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($gnIsAircon): ?>
<!-- ══ 到府施工四階段（冷淨億點行銷頁核可文案）══ -->
<section class="gn-section gn-section-navy">
  <div class="gn-wrap">
    <div class="gn-eyebrow gn-center" style="justify-content:center">Standard Process</div>
    <h2 class="gn-h2" style="text-align:center">每一台冷氣，都照同一套標準洗</h2>
    <p class="gn-sub" style="text-align:center">不靠師傅當天心情，照固定的四個階段一步步來</p>
    <div class="gn-steps">
      <div class="gn-step">
        <div class="gn-step-num">STEP 01</div>
        <h3 class="gn-step-name">前置作業</h3>
        <ul><li>室內機檢測</li><li>環境保護</li><li>清洗前測溫、測風</li></ul>
      </div>
      <div class="gn-step">
        <div class="gn-step-num">STEP 02</div>
        <h3 class="gn-step-name">開始清洗</h3>
        <ul><li>全機拆卸</li><li>噴專用無毒藥水</li><li>高壓水槍清洗</li><li>裝機復原</li></ul>
      </div>
      <div class="gn-step">
        <div class="gn-step-num">STEP 03</div>
        <h3 class="gn-step-name">完工檢測</h3>
        <ul><li>清洗後測溫、測風</li><li>環境復原</li><li>當面點交</li></ul>
      </div>
      <div class="gn-step">
        <div class="gn-step-num">STEP 04</div>
        <h3 class="gn-step-name">服務報告</h3>
        <ul><li>2–3 工作天內</li><li>清洗前後照片</li><li>溫度、風速整理成報告</li></ul>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 精選案例（有才顯示）══ -->
<?php $gnFeatured = array_slice(array_values(array_filter($cases, fn($c) => !empty($c['is_featured']))), 0, 3); ?>
<?php if ($gnFeatured): ?>
<section class="gn-section">
  <div class="gn-wrap">
    <div class="gn-eyebrow gn-center" style="justify-content:center">Case Studies</div>
    <h2 class="gn-h2" style="text-align:center">施工案例</h2>
    <p class="gn-sub" style="text-align:center">真實 Before／After，效果看得見</p>
    <div class="gn-case-grid">
      <?php foreach ($gnFeatured as $c): ?>
      <a class="gn-case-card" href="<?= siteUrl($sub, 'cases') ?>" style="text-decoration:none;color:inherit">
        <?php $b = !empty($c['before_image']) ? BASE_URL . '/' . h($c['before_image']) : '';
              $a = !empty($c['after_image'])  ? BASE_URL . '/' . h($c['after_image'])  : ''; ?>
        <?php if ($b && $a): ?>
        <div class="gn-case-imgs">
          <img class="gn-before" src="<?= $b ?>" alt="<?= h($c['title']) ?>・施作前">
          <img class="gn-after" src="<?= $a ?>" alt="<?= h($c['title']) ?>・施作後">
          <div class="gn-case-split"></div>
          <span class="gn-case-tag gn-tb">BEFORE</span><span class="gn-case-tag gn-ta">AFTER</span>
        </div>
        <?php elseif ($a ?: $b): ?>
        <img class="gn-case-single" src="<?= $a ?: $b ?>" alt="<?= h($c['title']) ?>">
        <?php endif; ?>
        <div class="gn-case-body">
          <h3 class="gn-case-title"><?= h($c['title']) ?></h3>
          <div class="gn-case-meta"><?= h(trim(($c['svc_name'] ?? '') . ($c['location'] ? '　' . $c['location'] : ''))) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 評價（有才顯示）══ -->
<?php if ($testimonials): ?>
<section class="gn-section gn-section-mist">
  <div class="gn-wrap">
    <div class="gn-eyebrow gn-center" style="justify-content:center">Testimonials</div>
    <h2 class="gn-h2" style="text-align:center">客戶好評</h2>
    <div class="gn-review-grid">
      <?php foreach (array_slice($testimonials, 0, 3) as $t): ?>
      <div class="gn-review-card">
        <div class="gn-review-stars">★★★★★</div>
        <p class="gn-review-text">「<?= h(mb_strimwidth($t['content'], 0, 110, '…')) ?>」</p>
        <div class="gn-review-name">—　<strong><?= h($t['reviewer_name']) ?></strong></div>
        <?php if (!empty($t['source_url'])): ?>
        <a class="gn-review-link" href="<?= h($t['source_url']) ?>" target="_blank" rel="noopener nofollow ugc">閱讀全文 →</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:44px">
      <a class="gn-btn gn-btn-ghost" href="<?= siteUrl($sub, 'testimonials') ?>">查看全部評價</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 老闆引言（有 owner 才顯示）══ -->
<?php if (!empty($client['owner_name']) && !empty($client['owner_intro'])): ?>
<section class="gn-section">
  <div class="gn-wrap">
    <div class="gn-owner">
      <div class="gn-owner-mark">“</div>
      <p class="gn-owner-quote"><?= nl2br(h($client['owner_intro'])) ?></p>
      <div class="gn-owner-name">負責人　<strong><?= h($client['owner_name']) ?></strong></div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 聯絡 ══ -->
<section class="gn-section gn-section-navy" id="contact">
  <div class="gn-wrap">
    <div class="gn-eyebrow gn-center" style="justify-content:center">Contact</div>
    <h2 class="gn-h2" style="text-align:center">聯絡我們</h2>
    <p class="gn-sub" style="text-align:center"><?= $gnIsAircon ? '來電或私訊我們，拍照即可快速估價' : '歡迎來電洽詢，專人為您服務' ?></p>
    <div class="gn-contact-grid">
      <?php if ($phone): ?>
      <div class="gn-contact-item">
        <div class="gn-contact-label">Phone</div>
        <div class="gn-contact-value"><a href="<?= h($telHref) ?>"><?= h($phone) ?></a></div>
        <div class="gn-contact-note">電話預約</div>
      </div>
      <?php endif; ?>
      <?php if (!empty($client['email'])): ?>
      <div class="gn-contact-item">
        <div class="gn-contact-label">Email</div>
        <div class="gn-contact-value" style="font-size:1rem"><a href="mailto:<?= h($client['email']) ?>"><?= h($client['email']) ?></a></div>
        <div class="gn-contact-note">信件聯繫</div>
      </div>
      <?php endif; ?>
      <?php if ($fbUrl): ?>
      <div class="gn-contact-item">
        <div class="gn-contact-label">Facebook</div>
        <div class="gn-contact-value" style="font-size:1rem"><a href="<?= h($fbUrl) ?>" target="_blank" rel="noopener"><?= h($client['brand_name']) ?></a></div>
        <div class="gn-contact-note">私訊預約・看最新案例</div>
      </div>
      <?php endif; ?>
      <?php if ($igUrl): ?>
      <div class="gn-contact-item">
        <div class="gn-contact-label">Instagram</div>
        <div class="gn-contact-value" style="font-size:1rem"><a href="<?= h($igUrl) ?>" target="_blank" rel="noopener">@<?= h(trim(parse_url($igUrl, PHP_URL_PATH) ?: '', '/')) ?></a></div>
        <div class="gn-contact-note">追蹤最新動態</div>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($phone): ?>
    <div style="text-align:center;margin-top:48px">
      <a class="gn-btn gn-btn-gold" href="<?= h($telHref) ?>"><?= h($phone) ?>　立即來電</a>
    </div>
    <?php endif; ?>
  </div>
</section>

</div>
