<?php
// site/index.php  ─  多客戶通用首頁模板
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';

$sub = getSubdomain();
if (!$sub) { http_response_code(404); die('找不到網站'); }

$site = loadSiteData($sub);
if (!$site) { http_response_code(404); die('網站不存在或已停用'); }

// ─── has_minisite 開關檢查 ───────────────────────
// 沒啟用小官網 → 跳外部官網（如有）或主站行銷頁
if (empty($site['client']['has_minisite'])) {
    if (!empty($site['client']['external_website_url'])) {
        header('Location: ' . $site['client']['external_website_url'], true, 301);
        exit;
    }
    // 跳主站行銷頁（301 永久轉址，保 SEO）
    $mainSite = IS_LOCAL
        ? BASE_URL . '/store.php?sub=' . urlencode($sub)
        : 'https://www.gomag.com.tw/store/' . urlencode($sub);
    header('Location: ' . $mainSite, true, 301);
    exit;
}

$slug    = $sub;
$pageKey = 'home';
$client  = $site['client'];
$social  = $site['social'];
$phone   = $client['phone'] ?? '';
$lineUrl = $social['line_url'] ?? '#';

// ── 依產業設定動態文案 ──
$ind = $client['industry'] ?? '';
$isFood = str_contains($ind,'餐') || str_contains($ind,'食') || str_contains($ind,'料理') || str_contains($ind,'咖啡') || str_contains($ind,'甜點');

// ── 從 DB 讀取 hero_stats 和 about_tags（如果有），否則使用業種預設值 ──
$dbHeroStats = !empty($client['hero_stats']) ? json_decode($client['hero_stats'], true) : null;
$dbAboutTags = !empty($client['about_tags']) ? json_decode($client['about_tags'], true) : null;

if ($isFood) {
    $defaultHeroStats = [['4.5★','Google評分'],['429+','真實評價'],['10年','料理經驗'],['95%','回訪率']];
    $defaultAboutTags = ['🍽️ 進口頂級食材','👨‍🍳 專業主廚料理','🍷 免開瓶費','🎂 包場客製服務'];
    $svcSubtitle = '嚴選食材、用心烹調，為您呈現每一道暖心料理';
    $caseLabel = 'FEATURED DISHES'; $caseTitle = '招牌料理'; $caseSub = '主廚精選，每一道都是暖心之作';
    $fbDesc = '看最新菜單、季節限定、美食分享，第一手掌握優惠資訊';
    $contactSub = '歡迎來電訂位或 LINE 預約包場';
} else {
    $defaultHeroStats = [['500+','服務客戶'],['8年','專業經驗'],['98%','回購率'],['4.9★','Google評分']];
    $defaultAboutTags = ['🏆 專業認證技師','🌱 環保清潔用品','📋 免費到府估價','⏱ 準時守信到場'];
    $svcSubtitle = '每項服務由專業技師執行，使用環保清潔劑，安全無毒';
    $caseLabel = 'CASE STUDIES'; $caseTitle = '精選施工案例'; $caseSub = '真實 Before / After 對比，讓成果說話';
    $fbDesc = '看最新優惠、案例分享、清潔小知識，第一手掌握優惠資訊';
    $contactSub = '免費到府估價，專人為您說明';
}

// DB 優先，否則用業種預設
$heroStats = $dbHeroStats
    ? array_map(fn($s) => [$s['value'], $s['label']], $dbHeroStats)
    : $defaultHeroStats;
$aboutTags = $dbAboutTags ?: $defaultAboutTags;

// aboutStats 從 heroStats 衍生（交替顯示 accent 色）
$aboutStats = array_map(fn($s, $i) => [$s[0], $s[1], $i % 2 ? 'accent' : ''], $heroStats, array_keys($heroStats));

require __DIR__ . '/layout_head.php';
?>

<!-- ══ 1. HERO ═══════════════════════════════════════════ -->
<section class="hero">
  <div class="hero-bg" <?php if($client['hero_image_path']): ?>style="background-image:url('<?= BASE_URL.'/'.h($client['hero_image_path']) ?>')"<?php endif; ?>>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <div class="hero-badge animate-in">✨ <?= h($client['industry'] ?? '專業服務') ?></div>
      <h1 class="hero-title animate-in delay-1">
        <?= h($client['brand_name']) ?><br>
        <span><?= h($client['tagline'] ?? '用心服務，品質保證') ?></span>
      </h1>
      <p class="hero-sub animate-in delay-2">
        <?php
        $svcNames = array_column(array_slice($site['services'],0,4),'name');
        echo h(implode('・',$svcNames));
        ?><br><?= h($client['address'] ?? '') ?>
      </p>
      <div class="hero-actions animate-in delay-3">
        <?php if($lineUrl!=='#'): ?><a href="<?= h($lineUrl) ?>" class="btn btn-accent" target="_blank">💬 LINE 立即諮詢</a><?php endif; ?>
        <?php if($phone): ?><a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.6)">📞 <?= h($phone) ?></a><?php endif; ?>
      </div>
      <div class="hero-stats animate-in delay-4">
        <?php foreach($heroStats as $hs): ?>
        <div class="stat-item"><strong><?= $hs[0] ?></strong><span><?= $hs[1] ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<style>
