<?php
$CFG = require __DIR__.'/lib/config.php';

/* Your app lives at /olt — set paths explicitly */
$ASSETS   = '/olt/assets/';
$API_BASE = '/olt/api/';

/* Accept ?pon=8 or ?pon=1,3,7 to filter which PONs load */
$ponList = $CFG['PONS'];
if (isset($_GET['pon']) && $_GET['pon'] !== '') {
  $req = array_filter(array_map('intval', preg_split('/[,\s]+/', $_GET['pon'])));
  if ($req) {
    $ponList = array_values(array_intersect($ponList, $req));
  }
}

/* cache-bust assets when you deploy */
$ver_css = @filemtime(__DIR__.'/assets/styles.css') ?: time();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Syrotech OLT — ONU Monitor (Ultra Fast)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= htmlspecialchars($ASSETS, ENT_QUOTES) ?>styles.css?v=<?= $ver_css ?>">
<style>
/* Copy all the styles from enhanced version */
:root {
  --primary: #6366f1;
  --primary-dark: #4f46e5;
  --success: #22c55e;
  --warning: #eab308;
  --danger: #f87171;
  --info: #38bdf8;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

* { box-sizing: border-box; }

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  margin: 0;
  padding: 20px;
  background: linear-gradient(135deg, #e0e7ff 0%, #fce7f3 100%);
  min-height: 100vh;
  color: var(--gray-800);
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
  position: relative;
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

.header .subtitle {
  font-size: 13px;
  opacity: 0.9;
  margin: 0;
  display: inline-block;
  margin-left: 12px;
}

.speed-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  backdrop-filter: blur(10px);
  white-space: nowrap;
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

.stat-card .label {
  color: var(--gray-600);
  font-weight: 500;
}

.stat-card .value {
  font-weight: 700;
  font-size: 16px;
  color: var(--gray-900);
}

.toolbar {
  display: flex;
  gap: 12px;
  padding: 12px 24px;
  background: white;
  border-bottom: 1px solid var(--gray-200);
  align-items: center;
  flex-wrap: wrap;
}

.search-container {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-container input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 2px solid var(--gray-200);
  border-radius: 12px;
  font-size: 14px;
  transition: all 0.2s ease;
  background: var(--gray-50);
}

.search-container input:focus {
  outline: none;
  border-color: var(--primary);
  background: white;
  box-shadow: 0 0 0 3px rgb(99 102 241 / 0.1);
}

.search-container::before {
  content: '🔍';
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  opacity: 0.5;
}

.filter-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 8px 16px;
  border: 2px solid var(--gray-200);
  background: white;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--gray-700);
}

.filter-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.filter-btn.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.table-container {
  overflow-x: auto;
  background: white;
  max-height: calc(100vh - 260px);
  overflow-y: auto;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
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
  transition: all 0.15s ease;
  border-bottom: 1px solid var(--gray-100);
}

tbody tr:hover {
  background: rgba(249, 250, 251, 0.8);
  box-shadow: var(--shadow-sm);
}

td {
  padding: 8px;
  font-size: 13px;
  vertical-align: middle;
}

.mono {
  font-family: 'JetBrains Mono', 'Fira Code', 'SF Mono', Consolas, monospace;
  font-size: 13px;
}

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

.speed-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: rgba(56, 189, 248, 0.12);
  color: #0369a1;
  border: 1px solid rgba(56, 189, 248, 0.25);
  min-width: 90px;
  justify-content: center;
}

.speed-sub {
  margin-top: 3px;
  font-size: 11px;
  color: var(--gray-500);
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
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
}

.rx-value.good {
  background: rgba(34, 197, 94, 0.1);
  color: var(--success);
}

.rx-value.warn {
  background: rgba(234, 179, 8, 0.1);
  color: var(--warning);
}

.rx-value.bad {
  background: rgba(248, 113, 113, 0.1);
  color: var(--danger);
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
  color: white;
}

