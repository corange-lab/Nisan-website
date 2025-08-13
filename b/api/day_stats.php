<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });
require __DIR__.'/../lib/_bootstrap.php';

try{
  $onuid = isset($_GET['onuid']) ? normalize_onuid($_GET['onuid']) : null;
  $date  = isset($_GET['date'])  ? $_GET['date'] : date('Y-m-d'); // server TZ
  if(!$onuid) json_out(['ok'=>false,'error'=>'Missing onuid']);

  $start = strtotime($date.' 00:00:00');
  $end   = $start + 86400;

  $pdo = db($CFG);
  $stmt = $pdo->prepare("SELECT ts,input_bytes,output_bytes FROM samples WHERE onuid=? AND ts>=? AND ts<? ORDER BY ts ASC");
  $stmt->execute([$onuid,$start,$end]);
  $samples = $stmt->fetchAll();

  $n = count($samples);
  if ($n < 2) json_out(['ok'=>true,'onuid'=>$onuid,'date'=>$date,'count'=>0]);

  $minIn=null;$maxIn=null;$sumIn=0;$cIn=0;
  $minOut=null;$maxOut=null;$sumOut=0;$cOut=0;
  $minTot=null;$maxTot=null;$sumTot=0;$cTot=0;

  for ($i=1; $i<$n; $i++){
    $a=$samples[$i-1]; $b=$samples[$i];
    $dt = (int)$b['ts'] - (int)$a['ts']; if ($dt<=0) continue;

    $inA=to_num($a['input_bytes']);  $inB=to_num($b['input_bytes']);
    $outA=to_num($a['output_bytes']); $outB=to_num($b['output_bytes']);

    $inMbps=$outMbps=$totMbps=null;

    if ($inA!==null && $inB!==null && $inB >= $inA)   $inMbps  = (($inB-$inA)*8.0)/($dt*1000000.0);
    if ($outA!==null && $outB!==null && $outB >= $outA) $outMbps = (($outB-$outA)*8.0)/($dt*1000000.0);
    if ($inMbps!==null || $outMbps!==null) $totMbps = (float)($inMbps?:0) + (float)($outMbps?:0);

    if ($inMbps!==null){ $minIn=min_val($minIn,$inMbps); $maxIn=max_val($maxIn,$inMbps); $sumIn+=$inMbps; $cIn++; }
    if ($outMbps!==null){ $minOut=min_val($minOut,$outMbps); $maxOut=max_val($maxOut,$outMbps); $sumOut+=$outMbps; $cOut++; }
    if ($totMbps!==null){ $minTot=min_val($minTot,$totMbps); $maxTot=max_val($maxTot,$totMbps); $sumTot+=$totMbps; $cTot++; }
  }

  json_out([
    'ok'=>true,'onuid'=>$onuid,'date'=>$date,'count_pairs'=>$cTot,
    'in_mbps'=> stat_pack($minIn,$maxIn,avg($sumIn,$cIn)),
    'out_mbps'=> stat_pack($minOut,$maxOut,avg($sumOut,$cOut)),
    'total_mbps'=> stat_pack($minTot,$maxTot,avg($sumTot,$cTot)),
  ]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}

function min_val($a,$b){ return $a===null?$b:min($a,$b); }
function max_val($a,$b){ return $a===null?$b:max($a,$b); }
function avg($sum,$cnt){ return $cnt>0 ? ($sum/$cnt) : null; }
function stat_pack($min,$max,$avg){ return ['min'=>$min,'max'=>$max,'avg'=>$avg]; }
