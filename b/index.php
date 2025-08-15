<?php /* /b/index.php */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Network Throughput & ONU Usage</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="./assets/style.css">
  <!-- For local proxy, set './api/proxy.php?p=' -->
  <script>window.API_BASE='./api/';</script>
</head>
<body>
  <div class="wrap">

    <div class="card">
      <div class="title">Network Live Throughput <span class="pill">≈3s realtime</span></div>
      <div class="grid3">
        <div>
          <div class="big"><span id="net_total_val" data-val="0">—</span><span class="unit"> Mbps</span></div>
          <div class="sub">Total (Upload + Download)</div>
        </div>
        <div>
          <div class="big"><span id="net_up_val" data-val="0">—</span><span class="unit"> Mbps</span></div>
          <div class="sub">Upload (Inbound)</div>
        </div>
        <div>
          <div class="big"><span id="net_down_val" data-val="0">—</span><span class="unit"> Mbps</span></div>
          <div class="sub">Download (Outbound)</div>
        </div>
      </div>
      <div class="notes">
        <span id="net_status">Realtime stream</span>
        · Δ <span id="net_dt">—</span>s
        · Updated <span id="net_when">—</span>
        · <label style="margin-left:10px;user-select:none">
            <input id="track_toggle" type="checkbox" checked style="vertical-align:middle;margin-right:6px">
            <span>Tracking</span>
          </label>
      </div>

      <div class="peaks">
        <div class="peak"><div class="k">24-hour peak</div><div class="v" id="pk_24h">— Mbps</div><div class="t" id="pk_24h_t">—</div></div>
        <div class="peak"><div class="k">7-day peak</div><div class="v" id="pk_7d">— Mbps</div><div class="t" id="pk_7d_t">—</div></div>
        <div class="peak"><div class="k">30-day peak</div><div class="v" id="pk_30d">— Mbps</div><div class="t" id="pk_30d_t">—</div></div>
      </div>

      <div class="chartWrap">
        <div class="chartToolbar">
          <div class="title" style="margin:0">Daily Trend</div>
          <input id="chart_date" type="date" class="btn">
          <select id="chart_tf" class="btn">
            <option value="1m" selected>1m (peak)</option>
            <option value="raw">3s (raw)</option>
          </select>
          <button id="chart_refresh" class="btn">Load</button>
          <div style="margin-left:auto;display:flex;gap:8px">
            <button id="zoom_in" class="btn">Zoom In</button>
            <button id="zoom_out" class="btn">Zoom Out</button>
            <button id="zoom_reset" class="btn">Reset</button>
          </div>
          <div class="legend" style="width:100%">
            <span class="dot u"></span> Upload
            <span class="dot d"></span> Download
          </div>
        </div>
        <div class="chart" id="chart_box">
          <svg id="chart_svg" viewBox="0 0 1000 420" preserveAspectRatio="none"></svg>
          <div class="tip" id="chart_tip"></div>
        </div>
      </div>
    </div>

    <div class="title" style="margin:6px 4px">ONU Usage (now / avg / max)</div>
    <div class="notes" id="notes"><span class="skeleton">Measuring ONUs…</span></div>

    <table id="tbl">
      <thead>
        <tr>
          <th>ONU ID</th>
          <th>PON</th>
          <th class="num">Input</th>
          <th class="num">Output</th>
          <th class="num">Now</th>
          <th class="num">Avg (Today)</th>
          <th class="num">Max (Today)</th>
          <th>Test</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <script src="./app.js"></script>
</body>
</html>
