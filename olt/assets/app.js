(function(){
  'use strict';

  /* --- API base set by index.php --- */
  var API_BASE = (window.API_BASE ? String(window.API_BASE) : '/olt/api/');
  API_BASE = API_BASE.replace(/\/+$/,'') + '/';
  window.__api_base_dbg = API_BASE;
  if (window.console && console.log) console.log('OLT Monitor API_BASE = ' + API_BASE);

  /* --- DOM refs --- */
  var PONS   = (window.PONS && window.PONS.length) ? window.PONS : [1,2,3,4,5,6,7,8];
  var tbody  = document.getElementById('body');
  var notes  = document.getElementById('notes');
  var count  = document.getElementById('count');
  var snapEl = document.getElementById('snap');
  if (snapEl) snapEl.textContent = new Date().toISOString().replace('T',' ').slice(0,19);

  /* --- helpers --- */
  function esc(s){ s=(s==null?'':String(s)); return s.replace(/[&<>"']/g,function(m){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];}); }
  function normDesc(s){ s=(s==null?'':String(s)).toLowerCase(); s=s.replace(/\u00a0/g,' ').replace(/[_\-]+/g,' ').replace(/\s+/g,' '); return s.trim(); }
  function normOnu(s){ s=(s==null?'':String(s)).toUpperCase(); s=s.replace(/\u00a0/g,' ').replace(/\s+/g,' '); return s.trim(); }
  function getJSON(url, tries){ if(!tries)tries=2; return new Promise(function(res,rej){ (function a(n){ fetch(url,{cache:'no-store'}).then(function(r){ if(!r.ok)throw new Error('HTTP '+r.status+' for '+url); return r.json();}).then(res).catch(function(e){ if(n+1>=tries)rej(e); else setTimeout(function(){a(n+1);},300);}); })(0);}); }
  function colorRxCell(el,val){ var cls='rx-warn'; if(val<=-28)cls='rx-bad'; else if(val<=-23)cls='rx-warn'; else if(val<=-8)cls='rx-good'; else cls='rx-warn'; el.className=cls; el.textContent=Number(val).toFixed(2); }

  /* --- row maps --- */
  var allRows=[]; var rowsByKey={}; var rowsByOnu={};

  /* --- 24h averages cache (load immediately) --- */
  var avgCache=null, avgLoaded=false;
  function fetchAveragesOnce(){ return getJSON(API_BASE+'avg.php?hours=24').then(function(avg){ if(avg&&avg.ok){ avgCache=avg.avg||{}; avgLoaded=true; applyAveragesAll(); } }).catch(function(){}); }

  function applyAvgForKey(key,tr){
    if(!tr)return;
    var idn=tr.dataset.onuid||'';
    var avgCell=tr.querySelector('#avg-'+key);
    var deltCell=tr.querySelector('#delta-'+key);
    if(!avgCell||!deltCell)return;

    if(!avgLoaded){ avgCell.textContent='…'; avgCell.className='dim'; deltCell.textContent='—'; deltCell.className='delta-ok'; return; }
    var entry=avgCache&&avgCache[idn];
    if(!entry){ avgCell.textContent='N/A'; avgCell.className='dim'; deltCell.textContent='—'; deltCell.className='delta-ok'; return; }

    var avg=Number(entry.avg);
    if(isFinite(avg)){ avgCell.textContent=avg.toFixed(2); avgCell.className=''; } else { avgCell.textContent='N/A'; avgCell.className='dim'; }

    var rxCell=tr.querySelector('#rx-'+key);
    var rx=Number(rxCell&&rxCell.textContent);
    if(!isFinite(rx)){ deltCell.textContent='—'; deltCell.className='delta-ok'; return; }

    var delta=rx-avg, absd=Math.abs(delta), cls='delta-ok', icon='';
    if(absd>=2){ cls='delta-bad'; icon='⚠️'; }
    else if(absd>=1){ cls='delta-warn'; icon='⚠️'; }
    deltCell.className=cls;
    if(isFinite(delta)){
      var txt=(delta>=0?'+':'')+delta.toFixed(2)+' dB';
      deltCell.innerHTML=txt+(icon?' <span class="warn-icon" title="Deviation vs 24h avg">'+icon+'</span>':'');
    }else{ deltCell.textContent='—'; }
  }
  function applyAveragesAll(){ for(var k in rowsByKey){ if(rowsByKey.hasOwnProperty(k)) applyAvgForKey(k,rowsByKey[k]); } }

  function addRow(r){
    var key=(r.pon!=null&&r.onu!=null)?(r.pon+'-'+r.onu):('x-'+Math.random());
    var tr=document.createElement('tr');
    tr.className='pon-'+(r.pon||'');
    tr.dataset.pon=(r.pon!=null?r.pon:'');
    tr.dataset.onu=(r.onu!=null?r.onu:'');
    tr.dataset.desc=normDesc(r.desc||'');
    tr.dataset.onuid=normOnu(r.onuid||'');

    var statusOk=/online/i.test(r.status||'');
    var html='';
    html+='<td class="mono">'+(r.pon!=null?esc(r.pon):'')+'</td>';
    html+='<td class="mono">'+(r.onu!=null?esc(r.onu):'')+'</td>';
    html+='<td class="mono">'+esc(r.onuid||'')+'</td>';
    html+='<td>'+esc(r.desc||'')+'</td>';
    html+='<td>'+esc(r.model||'')+'</td>';
    html+='<td class="'+(statusOk?'ok':'bad')+'">'+esc(r.status||'')+'</td>';
    html+='<td id="wan-'+key+'" class="dim">…</td>';
    html+='<td id="rx-'+key+'" class="dim">…</td>';
    html+='<td id="avg-'+key+'" class="dim">N/A</td>';
    html+='<td id="delta-'+key+'" class="delta-ok">—</td>';
    tr.innerHTML=html;

    tbody.appendChild(tr);
    allRows.push(tr);
    if(r.pon!=null&&r.onu!=null) rowsByKey[key]=tr;
    if(r.onuid) rowsByOnu[normOnu(r.onuid)]=tr;

    /* show 24h Avg immediately for this row */
    applyAvgForKey(key,tr);
  }

  /* search filter */
  var search=document.getElementById('search');
  if(search){ search.addEventListener('input',function(){ var q=normDesc(search.value),shown=0; for(var i=0;i<allRows.length;i++){ var tr=allRows[i]; var hit=(!q||tr.dataset.desc.indexOf(q)!==-1); tr.style.display=hit?'':'none'; if(hit)shown++; } count.textContent=shown+' shown'; }); }

  function runPool(tasks,limit){ return new Promise(function(resolve){ var q=tasks.slice(0),running=0; function next(){ while(running<limit&&q.length){ (function(t){ running++; t().then(function(){running--;next();}).catch(function(){running--;next();}); })(q.shift()); } if(running===0&&q.length===0) resolve(); } next(); }); }

  /* Load averages right away */
  fetchAveragesOnce();

  /* Main flow */
  (function run(){
    (function loadNext(i){
      if(i>=PONS.length){
        fetchAveragesOnce(); notes.textContent='Done.'; return;
      }
      var pon=PONS[i];
      notes.textContent='Loading PON '+pon+' (auth)…';

      getJSON(API_BASE+'auth.php?pon='+encodeURIComponent(pon)).then(function(auth){
        if(!auth||!auth.ok) throw new Error(auth&&auth.error?auth.error:'auth failed');
        var rows=auth.rows||[]; for(var r=0;r<rows.length;r++) addRow(rows[r]);
        if(search){ var evt=document.createEvent('Event'); evt.initEvent('input',true,true); search.dispatchEvent(evt); }

        var idsList=[]; for(var j=0;j<rows.length;j++){ var id=(rows[j].onuid||''); id=id.toUpperCase().replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim(); if(id) idsList.push(id); }
        var idsParam=encodeURIComponent(idsList.join('|'));

        notes.textContent='Loading PON '+pon+' (optical)…';
        return getJSON(API_BASE+'optical.php?pon='+encodeURIComponent(pon)+'&ids='+idsParam).then(function(opt){
          if(opt&&opt.ok){
            var rxByOnu={}; var rxlist=opt.rx||[];
            for(var k=0;k<rxlist.length;k++){ var it=rxlist[k]; var idn=(it.onuid||it.onuid_norm||''); idn=idn.toUpperCase().replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim(); if(idn) rxByOnu[idn]=it.rx; }
            for(var key in rowsByKey){ if(!rowsByKey.hasOwnProperty(key))continue; var tr=rowsByKey[key]; if(Number(tr.dataset.pon)!==Number(pon))continue; var idn2=tr.dataset.onuid; var rxCell=tr.querySelector('#rx-'+key); var v=rxByOnu.hasOwnProperty(idn2)?rxByOnu[idn2]:undefined; if(v===undefined||v===null||isNaN(Number(v))){ rxCell.textContent='N/A'; rxCell.className='dim'; } else { colorRxCell(rxCell, Number(v)); } applyAvgForKey(key,tr); }
          }
        }).catch(function(){ /* leave RX N/A */ }).then(function(){
          notes.textContent='Loading PON '+pon+' (WAN)…';
          var tasks=[];
          for(var key in rowsByKey){ if(!rowsByKey.hasOwnProperty(key))continue; var tr=rowsByKey[key]; if(Number(tr.dataset.pon)!==Number(pon))continue;
            var statusTxt=(tr.children[5]&&tr.children[5].textContent)||''; /* Status col moved (no Info col) */
            var el=tr.querySelector('#wan-'+key);
            if(/online/i.test(statusTxt)){
              (function(key,tr,el){ var p=Number(tr.dataset.pon); var o=Number(tr.dataset.onu);
                tasks.push(function(){ return getJSON(API_BASE+'wan.php?pon='+encodeURIComponent(p)+'&onu='+encodeURIComponent(o)).then(function(r){
                  var stillOnline=/online/i.test((tr.children[5]&&tr.children[5].textContent)||''); if(!stillOnline){ el.textContent='N/A'; el.className='dim'; return; }
                  el.textContent=(r&&r.ok&&r.status)?r.status:'Unknown'; el.className=(/connect/i.test(el.textContent)?'ok':'bad');
                }).catch(function(){ el.textContent='Unknown'; el.className='dim'; }); });
              })(key,tr,el);
            } else { el.textContent='N/A'; el.className='dim'; }
          }
          return runPool(tasks,4);
        });
      }).catch(function(e){
        var tr=document.createElement('tr'); tr.className='pon-'+pon;
        tr.innerHTML='<td class="mono">'+pon+'</td><td></td><td></td><td colspan="7" class="bad">Error loading PON '+pon+': '+esc(e&&e.message?e.message:e)+'</td>';
        tbody.appendChild(tr);
        if(search){ var evt2=document.createEvent('Event'); evt2.initEvent('input',true,true); search.dispatchEvent(evt2); }
      }).then(function(){ loadNext(i+1); });
    })(0);
  })();
})();
