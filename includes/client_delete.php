<?php
/**
 * includes/client_delete.php ─ 刪除客戶（含所有關聯資料）
 *
 * 2026-07-24 建立。原本後台沒有刪除功能（全站無 DELETE FROM clients），
 * 清理陪襯店時需要，故補上並做成共用函式，後台與 migration 都用同一支。
 *
 * clients 有 9 個外鍵指過來，必須先刪子表再刪主表，否則被約束擋下。
 * 刪除前會把該客戶所有資料匯出成 JSON 備份（_backups/deleted_clients/）。
 */

/**
 * 刪除一個客戶及其所有關聯資料。
 *
 * @param PDO   $db
 * @param int   $clientId
 * @param string $backupDir  備份目錄（絕對路徑）；傳空字串則不備份
 * @return array ['ok'=>bool, 'msg'=>string, 'deleted'=>[表=>筆數], 'backup'=>路徑]
 */
function deleteClientCascade(PDO $db, int $clientId, string $backupDir = ''): array {
    $row = $db->prepare("SELECT * FROM clients WHERE id=?");
    $row->execute([$clientId]);
    $client = $row->fetch(PDO::FETCH_ASSOC);
    if (!$client) return ['ok'=>false, 'msg'=>"客戶 #{$clientId} 不存在", 'deleted'=>[], 'backup'=>''];

    // 子表刪除順序（外鍵子表在前）
    $childTables = [
        'store_blocks'            => 'client_id',
        'cases'                   => 'client_id',
        'testimonials'            => 'client_id',
        'services'                => 'client_id',
        'service_faqs'            => 'client_id',
        'client_themes'           => 'client_id',
        'seo_settings'            => 'client_id',
        'client_social'           => 'client_id',
        'client_service_keywords' => 'client_id',
        'client_city_pages'       => 'client_id',
        'articles'                => 'client_id',
        'contact_leads'           => 'client_id',
        'coupon_claims'           => 'client_id',
    ];

    // ── 備份（含主檔與所有子表）──
    $backupPath = '';
    if ($backupDir !== '') {
        if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);
        $dump = ['client' => $client, 'children' => []];
        foreach ($childTables as $t => $col) {
            try {
                $s = $db->prepare("SELECT * FROM `$t` WHERE `$col`=?");
                $s->execute([$clientId]);
                $rows = $s->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) $dump['children'][$t] = $rows;
            } catch (Throwable $e) { /* 表不存在就跳過 */ }
        }
        $backupPath = rtrim($backupDir,'/') . "/client_{$clientId}_{$client['slug']}.json";
        file_put_contents($backupPath, json_encode($dump, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    // ── 刪除 ──
    $deleted = [];
    $db->beginTransaction();
    try {
        // admin_users 若綁這個客戶，先解綁而非刪帳號（避免刪掉管理者）
        try {
            $u = $db->prepare("UPDATE admin_users SET client_id=NULL WHERE client_id=?");
            $u->execute([$clientId]);
            if ($u->rowCount()) $deleted['admin_users(解綁)'] = $u->rowCount();
        } catch (Throwable $e) {}

        foreach ($childTables as $t => $col) {
            try {
                $s = $db->prepare("DELETE FROM `$t` WHERE `$col`=?");
                $s->execute([$clientId]);
                if ($s->rowCount()) $deleted[$t] = $s->rowCount();
            } catch (Throwable $e) { /* 表不存在就跳過 */ }
        }
        $s = $db->prepare("DELETE FROM clients WHERE id=?");
        $s->execute([$clientId]);
        $deleted['clients'] = $s->rowCount();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        return ['ok'=>false, 'msg'=>'刪除失敗：' . $e->getMessage(), 'deleted'=>[], 'backup'=>$backupPath];
    }

    // ── 圖檔清理（DB 刪成功後才動檔案）──
    $root = dirname(__DIR__);
    $files = array_filter([$client['logo_path'] ?? '', $client['hero_image_path'] ?? '', $client['owner_avatar'] ?? '']);
    $photos = json_decode((string)($client['photos'] ?? ''), true);
    if (is_array($photos)) $files = array_merge($files, $photos);
    $imgN = 0;
    foreach (array_unique($files) as $f) {
        if (!$f) continue;
        $p = $root . '/' . ltrim($f, '/');
        if (is_file($p) && strpos(realpath($p), realpath($root . '/uploads')) === 0) { @unlink($p); $imgN++; }
    }
    if ($imgN) $deleted['圖檔'] = $imgN;

    return ['ok'=>true, 'msg'=>"已刪除「{$client['brand_name']}」(#{$clientId})", 'deleted'=>$deleted, 'backup'=>$backupPath];
}
