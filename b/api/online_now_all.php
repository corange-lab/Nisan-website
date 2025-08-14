<?php
// /b/api/online_now_all.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($n,$s,$f,$l){ throw new ErrorException($s,0,$n,$f,$l); });

try{
  $CFG = require __DIR__.'/../lib/config.php';
  require __DIR__.'/../lib/db.php';
  if (!isset($_SESSION)) session_start();
  $pdo = db($CFG);

  $tsRows = $pdo->query("SELECT ts FROM samples GROUP BY ts ORDER BY ts DESC LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
  if (count($tsRows) < 2) { echo json_encode(['ok'=>true,'has_data'=>false,'rows'=>[]]); exit; }
  $t2 = (int)$tsRows[0]['ts']; $t1 = (int)$tsRows[1]['ts']; $dt = max(1, $t2-$t1);

  $q = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
  $q->execute([$t2]); $curr = $q->fetchAll(PDO::FETCH_ASSOC);
  $q->execute([$t1]); $prev = $q->fetchAll(PDO::FETCH_ASSOC);

  $pm = []; foreach($prev as $r){ $pm[$r['onuid']] = $r; }
  $rows = [];
  $toNum = function($v){ if($v===null||$v==='') return null; return is_numeric($v)?(float)$v:null; };

  foreach($curr as $c){
    $id = $c['onuid']; if (!isset($pm[$id])) continue; $p = $pm[$id];
    $inC=$toNum($c['input_bytes']);  $inP=$toNum($p['input_bytes']);
    $outC=$toNum($c['output_bytes']); $outP=$toNum($p['output_bytes']);

    $up=0.0; $down=0.0;
    if($inC!==null && $inP!==null && $inC >= $inP)   $up   = (($inC-$inP)*8.0)/($dt*1000000.0);
    if($outC!==null && $outP!==null && $outC >= $outP) $down = (($outC-$outP)*8.0)/($dt*1000000.0);
    $tot = $up + $down;

    $lvl = 0;
    if ($tot >= 100) $lvl=5;
    else if ($tot >= 50) $lvl=4;
    else if ($tot >= 20) $lvl=3;
    else if ($tot >= 10) $lvl=2;
    else if ($tot >= 0.1) $lvl=1;

    $rows[$id] = [
      'upload_mbps'   => $up,
      'download_mbps' => $down,
      'total_mbps'    => $tot,
      'online'        => ($up>0 || $down>0),
      'level'         => $lvl
    ];
  }

  echo json_encode(['ok'=>true,'has_data'=>!empty($rows),'rows'=>$rows,'dt_sec'=>$dt,'ts_curr'=>$t2]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
