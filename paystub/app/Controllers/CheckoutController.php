<?php
namespace App\Controllers;

use App\Services\PaymentsService;
use App\Services\OrdersService;
use App\Services\PricingService;

class CheckoutController
{
    public function createSession()
    {
        $orderId = $_POST['order_id'] ?? '';
        if (!$orderId) {
            header('Location: /');
            return;
        }
        $orders = service(OrdersService::class);
        $order = $orders->find($orderId);
        if (!$order) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            echo 'Order not found';
            return;
        }
        if ($order['status'] !== 'draft') {
            header('Location: /order/' . $orderId);
            return;
        }

        $pricing = service(PricingService::class);
        $amount = $pricing->calculate((int)$order['count_stubs'], $order['template_key']);
        $session = service(PaymentsService::class)->createSession('stripe', [
            'order_id' => $orderId,
            'amount'   => $amount,
            'currency' => 'usd',
            'email'    => $order['email'],
        ]);

        if (!empty($session['redirect_url'])) {
            header('Location: ' . $session['redirect_url']);
            return;
        }
        echo 'Unable to create payment session';
    }
}
