<?php
/**
 * landing_extra_content 內嵌 JSON-LD 防呆守衛（2026-06-24）
 * ------------------------------------------------------------
 * 行銷頁的 landing 常手寫 inline <script application/ld+json>，編輯時最易掉逗號
 * → GSC 報「無法剖析的結構化資料：缺少, 或 }」(WNC-10030322)。
 * 後台儲存 landing 時呼叫本守衛：
 *   - 自動補「屬性間漏逗號」（值結尾 } ] " 後接 "key": 卻沒逗號），補完 json_decode 重驗。
 *   - 補不回的回傳錯誤訊息，讓儲存端警示使用者（不擋存、避免丟失大表單其他欄位）。
 * 回傳 ['html'=>處理後內容, 'fixed'=>自動修正段數, 'errors'=>[仍壞的訊息]]。
 */
function jsonldGuardFix(string $html): array {
    $fixed = 0; $errors = []; $block = 0;
    $out = preg_replace_callback(
        '#(<script[^>]*type=["\']application/ld\+json["\'][^>]*>)(.*?)(</script>)#is',
        function ($m) use (&$fixed, &$errors, &$block) {
            $block++;
            $body = $m[2];
            json_decode(trim($body));
            if (json_last_error() === JSON_ERROR_NONE) return $m[0]; // 本來就合法
            // 試補逗號：值結尾(} ] ")後接空白(含換行)再接 "key": → 中間補逗號
            // （合法 JSON 屬性間一定有逗號，逗號非 \s 會擋住匹配，故不會誤補）
            $cand = preg_replace('/([}\]"])(\s*)("@?[A-Za-z][\w@]*"\s*:)/u', '$1,$2$3', $body);
            json_decode(trim($cand));
            if (json_last_error() === JSON_ERROR_NONE) { $fixed++; return $m[1] . $cand . $m[3]; }
            $errors[] = "第 {$block} 段：" . json_last_error_msg();
            return $m[0]; // 補不回，原樣留
        },
        $html
    );
    if ($out === null) return ['html' => $html, 'fixed' => 0, 'errors' => []]; // 正則失敗保底
    return ['html' => $out, 'fixed' => $fixed, 'errors' => $errors];
}
