<?php
// Temporary protected endpoint to persist a calculation and generate PDF.
// USAGE (browser): https://yourdomain.com/persist_calc_web.php?token=THE_TOKEN
// Optional: POST JSON body with { state: {...}, calculation_orchestrator: {...} }

define('PERSIST_TOKEN', 'persist_token_4b2f9a');

function out_json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

$token = $_GET['token'] ?? '';
if ($token !== PERSIST_TOKEN) {
    out_json(['ok'=>false,'error'=>'invalid_token'],403);
}

// Try to locate bootstrap.php
$candidates = [
    __DIR__ . '/../paystub/app/bootstrap.php',
    __DIR__ . '/../../paystub/app/bootstrap.php',
    __DIR__ . '/../app/bootstrap.php',
    __DIR__ . '/../../app/bootstrap.php',
];
$boot = null;
foreach ($candidates as $p) { if (file_exists($p)) { $boot = $p; break; } }
if (!$boot) { out_json(['ok'=>false,'error'=>'bootstrap_not_found','candidates'=>$candidates],500); }
try { require_once $boot; } catch (Throwable $e) { out_json(['ok'=>false,'error'=>'bootstrap_failed','message'=>$e->getMessage()],500); }

// Read input JSON if provided
$raw = file_get_contents('php://input');
$input = [];
if ($raw) { $decoded = json_decode($raw, true); if (is_array($decoded)) $input = $decoded; }

// Minimal state defaults — override with input['state'] or query params
$state = $input['state'] ?? [
    'buyer_email' => $_GET['email'] ?? 'no-reply@example.com',
    'template_key' => $_GET['template_key'] ?? 'horizontal_blue',
    'pay_schedule' => $_GET['pay_schedule'] ?? 'biweekly',
    'stubs_count' => isset($_GET['stubs_count']) ? (int)$_GET['stubs_count'] : 2,
    'employer_name' => $_GET['employer_name'] ?? 'ACME Inc',
    'employee_name' => $_GET['employee_name'] ?? 'Test Employee',
];

// Default calculation_orchestrator — uses the payload you provided earlier if none provided
$defaultCalc = [
    'earnings'=>[
        [ ['label'=>'Regular','hours'=>40,'rate'=>18,'current'=>720,'ytd'=>720] ],
        [ ['label'=>'Regular','hours'=>40,'rate'=>18,'current'=>720,'ytd'=>1440] ]
    ],
    'taxes'=>[
        [
            ['label'=>'Federal Income Tax','current'=>15.85,'ytd'=>15.85,'sort_order'=>0],
            ['label'=>'Social Security Employee Tax','current'=>44.64,'ytd'=>44.64,'sort_order'=>1],
            ['label'=>'Employee Medicare','current'=>10.44,'ytd'=>10.44,'sort_order'=>2],
            ['label'=>'Additional Medicare','current'=>0,'ytd'=>0,'sort_order'=>3],
            ['label'=>'CA State Tax','current'=>0,'ytd'=>0,'sort_order'=>10]
        ],
        [
            ['label'=>'Federal Income Tax','current'=>15.85,'ytd'=>31.69,'sort_order'=>0],
            ['label'=>'Social Security Employee Tax','current'=>44.64,'ytd'=>89.28,'sort_order'=>1],
            ['label'=>'Employee Medicare','current'=>10.44,'ytd'=>20.88,'sort_order'=>2],
            ['label'=>'Additional Medicare','current'=>0,'ytd'=>0,'sort_order'=>3],
            ['label'=>'CA State Tax','current'=>0,'ytd'=>0,'sort_order'=>10]
        ]
    ],
    'deductions'=>[[],[]],
    'summary'=>[
        ['gross'=>720,'fit_taxable_wages'=>158.46,'taxes_total'=>70.93,'deductions_total'=>0,'net'=>649.07],
        ['gross'=>720,'fit_taxable_wages'=>158.46,'taxes_total'=>70.93,'deductions_total'=>0,'net'=>649.07],
    ],
    'meta'=>['gross_per_period'=>720,'annual_gross'=>18720,'federal_taxable_annual'=>4120]
];

$calc = $input['calculation_orchestrator'] ?? $defaultCalc;

// Persist via OrdersService
try {
    $ordersService = service(\App\Services\OrdersService::class);
    $orderId = $ordersService->createOrUpdateFromCalc($state, $calc);
} catch (Throwable $e) {
    out_json(['ok'=>false,'error'=>'persist_failed','message'=>$e->getMessage(),'trace'=>explode("\n",$e->getTraceAsString())],500);
}

// Generate PDF
try {
    $pdfService = service(\App\Services\PdfService::class);
    $orders = $ordersService->find($orderId);
    $periods = service(\App\Services\PeriodsService::class)->generate($state['pay_schedule'], (int)$state['stubs_count']);
    $items = $ordersService->fetchItems($orderId);
    $path = $pdfService->render($state['template_key'] ?? 'horizontal_blue', [
        'order'=>$orders, 'periods'=>$periods, 'items'=>$items, 'employer'=>$orders['employer_json'] ? json_decode($orders['employer_json'],true):[], 'employee'=>$orders['employee_json'] ? json_decode($orders['employee_json'],true):[]
    ], false, $orderId);
} catch (Throwable $e) {
    out_json(['ok'=>false,'error'=>'pdf_failed','message'=>$e->getMessage(),'trace'=>explode("\n",$e->getTraceAsString())],500);
}

out_json(['ok'=>true,'order_id'=>$orderId,'pdf'=>$path]);
