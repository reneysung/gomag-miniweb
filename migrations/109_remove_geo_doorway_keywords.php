<?php
// ============================================================
// Migration 109 — 移除「台南美食 / 台南東區美食」地理 doorway keywords
// ------------------------------------------------------------
// service_keywords #142 (tainanmeishi)、#143 (tainandongqumeishi) 是
// 把「地理修飾詞」錯位到子服務層，會造成跟 /city/tainan/food 樞紐頁
// 內容 95% 重複的 doorway page。
//
// 處理：
// 1. 清掉所有客戶在這 2 個 keyword 的標籤（client_service_keywords）
// 2. service_keywords #142/#143 設 is_active=0（保留 row 做 audit、不刪）
// 3. 客戶本來想吃的「台南美食推薦」流量由 /city/tainan/food 樞紐頁吃
//
// 「台南美食推薦」的正確 SEO 工具：
//   - 樞紐頁 /city/tainan/food (meta_title + intro 強化)
//   - 攻略文 /guide/tainan-meishi-* (這次先不寫)
//   - 懶人包外站發佈（另外執行）
//
// 跑法：HTTP_HOST=www.gomag.com.tw php migrations/109_remove_geo_doorway_keywords.php
// 冪等：DELETE / UPDATE
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

echo "== 處理前：誰標了 #142/#143 ==\n";
$before = $db->query("SELECT csk.client_id, c.brand_name, sk.id kw_id, sk.name kw_name
    FROM client_service_keywords csk
    JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    JOIN clients c ON c.id=csk.client_id
    WHERE csk.service_keyword_id IN (142, 143)
    ORDER BY csk.client_id, sk.id")->fetchAll(PDO::FETCH_ASSOC);
if (!$before) {
    echo "  (沒人標)\n";
} else {
    foreach ($before as $r) printf("  #%-4d %-30s ← #%d %s\n", $r['client_id'], $r['brand_name'], $r['kw_id'], $r['kw_name']);
}

// 1. 移除所有 client ↔ #142/#143 的 mapping
$n = $db->exec("DELETE FROM client_service_keywords WHERE service_keyword_id IN (142, 143)");
echo "\n>> DELETE client_service_keywords：{$n} 條\n";

// 2. 停用 #142/#143 keyword 本身
$db->exec("UPDATE service_keywords SET is_active=0 WHERE id IN (142, 143)");
echo ">> UPDATE service_keywords #142/#143 → is_active=0\n";

// 3. 驗證
echo "\n== 處理後驗證 ==\n";
echo "\n[a] #142/#143 keyword 狀態\n";
foreach ($db->query("SELECT id, slug, name, page_slug, is_active FROM service_keywords WHERE id IN (142, 143)") as $r) {
    printf("  #%d %s (%s) page=%s active=%d\n", $r['id'], $r['slug'], $r['name'], $r['page_slug'] ?: '(空)', $r['is_active']);
}

echo "\n[b] #252 辣嬌程癮 最終標籤\n";
$tags = $db->query("SELECT sk.id, sk.slug, sk.name FROM client_service_keywords csk
    JOIN service_keywords sk ON sk.id=csk.service_keyword_id
    WHERE csk.client_id=252 ORDER BY sk.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tags as $r) printf("  #%d %s (%s)\n", $r['id'], $r['slug'], $r['name']);

echo "\n[c] #252 會出現在哪些頁面\n";
$stmt = $db->prepare("SELECT id, brand_name, city_slug, category_id FROM clients WHERE id=252");
$stmt->execute();
$c = $stmt->fetch(PDO::FETCH_ASSOC);
printf("  client: #%d %s (city=%s, cat=%d)\n", $c['id'], $c['brand_name'], $c['city_slug'], $c['category_id']);
echo "  → /city/{$c['city_slug']}                  (縣市頁)\n";
echo "  → /city/{$c['city_slug']}/food             (餐飲樞紐頁，吃「台南美食推薦」流量)\n";
echo "  → /city/{$c['city_slug']}/food/chinese     (中式熱炒子服務頁)\n";
echo "  → /store/hot                           (店家行銷頁)\n";

echo "\n完成。\n";
