<?php /** @var array $payload */ $tokens = $tokens ?? ['primary'=>'#111827','accent'=>'#374151','muted'=>'#9CA3AF']; ?>
<?php include __DIR__.'/partials/pdf-style-horizontal-black.php'; ?>
<div>
  <!-- Header Band -->
  <div class="header-band">
    <div style="display:flex;justify-content:space-between;gap:10pt;">
      <div>
        <?php if (!empty($payload['branding']['logo'])): ?>
          <div style="margin:0 0 4pt;max-width:220pt;">
            <img src="<?= htmlspecialchars($payload['branding']['logo']) ?>" alt="Logo" style="max-width:100%;max-height:32pt;object-fit:contain;">
          </div>
        <?php else: ?>
          <h1 style="margin:0 0 4pt;">Pay Stub</h1>
        <?php endif; ?>
        <div style="opacity:.9;">Folio: <?= htmlspecialchars($payload['folio'] ?? '') ?></div>
      </div>
      <div>
        <?php $i=$payload['stub_index']??0; $p=$payload['periods'][$i]??null; ?>
        <div><strong>Pay Period:</strong> <?= htmlspecialchars($p['period_start'] ?? $p['start_date'] ?? '') ?> – <?= htmlspecialchars($p['period_end'] ?? $p['end_date'] ?? '') ?></div>
        <div><strong>Pay Date:</strong> <?= htmlspecialchars($p['pay_date'] ?? '') ?></div>
      </div>
    </div>
  </div>
  <!-- Employer / Employee -->
  <div class="block">
    <table>
      <tbody>
        <tr>
          <td style="width:50%">
            <strong>Employer</strong><br>
            <?= htmlspecialchars($payload['employer']['name'] ?? '') ?><br>
            <?php foreach (($payload['employer']['address'] ?? []) as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; ?>
            <?php if (!empty($payload['employer']['ein'])): ?>EIN: <?= htmlspecialchars($payload['employer']['ein']) ?><?php endif; ?>
          </td>
          <td style="width:50%">
            <strong>Employee</strong><br>
            <?= htmlspecialchars($payload['employee']['name'] ?? '') ?><br>
            <?php foreach (($payload['employee']['address'] ?? []) as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; ?>
            <?php if (!empty($payload['employee']['ssn_last4'])): ?>SSN: ***-**-<?= htmlspecialchars($payload['employee']['ssn_last4']) ?><?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php $i=$payload['stub_index']??0; $earnRows=$payload['earnings'][$i]??[]; $dedRows=$payload['deductions'][$i]??[]; $taxRows=$payload['taxes'][$i]??[]; $sum = $payload['summary'][$i] ?? ['gross'=>0.0,'fit_taxable_wages'=>0.0,'taxes_total'=>0.0,'deductions_total'=>0.0,'net'=>0.0]; $dist=$payload['distribution'][$i]??[]; ?>
  <!-- Earnings -->
  <div class="block">
    <h2>Earnings</h2>
    <table>
      <thead>
        <tr><th>Type</th><th>Hours</th><th>Rate</th><th>Current</th><th>YTD</th></tr>
      </thead>
      <tbody>
      <?php foreach ($earnRows as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['label']) ?></td>
          <td style="text-align:right;">
            <?= isset($row['hours'])?number_format((float)$row['hours'],2):'' ?>
          </td>
          <td style="text-align:right;">
            <?= isset($row['rate'])?number_format((float)$row['rate'],2):'' ?>
          </td>
          <td style="text-align:right;"><?= number_format((float)$row['current'],2) ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['ytd'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Taxes -->
  <div class="block">
    <h2>Taxes</h2>
    <table>
      <thead>
        <tr><th colspan="3">Tax</th><th>Current</th><th>YTD</th></tr>
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
  <!-- Deductions -->
  <div class="block">
    <h2>Deductions</h2>
    <table>
      <thead>
        <tr><th colspan="3">Deduction</th><th>Current</th><th>YTD</th></tr>
      </thead>
      <tbody>
      <?php foreach ($dedRows as $row): ?>
        <tr>
          <td colspan="3"><?= htmlspecialchars($row['label']) ?><?= !empty($row['pretax'])?' (Pre-Tax)':'' ?></td>
          <td style="text-align:right;"><?= number_format((float)$row['current'],2) ?></td>
          <td style="text-align:right;">&dollar;<?= number_format((float)$row['ytd'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- Summary -->
  <?php if ($sum): ?>
  <div class="block">
    <h2>Pay Summary</h2>
    <table>
      <tbody>
        <tr><td>Gross</td><td style="text-align:right;"><?= number_format((float)$sum['gross'],2) ?></td></tr>
  <tr><td>FIT Taxable Wages</td><td style="text-align:right;"><?= number_format((float)($sum['fit_taxable_wages'] ?? 0),2) ?></td></tr>
        <tr><td>Taxes</td><td style="text-align:right;"><?= number_format((float)$sum['taxes_total'],2) ?></td></tr>
        <tr><td>Deductions</td><td style="text-align:right;"><?= number_format((float)$sum['deductions_total'],2) ?></td></tr>
        <tr><td><strong>Net Pay</strong></td><td style="text-align:right;"><strong><?= number_format((float)$sum['net'],2) ?></strong></td></tr>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <!-- Distribution -->
  <?php if (!empty($dist)): ?>
  <div class="block">
    <h2>Net Pay Distribution</h2>
    <table>
      <thead><tr><th>Account</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($dist as $d): $amt=(float)($d['amount'] ?? ($d['current'] ?? 0)); ?>
        <tr>
          <td><?= htmlspecialchars(($d['account_type'] ?? '').' '.($d['masked'] ?? '')) ?></td>
          <td style="text-align:right;">&dollar;<?= number_format($amt,2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
