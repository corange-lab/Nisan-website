<?php
// /b/cli/cron_minute.php
// Loops ~58s, calling your sampler every 3s. Respects PAUSE flag.
// Works on shared hosting. Keeps PHP session via cookie jar so OLT login is reused.

$END  = microtime(true) + 58;
$URL  = getenv('SAMPLE_URL') ?: 'https://www.nisan.co.in/b/api/network_sample.php?source=cron';
$PAUSE= __DIR__.'/../data/PAUSE_SAMPLING';
$LOG  = __DIR__.'/../logs/cron_minute.log';
$JAR  = __DIR__.'/../data/cron_cookie.txt';

@is_dir(dirname($LOG)) || @mkdir(dirname($LOG),0775,true);

function hit($u,$jar){
  $ch = curl_init($u);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_CONNECTTIMEOUT=>10,   CURLOPT_TIMEOUT=>20,
    CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false,
    CURLOPT_COOKIEJAR=>$jar, CURLOPT_COOKIEFILE=>$jar,
    CURLOPT_USERAGENT=>"NisanCron/1.0"
  ]);
  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code,$err,$body];
}

while (microtime(true) < $END) {
  if (file_exists($PAUSE)) { @file_put_contents($LOG, date('c')." paused\n", FILE_APPEND); sleep(5); continue; }
  [$code,$err] = hit($URL,$JAR);
  @file_put_contents($LOG, date('c')." hit:$code ".($err?:'ok')."\n", FILE_APPEND);
  usleep(3000000); // ~3s
}
