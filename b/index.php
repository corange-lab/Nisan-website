<?php /* /b/index.php */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Nisan • Network Bandwidth & ONU Usage</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="./assets/style.css">
  <style>
    :root{
      --bg:#0b1226; --card:#0f1833; --muted:#9bb0e4; --text:#e8eeff;
      --u:#60a5fa; --d:#f472b6; --accent:#3b82f6; --ok:#22c55e;
      --row:#0c152d; --row2:#0d1731;
    }
    html,body{background:#070e1f;color:var(--text);font:14px/1.45 system-ui,Segoe UI,Roboto,Helvetica,Arial;}
    .wrap{max-width:1200px;margin:18px auto;padding:0 12px;}
    .topbar{display:flex;align-items:center;gap:12px;justify-content:space-between;margin-bottom:10px;}
    .pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#142458;color:#cfe0ff;font-size:11px}
    .switch{position:relative;width:44px;height:24px;background:#1f2a4d;border-radius:12px;cursor:pointer}
    .switch input{display:none}
    .switch .knob{position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .2s}
    .switch input:checked + .knob{transform:translateX(20px)}

    .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:12px 0}
    .card{background:#0f1833;border-radius:14px;padding:12px 14px;box-shadow:0 6px 20px rgba(0,0,0,.25)}
    .card h3{margin:0 0 6px;font-size:12px;color:var(--muted);font-weight:600;letter-spacing:.3px}
    .big{font-size:26px;font-weight:800}
    .muted{color:var(--muted);font-size:12px}

    .panel{background:#0f1833;border-radius:14px;padding:12px 14px;margin:12px 0}
    .controls{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:8px}
    .ctrl{display:flex;flex-direction:column;gap:6px}
    .btn{background:#16224a;border:1px solid #23336c;color:#cfe0ff;border-radius:10px;padding:6px 10px;cursor:pointer}
    .btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
    #chart_box{position:relative;background:#0c1431;border-radius:12px;padding:6px}
    #chart_svg{width:100%;height:420px;display:block}
    #chart_tip{position:absolute;display:none;background:#0e1a3d;color:#e8eeff;border:1px solid #24336e;border-radius:8px;padding:8px 10px;font-size:12px;pointer-events:none}
    #chart_tip .dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;vertical-align:-1px}
    #chart_tip .dot.u{background:var(--u)} #chart_tip .dot.d{background:var(--d)}

    table{width:100%;border-collapse:separate;border-spacing:0 6px;margin-top:10px}
    th,td{text-align:left;padding:10px 12px}
    thead th{font-size:12px;color:var(--muted);font-weight:700}
    tbody tr{background:var(--row);border-radius:10px}
    tbody tr:nth-child(2n){background:var(--row2)}
    td.num{text-align:right}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
    .status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#2b365e;margin-right:8px;vertical-align:1px}
    .status-dot.on{background:var(--ok)}
    .bars{display:inline-flex;gap:3px;margin-left:8px;vertical-align:middle}
    .bars .bar{width:6px;height:12px;background:#2b396e;border-radius:2px}
    .bars .bar.on{background:#23c55e}
    .title{font-weight:800;font-size:14px;margin:6px 0 4px;color:#cfe0ff}
    .notes{color:var(--muted);font-size:12px;margin:4px 2px}
    .skeleton{display:inline-block;background:linear-gradient(90deg,#13214a 25%,#1a2a5f 37%,#13214a 63%);background-size:400% 100%;border-radius:8px;color:transparent;user-select:none;padding:2px 8px;animation:sh 1.4s ease infinite}
    @keyframes sh{0%{background-position:100% 50%}100%{background-position:0 50%}}
  </style>
  <script>window.API_BASE='./api/';</script>
</head>
<body>
  <div class="wrap">

    <div class="topbar">
      <h1 style="margin:0">Network Bandwidth & ONU Usage</h1>
      <div style="display:flex;align-items:center;gap:12px">
        <span id="net_status" class="pill">Loading…</span>
        <label class="switch" title="Toggle tracking">
          <input type="checkbox" id="track_toggle" checked>
          <span class="knob"></span>
        </label>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <h3>Download (Now)</h3>
        <div class="big"><span id="net_down_val" data-val="0">0.00</span> <span class="muted">Mbps</span></div>
        <div class="muted">Sum of all ONUs</div>
      </div>
      <div class="card">
        <h3>Upload (Now)</h3>
        <div class="big"><span id="net_up_val" data-val="0">0.00</span> <span class="muted">Mbps</span></div>
        <div class="muted">Sum of all ONUs</div>
      </div>
      <div class="card">
        <h3>Total (Now)</h3>
        <div class="big"><span id="net_total_val" data-val="0">0.00</span> <span class="muted">Mbps</span></div>
        <div class="muted">Updated <span id="net_dt">—</span>s ago · <span id="net_when">—</span></div>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <h3>Peak (24h)</h3>
        <div class="big" id="pk_24h">— Mbps</div>
        <div class="muted" id="pk_24h_t">—</div>
      </div>
      <div class="card">
        <h3>Peak (7 days)</h3>
        <div class="big" id="pk_7d">— Mbps</div>
        <div class="muted" id="pk_7d_t">—</div>
      </div>
      <div class="card">
        <h3>Peak (30 days)</h3>
        <div class="big" id="pk_30d">— Mbps</div>
        <div class="muted" id="pk_30d_t">—</div>
      </div>
    </div>

    <div class="panel">
      <div class="controls">
        <div class="ctrl">
          <label class="muted" for="chart_date">Date</label>
          <input id="chart_date" type="date" class="btn" style="padding:6px 8px">
        </div>
        <div class="ctrl">
          <label class="muted" for="chart_tf">Timeframe</label>
          <select id="chart_tf" class="btn" style="padding:6px 8px">
            <option value="1m" selected>1-minute (per-minute peak)</option>
            <option value="raw">Raw (~3s)</option>
          </select>
        </div>
        <button id="chart_refresh" class="btn primary">Refresh</button>
      </div>
      <div id="chart_box">
        <svg id="chart_svg" viewBox="0 0 1000 420" preserveAspectRatio="none"></svg>
        <div id="chart_tip"></div>
      </div>
    </div>

    <div class="title">ONU Usage (Now • Today • Max)</div>
    <div class="notes" id="notes"><span class="skeleton">Measuring ONUs…</span></div>

    <table id="tbl">
      <thead>
        <tr>
          <th>ONU ID</th>
          <th>PON</th>
          <th class="num">Input (Download)</th>
          <th class="num">Output (Upload)</th>
          <th class="num">Now</th>
          <th class="num">Today Usage</th>
          <th class="num">Max (Today)</th>
          <th>Test</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <script>window.API_BASE='./api/';</script>
  <script src="./js/helpers.js?v=2025-08-17a"></script>
  <script src="./js/chart.js?v=2025-08-17a"></script>
  <script src="./js/table.js?v=2025-08-17a"></script>
</body>
</html>
