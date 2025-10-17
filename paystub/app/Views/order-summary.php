<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Summary</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: system-ui, Arial, sans-serif; margin:2rem; max-width:880px; }
        h1 { margin-top:0; }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th, td { padding:.5rem .6rem; border:1px solid #e5e7eb; font-size:.85rem; text-align:left; }
        th { background:#f1f5f9; }
        .actions { margin-top:1.5rem; }
        a.btn, button { background:#2563eb; color:#fff; padding:.75rem 1.2rem; border-radius:6px; text-decoration:none; border:none; cursor:pointer; }
        a.btn:hover, button:hover { background:#1d4ed8; }
        .muted { color:#555; }
    </style>
</head>
<body>
    <h1>Order Created</h1>
        <?php $qsPaid = isset($_GET['paid']); $qsCanceled = isset($_GET['canceled']); ?>
        <?php if ($qsPaid && $order['status']!=='paid'): ?>
            <div style="background:#fff3cd;color:#92400e;padding:.75rem 1rem;border:1px solid #fcd34d;border-radius:6px;">Returning from payment... waiting for confirmation. This page will update automatically.</div>
        <?php elseif ($qsPaid && $order['status']==='paid'): ?>
            <div style="background:#dcfce7;color:#166534;padding:.75rem 1rem;border:1px solid #86efac;border-radius:6px;">Payment confirmed. Download is ready below.</div>
        <?php elseif ($qsCanceled): ?>
            <div style="background:#fee2e2;color:#991b1b;padding:.75rem 1rem;border:1px solid #fecaca;border-radius:6px;">Payment canceled. You can retry below.</div>
        <?php endif; ?>
    <p class="muted">Order ID: <code><?= htmlspecialchars($order['id']) ?></code></p>
    <p>Status: <strong><?= htmlspecialchars($order['status']) ?></strong></p>
    <p>Total (est.): <strong>$<?= number_format($total, 2) ?></strong></p>
    <p>Template: <strong><?= htmlspecialchars($order['template_key']) ?></strong></p>
        <?php if($pmt): ?>
            <p>Payment Ref: <code><?= htmlspecialchars($pmt['session_id'] ?? '') ?></code> (<?= htmlspecialchars($pmt['provider'] ?? '') ?> / <?= htmlspecialchars($pmt['status'] ?? '') ?>)</p>
        <?php endif; ?>

    <?php if (!empty($items)): ?>
        <h3>Stub Items (per period amounts)</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
            <div>
                <h4 style="margin:.25rem 0;">Earnings</h4>
                <table>
                    <thead><tr><th>Label</th><th>Current</th></tr></thead>
                    <tbody>
                    <?php foreach ($items['earnings'] as $e): if($e['stub_index']!=0) continue; ?><tr><td><?= htmlspecialchars($e['label']) ?></td><td><?= number_format($e['current_amount'],2) ?></td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <h4 style="margin:.25rem 0;">Deductions</h4>
                <table>
                    <thead><tr><th>Label</th><th>Current</th></tr></thead>
                    <tbody>
                    <?php foreach ($items['deductions'] as $d): if($d['stub_index']!=0) continue; ?><tr><td><?= htmlspecialchars($d['label']) ?></td><td><?= number_format($d['current_amount'],2) ?></td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <h4 style="margin:.25rem 0;">Taxes</h4>
                <table>
                    <thead><tr><th>Label</th><th>Current</th></tr></thead>
                    <tbody>
                    <?php foreach ($items['taxes'] as $t): if($t['stub_index']!=0) continue; ?><tr><td><?= htmlspecialchars($t['label']) ?></td><td><?= number_format($t['current_amount'],2) ?></td></tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <h4 style="margin-top:1.25rem;">Per Stub Summary</h4>
        <table>
            <tbody>
                <tr><th>Gross</th><td><?= number_format($order['gross'],2) ?></td></tr>
                <tr><th>Deductions</th><td><?= number_format($order['deductions_total'] ?? 0,2) ?></td></tr>
                <tr><th>Taxes</th><td><?= number_format($order['taxes_total'] ?? 0,2) ?></td></tr>
                <tr><th>Net</th><td><?= number_format($order['net'],2) ?></td></tr>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Pay Periods</h3>
    <table>
        <thead><tr><th>#</th><th>Start</th><th>End</th><th>Pay Date</th></tr></thead>
        <tbody>
            <?php foreach ($periods as $p): ?>
                <tr>
                    <td><?= $p['index']+1 ?></td>
                    <td><?= $p['start_date'] ?></td>
                    <td><?= $p['end_date'] ?></td>
                    <td><?= $p['pay_date'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

        <div class="actions">
        <?php if ($order['status'] === 'draft'): ?>
            <form action="/checkout" method="POST" style="display:inline">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                <button type="submit">Proceed to Checkout</button>
            </form>
        <?php elseif ($order['status'] === 'pending'): ?>
            <button disabled id="waitBtn">Waiting Payment...</button>
            <button type="button" id="checkNowBtn" style="margin-left:8px;background:#334155;">Check Payment Status</button>
        <?php elseif ($order['status'] === 'paid'): ?>
            <?php $downloadToken = service(\App\Services\TokenService::class)->generate($order['id']); ?>
                        <?php if ((int)$order['count_stubs'] > 1): ?>
                            <a class="btn" href="/pdf-zip/<?= htmlspecialchars($downloadToken) ?>">Download ZIP</a>
                        <?php endif; ?>
                        <a class="btn" href="/pdf/<?= htmlspecialchars($downloadToken) ?>">Download PDF</a>
            <?php if($pmt): ?><span style="margin-left:8px;font-size:.75rem;color:#166534;">Paid via <?= htmlspecialchars($pmt['provider']) ?></span><?php endif; ?>
            <form action="/order/<?= htmlspecialchars($order['id']) ?>/regenerate" method="POST" style="display:inline">
                <button type="submit" style="background:#0d9488">Regenerate</button>
            </form>
        <?php else: ?>
            <button disabled><?= htmlspecialchars($order['status']) ?></button>
        <?php endif; ?>
        <a class="btn" href="/" style="background:#6b7280">New Order</a>
    </div>

        <?php if ($order['status'] === 'pending'): ?>
            <div id="loadingOverlay" style="position:fixed;inset:0;background:rgba(255,255,255,.8);display:none;align-items:center;justify-content:center;z-index:50;">
                <div style="padding:16px 20px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;box-shadow:0 10px 25px rgba(0,0,0,.08);font-size:.95rem;">Confirming payment…</div>
            </div>
            <script>
                (function(){
                    const ov=document.getElementById('loadingOverlay');
                    const btn=document.getElementById('checkNowBtn');
                    if(btn&&ov){
                        btn.addEventListener('click',()=>{ ov.style.display='flex'; setTimeout(()=>{ ov.style.display='none'; }, 6000); });
                    }
                })();
            </script>
        <?php endif; ?>
</body>
</html>
<script>
(function(){
    const qs=new URLSearchParams(window.location.search);
    const cameFromStripe = qs.has('paid') || qs.has('canceled');
    const statusEl=document.querySelector('p strong');
    const orderId = '<?= htmlspecialchars($order['id']) ?>';
    const checkBtn=document.getElementById('checkNowBtn');
    function verify(){
        return fetch('/webhook/stripe?verify=1&id='+encodeURIComponent(orderId), {method:'POST'})
            .then(r=>r.json()).catch(()=>({ok:false}));
    }
        function poll(){
        fetch('/order/'+orderId+'/status', {headers:{'Accept':'application/json'}})
            .then(r=>r.json())
            .then(j=>{
                if(j.status){
                    if(statusEl) statusEl.textContent=j.status;
                    if(j.status==='paid'){
                         // Reload whole page to show download link
                         window.location.replace('/order/'+orderId);
                         return;
                    }
                    if(j.status==='pending'){
                         // If came from Stripe try server-side verification repeatedly
                         const doVerify = cameFromStripe ? verify() : Promise.resolve({ok:false});
                         doVerify.finally(()=>setTimeout(poll, 3000));
                    }
                }
            }).catch(()=>{ setTimeout(poll,5000); });
    }
            // On return from Stripe, verify by order id via server (no session id in URL to avoid ModSecurity)
            if (cameFromStripe && ('<?= $order['status'] ?>'==='pending' || '<?= $order['status'] ?>'==='draft')){
                fetch('/webhook/stripe?verify=1&id='+encodeURIComponent(orderId), {method:'POST'})
                .then(()=>poll())
                .catch(()=>poll());
            } else if(cameFromStripe && ('<?= $order['status'] ?>'==='pending' || '<?= $order['status'] ?>'==='draft')){
        poll();
    }
    if(checkBtn){
        checkBtn.addEventListener('click', ()=>{
            checkBtn.disabled = true;
            verify().finally(()=>{ checkBtn.disabled=false; poll(); });
        });
    }
})();
</script>
</html>