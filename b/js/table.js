// /b/js/table.js
(function(){
  'use strict';
  const { API,$,esc,idSafe,normOnu,getJSON,fmtMBGB,fmtBytesNice,animateNumber,setBarsLevel } = window.App;

  const tbody = $('#body'), notes = $('#notes');
  let allRows = [];
  let tracking = (localStorage.getItem('tracking')!=='off');
  let timers = { sample:null, agg:null };

  function clearTimers(){ if(timers.sample){ clearInterval(timers.sample); timers.sample=null; } if(timers.agg){ clearInterval(timers.agg); timers.agg=null; } }
  function startTracking(){ if(timers.sample||timers.agg) return;
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
      `<td class="num" id="max-${safe}">— Mbps</td>`+
      `<td class="num" id="test-${safe}">
        <button class="btn btn-test" data-onu="${esc(r.onuid)}" data-secs="10" style="margin-right:6px">Test 10s</button>
        <button class="btn btn-test" data-onu="${esc(r.onuid)}" data-secs="30">Test 30s</button>
      </td>`
    );
  }
  function renderRows(rows){ tbody.innerHTML=''; rows.forEach(r=>{ const tr=document.createElement('tr'); tr.innerHTML=rowHTML(r); tbody.appendChild(tr); }); }

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

  // Make usage mapping tolerant to any server shape
  function normalizeUsageMap(resp){
    const map = {};
    if (!resp) return map;

    // 1) preferred: resp.by_onuid
    if (resp.by_onuid && typeof resp.by_onuid === 'object'){
      for (const k in resp.by_onuid){
        const key = normOnu(k);
        const v = resp.by_onuid[k] || {};
        map[key] = {
          onuid: key,
          download_bytes: Number(v.download_bytes ?? v.d ?? 0),
          upload_bytes:   Number(v.upload_bytes   ?? v.u ?? 0)
        };
      }
    }

    // 2) also accept resp.rows as array or map
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
        const key = normOnu(k);
        const v = rows[k] || {};
        map[key] = {
          onuid: key,
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

  // Per-ONU tests (unchanged)
  let testLock=false, testTicker=null;
  function disableRowButtons(onuid, disabled){
    document.querySelectorAll(`.btn-test[data-onu="${CSS.escape(onuid)}"]`).forEach(b=>{ b.disabled=!!disabled; });
  }
  function runSingleTest(onuid, secs){
    if (testLock) return; testLock=true;
    const safe=idSafe(onuid);
    const nowValEl=$('#nowv-'+safe), barsElId='bars-'+safe, dotEl=$('#dot-'+safe), testCell=$('#test-'+safe);
    const wasTracking=tracking;
    if (wasTracking){ tracking=false; localStorage.setItem('tracking','off'); stopTracking(); if($('#track_toggle')) $('#track_toggle').checked=false; }
    disableRowButtons(onuid,true);
    let remain=secs; if (testCell) testCell.dataset.originalHTML=testCell.innerHTML;
    sampleAll().then(()=>{
      testTicker=setInterval(()=>{
        remain--;
        sampleAll()
          .then(()=>getJSON(API+'online_now_single.php?onuid='+encodeURIComponent(onuid)))
          .then(j=>{
            if (j && j.ok && j.has_data){
              if (nowValEl) animateNumber(nowValEl, j.total_mbps||0);
              setBarsLevel(barsElId, j.level||0);
              if (dotEl) dotEl.classList.toggle('on', (j.total_mbps>0));
              if (testCell) testCell.innerHTML = `<button class="btn btn-test" data-onu="${onuid}" data-secs="10" style="margin-right:6px" disabled>Test 10s</button><button class="btn btn-test" data-onu="${onuid}" data-secs="30" disabled>Test 30s</button> <span style="color:#9bb0e4;font-size:11px;margin-left:6px">(${remain}s)</span>`;
            }
          }).catch(()=>{});
        if (remain<=0){
          clearInterval(testTicker); testTicker=null;
          sampleAll().finally(()=>{
            if (testCell) testCell.innerHTML = testCell.dataset.originalHTML || testCell.innerHTML;
            disableRowButtons(onuid,false);
            if (wasTracking){ tracking=true; localStorage.setItem('tracking','on'); startTracking(); if($('#track_toggle')) $('#track_toggle').checked=true; }
            testLock=false;
          });
        }
      },1000);
    }).catch(()=>{
      if (testCell) testCell.innerHTML = testCell.dataset.originalHTML || testCell.innerHTML;
      disableRowButtons(onuid,false);
      if (wasTracking){ tracking=true; localStorage.setItem('tracking','on'); startTracking(); if($('#track_toggle')) $('#track_toggle').checked=true; }
      testLock=false;
    });
  }

  $('#body').addEventListener('click', function(e){
    const btn = e.target && e.target.classList && e.target.classList.contains('btn-test') ? e.target : null;
    if (!btn) return;
    const onuid = btn.getAttribute('data-onu'); const secs = parseInt(btn.getAttribute('data-secs')||'10',10);
    runSingleTest(onuid, secs);
  });

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

  document.addEventListener('DOMContentLoaded', function(){
    loadTable();
    setInterval(()=>{ if(tracking && $('#chart_refresh')) $('#chart_refresh').click(); }, 60000);
  });

  window.App.refreshTodayUsageAndMax = refreshTodayUsageAndMax;
})();
