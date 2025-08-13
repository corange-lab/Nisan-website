<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

try{
  $onuid = isset($_GET['onuid']) ? normalize_onuid($_GET['onuid']) : null;
  if(!$onuid) json_out(['ok'=>false,'error'=>'Missing onuid']);

  list($ponPort,$onuNum) = extract_port_onu_id($onuid);
  if ($ponPort<=0 || $onuNum<=0) json_out(['ok'=>false,'error'=>'Bad onuid format']);

  $group = ($onuNum > 64) ? 1 : 0;

  // login/reuse
  [$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
  if ($err) json_out(["ok"=>false,"error"=>$err]);
  $urls = olt_urls($CFG);

  // fetch just this PON+group page
  curl_setopt_array($ch, [
    CURLOPT_URL => $urls['STATS'],
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Origin: {$CFG['BASE']}",
      "Referer: {$CFG['BASE']}/action/onustatistics.html",
      "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_POSTFIELDS => http_build_query([
      'pon'=>(string)$ponPort, 'onu_group'=>(string)$group,
      'who'=>'100','onuid'=>'0','port_refresh'=>'Refresh','whichfun'=>'2'
    ]),
    CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
  ]);
  $html = curl_exec($ch);
  if ($html === false) {
    $e = curl_error($ch); olt_close($ch);
    json_out(['ok'=>false,'error'=>"curl:$e"]);
  }
  olt_close($ch);

  // parse rows for that page, then pick our ONU only
  $rows = parse_onu_statistics_html($html, $ponPort);
  $match = null;
  foreach ($rows as $r){ if (normalize_onuid($r['onuid']) === $onuid){ $match = $r; break; } }

  $ts = time();
  if ($match){
    // store to DB
    $pdo = db($CFG);
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO samples (ts, pon, onuid, input_bytes, output_bytes) VALUES (:ts,:pon,:onuid,:inb,:outb)");
    $stmt->execute([
      ':ts'=>$ts, ':pon'=>$ponPort, ':onuid'=>$onuid,
      ':inb'=>($match['input_bytes']===null?null:(string)$match['input_bytes']),
      ':outb'=>($match['output_bytes']===null?null:(string)$match['output_bytes']),
    ]);
  }

  json_out(['ok'=>true,'onuid'=>$onuid,'ts'=>$ts,'found'=>$match?true:false,'row'=>$match]);
}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
