<?php
// admin/pages/new_client.php  ─  新增客戶精靈（Super Admin）
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

// 只有 super admin 可以用
if (currentAdmin()['role'] !== 'super') {
    setFlash('danger', '權限不足');
    redirect(BASE_URL . '/admin/index.php');
}

$pageTitle = '新增客戶';
$db = getDB();
$step = (int)($_GET['step'] ?? 1);
$error = '';

// ── STEP 2：儲存 ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // 驗證
    $subdomain = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['subdomain'] ?? '')));
    $brandName = trim($_POST['brand_name'] ?? '');

    if (!$subdomain || !$brandName) {
        $error = '請填寫品牌名稱與網址識別碼';
    } else {
        // 檢查重複
        $dup = $db->prepare('SELECT id FROM clients WHERE subdomain=? OR slug=?');
        $dup->execute([$subdomain, $subdomain]);
        if ($dup->fetch()) {
            $error = "子網域「{$subdomain}」已被使用，請換一個";
        }
    }

    if (!$error) {
        $db->beginTransaction();
        try {
            // 1. 建立客戶（含新欄位：category_id / has_minisite / external_website_url / legacy_store_id）
            $db->prepare("INSERT INTO clients
                (subdomain,slug,brand_name,tagline,industry,category_id,phone,email,address,external_website_url,about_text,has_minisite,legacy_store_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $subdomain,
                   $subdomain,
                   $brandName,
                   trim($_POST['tagline']  ?? ''),
                   trim($_POST['industry'] ?? ''),
                   !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                   trim($_POST['phone']    ?? ''),
                   trim($_POST['email']    ?? ''),
                   trim($_POST['address']  ?? ''),
                   trim($_POST['external_website_url'] ?? ''),
                   trim($_POST['about_text'] ?? ''),
                   isset($_POST['has_minisite']) ? 1 : 0,
                   trim($_POST['legacy_store_id'] ?? ''),
               ]);
            $clientId = (int)$db->lastInsertId();

            // 2. 插入 6 套預設主題（以主色+強調色客製化）
            $primary = $_POST['color_primary'] ?? '#1c3d3d';
            $accent  = $_POST['color_accent']  ?? '#c8922a';
            $themes  = [
                [$primary, $accent, '#f0f4f2', 1],
                ['#1a2e4a', '#e8943a', '#eef2f7', 0],
                ['#7d2d2d', '#d4a853', '#fdf5f0', 0],
                ['#1a1a1a', '#ff6b35', '#f5f5f5', 0],
                ['#3d1f5e', '#f0c040', '#f5f0fa', 0],
                ['#0d5c63', '#f4a261', '#e8f5f6', 0],
            ];
            $names = ['自訂主題（預設）','海軍藍','磚紅暖系','炭黑極簡','紫羅蘭','湖水藍'];
            foreach ($themes as $i => $t) {
                $db->prepare("INSERT INTO client_themes (client_id,theme_name,color_primary,color_accent,color_light,is_active) VALUES (?,?,?,?,?,?)")
                   ->execute([$clientId, $names[$i], $t[0], $t[1], $t[2], $t[3]]);
            }

            // 3. 社群（若填了）
            $db->prepare("INSERT INTO client_social (client_id,line_url,line_id,fb_page_url) VALUES (?,?,?,?)")
               ->execute([$clientId, trim($_POST['line_url']??''), trim($_POST['line_id']??''), trim($_POST['fb_page_url']??'')]);

            // 4. 預設 SEO
            $seoPages = ['home'=>$brandName.'｜'.trim($_POST['tagline']??''), 'services'=>'服務項目｜'.$brandName, 'cases'=>'施工案例｜'.$brandName];
            foreach ($seoPages as $pk => $title) {
                $db->prepare("INSERT INTO seo_settings (client_id,page_key,meta_title) VALUES (?,?,?)")
                   ->execute([$clientId, $pk, $title]);
            }

            // （已移除：客戶登入帳號 — 統一由 superadmin 管理，不需要單獨帳密）

            $db->commit();

            // 切到剛建立的客戶
            $_SESSION['viewing_client_id'] = $clientId;

            setFlash('success', "✅ 客戶「{$brandName}」已建立！繼續填寫詳細資料：SEO、Logo、Hero 圖、行銷頁內容...");
            // 立刻跳到該客戶的「基本資訊」頁，讓 Super 接著填 SEO/圖/內容
            redirect(BASE_URL . '/admin/pages/settings.php');

        } catch (Exception $e) {
            $db->rollBack();
            $error = '建立失敗：' . $e->getMessage();
        }
    }
}

