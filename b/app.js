(function(){
  'use strict';
  var API_BASE = (window.API_BASE ? String(window.API_BASE) : './api/').replace(/\/+$/,'') + '/';

  // ---------- helpers ----------
  var $ = s => document.querySelector(s);
  function esc(s){ s=(s==null?'':String(s)); return s.replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }
  function idSafe(s){ return (s||'').replace(/[^\w\-]+/g,'_'); }
  function getJSON(url, opts){ return fetch(url,opts||{cache:'no-store'}).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); }); }
  function fmtMBGB(val){ if(val==null||val==='')return'NULL'; var n=+val; if(!isFinite(n))return String(val); var mb=n/1048576; return mb>=1024?(mb/1024).toFixed(2)+' GB':mb.toFixed(2)+' MB'; }
  function fMbps(x){ return (x==null||!isFinite(x)) ? '— Mbps' : Number(x).toFixed(2)+' Mbps'; }
  function parsePort(id){ var m=/^GPON\d+\/(\d+):(\d+)/i.exec(id||''); return m?{port:+m[1]||0,onu:+m[2]||0}:{port:0,onu:0}; }
  function bars(level){ var html='<span class="bars">'; for(let i=1;i<=5;i++) html+=`<span class="bar ${i<=level?'on':''}"></span>`; return html+'</span>'; }

  // ---------- Network hero (variable 10s/30s) ----------
  var state={loop:true, windowSec:30, cd:30, cdTimer:null, dotsTimer:null};
  var el={ cd:$('#net_cd'), dt:$('#net_dt'), when:$('#net_when'),
           total:$('#net_total'), up:$('#net_up'), down:$('#net_down'),
           status:$('#net_status'), tlen:$('#test_len'),
           btn10:$('#btn_10s'), btn30:$('#btn_30s') };

  function setStatusMeasuring(){ let i=0; clearInterval(state.dotsTimer); el.status.textContent='Measuring… running test'; state.dotsTimer=setInterval(()=>{ i=(i+1)%4; el.status.textContent='Measuring'+'.'.repeat(i)+' running test'; },600); }
  function setStatusReady(){ clearInterval(state.dotsTimer); el.status.textContent='Latest result ready'; }

  function networkSample(){ return getJSON(API_BASE+'network_sample.php').catch(()=>({ok:false})); }
  function networkSpeed(win){ // win: {min,max}
    var qs = win ? (`?min=${encodeURIComponent(win.min)}&max=${encodeURIComponent(win.max)}`) : '';
    return getJSON(API_BASE+'network_speed_from_last.php'+qs);
  }
  function networkPeaks(){
    return getJSON(API_BASE+'network_peaks.php').then(j=>{
      if(!j.ok) return;
      function upd(key,rec){ let v=$(`#pk_${key}`), t=$(`#pk_${key}_t`); if(!v||!t)return;
        if(!rec || !rec.has_data){ v.textContent='— Mbps'; t.textContent='—'; return; }
        v.textContent = Number(rec.total_mbps).toFixed(2)+' Mbps';
        t.textContent = new Date(rec.ts_curr*1000).toLocaleString()+' (Δ '+rec.dt_sec+'s)';
      }
      upd('24h', j.peaks['24h']); upd('7d', j.peaks['7d']); upd('30d', j.peaks['30d']);
    }).catch(()=>{});
  }
  function updateHero(j){
    if(!j||!j.ok||!j.has_data){
      el.dt.textContent='—'; el.when.textContent='—';
      el.total.innerHTML='—<span class="unit">Mbps</span>';
      el.up.innerHTML='—<span class="unit">Mbps</span>';
      el.down.innerHTML='—<span class="unit">Mbps</span>';
      el.status.textContent='Waiting for two snapshots…';
      return;
    }
    el.dt.textContent = j.dt_sec;
    el.when.textContent = new Date(j.ts_curr*1000).toLocaleString();
    el.total.innerHTML = esc(Number(j.total_mbps).toFixed(2))+'<span class="unit">Mbps</span>';
    el.up.innerHTML    = esc(Number(j.upload_mbps).toFixed(2))  +'<span class="unit">Mbps</span>';
    el.down.innerHTML  = esc(Number(j.download_mbps).toFixed(2))+'<span class="unit">Mbps</span>';
    setStatusReady();
  }
  function startCountdown(){
    clearInterval(state.cdTimer);
    state.cd = state.windowSec;
    el.cd.textContent = state.cd;
    setStatusMeasuring();
    state.cdTimer = setInterval(()=>{
      state.cd -= 1; if (state.cd<0) state.cd=0;
      el.cd.textContent = state.cd;
      if (state.cd===0){
        clearInterval(state.cdTimer); state.cdTimer=null;
        // second sample → compute → if loop, chain into next window
        var is10s = (state.windowSec===10);
        var rg = is10s ? {min:8,max:20} : {min:25,max:60};
        networkSample().then(()=>networkSpeed(rg)).then(updateHero).finally(()=>{
          // Immediately prime next window with a fresh sample
          if (state.loop){
            // keep 30s loop always
            state.windowSec = 30; el.tlen.textContent='30s';
            networkSample().finally(startCountdown);
          } else {
            // one-shot (10s): exit measuring text, restore 30s loop
            setStatusReady();
            state.windowSec = 30; el.tlen.textContent='30s';
          }
        });
      }
    },1000);
  }
  function startAuto30(){
    state.loop = true; state.windowSec = 30; el.tlen.textContent='30s';
    networkSample().finally(()=>{ networkSpeed({min:25,max:60}).then(updateHero).catch(()=>{}); startCountdown(); networkPeaks(); });
  }
  function run10sOnce(){
    state.loop = false; state.windowSec = 10; el.tlen.textContent='10s';
    networkSample().finally(()=>{ startCountdown(); });
  }
  el.btn10.addEventListener('click', run10sOnce);
  el.btn30.addEventListener('click', startAuto30);

  // ---------- Table (now / avg / max) with online bars ----------
  var tbody = $('#body'), notes = $('#notes');
  var allRows = []; // metadata for rendering

  function renderRows(rows){
    tbody.innerHTML='';
    rows.forEach(r=>{
      var id = r.onuid, safe = idSafe(id);
      var tr = document.createElement('tr');
      tr.innerHTML =
        `<td><button class="btn" data-onu="${esc(id)}">+</button></td>`+
        `<td class="mono"><span id="dot-${safe}" class="status-dot"></span>${esc(id)}</td>`+
        `<td>${esc(r.pon!=null?String(r.pon):'')}</td>`+
        `<td class="mono">${esc(fmtMBGB(r.input_bytes))}</td>`+
        `<td class="mono">${esc(fmtMBGB(r.output_bytes))}</td>`+
        `<td class="mono" id="now-${safe}">— Mbps ${bars(0)}</td>`+
        `<td class="mono" id="avg-${safe}">— Mbps</td>`+
        `<td class="mono" id="max-${safe}">— Mbps</td>`;
      tbody.appendChild(tr);
      attachExpander(tr, id); // keep your + expand (with 30s countdown inside)
    });
  }

  // Per-ONU expand (unchanged logic, only label tweaks)
  var cdowns={}, cdvals={}, dotT={};
  function startOnuCountdown(id){
    stopOnuCountdown(id);
    cdvals[id]=30; var el=document.getElementById('cd-'+idSafe(id)); if(el) el.textContent=cdvals[id];
    dotT[id]=setInterval(()=>{ var s=document.getElementById('st-'+idSafe(id)); if(!s)return; s.textContent='Measuring… 30s test'; },800);
    cdowns[id]=setInterval(()=>{ cdvals[id]-=1; if(cdvals[id]<0) cdvals[id]=0;
      var e=document.getElementById('cd-'+idSafe(id)); if(e) e.textContent=cdvals[id];
      if (cdvals[id]===0){ clearInterval(cdowns[id]); delete cdowns[id]; refreshDetail(id); startOnuCountdown(id); }
    },1000);
  }
  function stopOnuCountdown(id){ if(cdowns[id]){ clearInterval(cdowns[id]); delete cdowns[id]; } if(dotT[id]){ clearInterval(dotT[id]); delete dotT[id]; } }

  function makeDetailRow(id){
    var sid=idSafe(id);
    var tr=document.createElement('tr'); tr.className='detail';
    var td=document.createElement('td'); td.colSpan=8;
    td.innerHTML =
      `<div style="display:flex;align-items:center;gap:8px"><span class="status-dot on"></span><strong class="mono">${esc(id)}</strong> — <span id="st-${sid}">Measuring… 30s test</span></div>`+
      `<div class="notes">Next update in <span class="countdown" id="cd-${sid}">30</span>s · Updated <span id="ts-${sid}">—</span> (Δ <span id="dt-${sid}">—</span> s)</div>`+
      `<table style="margin-top:6px;"><tr><th></th><th>Upload</th><th>Download</th><th>Total</th></tr>`+
      `<tr><td>Speed</td><td class="mono" id="up-${sid}">— Mbps</td><td class="mono" id="down-${sid}">— Mbps</td><td class="mono" id="tot-${sid}">— Mbps</td></tr></table>`;
    tr.appendChild(td);
    return tr;
  }

  function refreshDetail(id){
    fetch(API_BASE+'sample_one.php?onuid='+encodeURIComponent(id)).catch(()=>{});
    getJSON(API_BASE+'speed_now.php?onuid='+encodeURIComponent(id)).then(j=>{
      var sid=idSafe(id);
      document.getElementById('up-'+sid).textContent   = fMbps(j.in_mbps);     // inbound → upload
      document.getElementById('down-'+sid).textContent = fMbps(j.out_mbps);    // outbound → download
      document.getElementById('tot-'+sid).textContent  = fMbps(j.total_mbps);
      $('#dt-'+sid).textContent = j.dt_sec==null?'—':j.dt_sec;
      $('#ts-'+sid).textContent = j.at_ts? new Date(j.at_ts*1000).toLocaleString() : '—';
      var s=$('#st-'+sid); if(s) s.textContent='Latest result ready';
    }).catch(()=>{});
  }

  function attachExpander(tr, id){
    var btn = tr.querySelector('button[data-onu]');
    var expanded=false, dtr=null;
    btn.addEventListener('click', ()=>{
      if(!expanded){
        btn.textContent='−';
        dtr=makeDetailRow(id);
        tr.parentNode.insertBefore(dtr, tr.nextSibling);
        expanded=true;
        refreshDetail(id);
        startOnuCountdown(id);
      }else{
        btn.textContent='+';
        if(dtr&&dtr.parentNode) dtr.parentNode.removeChild(dtr);
        stopOnuCountdown(id);
        expanded=false;
      }
    });
  }

  // Fill table base rows (static bytes)
  function loadTable(){
    notes.innerHTML='<span class="skeleton">Measuring ONUs…</span>';
    getJSON(API_BASE+'stats_all.php').then(j=>{
      if(!j.ok) throw new Error(j.error||'failed');
      var rows=(j.rows||[]).slice();
      rows.sort((a,b)=>{
        var pa=(+a.pon||0), pb=(+b.pon||0); if(pa!==pb) return pa-pb;
        var aa=parsePort(a.onuid), bb=parsePort(b.onuid);
        if(aa.port!==bb.port) return aa.port-bb.port; return aa.onu-bb.onu;
      });
      allRows=rows;
      renderRows(rows);
      notes.textContent='Loaded '+rows.length+' ONUs. “Now/Avg/Max” update automatically.';
      refreshNowBars();            // first “now” paint
      refreshTodayAvgMax();        // first avg/max paint
      setInterval(refreshNowBars, 30000); // keep “now” fresh every 30s
    }).catch(e=>{ notes.textContent='Error: '+e.message; });
  }

  // Update NOW bars/dots from last two snapshots
  function refreshNowBars(){
    getJSON(API_BASE+'online_now_all.php').then(j=>{
      if(!j.ok || !j.has_data) return;
      for (var id in j.rows){
        var safe=idSafe(id), rec=j.rows[id];
        var nowEl = document.getElementById('now-'+safe);
        var dotEl = document.getElementById('dot-'+safe);
        if(nowEl) nowEl.innerHTML = fMbps(rec.total_mbps)+' '+bars(rec.level);
        if(dotEl) dotEl.classList.toggle('on', !!rec.online);
      }
    }).catch(()=>{});
  }

  // Update AVG/MAX (today) for all ONUs
  function refreshTodayAvgMax(){
    var today = new Date().toISOString().slice(0,10);
    getJSON(API_BASE+'day_stats_all.php?date='+today).then(j=>{
      if(!j.ok) return;
      var map=j.rows||{};
      allRows.forEach(r=>{
        var safe=idSafe(r.onuid), st=map[r.onuid]||{};
        var avgEl=document.getElementById('avg-'+safe);
        var maxEl=document.getElementById('max-'+safe);
        if(avgEl) avgEl.textContent = fMbps(st.avg_total_mbps);
        if(maxEl) maxEl.textContent = fMbps(st.max_total_mbps);
      });
    }).catch(()=>{});
  }

  // Boot
  loadTable();
  startAuto30(); // default loop
})();
