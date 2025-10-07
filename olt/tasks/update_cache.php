<?php
// Background task to update ONU cache in database
// Run this every 2-5 minutes via cron for optimal performance
// php tasks/update_cache.php

$CFG = require __DIR__ . '/../lib/config.php';
require __DIR__ . '/../lib/util.php';
require __DIR__ . '/../lib/cache.php';
require __DIR__ . '/../lib/session.php';
require __DIR__ . '/../lib/parsers.php';
require __DIR__ . '/../lib/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting ONU cache update...\n";

$urls = (function($CFG){
  return [
    'LOGIN' => $CFG['BASE'].'/action/main.html',
    'AUTH'  => $CFG['BASE'].'/action/onuauthinfo.html',
    'WAN'   => $CFG['BASE'].'/action/onuWanv4v6.html',
    'OPT'   => $CFG['BASE'].'/action/pononuopticalinfo.html',
  ];
})($CFG);

// Create cache table if not exists
try {
    $pdo = db();
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
            wan_username TEXT,
            wan_mac TEXT,
            rx_power REAL,
            last_update DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(pon, onu)
        )
    ");
    
    // Add new columns if they don't exist (migration)
    try {
        $pdo->exec("ALTER TABLE onu_cache ADD COLUMN wan_username TEXT");
    } catch (Exception $e) {
        // Column already exists
    }
    try {
        $pdo->exec("ALTER TABLE onu_cache ADD COLUMN wan_mac TEXT");
    } catch (Exception $e) {
        // Column already exists
    }
    
    // Create index for faster queries
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pon ON onu_cache(pon)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_onuid_norm ON onu_cache(onuid_norm)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_status ON onu_cache(status)");
    
    echo "Database tables ready\n";
} catch (Exception $e) {
    fwrite(STDERR, "Database error: " . $e->getMessage() . "\n");
    exit(1);
}

// Reuse single login
[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) { 
    fwrite(STDERR, "LOGIN ERROR: $err\n"); 
    exit(1); 
}

$pdo = db();
$now = date('Y-m-d H:i:s');
$totalUpdated = 0;
$totalOnline = 0;

foreach ($CFG['PONS'] as $pon) {
    echo "Processing PON $pon...\n";
    
    // 1) Get AUTH data (list of ONUs)
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
    if ($html === false) { 
        fwrite(STDERR, "AUTH PON $pon: ".curl_error($ch)."\n"); 
        continue; 
    }
    $authRows = parse_onu_rows($html, $pon);
    if (!$authRows) continue;

    $expected = array_map(fn($r)=>norm_onuid($r['onuid']??''), $authRows);
    $expected = array_values(array_filter($expected));

    // 2) Get OPTICAL data (RX power) - only for online ONUs
    $onlineOnus = array_filter($authRows, function($r) {
        return stripos($r['status'] ?? '', 'online') !== false;
    });
    
    $rxByOnu = [];
    if (count($onlineOnus) > 0) {
        $vals   = [ (string)$pon, "PON".$pon, "GPON0/".$pon, "GPON".$pon ];
        $fields = ['select','ponid','portid','pon','port'];
        $payloads=[];
        foreach($fields as $f){ 
            foreach($vals as $v){ 
                $p=[$f=>$v]; 
                if($f==='select') $p['who']='100'; 
                $payloads[]=$p; 
            } 
        }

        $best = ['score'=>-1,'list'=>[]];
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
                if(!empty($r['pon']) && (int)$r['pon']===$pon){ 
                    $matchedPon=true; 
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
            $score = ($matchedPon?1000:0) + $overlap;
            if ($score > $best['score']) $best = ['score'=>$score,'list'=>$list];
            if ($matchedPon || $overlap >= 3) break;
        }

        foreach ($best['list'] as $row) {
            if (!isset($row['rx'])) continue;
            $idn = norm_onuid($row['onuid'] ?? '');
            if ($idn==='') continue;
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

    echo "  Updated " . count($authRows) . " ONUs (" . count($onlineOnus) . " online)\n";
}

// 4) Update WAN status for ALL online ONUs
$onlineOnusStmt = $pdo->query("
    SELECT pon, onu, onuid_norm 
    FROM onu_cache 
    WHERE status LIKE '%online%'
    ORDER BY pon ASC, onu ASC
");
$onlineOnusList = $onlineOnusStmt->fetchAll();

echo "\nUpdating WAN details (status, username, MAC) for " . count($onlineOnusList) . " online ONUs...\n";
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
        
        // Show progress every 10 ONUs
        if ($wanUpdated % 10 == 0) {
            echo "  Progress: $wanUpdated/" . count($onlineOnusList) . " WAN details updated\n";
        }
    } else {
        $wanFailed++;
        echo "  Warning: Failed to get WAN for PON {$onuRow['pon']} ONU {$onuRow['onu']}\n";
    }
}

olt_close($ch);

echo "\n=== CACHE UPDATE COMPLETE ===\n";
echo "Total ONUs updated: $totalUpdated\n";
echo "Online ONUs: $totalOnline\n";
echo "WAN status updated: $wanUpdated\n";
echo "Timestamp: $now\n";
