<?php /* /b/index.php */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Network Throughput & ONU Usage</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="./assets/style.css">
  <script>window.API_BASE='./api/';</script>
</head>
<body>
  <div class="wrap">
    <!-- ... top cards & chart exactly as you had ... -->

    <div class="title" style="margin:6px 4px">ONU Usage (now / today / max)</div>
    <div class="notes" id="notes"><span class="skeleton">Measuring ONUs…</span></div>

    <table id="tbl">
      <thead>
        <tr>
          <th>ONU ID</th>
          <th>PON</th>
          <th class="num">Input</th>
          <th class="num">Output</th>
          <th class="num">Now</th>
          <th class="num">Today Usage</th>  <!-- was Avg (Today) -->
          <th class="num">Max (Today)</th>
          <th>Test</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <!-- cache-bust to be safe -->
  <script src="./app.js?v=2025-08-17-2"></script>
</body>
</html>
