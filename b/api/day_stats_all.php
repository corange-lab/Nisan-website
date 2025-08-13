<?php
ini_set('display_errors','0'); error_reporting(EALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });
require __DIR__.'/../lib/_bootstrap.php';

try{
  $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  $pdo = db($CFG);
  $stmt = $pdo->prepare("SELECT onuid,ts,input_bytes,output_bytes FROM samples WHERE ts>=? AND ts<? ORDER BY onuid, ts ASC");
  $stmt->execute([$start,$end]);

  $stats = []; // onuid => accumulators
  $last  = []; // onuid => last row

  while ($r = $stmt->fetch()){
    $id = $r['onuid'];
    if (!isset($last[$id])) { $last[$id]=$r; continue; }
    $a = $last[$id]; $b = $r;
    $dt = (int)$b['ts'] - (int)$a['ts']; if ($dt<=0){ $last[$id]=$b; continue; }

    $inA=to_num($a['input_bytes']);  $inB=to_num($b['input_bytes']);
    $outA=to_num($a['output_bytes']); $outB=to_num($b['output_bytes']);

    $up=$down=$tot=null;
    if ($inA!==null && $inB!==null && $inB >= $inA)   $up   = (($inB-$inA)*8.0)/($dt*1000000.0);
    if ($outA!==null && $outB!==null && $outB >= $outA) $down = (($outB-$outA)*8.0)/($dt*1000000.0);
    if ($up!==null || $down!==null) { $tot = (float)($up?:0) + (float)($down?:0); }

    if (!isset($stats[$id])) $stats[$id]=['sumTot'=>0.0,'cntTot'=>0,'maxTot'=>0.0];
    if ($tot!==null){
      $stats[$id]['sumTot'] += $tot;
      $stats[$id]['cntTot'] += 1;
      if ($tot > $stats[$id]['maxTot']) $stats[$id]['maxTot']=$tot;
    }
    $last[$id]=$b;
  }

  $out = [];
  foreach ($stats as $id=>$s){
    $avg = $s['cntTot']>0 ? $s['sumTot']/$s['cntTot'] : null;
    $out[$id] = ['avg_total_mbps'=>$avg,'max_total_mbps'=>$s['maxTot']];
  }

  json_out(['ok'=>true,'date'=>$date,'rows'=>$out]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
