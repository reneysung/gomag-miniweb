<?php
// ============================================================
// robots.php  ─  動態產生 robots.txt
// 網址：/miniweb/robots.php 或 /robots.txt（需 .htaccess rewrite）
// ============================================================
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=UTF-8');

$sitemapUrl = IS_LOCAL
    ? BASE_URL . '/sitemap.php'
    : 'https://gomag.com.tw/sitemap.xml';
?>
User-agent: *
Allow: /

# 禁止爬後台
Disallow: /admin/
Disallow: /includes/
Disallow: /install.php

# Sitemap
Sitemap: <?= $sitemapUrl ?>

