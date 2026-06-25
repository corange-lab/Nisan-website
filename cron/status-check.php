<?php
/**
 * Server-side status poller — run via cron every 1 minute.
 * It decides internally whether to actually record a check based on
 * adaptive interval: 30s during outage, 3 min during normal operation.
 *
 * Crontab (add via `crontab -e`):
 *   * * * * * php /var/www/html/cron/status-check.php >> /var/log/nisan-status.log 2>&1
 */

define('DB_PATH', __DIR__ . '/../olt/data/status.sqlite');

function getDb(): PDO {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA journal_mode=WAL");
    $db->exec("CREATE TABLE IF NOT EXISTS status_checks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        checked_at INTEGER NOT NULL,
        is_up INTEGER NOT NULL DEFAULT 0,
        response_ms INTEGER
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS incidents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        started_at INTEGER NOT NULL,
        resolved_at INTEGER,
        duration_sec INTEGER,
        title TEXT NOT NULL DEFAULT 'Network Outage'
    )");
    return $db;
}

function checkOlt(): array {
    $start = microtime(true);
    $ch    = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://103.178.104.28:18292',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno    = curl_errno($ch);
    curl_close($ch);

    $ms   = (int) round((microtime(true) - $start) * 1000);
    $isUp = ($errno === 0 && $httpCode > 0);
    return ['is_up' => $isUp, 'ms' => $ms];
}

$db  = getDb();
$now = time();

// Get last check and last status
$last = $db->query(
    "SELECT is_up, checked_at FROM status_checks ORDER BY checked_at DESC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

$lastIsUp = $last ? (bool) $last['is_up'] : true;
$lastTime = $last ? (int) $last['checked_at'] : 0;
$elapsed  = $now - $lastTime;

// Adaptive interval: 30s when down, 180s (3 min) when up
$interval = $lastIsUp ? 180 : 30;

if ($elapsed < $interval) {
    echo date('c') . " | Skip — {$elapsed}s elapsed, next check in " . ($interval - $elapsed) . "s\n";
    exit(0);
}

$result = checkOlt();
$isUp   = $result['is_up'];
$ms     = $result['ms'];

// Record check
$db->prepare("INSERT INTO status_checks (checked_at, is_up, response_ms) VALUES (?, ?, ?)")
   ->execute([$now, $isUp ? 1 : 0, $ms]);

// Manage incidents
$lastIncident = $db->query(
    "SELECT * FROM incidents ORDER BY started_at DESC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!$isUp) {
    if (!$lastIncident || $lastIncident['resolved_at'] !== null) {
        $db->prepare("INSERT INTO incidents (started_at, title) VALUES (?, 'Network Outage')")
           ->execute([$now]);
        echo date('c') . " | DOWN — Incident opened\n";
    } else {
        echo date('c') . " | DOWN — Incident ongoing\n";
    }
} else {
    if ($lastIncident && $lastIncident['resolved_at'] === null) {
        $dur = $now - $lastIncident['started_at'];
        $db->prepare("UPDATE incidents SET resolved_at=?, duration_sec=? WHERE id=?")
           ->execute([$now, $dur, $lastIncident['id']]);
        echo date('c') . " | UP — Incident resolved after {$dur}s\n";
    } else {
        echo date('c') . " | UP — {$ms}ms\n";
    }
}

// Prune old data
$db->exec("DELETE FROM status_checks WHERE checked_at < " . ($now - 35 * 86400));
