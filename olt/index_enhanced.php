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
<title>Syrotech OLT — ONU Monitor (Enhanced)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= htmlspecialchars($ASSETS, ENT_QUOTES) ?>styles.css?v=<?= $ver_css ?>">
<style>
/* Enhanced UI Styles */
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

* {
  box-sizing: border-box;
}

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
  padding: 24px 32px;
  position: relative;
  overflow: hidden;
}

.header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
}

.header h1 {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 8px 0;
  position: relative;
  z-index: 1;
}

.header .subtitle {
  font-size: 16px;
  opacity: 0.9;
  margin: 0;
  position: relative;
  z-index: 1;
}

.stats-bar {
  display: flex;
  gap: 24px;
  padding: 20px 32px;
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  flex-wrap: wrap;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: white;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-200);
  min-width: 160px;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 600;
}

.stat-icon.pon { background: var(--primary); color: white; }
.stat-icon.onu { background: var(--success); color: white; }
.stat-icon.online { background: var(--info); color: white; }
.stat-icon.offline { background: var(--danger); color: white; }

.stat-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: var(--gray-900);
}

.stat-content p {
  margin: 0;
  font-size: 14px;
  color: var(--gray-600);
  font-weight: 500;
}

.toolbar {
  display: flex;
  gap: 16px;
  padding: 20px 32px;
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
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
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
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  min-width: 1200px;
}

thead th {
  background: var(--gray-50);
  padding: 16px 12px;
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  color: var(--gray-700);
  border-bottom: 2px solid var(--gray-200);
  position: sticky;
  top: 0;
  z-index: 10;
}

tbody tr {
  transition: all 0.2s ease;
  border-bottom: 1px solid var(--gray-100);
}

tbody tr:hover {
  background: var(--gray-50);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

td {
  padding: 12px;
  font-size: 14px;
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
  background: rgba(16, 185, 129, 0.1);
  color: var(--success);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-badge.offline {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger);
  border: 1px solid rgba(239, 68, 68, 0.2);
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
  background: rgba(16, 185, 129, 0.1);
  color: var(--success);
}

.rx-value.warn {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning);
}

.rx-value.bad {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger);
}

.top-progress-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  z-index: 9999;
  overflow: hidden;
}

.top-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #818cf8, #a78bfa, #c084fc);
  background-size: 200% 100%;
  animation: shimmer 2s ease-in-out infinite;
  transition: width 0.3s ease;
  box-shadow: 0 0 10px rgba(129, 140, 248, 0.5);
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.loading-status {
  position: fixed;
  top: 16px;
  right: 16px;
  background: rgba(255, 255, 255, 0.95);
  padding: 12px 20px;
  border-radius: 12px;
  box-shadow: var(--shadow-lg);
  z-index: 9998;
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-700);
  border: 2px solid var(--primary);
  backdrop-filter: blur(10px);
}

