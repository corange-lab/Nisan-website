<?php
// /b/api/day_usage_all.php
// Per-ONU usage for a local date (00:00–23:59), default Asia/Kolkata.
// Usage = sum of positive deltas of input_bytes (Download) and output_bytes (Upload).
// Robust to: sparse samples (falls back to max-min), case/space differences in IDs.

require __DIR__.'/_bootstrap.php';

function norm_onu($s){
  $s = strtoupper(trim((string)$s));
  $s = preg_replace('/\x{00A0}/u',' ',$s); // nbsp
  $s = preg_replace('/\s+/',' ',$s);
  return $s;
}

$date = isset($_GET['date']) ? $_GET['date'] : (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$tzid = isset($_GET['tz']) && $_GET['tz'] ? $_GET['tz'] : 'Asia/Kolkata';

try { $tz = new DateTimeZone($tzid); }
catch(Throwable $e){ $tzid = 'Asia/Kolkata'; $tz = new DateTimeZone($tzid); }

try {
  $pdo = new PDO(
    $CFG['DB']['dsn'], $CFG['DB']['user'], $CFG['DB']['pass'],
    [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
  );
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>'db_connect:'.$e->getMessage()]);
}

// local day → UTC [start, end)
$startLocal = new DateTime($date.' 00:00:00', $tz);
$endLocal   = new DateTime($date.' 23:59:59', $tz);
$startUtc = (clone $startLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
$endUtc   = (clone $endLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp() + 1;

// Pull samples ordered by id/time
$sql = "SELECT onuid, ts, input_bytes, output_bytes
        FROM samples
        WHERE ts >= :s AND ts < :e
        ORDER BY onuid, ts";
$stm = $pdo->prepare($sql);
$stm->execute([':s'=>$startUtc, ':e'=>$endUtc]);

$acc = []; // id => ['prev_in'=>null,'prev_out'=>null,'d'=>0,'u'=>0,'min_in'=>null,'max_in'=>null,'min_out'=>null,'max_out'=>null,'id_raw'=>null]
while ($row = $stm->fetch()){
  $raw = (string)($row['onuid'] ?? '');
  if ($raw === '') continue;
  $id = norm_onu($raw);

  if (!isset($acc[$id])) $acc[$id] = ['prev_in'=>null,'prev_out'=>null,'d'=>0,'u'=>0,'min_in'=>null,'max_in'=>null,'min_out'=>null,'max_out'=>null,'id_raw'=>$raw];

  $in  = is_numeric($row['input_bytes'])  ? (float)$row['input_bytes']  : null;
  $out = is_numeric($row['output_bytes']) ? (float)$row['output_bytes'] : null;

  // rolling positive deltas
  if ($acc[$id]['prev_in'] !== null && $in !== null) {
    $delta = $in - $acc[$id]['prev_in'];
    if ($delta > 0) $acc[$id]['d'] += $delta;
  }
  if ($acc[$id]['prev_out'] !== null && $out !== null) {
    $delta = $out - $acc[$id]['prev_out'];
    if ($delta > 0) $acc[$id]['u'] += $delta;
  }

  $acc[$id]['prev_in']  = $in;
  $acc[$id]['prev_out'] = $out;

  // min/max for fallback
  if ($in !== null){
    if ($acc[$id]['min_in'] === null || $in < $acc[$id]['min_in']) $acc[$id]['min_in'] = $in;
    if ($acc[$id]['max_in'] === null || $in > $acc[$id]['max_in']) $acc[$id]['max_in'] = $in;
  }
  if ($out !== null){
    if ($acc[$id]['min_out'] === null || $out < $acc[$id]['min_out']) $acc[$id]['min_out'] = $out;
    if ($acc[$id]['max_out'] === null || $out > $acc[$id]['max_out']) $acc[$id]['max_out'] = $out;
  }
}

$rows_arr = [];
$rows_map = []; // normalized key -> record
foreach ($acc as $id => $a){
  // If deltas are zero but we have sparse data, fall back to max-min (non-negative).
  $d = (float)$a['d'];
  $u = (float)$a['u'];
  if (($d<=0 || $u<=0) && ($a['min_in'] !== null || $a['min_out'] !== null)) {
    $d2 = ($a['min_in'] !== null && $a['max_in'] !== null) ? max(0.0, $a['max_in'] - $a['min_in']) : 0.0;
    $u2 = ($a['min_out'] !== null && $a['max_out'] !== null) ? max(0.0, $a['max_out'] - $a['min_out']) : 0.0;
    if ($d<=0) $d = $d2;
    if ($u<=0) $u = $u2;
  }

  $rec = [
    'onuid'           => $id,               // normalized
    'onuid_raw'       => $a['id_raw'],
    'download_bytes'  => (int)round($d),    // input_bytes
    'upload_bytes'    => (int)round($u),    // output_bytes
  ];
  $rows_arr[] = $rec;
  $rows_map[$id] = $rec;
}

json_out([
  'ok'=>true,
  'date'=>$date,
  'tz'=>$tzid,
  'rows'=>$rows_arr,      // array of objects
  'by_onuid'=>$rows_map   // convenience map (keys are normalized)
]);
