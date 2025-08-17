<?php
// /b/api/tracking_set.php
require __DIR__.'/_bootstrap.php';
$on = isset($_GET['on']) ? intval($_GET['on']) : 1;
$flag = __DIR__.'/../data/PAUSE_SAMPLING';

if ($on) {
  if (file_exists($flag)) @unlink($flag);
  json_out(['ok'=>true,'tracking'=>'on']);
} else {
  @file_put_contents($flag, "paused ".date('c')."\n");
  json_out(['ok'=>true,'tracking'=>'off']);
}
