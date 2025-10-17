<?php /** @var array $payload */ $tokens = $tokens ?? ['primary'=>'#0f172a','accent'=>'#22c55e','muted'=>'#64748b']; ?>
<?php include __DIR__.'/partials/pdf-style-statement-wide.php'; ?>
<div>
  <div class="band" style="margin-bottom:8pt;display:flex;justify-content:space-between;align-items:center;">
    <div>
      <?php if (!empty($payload['branding']['logo'])): ?>
        <div style="margin:0 0 4pt;max-width:260pt;">
          <img src="<?= htmlspecialchars($payload['branding']['logo']) ?>" alt="Logo" style="max-width:100%;max-height:32pt;object-fit:contain;">
        </div>
      <?php else: ?>
        <h1 style="margin:0;">Pay Stub</h1>
      <?php endif; ?>
      <div class="muted" style="opacity:.9;">Folio: <?= htmlspecialchars($payload['folio'] ?? '') ?></div>
    </div>
    <?php $i=$payload['stub_index']??0; $p=$payload['periods'][$i]??null; ?>
    <div><strong>Period</strong> <?= htmlspecialchars($p['period_start'] ?? $p['start_date'] ?? '') ?> – <?= htmlspecialchars($p['period_end'] ?? $p['end_date'] ?? '') ?> · <strong>Pay</strong> <?= htmlspecialchars($p['pay_date'] ?? '') ?></div>
  </div>
  <div class="block">
    <table><tbody><tr>
      <td style="width:50%"><strong>Employer</strong><br><?= htmlspecialchars($payload['employer']['name'] ?? '') ?><br><?php foreach (($payload['employer']['address'] ?? []) as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; ?></td>
      <td style="width:50%"><strong>Employee</strong><br><?= htmlspecialchars($payload['employee']['name'] ?? '') ?><br><?php foreach (($payload['employee']['address'] ?? []) as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; ?></td>
    </tr></tbody></table>
  </div>
  <?php $i=$payload['stub_index']??0; $earnRows=$payload['earnings'][$i]??[]; $dedRows=$payload['deductions'][$i]??[]; $taxRows=$payload['taxes'][$i]??[]; $sum = $payload['summary'][$i] ?? ['gross'=>0.0,'fit_taxable_wages'=>0.0,'taxes_total'=>0.0,'deductions_total'=>0.0,'net'=>0.0]; $dist=$payload['distribution'][$i]??[]; ?>
  <div class="block">
    <h2>Earnings</h2>
    <table>
      <thead><tr><th>Type</th><th>Hours</th><th>Rate</th><th>Current</th><th>YTD</th></tr></thead>
      <tbody>
        <?php foreach($earnRows as $row): ?>
        <tr><td><?= htmlspecialchars($row['label']) ?></td><td style="text-align:right;"><?= isset($row['hours'])?number_format((float)$row['hours'],2):'' ?></td><td style="text-align:right;"><?= isset($row['rate'])?number_format((float)$row['rate'],2):'' ?></td><td style="text-align:right;">&dollar;<?= number_format((float)$row['current'],2) ?></td><td style="text-align:right;">&dollar;<?= number_format((float)$row['ytd'],2) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="block" style="display:grid;grid-template-columns:1fr 1fr;gap:8pt;">
    <div>
      <h2>Deductions</h2>
      <table><thead><tr><th colspan="3">Deduction</th><th>Current</th><th>YTD</th></tr></thead><tbody>
        <?php foreach($dedRows as $row): ?>
        <tr><td colspan="3"><?= htmlspecialchars($row['label']) ?></td><td style="text-align:right;">&dollar;<?= number_format((float)$row['current'],2) ?></td><td style="text-align:right;">&dollar;<?= number_format((float)$row['ytd'],2) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
    <div>
      <h2>Taxes</h2>
      <table><thead><tr><th colspan="3">Tax</th><th>Current</th><th>YTD</th></tr></thead><tbody>
        <?php foreach($taxRows as $row): ?>
        <tr><td colspan="3"><?= htmlspecialchars($row['label']) ?></td><td style="text-align:right;">&dollar;<?= number_format((float)$row['current'],2) ?></td><td style="text-align:right;">&dollar;<?= number_format((float)$row['ytd'],2) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <?php if ($sum): ?><div class="band" style="margin-top:8pt;">
    <table style="border:0;">
      <tbody>
        <tr><td style="border:0;">Gross</td><td style="text-align:right;border:0;">&dollar;<?= number_format((float)$sum['gross'],2) ?></td></tr>
  <tr><td style="border:0;">Taxes</td><td style="text-align:right;border:0;">&dollar;<?= number_format((float)($sum['taxes_total'] ?? 0),2) ?></td></tr>
        <tr><td style="border:0;">Deductions</td><td style="text-align:right;border:0;">&dollar;<?= number_format((float)$sum['deductions_total'],2) ?></td></tr>
        <tr><td style="border:0;"><strong>Net Pay</strong></td><td style="text-align:right;border:0;"><strong>&dollar;<?= number_format((float)$sum['net'],2) ?></strong></td></tr>
      </tbody>
    </table>
  </div><?php endif; ?>
  <?php if (!empty($dist)): ?><div class="block"><h2>Net Pay Distribution</h2>
    <table><thead><tr><th>Account</th><th>Amount</th></tr></thead><tbody>
  <?php foreach ($dist as $d): $amt=(float)($d['amount'] ?? ($d['current'] ?? 0)); ?><tr><td><?= htmlspecialchars(($d['account_type'] ?? '').' '.($d['masked'] ?? '')) ?></td><td style="text-align:right;">&dollar;<?= number_format($amt,2) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div><?php endif; ?>
</div>
