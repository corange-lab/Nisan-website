// /b/js/table.js
(function(){
  'use strict';
  const { API,$,esc,idSafe,normOnu,getJSON,fmtMBGB,fmtBytesNice,animateNumber,setBarsLevel } = window.App;

  const tbody = $('#body'), notes = $('#notes');
  let allRows = [];
  let tracking = (localStorage.getItem('tracking')!=='off'); // default ON
  let timers = { sample:null, agg:null };

  function clearTimers(){
    if (timers.sample){ clearInterval(timers.sample); timers.sample=null; }
    if (timers.agg){ clearInterval(timers.agg); timers.agg=null; }
  }
  function startTracking(){
    if (timers.sample||timers.agg) return;
    timers.sample=setInterval(()=>{ sampleAll().then(refreshNowFromServer).catch(()=>{}); },3000);
    timers.agg=setInterval(refreshTodayUsageAndMax,15000);
    $('#net_status').textContent='Realtime stream';
  }
  function stopTracking(){ clearTimers(); $('#net_status').textContent='Tracking paused'; }

  function rowHTML(r){
    const safe=idSafe(r.onuid);
    return (
      `<td class="mono"><span id="dot-${safe}" class="status-dot"></span>${esc(r.onuid)}</td>`+
      `<td>${esc(r.pon!=null?String(r.pon):'')}</td>`+
      `<td class="num">${esc(fmtMBGB(r.input_bytes))}</td>`+
      `<td class="num">${esc(fmtMBGB(r.output_bytes))}</td>`+
      `<td class="num"><span class="now-num" id="nowv-${safe}" data-val="0">—</span> Mbps
         <span class="bars" id="bars-${safe}"><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span></span></td>`+
      `<td class="num" id="usage-${safe}">—</td>`+
      `<td class="num" id="max-${safe}">— Mbps</td>`
    );
  }
  function renderRows(rows){
    tbody.innerHTML=''; rows.forEach(r=>{ const tr=document.createElement('tr'); tr.innerHTML=rowHTML(r); tbody.appendChild(tr); });
  }

  function loadTable(){
    notes.innerHTML='<span class="skeleton">Measuring ONUs…</span>';
    getJSON(API+'stats_all.php').then(j=>{
      if(!j.ok) throw new Error(j.error||'failed');
      const rows=(j.rows||[]).slice();
      rows.sort((a,b)=>{
        const A=/^GPON\d+\/(\d+):(\d+)/i.exec(a.onuid||'')||[null,0,0];
        const B=/^GPON\d+\/(\d+):(\d+)/i.exec(b.onuid||'')||[null,0,0];
        if(+A[1]!==+B[1]) return +A[1]-+B[1]; return +A[2]-+B[2];
      });
      allRows=rows; renderRows(rows);
      notes.textContent='Loaded '+rows.length+' ONUs. “Now / Today / Max” update automatically.';
      refreshNowFromServer(); refreshTodayUsageAndMax();
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
        const nowValEl = document.getElementById('nowv-'+safe);
        const barsElId = 'bars-'+safe;
        const dotEl = document.getElementById('dot-'+safe);
        if(nowValEl) animateNumber(nowValEl, rec.total_mbps||0);
        setBarsLevel(barsElId, rec.level||0);
        if(dotEl) dotEl.classList.toggle('on', !!rec.online);
        sumUp   += +rec.upload_mbps  || 0;
        sumDown += +rec.download_mbps|| 0;
      });
      animateNumber(document.getElementById('net_up_val'),   sumUp);
      animateNumber(document.getElementById('net_down_val'), sumDown);
      animateNumber(document.getElementById('net_total_val'), sumUp+sumDown);
      $('#net_dt').textContent  = j.dt_sec;
      $('#net_when').textContent= new Date(j.ts_curr*1000).toLocaleString();
    }).catch(()=>{});
  }

  // Accept both {rows:[...]} and {by_onuid:{...}} shapes
  function normalizeUsageMap(resp){
    const map = {};
    if (!resp) return map;

    if (resp.by_onuid && typeof resp.by_onuid === 'object'){
      for (const k in resp.by_onuid){
        const v = resp.by_onuid[k] || {};
        map[normOnu(k)] = {
          onuid: normOnu(k),
          download_bytes: Number(v.download_bytes ?? v.d ?? 0),
          upload_bytes:   Number(v.upload_bytes   ?? v.u ?? 0)
        };
      }
    }
    const rows = resp.rows || [];
    if (Array.isArray(rows)){
      for (const it of rows){
        if (!it) continue;
        const key = normOnu(it.onuid || it.onuid_norm || it.onuid_raw || '');
        if (!key) continue;
        map[key] = {
          onuid: key,
          download_bytes: Number(it.download_bytes ?? it.d ?? 0),
          upload_bytes:   Number(it.upload_bytes   ?? it.u ?? 0)
        };
      }
    } else if (typeof rows === 'object'){
      for (const k in rows){
        const v = rows[k] || {};
        map[normOnu(k)] = {
          onuid: normOnu(k),
          download_bytes: Number(v.download_bytes ?? v.d ?? 0),
          upload_bytes:   Number(v.upload_bytes   ?? v.u ?? 0)
        };
      }
    }
    return map;
  }

  function refreshTodayUsageAndMax(){
    const date = $('#chart_date') ? ($('#chart_date').value || new Date().toISOString().slice(0,10)) : new Date().toISOString().slice(0,10);
    const p1 = getJSON(API+'day_usage_all.php?date='+encodeURIComponent(date)+'&tz=Asia%2FKolkata').catch(()=>({}));
    const p2 = getJSON(API+'day_stats_all.php?date='+encodeURIComponent(date)).catch(()=>({}));

    Promise.all([p1,p2]).then(([u,m])=>{
      const usage = normalizeUsageMap(u);
      const maxes = (m && m.rows) ? m.rows : {};

      allRows.forEach(r=>{
        const safe=idSafe(r.onuid);
        const key = normOnu(r.onuid);
        const U = usage[key];
        const M = maxes[key] || maxes[r.onuid];

        const usageEl = document.getElementById('usage-'+safe);
        const maxEl   = document.getElementById('max-'+safe);

        if (usageEl){
          if (U){
            const dTxt = fmtBytesNice(U.download_bytes) + ' (D)';
            const uTxt = fmtBytesNice(U.upload_bytes)   + ' (U)';
            usageEl.textContent = `${dTxt} | ${uTxt}`;
          } else {
            usageEl.textContent = '—';
          }
        }
        if (maxEl){
          maxEl.textContent = (M && isFinite(M.max_total_mbps)) ? (Number(M.max_total_mbps).toFixed(2)+' Mbps') : '— Mbps';
        }
      });
    });
  }

  // Tracking toggle
  const trackToggle=$('#track_toggle');
  if (trackToggle){
    trackToggle.checked=tracking;
    trackToggle.addEventListener('change', function(){
      tracking = !!this.checked;
      localStorage.setItem('tracking', tracking ? 'on':'off');
      fetch(API+'tracking_set.php?on='+(tracking?1:0),{cache:'no-store'}).catch(()=>{});
      if (tracking) startTracking(); else stopTracking();
    });
  }

  // Boot
  document.addEventListener('DOMContentLoaded', function(){
    window.App.loadPeaks && window.App.loadPeaks();
    window.App.loadChart && window.App.loadChart();
    loadTable();
    setInterval(()=>{ if(tracking && $('#chart_refresh')) $('#chart_refresh').click(); }, 60000);
  });

  // Expose
  window.App.refreshTodayUsageAndMax = refreshTodayUsageAndMax;
})();
