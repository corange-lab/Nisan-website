<?php
$CFG = require __DIR__.'/lib/config.php';

// Auto-detect this app’s base path (e.g. "/" or "/olt")
$BASE_PATH = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($BASE_PATH === '') $BASE_PATH = '/';
$API_BASE  = $BASE_PATH . '/api/';      // e.g. "/api/" or "/olt/api/"
$ASSETS    = $BASE_PATH . '/assets/';   // e.g. "/assets/" or "/olt/assets/"
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Syrotech OLT — ONU Monitor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Tell JS exactly where the API lives -->
<meta name="api-base" content="<?= htmlspecialchars($API_BASE, ENT_QUOTES) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($ASSETS, ENT_QUOTES) ?>styles.css">
</head>
<body>
  <h1>Syrotech OLT — ONU Monitor</h1>
  <div class="sub">Snapshot: <span class="mono" id="snap"></span> · Progressive load: <b>Auth</b> → <b>RX</b> → <b>WAN</b></div>

  <div class="toolbar">
    <input id="search" type="text" placeholder="Search by Description…">
    <div class="count" id="count">0 shown</div>
  </div>

  <div class="wrap">
    <table id="tbl">
      <colgroup>
        <col class="col-pon"><col class="col-onu"><col class="col-onuid"><col class="col-desc"><col class="col-model">
        <col class="col-info"><col class="col-status"><col class="col-wan"><col class="col-rx"><col class="col-avg"><col class="col-delta">
      </colgroup>
      <thead>
        <tr>
          <th>PON</th><th>ONU</th><th>ONU ID</th><th>Description</th><th>Model</th><th>Info</th><th>Status</th><th>WAN Status</th>
          <th>RX Power (dBm)</th><th>24h Avg</th><th>Δ vs 24h</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <div class="sub" id="notes"></div>

<script>
window.PONS = <?php echo json_encode($CFG['PONS']); ?>;
</script>
<script src="<?= htmlspecialchars($ASSETS, ENT_QUOTES) ?>app.js"></script>
</body>
</html>
