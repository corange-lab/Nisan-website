<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });
require __DIR__.'/../lib/_bootstrap.php';

try{
  $pdo = db($CFG);

  $rowsTs = $pdo->query("SELECT ts FROM samples GROUP BY ts ORDER BY ts DESC LIMIT 2")->fetchAll();
  if (count($rowsTs)<2) json_out(['ok'=>true,'has_data'=>false]);

  $tsCurr = (int)$rowsTs[0]['ts'];
  $tsPrev = (int)$rowsTs[1]['ts'];
  $dt = max(1, $tsCurr - $tsPrev);

  $q = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
  $q->execute([$tsCurr]); $curr = $q->fetchAll();
  $q->execute([$tsPrev]); $prev = $q->fetchAll();
  $pm = []; foreach($prev as $r){ $pm[$r['onuid']]=$r; }

  $levels = []; // onuid => {up,down,total,level}
  foreach ($curr as $c){
    $id=$c['onuid']; if (!isset($pm[$id])) continue; $p=$pm[$id];
    $inC=to_num($c['input_bytes']); $inP=to_num($p['input_bytes']);
    $outC=to_num($c['output_bytes']); $outP=to_num($p['output_bytes']);

    $up=$down=$tot=0.0; $online=false;
    if ($inC!==null && $inP!==null && $inC >= $inP){ $up   = (($inC - $inP) * 8.0) / ($dt * 1000000.0); if ($up>0) $online=true; }
    if ($outC!==null && $outP!==null && $outC >= $outP){ $down = (($outC - $outP) * 8.0) / ($dt * 1000000.0); if ($down>0) $online=true; }
    $tot = $up + $down;

    // map total Mbps to 0..5 bars
    $lvl = 0;
    if ($tot >= 0.1) $lvl = 1;
    if ($tot >= 1.0) $lvl = 2;
    if ($tot >= 5.0) $lvl = 3;
    if ($tot >= 20.0)$lvl = 4;
    if ($tot >= 50.0)$lvl = 5;

    $levels[$id] = ['upload_mbps'=>$up,'download_mbps'=>$down,'total_mbps'=>$tot,'level'=>$lvl,'online'=>$online];
  }

  json_out(['ok'=>true,'has_data'=>true,'ts_curr'=>$tsCurr,'ts_prev'=>$tsPrev,'dt_sec'=>$dt,'rows'=>$levels]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
