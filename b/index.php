<?php $CFG = require __DIR__.'/lib/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Network Throughput & ONU Usage</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#0b1023; --bg2:#0e1630; --card:#101935; --ink:#e6e9f2; --muted:#93a4c7; --edge:#1d2750;
      --accent:#6c7bff; --ok:#1cc8a0; --warn:#f7b955; --shadow:0 14px 40px rgba(0,0,0,.35);
      --br:18px;
    }
    *{box-sizing:border-box}
    body{margin:0;background:radial-gradient(1200px 600px at 20% -10%,#16245b22,transparent),linear-gradient(180deg,#0b1023,#0b1023 30%,#0e1630);
         color:var(--ink);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif}
    .wrap{max-width:1280px;margin:28px auto;padding:0 18px}
    .hero{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px}
    .card{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--edge);
          border-radius:var(--br);box-shadow:var(--shadow);padding:18px 20px}
    .title{font-weight:800;font-size:18px;margin:0 0 8px;letter-spacing:.3px}
    .pill{display:inline-block;margin-left:8px;background:#1a2449;color:#cbd5ff;border:1px solid #2a3670;padding:4px 10px;border-radius:999px;font-size:12px}
    .grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-top:8px}
    .big{font-weight:900;font-size:44px;line-height:1.1}
    .unit{font-size:16px;color:var(--muted);margin-left:6px}
    .sub{color:var(--muted);font-size:13px;margin-top:6px}
    .notes{color:var(--muted);font-size:13px;margin:10px 2px 14px}
    .btn{cursor:pointer;border:1px solid #2a3670;background:#0e1630;color:#d7def9;border-radius:12px;padding:7px 12px;margin-right:8px}
    .btn:hover{background:#12204a}
    .peaks{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
    .peak{background:linear-gradient(180deg,#0b1432,#0b132e);border:1px solid #1c2550;border-radius:14px;padding:12px 14px}
    .peak .k{font-size:12px;color:#9bb0e4;text-transform:uppercase;letter-spacing:.06em}
    .peak .v{font-weight:800;font-size:22px}
    .peak .t{color:#7d8fbf;font-size:12px;margin-top:6px}
    table{width:100%;border-collapse:separate;border-spacing:0 10px}
    thead th{color:#b9c7ee;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-size:12px;padding:10px 14px}
    tbody td, thead th{background:#0c1533;border:1px solid #1a2450}
    thead th:first-child, tbody tr td:first-child{border-top-left-radius:12px;border-bottom-left-radius:12px}
    thead th:last-child,  tbody tr td:last-child{border-top-right-radius:12px;border-bottom-right-radius:12px}
    tbody td{padding:12px 14px;vertical-align:top}
    .mono{font-family:ui-monospace,Consolas,monospace}
    .detail td{background:#0f1c46}
    .status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#3a4a8a;margin-right:6px}
    .status-dot.on{background:#1cc8a0; box-shadow:0 0 0 3px rgba(28,200,160,.15)}
    .bars{display:inline-flex;gap:3px;margin-left:8px;vertical-align:middle}
    .bar{width:6px;height:12px;border-radius:2px;background:#1f2a55;opacity:.5}
    .bar.on{background:#25d0a7;opacity:1}
    .countdown{font-variant-numeric:tabular-nums}
    .skeleton{display:inline-block;min-width:7ch;background:linear-gradient(90deg,#111e49,#172868,#111e49);background-size:200% 100%;animation:shimmer 1.2s linear infinite;color:transparent;border-radius:6px}
    @keyframes shimmer{0%{background-position:0% 0}100%{background-position:200% 0}}
  </style>
  <script>window.API_BASE='./api/';</script>
</head>
<body>
  <div class="wrap">
    <!-- Network card -->
    <div class="hero">
      <div class="card">
        <div class="title">Network Live Throughput <span class="pill"><span id="test_len">30s</span> test</span></div>
        <div class="grid3">
          <div>
            <div class="big" id="net_total">—<span class="unit">Mbps</span></div>
            <div class="sub">Total (Upload + Download)</div>
          </div>
          <div>
            <div class="big" id="net_up">—<span class="unit">Mbps</span></div>
            <div class="sub">Upload (Inbound)</div>
          </div>
          <div>
            <div class="big" id="net_down">—<span class="unit">Mbps</span></div>
            <div class="sub">Download (Outbound)</div>
          </div>
        </div>
        <div class="notes">
          <span id="net_status">Measuring… spinning up test</span>
          · Next update in <span class="countdown" id="net_cd">—</span>s
          · Window Δ <span id="net_dt">—</span>s
          · Updated <span id="net_when">—</span>
        </div>
        <div>
          <button class="btn" id="btn_10s">10s test (once)</button>
          <button class="btn" id="btn_30s">30s test (auto)</button>
        </div>
        <div class="peaks" style="margin-top:14px">
          <div class="peak">
            <div class="k">24-hour peak</div>
            <div class="v" id="pk_24h">— Mbps</div>
            <div class="t" id="pk_24h_t">—</div>
          </div>
          <div class="peak">
            <div class="k">7-day peak</div>
            <div class="v" id="pk_7d">— Mbps</div>
            <div class="t" id="pk_7d_t">—</div>
          </div>
          <div class="peak">
            <div class="k">30-day peak</div>
            <div class="v" id="pk_30d">— Mbps</div>
            <div class="t" id="pk_30d_t">—</div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="title">Sampler</div>
        <div class="sub">Press “10s test” for a quick one-off; it auto-returns to the 30s loop.</div>
        <div class="sub">Peaks come from stored snapshots (today / 7d / 30d).</div>
      </div>
    </div>

    <div class="title" style="margin:6px 4px">ONU Usage (now / avg / max)</div>
    <div class="notes" id="notes"><span class="skeleton">Measuring ONUs…</span></div>

    <table id="tbl">
      <thead>
        <tr>
          <th>Expand</th>
          <th>ONU ID</th>
          <th>PON</th>
          <th class="mono">Input</th>
          <th class="mono">Output</th>
          <th class="mono">Now</th>
          <th class="mono">Avg (Today)</th>
          <th class="mono">Max (Today)</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <script src="./app.js"></script>
</body>
</html>
