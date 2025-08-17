<?php
// /b/api/day_usage_all.php
// Per-ONU usage for a local date (00:00–23:59). Output both an array and a keyed map.
// Usage = positive deltas of counters; if sparse, falls back to (max-min). Units: bytes.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$DEBUG = isset($_GET['debug']) && $_GET['debug']=='1';
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }

function json_out($arr){ echo json_encode($arr, JSON_UNESCAPED_SLASHES); exit; }

function norm_onu($s){
  $s = strtoupper(trim((string)$s));
  $s = preg_replace('/\x{00A0}/u',' ',$s); // nbsp
  $s = preg_replace('/\s+/',' ',$s);
  return $s;
}

// Load config directly (avoid other includes to reduce failure points)
$CFG_FILE = __DIR__ . '/../lib/config.php';
if (!file_exists($CFG_FILE)) json_out(['ok'=>false,'error'=>'config_missing']);
$CFG = require $CFG_FILE;

// Connect DB
try {
  $pdo = new PDO(
    $CFG['DB']['dsn'],
    $CFG['DB']['user'],
    $CFG['DB']['pass'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  );
} catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'db_connect:'.$e->getMessage()]);
}

// Params
$tzid = isset($_GET['tz']) && $_GET['tz'] ? $_GET['tz'] : 'Asia/Kolkata';
try { $tz = new DateTimeZone($tzid); } catch(Throwable $e){ $tzid='Asia/Kolkata'; $tz = new DateTimeZone($tzid); }

$date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : (new DateTime('now',$tz))->format('Y-m-d');

// local day → UTC [start, end)
$startLocal = new DateTime($date.' 00:00:00', $tz);
$endLocal   = new DateTime($date.' 23:59:59', $tz);
$startUtc = (clone $startLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
$endUtc   = (clone $endLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp() + 1;

// Ensure table exists quickly (won’t modify schema; just for clearer error)
try {
  $pdo->query("SELECT onuid, ts, input_bytes, output_bytes FROM samples LIMIT 1");
} catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'samples_table_missing:'.$e->getMessage()]);
}

// Fetch samples ordered by id/time within the day window
$sql = "SELECT onuid, ts, input_bytes, output_bytes
        FROM samples
        WHERE ts >= :s AND ts < :e
        ORDER BY onuid, ts";
try {
  $stm = $pdo->prepare($sql);
  $stm->execute([':s'=>$startUtc, ':e'=>$endUtc]);
} catch(Throwable $e){
  json_out(['ok'=>false,'error'=>'db_query:'.$e->getMessage()]);
}

$acc = []; // id => accumulators
while ($row = $stm->fetch()){
  $raw = (string)($row['onuid'] ?? '');
  if ($raw === '') continue;
  $id = norm_onu($raw);

  if (!isset($acc[$id])) {
    $acc[$id] = [
      'onuid_raw'=>$raw,
      'prev_in'=>null,'prev_out'=>null,
      'd'=>0.0,'u'=>0.0,
      'min_in'=>null,'max_in'=>null,
      'min_out'=>null,'max_out'=>null,
    ];
  }

  $in  = is_numeric($row['input_bytes'])  ? (float)$row['input_bytes']  : null;
  $out = is_numeric($row['output_bytes']) ? (float)$row['output_bytes'] : null;

  // Sum positive deltas
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
$rows_map = [];
foreach ($acc as $id => $a){
  // Fallback to (max - min) if deltas ended up zero but we have sparse points
  $d = (float)$a['d'];
  $u = (float)$a['u'];
  if (($d <= 0 || $u <= 0)) {
    if ($a['min_in'] !== null && $a['max_in'] !== null && $d <= 0)  $d = max(0.0, $a['max_in']  - $a['min_in']);
    if ($a['min_out']!== null && $a['max_out']!== null && $u <= 0)  $u = max(0.0, $a['max_out'] - $a['min_out']);
  }

  $rec = [
    'onuid'          => $id,               // normalized key
    'onuid_raw'      => $a['onuid_raw'],
    'download_bytes' => (int)round($d),    // input_bytes
    'upload_bytes'   => (int)round($u),    // output_bytes
  ];
  $rows_arr[] = $rec;
  $rows_map[$id] = $rec;
}

json_out([
  'ok'   => true,
  'date' => $date,
  'tz'   => $tzid,
  'rows' => $rows_arr,   // array
  'by_onuid' => $rows_map // map keyed by normalized onuid
]);
