<?php
// /b/api/network_timeseries.php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($n,$s,$f,$l){ throw new ErrorException($s,0,$n,$f,$l); });

require __DIR__.'/../lib/_bootstrap.php';

function num_or_null($v){
  if ($v===null || $v==='') return null;
  return is_numeric($v) ? (float)$v : null;
}

try{
  $pdo   = db($CFG);
  $date  = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
  $tf    = isset($_GET['tf']) ? strtolower(trim($_GET['tf'])) : '1m'; // 'raw' or '1m'
  if ($tf==='1min') $tf='1m';

  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  // Collect distinct timestamps for the day
  $st = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $st->execute([$start,$end]);
  $tsRows = $st->fetchAll();
  $tsList = [];
  foreach($tsRows as $r){ $tsList[] = (int)$r['ts']; }
  $n = count($tsList);

  if ($n < 2) {
    echo json_encode(['ok'=>true,'date'=>$date,'tf'=>$tf,'points'=>[]]);
    exit;
  }

  $get = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");

  // Build per-interval speeds
  $intervals = []; // each: ['t'=>t2,'up'=>Mbps,'down'=>Mbps]
  for ($i=1;$i<$n;$i++){
    $t1=$tsList[$i-1]; $t2=$tsList[$i]; $dt=max(1,$t2-$t1);

    $get->execute([$t2]); $curr=$get->fetchAll();
    $get->execute([$t1]); $prev=$get->fetchAll();
    $pm=[]; foreach($prev as $r){ $pm[$r['onuid']]=$r; }

    $up=0.0; $down=0.0;
    foreach($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue; $p=$pm[$id];
      $inC=num_or_null($c['input_bytes']);  $inP=num_or_null($p['input_bytes']);
      $outC=num_or_null($c['output_bytes']); $outP=num_or_null($p['output_bytes']);
      if($inC!==null && $inP!==null && $inC >= $inP)   $up   += (($inC-$inP)*8.0)/($dt*1000000.0);
      if($outC!==null && $outP!==null && $outC >= $outP) $down += (($outC-$outP)*8.0)/($dt*1000000.0);
    }
    $intervals[] = ['t'=>$t2,'up'=>$up,'down'=>$down];
  }

  if ($tf==='raw' || $tf==='3s' || $tf==='sec'){
    // decimate to keep payload reasonable
    $maxPoints = 2000;
    $m = count($intervals);
    if ($m > $maxPoints){
      $step = (int)ceil($m / $maxPoints);
      $dec=[]; for($i=0;$i<$m;$i+=$step){ $dec[] = $intervals[$i]; }
      if ($dec && $intervals) {
        $lastDec = $dec[count($dec)-1]; $lastAll = $intervals[count($intervals)-1];
        if ($lastDec['t'] !== $lastAll['t']) $dec[] = $lastAll;
      }
      $intervals = $dec;
    }
    $points=[]; foreach($intervals as $x){
      $points[] = ['t'=>$x['t'],'upload_mbps'=>$x['up'],'download_mbps'=>$x['down']];
    }
    echo json_encode(['ok'=>true,'date'=>$date,'tf'=>'raw','points'=>$points]);
    exit;
  }

  // 1-minute MAX aggregation
  $buckets = []; // ts_minute => ['up'=>max,'down'=>max]
  foreach ($intervals as $x){
    $b = (int)floor($x['t']/60)*60;
    if (!isset($buckets[$b])) $buckets[$b] = ['up'=>0.0,'down'=>0.0];
    if ($x['up']   > $buckets[$b]['up'])   $buckets[$b]['up']   = $x['up'];
    if ($x['down'] > $buckets[$b]['down']) $buckets[$b]['down'] = $x['down'];
  }
  ksort($buckets);
  $points=[];
  foreach($buckets as $t=>$v){
    $points[] = ['t'=>$t,'upload_mbps'=>$v['up'],'download_mbps'=>$v['down']];
  }

  echo json_encode(['ok'=>true,'date'=>$date,'tf'=>'1m','points'=>$points]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
