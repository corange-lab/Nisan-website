<?php
// /b/api/health.php
// Simple health probe so you can verify 24x7 sampling.
// Reports last sample age, recent counts, and whether server-side
// sampling is paused (via /b/api/tracking_set.php).

require __DIR__.'/_bootstrap.php';

try {
  $pdo = new PDO(
    $CFG['DB']['dsn'],
    $CFG['DB']['user'],
    $CFG['DB']['pass'],
    [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
  );
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>'db_connect:'.$e->getMessage()]);
}

$now = time();
$pauseFlag = __DIR__.'/../data/PAUSE_SAMPLING';
$tracking  = file_exists($pauseFlag) ? 'off' : 'on';

try {
  // Oldest / newest sample timestamps
  $minTs = (int)($pdo->query("SELECT MIN(ts) AS m FROM samples")->fetchColumn() ?: 0);
  $maxTs = (int)($pdo->query("SELECT MAX(ts) AS m FROM samples")->fetchColumn() ?: 0);

  // Recent activity windows
  $qCnt = $pdo->prepare("SELECT COUNT(*) FROM samples WHERE ts >= :t");
  $qCnt->execute([':t'=>$now-60]);
  $samples1m = (int)$qCnt->fetchColumn();

  $qCnt->execute([':t'=>$now-300]);
  $samples5m = (int)$qCnt->fetchColumn();

  $qCnt->execute([':t'=>$now-3600]);
  $samples1h = (int)$qCnt->fetchColumn();

  $qOnu = $pdo->prepare("SELECT COUNT(DISTINCT onuid) FROM samples WHERE ts >= :t");
  $qOnu->execute([':t'=>$now-300]);
  $onu5m = (int)$qOnu->fetchColumn();

  // Optional: show how many ONUs seen today (helps sanity-check)
  $startToday = strtotime('today UTC'); // samples are stored in UTC
  $qOnuToday = $pdo->prepare("SELECT COUNT(DISTINCT onuid) FROM samples WHERE ts >= :t");
  $qOnuToday->execute([':t'=>$startToday]);
  $onuToday = (int)$qOnuToday->fetchColumn();

  json_out([
    'ok' => true,
    'tracking' => $tracking,
    'now_ts' => $now,
    'last_sample_ts' => $maxTs ?: null,
    'dt_sec' => $maxTs ? ($now - $maxTs) : null, // age of last sample; should stay small (<10s) if cron is working
    'samples_1m' => $samples1m,
    'samples_5m' => $samples5m,
    'samples_1h' => $samples1h,
    'distinct_onu_5m' => $onu5m,
    'distinct_onu_today' => $onuToday,
    'min_ts' => $minTs ?: null
  ]);

} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>'db_query:'.$e->getMessage()]);
}
