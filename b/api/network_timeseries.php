<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

try{
  $pdo   = db($CFG);
  $date  = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  // Distinct snapshot times for the day
  $st = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $st->execute([$start,$end]);
  $tsList = array_map(fn($r)=>(int)$r['ts'],$st->fetchAll());
  $n = count($tsList);
  if ($n < 2) json_out(['ok'=>true,'date'=>$date,'points'=>[]]);

  // To keep payload light: optional step
  $maxPoints = 2000;
  $step = max(1, (int)ceil($n / $maxPoints));

  $get = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");

  $points = [];
  for ($i=$step; $i<$n; $i+=$step){
    $t1 = $tsList[$i-1]; $t2 = $tsList[$i]; $dt = max(1, $t2-$t1);

    $get->execute([$t2]); $curr=$get->fetchAll();
    $get->execute([$t1]); $prev=$get->fetchAll();
    $pm = []; foreach($prev as $r){ $pm[$r['onuid']]=$r; }

    $up=0.0; $down=0.0;
    foreach ($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue; $p=$pm[$id];
      $inC=to_num($c['input_bytes']);  $inP=to_num($p['input_bytes']);
      $outC=to_num($c['output_bytes']); $outP=to_num($p['output_bytes']);
      if ($inC!==null && $inP!==null && $inC >= $inP)   $up   += (($inC-$inP)*8.0)/($dt*1000000.0);
      if ($outC!==null && $outP!==null && $outC >= $outP) $down += (($outC-$outP)*8.0)/($dt*1000000.0);
    }
    $points[] = ['t'=>$t2,'upload_mbps'=>$up,'download_mbps'=>$down,'total_mbps'=>($up+$down)];
  }

  json_out(['ok'=>true,'date'=>$date,'points'=>$points]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
