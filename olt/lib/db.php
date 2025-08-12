<?php
function db(PDO $pdo = null){
  static $conn = null;
  if ($pdo) { $conn = $pdo; }
  if ($conn) return $conn;

  $CFG = require __DIR__.'/config.php';
  $dsn  = $CFG['DB']['dsn'];
  $user = $CFG['DB']['user'];
  $pass = $CFG['DB']['pass'];

  // Ensure SQLite directory exists
  if (strpos($dsn, 'sqlite:') === 0) {
    $path = substr($dsn, 7);
    $dir  = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
  }

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  if (strpos($dsn, 'sqlite:') === 0) {
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL;');
  }
  return $conn = $pdo;
}