.loading-status::before {
  content: '⏳';
  margin-right: 8px;
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

.pon-1 { background: linear-gradient(135deg, #a5b4fc, #c7d2fe); }
.pon-2 { background: linear-gradient(135deg, #fda4af, #fecdd3); }
.pon-3 { background: linear-gradient(135deg, #7dd3fc, #bae6fd); }
.pon-4 { background: linear-gradient(135deg, #86efac, #bbf7d0); }
.pon-5 { background: linear-gradient(135deg, #f0abfc, #f5d0fe); }
.pon-6 { background: linear-gradient(135deg, #fcd34d, #fde68a); }
.pon-7 { background: linear-gradient(135deg, #fdba74, #fed7aa); }
.pon-8 { background: linear-gradient(135deg, #c4b5fd, #ddd6fe); }

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--gray-500);
}

.empty-state h3 {
  font-size: 18px;
  margin: 0 0 8px 0;
  color: var(--gray-700);
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

@media (max-width: 768px) {
  body {
    padding: 10px;
  }
  
  .header {
    padding: 20px;
  }
  
  .header h1 {
    font-size: 24px;
  }
  
  .stats-bar {
    padding: 16px 20px;
    gap: 12px;
  }
  
  .stat-card {
    min-width: 140px;
  }
  
  .toolbar {
    padding: 16px 20px;
  }
  
  .search-container {
    min-width: 250px;
  }
}

.spinner-text {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
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
      <h1>🚀 Syrotech OLT — ONU Monitor</h1>
      <p class="subtitle">
        <span class="mono" id="snap"></span>
        · Enhanced monitoring with real-time data
        <?php if (!empty($_GET['pon'])): ?>
          · Filter: PON <?= htmlspecialchars($_GET['pon']) ?>
        <?php endif; ?>
      </p>
    </div>

    <div class="stats-bar">
      <div class="stat-card">
        <div class="stat-icon pon">📡</div>
        <div class="stat-content">
          <h3 id="total-pons">0</h3>
          <p>PONs</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon onu">🔌</div>
        <div class="stat-content">
          <h3 id="total-onus">0</h3>
          <p>ONUs</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon online">✅</div>
        <div class="stat-content">
          <h3 id="online-onus">0</h3>
          <p>Online</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon offline">❌</div>
        <div class="stat-content">
          <h3 id="offline-onus">0</h3>
          <p>Offline</p>
        </div>
      </div>
    </div>

    <div class="toolbar">
      <div class="search-container">
        <input id="search" type="text" placeholder="Search by description, ONU ID, or model...">
      </div>
      <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="online">Online</button>
        <button class="filter-btn" data-filter="offline">Offline</button>
      </div>
      <div style="margin-left: auto; font-weight: 600; color: var(--gray-600);">
        <span id="count">0 shown</span>
      </div>
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
        <tbody id="body"></tbody>
      </table>
    </div>

    <div id="empty-state" class="empty-state" style="display: none;">
      <h3>No ONUs found</h3>
      <p>Try adjusting your search or filter criteria</p>
    </div>
  </div>

  <div id="top-progress-bar" class="top-progress-bar" style="display: none;">
    <div id="top-progress-fill" class="top-progress-fill" style="width: 0%"></div>
  </div>

  <div id="loading-status" class="loading-status" style="display: none;">
    Loading PON 1/8...
  </div>

<script>
  /* Hard-set API base & PONs so JS never guesses */
  window.API_BASE = "/api/";
  window.PONS     = <?= json_encode(array_values($ponList)) ?>;
  
  console.log('Enhanced page loaded');
  console.log('API_BASE:', window.API_BASE);
  console.log('PONS:', window.PONS);

  // Enhanced JavaScript with better UI
  (function(){
    'use strict';

    console.log('OLT Enhanced Script Starting...');

    // Get API base and PONs from window variables
    var API_BASE = window.API_BASE || '/api/';
    var PONS = window.PONS || [1,2,3,4,5,6,7,8];
    
    // Ensure API_BASE ends with /
    if (!API_BASE.endsWith('/')) {
      API_BASE += '/';
    }
    
    console.log('API_BASE:', API_BASE);
    console.log('PONS:', PONS);

    // Get DOM elements
    var tbody = document.getElementById('body');
    var notes = document.getElementById('notes');
    var count = document.getElementById('count');
    var snapEl = document.getElementById('snap');
    var topProgressBar = document.getElementById('top-progress-bar');
    var topProgressFill = document.getElementById('top-progress-fill');
    var loadingStatus = document.getElementById('loading-status');
    var emptyState = document.getElementById('empty-state');
    
    // Stats elements
    var totalPonsEl = document.getElementById('total-pons');
    var totalOnusEl = document.getElementById('total-onus');
    var onlineOnusEl = document.getElementById('online-onus');
    var offlineOnusEl = document.getElementById('offline-onus');
    
    console.log('DOM elements found:', {
      tbody: !!tbody,
      count: !!count,
      snapEl: !!snapEl,
      topProgressBar: !!topProgressBar
    });

    // Set snapshot time
    if (snapEl) {
      snapEl.textContent = new Date().toISOString().replace('T',' ').slice(0,19);
    }

    // Helper functions
    function escapeHtml(text) {
      if (text == null) return '';
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function normalizeDesc(text) {
      if (text == null) return '';
      return text.toLowerCase()
        .replace(/\u00a0/g, ' ')
        .replace(/[_\-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function normalizeOnu(text) {
      if (text == null) return '';
      return text.toUpperCase()
        .replace(/\u00a0/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    // Simple fetch with error handling
    function fetchJSON(url) {
      console.log('Fetching:', url);
      return fetch(url, {cache: 'no-store'})
        .then(function(response) {
          if (!response.ok) {
            throw new Error('HTTP ' + response.status + ' for ' + url);
          }
          return response.text(); // Get as text first
        })
        .then(function(text) {
          console.log('Raw response length:', text.length);
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text (first 500 chars):', text.substring(0, 500));
            throw new Error('Invalid JSON response');
          }
        });
    }

    // Color RX cell based on value
    function getRxClass(value) {
      var numValue = parseFloat(value);
      if (isNaN(numValue)) return 'dim';
      
      if (numValue <= -28) return 'bad';
      if (numValue <= -23) return 'warn';
      if (numValue <= -8) return 'good';
      return 'warn';
    }

    // Row storage
    var allRows = [];
    var rowsByKey = {};
    var stats = {
      totalPons: 0,
      totalOnus: 0,
      onlineOnus: 0,
      offlineOnus: 0
    };

    // Update stats
    function updateStats() {
      if (totalPonsEl) totalPonsEl.textContent = stats.totalPons;
      if (totalOnusEl) totalOnusEl.textContent = stats.totalOnus;
      if (onlineOnusEl) onlineOnusEl.textContent = stats.onlineOnus;
      if (offlineOnusEl) offlineOnusEl.textContent = stats.offlineOnus;
    }

    // Add row to table
    function addRow(rowData) {
      console.log('Adding row:', rowData);
      
      if (!tbody) {
        console.error('tbody not found!');
        return;
      }

      var key = (rowData.pon != null && rowData.onu != null) ? 
                (rowData.pon + '-' + rowData.onu) : 
                ('x-' + Math.random());
      
      var tr = document.createElement('tr');
      tr.className = 'pon-' + (rowData.pon || '');
      tr.dataset.pon = (rowData.pon != null ? rowData.pon : '');
      tr.dataset.onu = (rowData.onu != null ? rowData.onu : '');
      tr.dataset.desc = normalizeDesc(rowData.desc || '');
      tr.dataset.onuid = normalizeOnu(rowData.onuid || '');
      tr.dataset.status = normalizeDesc(rowData.status || '');

      var statusOk = /online/i.test(rowData.status || '');
      var statusClass = statusOk ? 'online' : 'offline';
      var statusIcon = statusOk ? '✅' : '❌';
      
      // Update stats
      stats.totalOnus++;
      if (statusOk) {
        stats.onlineOnus++;
      } else {
        stats.offlineOnus++;
      }
      updateStats();
      
      var html = '';
      html += '<td><div class="pon-badge pon-' + (rowData.pon || '1') + '">' + escapeHtml(rowData.pon || '') + '</div></td>';
      html += '<td class="mono">' + escapeHtml(rowData.onu || '') + '</td>';
      html += '<td class="mono">' + escapeHtml(rowData.onuid || '') + '</td>';
      html += '<td><strong>' + escapeHtml(rowData.desc || '') + '</strong></td>';
      html += '<td>' + escapeHtml(rowData.model || '') + '</td>';
      html += '<td><span class="status-badge ' + statusClass + '">' + statusIcon + ' ' + escapeHtml(rowData.status || '') + '</span></td>';
      
      // Show spinner only for online ONUs, "—" for offline
      if (statusOk) {
        html += '<td id="wan-' + key + '"><span class="spinner-text dim"><span class="spinner"></span> Loading...</span></td>';
        html += '<td id="rx-' + key + '"><span class="spinner-text dim"><span class="spinner"></span> Loading...</span></td>';
      } else {
        html += '<td class="dim" title="ONU is offline - no WAN data">—</td>';
        html += '<td class="dim" title="ONU is offline - no optical data">—</td>';
      }
      
      html += '<td id="avg-' + key + '" class="dim">N/A</td>';
      html += '<td id="delta-' + key + '" class="delta-ok">—</td>';
      
      tr.innerHTML = html;
      tbody.appendChild(tr);
      
      allRows.push(tr);
      if (rowData.pon != null && rowData.onu != null) {
        rowsByKey[key] = tr;
      }
      
      console.log('Row added successfully, total rows:', allRows.length);
    }

    // Search and filter functionality
    var searchInput = document.getElementById('search');
    var filterButtons = document.querySelectorAll('.filter-btn');
    var currentFilter = 'all';

    function updateDisplay() {
      var query = normalizeDesc(searchInput ? searchInput.value : '');
      var shown = 0;
      var hasVisibleRows = false;
      
      for (var i = 0; i < allRows.length; i++) {
        var tr = allRows[i];
        var descMatch = (!query || tr.dataset.desc.indexOf(query) !== -1);
        var statusMatch = (currentFilter === 'all' || 
                          (currentFilter === 'online' && tr.dataset.status.indexOf('online') !== -1) ||
                          (currentFilter === 'offline' && tr.dataset.status.indexOf('offline') !== -1));
        
        var visible = descMatch && statusMatch;
        tr.style.display = visible ? '' : 'none';
        
        if (visible) {
          shown++;
          hasVisibleRows = true;
        }
      }
      
      if (count) {
        count.textContent = shown + ' shown';
      }
      
      // Show/hide empty state
      if (emptyState) {
        emptyState.style.display = hasVisibleRows ? 'none' : 'block';
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', updateDisplay);
    }

    // Filter button functionality
    filterButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        updateDisplay();
      });
    });

    // Loading functions
    function showLoading() {
      if (topProgressBar) topProgressBar.style.display = 'block';
      if (loadingStatus) loadingStatus.style.display = 'block';
    }

    function hideLoading() {
      if (topProgressBar) topProgressBar.style.display = 'none';
      if (loadingStatus) loadingStatus.style.display = 'none';
    }

    function updateProgress(current, total, message) {
      if (topProgressFill) {
        var percentage = Math.round((current / total) * 100);
        topProgressFill.style.width = percentage + '%';
      }
      if (loadingStatus && message) {
        loadingStatus.textContent = message;
      }
    }

    // Main loading function
    function loadData() {
      console.log('Starting data load...');
      
      showLoading();
      stats.totalPons = PONS.length;
      updateStats();

      var currentPonIndex = 0;
      var totalOnus = 0;

      function loadNextPon() {
        if (currentPonIndex >= PONS.length) {
          console.log('All PONs loaded, total ONUs:', totalOnus);
          hideLoading();
          updateDisplay();
          return;
        }

        var pon = PONS[currentPonIndex];
        console.log('Loading PON', pon);
        
        updateProgress(currentPonIndex, PONS.length, 'Loading PON ' + pon + ' (' + (currentPonIndex + 1) + '/' + PONS.length + ')');

        // Load auth data for this PON
        fetchJSON(API_BASE + 'auth.php?pon=' + encodeURIComponent(pon))
          .then(function(authData) {
            console.log('Auth data for PON', pon, ':', authData);
            
            if (!authData || !authData.ok) {
              throw new Error(authData && authData.error ? authData.error : 'Auth failed for PON ' + pon);
            }

            var rows = authData.rows || [];
            console.log('Found', rows.length, 'ONUs for PON', pon);
            
            // Separate online and offline ONUs
            var onlineOnus = [];
            var offlineOnus = [];
            
            for (var i = 0; i < rows.length; i++) {
              addRow(rows[i]);
              totalOnus++;
              
              // Categorize by status for optimization
              if (/online/i.test(rows[i].status || '')) {
                onlineOnus.push(rows[i]);
              } else {
                offlineOnus.push(rows[i]);
              }
            }

            // Only load optical and WAN data for ONLINE ONUs
            if (onlineOnus.length > 0) {
              var idsList = [];
              for (var j = 0; j < Math.min(onlineOnus.length, 10); j++) {
                var id = (onlineOnus[j].onuid || '');
                id = normalizeOnu(id);
                if (id) idsList.push(id);
              }
              
              var idsParam = encodeURIComponent(idsList.join('|'));
              var opticalUrl = API_BASE + 'optical.php?pon=' + encodeURIComponent(pon) + '&ids=' + idsParam;
              
              console.log('Loading optical data for', onlineOnus.length, 'online ONUs in PON', pon);
              return fetchJSON(opticalUrl)
                .then(function(opticalData) {
                  console.log('Optical data for PON', pon, ':', opticalData);
                  
                  if (opticalData && opticalData.ok && opticalData.rx) {
                    var rxByOnu = {};
                    for (var k = 0; k < opticalData.rx.length; k++) {
                      var item = opticalData.rx[k];
                      var idn = normalizeOnu(item.onuid || item.onuid_norm || '');
                      if (idn) {
                        rxByOnu[idn] = item.rx;
                      }
                    }
                    
                    // Update RX cells for online ONUs only
                    for (var key in rowsByKey) {
                      if (!rowsByKey.hasOwnProperty(key)) continue;
                      var tr = rowsByKey[key];
                      if (Number(tr.dataset.pon) !== Number(pon)) continue;
                      
                      // Only update if online
                      var isOnline = /online/i.test(tr.dataset.status);
                      if (!isOnline) continue;
                      
                      var idn2 = tr.dataset.onuid;
                      var rxCell = tr.querySelector('#rx-' + key);
                      var rxValue = rxByOnu[idn2];
                      
                      if (rxCell && rxValue !== undefined) {
                        var rxClass = getRxClass(rxValue);
                        rxCell.innerHTML = '<span class="rx-value ' + rxClass + '">' + 
                                          (isNaN(parseFloat(rxValue)) ? 'N/A' : parseFloat(rxValue).toFixed(2) + ' dBm') + 
                                          '</span>';
                      }
                    }
                    
                    // Now load WAN status for ALL online ONUs in batches
                    var wanPromises = [];
                    var batchSize = 6; // Load 6 WAN requests at a time for optimal speed
                    
                    function loadWanBatch(startIndex) {
                      var batchPromises = [];
                      var endIndex = Math.min(startIndex + batchSize, onlineOnus.length);
                      
                      for (var w = startIndex; w < endIndex; w++) {
                        var onuData = onlineOnus[w];
                        var wanKey = pon + '-' + onuData.onu;
                        var wanCell = document.querySelector('#wan-' + wanKey);
                        
                        if (wanCell) {
                          (function(cell, p, o) {
                            batchPromises.push(
                              fetchJSON(API_BASE + 'wan.php?pon=' + p + '&onu=' + o)
                                .then(function(wanData) {
                                  if (wanData && wanData.ok && wanData.status) {
                                    var isConnected = /connect/i.test(wanData.status);
                                    cell.innerHTML = '<span class="status-badge ' + (isConnected ? 'online' : 'offline') + '">' +
                                                    (isConnected ? '✅' : '❌') + ' ' + escapeHtml(wanData.status) + '</span>';
                                  }
                                })
                                .catch(function() {
                                  cell.textContent = 'Unknown';
                                  cell.className = 'dim';
                                })
                            );
                          })(wanCell, pon, onuData.onu);
                        }
                      }
                      
                      return Promise.all(batchPromises).then(function() {
                        if (endIndex < onlineOnus.length) {
                          return loadWanBatch(endIndex);
                        }
                      });
                    }
                    
                    return loadWanBatch(0);
                  }
                })
                .catch(function(error) {
                  console.error('Optical data error for PON', pon, ':', error);
                  // Continue anyway
                });
            }
          })
          .catch(function(error) {
            console.error('Error loading PON', pon, ':', error);
            
            // Add error row
            if (tbody) {
              var errorTr = document.createElement('tr');
              errorTr.className = 'pon-' + pon;
              errorTr.innerHTML = '<td><div class="pon-badge pon-' + pon + '">' + pon + '</div></td><td></td><td></td><td colspan="7" class="bad">❌ Error: ' + escapeHtml(error.message || error) + '</td>';
              tbody.appendChild(errorTr);
              allRows.push(errorTr);
            }
          })
          .then(function() {
            // Move to next PON
            currentPonIndex++;
            setTimeout(loadNextPon, 100); // Reduced delay for faster loading
          });
      }

      // Start loading
      loadNextPon();
    }

    // Start loading when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', loadData);
    } else {
      loadData();
    }

    console.log('OLT Enhanced Script Loaded');
  })();
</script>
</body>
</html>
