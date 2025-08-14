<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){
  throw new ErrorException($str, 0, $no, $file, $line);
});

require __DIR__.'/../lib/_bootstrap.php';

try{
  // Date window (server timezone). You can pass ?date=YYYY-MM-DD
  $date  = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  $pdo = db($CFG);

  // Stream rows ordered by ONU then timestamp
  $stmt = $pdo->prepare("
    SELECT onuid, ts, input_bytes, output_bytes
    FROM samples
    WHERE ts >= :s AND ts < :e
    ORDER BY onuid ASC, ts ASC
  ");
  $stmt->execute([':s'=>$start, ':e'=>$end]);

  $stats = []; // onuid => ['sum'=>float,'cnt'=>int,'max'=>float]
  $last  = []; // onuid => last row used for delta

  while ($r = $stmt->fetch()){
    $id = $r['onuid'];

    // Initialize bins
    if (!isset($stats[$id])) $stats[$id] = ['sum'=>0.0,'cnt'=>0,'max'=>0.0];

    // Need a previous point to measure speed
    if (!isset($last[$id])) { $last[$id] = $r; continue; }

    $a = $last[$id];
    $b = $r;
    $dt = (int)$b['ts'] - (int)$a['ts'];
    if ($dt <= 0) { $last[$id] = $b; continue; }

    // Convert counters to Mbps (skip NULLs / wraps)
    $inA  = to_num($a['input_bytes']);   $inB  = to_num($b['input_bytes']);
    $outA = to_num($a['output_bytes']);  $outB = to_num($b['output_bytes']);

    $up = null; $down = null;
    if ($inA  !== null && $inB  !== null && $inB  >= $inA)   $up   = (($inB  - $inA)  * 8.0) / ($dt * 1000000.0);
    if ($outA !== null && $outB !== null && $outB >= $outA)  $down = (($outB - $outA) * 8.0) / ($dt * 1000000.0);

    if ($up !== null || $down !== null) {
      $tot = (float)($up ?: 0) + (float)($down ?: 0);
      $s =& $stats[$id];
      $s['sum'] += $tot;
      $s['cnt'] += 1;
      if ($tot > $s['max']) $s['max'] = $tot;
    }

    $last[$id] = $b;
  }

  // Build output
  $out = [];
  foreach ($stats as $id=>$s){
    if ($s['cnt'] > 0) {
      $out[$id] = [
        'avg_total_mbps' => $s['sum'] / $s['cnt'],
        'max_total_mbps' => $s['max'],
      ];
    }
  }

  json_out(['ok'=>true,'date'=>$date,'rows'=>$out]);

}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
