<?php $CFG = require __DIR__.'/../lib/config.php'; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>ONU Statistics</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:20px}
    table{border-collapse:collapse;width:100%;max-width:1100px}
    th,td{border:1px solid #ddd;padding:6px 8px;font-size:14px}
    th{background:#f5f5f5;text-align:left}
    .mono{font-family:ui-monospace,Consolas,monospace}
    .dim{opacity:.7}
  </style>
  <script>window.API_BASE='../api/';</script>
</head>
<body>
  <h2>ONU Statistics (Input/Output Bytes & Packets)</h2>
  <p id="notes" class="dim">Loading…</p>
  <input id="q" placeholder="Filter by ONU ID (e.g., GPON0/1:3)" style="padding:6px 8px;width:320px">
  <p id="count" class="dim"></p>
  <table>
    <thead><tr>
      <th>ONU ID</th><th>PON</th>
      <th class="mono">Input Bytes</th><th class="mono">Output Bytes</th>
      <th class="mono">Input Packets</th><th class="mono">Output Packets</th>
    </tr></thead>
    <tbody id="body"></tbody>
  </table>
  <script src="./app.js"></script>
</body>
</html>
