<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/front_functions.php';
$sub = getSubdomain();
$site = loadSiteData($sub ?: 'xusen');
echo '<pre>';
print_r(array_keys($site));
echo count($site['cases'] ?? []) . ' cases';
echo '</pre>';
