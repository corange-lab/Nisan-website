<?php
require __DIR__.'/../lib/_bootstrap.php';

$pons = $CFG['PONS'] ?? range(1, 8);

[$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
if ($err) json_out(["ok"=>false,"error"=>$err]);

$urls = olt_urls($CFG);

/**
 * Perform a POST to onustatistics.html like the UI does:
 * - pon:       1..8
 * - onu_group: 0 (1–64) or 1 (65–128)
 * - who:       100
 * - onuid:     0
 * - port_refresh: "Refresh" (mimics the Refresh button)
 * - whichfun:  2 (the UI sets this via JS on click)
 */
function fetch_stats_html($ch, $CFG, $urls, $pon, $group){
  curl_setopt_array($ch, [
    CURLOPT_URL => $urls['STATS'],
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Origin: {$CFG['BASE']}",
      "Referer: {$CFG['BASE']}/action/onustatistics.html",
      "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_POSTFIELDS => http_build_query([
      'pon'          => (string)$pon,
      'onu_group'    => (string)$group,  // 0: 1-64, 1: 65-128
      'who'          => '100',
      'onuid'        => '0',
      'port_refresh' => 'Refresh',
      'whichfun'     => '2',
    ]),
    CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
  ]);
  $html = curl_exec($ch);
  if ($html === false) return [null, curl_error($ch)];
  return [$html, null];
}

$byOnu = [];   // onuid => row (last write wins; groups are disjoint, so OK)
$errors = [];

foreach ($pons as $pon) {
  foreach ([0,1] as $group) {
    list($html, $e) = fetch_stats_html($ch, $CFG, $urls, $pon, $group);
    if ($e) { $errors[] = "PON $pon group $group: $e"; continue; }
    $rows = parse_onu_statistics_html($html, $pon);
    foreach ($rows as $r) {
      $byOnu[$r['onuid']] = $r;
    }
  }
}

olt_close($ch);

/**
 * Natural sort by:
 *   1) PON number (r.pon asc)
 *   2) Port number inside the onuid (GPONx/<port>:<onu>)
 *   3) ONU number
 */
usort($byOnuVals = array_values($byOnu), function($a, $b){
  $pa = (int)($a['pon'] ?? 0);
  $pb = (int)($b['pon'] ?? 0);
  if ($pa !== $pb) return $pa - $pb;

  list($aport, $aonu) = extract_port_onu($a['onuid'] ?? '');
  list($bport, $bonu) = extract_port_onu($b['onuid'] ?? '');

  if ($aport !== $bport) return $aport - $bport;
  return $aonu - $bonu;
});

json_out(['ok'=>true,'rows'=>$byOnuVals,'errors'=>$errors,'reused'=>$reused]);

/** Helpers for natural sorting */
function extract_port_onu($id){
  // Expected like: GPON0/1:23  → port=1, onu=23
  // If pattern differs, we fall back to zeros so it doesn't crash sorting.
  if (preg_match('~^GPON\d+/(\d+):(\d+)~i', $id, $m)) {
    return [ (int)$m[1], (int)$m[2] ];
  }
  return [0, 0];
}
