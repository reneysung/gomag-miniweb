<?php
// admin/forms/form-service.php — service block 編輯表單
// 由 store_block_edit.php include，可用變數：$blockData (array)
$items = $blockData['items'] ?? [];
?>
<div class="form-group">
  <label>區塊標題</label>
  <input type="text" name="title" value="<?= h($blockData['title'] ?? '核心服務') ?>"
    class="form-control" placeholder="例：核心服務 / 我們的服務">
</div>

<h3 style="margin:24px 0 12px; padding-top:18px; border-top:1px solid var(--border);">服務項目</h3>
<p style="color:var(--muted); font-size:.85rem; margin-bottom:14px;">每個服務一筆。可新增多筆。</p>

<div id="items-container">
  <?php foreach ($items as $i => $item): ?>
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">服務 #<?= $i+1 ?></strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div style="display:grid; grid-template-columns:80px 1fr 200px; gap:12px;">
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">圖示</label>
        <input type="text" name="items[<?= $i ?>][icon]" value="<?= h($item['icon'] ?? '') ?>"
          class="form-control" placeholder="🛠️" style="text-align:center; font-size:1.4rem;">
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">服務名稱 *</label>
        <input type="text" name="items[<?= $i ?>][name]" value="<?= h($item['name'] ?? '') ?>"
          class="form-control" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">價格文字</label>
        <input type="text" name="items[<?= $i ?>][price_text]" value="<?= h($item['price_text'] ?? '') ?>"
          class="form-control" placeholder="$300/坪起">
      </div>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">簡短描述</label>
      <textarea name="items[<?= $i ?>][short_desc]" class="form-control" rows="2"><?= h($item['short_desc'] ?? '') ?></textarea>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">圖片</label>
      <?php if (!empty($item['image'])): ?>
      <div style="display:flex; gap:12px; align-items:flex-start; margin-bottom:6px;">
        <img src="<?= h(BASE_URL . '/' . ltrim($item['image'], '/')) ?>"
          style="width:80px; height:80px; object-fit:cover; border-radius:6px; border:1px solid var(--border);">
        <div style="flex:1;">
          <input type="text" name="items[<?= $i ?>][image]" value="<?= h($item['image'] ?? '') ?>"
            class="form-control" placeholder="uploads/services/xxx.jpg" style="font-size:.8rem;">
          <small style="color:var(--muted);">既有路徑（手動編輯或下方上傳新圖取代）</small>
        </div>
      </div>
      <?php else: ?>
      <input type="hidden" name="items[<?= $i ?>][image]" value="">
      <?php endif; ?>
      <input type="file" name="image_items_<?= $i ?>" accept="image/*"
        style="font-size:.8rem;">
      <small style="color:var(--muted);">上傳新圖（jpg/png/webp，最大 5MB）— 留空保持原圖</small>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<button type="button" class="btn btn-ghost" onclick="addServiceItem()">+ 新增服務</button>

<template id="service-item-template">
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">服務 #__N__</strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div style="display:grid; grid-template-columns:80px 1fr 200px; gap:12px;">
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">圖示</label>
        <input type="text" name="items[__I__][icon]" value="" class="form-control" placeholder="🛠️" style="text-align:center; font-size:1.4rem;">
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">服務名稱 *</label>
        <input type="text" name="items[__I__][name]" value="" class="form-control" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">價格文字</label>
        <input type="text" name="items[__I__][price_text]" value="" class="form-control" placeholder="$300/坪起">
      </div>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">簡短描述</label>
      <textarea name="items[__I__][short_desc]" class="form-control" rows="2"></textarea>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">圖片</label>
      <input type="hidden" name="items[__I__][image]" value="">
      <input type="file" name="image_items___I__" accept="image/*" style="font-size:.8rem;">
      <small style="color:var(--muted);">上傳新圖（jpg/png/webp，最大 5MB）</small>
    </div>
  </div>
</template>

<script>
function addServiceItem() {
  const tpl = document.getElementById('service-item-template').innerHTML;
  const container = document.getElementById('items-container');
  const i = container.children.length;
  container.insertAdjacentHTML('beforeend', tpl.replace(/__I__/g, i).replace(/__N__/g, i+1));
}
function removeItem(btn) {
  if (confirm('確定刪除此項？')) btn.closest('.item-row').remove();
}
</script>
