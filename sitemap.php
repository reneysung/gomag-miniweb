<?php
// ============================================================
// sitemap.php  ─  動態產生 sitemap.xml（主站 + 行銷頁 + mini-site）
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/front_functions.php';

header('Content-Type: application/xml; charset=UTF-8');

$db = getDB();

// ── 判斷是否為 mini-site 子網域訪問（非 www / 非主站）──
// 子網域訪問 → sitemap 只列該子網域自己的 URL（避免 cross-host sitemap）
$host = $_SERVER['HTTP_HOST'] ?? '';
$subOnly = null;
if (preg_match('/^([a-z0-9-]+)\.gomag\.com\.tw$/i', $host, $m) && strtolower($m[1]) !== 'www') {
    $subOnly = strtolower($m[1]);
}

// 排除重複客戶（同店多筆 → 已 301 到主檔）— 集中於 getDuplicateSkipSlugs()
$dupSkip = getDuplicateSkipSlugs();
if ($subOnly) {
    // 子網域模式：只撈該 sub 的 client（且必須啟用且有 mini-site）
    $stmt = $db->prepare("SELECT subdomain, slug, has_minisite, updated_at FROM clients WHERE is_active=1 AND has_minisite=1 AND (subdomain=? OR slug=?) LIMIT 1");
    $stmt->execute([$subOnly, $subOnly]);
    $clients = $stmt->fetchAll();
    $cats = [];
} else {
    // 主站模式：撈所有啟用 client + 排除重複 + 排除 placeholder（薄頁不進 sitemap）
    $ph = implode(',', array_fill(0, count($dupSkip), '?'));
    $stmt = $db->prepare("SELECT subdomain, slug, has_minisite, updated_at FROM clients WHERE is_active=1 AND COALESCE(is_placeholder, 0) = 0 AND slug NOT IN ($ph) ORDER BY id");
    $stmt->execute($dupSkip);
    $clients = $stmt->fetchAll();
    $cats = $db->query('SELECT slug FROM categories WHERE is_active=1')->fetchAll();
}

