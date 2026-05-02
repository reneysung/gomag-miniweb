<?php
/**
 * main/layout_foot.php
 * 主站共用 Footer
 */
?>
</main>

<footer style="background:var(--m-primary); color:rgba(255,255,255,.7); padding:40px 0 20px; margin-top:60px;">
  <div class="m-container">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:30px; padding-bottom:30px; border-bottom:1px solid rgba(255,255,255,.1);">

      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
          <span style="width:32px;height:32px;background:var(--m-accent);color:var(--m-primary);border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:900">🏪</span>
          <span style="font-weight:900; color:#fff; font-size:1.1rem;">店家好口碑</span>
        </div>
        <p style="font-size:.85rem; line-height:1.6;">全台在地店家平台，匯集優質商家，提供完整服務資訊與真實口碑。</p>
      </div>

      <div>
        <h4 style="color:#fff; font-size:.95rem; margin-bottom:12px;">瀏覽</h4>
        <ul style="list-style:none; font-size:.85rem; line-height:2;">
          <li><a href="<?= BASE_URL ?>/" style="color:rgba(255,255,255,.7);">首頁</a></li>
          <li><a href="<?= BASE_URL ?>/category.php" style="color:rgba(255,255,255,.7);">所有分類</a></li>
          <li><a href="<?= BASE_URL ?>/search.php" style="color:rgba(255,255,255,.7);">搜尋店家</a></li>
        </ul>
      </div>

      <div>
        <h4 style="color:#fff; font-size:.95rem; margin-bottom:12px;">店家專區</h4>
        <ul style="list-style:none; font-size:.85rem; line-height:2;">
          <li><a href="<?= BASE_URL ?>/admin/" style="color:rgba(255,255,255,.7);">店家後台登入</a></li>
        </ul>
      </div>

    </div>

    <div style="text-align:center; padding-top:20px; font-size:.8rem;">
      © <?= date('Y') ?> 店家好口碑 · 全台在地店家平台 · gomag.com.tw
    </div>
  </div>
</footer>

</body>
</html>
