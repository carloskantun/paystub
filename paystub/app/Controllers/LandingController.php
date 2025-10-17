<?php
namespace App\Controllers;

class LandingController
{
    public function show()
    {
        // Could load dynamic stats later (e.g., total stubs generated) – keep simple for now
        $pricingBase = (float)(env('PRICE_PER_STUB', '5.00'));
        include __DIR__ . '/../Views/landing.php';
    }
}
