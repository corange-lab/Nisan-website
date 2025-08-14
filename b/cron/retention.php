<?php
// b/cron/retention.php
// Keep last 3 full days of raw `samples`; roll up older days; retain rollups for 30 days.
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

require __DIR__ . '/../lib/_bootstrap.php';

function ensure_schema(PDO $pdo){
  $pdo->exec("CREATE TABLE IF NOT EXISTS rollup_daily(
    ymd TEXT PRIMARY KEY,
    start_ts INTEGER NOT NULL,
    end_ts INTEGER NOT NULL,
    avg_total_mbps REAL,
    max_total_mbps REAL,
    intervals INTEGER NOT NULL DEFAULT 0
  )");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_ts ON samples(ts)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_onu_ts ON samples(onuid, ts)");
}

function to_num_or_null($v){ if($v===null) return null; $n=+($v); return is_finite($n)?$n:null; }

function day_rollup(PDO $pdo, int $dayStart, int $dayEnd){
  // Build ordered snapshot list inside the day
  $qTs = $pdo->prepare("SELECT ts FROM samples WHERE ts>=? AND ts<? GROUP BY ts ORDER BY ts ASC");
  $qTs->execute([$dayStart,$dayEnd]);
  $tsList = array_map(fn($r)=>(int)$r['ts'],$qTs->fetchAll());
  $n = count($tsList);
  if ($n < 2) return ['avg'=>null,'max'=>0.0,'intervals'=>0];

  $qRows = $pdo->prepare("SELECT onuid,input_bytes,output_bytes FROM samples WHERE ts=?");

  $sum=0.0; $cnt=0; $mx=0.0;
  for($i=1;$i<$n;$i++){
    $t1=$tsList[$i-1]; $t2=$tsList[$i]; $dt=max(1,$t2-$t1);

    $qRows->execute([$t2]); $curr=$qRows->fetchAll();
    $qRows->execute([$t1]); $prev=$qRows->fetchAll();
    $pm=[]; foreach($prev as $r){ $pm[$r['onuid']]=$r; }

    $tot=0.0;
    foreach($curr as $c){
      $id=$c['onuid']; if(!isset($pm[$id])) continue;
      $p=$pm[$id];
      $inC=to_num_or_null($c['input_bytes']);  $inP=to_num_or_null($p['input_bytes']);
      $outC=to_num_or_null($c['output_bytes']); $outP=to_num_or_null($p['output_bytes']);
      if($inC!==null && $inP!==null && $inC >= $inP)   $tot += (($inC-$inP)*8.0)/($dt*1000000.0);
      if($outC!==null && $outP!==null && $outC >= $outP) $tot += (($outC-$outP)*8.0)/($dt*1000000.0);
    }
    $sum += $tot; $cnt++; if($tot>$mx)$mx=$tot;
  }
  return ['avg'=>($cnt?($sum/$cnt):null),'max'=>$mx,'intervals'=>$cnt];
}

try{
  $pdo = db($CFG);
  ensure_schema($pdo);

  $today0  = strtotime('today 00:00:00');
  $keep0   = $today0 - 2*86400;  // keep today, D-1, D-2 raw
  $cutoff0 = $keep0;             // anything strictly before this is eligible for rollup+delete

  // Find oldest day present
  $minTs = (int)$pdo->query("SELECT MIN(ts) AS t FROM samples")->fetch()['t'];
  if (!$minTs){ echo "No samples.\n"; exit; }
  $firstDay0 = strtotime(date('Y-m-d 00:00:00', $minTs));

  $deleted=0; $rolled=0;

  for($day0=$firstDay0; $day0 < $cutoff0; $day0 += 86400){
    $ymd = date('Y-m-d', $day0);
    $exists = $pdo->prepare("SELECT 1 FROM rollup_daily WHERE ymd=?");
    $exists->execute([$ymd]);
    if(!$exists->fetchColumn()){
      $stats = day_rollup($pdo, $day0, $day0+86400);
      $ins = $pdo->prepare("INSERT OR REPLACE INTO rollup_daily (ymd,start_ts,end_ts,avg_total_mbps,max_total_mbps,intervals) VALUES (?,?,?,?,?,?)");
      $ins->execute([$ymd,$day0,$day0+86400,$stats['avg'],$stats['max'],$stats['intervals']]);
      $rolled++;
    }
    // delete that day's raw rows
    $del = $pdo->prepare("DELETE FROM samples WHERE ts>=? AND ts<?");
    $del->execute([$day0,$day0+86400]);
    $deleted += $del->rowCount();
  }

  // keep rollups for last 30 days only
  $keepRollFrom = date('Y-m-d', $today0 - 30*86400);
  $prune = $pdo->prepare("DELETE FROM rollup_daily WHERE ymd < ?");
  $prune->execute([$keepRollFrom]);

  // compact file
  $pdo->exec("VACUUM");

  echo "Rolled days: $rolled; Deleted rows: $deleted; Kept rollups since $keepRollFrom\n";
}catch(Throwable $e){
  echo "ERROR: ".$e->getMessage()."\n";
  exit(1);
}
