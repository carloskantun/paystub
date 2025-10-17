<?php
namespace App\Controllers;

use App\Services\PaymentsService;

class WebhookController
{
    public function handle()
    {
        $provider = 'stripe'; // Ruta dedicada a stripe en esta iteración
        if (isset($_GET['verify']) && $_GET['verify']=='1') {
            if (!empty($_GET['sid'])) {
                $out = service(PaymentsService::class)->verifyAndMarkPaid($_GET['sid']);
            } elseif (!empty($_GET['id'])) {
                $out = service(PaymentsService::class)->verifyByOrder($_GET['id']);
            } else {
                $out = ['ok'=>false,'error'=>'missing_params'];
            }
            header('Content-Type: application/json');
            echo json_encode($out);
            return;
        }
        $raw = file_get_contents('php://input');
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
        $result = service(PaymentsService::class)->handleWebhook($provider, $raw, $sig);
        http_response_code($result['status_code'] ?? 200);
        header('Content-Type: application/json');
        echo json_encode(['ok' => $result['ok'] ?? false]);
    }
}
