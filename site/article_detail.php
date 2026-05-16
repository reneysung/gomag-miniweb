<?php
// site/article_detail.php — Mini-site 專欄文章 /column/{slug}
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

header('Cache-Control: public, max-age=300, must-revalidate');

$sub = getSubdomain();
if (!$sub) { http_response_code(404); die('找不到網站'); }

$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在或已停用'); }
if (empty($site['client']['has_minisite'])) { http_response_code(404); die('未啟用小官網'); }

$artSlug = trim($_GET['slug'] ?? $_GET['a'] ?? '');
if (!$artSlug) { http_response_code(404); die('未指定文章'); }

$db = getDB();
$stmt = $db->prepare("SELECT * FROM articles WHERE client_id=? AND slug=? AND is_active=1 LIMIT 1");
$stmt->execute([(int)$site['client']['id'], $artSlug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$article) { http_response_code(404); die('找不到該文章'); }

$slug    = $sub;
$pageKey = 'article_detail';
$client  = $site['client'];
$social  = $site['social'];
$phone   = $client['phone'] ?? '';

$lineUrl = '';
if (!empty($social['line_url']) && filter_var($social['line_url'], FILTER_VALIDATE_URL)) {
    $lineUrl = $social['line_url'];
} elseif (!empty($social['line_id'])) {
    $rawId = ltrim(trim($social['line_id']), '@');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $rawId)) $lineUrl = 'https://line.me/R/ti/p/@' . $rawId;
}

// SEO
$metaTitle = "{$article['title']}｜{$client['brand_name']}專欄";
$metaDesc  = !empty($article['summary'])
    ? mb_strimwidth(strip_tags($article['summary']), 0, 150, '…')
    : (!empty($article['content'])
        ? mb_strimwidth(strip_tags($article['content']), 0, 150, '…')
        : "{$article['title']} — {$client['brand_name']}專欄文章");

if (IS_LOCAL || IS_STAGING) {
    $canonicalUrl = BASE_URL . '/site/article_detail.php?sub=' . urlencode($sub) . '&slug=' . urlencode($artSlug);
} else {
    $canonicalUrl = 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column/' . urlencode($artSlug);
}

$ogImage = !empty($article['cover_image']) ? BASE_URL . '/' . $article['cover_image']
        : (!empty($client['hero_image_path']) ? BASE_URL . '/' . $client['hero_image_path'] : '');

// 相關文章
$relStmt = $db->prepare("SELECT slug, title, cover_image FROM articles WHERE client_id=? AND id!=? AND is_active=1 ORDER BY COALESCE(published_at, created_at) DESC LIMIT 3");
$relStmt->execute([(int)$client['id'], (int)$article['id']]);
$relatedArticles = $relStmt->fetchAll(PDO::FETCH_ASSOC);

$publishDate = $article['published_at'] ?: $article['created_at'];

$seo = [
    'meta_title' => $metaTitle, 'meta_desc' => $metaDesc, 'og_image' => $ogImage, 'og_title' => $metaTitle,
];
$site['seo']['article_detail'] = $seo;
$site['_current_article'] = $article;  // 給 outputJsonLd() 用

require __DIR__ . '/layout_head.php';
?>

