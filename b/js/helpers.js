(function(){
  'use strict';
  const API = (window.API_BASE||'./api/').replace(/\/+$/,'')+'/';

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

  // animated number
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

  window.App = { API,$,esc,idSafe,normOnu,getJSON,fmtMBGB,fMbps,fmtBytesNice,animateNumber,setBarsLevel };
})();
