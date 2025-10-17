<?php /** Minimal-first wizard step with advanced collapsibles */ ?>
<section class="wizard-step1-minimal" style="display:grid;grid-template-columns: minmax(0,660px) 320px;gap:1.75rem;align-items:start;">
  <div class="ws1-left" style="display:flex;flex-direction:column;gap:1.25rem;">
    <div class="card-block" style="padding:1.1rem 1.25rem;">
      <h3 style="margin:0 0 .75rem;display:flex;align-items:center;gap:.6rem;">Basic Info <span style="font-size:.55rem;font-weight:600;letter-spacing:.08em;color:var(--brand-muted);background:rgba(255,255,255,.08);padding:.25rem .45rem;border-radius:30px;">Fast</span></h3>
      <div class="mf-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.85rem 1rem;">
        <label>Employer Name<input name="employer_name" value="<?= htmlspecialchars($state['employer_name']) ?>" required></label>
        <label>Employee Name<input name="employee_name" value="<?= htmlspecialchars($state['employee_name']) ?>" required></label>
        <label>Email (Delivery)<input type="email" name="buyer_email" value="<?= htmlspecialchars($state['buyer_email'] ?? '') ?>" required></label>
        <label>State (opt)<input name="employee_state" value="<?= htmlspecialchars($state['employee_state'] ?? '') ?>" maxlength="2" style="text-transform:uppercase"></label>
        <label>Pay Type<select name="pay_type" id="payType"><option value="hourly" <?= $state['pay_type']==='hourly'?'selected':'' ?>>Hourly</option><option value="salary" <?= $state['pay_type']==='salary'?'selected':'' ?>>Salary</option></select></label>
        <label data-show-hourly>Hourly Rate<input type="number" step="0.01" name="hourly_rate" value="<?= htmlspecialchars($state['hourly_rate']) ?>"></label>
        <label data-show-hourly>Hours / Period<input type="number" step="0.01" name="hours_per_period" value="<?= htmlspecialchars($state['hours_per_period']) ?>"></label>
        <label data-show-salary style="display:none;">Annual Salary<input type="number" step="0.01" name="annual_salary" value="<?= htmlspecialchars($state['annual_salary']) ?>"></label>
        <label>Pay Frequency<select name="pay_schedule" id="paySchedule"><?php foreach(['weekly','biweekly','semi-monthly','monthly'] as $opt): ?><option value="<?= $opt ?>" <?= $state['pay_schedule']===$opt?'selected':'' ?>><?= $opt ?></option><?php endforeach; ?></select></label>
        <label># Stubs<input type="number" min="1" max="12" name="stubs_count" id="stubsCount" value="<?= (int)$state['stubs_count'] ?>"></label>
        <label>Anchor Pay Date<input type="date" name="pay_anchor" id="anchorDate" value="<?= htmlspecialchars($state['pay_anchor'] ?? '') ?>"></label>
      </div>
      <div class="adv-toggle" style="margin:.9rem 0 -.4rem;">
        <button type="button" id="toggleAdvanced" class="btn-secondary" style="font-size:.65rem;">Show Advanced Fields ↓</button>
      </div>
    </div>

    <div id="advancedBlocks" style="display:none;display:flex;flex-direction:column;gap:1.1rem;">
      <div class="card-block" data-adv-employer>
        <h3 style="margin:0 0 .6rem;">Employer Details</h3>
        <div class="mf-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.75rem 1rem;">
          <label>Address<textarea name="employer_address" rows="2"><?= htmlspecialchars($state['employer_address']) ?></textarea></label>
          <label>EIN<input name="employer_ein" value="<?= htmlspecialchars($state['employer_ein'] ?? '') ?>"></label>
          <label>Phone<input name="employer_phone" value="<?= htmlspecialchars($state['employer_phone'] ?? '') ?>"></label>
          <label>Logo (PNG/JPG/SVG, max 1MB)
            <input type="file" name="employer_logo" accept="image/png,image/jpeg,image/svg+xml">
            <?php if (!empty($state['employer_logo_path'])): ?>
              <span class="field-hint" style="display:block;font-size:.6rem;color:var(--brand-muted);margin-top:.25rem;">Current: <?= htmlspecialchars(basename($state['employer_logo_path'])) ?></span>
            <?php endif; ?>
          </label>
        </div>
      </div>
      <div class="card-block" data-adv-employee>
        <h3 style="margin:0 0 .6rem;">Employee Details</h3>
        <div class="mf-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.75rem 1rem;">
          <label>Address<textarea name="employee_address" rows="2"><?= htmlspecialchars($state['employee_address']) ?></textarea></label>
          <label>SSN Last 4<input name="employee_ssn_last4" maxlength="4" value="<?= htmlspecialchars($state['employee_ssn_last4']) ?>"></label>
          <label>Employee #<input name="employee_number" value="<?= htmlspecialchars($state['employee_number'] ?? '') ?>"></label>
          <label>Job Title<input name="employee_title" value="<?= htmlspecialchars($state['employee_title'] ?? '') ?>"></label>
        </div>
      </div>
      <div class="card-block" data-adv-periods>
        <h3 style="margin:0 0 .6rem;">Pay Periods</h3>
        <div class="periods-edit" style="max-height:260px;">
          <?php foreach($periods as $p): ?>
            <div class="period-row">
              <input type="date" name="period_start[]" value="<?= htmlspecialchars($p['start_date']) ?>">
              <input type="date" name="period_end[]" value="<?= htmlspecialchars($p['end_date']) ?>">
              <input type="date" name="pay_date[]" value="<?= htmlspecialchars($p['pay_date']) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <aside class="ws1-summary" style="position:sticky;top:1rem;display:flex;flex-direction:column;gap:1rem;">
    <div class="card-block" style="padding:1rem 1.05rem 1.2rem;">
      <h3 style="margin:0 0 .6rem;">Live Summary</h3>
      <div class="mini-pricing" style="margin:.4rem 0 1rem;">
        <div><span>Stubs</span><strong id="sumStubs"><?= (int)$state['stubs_count'] ?></strong></div>
        <div><span>Unit</span><strong>$<?= number_format($breakdown['unit_price'],2) ?></strong></div>
        <div><span>Total</span><strong id="sumTotal">$<?= number_format($price,2) ?></strong></div>
      </div>
      <div style="display:grid;gap:.5rem;font-size:.6rem;">
        <div><span style="display:block;color:var(--brand-muted);text-transform:uppercase;font-size:.55rem;letter-spacing:.08em;font-weight:600;">Gross / Period</span><strong style="font-size:.85rem;" id="grossPerPeriod">$0.00</strong></div>
        <div><span style="display:block;color:var(--brand-muted);text-transform:uppercase;font-size:.55rem;letter-spacing:.08em;font-weight:600;">Annualized</span><strong style="font-size:.85rem;" id="annualized">$0.00</strong></div>
        <div><span style="display:block;color:var(--brand-muted);text-transform:uppercase;font-size:.55rem;letter-spacing:.08em;font-weight:600;">Est. Net / Period</span><strong style="font-size:.85rem;" id="netEstimate">$0.00</strong></div>
      </div>
      <button type="submit" name="nav" value="next" class="btn-primary" style="margin-top:1rem;width:100%;">Next: Template →</button>
      <button type="button" id="showAdvFromSummary" class="btn-secondary" style="margin-top:.5rem;width:100%;font-size:.65rem;">Advanced Mode</button>
    </div>
    <div class="card-block" style="font-size:.6rem;line-height:1.35;">
      <strong style="font-size:.65rem;">How it works</strong><br>Enter only essentials. We auto-calculate periods and estimate taxes. Switch to Advanced to fine‑tune.
    </div>
  </aside>
