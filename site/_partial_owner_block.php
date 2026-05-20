<?php
// site/_partial_owner_block.php — 共用 partial：經營者介紹
// 給 site/index.php / index_design.php / index_food.php 都 include
// 需要外層已定義：$client
// 沒填 owner_name 就不輸出

if (empty($client['owner_name'])) return;
$_ownerAvatar = !empty($client['owner_avatar']) ? BASE_URL . '/' . h($client['owner_avatar']) : '';
$_ownerInitial = mb_substr($client['owner_name'], 0, 1);
?>
<section class="section" style="background:#fff">
  <div class="container" style="max-width:880px">
    <div style="display:grid;grid-template-columns:140px 1fr;gap:32px;align-items:center;padding:36px;background:var(--g-bg-alt);border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.04)">
      <?php if ($_ownerAvatar): ?>
        <img src="<?= h($_ownerAvatar) ?>" alt="<?= h($client['owner_name']) ?>" style="width:140px;height:140px;border-radius:50%;object-fit:cover;border:3px solid var(--g-accent)">
      <?php else: ?>
        <div style="width:140px;height:140px;border-radius:50%;background:linear-gradient(135deg,var(--g-accent),rgba(var(--g-accent-rgb),.7));color:#fff;display:grid;place-items:center;font-size:3.5rem;font-weight:900"><?= h($_ownerInitial) ?></div>
      <?php endif; ?>
      <div>
        <div style="font-size:.72rem;font-weight:700;color:var(--g-accent);letter-spacing:.14em;margin-bottom:6px">FROM THE OWNER</div>
        <h2 style="font-size:1.4rem;font-weight:900;color:var(--g-ink);margin-bottom:8px"><?= h($client['owner_name']) ?></h2>
        <?php if (!empty($client['owner_intro'])): ?>
        <p style="color:var(--g-ink-soft);line-height:1.8;font-size:1rem"><?= nl2br(h($client['owner_intro'])) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
