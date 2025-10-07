<?php
require __DIR__.'/_bootstrap.php';

// New batch API that loads multiple PONs in parallel
$pons = isset($_GET['pons']) ? array_map('intval', explode(',', $_GET['pons'])) : $CFG['PONS'];
$pons = array_filter($pons, function($p) { return $p >= 1; });

if (empty($pons)) {
    json_out(["ok" => false, "error" => "No valid PONs specified"]);
}

// Cache key for batch results
$cache_key = "batch_" . md5(implode(',', $pons));
if (($cached = cache_get($cache_key, 30))) { // 30 second cache
    json_out($cached);
}

[$ch, $cookie, $err, $reused] = olt_login_or_reuse($CFG);
if ($err) {
    json_out(["ok" => false, "error" => $err]);
}

$urls = olt_urls($CFG);
$results = [];
$start_time = microtime(true);

// Phase 1: Load Auth data for all PONs in parallel
$multi_handle = curl_multi_init();
$auth_handles = [];
$auth_pon_map = [];

foreach ($pons as $pon) {
    $ch_auth = curl_init();
    curl_setopt_array($ch_auth, [
        CURLOPT_URL => $urls['AUTH'],
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
        CURLOPT_HTTPHEADER => [
            "Origin: {$CFG['BASE']}",
            "Referer: {$CFG['BASE']}/action/onuauthinfo.html",
            "Content-Type: application/x-www-form-urlencoded",
        ],
        CURLOPT_POSTFIELDS => http_build_query([
            "select" => (string)$pon,
            "authmode" => "0",
            "who" => "100",
            "onuid" => "0"
        ]),
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_COOKIEJAR => $cookie,
    ]);
    
    curl_multi_add_handle($multi_handle, $ch_auth);
    $auth_handles[] = $ch_auth;
    $auth_pon_map[spl_object_hash($ch_auth)] = $pon;
}

// Execute all auth requests in parallel
$running = null;
do {
    curl_multi_exec($multi_handle, $running);
    curl_multi_select($multi_handle);
} while ($running > 0);

// Collect auth results
$auth_data = [];
foreach ($auth_handles as $handle) {
    $pon = $auth_pon_map[spl_object_hash($handle)];
    $html = curl_multi_getcontent($handle);
    if ($html && $html !== false && strlen($html) > 100) {
        $rows = parse_onu_rows($html, $pon);
        $auth_data[$pon] = $rows;
    } else {
        $auth_data[$pon] = [];
        // Debug: log empty response
        error_log("Batch API: Empty response for PON $pon, HTML length: " . strlen($html));
    }
    curl_multi_remove_handle($multi_handle, $handle);
    curl_close($handle);
}
curl_multi_close($multi_handle);

// Phase 2: Load Optical data for all PONs in parallel (with optimized payloads)
$multi_handle = curl_multi_init();
$optical_handles = [];
$optical_pon_map = [];

foreach ($pons as $pon) {
    if (empty($auth_data[$pon])) continue;
    
    // Use optimized payload strategy - try most likely payloads first
    $expected = array_values(array_filter(array_map(function($r) { 
        return norm_onuid($r['onuid'] ?? ''); 
    }, $auth_data[$pon])));
    
    // Try the most effective payloads first (based on analysis)
    $priority_payloads = [
        ['pon' => (string)$pon],
        ['select' => (string)$pon, 'who' => '100'],
        ['ponid' => (string)$pon],
        ['portid' => "PON" . $pon],
    ];
    
    foreach ($priority_payloads as $payload) {
        $ch_opt = curl_init();
        curl_setopt_array($ch_opt, [
            CURLOPT_URL => $urls['OPT'],
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $CFG['OPT_TIMEOUT'],
            CURLOPT_HTTPHEADER => [
                "Origin: {$CFG['BASE']}",
                "Referer: {$CFG['BASE']}/action/pononuopticalinfo.html",
                "Content-Type: application/x-www-form-urlencoded",
            ],
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_COOKIEJAR => $cookie,
        ]);
        
        curl_multi_add_handle($multi_handle, $ch_opt);
        $optical_handles[] = $ch_opt;
        $optical_pon_map[spl_object_hash($ch_opt)] = ['pon' => $pon, 'payload' => $payload];
        
        // Only try first payload for now - we can expand if needed
        break;
    }
}

// Execute optical requests in parallel
$running = null;
do {
    curl_multi_exec($multi_handle, $running);
    curl_multi_select($multi_handle);
} while ($running > 0);

// Collect optical results
$optical_data = [];
foreach ($optical_handles as $handle) {
    $info = $optical_pon_map[spl_object_hash($handle)];
    $pon = $info['pon'];
    $html = curl_multi_getcontent($handle);
    
    if ($html && $html !== false) {
        $list = parse_optical_map($html, $pon);
        if (!empty($list)) {
            $optical_data[$pon] = $list;
        }
    }
    
    curl_multi_remove_handle($multi_handle, $handle);
    curl_close($handle);
}
curl_multi_close($multi_handle);

