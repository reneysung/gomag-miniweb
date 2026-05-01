<?php
/**
 * blocks/_block_helpers.php — 共用 helper（被各 block partial include）
 *
 * 由 includes/block_helpers.php::renderBlock() 在 require 此檔之前
 * 設定下列變數：
 *   $blockData : array  — JSON decode 後的 data
 *   $blockType : string — service|menu|portfolio|pricing|faq
 *   $blockId   : int    — store_blocks.id
 *   $blockMeta : array  — 完整 row（含 sort_order、is_active 等）
 */

// 安全 escape — 短名 g() 在 helpers.php 已定義為 h()
if (!function_exists('h')) {
    function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// 檢查必填
if (!isset($blockData) || !is_array($blockData)) {
    return; // 安靜失敗，避免破壞整頁
}
