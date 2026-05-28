<?php
// includes/template_loader.php
// ─── 視覺模板系統載入器（Phase 3）─────────────────────
// 設計：
//   - `_default` 模板 = 維持 store.php 既有 HTML（向後相容、零風險）
//   - 其他模板 = 提供 render.php，由 store.php 在資料準備完後 delegate
//   - 模板 metadata 用 meta.json 描述（名稱／適用產業／縮圖）
//
// 用法（store.php 內）:
//   require_once __DIR__ . '/includes/template_loader.php';
//   $tpl = templateGet($client['store_template'] ?? '_default');
//   if (templateHasRender($tpl['slug'])) {
//       templateRender($tpl['slug']);  // 載入 render.php 並結束
//       exit;
//   }
//   // else fall through to default HTML
// ─────────────────────────────────────────────────────

declare(strict_types=1);

const STORE_TEMPLATE_DIR = __DIR__ . '/../templates/store';

/**
 * 列出所有可用模板（含 metadata）
 * @return array<string,array{slug:string, name:string, description:string, industries:array, thumbnail:string, status:string, has_render:bool}>
 */
function templateList(): array {
    $out = [];
    if (!is_dir(STORE_TEMPLATE_DIR)) return $out;

    foreach (scandir(STORE_TEMPLATE_DIR) as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $path = STORE_TEMPLATE_DIR . '/' . $dir;
        if (!is_dir($path)) continue;

        $meta = templateGet($dir);
        if ($meta) $out[$dir] = $meta;
    }
    return $out;
}

/**
 * 取單一模板 metadata，找不到回 fallback `_default`
 */
function templateGet(string $slug): ?array {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $path = STORE_TEMPLATE_DIR . '/' . $slug;
    if (!is_dir($path)) {
        if ($slug !== '_default') return templateGet('_default');
        return null;  // 連 _default 都沒有 = 系統壞了
    }

    $metaFile = $path . '/meta.json';
    $meta = is_file($metaFile)
        ? (json_decode((string)file_get_contents($metaFile), true) ?: [])
        : [];

    return [
        'slug'        => $slug,
        'name'        => $meta['name']        ?? $slug,
        'description' => $meta['description'] ?? '',
        'industries'  => $meta['industries']  ?? [],   // ['伴手禮','和菓子',...] 用來推薦
        'thumbnail'   => $meta['thumbnail']   ?? 'thumbnail.jpg',
        'status'      => $meta['status']      ?? 'active',  // 'active' | 'beta' | 'dev'
        'has_render'  => is_file($path . '/render.php'),
    ];
}

function templateHasRender(string $slug): bool {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    return is_file(STORE_TEMPLATE_DIR . '/' . $slug . '/render.php');
}

/**
 * 回傳該模板 render.php 的絕對路徑（or 空字串）
 * 注意：呼叫端應直接 require 此路徑（不要透過 function 包一層，否則變數作用域會切斷）
 */
function templateRenderPath(string $slug): string {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $path = STORE_TEMPLATE_DIR . '/' . $slug . '/render.php';
    return is_file($path) ? $path : '';
}

/**
 * @deprecated 用 templateRenderPath + require 取代（function scope 會吃掉變數）
 */
function templateRender(string $slug): void {
    $path = templateRenderPath($slug);
    if ($path) require $path;
}

/**
 * 取該模板的縮圖 URL（後台 UI 用）
 */
function templateThumbnailUrl(string $slug): string {
    $slug = preg_replace('/[^a-z0-9_-]/i', '', $slug);
    $meta = templateGet($slug);
    if (!$meta) return '';
    $file = STORE_TEMPLATE_DIR . '/' . $slug . '/' . $meta['thumbnail'];
    if (!is_file($file)) return BASE_URL . '/assets/img/template-placeholder.svg';
    return BASE_URL . '/templates/store/' . $slug . '/' . $meta['thumbnail'];
}

/**
 * 推薦最合適模板（根據客戶分類）
 * @param string|null $categoryName 例如「伴手禮」「和菓子」
 * @return string slug
 */
function templateRecommend(?string $categoryName): string {
    if (!$categoryName) return '_default';
    foreach (templateList() as $slug => $meta) {
        if ($slug === '_default') continue;
        if (in_array($categoryName, $meta['industries'], true)) return $slug;
    }
    return '_default';
}
