<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preview | createpaystubdocs.com</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body { background:#0f172a; color:#fff; }
        .preview-shell {background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);padding:1.5rem 1.75rem 2rem;border-radius:14px; position:relative;}
        .watermark { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:5rem; font-weight:700; color:rgba(255,255,255,.04); pointer-events:none; user-select:none; transform:rotate(-25deg); letter-spacing:.25em; }
        table { width:100%; border-collapse:collapse; margin-top:.75rem; font-size:.7rem; }
        th, td { padding:.45rem .55rem; border:1px solid rgba(255,255,255,.15); text-align:left; }
        th { background:rgba(255,255,255,.07); letter-spacing:.05em; font-size:.6rem; }
        h1 { font-size:1.85rem; margin:.2rem 0 1rem; background:linear-gradient(135deg,#38bdf8,#6366f1); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-top:1.25rem; }
        .summary-box { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); padding:.9rem 1rem; border-radius:10px; }
        .summary-box h3 { margin:.2rem 0 .6rem; font-size:.7rem; letter-spacing:.1em; text-transform:uppercase; font-weight:600; }
        .actions { margin-top:1.4rem; }
        .btn-primary { background:linear-gradient(135deg,#2563eb,#6366f1); border:none; color:#fff; padding:.8rem 1.25rem; font-size:.75rem; font-weight:600; border-radius:8px; cursor:pointer; }
        .btn-primary.edit { background:#334155; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Preview (Step 2)</h1>
        <p style="margin-top:-.5rem;color:#94a3b8;font-size:.85rem;">Watermarked preview – finalize payment to get downloadable PDF(s) + email delivery.</p>
        <?php if (isset($priceEstimate) && isset($priceBreakdown)): ?>
            <div style="margin:.6rem 0 1.1rem;display:flex;flex-wrap:wrap;gap:.6rem;font-size:.65rem;">
                <div style="background:rgba(255,255,255,.07);padding:.55rem .7rem;border-radius:8px;">Base: $<?= number_format($priceBreakdown['base_per_stub'],2) ?>/stub</div>
                <?php if ($priceBreakdown['template_surcharge_pct']>0): ?>
                    <div style="background:rgba(255,255,255,.07);padding:.55rem .7rem;border-radius:8px;">Template +<?= (int)($priceBreakdown['template_surcharge_pct']*100) ?>%</div>
                <?php endif; ?>
                <?php if ($priceBreakdown['discount_pct']>0): ?>
                    <div style="background:#14532d;padding:.55rem .7rem;border-radius:8px;">Discount -<?= (int)($priceBreakdown['discount_pct']*100) ?>%</div>
                <?php endif; ?>
                <div style="background:linear-gradient(135deg,#2563eb,#6366f1);padding:.55rem .7rem;border-radius:8px;">Total: <strong>$<?= number_format($priceBreakdown['total'],2) ?></strong></div>
            </div>
        <?php endif; ?>
        <div class="preview-shell">
            <?php if (!empty($watermark)): ?><div class="watermark">PREVIEW</div><?php endif; ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem 1.25rem;font-size:.7rem;">
                <div><strong style="display:block;color:#38bdf8;font-size:.55rem;letter-spacing:.1em;">EMAIL</strong><?= htmlspecialchars($payload['email'] ?? '') ?></div>
                <div><strong style="display:block;color:#38bdf8;font-size:.55rem;letter-spacing:.1em;">EMPLOYER</strong><?= htmlspecialchars($payload['employer_name']) ?></div>
                <div><strong style="display:block;color:#38bdf8;font-size:.55rem;letter-spacing:.1em;">EMPLOYEE</strong><?= htmlspecialchars($payload['employee_name']) ?></div>
                <div><strong style="display:block;color:#38bdf8;font-size:.55rem;letter-spacing:.1em;">SCHEDULE</strong><?= htmlspecialchars($payload['pay_schedule']) ?></div>
                <div><strong style="display:block;color:#38bdf8;font-size:.55rem;letter-spacing:.1em;">STUBS</strong><?= htmlspecialchars($payload['stubs_count']) ?></div>
                <div><strong style="display:block;color:#38bdf8;font-size:.55rem;letter-spacing:.1em;">TEMPLATE</strong><?= htmlspecialchars($payload['template_key']) ?></div>
            </div>
                        <?php if (!empty($periods)): ?>
                        <h3 style="margin:1rem 0 .3rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:#fff;">Pay Periods</h3>
                        <table style="font-size:.6rem;">
                            <thead><tr><th>#</th><th>Start</th><th>End</th><th>Pay Date</th></tr></thead>
                            <tbody>
                                <?php foreach(array_reverse($periods) as $p): ?>
                                    <tr><td><?= (int)$p['index']+1 ?></td><td><?= htmlspecialchars($p['start_date']) ?></td><td><?= htmlspecialchars($p['end_date']) ?></td><td><?= htmlspecialchars($p['pay_date']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
            <div style="margin:.9rem 0 .5rem;height:1px;background:linear-gradient(90deg,rgba(255,255,255,.15),rgba(255,255,255,.02));"></div>
            <?php
            $grossPer = 0; foreach ($payload['earnings'] as $e) { $grossPer += (float)($e['current'] ?? ($e['amount'] ?? 0)); }
            $dedPer = 0; foreach ($payload['deductions'] as $d) { $dedPer += (float)($d['current'] ?? ($d['amount'] ?? 0)); }
            $taxPer = 0; foreach ($payload['taxes'] as $t) { $taxPer += (float)($t['current'] ?? ($t['amount'] ?? 0)); }
            $netPer = $grossPer - $dedPer - $taxPer;
            ?>
            <h3 style="margin:1.1rem 0 .35rem;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#fff;">Earnings</h3>
            <table>
                <thead><tr><th>Pay Type</th><th style="width:12%;text-align:right;">Hours</th><th style="width:14%;text-align:right;">Pay Rate</th><th style="width:14%;text-align:right;">Current</th><th style="width:14%;text-align:right;">YTD</th></tr></thead>
                <tbody>
                <?php foreach ($payload['earnings'] as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['label'] ?? '') ?></td>
                        <td style="text-align:right;"><?= isset($e['hours'])?number_format((float)$e['hours'],2):'' ?></td>
                        <td style="text-align:right;"><?= isset($e['rate'])?number_format((float)$e['rate'],4):'' ?></td>
                        <td style="text-align:right;">$<?= number_format((float)($e['current'] ?? ($e['amount'] ?? 0)),2) ?></td>
                        <td style="text-align:right;">$<?= number_format((float)($e['ytd'] ?? ($e['amount'] ?? 0)),2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <h3 style="margin:1.1rem 0 .35rem;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#fff;">Deductions</h3>
            <table>
                <thead><tr><th>Label</th><th style="width:14%;text-align:right;">Current</th><th style="width:14%;text-align:right;">YTD</th></tr></thead>
                <tbody>
                <?php foreach ($payload['deductions'] as $d): ?>
                    <tr><td><?= htmlspecialchars($d['label']) ?></td><td style="text-align:right;">$<?= number_format((float)($d['current'] ?? ($d['amount'] ?? 0)),2) ?></td><td style="text-align:right;">$<?= number_format((float)($d['ytd'] ?? ($d['amount'] ?? 0)),2) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <h3 style="margin:1.1rem 0 .35rem;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#fff;">Taxes</h3>
            <table>
                <thead><tr><th>Label</th><th style="width:14%;text-align:right;">Current</th><th style="width:14%;text-align:right;">YTD</th></tr></thead>
                <tbody>
                <?php foreach ($payload['taxes'] as $t): ?>
                    <tr><td><?= htmlspecialchars($t['label']) ?></td><td style="text-align:right;">$<?= number_format((float)($t['current'] ?? ($t['amount'] ?? 0)),2) ?></td><td style="text-align:right;">$<?= number_format((float)($t['ytd'] ?? ($t['amount'] ?? 0)),2) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="summary-grid">
                <div class="summary-box"><h3>Gross</h3><div style="font-size:1.1rem;font-weight:600;">$<?= number_format($grossPer,2) ?></div></div>
                <div class="summary-box"><h3>Deductions</h3><div style="font-size:1.1rem;font-weight:600;">$<?= number_format($dedPer,2) ?></div></div>
                <div class="summary-box"><h3>Taxes</h3><div style="font-size:1.1rem;font-weight:600;">$<?= number_format($taxPer,2) ?></div></div>
                <div class="summary-box"><h3>Net Pay</h3><div style="font-size:1.1rem;font-weight:600;">$<?= number_format($netPer,2) ?></div></div>
            </div>
            <div style="margin:1.2rem 0 .6rem;height:1px;background:linear-gradient(90deg,rgba(255,255,255,.15),rgba(255,255,255,0));"></div>
            <div class="actions" style="display:flex;gap:.8rem;flex-wrap:wrap;">
                <form action="/create" method="GET">
                    <button type="submit" class="btn-primary edit">← Edit</button>
                </form>
                <form action="/order" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <?php foreach ($payload as $k=>$v): if(in_array($k,['earnings','deductions','taxes'])) continue; ?>
                        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars(is_scalar($v)?$v:json_encode($v)) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="earnings_json" value='<?= json_encode($payload['earnings']) ?>'>
                    <input type="hidden" name="deductions_json" value='<?= json_encode($payload['deductions']) ?>'>
                    <input type="hidden" name="taxes_json" value='<?= json_encode($payload['taxes']) ?>'>
                    <input type="hidden" name="template_key" value="<?= htmlspecialchars($payload['template_key'] ?? 'black') ?>">
                    <button type="submit" class="btn-primary">Create Order & Checkout →</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
