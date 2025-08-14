<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); error_reporting(E_ALL);
set_error_handler(function($no,$str,$file,$line){ throw new ErrorException($str,0,$no,$file,$line); });

$CFG = require __DIR__.'/../lib/config.php';
$info = ['php_version'=>PHP_VERSION, 'ext'=>[], 'db'=>[], 'olt'=>['base'=>$CFG['BASE']]];
$ok = true; $errors = [];

// extensions
foreach (['curl','pdo','pdo_sqlite','sqlite3'] as $e) {
  $has = extension_loaded($e);
  $info['ext'][$e] = $has;
  if (!$has) { $ok=false; $errors[] = "Missing PHP extension: $e"; }
}

// db path / perms
$dsn = $CFG['DB']['dsn'] ?? '';
$info['db']['dsn'] = $dsn;
if (strpos($dsn, 'sqlite:') === 0) {
  $path = substr($dsn, 7);
  $info['db']['file'] = $path;
  $info['db']['dir']  = dirname($path);
  $info['db']['dir_exists']   = is_dir($info['db']['dir']);
  $info['db']['dir_writable'] = is_writable($info['db']['dir']);
}

// try connect
try {
  require __DIR__.'/../lib/db.php';
  $pdo = db($CFG);
  $info['db']['connect'] = true;
  $row = $pdo->query("SELECT COUNT(*) c, MIN(ts) mi, MAX(ts) ma FROM samples")->fetch();
  $info['db']['samples'] = (int)$row['c'];
  $info['db']['min_ts']  = $row['mi']; 
  $info['db']['max_ts']  = $row['ma'];
} catch (Throwable $e) {
  $ok=false; $errors[]='DB error: '.$e->getMessage();
}

// outbound reachability to OLT (HEAD only; auth not needed)
if (function_exists('curl_init')) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($CFG['BASE'],'/').'/action/onustatistics.html',
    CURLOPT_NOBODY => true,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10,
  ]);
  $resp = curl_exec($ch);
  if ($resp === false) {
    $info['olt']['reachable'] = false;
    $info['olt']['curl_error'] = curl_error($ch);
  } else {
    $info['olt']['reachable'] = true;
    $info['olt']['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  }
  curl_close($ch);
}

echo json_encode(['ok'=>$ok,'errors'=>$errors,'info'=>$info], JSON_PRETTY_PRINT);
