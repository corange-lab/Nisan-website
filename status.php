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
    <meta property="og:site_name" content="Nisan Cable &amp; Internet">
    <meta property="og:locale" content="en_IN">

    <?php include('common-css.php'); ?>

    <style>
    /* ── Status page ──────────────────────────────────────────── */
    .status-page { background: #f4f5f8; min-height: 80vh; padding-bottom: 72px; }

    /* Hero */
    .status-hero { padding: 52px 0 36px; background: #fff; border-bottom: 1px solid #e3e5ec; }
    .status-indicator-wrap { display: flex; align-items: center; gap: 20px; }
    .status-dot {
      width: 24px; height: 24px; border-radius: 50%;
      background: #d1d5db; flex-shrink: 0; transition: background .4s;
    }
    .status-dot.up   { background: #22c55e; box-shadow: 0 0 0 6px rgba(34,197,94,.15); }
    .status-dot.down { background: #ef4444; animation: spulse 1.4s ease infinite; }
    @keyframes spulse {
      0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.55); }
      70%      { box-shadow: 0 0 0 16px rgba(239,68,68,0); }
    }
    .status-headline { font-size: 2rem; font-weight: 700; margin: 0; color: #1a1a2e; }
    .status-sub      { margin: 5px 0 0; color: #6b7280; font-size: .96rem; }

    /* Stats */
    .status-stats-bar  { background: #fff; padding: 26px 0; border-bottom: 1px solid #e3e5ec; }
    .status-stats-grid { display: flex; gap: 14px; flex-wrap: wrap; }
    .stat-card {
      background: #f8f9fc; border: 1px solid #e3e5ec; border-radius: 12px;
      padding: 18px 24px; flex: 1; min-width: 140px; text-align: center;
    }
    .stat-val   { display: block; font-size: 1.55rem; font-weight: 700; color: #1a1a2e; font-variant-numeric: tabular-nums; }
    .stat-label { display: block; font-size: .75rem; color: #6b7280; margin-top: 4px; text-transform: uppercase; letter-spacing: .06em; }
    /* Countdown card */
    .stat-card.countdown-card { cursor: default; }
    .countdown-wrap { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .countdown-num  { font-size: 1.55rem; font-weight: 700; color: #1a1a2e; font-variant-numeric: tabular-nums; min-width: 3ch; text-align: right; }
    .countdown-sep  { font-size: 1.1rem; color: #9ca3af; margin-bottom: 2px; }
    .countdown-sec  { font-size: 1.55rem; font-weight: 700; color: #6366f1; font-variant-numeric: tabular-nums; min-width: 2ch; text-align: left; }

    /* Sections */
    .status-section       { padding: 40px 0 0; }
    .status-section-title { font-size: .9rem; font-weight: 700; color: #1a1a2e; margin: 0 0 16px; text-transform: uppercase; letter-spacing: .08em; }

    /* Bar shared styles */
    .bar-row     { display: flex; gap: 2px; height: 36px; align-items: stretch; }
    .bar-seg {
      flex: 1; border-radius: 3px; position: relative; cursor: pointer; transition: opacity .15s;
    }
    .bar-seg:hover { opacity: .72; }
    .bar-seg.up      { background: #22c55e; }
    .bar-seg.partial { background: #f59e0b; }
    .bar-seg.down    { background: #ef4444; }
    .bar-seg.no-data { background: #e5e7eb; cursor: default; }
    .bar-seg .seg-tip {
      display: none; position: absolute; bottom: calc(100% + 7px); left: 50%;
      transform: translateX(-50%); background: #1a1a2e; color: #fff;
      font-size: .72rem; padding: 5px 10px; border-radius: 7px;
      white-space: nowrap; z-index: 30; pointer-events: none;
      box-shadow: 0 2px 8px rgba(0,0,0,.18);
    }
    .bar-seg .seg-tip::after {
      content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
      border: 5px solid transparent; border-top-color: #1a1a2e;
    }
    .bar-seg:hover .seg-tip { display: block; }
    .bar-labels { display: flex; justify-content: space-between; font-size: .75rem; color: #9ca3af; margin-top: 6px; }

    .uptime-legend { display: flex; align-items: center; gap: 14px; font-size: .8rem; color: #6b7280; flex-wrap: wrap; margin-top: 10px; }
    .legend-dot    { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 3px; vertical-align: middle; }
    .l-up      { background: #22c55e; }
    .l-partial { background: #f59e0b; }
    .l-down    { background: #ef4444; }
    .l-nodata  { background: #e5e7eb; }

    /* Day drill-down panel */
    .day-detail-panel {
      margin-top: 14px; background: #fff; border: 1px solid #e3e5ec;
      border-radius: 14px; padding: 22px 24px; display: none;
    }
    .day-detail-panel.open { display: block; }
    .day-detail-title { font-weight: 700; font-size: 1rem; color: #1a1a2e; margin: 0 0 14px; }
    .day-detail-close { float: right; cursor: pointer; color: #6b7280; font-size: 1.2rem; line-height: 1; }
    .hour-bars { display: flex; gap: 2px; height: 28px; align-items: stretch; margin-bottom: 6px; }
    .hour-bar-labels { display: flex; justify-content: space-between; font-size: .7rem; color: #9ca3af; }
    .day-incidents-title { font-size: .8rem; font-weight: 700; color: #374151; margin: 16px 0 8px; text-transform: uppercase; letter-spacing: .06em; }
    .day-no-incidents { font-size: .85rem; color: #6b7280; }

    /* Incidents */
    .incident-list { display: flex; flex-direction: column; gap: 10px; }
    .incident-item {
      background: #fff; border: 1px solid #e3e5ec; border-left: 4px solid #e3e5ec;
      border-radius: 12px; padding: 16px 20px;
    }
    .incident-item.ongoing  { border-left-color: #ef4444; }
    .incident-item.resolved { border-left-color: #22c55e; }
    .inc-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
    .incident-badge {
      font-size: .68rem; font-weight: 700; padding: 3px 9px;
      border-radius: 99px; white-space: nowrap; flex-shrink: 0;
    }
    .badge-ongoing  { background: #fee2e2; color: #b91c1c; }
    .badge-resolved { background: #dcfce7; color: #15803d; }
    .inc-title { font-weight: 700; color: #1a1a2e; font-size: .95rem; }
    .inc-timeline {
      display: flex; flex-direction: column; gap: 4px;
      margin-top: 6px; padding-left: 2px;
    }
    .inc-row { font-size: .82rem; color: #4b5563; display: flex; align-items: baseline; gap: 8px; }
    .inc-row-label { color: #9ca3af; font-size: .75rem; min-width: 60px; }
    .inc-dur-pill {
      display: inline-block; background: #fef3c7; color: #92400e;
      font-size: .75rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; margin-top: 6px;
    }
    .inc-dur-pill.long { background: #fee2e2; color: #991b1b; }

    .no-incidents {
      background: #fff; border: 1px solid #e3e5ec; border-radius: 12px;
      padding: 28px 24px; text-align: center; color: #6b7280; font-size: .9rem;
    }
    .ni-icon { font-size: 1.5rem; display: block; margin-bottom: 8px; }

    /* Checking spinner */
    .status-loading { color: #9ca3af; font-size: .88rem; }
    .spin { display: inline-block; animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 576px) {
      .status-headline { font-size: 1.35rem; }
      .bar-row  { height: 26px; }
      .stat-val, .countdown-num, .countdown-sec { font-size: 1.25rem; }
      .stat-card { padding: 14px 16px; }
      .day-detail-panel { padding: 16px; }
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
        <div class="stat-card countdown-card">
          <div class="countdown-wrap">
            <span class="countdown-num" id="cdMins">—</span>
            <span class="countdown-sep">:</span>
            <span class="countdown-sec" id="cdSecs">--</span>
          </div>
          <span class="stat-label">Next check in</span>
        </div>
      </div>
    </div>
  </section>

  <!-- 24-hour graph -->
  <section class="status-section">
    <div class="container">
      <h2 class="status-section-title">Last 24 Hours</h2>
      <div class="bar-row" id="bars24"></div>
      <div class="bar-labels">
        <span id="bar24From"></span>
        <span>Now</span>
      </div>
      <div class="uptime-legend">
        <span class="legend-dot l-up"></span>Operational&nbsp;
        <span class="legend-dot l-partial"></span>Partial&nbsp;
        <span class="legend-dot l-down"></span>Outage&nbsp;
        <span class="legend-dot l-nodata"></span>No data
      </div>
    </div>
  </section>

  <!-- 30-day graph -->
  <section class="status-section">
    <div class="container">
      <h2 class="status-section-title">30-Day Availability <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.78rem;color:#9ca3af;margin-left:6px;">Click any day for hourly detail</span></h2>
      <div class="bar-row" id="bars30d"></div>
      <div class="bar-labels">
        <span id="bar30From"></span>
        <span>Today</span>
      </div>
      <div class="uptime-legend">
        <span class="legend-dot l-up"></span>Operational&nbsp;
        <span class="legend-dot l-partial"></span>Partial&nbsp;
        <span class="legend-dot l-down"></span>Outage&nbsp;
        <span class="legend-dot l-nodata"></span>No data
      </div>
      <!-- Day drill-down -->
      <div class="day-detail-panel" id="dayDetailPanel">
        <span class="day-detail-close" id="dayDetailClose" title="Close">✕</span>
        <p class="day-detail-title" id="dayDetailTitle"></p>
        <div class="hour-bars" id="hourBars"></div>
        <div class="hour-bar-labels">
          <span>12 AM</span><span>6 AM</span><span>12 PM</span><span>6 PM</span><span>11 PM</span>
        </div>
        <p class="day-incidents-title">Incidents on this day</p>
        <div id="dayIncidents"></div>
      </div>
    </div>
  </section>

  <!-- Incidents -->
  <section class="status-section">
    <div class="container">
      <h2 class="status-section-title">Incident History</h2>
      <div id="incidentList" class="incident-list">
        <p class="status-loading"><span class="spin">⟳</span> Loading…</p>
      </div>
    </div>
  </section>

</main>

<?php include('footer.php'); ?>

<script src="/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/main.js"></script>

<script>
(function () {
  /* ── constants ─────────────────────────────────────────── */
  var POLL_UP   = 180; // seconds
  var POLL_DOWN = 30;

  /* ── state ──────────────────────────────────────────────── */
  var pollTimer     = null;
  var cdTimer       = null;
  var cdRemaining   = 0;
  var nextPollSecs  = POLL_UP;
  var lastCheckedAt = 0;

  /* ── DOM ────────────────────────────────────────────────── */
  var dot          = document.getElementById('statusDot');
  var hdln         = document.getElementById('statusHeadline');
  var sub          = document.getElementById('statusSub');
  var s30          = document.getElementById('stat30d');
  var sMs          = document.getElementById('statMs');
  var cdMins       = document.getElementById('cdMins');
  var cdSecs       = document.getElementById('cdSecs');
  var bars24el     = document.getElementById('bars24');
  var bars30el     = document.getElementById('bars30d');
  var bar24From    = document.getElementById('bar24From');
  var bar30From    = document.getElementById('bar30From');
  var incListEl    = document.getElementById('incidentList');
  var dayPanel     = document.getElementById('dayDetailPanel');
  var dayTitle     = document.getElementById('dayDetailTitle');
  var hourBarsEl   = document.getElementById('hourBars');
  var dayIncEl     = document.getElementById('dayIncidents');
  var dayClose     = document.getElementById('dayDetailClose');

  dayClose.addEventListener('click', function () { dayPanel.classList.remove('open'); });

  /* ── helpers ─────────────────────────────────────────────── */
  function pad2(n) { return n < 10 ? '0' + n : '' + n; }

  function segClass(pct, total) {
    if (!total) return 'no-data';
    if (pct >= 99) return 'up';
    if (pct >= 50) return 'partial';
    return 'down';
  }

  function makeBar(cls, tipText, onClick) {
    var b = document.createElement('div');
    b.className = 'bar-seg ' + cls;
    if (tipText) {
      var t = document.createElement('div');
      t.className = 'seg-tip';
      t.textContent = tipText;
      b.appendChild(t);
    }
    if (onClick) b.addEventListener('click', onClick);
    return b;
  }

  function shortDate(str) {
    var d = new Date(str + 'T00:00:00');
    return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
  }

  /* ── countdown ───────────────────────────────────────────── */
  function startCountdown(seconds) {
    cdRemaining = seconds;
    if (cdTimer) clearInterval(cdTimer);
    tickCountdown();
    cdTimer = setInterval(tickCountdown, 1000);
  }

  function tickCountdown() {
    if (cdRemaining < 0) cdRemaining = 0;
    var m = Math.floor(cdRemaining / 60);
    var s = cdRemaining % 60;
    cdMins.textContent = m;
    cdSecs.textContent = pad2(s);
    if (cdRemaining > 0) cdRemaining--;
  }

  /* ── render 24h bars ─────────────────────────────────────── */
  function render24(hours24) {
    bars24el.innerHTML = '';
    if (!hours24 || !hours24.length) return;
    // label: 24 hours ago
    bar24From.textContent = '24h ago';
    hours24.forEach(function (h) {
      var cls = segClass(h.pct, h.total);
      var tip = !h.total
        ? (h.label + ' · No data')
        : (h.label + ' · ' + (h.pct != null ? h.pct.toFixed(0) : '—') + '% uptime'
           + (h.avg_ms ? ' · ' + h.avg_ms + 'ms avg' : ''));
      bars24el.appendChild(makeBar(cls, tip, null));
    });
  }

  /* ── render 30d bars ─────────────────────────────────────── */
  function render30(days) {
    bars30el.innerHTML = '';
    if (!days || !days.length) return;
    bar30From.textContent = shortDate(days[0].date);
    days.forEach(function (day) {
      var cls = segClass(day.pct, day.total);
      var tip = !day.total
        ? (shortDate(day.date) + ' · No data yet')
        : (shortDate(day.date) + ' · ' + (day.pct != null ? day.pct.toFixed(1) : '—') + '% uptime');
      bars30el.appendChild(makeBar(cls, tip, function () { loadDayDetail(day.date); }));
    });
  }

  /* ── day drill-down ──────────────────────────────────────── */
  function loadDayDetail(date) {
    dayPanel.classList.add('open');
    dayTitle.textContent = 'Hourly detail — ' + shortDate(date);
    hourBarsEl.innerHTML = '<span style="color:#9ca3af;font-size:.8rem">Loading…</span>';
    dayIncEl.innerHTML   = '';

    fetch('/api/status.php?action=day&date=' + date + '&v=' + Date.now())
      .then(function (r) { return r.json(); })
      .then(function (d) {
        // Hour bars
        hourBarsEl.innerHTML = '';
        d.hours.forEach(function (h) {
          var cls = segClass(h.pct, h.total);
          var tip = !h.total
            ? (h.label + ' · No data')
            : (h.label + ' · ' + (h.pct != null ? h.pct.toFixed(0) : '—') + '% uptime'
               + (h.avg_ms ? ' · ' + h.avg_ms + 'ms' : ''));
          hourBarsEl.appendChild(makeBar(cls, tip, null));
        });

        // Incidents that day
        if (!d.incidents || !d.incidents.length) {
          dayIncEl.innerHTML = '<p class="day-no-incidents">✅ No incidents on this day.</p>';
        } else {
          dayIncEl.innerHTML = '';
          d.incidents.forEach(function (inc) {
            dayIncEl.appendChild(buildIncidentEl(inc));
          });
        }

        dayPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      })
      .catch(function () {
        hourBarsEl.innerHTML = '<span style="color:#ef4444;font-size:.8rem">Failed to load.</span>';
      });
  }

  /* ── build incident element ─────────────────────────────── */
  function buildIncidentEl(inc) {
    var wrap = document.createElement('div');
    wrap.className = 'incident-item ' + (inc.ongoing ? 'ongoing' : 'resolved');

    var hdr = document.createElement('div');
    hdr.className = 'inc-header';

    var badge = document.createElement('span');
    badge.className = 'incident-badge ' + (inc.ongoing ? 'badge-ongoing' : 'badge-resolved');
    badge.textContent = inc.ongoing ? 'Ongoing' : 'Resolved';

    var title = document.createElement('span');
    title.className = 'inc-title';
    title.textContent = 'Network Outage';

    hdr.appendChild(badge);
    hdr.appendChild(title);

    var timeline = document.createElement('div');
    timeline.className = 'inc-timeline';

    // Started row
    var r1 = document.createElement('div');
    r1.className = 'inc-row';
    r1.innerHTML = '<span class="inc-row-label">Started</span><span>' + (inc.started_fmt || '—') + '</span>';

    // Resolved row
    var r2 = document.createElement('div');
    r2.className = 'inc-row';
    r2.innerHTML = '<span class="inc-row-label">Resolved</span><span>'
      + (inc.ongoing ? '<em style="color:#ef4444">Investigating…</em>' : (inc.resolved_fmt || '—'))
      + '</span>';

    timeline.appendChild(r1);
    timeline.appendChild(r2);

    // Duration pill
    if (inc.duration) {
      var pill = document.createElement('span');
      var isLong = (inc.duration_sec || 0) > 3600;
      pill.className = 'inc-dur-pill' + (isLong ? ' long' : '');
      pill.textContent = '⏱ Down for ' + inc.duration;
      timeline.appendChild(pill);
    }

    wrap.appendChild(hdr);
    wrap.appendChild(timeline);
    return wrap;
  }

  /* ── render incidents ────────────────────────────────────── */
  function renderIncidents(incidents) {
    if (!incidents || !incidents.length) {
      incListEl.innerHTML = '<div class="no-incidents"><span class="ni-icon">✅</span>No incidents recorded yet.</div>';
      return;
    }
    incListEl.innerHTML = '';
    incidents.forEach(function (inc) {
      incListEl.appendChild(buildIncidentEl(inc));
    });
  }

  /* ── main render ─────────────────────────────────────────── */
  function render(d) {
    var up = d.status === 'up';
    lastCheckedAt = d.checked_at || Math.floor(Date.now() / 1000);
    nextPollSecs  = d.next_poll || (up ? POLL_UP : POLL_DOWN);

    // Hero
    dot.className = 'status-dot ' + (up ? 'up' : 'down');
    hdln.textContent = up ? 'All Systems Operational' : 'Network Disruption Detected';
    hdln.style.color = up ? '#15803d' : '#b91c1c';
    sub.textContent  = d.open_incident
      ? 'Outage in progress — started ' + (d.open_incident.started_fmt || '')
      : (up ? 'Nisan Internet is running normally.' : 'Our team is investigating.');

    // Stats
    s30.textContent = d.uptime_30d != null ? d.uptime_30d.toFixed(2) + '%' : '—';
    sMs.textContent = d.response_ms != null ? d.response_ms + ' ms' : '—';

    // Countdown — starts from next_poll seconds
    startCountdown(nextPollSecs);

    // Bars
    render24(d.hours_24);
    render30(d.days);
    renderIncidents(d.incidents);

    // Schedule next fetch
    if (pollTimer) clearTimeout(pollTimer);
    pollTimer = setTimeout(load, nextPollSecs * 1000);
  }

  /* ── fetch ───────────────────────────────────────────────── */
  function load() {
    if (cdTimer) clearInterval(cdTimer);
    cdMins.textContent = '…'; cdSecs.textContent = '--';

    fetch('/api/status.php?v=' + Date.now())
      .then(function (r) { return r.json(); })
      .then(render)
      .catch(function () {
        if (pollTimer) clearTimeout(pollTimer);
        pollTimer = setTimeout(load, POLL_DOWN * 1000);
        startCountdown(POLL_DOWN);
      });
  }

  // Re-fetch immediately on tab focus
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { clearTimeout(pollTimer); load(); }
  });

  load();
})();
</script>
</body>
</html>
