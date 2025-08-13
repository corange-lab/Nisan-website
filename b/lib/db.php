<?php
function db($CFG){
  static $pdo=null;
  if ($pdo) return $pdo;
  $dsn  = $CFG['DB']['dsn'];
  $user = $CFG['DB']['user'];
  $pass = $CFG['DB']['pass'];
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  if (strpos($dsn,'sqlite:')===0){
    $pdo->exec("PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL;");
  }
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS samples (
      ts INTEGER NOT NULL,
      pon INTEGER NOT NULL,
      onuid TEXT NOT NULL,
      input_bytes TEXT,
      output_bytes TEXT,
      PRIMARY KEY (ts, onuid)
    )
  ");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_samples_onuid_ts ON samples(onuid, ts)");
  return $pdo;
}

function insert_samples($pdo, $rows, $ts){
  $stmt = $pdo->prepare("INSERT OR REPLACE INTO samples (ts, pon, onuid, input_bytes, output_bytes) VALUES (:ts,:pon,:onuid,:inb,:outb)");
  foreach ($rows as $r){
    $stmt->execute([
      ':ts'    => $ts,
      ':pon'   => (int)$r['pon'],
      ':onuid' => (string)$r['onuid'],
      ':inb'   => ($r['input_bytes']===null?null:(string)$r['input_bytes']),
      ':outb'  => ($r['output_bytes']===null?null:(string)$r['output_bytes']),
    ]);
  }
}
