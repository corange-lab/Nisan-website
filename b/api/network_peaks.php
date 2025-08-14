<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

function ensure_rollup_table(PDO $pdo){
  $pdo->exec("CREATE TABLE IF NOT EXISTS rollup_daily(
    ymd TEXT PRIMARY KEY,
    start_ts INTEGER NOT NULL,
    end_ts INTEGER NOT NULL,
    avg_total_mbps REAL,
    max_total_mbps REAL,
    intervals INTEGER NOT NULL DEFAULT 0
  )");
}

function to_num_or_null($v){ if($v===null) return null; $n=+($v); return is_finite($n)?$n:null; }

// Peak from SAMPLES between [start,end)
function peak_from_samples(PDO $pdo, int $start, int $end){
  $qTs = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $qTs->execute([$start,$end]);
  $tsList = array_map(fn($r)=>(int)$r['ts'],$qTs->fetchAll());
  $n=count($tsList);
  if($n<2) return [ 'has_data'=>false ];

  $qRows = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
  $mx=0.0; $mx_t=0; $mx_dt=0;
  for($i=1;$i<$n;$i++){
    $t1=$tsList[$i-1]; $t2=$tsList[$i]; $dt=max(1,$t2-$t1);
    $qRows->execute([$t2]); $curr=$qRows->fetchAll();
    $qRows->execute([$t1]); $prev=$qRows->fetchAll();
    $pm=[]; foreach($prev as $r){ $pm[$r['onuid']]=$r; }
    $tot=0.0;
    foreach($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue; $p=$pm[$id];
      $inC=to_num_or_null($c['input_bytes']);  $inP=to_num_or_null($p['input_bytes']);
      $outC=to_num_or_null($c['output_bytes']); $outP=to_num_or_null($p['output_bytes']);
      if($inC!==null && $inP!==null && $inC >= $inP)   $tot += (($inC-$inP)*8.0)/($dt*1000000.0);
      if($outC!==null && $outP!==null && $outC >= $outP) $tot += (($outC-$outP)*8.0)/($dt*1000000.0);
    }
    if($tot>$mx){ $mx=$tot; $mx_t=$t2; $mx_dt=$dt; }
  }
  return [ 'has_data'=>true, 'total_mbps'=>$mx, 'ts_curr'=>$mx_t, 'dt_sec'=>$mx_dt ];
}

// Peak from ROLLUPS between YMD inclusive range
function peak_from_rollups(PDO $pdo, string $ymd_from, string $ymd_to){
  $q=$pdo->prepare("SELECT MAX(max_total_mbps) AS m FROM rollup_daily WHERE ymd>=? AND ymd<=?");
  $q->execute([$ymd_from,$ymd_to]);
  $m=$q->fetchColumn();
  if($m===false || $m===null) return [ 'has_data'=>false ];
  // represent as day-long window (for UI text)
  $ts = strtotime($ymd_to.' 12:00:00');
  return [ 'has_data'=>true, 'total_mbps'=>(float)$m, 'ts_curr'=>$ts, 'dt_sec'=>86400 ];
}

try{
  $pdo=db($CFG);
  ensure_rollup_table($pdo);

  $now=time();
  $today0=strtotime('today 00:00:00');
  $recentStart = $today0 - 2*86400; // we expect to have raw samples for today, D-1, D-2

  // 24h peak: use raw samples only (we keep 3 days)
  $p24 = peak_from_samples($pdo, $now-86400, $now);

  // 7d peak: combine recent raw (up to 3d) + rollups for older days in window
  $win7_from = date('Y-m-d', $now - 7*86400);
  $cut_roll  = date('Y-m-d', $today0 - 3*86400); // rollups cover days strictly older than D-2
  $p7_raw  = peak_from_samples($pdo, max($recentStart,$now-7*86400), $now);
  $p7_roll = peak_from_rollups($pdo, $win7_from, $cut_roll);
  $p7 = $p7_raw['has_data'] && $p7_roll['has_data']
        ? ( $p7_raw['total_mbps'] >= $p7_roll['total_mbps'] ? $p7_raw : $p7_roll )
        : ( $p7_raw['has_data'] ? $p7_raw : $p7_roll );

  // 30d peak: same mix
  $win30_from = date('Y-m-d', $now - 30*86400);
  $p30_raw  = peak_from_samples($pdo, max($recentStart,$now-30*86400), $now);
  $p30_roll = peak_from_rollups($pdo, $win30_from, $cut_roll);
  $p30 = $p30_raw['has_data'] && $p30_roll['has_data']
        ? ( $p30_raw['total_mbps'] >= $p30_roll['total_mbps'] ? $p30_raw : $p30_roll )
        : ( $p30_raw['has_data'] ? $p30_raw : $p30_roll );

  json_out([
    'ok'=>true,
    'peaks'=>[
      '24h'=>$p24,
      '7d' =>$p7,
      '30d'=>$p30,
    ],
  ]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
