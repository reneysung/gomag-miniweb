<?php
/**
 * google_reviews.php — 抓 Google Places 評價（含快取）
 *
 * 用法：
 *   $reviews = getGoogleReviews($placeId);
 *   if ($reviews) {
 *       echo $reviews['rating'];        // 4.5
 *       echo $reviews['userRatingCount']; // 38
 *       foreach ($reviews['reviews'] as $r) { ... }
 *   }
 *
 * 沒設 API key 或失敗就 return null，前台 fallback 到 testimonials。
 */

/**
 * 取單一店家的 Google 評價（含快取 24 小時）
 *
 * @param string $placeId Google place_id
 * @param bool $forceRefresh 強制刷新快取
 * @return array|null
 */
function getGoogleReviews(string $placeId, bool $forceRefresh = false): ?array
{
    if (empty($placeId)) return null;

    $apiKey = getPlatformSetting('google_maps_api_key', '');
    if (empty($apiKey)) return null;

    $cacheDir = __DIR__ . '/../cache/google_reviews';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

    $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $placeId);
    $cacheFile = $cacheDir . '/' . $safeId . '.json';

    // 檢查快取（24 小時）
    if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        $cached = @json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    // 呼叫 Google Places API (New) v1
    $url = 'https://places.googleapis.com/v1/places/' . urlencode($placeId);
    $fields = 'id,displayName,rating,userRatingCount,reviews,googleMapsUri';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => [
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . $fields,
            'Accept-Language: zh-TW',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$resp) {
        // 失敗時若有舊快取，繼續用
        if (file_exists($cacheFile)) {
            $cached = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }
        return null;
    }

    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['rating'])) {
        return null;
    }

    // 整理成統一格式
    $result = [
        'place_id'        => $data['id'] ?? $placeId,
        'name'            => $data['displayName']['text'] ?? '',
        'rating'          => round((float)$data['rating'], 1),
        'userRatingCount' => (int)($data['userRatingCount'] ?? 0),
        'mapsUrl'         => $data['googleMapsUri'] ?? '',
        'reviews'         => [],
        'fetched_at'      => time(),
    ];

    foreach (($data['reviews'] ?? []) as $r) {
        $result['reviews'][] = [
            'author'    => $r['authorAttribution']['displayName'] ?? '匿名',
            'avatar'    => $r['authorAttribution']['photoUri'] ?? '',
            'rating'    => (int)($r['rating'] ?? 0),
            'text'      => $r['text']['text'] ?? '',
            'time'      => $r['publishTime'] ?? '',
            'relative'  => $r['relativePublishTimeDescription'] ?? '',
        ];
    }

    // 寫快取
    @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * 用店家名 + 地址自動找 Google place_id（一次性 / 後台批次用）
 *
 * @param string $name 店家名
 * @param string $address 地址
 * @return string|null place_id
 */
function findGooglePlaceId(string $name, string $address = ''): ?string
{
    $apiKey = getPlatformSetting('google_maps_api_key', '');
    if (empty($apiKey) || empty(trim($name))) return null;

    $query = trim($name . ' ' . $address);

    $ch = curl_init('https://places.googleapis.com/v1/places:searchText');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['textQuery' => $query]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress',
            'Accept-Language: zh-TW',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$resp) return null;

    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['places'])) return null;

    return $data['places'][0]['id'] ?? null;
}

/**
 * 清掉某個 place_id 的快取（強制下次重抓）
 */
function clearGoogleReviewCache(string $placeId): bool
{
    $cacheDir = __DIR__ . '/../cache/google_reviews';
    $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $placeId);
    $cacheFile = $cacheDir . '/' . $safeId . '.json';
    return file_exists($cacheFile) ? @unlink($cacheFile) : true;
}
