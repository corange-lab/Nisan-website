<?php
$CFG = require __DIR__.'/lib/config.php';
$PONS = $CFG['PONS'] ?? range(1,8);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>ONU Statistics</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;margin:20px}
    table{border-collapse:collapse;width:100%;max-width:1100px}
    th,td{border:1px solid #ddd;padding:6px 8px;font-size:14px}
    th{background:#f5f5f5;text-align:left}
    .mono{font-family:ui-monospace,Consolas,monospace}
    .dim{opacity:.7}
  </style>
  <script>
    // index.php is now at /b/, so api is ./api/
    window.API_BASE = './api/';
    window.PONS = <?php echo json_encode($PONS, JSON_UNESCAPED_SLASHES); ?>;
  </script>
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

  <!-- If you moved app.js next to index.php -->
  <script src="./app.js"></script>

  <!-- If you kept app.js in /b/public/, comment the line above and use this: -->
  <!-- <script src="./public/app.js"></script> -->
</body>
</html>
