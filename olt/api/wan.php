<?php
require __DIR__.'/_bootstrap.php';

$pon = isset($_GET['pon']) ? intval($_GET['pon']) : null;
$onu = isset($_GET['onu']) ? intval($_GET['onu']) : null;
if (!$pon || !$onu) json_out(["ok"=>false,"error"=>"Missing pon/onu"]);

$ckey="wan_{$pon}_{$onu}"; if(($cached=cache_get($ckey,$CFG['WAN_CACHE_TTL']))) json_out($cached);

[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) json_out(["ok"=>false,"error"=>$err]);

$urls = olt_urls($CFG);
$url = $urls['WAN'].'?'.http_build_query(['gponid'=>$pon,'gonuid'=>$onu]);
curl_setopt_array($ch, [
  CURLOPT_URL=>$url, CURLOPT_HTTPGET=>true, CURLOPT_TIMEOUT=>$CFG['WAN_TIMEOUT'],
  CURLOPT_HTTPHEADER=>["Referer: {$CFG['BASE']}/action/onuconfigsrv.html?ponid={$pon}&onuid={$onu}&targid=onuTcont.html"],
]);
$html=curl_exec($ch);
if($html===false){ $e=curl_error($ch); olt_close($ch); json_out(["ok"=>false,"error"=>"wan:$e"]); }

// Use detailed parser to get status, username, and MAC
$details = parse_wan_details($html);
$status = $details['status'] ?: 'Unknown';
$username = $details['username'];
$mac = $details['mac'];

olt_close($ch);
$resp=["ok"=>true,"pon"=>$pon,"onu"=>$onu,"status"=>$status,"username"=>$username,"mac"=>$mac,"ts"=>time()];
cache_set($ckey,$resp); json_out($resp);
