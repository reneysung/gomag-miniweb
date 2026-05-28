<?php
// includes/minisite_template_loader.php
// ─── 小官網視覺模板載入器（跟 store_template 脫鉤）─────
// 對應 site/index.php 跟其他 site/*.php 的入口
//
// 用法（site/index.php 內）:
//   require_once __DIR__ . '/../includes/minisite_template_loader.php';
//   $mtpl = $client['minisite_template'] ?? '_default';
//   $renderPath = minisiteTemplateRenderPath($mtpl, 'home');
//   if ($renderPath) {
//       require $renderPath;
//       exit;
//   }
//   // 否則 fallback 走原本 industry-based 邏輯

declare(strict_types=1);

const MINISITE_TEMPLATE_DIR = __DIR__ . '/../templates/minisite';

/**
 * @return array<string,array{slug:string, name:string, description:string, industries:array, thumbnail:string, status:string}>
 */
function minisiteTemplateList(): array {
    $out = [];
    if (!is_dir(MINISITE_TEMPLATE_DIR)) return $out;
    foreach (scandir(MINISITE_TEMPLATE_DIR) as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $path = MINISITE_TEMPLATE_DIR . '/' . $dir;
        if (!is_dir($path)) continue;
        $meta = minisiteTemplateGet($dir);
        if ($meta) $out[$dir] = $meta;
    }
    return $out;
}

function minisiteTemplateGet(string $slug): ?array {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $path = MINISITE_TEMPLATE_DIR . '/' . $slug;
    if (!is_dir($path)) {
        if ($slug !== '_default') return minisiteTemplateGet('_default');
        return null;
    }
    $metaFile = $path . '/meta.json';
    $meta = is_file($metaFile)
        ? (json_decode((string)file_get_contents($metaFile), true) ?: [])
        : [];
    return [
        'slug'        => $slug,
        'name'        => $meta['name']        ?? $slug,
        'description' => $meta['description'] ?? '',
        'industries'  => $meta['industries']  ?? [],
        'thumbnail'   => $meta['thumbnail']   ?? 'thumbnail.jpg',
        'status'      => $meta['status']      ?? 'active',
    ];
}

/**
 * 取該模板特定 page 的 render path（'home' / 'cases' / 'services' / 'testimonials' / 'contact'）
 * 找不到回空字串（呼叫端 fallback 到原 industry-based）
 */
function minisiteTemplateRenderPath(string $slug, string $page = 'home'): string {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $page = preg_replace('/[^a-z0-9_-]/i', '', $page);
    $path = MINISITE_TEMPLATE_DIR . "/{$slug}/{$page}.php";
    return is_file($path) ? $path : '';
}

function minisiteTemplateHasRender(string $slug, string $page = 'home'): bool {
    return minisiteTemplateRenderPath($slug, $page) !== '';
}

function minisiteTemplateThumbnailUrl(string $slug): string {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $meta = minisiteTemplateGet($slug);
    if (!$meta) return '';
    $file = MINISITE_TEMPLATE_DIR . '/' . $slug . '/' . $meta['thumbnail'];
    if (!is_file($file)) return BASE_URL . '/assets/img/template-placeholder.svg';
    return BASE_URL . '/templates/minisite/' . $slug . '/' . $meta['thumbnail'];
}

function minisiteTemplateRecommend(?string $categoryName): string {
    if (!$categoryName) return '_default';
    foreach (minisiteTemplateList() as $slug => $meta) {
        if ($slug === '_default') continue;
        if (in_array($categoryName, $meta['industries'], true)) return $slug;
    }
    return '_default';
}

/**
 * 判斷該模板是否為 single-page 模式（meta.json 內 single_page=true）
 */
function minisiteTemplateIsSinglePage(string $slug): bool {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $metaFile = MINISITE_TEMPLATE_DIR . '/' . $slug . '/meta.json';
    if (!is_file($metaFile)) return false;
    $meta = json_decode((string)file_get_contents($metaFile), true) ?: [];
    return !empty($meta['single_page']);
}

/**
 * 在 sub-page (site/services.php / cases.php / 等) 開頭呼叫
 * 若客戶用的模板是 single-page mode，則 302 redirect 回首頁
 */
function minisiteRedirectSubpageIfSinglePage(string $sub, array $client): void {
    $mtpl = $client['minisite_template'] ?? '_default';
    if ($mtpl === '_default') return;
    if (!minisiteTemplateIsSinglePage($mtpl)) return;
    // 蓋掉前面 sub-page 設的 Cache-Control: public, max-age=300（避免 LiteSpeed cache 住 200）
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    $homeUrl = (defined('IS_LOCAL') && (IS_LOCAL || IS_STAGING))
        ? BASE_URL . '/site/index.php?sub=' . urlencode($sub)
        : 'https://' . $sub . '.' . MINISITE_DOMAIN . '/';
    header('Location: ' . $homeUrl, true, 302);
    exit;
}
