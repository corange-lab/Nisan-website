(function(){
  'use strict';
  var API = (window.API_BASE||'./api/').replace(/\/+$/,'')+'/';

  // ===== helpers =====
  const $ = s => document.querySelector(s);
  const esc = s => (s==null?'':String(s)).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
  const idSafe = s => (s||'').replace(/[^\w\-]+/g,'_');
  if (!window.CSS || !CSS.escape) window.CSS = Object.assign(window.CSS||{}, { escape: s => String(s).replace(/[^\w\-]/g,'_') });

  function getJSON(u,o){
    const url = u + (u.indexOf('?')>=0?'&':'?') + '_t=' + Date.now(); // cache-bust (Safari)
    return fetch(url, Object.assign({cache:'no-store'}, o||{})).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status+' for '+u); return r.json(); });
  }
  function fmtMBGB(v){ if(v==null||v==='')return'NULL'; const n=+v; if(!isFinite(n))return String(v); const mb=n/1048576; return mb>=1024?(mb/1024).toFixed(2)+' GB':mb.toFixed(2)+' MB'; }
  const fMbps = x => (x==null||!isFinite(x))?'—':Number(x).toFixed(2);
  function fmtBytesNice(bytes){
    if(bytes==null || !isFinite(bytes)) return '—';
    const n = Number(bytes);
    const GB = 1024*1024*1024, MB = 1024*1024;
    if (n >= GB) return (n/GB).toFixed(2)+' GB';
    return (n/MB).toFixed(2)+' MB';
  }

  // === animated number (Ookla-feel) ===
  const easeOutExpo = t => (t===1?1:1 - Math.pow(2, -10*t));
  function animMs(delta){
    const d = Math.abs(delta);
    if (d >= 200) return 280;
    if (d >= 50)  return 380;
    if (d >= 20)  return 520;
    if (d >= 5)   return 700;
    return 850;
  }
  function animateNumber(el, target){
    if (!el) return;
    const from = parseFloat(el.dataset.val || '0') || 0;
    const to   = (isFinite(target)? target : 0);
    const dur  = animMs(to - from);
    const start = performance.now();
    const gran = (to>=100?1:(to>=10?0.1:0.01));
    function frame(now){
      const p = Math.min(1, (now - start) / dur);
      const eased = easeOutExpo(p);
      let v = from + (to - from) * eased;
      v = Math.round(v / gran) * gran;
      el.textContent = fMbps(v);
      el.dataset.val = String(v);
      if (p < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }
  function setBarsLevel(containerId, lvl){
    const box = document.getElementById(containerId);
    if (!box) return;
    const kids = box.querySelectorAll('.bar');
    kids.forEach((b,i)=>b.classList.toggle('on', i < lvl));
  }

  // ===== Peaks (unchanged) =====
  function loadPeaks(){
    return getJSON(API+'network_peaks.php').then(j=>{
      if(!j.ok) return;
      const upd=(k,rec)=>{ let V=$('#pk_'+k), T=$('#pk_'+k+'_t'); if(!V||!T)return;
        if(!rec||!rec.has_data){ V.textContent='— Mbps'; T.textContent='—'; return; }
        V.textContent = Number(rec.total_mbps).toFixed(2)+' Mbps';
        T.textContent = new Date(rec.ts_curr*1000).toLocaleString()+' (Δ '+rec.dt_sec+'s)';
      };
      if (j.peaks){ upd('24h', j.peaks['24h']); upd('7d', j.peaks['7d']); upd('30d', j.peaks['30d']); }
    }).catch(()=>{});
  }

  // ===== Chart load (uses tz=Asia/Kolkata&fill=1) =====
  const el = { cdate:$('#chart_date'), tf:$('#chart_tf'), loadBtn:$('#chart_refresh'),
    box:$('#chart_box'), svg:$('#chart_svg'), tip:$('#chart_tip'),
    zin:$('#zoom_in'), zout:$('#zoom_out'), zreset:$('#zoom_reset') };
  let chart = { points:[], minT:0, maxT:0, viewMin:0, viewMax:0, tf:'1m' };
  const W=1000,H=420,Px=44,Py=34;
  function X(t){ const vspan=chart.viewMax-chart.viewMin||1; return Px + ((t-chart.viewMin)/vspan)*(W-2*Px); }
  function Y(v, maxY){ return H-Py - (v/(maxY||1))*(H-2*Py); }

  function loadChart(){
    const date = el.cdate?.value || new Date().toISOString().slice(0,10);
    const tf   = el.tf?.value || '1m';
    const params = `date=${encodeURIComponent(date)}&tf=${encodeURIComponent(tf)}&tz=Asia%2FKolkata&fill=1`;
    getJSON(API+'network_timeseries.php?'+params).then(j=>{
      const pts = j.points||[]; chart.points=pts; chart.tf=j.tf||tf;
      if(!pts.length){ el.svg.innerHTML=''; return; }
      chart.minT = pts[0].t; chart.maxT = pts[pts.length-1].t;
      chart.viewMin = chart.minT; chart.viewMax = chart.maxT;
      renderChart();
    }).catch(()=>{});
  }
  function renderChart(){
    const svg = el.svg; if(!svg) return; svg.innerHTML='';
    const pts = chart.points; if(!pts.length) return;
    const vmin=chart.viewMin, vmax=chart.viewMax;
    const vis = pts.filter(p=>p.t>=vmin && p.t<=vmax); if(!vis.length) return;
    let maxY=0; vis.forEach(p=>{ maxY=Math.max(maxY,p.upload_mbps,p.download_mbps); }); if(maxY<=0) maxY=1;

    const gAxes = document.createElementNS(svg.namespaceURI,'g');
    for(let i=0;i<=5;i++){
      const y=Y(maxY*i/5,maxY); const ln=document.createElementNS(svg.namespaceURI,'line');
      ln.setAttribute('x1',Px); ln.setAttribute('x2',W-Px);
      ln.setAttribute('y1',y); ln.setAttribute('y2',y);
      ln.setAttribute('stroke','#18224b'); ln.setAttribute('stroke-width','1'); gAxes.appendChild(ln);
      const lab=document.createElementNS(svg.namespaceURI,'text');
      lab.textContent=(maxY*i/5).toFixed(0)+' Mbps';
      lab.setAttribute('x',10); lab.setAttribute('y',y-2);
      lab.setAttribute('fill','#7d8fbf'); lab.setAttribute('font-size','11'); gAxes.appendChild(lab);
    }
    const span=vmax-vmin; const tickCount=10;
    for(let i=0;i<=tickCount;i++){
      const t = vmin + (i*span/tickCount); const x = X(t);
      const ln=document.createElementNS(svg.namespaceURI,'line');
      ln.setAttribute('x1',x); ln.setAttribute('x2',x);
      ln.setAttribute('y1',H-Py); ln.setAttribute('y2',H-Py+6);
      ln.setAttribute('stroke','#18224b'); ln.setAttribute('stroke-width','1.2'); gAxes.appendChild(ln);
      const d=new Date(t*1000); const hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0');
      const lab=document.createElementNS(svg.namespaceURI,'text');
      lab.textContent = hh+':'+mm; lab.setAttribute('x',x-14); lab.setAttribute('y',H-10);
      lab.setAttribute('fill','#9bb0e4'); lab.setAttribute('font-size','10'); gAxes.appendChild(lab);
    }
    svg.appendChild(gAxes);

    function pathFor(key, color){
      let d='M '+X(vis[0].t)+' '+Y(vis[0][key],maxY);
      for(let i=1;i<vis.length;i++) d+=' L '+X(vis[i].t)+' '+Y(vis[i][key],maxY);
      const p=document.createElementNS(svg.namespaceURI,'path');
      p.setAttribute('d',d); p.setAttribute('fill','none'); p.setAttribute('stroke',color); p.setAttribute('stroke-width','2'); return p;
    }
    const colU=getComputedStyle(document.documentElement).getPropertyValue('--u')||'#60a5fa';
    const colD=getComputedStyle(document.documentElement).getPropertyValue('--d')||'#f472b6';
    svg.appendChild(pathFor('upload_mbps',colU));
    svg.appendChild(pathFor('download_mbps',colD));

    const vline=document.createElementNS(svg.namespaceURI,'line');
    vline.setAttribute('y1',Py); vline.setAttribute('y2',H-Py);
    vline.setAttribute('stroke','#93a4c7'); vline.setAttribute('stroke-width','1'); vline.setAttribute('opacity','0'); svg.appendChild(vline);
    const dotU=document.createElementNS(svg.namespaceURI,'circle'); const dotD=document.createElementNS(svg.namespaceURI,'circle');
    [dotU,dotD].forEach(c=>{ c.setAttribute('r','3.5'); c.setAttribute('opacity','0'); svg.appendChild(c); });
    dotU.setAttribute('fill',colU); dotD.setAttribute('fill',colD);
    const tip=el.tip, box=el.box;

    const overlay = document.createElementNS(svg.namespaceURI,'rect');
    overlay.setAttribute('x',0); overlay.setAttribute('y',0); overlay.setAttribute('width','100%'); overlay.setAttribute('height','100%');
    overlay.setAttribute('fill','transparent'); svg.appendChild(overlay);

    function nearestIdx(px){
      const frac = (px-Px)/(W-2*Px);
      const t = chart.viewMin + Math.min(1,Math.max(0,frac))*(chart.viewMax-chart.viewMin);
      let lo=0, hi=vis.length-1;
      while(hi-lo>1){ const mid=(lo+hi)>>1; if (vis[mid].t < t) lo=mid; else hi=mid; }
      return (t-vis[lo].t <= vis[hi].t - t) ? lo : hi;
    }
    function showAt(x){
      const i=nearestIdx(x); const p=vis[i]; const sx=X(p.t);
      vline.setAttribute('x1',sx); vline.setAttribute('x2',sx); vline.setAttribute('opacity','1');
      const yU=Y(p.upload_mbps,maxY), yD=Y(p.download_mbps,maxY);
      dotU.setAttribute('cx',sx); dotU.setAttribute('cy',yU); dotU.setAttribute('opacity','1');
      dotD.setAttribute('cx',sx); dotD.setAttribute('cy',yD); dotD.setAttribute('opacity','1');
      const d=new Date(p.t*1000);
      const hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0'), ss=String(d.getSeconds()).padStart(2,'0');
      tip.innerHTML = `<div style="font-weight:700;margin-bottom:4px">${hh}:${mm}:${ss}${chart.tf==='1m'?' (1m max)':''}</div>
        <div><span class="dot u"></span> Upload: ${p.upload_mbps.toFixed(2)} Mbps</div>
        <div><span class="dot d"></span> Download: ${p.download_mbps.toFixed(2)} Mbps</div>`;
      tip.style.display='block';
      const rect = box.getBoundingClientRect();
      let tx = (sx/1000)*rect.width + 10; let ty = 10;
      if (tx + tip.offsetWidth > rect.width) tx = rect.width - tip.offsetWidth - 10;
      tip.style.left = tx+'px'; tip.style.top = ty+'px';
    }
    function hideHover(){ vline.setAttribute('opacity','0'); dotU.setAttribute('opacity','0'); dotD.setAttribute('opacity','0'); tip.style.display='none'; }

    // pan/zoom handlers omitted for brevity (unchanged from earlier version)...
    let dragging=false, dragStartX=0, dragStartMin=0, dragStartMax=0;
    overlay.addEventListener('mousedown',e=>{ dragging=true; dragStartX=e.clientX; dragStartMin=chart.viewMin; dragStartMax=chart.viewMax; });
    window.addEventListener('mouseup',()=>{ dragging=false; });
    window.addEventListener('mousemove',e=>{
      if(!dragging) return; const rect=svg.getBoundingClientRect(); const dx=e.clientX-dragStartX;
      const frac = dx/rect.width; const span=dragStartMax-dragStartMin;
      chart.viewMin = dragStartMin - frac*span; chart.viewMax = dragStartMax - frac*span;
      if(chart.viewMin<chart.minT){ const d=chart.minT-chart.viewMin; chart.viewMin+=d; chart.viewMax+=d; }
      if(chart.viewMax>chart.maxT){ const d=chart.viewMax-chart.maxT; chart.viewMin-=d; chart.viewMax-=d; }
      renderChart();
    });
    overlay.addEventListener('wheel',e=>{
      e.preventDefault(); const rect=svg.getBoundingClientRect(); const x=e.clientX-rect.left;
      const tAtX = chart.viewMin + ((chart.viewMax-chart.viewMin)*((x-Px)/(W-2*Px)));
      const scale = (e.deltaY<0)?0.8:1.25; const newSpan = (chart.viewMax-chart.viewMin)*scale;
      let nMin = tAtX - (tAtX-chart.viewMin)*scale; let nMax = nMin + newSpan;
      if(nMin<chart.minT){ const d=chart.minT-nMin; nMin+=d; nMax+=d; }
      if(nMax>chart.maxT){ const d=nMax-chart.maxT; nMin-=d; nMax-=d; }
      chart.viewMin=nMin; chart.viewMax=nMax; renderChart();
    }, {passive:false});
    overlay.addEventListener('mousemove',e=>{
      const rect=svg.getBoundingClientRect(); const x=e.clientX-rect.left; showAt(x);
    });
    overlay.addEventListener('mouseleave',hideHover);
  }
  if ($('#zoom_in'))  $('#zoom_in').addEventListener('click',()=>{ const c=(chart.viewMin+chart.viewMax)/2, span=(chart.viewMax-chart.viewMin)*0.5;
    chart.viewMin=Math.max(chart.minT, c-span/2); chart.viewMax=Math.min(chart.maxT, c+span/2); renderChart(); });
  if ($('#zoom_out')) $('#zoom_out').addEventListener('click',()=>{ const c=(chart.viewMin+chart.viewMax)/2, span=(chart.viewMax-chart.viewMin)*2.0;
    let vmin=c-span/2, vmax=c+span/2; if(vmin<chart.minT){ const d=chart.minT-vmin; vmin+=d; vmax+=d; }
    if(vmax>chart.maxT){ const d=vmax-chart.maxT; vmin-=d; vmax-=d; } chart.viewMin=vmin; chart.viewMax=vmax; renderChart(); });
  if ($('#zoom_reset')) $('#zoom_reset').addEventListener('click',()=>{ chart.viewMin=chart.minT; chart.viewMax=chart.maxT; renderChart(); });

  // ===== Table / live =====
  const tbody = $('#body'), notes = $('#notes'); let allRows = [];
  let tracking = (localStorage.getItem('tracking')!=='off'); // default ON
  let timers = { sample:null, agg:null };
  function clearTimers(){ if(timers.sample){ clearInterval(timers.sample); timers.sample=null; } if(timers.agg){ clearInterval(timers.agg); timers.agg=null; } }
  function startTracking(){
    if (timers.sample || timers.agg) return;
    timers.sample = setInterval(()=>{ sampleAll().then(refreshNowFromServer).catch(()=>{}); }, 3000);
    timers.agg    = setInterval(refreshTodayUsageAndMax, 15000);
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
      `<td class="num">
          <span class="now-num" id="nowv-${safe}" data-val="0">—</span> Mbps
          <span class="bars" id="bars-${safe}">
            <span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span>
          </span>
       </td>`+
      `<td class="num" id="usage-${safe}">—</td>`+      /* NEW: Today Usage cell */
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
        const aa=/^GPON\d+\/(\d+):(\d+)/i.exec(a.onuid||'')||[null,0,0];
        const bb=/^GPON\d+\/(\d+):(\d+)/i.exec(b.onuid||'')||[null,0,0];
        if(+aa[1]!==+bb[1]) return +aa[1]-+bb[1]; return +aa[2]-+bb[2];
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

  // NEW: Today Usage (Download|Upload) + Max (Today)
  function refreshTodayUsageAndMax(){
    const date = $('#chart_date') ? ($('#chart_date').value || new Date().toISOString().slice(0,10)) : new Date().toISOString().slice(0,10);
    const p1 = getJSON(API+'day_usage_all.php?date='+encodeURIComponent(date)+'&tz=Asia%2FKolkata').catch(()=>({ok:false,rows:{}}));
    const p2 = getJSON(API+'day_stats_all.php?date='+encodeURIComponent(date)).catch(()=>({ok:false,rows:{}})); // reuse for Max
    Promise.all([p1,p2]).then(([u,m])=>{
      const usage = (u&&u.ok&&u.rows)||{};
      const maxes = (m&&m.ok&&m.rows)||{};
      allRows.forEach(r=>{
        const safe=idSafe(r.onuid);
        const U = usage[r.onuid];
        const M = maxes[r.onuid];
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

  // ===== per-ONU realtime test (unchanged) =====
  let testLock = false, testTicker = null;
  function disableRowButtons(onuid, disabled){
    document.querySelectorAll(`.btn-test[data-onu="${CSS.escape(onuid)}"]`).forEach(b=>{ b.disabled = !!disabled; });
  }
  function runSingleTest(onuid, secs){
    if (testLock) return;
    testLock = true;
    const safe=idSafe(onuid);
    const nowValEl = document.getElementById('nowv-'+safe);
    const barsElId = 'bars-'+safe;
    const dotEl = document.getElementById('dot-'+safe);
    const testCell = document.getElementById('test-'+safe);
    const wasTracking = tracking;

    if (wasTracking) { tracking=false; localStorage.setItem('tracking','off'); stopTracking(); if($('#track_toggle')) $('#track_toggle').checked=false; }
    disableRowButtons(onuid,true);

    let remain = secs;
    if (testCell) testCell.dataset.originalHTML = testCell.innerHTML;

    sampleAll().then(()=>{
      testTicker = setInterval(()=>{
        remain--;
        sampleAll()
          .then(()=>getJSON(API+'online_now_single.php?onuid='+encodeURIComponent(onuid)))
          .then(j=>{
            if (j && j.ok && j.has_data){
              if (nowValEl) animateNumber(nowValEl, j.total_mbps||0);
              setBarsLevel(barsElId, j.level||0);
              if (dotEl) dotEl.classList.toggle('on', (j.total_mbps>0));
              if (testCell) testCell.innerHTML = `<button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="10" style="margin-right:6px" disabled>Test 10s</button><button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="30" disabled>Test 30s</button> <span style="color:#9bb0e4;font-size:11px;margin-left:6px">(${remain}s)</span>`;
            } else if (testCell) {
              testCell.innerHTML = `<button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="10" style="margin-right:6px" disabled>Test 10s</button><button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="30" disabled>Test 30s</button> <span style="color:#9bb0e4;font-size:11px;margin-left:6px">(${remain}s)</span>`;
            }
          }).catch(()=>{ if (testCell) testCell.innerHTML = `<button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="10" style="margin-right:6px" disabled>Test 10s</button><button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="30" disabled>Test 30s</button> <span style="color:#9bb0e4;font-size:11px;margin-left:6px">(${remain}s)</span>`; });

        if (remain<=0){
          clearInterval(testTicker); testTicker=null;
          sampleAll().finally(()=>{
            if (testCell) testCell.innerHTML = testCell.dataset.originalHTML || testCell.innerHTML;
            disableRowButtons(onuid,false);
            if (wasTracking){ tracking=true; localStorage.setItem('tracking','on'); startTracking(); if($('#track_toggle')) $('#track_toggle').checked=true; }
            testLock=false;
          });
        }
      }, 1000);
    }).catch(()=>{
      if (testCell) testCell.innerHTML = testCell.dataset.originalHTML || testCell.innerHTML;
      disableRowButtons(onuid,false);
      if (wasTracking){ tracking=true; localStorage.setItem('tracking','on'); startTracking(); if($('#track_toggle')) $('#track_toggle').checked=true; }
      testLock=false;
    });
  }

  // clicks
  $('#body').addEventListener('click', function(e){
    const btn = e.target && e.target.classList && e.target.classList.contains('btn-test') ? e.target : null;
    if (!btn) return;
    const onuid = btn.getAttribute('data-onu');
    const secs  = parseInt(btn.getAttribute('data-secs')||'10',10);
    runSingleTest(onuid, secs);
  });

  // Tracking toggle (also tell server)
  const trackToggle = $('#track_toggle');
  if (trackToggle){
    trackToggle.checked = tracking;
    trackToggle.addEventListener('change', function(){
      tracking = !!this.checked;
      localStorage.setItem('tracking', tracking ? 'on':'off');
      fetch(API + 'tracking_set.php?on=' + (tracking?1:0), {cache:'no-store'}).catch(()=>{});
      if (tracking) startTracking(); else stopTracking();
    });
  }

  // Boot
  const todayISO = new Date().toISOString().slice(0,10);
  if ($('#chart_date')) $('#chart_date').value = todayISO;
  if ($('#chart_refresh')) $('#chart_refresh').addEventListener('click',()=>{ loadChart(); refreshTodayUsageAndMax(); });
  if ($('#chart_tf')) $('#chart_tf').addEventListener('change',()=>{ loadChart(); });
  loadPeaks();
  loadChart();
  loadTable();
  setInterval(()=>{ if(tracking && $('#chart_refresh')) $('#chart_refresh').click(); }, 60000);

  window.addEventListener('error', function(e){
    const n = $('#notes'); if (n) n.textContent = 'Script error: '+ (e && e.message ? e.message : 'unknown');
  });
})();
