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

function insert_samples(PDO $pdo, array $rows, int $ts): int
{
  if (empty($rows)) {
    return 0;
  }

  static $hasPon = null;
  if ($hasPon === null) {
    $hasPon = false;
    $res = $pdo->query("PRAGMA table_info(samples)");
    foreach ($res as $r) {
      if (strcasecmp((string)$r['name'], 'pon') === 0) {
        $hasPon = true;
        break;
      }
    }
  }

  $inserted = 0;
  if ($hasPon) {
    $stmt = $pdo->prepare("INSERT INTO samples(pon, onuid, ts, input_bytes, output_bytes) VALUES(?,?,?,?,?)");
  } else {
    $stmt = $pdo->prepare("INSERT INTO samples(onuid, ts, input_bytes, output_bytes) VALUES(?,?,?,?)");
  }

  $pdo->beginTransaction();
  try {
    foreach ($rows as $r) {
      $onuid = normalize_onuid($r['onuid'] ?? '');
      if ($onuid === '') {
        continue;
      }

      $pon = isset($r['pon']) ? (int)$r['pon'] : null;
      $inb = val_or_null($r['input_bytes'] ?? null);
      $outb = val_or_null($r['output_bytes'] ?? null);

      if ($hasPon) {
        $stmt->execute([$pon, $onuid, $ts, $inb, $outb]);
      } else {
        $stmt->execute([$onuid, $ts, $inb, $outb]);
      }
      $inserted++;
    }
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }

  return $inserted;
}
