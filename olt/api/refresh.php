<?php
// Live refresh endpoint - triggers cache update and returns fresh data
require __DIR__.'/_bootstrap.php';
require __DIR__.'/../lib/db.php';

set_time_limit(120); // Allow up to 2 minutes for full refresh

$pons = isset($_GET['pons']) ? array_map('intval', explode(',', $_GET['pons'])) : $CFG['PONS'];
$pons = array_filter($pons, function($p) { return $p >= 1; });

if (empty($pons)) {
    json_out(["ok" => false, "error" => "No valid PONs specified"]);
}

$start_time = microtime(true);
$urls = olt_urls($CFG);

// Login to OLT
[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) {
    json_out(["ok" => false, "error" => $err]);
}

try {
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    $totalUpdated = 0;
    $totalOnline = 0;
    
    // Create cache table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS onu_cache (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pon INTEGER NOT NULL,
            onu INTEGER NOT NULL,
            onuid TEXT NOT NULL,
            onuid_norm TEXT NOT NULL,
            description TEXT,
            model TEXT,
            status TEXT,
            wan_status TEXT,
            rx_power REAL,
            last_update DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(pon, onu)
        )
    ");

    foreach ($pons as $pon) {
        // 1) Get AUTH data
        curl_setopt_array($ch, [
            CURLOPT_URL  => $urls['AUTH'],
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Origin: {$CFG['BASE']}",
                "Referer: {$CFG['BASE']}/action/onuauthinfo.html",
                "Content-Type: application/x-www-form-urlencoded",
            ],
            CURLOPT_POSTFIELDS => http_build_query(["select"=>(string)$pon,"authmode"=>"0","who"=>"100","onuid"=>"0"]),
            CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
        ]);
        $html = curl_exec($ch);
        if ($html === false) continue;
        
        $authRows = parse_onu_rows($html, $pon);
        if (!$authRows) continue;

        $expected = array_map(function($r){ return norm_onuid($r['onuid']??''); }, $authRows);
        $expected = array_values(array_filter($expected));

        // 2) Get OPTICAL data (only for online ONUs)
        $onlineOnus = array_filter($authRows, function($r) {
            return stripos($r['status'] ?? '', 'online') !== false;
        });
        
        $rxByOnu = [];
        if (count($onlineOnus) > 0) {
            // Try best payload for optical data
            $vals = [(string)$pon, "PON".$pon];
            $fields = ['pon', 'select'];
            $payloads = [];
            foreach($fields as $f){ 
                foreach($vals as $v){ 
                    $p = [$f => $v]; 
                    if($f === 'select') $p['who'] = '100'; 
                    $payloads[] = $p; 
                } 
            }

            $best = ['score' => -1, 'list' => []];
            foreach($payloads as $payload){
                curl_setopt_array($ch, [
                    CURLOPT_URL  => $urls['OPT'], 
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        "Origin: {$CFG['BASE']}",
                        "Referer: {$CFG['BASE']}/action/pononuopticalinfo.html",
                        "Content-Type: application/x-www-form-urlencoded",
                    ],
                    CURLOPT_POSTFIELDS => http_build_query($payload),
                    CURLOPT_TIMEOUT => $CFG['OPT_TIMEOUT'],
                ]);
                $h = curl_exec($ch);
                if ($h === false) continue;
                $list = parse_optical_map($h, null);
                if (!$list) continue;

                $matchedPon = false; 
                foreach($list as $r){ 
                    if(!empty($r['pon']) && (int)$r['pon'] === $pon){ 
                        $matchedPon = true; 
                        break; 
                    } 
                }
                
                $overlap = 0;
                if ($expected){
                    $set = array_flip($expected);
                    foreach($list as $r){ 
                        if(!empty($r['onuid_norm']) && isset($set[$r['onuid_norm']])) 
                            $overlap++; 
                    }
                }
                
                $score = ($matchedPon ? 1000 : 0) + $overlap;
                if ($score > $best['score']) {
                    $best = ['score' => $score, 'list' => $list];
                }
                if ($matchedPon || $overlap >= 3) break;
            }

            foreach ($best['list'] as $row) {
                if (!isset($row['rx'])) continue;
                $idn = norm_onuid($row['onuid'] ?? '');
                if ($idn === '') continue;
                $rxByOnu[$idn] = (float)$row['rx'];
            }
        }

        // 3) Update database with auth + optical data
        $stmt = $pdo->prepare("
            INSERT INTO onu_cache (pon, onu, onuid, onuid_norm, description, model, status, rx_power, last_update)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(pon, onu) DO UPDATE SET
                onuid=excluded.onuid,
                onuid_norm=excluded.onuid_norm,
                description=excluded.description,
                model=excluded.model,
                status=excluded.status,
                rx_power=excluded.rx_power,
                last_update=excluded.last_update
        ");

        foreach ($authRows as $row) {
            $idn = norm_onuid($row['onuid'] ?? '');
            $rx = isset($rxByOnu[$idn]) ? $rxByOnu[$idn] : null;
            
            $stmt->execute([
                $row['pon'],
                $row['onu'],
                $row['onuid'],
                $idn,
                $row['desc'],
                $row['model'],
                $row['status'],
                $rx,
                $now
            ]);
            $totalUpdated++;
            
            if (stripos($row['status'], 'online') !== false) {
                $totalOnline++;
            }
        }
    }

    // 4) Update WAN status for ALL online ONUs in the selected PONs
    $ponList = implode(',', array_map('intval', $pons));
    $onlineOnusStmt = $pdo->query("
        SELECT pon, onu, onuid_norm 
        FROM onu_cache 
        WHERE status LIKE '%online%' AND pon IN ($ponList)
        ORDER BY pon ASC, onu ASC
    ");
    $onlineOnusList = $onlineOnusStmt->fetchAll();

    $wanStmt = $pdo->prepare("UPDATE onu_cache SET wan_status = ?, wan_username = ?, wan_mac = ?, last_update = ? WHERE pon = ? AND onu = ?");
    $wanUpdated = 0;
    $wanFailed = 0;

    foreach ($onlineOnusList as $onuRow) {
        $url = $urls['WAN'].'?'.http_build_query(['gponid'=>$onuRow['pon'],'gonuid'=>$onuRow['onu']]);
        curl_setopt_array($ch, [
            CURLOPT_URL=>$url, 
            CURLOPT_HTTPGET=>true, 
            CURLOPT_TIMEOUT=>$CFG['WAN_TIMEOUT'],
            CURLOPT_HTTPHEADER=>["Referer: {$CFG['BASE']}/action/onuconfigsrv.html?ponid={$onuRow['pon']}&onuid={$onuRow['onu']}&targid=onuTcont.html"],
        ]);
        $wh = curl_exec($ch);
        if ($wh !== false) {
            $details = parse_wan_details($wh);
            $wanStatus = $details['status'] ?: 'Unknown';
            $wanUsername = $details['username'];
            $wanMac = $details['mac'];
            
            $wanStmt->execute([$wanStatus, $wanUsername, $wanMac, $now, $onuRow['pon'], $onuRow['onu']]);
            $wanUpdated++;
        } else {
            $wanFailed++;
        }
    }

    olt_close($ch);
    
    $total_time = microtime(true) - $start_time;
    
    json_out([
        'ok' => true,
        'message' => 'Live data refreshed successfully',
        'pons_updated' => count($pons),
        'onus_updated' => $totalUpdated,
        'online_onus' => $totalOnline,
        'wan_updated' => $wanUpdated,
        'wan_failed' => $wanFailed,
        'wan_total' => count($onlineOnusList),
        'refresh_time' => round($total_time, 2),
        'timestamp' => $now,
        'ts' => time()
    ]);
    
} catch (Exception $e) {
    olt_close($ch);
    error_log("Refresh API error: " . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Refresh error: ' . $e->getMessage()]);
}
