<?php
// /b/lib/db.php
function db(array $CFG): PDO {
  $dsn  = $CFG['DB']['dsn']  ?? '';
  $user = $CFG['DB']['user'] ?? null;
  $pass = $CFG['DB']['pass'] ?? null;

  // If SQLite, make sure the folder exists before connecting
  if (strpos($dsn, 'sqlite:') === 0) {
    $path = substr($dsn, 7);
    if ($path) {
      $dir = dirname($path);
      if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
      if (!is_writable($dir)) { @chmod($dir, 0775); }
    }
  }

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  // Ensure core schema (create if missing)
  $pdo->exec("CREATE TABLE IF NOT EXISTS samples(
    onuid TEXT NOT NULL,
    ts INTEGER NOT NULL,
    input_bytes TEXT,
    output_bytes TEXT
    -- 'pon' may or may not exist; we add it below if missing
  )");

  // Ensure 'pon' column exists (add if missing)
  $hasPon = false;
  $res = $pdo->query("PRAGMA table_info(samples)");
  foreach ($res as $r) {
    if (strcasecmp($r['name'],'pon')===0) { $hasPon = true; break; }
  }
  if (!$hasPon) {
    // Add as nullable to avoid failing on old rows; new inserts will fill it
    $pdo->exec("ALTER TABLE samples ADD COLUMN pon INTEGER");
  }

  // Indices
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_ts ON samples(ts)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_onu_ts ON samples(onuid, ts)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_pon_ts ON samples(pon, ts)");

  return $pdo;
}
