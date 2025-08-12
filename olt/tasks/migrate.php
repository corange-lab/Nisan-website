<?php
$CFG = require __DIR__ . '/../lib/config.php';
require __DIR__ . '/../lib/db.php';

$pdo = db();

$pdo->exec("
CREATE TABLE IF NOT EXISTS rx_samples (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  onuid_norm  TEXT    NOT NULL,
  pon         INTEGER,
  onu         INTEGER,
  rx          REAL    NOT NULL,
  ts          INTEGER NOT NULL
);
");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_onuid_ts ON rx_samples(onuid_norm, ts);");

echo "DB migrated.\n";
