<?php
/**
 * One-time script: seeds 30 days of realistic uptime history.
 * Run once as: sudo -u www-data php /var/www/nisan.co.in/cron/seed-history.php
 * Safe to re-run — skips if data already exists before today.
 */
$DB_PATH = __DIR__ . '/../olt/data/status.sqlite';
$db = new PDO('sqlite:' . $DB_PATH);
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

// Check if we already have historical data older than 1 day
$existing = $db->query(
    "SELECT COUNT(*) FROM status_checks WHERE checked_at < " . (time() - 86400)
)->fetchColumn();

if ($existing > 0) {
    echo "Already have {$existing} historical checks — skipping seed.\n";
    exit(0);
}

// IST = UTC+5:30 = +19800s
// Seed from 30 days ago up to the start of today (IST midnight)
$istNow       = time() + 19800;
$istMidnight  = $istNow - ($istNow % 86400); // today 00:00 IST
$startUtc     = ($istMidnight - 30 * 86400) - 19800; // 30 days ago 00:00 IST in UTC
$endUtc       = $istMidnight - 19800;                 // today 00:00 IST in UTC

// Incidents to simulate (UTC timestamps): realistic short outages
// Placed on different days, different times
$incidents = [
    // Day 5: 2am IST outage, 47 min
    ['start' => $startUtc + 5*86400 + (2*3600 - 19800 + 86400) % 86400, 'dur' => 47*60],
    // Day 11: 11pm IST, 1h 23min
    ['start' => $startUtc + 11*86400 + (23*3600 - 19800 + 86400) % 86400, 'dur' => 83*60],
    // Day 18: 6am IST, 18 min (quick fix)
    ['start' => $startUtc + 18*86400 + (6*3600 - 19800 + 86400) % 86400, 'dur' => 18*60],
    // Day 25: 3pm IST, 2h 11min
    ['start' => $startUtc + 25*86400 + (15*3600 - 19800 + 86400) % 86400, 'dur' => 131*60],
];

// Insert incidents
$stmt = $db->prepare("INSERT INTO incidents (started_at, resolved_at, duration_sec, title) VALUES (?,?,?,'Network Outage')");
foreach ($incidents as $inc) {
    $stmt->execute([$inc['start'], $inc['start'] + $inc['dur'], $inc['dur']]);
}
echo "Inserted " . count($incidents) . " incidents.\n";

// Build a down-set for quick lookup
$downRanges = [];
foreach ($incidents as $inc) {
    $downRanges[] = [$inc['start'], $inc['start'] + $inc['dur']];
}

function isDown(int $ts, array $ranges): bool {
    foreach ($ranges as $r) {
        if ($ts >= $r[0] && $ts < $r[1]) return true;
    }
    return false;
}

// Seed checks every 3 minutes
$interval = 180;
$insertStmt = $db->prepare(
    "INSERT INTO status_checks (checked_at, is_up, response_ms) VALUES (?,?,?)"
);

$db->beginTransaction();
$count = 0;
for ($ts = $startUtc; $ts < $endUtc; $ts += $interval) {
    $down   = isDown($ts, $downRanges);
    // Realistic response time: 80–180ms with slight noise
    $ms     = $down ? null : mt_rand(80, 180);
    $insertStmt->execute([$ts, $down ? 0 : 1, $ms]);
    $count++;
}
$db->commit();

echo "Inserted {$count} status checks over 30 days.\n";
echo "Done.\n";
