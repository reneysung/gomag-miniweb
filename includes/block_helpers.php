<?php
/**
 * includes/block_helpers.php
 *
 * Modular Blocks 系統的 PHP helper API。
 * 任何頁面要渲染店家 blocks 都用這裡的函式，不要直接 SQL。
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

const BLOCK_TYPES = ['service', 'menu', 'portfolio', 'pricing', 'faq'];

/**
 * 取出某店家所有 active blocks（依 sort_order）。
 *
 * @return array<int,array{id:int, type:string, data:array, sort_order:int, is_active:int}>
 */
function getStoreBlocks(int $clientId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, client_id, type, data, sort_order, is_active, created_at, updated_at
        FROM store_blocks
        WHERE client_id = ? AND is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([$clientId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $decoded = json_decode((string)$r['data'], true);
        $r['data'] = is_array($decoded) ? $decoded : [];
    }
    return $rows;
}

/**
 * 取出某分類「建議」的 block types（依 default_sort）。
 * 後台新增 block 下拉選單會用到。
 *
 * @return array<int,array{block_type:string, is_required:int, default_sort:int}>
 */
function getCategoryBlockSuggestions(int $categoryId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT block_type, is_required, default_sort
        FROM category_block_suggestions
        WHERE category_id = ?
        ORDER BY default_sort ASC
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}

/**
 * 渲染單一 block（include 對應的 blocks/{type}.php）。
 * 在 partial 內可用 $blockData / $blockType / $blockId / $blockMeta。
 */
function renderBlock(array $block): void {
    $type = $block['type'] ?? '';
    if (!in_array($type, BLOCK_TYPES, true)) return;

    $partialPath = __DIR__ . '/../blocks/' . $type . '.php';
    if (!is_file($partialPath)) return;

    // 暴露給 partial 的變數
    $blockData = $block['data'] ?? [];
    $blockType = $type;
    $blockId   = (int)($block['id'] ?? 0);
    $blockMeta = $block;

    include $partialPath;
}

/**
 * 渲染某店家全部 blocks。
 */
function renderStoreBlocks(int $clientId): void {
    foreach (getStoreBlocks($clientId) as $block) {
        renderBlock($block);
    }
}

/**
 * 新增 / 更新一個 block（後台用）。
 *
 * @return int  store_blocks.id
 */
function saveStoreBlock(int $clientId, string $type, array $data, int $sortOrder = 0, ?int $blockId = null): int {
    if (!in_array($type, BLOCK_TYPES, true)) {
        throw new InvalidArgumentException("Invalid block type: $type");
    }
    $db = getDB();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($blockId) {
        $stmt = $db->prepare("UPDATE store_blocks SET type=?, data=?, sort_order=? WHERE id=? AND client_id=?");
        $stmt->execute([$type, $json, $sortOrder, $blockId, $clientId]);
        return $blockId;
    }
    $stmt = $db->prepare("INSERT INTO store_blocks (client_id, type, data, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$clientId, $type, $json, $sortOrder]);
    return (int)$db->lastInsertId();
}

function deleteStoreBlock(int $blockId, int $clientId): void {
    $db = getDB();
    // 撈出 data，把區塊內的圖檔一起刪
    $stmt = $db->prepare("SELECT data FROM store_blocks WHERE id=? AND client_id=?");
    $stmt->execute([$blockId, $clientId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['data'])) {
        $data = json_decode($row['data'], true) ?: [];
        foreach (collectBlockImagePaths($data) as $p) {
            if ($p) deleteImage($p);
        }
    }
    $stmt = $db->prepare("DELETE FROM store_blocks WHERE id=? AND client_id=?");
    $stmt->execute([$blockId, $clientId]);
}

/**
 * 從 block data 結構（service / menu / portfolio / pricing）撈出所有圖檔路徑。
 * 用於替換 / 刪除區塊時，連帶清掉硬碟上的孤兒圖檔。
 */
function collectBlockImagePaths(array $data): array {
    $paths = [];
    // service / portfolio / pricing → items[*].image
    if (!empty($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $it) {
            if (!empty($it['image'])) $paths[] = $it['image'];
        }
    }
    // menu → groups[*].items[*].image
    if (!empty($data['groups']) && is_array($data['groups'])) {
        foreach ($data['groups'] as $g) {
            if (!empty($g['items']) && is_array($g['items'])) {
                foreach ($g['items'] as $it) {
                    if (!empty($it['image'])) $paths[] = $it['image'];
                }
            }
        }
    }
    return array_values(array_filter(array_unique($paths)));
}

/**
 * 該 client 是否已遷移到 store_blocks 系統（用於 store.php 雙寫期判斷）。
 */
function clientHasBlocks(int $clientId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT 1 FROM store_blocks WHERE client_id=? AND is_active=1 LIMIT 1");
    $stmt->execute([$clientId]);
    return (bool)$stmt->fetchColumn();
}
