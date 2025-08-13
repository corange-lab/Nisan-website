(function(){
  'use strict';
  var API = (window.API_BASE||'./api/').replace(/\/+$/,'')+'/';

  var sel = document.getElementById('onu');
  var status = document.getElementById('status');
  var refreshListBtn = document.getElementById('refreshList');

  function getJSON(u){ return fetch(u,{cache:'no-store'}).then(r=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json();}); }
  function esc(x){ x=(x==null?'':String(x)); return x; }
  function f2(x){ return (x==null||!isFinite(x))?'—':Number(x).toFixed(2); }

  function loadOnus(){
    status.textContent='Loading ONUs...';
    return getJSON(API+'stats_all.php').then(j=>{
      if(!j.ok) throw new Error(j.error||'failed');
      var rows = j.rows||[];
      rows.sort((a,b)=>String(a.onuid).localeCompare(String(b.onuid)));
      sel.innerHTML='';
      rows.forEach(r=>{
        var opt=document.createElement('option');
        opt.value=r.onuid; opt.textContent=r.onuid;
        sel.appendChild(opt);
      });
      status.textContent='Loaded '+rows.length+' ONUs';
    }).catch(e=>{
      status.textContent='Error: '+e.message;
    });
  }

  function updateLive(){
    var onuid = sel.value;
    if(!onuid){ status.textContent='Pick an ONU'; return; }
    status.textContent='Sampling...';
    // Optional: trigger a server-side sample if cron is not set up (comment this if cron is running)
    fetch(API+'sample_once.php').catch(()=>{});

    getJSON(API+'speed_now.php?onuid='+encodeURIComponent(onuid)).then(j=>{
      if(!j.ok) throw new Error(j.error||'failed');
      if(!j.has_data){ document.getElementById('in_mbps').textContent='—';
        document.getElementById('out_mbps').textContent='—';
        document.getElementById('tot_mbps').textContent='—';
        document.getElementById('live_dt').textContent='—';
        document.getElementById('live_ts').textContent='—';
      }else{
        document.getElementById('in_mbps').textContent=f2(j.in_mbps);
        document.getElementById('out_mbps').textContent=f2(j.out_mbps);
        document.getElementById('tot_mbps').textContent=f2(j.total_mbps);
        document.getElementById('live_dt').textContent=esc(j.dt_sec);
        document.getElementById('live_ts').textContent=new Date(j.at_ts*1000).toLocaleString();
      }
      status.textContent='OK';
    }).catch(e=>{
      status.textContent='Error: '+e.message;
    });

    var today = new Date().toISOString().slice(0,10);
    document.getElementById('day').textContent=today;
    getJSON(API+'day_stats.php?onuid='+encodeURIComponent(onuid)+'&date='+today).then(j=>{
      if(!j.ok) throw new Error(j.error||'failed');
      var inS=j.in_mbps||{}, outS=j.out_mbps||{}, totS=j.total_mbps||{};
      document.getElementById('din_min').textContent=f2(inS.min);
      document.getElementById('din_max').textContent=f2(inS.max);
      document.getElementById('din_avg').textContent=f2(inS.avg);
      document.getElementById('dout_min').textContent=f2(outS.min);
      document.getElementById('dout_max').textContent=f2(outS.max);
      document.getElementById('dout_avg').textContent=f2(outS.avg);
      document.getElementById('dtot_min').textContent=f2(totS.min);
      document.getElementById('dtot_max').textContent=f2(totS.max);
      document.getElementById('dtot_avg').textContent=f2(totS.avg);
    }).catch(e=>{
      // keep previous values, just show status
      status.textContent='(live ok) daily err: '+e.message;
    });
  }

  refreshListBtn.addEventListener('click', loadOnus);
  sel.addEventListener('change', updateLive);

  // boot
  loadOnus().then(updateLive);
  // auto-refresh every 15s
  setInterval(updateLive, 15000);
})();
