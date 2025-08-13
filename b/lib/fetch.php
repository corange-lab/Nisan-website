<?php
// fetch all stats (PON 1..8 x groups 0/1) as bytes-only rows
function fetch_stats_all($CFG){
  [$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
  if ($err) return [[], ["login:$err"], false];

  $urls = olt_urls($CFG);
  $errors = [];
  $byOnu = [];

  $fetch = function($pon,$group) use ($ch,$CFG,$urls){
    curl_setopt_array($ch, [
      CURLOPT_URL => $urls['STATS'],
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        "Origin: {$CFG['BASE']}",
        "Referer: {$CFG['BASE']}/action/onustatistics.html",
        "Content-Type: application/x-www-form-urlencoded"
      ],
      CURLOPT_POSTFIELDS => http_build_query([
        'pon'=>(string)$pon, 'onu_group'=>(string)$group,
        'who'=>'100','onuid'=>'0','port_refresh'=>'Refresh','whichfun'=>'2'
      ]),
      CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
    ]);
    $html = curl_exec($ch);
    if ($html === false) return [null, curl_error($ch)];
    return [$html, null];
  };

  foreach (($CFG['PONS'] ?? range(1,8)) as $pon){
    foreach ([0,1] as $g){
      [$html,$e] = $fetch($pon,$g);
      if ($e){ $errors[] = "PON $pon group $g: $e"; continue; }
      $rows = parse_onu_statistics_html($html,$pon);
      foreach ($rows as $r){ $byOnu[$r['onuid']] = $r; }
    }
  }
  olt_close($ch);
  return [ array_values($byOnu), $errors, $reused ];
}
