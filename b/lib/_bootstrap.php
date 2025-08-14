<?php
// /b/lib/_bootstrap.php (resilient)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$CFG = require __DIR__.'/config.php';

// Optionally include helpers if present (don't 500 if they're missing)
foreach (['util.php','cache.php','session.php','parsers.php','db.php'] as $f) {
  $p = __DIR__.'/'.$f;
  if (is_file($p)) require $p;
}

// Ensure db() is available even if db.php wasn’t present in the loop above
if (!function_exists('db')) {
  require __DIR__.'/db.php';
}

// Provide json_out() if util.php wasn’t present
if (!function_exists('json_out')) {
  function json_out($arr){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($arr); }
}

if (!isset($_SESSION)) session_start();
