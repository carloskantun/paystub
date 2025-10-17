<?php
// vendor is located at ../vendor relative to this tests folder (paystub/paystub/vendor)
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

use App\Services\CalculationOrchestrator;
use App\Services\AutoCalcService;

function dump_json($label, $var) {
    echo "\n---- $label ----\n";
    echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

$payload = [
    'pay_schedule' => 'biweekly',
    'stubs_count' => 2,
    'pay_type' => 'hourly',
    'hours_per_period' => 40,
    'hourly_rate' => 18.0,
    'employee_state' => 'CA',
    'year' => (int)date('Y')
];

$orc = service(CalculationOrchestrator::class);
$auto = service(AutoCalcService::class);

$batch = $orc->computeBatch($payload);
$autoRes = $auto->compute($payload);

dump_json('CalculationOrchestrator', $batch);
dump_json('AutoCalcService', $autoRes);

echo "\nNota: si los montos aparecen 0.00 en taxes, revisa que las tasas en TaxConfigRepository / fica / state rates no sean 0 y que el " .
     "payload tenga hours/rate o annual salary válido.\n";