$baseUrl = (IS_LOCAL || IS_STAGING) ? BASE_URL : 'https://www.gomag.com.tw';
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php if (!$subOnly): /* 主站專屬區段：子網域 sitemap 不列 cross-host URL */ ?>

  <!-- 主站首頁 -->
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- 主站分類頁總覽 -->
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/category</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <!-- 各分類列表頁 -->
  <?php foreach ($cats as $c): ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/category/<?= htmlspecialchars($c['slug']) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>

  <!-- 縣市瀏覽總覽 -->
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/city</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <!-- 各縣市落地頁（依 DB 動態抓有 ≥3 家店家的縣市）-->
  <?php
    $cityNameToSlug = array_flip(getCityMap());  // 唯一來源：cities 表
    $cityListRegex = '臺北市|台北市|新北市|桃園市|臺中市|台中市|臺南市|台南市|高雄市|基隆市|新竹市|新竹縣|苗栗縣|彰化縣|南投縣|雲林縣|嘉義市|嘉義縣|屏東縣|宜蘭縣|花蓮縣|臺東縣|台東縣|澎湖縣|金門縣|連江縣';
    $cityRows = $db->query("SELECT address FROM clients WHERE is_active=1 AND address IS NOT NULL AND address != ''")->fetchAll();
    $cityCounts = [];
    foreach ($cityRows as $r) {
        if (preg_match('/^(' . $cityListRegex . ')/u', $r['address'], $m)) {
            $c = str_replace('臺', '台', $m[1]);
            $cityCounts[$c] = ($cityCounts[$c] ?? 0) + 1;
        }
    }
    foreach ($cityCounts as $cityName => $cnt):
        if ($cnt < 3) continue;
        if (!isset($cityNameToSlug[$cityName])) continue;
        $citySlug = $cityNameToSlug[$cityName];
        $prio = $cnt >= 5 ? '0.85' : '0.65';
  ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/city/<?= htmlspecialchars($citySlug) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority><?= $prio ?></priority>
  </url>
  <?php endforeach; ?>

  <!-- 城市×產業 交叉頁（樞紐：≥3 真實店 或 有大分類內容 或 有子服務；子服務頁：皆有內容必收）-->
  <?php
    $catIdToSlug = [];
    foreach ($db->query("SELECT id, slug FROM categories WHERE is_active=1") as $cc) $catIdToSlug[(int)$cc['id']] = $cc['slug'];
    $geoSvc = [];  // "city|cid" => [service_slug, ...]（'' = 大分類層）
    try {
        foreach ($db->query("SELECT city_slug, category_id, service_slug FROM geo_category_pages WHERE is_active=1") as $g)
            $geoSvc[$g['city_slug'] . '|' . (int)$g['category_id']][] = (string)$g['service_slug'];
    } catch (\Throwable $e) { /* 表未建時略過 */ }
    $dupPhCross = implode(',', array_fill(0, count($dupSkip), '?'));
    $cellStmt = $db->prepare("SELECT category_id, SUM(COALESCE(is_placeholder,0)=0) AS rc FROM clients WHERE is_active=1 AND city_slug = ? AND slug NOT IN ($dupPhCross) AND category_id IS NOT NULL GROUP BY category_id");
    foreach (getCityMap() as $cSlug => $cName):
        $cellStmt->execute(array_merge([$cSlug], $dupSkip));
        $counts = [];
        foreach ($cellStmt->fetchAll() as $r) $counts[(int)$r['category_id']] = (int)$r['rc'];
        foreach ($catIdToSlug as $cid => $catSlugX):
            $svcList = $geoSvc[$cSlug . '|' . $cid] ?? [];
            $hasCatContent = in_array('', $svcList, true);
            $subs = array_values(array_filter($svcList, fn($s) => $s !== ''));
            $rc = $counts[$cid] ?? 0;
            $emitHub = ($rc >= 3) || $hasCatContent || count($subs) > 0;
            // 樞紐頁
            if ($emitHub):
  ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/city/<?= htmlspecialchars($cSlug) ?>/<?= htmlspecialchars($catSlugX) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endif; ?>
  <?php foreach ($subs as $svSlug): // 子服務頁（皆有內容） ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/city/<?= htmlspecialchars($cSlug) ?>/<?= htmlspecialchars($catSlugX) ?>/<?= htmlspecialchars($svSlug) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>
  <?php endforeach; endforeach; ?>

  <!-- 攻略文總覽 + 各 published 攻略文 -->
  <?php
    $guideRows = [];
    try {
        $guideRows = $db->query("SELECT slug, COALESCE(updated_at, published_at) AS modified FROM guides WHERE status='published' ORDER BY published_at DESC")->fetchAll();
    } catch (\Throwable $e) { $guideRows = []; /* 表未建時略過 */ }
    if ($guideRows):
  ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/guide</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php foreach ($guideRows as $gr):
        $gMod = $gr['modified'] ? date('Y-m-d', strtotime($gr['modified'])) : $today;
  ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/guide/<?= htmlspecialchars($gr['slug']) ?></loc>
    <lastmod><?= $gMod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; endif; ?>

  <!-- 各客戶行銷頁（所有客戶都有；有 mini-site 的客戶 mini-site 在下方獨立列） -->
  <?php foreach ($clients as $cl):
      $sub = $cl['subdomain'] ?: $cl['slug'];
      $lastmod = date('Y-m-d', strtotime($cl['updated_at']));
  ?>
  <url>
    <loc><?= htmlspecialchars($baseUrl) ?>/store/<?= htmlspecialchars($sub) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.9</priority>
  </url>
  <?php endforeach; ?>

