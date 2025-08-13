<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($errstr ?? $str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

try{
  $pdo = db($CFG);

  // optional window tuning (seconds) so we can support a 10s one-shot
  $minWin = isset($_GET['min']) ? max(1, (int)$_GET['min']) : 25;
  $maxWin = isset($_GET['max']) ? max($minWin, (int)$_GET['max']) : 60;

  $tsCurr = (int)$pdo->query("SELECT MAX(ts) AS t FROM samples")->fetch()['t'];
  if (!$tsCurr) json_out(['ok'=>true,'has_data'=>false]);

  $target = $tsCurr - $minWin;
  $lower  = $tsCurr - $maxWin;

  $stmt = $pdo->prepare("SELECT MAX(ts) AS t FROM samples WHERE ts <= ? AND ts >= ?");
  $stmt->execute([$target, $lower]);
  $tsPrev = (int)$stmt->fetch()['t'];

  if (!$tsPrev) {
    $rowsTs = $pdo->query("SELECT ts FROM samples GROUP BY ts ORDER BY ts DESC LIMIT 2")->fetchAll();
    if (count($rowsTs) < 2) json_out(['ok'=>true,'has_data'=>false]);
    $tsPrev = (int)$rowsTs[1]['ts'];
  }

  $getRows = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
  $getRows->execute([$tsCurr]); $currRows = $getRows->fetchAll();
  $getRows->execute([$tsPrev]); $prevRows = $getRows->fetchAll();

  $pm = []; foreach($prevRows as $r){ $pm[$r['onuid']]=$r; }

  $sumUp=0.0;   // inbound == upload
  $sumDown=0.0; // outbound == download
  $pairs=0;
  $dt = max(1, $tsCurr - $tsPrev);

  foreach ($currRows as $c){
    $id = $c['onuid']; if (!isset($pm[$id])) continue;
    $p = $pm[$id];

    $inC  = to_num($c['input_bytes']);  $inP  = to_num($p['input_bytes']);
    $outC = to_num($c['output_bytes']); $outP = to_num($p['output_bytes']);

    if ($inC!==null && $inP!==null && $inC >= $inP)   $sumUp   += (($inC  - $inP)  * 8.0) / ($dt * 1000000.0);
    if ($outC!==null && $outP!==null && $outC >= $outP) $sumDown += (($outC - $outP) * 8.0) / ($dt * 1000000.0);
    $pairs++;
  }

  json_out([
    'ok'=>true,'has_data'=>true,
    'ts_curr'=>$tsCurr,'ts_prev'=>$tsPrev,'dt_sec'=>$dt,
    'upload_mbps'=>$sumUp,      // inbound → upload
    'download_mbps'=>$sumDown,  // outbound → download
    'total_mbps'=>($sumUp+$sumDown),
    'onuid_pairs'=>$pairs
  ]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
