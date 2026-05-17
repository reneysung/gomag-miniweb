<?php
// site/_partial_photos_gallery.php — 共用 partial：店家相簿
// 給 site/index.php / index_design.php / index_food.php 都 include
// 需要外層已定義：$client
// 沒 photos JSON 就不輸出

$_photos = !empty($client['photos']) ? json_decode($client['photos'], true) : [];
if (!is_array($_photos) || empty($_photos)) return;
?>
<section class="section" style="background:var(--g-bg-alt)">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">GALLERY</div>
      <h2>店家相簿</h2>
      <p>真實環境與服務現場</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
      <?php foreach ($_photos as $_p): if (empty($_p)) continue; ?>
        <div style="aspect-ratio:1/1;background:url('<?= BASE_URL . '/' . h($_p) ?>') center/cover;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.06);transition:transform .3s"
             onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform=''"></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