.hero{position:relative;min-height:580px;display:flex;align-items:center}
.hero-bg{position:absolute;inset:0;background:var(--g-ink);background-size:cover;background-position:center}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(var(--g-ink-rgb),.92) 0%,rgba(var(--g-ink-rgb),.75) 60%,rgba(var(--g-ink-rgb),.5) 100%)}
.hero-content{position:relative;z-index:1;color:#fff;padding:80px 20px 72px;max-width:680px}
.hero-badge{display:inline-block;background:rgba(var(--g-accent-rgb),.25);border:1px solid rgba(var(--g-accent-rgb),.5);color:#f5d080;padding:5px 16px;border-radius:20px;font-size:.8rem;font-weight:700;letter-spacing:.08em;margin-bottom:18px}
.hero-title{font-size:clamp(2rem,5vw,3.2rem);font-weight:900;line-height:1.2;margin-bottom:18px}
.hero-title span{color:var(--g-accent);display:block;font-size:.7em;margin-top:6px}
.hero-sub{font-size:1rem;line-height:1.8;opacity:.85;margin-bottom:28px}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:40px}
.hero-stats{display:flex;background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border-radius:12px;padding:16px 24px;border:1px solid rgba(255,255,255,.15)}
.stat-item{flex:1;text-align:center;padding:0 12px;border-right:1px solid rgba(255,255,255,.2)}
.stat-item:last-child{border-right:none}
.stat-item strong{display:block;font-size:1.4rem;font-weight:900;color:var(--g-accent)}
.stat-item span{font-size:.72rem;opacity:.8}
@media(max-width:600px){.hero-stats{padding:12px}.stat-item{padding:0 6px}.stat-item strong{font-size:1.1rem}}
</style>

<!-- ══ 2. 關於我們 ════════════════════════════════════════ -->
<section class="section" style="background:var(--g-bg-alt)">
  <div class="container">
    <div class="about-layout">
      <div class="about-text animate-in">
        <div class="section-title" style="text-align:left;margin-bottom:24px">
          <div class="label">ABOUT US</div>
          <h2>關於<?= h($client['brand_name']) ?></h2>
        </div>
        <p style="color:#555;line-height:1.9;margin-bottom:20px"><?= nl2br(h($client['about_text']??'')) ?></p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px">
          <?php foreach($aboutTags as $f): ?>
            <div style="background:rgba(var(--g-ink-rgb),.07);color:var(--g-ink);padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:700;border:1px solid rgba(var(--g-ink-rgb),.15)"><?= $f ?></div>
          <?php endforeach; ?>
        </div>
        <a href="<?= siteUrl($sub,'services') ?>" class="btn btn-primary" style="margin-top:24px">查看服務項目 →</a>
      </div>
      <div class="about-visual animate-in delay-2">
        <?php foreach($aboutStats as $v): ?>
        <div style="background:<?= $v[2]?'var(--g-ink)':'#fff' ?>;border-radius:16px;padding:28px 20px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.07)">
          <div style="font-size:2.2rem;font-weight:900;color:<?= $v[2]?'#fff':'var(--g-ink)' ?>;line-height:1"><?= $v[0] ?></div>
          <div style="font-size:.78rem;color:<?= $v[2]?'rgba(255,255,255,.75)':'#888' ?>;margin-top:6px;font-weight:600"><?= $v[1] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<style>
.about-layout{display:grid;grid-template-columns:1.2fr 1fr;gap:60px;align-items:center}
@media(max-width:768px){.about-layout{grid-template-columns:1fr;gap:32px}}
.about-visual{display:grid;grid-template-columns:1fr 1fr;gap:16px}
</style>

<!-- ══ 3. 服務預覽 ════════════════════════════════════════ -->
<section class="section">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">OUR SERVICES</div>
      <h2>專業服務項目</h2>
      <p><?= $svcSubtitle ?></p>
    </div>
    <div class="grid-<?= min(count($site['services']),4) ?>">
      <?php foreach($site['services'] as $i=>$svc): ?>
      <a href="<?= siteUrl($sub,'services') ?>#svc-<?= $svc['id'] ?>"
         class="animate-in delay-<?= min($i+1,4) ?>"
         style="background:#fff;border-radius:16px;padding:0 0 0 0;border:1.5px solid rgba(var(--g-ink-rgb),.08);transition:all .25s;display:block;color:inherit;overflow:hidden">
        <?php
        $icon = $svc['icon'] ?? '';
        $imgPath = $svc['image_path'] ?? '';
        if ($imgPath): ?>
          <div style="overflow:hidden">
            <img src="<?= BASE_URL.'/'.h($imgPath) ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block" alt="<?= h($svc['name']) ?>"
                 onerror="this.parentElement.innerHTML='<div style=\'width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.6));display:flex;align-items:center;justify-content:center;font-size:3rem\'><?= $isFood ? '🍽️' : addslashes(h($icon ?: '🛠️')) ?></div>'">
          </div>
        <?php elseif ($icon && preg_match('/\.(svg|jpg|jpeg|png|webp)$/i', $icon)): ?>
          <div style="overflow:hidden">
            <img src="<?= BASE_URL.'/'.h($icon) ?>" style="width:100%;aspect-ratio:4/3;object-fit:contain;display:block;background:#f8f9fa;padding:20px" alt="<?= h($svc['name']) ?>"
                 onerror="this.parentElement.innerHTML='<div style=\'width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.6));display:flex;align-items:center;justify-content:center;font-size:3rem\'>🛠️</div>'">
          </div>
        <?php else: ?>
          <div style="overflow:hidden">
            <div style="width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.6));display:flex;align-items:center;justify-content:center;font-size:3rem"><?= $isFood ? '🍽️' : h($icon ?: '🛠️') ?></div>
          </div>
        <?php endif; ?>
        <div style="padding:20px 22px 24px">
          <h3 style="font-size:1.05rem;font-weight:800;color:var(--g-ink);margin-bottom:8px"><?= h($svc['name']) ?></h3>
          <p  style="font-size:.85rem;color:#666;line-height:1.7;margin-bottom:12px"><?= h($svc['short_desc']??'') ?></p>
          <?php if($svc['price_text']): ?>
            <div style="display:inline-block;background:rgba(var(--g-accent-rgb),.12);color:var(--g-accent);font-weight:800;font-size:.85rem;padding:4px 12px;border-radius:20px;margin-bottom:10px"><?= h($svc['price_text']) ?></div>
          <?php endif; ?>
          <div style="font-size:.8rem;color:var(--g-ink);font-weight:700">了解詳情 →</div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:40px">
      <a href="<?= siteUrl($sub,'services') ?>" class="btn btn-primary">查看完整服務說明與 FAQ</a>
    </div>
  </div>
</section>

<!-- ══ 4. 精選案例 ════════════════════════════════════════ -->
<?php $featured=array_slice(array_values(array_filter($site['cases'],fn($c)=>$c['is_featured'])),0,3);
if($featured): ?>
<section class="section" style="background:var(--g-bg-alt)">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label"><?= $caseLabel ?></div>
      <h2><?= $caseTitle ?></h2>
      <p><?= $caseSub ?></p>
    </div>
    <div class="grid-3">
      <?php foreach($featured as $i=>$c): ?>
      <?php $bThumb = caseThumb($c['before_image']??''); $aThumb = caseThumb($c['after_image']??''); ?>
      <a href="<?= siteUrl($sub,'cases') ?>" class="animate-in delay-<?= $i+1 ?>" style="display:block;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);transition:transform .2s,box-shadow .2s;text-decoration:none;color:inherit"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 32px rgba(0,0,0,.12)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <?php if($bThumb && $aThumb && !$isFood): ?>
        <div style="position:relative;height:200px;overflow:hidden">
          <img src="<?= BASE_URL.'/'.h($bThumb) ?>" style="position:absolute;top:0;left:0;width:50%;height:100%;object-fit:cover;filter:saturate(.6) brightness(.95)">
          <img src="<?= BASE_URL.'/'.h($aThumb) ?>" style="position:absolute;top:0;right:0;width:50%;height:100%;object-fit:cover">
          <div style="position:absolute;left:50%;top:0;width:2px;height:100%;background:#fff;z-index:2"></div>
          <div style="position:absolute;top:8px;left:0;right:0;display:flex;justify-content:space-between;padding:0 8px;z-index:3">
            <span style="font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:4px;background:rgba(0,0,0,.55);color:#fff">BEFORE</span>
            <span style="font-size:.65rem;font-weight:800;padding:2px 8px;border-radius:4px;background:var(--g-accent);color:#fff">AFTER</span>
          </div>
        </div>
        <?php elseif($aThumb): ?>
          <img src="<?= BASE_URL.'/'.h($aThumb) ?>" style="width:100%;height:200px;object-fit:cover">
        <?php elseif($bThumb): ?>
          <img src="<?= BASE_URL.'/'.h($bThumb) ?>" style="width:100%;height:200px;object-fit:cover">
        <?php else: ?>
          <div style="width:100%;height:200px;background:linear-gradient(135deg,var(--g-ink),rgba(var(--g-ink-rgb),.7));display:flex;align-items:center;justify-content:center">
            <span style="font-size:3rem;opacity:.3"><?= $isFood ? '🍽️' : '📸' ?></span>
          </div>
        <?php endif; ?>
        <div style="padding:16px 18px">
          <?php if($c['svc_name']): ?>
            <span style="background:rgba(var(--g-ink-rgb),.08);color:var(--g-ink);font-size:.72rem;font-weight:700;padding:2px 10px;border-radius:12px;margin-bottom:8px;display:inline-block"><?= h($c['svc_name']) ?></span>
          <?php endif; ?>
          <h3 style="font-size:.95rem;font-weight:800;color:var(--g-ink-soft);margin-bottom:6px"><?= h($c['title']) ?></h3>
          <div style="display:flex;gap:12px;font-size:.78rem;color:#888">
            <?php if($c['location']): ?><span>📍 <?= h($c['location']) ?></span><?php endif; ?>
            <?php if($c['area_sqm'] && !$isFood): ?><span>📐 <?= $c['area_sqm'] ?>坪</span><?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:36px">
      <a href="<?= siteUrl($sub,'cases') ?>" class="btn btn-outline">查看全部案例 →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 5. 網友評價 ════════════════════════════════════════ -->
<?php if($site['testimonials']): ?>
<section class="section">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">TESTIMONIALS</div>
      <h2>客戶真實評價</h2>
      <p>每一個笑容，都是我們最大的動力</p>
    </div>
    <div class="grid-3">
      <?php foreach($site['testimonials'] as $i=>$t): ?>
      <div class="animate-in delay-<?= min($i%4+1,4) ?>" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);transition:transform .2s,box-shadow .2s"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 32px rgba(0,0,0,.12)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <?php if(!empty($t['og_image'])): ?>
          <img src="<?= h($t['og_image']) ?>" style="width:100%;height:180px;object-fit:cover" loading="lazy" alt="口碑文章"
               onerror="this.style.display='none'">
        <?php endif; ?>
        <div style="padding:18px 20px">
          <div style="color:#f4a611;font-size:1rem;margin-bottom:10px">★★★★★</div>
          <p style="font-size:.88rem;color:#555;line-height:1.8;margin-bottom:14px;font-style:italic">"<?= h(mb_strimwidth($t['content'],0,100,'…')) ?>"</p>
          <?php if($t['svc_name']): ?>
            <span style="display:inline-block;background:rgba(var(--g-ink-rgb),.08);color:var(--g-ink);font-size:.72rem;font-weight:700;padding:2px 10px;border-radius:12px"><?= h($t['svc_name']) ?></span>
          <?php endif; ?>
          <?php if(!empty($t['source_url'])): ?>
            <a href="<?= h($t['source_url']) ?>" target="_blank" rel="noopener" style="display:inline-block;margin-top:10px;font-size:.78rem;color:var(--g-ink);text-decoration:none;font-weight:600">📖 閱讀全文 →</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="animate-in" style="text-align:center;margin-top:36px">
      <a href="<?= siteUrl($sub,'testimonials') ?>" class="btn btn-outline">查看全部評價 →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 6. 聯絡 + 地圖 + FB 粉絲頁 ═══════════════════════ -->
