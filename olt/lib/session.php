<?php
function olt_urls(array $CFG){
  return [
    'LOGIN' => $CFG['BASE'].'/action/main.html',
    'AUTH'  => $CFG['BASE'].'/action/onuauthinfo.html',
    'WAN'   => $CFG['BASE'].'/action/onuWanv4v6.html',
    'OPT'   => $CFG['BASE'].'/action/pononuopticalinfo.html',
  ];
}
function have_valid_cookie($cookie, $ttl){ return $cookie && is_file($cookie) && (time()-filemtime($cookie) < $ttl); }

function olt_login_or_reuse(array $CFG){
  $urls = olt_urls($CFG);
  if (!isset($_SESSION)) session_start();
  $cookie = $_SESSION['olt_cookie'] ?? null;

  // Reuse existing session?
  if (have_valid_cookie($cookie, $CFG['COOKIE_TTL'])) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true,
      CURLOPT_CONNECTTIMEOUT=>$CFG['TIMEOUT'], CURLOPT_TIMEOUT=>$CFG['TIMEOUT'],
      CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false,
      CURLOPT_USERAGENT=>"Mozilla/5.0", CURLOPT_HEADER=>false,
      CURLOPT_COOKIEJAR=>$cookie, CURLOPT_COOKIEFILE=>$cookie,
    ]);
    return [$ch, $cookie, null, true];
  }

  // Fresh login
  $cookie = tempnam(sys_get_temp_dir(), "oltc_");
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_CONNECTTIMEOUT=>$CFG['TIMEOUT'], CURLOPT_TIMEOUT=>$CFG['TIMEOUT'],
    CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false,
    CURLOPT_USERAGENT=>"Mozilla/5.0", CURLOPT_HEADER=>false,
    CURLOPT_COOKIEJAR=>$cookie, CURLOPT_COOKIEFILE=>$cookie,
    CURLOPT_URL=>$urls['LOGIN'], CURLOPT_POST=>true,
    CURLOPT_HTTPHEADER=>["Origin: {$CFG['BASE']}","Referer: {$CFG['BASE']}/action/login.html","Content-Type: application/x-www-form-urlencoded"],
    CURLOPT_POSTFIELDS=>http_build_query(["user"=>$CFG['USERNAME'],"pass"=>$CFG['PASSWORD'],"button"=>"Login","who"=>"100"]),
  ]);
  $resp = curl_exec($ch);
  if ($resp === false) { $err = curl_error($ch); curl_close($ch); @unlink($cookie); return [null,null,"login:$err",false]; }
  $_SESSION['olt_cookie'] = $cookie;
  return [$ch, $cookie, null, false];
}
function olt_close($ch){ if ($ch) curl_close($ch); }
