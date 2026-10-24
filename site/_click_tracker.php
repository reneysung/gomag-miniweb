<?php
// site/_click_tracker.php — 小官網按鈕點擊監聽器（layout_foot.php 與不走共用頁尾的 index_design.php 都 require_once 這支）
?>
<?php if (!empty($client['id'])): ?>
<script>
// 小官網按鈕點擊記錄 → /track_click.php（source=mini，後台 click_stats.php）。
// 小官網的頁首頁尾就是店家自己的，所以不排除；站內連結不符合任何分類，自然不會算。
(function(){
  var CID=<?= (int)$client['id'] ?>;
  var SITE=<?= json_encode(strtolower(preg_replace('/^www\./', '', (string)(parse_url((string)($client['external_website_url'] ?? ''), PHP_URL_HOST) ?: '')))) ?>;
  var ORDER=<?= json_encode(strtolower(preg_replace('/^www\./', '', (string)(parse_url((string)($client['order_url'] ?? ''), PHP_URL_HOST) ?: '')))) ?>;
  function hostOf(h){ var m=h.match(/^https?:\/\/([^\/?#]+)/); return m ? m[1].replace(/^www\./,'') : ''; }
  function act(h){
    h=h.toLowerCase();
    if(h.indexOf('tel:')===0) return 'phone';
    if(/line\.me|lin\.ee/.test(h)) return 'line';
    if(/google\.[a-z.]+\/maps|maps\.google|goo\.gl\/maps|maps\.app\.goo\.gl/.test(h)) return 'map';
    if(/facebook\.com|fb\.com|fb\.me|instagram\.com/.test(h)) return 'social';
    if(/foodpanda|ubereats/.test(h)) return 'order';
    var host=hostOf(h); if(!host) return '';
    if(ORDER && host===ORDER) return 'order';
    if(SITE && host===SITE) return 'website';
    return '';
  }
  document.addEventListener('click', function(e){
    var el=e.target && e.target.closest && e.target.closest('a[href]'); if(!el) return;
    var a=act(el.getAttribute('href')||''); if(!a) return;
    try{ var f=new FormData(); f.append('cid',CID); f.append('a',a); f.append('s','mini'); f.append('p',location.host+location.pathname);
         navigator.sendBeacon('/track_click.php', f); }catch(err){}
  }, true);
})();
</script>
<?php endif; ?>
