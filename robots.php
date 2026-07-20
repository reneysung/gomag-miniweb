<?php
// ============================================================
// robots.php  ─  動態產生 robots.txt
// 網址：/miniweb/robots.php 或 /robots.txt（需 .htaccess rewrite）
// ============================================================
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=UTF-8');

// Sitemap URL 永遠指向「同一個 host」的 sitemap.xml
// 主站、各 mini-site 子網域各自看到自己的 sitemap（sitemap.php 依 host 過濾）
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? 'www.gomag.com.tw';
$sitemapUrl = $proto . '://' . $host . '/sitemap.xml';
?>
User-agent: *
Allow: /

# 禁止爬後台
Disallow: /admin/
Disallow: /includes/
Disallow: /install.php

# 優惠券領取 API（POST 端點，GET 回 400；從未收錄，擋掉即從 GSC「其他 4xx」消失）
Disallow: /coupon_claim.php

# 註：2022 hack 殘留路徑（/edm/、/shopx.php、/coupon_show.php）已全部 404，
# 不再 Disallow，讓 Google 重爬看到 404 → 自然刪舊索引
# （之前 Disallow 反而擋 Google 看 404，造成「已建立索引但遭 robots 封鎖」）

# Sitemap
Sitemap: <?= $sitemapUrl ?>

