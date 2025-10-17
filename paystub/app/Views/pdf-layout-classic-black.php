<?php /** @var array $payload */ $tokens = $tokens ?? ['primary'=>'#111827','accent'=>'#2563eb','muted'=>'#6b7280']; ?>
<?php include __DIR__.'/partials/pdf-style-classic-black.php'; ?>
<div style="color:#111827;">
  <!-- Header -->
  <div class="block">
    <div class="header-grid">
      <div class="col">
        <?php if (!empty($payload['branding']['logo'])): ?>
          <div style="margin:0 0 6pt;max-width:200pt;">
            <img src="<?= htmlspecialchars($payload['branding']['logo']) ?>" alt="Logo" style="max-height:32pt;max-width:100%;object-fit:contain;">
          </div>
        <?php else: ?>
          <h1>Pay Stub</h1>
        <?php endif; ?>
        <div class="muted">Folio: <?= htmlspecialchars($payload['folio'] ?? '') ?></div>
      </div>
      <div class="col">
        <strong>Employer</strong><br>
        <?= htmlspecialchars($payload['employer']['name'] ?? '') ?><br>
        <?php foreach (($payload['employer']['address'] ?? []) as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; ?>
        <?php if (!empty($payload['employer']['ein'])): ?>EIN: <?= htmlspecialchars($payload['employer']['ein']) ?><?php endif; ?>
      </div>
      <div class="col">
        <strong>Employee</strong><br>
        <?= htmlspecialchars($payload['employee']['name'] ?? '') ?><br>
        <?php foreach (($payload['employee']['address'] ?? []) as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; ?>
        <?php if (!empty($payload['employee']['ssn_last4'])): ?>SSN: ***-**-<?= htmlspecialchars($payload['employee']['ssn_last4']) ?><?php endif; ?>
      </div>
      <div class="col">
        <?php $i=$payload['stub_index']??0; $p=$payload['periods'][$i]??null; ?>
        <strong>Pay Period</strong><br>
        <?= htmlspecialchars($p['period_start'] ?? $p['start_date'] ?? '') ?> – <?= htmlspecialchars($p['period_end'] ?? $p['end_date'] ?? '') ?><br>
        <strong>Pay Date</strong><br>
        <?= htmlspecialchars($p['pay_date'] ?? '') ?>
      </div>
    </div>
  </div>
  <!-- Earnings -->
  <?php $i=$payload['stub_index']??0; $earnRows=$payload['earnings'][$i]??[]; $dedRows=$payload['deductions'][$i]??[]; $taxRows=$payload['taxes'][$i]??[]; $sum = $payload['summary'][$i] ?? ['gross'=>0.0,'fit_taxable_wages'=>0.0,'taxes_total'=>0.0,'deductions_total'=>0.0,'net'=>0.0]; $dist=$payload['distribution'][$i]??[]; ?>
  <div class="block">
    <table>
      <thead>
        <tr><th>Type</th><th>Hours</th><th>Rate</th><th>Current</th><th>YTD</th></tr>
      </thead>
      <tbody>
      <?php foreach ($earnRows as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['label']) ?></td>
          <td style="text-align:right;"><?= isset($row['hours'])?number_format((float)$row['hours'],2):'' ?></td>
          <td style="text-align:right;"><?= isset($row['rate'])?number_format((float)$row['rate'],2):'' ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['current'],2) ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['ytd'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Deductions / Taxes -->
  <div class="block">
    <table>
      <thead>
        <tr><th colspan="3">Deductions</th><th style="width:20%">Current</th><th style="width:20%">YTD</th></tr>
      </thead>
      <tbody>
      <?php foreach ($dedRows as $row): ?>
        <tr>
          <td colspan="3"><?= htmlspecialchars($row['label']) ?><?= !empty($row['pretax'])?' (Pre-Tax)':'' ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['current'],2) ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['ytd'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <table style="margin-top:8pt;">
      <thead>
        <tr><th colspan="3">Taxes</th><th style="width:20%">Current</th><th style="width:20%">YTD</th></tr>
      </thead>
      <tbody>
      <?php foreach ($taxRows as $row): ?>
        <tr>
          <td colspan="3"><?= htmlspecialchars($row['label']) ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['current'],2) ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['ytd'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Summary -->
  <?php if ($sum): ?>
  <div class="block">
    <table class="summary">
      <tbody>
        <tr><td class="label">Gross</td><td style="text-align:right;"><?= number_format((float)$sum['gross'],2) ?></td></tr>
  <tr><td class="label">FIT Taxable Wages</td><td style="text-align:right;"><?= number_format((float)($sum['fit_taxable_wages'] ?? 0),2) ?></td></tr>
        <tr><td class="label">Taxes</td><td style="text-align:right;"><?= number_format((float)$sum['taxes_total'],2) ?></td></tr>
        <tr><td class="label">Deductions</td><td style="text-align:right;"><?= number_format((float)$sum['deductions_total'],2) ?></td></tr>
        <tr><td class="label"><strong>Net Pay</strong></td><td style="text-align:right;"><strong><?= number_format((float)$sum['net'],2) ?></strong></td></tr>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <!-- Distribution -->
  <?php if (!empty($dist)): ?>
  <div class="block">
    <table>
      <thead><tr><th>Account</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($dist as $d): $amt=(float)($d['amount'] ?? ($d['current'] ?? 0)); ?>
        <tr>
          <td><?= htmlspecialchars(($d['account_type'] ?? '').' '.($d['masked'] ?? '')) ?></td>
          <td style="text-align:right;"><?= number_format($amt,2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
