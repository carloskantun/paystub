<?php
// Temporary debug endpoint — protected with a token. Upload into your public_html and remove after use.
// USAGE: https://yourdomain.com/debug_calc_web.php?token=THE_TOKEN&mode=nodb

// CHANGE THIS TOKEN before using in production if you copy the file to a public server.
define('DEBUG_TOKEN', 'debug_token_9f3a0b8c');

// Simple helper to print JSON in a <pre> for browser
function out($data) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="white-space:pre-wrap;word-break:break-word">'.htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES))."</pre>";
    exit;
}

$token = $_GET['token'] ?? '';
if ($token !== DEBUG_TOKEN) {
    http_response_code(403);
    echo "<h2>403 Forbidden</h2><p>Invalid or missing token.</p>";
    echo "<p>Upload this file to your <code>public_html</code> and call it with ?token=...&mode=nodb (or mode=full).</p>";
    exit;
}

$mode = $_GET['mode'] ?? 'nodb';

// Try to locate bootstrap.php in a few common relative locations
$candidates = [
    __DIR__ . '/../paystub/app/bootstrap.php', // if file placed in public_html
    __DIR__ . '/../../paystub/app/bootstrap.php', // if placed in public_html/paystub
    __DIR__ . '/../app/bootstrap.php', // if placed one level below app (less likely)
    __DIR__ . '/../../app/bootstrap.php',
];
$boot = null;
foreach ($candidates as $p) {
    if (file_exists($p)) { $boot = $p; break; }
}

if ($boot) {
    try {
        require_once $boot;
    } catch (Throwable $e) {
        out(['error' => 'Failed to include bootstrap', 'exception' => $e->getMessage(), 'path' => $boot]);
    }
}

// If bootstrap not found, we will run a small safe fallback calculation
if (!function_exists('service')) {
    // bootstrap not loaded — fallback simple calc function
    $hours = 40.0;
    $rate = 18.0;
    $gross = $hours * $rate;
    $federal = round($gross * 0.022, 2);
    $ss = round($gross * 0.062, 2);
    $med = round($gross * 0.0145, 2);
    $state = round($gross * 0.01, 2);
    $taxes = [
        ['label'=>'Federal Income Tax','current'=>$federal,'ytd'=>$federal],
        ['label'=>'Social Security Employee Tax','current'=>$ss,'ytd'=>$ss],
        ['label'=>'Employee Medicare','current'=>$med,'ytd'=>$med],
        ['label'=>'State Tax','current'=>$state,'ytd'=>$state],
    ];
    out(['mode'=>'fallback_nobootstrap','gross'=>$gross,'earnings'=>[['label'=>'Regular Income','hours'=>$hours,'rate'=>$rate,'current'=>$gross,'ytd'=>$gross]],'taxes'=>$taxes,'summary'=>['gross'=>$gross,'taxes_total'=>array_sum(array_column($taxes,'current'))]]);
}

// If we reached here bootstrap is loaded and service() is available
try {
    if ($mode === 'full') {
        $orc = service(\App\Services\CalculationOrchestrator::class);
        $payload = [
            'pay_schedule'=>'biweekly','stubs_count'=>2,'pay_type'=>'hourly','hours_per_period'=>40,'hourly_rate'=>18.0,'employee_state'=>'CA','year'=>date('Y')
        ];
        $batch = $orc->computeBatch($payload);
        out(['mode'=>'full','payload'=>$payload,'calculation_orchestrator'=>$batch]);
    } else {
        $auto = service(\App\Services\AutoCalcService::class);
        $payload = ['pay_schedule'=>'biweekly','stubs_count'=>2,'pay_type'=>'hourly','hours_per_period'=>40,'hourly_rate'=>18.0,'employee_state'=>'CA'];
        $res = $auto->compute($payload);
        out(['mode'=>'nodb','payload'=>$payload,'autocalc'=>$res]);
    }
} catch (Throwable $e) {
    out(['error'=>'exception','message'=>$e->getMessage(),'trace'=> explode("\n", $e->getTraceAsString())]);
}

