<?php
// admin/forms/form-pricing.php — pricing block 編輯表單
$items    = $blockData['items']    ?? [];
$currency = $blockData['currency'] ?? 'TWD';
?>
<div class="form-group">
  <label>區塊標題</label>
  <input type="text" name="title" value="<?= h($blockData['title'] ?? '價目表') ?>"
    class="form-control" placeholder="例：價目表 / 服務方案">
</div>

<div class="form-group">
  <label>幣別</label>
  <select name="currency" class="form-control" style="max-width:200px;">
    <option value="TWD" <?= $currency === 'TWD' ? 'selected' : '' ?>>NT$ (台幣)</option>
    <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>$ (美元)</option>
  </select>
</div>

<h3 style="margin:24px 0 12px; padding-top:18px; border-top:1px solid var(--border);">方案</h3>
<p style="color:var(--muted); font-size:.85rem; margin-bottom:14px;">建議 2-4 個方案，可標記其中一個為「推薦」會放大顯示。</p>

<div id="items-container">
  <?php foreach ($items as $i => $item): ?>
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">方案 #<?= $i+1 ?></strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div style="display:grid; grid-template-columns:1fr 140px 100px; gap:12px;">
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">方案名稱 *</label>
        <input type="text" name="items[<?= $i ?>][name]" value="<?= h($item['name'] ?? '') ?>" class="form-control" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">價格</label>
        <input type="text" name="items[<?= $i ?>][price]" value="<?= h($item['price'] ?? '') ?>" class="form-control" placeholder="38000">
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">單位</label>
        <input type="text" name="items[<?= $i ?>][unit]" value="<?= h($item['unit'] ?? '') ?>" class="form-control" placeholder="/ 整車">
      </div>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">特色（一行一個）</label>
      <textarea name="items[<?= $i ?>][features_text]" class="form-control" rows="4" placeholder="XPEL Ultimate Plus 漆面保護膜&#10;10 年原廠保固&#10;無塵施工環境"><?= h(implode("\n", $item['features'] ?? [])) ?></textarea>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">
        <input type="checkbox" name="items[<?= $i ?>][highlight]" value="1" <?= !empty($item['highlight']) ? 'checked' : '' ?>>
        ⭐ 標記為「推薦」（放大 + 橘色邊框）
      </label>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<button type="button" class="btn btn-ghost" onclick="addPricingItem()">+ 新增方案</button>

<template id="pricing-item-template">
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">方案 #__N__</strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div style="display:grid; grid-template-columns:1fr 140px 100px; gap:12px;">
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">方案名稱 *</label>
        <input type="text" name="items[__I__][name]" value="" class="form-control" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">價格</label>
        <input type="text" name="items[__I__][price]" value="" class="form-control" placeholder="38000">
      </div>
      <div class="form-group" style="margin:0;">
        <label style="font-size:.8rem;">單位</label>
        <input type="text" name="items[__I__][unit]" value="" class="form-control" placeholder="/ 整車">
      </div>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">特色（一行一個）</label>
      <textarea name="items[__I__][features_text]" class="form-control" rows="4"></textarea>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">
        <input type="checkbox" name="items[__I__][highlight]" value="1">
        ⭐ 標記為「推薦」
      </label>
    </div>
  </div>
</template>

<script>
function addPricingItem() {
  const tpl = document.getElementById('pricing-item-template').innerHTML;
  const container = document.getElementById('items-container');
  const i = container.children.length;
  container.insertAdjacentHTML('beforeend', tpl.replace(/__I__/g, i).replace(/__N__/g, i+1));
}
function removeItem(btn) {
  if (confirm('確定刪除此項？')) btn.closest('.item-row').remove();
}
</script>
