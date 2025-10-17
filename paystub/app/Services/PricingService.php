<?php
namespace App\Services;

class PricingService
{
    /**
     * Calcula precio total por número de stubs.
     * Reglas simples: precio base * cantidad con descuentos volumen.
     */
    public function calculate(int $count, string $templateKey = 'black'): float
    {
        $base = (float)(env('PRICE_PER_STUB', '5.00'));

        // Descuentos escalonados simples:
        if ($count >= 10) {
            $discountFactor = 0.85; // 15% off
        } elseif ($count >= 5) {
            $discountFactor = 0.90; // 10% off
        } else {
            $discountFactor = 1.0;
        }

        // Plantillas podrían tener recargos: ejemplo 'horizontal-blue' +10%
    if ($templateKey === 'horizontal_blue') {
            $base *= 1.10;
        }

        $total = $base * $count * $discountFactor;
        return round($total, 2);
    }

    /**
     * Devuelve desglose detallado para UI: base_per_stub, template_surcharge_pct, discount_pct, subtotal, total.
     */
    public function breakdown(int $count, string $templateKey = 'black'): array
    {
        $basePer = (float)(env('PRICE_PER_STUB', '5.00'));
    $surchargePct = ($templateKey === 'horizontal_blue') ? 0.10 : 0.0;
        $discountPct = 0.0;
        if ($count >= 10) { $discountPct = 0.15; } elseif ($count >=5) { $discountPct = 0.10; }
        $effectivePer = $basePer * (1 + $surchargePct) * (1 - $discountPct);
        $subtotal = $basePer * (1 + $surchargePct) * $count;
        $total = $effectivePer * $count;
        return [
            'count' => $count,
            'base_per_stub' => round($basePer,2),
            'template_surcharge_pct' => $surchargePct,
            'discount_pct' => $discountPct,
            'subtotal' => round($subtotal,2),
            'total' => round($total,2),
            'effective_per_stub' => round($effectivePer,2),
            'unit_price' => round($effectivePer,2),
        ];
    }
}
