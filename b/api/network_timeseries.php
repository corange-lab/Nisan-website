<?php
// /b/api/network_timeseries.php  — self-contained, safe, fast enough for daily views
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($n,$s,$f,$l){ throw new ErrorException($s,0,$n,$f,$l); });

try{
  // Load config + DB directly (no other includes required)
  $CFG = require __DIR__.'/../lib/config.php';
  require __DIR__.'/../lib/db.php';
  if (!isset($_SESSION)) session_start();
  $pdo = db($CFG);

  // Inputs
  $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
  $tf = isset($_GET['tf']) ? strtolower(trim($_GET['tf'])) : '1m';   // 'raw' or '1m'
  if ($tf === '1min') $tf = '1m';

  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  // List distinct snapshot times
  $qTs = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $qTs->execute([$start,$end]);
  $tsList = [];
  foreach($qTs->fetchAll(PDO::FETCH_ASSOC) as $r){ $tsList[] = (int)$r['ts']; }

  $n = count($tsList);
  if ($n < 2){
    echo json_encode(['ok'=>true,'date'=>$date,'tf'=>$tf,'points'=>[]]);
    exit;
  }

  // Prepare row fetch
  $qRows = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
  $toNum = function($v){ if ($v===null || $v==='') return null; return is_numeric($v) ? (float)$v : null; };

  // Build per-interval totals
  $intervals = []; // ['t'=>t2, 'up'=>Mbps, 'down'=>Mbps]
  for ($i=1; $i<$n; $i++){
    $t1=$tsList[$i-1]; $t2=$tsList[$i]; $dt=max(1,$t2-$t1);

    $qRows->execute([$t2]); $curr=$qRows->fetchAll(PDO::FETCH_ASSOC);
    $qRows->execute([$t1]); $prev=$qRows->fetchAll(PDO::FETCH_ASSOC);

    $pm=[]; foreach($prev as $r){ $pm[$r['onuid']]=$r; }

    $up=0.0; $down=0.0;
    foreach($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue;
      $p=$pm[$id];
      $inC=$toNum($c['input_bytes']);  $inP=$toNum($p['input_bytes']);
      $outC=$toNum($c['output_bytes']); $outP=$toNum($p['output_bytes']);
      if($inC!==null && $inP!==null && $inC>=$inP)   $up   += (($inC-$inP)*8.0)/($dt*1000000.0);
      if($outC!==null && $outP!==null && $outC>=$outP) $down += (($outC-$outP)*8.0)/($dt*1000000.0);
    }
    $intervals[] = ['t'=>$t2,'up'=>$up,'down'=>$down];
  }

  if ($tf==='raw' || $tf==='3s' || $tf==='sec'){
    // Light decimation to keep payload reasonable
    $maxPoints = 2000;
    $m = count($intervals);
    if ($m > $maxPoints){
      $step = max(1, (int)ceil($m/$maxPoints));
      $dec=[]; for($i=0;$i<$m;$i+=$step){ $dec[] = $intervals[$i]; }
      if ($dec && end($dec)['t'] !== end($intervals)['t']) $dec[] = end($intervals);
      $intervals = $dec;
    }
    $points=[]; foreach($intervals as $x){
      $points[] = ['t'=>$x['t'],'upload_mbps'=>$x['up'],'download_mbps'=>$x['down']];
    }
    echo json_encode(['ok'=>true,'date'=>$date,'tf'=>'raw','points'=>$points]);
    exit;
  }

  // 1-minute PEAK aggregation (TradingView-style)
  $bucket = []; // ts_minute => ['up'=>max,'down'=>max]
  foreach($intervals as $x){
    $b = (int)floor($x['t']/60)*60;
    if (!isset($bucket[$b])) $bucket[$b] = ['up'=>0.0,'down'=>0.0];
    if ($x['up']   > $bucket[$b]['up'])   $bucket[$b]['up']   = $x['up'];
    if ($x['down'] > $bucket[$b]['down']) $bucket[$b]['down'] = $x['down'];
  }
  ksort($bucket);
  $points = [];
  foreach($bucket as $t=>$v){
    $points[] = ['t'=>$t,'upload_mbps'=>$v['up'],'download_mbps'=>$v['down']];
  }

  echo json_encode(['ok'=>true,'date'=>$date,'tf'=>'1m','points'=>$points]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
