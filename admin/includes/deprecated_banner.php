<?php
/**
 * admin/includes/deprecated_banner.php
 * 在舊 admin 頁面（services/cases/faqs）顯示「已被新區塊管理取代」提示
 * 用法：在 layout_head.php require 之後 include 此檔
 */
?>
<div style="background:linear-gradient(135deg,#FFF8F4,#FFE8DF); border:1.5px solid #FF5A36; border-radius:12px; padding:18px 22px; margin-bottom:20px; display:flex; align-items:center; gap:18px; box-shadow:0 4px 12px rgba(255,90,54,.12);">
  <div style="font-size:2.2rem; flex-shrink:0;">📦</div>
  <div style="flex:1; min-width:0;">
    <div style="font-weight:800; font-size:1.05rem; color:#191919; margin-bottom:4px;">
      此功能已被「📦 區塊管理」取代
    </div>
    <div style="font-size:.85rem; color:#4A4A4A; line-height:1.55;">
      建議改用統一的「區塊管理」介面，整合服務、菜單、案例、價目表、FAQ 五種區塊類型，並支援圖片直接上傳。
      <br>
      （此頁仍可使用 — 編輯後請至「<a href="<?= BASE_URL ?>/admin/pages/store_blocks.php" style="color:#FF5A36; font-weight:700;">區塊管理</a>」按「重新同步」反映到前台）
    </div>
  </div>
  <a href="<?= BASE_URL ?>/admin/pages/store_blocks.php"
     style="background:#FF5A36; color:#fff; padding:11px 22px; border-radius:100px; font-size:.85rem; font-weight:700; text-decoration:none; flex-shrink:0; white-space:nowrap;">
    前往 →
  </a>
</div>
