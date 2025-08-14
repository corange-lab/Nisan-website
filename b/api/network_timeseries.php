<?php
// /b/api/network_timeseries.php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($n,$s,$f,$l){ throw new ErrorException($s,0,$n,$f,$l); });

require __DIR__.'/../lib/_bootstrap.php';

function to_num($v){
  if ($v===null || $v==='') return null;
  if (!is_numeric($v)) return null;
  return (float)$v;
}

try{
  $pdo   = db($CFG);
  $date  = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
  $tf    = isset($_GET['tf']) ? strtolower(trim($_GET['tf'])) : '1m'; // default 1m peak
  if ($tf==='1min') $tf='1m';
  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  // Get distinct timestamps in order
  $st = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $st->execute([$start,$end]);
  $tsList = array_map(function($r){ return (int)$r['ts']; }, $st->fetchAll());
  $n = count($tsList);
  if ($n < 2) { json_out(['ok'=>true,'date'=>$date,'points'=>[],'tf'=>$tf]); return; }

  $get = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");

  // Build per-interval speeds at t2
  $intervals = []; // each: ['t'=>t2,'up'=>Mbps,'down'=>Mbps,'tot'=>Mbps]
  for ($i=1;$i<$n;$i++){
    $t1=$tsList[$i-1]; $t2=$tsList[$i]; $dt = max(1, $t2-$t1);

    $get->execute([$t2]); $curr=$get->fetchAll();
    $get->execute([$t1]); $prev=$get->fetchAll();
    $pm=[]; foreach($prev as $r){ $pm[$r['onuid']]=$r; }

    $up=0.0; $down=0.0;
    foreach($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue; $p=$pm[$id];
      $inC=to_num($c['input_bytes']);  $inP=to_num($p['input_bytes']);
      $outC=to_num($c['output_bytes']); $outP=to_num($p['output_bytes']);
      if ($inC!==null && $inP!==null && $inC >= $inP)   $up   += (($inC-$inP)*8.0)/($dt*1000000.0);
      if ($outC!==null && $outP!==null && $outC >= $outP) $down += (($outC-$outP)*8.0)/($dt*1000000.0);
    }
    $intervals[] = ['t'=>$t2,'up'=>$up,'down'=>$down,'tot'=>$up+$down];
  }

  // Timeframe: raw (≈3s) or 1m max
  if ($tf==='raw' || $tf==='3s' || $tf==='sec'){
    // (Optional) server-side decimation to avoid huge payloads
    $maxPoints = 2000;
    $m = count($intervals);
    if ($m > $maxPoints){
      $step = (int)ceil($m / $maxPoints);
      $dec=[]; for($i=0;$i<$m;$i+=$step){ $dec[] = $intervals[$i]; }
      if (end($dec)['t'] !== end($intervals)['t']) $dec[] = end($intervals);
      $intervals = $dec;
    }
    $points = array_map(function($x){
      return ['t'=>$x['t'],'upload_mbps'=>$x['up'],'download_mbps'=>$x['down']];
    }, $intervals);
    json_out(['ok'=>true,'date'=>$date,'tf'=>'raw','points'=>$points]);
    return;
  }

  // 1-minute MAX aggregation (bucket by floor(t/60)*60)
  $buckets = []; // tsBucket => ['up'=>max,'down'=>max]
  foreach ($intervals as $x){
    $b = (int)floor($x['t']/60)*60;
    if (!isset($buckets[$b])) $buckets[$b] = ['up'=>0.0,'down'=>0.0];
    if ($x['up']   > $buckets[$b]['up'])   $buckets[$b]['up']   = $x['up'];
    if ($x['down'] > $buckets[$b]['down']) $buckets[$b]['down'] = $x['down'];
  }
  ksort($buckets);
  $points=[];
  foreach ($buckets as $t=>$v){
    $points[] = ['t'=>$t,'upload_mbps'=>$v['up'],'download_mbps'=>$v['down']];
  }

  json_out(['ok'=>true,'date'=>$date,'tf'=>'1m','points'=>$points]);

}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
