<?php
// Expects $old, $errors similar to previous form, plus templates_config()
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Pay Stub | createpaystubdocs.com</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/css/app.css">
  <meta name="description" content="Generate professional pay stubs instantly with createpaystubdocs.com – accurate, fast, secure.">
</head>
<body>
  <header class="site-header">
    <div class="logo">createpaystubdocs<span style="opacity:.55;font-weight:400">.com</span></div>
    <nav class="nav">
      <a href="/">Home</a>
      <a href="/create">Create</a>
      <a href="/#features">Features</a>
      <a href="/#faq">FAQ</a>
  <button type="button" id="themeToggle" style="margin-left:1.25rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;padding:.45rem .75rem;font-size:.65rem;border-radius:6px;cursor:pointer;">🌙</button>
    </nav>
  </header>
  <div class="container">
    <section class="hero">
      <p class="tagline">FAST • ACCURATE • SECURE</p>
      <h1>Instant Pay Stub Generator</h1>
      <p>Create professional, multi-period pay stubs with customizable earnings, deductions and taxes. Preview with watermark, pay securely, download final PDF and receive it by email.</p>
    </section>
    <div class="builder-layout">
      <div class="glass form-shell">
        <ul class="progress-bar" id="progressBar">
          <li class="active" data-step="1"></li>
          <li data-step="2"></li>
          <li data-step="3"></li>
          <li data-step="4"></li>
        </ul>
        <?php if (!empty($errors)): ?>
          <div class="alert-errors">
            <strong>Fix the following:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>
  <form method="POST" action="/preview" class="paystub-form" novalidate aria-describedby="live-summary" id="wizardForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="template_key" id="templateKeyHidden" value="<?= htmlspecialchars($old['template_key'] ?? 'classic_black') ?>">
        <section class="step-section active" data-step="1">
        <div style="grid-column:1 / -1;margin:0 0 .25rem;font-weight:600;font-size:.9rem;">Company & Employee</div>
        <div>
          <label>Email (delivery)</label>
          <input required type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="you@example.com">
        </div>
        <div>
          <label>Employer Name</label>
          <input required name="employer_name" value="<?= htmlspecialchars($old['employer_name'] ?? '') ?>" placeholder="Company LLC">
        </div>
        <div>
          <label>Employer Address <span style="opacity:.5;font-weight:400;">(optional)</span></label>
          <input name="employer_address" value="<?= htmlspecialchars($old['employer_address'] ?? '') ?>" placeholder="Street, City, State">
        </div>
  <div>
          <label>Employee Name</label>
          <input required name="employee_name" value="<?= htmlspecialchars($old['employee_name'] ?? '') ?>" placeholder="John Doe">
        </div>
        <div>
          <label>Employee Address <span style="opacity:.5;font-weight:400;">(optional)</span></label>
          <input name="employee_address" value="<?= htmlspecialchars($old['employee_address'] ?? '') ?>" placeholder="Address line">
        </div>
  <div>
          <label>Employee SSN Last 4 <span style="opacity:.5;font-weight:400;">(optional)</span></label>
          <input name="employee_ssn_last4" maxlength="4" value="<?= htmlspecialchars($old['employee_ssn_last4'] ?? '') ?>" placeholder="1234">
        </div>
        <div>
          <label>State <span style="opacity:.5;font-weight:400;">(opt. for state tax)</span></label>
          <select name="employee_state">
            <option value="">--</option>
            <?php foreach(['CA','NY','TX','FL','IL'] as $st): ?>
              <option value="<?= $st ?>" <?= (($old['employee_state']??'')===$st?'selected':'') ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Pay Schedule</label>
          <select name="pay_schedule">
            <?php foreach(['weekly','biweekly','semi-monthly','monthly'] as $o): ?>
              <option value="<?= $o ?>" <?= (($old['pay_schedule']??'')===$o?'selected':'') ?>><?= $o ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label># Stubs</label>
          <input type="number" min="1" max="12" name="stubs_count" value="<?= htmlspecialchars($old['stubs_count'] ?? 1) ?>">
        </div>
  <div>
          <label>Pay Type</label>
          <select name="pay_type" id="payType">
            <?php foreach(['hourly'=>'Hourly','salary'=>'Salary'] as $k=>$lbl): ?>
              <option value="<?= $k ?>" <?= (($old['pay_type']??'hourly')===$k?'selected':'') ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div data-pay-hourly>
          <label>Hourly Rate</label>
          <input type="number" step="0.01" name="hourly_rate" value="<?= htmlspecialchars($old['hourly_rate'] ?? 25) ?>" placeholder="25.00">
        </div>
        <div data-pay-hourly>
          <label>Hours / Period</label>
          <input type="number" step="0.01" name="hours_per_period" value="<?= htmlspecialchars($old['hours_per_period'] ?? 80) ?>" placeholder="80">
        </div>
        <div data-pay-salary style="display:none;">
          <label>Annual Salary</label>
          <input type="number" step="0.01" name="annual_salary" value="<?= htmlspecialchars($old['annual_salary'] ?? 60000) ?>" placeholder="60000">
        </div>
        <div>
          <label>Check # <span style="opacity:.5;font-weight:400;">(optional)</span></label>
          <input name="check_number" value="<?= htmlspecialchars($old['check_number'] ?? '') ?>" placeholder="1234">
        </div>
        </section>
        <section class="step-section" data-step="2">
          <div style="grid-column:1 / -1;margin:0 0 .25rem;font-weight:600;font-size:.9rem;">Earnings, Deductions & Taxes</div>
        <div style="grid-column:1 / -1;">
          <div class="section-title">Template</div>
          <div class="template-grid">
            <?php $templates = templates_config(); $sel = $old['template_key'] ?? 'classic_black'; foreach($templates as $k=>$tpl): ?>
              <label class="template-card">
                <input type="radio" name="template_key" value="<?= htmlspecialchars($k) ?>" <?= $sel===$k?'checked':'' ?>>
                <?php if (!empty($tpl['preview'])): ?><img src="<?= htmlspecialchars($tpl['preview']) ?>" alt="preview"><?php else: ?><div style="height:74px;display:flex;align-items:center;justify-content:center;font-size:.55rem;color:#64748b;">No preview</div><?php endif; ?>
                <span><?= htmlspecialchars($tpl['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
  <!-- Earnings -->
        <div style="grid-column:1 / -1;">
          <div class="section-title">Earnings</div>
          <table class="lines-table" id="earnings-table">
            <thead><tr><th style="width:60%">Label</th><th>Amount</th><th style="width:40px"></th></tr></thead>
            <tbody>
              <?php foreach(($old['earnings'] ?? []) as $row): ?>
                <tr>
                  <td><input name="earnings[label][]" value="<?= htmlspecialchars($row['label'] ?? '') ?>" placeholder="Regular Pay"></td>
                  <td><input name="earnings[amount][]" type="number" step="0.01" value="<?= htmlspecialchars($row['amount'] ?? '0.00') ?>"></td>
                  <td><button type="button" class="remove-line" onclick="this.closest('tr').remove()">✕</button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <button type="button" class="add-btn" data-target="earnings-table">+ Add Earning</button>
        </div>
        <!-- Deductions -->
        <div style="grid-column:1 / -1;">
          <div class="section-title">Deductions</div>
          <table class="lines-table" id="deductions-table">
            <thead><tr><th>Label</th><th>Amount</th><th style="width:40px"></th></tr></thead>
            <tbody>
              <?php foreach(($old['deductions'] ?? []) as $row): ?>
                <tr>
                  <td><input name="deductions[label][]" value="<?= htmlspecialchars($row['label'] ?? '') ?>" placeholder="401k"></td>
                  <td><input name="deductions[amount][]" type="number" step="0.01" value="<?= htmlspecialchars($row['amount'] ?? '0.00') ?>"></td>
                  <td><button type="button" class="remove-line" onclick="this.closest('tr').remove()">✕</button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <button type="button" class="add-btn" data-target="deductions-table">+ Add Deduction</button>
        </div>
        <!-- Taxes -->
        <div style="grid-column:1 / -1;">
          <div class="section-title">Taxes</div>
          <table class="lines-table" id="taxes-table">
            <thead><tr><th>Label</th><th>Amount</th><th style="width:40px"></th></tr></thead>
            <tbody>
              <?php foreach(($old['taxes'] ?? []) as $row): ?>
                <tr>
                  <td><input name="taxes[label][]" value="<?= htmlspecialchars($row['label'] ?? '') ?>" placeholder="Federal Tax"></td>
                  <td><input name="taxes[amount][]" type="number" step="0.01" value="<?= htmlspecialchars($row['amount'] ?? '0.00') ?>"></td>
                  <td><button type="button" class="remove-line" onclick="this.closest('tr').remove()">✕</button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <button type="button" class="add-btn" data-target="taxes-table">+ Add Tax</button>
        </div>
        </section>
        <section class="step-section" data-step="3">
          <div style="grid-column:1 / -1;margin:0 0 .25rem;font-weight:600;font-size:.9rem;">Pay Periods</div>
        <!-- Advanced Pay Periods Accordion -->
        <?php if (!empty($periods)): ?>
          <div style="grid-column:1 / -1; margin-top:.75rem;font-size:.65rem;color:#94a3b8;line-height:1.35;">
            Review or adjust each generated period. Click a row to expand and edit dates. All periods must have Start, End & Pay Date.
          </div>
          <div style="grid-column:1 / -1;">
            <div class="periods-actions">
              <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                <button type="button" class="btn-secondary" id="expandAllPeriods" style="font-size:.55rem;padding:.55rem .8rem;">Expand All</button>
                <button type="button" class="btn-secondary" id="collapseAllPeriods" style="font-size:.55rem;padding:.55rem .8rem;">Collapse All</button>
                <div class="periods-pager" style="display:flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.05);padding:.4rem .55rem;border-radius:8px;">
                  <button type="button" id="periodPrev" class="mini-nav" aria-label="Previous period page" style="background:#334155;border:none;color:#fff;padding:.35rem .5rem;border-radius:6px;font-size:.55rem;cursor:pointer;">◀</button>
                  <span id="periodPageLabel" style="font-size:.55rem;letter-spacing:.05em;">Page 1/1</span>
                  <button type="button" id="periodNext" class="mini-nav" aria-label="Next period page" style="background:#334155;border:none;color:#fff;padding:.35rem .5rem;border-radius:6px;font-size:.55rem;cursor:pointer;">▶</button>
                </div>
                <div class="periods-shift" style="display:flex;align-items:center;gap:.4rem;">
                  <input type="number" id="shiftDays" placeholder="±Days" style="width:80px;padding:.45rem .5rem;font-size:.55rem;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);color:inherit;border-radius:6px;" aria-label="Shift days (positive or negative)">
                  <button type="button" class="btn-secondary" id="applyShift" style="font-size:.55rem;padding:.55rem .7rem;">Shift</button>
                </div>
              </div>
            </div>
            <div id="periodsErrors" class="periods-errors" style="display:none;margin:.25rem 0 .6rem;font-size:.55rem;color:#fca5a5;background:#7f1d1d;border:1px solid #b91c1c;padding:.45rem .6rem;border-radius:6px;grid-column:1 / -1;"></div>
            <div class="periods-accordion" id="periodsAccordion">
              <?php foreach($periods as $i=>$p): ?>
                <div class="period-item<?= $i===0 ? ' open':'' ?>">
                  <button type="button" class="period-header" data-index="<?= $i ?>" aria-expanded="<?= $i===0 ? 'true':'false' ?>">
                    <span class="ph-left">
                      <strong>#<?= $i+1 ?></strong>
                      <span class="ph-range"><?= htmlspecialchars($p['start_date']) ?> → <?= htmlspecialchars($p['end_date']) ?></span>
                    </span>
                    <span class="ph-dates">Pay: <span class="ph-pay"><?= htmlspecialchars($p['pay_date']) ?></span>
                      <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                  </button>
                  <div class="period-body">
                    <div class="period-grid">
                      <div>
                        <label>Start Date</label>
                        <input name="period_start[]" type="date" value="<?= htmlspecialchars($p['start_date']) ?>" aria-label="Period #<?= $i+1 ?> start date">
                      </div>
                      <div>
                        <label>End Date</label>
                        <input name="period_end[]" type="date" value="<?= htmlspecialchars($p['end_date']) ?>" aria-label="Period #<?= $i+1 ?> end date">
                      </div>
                      <div>
                        <label>Pay Date</label>
                        <input name="pay_date[]" type="date" value="<?= htmlspecialchars($p['pay_date']) ?>" aria-label="Period #<?= $i+1 ?> pay date">
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <div style="grid-column:1 / -1;font-size:.65rem;color:#94a3b8;">No periods generated. Adjust schedule or stubs count.</div>
        <?php endif; ?>
        </section>
        <section class="step-section" data-step="4">
          <div style="grid-column:1 / -1;margin:0 0 .25rem;font-weight:600;font-size:.9rem;">Review & Submit</div>
        <div style="grid-column:1 / -1; margin-top:1.25rem;">
            <div id="live-summary" style="display:flex;flex-wrap:wrap;gap:.75rem;font-size:.65rem;">
                <div class="live-pill" data-pill="gross" style="background:rgba(255,255,255,.07);padding:.55rem .7rem;border-radius:6px;">Gross: <strong>$0.00</strong></div>
                <div class="live-pill" data-pill="ded" style="background:rgba(255,255,255,.07);padding:.55rem .7rem;border-radius:6px;">Deductions: <strong>$0.00</strong></div>
                <div class="live-pill" data-pill="tax" style="background:rgba(255,255,255,.07);padding:.55rem .7rem;border-radius:6px;">Taxes: <strong>$0.00</strong></div>
                <div class="live-pill" data-pill="net" style="background:linear-gradient(135deg,#2563eb,#6366f1);padding:.55rem .7rem;border-radius:6px;">Net: <strong>$0.00</strong></div>
                <div class="live-pill" data-pill="price" style="background:#334155;padding:.55rem .7rem;border-radius:6px;">Est. Price: <strong>$0.00</strong></div>
                <label style="display:flex;align-items:center;gap:.35rem;background:rgba(255,255,255,.05);padding:.4rem .6rem;border-radius:6px;cursor:pointer;font-weight:500;">
                  <input type="checkbox" name="auto_mode" value="1" <?= !empty($old['auto_mode'])?'checked':'' ?>> Auto-calc taxes
                </label>
            </div>
        </div>
        <div class="actions">
          <button type="button" class="btn-secondary" id="prevStep" disabled>← Back</button>
          <button type="button" class="btn-secondary" id="nextStep">Next Step →</button>
          <button type="submit" class="btn-primary" id="submitBtn" style="display:none" aria-label="Continue to preview and see watermark version">Generate Preview →</button>
        </div>
        </section>
      </form>
      </div>
      <aside class="side-preview">
        <div style="font-size:.75rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin:0 0 .6rem;">Live Preview (Lite)</div>
        <div class="mini-preview-frame"><span class="watermark-preview">PREVIEW</span><div style="text-align:left;font-size:.55rem;line-height:1.2;max-width:90%;">
          <div><strong>Employer:</strong> <span id="pvEmployer">—</span></div>
          <div><strong>Employee:</strong> <span id="pvEmployee">—</span></div>
          <div><strong>Schedule:</strong> <span id="pvSchedule">—</span></div>
          <div><strong>Pay Type:</strong> <span id="pvPayType">—</span></div>
          <div><strong>Gross/Period:</strong> <span id="pvGross">$0.00</span></div>
          <div><strong>Net/Period:</strong> <span id="pvNet">$0.00</span></div>
        </div></div>
        <div class="price-box" id="priceBoxMini" style="margin-top:1rem;">
          <div><span>Stubs</span><span id="miniStubs">0</span></div>
          <div><span>Base/Stub</span><span id="miniBase">$0.00</span></div>
          <div><span>Total</span><span class="price-total" id="miniTotal">$0.00</span></div>
          <button type="button" id="openTemplateModal" class="btn-secondary" style="margin-top:.5rem;width:100%;font-size:.6rem;padding:.55rem .6rem;">Change Template</button>
        </div>
      </aside>
    </div>
    <!-- Full Preview Modal -->
    <div class="preview-modal-backdrop" id="fullPreviewModal" aria-hidden="true">
      <div class="preview-modal" role="dialog" aria-modal="true" aria-labelledby="fullPreviewTitle">
        <div class="preview-modal-header">
          <h3 id="fullPreviewTitle">Stub Preview (Watermarked)</h3>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <div id="stubPager" style="display:none;align-items:center;gap:.4rem;">
              <button type="button" class="stub-nav" id="stubPrev" aria-label="Previous stub">◀</button>
              <span id="stubIndexLabel" style="font-size:.6rem;letter-spacing:.05em;">1/1</span>
              <button type="button" class="stub-nav" id="stubNext" aria-label="Next stub">▶</button>
            </div>
            <button type="button" class="btn-secondary" id="closeFullPreview" style="padding:.5rem .8rem;font-size:.55rem;">Close</button>
          </div>
        </div>
        <div class="preview-canvas-wrapper">
          <div class="preview-canvas" id="previewCanvas">
            <span class="big-watermark">PREVIEW</span>
            <div class="pc-row">
              <div><div class="pc-label">Employer</div><div class="pc-value" id="pcEmployer">—</div></div>
              <div><div class="pc-label">Employee</div><div class="pc-value" id="pcEmployee">—</div></div>
              <div><div class="pc-label">Schedule</div><div class="pc-value" id="pcSchedule">—</div></div>
              <div><div class="pc-label">Pay Type</div><div class="pc-value" id="pcPayType">—</div></div>
            </div>
            <div class="pc-row">
              <div><div class="pc-label">Period Start</div><div class="pc-value" id="pcStart">—</div></div>
              <div><div class="pc-label">Period End</div><div class="pc-value" id="pcEnd">—</div></div>
              <div><div class="pc-label">Pay Date</div><div class="pc-value" id="pcPay">—</div></div>
              <div><div class="pc-label">Check #</div><div class="pc-value" id="pcCheck">—</div></div>
            </div>
            <div class="pc-section">
              <div class="pc-section-title">Earnings</div>
              <table class="pc-table" id="pcEarnings"><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody></tbody></table>
            </div>
            <div class="pc-section two-cols">
              <div>
                <div class="pc-section-title">Deductions</div>
                <table class="pc-table" id="pcDeductions"><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody></tbody></table>
              </div>
              <div>
                <div class="pc-section-title">Taxes</div>
                <table class="pc-table" id="pcTaxes"><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody></tbody></table>
              </div>
            </div>
            <div class="pc-totals">
              <div><span>Gross</span><strong id="pcGross">$0.00</strong></div>
              <div><span>Deductions</span><strong id="pcDed">$0.00</strong></div>
              <div><span>Taxes</span><strong id="pcTax">$0.00</strong></div>
              <div class="pc-net"><span>Net Pay</span><strong id="pcNet">$0.00</strong></div>
            </div>
          </div>
        </div>
        <div style="text-align:right;margin-top:1rem;font-size:.55rem;color:#94a3b8;">Dynamic in-browser preview. Final PDF may vary per template.</div>
      </div>
    </div>
    <!-- Template Modal -->
    <div class="template-modal-backdrop" id="templateModal">
      <div class="template-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <h3 style="margin:0;font-size:1rem;">Select Template</h3>
          <button type="button" id="closeTemplateModal" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;">✕</button>
        </div>
        <div class="template-grid-modal" id="templateGridModal">
          <?php $templates = templates_config(); $sel = $old['template_key'] ?? 'classic_black'; foreach($templates as $k=>$tpl): ?>
            <div class="template-card-modal <?= $sel===$k?'selected':'' ?>" data-tpl="<?= htmlspecialchars($k) ?>">
              <?php if (!empty($tpl['preview'])): ?><img src="<?= htmlspecialchars($tpl['preview']) ?>" alt="preview" style="width:100%;aspect-ratio:3/2;object-fit:cover;border-radius:6px;"><?php else: ?><div style="height:110px;display:flex;align-items:center;justify-content:center;font-size:.6rem;color:#64748b;">No preview</div><?php endif; ?>
              <div style="margin-top:.4rem;font-size:.6rem;font-weight:600;letter-spacing:.05em;"><?= htmlspecialchars($tpl['name']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="text-align:right;margin-top:1rem;">
          <button type="button" class="btn-primary" id="applyTemplateBtn" style="font-size:.65rem;">Apply Template</button>
        </div>
      </div>
    </div>
    <div class="empty-state" id="features" style="margin-top:3.5rem;">
      <p><strong>Why choose us?</strong> Accurate period generation, customizable line items, PDF watermark preview, secure payments, instant download + email delivery.</p>
    </div>
  </div>
  <section id="features" class="container" style="padding-top:0;">
    <div style="margin:3rem 0 2rem;">
      <h2 style="font-size:1.4rem;margin:0 0 .75rem;">Features</h2>
      <ul style="list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem 1.75rem;font-size:.8rem;line-height:1.35;color:#cbd5e1;">
        <li><strong style="color:#fff;">Multi-Period Generator</strong><br>Automatically builds accurate pay periods for weekly, biweekly, semi‑monthly or monthly schedules.</li>
        <li><strong style="color:#fff;">Custom Line Items</strong><br>Add unlimited earnings, deductions and tax lines with instant totals.</li>
        <li><strong style="color:#fff;">Live Price Estimate</strong><br>See the price before checkout; volume discounts applied automatically.</li>
        <li><strong style="color:#fff;">Secure PDF Delivery</strong><br>Watermarked preview, then downloadable final PDF + email link after payment.</li>
        <li><strong style="color:#fff;">Version Regeneration</strong><br>Create corrected versions while retaining history and audit log.</li>
        <li><strong style="color:#fff;">Template Selection</strong><br>Choose from multiple professional PDF layouts.</li>
      </ul>
    </div>
  </section>
  <section id="faq" class="container" style="padding-top:0;">
    <div style="margin:2rem 0 3rem;">
      <h2 style="font-size:1.4rem;margin:0 0 .75rem;">FAQ</h2>
      <div style="display:grid;gap:1rem;max-width:960px;font-size:.8rem;line-height:1.45;">
        <div><strong style="color:#fff;">Do you store my generated stubs?</strong><br>We retain them temporarily for secure delivery and regeneration; you can request permanent deletion.</div>
        <div><strong style="color:#fff;">Can I create multiple stubs at once?</strong><br>Yes, pick 1–12 and we calculate each period automatically.</div>
        <div><strong style="color:#fff;">How are taxes calculated?</strong><br>You control each tax line; advanced automated tax logic can be enabled per tenant later.</div>
        <div><strong style="color:#fff;">What payment methods are supported?</strong><br>Stripe (cards, wallets). Integration layer prepared for additional providers.</div>
      </div>
    </div>
  </section>
  <footer class="footer">&copy; <?= date('Y') ?> createpaystubdocs.com – Instant Pay Stub Generator. All rights reserved.</footer>
  <script>
    // Theme toggle & persistence
    const root = document.documentElement;
    const pref = localStorage.getItem('theme');
    if (pref) root.setAttribute('data-theme', pref);
    const toggleBtn = document.getElementById('themeToggle');
    const setIcon = () => toggleBtn.textContent = root.getAttribute('data-theme')==='light' ? '🌙' : '☀️';
    setIcon();
    toggleBtn.addEventListener('click', ()=>{
        const next = root.getAttribute('data-theme')==='light' ? 'dark' : 'light';
        if (next==='dark') root.removeAttribute('data-theme'); else root.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', next==='dark' ? '' : 'light');
        setIcon();
    });
    // Dynamic add line functionality
    document.querySelectorAll('[data-target]').forEach(btn=>{
      btn.addEventListener('click',()=>{
        const id=btn.getAttribute('data-target');
        const tbody=document.querySelector('#'+id+' tbody');
        const base=id.replace('-table','');
        const tr=document.createElement('tr');
        tr.innerHTML=`<td><input name="${base}[label][]" aria-label="${base} label"></td><td><input name="${base}[amount][]" aria-label="${base} amount" type="number" step="0.01" value="0.00"></td><td><button type="button" class="remove-line" aria-label="Remove line" onclick="this.closest('tr').remove(); calculateTotals();">✕</button></td>`;
        tbody.appendChild(tr);
        calculateTotals();
      });
    });
    // Live summary calculation
    function sumInputs(selector){
        let total=0; document.querySelectorAll(selector).forEach(i=>{ const v=parseFloat(i.value); if(!isNaN(v)) total+=v; }); return total; }
    function priceEstimate(stubs){
        const base = 5.00; // fallback
        let per = base;
        // Mirror pricing service logic (template surcharge will reapply in preview)
        if (stubs >= 10) per *= 0.85; else if (stubs >=5) per*=0.90; else per*=1.0;
        return per * stubs; }
    function calculateTotals(){
        const gross = sumInputs('#earnings-table input[name="earnings[amount][]"]');
        const ded = sumInputs('#deductions-table input[name="deductions[amount][]"]');
        const tax = sumInputs('#taxes-table input[name="taxes[amount][]"]');
        const net = gross - ded - tax;
        const stubs = parseInt(document.querySelector('input[name="stubs_count"]').value||'1',10);
        const price = priceEstimate(stubs);
        const fmt=n=>'$'+(n||0).toFixed(2);
        document.querySelector('[data-pill="gross"] strong').textContent=fmt(gross);
        document.querySelector('[data-pill="ded"] strong').textContent=fmt(ded);
        document.querySelector('[data-pill="tax"] strong').textContent=fmt(tax);
        document.querySelector('[data-pill="net"] strong').textContent=fmt(net);
        document.querySelector('[data-pill="price"] strong').textContent=fmt(price);
    }
    // Pay type toggle visibility + auto earning suggestion
    const payTypeSel = document.getElementById('payType');
    function updatePayType(){
        const mode = payTypeSel.value;
        document.querySelectorAll('[data-pay-hourly]').forEach(el=>el.style.display = mode==='hourly' ? 'block':'none');
        document.querySelectorAll('[data-pay-salary]').forEach(el=>el.style.display = mode==='salary' ? 'block':'none');
        // If no earnings rows yet, create one based on selection
        const earningsTbody = document.querySelector('#earnings-table tbody');
        if (earningsTbody.children.length===0) {
            let amount = 0;
            if (mode==='salary') {
              const annual = parseFloat(document.querySelector('input[name="annual_salary"]').value||'0');
              const sch = document.querySelector('select[name="pay_schedule"]').value;
              const map = {weekly:52,biweekly:26,'semi-monthly':24,monthly:12};
              const periodsYear = map[sch]||26; amount = annual/periodsYear;
            } else {
              const rate = parseFloat(document.querySelector('input[name="hourly_rate"]').value||'0');
              const hours = parseFloat(document.querySelector('input[name="hours_per_period"]').value||'0');
              amount = rate*hours;
            }
            const tr=document.createElement('tr');
            tr.innerHTML=`<td><input name="earnings[label][]" value="Regular Income"></td><td><input name="earnings[amount][]" type="number" step="0.01" value="${amount.toFixed(2)}"></td><td><button type="button" class="remove-line" onclick="this.closest('tr').remove();calculateTotals();">✕</button></td>`;
            earningsTbody.appendChild(tr);
            calculateTotals();
        }
    }
    payTypeSel.addEventListener('change', ()=>{updatePayType();});
    updatePayType();
    document.addEventListener('input', e=>{
        if (e.target.closest('#earnings-table, #deductions-table, #taxes-table') || e.target.name==='stubs_count') {
            calculateTotals();
        }
    });
    // Initial calc
    calculateTotals();
    // Wizard logic
    const steps = Array.from(document.querySelectorAll('.step-section'));
    let currentStep = 0;
    const btnPrev = document.getElementById('prevStep');
    const btnNext = document.getElementById('nextStep');
    const btnSubmit = document.getElementById('submitBtn');
    const progressBar = document.getElementById('progressBar').children;
    function updateStep(delta){
      if (typeof delta === 'number'){currentStep += delta;}
      if (currentStep < 0) currentStep=0;
      if (currentStep >= steps.length) currentStep=steps.length-1;
      steps.forEach((s,i)=>s.classList.toggle('active', i===currentStep));
      btnPrev.disabled = currentStep===0;
      const last = currentStep===steps.length-1;
      btnNext.style.display = last?'none':'inline-block';
      btnSubmit.style.display = last?'inline-block':'none';
      Array.from(progressBar).forEach((li,i)=>{
        li.classList.toggle('active', i===currentStep);
        li.classList.toggle('completed', i<currentStep);
      });
      updateMiniPreview();
    }
    btnPrev.addEventListener('click', ()=>updateStep(-1));
    btnNext.addEventListener('click', ()=>updateStep(1));
    // Mini preview updates
    function updateMiniPreview(){
      const g = sumInputs('#earnings-table input[name="earnings[amount][]"]');
      const ded = sumInputs('#deductions-table input[name="deductions[amount][]"]');
      const tax = sumInputs('#taxes-table input[name="taxes[amount][]"]');
      const net = g - ded - tax;
      document.getElementById('pvEmployer').textContent = document.querySelector('input[name="employer_name"]').value||'—';
      document.getElementById('pvEmployee').textContent = document.querySelector('input[name="employee_name"]').value||'—';
      document.getElementById('pvSchedule').textContent = document.querySelector('select[name="pay_schedule"]').value;
      document.getElementById('pvPayType').textContent = document.getElementById('payType').value;
      document.getElementById('pvGross').textContent = '$'+g.toFixed(2);
      document.getElementById('pvNet').textContent = '$'+net.toFixed(2);
      const stubs = parseInt(document.querySelector('input[name="stubs_count"]').value||'1',10);
      document.getElementById('miniStubs').textContent = stubs;
      const basePer = parseFloat('<?= htmlspecialchars(env('PRICE_PER_STUB','5.00')) ?>');
      document.getElementById('miniBase').textContent = '$'+basePer.toFixed(2);
      document.getElementById('miniTotal').textContent = document.querySelector('[data-pill="price"] strong').textContent;
    }
    document.addEventListener('input', e=>{ if (e.target.form && e.target.form.id==='wizardForm'){ updateMiniPreview(); }});
    updateMiniPreview();
    // Template modal logic
    const modal = document.getElementById('templateModal');
    document.getElementById('openTemplateModal').addEventListener('click', ()=>{modal.style.display='flex';});
    document.getElementById('closeTemplateModal').addEventListener('click', ()=>{modal.style.display='none';});
    let selectedTpl = document.getElementById('templateKeyHidden').value;
    document.querySelectorAll('.template-card-modal').forEach(card=>{
      card.addEventListener('click',()=>{
        document.querySelectorAll('.template-card-modal').forEach(c=>c.classList.remove('selected'));
        card.classList.add('selected');
        selectedTpl = card.getAttribute('data-tpl');
      });
    });
    document.getElementById('applyTemplateBtn').addEventListener('click',()=>{
      document.getElementById('templateKeyHidden').value = selectedTpl;
      try { localStorage.setItem('template_key', selectedTpl);} catch(e){}
      modal.style.display='none';
    });
    // Load persisted template key
    (function(){ try { const stored = localStorage.getItem('template_key'); if(stored){ document.getElementById('templateKeyHidden').value = stored; selectedTpl = stored; } } catch(e){} })();
    // Periods accordion logic + pagination + shift + validation
    (function(){
      const acc = document.getElementById('periodsAccordion');
      if(!acc) return;
      const errBox = document.getElementById('periodsErrors');
      acc.querySelectorAll('.period-header').forEach(btn=>{
        btn.addEventListener('click',()=>{
          const item = btn.closest('.period-item');
          const open = item.classList.contains('open');
          if(open){ item.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
          else { item.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
        });
      });
      const expandAll = document.getElementById('expandAllPeriods');
      const collapseAll = document.getElementById('collapseAllPeriods');
      if(expandAll) expandAll.addEventListener('click',()=>{
        acc.querySelectorAll('.period-item').forEach(it=>{it.style.display='block'; it.classList.add('open'); it.querySelector('.period-header').setAttribute('aria-expanded','true');});
        page=0; renderPage();
      });
      if(collapseAll) collapseAll.addEventListener('click',()=>{
        acc.querySelectorAll('.period-item').forEach(it=>{it.classList.remove('open'); it.querySelector('.period-header').setAttribute('aria-expanded','false');});
      });
      // Sync header text when dates change
      acc.addEventListener('input', e=>{
        if(e.target.matches('input[type="date"]')){
          const body = e.target.closest('.period-body');
          if(!body) return; const item = body.closest('.period-item');
          const start = body.querySelector('input[name="period_start[]"]').value;
          const end = body.querySelector('input[name="period_end[]"]').value;
          const pay = body.querySelector('input[name="pay_date[]"]').value;
          item.querySelector('.ph-range').textContent = (start||'????-??-??') + ' → ' + (end||'????-??-??');
          item.querySelector('.ph-pay').textContent = pay || '????-??-??';
          validatePeriods();
        }
      });
      const items = Array.from(acc.querySelectorAll('.period-item'));
      const perPage = 4;
      let page = 0;
      const pagerLabel = document.getElementById('periodPageLabel');
      const btnPrevP = document.getElementById('periodPrev');
      const btnNextP = document.getElementById('periodNext');
      function renderPage(){
        if(items.length <= perPage){
          if(pagerLabel) pagerLabel.textContent = 'Page 1/1';
          if(btnPrevP) btnPrevP.disabled=true; if(btnNextP) btnNextP.disabled=true;
          items.forEach(it=>it.style.display='block');
          return;
        }
        const totalPages = Math.ceil(items.length/perPage);
        page = Math.min(Math.max(0,page), totalPages-1);
        items.forEach((it,i)=>{ const show = Math.floor(i/perPage)===page; it.style.display = show ? 'block':'none'; });
        if(pagerLabel) pagerLabel.textContent = `Page ${page+1}/${totalPages}`;
        if(btnPrevP) btnPrevP.disabled = page===0;
        if(btnNextP) btnNextP.disabled = page===totalPages-1;
      }
      if(btnPrevP) btnPrevP.addEventListener('click',()=>{ page--; renderPage(); });
      if(btnNextP) btnNextP.addEventListener('click',()=>{ page++; renderPage(); });
      renderPage();
      // Bulk shift logic
      const shiftBtn = document.getElementById('applyShift');
      if(shiftBtn){
        shiftBtn.addEventListener('click',()=>{
          const days = parseInt(document.getElementById('shiftDays').value||'0',10);
            if(!days) return;
          items.forEach(it=>{
            const body = it.querySelector('.period-body');
            const updateDate = sel => {
              const input = body.querySelector(sel);
              if(!input || !input.value) return;
              const d = new Date(input.value);
              if(isNaN(d.getTime())) return; d.setDate(d.getDate()+days);
              const m=(d.getMonth()+1).toString().padStart(2,'0'); const da=d.getDate().toString().padStart(2,'0');
              input.value = `${d.getFullYear()}-${m}-${da}`;
            };
            updateDate('input[name="period_start[]"]');
            updateDate('input[name="period_end[]"]');
            updateDate('input[name="pay_date[]"]');
            const start = body.querySelector('input[name="period_start[]"]').value;
            const end = body.querySelector('input[name="period_end[]"]').value;
            const pay = body.querySelector('input[name="pay_date[]"]').value;
            it.querySelector('.ph-range').textContent = (start||'????-??-??') + ' → ' + (end||'????-??-??');
            it.querySelector('.ph-pay').textContent = pay || '????-??-??';
          });
          validatePeriods();
        });
      }
      function validatePeriods(){
        let hasError=false;
        items.forEach(it=>{
          it.classList.remove('invalid');
          const body = it.querySelector('.period-body'); if(!body) return;
          const s = body.querySelector('input[name="period_start[]"]').value;
          const e = body.querySelector('input[name="period_end[]"]').value;
          const p = body.querySelector('input[name="pay_date[]"]').value;
          if(!s||!e||!p){ it.classList.add('invalid'); hasError=true; return; }
          if(e < s){ it.classList.add('invalid'); hasError=true; }
          if(p < e){ it.classList.add('invalid'); hasError=true; }
        });
        if(errBox){
          if(hasError){ errBox.style.display='block'; errBox.textContent='Please correct highlighted periods (missing dates or invalid order).'; }
          else { errBox.style.display='none'; }
        }
        return !hasError;
      }
      validatePeriods();
      const form = document.getElementById('wizardForm');
      if(form){ form.addEventListener('submit', e=>{ if(!validatePeriods()){ e.preventDefault(); if(currentStep!==2) updateStep(2-currentStep); } }); }
    })();
    // Full preview modal script
    (function(){
      const modal = document.getElementById('fullPreviewModal');
      if(!modal) return;
      // Add trigger button near submit when final step active
      const submitBtn = document.getElementById('submitBtn');
      const previewBtn = document.createElement('button');
      previewBtn.type='button';
      previewBtn.textContent='Full Preview';
      previewBtn.className='btn-secondary';
      previewBtn.style.order='-1';
      submitBtn.parentElement.insertBefore(previewBtn, submitBtn);
      const closeBtn = document.getElementById('closeFullPreview');
      const pc = {
        employer: document.getElementById('pcEmployer'), employee: document.getElementById('pcEmployee'), schedule: document.getElementById('pcSchedule'), payType: document.getElementById('pcPayType'), start: document.getElementById('pcStart'), end: document.getElementById('pcEnd'), pay: document.getElementById('pcPay'), check: document.getElementById('pcCheck'), gross: document.getElementById('pcGross'), ded: document.getElementById('pcDed'), tax: document.getElementById('pcTax'), net: document.getElementById('pcNet'),
        earnT: document.querySelector('#pcEarnings tbody'), dedT: document.querySelector('#pcDeductions tbody'), taxT: document.querySelector('#pcTaxes tbody')
      };
      let currentStub = 0; let totalStubs=1;
      const pager = document.getElementById('stubPager');
      const lbl = document.getElementById('stubIndexLabel');
      const btnPrev = document.getElementById('stubPrev');
      const btnNext = document.getElementById('stubNext');
      function collectPeriods(){
        const starts = Array.from(document.querySelectorAll('input[name="period_start[]"]')).map(i=>i.value);
        const ends = Array.from(document.querySelectorAll('input[name="period_end[]"]')).map(i=>i.value);
        const pays = Array.from(document.querySelectorAll('input[name="pay_date[]"]')).map(i=>i.value);
        return starts.map((s,i)=>({start:s,end:ends[i]||'',pay:pays[i]||''}));
      }
      function fmt(n){return '$'+Number(n||0).toFixed(2);}    
      function sum(selector){let t=0; document.querySelectorAll(selector).forEach(i=>{const v=parseFloat(i.value); if(!isNaN(v)) t+=v;}); return t;}
      function populateTable(srcSelector, tgt){
        tgt.innerHTML='';
        const rows = document.querySelectorAll(srcSelector);
        if(rows.length===0){ tgt.innerHTML='<tr><td colspan="2" style="opacity:.5">None</td></tr>'; return; }
        rows.forEach(r=>{
          const inputs = r.querySelectorAll('input');
          if(inputs.length<2) return; const label=inputs[0].value||'—'; const amt=parseFloat(inputs[1].value||'0');
          if(label==='' && amt===0) return;
          const tr=document.createElement('tr');
          tr.innerHTML=`<td>${label||'—'}</td><td style="text-align:right">${fmt(amt)}</td>`;
          tgt.appendChild(tr);
        });
      }
      function renderStub(idx){
        const periods = collectPeriods();
        currentStub = Math.min(Math.max(0,idx), periods.length-1);
        totalStubs = periods.length;
        if(totalStubs>1){ pager.style.display='flex'; lbl.textContent=`${currentStub+1}/${totalStubs}`; btnPrev.disabled=currentStub===0; btnNext.disabled=currentStub===totalStubs-1; } else { pager.style.display='none'; }
        const p = periods[currentStub]||{start:'',end:'',pay:''};
        pc.employer.textContent = document.querySelector('input[name="employer_name"]').value||'—';
        pc.employee.textContent = document.querySelector('input[name="employee_name"]').value||'—';
        pc.schedule.textContent = document.querySelector('select[name="pay_schedule"]').value||'—';
        pc.payType.textContent = document.getElementById('payType').value||'—';
        pc.start.textContent = p.start||'—'; pc.end.textContent = p.end||'—'; pc.pay.textContent = p.pay||'—';
        pc.check.textContent = document.querySelector('input[name="check_number"]').value||'—';
        populateTable('#earnings-table tbody tr', pc.earnT);
        populateTable('#deductions-table tbody tr', pc.dedT);
        populateTable('#taxes-table tbody tr', pc.taxT);
        const gross = sum('#earnings-table input[name="earnings[amount][]"]');
        const ded = sum('#deductions-table input[name="deductions[amount][]"]');
        const tax = sum('#taxes-table input[name="taxes[amount][]"]');
        const net = gross - ded - tax;
        pc.gross.textContent=fmt(gross); pc.ded.textContent=fmt(ded); pc.tax.textContent=fmt(tax); pc.net.textContent=fmt(net);
      }
      previewBtn.addEventListener('click',()=>{ modal.classList.add('active'); modal.setAttribute('aria-hidden','false'); renderStub(currentStub); });
      closeBtn.addEventListener('click',()=>{ modal.classList.remove('active'); modal.setAttribute('aria-hidden','true'); });
      btnPrev && btnPrev.addEventListener('click',()=>{ renderStub(currentStub-1); });
      btnNext && btnNext.addEventListener('click',()=>{ renderStub(currentStub+1); });
      document.addEventListener('keydown',e=>{ if(modal.classList.contains('active')){ if(e.key==='Escape'){ closeBtn.click(); } if(e.key==='ArrowRight'){ btnNext && !btnNext.disabled && btnNext.click(); } if(e.key==='ArrowLeft'){ btnPrev && !btnPrev.disabled && btnPrev.click(); } }});
    })();
    // Kick off
    updateStep(0);
  </script>
</body>
</html>