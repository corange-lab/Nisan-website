(function(){
  'use strict';
  var API = (window.API_BASE||'./api/').replace(/\/+$/,'')+'/';

  // -------- helpers --------
  const $ = s => document.querySelector(s);
  const esc = s => (s==null?'':String(s)).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
  const idSafe = s => (s||'').replace(/[^\w\-]+/g,'_');
  function normOnu(s){ s=(s==null?'':String(s)).toUpperCase(); s=s.replace(/\u00a0/g,' ').replace(/\s+/g,' '); return s.trim(); }

  function getJSON(u,o){
    const url = u + (u.indexOf('?')>=0?'&':'?') + '_t=' + Date.now(); // cache-bust (Safari)
    return fetch(url, Object.assign({cache:'no-store'}, o||{})).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status+' for '+u); return r.json(); });
  }
  function fmtMBGB(v){ if(v==null||v==='')return'NULL'; const n=+v; if(!isFinite(n))return String(v); const mb=n/1048576; return mb>=1024?(mb/1024).toFixed(2)+' GB':mb.toFixed(2)+' MB'; }
  const fMbps = x => (x==null||!isFinite(x))?'—':Number(x).toFixed(2);
  function fmtBytesNice(bytes){ if(bytes==null || !isFinite(bytes)) return '—'; const n=Number(bytes); const GB=1073741824, MB=1048576; return n>=GB? (n/GB).toFixed(2)+' GB' : (n/MB).toFixed(2)+' MB'; }

  // animated number (Ookla-feel)
  const easeOutExpo = t => (t===1?1:1-Math.pow(2,-10*t));
  function animMs(delta){ const d=Math.abs(delta); if(d>=200)return 280; if(d>=50)return 380; if(d>=20)return 520; if(d>=5)return 700; return 850; }
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
    const box = document.getElementById(containerId); if (!box) return;
    const kids = box.querySelectorAll('.bar'); kids.forEach((b,i)=>b.classList.toggle('on', i < lvl));
  }

  // -------- peaks --------
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

  // -------- chart (always 00:00–23:59, no zoom/pan) --------
  const el = { cdate:$('#chart_date'), tf:$('#chart_tf'), loadBtn:$('#chart_refresh'),
    box:$('#chart_box'), svg:$('#chart_svg'), tip:$('#chart_tip') };
  let chart = { points:[], tf:'1m' };
  const W=1000,H=420,Px=44,Py=34;

  function X(t, minT, maxT){ const span=Math.max(1,maxT-minT); return Px + ((t-minT)/span)*(W-2*Px); }
  function Y(v, maxY){ return H-Py - (v/(maxY||1))*(H-2*Py); }

  function loadChart(){
    const date = el.cdate?.value || new Date().toISOString().slice(0,10);
    const tf   = el.tf?.value || '1m';
    const params = `date=${encodeURIComponent(date)}&tf=${encodeURIComponent(tf)}&tz=Asia%2FKolkata&fill=1`;
    getJSON(API+'network_timeseries.php?'+params).then(j=>{
      chart.points = j.points||[]; chart.tf = j.tf||tf;
      renderChart();
    }).catch(()=>{});
  }

  function renderChart(){
    const svg=el.svg; if(!svg) return; svg.innerHTML='';
    const pts=chart.points; if(!pts.length) return;

    const minT = pts[0].t, maxT = pts[pts.length-1].t;
    let maxY=0; pts.forEach(p=>{ maxY=Math.max(maxY,p.upload_mbps,p.download_mbps); }); if(maxY<=0) maxY=1;

    const gAxes = document.createElementNS(svg.namespaceURI,'g');
    for(let i=0;i<=5;i++){
      const y=Y(maxY*i/5,maxY), ln=document.createElementNS(svg.namespaceURI,'line');
      ln.setAttribute('x1',Px); ln.setAttribute('x2',W-Px); ln.setAttribute('y1',y); ln.setAttribute('y2',y);
      ln.setAttribute('stroke','#18224b'); gAxes.appendChild(ln);
      const lab=document.createElementNS(svg.namespaceURI,'text');
      lab.textContent=(maxY*i/5).toFixed(0)+' Mbps'; lab.setAttribute('x',10); lab.setAttribute('y',y-2);
      lab.setAttribute('fill','#7d8fbf'); lab.setAttribute('font-size','11'); gAxes.appendChild(lab);
    }
    const tickCount=10, span=maxT-minT;
    for(let i=0;i<=tickCount;i++){
      const t=minT + (i*span/tickCount), x=X(t,minT,maxT);
      const ln=document.createElementNS(svg.namespaceURI,'line');
      ln.setAttribute('x1',x); ln.setAttribute('x2',x); ln.setAttribute('y1',H-Py); ln.setAttribute('y2',H-Py+6);
      ln.setAttribute('stroke','#18224b'); gAxes.appendChild(ln);
      const d=new Date(t*1000), hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0');
      const lab=document.createElementNS(svg.namespaceURI,'text');
      lab.textContent=hh+':'+mm; lab.setAttribute('x',x-14); lab.setAttribute('y',H-10);
      lab.setAttribute('fill','#9bb0e4'); lab.setAttribute('font-size','10'); gAxes.appendChild(lab);
    }
    svg.appendChild(gAxes);

    function pathFor(key,color){
      let d='M '+X(pts[0].t,minT,maxT)+' '+Y(pts[0][key],maxY);
      for(let i=1;i<pts.length;i++) d+=' L '+X(pts[i].t,minT,maxT)+' '+Y(pts[i][key],maxY);
      const p=document.createElementNS(svg.namespaceURI,'path');
      p.setAttribute('d',d); p.setAttribute('fill','none'); p.setAttribute('stroke',color); p.setAttribute('stroke-width','2'); return p;
    }
    const colU=getComputedStyle(document.documentElement).getPropertyValue('--u')||'#60a5fa';
    const colD=getComputedStyle(document.documentElement).getPropertyValue('--d')||'#f472b6';
    svg.appendChild(pathFor('upload_mbps',colU));
    svg.appendChild(pathFor('download_mbps',colD));

    // hover only
    const vline=document.createElementNS(svg.namespaceURI,'line'); vline.setAttribute('y1',Py); vline.setAttribute('y2',H-Py);
    vline.setAttribute('stroke','#93a4c7'); vline.setAttribute('stroke-width','1'); vline.setAttribute('opacity','0'); svg.appendChild(vline);
    const dotU=document.createElementNS(svg.namespaceURI,'circle'), dotD=document.createElementNS(svg.namespaceURI,'circle');
    [dotU,dotD].forEach(c=>{ c.setAttribute('r','3.5'); c.setAttribute('opacity','0'); svg.appendChild(c); });
    dotU.setAttribute('fill',colU); dotD.setAttribute('fill',colD);

    const overlay=document.createElementNS(svg.namespaceURI,'rect');
    overlay.setAttribute('x',0); overlay.setAttribute('y',0); overlay.setAttribute('width','100%'); overlay.setAttribute('height','100%');
    overlay.setAttribute('fill','transparent'); svg.appendChild(overlay);
    const tip=el.tip, box=el.box;

    function nearestIdx(px){
      const rect=svg.getBoundingClientRect(); const frac=Math.min(1,Math.max(0,(px-Px)/(rect.width-2*Px)));
      const t = minT + frac*(maxT-minT);
      let lo=0, hi=pts.length-1;
      while(hi-lo>1){ const mid=(lo+hi)>>1; if(pts[mid].t<t)lo=mid; else hi=mid; }
      return (t-pts[lo].t <= pts[hi].t - t) ? lo : hi;
    }
    function showAt(clientX){
      const rect=svg.getBoundingClientRect(); const x=clientX-rect.left, i=nearestIdx(x), p=pts[i], sx=X(p.t,minT,maxT);
      vline.setAttribute('x1',sx); vline.setAttribute('x2',sx); vline.setAttribute('opacity','1');
      dotU.setAttribute('cx',sx); dotU.setAttribute('cy',Y(p.upload_mbps,maxY)); dotU.setAttribute('opacity','1');
      dotD.setAttribute('cx',sx); dotD.setAttribute('cy',Y(p.download_mbps,maxY)); dotD.setAttribute('opacity','1');
      const d=new Date(p.t*1000), hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0'), ss=String(d.getSeconds()).padStart(2,'0');
      tip.innerHTML = `<div style="font-weight:700;margin-bottom:4px">${hh}:${mm}:${ss}${chart.tf==='1m'?' (1m max)':''}</div>
        <div><span class="dot u"></span> Upload: ${p.upload_mbps.toFixed(2)} Mbps</div>
        <div><span class="dot d"></span> Download: ${p.download_mbps.toFixed(2)} Mbps</div>`;
      tip.style.display='block';
      let tx = (sx/1000)*box.getBoundingClientRect().width + 10; let ty=10;
      if (tx + tip.offsetWidth > box.clientWidth) tx = box.clientWidth - tip.offsetWidth - 10;
      tip.style.left=tx+'px'; tip.style.top=ty+'px';
    }
    function hideHover(){ vline.setAttribute('opacity','0'); dotU.setAttribute('opacity','0'); dotD.setAttribute('opacity','0'); tip.style.display='none'; }

    overlay.addEventListener('mousemove', e=>showAt(e.clientX));
    overlay.addEventListener('mouseleave', hideHover);
  }

  // -------- table / live --------
  const tbody=$('#body'), notes=$('#notes'); let allRows=[];
  let tracking = (localStorage.getItem('tracking')!=='off'); // default ON
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
        const aa=/^GPON\d+\/(\d+):(\+)/i.exec('')||[null,0,0]; // keep consistent signature
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

  // ---- Today Usage (Download|Upload) + Max (Today) ----
  function refreshTodayUsageAndMax(){
    const date = $('#chart_date') ? ($('#chart_date').value || new Date().toISOString().slice(0,10)) : new Date().toISOString().slice(0,10);
    const p1 = getJSON(API+'day_usage_all.php?date='+encodeURIComponent(date)+'&tz=Asia%2FKolkata').catch(()=>({ok:false,rows:{}}));
    const p2 = getJSON(API+'day_stats_all.php?date='+encodeURIComponent(date)).catch(()=>({ok:false,rows:{}}));
    Promise.all([p1,p2]).then(([u,m])=>{
      const usage = (u&&u.ok&&u.rows)||{};
      const maxes = (m&&m.ok&&m.rows)||{};
      allRows.forEach(r=>{
        const safe=idSafe(r.onuid);
        const key = normOnu(r.onuid); // <<< important: normalized ID
        const U = usage[key];
        const M = maxes[key] || maxes[r.onuid]; // be tolerant
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

  // per-ONU realtime test (unchanged)
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
              if (testCell) testCell.innerHTML = `<button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="10" style="margin-right:6px" disabled>Test 10s</button><button class="btn btn-test" data-onu="${esc(onuid)}" data-secs="30" disabled>Test 30s</button> <span style="color:#9bb0e4;font-size:11px;margin-left:6px">(${remain}s)</span>`;
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

  // tracking toggle (also inform server)
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

  // boot
  const todayISO=new Date().toISOString().slice(0,10);
  if ($('#chart_date')) $('#chart_date').value=todayISO;
  if ($('#chart_refresh')) $('#chart_refresh').addEventListener('click',()=>{ loadChart(); refreshTodayUsageAndMax(); });
  if ($('#chart_tf')) $('#chart_tf').addEventListener('change',()=>{ loadChart(); });

  loadPeaks();
  loadChart();
  loadTable();
  setInterval(()=>{ if(tracking && $('#chart_refresh')) $('#chart_refresh').click(); }, 60000);

  window.addEventListener('error', function(e){
    const n=$('#notes'); if(n) n.textContent='Script error: '+(e&&e.message?e.message:'unknown');
  });
})();