<!-- Hero -->
<section style="background:linear-gradient(135deg,var(--g-ink) 0%,rgba(var(--g-ink-rgb),.85) 100%);color:#fff;padding:64px 0 56px;position:relative;overflow:hidden">
  <?php if ($ogImage): ?>
    <div style="position:absolute;inset:0;background:url('<?= h($ogImage) ?>') center/cover;opacity:.22;"></div>
  <?php endif; ?>
  <div class="container" style="position:relative;max-width:760px;text-align:center">
    <nav class="breadcrumb" style="color:rgba(255,255,255,.7);margin-bottom:14px;font-size:.85rem;justify-content:center;display:flex;flex-wrap:wrap">
      <a href="<?= siteUrl($sub) ?>" style="color:#fff">首頁</a><span style="margin:0 6px"> / </span>
      <a href="<?= (IS_LOCAL || IS_STAGING) ? BASE_URL . '/site/articles.php?sub=' . urlencode($sub) : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column' ?>" style="color:#fff">專欄</a>
      <span style="margin:0 6px"> / </span><span style="opacity:.85"><?= h($article['title']) ?></span>
    </nav>
    <?php if ($article['category']): ?>
      <div style="display:inline-block;font-size:.72rem;font-weight:700;letter-spacing:.12em;color:rgba(255,255,255,.9);background:rgba(255,255,255,.15);padding:5px 12px;border-radius:20px;margin-bottom:14px"><?= h(strtoupper($article['category'])) ?></div>
    <?php endif; ?>
    <h1 style="font-size:clamp(1.7rem,4vw,2.4rem);font-weight:900;line-height:1.4;margin-bottom:12px"><?= h($article['title']) ?></h1>
    <div style="font-size:.85rem;opacity:.75">
      <?= h($client['brand_name']) ?> · <?= date('Y 年 m 月 d 日', strtotime($publishDate)) ?>
    </div>
  </div>
</section>

<!-- 內容 -->
<section class="section">
  <div class="container" style="max-width:760px">
    <?php if (!empty($article['summary'])): ?>
      <p style="font-size:1.05rem;line-height:1.85;color:#555;border-left:3px solid var(--g-accent);padding-left:18px;margin-bottom:36px;font-style:italic"><?= h($article['summary']) ?></p>
    <?php endif; ?>

    <?php if (!empty($article['content'])): ?>
      <article class="rich-content" style="font-size:1.02rem;line-height:1.95;color:var(--g-ink-soft)">
        <?= $article['content'] ?>
      </article>
    <?php endif; ?>

    <!-- CTA -->
    <div style="margin-top:48px;padding:28px;background:var(--g-bg-alt);border-radius:14px;text-align:center">
      <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:6px;color:var(--g-ink)">看完文章想諮詢？</h3>
      <p style="color:#777;font-size:.9rem;margin-bottom:16px">歡迎來電或 LINE 找我們聊聊</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <?php if ($phone): ?>
          <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn btn-primary">📞 <?= h($phone) ?></a>
        <?php endif; ?>
        <?php if ($lineUrl): ?>
          <a href="<?= h($lineUrl) ?>" target="_blank" class="btn btn-accent">💬 LINE 諮詢</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- 相關文章 -->
<?php if ($relatedArticles): ?>
<section class="section" style="background:var(--g-bg-alt);padding:48px 0 64px">
  <div class="container" style="max-width:960px">
    <h2 style="font-size:1.2rem;font-weight:800;color:var(--g-ink);margin-bottom:20px;text-align:center">繼續閱讀</h2>
    <div class="grid-3" style="gap:16px">
      <?php foreach ($relatedArticles as $ra):
        $rurl = (IS_LOCAL || IS_STAGING)
            ? BASE_URL . '/site/article_detail.php?sub=' . urlencode($sub) . '&slug=' . urlencode($ra['slug'])
            : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column/' . rawurlencode($ra['slug']);
        $rcov = $ra['cover_image'] ? BASE_URL . '/' . h($ra['cover_image']) : '';
      ?>
        <a href="<?= h($rurl) ?>" style="display:block;background:#fff;border-radius:10px;overflow:hidden;color:var(--g-ink);text-decoration:none;box-shadow:0 2px 12px rgba(0,0,0,.04)">
          <?php if ($rcov): ?><div style="aspect-ratio:16/10;background:url('<?= h($rcov) ?>') center/cover"></div><?php endif; ?>
          <div style="padding:14px 18px"><div style="font-size:.92rem;font-weight:700;line-height:1.45"><?= h($ra['title']) ?></div></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/layout_foot.php'; ?>