// 完成畫面
$done = isset($_GET['done']);

// 載入分類清單給下拉選單
$categories = $db->query("SELECT id, name, icon, slug FROM categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll();

require_once __DIR__ . '/../includes/layout_head.php';

if ($done): ?>
<div class="card" style="max-width:560px">
  <div class="card-body" style="text-align:center;padding:40px">
    <div style="font-size:4rem;margin-bottom:16px">🎉</div>
    <h2 style="font-size:1.4rem;font-weight:900;color:var(--primary);margin-bottom:12px">客戶已成功建立！</h2>
    <div style="margin:20px 0;display:flex;flex-direction:column;gap:10px">
      <a href="<?= BASE_URL ?>/site/index.php?sub=<?= h($_GET['sub'] ?? '') ?>" target="_blank"
         class="btn btn-primary">🌐 前往前台官網</a>
      <a href="<?= BASE_URL ?>/admin/pages/new_client.php" class="btn btn-outline">➕ 再新增一個客戶</a>
      <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-ghost">← 回到 Dashboard</a>
    </div>
  </div>
</div>

<?php else: ?>

<!-- 流程提示 -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:20px;max-width:700px;font-size:.85rem">
  <strong>📋 兩步建立客戶：</strong> 1) 這頁填基本欄位 → 2) 自動跳「基本資訊」頁繼續填 SEO / Logo / 內容
</div>

