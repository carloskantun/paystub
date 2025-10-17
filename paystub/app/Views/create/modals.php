<?php /** Shared modals for /create wizard */ ?>
<div class="modal" id="fullPreviewModal" hidden>
  <div class="modal-backdrop" data-close></div>
  <div class="modal-dialog modal-xl">
    <header style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;">
      <h4 style="margin:0;">Full Preview</h4>
      <div style="display:flex;gap:.5rem;align-items:center;">
        <button type="button" id="fpPrev" class="btn-secondary" style="padding:.3rem .5rem;">◀</button>
        <span id="fpIndex" style="font-size:.75rem;color:#94a3b8;">1/1</span>
        <button type="button" id="fpNext" class="btn-secondary" style="padding:.3rem .5rem;">▶</button>
        <button type="button" data-close aria-label="Close" class="btn-secondary">Close</button>
      </div>
    </header>
    <div class="modal-body" id="fullPreviewBody"></div>
  </div>
</div>
<script>
 (function(){
   const modal=document.getElementById('fullPreviewModal');
   let idx=0; let total=1; let cachedPages=[];
   function open(){ modal.hidden=false; document.body.classList.add('modal-open'); load(); }
   function close(){ modal.hidden=true; document.body.classList.remove('modal-open'); }
   function load(){
     const tpl=document.querySelector('input[name=template_key]:checked')?.value||'classic_black';
     const url='/create/preview?template='+encodeURIComponent(tpl)+'&full=1&t='+(Date.now());
     const c=document.getElementById('fullPreviewBody'); c.innerHTML='<div style="padding:.75rem;color:#94a3b8;font-size:.9rem;">Loading…</div>';
     fetch(url,{credentials:'same-origin'})
      .then(r=>r.ok?r.text():Promise.reject(r.status))
      .then(html=>{
        // Split pages by page-break markers
        const tmp=document.createElement('div'); tmp.innerHTML=html;
        cachedPages = Array.from(tmp.querySelectorAll('.stub-page')).map(el=>el.outerHTML);
        total = Math.max(1, cachedPages.length); idx = 0; renderPage(); updateNav();
      })
      .catch(()=>{ c.innerHTML='<div style="padding:.75rem;color:#ef4444;font-size:.9rem;">Preview unavailable.</div>'; });
   }
   function renderPage(){ document.getElementById('fullPreviewBody').innerHTML = cachedPages[idx]||''; }
   function updateNav(){ document.getElementById('fpIndex').textContent=(idx+1)+'/'+total; document.getElementById('fpPrev').disabled=idx===0; document.getElementById('fpNext').disabled=idx===total-1; }
   document.addEventListener('openFullPreview', open);
   modal.addEventListener('click', e=>{ if(e.target.matches('[data-close]')) close(); });
   document.getElementById('fpPrev').addEventListener('click',()=>{ if(idx>0){ idx--; renderPage(); updateNav(); }});
   document.getElementById('fpNext').addEventListener('click',()=>{ if(idx<total-1){ idx++; renderPage(); updateNav(); }});
   function escapeHtml(str){ return str.replace(/[&<>"']/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[s])); }
 })();
</script>
