<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

try{
  [$rows,$errors,$reused] = fetch_stats_all($CFG);
  $pdo = db($CFG);
  $ts = time();
  insert_samples($pdo,$rows,$ts);
  json_out(['ok'=>true,'inserted'=>count($rows),'ts'=>$ts,'errors'=>$errors,'reused'=>$reused]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
