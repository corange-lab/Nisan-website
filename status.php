<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Status — Nisan Internet Bilimora</title>
    <meta name="description" content="Real-time network status for Nisan Internet in Bilimora. Check current uptime, outage history, and 30-day availability.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://www.nisan.co.in/status">

    <meta property="og:title" content="Network Status — Nisan Internet Bilimora">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.nisan.co.in/status">
    <meta property="og:image" content="https://www.nisan.co.in/assets/imgs/metaog.webp">
    <meta property="og:description" content="Live network uptime status for Nisan Internet, Bilimora.">
    <meta property="og:site_name" content="Nisan Cable &amp; Internet">
    <meta property="og:locale" content="en_IN">

    <?php include('common-css.php'); ?>

    <style>
    /* ── Status page ─────────────────────────────── */
    .status-page { background: #f8f9fc; min-height: 80vh; padding-bottom: 64px; }

    .status-hero {
      padding: 52px 0 36px;
      background: #fff;
      border-bottom: 1px solid #e8eaf0;
    }
    .status-indicator-wrap { display: flex; align-items: center; gap: 18px; }
    .status-dot {
      width: 22px; height: 22px; border-radius: 50%;
      background: #d1d5db; flex-shrink: 0; transition: background .4s;
    }
    .status-dot.up   { background: #22c55e; box-shadow: 0 0 0 5px rgba(34,197,94,.18); }
    .status-dot.down { background: #ef4444; animation: spulse 1.4s ease infinite; }
    @keyframes spulse {
      0%, 100% { box-shadow: 0 0 0 0   rgba(239,68,68,.55); }
      70%       { box-shadow: 0 0 0 14px rgba(239,68,68,0); }
    }
    .status-headline { font-size: 2rem; font-weight: 700; margin: 0; color: #1a1a2e; }
    .status-sub      { margin: 5px 0 0; color: #6b7280; font-size: .95rem; }

    .status-stats-bar { background: #fff; padding: 26px 0; border-bottom: 1px solid #e8eaf0; }
    .status-stats-grid { display: flex; gap: 14px; flex-wrap: wrap; }
    .stat-card {
      background: #f8f9fc; border: 1px solid #e8eaf0; border-radius: 12px;
      padding: 18px 28px; flex: 1; min-width: 150px; text-align: center;
    }
    .stat-val   { display: block; font-size: 1.6rem; font-weight: 700; color: #1a1a2e; }
    .stat-label { display: block; font-size: .78rem; color: #6b7280; margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }

    .status-section       { padding: 40px 0 0; }
    .status-section-title { font-size: 1rem; font-weight: 600; color: #1a1a2e; margin: 0 0 18px; text-transform: uppercase; letter-spacing: .07em; }

    .uptime-bars { display: flex; gap: 3px; height: 38px; align-items: stretch; }
    .day-bar {
      flex: 1; border-radius: 3px; position: relative;
    }
    .day-bar.up      { background: #22c55e; }
    .day-bar.partial { background: #f59e0b; }
    .day-bar.down    { background: #ef4444; }
    .day-bar.no-data { background: #e5e7eb; }
    .day-bar .day-tip {
      display: none; position: absolute; bottom: calc(100% + 6px); left: 50%;
      transform: translateX(-50%); background: #1a1a2e; color: #fff;
      font-size: .73rem; padding: 5px 9px; border-radius: 6px;
      white-space: nowrap; z-index: 20; pointer-events: none;
    }
    .day-bar:hover .day-tip { display: block; }

    .uptime-bar-labels { display: flex; justify-content: space-between; font-size: .77rem; color: #9ca3af; margin-top: 7px; }
    .uptime-legend     { display: flex; align-items: center; gap: 14px; font-size: .82rem; color: #6b7280; flex-wrap: wrap; margin-top: 10px; }
    .legend-dot        { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 3px; }
    .legend-up      { background: #22c55e; }
    .legend-partial { background: #f59e0b; }
    .legend-down    { background: #ef4444; }
    .legend-no-data { background: #e5e7eb; }

    .incident-list { display: flex; flex-direction: column; gap: 10px; }
    .incident-item {
      background: #fff; border: 1px solid #e8eaf0; border-left: 4px solid #e5e7eb;
      border-radius: 10px; padding: 15px 20px; display: flex; align-items: flex-start; gap: 14px;
    }
    .incident-item.ongoing  { border-left-color: #ef4444; }
    .incident-item.resolved { border-left-color: #22c55e; }
    .incident-badge {
      font-size: .7rem; font-weight: 700; padding: 3px 9px;
      border-radius: 99px; white-space: nowrap; flex-shrink: 0; margin-top: 2px;
    }
    .badge-ongoing  { background: #fee2e2; color: #b91c1c; }
    .badge-resolved { background: #dcfce7; color: #15803d; }
    .incident-title { font-weight: 600; color: #1a1a2e; margin: 0 0 3px; font-size: .93rem; }
    .incident-meta  { font-size: .81rem; color: #6b7280; margin: 0; }
    .no-incidents {
      background: #fff; border: 1px solid #e8eaf0; border-radius: 10px;
      padding: 28px 24px; text-align: center; color: #6b7280;
    }
    .ni-icon { font-size: 1.6rem; display: block; margin-bottom: 8px; }
    .status-loading { color: #9ca3af; font-size: .9rem; }

    @media (max-width: 576px) {
      .status-headline { font-size: 1.4rem; }
      .uptime-bars     { height: 28px; }
      .stat-val        { font-size: 1.25rem; }
      .stat-card       { padding: 14px 16px; }
    }
    </style>
</head>

<body>
<?php include('header.php'); ?>

<main class="status-page">

  <!-- Hero -->
  <section class="status-hero">
    <div class="container">
      <div class="status-indicator-wrap">
        <span class="status-dot" id="statusDot"></span>
        <div>
          <h1 class="status-headline" id="statusHeadline">Checking…</h1>
          <p class="status-sub" id="statusSub">Fetching live data</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <section class="status-stats-bar">
    <div class="container">
      <div class="status-stats-grid">
        <div class="stat-card">
          <span class="stat-val" id="stat30d">—</span>
          <span class="stat-label">30-day uptime</span>
        </div>
        <div class="stat-card">
          <span class="stat-val" id="statMs">—</span>
          <span class="stat-label">Response time</span>
        </div>
        <div class="stat-card">
          <span class="stat-val" id="statLast">—</span>
          <span class="stat-label">Last checked</span>
        </div>
      </div>
    </div>
  </section>

  <!-- 30-day graph -->
  <section class="status-section">
    <div class="container">
      <h2 class="status-section-title">30-Day Availability</h2>
      <div class="uptime-bars" id="uptimeBars"></div>
      <div class="uptime-bar-labels">
        <span id="barFrom">30 days ago</span>
        <span id="barTo">Today</span>
      </div>
      <div class="uptime-legend">
        <span class="legend-dot legend-up"></span>Operational&nbsp;
        <span class="legend-dot legend-partial"></span>Partial outage&nbsp;
        <span class="legend-dot legend-down"></span>Outage&nbsp;
        <span class="legend-dot legend-no-data"></span>No data yet
      </div>
    </div>
  </section>

  <!-- Incidents -->
  <section class="status-section">
    <div class="container">
      <h2 class="status-section-title">Recent Incidents</h2>
      <div id="incidentList" class="incident-list">
        <p class="status-loading">Loading…</p>
      </div>
    </div>
  </section>

</main>

<?php include('footer.php'); ?>

<!-- Site JS -->
<script src="/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/main.js"></script>

<script>
(function () {
  var POLL_UP   = 3 * 60 * 1000;
  var POLL_DOWN = 30 * 1000;
  var timer = null;

  var dot     = document.getElementById('statusDot');
  var hdln    = document.getElementById('statusHeadline');
  var sub     = document.getElementById('statusSub');
  var s30     = document.getElementById('stat30d');
  var sMs     = document.getElementById('statMs');
  var sLast   = document.getElementById('statLast');
  var bars    = document.getElementById('uptimeBars');
  var incList = document.getElementById('incidentList');
  var bFrom   = document.getElementById('barFrom');
  var bTo     = document.getElementById('barTo');

  function fmtDate(ts) {
    var d = new Date(ts * 1000);
    return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
         + ' ' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
  }

  function ago(ts) {
    var s = Math.floor(Date.now() / 1000) - ts;
    if (s < 60)   return s + 's ago';
    if (s < 3600) return Math.floor(s / 60) + 'm ago';
    return Math.floor(s / 3600) + 'h ago';
  }

  function shortDate(str) {
    return new Date(str).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
  }

  function render(d) {
    var up = d.status === 'up';

    dot.className = 'status-dot ' + (up ? 'up' : 'down');
    hdln.textContent = up ? 'All Systems Operational' : 'Network Disruption Detected';
    hdln.style.color = up ? '#15803d' : '#b91c1c';
    sub.textContent  = d.open_incident
      ? 'Outage ongoing since ' + ago(d.open_incident.started_at)
      : (up ? 'Nisan Internet is running normally.' : 'We are working to restore service.');

    s30.textContent  = d.uptime_30d  != null ? d.uptime_30d.toFixed(2) + '%' : '—';
    sMs.textContent  = d.response_ms != null ? d.response_ms + ' ms'        : '—';
    sLast.textContent= d.checked_at  ? ago(d.checked_at) : '—';

    // Bars
    if (d.days && d.days.length) {
      bars.innerHTML = '';
      d.days.forEach(function (day) {
        var bar = document.createElement('div');
        bar.className = 'day-bar ' + (!day.total ? 'no-data' : day.pct >= 99 ? 'up' : day.pct >= 50 ? 'partial' : 'down');
        var tip = document.createElement('div');
        tip.className = 'day-tip';
        tip.textContent = !day.total
          ? shortDate(day.date) + ' · No data'
          : shortDate(day.date) + ' · ' + (day.pct != null ? day.pct.toFixed(1) : '—') + '% uptime';
        bar.appendChild(tip);
        bars.appendChild(bar);
      });
      bFrom.textContent = shortDate(d.days[0].date);
      bTo.textContent   = shortDate(d.days[d.days.length - 1].date);
    }

    // Incidents
    if (!d.incidents || !d.incidents.length) {
      incList.innerHTML = '<div class="no-incidents"><span class="ni-icon">✅</span>No incidents in the last 30 days.</div>';
    } else {
      incList.innerHTML = '';
      d.incidents.forEach(function (inc) {
        var wrap  = document.createElement('div');
        wrap.className = 'incident-item ' + (inc.ongoing ? 'ongoing' : 'resolved');

        var badge = document.createElement('span');
        badge.className = 'incident-badge ' + (inc.ongoing ? 'badge-ongoing' : 'badge-resolved');
        badge.textContent = inc.ongoing ? 'Ongoing' : 'Resolved';

        var title = document.createElement('p');
        title.className = 'incident-title';
        title.textContent = inc.title;

        var meta = document.createElement('p');
        meta.className = 'incident-meta';
        meta.textContent = inc.ongoing
          ? 'Started ' + fmtDate(inc.started_at)
          : fmtDate(inc.started_at) + (inc.duration ? ' · Duration: ' + inc.duration : '');

        var info = document.createElement('div');
        info.appendChild(title);
        info.appendChild(meta);
        wrap.appendChild(badge);
        wrap.appendChild(info);
        incList.appendChild(wrap);
      });
    }

    if (timer) clearTimeout(timer);
    timer = setTimeout(load, up ? POLL_UP : POLL_DOWN);
  }

  function load() {
    fetch('/api/status.php?v=' + Date.now())
      .then(function (r) { return r.json(); })
      .then(render)
      .catch(function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(load, POLL_DOWN);
      });
  }

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { clearTimeout(timer); load(); }
  });

  load();
})();
</script>
</body>
</html>
