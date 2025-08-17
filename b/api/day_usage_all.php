<?php
// /b/api/day_usage_all.php
// Returns per-ONU usage between 00:00–23:59 of a given local date.
// Usage = sum of positive deltas of input_bytes (Download) and output_bytes (Upload).
// Default timezone: Asia/Kolkata.

require __DIR__.'/_bootstrap.php';

$date = isset($_GET['date']) ? $_GET['date'] : (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$tzid = isset($_GET['tz']) && $_GET['tz'] ? $_GET['tz'] : 'Asia/Kolkata';

try {
  $tz = new DateTimeZone($tzid);
} catch (Throwable $e) {
  $tz = new DateTimeZone('Asia/Kolkata');
  $tzid = 'Asia/Kolkata';
}

try {
  $pdo = new PDO(
    $CFG['DB']['dsn'], $CFG['DB']['user'], $CFG['DB']['pass'],
    [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
  );
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>'db_connect:'.$e->getMessage()]);
}

// local day start/end -> UTC epoch
$startLocal = new DateTime($date.' 00:00:00', $tz);
$endLocal   = new DateTime($date.' 23:59:59', $tz);

$startUtc = (clone $startLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
$endUtc   = (clone $endLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp() + 1; // exclusive

// Pull all samples for the day, ordered by onuid,ts
$sql = "SELECT onuid, ts, input_bytes, output_bytes
        FROM samples
        WHERE ts >= :s AND ts < :e
        ORDER BY onuid, ts";
$stm = $pdo->prepare($sql);
$stm->execute([':s'=>$startUtc, ':e'=>$endUtc]);

$by = []; // onuid => [prev_in, prev_out, d_bytes, u_bytes]

while ($row = $stm->fetch()) {
  $id = strtoupper(trim((string)($row['onuid'] ?? '')));
  if ($id === '') continue;

  if (!isset($by[$id])) $by[$id] = ['prev_in'=>null,'prev_out'=>null,'d'=>0.0,'u'=>0.0];

  $in  = is_numeric($row['input_bytes'])  ? (float)$row['input_bytes']  : null;
  $out = is_numeric($row['output_bytes']) ? (float)$row['output_bytes'] : null;

  // input_bytes = Download (as per your mapping)
  if ($by[$id]['prev_in'] !== null && $in !== null) {
    $delta = $in - $by[$id]['prev_in'];
    if ($delta > 0) $by[$id]['d'] += $delta;
  }
  // output_bytes = Upload
  if ($by[$id]['prev_out'] !== null && $out !== null) {
    $delta = $out - $by[$id]['prev_out'];
    if ($delta > 0) $by[$id]['u'] += $delta;
  }

  $by[$id]['prev_in']  = $in;
  $by[$id]['prev_out'] = $out;
}

// Build response
$rows = [];
foreach ($by as $id=>$acc) {
  $rows[$id] = [
    'download_bytes' => (int)round($acc['d']),
    'upload_bytes'   => (int)round($acc['u']),
  ];
}

json_out([
  'ok'=>true,
  'date'=>$date,
  'tz'=>$tzid,
  'rows'=>$rows
]);
