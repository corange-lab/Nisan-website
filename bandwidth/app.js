(function(){
  'use strict';
  var API_BASE = (window.API_BASE? String(window.API_BASE):'../api/').replace(/\/+$/,'')+'/';
  var tbody=document.getElementById('body'), notes=document.getElementById('notes'),
      count=document.getElementById('count'), q=document.getElementById('q');

  function esc(s){ s=(s==null?'':String(s)); return s.replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }
  function norm(s){ s=(s==null?'':String(s)).toUpperCase().replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim(); return s; }
  function getJSON(u){ return fetch(u,{cache:'no-store'}).then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();}); }

  var rowsAll=[];
  function render(rows){
    tbody.innerHTML='';
    rows.forEach(r=>{
      var tr=document.createElement('tr');
      tr.innerHTML =
        '<td class="mono">'+esc(r.onuid||'')+'</td>'+
        '<td>'+esc(r.pon!=null?String(r.pon):'')+'</td>'+
        '<td class="mono">'+esc(r.input_bytes!=null?String(r.input_bytes):'NULL')+'</td>'+
        '<td class="mono">'+esc(r.output_bytes!=null?String(r.output_bytes):'NULL')+'</td>'+
        '<td class="mono">'+esc(r.input_packets!=null?String(r.input_packets):'NULL')+'</td>'+
        '<td class="mono">'+esc(r.output_packets!=null?String(r.output_packets):'NULL')+'</td>';
      tbody.appendChild(tr);
    });
    count.textContent = rows.length+' shown';
  }

  q.addEventListener('input', function(){
    var s=norm(q.value);
    var v=!s?rowsAll:rowsAll.filter(r=>norm(r.onuid||'').indexOf(s)!==-1);
    render(v);
  });

  notes.textContent='Fetching stats for PON1–8…';
  getJSON(API_BASE+'stats_all.php').then(j=>{
    if(!j || !j.ok) throw new Error(j&&j.error||'failed');
    rowsAll=(j.rows||[]).sort((a,b)=>norm(a.onuid).localeCompare(norm(b.onuid)));
    render(rowsAll);
    notes.textContent='Done.';
  }).catch(e=>{
    notes.textContent='Error: '+e.message;
  });
})();
