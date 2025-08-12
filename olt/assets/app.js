(function(){
  const PONS = window.PONS || [1,2,3,4,5,6,7,8];
  const tbody   = document.getElementById('body');
  const notesEl = document.getElementById('notes');
  const countEl = document.getElementById('count');
  document.getElementById('snap').textContent = new Date().toISOString().replace('T',' ').slice(0,19);

  const esc = s => (s??'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
  const normDesc = s => String(s||'').toLowerCase().replace(/\u00a0/g,' ').replace(/[_\-]+/g,' ').replace(/\s+/g,' ').trim();
  const normOnu  = s => String(s||'').toUpperCase().replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim();

  const allRows = [];
  const rowsByKey = new Map();   // "pon-onu" -> <tr> (WAN)
  const rowsByOnu = new Map();   // onuid_norm -> <tr> (Avg/Delta)

  function addRow(r){
    const key = (r.pon!=null && r.onu!=null) ? `${r.pon}-${r.onu}` : `x-${Math.random()}`;
    const tr = document.createElement('tr');
    tr.className = `pon-${r.pon||''}`;
    tr.dataset.pon = r.pon ?? '';
    tr.dataset.onu = r.onu ?? '';
    tr.dataset.desc = normDesc(r.desc||'');
    tr.dataset.onuid = normOnu(r.onuid||'');

    tr.innerHTML = `
      <td class="mono">${r.pon ?? ''}</td>
      <td class="mono">${r.onu ?? ''}</td>
      <td class="mono">${esc(r.onuid||'')}</td>
      <td>${esc(r.desc||'')}</td>
      <td>${esc(r.model||'')}</td>
      <td>${esc(r.info||'')}</td>
      <td class="${/online/i.test(r.status||'')?'ok':'bad'}">${esc(r.status||'')}</td>
      <td id="wan-${key}" class="dim">…</td>
      <td id="rx-${key}" class="dim">…</td>
      <td id="avg-${key}" class="dim">N/A</td>
      <td id="delta-${key}" class="delta-ok">—</td>
    `;
    tbody.appendChild(tr);
    allRows.push(tr);
    if (r.pon!=null && r.onu!=null) rowsByKey.set(key, tr);
    if (r.onuid) rowsByOnu.set(normOnu(r.onuid), tr);
  }

  // search filter
  const search = document.getElementById('search');
  search.addEventListener('input', () => {
    const q = normDesc(search.value); let shown=0;
    allRows.forEach(tr => {
      const hit = !q || tr.dataset.desc.includes(q);
      tr.style.display = hit ? '' : 'none';
      if (hit) shown++;
    });
    countEl.textContent = `${shown} shown`;
  });

  function colorRxCell(el, val){
    let cls='rx-warn';
    if (val<=-28) cls='rx-bad'; else if (val<=-23) cls='rx-warn'; else if (val<=-8) cls='rx-good'; else cls='rx-warn';
    el.className = cls; el.textContent = Number(val).toFixed(2);
  }

  async function getJSON(url, tries=2){
    for (let i=0;i<tries;i++){
      try{
        const r = await fetch(url, {cache:'no-store'});
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return await r.json();
      }catch(e){
        if (i===tries-1) throw e;
        await new Promise(res=>setTimeout(res, 300));
      }
    }
  }

  function applyAverages(avgMap){
    // avgMap: { onuid_norm: {avg, cnt} }
    rowsByKey.forEach((tr, key)=>{
      const idn = tr.dataset.onuid;
      const rxCell = tr.querySelector(`#rx-${key}`);
      const avgCell = tr.querySelector(`#avg-${key}`);
      const deltaCell = tr.querySelector(`#delta-${key}`);

      const entry = avgMap[idn];
      if (!entry) {
        avgCell.textContent = 'N/A'; avgCell.className = 'dim';
        deltaCell.textContent = '—'; deltaCell.className = 'delta-ok';
        return;
      }
      const avg = Number(entry.avg);
      avgCell.textContent = isFinite(avg) ? avg.toFixed(2) : 'N/A';
      avgCell.className = isFinite(avg) ? '' : 'dim';

      const rx = Number(rxCell?.textContent);
      if (!isFinite(rx)) {
        deltaCell.textContent = '—'; deltaCell.className = 'delta-ok';
        return;
      }
      const delta = rx - avg; // signed
      const absd = Math.abs(delta);
      let cls = 'delta-ok';
      let icon = '';
      if (absd >= 2) { cls = 'delta-bad';  icon = '⚠️'; }
      else if (absd >= 1) { cls = 'delta-warn'; icon = '⚠️'; }

      deltaCell.className = cls;
      deltaCell.innerHTML = (isFinite(delta) ? (delta>=0?'+':'')+delta.toFixed(2)+' dB' : '—')
                            + (icon ? ` <span class="warn-icon" title="Deviation vs 24h avg">${icon}</span>` : '');
    });
  }

  (async function run(){
    for (const pon of PONS){
      notesEl.textContent = `Loading PON ${pon} (auth)…`;
      try{
        const auth = await getJSON(`api/auth.php?pon=${pon}`);
        if (!auth.ok) throw new Error(auth.error || 'auth failed');
        (auth.rows || []).forEach(addRow);
        search.dispatchEvent(new Event('input'));

        // expected ONU IDs help optical endpoint pick the right payload
        const ids = (auth.rows || []).map(r => (r.onuid || '')).map(s => s.toUpperCase().replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim()).filter(Boolean);
        const idsParam = encodeURIComponent(ids.join('|'));

        // RX by ONU ID
        notesEl.textContent = `Loading PON ${pon} (optical)…`;
        try{
          const opt = await getJSON(`api/optical.php?pon=${pon}&ids=${idsParam}`);
          if (opt.ok){
            const rxByOnu = new Map();
            (opt.rx || []).forEach(i=>{
              const idn = (i.onuid || i.onuid_norm || '').toUpperCase().replace(/\u00a0/g,' ').replace(/\s+/g,' ').trim();
              if (idn) rxByOnu.set(idn, i.rx);
            });
            rowsByKey.forEach((tr,key)=>{
              if (Number(tr.dataset.pon)!==pon) return;
              const idn = tr.dataset.onuid;
              const rxCell = tr.querySelector(`#rx-${key}`);
              const v = rxByOnu.get(idn);
              if (v===undefined || v===null || isNaN(Number(v))) { rxCell.textContent='N/A'; rxCell.className='dim'; }
              else { colorRxCell(rxCell, Number(v)); }
            });
          }
        }catch(e){ /* leave RX as N/A */ }

        // WAN
        notesEl.textContent = `Loading PON ${pon} (WAN)…`;
        const tasks=[];
        rowsByKey.forEach((tr,key)=>{
          const p = Number(tr.dataset.pon); if (p!==pon) return;
          const o = Number(tr.dataset.onu);
          const el = tr.querySelector(`#wan-${key}`);
          const statusTxt = tr.children[6].textContent || '';
          if (/online/i.test(statusTxt)){
            tasks.push(async ()=>{
              try{
                const r = await getJSON(`api/wan.php?pon=${p}&onu=${o}`);
                const stillOnline = /online/i.test(tr.children[6].textContent || '');
                if (!stillOnline){ el.textContent='N/A'; el.className='dim'; return; }
                el.textContent = (r.ok && r.status) ? r.status : 'Unknown';
                el.className = /connect/i.test(el.textContent) ? 'ok' : 'bad';
              }catch(e){ el.textContent='Unknown'; el.className='dim'; }
            });
          }else{ el.textContent='N/A'; el.className='dim'; }
        });
        await runPool(tasks, 4);
      }catch(e){
        const tr = document.createElement('tr');
        tr.className = `pon-${pon}`;
        tr.innerHTML = `<td class="mono">${pon}</td><td></td><td></td><td colspan="8" class="bad">Error loading PON ${pon}: ${esc(e.message || e)}</td>`;
        tbody.appendChild(tr);
        search.dispatchEvent(new Event('input'));
      }
    }

    // === Fetch 24h averages once and annotate all rows ===
    try{
      const avg = await getJSON(`api/avg.php?hours=24`);
      if (avg.ok) applyAverages(avg.avg || {});
    }catch(e){ /* ignore; averages stay N/A */ }

    notesEl.textContent = 'Done.';
  })();

  async function runPool(tasks, limit){
    const q = tasks.slice();
    const workers = new Array(Math.min(limit, q.length)).fill(0).map(async ()=>{
      while(q.length){ await q.shift()(); }
    });
    await Promise.all(workers);
  }
})();