<section class="section" id="contact">
  <div class="container">
    <div class="section-title animate-in">
      <div class="label">CONTACT</div>
      <h2>立即聯絡我們</h2>
      <p><?= $contactSub ?></p>
    </div>
    <style>
    .contact-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:40px;align-items:start}
    @media(max-width:768px){.contact-grid{grid-template-columns:1fr;gap:24px}}
    </style>
    <div class="contact-grid">
      <!-- 左：Facebook 粉絲頁嵌入（最新貼文） -->
      <div class="animate-in">
        <?php if(!empty($social['fb_page_url'])): ?>
          <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07);border:1px solid #e4e4e4">
            <div style="background:#1877f2;color:#fff;padding:14px 20px;display:flex;align-items:center;gap:10px">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="#fff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              <span style="font-weight:700;font-size:.95rem">Facebook 粉絲頁</span>
            </div>
            <div style="padding:0">
              <div id="fb-root"></div>
              <script async defer crossorigin="anonymous" src="https://connect.facebook.net/zh_TW/sdk.js#xfbml=1&version=v21.0" nonce="fbSDK"></script>
              <div class="fb-page"
                   data-href="<?= h($social['fb_page_url']) ?>"
                   data-tabs="timeline"
                   data-width="500"
                   data-height="700"
                   data-small-header="true"
                   data-adapt-container-width="true"
                   data-hide-cover="false"
                   data-show-facepile="true">
                <blockquote cite="<?= h($social['fb_page_url']) ?>" class="fb-xfbml-parse-ignore">
                  <a href="<?= h($social['fb_page_url']) ?>" target="_blank" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:400px;text-decoration:none;color:#666">
                    <div style="font-size:3rem;margin-bottom:12px">📘</div>
                    <div style="font-weight:700;color:var(--g-ink);margin-bottom:8px">前往 Facebook 粉絲頁</div>
                    <div style="font-size:.85rem">查看最新動態與施工分享</div>
                  </a>
                </blockquote>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- 沒有 FB 粉絲頁：地圖放大 -->
          <div style="border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);min-height:400px">
            <?php if($client['google_maps_embed']): ?>
              <iframe src="<?= h($client['google_maps_embed']) ?>" width="100%" height="500" style="border:0" allowfullscreen loading="lazy"></iframe>
            <?php else: ?>
              <div style="height:400px;background:var(--g-bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px">
                <div style="font-size:3rem;margin-bottom:12px">🗺️</div>
                <div style="font-weight:700;color:var(--g-ink)">📍 <?= h($client['address']??'') ?></div>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- 右：聯絡資訊 + LINE CTA + 地圖 -->
      <div class="animate-in delay-2">
        <?php
        $rows=[
          ['📞','電話',$phone?'<a href="tel:'.preg_replace('/[^0-9+]/','',$phone).'">'.h($phone).'</a>':''],
          ['📍','地址',h($client['address']??'')],
          ['📧','Email',$client['email']?'<a href="mailto:'.h($client['email']).'">'.h($client['email']).'</a>':''],
          ['💬','LINE',h($social['line_id']??'')],
        ];
        foreach(array_filter($rows,fn($r)=>$r[2]) as $r): ?>
        <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 0;border-bottom:1px solid rgba(var(--g-ink-rgb),.07)">
          <div style="font-size:1.4rem;flex-shrink:0;margin-top:2px"><?= $r[0] ?></div>
          <div>
            <div style="font-size:.75rem;color:#999;font-weight:600;letter-spacing:.05em;margin-bottom:2px"><?= $r[1] ?></div>
            <div style="font-size:1rem;font-weight:700"><?= $r[2] ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- LINE + 電話 CTA -->
        <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap">
          <?php if($lineUrl!=='#'): ?>
            <a href="<?= h($lineUrl) ?>" class="btn btn-accent" target="_blank" style="flex:1;justify-content:center">💬 LINE 立即諮詢</a>
          <?php endif; ?>
          <?php if($phone): ?>
            <a href="tel:<?= h(preg_replace('/[^0-9+]/','',$phone)) ?>" class="btn btn-primary" style="flex:1;justify-content:center">📞 <?= h($phone) ?></a>
          <?php endif; ?>
        </div>

        <!-- 地圖 -->
        <div style="margin-top:24px;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);height:280px">
          <?php if($client['google_maps_embed']): ?>
            <iframe src="<?= h($client['google_maps_embed']) ?>" width="100%" height="100%" style="border:0;min-height:280px" allowfullscreen loading="lazy"></iframe>
          <?php else: ?>
            <div style="height:100%;background:var(--g-bg-alt);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px">
              <div style="font-size:3rem;margin-bottom:12px">🗺️</div>
              <div style="font-weight:700;color:var(--g-ink)">📍 <?= h($client['address']??'') ?></div>
              <div style="font-size:.85rem;color:#888;margin-top:6px">後台設定 Google Maps 後會顯示地圖</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/layout_foot.php'; ?>
