<?php
require __DIR__.'/_bootstrap.php';
require __DIR__.'/../lib/db.php';

// Database-first dashboard API - loads data from DB (FAST) with periodic refresh
$pons = isset($_GET['pons']) ? array_map('intval', explode(',', $_GET['pons'])) : $CFG['PONS'];
$pons = array_filter($pons, function($p) { return $p >= 1; });

if (empty($pons)) {
    json_out(["ok" => false, "error" => "No valid PONs specified"]);
}

try {
    $pdo = db();
    $start_time = microtime(true);
    
    // Get latest snapshot time
    $stmt = $pdo->query("SELECT MAX(last_update) as latest FROM onu_cache LIMIT 1");
    $latestRow = $stmt->fetch();
    $lastUpdate = $latestRow ? $latestRow['latest'] : null;
    
    // Get all ONU data from cache
    $ponList = implode(',', array_map('intval', $pons));
    $query = "
        SELECT 
            pon, onu, onuid, onuid_norm, description, model, 
            status, wan_status, wan_username, wan_mac, rx_power, last_update,
            (SELECT AVG(rx) FROM rx_samples WHERE onuid_norm = onu_cache.onuid_norm AND ts >= (strftime('%s', 'now') - 86400)) as rx_avg_24h
        FROM onu_cache 
        WHERE pon IN ($ponList)
        ORDER BY pon ASC, onu ASC
    ";
    
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by PON
    $grouped = [];
    $stats = [
        'total_onus' => 0,
        'online_onus' => 0,
        'offline_onus' => 0,
    ];
    
    foreach ($results as $row) {
        $pon = $row['pon'];
        if (!isset($grouped[$pon])) {
            $grouped[$pon] = [];
        }
        
        // Calculate delta vs 24h avg
        $delta = null;
        if ($row['rx_power'] !== null && $row['rx_avg_24h'] !== null) {
            $delta = round($row['rx_power'] - $row['rx_avg_24h'], 2);
        }
        
        $grouped[$pon][] = [
            'pon' => (int)$row['pon'],
            'onu' => (int)$row['onu'],
            'onuid' => $row['onuid'],
            'onuid_norm' => $row['onuid_norm'],
            'desc' => $row['description'],
            'model' => $row['model'],
            'status' => $row['status'],
            'wan' => $row['wan_status'] ?: 'N/A',
            'wan_username' => $row['wan_username'],
            'wan_mac' => $row['wan_mac'],
            'rx' => $row['rx_power'],
            'rx_avg_24h' => $row['rx_avg_24h'] ? round($row['rx_avg_24h'], 2) : null,
            'rx_delta' => $delta,
            'last_update' => $row['last_update'],
        ];
        
        $stats['total_onus']++;
        if (stripos($row['status'], 'online') !== false) {
            $stats['online_onus']++;
        } else {
            $stats['offline_onus']++;
        }
    }
    
    $total_time = microtime(true) - $start_time;
    
    // Check if data is stale (older than 5 minutes)
    $dataAge = $lastUpdate ? (time() - strtotime($lastUpdate)) : null;
    $isStale = $dataAge === null || $dataAge > 300; // 5 minutes
    
    json_out([
        'ok' => true,
        'source' => 'database',
        'pons' => $pons,
        'data' => $grouped,
        'stats' => $stats,
        'last_update' => $lastUpdate,
        'data_age_seconds' => $dataAge,
        'is_stale' => $isStale,
        'query_time' => round($total_time, 3),
        'ts' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
