<?php /** Step 3: Review & Payment */ ?>
<div class="step3-review">
  <h3>Review & Confirm</h3>
  <?php if(!env('STRIPE_SECRET') && !env('STRIPE_SK')): ?>
    <div style="background:#fff3cd;color:#92400e;padding:.75rem 1rem;border:1px solid #fcd34d;border-radius:4px;font-size:.75rem;margin-bottom:1rem;">
      <strong>Payment configuration missing:</strong> Add <code>STRIPE_SECRET</code> and optional <code>STRIPE_WEBHOOK_SECRET</code> to your .env file to enable live checkout. Currently running in no-payment mode.
    </div>
  <?php endif; ?>
  <div class="review-grid">
    <section>
      <h4>Employer</h4>
      <p><strong>Name:</strong> <?= htmlspecialchars($state['employer_name']) ?></p>
      <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($state['employer_address'])) ?></p>
    </section>
    <section>
      <h4>Employee</h4>
      <p><strong>Name:</strong> <?= htmlspecialchars($state['employee_name']) ?></p>
      <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($state['employee_address'])) ?></p>
      <p><strong>SSN (last4):</strong> ****<?= htmlspecialchars($state['employee_ssn_last4'] ?? '') ?></p>
    </section>
    <section>
      <h4>Pay Details</h4>
      <p><strong>Schedule:</strong> <?= htmlspecialchars($state['pay_schedule']) ?></p>
      <?php if(($state['pay_type'] ?? 'hourly')==='hourly'): ?>
        <p><strong>Hourly Rate:</strong> $<?= htmlspecialchars(number_format($state['hourly_rate'] ?? 0,2)) ?></p>
        <p><strong>Hours / Period:</strong> <?= htmlspecialchars($state['hours_per_period'] ?? 0) ?></p>
      <?php else: ?>
        <p><strong>Annual Salary:</strong> $<?= htmlspecialchars(number_format($state['annual_salary'] ?? 0,2)) ?></p>
      <?php endif; ?>
      <p><strong>Stubs:</strong> <?= (int)($state['stubs_count'] ?? 1) ?></p>
      <p><strong>Template:</strong> <?= htmlspecialchars($state['template_key'] ?? 'horizontal_blue') ?></p>
    </section>
    <section>
      <h4>Pricing</h4>
      <ul class="price-lines">
        <li>Base Unit: $<?= number_format($breakdown['unit_price'],2) ?> x <?= (int)$state['stubs_count'] ?> = $<?= number_format($breakdown['subtotal'],2) ?></li>
        <?php if(!empty($breakdown['discount_amount'])): ?>
          <li>Discount: -$<?= number_format($breakdown['discount_amount'],2) ?></li>
        <?php endif; ?>
        <li><strong>Total: $<?= number_format($price,2) ?></strong></li>
      </ul>
    </section>
  </div>
  <div class="terms-box">
    <div style="margin-bottom:.75rem;">
      <label for="buyer_email"><strong>Receipt Email</strong></label>
      <input type="email" id="buyer_email" name="buyer_email" value="<?= htmlspecialchars($state['buyer_email'] ?? '') ?>" required placeholder="you@example.com">
    </div>
    <label><input type="checkbox" name="accept_terms" value="1" required <?= !empty($state['accept_terms'])?'checked':'' ?>> I confirm the information is accurate and I agree to the Terms.</label>
  </div>
  <div class="final-actions">
  <input type="hidden" name="order_id" value="<?= htmlspecialchars($state['order_id'] ?? '') ?>">
  <button type="submit" class="btn primary">Pay & Generate →</button>
    <button type="button" class="btn ghost" id="openFullPreview">Full Preview</button>
  </div>
</div>
<script>
 (function(){
   const fp=document.getElementById('openFullPreview');
   if(fp){ fp.addEventListener('click',()=>{ document.dispatchEvent(new CustomEvent('openFullPreview')); }); }
 })();
</script>
