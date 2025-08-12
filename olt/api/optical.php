<?php
require __DIR__.'/_bootstrap.php';

$pon = isset($_GET['pon']) ? intval($_GET['pon']) : null;
if ($pon===null || $pon<1) json_out(["ok"=>false,"error"=>"Missing pon"]);

$idsParam = isset($_GET['ids']) ? $_GET['ids'] : '';
$expected = array_filter(array_map('norm_onuid', preg_split('/[|,]+/',$idsParam,-1,PREG_SPLIT_NO_EMPTY)));

$ckey="optical_pon_$pon"; if(($cached=cache_get($ckey,$CFG['OPT_CACHE_TTL']))) json_out($cached);

[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) json_out(["ok"=>false,"error"=>$err]);

$urls = olt_urls($CFG);
$vals   = [ (string)$pon, "PON".$pon, "GPON0/".$pon, "GPON".$pon ];
$fields = ['select','ponid','portid','pon','port'];
$payloads=[];
foreach($fields as $f){ foreach($vals as $v){ $p=[$f=>$v]; if($f==='select') $p['who']='100'; $payloads[]=$p; } }
foreach(['group','onugroup'] as $gk){
  foreach(['1','0','ONU 1-64'] as $gv){
    $payloads[]=['select'=>(string)$pon,'who'=>'100',$gk=>$gv];
    $payloads[]=['ponid'=>(string)$pon,$gk=>$gv];
    $payloads[]=['portid'=>"PON".$pon,$gk=>$gv];
    $payloads[]=['portid'=>"GPON0/".$pon,$gk=>$gv];
  }
}

$best=['score'=>-1,'list'=>[],'payload'=>null,'matchedPon'=>false];
foreach($payloads as $payload){
  curl_setopt_array($ch, [
    CURLOPT_URL=>$urls['OPT'], CURLOPT_POST=>true, CURLOPT_TIMEOUT=>$CFG['OPT_TIMEOUT'],
    CURLOPT_HTTPHEADER=>["Origin: {$CFG['BASE']}","Referer: {$CFG['BASE']}/action/pononuopticalinfo.html","Content-Type: application/x-www-form-urlencoded"],
    CURLOPT_POSTFIELDS=>http_build_query($payload),
  ]);
  $html=curl_exec($ch); if($html===false) continue;
  $list=parse_optical_map($html,null); if(empty($list)) continue;

  $matchedPon=false; foreach($list as $r){ if(!empty($r['pon']) && (int)$r['pon']===$pon){ $matchedPon=true; break; } }
  $overlap=0; if(!empty($expected)){ $set=array_flip($expected); foreach($list as $r){ if(!empty($r['onuid_norm']) && isset($set[$r['onuid_norm']])) $overlap++; } }
  $score = ($matchedPon?1000:0) + $overlap;

  if ($score > $best['score']){
    $best=['score'=>$score,'list'=>$list,'payload'=>$payload,'matchedPon'=>$matchedPon];
    if ($matchedPon || $overlap>=3) break;
  }
}
olt_close($ch);

$resp=["ok"=>true,"pon"=>$pon,"rx"=>$best['list'],"matchedPon"=>$best['matchedPon'],"payloadUsed"=>$best['payload'],"score"=>$best['score'],"ts"=>time()];
cache_set($ckey,$resp); json_out($resp);
