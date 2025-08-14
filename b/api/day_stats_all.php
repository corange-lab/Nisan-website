<?php
// /b/api/day_stats_all.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($n,$s,$f,$l){ throw new ErrorException($s,0,$n,$f,$l); });

try{
  $CFG = require __DIR__.'/../lib/config.php';
  require __DIR__.'/../lib/db.php';
  if (!isset($_SESSION)) session_start();
  $pdo = db($CFG);

  $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $date = date('Y-m-d');
  $start = strtotime($date.' 00:00:00'); $end = $start + 86400;

  $st = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $st->execute([$start,$end]);
  $tsList = array_map(function($r){ return (int)$r['ts']; }, $st->fetchAll(PDO::FETCH_ASSOC));
  $n = count($tsList);
  if ($n < 2) { echo json_encode(['ok'=>true,'rows'=>[]]); exit; }

  $get = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");
  $toNum = function($v){ if($v===null||$v==='') return null; return is_numeric($v)?(float)$v:null; };

  $sum = []; $cnt = []; $mx = [];

  for($i=1;$i<$n;$i++){
    $t1=$tsList[$i-1]; $t2=$tsList[$i]; $dt=max(1,$t2-$t1);
    $get->execute([$t2]); $curr=$get->fetchAll(PDO::FETCH_ASSOC);
    $get->execute([$t1]); $prev=$get->fetchAll(PDO::FETCH_ASSOC);
    $pm = []; foreach($prev as $r){ $pm[$r['onuid']] = $r; }

    foreach($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue; $p=$pm[$id];
      $inC=$toNum($c['input_bytes']);  $inP=$toNum($p['input_bytes']);
      $outC=$toNum($c['output_bytes']); $outP=$toNum($p['output_bytes']);
      $up=0.0; $down=0.0;
      if($inC!==null && $inP!==null && $inC >= $inP)   $up   = (($inC-$inP)*8.0)/($dt*1000000.0);
      if($outC!==null && $outP!==null && $outC >= $outP) $down = (($outC-$outP)*8.0)/($dt*1000000.0);
      $tot = $up + $down;
      if (!isset($sum[$id])) { $sum[$id]=0.0; $cnt[$id]=0; $mx[$id]=0.0; }
      $sum[$id] += $tot; $cnt[$id] += 1; if ($tot > $mx[$id]) $mx[$id] = $tot;
    }
  }

  $out = [];
  foreach($sum as $id=>$s){
    $out[$id] = [
      'avg_total_mbps' => ($cnt[$id] ? ($s/$cnt[$id]) : null),
      'max_total_mbps' => $mx[$id],
    ];
  }

  echo json_encode(['ok'=>true,'rows'=>$out]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
