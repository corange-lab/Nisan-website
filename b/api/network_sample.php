<?php
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__.'/../lib/_bootstrap.php';

/** Return true if table has given column (SQLite). */
function table_has_column(PDO $pdo, $table, $column){
  $st = $pdo->prepare("PRAGMA table_info(".$table.")");
  $st->execute();
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    if (strcasecmp($r['name'],$column)===0) return true;
  }
  return false;
}

/** Minimal HTML table parser for onustatistics.html */
function parse_onu_stats_rows($html){
  $rows = [];
  $doc = new DOMDocument();
  libxml_use_internal_errors(true);
  $ok = $doc->loadHTML($html);
  libxml_clear_errors();
  if(!$ok) return $rows;

  $xpath = new DOMXPath($doc);
  foreach ($xpath->query('//tr[td]') as $tr){
    $tds = $tr->getElementsByTagName('td');
    if ($tds->length !== 5) continue;
    $onuid = trim($tds->item(0)->textContent);
    $inb   = trim($tds->item(1)->textContent);
    $outb  = trim($tds->item(3)->textContent);
    if ($onuid === '' || stripos($onuid,'gpon')===false) continue;

    $inb  = (strcasecmp($inb,'NULL')===0 ? null : $inb);
    $outb = (strcasecmp($outb,'NULL')===0 ? null : $outb);
    $rows[] = ['onuid'=>$onuid, 'input_bytes'=>$inb, 'output_bytes'=>$outb];
  }
  return $rows;
}

try{
  $debug = !empty($_GET['debug']);
  $pdo   = db($CFG);

  // See if 'pon' column exists in server DB
  $hasPon = table_has_column($pdo, 'samples', 'pon');

  // login (reuse cookie if valid)
  [$ch,$cookie,$err,$reused] = olt_login_or_reuse($CFG);
  if ($err) throw new RuntimeException($err);

  $urls = olt_urls($CFG);
  if (empty($urls['STATS'])) throw new RuntimeException('Missing STATS URL in session.php');

  $ts = time();
  $inserted = 0; $byPon = []; $log = [];

  // Prepare insert statements (depending on schema)
  if ($hasPon) {
    $ins = $pdo->prepare("INSERT INTO samples(pon, onuid, ts, input_bytes, output_bytes) VALUES(?,?,?,?,?)");
  } else {
    $ins = $pdo->prepare("INSERT INTO samples(onuid, ts, input_bytes, output_bytes) VALUES(?,?,?,?)");
  }

  // Iterate PONs & both ONU groups (0: 1-64, 1: 65-128)
  $pons = isset($CFG['PONS']) && is_array($CFG['PONS']) ? $CFG['PONS'] : range(1,8);

  foreach ($pons as $pon){
    foreach ([0,1] as $group){
      curl_setopt_array($ch, [
        CURLOPT_URL => $urls['STATS'],
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
          "Origin: {$CFG['BASE']}",
          "Referer: {$CFG['BASE']}/action/onustatistics.html",
          "Content-Type: application/x-www-form-urlencoded"
        ],
        CURLOPT_POSTFIELDS => http_build_query([
          'pon' => (string)$pon,
          'onu_group' => (string)$group,
          'who' => '100',
          'onuid' => '0',
          'port_refresh' => 'refresh'
        ]),
        CURLOPT_TIMEOUT => $CFG['TIMEOUT'],
      ]);
      $html = curl_exec($ch);
      if ($html === false){
        if ($debug) $log[] = "PON {$pon} grp {$group} curl_error: ".curl_error($ch);
        continue;
      }

      // If we hit a login screen again, re-login once and retry this group
      if (stripos($html, 'login') !== false && stripos($html, 'password') !== false) {
        olt_close($ch);
        [$ch,$cookie,$err,$reused2] = olt_login_or_reuse($CFG);
        if ($err) throw new RuntimeException("relogin:$err");
        $group--; // retry same group
        continue;
      }

      $rows = parse_onu_stats_rows($html);
      if ($debug) $log[] = "PON {$pon} grp {$group} parsed rows: ".count($rows);
      if (!isset($byPon[$pon])) $byPon[$pon]=0;

      foreach ($rows as $r){
        if ($hasPon) {
          $ins->execute([$pon, $r['onuid'], $ts, $r['input_bytes'], $r['output_bytes']]);
        } else {
          $ins->execute([$r['onuid'], $ts, $r['input_bytes'], $r['output_bytes']]);
        }
        $inserted++; $byPon[$pon]++;
      }
    }
  }

  olt_close($ch);

  $out = ['ok'=>true,'inserted'=>$inserted,'by_pon'=>$byPon,'ts'=>$ts,'reused_cookie'=>$reused];
  if ($debug) $out['debug']=$log;
  json_out($out);

}catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'php:'.$e->getMessage()]);
}
