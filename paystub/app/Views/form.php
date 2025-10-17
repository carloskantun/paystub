<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Paystub - Form</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: system-ui, Arial, sans-serif; margin: 2rem; max-width: 1040px; }
        h1 { margin-top: 0; }
        form { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 1rem 2rem; }
        label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; display:block; margin-bottom: .25rem; }
        input, select { width:100%; padding:.5rem .55rem; border:1px solid #ccc; border-radius:4px; font-size:.9rem; }
        .full { grid-column: 1 / -1; }
        .actions { margin-top:1rem; }
        button { background:#2563eb; border:none; color:#fff; padding:.75rem 1.2rem; border-radius:6px; font-size:.95rem; cursor:pointer; }
        button:hover { background:#1d4ed8; }
        .errors { background:#fee2e2; color:#991b1b; padding:.75rem 1rem; border-radius:4px; grid-column:1 / -1; }
        fieldset { border:1px solid #ddd; padding:1rem; border-radius:6px; grid-column:1 / -1; }
        fieldset legend { padding:0 .5rem; font-weight:600; font-size:.9rem; }
        table.lines { width:100%; border-collapse:collapse; margin-top:.5rem; }
        table.lines th, table.lines td { border:1px solid #e5e7eb; padding:.35rem .45rem; font-size:.7rem; }
        table.lines input { font-size:.65rem; padding:.25rem .3rem; }
        .add-line { background:#059669; margin-top:.5rem; }
        .add-line:hover { background:#047857; }
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:.75rem 1.25rem; }
    </style>
</head>
<body>
    <h1>Create pay stub</h1>
    <p style="margin-top:-.5rem;color:#555">Step 1 of 2 – Enter information.</p>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <strong>Fix the following:</strong>
            <ul>
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/preview">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="full">
            <label for="email">Email (delivery)</label>
            <input required type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
        </div>
        <div>
            <label for="employer_name">Employer Name</label>
            <input required id="employer_name" name="employer_name" value="<?= htmlspecialchars($old['employer_name'] ?? '') ?>">
        </div>
        <div>
            <label for="employee_name">Employee Name</label>
            <input required id="employee_name" name="employee_name" value="<?= htmlspecialchars($old['employee_name'] ?? '') ?>">
        </div>
        <div>
            <label for="pay_schedule">Pay Schedule</label>
            <select id="pay_schedule" name="pay_schedule">
                <?php foreach (['weekly','biweekly','semi-monthly','monthly'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= (($old['pay_schedule'] ?? '')===$opt?'selected':'') ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="stubs_count"># Stubs</label>
            <input type="number" min="1" max="12" id="stubs_count" name="stubs_count" value="<?= htmlspecialchars($old['stubs_count'] ?? 1) ?>">
        </div>
        <div>
            <label for="hourly_rate">Hourly Rate</label>
            <input type="number" step="0.01" id="hourly_rate" name="hourly_rate" value="<?= htmlspecialchars($old['hourly_rate'] ?? 25) ?>">
        </div>
        <div>
            <label for="hours_per_period">Hours / Period</label>
            <input type="number" step="0.01" id="hours_per_period" name="hours_per_period" value="<?= htmlspecialchars($old['hours_per_period'] ?? 80) ?>">
        </div>
        <fieldset style="grid-column:1 / -1;">
            <legend>Template</legend>
            <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                <?php $templates = templates_config(); $selectedTpl = $old['template_key'] ?? 'classic_black'; ?>
                <?php foreach ($templates as $k=>$tpl): ?>
                    <label style="border:1px solid #ccc;border-radius:6px;padding:.5rem;display:flex;flex-direction:column;align-items:center;width:140px;cursor:pointer;position:relative;">
                        <input type="radio" name="template_key" value="<?= htmlspecialchars($k) ?>" <?= $selectedTpl===$k? 'checked':'' ?> style="position:absolute;top:6px;left:6px;">
                        <span style="font-size:.65rem;font-weight:600;margin-top:1.5rem;"><?= htmlspecialchars($tpl['name']) ?></span>
                        <?php if (!empty($tpl['preview'])): ?>
                            <img alt="preview" src="<?= htmlspecialchars($tpl['preview']) ?>" style="width:100%;object-fit:cover;margin-top:.35rem;border-radius:4px;">
                        <?php else: ?><span style="font-size:.55rem;color:#666;margin-top:.35rem;">No preview</span><?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset>
            <legend>Earnings</legend>
            <table class="lines" id="earnings-table">
                <thead><tr><th>Label</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    <?php foreach (($old['earnings'] ?? []) as $row): ?>
                        <tr>
                            <td><input name="earnings[label][]" value="<?= htmlspecialchars($row['label'] ?? '') ?>"></td>
                            <td><input name="earnings[amount][]" type="number" step="0.01" value="<?= htmlspecialchars($row['amount'] ?? '') ?>"></td>
                            <td><button type="button" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="add-line" data-target="earnings-table">+ Add Earning</button>
        </fieldset>
        <fieldset>
            <legend>Deductions</legend>
            <table class="lines" id="deductions-table">
                <thead><tr><th>Label</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    <?php foreach (($old['deductions'] ?? []) as $row): ?>
                        <tr>
                            <td><input name="deductions[label][]" value="<?= htmlspecialchars($row['label'] ?? '') ?>"></td>
                            <td><input name="deductions[amount][]" type="number" step="0.01" value="<?= htmlspecialchars($row['amount'] ?? '') ?>"></td>
                            <td><button type="button" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="add-line" data-target="deductions-table">+ Add Deduction</button>
        </fieldset>
        <fieldset>
            <legend>Taxes</legend>
            <table class="lines" id="taxes-table">
                <thead><tr><th>Label</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                    <?php foreach (($old['taxes'] ?? []) as $row): ?>
                        <tr>
                            <td><input name="taxes[label][]" value="<?= htmlspecialchars($row['label'] ?? '') ?>"></td>
                            <td><input name="taxes[amount][]" type="number" step="0.01" value="<?= htmlspecialchars($row['amount'] ?? '') ?>"></td>
                            <td><button type="button" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="add-line" data-target="taxes-table">+ Add Tax</button>
        </fieldset>
    <div class="full actions">
            <button type="submit">Preview</button>
        </div>
    </form>
    <script>
    document.querySelectorAll('.add-line').forEach(btn=>{
        btn.addEventListener('click',()=>{
            const id = btn.dataset.target; const tbody = document.querySelector('#'+id+' tbody');
            const tr = document.createElement('tr');
            const base = id.replace('-table','');
            tr.innerHTML = `<td><input name="${base}[label][]"></td><td><input name="${base}[amount][]" type="number" step="0.01" value="0.00"></td><td><button type="button" onclick="this.closest('tr').remove()">✕</button></td>`;
            tbody.appendChild(tr);
        });
    });
    </script>
</body>
</html>
