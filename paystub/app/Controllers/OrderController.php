<?php
namespace App\Controllers;

use App\Services\OrdersService;
use App\Services\PricingService;
use App\Services\PeriodsService;

class OrderController
{
    public function create()
    {
        $payload = [
            'email'         => trim($_POST['email'] ?? ''),
            'employer_name' => trim($_POST['employer_name'] ?? ''),
            'employee_name' => trim($_POST['employee_name'] ?? ''),
            'pay_schedule'  => $_POST['pay_schedule'] ?? 'biweekly',
            'stubs_count'   => (int)($_POST['stubs_count'] ?? 1),
            'template_key'  => $_POST['template_key'] ?? 'black',
        ];

        // Decode custom lines (JSON hidden fields from preview)
        foreach (['earnings','deductions','taxes'] as $k) {
            $jsonKey = $k . '_json';
            if (!empty($_POST[$jsonKey])) {
                $decoded = json_decode((string)$_POST[$jsonKey], true);
                if (is_array($decoded)) { $payload[$k] = $decoded; }
            }
        }

        $errors = [];
        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email required'; }
        foreach (['employer_name','employee_name'] as $f) { if ($payload[$f] === '') { $errors[] = "$f required"; } }
        if ($payload['stubs_count'] < 1 || $payload['stubs_count'] > 12) { $errors[] = 'Stubs count 1-12'; }

        if ($errors) {
            $old = $payload;
            include __DIR__ . '/../Views/form-branded.php';
            return;
        }

        $ordersService = service(OrdersService::class);
        $id = $ordersService->save($payload);
        header('Location: /order/' . $id);
        exit;
    }

    public function show(string $id)
    {
        $ordersService = service(OrdersService::class);
        $order = $ordersService->find($id);
        if (!$order) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            echo 'Order not found';
            return;
        }
        $pricing = service(PricingService::class);
        $periods = service(PeriodsService::class)->generate($order['pay_schedule'], (int)$order['count_stubs']);
    $total = $pricing->calculate((int)$order['count_stubs'], $order['template_key']);
    $items = $ordersService->fetchItems($id);
    // Payment info
    $pdo = db();
    $pmt = null; $stmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY created_at DESC LIMIT 1');
    if ($stmt->execute([':id'=>$id])) { $pmt = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null; }
    include __DIR__ . '/../Views/order-summary.php';
    }

    public function regenerate(string $id)
    {
        $ordersService = service(OrdersService::class);
        $newId = $ordersService->regenerate($id);
        if (!$newId) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            echo 'Order not found';
            return;
        }
        header('Location: /order/' . $newId);
        exit;
    }

    public function status(string $id)
    {
        $ordersService = service(OrdersService::class);
        $order = $ordersService->find($id);
        if (!$order) {
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
            header('Content-Type: application/json');
            echo json_encode(['error'=>'not_found']);
            return;
        }
        header('Content-Type: application/json');
        echo json_encode([
            'id'=>$order['id'],
            'status'=>$order['status'],
            'gross'=>$order['gross'],
            'net'=>$order['net'],
        ]);
    }
}
