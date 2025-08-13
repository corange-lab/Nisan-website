<?php
require __DIR__.'/../lib/_bootstrap.php';

$pons = $CFG['PONS'] ?? range(1,8);

[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) json_out(["ok"=>false,"error"=>$err]);

$urls = olt_urls($CFG);
function fetch_stats_html($ch,$CFG,$urls,$pon,$group){
  curl_setopt_array($ch, [
    CURLOPT_URL=>$urls['STATS'],
    CURLOPT_POST=>true,
    CURLOPT_HTTPHEADER=>[
      "Origin: {$CFG['BASE']}",
      "Referer: {$CFG['BASE']}/action/onustatistics.html",
      "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_POSTFIELDS=>http_build_query([
      'pon'=>(string)$pon,
      'onu_group'=>(string)$group, // 0: 1-64, 1: 65-128
      'who'=>'100',
      'onuid'=>'0',
    ]),
    CURLOPT_TIMEOUT=>$CFG['TIMEOUT'],
  ]);
  $html = curl_exec($ch);
  if ($html === false) return [null, curl_error($ch)];
  return [$html, null];
}

$byOnu = [];   // onuid => row (last write wins)
$errors = [];

foreach ($pons as $pon) {
  foreach ([0,1] as $group) {
    list($html,$e) = fetch_stats_html($ch,$CFG,$urls,$pon,$group);
    if ($e) { $errors[] = "PON $pon group $group: $e"; continue; }
    $rows = parse_onu_statistics_html($html,$pon);
    foreach ($rows as $r) {
      $byOnu[$r['onuid']] = $r;
    }
  }
}

olt_close($ch);
json_out(['ok'=>true,'rows'=>array_values($byOnu),'errors'=>$errors,'reused'=>$reused]);
