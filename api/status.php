<?php
/**
 * Public Status API — no credentials, no IP/port exposed
 * ?action=status   → current status + 30d + 24h + incidents
 * ?action=day&date=YYYY-MM-DD → hourly breakdown for one day
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');

$DB_PATH = __DIR__ . '/../olt/data/status.sqlite';

function getDb(): PDO {
    global $DB_PATH;
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

function checkOlt(): array {
    $start = microtime(true);
    $ch = curl_init();
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

function recordCheck(PDO $db, bool $isUp, int $ms): void {
    $now = time();
    $db->prepare("INSERT INTO status_checks (checked_at, is_up, response_ms) VALUES (?, ?, ?)")
       ->execute([$now, $isUp ? 1 : 0, $ms]);

    $lastIncident = $db->query(
        "SELECT * FROM incidents ORDER BY started_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    if (!$isUp) {
        if (!$lastIncident || $lastIncident['resolved_at'] !== null) {
            $db->prepare("INSERT INTO incidents (started_at, title) VALUES (?, 'Network Outage')")
               ->execute([$now]);
        }
    } else {
        if ($lastIncident && $lastIncident['resolved_at'] === null) {
            $dur = $now - $lastIncident['started_at'];
            $db->prepare("UPDATE incidents SET resolved_at=?, duration_sec=? WHERE id=?")
               ->execute([$now, $dur, $lastIncident['id']]);
        }
    }

    $db->exec("DELETE FROM status_checks WHERE checked_at < " . ($now - 35 * 86400));
}

// Format human-readable duration
function fmtDur(int $s): string {
    if ($s < 60)   return "{$s} sec";
    if ($s < 3600) {
        $m = intdiv($s, 60); $r = $s % 60;
        return $r ? "{$m} min {$r} sec" : "{$m} min";
    }
    $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60);
    return $m ? "{$h} hr {$m} min" : "{$h} hr";
}

// Build IST time string (UTC+5:30)
function istTime(int $ts): string {
    $dt = new DateTime('@' . $ts);
    $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
    return $dt->format('d M Y, h:i A');
}

function buildDayDetail(PDO $db, string $date): array {
    $ist = new DateTimeZone('Asia/Kolkata');

    // Parse as IST midnight — strtotime() uses server UTC, so use DateTime instead
    $dayDt = new DateTime($date . ' 00:00:00', $ist);
    if (!$dayDt) { http_response_code(400); echo json_encode(['error'=>'Invalid date']); exit; }
    $dayStart = $dayDt->getTimestamp();   // UTC ts of 00:00 IST on $date
    $dayEnd   = $dayStart + 86400;
    $rows = $db->query(
        "SELECT
            CAST((checked_at - {$dayStart}) / 3600 AS INTEGER) as hr,
            SUM(is_up) as up_cnt, COUNT(*) as total,
            MIN(response_ms) as min_ms, MAX(response_ms) as max_ms,
            CAST(AVG(response_ms) AS INTEGER) as avg_ms
         FROM status_checks
         WHERE checked_at >= {$dayStart} AND checked_at < {$dayEnd}
         GROUP BY hr ORDER BY hr"
    )->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) $map[(int)$r['hr']] = $r;

    $hours = [];
    for ($h = 0; $h < 24; $h++) {
        $slotTs = $dayStart + $h * 3600;
        $slotDt = new DateTime('@' . $slotTs);
        $slotDt->setTimezone($ist);
        $r = $map[$h] ?? null;
        $hours[] = [
            'hour'   => $h,
            'label'  => $slotDt->format('g A'),  // "12 AM", "1 AM" … "11 PM"
            'pct'    => $r ? round($r['up_cnt'] / max($r['total'],1) * 100, 1) : null,
            'total'  => $r ? (int)$r['total'] : 0,
            'avg_ms' => $r && $r['avg_ms'] ? (int)$r['avg_ms'] : null,
            'min_ms' => $r && $r['min_ms'] ? (int)$r['min_ms'] : null,
            'max_ms' => $r && $r['max_ms'] ? (int)$r['max_ms'] : null,
        ];
    }

    // Incidents that overlap this day
    $incRows = $db->query(
        "SELECT * FROM incidents
         WHERE started_at < {$dayEnd}
           AND (resolved_at IS NULL OR resolved_at >= {$dayStart})
         ORDER BY started_at"
    )->fetchAll(PDO::FETCH_ASSOC);

    $incidents = array_map(function($inc) use ($dayStart, $dayEnd) {
        $start = max((int)$inc['started_at'], $dayStart);
        $end   = $inc['resolved_at'] ? min((int)$inc['resolved_at'], $dayEnd) : null;
        $dur   = $end ? ($end - $start) : null;
        return [
            'started_at'  => (int)$inc['started_at'],
            'resolved_at' => $inc['resolved_at'] ? (int)$inc['resolved_at'] : null,
            'ongoing'     => $inc['resolved_at'] === null,
            'started_fmt' => istTime((int)$inc['started_at']),
            'resolved_fmt'=> $inc['resolved_at'] ? istTime((int)$inc['resolved_at']) : null,
            'duration'    => $dur ? fmtDur($dur) : null,
        ];
    }, $incRows);

    return ['date' => $date, 'hours' => $hours, 'incidents' => $incidents];
}

function buildResponse(PDO $db): array {
    $now = time();
    $ist = new DateTimeZone('Asia/Kolkata');

    // Anchor everything to IST midnight of today
    $todayIst = new DateTime('now', $ist);
    $todayIst->setTime(0, 0, 0);
    $todayMidnightUtc = $todayIst->getTimestamp(); // today 00:00 IST in UTC

    // 30-day window: from 30 IST-days ago midnight up to now
    $day30start = $todayMidnightUtc - 30 * 86400; // 00:00 IST, 30 days ago

    // Current status
    $latest = $db->query(
        "SELECT is_up, response_ms, checked_at FROM status_checks ORDER BY checked_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $isUp = $latest ? (bool)$latest['is_up'] : true;

    // 30-day uptime
    $counts = $db->query(
        "SELECT SUM(is_up) as up_cnt, COUNT(*) as total FROM status_checks WHERE checked_at >= {$day30start}"
    )->fetch(PDO::FETCH_ASSOC);
    $uptime30 = $counts['total'] > 0
        ? round($counts['up_cnt'] / $counts['total'] * 100, 3) : 100.0;

    // 30-day daily buckets — each bucket = one IST calendar day
    $dailyRows = $db->query(
        "SELECT
            CAST((checked_at - {$day30start}) / 86400 AS INTEGER) as day_idx,
            SUM(is_up) as up_cnt, COUNT(*) as total
         FROM status_checks WHERE checked_at >= {$day30start}
         GROUP BY day_idx ORDER BY day_idx"
    )->fetchAll(PDO::FETCH_ASSOC);

    $dailyMap = [];
    foreach ($dailyRows as $r) {
        $idx = (int)$r['day_idx'];
        if ($idx >= 0 && $idx < 30) {
            $dailyMap[$idx] = [
                'pct'   => round($r['up_cnt'] / max($r['total'],1) * 100, 1),
                'total' => (int)$r['total'],
            ];
        }
    }
    $days = [];
    for ($i = 0; $i < 30; $i++) {
        // Date label = IST date of the bucket start
        $bucketDt = new DateTime('@' . ($day30start + $i * 86400));
        $bucketDt->setTimezone($ist);
        $days[] = [
            'date'  => $bucketDt->format('Y-m-d'),  // IST calendar date
            'pct'   => $dailyMap[$i]['pct'] ?? null,
            'total' => $dailyMap[$i]['total'] ?? 0,
        ];
    }

    // 24-hour hourly buckets — IST-aligned so labels match clock hours
    // Find the start of the current IST hour
    $ist = new DateTimeZone('Asia/Kolkata');
    $nowDt = new DateTime('@' . $now);
    $nowDt->setTimezone($ist);
    // Truncate to current hour in IST
    $nowDt->setTime((int)$nowDt->format('H'), 0, 0);
    $istHourStart = $nowDt->getTimestamp(); // start of current IST hour (UTC ts)
    $window24Start = $istHourStart - 23 * 3600; // 24 slots: 23 past hours + current

    $hourRows = $db->query(
        "SELECT
            CAST((checked_at - {$window24Start}) / 3600 AS INTEGER) as hr_idx,
            SUM(is_up) as up_cnt, COUNT(*) as total,
            CAST(AVG(response_ms) AS INTEGER) as avg_ms
         FROM status_checks
         WHERE checked_at >= {$window24Start} AND hr_idx BETWEEN 0 AND 23
         GROUP BY hr_idx ORDER BY hr_idx"
    )->fetchAll(PDO::FETCH_ASSOC);

    $hourMap = [];
    foreach ($hourRows as $r) $hourMap[(int)$r['hr_idx']] = $r;

    $hours24 = [];
    for ($i = 0; $i < 24; $i++) {
        $ts = $window24Start + $i * 3600;
        $dt = new DateTime('@' . $ts);
        $dt->setTimezone($ist);
        $r = $hourMap[$i] ?? null;
        $hours24[] = [
            'label'  => $dt->format('g A'),   // "9 AM", "10 AM" — no leading zero
            'pct'    => $r ? round($r['up_cnt'] / max($r['total'],1) * 100, 1) : null,
            'total'  => $r ? (int)$r['total'] : 0,
            'avg_ms' => $r && $r['avg_ms'] ? (int)$r['avg_ms'] : null,
        ];
    }

    // Incidents (last 15)
    $incRows = $db->query(
        "SELECT * FROM incidents ORDER BY started_at DESC LIMIT 15"
    )->fetchAll(PDO::FETCH_ASSOC);

    $incidents = array_map(function($inc) use ($now) {
        $ongoing = $inc['resolved_at'] === null;
        $dur     = $ongoing
            ? ($now - $inc['started_at'])
            : (int)$inc['duration_sec'];
        return [
            'started_at'    => (int)$inc['started_at'],
            'resolved_at'   => $ongoing ? null : (int)$inc['resolved_at'],
            'ongoing'       => $ongoing,
            'started_fmt'   => istTime((int)$inc['started_at']),
            'resolved_fmt'  => $ongoing ? null : istTime((int)$inc['resolved_at']),
            'duration'      => fmtDur($dur),
            'duration_sec'  => $dur,
        ];
    }, $incRows);

    // Open incident
    $openIncident = ($incidents && $incidents[0]['ongoing']) ? $incidents[0] : null;

    // Next poll interval hint for frontend
    $nextPoll = $isUp ? 180 : 30;

    return [
        'status'        => $isUp ? 'up' : 'down',
        'checked_at'    => $latest ? (int)$latest['checked_at'] : $now,
        'response_ms'   => $latest ? (int)$latest['response_ms'] : null,
        'uptime_30d'    => $uptime30,
        'next_poll'     => $nextPoll,
        'days'          => $days,
        'hours_24'      => $hours24,
        'incidents'     => $incidents,
        'open_incident' => $openIncident,
    ];
}

// ── routing ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'status';

try {
    $db = getDb();

    if ($action === 'check') {
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

    if ($action === 'day') {
        $date = preg_replace('/[^0-9-]/', '', $_GET['date'] ?? date('Y-m-d'));
        echo json_encode(buildDayDetail($db, $date));
        exit;
    }

    echo json_encode(buildResponse($db));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error', 'msg' => $e->getMessage()]);
}