<div class="card" style="max-width:700px">
  <div class="card-header"><h2>➕ 新增客戶精靈</h2></div>
  <div class="card-body">
    <?php if($error): ?>
      <div class="flash flash-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">

      <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">
        📋 基本資訊
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>品牌名稱 *</label>
          <input type="text" name="brand_name" class="form-control" required placeholder="旭浪清潔"
                 value="<?= h($_POST['brand_name']??'') ?>">
        </div>
        <div class="form-group-admin">
          <label>網址識別碼（英數字）*</label>
          <div style="display:flex;align-items:center;gap:0">
            <span style="padding:9px 10px;background:var(--bg);border:1.5px solid var(--border);border-right:none;border-radius:7px 0 0 7px;font-size:.85rem;color:var(--muted);white-space:nowrap">https://</span>
            <input type="text" name="subdomain" class="form-control" required placeholder="xusen"
                   style="border-radius:0 7px 7px 0"
                   value="<?= h($_POST['subdomain']??'') ?>"
                   oninput="this.value=this.value.replace(/[^a-z0-9_-]/g,'')">
          </div>
          <div class="hint">只能英文小寫、數字、底線、連字號</div>
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>品牌標語</label>
          <input type="text" name="tagline" class="form-control" placeholder="專業到位，潔淨生活每一天"
                 value="<?= h($_POST['tagline']??'') ?>">
        </div>
        <div class="form-group-admin">
          <label>業種</label>
          <input type="text" name="industry" class="form-control" placeholder="居家清潔"
                 value="<?= h($_POST['industry']??'') ?>">
        </div>
      </div>

      <!-- ═══ 主站分類 + 舊店家編號 ═══ -->
      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>主站分類 *</label>
          <select name="category_id" class="form-control" required>
            <option value="">— 請選擇分類 —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"
              <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
              <?= h($cat['icon']) ?> <?= h($cat['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-admin">
          <label>舊 gomag 店家編號（可選）</label>
          <input type="text" name="legacy_store_id" class="form-control"
                 placeholder="例：062051129"
                 value="<?= h($_POST['legacy_store_id']??'') ?>">
          <div class="hint">如有舊網址 SEO，填這裡對照</div>
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>電話</label>
          <input type="text" name="phone" class="form-control" placeholder="06-123-4567"
                 value="<?= h($_POST['phone']??'') ?>">
        </div>
        <div class="form-group-admin">
          <label>Email</label>
          <input type="email" name="email" class="form-control" placeholder="info@example.com"
                 value="<?= h($_POST['email']??'') ?>">
        </div>
      </div>
      <div class="form-group-admin">
        <label>地址</label>
        <input type="text" name="address" class="form-control" placeholder="台南市永康區"
               value="<?= h($_POST['address']??'') ?>">
      </div>

      <!-- ═══ 平台設定（小官網 + 外部官網）═══ -->
      <div class="form-group-admin" style="background:var(--bg);padding:14px;border-radius:8px;border:1.5px solid var(--border);margin:14px 0;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;">
          <input type="checkbox" name="has_minisite" value="1"
                 <?= !empty($_POST['has_minisite']) ? 'checked' : '' ?>
                 style="width:20px;height:20px;cursor:pointer;">
          <span>啟用子網域小官網（完整 mini-site）</span>
        </label>
        <div class="hint" style="margin-top:6px;margin-left:30px;">
          ☑ 啟用 → 子網域顯示完整小官網（含服務、案例、評價）<br>
          ☐ 不啟用 → 只在主站有曝光頁，子網域跳到外部官網或主站
        </div>
      </div>

      <div class="form-group-admin">
        <label>客戶自有獨立官網 URL（可選）</label>
        <input type="url" name="external_website_url" class="form-control"
               placeholder="https://example.com.tw"
               value="<?= h($_POST['external_website_url']??'') ?>">
        <div class="hint">如客戶已有獨立官網，主站行銷頁會顯示「前往官網」按鈕</div>
      </div>

      <div class="form-group-admin">
        <label>關於我們</label>
        <textarea name="about_text" class="form-control" rows="3" placeholder="品牌介紹文字..."><?= h($_POST['about_text']??'') ?></textarea>
      </div>

      <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin:20px 0 16px;padding-top:16px;padding-bottom:8px;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
        🎨 主題顏色
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>主色</label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="color" name="color_primary" value="<?= h($_POST['color_primary']??'#1c3d3d') ?>"
                   style="width:40px;height:36px;border:1.5px solid var(--border);border-radius:6px;cursor:pointer">
            <input type="text" id="cp_text" class="form-control" value="<?= h($_POST['color_primary']??'#1c3d3d') ?>"
                   style="width:100px" onchange="document.querySelector('[name=color_primary]').value=this.value">
          </div>
        </div>
        <div class="form-group-admin">
          <label>強調色</label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="color" name="color_accent" value="<?= h($_POST['color_accent']??'#c8922a') ?>"
                   style="width:40px;height:36px;border:1.5px solid var(--border);border-radius:6px;cursor:pointer">
            <input type="text" id="ca_text" class="form-control" value="<?= h($_POST['color_accent']??'#c8922a') ?>"
                   style="width:100px" onchange="document.querySelector('[name=color_accent]').value=this.value">
          </div>
        </div>
      </div>

      <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin:20px 0 16px;padding-top:16px;padding-bottom:8px;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
        📱 社群連結（可選）
      </div>

      <div class="form-row cols-2">
        <div class="form-group-admin">
          <label>LINE 官方帳號網址</label>
          <input type="text" name="line_url" class="form-control" placeholder="https://line.me/..."
                 value="<?= h($_POST['line_url']??'') ?>">
        </div>
        <div class="form-group-admin">
          <label>LINE ID</label>
          <input type="text" name="line_id" class="form-control" placeholder="@brandname"
                 value="<?= h($_POST['line_id']??'') ?>">
        </div>
      </div>
      <div class="form-group-admin">
        <label>Facebook 粉絲頁</label>
        <input type="text" name="fb_page_url" class="form-control" placeholder="https://www.facebook.com/..."
               value="<?= h($_POST['fb_page_url']??'') ?>">
      </div>

      <div style="display:flex;gap:12px;margin-top:24px">
        <button type="submit" class="btn btn-primary">🚀 建立客戶</button>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-ghost">取消</a>
      </div>
      <div style="font-size:.8rem;color:var(--muted);margin-top:10px">
        建立後會自動跳到該客戶的「基本資訊」頁，繼續填 SEO、Logo、Hero、行銷頁內容。
      </div>
    </form>
  </div>
</div>

<script>
document.querySelector('[name="color_primary"]').addEventListener('input',function(){document.getElementById('cp_text').value=this.value});
document.querySelector('[name="color_accent"]').addEventListener('input',function(){document.getElementById('ca_text').value=this.value});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
