
<?php
// php tasks/collect.php  (run hourly via cron)
// Grabs RX per ONU for all configured PONs and stores in DB.

$CFG = require __DIR__ . '/../lib/config.php';
require __DIR__ . '/../lib/util.php';
require __DIR__ . '/../lib/cache.php';
require __DIR__ . '/../lib/session.php';
require __DIR__ . '/../lib/parsers.php';
require __DIR__ . '/../lib/db.php';

$urls = (function($CFG){
  return [
    'LOGIN' => $CFG['BASE'].'/action/main.html',
    'AUTH'  => $CFG['BASE'].'/action/onuauthinfo.html',
    'WAN'   => $CFG['BASE'].'/action/onuWanv4v6.html',
    'OPT'   => $CFG['BASE'].'/action/pononuopticalinfo.html',
  ];
})($CFG);

// Reuse single login
[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) { fwrite(STDERR, "LOGIN ERROR: $err\n"); exit(1); }

$pdo = db();
$now = time();
$total = 0;

foreach ($CFG['PONS'] as $pon) {
  // 1) AUTH — to get expected ONU IDs
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
  if ($html === false) { fwrite(STDERR, "AUTH PON $pon: ".curl_error($ch)."\n"); continue; }
  $authRows = parse_onu_rows($html, $pon);
  if (!$authRows) continue;

  $expected = array_map(fn($r)=>norm_onuid($r['onuid']??''), $authRows);
  $expected = array_values(array_filter($expected));

  // 2) OPTICAL — try multiple payloads and pick list that overlaps expected ONU IDs
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

  $best = ['score'=>-1,'list'=>[]];
  foreach($payloads as $payload){
    curl_setopt_array($ch, [
      CURLOPT_URL  => $urls['OPT'], CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        "Origin: {$CFG['BASE']}",
        "Referer: {$CFG['BASE']}/action/pononuopticalinfo.html",
        "Content-Type: application/x-www-form-urlencoded",
      ],
      CURLOPT_POSTFIELDS => http_build_query($payload),
      CURLOPT_TIMEOUT => $CFG['OPT_TIMEOUT'],
    ]);
    $h = curl_exec($ch);
    if ($h === false) continue;
    $list = parse_optical_map($h, null);
    if (!$list) continue;

    $matchedPon = false; foreach($list as $r){ if(!empty($r['pon']) && (int)$r['pon']===$pon){ $matchedPon=true; break; } }
    $overlap = 0;
    if ($expected){
      $set = array_flip($expected);
      foreach($list as $r){ if(!empty($r['onuid_norm']) && isset($set[$r['onuid_norm']])) $overlap++; }
    }
    $score = ($matchedPon?1000:0) + $overlap;
    if ($score > $best['score']) $best = ['score'=>$score,'list'=>$list];
    if ($matchedPon || $overlap >= 3) break;
  }

  // 3) Store RX samples
  $ins = $pdo->prepare("INSERT INTO rx_samples(onuid_norm, pon, onu, rx, ts) VALUES(?,?,?,?,?)");
  foreach ($best['list'] as $row) {
    if (!isset($row['rx'])) continue;
    $idn = norm_onuid($row['onuid'] ?? '');
    if ($idn==='') continue;
    $rx = (float)$row['rx'];
    $ponRow = isset($row['pon']) ? (int)$row['pon'] : $pon;
    $onuRow = isset($row['onu']) ? (int)$row['onu'] : null;
    $ins->execute([$idn, $ponRow, $onuRow, $rx, $now]);
    $total++;
  }
}

olt_close($ch);
echo "Inserted $total RX samples @ ".date('c',$now)."\n";
