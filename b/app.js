(function(){
  'use strict';
  var API = (window.API_BASE||'./api/').replace(/\/+$/,'')+'/';

  // ===== helpers =====
  const $ = s => document.querySelector(s);
  const esc = s => (s==null?'':String(s)).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
  const idSafe = s => (s||'').replace(/[^\w\-]+/g,'_');
  const nowSec = ()=> Math.floor(Date.now()/1000);
  function getJSON(u,o){
    const url = u + (u.indexOf('?')>=0?'&':'?') + '_t=' + Date.now(); // Safari cache-bust
    return fetch(url, Object.assign({cache:'no-store'}, o||{})).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); });
  }
  function fmtMBGB(v){ if(v==null||v==='')return'NULL'; const n=+v; if(!isFinite(n))return String(v); const mb=n/1048576; return mb>=1024?(mb/1024).toFixed(2)+' GB':mb.toFixed(2)+' MB'; }
  const fMbps = x => (x==null||!isFinite(x))?'— Mbps':Number(x).toFixed(2)+' Mbps';
  const parsePort = id => { const m=/^GPON\d+\/(\d+):(\d+)/i.exec(id||''); return m?{port:+m[1]||0, onu:+m[2]||0}:{port:0,onu:0}; };
  const bars = lvl => { let h='<span class="bars">'; for(let i=1;i<=5;i++) h+=`<span class="bar ${i<=lvl?'on':''}"></span>`; return h+'</span>'; };

  // ===== global state / timers =====
  let tracking = (localStorage.getItem('tracking')!=='off'); // default ON
  let timers = { sample:null, avg:null };
  function clearTimers(){ if(timers.sample){ clearInterval(timers.sample); timers.sample=null; } if(timers.avg){ clearInterval(timers.avg); timers.avg=null; } }
  function startTracking(){
    if (timers.sample || timers.avg) return; // already running
    timers.sample = setInterval(()=>{ sampleAll().then(refreshNowFromServer).catch(()=>{}); }, 3000);
    timers.avg    = setInterval(refreshTodayAvgMax, 15000);
    $('#net_status').textContent='Realtime stream';
  }
  function stopTracking(){
    clearTimers();
    $('#net_status').textContent='Tracking paused';
  }

  // ===== peaks =====
  function loadPeaks(){
    return getJSON(API+'network_peaks.php').then(j=>{
      if(!j.ok) return;
      const upd=(k,rec)=>{ let V=$('#pk_'+k), T=$('#pk_'+k+'_t'); if(!V||!T)return;
        if(!rec||!rec.has_data){ V.textContent='— Mbps'; T.textContent='—'; return; }
        V.textContent = Number(rec.total_mbps).toFixed(2)+' Mbps';
        T.textContent = new Date(rec.ts_curr*1000).toLocaleString()+' (Δ '+rec.dt_sec+'s)';
      };
      upd('24h', j.peaks['24h']); upd('7d', j.peaks['7d']); upd('30d', j.peaks['30d']);
    }).catch(()=>{});
  }

  // ===== table =====
  const tbody = $('#body'), notes = $('#notes'); let allRows = [];

  function rowHTML(r){
    const safe=idSafe(r.onuid);
    return (
      `<td class="mono"><span id="dot-${safe}" class="status-dot"></span>${esc(r.onuid)}</td>`+
      `<td>${esc(r.pon!=null?String(r.pon):'')}</td>`+
      `<td class="num">${esc(fmtMBGB(r.input_bytes))}</td>`+
      `<td class="num">${esc(fmtMBGB(r.output_bytes))}</td>`+
      `<td class="num" id="now-${safe}">— Mbps ${bars(0)}</td>`+
      `<td class="num" id="avg-${safe}">— Mbps</td>`+
      `<td class="num" id="max-${safe}">— Mbps</td>`+
      `<td class="num" id="test-${safe}">
        <button class="btn-test" data-onu="${esc(r.onuid)}" data-secs="10" style="margin-right:6px">Test 10s</button>
        <button class="btn-test" data-onu="${esc(r.onuid)}" data-secs="30">Test 30s</button>
      </td>`
    );
  }

  function renderRows(rows){
    tbody.innerHTML='';
    rows.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML = rowHTML(r);
      tbody.appendChild(tr);
    });
  }

  function loadTable(){
    notes.innerHTML='<span class="skeleton">Measuring ONUs…</span>';
    getJSON(API+'stats_all.php').then(j=>{
      if(!j.ok) throw new Error(j.error||'failed');
      const rows=(j.rows||[]).slice();
      rows.sort((a,b)=>{
        const pa=(+a.pon||0), pb=(+b.pon||0); if(pa!==pb) return pa-pb;
        const aa=parsePort(a.onuid), bb=parsePort(b.onuid);
        if(aa.port!==bb.port) return aa.port-bb.port; return aa.onu-bb.onu;
      });
      allRows=rows; renderRows(rows);
      notes.textContent='Loaded '+rows.length+' ONUs. “Now / Avg / Max” update automatically.';
      // first paint
      refreshNowFromServer(); refreshTodayAvgMax();
      // tracking timers
      if (tracking) startTracking(); else stopTracking();
    }).catch(e=>{ notes.textContent='Error: '+e.message; });
  }

  function sampleAll(){ return getJSON(API+'network_sample.php').catch(()=>({ok:false})); }
  function refreshNowFromServer(){
    return getJSON(API+'online_now_all.php').then(j=>{
      if(!j.ok || !j.has_data) return;
      let sumUp=0, sumDown=0;
      Object.keys(j.rows).forEach(id=>{
        const safe=idSafe(id), rec=j.rows[id];
        const nowEl = $('#now-'+safe), dotEl=$('#dot-'+safe);
        if(nowEl) nowEl.innerHTML = fMbps(rec.total_mbps)+' '+bars(rec.level);
        if(dotEl) dotEl.classList.toggle('on', !!rec.online);
        sumUp   += +rec.upload_mbps  || 0;
        sumDown += +rec.download_mbps|| 0;
      });
      $('#net_up').innerHTML    = esc(sumUp.toFixed(2))   +'<span class="unit">Mbps</span>';
      $('#net_down').innerHTML  = esc(sumDown.toFixed(2)) +'<span class="unit">Mbps</span>';
      $('#net_total').innerHTML = esc((sumUp+sumDown).toFixed(2))+'<span class="unit">Mbps</span>';
      $('#net_dt').textContent  = j.dt_sec;
      $('#net_when').textContent= new Date(j.ts_curr*1000).toLocaleString();
    }).catch(()=>{});
  }
  function refreshTodayAvgMax(){
    const date = $('#chart_date') ? ($('#chart_date').value || new Date().toISOString().slice(0,10)) : new Date().toISOString().slice(0,10);
    getJSON(API+'day_stats_all.php?date='+encodeURIComponent(date)).then(j=>{
      if(!j.ok) return; const map=j.rows||{};
      allRows.forEach(r=>{
        const safe=idSafe(r.onuid); const st=map[r.onuid];
        const avgEl=$('#avg-'+safe), maxEl=$('#max-'+safe);
        if(avgEl) avgEl.textContent = st ? fMbps(st.avg_total_mbps) : '— Mbps';
        if(maxEl) maxEl.textContent = st ? fMbps(st.max_total_mbps) : '— Mbps';
      });
    }).catch(()=>{});
  }

  // ===== per-ONU test (10s / 30s) =====
  let testLock = false; // allow one test at a time to keep last-2-snapshots clean
  function disableRowButtons(onuid, disabled){
    const sel = `.btn-test[data-onu="${CSS.escape(onuid)}"]`;
    document.querySelectorAll(sel).forEach(b=>{ b.disabled = !!disabled; });
  }
  function runSingleTest(onuid, secs){
    if (testLock) return;
    testLock = true;
    const safe=idSafe(onuid);
    const nowCell = $('#now-'+safe);
    const testCell = $('#test-'+safe);
    const wasTracking = tracking;

    // pause global tracking during test to make last-two-snapshots accurate
    if (wasTracking) { tracking=false; localStorage.setItem('tracking','off'); stopTracking(); if($('#track_toggle')) $('#track_toggle').checked=false; }

    disableRowButtons(onuid,true);
    let remain = secs;
    if (testCell) testCell.dataset.originalHTML = testCell.innerHTML;
    if (nowCell) nowCell.innerHTML = `Testing… ${remain}s`;

    function tickUI(){
      if (nowCell) nowCell.textContent = `Testing… ${remain}s`;
    }

    // 1) baseline snapshot
    sampleAll().then(()=>{
      return new Promise(res=>{
        const t = setInterval(()=>{
          remain--; tickUI();
          if (remain<=0){ clearInterval(t); res(); }
        }, 1000);
      });
    }).then(()=>{
      // 2) end snapshot
      return sampleAll();
    }).then(()=>{
      // 3) compute for that single ONU
      return getJSON(API+'online_now_single.php?onuid='+encodeURIComponent(onuid));
    }).then(j=>{
      if (j && j.ok && j.has_data){
        const lvl = j.level||0;
        if (nowCell) nowCell.innerHTML = fMbps(j.total_mbps)+' '+bars(lvl)+' <span style="color:#9bb0e4;font-size:11px;margin-left:6px">(test '+secs+'s)</span>';
        const dotEl=$('#dot-'+safe); if (dotEl) dotEl.classList.toggle('on', (j.total_mbps>0));
      } else {
        if (nowCell) nowCell.textContent = 'Test failed';
      }
    }).catch(()=>{
      if (nowCell) nowCell.textContent = 'Test error';
    }).finally(()=>{
      disableRowButtons(onuid,false);
      // restore tracking state
      if (wasTracking){ tracking=true; localStorage.setItem('tracking','on'); startTracking(); if($('#track_toggle')) $('#track_toggle').checked=true; }
      testLock = false;
    });
  }

  // Delegate clicks for test buttons
  tbody.addEventListener('click', function(e){
    const btn = e.target && e.target.classList && e.target.classList.contains('btn-test') ? e.target : null;
    if (!btn) return;
    const onuid = btn.getAttribute('data-onu');
    const secs  = parseInt(btn.getAttribute('data-secs')||'10',10);
    runSingleTest(onuid, secs);
  });

  // ===== Chart bits already exist in your page; we leave as-is =====
  // (app.js from earlier already handles chart timeframe/zoom)

  // ===== Tracking toggle wiring =====
  const trackToggle = $('#track_toggle');
  if (trackToggle){
    trackToggle.checked = tracking;
    trackToggle.addEventListener('change', function(){
      tracking = !!this.checked;
      localStorage.setItem('tracking', tracking ? 'on':'off');
      if (tracking) startTracking(); else stopTracking();
    });
  }

  // ===== Boot =====
  loadPeaks();
  loadTable();

  // keep chart light refresh (if present)
  if ($('#chart_refresh')) setInterval(()=>{ if(tracking) { const btn=$('#chart_refresh'); if(btn) btn.click(); } }, 60000);
})();
