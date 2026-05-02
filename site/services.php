<?php
// site/services.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

$sub  = getSubdomain();
$slug = $sub;
$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在'); }

$pageKey = 'services';
$client  = $site['client'];

require __DIR__ . '/layout_head.php';
?>

<?php
$ind = $client['industry'] ?? '';
$isFood = str_contains($ind,'餐') || str_contains($ind,'食') || str_contains($ind,'料理') || str_contains($ind,'咖啡') || str_contains($ind,'甜點');
?>
<div class="page-banner">
  <div class="container"><h1><?= $isFood ? '🍽️ 服務項目' : '🛠️ 服務項目' ?></h1><p><?= $isFood ? '嚴選食材・用心料理・暖心服務' : '專業清潔・用心到位・安心保障' ?></p></div>
</div>
<div class="container">
  <div class="breadcrumb"><a href="<?= siteUrl($sub) ?>">首頁</a> › <span>服務項目</span></div>
</div>

<!-- 快速跳轉 Tab -->
<?php if(count($site['services'])>1): ?>
<div style="background:var(--g-bg-alt);border-bottom:1px solid rgba(var(--g-ink-rgb),.08);position:sticky;top:66px;z-index:150">
  <div class="container">
    <div style="display:flex;gap:0;overflow-x:auto;scrollbar-width:none">
      <?php foreach($site['services'] as $svc): ?>
      <a href="#svc-<?= $svc['id'] ?>" style="padding:14px 20px;font-size:.88rem;font-weight:700;color:var(--g-ink-soft);white-space:nowrap;border-bottom:3px solid transparent;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px"
         onmouseover="this.style.color='var(--g-ink)';this.style.borderBottomColor='var(--g-ink)'"
         onmouseout="this.style.color='';this.style.borderBottomColor='transparent'">
        <?php $ti = $svc['icon']??''; if ($ti && str_ends_with($ti,'.svg')): ?>
          <img src="<?= BASE_URL.'/'.h($ti) ?>" style="width:20px;height:20px;vertical-align:middle" alt="">
        <?php else: echo h($ti); endif; ?>
        <?= h($svc['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 服務詳情 -->
<?php foreach($site['services'] as $idx=>$svc): ?>
<section id="svc-<?= $svc['id'] ?>" style="padding:72px 0;<?= $idx%2===1?'background:var(--g-bg-alt)':'' ?>">
  <div class="container">
    <div style="display:grid;grid-template-columns:<?= $idx%2===1?'1.4fr 1fr':'1fr 1.4fr' ?>;gap:60px;align-items:center;margin-bottom:48px">
      <?php if($idx%2===1): /* 文字在左 */ ?>
      <!-- 文字 -->
      <div class="animate-in">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px">
          <div style="font-size:2.5rem;flex-shrink:0;margin-top:4px"><?php $si=$svc['icon']??''; if($si && str_ends_with($si,'.svg')): ?><img src="<?= BASE_URL.'/'.h($si) ?>" style="width:48px;height:48px" alt=""><?php else: echo h($si?:'🛠️'); endif; ?></div>
          <div>
            <h2 style="font-size:clamp(1.4rem,3vw,1.8rem);font-weight:900;color:var(--g-ink);line-height:1.2"><?= h($svc['name']) ?></h2>
            <?php if($svc['price_text']): ?><div style="display:inline-block;margin-top:6px;background:rgba(var(--g-accent-rgb),.15);color:var(--g-accent);font-weight:800;font-size:.9rem;padding:4px 14px;border-radius:20px"><?= h($svc['price_text']) ?></div><?php endif; ?>
          </div>
        </div>
        <p style="color:#555;line-height:1.9;font-size:.95rem;margin-bottom:20px"><?= nl2br(h($svc['full_desc']??$svc['short_desc']??'')) ?></p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:24px">
          <?php
          $features = $isFood
              ? ['✅ 進口頂級食材','✅ 專業主廚料理','✅ 免開瓶費','✅ 歡迎預約包場']
              : ['✅ 專業技師執行','✅ 環保清潔劑','✅ 免費到府估價','✅ 滿意保證'];
          foreach($features as $f): ?>
            <div style="font-size:.85rem;font-weight:600;color:var(--g-ink);padding:8px 12px;background:rgba(var(--g-ink-rgb),.06);border-radius:8px"><?= $f ?></div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <?php $lineUrl=$site['social']['line_url']??'#'; ?>
          <?php if($lineUrl!=='#'): ?><a href="<?= h($lineUrl) ?>" class="btn btn-accent" target="_blank">💬 <?= $isFood ? 'LINE 訂位預約' : 'LINE 詢問報價' ?></a><?php endif; ?>
          <?php if($client['phone']): ?><a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="btn btn-outline">📞 <?= $isFood ? '電話訂位' : '電話諮詢' ?></a><?php endif; ?>
        </div>
      </div>
      <!-- 圖片 -->
      <div class="animate-in delay-2">
        <?php if($svc['image_path']): ?>
          <img src="<?= BASE_URL.'/'.h($svc['image_path']) ?>" style="width:100%;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);aspect-ratio:4/3;object-fit:cover" alt="<?= h($svc['name']) ?>">
        <?php else: ?>
          <div style="font-size:6rem;text-align:center;padding:60px 0;background:var(--g-bg-alt);border-radius:20px"><?php $fi=$svc['icon']??''; if($fi && str_ends_with($fi,'.svg')): ?><img src="<?= BASE_URL.'/'.h($fi) ?>" style="width:120px;height:120px;margin:0 auto" alt=""><?php else: echo h($fi?:'🛠️'); endif; ?></div>
        <?php endif; ?>
      </div>

      <?php else: /* 圖片在左，文字在右 */ ?>
      <!-- 圖片 -->
      <div class="animate-in">
        <?php if($svc['image_path']): ?>
          <img src="<?= BASE_URL.'/'.h($svc['image_path']) ?>" style="width:100%;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);aspect-ratio:4/3;object-fit:cover" alt="<?= h($svc['name']) ?>">
        <?php else: ?>
          <div style="font-size:6rem;text-align:center;padding:60px 0;background:var(--g-bg-alt);border-radius:20px"><?php $fi=$svc['icon']??''; if($fi && str_ends_with($fi,'.svg')): ?><img src="<?= BASE_URL.'/'.h($fi) ?>" style="width:120px;height:120px;margin:0 auto" alt=""><?php else: echo h($fi?:'🛠️'); endif; ?></div>
        <?php endif; ?>
      </div>
      <!-- 文字 -->
      <div class="animate-in delay-2">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px">
          <div style="font-size:2.5rem;flex-shrink:0;margin-top:4px"><?php $si=$svc['icon']??''; if($si && str_ends_with($si,'.svg')): ?><img src="<?= BASE_URL.'/'.h($si) ?>" style="width:48px;height:48px" alt=""><?php else: echo h($si?:'🛠️'); endif; ?></div>
          <div>
            <h2 style="font-size:clamp(1.4rem,3vw,1.8rem);font-weight:900;color:var(--g-ink);line-height:1.2"><?= h($svc['name']) ?></h2>
            <?php if($svc['price_text']): ?><div style="display:inline-block;margin-top:6px;background:rgba(var(--g-accent-rgb),.15);color:var(--g-accent);font-weight:800;font-size:.9rem;padding:4px 14px;border-radius:20px"><?= h($svc['price_text']) ?></div><?php endif; ?>
          </div>
        </div>
        <p style="color:#555;line-height:1.9;font-size:.95rem;margin-bottom:20px"><?= nl2br(h($svc['full_desc']??$svc['short_desc']??'')) ?></p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:24px">
          <?php
          $features = $isFood
              ? ['✅ 進口頂級食材','✅ 專業主廚料理','✅ 免開瓶費','✅ 歡迎預約包場']
              : ['✅ 專業技師執行','✅ 環保清潔劑','✅ 免費到府估價','✅ 滿意保證'];
          foreach($features as $f): ?>
            <div style="font-size:.85rem;font-weight:600;color:var(--g-ink);padding:8px 12px;background:rgba(var(--g-ink-rgb),.06);border-radius:8px"><?= $f ?></div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <?php if($lineUrl!=='#'): ?><a href="<?= h($lineUrl) ?>" class="btn btn-accent" target="_blank">💬 <?= $isFood ? 'LINE 訂位預約' : 'LINE 詢問報價' ?></a><?php endif; ?>
          <?php if($client['phone']): ?><a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="btn btn-outline">📞 <?= $isFood ? '電話訂位' : '電話諮詢' ?></a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- FAQ 手風琴 -->
    <?php if(!empty($svc['faqs'])): ?>
    <div class="animate-in" style="max-width:760px;margin:0 auto">
      <h3 style="font-size:1.1rem;font-weight:800;color:var(--g-ink);margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid rgba(var(--g-ink-rgb),.12)">❓ 常見問題</h3>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach($svc['faqs'] as $fi=>$faq): ?>
        <div style="border:1.5px solid rgba(var(--g-ink-rgb),.1);border-radius:10px;overflow:hidden;background:#fff">
          <button onclick="toggleFaq(this)" aria-expanded="false"
            style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:none;border:none;cursor:pointer;text-align:left;font-size:.92rem;font-weight:700;color:var(--g-ink-soft);transition:all .2s"
            onmouseover="this.style.background='rgba(var(--g-ink-rgb),.04)'"
            onmouseout="this.style.background=''">
            <span><?= h($faq['question']) ?></span>
            <svg style="width:18px;height:18px;flex-shrink:0;color:var(--g-ink);transition:transform .25s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div style="max-height:0;overflow:hidden;transition:max-height .3s ease,padding .3s" class="faq-body">
            <p style="padding:0 18px 18px;color:#555;font-size:.9rem;line-height:1.8"><?= nl2br(h($faq['answer'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>

<!-- CTA Banner -->
<section style="background:var(--g-ink);padding:56px 0;text-align:center;color:#fff">
  <div class="container animate-in">
    <div style="font-size:.85rem;opacity:.7;margin-bottom:10px;letter-spacing:.1em">FREE ESTIMATE</div>
    <h2 style="font-size:clamp(1.5rem,3vw,2.2rem);font-weight:900;margin-bottom:14px">免費估價・今天就預約！</h2>
    <p style="opacity:.8;margin-bottom:32px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.8">台南在地團隊，快速回覆、到府勘查，透明報價無隱藏費用</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <?php $lu=$site['social']['line_url']??'#'; if($lu!=='#'): ?>
        <a href="<?= h($lu) ?>" class="btn" style="background:#06c755;border-color:#06c755;color:#fff" target="_blank">💬 LINE 免費報價</a>
      <?php endif; ?>
      <?php if($client['phone']): ?>
        <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$client['phone'])) ?>" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)">📞 <?= h($client['phone']) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
function toggleFaq(btn) {
  const isOpen = btn.getAttribute('aria-expanded')==='true';
  btn.closest('.container').querySelectorAll('button[aria-expanded]').forEach(b=>{
    b.setAttribute('aria-expanded','false');
    b.querySelector('svg').style.transform='';
    b.nextElementSibling.style.maxHeight='0';
    b.nextElementSibling.style.paddingBottom='0';
  });
  if(!isOpen){
    btn.setAttribute('aria-expanded','true');
    btn.querySelector('svg').style.transform='rotate(180deg)';
    const body=btn.nextElementSibling;
    body.style.maxHeight=body.scrollHeight+'px';
  }
}
document.addEventListener('DOMContentLoaded',function(){
  const hash=window.location.hash;
  if(hash){const el=document.querySelector(hash);if(el)setTimeout(()=>el.scrollIntoView({behavior:'smooth',block:'start'}),150);}
});
</script>

<?php require __DIR__ . '/layout_foot.php'; ?>
