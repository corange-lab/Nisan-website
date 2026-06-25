<?php
/**
 * Public Status API — no credentials, no IP/port exposed
 * Returns: current status, last 30 days uptime, recent incidents
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$DB_PATH = __DIR__ . '/../olt/data/status.sqlite';

function getDb(): PDO {
    global $DB_PATH;
    $dir = dirname($DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
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

    $db->exec("CREATE INDEX IF NOT EXISTS idx_checks_time ON status_checks(checked_at)");
    return $db;
}

// ── helpers ──────────────────────────────────────────────────────────────────

function checkOlt(): array {
    // Check internal OLT endpoint (server-side only — never exposed to browser)
    $start = microtime(true);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://103.178.104.34:18292',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_NOBODY         => true,   // HEAD only — fast
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

function recordCheck(PDO $db, bool $isUp, int $ms): void {
    $now = time();
    $db->prepare("INSERT INTO status_checks (checked_at, is_up, response_ms) VALUES (?, ?, ?)")
       ->execute([$now, $isUp ? 1 : 0, $ms]);

    // Manage incidents
    $lastIncident = $db->query(
        "SELECT * FROM incidents ORDER BY started_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    if (!$isUp) {
        // Open new incident if none open
        if (!$lastIncident || $lastIncident['resolved_at'] !== null) {
            $db->prepare("INSERT INTO incidents (started_at, title) VALUES (?, 'Network Outage')")
               ->execute([$now]);
        }
    } else {
        // Close any open incident
        if ($lastIncident && $lastIncident['resolved_at'] === null) {
            $dur = $now - $lastIncident['started_at'];
            $db->prepare("UPDATE incidents SET resolved_at=?, duration_sec=? WHERE id=?")
               ->execute([$now, $dur, $lastIncident['id']]);
        }
    }

    // Prune checks older than 35 days
    $db->exec("DELETE FROM status_checks WHERE checked_at < " . ($now - 35 * 86400));
}

function buildResponse(PDO $db): array {
    $now   = time();
    $day30 = $now - 30 * 86400;

    // Current status — latest check
    $latest = $db->query(
        "SELECT is_up, response_ms, checked_at FROM status_checks ORDER BY checked_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $isUp = $latest ? (bool) $latest['is_up'] : true;

    // 30-day uptime %
    $counts = $db->query(
        "SELECT SUM(is_up) as up_cnt, COUNT(*) as total
         FROM status_checks WHERE checked_at >= $day30"
    )->fetch(PDO::FETCH_ASSOC);

    $uptime30 = ($counts['total'] > 0)
        ? round($counts['up_cnt'] / $counts['total'] * 100, 3)
        : 100.0;

    // Daily buckets for 30-day graph (day index 0=oldest, 29=today)
    $dailyRows = $db->query(
        "SELECT
            CAST((checked_at - $day30) / 86400 AS INTEGER) as day_idx,
            SUM(is_up) as up_cnt,
            COUNT(*) as total
         FROM status_checks
         WHERE checked_at >= $day30
         GROUP BY day_idx
         ORDER BY day_idx"
    )->fetchAll(PDO::FETCH_ASSOC);

    $dailyMap = [];
    foreach ($dailyRows as $r) {
        $dailyMap[(int)$r['day_idx']] = [
            'pct'   => round($r['up_cnt'] / max($r['total'], 1) * 100, 1),
            'total' => (int) $r['total'],
        ];
    }

    $days = [];
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', $day30 + $i * 86400);
        $days[] = [
            'date'  => $date,
            'pct'   => $dailyMap[$i]['pct'] ?? null,
            'total' => $dailyMap[$i]['total'] ?? 0,
        ];
    }

    // Recent incidents (last 10)
    $incidents = $db->query(
        "SELECT started_at, resolved_at, duration_sec, title
         FROM incidents
         ORDER BY started_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    $formattedIncidents = array_map(function($inc) {
        $dur = null;
        if ($inc['duration_sec']) {
            $s = (int) $inc['duration_sec'];
            if ($s < 60)       $dur = "{$s}s";
            elseif ($s < 3600) $dur = round($s/60) . "m";
            else               $dur = round($s/3600, 1) . "h";
        }
        return [
            'title'       => $inc['title'],
            'started_at'  => (int) $inc['started_at'],
            'resolved_at' => $inc['resolved_at'] ? (int) $inc['resolved_at'] : null,
            'duration'    => $dur,
            'ongoing'     => $inc['resolved_at'] === null,
        ];
    }, $incidents);

    // Open incident?
    $openIncident = null;
    if ($formattedIncidents && $formattedIncidents[0]['ongoing']) {
        $openIncident = $formattedIncidents[0];
    }

    return [
        'status'        => $isUp ? 'up' : 'down',
        'checked_at'    => $latest ? (int) $latest['checked_at'] : $now,
        'response_ms'   => $latest ? (int) $latest['response_ms'] : null,
        'uptime_30d'    => $uptime30,
        'days'          => $days,
        'incidents'     => $formattedIncidents,
        'open_incident' => $openIncident,
    ];
}

// ── routing ───────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? 'status';

try {
    $db = getDb();

    if ($action === 'check') {
        // Called by server-side cron / internal poller — not from browser
        $secret = $_GET['secret'] ?? '';
        $expectedSecret = 'nisan_status_check_' . date('Ymd');
        if ($secret !== $expectedSecret) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        $result = checkOlt();
        recordCheck($db, $result['is_up'], $result['ms']);
        echo json_encode(['recorded' => true, 'is_up' => $result['is_up'], 'ms' => $result['ms']]);
        exit;
    }

    // Default: return status data
    echo json_encode(buildResponse($db));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
