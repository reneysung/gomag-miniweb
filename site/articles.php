<?php
// site/articles.php — Mini-site 專欄列表頁 /column
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

header('Cache-Control: public, max-age=300, must-revalidate');

$sub = getSubdomain();
if (!$sub) { http_response_code(404); die('找不到網站'); }

$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在或已停用'); }
if (empty($site['client']['has_minisite'])) { http_response_code(404); die('未啟用小官網'); }

// 模板系統 sub-page 路由（articles 通常不放在 pages，會 302 回首頁）
require_once __DIR__ . '/../includes/minisite_template_loader.php';
$_mr = minisiteSubpageRoute('articles', $sub, $site['client']);
if (!empty($_mr['redirect'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . $_mr['redirect'], true, 302);
    exit;
}
if (!empty($_mr['render'])) {
    require __DIR__ . '/layout_head.php';
    require $_mr['render'];
    require __DIR__ . '/layout_foot.php';
    exit;
}

$slug    = $sub;
$pageKey = 'articles';
$client  = $site['client'];
$social  = $site['social'];

// 撈該 client 所有 articles
$db = getDB();
$stmt = $db->prepare("SELECT id, slug, title, summary, cover_image, category, published_at, created_at
                      FROM articles WHERE client_id=? AND is_active=1
                      ORDER BY COALESCE(published_at, created_at) DESC, sort_order ASC, id DESC");
$stmt->execute([(int)$client['id']]);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$metaTitle = "專欄文章｜{$client['brand_name']}";
$metaDesc  = "{$client['brand_name']}專業知識文章、案例分享、選購指南。";

if (IS_LOCAL || IS_STAGING) {
    $canonicalUrl = BASE_URL . '/site/articles.php?sub=' . urlencode($sub);
} else {
    $canonicalUrl = 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column';
}

$seo = ['meta_title' => $metaTitle, 'meta_desc' => $metaDesc];
$site['seo']['articles'] = $seo;

require __DIR__ . '/layout_head.php';
?>

<section style="background:linear-gradient(135deg,var(--g-ink) 0%,rgba(var(--g-ink-rgb),.85) 100%);color:#fff;padding:64px 0 48px;text-align:center">
  <div class="container">
    <nav class="breadcrumb" style="color:rgba(255,255,255,.7);margin-bottom:14px;font-size:.85rem;justify-content:center;display:flex">
      <a href="<?= siteUrl($sub) ?>" style="color:#fff">首頁</a><span style="margin:0 6px"> / </span><span style="opacity:.85">專欄</span>
    </nav>
    <h1 style="font-size:clamp(1.8rem,4vw,2.4rem);font-weight:900;margin-bottom:10px">專欄文章</h1>
    <p style="opacity:.8">專業知識、案例分享、選購指南</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($articles)): ?>
      <div style="text-align:center;color:#888;padding:80px 0;font-size:1rem">文章整理中，請稍後再回來看看。</div>
    <?php else: ?>
      <div class="grid-3" style="gap:24px">
        <?php foreach ($articles as $a):
          $url = (IS_LOCAL || IS_STAGING)
              ? BASE_URL . '/site/article_detail.php?sub=' . urlencode($sub) . '&slug=' . urlencode($a['slug'])
              : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column/' . rawurlencode($a['slug']);
          $cover = $a['cover_image'] ? BASE_URL . '/' . h($a['cover_image']) : '';
          $date  = $a['published_at'] ?: $a['created_at'];
        ?>
        <a href="<?= h($url) ?>" style="display:block;background:#fff;border:1px solid rgba(var(--g-ink-rgb),.08);border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.04);transition:all .2s;color:var(--g-ink);text-decoration:none">
          <?php if ($cover): ?>
            <div style="aspect-ratio:16/10;background:url('<?= h($cover) ?>') center/cover"></div>
          <?php else: ?>
            <div style="aspect-ratio:16/10;background:linear-gradient(135deg,var(--g-bg-alt),#fff);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#ccc">📄</div>
          <?php endif; ?>
          <div style="padding:20px 22px 22px">
            <?php if ($a['category']): ?>
              <div style="font-size:.72rem;font-weight:700;color:var(--g-accent);letter-spacing:.1em;margin-bottom:8px;text-transform:uppercase"><?= h($a['category']) ?></div>
            <?php endif; ?>
            <h3 style="font-size:1.05rem;font-weight:800;color:var(--g-ink);line-height:1.45;margin-bottom:8px"><?= h($a['title']) ?></h3>
            <?php if ($a['summary']): ?>
              <p style="font-size:.88rem;color:#888;line-height:1.65;margin-bottom:10px"><?= h(mb_strimwidth($a['summary'], 0, 80, '…')) ?></p>
            <?php endif; ?>
            <div style="font-size:.75rem;color:#aaa"><?= date('Y/m/d', strtotime($date)) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/layout_foot.php'; ?>
