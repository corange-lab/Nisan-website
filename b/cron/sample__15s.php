<?php
// CLI script: php /home/officialmobile/nisan.co.in/b/cron/sample_15s.php
chdir(__DIR__.'/..');
require __DIR__.'/../lib/_bootstrap.php';

for ($i=0; $i<4; $i++){
  [$rows,$errors,$reused] = fetch_stats_all($CFG);
  $pdo = db($CFG);
  $ts = time();
  insert_samples($pdo,$rows,$ts);
  // optional: keep only last N days; uncomment if you want auto-trim
  // $pdo->exec("DELETE FROM samples WHERE ts < ".(time()-7*86400));
  if ($i<3) sleep(15);
}
