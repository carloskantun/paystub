<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

$tests = [];

$tests['Periods weekly count'] = function () {
    $svc = service(App\Services\PeriodsService::class);
    $r = $svc->generate('weekly', 3);
    assert(count($r) === 3, 'Expected 3 periods');
};

$tests['Token roundtrip'] = function () {
    $tokSvc = service(App\Services\TokenService::class);
    $t = $tokSvc->generate('abc');
    $id = $tokSvc->verify($t);
    assert($id === 'abc', 'Token verify mismatch');
};

$tests['Pricing discount'] = function () {
    $p = service(App\Services\PricingService::class)->calculate(10, 'classic_black');
    assert($p < 10 * (float)env('PRICE_PER_STUB', '5.00'), 'Discount not applied');
};

$failures = 0; $ran = 0;
foreach ($tests as $name => $fn) {
    try { $fn(); echo "[PASS] $name\n"; } catch (AssertionError $e) { $failures++; echo "[FAIL] $name: {$e->getMessage()}\n"; }
    $ran++;
}
// Summary
if ($failures) {
    echo "Executed $ran tests. Failures: $failures\n";
    exit(1);
}

echo "All $ran tests passed.\n";
