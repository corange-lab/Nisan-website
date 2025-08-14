<?php
// /b/api/stats_all.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($n,$s,$f,$l){ throw new ErrorException($s,0,$n,$f,$l); });

function parsePonFromOnu($onuid){
  if (preg_match('/GPON\d+\/(\d+):(\d+)/i', $onuid, $m)) return (int)$m[1];
  return null;
}

try{
  $CFG = require __DIR__.'/../lib/config.php';
  require __DIR__.'/../lib/db.php';
  if (!isset($_SESSION)) session_start();
  $pdo = db($CFG);

  $t2 = $pdo->query("SELECT ts FROM samples ORDER BY ts DESC LIMIT 1")->fetchColumn();
  if (!$t2) { echo json_encode(['ok'=>true,'rows'=>[]]); exit; }

  $rows = $pdo->prepare("SELECT onuid,input_bytes,output_bytes,pon FROM samples WHERE ts=?");
  $rows->execute([$t2]);
  $data = [];
  while($r = $rows->fetch(PDO::FETCH_ASSOC)){
    $pon = isset($r['pon']) ? $r['pon'] : parsePonFromOnu($r['onuid']);
    $data[] = [
      'onuid'        => $r['onuid'],
      'pon'          => $pon,
      'input_bytes'  => $r['input_bytes'],
      'output_bytes' => $r['output_bytes'],
    ];
  }

  echo json_encode(['ok'=>true,'ts'=>$t2,'rows'=>$data]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
