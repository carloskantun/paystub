<?php
namespace App\Services;

use Stripe\StripeClient;
use Stripe\Webhook;
use App\Services\OrdersService;
use App\Services\PdfService;

class PaymentsService
{
    public function createSession(string $provider, array $data): array
    {
    if ($provider === 'stripe') {
            // Accept STRIPE_SECRET (preferred) or legacy STRIPE_SK
            $secret = env('STRIPE_SECRET') ?: env('STRIPE_SK');
            if (!$secret) {
                return [
                    'error' => 'Stripe not configured',
                    'needs_config' => true,
                    'message' => 'Missing STRIPE_SECRET in .env. Add STRIPE_SECRET=sk_live_xxx (or test key) and reload.'
                ];
            }
            // Store publishable key for potential client usage (if set)
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            if (empty($_SESSION['stripe_pk'])) {
                $_SESSION['stripe_pk'] = env('STRIPE_PK');
            }
            $stripe = new StripeClient($secret);
            // Cantidades en cents
            $amountCents = (int)round($data['amount'] * 100);
            $domain = rtrim((isset($_SERVER['HTTPS'])?'https://':'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
            // Do NOT include session_id in query (can trigger ModSecurity). We'll verify server-side by order id.
            $successUrl = $domain . '/order/' . $data['order_id'] . '?paid=1';
            $cancelUrl = $domain . '/order/' . $data['order_id'] . '?canceled=1';
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $data['email'] ?? null,
                'client_reference_id' => $data['order_id'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $data['currency'],
                        'product_data' => [ 'name' => 'Pay Stubs (' . $data['order_id'] . ')' ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [ 'order_id' => $data['order_id'] ],
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
            ]);

            // Persistir payment y actualizar estado order -> pending
            $pdo = db();
            $pdo->prepare('REPLACE INTO payments (order_id, provider, session_id, status, currency, amount_total, created_at) VALUES (:order_id, "stripe", :session_id, "pending", :currency, :amount_total, NOW())')
                ->execute([
                    ':order_id' => $data['order_id'],
                    ':session_id' => $session->id,
                    ':currency' => $data['currency'],
                    ':amount_total' => $data['amount'],
                ]);
            service(OrdersService::class)->setStatus($data['order_id'], 'pending');

            return [
                'provider' => 'stripe',
                'session_id' => $session->id,
                'redirect_url' => $session->url,
            ];
        }
        return ['error' => 'Unsupported provider'];
    }

    public function handleWebhook(string $provider, string $raw, ?string $signature): array
    {
        if ($provider === 'stripe') {
            $endpointSecret = env('STRIPE_WEBHOOK_SECRET');
            if (!$endpointSecret) { return ['ok' => false, 'status_code' => 400, 'error' => 'Webhook secret missing']; }
            try {
                $event = Webhook::constructEvent($raw, $signature ?? '', $endpointSecret);
            } catch (\Throwable $e) {
                $GLOBALS['logger']->error('Webhook signature failed: ' . $e->getMessage());
                return ['ok' => false, 'status_code' => 400];
            }
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $orderId = $session->metadata->order_id ?? null;
                if ($orderId) {
                    $this->markOrderPaid($orderId, $session->id, $raw);
                }
            }
            return ['ok' => true];
        }
        return ['ok' => false, 'status_code' => 400];
    }

    /**
     * Fallback verification when returning from success_url without webhook.
     * Returns ['ok'=>bool, 'order_id'=>?, 'status'=>?]
     */
    public function verifyAndMarkPaid(string $sessionId): array
    {
        $secret = env('STRIPE_SECRET') ?: env('STRIPE_SK');
        if (!$secret) { return ['ok'=>false,'error'=>'missing_secret']; }
        try {
            $stripe = new StripeClient($secret);
            $session = $stripe->checkout->sessions->retrieve($sessionId, []);
            $paid = ($session->payment_status === 'paid') || ($session->status === 'complete');
            $orderId = $session->metadata->order_id ?? $session->client_reference_id ?? null;
            if ($paid && $orderId) {
                $this->markOrderPaid($orderId, $sessionId, json_encode($session));
                return ['ok'=>true,'order_id'=>$orderId,'status'=>'paid'];
            }
            return ['ok'=>false,'order_id'=>$orderId,'status'=>$session->payment_status ?? 'unknown'];
        } catch (\Throwable $e) {
            if (isset($GLOBALS['logger'])) { $GLOBALS['logger']->error('verifyAndMarkPaid error: '.$e->getMessage()); }
            return ['ok'=>false,'error'=>'exception'];
        }
    }

    private function markOrderPaid(string $orderId, string $sessionId = '', string $payloadRaw = ''): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE orders SET status = "paid" WHERE id = :id AND status <> "paid"');
        $stmt->execute([':id' => $orderId]);
        // Actualizar payment row
        if ($sessionId) {
            $pdo->prepare('UPDATE payments SET status = "paid", webhook_payload_json = :payload WHERE order_id = :order_id AND session_id = :sid')
                ->execute([
                    ':payload' => $payloadRaw ?: null,
                    ':order_id' => $orderId,
                    ':sid' => $sessionId,
                ]);
        }
        // Generar PDF al pagar (sin cola todavía) asegurando incluir items
        $orders = service(OrdersService::class)->find($orderId);
        $periods = service(\App\Services\PeriodsService::class)->generate($orders['pay_schedule'], (int)$orders['count_stubs']);
        $items = service(OrdersService::class)->fetchItems($orderId);
        // Decode employer/employee JSON for richer header info
        $employer = [];$employee=[];
        if (!empty($orders['employer_json'])) { $tmp=json_decode($orders['employer_json'], true); if (is_array($tmp)) $employer=$tmp; }
        if (!empty($orders['employee_json'])) { $tmp=json_decode($orders['employee_json'], true); if (is_array($tmp)) $employee=$tmp; }
        // If a logo was captured in session state and stored on disk, embed as data URI
        $branding = [];
        if (session_status()!==PHP_SESSION_ACTIVE) { @session_start(); }
        if (!empty($_SESSION['wizard_state']['employer_logo_data'])) {
            $branding['logo'] = $_SESSION['wizard_state']['employer_logo_data'];
        } elseif (!empty($employer['logo_data'])) {
            $branding['logo'] = $employer['logo_data'];
        }
        service(PdfService::class)->render($orders['template_key'], [
            'order' => $orders,
            'periods' => $periods,
            'items' => $items,
            'employer' => $employer,
            'employee' => $employee,
            'branding' => $branding,
        ], false, $orderId);
        service(\App\Services\AuditService::class)->log('paid', $orderId, []);
        // Email con link de descarga
        $token = service(\App\Services\TokenService::class)->generate($orderId);
        $domain = rtrim((isset($_SERVER['HTTPS'])?'https://':'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
        $downloadUrl = $domain . '/pdf/' . $token;
    $amount = $orders['gross'] ?? 0; // per stub gross; pricing can recalc for total
    try { $pricing = service(\App\Services\PricingService::class); $total = $pricing->calculate((int)$orders['count_stubs'], $orders['template_key']); } catch (\Throwable $e) { $total = null; }
    $body = "<p>Payment confirmed.</p>".
         "<p><strong>Order:</strong> {$orderId}<br>".
         "<strong>Stubs:</strong> {$orders['count_stubs']}<br>".
         ($total!==null?"<strong>Total Paid:</strong> $".number_format($total,2)."<br>":'').
         "<strong>Template:</strong> ".htmlspecialchars($orders['template_key'])."</p>".
         "<p>You can download your PDF here:<br><a href='{$downloadUrl}'>{$downloadUrl}</a></p>".
         "<p>Keep this email for your records.</p>";
    $pdfPath = __DIR__.'/../../storage/paystubs/'.$orderId.'/stub.pdf';
    service(\App\Services\EmailService::class)->send($orders['email'], 'Your Pay Stubs Are Ready', $body, is_file($pdfPath)?[$pdfPath]:[]);
    }

    /**
     * Verify by order id (read pending payment session_id from DB).
     */
    public function verifyByOrder(string $orderId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT session_id FROM payments WHERE order_id = :id AND provider = "stripe" ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([':id'=>$orderId]);
        $sid = $stmt->fetchColumn();
        if (!$sid) { return ['ok'=>false,'error'=>'no_session','order_id'=>$orderId]; }
        return $this->verifyAndMarkPaid($sid);
    }
}
