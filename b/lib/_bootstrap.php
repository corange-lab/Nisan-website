<?php
// /b/lib/_bootstrap.php (resilient)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

$CFG = require __DIR__ . '/config.php';

// Optionally include helpers if present (don't 500 if they're missing)
foreach (['util.php', 'cache.php', 'session.php', 'parsers.php', 'db.php', 'fetch.php'] as $f) {
  $p = __DIR__ . '/' . $f;
  if (is_file($p)) {
    require $p;
  }
}

// Ensure db() is available even if db.php wasn’t present in the loop above
if (!function_exists('db')) {
  require __DIR__ . '/db.php';
}

// Provide json_out() if util.php wasn’t present
if (!function_exists('json_out')) {
  function json_out($arr)
  {
    if (!headers_sent()) {
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
    }
    echo json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
  }
}

if (!isset($_SESSION)) {
  session_start();
}

// Optional API key gate for private deployments.
if (PHP_SAPI !== 'cli' && !empty($CFG['API_KEY'])) {
  $provided = '';
  if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $provided = (string)$_SERVER['HTTP_X_API_KEY'];
  } elseif (isset($_GET['key'])) {
    $provided = (string)$_GET['key'];
  } elseif (isset($_POST['key'])) {
    $provided = (string)$_POST['key'];
  }

  if (!hash_equals((string)$CFG['API_KEY'], $provided)) {
    http_response_code(401);
    json_out(['ok' => false, 'error' => 'Unauthorized']);
  }
}
