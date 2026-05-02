<?php
// analytics/search.php — city.php ?q= 搜尋查詢統計報告
// 用法：?key={SECRET}&days=7&format=md (or json，預設 json)
// 安全：用 hash_equals 比對 secret，timing-attack 防護
// Phase D Day 3.1

declare(strict_types=1);

const SEARCH_LOG_SECRET = 'd53dee491a87e16baa24ec599389ee460b76e62724bd34c5';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$key = (string)($_GET['key'] ?? '');
if (!hash_equals(SEARCH_LOG_SECRET, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("forbidden\n");
}

$days   = max(1, min(90, (int)($_GET['days'] ?? 7)));
$format = $_GET['format'] ?? 'json';

$logFile = dirname(__DIR__) . '/_logs/search.log';

if (!is_file($logFile)) {
    if ($format === 'md') {
        header('Content-Type: text/markdown; charset=utf-8');
        echo "# 搜尋分析報告\n\n_log 檔不存在 — 站上還沒有任何搜尋紀錄。_\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'no log yet', 'log_file' => $logFile], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$cutoff = strtotime("-{$days} days");
$total       = 0;
$zeroResult  = 0;
$byQuery     = [];   // q → ['count'=>n, 'zero'=>n, 'cities'=>[city=>n]]
$byCity      = [];   // city → n
$byDay       = [];   // YYYY-MM-DD → n
$uniqueIps   = [];   // ip_hash → 1
$zeroQueries = [];   // q with all-zero results

$fp = fopen($logFile, 'r');
if ($fp) {
    while (($line = fgets($fp)) !== false) {
        $line = rtrim($line, "\n\r");
        if ($line === '') continue;
        $parts = explode("\t", $line);
        if (count($parts) < 6) continue;
        [$ts, $city, $slug, $q, $count, $ipH] = $parts;
        $tsUnix = strtotime($ts);
        if (!$tsUnix || $tsUnix < $cutoff) continue;

        $total++;
        $count = (int)$count;
        if ($count === 0) $zeroResult++;
        $uniqueIps[$ipH] = 1;
        $day = substr($ts, 0, 10);
        $byDay[$day]   = ($byDay[$day]   ?? 0) + 1;
        $byCity[$city] = ($byCity[$city] ?? 0) + 1;

        $qNorm = mb_strtolower(trim($q));
        if (!isset($byQuery[$qNorm])) {
            $byQuery[$qNorm] = ['display' => $q, 'count' => 0, 'zero' => 0, 'cities' => []];
        }
        $byQuery[$qNorm]['count']++;
        if ($count === 0) $byQuery[$qNorm]['zero']++;
        $byQuery[$qNorm]['cities'][$city] = ($byQuery[$qNorm]['cities'][$city] ?? 0) + 1;
    }
    fclose($fp);
}

// Top queries by count
uasort($byQuery, fn($a, $b) => $b['count'] <=> $a['count']);
$topQueries = array_slice($byQuery, 0, 10, true);

// Queries that ALWAYS return 0 results — 強烈訊號：要做關鍵字 redirect
$alwaysZero = array_filter($byQuery, fn($v) => $v['zero'] === $v['count'] && $v['count'] >= 2);
uasort($alwaysZero, fn($a, $b) => $b['count'] <=> $a['count']);
$alwaysZero = array_slice($alwaysZero, 0, 10, true);

arsort($byCity);
ksort($byDay);

$report = [
    'period_days'    => $days,
    'generated_at'   => date('c'),
    'totals' => [
        'searches'        => $total,
        'unique_searchers' => count($uniqueIps),
        'zero_result'     => $zeroResult,
        'zero_result_pct' => $total > 0 ? round($zeroResult / $total * 100, 1) : 0,
    ],
    'top_queries' => array_map(fn($k, $v) => [
        'query'     => $v['display'],
        'count'     => $v['count'],
        'zero_hits' => $v['zero'],
        'cities'    => $v['cities'],
    ], array_keys($topQueries), $topQueries),
    'always_zero_queries' => array_map(fn($k, $v) => [
        'query' => $v['display'],
        'count' => $v['count'],
        'cities' => $v['cities'],
    ], array_keys($alwaysZero), $alwaysZero),
    'by_city'        => $byCity,
    'by_day'         => $byDay,
];

if ($format === 'md') {
    header('Content-Type: text/markdown; charset=utf-8');
    $T = $report['totals'];
    echo "# 搜尋分析報告（過去 {$days} 天）\n\n";
    echo "_產生時間：{$report['generated_at']}_\n\n";
    echo "## 總覽\n\n";
    echo "| 指標 | 值 |\n|---|---|\n";
    echo "| 搜尋次數 | {$T['searches']} |\n";
    echo "| 不重複搜尋者（每日 IP hash） | {$T['unique_searchers']} |\n";
    echo "| 零結果次數 | {$T['zero_result']} |\n";
    echo "| 零結果比例 | {$T['zero_result_pct']}% |\n\n";

    if ($T['searches'] === 0) {
        echo "_期間內沒有任何搜尋紀錄。_\n";
    } else {
        echo "## 熱門搜尋詞 Top 10\n\n";
        echo "| # | 關鍵字 | 次數 | 其中零結果 | 主要城市 |\n|---|---|---|---|---|\n";
        $i = 1;
        foreach ($report['top_queries'] as $row) {
            $cityStr = implode(', ', array_map(fn($c, $n) => "{$c}({$n})", array_keys($row['cities']), $row['cities']));
            echo "| {$i} | {$row['query']} | {$row['count']} | {$row['zero_hits']} | {$cityStr} |\n";
            $i++;
        }
        echo "\n";

        if ($report['always_zero_queries']) {
            echo "## 完全沒結果的關鍵字（≥2 次嘗試）— ⚠️ 建議考慮 redirect / 收錄相關店家\n\n";
            echo "| 關鍵字 | 次數 | 城市 |\n|---|---|---|\n";
            foreach ($report['always_zero_queries'] as $row) {
                $cityStr = implode(', ', array_keys($row['cities']));
                echo "| **{$row['query']}** | {$row['count']} | {$cityStr} |\n";
            }
            echo "\n";
        }

        echo "## 各城市搜尋量\n\n";
        echo "| 城市 | 次數 |\n|---|---|\n";
        foreach ($report['by_city'] as $c => $n) echo "| {$c} | {$n} |\n";
        echo "\n";

        echo "## 每日搜尋量\n\n";
        echo "| 日期 | 次數 |\n|---|---|\n";
        foreach ($report['by_day'] as $d => $n) echo "| {$d} | {$n} |\n";
        echo "\n";

        echo "## 建議\n\n";
        if ($T['searches'] < 10) {
            echo "- 搜尋使用量還很低（< 10 次/週），暫時不需要做 search analytics 整合或 redirect。\n";
            echo "- 持續觀察 1-2 個月再評估。\n";
        } else {
            $z = $T['zero_result_pct'];
            if ($z >= 30) {
                echo "- ⚠️ 零結果比例 {$z}% 偏高（>30%），代表相當比例使用者找不到要的店家。\n";
                echo "- 行動：把上面「完全沒結果的關鍵字」表跑一下，看哪些是該收錄的店家分類。\n";
            } elseif ($z >= 15) {
                echo "- 零結果比例 {$z}%（15-30%），中等。考慮為 top zero 關鍵字加 redirect 或內容。\n";
            } else {
                echo "- 零結果比例 {$z}%（< 15%），健康。\n";
            }
            if ($T['searches'] >= 50) {
                echo "- 搜尋量已具規模（≥50/週），值得：(1) 把熱門詞變成 SEO 著陸頁；(2) 追蹤點擊事件做轉換漏斗。\n";
            }
        }
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
