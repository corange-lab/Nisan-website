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
$ver_js  = @filemtime(__DIR__.'/assets/app.js') ?: time();
$ver_css = @filemtime(__DIR__.'/assets/styles.css') ?: time();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Syrotech OLT — ONU Monitor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= htmlspecialchars($ASSETS, ENT_QUOTES) ?>styles.css?v=<?= $ver_css ?>">
</head>
<body>
  <h1>Syrotech OLT — ONU Monitor</h1>
  <div class="sub">
    Snapshot: <span class="mono" id="snap"></span>
    · Progressive load: <b>Auth</b> → <b>RX</b> → <b>WAN</b>
    <?php if (!empty($_GET['pon'])): ?>
      · Filter: PON <?= htmlspecialchars($_GET['pon']) ?>
    <?php endif; ?>
  </div>

  <div class="toolbar">
    <input id="search" type="text" placeholder="Search by Description…">
    <div class="count" id="count">0 shown</div>
  </div>

  <div class="wrap">
    <table id="tbl">
      <colgroup>
        <col class="col-pon"><col class="col-onu"><col class="col-onuid"><col class="col-desc"><col class="col-model">
        <col class="col-status"><col class="col-wan"><col class="col-rx"><col class="col-avg"><col class="col-delta">
      </colgroup>
      <thead>
        <tr>
          <th>PON</th><th>ONU</th><th>ONU ID</th><th>Description</th><th>Model</th>
          <th>Status</th><th>WAN Status</th><th>RX Power (dBm)</th><th>24h Avg</th><th>Δ vs 24h</th>
        </tr>
      </thead>
      <tbody id="body"></tbody>
    </table>
  </div>

  <div class="sub" id="notes"></div>

<script>
  /* Hard-set API base & PONs so JS never guesses */
  window.API_BASE = "<?= rtrim($API_BASE,'/') ?>/";
  window.PONS     = <?= json_encode(array_values($ponList)) ?>;
</script>
<script src="<?= htmlspecialchars($ASSETS, ENT_QUOTES) ?>app.js?v=<?= $ver_js ?>"></script>
</body>
</html>
