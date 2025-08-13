<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });
require __DIR__.'/../lib/_bootstrap.php';

try{
  $onuid = isset($_GET['onuid']) ? normalize_onuid($_GET['onuid']) : null;
  if(!$onuid) json_out(['ok'=>false,'error'=>'Missing onuid']);

  $pdo = db($CFG);
  $stmt = $pdo->prepare("SELECT ts, input_bytes, output_bytes FROM samples WHERE onuid=? ORDER BY ts DESC LIMIT 2");
  $stmt->execute([$onuid]);
  $rows = $stmt->fetchAll();

  if (count($rows) < 2){
    json_out(['ok'=>true,'onuid'=>$onuid,'has_data'=>false]);
  }

  $curr = $rows[0]; $prev = $rows[1];
  $dt = max(1, (int)$curr['ts'] - (int)$prev['ts']); // seconds

  $inCurr = to_num($curr['input_bytes']);  $inPrev = to_num($prev['input_bytes']);
  $outCurr= to_num($curr['output_bytes']); $outPrev= to_num($prev['output_bytes']);

  $inMbps = null; $outMbps = null; $totMbps = null;

  if ($inCurr!==null && $inPrev!==null && $inCurr >= $inPrev){
    $inMbps = (($inCurr - $inPrev) * 8.0) / ($dt * 1000000.0);
  }
  if ($outCurr!==null && $outPrev!==null && $outCurr >= $outPrev){
    $outMbps = (($outCurr - $outPrev) * 8.0) / ($dt * 1000000.0);
  }
  if ($inMbps!==null || $outMbps!==null){
    $totMbps = (float)($inMbps?:0) + (float)($outMbps?:0);
  }

  json_out([
    'ok'=>true,'onuid'=>$onuid,'dt_sec'=>$dt,'has_data'=>true,
    'in_mbps'=>$inMbps,'out_mbps'=>$outMbps,'total_mbps'=>$totMbps,
    'at_ts'=>(int)$curr['ts']
  ]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
