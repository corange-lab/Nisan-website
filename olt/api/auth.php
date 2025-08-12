<?php
require __DIR__.'/_bootstrap.php';

$pon = isset($_GET['pon']) ? intval($_GET['pon']) : null;
if ($pon===null || $pon<1) json_out(["ok"=>false,"error"=>"Missing pon"]);

[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) json_out(["ok"=>false,"error"=>$err]);

$urls = olt_urls($CFG);
curl_setopt_array($ch, [
  CURLOPT_URL=>$urls['AUTH'], CURLOPT_POST=>true,
  CURLOPT_HTTPHEADER=>["Origin: {$CFG['BASE']}","Referer: {$CFG['BASE']}/action/onuauthinfo.html","Content-Type: application/x-www-form-urlencoded"],
  CURLOPT_POSTFIELDS=>http_build_query(["select"=>(string)$pon,"authmode"=>"0","who"=>"100","onuid"=>"0"]),
]);
$html = curl_exec($ch);
if ($html===false){ $e=curl_error($ch); olt_close($ch); json_out(["ok"=>false,"error"=>"auth:$e"]); }
$rows = parse_onu_rows($html,$pon);
olt_close($ch);
json_out(["ok"=>true,"pon"=>$pon,"rows"=>$rows,"reused"=>$reused]);
