<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

try{
  [$rows,$errors,$reused] = fetch_stats_all($CFG);

  // natural sort by PON, then GPON port, then ONU
  usort($rows,function($a,$b){
    $pa=(int)($a['pon']??0); $pb=(int)($b['pon']??0);
    if($pa!==$pb) return $pa-$pb;
    [$aport,$aonu]=extract_port_onu($a['onuid']??''); [$bport,$bonu]=extract_port_onu($b['onuid']??'');
    if($aport!==$bport) return $aport-$bport;
    return $aonu-$bonu;
  });
  json_out(['ok'=>true,'rows'=>$rows,'errors'=>$errors,'reused'=>$reused]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}

function extract_port_onu($id){
  if (preg_match('~^GPON\d+/(\d+):(\d+)~i',$id,$m)) return [(int)$m[1],(int)$m[2]];
  return [0,0];
}
