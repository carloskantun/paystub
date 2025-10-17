<?php /** @var array $payload */
// Ensure tokens default
$tokens = $tokens ?? ['primary'=>'#0f172a','accent'=>'#1d4ed8','muted'=>'#64748b'];
// Normalize commonly used structures for legacy sections
$order    = $payload['order']    ?? null;
$items    = $payload['items']    ?? [];
$earnRows = $items['earnings']   ?? [];
$taxRows  = isset($payload['taxes']) ? $payload['taxes'] : ($items['taxes'] ?? []); // accept either shape
$dedRows  = isset($payload['deductions']) ? $payload['deductions'] : ($items['deductions'] ?? []);
$sum      = $payload['summary']  ?? ($payload['summary_row'] ?? null);
if (is_array($sum) && isset($sum[0]) && is_array($sum[0]) && isset($sum[0]['gross'])) { $sum = $sum[0]; }
$dist     = $payload['distribution'] ?? [];
// Map new calc line structure (earnings per stub) to old expectation if needed
if (!empty($payload['earnings']) && empty($earnRows)) {
  $idx = (int)($payload['stub_index'] ?? 0);
  $earnRows = $payload['earnings'][$idx] ?? [];
}
if (!empty($payload['taxes']) && empty($taxRows)) {
  $idx = (int)($payload['stub_index'] ?? 0);
  $taxRows = $payload['taxes'][$idx] ?? [];
}
if (!empty($payload['deductions']) && empty($dedRows)) {
  $idx = (int)($payload['stub_index'] ?? 0);
  $dedRows = $payload['deductions'][$idx] ?? [];
}
// Normalize each line to expected keys current / ytd
$norm = function(array $lines): array { return array_map(function($l){
  return [
    'label'=>$l['label'] ?? '',
    'current'=>$l['current'] ?? ($l['current_amount'] ?? 0),
    'ytd'=>$l['ytd'] ?? ($l['ytd_amount'] ?? ($l['current'] ?? ($l['current_amount'] ?? 0))),
    'pretax'=>$l['pretax'] ?? 0,
  ];
}, $lines); };
$taxRows = $norm(is_array($taxRows)?$taxRows:[]);
$dedRows = $norm(is_array($dedRows)?$dedRows:[]);
if ($sum && is_array($sum)) {
  $sum += [
    'gross'=>$sum['gross'] ?? 0,
    'fit_taxable_wages'=>$sum['fit_taxable_wages'] ?? 0,
    'taxes_total'=>$sum['taxes_total'] ?? 0,
    'deductions_total'=>$sum['deductions_total'] ?? 0,
    'net'=>$sum['net'] ?? (($sum['gross'] ?? 0) - ($sum['taxes_total'] ?? 0) - ($sum['deductions_total'] ?? 0)),
  ];
}
?>
<?php include __DIR__.'/partials/pdf-style-horizontal-blue.php'; ?>
<div>
  <!-- Header Band -->
  <div class="header-band">
    <div style="display:flex;justify-content:space-between;gap:10pt;align-items:flex-start;">
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
    <?php $employer = $payload['employer'] ?? (isset($order['employer_json'])? json_decode($order['employer_json'], true):[]); $employee = $payload['employee'] ?? (isset($order['employee_json'])? json_decode($order['employee_json'], true):[]); ?>
    <table>
      <tbody>
        <tr>
          <td style="width:50%;vertical-align:top;">
            <?php if (!empty($payload['branding']['logo'])): ?>
              <div style="margin:0 0 6pt;max-width:140pt;"><img src="<?= htmlspecialchars($payload['branding']['logo']) ?>" alt="Logo" style="max-width:100%;max-height:24pt;object-fit:contain;"></div>
            <?php endif; ?>
            <strong>Employer</strong><br>
            <?= htmlspecialchars($employer['name'] ?? '') ?><br>
            <?php if (!empty($employer['address']) && is_array($employer['address'])): foreach ($employer['address'] as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; endif; ?>
            <?php if (!empty($employer['ein'])): ?>EIN: <?= htmlspecialchars($employer['ein']) ?><br><?php endif; ?>
            <?php if (!empty($employer['phone'])): ?>Phone: <?= htmlspecialchars($employer['phone']) ?><br><?php endif; ?>
          </td>
          <td style="width:50%;vertical-align:top;">
            <strong>Employee</strong><br>
            <?= htmlspecialchars($employee['name'] ?? '') ?><br>
            <?php if (!empty($employee['address']) && is_array($employee['address'])): foreach ($employee['address'] as $line): ?><?= htmlspecialchars($line) ?><br><?php endforeach; endif; ?>
            <?php if (!empty($employee['ssn_last4'])): ?>SSN: ***-**-<?= htmlspecialchars($employee['ssn_last4']) ?><br><?php endif; ?>
            <?php if (!empty($employee['employee_number'])): ?>#<?= htmlspecialchars($employee['employee_number']) ?><br><?php endif; ?>
            <?php if (!empty($employee['job_title'])): ?>Title: <?= htmlspecialchars($employee['job_title']) ?><br><?php endif; ?>
          </td>
        </tr>
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
          <td style="text-align:right;"><?= number_format((float)$row['ytd'],2) ?></td>
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
