<?php
// admin/forms/form-menu.php — menu block 編輯表單（多群組 + 多項目）
$groups = $blockData['groups'] ?? [];
?>
<div class="form-group">
  <label>區塊標題</label>
  <input type="text" name="title" value="<?= h($blockData['title'] ?? '招牌菜單') ?>"
    class="form-control" placeholder="例：招牌菜單 / 完整菜單">
</div>

<h3 style="margin:24px 0 12px; padding-top:18px; border-top:1px solid var(--border);">菜單群組</h3>
<p style="color:var(--muted); font-size:.85rem; margin-bottom:14px;">
  💡 每個群組（前菜、主餐、甜點...）可包含多個品項。
</p>

<div id="groups-container">
  <?php foreach ($groups as $gi => $group):
    $gItems = $group['items'] ?? [];
  ?>
  <div class="group-row" data-gi="<?= $gi ?>" style="border:2px solid var(--primary); border-radius:var(--radius); padding:18px; margin-bottom:18px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <input type="text" name="groups[<?= $gi ?>][name]" value="<?= h($group['name'] ?? '') ?>"
        placeholder="群組名稱（例：前菜）" required
        style="flex:1; font-size:1.1rem; font-weight:700; padding:8px 12px; border:1px solid var(--border); border-radius:6px; margin-right:12px;">
      <button type="button" class="btn btn-sm btn-danger" onclick="removeGroup(this)">刪除群組</button>
    </div>
    <div class="menu-items" data-gi="<?= $gi ?>">
      <?php foreach ($gItems as $ii => $item): ?>
      <div class="item-row" style="border:1px solid var(--border); border-radius:6px; padding:12px; margin-bottom:8px; background:var(--bg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <strong style="color:var(--muted); font-size:.85rem;">品項 #<?= $ii+1 ?></strong>
          <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">×</button>
        </div>
        <div style="display:grid; grid-template-columns:1fr 100px 100px; gap:8px;">
          <input type="text" name="groups[<?= $gi ?>][items][<?= $ii ?>][name]" value="<?= h($item['name'] ?? '') ?>" placeholder="品項名稱" class="form-control" required>
          <input type="number" name="groups[<?= $gi ?>][items][<?= $ii ?>][price]" value="<?= h($item['price'] ?? '') ?>" placeholder="價格" class="form-control">
          <input type="text" name="groups[<?= $gi ?>][items][<?= $ii ?>][tag]" value="<?= h($item['tag'] ?? '') ?>" placeholder="標籤（招牌）" class="form-control">
        </div>
        <input type="text" name="groups[<?= $gi ?>][items][<?= $ii ?>][desc]" value="<?= h($item['desc'] ?? '') ?>" placeholder="說明（可選）" class="form-control" style="margin-top:8px;">
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-ghost" onclick="addMenuItem(<?= $gi ?>)">+ 新增品項</button>
  </div>
  <?php endforeach; ?>
</div>

<button type="button" class="btn btn-primary" onclick="addGroup()">+ 新增群組</button>

<template id="menu-group-template">
  <div class="group-row" data-gi="__GI__" style="border:2px solid var(--primary); border-radius:var(--radius); padding:18px; margin-bottom:18px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <input type="text" name="groups[__GI__][name]" value="" placeholder="群組名稱（例：前菜）" required style="flex:1; font-size:1.1rem; font-weight:700; padding:8px 12px; border:1px solid var(--border); border-radius:6px; margin-right:12px;">
      <button type="button" class="btn btn-sm btn-danger" onclick="removeGroup(this)">刪除群組</button>
    </div>
    <div class="menu-items" data-gi="__GI__"></div>
    <button type="button" class="btn btn-sm btn-ghost" onclick="addMenuItem(__GI__)">+ 新增品項</button>
  </div>
</template>

<template id="menu-item-template">
  <div class="item-row" style="border:1px solid var(--border); border-radius:6px; padding:12px; margin-bottom:8px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
      <strong style="color:var(--muted); font-size:.85rem;">品項 #__N__</strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">×</button>
    </div>
    <div style="display:grid; grid-template-columns:1fr 100px 100px; gap:8px;">
      <input type="text" name="groups[__GI__][items][__II__][name]" value="" placeholder="品項名稱" class="form-control" required>
      <input type="number" name="groups[__GI__][items][__II__][price]" value="" placeholder="價格" class="form-control">
      <input type="text" name="groups[__GI__][items][__II__][tag]" value="" placeholder="標籤" class="form-control">
    </div>
    <input type="text" name="groups[__GI__][items][__II__][desc]" value="" placeholder="說明（可選）" class="form-control" style="margin-top:8px;">
  </div>
</template>

<script>
function addGroup() {
  const tpl = document.getElementById('menu-group-template').innerHTML;
  const container = document.getElementById('groups-container');
  const gi = container.querySelectorAll('.group-row').length;
  container.insertAdjacentHTML('beforeend', tpl.replace(/__GI__/g, gi));
}
function addMenuItem(gi) {
  const tpl = document.getElementById('menu-item-template').innerHTML;
  const container = document.querySelector(`.menu-items[data-gi="${gi}"]`);
  const ii = container.children.length;
  container.insertAdjacentHTML('beforeend', tpl.replace(/__GI__/g, gi).replace(/__II__/g, ii).replace(/__N__/g, ii+1));
}
function removeGroup(btn) {
  if (confirm('確定刪除整個群組？所有品項都會清掉。')) btn.closest('.group-row').remove();
}
function removeItem(btn) {
  btn.closest('.item-row').remove();
}
</script>
