(function(){
  'use strict';
  const { API, $, getJSON } = window.App;

  const el = { cdate:$('#chart_date'), tf:$('#chart_tf'), loadBtn:$('#chart_refresh'),
    box:$('#chart_box'), svg:$('#chart_svg'), tip:$('#chart_tip') };
  let chart = { points:[], tf:'1m' };
  const W=1000,H=420,Px=44,Py=34;

  function X(t, minT, maxT){ const span=Math.max(1,maxT-minT); return Px + ((t-minT)/span)*(W-2*Px); }
  function Y(v, maxY){ return H-Py - (v/(maxY||1))*(H-2*Py); }

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
      ln.setAttribute('stroke','#18224b'); ln.setAttribute('stroke-width','1'); gAxes.appendChild(ln);
      const lab=document.createElementNS(svg.namespaceURI,'text');
      lab.textContent=(maxY*i/5).toFixed(0)+' Mbps'; lab.setAttribute('x',10); lab.setAttribute('y',y-2);
      lab.setAttribute('fill','#7d8fbf'); lab.setAttribute('font-size','11'); gAxes.appendChild(lab);
    }
    const tickCount=10, span=maxT-minT;
    for(let i=0;i<=tickCount;i++){
      const t=minT + (i*span/tickCount), x=X(t,minT,maxT);
      const ln=document.createElementNS(svg.namespaceURI,'line');
      ln.setAttribute('x1',x); ln.setAttribute('x2',x); ln.setAttribute('y1',H-Py); ln.setAttribute('y2',H-Py+6);
      ln.setAttribute('stroke','#18224b'); ln.setAttribute('stroke-width','1.2'); gAxes.appendChild(ln);
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
    const tip=$('#chart_tip'), box=$('#chart_box');

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

  // expose
  window.App.loadPeaks = loadPeaks;
  window.App.loadChart  = loadChart;

  // wire date/tf
  document.addEventListener('DOMContentLoaded', function(){
    const todayISO = new Date().toISOString().slice(0,10);
    if ($('#chart_date')) $('#chart_date').value = todayISO;
    if ($('#chart_refresh')) $('#chart_refresh').addEventListener('click', ()=>{ loadChart(); window.App.refreshTodayUsageAndMax && window.App.refreshTodayUsageAndMax(); });
    if ($('#chart_tf')) $('#chart_tf').addEventListener('change', ()=>{ loadChart(); });
  });
})();
