<div style="width:100%;min-height:100%;background:#000;color:#fff;padding:20px;font-family:Arial, sans-serif;">
        <?php if (!empty($payload['branding']['logo'])): ?>
                    <div style="margin:0 0 6pt;max-width:200pt;">
                        <img src="<?= htmlspecialchars($payload['branding']['logo']) ?>" alt="Logo" style="max-width:100%;max-height:32pt;object-fit:contain;">
            </div>
        <?php else: ?>
            <h2 style="margin-top:0;">Pay Stub (Black)</h2>
        <?php endif; ?>
    <?php if (!empty($payload['period'])): ?>
        <p style="margin:0 0 8px;">Period: <?= htmlspecialchars($payload['period']['start_date'] ?? '') ?> to <?= htmlspecialchars($payload['period']['end_date'] ?? '') ?> | Pay Date: <?= htmlspecialchars($payload['period']['pay_date'] ?? '') ?></p>
    <?php endif; ?>
    <?php $employer = json_decode($payload['order']['employer_json'] ?? '{}', true); $employee = json_decode($payload['order']['employee_json'] ?? '{}', true); ?>
    <p style="margin:0 0 4px;">Employer: <?= htmlspecialchars($employer['name'] ?? '') ?></p>
    <p style="margin:0 0 4px;">Employee: <?= htmlspecialchars($employee['name'] ?? '') ?></p>
    <p style="font-size:12px;opacity:.65;margin:4px 0 12px;">Stub #<?= (int)($payload['stub_index']+1) ?> / <?= (int)$payload['order']['count_stubs'] ?></p>
    <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <tr>
            <td style="vertical-align:top;width:50%;padding-right:12px;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr><th style="text-align:left;border-bottom:1px solid #444;padding:4px 0;">Earnings</th><th style="text-align:right;border-bottom:1px solid #444;padding:4px 0;">Current</th><th style="text-align:right;border-bottom:1px solid #444;padding:4px 0;">YTD</th></tr></thead>
                    <tbody>
                    <?php $gross=0;$grossY=0; foreach (($payload['items']['earnings'] ?? []) as $e): if ($e['stub_index'] != $payload['stub_index']) continue; $gross+=$e['current_amount']; $grossY=$e['ytd_amount']; ?>
                        <tr><td style="padding:3px 0;"><?= htmlspecialchars($e['label']) ?></td><td style="text-align:right;"><?= number_format($e['current_amount'],2) ?></td><td style="text-align:right;"><?= number_format($e['ytd_amount'],2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td style="padding:4px 0;font-weight:bold;border-top:1px solid #444;">Gross</td><td style="text-align:right;font-weight:bold;border-top:1px solid #444;"><?= number_format($gross,2) ?></td><td style="text-align:right;font-weight:bold;border-top:1px solid #444;"><?= number_format($grossY,2) ?></td></tr>
                    </tbody>
                </table>
            </td>
            <td style="vertical-align:top;width:50%;padding-left:12px;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr><th style="text-align:left;border-bottom:1px solid #444;padding:4px 0;">Taxes & Deductions</th><th style="text-align:right;border-bottom:1px solid #444;padding:4px 0;">Current</th><th style="text-align:right;border-bottom:1px solid #444;padding:4px 0;">YTD</th></tr></thead>
                    <tbody>
                    <?php $withhold=0;$withholdY=0; foreach (array_merge(($payload['items']['taxes'] ?? []), ($payload['items']['deductions'] ?? [])) as $x): if (($x['stub_index'] ?? null) != $payload['stub_index']) continue; $cur = $x['current_amount'] ?? ($x['current'] ?? 0); $ytd = $x['ytd_amount'] ?? ($x['ytd'] ?? 0); $withhold += $cur; $withholdY = $ytd; ?>
                        <tr><td style="padding:3px 0;"><?= htmlspecialchars($x['label'] ?? '') ?></td><td style="text-align:right;">&dollar;<?= number_format($cur,2) ?></td><td style="text-align:right;">&dollar;<?= number_format($ytd,2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td style="padding:4px 0;font-weight:bold;border-top:1px solid #444;">Total Withheld</td><td style="text-align:right;font-weight:bold;border-top:1px solid #444;"><?= number_format($withhold,2) ?></td><td style="text-align:right;font-weight:bold;border-top:1px solid #444;"><?= number_format($withholdY,2) ?></td></tr>
                    <?php $net = $gross - $withhold; ?>
                    <tr><td style="padding:6px 0;font-weight:bold;border-top:1px solid #444;">Net Pay</td><td style="text-align:right;font-weight:bold;border-top:1px solid #444;"><?= number_format($net,2) ?></td><td style="text-align:right;font-weight:bold;border-top:1px solid #444;">&nbsp;</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</div>
