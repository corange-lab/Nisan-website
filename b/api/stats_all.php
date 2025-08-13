<?php
// ---- Robust error reporting to JSON (only for this endpoint) ----
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function($errno,$errstr,$errfile,$errline){
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require __DIR__.'/../lib/_bootstrap.php';

try {
  $pons = $CFG['PONS'] ?? range(1, 8);

  // Login or reuse cookie
  [$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
  if ($err) json_out(["ok"=>false,"error"=>$err]);

  $urls = olt_urls($CFG);

  /**
   * Post exactly like the UI's Refresh:
   * pon: 1..8, onu_group: 0/1, who:100, onuid:0, port_refresh=Refresh, whichfun=2
   */
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
    if ($html === false) {
      return [null, curl_error($ch)];
    }
    return [$html, null];
  };

  $byOnu = [];  // onuid => row
  $errors = [];

  foreach ($pons as $pon) {
    foreach ([0,1] as $group) {
      [$html, $e] = $fetch($pon, $group);
      if ($e) { $errors[] = "PON $pon group $group: $e"; continue; }
      $rows = parse_onu_statistics_html($html, $pon);
      foreach ($rows as $r) {
        $byOnu[$r['onuid']] = $r; // groups are disjoint; last write ok
      }
    }
  }

  // Natural sort rows: by PON, then GPON port, then ONU number
  $rowsOut = array_values($byOnu);
  usort($rowsOut, function($a, $b){
    $pa = (int)($a['pon'] ?? 0);
    $pb = (int)($b['pon'] ?? 0);
    if ($pa !== $pb) return $pa - $pb;

    [$aport, $aonu] = extract_port_onu($a['onuid'] ?? '');
    [$bport, $bonu] = extract_port_onu($b['onuid'] ?? '');

    if ($aport !== $bport) return $aport - $bport;
    return $aonu - $bonu;
  });

  olt_close($ch);
  json_out(['ok'=>true,'rows'=>$rowsOut,'errors'=>$errors,'reused'=>$reused]);

} catch (Throwable $ex) {
  // Convert any PHP runtime error to JSON instead of 500
  if (isset($ch) && $ch) { @curl_close($ch); }
  json_out([
    'ok'=>false,
    'error'=>'php:'.$ex->getMessage(),
    'at'=>basename($ex->getFile()).':'.$ex->getLine()
  ]);
}

// ---- helpers used above ----
function extract_port_onu($id){
  // GPONx/<port>:<onu>  e.g., GPON0/1:23 → [1,23]
  if (preg_match('~^GPON\d+/(\d+):(\d+)~i', $id, $m)) {
    return [ (int)$m[1], (int)$m[2] ];
  }
  return [0, 0];
}
