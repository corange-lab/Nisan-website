(function(){
  'use strict';
  var API_BASE = (window.API_BASE ? String(window.API_BASE) : './api/');
  API_BASE = API_BASE.replace(/\/+$/,'') + '/';

  var tbody  = document.getElementById('body');
  var notes  = document.getElementById('notes');
  var count  = document.getElementById('count');
  var search = document.getElementById('q');

  function esc(s){ s=(s==null?'':String(s)); return s.replace(/[&<>"']/g,function(m){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];}); }
  function normOnu(s){ s=(s==null?'':String(s)).toUpperCase(); s=s.replace(/\u00a0/g,' ').replace(/\s+/g,' '); return s.trim(); }
  function getJSON(url){ return fetch(url,{cache:'no-store'}).then(function(r){ if(!r.ok)throw new Error('HTTP '+r.status+' for '+url); return r.json();}); }

  function parsePort(id){
    var m = /^GPON\d+\/(\d+):(\d+)/i.exec(id||'');
    if(!m) return {port:0, onu:0};
    return {port:parseInt(m[1],10)||0, onu:parseInt(m[2],10)||0};
  }

  var all=[];
  function render(rows){
    tbody.innerHTML='';
    for(var i=0;i<rows.length;i++){
      var r=rows[i];
      var tr=document.createElement('tr');
      var html='';
      html+='<td class="mono">'+esc(r.onuid||'')+'</td>';
      html+='<td>'+esc(r.pon!=null?String(r.pon):'')+'</td>';
      html+='<td class="mono">'+esc(r.input_bytes!=null?String(r.input_bytes):'NULL')+'</td>';
      html+='<td class="mono">'+esc(r.output_bytes!=null?String(r.output_bytes):'NULL')+'</td>';
      html+='<td class="mono">'+esc(r.input_packets!=null?String(r.input_packets):'NULL')+'</td>';
      html+='<td class="mono">'+esc(r.output_packets!=null?String(r.output_packets):'NULL')+'</td>';
      tr.innerHTML=html;
      tbody.appendChild(tr);
    }
    count.textContent = rows.length+' shown';
  }

  if (search){
    search.addEventListener('input', function(){
      var q = normOnu(search.value);
      var rows = !q ? all : all.filter(function(r){ return normOnu(r.onuid||'').indexOf(q)!==-1; });
      render(rows);
    });
  }

  notes.textContent='Fetching stats for PON1–8…';
  getJSON(API_BASE+'stats_all.php').then(function(j){
    if(!j || !j.ok) throw new Error((j && j.error) || 'failed');

    all = (j.rows || []).slice();

    // Natural sort: PON asc, then port asc, then ONU number asc
    all.sort(function(a,b){
      var pa=(+a.pon||0), pb=(+b.pon||0);
      if (pa!==pb) return pa-pb;
      var aa=parsePort(a.onuid), bb=parsePort(b.onuid);
      if (aa.port!==bb.port) return aa.port-bb.port;
      return aa.onu-bb.onu;
    });

    render(all);
    notes.textContent='Done.';
  }).catch(function(e){
    notes.textContent='Error: '+e.message;
  });
})();
