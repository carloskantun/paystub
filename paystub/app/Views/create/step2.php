<?php /** Step 2: Carousel-only template selection + live preview */ $templates = array_filter(templates_config(), fn($t)=>empty($t['hidden'])); ?>
<div class="step2-layout only-preview">
  <input type="hidden" name="template_key" id="tplInput" value="<?= htmlspecialchars($state['template_key'] ?? 'horizontal_blue') ?>">
  <aside class="preview-pane">
    <div class="pane-header">Preview <span id="ppIndex">1/<?= (int)$state['stubs_count'] ?></span></div>
    <div class="preview-stub" id="previewStub" style="padding:0;overflow:auto;">
      <div style="padding:.75rem;color:#94a3b8;font-size:.7rem;">Loading preview…</div>
    </div>
    <div class="tpl-disclaimer">All watermarks and background images will be removed from your final document.</div>
    <div class="tpl-carousel" aria-label="Template carousel">
      <button type="button" class="tc-arrow" id="tcPrev" aria-label="Previous templates" disabled>◀</button>
      <div class="tc-viewport" id="tcViewport">
        <div class="tc-track" id="tcTrack">
          <?php $currentKey = $state['template_key'] ?? 'horizontal_blue'; foreach($templates as $k=>$tpl): ?>
            <div class="tc-item<?= $currentKey===$k?' selected':'' ?>" data-key="<?= htmlspecialchars($k) ?>" role="button" tabindex="0" aria-selected="<?= $currentKey===$k?'true':'false' ?>" aria-label="Select template <?= htmlspecialchars($tpl['name']) ?>">
              <div class="tc-thumb"><?= $tpl['thumbnail_svg'] ?? '' ?></div>
              <div class="tc-name"><?= htmlspecialchars($tpl['name']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="button" class="tc-arrow" id="tcNext" aria-label="Next templates">▶</button>
    </div>
    <div class="mini-pricing" style="margin-top:1rem;">
      <div><span>Stubs</span><strong><?= (int)$state['stubs_count'] ?></strong></div>
      <div><span>Unit</span><strong>$<?= number_format($breakdown['unit_price'],2) ?></strong></div>
      <div><span>Total</span><strong>$<?= number_format($price,2) ?></strong></div>
    </div>
    <div class="stub-nav"><button type="button" id="stubPrev" disabled>◀</button><button type="button" id="stubNext" <?= $state['stubs_count']>1?'':'disabled' ?>>▶</button></div>
  </aside>
</div>
<script>
(function(){
  const tplInput = document.getElementById('tplInput');
  function syncCarouselTo(key){
    document.querySelectorAll('.tc-item').forEach(el=>{
  const is = el.getAttribute('data-key')===key;
  el.classList.toggle('selected', is);
  el.setAttribute('aria-selected', is ? 'true' : 'false');
    });
  }
  function fetchPreview(){
  const current= tplInput?.value || 'horizontal_blue';
    const url='/create/preview?template='+encodeURIComponent(current)+'&t='+(Date.now());
    const el=document.getElementById('previewStub');
    el.innerHTML='<div style="padding:.75rem;color:#94a3b8;font-size:.7rem;">Loading preview…</div>';
    fetch(url,{credentials:'same-origin'})
      .then(async r=>{ if(r.ok) return r.text(); const txt=await r.text(); throw new Error(txt||('HTTP '+r.status)); })
      .then(html=>{ el.innerHTML=html; })
      .catch(err=>{ el.innerHTML='<div style="padding:.75rem;color:#ef4444;font-size:.65rem;line-height:1.3;">Preview unavailable.<br><span style="opacity:.75">'+(err.message.replace(/[<>]/g,'')||'')+'</span></div>'; });
  }
  let idx=0; const total=<?= (int)$state['stubs_count'] ?>; const prev=document.getElementById('stubPrev'); const next=document.getElementById('stubNext'); const pp=document.getElementById('ppIndex');
  function updateNav(){ pp.textContent=(idx+1)+'/'+total; prev.disabled=idx===0; next.disabled=idx===total-1; }
  prev&&prev.addEventListener('click',()=>{ if(idx>0){idx--; updateNav(); }}); next&&next.addEventListener('click',()=>{ if(idx<total-1){idx++; updateNav(); }}); updateNav();
  // initial
  fetchPreview();
  // Carousel logic
  const vp=document.getElementById('tcViewport');
  const track=document.getElementById('tcTrack');
  const prevBtn=document.getElementById('tcPrev');
  const nextBtn=document.getElementById('tcNext');
  function focusCarouselItem(key){
    const el = track?.querySelector(`.tc-item[data-key="${CSS.escape(key)}"]`);
    if (!el || !vp) return;
    const target = Math.max(0, el.offsetLeft - (vp.clientWidth - el.clientWidth)/2);
    vp.scrollTo({left: target, behavior: 'smooth'});
  }
  function updateCarouselNav(){
    const maxScroll = track.scrollWidth - vp.clientWidth;
    prevBtn.disabled = vp.scrollLeft <= 2;
    nextBtn.disabled = vp.scrollLeft >= maxScroll - 2;
  }
  if (vp && track){
    vp.addEventListener('scroll', updateCarouselNav, {passive:true});
    prevBtn&&prevBtn.addEventListener('click',()=>{ vp.scrollBy({left: -vp.clientWidth*0.9, behavior:'smooth'}); });
    nextBtn&&nextBtn.addEventListener('click',()=>{ vp.scrollBy({left: vp.clientWidth*0.9, behavior:'smooth'}); });
    const activateItem=(item)=>{
      const key = item.getAttribute('data-key'); if(!key) return;
      if (tplInput) { tplInput.value = key; tplInput.dispatchEvent(new Event('change',{bubbles:true})); fetchPreview(); }
      document.querySelectorAll('.tc-item').forEach(el=>{
        const is = el===item; el.classList.toggle('selected', is); el.setAttribute('aria-selected', is ? 'true' : 'false');
      });
      focusCarouselItem(key);
    };
    track.addEventListener('click',(e)=>{
      const item = e.target.closest('.tc-item'); if(!item) return;
      activateItem(item);
    });
    track.addEventListener('dblclick',(e)=>{ const item=e.target.closest('.tc-item'); if(!item) return; document.dispatchEvent(new Event('openFullPreview')); });
    track.addEventListener('keydown',(e)=>{
      if (e.key!=='Enter' && e.key!==' ') return; const item=e.target.closest('.tc-item'); if(!item) return; e.preventDefault(); activateItem(item);
    });
    window.addEventListener('resize', updateCarouselNav);
    updateCarouselNav();
    // center current selection on load
    const currentSel = tplInput?.value;
    if (currentSel) focusCarouselItem(currentSel);
  }
  // Full Preview button (event delegation to ensure it works even if added later)
  document.addEventListener('click', function(e){
    var t = e.target;
    if (!t) return;
    if ((t.id && t.id === 'openFullPreview') || (t.closest && t.closest('#openFullPreview'))) {
      document.dispatchEvent(new Event('openFullPreview'));
    }
  });
})();
</script>