// Phase 3: Prepare WAN data (batch WAN loading for online ONUs)
$wan_tasks = [];
foreach ($pons as $pon) {
    if (empty($auth_data[$pon])) continue;
    
    $online_onus = array_filter($auth_data[$pon], function($row) {
        return stripos($row['status'] ?? '', 'online') !== false;
    });
    
    // Limit to first 5 online ONUs per PON to avoid too many requests
    $sample_onus = array_slice($online_onus, 0, 5);
    
    foreach ($sample_onus as $onu) {
        $wan_tasks[] = [
            'pon' => $pon,
            'onu' => $onu['onu'],
            'onuid' => $onu['onuid_norm']
        ];
    }
}

// Load WAN data in parallel (limited concurrency)
$wan_data = [];
if (!empty($wan_tasks)) {
    $multi_handle = curl_multi_init();
    $wan_handles = [];
    $wan_task_map = [];
    $concurrency_limit = 8; // Limit concurrent WAN requests
    
    foreach (array_slice($wan_tasks, 0, $concurrency_limit) as $task) {
        $ch_wan = curl_init();
        $url = $urls['WAN'] . '?' . http_build_query([
            'gponid' => $task['pon'],
            'gonuid' => $task['onu']
        ]);
        
        curl_setopt_array($ch_wan, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $CFG['WAN_TIMEOUT'],
            CURLOPT_HTTPHEADER => [
                "Referer: {$CFG['BASE']}/action/onuconfigsrv.html?ponid={$task['pon']}&onuid={$task['onu']}&targid=onuTcont.html"
            ],
            CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_COOKIEJAR => $cookie,
        ]);
        
        curl_multi_add_handle($multi_handle, $ch_wan);
        $wan_handles[] = $ch_wan;
        $wan_task_map[spl_object_hash($ch_wan)] = $task;
    }
    
    // Execute WAN requests
    $running = null;
    do {
        curl_multi_exec($multi_handle, $running);
        curl_multi_select($multi_handle);
    } while ($running > 0);
    
    // Collect WAN results
    foreach ($wan_handles as $handle) {
        $task = $wan_task_map[spl_object_hash($handle)];
        $html = curl_multi_getcontent($handle);
        
        if ($html && $html !== false) {
            $status = parse_wan_status($html) ?: 'Unknown';
        } else {
            $status = 'Unknown';
        }
        
        $wan_data[$task['pon']][$task['onu']] = $status;
        
        curl_multi_remove_handle($multi_handle, $handle);
        curl_close($handle);
    }
    curl_multi_close($multi_handle);
}

olt_close($ch);

// Compile final results
$final_results = [];
foreach ($pons as $pon) {
    $auth_rows = $auth_data[$pon] ?? [];
    $optical_rows = $optical_data[$pon] ?? [];
    $wan_statuses = $wan_data[$pon] ?? [];
    
    // Create RX lookup
    $rx_by_onu = [];
    foreach ($optical_rows as $row) {
        if (!empty($row['onuid_norm']) && isset($row['rx'])) {
            $rx_by_onu[$row['onuid_norm']] = $row['rx'];
        }
    }
    
    // Combine data
    $combined_rows = [];
    foreach ($auth_rows as $auth_row) {
        $onuid_norm = norm_onuid($auth_row['onuid'] ?? '');
        $rx = $rx_by_onu[$onuid_norm] ?? null;
        $wan = $wan_statuses[$auth_row['onu']] ?? 'N/A';
        
        $combined_rows[] = [
            'pon' => $auth_row['pon'],
            'onu' => $auth_row['onu'],
            'onuid' => $auth_row['onuid'],
            'onuid_norm' => $onuid_norm,
            'desc' => $auth_row['desc'],
            'model' => $auth_row['model'],
            'status' => $auth_row['status'],
            'rx' => $rx,
            'wan' => $wan,
        ];
    }
    
    $final_results[$pon] = $combined_rows;
}

$total_time = microtime(true) - $start_time;

$response = [
    'ok' => true,
    'pons' => $pons,
    'data' => $final_results,
    'stats' => [
        'total_time' => round($total_time, 3),
        'auth_requests' => count($pons),
        'optical_requests' => count($pons),
        'wan_requests' => count($wan_tasks),
        'total_onus' => array_sum(array_map('count', $auth_data)),
        'online_onus' => array_sum(array_map(function($rows) {
            return count(array_filter($rows, function($r) {
                return stripos($r['status'] ?? '', 'online') !== false;
            }));
        }, $auth_data)),
    ],
    'ts' => time()
];

cache_set($cache_key, $response);
json_out($response);
