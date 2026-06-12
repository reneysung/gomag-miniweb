<?php
// site/service_detail.php — Mini-site 服務詳細頁 /services/{slug}
// 旭森風格：每服務獨立 URL + SEO title/desc 含關鍵字 + Service schema
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

header('Cache-Control: public, max-age=300, must-revalidate');

$sub = getSubdomain();
if (!$sub) { http_response_code(404); die('找不到網站'); }

$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在或已停用'); }
if (empty($site['client']['has_minisite'])) { http_response_code(404); die('未啟用小官網'); }

// 拿 slug — 從 .htaccess routing 過來會是 $_GET['svc_slug']，或從 query 直接傳
$svcSlug = trim($_GET['svc_slug'] ?? $_GET['s'] ?? '');
if (!$svcSlug) { http_response_code(404); die('未指定服務'); }

// 從 loadSiteData 撈出的 services array 找
$service = null;
foreach ($site['services'] as $s) {
    if (!empty($s['slug']) && $s['slug'] === $svcSlug) { $service = $s; break; }
}
if (!$service) { http_response_code(404); die('找不到該服務'); }

$slug    = $sub;
$pageKey = 'service_detail';
$client  = $site['client'];
$social  = $site['social'];
$phone   = $client['phone'] ?? '';

// LINE URL
$lineUrl = '';
if (!empty($social['line_url']) && filter_var($social['line_url'], FILTER_VALIDATE_URL)) {
    $lineUrl = $social['line_url'];
} elseif (!empty($social['line_id'])) {
    $rawId = ltrim(trim($social['line_id']), '@');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $rawId)) $lineUrl = 'https://line.me/R/ti/p/@' . $rawId;
}

// 城市（從 address_region 優先，fallback 從 address 抽）
$city = $client['address_region'] ?? '';
if (!$city && !empty($client['address'])) {
    if (preg_match('/^(臺?[北中南東]市|新北市|桃園市|基隆市|新竹[市縣]|苗栗縣|彰化縣|南投縣|雲林縣|嘉義[市縣]|屏東縣|宜蘭縣|花蓮縣|臺?東縣|澎湖縣|金門縣|連江縣)/u', $client['address'], $m)) {
        $city = str_replace('臺', '台', $m[1]);
    }
}

// ── SEO meta ──
//   title 模板：{服務名}-{城市}｜{品牌名}
//   meta desc：服務短描述（fallback：service name + city + tagline）
// 去掉「市/縣」尾綴：「台中市」→「台中」，貼合搜尋意圖（grep「台中裝潢細清」精準命中）
$city = preg_replace('/(市|縣)$/u', '', (string)$city);
$pageSuffix = $city ? "-{$city}" : '';
// city 放前面：「台中裝潢細清」比「裝潢細清-台中市」更貼搜尋意圖（避免「裝潢細清」單詞優先匹配）
$metaTitle  = "{$city}{$service['name']}｜{$client['brand_name']}";
$metaDesc   = !empty($service['short_desc'])
    ? mb_strimwidth(strip_tags($service['short_desc']), 0, 150, '…')
    : ((!empty($service['full_desc'])
        ? mb_strimwidth(strip_tags($service['full_desc']), 0, 150, '…')
        : "{$city}{$service['name']}：{$client['brand_name']}{$pageSuffix}專業服務。" . ($client['tagline'] ?? '')));

// canonical
if (IS_LOCAL || IS_STAGING) {
    $canonicalUrl = BASE_URL . '/site/service_detail.php?sub=' . urlencode($sub) . '&svc_slug=' . urlencode($svcSlug);
} else {
    $canonicalUrl = 'https://' . $sub . '.' . MINISITE_DOMAIN . '/services/' . urlencode($svcSlug);
}

// og image
$ogImage = !empty($service['image_path']) ? BASE_URL . '/' . $service['image_path']
        : (!empty($client['hero_image_path']) ? BASE_URL . '/' . $client['hero_image_path'] : '');

// 相關服務（其他 3 個服務，排除自己）
$relatedServices = array_filter($site['services'], fn($s) => ($s['slug'] ?? '') !== $svcSlug);
$relatedServices = array_slice($relatedServices, 0, 3);

// 引 head（layout_head.php 用 $metaTitle / $metaDesc / $canonicalUrl / $ogImage 變數）
// 為了相容 layout_head.php 的 getSeo() 邏輯，這裡先塞 $seo array
$seo = [
    'meta_title' => $metaTitle,
    'meta_desc'  => $metaDesc,
    'og_image'   => $ogImage,
    'og_title'   => $metaTitle,
];
$site['seo']['service_detail'] = $seo;
$site['_current_service'] = $service;  // 給 outputJsonLd() 用

require __DIR__ . '/layout_head.php';
?>

