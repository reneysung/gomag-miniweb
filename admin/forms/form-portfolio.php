<?php
// admin/forms/form-portfolio.php — portfolio block 編輯表單
$items  = $blockData['items']  ?? [];
$layout = $blockData['layout'] ?? 'grid';
?>
<div class="form-group">
  <label>區塊標題</label>
  <input type="text" name="title" value="<?= h($blockData['title'] ?? '作品集') ?>"
    class="form-control" placeholder="例：作品集 / 施工案例 / 用餐氛圍">
</div>

<input type="hidden" name="layout" value="grid">

<h3 style="margin:24px 0 12px; padding-top:18px; border-top:1px solid var(--border);">作品 / 案例</h3>
<p style="color:var(--muted); font-size:.85rem; margin-bottom:14px;">
  💡 標記「大圖」會佔 2 格寬度（例如左大右 4 小的 Airbnb 排版）。
</p>

<div id="items-container">
  <?php foreach ($items as $i => $item): ?>
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">作品 #<?= $i+1 ?></strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div class="form-group" style="margin:0;">
      <label style="font-size:.8rem;">圖片 *</label>
      <?php if (!empty($item['image'])): ?>
      <div style="display:flex; gap:12px; align-items:flex-start; margin-bottom:6px;">
        <img src="<?= h(BASE_URL . '/' . ltrim($item['image'], '/')) ?>"
          style="width:120px; height:80px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">
        <div style="flex:1;">
          <input type="text" name="items[<?= $i ?>][image]" value="<?= h($item['image']) ?>" class="form-control" style="font-size:.8rem;" required>
          <small style="color:var(--muted);">既有路徑</small>
        </div>
      </div>
      <?php else: ?>
      <input type="text" name="items[<?= $i ?>][image]" value="" class="form-control" placeholder="或貼上路徑" style="margin-bottom:6px;">
      <?php endif; ?>
      <input type="file" name="image_items_<?= $i ?>" accept="image/*" style="font-size:.8rem;">
      <small style="color:var(--muted);">上傳新圖取代</small>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">標題</label>
      <input type="text" name="items[<?= $i ?>][title]" value="<?= h($item['title'] ?? '') ?>" class="form-control">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">描述</label>
      <input type="text" name="items[<?= $i ?>][desc]" value="<?= h($item['desc'] ?? '') ?>" class="form-control">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">標籤（逗號分隔）</label>
      <input type="text" name="items[<?= $i ?>][tags_text]" value="<?= h(implode(',', $item['tags'] ?? [])) ?>" class="form-control" placeholder="XPEL, Tesla, 全車包膜">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">
        <input type="checkbox" name="items[<?= $i ?>][is_large]" value="1" <?= !empty($item['is_large']) ? 'checked' : '' ?>>
        🖼 標記為大圖（佔 2 格寬度）
      </label>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<button type="button" class="btn btn-ghost" onclick="addPortfolioItem()">+ 新增作品</button>

<template id="portfolio-item-template">
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">作品 #__N__</strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div class="form-group" style="margin:0;">
      <label style="font-size:.8rem;">圖片 *</label>
      <input type="text" name="items[__I__][image]" value="" class="form-control" placeholder="路徑或上傳新圖" style="margin-bottom:6px;">
      <input type="file" name="image_items___I__" accept="image/*" style="font-size:.8rem;">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">標題</label>
      <input type="text" name="items[__I__][title]" value="" class="form-control">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">描述</label>
      <input type="text" name="items[__I__][desc]" value="" class="form-control">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">標籤（逗號分隔）</label>
      <input type="text" name="items[__I__][tags_text]" value="" class="form-control" placeholder="XPEL, Tesla">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">
        <input type="checkbox" name="items[__I__][is_large]" value="1">
        🖼 大圖
      </label>
    </div>
  </div>
</template>

<script>
function addPortfolioItem() {
  const tpl = document.getElementById('portfolio-item-template').innerHTML;
  const container = document.getElementById('items-container');
  const i = container.children.length;
  container.insertAdjacentHTML('beforeend', tpl.replace(/__I__/g, i).replace(/__N__/g, i+1));
}
function removeItem(btn) {
  if (confirm('確定刪除此項？')) btn.closest('.item-row').remove();
}
</script>
