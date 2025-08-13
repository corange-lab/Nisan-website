<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

try{
  $pdo = db($CFG);
  $now = time();
  $windows = [
    '24h' => $now - 24*3600,
    '7d'  => $now - 7*86400,
    '30d' => $now - 30*86400,
  ];

  $out = [];

  foreach ($windows as $label=>$startTs) {
    // list distinct timestamps in window
    $stmt = $pdo->prepare("SELECT ts FROM samples WHERE ts >= ? GROUP BY ts ORDER BY ts ASC");
    $stmt->execute([$startTs]);
    $tsList = array_map(function($r){ return (int)$r['ts']; }, $stmt->fetchAll());

    if (empty($tsList)) { $out[$label] = ['has_data'=>false]; continue; }

    // downsample to keep runtime reasonable (max ~6000 evaluations)
    $n = count($tsList);
    $step = max(1, (int)ceil($n / 6000));

    $best = ['has_data'=>false, 'total_mbps'=>0.0, 'in_mbps'=>0.0, 'out_mbps'=>0.0, 'ts_curr'=>null, 'ts_prev'=>null, 'dt_sec'=>null];

    // helper to compute network speed between a ts_prev and ts_curr (same logic as network_speed_from_last)
    $calc = function(int $tsCurr) use ($pdo){
      // choose a prev sample ~30s back (25–60s)
      $target = $tsCurr - 25;
      $lower  = $tsCurr - 60;
      $stmt = $pdo->prepare("SELECT MAX(ts) AS t FROM samples WHERE ts <= ? AND ts >= ?");
      $stmt->execute([$target,$lower]);
      $tsPrev = (int)$stmt->fetch()['t'];
      if (!$tsPrev) return null;

      // load both snapshots
      $q = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
      $q->execute([$tsCurr]); $curr = $q->fetchAll();
      $q->execute([$tsPrev]); $prev = $q->fetchAll();
      $pm = []; foreach ($prev as $r){ $pm[$r['onuid']]=$r; }

      $dt = max(1, $tsCurr - $tsPrev);
      $sumIn = 0.0; $sumOut = 0.0; $pairs=0;

      foreach ($curr as $c){
        $id = $c['onuid']; if (!isset($pm[$id])) continue;
        $p = $pm[$id];

        $inC  = to_num($c['input_bytes']);  $inP  = to_num($p['input_bytes']);
        $outC = to_num($c['output_bytes']); $outP = to_num($p['output_bytes']);

        if ($inC!==null && $inP!==null && $inC >= $inP)   $sumIn  += (($inC - $inP) * 8.0) / ($dt * 1000000.0);
        if ($outC!==null && $outP!==null && $outC >= $outP) $sumOut += (($outC - $outP) * 8.0) / ($dt * 1000000.0);
        $pairs++;
      }
      return ['in'=>$sumIn,'out'=>$sumOut,'dt'=>$dt,'ts_prev'=>$tsPrev,'pairs'=>$pairs];
    };

    for ($i=0; $i<$n; $i+=$step){
      $ts = $tsList[$i];
      $r = $calc($ts);
      if (!$r) continue;
      $total = $r['in'] + $r['out'];
      if (!$best['has_data'] || $total > $best['total_mbps']){
        $best = [
          'has_data'=>true,
          'total_mbps'=>$total, 'in_mbps'=>$r['in'], 'out_mbps'=>$r['out'],
          'ts_curr'=>$ts, 'ts_prev'=>$r['ts_prev'], 'dt_sec'=>$r['dt']
        ];
      }
    }

    $out[$label] = $best;
  }

  json_out(['ok'=>true,'peaks'=>$out]);

}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