<!-- Hero -->
<section style="background:linear-gradient(135deg,var(--g-ink) 0%,rgba(var(--g-ink-rgb),.85) 100%);color:#fff;padding:64px 0 56px;position:relative;overflow:hidden">
  <?php if ($ogImage): ?>
    <div style="position:absolute;inset:0;background:url('<?= h($ogImage) ?>') center/cover;opacity:.18;"></div>
  <?php endif; ?>
  <div class="container" style="position:relative">
    <nav class="breadcrumb" style="color:rgba(255,255,255,.7);margin-bottom:16px;font-size:.85rem">
      <a href="<?= siteUrl($sub) ?>" style="color:#fff">首頁</a>
      <span> / </span>
      <a href="<?= siteUrl($sub, 'services') ?>" style="color:#fff">服務項目</a>
      <span> / </span>
      <span style="opacity:.85"><?= h($service['name']) ?></span>
    </nav>
    <h1 style="font-size:clamp(1.8rem,4vw,2.6rem);font-weight:900;line-height:1.3;margin-bottom:12px">
      <?= h($service['name']) ?><?php if ($city): ?><span style="font-weight:400;opacity:.7;margin-left:8px;font-size:.6em"><?= h($city) ?></span><?php endif; ?>
    </h1>
    <?php if (!empty($service['short_desc'])): ?>
      <p style="font-size:1.1rem;opacity:.85;max-width:680px;line-height:1.7"><?= h($service['short_desc']) ?></p>
    <?php endif; ?>
    <?php if (!empty($service['price_text'])): ?>
      <div style="display:inline-block;margin-top:24px;padding:10px 20px;background:rgba(255,255,255,.15);border-radius:30px;font-size:.95rem;font-weight:600">
        💰 <?= h($service['price_text']) ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- 主內容 -->
<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:48px;align-items:start">
      <div>

        <?php if (!empty($service['image_path'])): ?>
        <div style="border-radius:12px;overflow:hidden;margin-bottom:32px;box-shadow:0 8px 30px rgba(0,0,0,.08)">
          <img src="<?= BASE_URL . '/' . h($service['image_path']) ?>" alt="<?= h($service['name']) ?>" style="width:100%;height:auto;display:block">
        </div>
        <?php endif; ?>

        <?php if (!empty($service['full_desc'])): ?>
        <div class="rich-content" style="font-size:1.02rem;line-height:1.85;color:var(--g-ink-soft)">
          <?= $service['full_desc'] ?>
        </div>
        <?php else: ?>
        <p style="font-size:1.02rem;line-height:1.85;color:var(--g-ink-soft)">
          <?= h($service['short_desc'] ?? "歡迎來電諮詢 {$client['brand_name']} 的{$service['name']}服務細節，我們提供{$city}地區專業到府服務。") ?>
        </p>
        <?php endif; ?>

        <!-- FAQ -->
        <?php if (!empty($service['faqs'])): ?>
        <section id="faq" style="margin-top:48px">
          <h2 style="font-size:1.5rem;font-weight:900;margin-bottom:24px;color:var(--g-ink)">常見問題</h2>
          <div style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($service['faqs'] as $faq): ?>
            <details style="border:1px solid rgba(var(--g-ink-rgb),.1);border-radius:10px;padding:16px 20px;background:#fff">
              <summary style="font-weight:700;cursor:pointer;color:var(--g-ink);font-size:1rem">❓ <?= h($faq['question']) ?></summary>
              <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(var(--g-ink-rgb),.08);color:var(--g-ink-soft);line-height:1.75">
                <?= nl2br(h($faq['answer'])) ?>
              </div>
            </details>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <!-- 相關服務 -->
        <?php if ($relatedServices): ?>
        <section style="margin-top:64px">
          <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:20px;color:var(--g-ink)">其他服務</h2>
          <div class="grid-3" style="gap:16px">
            <?php foreach ($relatedServices as $rs): ?>
              <a href="<?= siteUrl($sub, 'services') ?>/<?= h(rawurlencode($rs['slug'])) ?>" style="display:block;padding:18px;border:1px solid rgba(var(--g-ink-rgb),.12);border-radius:10px;background:#fff;transition:all .2s;color:var(--g-ink);text-decoration:none">
                <div style="font-weight:700;font-size:1rem;margin-bottom:6px"><?= h($rs['name']) ?></div>
                <?php if (!empty($rs['short_desc'])): ?>
                  <div style="font-size:.85rem;color:#888;line-height:1.5"><?= h(mb_strimwidth($rs['short_desc'], 0, 60, '…')) ?></div>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <!-- Sticky CTA Sidebar -->
      <aside style="position:sticky;top:90px;background:#fff;border:1px solid rgba(var(--g-ink-rgb),.1);border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.05)">
        <h3 style="font-size:1.1rem;font-weight:800;color:var(--g-ink);margin-bottom:6px">立即諮詢</h3>
        <p style="font-size:.85rem;color:#666;margin-bottom:18px"><?= h($city) ?>{$service['name']}專業服務，歡迎來電。</p>
        <?php if ($phone): ?>
          <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" style="display:block;width:100%;padding:14px;background:var(--g-ink);color:#fff;border-radius:50px;text-align:center;font-weight:700;text-decoration:none;margin-bottom:10px">📞 <?= h($phone) ?></a>
        <?php endif; ?>
        <?php if ($lineUrl): ?>
          <a href="<?= h($lineUrl) ?>" target="_blank" style="display:block;width:100%;padding:14px;background:#06c755;color:#fff;border-radius:50px;text-align:center;font-weight:700;text-decoration:none">💬 LINE 諮詢</a>
        <?php endif; ?>
        <?php if (!empty($client['address'])): ?>
          <div style="margin-top:18px;padding-top:18px;border-top:1px solid #eee;font-size:.85rem;color:#666;line-height:1.6">
            📍 <?= h($client['address']) ?>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/layout_foot.php'; ?>
