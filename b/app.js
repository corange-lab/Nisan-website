(function(){
  'use strict';
  var API = (window.API_BASE||'./api/').replace(/\/+$/,'')+'/';

  // ===== helpers =====
  const $ = s => document.querySelector(s);
  const esc = s => (s==null?'':String(s)).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
  const idSafe = s => (s||'').replace(/[^\w\-]+/g,'_');
  const getJSON = (u,o)=>fetch(u,o||{cache:'no-store'}).then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();});
  function fmtMBGB(v){ if(v==null||v==='')return'NULL'; const n=+v; if(!isFinite(n))return String(v); const mb=n/1048576; return mb>=1024?(mb/1024).toFixed(2)+' GB':mb.toFixed(2)+' MB'; }
  const fMbps = x => (x==null||!isFinite(x))?'— Mbps':Number(x).toFixed(2)+' Mbps';
  const parsePort = id => { const m=/^GPON\d+\/(\d+):(\d+)/i.exec(id||''); return m?{port:+m[1]||0, onu:+m[2]||0}:{port:0,onu:0}; };
  const bars = lvl => { let h='<span class="bars">'; for(let i=1;i<=5;i++) h+=`<span class="bar ${i<=lvl?'on':''}"></span>`; return h+'</span>'; };

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
  const tbody = $('#body'), notes = $('#notes');
  let allRows = [];

  function renderRows(rows){
    tbody.innerHTML='';
    rows.forEach(r=>{
      const id=r.onuid, safe=idSafe(id);
      const tr=document.createElement('tr');
      tr.innerHTML =
        `<td class="mono"><span id="dot-${safe}" class="status-dot"></span>${esc(id)}</td>`+
        `<td>${esc(r.pon!=null?String(r.pon):'')}</td>`+
        `<td class="num">${esc(fmtMBGB(r.input_bytes))}</td>`+
        `<td class="num">${esc(fmtMBGB(r.output_bytes))}</td>`+
        `<td class="num" id="now-${safe}">— Mbps ${bars(0)}</td>`+
        `<td class="num" id="avg-${safe}">— Mbps</td>`+
        `<td class="num" id="max-${safe}">— Mbps</td>`;
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
      // first fills
      refreshNowFromServer(); refreshTodayAvgMax();
      // realtime loop (≈3s): take snapshot → refresh NOW + top card
      setInterval(()=>{ sampleAll().then(refreshNowFromServer).catch(()=>{}); }, 3000);
      // refresh Avg/Max every 15s
      setInterval(refreshTodayAvgMax, 15000);
    }).catch(e=>{ notes.textContent='Error: '+e.message; });
  }

  // ===== realtime “NOW” + top card =====
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
      $('#net_status').textContent = 'Realtime stream';
    }).catch(()=>{});
  }

  // ===== Avg/Max (today) =====
  function refreshTodayAvgMax(){
    const dateInput = $('#chart_date');
    const date = (dateInput && dateInput.value) ? dateInput.value : new Date().toISOString().slice(0,10);
    getJSON(API+'day_stats_all.php?date='+encodeURIComponent(date)).then(j=>{
      if(!j.ok) return;
      const map = j.rows || {};
      allRows.forEach(r=>{
        const safe = idSafe(r.onuid);
        const st = map[r.onuid];
        const avgEl = $('#avg-'+safe);
        const maxEl = $('#max-'+safe);
        if (st){
          if (avgEl) avgEl.textContent = fMbps(st.avg_total_mbps);
          if (maxEl) maxEl.textContent = fMbps(st.max_total_mbps);
        } else {
          if (avgEl) avgEl.textContent = '— Mbps';
          if (maxEl) maxEl.textContent = '— Mbps';
        }
      });
    }).catch(()=>{});
  }

  // ===== Chart (Upload + Download only) with time axis & hover; client-side decimation =====
  const el = { cdate:$('#chart_date'), crefresh:$('#chart_refresh'), svg:$('#chart_svg'), box:$('#chart_box'), tip:$('#chart_tip') };

  function decimate(points, target){
    if(points.length<=target) return points;
    const step = points.length/target;
    const out=[]; for(let i=0;i<points.length;i+=step){ out.push(points[Math.floor(i)]); }
    if(out[out.length-1]!==points[points.length-1]) out.push(points[points.length-1]);
    return out;
  }

  function loadChart(){
    const date = el.cdate.value || new Date().toISOString().slice(0,10);
    getJSON(API+'network_timeseries.php?date='+encodeURIComponent(date)).then(j=>{
      const svg = el.svg; svg.innerHTML='';
      let pts = j.points||[]; if(!pts.length){ svg.innerHTML=''; return; }

      // Reduce clutter based on actual pixel width (≈ one point per 2px, max 900)
      const rect = el.box.getBoundingClientRect();
      const target = Math.min(900, Math.max(120, Math.floor(rect.width/2)));
      pts = decimate(pts, target);

      // ranges
      const minT = pts[0].t, maxT = pts[pts.length-1].t;
      let maxY = 0; pts.forEach(p=>{ maxY=Math.max(maxY,p.upload_mbps,p.download_mbps); });
      if (maxY<=0) maxY = 1;

      const W=1000,H=420,Px=44,Py=34; // larger padding for labels
      const X = t => Px + ((t-minT)/(maxT-minT))*(W-2*Px);
      const Y = v => H-Py - (v/maxY)*(H-2*Py);

      // axes
      const gAxes = document.createElementNS(svg.namespaceURI,'g');
      // y grid
      for(let i=0;i<=5;i++){
        const y=Y(maxY*i/5);
        const ln=document.createElementNS(svg.namespaceURI,'line');
        ln.setAttribute('x1',Px); ln.setAttribute('x2',W-Px);
        ln.setAttribute('y1',y); ln.setAttribute('y2',y);
        ln.setAttribute('stroke','#18224b'); ln.setAttribute('stroke-width','1');
        gAxes.appendChild(ln);
        const lab=document.createElementNS(svg.namespaceURI,'text');
        lab.textContent=(maxY*i/5).toFixed(0)+' Mbps';
        lab.setAttribute('x',10); lab.setAttribute('y',y-2);
        lab.setAttribute('fill','#7d8fbf'); lab.setAttribute('font-size','11');
        gAxes.appendChild(lab);
      }
      // x ticks
      const tickCount = 10;
      for(let i=0;i<=tickCount;i++){
        const t = minT + (i*(maxT-minT)/tickCount);
        const x = X(t);
        const ln=document.createElementNS(svg.namespaceURI,'line');
        ln.setAttribute('x1',x); ln.setAttribute('x2',x);
        ln.setAttribute('y1',H-Py); ln.setAttribute('y2',H-Py+6);
        ln.setAttribute('stroke','#18224b'); ln.setAttribute('stroke-width','1.2');
        gAxes.appendChild(ln);
        const lab=document.createElementNS(svg.namespaceURI,'text');
        const d=new Date(t*1000);
        const hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0');
        lab.textContent = hh+':'+mm;
        lab.setAttribute('x',x-14); lab.setAttribute('y',H-10);
        lab.setAttribute('fill','#9bb0e4'); lab.setAttribute('font-size','10');
        gAxes.appendChild(lab);
      }
      svg.appendChild(gAxes);

      function pathFor(key, color){
        let d='M '+X(pts[0].t)+' '+Y(pts[0][key]);
        for(let i=1;i<pts.length;i++) d+=' L '+X(pts[i].t)+' '+Y(pts[i][key]);
        const p=document.createElementNS(svg.namespaceURI,'path');
        p.setAttribute('d',d); p.setAttribute('fill','none'); p.setAttribute('stroke',color); p.setAttribute('stroke-width','2');
        return p;
      }
      const colU = getComputedStyle(document.documentElement).getPropertyValue('--u')||'#60a5fa';
      const colD = getComputedStyle(document.documentElement).getPropertyValue('--d')||'#f472b6';
      svg.appendChild(pathFor('upload_mbps',   colU));
      svg.appendChild(pathFor('download_mbps', colD));

      // hover (upload/download only)
      const vline=document.createElementNS(svg.namespaceURI,'line');
      vline.setAttribute('y1',Py); vline.setAttribute('y2',H-Py);
      vline.setAttribute('stroke','#93a4c7'); vline.setAttribute('stroke-width','1'); vline.setAttribute('opacity','0');
      svg.appendChild(vline);

      const dotU=document.createElementNS(svg.namespaceURI,'circle');
      const dotD=document.createElementNS(svg.namespaceURI,'circle');
      [dotU,dotD].forEach(c=>{ c.setAttribute('r','3.5'); c.setAttribute('opacity','0'); svg.appendChild(c); });
      dotU.setAttribute('fill',colU); dotD.setAttribute('fill',colD);

      const tip=$('#chart_tip'), box=$('#chart_box');

      function nearestIdx(px){
        const frac = (px-Px)/(W-2*Px);
        const t = minT + Math.min(1,Math.max(0,frac))*(maxT-minT);
        let lo=0, hi=pts.length-1;
        while(hi-lo>1){
          const mid=(lo+hi)>>1;
          if (pts[mid].t < t) lo=mid; else hi=mid;
        }
        return (t-pts[lo].t <= pts[hi].t - t) ? lo : hi;
      }

      function showAt(x){
        const i = nearestIdx(x);
        const p = pts[i];
        const sx = X(p.t);
        vline.setAttribute('x1',sx); vline.setAttribute('x2',sx); vline.setAttribute('opacity','1');
        const yU=Y(p.upload_mbps), yD=Y(p.download_mbps);
        dotU.setAttribute('cx',sx); dotU.setAttribute('cy',yU); dotU.setAttribute('opacity','1');
        dotD.setAttribute('cx',sx); dotD.setAttribute('cy',yD); dotD.setAttribute('opacity','1');
        const d=new Date(p.t*1000);
        const hh=String(d.getHours()).padStart(2,'0'), mm=String(d.getMinutes()).padStart(2,'0'), ss=String(d.getSeconds()).padStart(2,'0');
        tip.innerHTML = `<div style="font-weight:700;margin-bottom:4px">${hh}:${mm}:${ss}</div>
          <div><span class="dot u"></span> Upload: ${p.upload_mbps.toFixed(2)} Mbps</div>
          <div><span class="dot d"></span> Download: ${p.download_mbps.toFixed(2)} Mbps</div>`;
        tip.style.display='block';
        const rect = box.getBoundingClientRect();
        let tx = sx/1000*rect.width + 10;
        let ty = 10;
        if (tx + tip.offsetWidth > rect.width) tx = rect.width - tip.offsetWidth - 10;
        tip.style.left = tx+'px'; tip.style.top = ty+'px';
      }
      function hideHover(){ vline.setAttribute('opacity','0'); dotU.setAttribute('opacity','0'); dotD.setAttribute('opacity','0'); tip.style.display='none'; }

      const overlay = document.createElementNS(svg.namespaceURI,'rect');
      overlay.setAttribute('x',0); overlay.setAttribute('y',0);
      overlay.setAttribute('width','100%'); overlay.setAttribute('height','100%');
      overlay.setAttribute('fill','transparent');
      overlay.addEventListener('mousemove',e=>{
        const rect=svg.getBoundingClientRect();
        const x=e.clientX-rect.left;
        showAt(x);
      });
      overlay.addEventListener('mouseleave',hideHover);
      svg.appendChild(overlay);
    }).catch(()=>{});
  }

  // ===== boot =====
  const todayISO = new Date().toISOString().slice(0,10);
  $('#chart_date').value = todayISO;
  $('#chart_refresh').addEventListener('click',()=>{ loadChart(); refreshTodayAvgMax(); });

  // initial loads
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
    // first fills
    refreshNowFromServer(); refreshTodayAvgMax();
    // realtime loop (≈3s)
    setInterval(()=>{ sampleAll().then(refreshNowFromServer).catch(()=>{}); }, 3000);
    // avg/max loop
    setInterval(refreshTodayAvgMax, 15000);
  }).catch(e=>{ notes.textContent='Error: '+e.message; });

  loadPeaks();
  loadChart();
})();
