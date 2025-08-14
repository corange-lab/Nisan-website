<?php
// b/lib/db.php
function db(array $CFG): PDO {
  $dsn  = $CFG['DB']['dsn']  ?? '';
  $user = $CFG['DB']['user'] ?? null;
  $pass = $CFG['DB']['pass'] ?? null;

  // If SQLite, make sure the folder exists before connecting
  if (strpos($dsn, 'sqlite:') === 0) {
    $path = substr($dsn, 7);
    if ($path) {
      $dir = dirname($path);
      if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
      }
      if (!is_writable($dir)) {
        // Try to make it writable; ignore failure silently
        @chmod($dir, 0775);
      }
    }
  }

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  // Ensure core schema
  $pdo->exec("CREATE TABLE IF NOT EXISTS samples(
    onuid TEXT NOT NULL,
    ts INTEGER NOT NULL,
    input_bytes TEXT,
    output_bytes TEXT
  )");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_ts ON samples(ts)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_onu_ts ON samples(onuid, ts)");

  return $pdo;
}
