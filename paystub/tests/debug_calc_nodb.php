<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

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
    'auto_mode' => 1,
];

$auto = service(AutoCalcService::class);
$autoRes = $auto->compute($payload);

// Mostrar por stub
foreach ($autoRes['earnings'] as $i => $eLines) {
    echo "\n=== Stub #".($i+1)." ===\n";
    echo "Earnings:\n";
    foreach ($eLines as $l) { echo " - " . ($l['label'] ?? '') . " | hours=" . var_export($l['hours'], true) . " | rate=" . var_export($l['rate'], true) . " | current=" . number_format($l['current'],2) . " | ytd=" . number_format($l['ytd'],2) . "\n"; }
    echo "Taxes:\n";
    foreach ($autoRes['taxes'][$i] as $t) { echo " - " . ($t['label'] ?? '') . " | current=" . number_format($t['current'],2) . " | ytd=" . number_format($t['ytd'],2) . "\n"; }
    echo "Summary:\n";
    print_r($autoRes['summary'][$i]);
}

echo "\nNota: este script evita la conexión a BD usando directamente AutoCalcService. Para test completo (federal brackets desde DB y persistencia) necesitas ejecutar el otro script con credenciales DB correctas.\n";
