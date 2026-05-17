<?php
// site/_partial_column_preview.php — 共用 partial：最新專欄 preview
// 給 site/index.php / index_design.php / index_food.php 都 include
// 需要外層已定義：$client, $sub
// 如該 client 沒文章就不輸出

$_latestArticles = [];
try {
    $_artSt = getDB()->prepare("SELECT id, slug, title, summary, cover_image, category, published_at, created_at
                                FROM articles WHERE client_id=? AND is_active=1
                                ORDER BY COALESCE(published_at, created_at) DESC LIMIT 3");
    $_artSt->execute([(int)$client['id']]);
    $_latestArticles = $_artSt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if ($_latestArticles):
?>
<section class="section" style="background:var(--g-bg-alt)">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">COLUMN</div>
      <h2>最新專欄</h2>
      <p>專業知識、選購指南、案例分享</p>
    </div>
    <div class="grid-3" style="gap:20px">
      <?php foreach ($_latestArticles as $_a):
          $_aUrl = (IS_LOCAL || IS_STAGING)
              ? BASE_URL . '/site/article_detail.php?sub=' . urlencode($sub) . '&slug=' . urlencode($_a['slug'])
              : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column/' . rawurlencode($_a['slug']);
          $_aCover = $_a['cover_image'] ? BASE_URL . '/' . h($_a['cover_image']) : '';
          $_aDate  = $_a['published_at'] ?: $_a['created_at'];
      ?>
      <a href="<?= h($_aUrl) ?>" class="animate-in" style="display:block;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.04);color:var(--g-ink);text-decoration:none;transition:transform .2s,box-shadow .2s"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 28px rgba(0,0,0,.1)'"
         onmouseout="this.style.transform='';this.style.boxShadow='0 4px 16px rgba(0,0,0,.04)'">
        <?php if ($_aCover): ?>
          <div style="aspect-ratio:16/10;background:url('<?= h($_aCover) ?>') center/cover"></div>
        <?php else: ?>
          <div style="aspect-ratio:16/10;background:linear-gradient(135deg,var(--g-bg),#fff);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#ccc">📄</div>
        <?php endif; ?>
        <div style="padding:18px 22px">
          <?php if ($_a['category']): ?>
            <div style="font-size:.7rem;font-weight:700;color:var(--g-accent);letter-spacing:.1em;margin-bottom:6px;text-transform:uppercase"><?= h($_a['category']) ?></div>
          <?php endif; ?>
          <h3 style="font-size:1rem;font-weight:800;color:var(--g-ink);line-height:1.5;margin-bottom:6px"><?= h($_a['title']) ?></h3>
          <?php if ($_a['summary']): ?>
            <p style="font-size:.85rem;color:#888;line-height:1.6;margin-bottom:8px"><?= h(mb_strimwidth($_a['summary'], 0, 60, '…')) ?></p>
          <?php endif; ?>
          <div style="font-size:.72rem;color:#aaa"><?= date('Y/m/d', strtotime($_aDate)) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:36px">
      <a href="<?= h((IS_LOCAL || IS_STAGING) ? BASE_URL . '/site/articles.php?sub=' . urlencode($sub) : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/column') ?>" class="btn btn-outline">看全部文章 →</a>
    </div>
  </div>
</section>
<?php endif; ?>
