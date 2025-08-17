<?php
// /b/api/day_usage_all.php
// Per-ONU usage for a local day (00:00–23:59 local). Units: bytes.
// Primary: sum of positive deltas for input_bytes (Download) and output_bytes (Upload).
// Fallback (only if fewer than 2 valid samples for that field): last - first (non-negative).

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function json_out($a){ echo json_encode($a, JSON_UNESCAPED_SLASHES); exit; }

function norm_onu($s){
  $s = strtoupper(trim((string)$s));
  $s = preg_replace('/\x{00A0}/u',' ',$s);
  $s = preg_replace('/\s+/',' ',$s);
  return $s;
}

$cfgFile = __DIR__ . '/../lib/config.php';
if (!file_exists($cfgFile)) json_out(['ok'=>false,'error'=>'config_missing']);
$CFG = require $cfgFile;

// Connect DB
try{
  $pdo = new PDO(
    $CFG['DB']['dsn'], $CFG['DB']['user'], $CFG['DB']['pass'],
    [ PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC ]
  );
}catch(Throwable $e){ json_out(['ok'=>false,'error'=>'db_connect:'.$e->getMessage()]); }

// Params
$tzid = isset($_GET['tz']) && $_GET['tz'] ? $_GET['tz'] : 'Asia/Kolkata';
try { $tz = new DateTimeZone($tzid); } catch(Throwable $e){ $tzid='Asia/Kolkata'; $tz = new DateTimeZone($tzid); }
$date = (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) ? $_GET['date'] : (new DateTime('now',$tz))->format('Y-m-d');

// Local day → UTC [start, end)
$startLocal = new DateTime($date.' 00:00:00', $tz);
$endLocal   = new DateTime($date.' 23:59:59', $tz);
$startUtc = (clone $startLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp();
$endUtc   = (clone $endLocal)->setTimezone(new DateTimeZone('UTC'))->getTimestamp() + 1;

// Ensure table exists
try { $pdo->query("SELECT onuid, ts, input_bytes, output_bytes FROM samples LIMIT 1"); }
catch(Throwable $e){ json_out(['ok'=>false,'error'=>'samples_table_missing:'.$e->getMessage()]); }

// Fetch the day’s samples
$sql = "SELECT onuid, ts, input_bytes, output_bytes
        FROM samples
        WHERE ts >= :s AND ts < :e
        ORDER BY onuid, ts";
$stm = $pdo->prepare($sql);
$stm->execute([':s'=>$startUtc, ':e'=>$endUtc]);

$acc = []; // id => state
while ($row = $stm->fetch()){
  $raw = (string)($row['onuid'] ?? '');
  if ($raw === '') continue;
  $id = norm_onu($raw);

  if (!isset($acc[$id])){
    $acc[$id] = [
      'onuid_raw'=>$raw,
      // Download (input_bytes)
      'prev_in'=>null, 'sum_in'=>0.0, 'first_in'=>null, 'last_in'=>null, 'cnt_in'=>0,
      // Upload (output_bytes)
      'prev_out'=>null,'sum_out'=>0.0,'first_out'=>null,'last_out'=>null,'cnt_out'=>0,
    ];
  }

  $in  = is_numeric($row['input_bytes'])  ? (float)$row['input_bytes']  : null;
  $out = is_numeric($row['output_bytes']) ? (float)$row['output_bytes'] : null;

  // In (download)
  if ($in !== null){
    if ($acc[$id]['prev_in'] !== null){
      $delta = $in - $acc[$id]['prev_in'];
      if ($delta > 0) $acc[$id]['sum_in'] += $delta; // only positive deltas
    }
    if ($acc[$id]['cnt_in'] === 0) $acc[$id]['first_in'] = $in;
    $acc[$id]['last_in'] = $in;
    $acc[$id]['prev_in'] = $in;
    $acc[$id]['cnt_in']++;
  }

  // Out (upload)
  if ($out !== null){
    if ($acc[$id]['prev_out'] !== null){
      $delta = $out - $acc[$id]['prev_out'];
      if ($delta > 0) $acc[$id]['sum_out'] += $delta; // only positive deltas
    }
    if ($acc[$id]['cnt_out'] === 0) $acc[$id]['first_out'] = $out;
    $acc[$id]['last_out'] = $out;
    $acc[$id]['prev_out'] = $out;
    $acc[$id]['cnt_out']++;
  }
}

// Build output (apply fallback only when <2 samples)
$rows_arr = [];
$rows_map = [];
foreach ($acc as $id => $a){
  $d = (float)$a['sum_in'];
  $u = (float)$a['sum_out'];

  if ($a['cnt_in'] < 2)  $d = max(0.0, (float)$a['last_in'] - (float)$a['first_in']);
  if ($a['cnt_out'] < 2) $u = max(0.0, (float)$a['last_out'] - (float)$a['first_out']);

  $rec = [
    'onuid'          => $id,                // normalized key
    'onuid_raw'      => $a['onuid_raw'],
    'download_bytes' => (int)round($d),     // input_bytes
    'upload_bytes'   => (int)round($u),     // output_bytes
    'samples_in'     => (int)$a['cnt_in'],  // debug/help
    'samples_out'    => (int)$a['cnt_out'],
  ];
  $rows_arr[] = $rec;
  $rows_map[$id] = $rec;
}

json_out(['ok'=>true,'date'=>$date,'tz'=>$tzid,'rows'=>$rows_arr,'by_onuid'=>$rows_map]);
