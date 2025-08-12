<?php $CFG = require __DIR__.'/lib/config.php'; ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Syrotech OLT — ONU Monitor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/styles.css">
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
        <col class="col-info"><col class="col-status"><col class="col-wan"><col class="col-rx">
      </colgroup>
      <thead>
        <tr>
          <th>PON</th><th>ONU</th><th>ONU ID</th><th>Description</th><th>Model</th><th>Info</th><th>Status</th><th>WAN Status</th><th>RX Power (dBm)</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <div class="sub" id="notes"></div>

<script>
window.PONS = <?php echo json_encode($CFG['PONS']); ?>;
</script>
<script src="assets/app.js"></script>
</body>
</html>
