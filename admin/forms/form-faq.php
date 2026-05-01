<?php
// admin/forms/form-faq.php — faq block 編輯表單
$items = $blockData['items'] ?? [];
?>
<div class="form-group">
  <label>區塊標題</label>
  <input type="text" name="title" value="<?= h($blockData['title'] ?? '常見問題') ?>"
    class="form-control" placeholder="例：常見問題 / FAQ">
</div>

<h3 style="margin:24px 0 12px; padding-top:18px; border-top:1px solid var(--border);">問答</h3>
<p style="color:var(--muted); font-size:.85rem; margin-bottom:14px;">
  💡 自動產生 FAQPage 結構化資料，有助 Google 顯示「常見問題」搜尋結果摘要。
</p>

<div id="items-container">
  <?php foreach ($items as $i => $item): ?>
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">問答 #<?= $i+1 ?></strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div class="form-group" style="margin:0;">
      <label style="font-size:.8rem;">問題 *</label>
      <input type="text" name="items[<?= $i ?>][q]" value="<?= h($item['q'] ?? '') ?>"
        class="form-control" required placeholder="例：服務範圍包含哪些區域？">
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">回答 *</label>
      <textarea name="items[<?= $i ?>][a]" class="form-control" rows="4" required placeholder="回答內容，可換行..."><?= h($item['a'] ?? '') ?></textarea>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<button type="button" class="btn btn-ghost" onclick="addFaqItem()">+ 新增問答</button>

<template id="faq-item-template">
  <div class="item-row" style="border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; background:var(--bg);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <strong style="color:var(--muted);">問答 #__N__</strong>
      <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">刪除</button>
    </div>
    <div class="form-group" style="margin:0;">
      <label style="font-size:.8rem;">問題 *</label>
      <input type="text" name="items[__I__][q]" value="" class="form-control" required>
    </div>
    <div class="form-group" style="margin:12px 0 0;">
      <label style="font-size:.8rem;">回答 *</label>
      <textarea name="items[__I__][a]" class="form-control" rows="4" required></textarea>
    </div>
  </div>
</template>

<script>
function addFaqItem() {
  const tpl = document.getElementById('faq-item-template').innerHTML;
  const container = document.getElementById('items-container');
  const i = container.children.length;
  container.insertAdjacentHTML('beforeend', tpl.replace(/__I__/g, i).replace(/__N__/g, i+1));
}
function removeItem(btn) {
  if (confirm('確定刪除此項？')) btn.closest('.item-row').remove();
}
</script>