</section>
<script>
(function(){
  const payType=document.getElementById('payType');
  const schedule=document.getElementById('paySchedule');
  const stubs=document.getElementById('stubsCount');
  const hr=document.querySelector('[name="hourly_rate"]');
  const hp=document.querySelector('[name="hours_per_period"]');
  const sal=document.querySelector('[name="annual_salary"]');
  const grossPer=document.getElementById('grossPerPeriod');
  const annualized=document.getElementById('annualized');
  const netEstimate=document.getElementById('netEstimate');
  const sumTotal=document.getElementById('sumTotal');
  const sumStubs=document.getElementById('sumStubs');
  const advBlocks=document.getElementById('advancedBlocks');
  const toggleBtn=document.getElementById('toggleAdvanced');
  const showAdvFromSummary=document.getElementById('showAdvFromSummary');
  const anchor=document.getElementById('anchorDate');

  // Default anchor if empty
  if(anchor && !anchor.value){ const today=new Date(); anchor.value=today.toISOString().slice(0,10); }

  function periodsPerYear(freq){ return {weekly:52, biweekly:26, 'semi-monthly':24, monthly:12}[freq]||26; }
  function defaultHours(freq){ return {weekly:40, biweekly:80, 'semi-monthly':86.67, monthly:173.33}[freq]||80; }

  function syncVisibility(){ const mode=payType.value; document.querySelectorAll('[data-show-hourly]').forEach(el=>el.style.display=mode==='hourly'?'block':'none'); document.querySelectorAll('[data-show-salary]').forEach(el=>el.style.display=mode==='salary'?'block':'none'); if(mode==='hourly' && (!hp.value||parseFloat(hp.value)<=0)) hp.value=defaultHours(schedule.value); recalc(); }
  function syncSalaryHourly(){ const freq=schedule.value; const perYear=periodsPerYear(freq); if(payType.value==='hourly'){ if(hr.value && hp.value){ sal.value=(parseFloat(hr.value)*parseFloat(hp.value)*perYear).toFixed(2);} } else { if(sal.value && hp.value){ hr.value=(parseFloat(sal.value)/(parseFloat(hp.value)*perYear)).toFixed(2);} } }
  function recalc(){ syncSalaryHourly(); const mode=payType.value; const freq=schedule.value; const perYear=periodsPerYear(freq); let gPeriod=0; if(mode==='hourly'){ const rate=parseFloat(hr.value)||0; const hours=parseFloat(hp.value)||0; gPeriod=rate*hours; } else { const annual=parseFloat(sal.value)||0; gPeriod=annual/perYear; } const annual=gPeriod*perYear; const net=gPeriod*0.8; grossPer.textContent='$'+gPeriod.toFixed(2); annualized.textContent='$'+annual.toFixed(2); netEstimate.textContent='$'+net.toFixed(2); const unit=parseFloat('<?= $breakdown['unit_price'] ?>'); const sc=parseInt(stubs.value||'1',10); sumStubs.textContent=sc; sumTotal.textContent='$'+(unit*sc).toFixed(2); }

  [payType,schedule,stubs,hr,hp,sal].forEach(el=> el && el.addEventListener('input',()=>{ if(el===schedule){ if(payType.value==='hourly'){ hp.value=defaultHours(schedule.value); } } recalc(); }));
  [payType,schedule].forEach(el=> el && el.addEventListener('change',()=>{ syncVisibility(); recalc(); }));
  stubs.addEventListener('change',recalc);
  syncVisibility(); recalc();

  toggleBtn.addEventListener('click',()=>{ const open=advBlocks.style.display==='none'; advBlocks.style.display=open?'flex':'none'; toggleBtn.textContent=open?'Hide Advanced Fields ↑':'Show Advanced Fields ↓'; });
  showAdvFromSummary.addEventListener('click',()=>{ advBlocks.style.display='flex'; toggleBtn.textContent='Hide Advanced Fields ↑'; advBlocks.scrollIntoView({behavior:'smooth',block:'start'}); });
})();
</script>
