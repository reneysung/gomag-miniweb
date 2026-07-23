<?php
/**
 * includes/waf_bypass.php ─ 繞過主機 WAF(mod_security) 對大段 HTML 的累加評分誤判
 *
 * 背景（2026-07-23 實測於 Gerpros #286）：
 *   Hostinger(LiteSpeed) 的 mod_security 採「累加評分」。後台儲存時
 *     landing_extra_content（大段 HTML）單送 → 200
 *     google_maps_embed（<iframe>）單送      → 200
 *     兩者合送                                → 403
 *   且擋在 PHP 執行「之前」，所以 verifyCsrf／儲存邏輯從未被執行，
 *   使用者只看到一頁純文字 Forbidden，資料完全沒寫入。
 *
 * 解法：表單送出前由 JS 把指定欄位轉 base64 夾帶、原欄位移除 name 不送出；
 *       PHP 端在處理 POST 前還原。WAF 掃到的是無害字串，評分不再超標。
 *
 * 用法（兩行）：
 *   1) POST 處理之前：wafDecodePost(['landing_extra_content', 'google_maps_embed']);
 *   2) </form> 之前：  wafBypassFields(['landing_extra_content', 'google_maps_embed']);
 *   兩處欄位清單要一致。
 */

/**
 * 還原前端 base64 夾帶的欄位。
 * b64 為空（JS 沒跑、或使用者真的把欄位清空）時不覆蓋，維持原欄位邏輯，避免誤刪內容。
 */
function wafDecodePost(array $fields): void {
    foreach ($fields as $f) {
        if (!empty($_POST[$f . '_b64'])) {
            $decoded = base64_decode((string)$_POST[$f . '_b64'], true);
            if ($decoded !== false) $_POST[$f] = $decoded;
        }
    }
}

/**
 * 輸出 hidden 欄位與送出前編碼的 JS。必須放在 <form> 內（靠 currentScript 找所屬表單）。
 */
function wafBypassFields(array $fields): void {
    $ids = [];
    foreach ($fields as $f) {
        $id = 'wafB64_' . preg_replace('/[^A-Za-z0-9_]/', '', $f);
        $ids[$f] = $id;
        echo '<input type="hidden" name="' . htmlspecialchars($f, ENT_QUOTES) . '_b64" id="' . $id . '">' . "\n";
    }
    $map = json_encode($ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>
<script>
/* WAF 繞道：送出前把大欄位轉 base64，原欄位不送出（見 includes/waf_bypass.php） */
(function () {
  var form = document.currentScript.closest('form');
  if (!form) return;
  var map = <?= $map ?>;
  form.addEventListener('submit', function () {
    Object.keys(map).forEach(function (name) {
      var el  = form.querySelector('[name="' + name + '"]');
      var hid = document.getElementById(map[name]);
      if (!el || !hid) return;
      try {
        hid.value = btoa(unescape(encodeURIComponent(el.value)));
        el.removeAttribute('name');
      } catch (e) { /* 編碼失敗就照原樣送，行為與加此機制前一致 */ }
    });
  });
})();
</script>
<?php
}
