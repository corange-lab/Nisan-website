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
      <div style="margin-left: auto; font-size: 13px; color: var(--gray-500);">
        <span id="count">0 shown</span>
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
        <input type="checkbox" id="auto-refresh-checkbox" style="width: 16px; height: 16px; cursor: pointer;">
        Auto (5m)
      </label>
  </div>

    <div class="table-container">
    <table id="tbl">
      <thead>
        <tr>
            <th>PON</th>
            <th>ONU</th>
            <th>ONU ID</th>
            <th>Description</th>
            <th>Model</th>
            <th>Status</th>
            <th>WAN Status</th>
            <th>RX Power</th>
            <th>24h Avg</th>
            <th>Δ vs 24h</th>
        </tr>
      </thead>
        <tbody id="body">
          <tr>
            <td colspan="10" style="text-align: center; padding: 40px;">
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

  // Ultra-fast loading using database
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
    
    var totalPonsEl = document.getElementById('total-pons');
    var totalOnusEl = document.getElementById('total-onus');
    var onlineOnusEl = document.getElementById('online-onus');
    var offlineOnusEl = document.getElementById('offline-onus');
    var count = document.getElementById('count');
    
    if (snapEl) snapEl.textContent = new Date().toISOString().replace('T',' ').slice(0,19);

    var allRows = [];
    var currentFilter = 'all';

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

    // Load data from database API (ULTRA FAST!)
    var startTime = Date.now();
    
    fetch(API_BASE + 'dashboard.php?pons=' + encodeURIComponent(PONS.join(',')))
      .then(function(response) {
        return response.json();
      })
      .then(function(result) {
        var loadTime = (Date.now() - startTime) / 1000;
        console.log('Data loaded in', loadTime, 'seconds');
        
        if (loadTimeEl) loadTimeEl.textContent = loadTime.toFixed(2);
        
        if (!result.ok) {
          throw new Error(result.error || 'Failed to load data');
        }
        
        // Update stats
        if (totalPonsEl) totalPonsEl.textContent = PONS.length;
        if (totalOnusEl) totalOnusEl.textContent = result.stats.total_onus;
        if (onlineOnusEl) onlineOnusEl.textContent = result.stats.online_onus;
        if (offlineOnusEl) offlineOnusEl.textContent = result.stats.offline_onus;
        
        // Show data age notice
        if (result.data_age_seconds !== null) {
          var minutes = Math.floor(result.data_age_seconds / 60);
          var seconds = result.data_age_seconds % 60;
          var ageText = minutes > 0 ? minutes + 'm ' + Math.floor(seconds) + 's' : Math.floor(seconds) + 's';
          
          if (dataAgeEl) dataAgeEl.textContent = ageText + ' old';
          
          if (result.is_stale) {
            refreshNotice.className = 'refresh-notice stale';
            refreshMessage.innerHTML = '⚠️ Data is stale (' + ageText + ' old) - <strong>Please refresh!</strong>';
          } else {
            refreshNotice.className = 'refresh-notice';
            refreshMessage.innerHTML = '📊 Data age: ' + ageText;
          }
          
          refreshNotice.style.display = 'flex';
        }
        
        // Clear loading message
        tbody.innerHTML = '';
        
        // Add all rows
        for (var pon in result.data) {
          var ponData = result.data[pon];
          
          for (var i = 0; i < ponData.length; i++) {
            var row = ponData[i];
            
            var key = row.pon + '-' + row.onu;
            var tr = document.createElement('tr');
            tr.className = 'pon-' + row.pon;
            tr.dataset.pon = row.pon;
            tr.dataset.onu = row.onu;
            tr.dataset.desc = normalizeDesc(row.desc || '');
            tr.dataset.status = normalizeDesc(row.status || '');
            
            var statusOk = /online/i.test(row.status || '');
            var statusClass = statusOk ? 'online' : 'offline';
            var statusIcon = statusOk ? '✅' : '❌';
            
            var wanHtml = '<span class="dim">N/A</span>';
            if (statusOk) {
              if (row.wan && row.wan !== 'N/A' && row.wan !== null) {
                var isConnected = /connect/i.test(row.wan);
                var isUnknown = /unknown/i.test(row.wan);
                
                if (isConnected) {
                  wanHtml = '<span class="status-badge online">✅ ' + escapeHtml(row.wan) + '</span>';
                } else if (isUnknown) {
                  wanHtml = '<span class="status-badge" style="background: rgba(234, 179, 8, 0.1); color: var(--warning); border: 1px solid rgba(234, 179, 8, 0.2);">⚠️ ' + escapeHtml(row.wan) + '</span>';
                } else {
                  wanHtml = '<span class="status-badge offline">❌ ' + escapeHtml(row.wan) + '</span>';
                }
              } else {
                // Online but WAN not fetched yet - show loading spinner
                wanHtml = '<span class="spinner-text dim"><span class="spinner"></span> Loading...</span>';
              }
            } else {
              // Offline - no WAN to check
              wanHtml = '<span class="dim" title="ONU is offline - no WAN data available">—</span>';
            }
            
            var rxHtml = '<span class="dim">N/A</span>';
            if (statusOk) {
              if (row.rx !== null) {
                var rxClass = getRxClass(row.rx);
                rxHtml = '<span class="rx-value ' + rxClass + '">' + 
                        parseFloat(row.rx).toFixed(2) + ' dBm</span>';
              } else {
                // Online but RX not fetched yet - show loading spinner
                rxHtml = '<span class="spinner-text dim"><span class="spinner"></span> Loading...</span>';
              }
            } else {
              // Offline - no RX to check
              rxHtml = '<span class="dim" title="ONU is offline - no optical data">—</span>';
            }
            
            var avgHtml = '<span class="dim">N/A</span>';
            if (row.rx_avg_24h !== null) {
              avgHtml = parseFloat(row.rx_avg_24h).toFixed(2);
            }
            
            var deltaHtml = '<span style="color: var(--gray-400);">—</span>';
            if (row.rx_delta !== null) {
              var deltaAbs = Math.abs(row.rx_delta);
              var deltaClass = deltaAbs >= 2 ? 'color: var(--danger); font-weight: 700;' : 
                              (deltaAbs >= 1 ? 'color: var(--warning); font-weight: 600;' : 'color: var(--gray-600);');
              var deltaIcon = deltaAbs >= 1 ? ' ⚠️' : '';
              deltaHtml = '<span style="' + deltaClass + '">' + 
                         (row.rx_delta >= 0 ? '+' : '') + row.rx_delta.toFixed(2) + ' dB' + deltaIcon + '</span>';
            }
            
            // Build tooltip for description with username and MAC
            var descTooltip = '';
            if (row.wan_username || row.wan_mac) {
              var tooltipParts = [];
              if (row.wan_username) tooltipParts.push('Username: ' + row.wan_username);
              if (row.wan_mac) tooltipParts.push('MAC: ' + row.wan_mac);
              descTooltip = ' title="' + escapeHtml(tooltipParts.join(' | ')) + '"';
            }
            
            var html = '';
            html += '<td><div class="pon-badge pon-' + row.pon + '">' + row.pon + '</div></td>';
            html += '<td class="mono">' + row.onu + '</td>';
            html += '<td class="mono" title="' + escapeHtml(row.onuid) + '">' + escapeHtml(row.onuid) + '</td>';
            html += '<td' + descTooltip + '><strong>' + escapeHtml(row.desc) + '</strong>';
            
            // Show username and MAC inline if available
            if (row.wan_username || row.wan_mac) {
              html += '<div style="font-size: 11px; color: var(--gray-500); margin-top: 2px;">';
              if (row.wan_username) html += '👤 ' + escapeHtml(row.wan_username);
              if (row.wan_username && row.wan_mac) html += ' · ';
              if (row.wan_mac) html += '🔗 ' + escapeHtml(row.wan_mac);
              html += '</div>';
            }
            
            html += '</td>';
            html += '<td>' + escapeHtml(row.model) + '</td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + statusIcon + ' ' + escapeHtml(row.status) + '</span></td>';
            html += '<td>' + wanHtml + '</td>';
            html += '<td>' + rxHtml + '</td>';
            html += '<td>' + avgHtml + '</td>';
            html += '<td>' + deltaHtml + '</td>';
            
            tr.innerHTML = html;
            tbody.appendChild(tr);
            allRows.push(tr);
          }
        }
        
        updateDisplay();
        console.log('Total rows:', allRows.length);
      })
      .catch(function(error) {
        console.error('Error loading data:', error);
        tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px; color: var(--danger);">' +
                         '❌ Error: ' + escapeHtml(error.message) + '</td></tr>';
      });
    
    // Live Refresh Functionality
    function fetchLiveData() {
      console.log('Fetching live data from OLT...');
      
      // Disable buttons during refresh
      if (liveRefreshBtn) {
        liveRefreshBtn.disabled = true;
        liveRefreshBtn.innerHTML = '<span class="spinner-text"><span class="spinner"></span> Fetching...</span>';
      }
      if (liveRefreshBtnToolbar) {
        liveRefreshBtnToolbar.disabled = true;
        liveRefreshBtnToolbar.innerHTML = '<span class="spinner-text"><span class="spinner"></span> Fetching...</span>';
      }
      
      // Show all WAN/RX cells as loading
      for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        var isOnline = /online/i.test(tr.dataset.status);
        
        if (isOnline) {
          // Find WAN and RX cells for this row
          var wanCell = tr.querySelector('[id^="wan-"]');
          var rxCell = tr.querySelector('[id^="rx-"]');
          
          if (wanCell) {
            wanCell.innerHTML = '<span class="spinner-text dim"><span class="spinner"></span> Fetching...</span>';
          }
          if (rxCell) {
            rxCell.innerHTML = '<span class="spinner-text dim"><span class="spinner"></span> Fetching...</span>';
          }
        }
      }
      
      // Show loading notice
      if (refreshNotice) {
        refreshNotice.style.display = 'flex';
        refreshMessage.innerHTML = '<span class="spinner-text"><span class="spinner"></span> Fetching real-time data from OLT device...</span>';
      }
      
      // Trigger live refresh via API
      fetch(API_BASE + 'refresh.php?pons=' + encodeURIComponent(PONS.join(',')))
        .then(function(response) {
          return response.json();
        })
        .then(function(result) {
          console.log('Live refresh result:', result);
          
          if (result.ok) {
            // Show success message with detailed info
            var wanInfo = '';
            if (result.wan_total) {
              wanInfo = ' | WAN: ' + result.wan_updated + '/' + result.wan_total;
              if (result.wan_failed > 0) {
                wanInfo += ' (' + result.wan_failed + ' failed)';
              }
            }
            
            if (refreshMessage) {
              refreshMessage.innerHTML = '✅ Live data refreshed! Updated ' + result.onus_updated + ' ONUs' + wanInfo + ' in ' + result.refresh_time + 's. Reloading page...';
            }
            
            // Reload the page to show fresh data
            setTimeout(function() {
              location.reload();
            }, 1500);
          } else {
            throw new Error(result.error || 'Refresh failed');
          }
        })
        .catch(function(error) {
          console.error('Live refresh error:', error);
          
          if (refreshMessage) {
            refreshMessage.innerHTML = '❌ Failed to fetch live data: ' + escapeHtml(error.message);
          }
          
          // Re-enable buttons
          if (liveRefreshBtn) {
            liveRefreshBtn.disabled = false;
            liveRefreshBtn.textContent = '🔄 Fetch Live Data';
          }
          if (liveRefreshBtnToolbar) {
            liveRefreshBtnToolbar.disabled = false;
            liveRefreshBtnToolbar.textContent = '🔄 Fetch Live Data';
          }
        });
    }
    
    // Attach live refresh handlers
    if (liveRefreshBtn) {
      liveRefreshBtn.addEventListener('click', fetchLiveData);
    }
    if (liveRefreshBtnToolbar) {
      liveRefreshBtnToolbar.addEventListener('click', fetchLiveData);
    }
    
    // Keyboard shortcut: Ctrl+R or F5 to refresh live data
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey && e.key === 'r') || e.key === 'F5') {
        e.preventDefault();
        fetchLiveData();
      }
    });
    
    // Auto-refresh functionality
    var autoRefreshInterval = null;
    var autoRefreshCheckbox = document.getElementById('auto-refresh-checkbox');
    
    if (autoRefreshCheckbox) {
      // Load saved preference
      var autoRefreshEnabled = localStorage.getItem('autoRefresh') === 'true';
      autoRefreshCheckbox.checked = autoRefreshEnabled;
      
      // Start auto-refresh if enabled
      if (autoRefreshEnabled) {
        startAutoRefresh();
      }
      
      autoRefreshCheckbox.addEventListener('change', function() {
        if (this.checked) {
          localStorage.setItem('autoRefresh', 'true');
          startAutoRefresh();
          console.log('Auto-refresh enabled (every 5 minutes)');
        } else {
          localStorage.setItem('autoRefresh', 'false');
          stopAutoRefresh();
          console.log('Auto-refresh disabled');
        }
      });
    }
    
    function startAutoRefresh() {
      if (autoRefreshInterval) return; // Already running
      
      autoRefreshInterval = setInterval(function() {
        console.log('Auto-refresh triggered');
        fetchLiveData();
      }, 5 * 60 * 1000); // 5 minutes
      
      console.log('Auto-refresh started (every 5 minutes)');
    }
    
    function stopAutoRefresh() {
      if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        console.log('Auto-refresh stopped');
      }
    }
    
    // Show refresh notice by default with helpful info
    if (refreshNotice) {
      refreshNotice.style.display = 'flex';
    }
  })();
</script>
</body>
</html>