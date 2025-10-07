<?php
$CFG = require __DIR__.'/lib/config.php';
require __DIR__.'/lib/db.php';

/* Accept ?pon=8 or ?pon=1,3,7 to filter which PONs load */
$ponList = $CFG['PONS'];
if (isset($_GET['pon']) && $_GET['pon'] !== '') {
  $req = array_filter(array_map('intval', preg_split('/[,\s]+/', $_GET['pon'])));
  if ($req) {
    $ponList = array_values(array_intersect($ponList, $req));
  }
}

// Load data directly from database (server-side rendering for compatibility)
try {
    $pdo = db();
    $ponListStr = implode(',', array_map('intval', $ponList));
    
    $stmt = $pdo->query("
        SELECT 
            pon, onu, onuid, onuid_norm, description, model, 
            status, wan_status, wan_username, wan_mac, rx_power, last_update,
            (SELECT AVG(rx) FROM rx_samples WHERE onuid_norm = onu_cache.onuid_norm AND ts >= (strftime('%s', 'now') - 86400)) as rx_avg_24h
        FROM onu_cache 
        WHERE pon IN ($ponListStr)
        ORDER BY pon ASC, onu ASC
    ");
    
    $allOnus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest update time
    $latestStmt = $pdo->query("SELECT MAX(last_update) as latest FROM onu_cache LIMIT 1");
    $latestRow = $latestStmt->fetch();
    $lastUpdate = $latestRow ? $latestRow['latest'] : null;
    $dataAge = $lastUpdate ? (time() - strtotime($lastUpdate)) : null;
    
    $stats = [
        'total_onus' => 0,
        'online_onus' => 0,
        'offline_onus' => 0,
    ];
    
    foreach ($allOnus as $onu) {
        $stats['total_onus']++;
        if (stripos($onu['status'], 'online') !== false) {
            $stats['online_onus']++;
        } else {
            $stats['offline_onus']++;
        }
    }
    
} catch (Exception $e) {
    $allOnus = [];
    $stats = ['total_onus' => 0, 'online_onus' => 0, 'offline_onus' => 0];
    $dataAge = null;
    $error = $e->getMessage();
}

function getRxClass($rx) {
    if ($rx === null) return 'dim';
    if ($rx <= -28) return 'bad';
    if ($rx <= -23) return 'warn';
    if ($rx <= -8) return 'good';
    return 'warn';
}

/* cache-bust assets */
$ver_css = @filemtime(__DIR__.'/assets/styles.css') ?: time();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Syrotech OLT — ONU Monitor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/olt/assets/styles.css?v=<?= $ver_css ?>">
<style>
/* Copy optimized styles from index.php */
:root {
  --primary: #6366f1;
  --success: #22c55e;
  --warning: #eab308;
  --danger: #f87171;
  --info: #38bdf8;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

* { box-sizing: border-box; }

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  margin: 0;
  padding: 20px;
  background: linear-gradient(135deg, #e0e7ff 0%, #fce7f3 100%);
  min-height: 100vh;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  background: white;
  border-radius: 16px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.header {
  background: linear-gradient(135deg, #818cf8 0%, #a78bfa 100%);
  color: white;
  padding: 16px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.header h1 {
  font-size: 22px;
  font-weight: 700;
  margin: 0;
}

.stats-bar {
  display: flex;
  gap: 16px;
  padding: 12px 24px;
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  flex-wrap: wrap;
  align-items: center;
}

.stat-card {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: white;
  border-radius: 8px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-200);
  font-size: 14px;
}

.stat-card .label { color: var(--gray-600); font-weight: 500; }
.stat-card .value { font-weight: 700; font-size: 16px; color: var(--gray-900); }

.toolbar {
  display: flex;
  gap: 12px;
  padding: 12px 24px;
  background: white;
  border-bottom: 1px solid var(--gray-200);
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 250px;
  padding: 10px 14px;
  border: 2px solid var(--gray-200);
  border-radius: 10px;
  font-size: 14px;
}

.table-container {
  overflow-x: auto;
  background: white;
  max-height: calc(100vh - 260px);
  overflow-y: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1200px;
}

thead th {
  background: var(--gray-50);
  padding: 10px 8px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: var(--gray-700);
  border-bottom: 2px solid var(--gray-200);
  position: sticky;
  top: 0;
  z-index: 10;
}

tbody tr {
  border-bottom: 1px solid var(--gray-100);
}

tbody tr:hover {
  background: rgba(249, 250, 251, 0.8);
}

td {
  padding: 8px;
  font-size: 13px;
  vertical-align: middle;
}

.mono {
  font-family: 'Courier New', monospace;
  font-size: 12px;
}

.pon-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
}

.pon-1 { background: rgba(165, 180, 252, 0.15); color: #4f46e5; border: 1px solid rgba(165, 180, 252, 0.3); }
.pon-2 { background: rgba(253, 164, 175, 0.15); color: #be123c; border: 1px solid rgba(253, 164, 175, 0.3); }
.pon-3 { background: rgba(125, 211, 252, 0.15); color: #0369a1; border: 1px solid rgba(125, 211, 252, 0.3); }
.pon-4 { background: rgba(134, 239, 172, 0.15); color: #15803d; border: 1px solid rgba(134, 239, 172, 0.3); }
.pon-5 { background: rgba(240, 171, 252, 0.15); color: #a21caf; border: 1px solid rgba(240, 171, 252, 0.3); }
.pon-6 { background: rgba(252, 211, 77, 0.15); color: #a16207; border: 1px solid rgba(252, 211, 77, 0.3); }
.pon-7 { background: rgba(253, 186, 116, 0.15); color: #c2410c; border: 1px solid rgba(253, 186, 116, 0.3); }
.pon-8 { background: rgba(196, 181, 253, 0.15); color: #6d28d9; border: 1px solid rgba(196, 181, 253, 0.3); }

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.online {
  background: rgba(34, 197, 94, 0.1);
  color: var(--success);
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.status-badge.offline {
  background: rgba(248, 113, 113, 0.1);
  color: var(--danger);
  border: 1px solid rgba(248, 113, 113, 0.2);
}

.rx-value {
  display: inline-flex;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
}

.rx-value.good { background: rgba(34, 197, 94, 0.1); color: var(--success); }
.rx-value.warn { background: rgba(234, 179, 8, 0.1); color: var(--warning); }
.rx-value.bad { background: rgba(248, 113, 113, 0.1); color: var(--danger); }
.dim { color: #999; }

.wan-detail {
  font-size: 11px;
  color: var(--gray-500);
  margin-top: 2px;
}

@media (max-width: 768px) {
  body { padding: 10px; }
  .header { padding: 12px 16px; }
  .stats-bar, .toolbar { padding: 10px 16px; }
  table { min-width: 900px; }
  td, th { padding: 6px; font-size: 12px; }
}
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚡ Syrotech OLT — ONU Monitor <small style="opacity: 0.8; font-size: 14px;">· Server-Compatible Mode</small></h1>
      <div style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px;">
        🚀 Server Mode
      </div>
    </div>

    <div class="stats-bar">
      <div class="stat-card">
        <span class="label">📡 PONs:</span>
        <span class="value"><?= count($ponList) ?></span>
      </div>
      <div class="stat-card">
        <span class="label">🔌 ONUs:</span>
        <span class="value"><?= $stats['total_onus'] ?></span>
      </div>
      <div class="stat-card">
        <span class="label">✅ Online:</span>
        <span class="value"><?= $stats['online_onus'] ?></span>
      </div>
      <div class="stat-card">
        <span class="label">❌ Offline:</span>
        <span class="value"><?= $stats['offline_onus'] ?></span>
      </div>
      <?php if ($dataAge !== null): ?>
      <div class="stat-card">
        <span class="label">🕐 Data Age:</span>
        <span class="value"><?= floor($dataAge / 60) ?>m <?= $dataAge % 60 ?>s</span>
      </div>
      <?php endif; ?>
      <div style="margin-left: auto;">
        <a href="?refresh=1" style="background: var(--success); color: white; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">
          🔄 Refresh Data
        </a>
      </div>
    </div>

    <div class="toolbar">
      <input type="text" class="search-box" id="search" placeholder="Search by description, ONU ID, or model..." onkeyup="filterTable()">
      <select id="filter" onchange="filterTable()" style="padding: 10px; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 14px;">
        <option value="all">All ONUs</option>
        <option value="online">Online Only</option>
        <option value="offline">Offline Only</option>
      </select>
      <span id="count" style="font-weight: 600; color: var(--gray-600);"><?= count($allOnus) ?> shown</span>
    </div>

    <div class="table-container">
      <table id="data-table">
        <thead>
          <tr>
            <th>PON</th>
            <th>ONU</th>
            <th>ONU ID</th>
            <th>Description</th>
            <th>Model</th>
            <th>Status</th>
            <th>WAN</th>
            <th>RX Power</th>
            <th>24h Avg</th>
            <th>Δ</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($allOnus as $onu): 
            $statusOk = stripos($onu['status'], 'online') !== false;
            $statusClass = $statusOk ? 'online' : 'offline';
            $statusIcon = $statusOk ? '✅' : '❌';
            
            $rxClass = getRxClass($onu['rx_power']);
            $delta = null;
            if ($onu['rx_power'] !== null && $onu['rx_avg_24h'] !== null) {
                $delta = round($onu['rx_power'] - $onu['rx_avg_24h'], 2);
            }
            
            $searchText = strtolower($onu['description'] . ' ' . $onu['onuid'] . ' ' . $onu['model']);
        ?>
          <tr data-search="<?= htmlspecialchars($searchText) ?>" data-status="<?= $statusOk ? 'online' : 'offline' ?>">
            <td><div class="pon-badge pon-<?= $onu['pon'] ?>"><?= $onu['pon'] ?></div></td>
            <td class="mono"><?= $onu['onu'] ?></td>
            <td class="mono"><?= htmlspecialchars($onu['onuid']) ?></td>
            <td>
              <strong><?= htmlspecialchars($onu['description']) ?></strong>
              <?php if ($onu['wan_username'] || $onu['wan_mac']): ?>
              <div class="wan-detail">
                <?php if ($onu['wan_username']): ?>👤 <?= htmlspecialchars($onu['wan_username']) ?><?php endif; ?>
                <?php if ($onu['wan_username'] && $onu['wan_mac']): ?> · <?php endif; ?>
                <?php if ($onu['wan_mac']): ?>🔗 <?= htmlspecialchars($onu['wan_mac']) ?><?php endif; ?>
              </div>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($onu['model']) ?></td>
            <td><span class="status-badge <?= $statusClass ?>"><?= $statusIcon ?> <?= htmlspecialchars($onu['status']) ?></span></td>
            <td>
              <?php if ($statusOk && $onu['wan_status']): 
                  $isConnected = stripos($onu['wan_status'], 'connect') !== false;
                  $isUnknown = stripos($onu['wan_status'], 'unknown') !== false;
                  if ($isConnected): ?>
                <span class="status-badge online">✅ <?= htmlspecialchars($onu['wan_status']) ?></span>
                  <?php elseif ($isUnknown): ?>
                <span class="status-badge" style="background: rgba(234, 179, 8, 0.1); color: var(--warning); border: 1px solid rgba(234, 179, 8, 0.2);">⚠️ <?= htmlspecialchars($onu['wan_status']) ?></span>
                  <?php else: ?>
                <span class="status-badge offline">❌ <?= htmlspecialchars($onu['wan_status']) ?></span>
                  <?php endif; ?>
              <?php elseif ($statusOk): ?>
                <span class="dim">Loading...</span>
              <?php else: ?>
                <span class="dim">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($statusOk && $onu['rx_power'] !== null): ?>
                <span class="rx-value <?= $rxClass ?>"><?= number_format($onu['rx_power'], 2) ?> dBm</span>
              <?php elseif ($statusOk): ?>
                <span class="dim">Loading...</span>
              <?php else: ?>
                <span class="dim">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($onu['rx_avg_24h'] !== null): ?>
                <?= number_format($onu['rx_avg_24h'], 2) ?>
              <?php else: ?>
                <span class="dim">N/A</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($delta !== null): 
                  $deltaAbs = abs($delta);
                  $deltaClass = $deltaAbs >= 2 ? 'color: var(--danger); font-weight: 700;' : 
                               ($deltaAbs >= 1 ? 'color: var(--warning); font-weight: 600;' : 'color: var(--gray-600);');
                  $deltaIcon = $deltaAbs >= 1 ? ' ⚠️' : '';
              ?>
                <span style="<?= $deltaClass ?>"><?= ($delta >= 0 ? '+' : '') . number_format($delta, 2) ?> dB<?= $deltaIcon ?></span>
              <?php else: ?>
                <span style="color: var(--gray-400);">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
function filterTable() {
  var search = document.getElementById('search').value.toLowerCase();
  var filter = document.getElementById('filter').value;
  var rows = document.querySelectorAll('#data-table tbody tr');
  var shown = 0;
  
  rows.forEach(function(row) {
    var searchMatch = !search || row.dataset.search.indexOf(search) !== -1;
    var filterMatch = filter === 'all' || row.dataset.status === filter;
    
    if (searchMatch && filterMatch) {
      row.style.display = '';
      shown++;
    } else {
      row.style.display = 'none';
    }
  });
  
  document.getElementById('count').textContent = shown + ' shown';
}

// Handle refresh parameter
<?php if (isset($_GET['refresh'])): ?>
  window.location.href = '/olt/api/refresh.php?pons=<?= implode(',', $ponList) ?>&redirect=1';
<?php endif; ?>
</script>
</body>
</html>