.pon-1 { background: rgba(165, 180, 252, 0.15); color: #4f46e5; border: 1px solid rgba(165, 180, 252, 0.3); }
.pon-2 { background: rgba(253, 164, 175, 0.15); color: #be123c; border: 1px solid rgba(253, 164, 175, 0.3); }
.pon-3 { background: rgba(125, 211, 252, 0.15); color: #0369a1; border: 1px solid rgba(125, 211, 252, 0.3); }
.pon-4 { background: rgba(134, 239, 172, 0.15); color: #15803d; border: 1px solid rgba(134, 239, 172, 0.3); }
.pon-5 { background: rgba(240, 171, 252, 0.15); color: #a21caf; border: 1px solid rgba(240, 171, 252, 0.3); }
.pon-6 { background: rgba(252, 211, 77, 0.15); color: #a16207; border: 1px solid rgba(252, 211, 77, 0.3); }
.pon-7 { background: rgba(253, 186, 116, 0.15); color: #c2410c; border: 1px solid rgba(253, 186, 116, 0.3); }
.pon-8 { background: rgba(196, 181, 253, 0.15); color: #6d28d9; border: 1px solid rgba(196, 181, 253, 0.3); }

.refresh-notice {
  padding: 8px 24px;
  background: #fef3c7;
  border-bottom: 2px solid #fbbf24;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 13px;
}

.refresh-notice.stale {
  background: #fee2e2;
  border-color: #ef4444;
}

.refresh-btn {
  padding: 5px 12px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.refresh-btn:hover {
  background: var(--primary-dark);
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(220px, 1fr));
  gap: 12px;
  padding: 12px 24px;
  background: #fff;
  border-bottom: 1px solid var(--gray-200);
}

.insight-card {
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 10px;
  padding: 10px 12px;
}

.insight-card h3 {
  margin: 0 0 6px;
  font-size: 13px;
  color: var(--gray-700);
}

.insight-list {
  margin: 0;
  padding-left: 16px;
  font-size: 12px;
  color: var(--gray-700);
}

.insight-list li {
  margin: 4px 0;
}

@media (max-width: 768px) {
  body { padding: 8px; }
  
  .container {
    border-radius: 12px;
  }
  
  .header { 
    padding: 12px 16px; 
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .header h1 { 
    font-size: 18px; 
  }
  
  .header .subtitle {
    font-size: 12px;
    margin-left: 0;
    display: block;
  }
  
  .speed-badge {
    font-size: 12px;
    padding: 4px 10px;
  }
  
  .stats-bar { 
    padding: 10px 16px; 
    gap: 8px;
    font-size: 12px;
  }
  
  .stat-card {
    padding: 4px 10px;
    font-size: 12px;
  }
  
  .stat-card .value {
    font-size: 14px;
  }
  
  .toolbar { 
    padding: 10px 16px;
    gap: 8px;
  }
  
  .search-container { 
    min-width: 200px;
    flex: 1 1 100%;
  }
  
  .search-container input {
    font-size: 13px;
    padding: 10px 14px 10px 40px;
  }
  
  .filter-buttons {
    flex: 1 1 auto;
  }
  
  .filter-btn {
    padding: 6px 12px;
    font-size: 12px;
  }
  
  .refresh-btn {
    padding: 6px 10px;
    font-size: 11px;
  }
  
  .refresh-notice {
    padding: 8px 16px;
    font-size: 12px;
    flex-wrap: wrap;
  }

  .insights-grid {
    grid-template-columns: 1fr;
    padding: 10px 16px;
  }
  
  .table-container {
    max-height: calc(100vh - 320px);
  }
  
  table {
    min-width: 900px;
  }
  
  td, th {
    padding: 6px;
    font-size: 12px;
  }
}

.spinner-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--gray-300);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin-animation 0.8s linear infinite;
}

@keyframes spin-animation {
  to { transform: rotate(360deg); }
}
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div style="flex: 1;">
        <h1>
          ⚡ Syrotech OLT — ONU Monitor
          <span class="subtitle">
            · <span class="mono" id="snap"></span>
            · Database-powered
          </span>
        </h1>
      </div>
      <div class="speed-badge">
        🚀 <span id="load-time">0.5</span>s
      </div>
    </div>

    <div id="refresh-notice" class="refresh-notice" style="display: none;">
      <span id="refresh-message">📊 Data age: <span id="data-age">-</span></span>
      <button class="refresh-btn" onclick="location.reload()">Reload Page</button>
      <button class="refresh-btn" id="live-refresh-btn" style="background: var(--success);">🔄 Fetch Live Data</button>
    </div>

    <div class="stats-bar">
      <div class="stat-card">
        <span class="label">📡 PONs:</span>
        <span class="value" id="total-pons">0</span>
      </div>
      <div class="stat-card">
        <span class="label">🔌 ONUs:</span>
        <span class="value" id="total-onus">0</span>
      </div>
      <div class="stat-card">
        <span class="label">✅ Online:</span>
        <span class="value" id="online-onus">0</span>
      </div>
      <div class="stat-card">
        <span class="label">❌ Offline:</span>
        <span class="value" id="offline-onus">0</span>
      </div>
      <div class="stat-card">
        <span class="label">🌐 Net Now:</span>
        <span class="value" id="net-total-now">0.00 Mbps</span>
      </div>
      <div class="stat-card">
        <span class="label">⬆ Net Up:</span>
        <span class="value" id="net-up-now">0.00 Mbps</span>
      </div>
      <div class="stat-card">
        <span class="label">⬇ Net Down:</span>
        <span class="value" id="net-down-now">0.00 Mbps</span>
      </div>
      <div class="stat-card">
        <span class="label">📦 Net 24h Usage:</span>
        <span class="value" id="net-usage-24h">0 B</span>
      </div>
      <div style="margin-left: auto; font-size: 13px; color: var(--gray-500);">
        <span id="count">0 shown</span>
      </div>
  </div>

  <div class="insights-grid">
    <div class="insight-card">
      <h3>Network Peak (24h)</h3>
      <div id="net-peak-24h" style="font-weight:700;font-size:15px;">—</div>
      <div id="net-peak-24h-time" style="font-size:12px;color:var(--gray-500);margin-top:4px;">—</div>
    </div>
    <div class="insight-card">
      <h3>Top Users Now</h3>
      <ol id="top-users-now" class="insight-list"><li>Loading…</li></ol>
    </div>
    <div class="insight-card">
      <h3>Top Users by 24h Usage</h3>
      <ol id="top-users-usage" class="insight-list"><li>Loading…</li></ol>
    </div>
  </div>

  <div class="toolbar">
      <div class="search-container">
        <input id="search" type="text" placeholder="Search...">
      </div>
      <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="online">Online</button>
        <button class="filter-btn" data-filter="offline">Offline</button>
      </div>
      <button class="refresh-btn" id="live-refresh-btn-toolbar" style="background: var(--success); border: none; cursor: pointer; padding: 6px 14px; font-size: 13px;" title="Fetch real-time data from OLT (Ctrl+R)">
        🔄 Fetch Live
      </button>
      <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; color: var(--gray-600); white-space: nowrap;">
        <input type="checkbox" id="realtime-master-checkbox" style="width: 16px; height: 16px; cursor: pointer;" checked>
        Realtime Global
      </label>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <input id="specific-onu-input" type="text" placeholder="Specific ONU (e.g. GPON0/1:12)" style="padding:7px 10px;border:1px solid var(--gray-300);border-radius:8px;min-width:220px;font-size:12px;">
        <button class="refresh-btn" id="specific-onu-btn" style="background:#0ea5e9;border:none;cursor:pointer;padding:6px 12px;font-size:12px;">🎯 Specific Realtime</button>
        <button class="refresh-btn" id="specific-onu-stop-btn" style="background:#ef4444;border:none;cursor:pointer;padding:6px 10px;font-size:12px;">Stop</button>
      </div>
      <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px; color: var(--gray-600); white-space: nowrap;">
        <input type="checkbox" id="auto-refresh-checkbox" style="width: 16px; height: 16px; cursor: pointer;">
        Auto OLT Pull
      </label>
  </div>

    <div class="table-container">
    <table id="tbl">
      <thead>
        <tr>
            <th>ONU ID</th>
            <th>Description</th>
            <th>Status</th>
            <th>WAN Status</th>
            <th>Speed Now</th>
            <th>RX Power</th>
            <th>24h Avg</th>
            <th>Δ vs 24h</th>
        </tr>
      </thead>
        <tbody id="body">
          <tr>
            <td colspan="8" style="text-align: center; padding: 40px;">
              <div style="font-size: 16px; color: var(--gray-500);">⏳ Loading ultra-fast data...</div>
            </td>
          </tr>
        </tbody>
    </table>
    </div>
  </div>

<script>
  window.API_BASE = "/olt/api/";
  window.PONS = <?= json_encode(array_values($ponList)) ?>;
  
  console.log('Ultra-fast page loaded');
  console.log('API_BASE:', window.API_BASE);
  console.log('PONS:', window.PONS);

  // Realtime loading using database cache + quick live refresh
  (function(){
    'use strict';

    var API_BASE = window.API_BASE || '/api/';
    var PONS = window.PONS || [1,2,3,4,5,6,7,8];
    
    if (!API_BASE.endsWith('/')) API_BASE += '/';
    
    var tbody = document.getElementById('body');
    var snapEl = document.getElementById('snap');
    var loadTimeEl = document.getElementById('load-time');
    var refreshNotice = document.getElementById('refresh-notice');
    var refreshMessage = document.getElementById('refresh-message');
    var dataAgeEl = document.getElementById('data-age');
    var liveRefreshBtn = document.getElementById('live-refresh-btn');
    var liveRefreshBtnToolbar = document.getElementById('live-refresh-btn-toolbar');
    var realtimeMasterCheckbox = document.getElementById('realtime-master-checkbox');
    var specificOnuInput = document.getElementById('specific-onu-input');
    var specificOnuBtn = document.getElementById('specific-onu-btn');
    var specificOnuStopBtn = document.getElementById('specific-onu-stop-btn');
    
    var totalPonsEl = document.getElementById('total-pons');
    var totalOnusEl = document.getElementById('total-onus');
    var onlineOnusEl = document.getElementById('online-onus');
    var offlineOnusEl = document.getElementById('offline-onus');
    var netTotalNowEl = document.getElementById('net-total-now');
    var netUpNowEl = document.getElementById('net-up-now');
    var netDownNowEl = document.getElementById('net-down-now');
    var netUsage24hEl = document.getElementById('net-usage-24h');
    var netPeak24hEl = document.getElementById('net-peak-24h');
    var netPeak24hTimeEl = document.getElementById('net-peak-24h-time');
    var topUsersNowEl = document.getElementById('top-users-now');
    var topUsersUsageEl = document.getElementById('top-users-usage');
    var count = document.getElementById('count');
    
    if (snapEl) snapEl.textContent = new Date().toISOString().replace('T',' ').slice(0,19);

    var allRows = [];
    var currentFilter = 'all';
    var liveRefreshing = false;
    var dashboardPollInterval = null;
    var autoLiveInterval = null;
    var speedPollInterval = null;
    var fastSampleInterval = null;
    var specificPollInterval = null;
    var specificOnuMode = null;
    var metricsByOnu = {};
    var networkMetrics = null;
    var fastSpeedByOnu = {};
    var expandedRowKeys = new Set();
    var REALTIME_UI_MS = 1000;       // table + speed repaint cadence
    var DASHBOARD_POLL_MS = 6000;    // full table re-query cadence
    var REALTIME_OLT_MS = 5000;      // pull fresh OLT data cadence (quick mode)
    var latestLiveTs = null;
    var latestCacheAgeSec = null;

    function escapeHtml(text) {
      if (text == null) return '';
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function normalizeDesc(text) {
      if (text == null) return '';
      return text.toLowerCase().replace(/\u00a0/g, ' ').replace(/[_\-]+/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function getRxClass(value) {
      var numValue = parseFloat(value);
      if (isNaN(numValue)) return 'dim';
      if (numValue <= -28) return 'bad';
      if (numValue <= -23) return 'warn';
      if (numValue <= -8) return 'good';
      return 'warn';
    }

    function updateDisplay() {
      var search = document.getElementById('search');
      var query = normalizeDesc(search ? search.value : '');
      var shown = 0;
      
      for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        var descMatch = (!query || tr.dataset.desc.indexOf(query) !== -1);
        var statusMatch = (currentFilter === 'all' || 
                          (currentFilter === 'online' && tr.dataset.status.indexOf('online') !== -1) ||
                          (currentFilter === 'offline' && tr.dataset.status.indexOf('offline') !== -1));
        
        var visible = descMatch && statusMatch;
        tr.style.display = visible ? '' : 'none';
        if (visible) shown++;
      }
      
      if (count) count.textContent = shown + ' shown';
    }

    var searchInput = document.getElementById('search');
    if (searchInput) searchInput.addEventListener('input', updateDisplay);

    var filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        updateDisplay();
      });
    });

    function normalizeOnuId(v) {
      if (v == null) return '';
      return String(v).toUpperCase().replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function fmtBytes(n) {
      n = Number(n || 0);
      if (!isFinite(n) || n <= 0) return '0 B';
      var u = ['B', 'KB', 'MB', 'GB', 'TB'];
      var i = Math.min(u.length - 1, Math.floor(Math.log(n) / Math.log(1024)));
      var v = n / Math.pow(1024, i);
      return v.toFixed(i > 1 ? 2 : 0) + ' ' + u[i];
    }

    function fmtTs(ts) {
      if (!ts) return '—';
      var d = new Date(Number(ts) * 1000);
      if (isNaN(d.getTime())) return '—';
      return d.toLocaleString();
    }

    function formatAgeText(sec) {
      sec = Math.max(0, Math.floor(Number(sec || 0)));
      var m = Math.floor(sec / 60);
      var s = sec % 60;
      return m > 0 ? (m + 'm ' + s + 's') : (s + 's');
    }

    function updateFreshnessUI() {
      if (!refreshNotice || !refreshMessage) return;
      var nowTs = Math.floor(Date.now() / 1000);
      var liveAge = latestLiveTs ? Math.max(0, nowTs - Number(latestLiveTs)) : null;
      var cacheAge = (latestCacheAgeSec == null ? null : Number(latestCacheAgeSec));

      var parts = [];
      if (liveAge != null) parts.push('⚡ Live traffic age: ' + formatAgeText(liveAge));
      if (cacheAge != null) parts.push('🗂 ONU cache age: ' + formatAgeText(cacheAge));
      if (!parts.length) return;

      refreshNotice.style.display = 'flex';
      refreshMessage.innerHTML = parts.join(' · ');
      refreshNotice.className = (liveAge != null && liveAge > 20) ? 'refresh-notice stale' : 'refresh-notice';
      if (dataAgeEl && liveAge != null) dataAgeEl.textContent = formatAgeText(liveAge);
    }

    function renderTopList(targetEl, rows, valueLabelFn) {
      if (!targetEl) return;
      if (!rows || !rows.length) {
        targetEl.innerHTML = '<li>No data</li>';
        return;
      }
      targetEl.innerHTML = rows.map(function(item) {
        return '<li><span class="mono">' + escapeHtml(item.onuid) + '</span> — ' + escapeHtml(valueLabelFn(item)) + '</li>';
      }).join('');
    }

    function animateNumber(el, target) {
      if (!el) return;
      var from = parseFloat(el.dataset.val || '0') || 0;
      var to = (isFinite(target) ? Number(target) : 0);
      var start = performance.now();
      var dur = 650;
      function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }
      function frame(now) {
        var p = Math.min(1, (now - start) / dur);
        var v = from + (to - from) * easeOutExpo(p);
        el.dataset.val = String(v);
        el.textContent = v.toFixed(2);
        if (p < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    }

    function animateMbpsText(el, target) {
      if (!el) return;
      var raw = String(el.dataset.rawVal || el.textContent || '0').replace(/[^\d.\-]/g, '');
      var from = parseFloat(raw) || 0;
      var to = (isFinite(target) ? Number(target) : 0);
      var start = performance.now();
      var dur = 650;
      function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }
      function frame(now) {
        var p = Math.min(1, (now - start) / dur);
        var v = from + (to - from) * easeOutExpo(p);
        el.dataset.rawVal = String(v);
        el.textContent = v.toFixed(2) + ' Mbps';
        if (p < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    }

    function speedCellHtml(onuidNorm) {
      var rec = metricsByOnu[onuidNorm];
      if (!rec) return '<span class="dim">—</span>';

      var fast = fastSpeedByOnu[onuidNorm] || {};
      var total = Number((fast.total_mbps != null ? fast.total_mbps : rec.total_mbps) || 0);
      var up = Number((fast.upload_mbps != null ? fast.upload_mbps : rec.upload_mbps) || 0);
      var down = Number((fast.download_mbps != null ? fast.download_mbps : rec.download_mbps) || 0);
      return '<div><span class="speed-pill"><span class="spd-total" data-val="' + total + '">' + total.toFixed(2) + '</span> Mbps</span></div>' +
             '<div class="speed-sub">U <span class="spd-up" data-val="' + up + '">' + up.toFixed(2) + '</span> · D <span class="spd-down" data-val="' + down + '">' + down.toFixed(2) + '</span></div>';
    }

    function applySpeedToRows() {
      for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        var onuidNorm = tr.dataset.onuidNorm || '';
        var speedTd = tr.querySelector('.speed-cell');
        if (speedTd) speedTd.innerHTML = speedCellHtml(onuidNorm);

        var detail = tr.__detailRow;
        if (!detail) continue;
        var rec = metricsByOnu[onuidNorm];
        if (!rec) {
          detail.innerHTML = '<td colspan="8" style="background:#f8fafc;padding:10px 12px;"><strong>Live details</strong>: no recent sample.</td>';
          continue;
        }

        var up = Number(rec.upload_mbps || 0);
        var down = Number(rec.download_mbps || 0);
        var total = Number(rec.total_mbps || 0);
        var top = Number(rec.top_total_mbps || 0);
        var usageUp = Number(rec.usage_24h_upload_bytes || 0);
        var usageDown = Number(rec.usage_24h_download_bytes || 0);
        var usageTotal = Number(rec.usage_24h_total_bytes || 0);
        var liveAt = fmtTs(rec.at_ts);
        var topAt = fmtTs(rec.top_at_ts);

        detail.innerHTML =
          '<td colspan="8" style="background:#f8fafc;padding:10px 12px;">' +
            '<strong>Live details</strong> &nbsp;|&nbsp; ' +
            '<strong>Now:</strong> ' + total.toFixed(2) + ' Mbps (U ' + up.toFixed(2) + ' / D ' + down.toFixed(2) + ') at ' + escapeHtml(liveAt) +
            ' &nbsp;|&nbsp; <strong>Top (24h):</strong> ' + top.toFixed(2) + ' Mbps at ' + escapeHtml(topAt) +
            ' &nbsp;|&nbsp; <strong>Usage (24h):</strong> ' + escapeHtml(fmtBytes(usageTotal)) +
            ' (U ' + escapeHtml(fmtBytes(usageUp)) + ' / D ' + escapeHtml(fmtBytes(usageDown)) + ')' +
          '</td>';
      }
    }

    function applyFastSpeedToRows() {
      var sumUp = 0;
      var sumDown = 0;
      for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        var onuidNorm = tr.dataset.onuidNorm || '';
        var rec = fastSpeedByOnu[onuidNorm];
        if (!rec) continue;

        var total = Number(rec.total_mbps || 0);
        var up = Number(rec.upload_mbps || 0);
        var down = Number(rec.download_mbps || 0);

        var totalEl = tr.querySelector('.spd-total');
        var upEl = tr.querySelector('.spd-up');
        var downEl = tr.querySelector('.spd-down');
        animateNumber(totalEl, total);
        animateNumber(upEl, up);
        animateNumber(downEl, down);

        sumUp += up;
        sumDown += down;
      }

      animateMbpsText(netUpNowEl, sumUp);
      animateMbpsText(netDownNowEl, sumDown);
      animateMbpsText(netTotalNowEl, sumUp + sumDown);
    }

    function refreshFastSpeeds() {
      if (specificOnuMode) return Promise.resolve();
      return fetch('/b/api/online_now_all.php', { cache: 'no-store' })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
          if (!data || !data.ok || !data.rows) return;
          var next = {};
          for (var k in data.rows) {
            if (!Object.prototype.hasOwnProperty.call(data.rows, k)) continue;
            next[normalizeOnuId(k)] = data.rows[k];
          }
          fastSpeedByOnu = next;
          if (data.ts_curr) latestLiveTs = Number(data.ts_curr);
          applyFastSpeedToRows();
          updateFreshnessUI();
        })
        .catch(function() {});
    }

    function getRowByOnuId(onuidNorm) {
      for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        if ((tr.dataset.onuidNorm || '') === onuidNorm) return tr;
      }
      return null;
    }

    function refreshSpecificOnuSpeed(onuidNorm) {
      if (!onuidNorm) return Promise.resolve();
      return fetch('/b/api/online_now_single.php?onuid=' + encodeURIComponent(onuidNorm), { cache: 'no-store' })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
          if (!data || !data.ok || !data.has_data) return;
          var rec = {
            upload_mbps: Number(data.upload_mbps || 0),
            download_mbps: Number(data.download_mbps || 0),
            total_mbps: Number(data.total_mbps || 0),
            online: Number(data.total_mbps || 0) > 0
          };
          fastSpeedByOnu[onuidNorm] = rec;
          if (data.ts_curr) latestLiveTs = Number(data.ts_curr);

          var tr = getRowByOnuId(onuidNorm);
          if (tr) {
            var totalEl = tr.querySelector('.spd-total');
            var upEl = tr.querySelector('.spd-up');
            var downEl = tr.querySelector('.spd-down');
            animateNumber(totalEl, rec.total_mbps);
            animateNumber(upEl, rec.upload_mbps);
            animateNumber(downEl, rec.download_mbps);
          }
          updateFreshnessUI();
        })
        .catch(function() {});
    }

    function triggerFastSample() {
      if (specificOnuMode) return Promise.resolve();
      return fetch('/b/api/network_sample.php', { cache: 'no-store' }).catch(function() {});
    }

    function refreshMetricsNow() {
      return fetch(API_BASE + 'user_metrics.php', { cache: 'no-store' })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
          if (!data || !data.ok || !data.users) return;
          var next = {};
          for (var k in data.users) {
            if (!Object.prototype.hasOwnProperty.call(data.users, k)) continue;
            next[normalizeOnuId(k)] = data.users[k];
          }
          metricsByOnu = next;
          networkMetrics = data.network || null;
          if (networkMetrics && networkMetrics.current) {
            if (netTotalNowEl) netTotalNowEl.textContent = Number(networkMetrics.current.total_mbps || 0).toFixed(2) + ' Mbps';
            if (netUpNowEl) netUpNowEl.textContent = Number(networkMetrics.current.upload_mbps || 0).toFixed(2) + ' Mbps';
            if (netDownNowEl) netDownNowEl.textContent = Number(networkMetrics.current.download_mbps || 0).toFixed(2) + ' Mbps';
            if (networkMetrics.current.at_ts) latestLiveTs = Number(networkMetrics.current.at_ts);
          }
          if (networkMetrics && networkMetrics.usage_24h && netUsage24hEl) {
            netUsage24hEl.textContent = fmtBytes(networkMetrics.usage_24h.total_bytes || 0);
          }
          if (networkMetrics && networkMetrics.peak_24h) {
            if (netPeak24hEl) netPeak24hEl.textContent = Number(networkMetrics.peak_24h.total_mbps || 0).toFixed(2) + ' Mbps';
            if (netPeak24hTimeEl) netPeak24hTimeEl.textContent = 'at ' + fmtTs(networkMetrics.peak_24h.at_ts);
          }

          var topNow = [];
          var topUsage = [];
          for (var id in next) {
            if (!Object.prototype.hasOwnProperty.call(next, id)) continue;
            var rec = next[id] || {};
            topNow.push({
              onuid: id,
              total_mbps: Number(rec.total_mbps || 0)
            });
            topUsage.push({
              onuid: id,
              total_bytes: Number(rec.usage_24h_total_bytes || 0)
            });
          }
          topNow.sort(function(a, b) { return b.total_mbps - a.total_mbps; });
          topUsage.sort(function(a, b) { return b.total_bytes - a.total_bytes; });

          renderTopList(topUsersNowEl, topNow.slice(0, 6), function(item) {
            return item.total_mbps.toFixed(2) + ' Mbps';
          });
          renderTopList(topUsersUsageEl, topUsage.slice(0, 6), function(item) {
            return fmtBytes(item.total_bytes);
          });

          updateFreshnessUI();
          applySpeedToRows();
        })
        .catch(function() {});
    }

    function renderDashboard(result) {
      if (!result || !result.ok) {
        throw new Error((result && result.error) || 'Failed to load data');
      }

      if (totalPonsEl) totalPonsEl.textContent = PONS.length;
      if (totalOnusEl) totalOnusEl.textContent = result.stats.total_onus;
      if (onlineOnusEl) onlineOnusEl.textContent = result.stats.online_onus;
      if (offlineOnusEl) offlineOnusEl.textContent = result.stats.offline_onus;

      latestCacheAgeSec = result.data_age_seconds;
      updateFreshnessUI();

      var frag = document.createDocumentFragment();
      allRows = [];

      for (var pon in result.data) {
        if (!Object.prototype.hasOwnProperty.call(result.data, pon)) continue;
        var ponData = result.data[pon];
        for (var i = 0; i < ponData.length; i++) {
          var row = ponData[i];
          var tr = document.createElement('tr');
          tr.className = 'pon-' + row.pon;
          tr.dataset.rowKey = String(row.pon) + '-' + String(row.onu);
          tr.dataset.pon = row.pon;
          tr.dataset.onu = row.onu;
          tr.dataset.onuidNorm = normalizeOnuId(row.onuid || row.onuid_norm || '');
          tr.dataset.desc = normalizeDesc(row.desc || '');
          tr.dataset.status = normalizeDesc(row.status || '');

          var statusOk = /online/i.test(row.status || '');
          var statusClass = statusOk ? 'online' : 'offline';
          var statusIcon = statusOk ? '✅' : '❌';

          var wanHtml = '<span class="dim">N/A</span>';
          if (statusOk) {
            if (row.wan && row.wan !== 'N/A' && row.wan !== null) {
              if (/connect/i.test(row.wan)) {
                wanHtml = '<span class="status-badge online">✅ ' + escapeHtml(row.wan) + '</span>';
              } else if (/unknown/i.test(row.wan)) {
                wanHtml = '<span class="status-badge" style="background: rgba(234, 179, 8, 0.1); color: var(--warning); border: 1px solid rgba(234, 179, 8, 0.2);">⚠️ ' + escapeHtml(row.wan) + '</span>';
              } else {
                wanHtml = '<span class="status-badge offline">❌ ' + escapeHtml(row.wan) + '</span>';
              }
            } else {
              wanHtml = '<span class="spinner-text dim"><span class="spinner"></span> Pending...</span>';
            }
          } else {
            wanHtml = '<span class="dim" title="ONU is offline - no WAN data available">—</span>';
          }

          var rxHtml = '<span class="dim">N/A</span>';
          if (statusOk) {
            if (row.rx !== null) {
              var rxClass = getRxClass(row.rx);
              rxHtml = '<span class="rx-value ' + rxClass + '">' + parseFloat(row.rx).toFixed(2) + ' dBm</span>';
            } else {
              rxHtml = '<span class="spinner-text dim"><span class="spinner"></span> Pending...</span>';
            }
          } else {
            rxHtml = '<span class="dim" title="ONU is offline - no optical data">—</span>';
          }

          var avgHtml = '<span class="dim">N/A</span>';
          if (row.rx_avg_24h !== null) avgHtml = parseFloat(row.rx_avg_24h).toFixed(2);

          var deltaHtml = '<span style="color: var(--gray-400);">—</span>';
          if (row.rx_delta !== null) {
            var deltaAbs = Math.abs(row.rx_delta);
            var deltaClass = deltaAbs >= 2 ? 'color: var(--danger); font-weight: 700;' :
                            (deltaAbs >= 1 ? 'color: var(--warning); font-weight: 600;' : 'color: var(--gray-600);');
            var deltaIcon = deltaAbs >= 1 ? ' ⚠️' : '';
            deltaHtml = '<span style="' + deltaClass + '">' +
                       (row.rx_delta >= 0 ? '+' : '') + row.rx_delta.toFixed(2) + ' dB' + deltaIcon + '</span>';
          }

          var descTooltip = '';
          if (row.wan_username || row.wan_mac) {
            var tooltipParts = [];
            if (row.wan_username) tooltipParts.push('Username: ' + row.wan_username);
            if (row.wan_mac) tooltipParts.push('MAC: ' + row.wan_mac);
            descTooltip = ' title="' + escapeHtml(tooltipParts.join(' | ')) + '"';
          }

          var html = '';
          html += '<td class="mono" title="' + escapeHtml(row.onuid) + '">' + escapeHtml(row.onuid) + '</td>';
          html += '<td' + descTooltip + '><strong>' + escapeHtml(row.desc) + '</strong>';
          if (row.wan_username || row.wan_mac) {
            html += '<div style="font-size: 11px; color: var(--gray-500); margin-top: 2px;">';
            if (row.wan_username) html += '👤 ' + escapeHtml(row.wan_username);
            if (row.wan_username && row.wan_mac) html += ' · ';
            if (row.wan_mac) html += '🔗 ' + escapeHtml(row.wan_mac);
            html += '</div>';
          }
          html += '</td>';
          html += '<td><span class="status-badge ' + statusClass + '">' + statusIcon + ' ' + escapeHtml(row.status) + '</span></td>';
          html += '<td>' + wanHtml + '</td>';
          html += '<td class="speed-cell">' + speedCellHtml(tr.dataset.onuidNorm) + '</td>';
          html += '<td>' + rxHtml + '</td>';
          html += '<td>' + avgHtml + '</td>';
          html += '<td>' + deltaHtml + '</td>';

          tr.innerHTML = html;
          frag.appendChild(tr);
          allRows.push(tr);

          var detailTr = document.createElement('tr');
          detailTr.className = 'detail-row';
          detailTr.style.display = 'none';
          detailTr.innerHTML = '<td colspan="8" style="background:#f8fafc;padding:10px 12px;">' +
            '<strong>Live details</strong>: loading…' +
            '</td>';
          tr.__detailRow = detailTr;
          frag.appendChild(detailTr);
        }
      }

      tbody.innerHTML = '';
      tbody.appendChild(frag);
      for (var z = 0; z < allRows.length; z++) {
        (function(mainTr) {
          mainTr.style.cursor = 'pointer';
          mainTr.addEventListener('click', function() {
            var drow = mainTr.__detailRow;
            if (!drow) return;
            var opening = drow.style.display === 'none';
            drow.style.display = opening ? '' : 'none';
            var key = mainTr.dataset.rowKey || '';
            if (!key) return;
            if (opening) expandedRowKeys.add(key);
            else expandedRowKeys.delete(key);
          });

          var key = mainTr.dataset.rowKey || '';
          if (key && expandedRowKeys.has(key) && mainTr.__detailRow) {
            mainTr.__detailRow.style.display = '';
          }
        })(allRows[z]);
      }
      updateDisplay();
      applySpeedToRows();
    }

    function loadDashboard(silent) {
      var startTime = Date.now();
      return fetch(API_BASE + 'dashboard.php?pons=' + encodeURIComponent(PONS.join(',')), {cache: 'no-store'})
        .then(function(response) { return response.json(); })
        .then(function(result) {
          var loadTime = (Date.now() - startTime) / 1000;
          if (loadTimeEl) loadTimeEl.textContent = loadTime.toFixed(2);
          renderDashboard(result);
          if (!silent) console.log('Dashboard loaded in', loadTime.toFixed(2), 'seconds');
        })
        .catch(function(error) {
          if (silent) return;
          console.error('Error loading data:', error);
          tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--danger);">❌ Error: ' +
                           escapeHtml(error.message) + '</td></tr>';
        });
    }
    
    // Live Refresh Functionality
    function setLiveButtonsBusy(busy) {
      if (liveRefreshBtn) {
        liveRefreshBtn.disabled = !!busy;
        liveRefreshBtn.innerHTML = busy ? '<span class="spinner-text"><span class="spinner"></span> Fetching...</span>' : '🔄 Fetch Live Data';
      }
      if (liveRefreshBtnToolbar) {
        liveRefreshBtnToolbar.disabled = !!busy;
        liveRefreshBtnToolbar.innerHTML = busy ? '<span class="spinner-text"><span class="spinner"></span> Fetching...</span>' : '🔄 Fetch Live';
      }
    }

    function fetchLiveData(mode) {
      if (liveRefreshing) return Promise.resolve();
      liveRefreshing = true;
      mode = mode || 'quick';
      console.log('Fetching live data from OLT... mode=' + mode);
      setLiveButtonsBusy(true);
      
      if (refreshNotice) {
        refreshNotice.style.display = 'flex';
        refreshMessage.innerHTML = '<span class="spinner-text"><span class="spinner"></span> Fetching live data (' + mode + ')...</span>';
      }
      
      return fetch(API_BASE + 'refresh.php?pons=' + encodeURIComponent(PONS.join(',')) + '&mode=' + encodeURIComponent(mode), {cache: 'no-store'})
        .then(function(response) {
          return response.json();
        })
        .then(function(result) {
          if (result && result.busy) {
            refreshMessage.innerHTML = '⏳ Another refresh is already running. Waiting for latest data...';
            return;
          }
          if (result.ok) {
            var wanInfo = '';
            if (result.wan_total) {
              wanInfo = ' | WAN: ' + result.wan_updated + '/' + result.wan_total;
              if (result.wan_failed > 0) wanInfo += ' (' + result.wan_failed + ' failed)';
            }
            if (refreshMessage) {
              refreshMessage.innerHTML = '✅ Live data refreshed (' + (result.mode || mode) + '): ' +
                result.onus_updated + ' ONUs' + wanInfo + ' in ' + result.refresh_time + 's';
            }
            return;
          } else {
            throw new Error(result.error || 'Refresh failed');
          }
        })
        .catch(function(error) {
          console.error('Live refresh error:', error);
          if (refreshMessage) {
            refreshMessage.innerHTML = '❌ Failed to fetch live data: ' + escapeHtml(error.message);
          }
        })
        .finally(function() {
          liveRefreshing = false;
          setLiveButtonsBusy(false);
        });
    }
    
    // Attach live refresh handlers
    if (liveRefreshBtn) {
      liveRefreshBtn.addEventListener('click', fetchLiveData);
    }
    if (liveRefreshBtnToolbar) {
      liveRefreshBtnToolbar.addEventListener('click', fetchLiveData);
    }
    
    // Keyboard shortcut: Ctrl+R or F5 for quick live fetch
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey && e.key === 'r') || e.key === 'F5') {
        e.preventDefault();
        fetchLiveData('quick');
      }
    });
    
    // Poll dashboard every 5s for near realtime UI without heavy OLT calls
    function startDashboardPolling() {
      if (dashboardPollInterval) return;
      dashboardPollInterval = setInterval(function() {
        if (!liveRefreshing) loadDashboard(true);
      }, DASHBOARD_POLL_MS);
    }

    function stopDashboardPolling() {
      if (dashboardPollInterval) {
        clearInterval(dashboardPollInterval);
        dashboardPollInterval = null;
      }
    }

    function startSpeedPolling() {
      if (speedPollInterval) return;
      speedPollInterval = setInterval(function() {
        refreshFastSpeeds();
      }, REALTIME_UI_MS);
    }

    function stopSpeedPolling() {
      if (speedPollInterval) {
        clearInterval(speedPollInterval);
        speedPollInterval = null;
      }
    }

    function startFastSampling() {
      if (fastSampleInterval) return;
      fastSampleInterval = setInterval(function() {
        triggerFastSample();
      }, 3000);
    }

    function stopFastSampling() {
      if (fastSampleInterval) {
        clearInterval(fastSampleInterval);
        fastSampleInterval = null;
      }
    }

    // Auto live functionality
    var autoRefreshCheckbox = document.getElementById('auto-refresh-checkbox');
    var autoLiveEnabled = false;

    function stopAutoLive() {
      if (autoLiveInterval) {
        clearInterval(autoLiveInterval);
        autoLiveInterval = null;
      }
    }

    function stopSpecificMode() {
      specificOnuMode = null;
      if (specificPollInterval) {
        clearInterval(specificPollInterval);
        specificPollInterval = null;
      }
      for (var i = 0; i < allRows.length; i++) {
        allRows[i].style.outline = '';
      }
      if (specificOnuInput) specificOnuInput.value = '';
      if (refreshMessage) refreshMessage.innerHTML = '🎯 Specific ONU mode stopped';
    }

    function startSpecificMode(onuidRaw) {
      var onuidNorm = normalizeOnuId(onuidRaw);
      if (!onuidNorm) return;
      var tr = getRowByOnuId(onuidNorm);
      if (!tr) {
        if (refreshMessage) refreshMessage.innerHTML = '❌ ONU not found in table: ' + escapeHtml(onuidNorm);
        return;
      }

      // Stop global load-heavy loops.
      stopAutoLive();
      stopFastSampling();
      stopSpeedPolling();
      stopDashboardPolling();

      // Clear previous highlight and set new one.
      for (var i = 0; i < allRows.length; i++) allRows[i].style.outline = '';
      tr.style.outline = '2px solid #0ea5e9';
      tr.style.outlineOffset = '-2px';

      specificOnuMode = onuidNorm;
      if (refreshMessage) refreshMessage.innerHTML = '🎯 Specific realtime active for ' + escapeHtml(onuidNorm) + ' (1s)';

      if (specificPollInterval) clearInterval(specificPollInterval);
      refreshSpecificOnuSpeed(onuidNorm);
      specificPollInterval = setInterval(function() {
        refreshSpecificOnuSpeed(onuidNorm);
      }, 1000);
    }

    function setGlobalRealtimeEnabled(enabled) {
      if (!enabled) {
        stopAutoLive();
        stopFastSampling();
        stopSpeedPolling();
        stopDashboardPolling();
        if (refreshMessage) refreshMessage.innerHTML = '⏸ Global realtime OFF. Use Specific ONU mode for targeted checks.';
        return;
      }
      if (specificOnuMode) return;
      startDashboardPolling();
      startSpeedPolling();
      startFastSampling();
      if (autoRefreshCheckbox && autoRefreshCheckbox.checked) startAutoLive();
      if (refreshMessage) refreshMessage.innerHTML = '🟢 Global realtime ON';
    }

    function startAutoLive() {
      if (autoLiveInterval) return;
      autoLiveInterval = setInterval(function() {
        if (!liveRefreshing) fetchLiveData('quick');
      }, REALTIME_OLT_MS);
      console.log('Auto live started (every ' + (REALTIME_OLT_MS / 1000) + 's)');
    }

    if (autoRefreshCheckbox) {
      var saved = localStorage.getItem('autoLive');
      autoLiveEnabled = (saved === null) ? true : (saved === 'true');
      autoRefreshCheckbox.checked = autoLiveEnabled;
      if (autoLiveEnabled) startAutoLive();

      autoRefreshCheckbox.addEventListener('change', function() {
        if (this.checked) {
          localStorage.setItem('autoLive', 'true');
          startAutoLive();
          if (refreshMessage) {
            refreshMessage.innerHTML = '🟢 Realtime ON: UI ' + (REALTIME_UI_MS / 1000) + 's, OLT pull ' + (REALTIME_OLT_MS / 1000) + 's';
          }
        } else {
          localStorage.setItem('autoLive', 'false');
          stopAutoLive();
          if (refreshMessage) {
            refreshMessage.innerHTML = '🟡 Realtime OFF';
          }
        }
      });
    }

    if (realtimeMasterCheckbox) {
      var savedGlobalRealtime = localStorage.getItem('globalRealtime');
      var globalRealtimeEnabled = (savedGlobalRealtime === null) ? true : (savedGlobalRealtime === 'true');
      realtimeMasterCheckbox.checked = globalRealtimeEnabled;
      realtimeMasterCheckbox.addEventListener('change', function() {
        localStorage.setItem('globalRealtime', this.checked ? 'true' : 'false');
        if (!this.checked && specificOnuMode) stopSpecificMode();
        setGlobalRealtimeEnabled(this.checked);
      });
    }

    if (specificOnuBtn) {
      specificOnuBtn.addEventListener('click', function() {
        var value = specificOnuInput ? specificOnuInput.value : '';
        startSpecificMode(value);
      });
    }
    if (specificOnuInput) {
      specificOnuInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          startSpecificMode(specificOnuInput.value || '');
        }
      });
    }
    if (specificOnuStopBtn) {
      specificOnuStopBtn.addEventListener('click', function() {
        stopSpecificMode();
        var globalEnabled = realtimeMasterCheckbox ? realtimeMasterCheckbox.checked : true;
        setGlobalRealtimeEnabled(globalEnabled);
      });
    }

    loadDashboard(false).then(function() {
      refreshMetricsNow();
      refreshFastSpeeds();
      triggerFastSample();
      var globalEnabled = realtimeMasterCheckbox ? realtimeMasterCheckbox.checked : true;
      setGlobalRealtimeEnabled(globalEnabled);
      setInterval(updateFreshnessUI, 1000);
      // First quick live fetch after initial render to reduce stale gap.
      if (globalEnabled) return fetchLiveData('quick');
      return Promise.resolve();
    });
  })();
</script>
</body>
</html>
