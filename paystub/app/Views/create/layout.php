<?php
/** @var int $step */
/** @var array $errors */
/** @var array $state */
/** @var array $periods */
/** @var float $price */
/** @var array $breakdown */
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Create Pay Stubs</title><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app.css"></head><body class="create-wizard">
<header class="wizard-header"><div class="logo">createpaystubdocs<span style="opacity:.5;font-weight:400">.com</span></div><nav class="wizard-steps"><div class="wiz-step <?= $step===1?'active':($step>1?'done':'') ?>"><span>1</span><label>Enter Info</label></div><div class="wiz-step <?= $step===2?'active':($step>2?'done':'') ?>"><span>2</span><label>Preview & Template</label></div><div class="wiz-step <?= $step===3?'active':'' ?>"><span>3</span><label>Review & Pay</label></div></nav></header>
<main class="wizard-main">
  <?php if($errors): ?><div class="wizard-errors"><strong>Fix the following:</strong><ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" action="?step=<?= $step ?>" class="wizard-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
  <?php if ($step===1) { include __DIR__.'/step1.php'; } elseif ($step===2) { include __DIR__.'/step2.php'; } else { include __DIR__.'/step3.php'; } ?>
    <footer class="wizard-footer">
      <div class="wf-left">
        <button type="submit" name="nav" value="prev" class="btn-secondary" <?= $step===1?'disabled':'' ?>>← Previous</button>
      </div>
	<div class="wf-center status-badge" aria-live="polite" style="font-size:.7rem;letter-spacing:.05em;"><?= $errors? count($errors).' issue(s)':'All good' ?></div>
      <div class="wf-right">
        <?php if ($step===2): ?><button type="button" id="openFullPreview" class="btn-secondary" style="margin-right:.5rem;">Full Preview</button><?php endif; ?>
  <?php if ($step<3): ?><button type="submit" name="nav" value="next" class="btn-primary" id="nextBtn" <?= $errors?'disabled':'' ?>>Next →</button><?php else: ?><button type="submit" class="btn-primary" <?= $errors?'disabled':'' ?>>Pay & Generate →</button><?php endif; ?>
      </div>
    </footer>
  </form>
</main>
<?php include __DIR__.'/modals.php'; ?>
<script>
// Simple enable/disable of next based on required fields (client hint)
(function(){
  const step = <?= (int)$step ?>;
  if(step===1){
    const form=document.querySelector('.wizard-form');
    const nextBtn=document.getElementById('nextBtn');
    const ssnField=form.querySelector('[name="employee_ssn_last4"]');
    function periodsPresent(){ return form.querySelectorAll('.period-row input[name="period_start[]"]').length>0; }
    function validate(){
      let errors=[];
      function req(name){ const el=form.querySelector('[name="'+name+'"]'); if(!el||!el.value.trim()) errors.push(name+' required'); }
      req('employer_name'); req('employee_name');
      if(ssnField){ const v=ssnField.value.trim(); if(!/^\d{4}$/.test(v)) errors.push('SSN last 4 invalid'); }
      if(!periodsPresent()) errors.push('No periods');
      if(nextBtn) nextBtn.disabled=errors.length>0; // Display summary count in center
  const center=document.querySelector('.wf-center'); if(center){ const label=errors.length?errors.length+' issue(s)':'All good'; center.textContent=window.matchMedia('(max-width:640px)').matches && errors.length? '!' : label; }
      // Inline error for SSN
      let hint=ssnField.parentElement.querySelector('.field-hint');
      if(!hint){ hint=document.createElement('div'); hint.className='field-hint'; hint.style.cssText='font-size:.55rem;color:#f87171;margin-top:2px;'; ssnField.parentElement.appendChild(hint);} 
      if(errors.find(e=>e.includes('SSN'))){ hint.textContent='Enter exactly 4 digits.'; } else { hint.textContent=''; }
    }
    form.addEventListener('input', validate); form.addEventListener('change', validate); validate();
  }
  if(step===2){
    const form=document.querySelector('.wizard-form');
  function hasTemplate(){ const input=form.querySelector('input[name="template_key"]'); return !!(input && input.value && input.value.trim()); }
  function syncTemplate(){ const next=form.querySelector('.btn-primary'); if(next) next.disabled=!hasTemplate(); }
  form.addEventListener('change',syncTemplate); form.addEventListener('input',syncTemplate); syncTemplate();
    const fpBtn=document.getElementById('openFullPreview'); if(fpBtn){ fpBtn.addEventListener('click',()=>{ document.getElementById('fullPreviewModal').classList.add('active'); renderFullPreview(0); }); }
  }
})();
</script>
</body></html>
