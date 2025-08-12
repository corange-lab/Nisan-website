<?php
// Simple one-shot summary for Siri Shortcuts
require __DIR__.'/_bootstrap.php';

header_remove('Content-Type'); // we'll set based on format later

$pon = isset($_GET['pon']) ? intval($_GET['pon']) : null;
if (!$pon || $pon < 1) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Error: provide ?pon=1..8\n"; exit;
}
$onlyOnline = !empty($_GET['online']);
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'txt';
$sep    = isset($_GET['sep']) ? (string)$_GET['sep'] : ' - ';

[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Login error: $err\n"; exit;
}

$urls = [
  'AUTH' => $CFG['BASE'].'/action/onuauthinfo.html',
  'OPT'  => $CFG['BASE'].'/action/pononuopticalinfo.html',
  'WAN'  => $CFG['BASE'].'/action/onuWanv4v6.html',
];

/* 1) AUTH: list of ONUs */
curl_setopt_array($ch, [
  CURLOPT_URL  => $urls['AUTH'],
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Origin: {$CFG['BASE']}",
    "Referer: {$CFG['BASE']}/action/onuauthinfo.html",
    "Content-Type: application/x-www-form-urlencoded",
  ],
  CURLOPT_POSTFIELDS => http_build_query(["select"=>(string)$pon,"authmode"=>"0","who"=>"100","onuid"=>"0"]),
  CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
]);
$html = curl_exec($ch);
if ($html === false) { $e=curl_error($ch); olt_close($ch); header('Content-Type: text/plain; charset=utf-8'); echo "Auth error: $e\n"; exit; }
$authRows = parse_onu_rows($html, $pon);

/* 2) OPTICAL: try payloads, pick best */
$expected = array_values(array_filter(array_map(function($r){ return norm_onuid($r['onuid']??''); }, $authRows)));
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
$best=['score'=>-1,'list'=>[]];
foreach($payloads as $payload){
  curl_setopt_array($ch, [
    CURLOPT_URL=>$urls['OPT'], CURLOPT_POST=>true, CURLOPT_TIMEOUT=>$CFG['OPT_TIMEOUT'],
    CURLOPT_HTTPHEADER=>["Origin: {$CFG['BASE']}","Referer: {$CFG['BASE']}/action/pononuopticalinfo.html","Content-Type: application/x-www-form-urlencoded"],
    CURLOPT_POSTFIELDS=>http_build_query($payload),
  ]);
  $h=curl_exec($ch); if($h===false) continue;
  $list=parse_optical_map($h,null); if(!$list) continue;

  $matchedPon=false; foreach($list as $r){ if(!empty($r['pon']) && (int)$r['pon']===$pon){ $matchedPon=true; break; } }
  $overlap=0; if($expected){ $set=array_flip($expected); foreach($list as $r){ if(!empty($r['onuid_norm']) && isset($set[$r['onuid_norm']])) $overlap++; } }
  $score=($matchedPon?1000:0)+$overlap;
  if($score>$best['score']) $best=['score'=>$score,'list'=>$list];
  if($matchedPon || $overlap>=3) break;
}
$rxByOnu=[]; foreach($best['list'] as $r){ if(!empty($r['onuid_norm']) && isset($r['rx'])) $rxByOnu[$r['onuid_norm']] = $r['rx']; }

/* 3) Compose rows, fetch WAN for Online only */
$rowsOut = [];
foreach ($authRows as $r) {
  $desc = trim($r['desc'] ?? '');
  $status = trim($r['status'] ?? '');
  $onuid = norm_onuid($r['onuid'] ?? '');
  $rx = isset($rxByOnu[$onuid]) ? $rxByOnu[$onuid] : null;

  if ($onlyOnline && stripos($status,'online') === false) continue;

  $wan = 'N/A';
  if (stripos($status,'online') !== false && isset($r['pon'],$r['onu'])) {
    $p = (int)$r['pon']; $o = (int)$r['onu'];
    $url = $urls['WAN'].'?'.http_build_query(['gponid'=>$p,'gonuid'=>$o]);
    curl_setopt_array($ch, [
      CURLOPT_URL=>$url, CURLOPT_HTTPGET=>true, CURLOPT_TIMEOUT=>$CFG['WAN_TIMEOUT'],
      CURLOPT_HTTPHEADER=>["Referer: {$CFG['BASE']}/action/onuconfigsrv.html?ponid={$p}&onuid={$o}&targid=onuTcont.html"],
    ]);
    $wh = curl_exec($ch);
    if ($wh !== false) { $w = parse_wan_status($wh); if ($w) $wan = $w; else $wan='Unknown'; }
    else $wan = 'Unknown';
  }

  $rowsOut[] = [
    'description' => $desc,
    'status'      => $status ?: 'Unknown',
    'wan'         => $wan,
    'rx'          => is_null($rx) ? 'N/A' : round((float)$rx, 2),
  ];
}
olt_close($ch);

/* 4) Output */
if ($format === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>true,'pon'=>$pon,'rows'=>$rowsOut], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} else {
  header('Content-Type: text/plain; charset=utf-8');
  foreach ($rowsOut as $row) {
    echo $row['description'] . $sep . $row['status'] . $sep . $row['wan'] . $sep . $row['rx'] . "\n";
  }
}
