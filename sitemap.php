<?php
// ============================================================
// sitemap.php  ─  動態產生 sitemap.xml（主站 + 行銷頁 + mini-site）
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/front_functions.php';

header('Content-Type: application/xml; charset=UTF-8');

$db = getDB();

// 排除重複客戶（同店多筆 → 已 301 到主檔）— 集中於 getDuplicateSkipSlugs()
$dupSkip = getDuplicateSkipSlugs();
$ph = implode(',', array_fill(0, count($dupSkip), '?'));
$stmt = $db->prepare("SELECT subdomain, slug, has_minisite, updated_at FROM clients WHERE is_active=1 AND slug NOT IN ($ph) ORDER BY id");
$stmt->execute($dupSkip);
$clients = $stmt->fetchAll();
$cats = $db->query('SELECT slug FROM categories WHERE is_active=1')->fetchAll();

$baseUrl = (IS_LOCAL || IS_STAGING) ? BASE_URL : 'https://www.gomag.com.tw';
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

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
    $cityNameToSlug = [
        '台南市' => 'tainan', '高雄市' => 'kaohsiung', '嘉義市' => 'chiayi',
        '台中市' => 'taichung', '台北市' => 'taipei', '新北市' => 'newtaipei',
        '桃園市' => 'taoyuan', '台東縣' => 'taitung', '屏東縣' => 'pingtung',
        '新竹市' => 'hsinchu', '宜蘭縣' => 'yilan', '花蓮縣' => 'hualien',
    ];
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

  <!-- 各客戶行銷頁（所有客戶都有）-->
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
  <?php endforeach; endforeach; ?>

</urlset>
