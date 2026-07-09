<?php
define('GEO_CONTENT_SECRET', 'wcoeDCI3keu6ZziEnobpLJIJH5WRDgs1Q9KxBuN9ee0');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'POST only']));
}

$key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? '');
if (!$key || !hash_equals(GEO_CONTENT_SECRET, $key)) {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid JSON body']));
}

$citySlug  = trim($body['city_slug']     ?? '');
$catSlug   = trim($body['category_slug'] ?? '');
$svcSlug   = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($body['service_slug'] ?? '')));
$svcName   = trim($body['service_name']  ?? '');
$introHtml = trim($body['intro_html']    ?? '');
$faqs      = is_array($body['faqs'] ?? null) ? $body['faqs'] : [];
$metaTitle = trim($body['meta_title']    ?? '');
$metaDesc  = trim($body['meta_desc']     ?? '');
$isActive  = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 0;

if (!$citySlug || !$catSlug) {
    http_response_code(400);
    exit(json_encode(['error' => 'city_slug and category_slug are required']));
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$catSlug]);
$catId = (int)$stmt->fetchColumn();
if (!$catId) {
    http_response_code(422);
    exit(json_encode(['error' => "Category not found: $catSlug"]));
}

$cleanFaqs = [];
foreach ($faqs as $f) {
    $q = trim($f['q'] ?? '');
    $a = trim($f['a'] ?? '');
    if ($q !== '') $cleanFaqs[] = ['q' => $q, 'a' => $a];
}
$faqsJson = json_encode($cleanFaqs, JSON_UNESCAPED_UNICODE);

$check = $db->prepare("SELECT id FROM geo_category_pages WHERE city_slug=? AND category_id=? AND service_slug=?");
$check->execute([$citySlug, $catId, $svcSlug]);
$existId = (int)$check->fetchColumn();

if ($existId) {
    $up = $db->prepare("UPDATE geo_category_pages SET service_name=?, intro_html=?, faqs=?, meta_title=?, meta_desc=?, is_active=? WHERE id=?");
    $up->execute([$svcName, $introHtml, $faqsJson, $metaTitle, $metaDesc, $isActive, $existId]);
    exit(json_encode(['success' => true, 'action' => 'update', 'id' => $existId]));
} else {
    $ins = $db->prepare("INSERT INTO geo_category_pages (city_slug, category_id, service_slug, service_name, intro_html, faqs, meta_title, meta_desc, is_active) VALUES (?,?,?,?,?,?,?,?,?)");
    $ins->execute([$citySlug, $catId, $svcSlug, $svcName, $introHtml, $faqsJson, $metaTitle, $metaDesc, $isActive]);
    exit(json_encode(['success' => true, 'action' => 'insert', 'id' => (int)$db->lastInsertId()]));
}
