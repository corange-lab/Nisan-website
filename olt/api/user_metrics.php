<?php
require __DIR__ . '/_bootstrap.php';

function norm_onu_id($s) {
    $s = strtoupper(trim((string)$s));
    $s = preg_replace('/\x{00A0}/u', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

function to_num($v) {
    if ($v === null || $v === '') return null;
    return is_numeric($v) ? (float)$v : null;
}

function open_b_usage_db() {
    $bCfgPath = dirname(__DIR__, 2) . '/b/lib/config.php';
    if (!is_file($bCfgPath)) {
        throw new RuntimeException('Missing /b config file');
    }
    $bCfg = require $bCfgPath;
    $dsn = $bCfg['DB']['dsn'] ?? '';
    if ($dsn === '') {
        throw new RuntimeException('Missing /b DB DSN');
    }
    $user = $bCfg['DB']['user'] ?? null;
    $pass = $bCfg['DB']['pass'] ?? null;
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

try {
    $pdo = open_b_usage_db();
    $endTs = time();
    $startTs = $endTs - 86400;

    // Pull only the last 24h to build rolling usage + top speed with timestamp.
    $stmt = $pdo->prepare("
        SELECT ts, onuid, input_bytes, output_bytes
        FROM samples
        WHERE ts >= :s AND ts <= :e
        ORDER BY ts ASC, onuid ASC
    ");
    $stmt->execute([':s' => $startTs, ':e' => $endTs]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        json_out([
            'ok' => true,
            'has_data' => false,
            'users' => [],
            'network' => [
                'current' => ['upload_mbps' => 0, 'download_mbps' => 0, 'total_mbps' => 0, 'at_ts' => null, 'dt_sec' => null],
                'peak_24h' => ['total_mbps' => 0, 'at_ts' => null],
                'usage_24h' => ['upload_bytes' => 0, 'download_bytes' => 0, 'total_bytes' => 0],
            ],
            'ts' => $endTs,
        ]);
    }

    $byTs = [];
    foreach ($rows as $r) {
        $ts = (int)$r['ts'];
        if (!isset($byTs[$ts])) $byTs[$ts] = [];
        $id = norm_onu_id($r['onuid'] ?? '');
        if ($id === '') continue;
        $byTs[$ts][$id] = [
            'in' => to_num($r['input_bytes']),
            'out' => to_num($r['output_bytes']),
        ];
    }

    $tsList = array_keys($byTs);
    sort($tsList, SORT_NUMERIC);

    $users = []; // onuid => computed metrics
    $networkPeak = ['total_mbps' => 0.0, 'at_ts' => null];
    $networkCurrent = ['upload_mbps' => 0.0, 'download_mbps' => 0.0, 'total_mbps' => 0.0, 'at_ts' => null, 'dt_sec' => null];
    $netUsageUp = 0.0;
    $netUsageDown = 0.0;

    for ($i = 1; $i < count($tsList); $i++) {
        $t1 = (int)$tsList[$i - 1];
        $t2 = (int)$tsList[$i];
        $dt = max(1, $t2 - $t1);
        $prev = $byTs[$t1];
        $curr = $byTs[$t2];

        $sumUp = 0.0;
        $sumDown = 0.0;

        foreach ($curr as $id => $curVals) {
            if (!isset($prev[$id])) continue;
            $prvVals = $prev[$id];

            $inC = $curVals['in'];
            $inP = $prvVals['in'];
            $outC = $curVals['out'];
            $outP = $prvVals['out'];

            $upMbps = 0.0;     // input counter delta
            $downMbps = 0.0;   // output counter delta
            $upBytes = 0.0;
            $downBytes = 0.0;

            if ($inC !== null && $inP !== null && $inC >= $inP) {
                $upBytes = $inC - $inP;
                $upMbps = ($upBytes * 8.0) / ($dt * 1000000.0);
            }
            if ($outC !== null && $outP !== null && $outC >= $outP) {
                $downBytes = $outC - $outP;
                $downMbps = ($downBytes * 8.0) / ($dt * 1000000.0);
            }
            $totalMbps = $upMbps + $downMbps;

            if (!isset($users[$id])) {
                $users[$id] = [
                    'upload_mbps' => 0.0,
                    'download_mbps' => 0.0,
                    'total_mbps' => 0.0,
                    'at_ts' => null,
                    'dt_sec' => null,
                    'top_total_mbps' => 0.0,
                    'top_at_ts' => null,
                    'usage_24h_upload_bytes' => 0.0,
                    'usage_24h_download_bytes' => 0.0,
                ];
            }

            // Current speed is from latest available interval.
            if ($i === count($tsList) - 1) {
                $users[$id]['upload_mbps'] = $upMbps;
                $users[$id]['download_mbps'] = $downMbps;
                $users[$id]['total_mbps'] = $totalMbps;
                $users[$id]['at_ts'] = $t2;
                $users[$id]['dt_sec'] = $dt;
            }

            if ($totalMbps >= (float)$users[$id]['top_total_mbps']) {
                $users[$id]['top_total_mbps'] = $totalMbps;
                $users[$id]['top_at_ts'] = $t2;
            }

            $users[$id]['usage_24h_upload_bytes'] += $upBytes;
            $users[$id]['usage_24h_download_bytes'] += $downBytes;

            $sumUp += $upMbps;
            $sumDown += $downMbps;
            $netUsageUp += $upBytes;
            $netUsageDown += $downBytes;
        }

        $netTotal = $sumUp + $sumDown;
        if ($netTotal >= (float)$networkPeak['total_mbps']) {
            $networkPeak['total_mbps'] = $netTotal;
            $networkPeak['at_ts'] = $t2;
        }
        if ($i === count($tsList) - 1) {
            $networkCurrent = [
                'upload_mbps' => $sumUp,
                'download_mbps' => $sumDown,
                'total_mbps' => $netTotal,
                'at_ts' => $t2,
                'dt_sec' => $dt,
            ];
        }
    }

    foreach ($users as $id => &$u) {
        $u['usage_24h_total_bytes'] = $u['usage_24h_upload_bytes'] + $u['usage_24h_download_bytes'];
        $u['online'] = ((float)$u['total_mbps'] > 0.0);
    }
    unset($u);

    json_out([
        'ok' => true,
        'has_data' => true,
        'window' => ['start_ts' => $startTs, 'end_ts' => $endTs],
        'users' => $users,
        'network' => [
            'current' => $networkCurrent,
            'peak_24h' => $networkPeak,
            'usage_24h' => [
                'upload_bytes' => (int)round($netUsageUp),
                'download_bytes' => (int)round($netUsageDown),
                'total_bytes' => (int)round($netUsageUp + $netUsageDown),
            ],
        ],
        'ts' => $endTs,
    ]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => 'php:' . $e->getMessage()]);
}