<?php endif; /* !$subOnly */ ?>

  <!-- 啟用 mini-site 的客戶才列子網域頁面 -->
  <?php foreach ($clients as $cl):
      if (!$cl['has_minisite']) continue;
      $sub = $cl['subdomain'] ?: $cl['slug'];
      $lastmod = date('Y-m-d', strtotime($cl['updated_at']));
      $miniBase = (IS_LOCAL || IS_STAGING) ? BASE_URL . '/site' : 'https://' . $sub . '.' . MINISITE_DOMAIN;
      $pages = [
          ['', '0.9', 'weekly'],
          ['services', '0.8', 'monthly'],
          ['cases', '0.7', 'monthly'],
          ['testimonials', '0.6', 'monthly'],
      ];
      foreach ($pages as [$p, $prio, $freq]):
          $url = (IS_LOCAL || IS_STAGING)
              ? $miniBase . '/' . ($p ? $p . '.php' : 'index.php') . '?sub=' . urlencode($sub)
              : $miniBase . '/' . $p;
  ?>
  <url>
    <loc><?= htmlspecialchars($url) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq><?= $freq ?></changefreq>
    <priority><?= $prio ?></priority>
  </url>
  <?php endforeach; ?>

  <?php
  // 該客戶的服務詳細頁 + 案例詳細頁（旭森風 detail URL）— 每筆獨立
  // 注意 $clients query 沒撈 id（只撈 subdomain/slug/has_minisite/updated_at），改撈 by slug 對應
  $cidStmt = $db->prepare("SELECT id FROM clients WHERE subdomain=? OR slug=? LIMIT 1");
  $cidStmt->execute([$sub, $sub]);
  $cidRow = $cidStmt->fetch();
  if ($cidRow) {
      // 服務詳細頁
      $svcStmt = $db->prepare("SELECT slug FROM services WHERE client_id=? AND is_active=1 AND slug IS NOT NULL AND slug!='' ORDER BY sort_order,id");
      $svcStmt->execute([$cidRow['id']]);
      foreach ($svcStmt->fetchAll() as $svcRow) {
          $svcSlug = $svcRow['slug'];
          $detailUrl = (IS_LOCAL || IS_STAGING)
              ? $miniBase . '/service_detail.php?sub=' . urlencode($sub) . '&svc_slug=' . urlencode($svcSlug)
              : $miniBase . '/services/' . rawurlencode($svcSlug);
  ?>
  <url>
    <loc><?= htmlspecialchars($detailUrl) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.75</priority>
  </url>
  <?php }
      // 案例詳細頁
      $caseStmt = $db->prepare("SELECT slug FROM cases WHERE client_id=? AND is_active=1 AND slug IS NOT NULL AND slug!='' ORDER BY sort_order,id");
      $caseStmt->execute([$cidRow['id']]);
      foreach ($caseStmt->fetchAll() as $caseRow) {
          $caseSlug = $caseRow['slug'];
          $caseUrl = (IS_LOCAL || IS_STAGING)
              ? $miniBase . '/case_detail.php?sub=' . urlencode($sub) . '&case_slug=' . urlencode($caseSlug)
              : $miniBase . '/cases/' . rawurlencode($caseSlug);
  ?>
  <url>
    <loc><?= htmlspecialchars($caseUrl) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.65</priority>
  </url>
  <?php }
      // 案例地區頁（/cases/taichung|changhua）— 只在案例橫跨 2+ 地區時收錄，避免與 /cases 重複
      $caseLocStmt = $db->prepare("SELECT location FROM cases WHERE client_id=? AND is_active=1");
      $caseLocStmt->execute([$cidRow['id']]);
      $caseLocs = array_map(fn($r) => ['location' => $r['location']], $caseLocStmt->fetchAll());
      $caseRegions = caseRegionsPresent($caseLocs);
      if (count($caseRegions) > 1) {
          foreach ($caseRegions as $rSlug) {
              $regionUrl = (IS_LOCAL || IS_STAGING)
                  ? $miniBase . '/cases.php?sub=' . urlencode($sub) . '&region=' . urlencode($rSlug)
                  : $miniBase . '/cases/' . $rSlug;
  ?>
  <url>
    <loc><?= htmlspecialchars($regionUrl) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php     }
      }
      // 專欄文章（Phase 7：/column/{slug}）
      try {
          $artStmt = $db->prepare("SELECT slug, COALESCE(updated_at, created_at) AS modified FROM articles WHERE client_id=? AND is_active=1 AND slug IS NOT NULL AND slug!='' ORDER BY id");
          $artStmt->execute([$cidRow['id']]);
          $artRows = $artStmt->fetchAll();
      } catch (PDOException $e) { $artRows = []; /* articles 表可能還沒建 */ }
      if ($artRows) {
          // 專欄列表頁
          $colListUrl = (IS_LOCAL || IS_STAGING)
              ? $miniBase . '/articles.php?sub=' . urlencode($sub)
              : $miniBase . '/column';
  ?>
  <url>
    <loc><?= htmlspecialchars($colListUrl) ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php
          // 各文章
          foreach ($artRows as $a) {
              $aMod = date('Y-m-d', strtotime($a['modified']));
              $aUrl = (IS_LOCAL || IS_STAGING)
                  ? $miniBase . '/article_detail.php?sub=' . urlencode($sub) . '&slug=' . urlencode($a['slug'])
                  : $miniBase . '/column/' . rawurlencode($a['slug']);
  ?>
  <url>
    <loc><?= htmlspecialchars($aUrl) ?></loc>
    <lastmod><?= $aMod ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.55</priority>
  </url>
  <?php } } } ?>
  <?php endforeach; ?>

</urlset>
