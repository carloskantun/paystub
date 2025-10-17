<?php
namespace App\Services;

/**
 * AutoCalcService
 * Genera líneas básicas (earnings + taxes) a partir de inputs mínimos.
 * Mejora: incluye deducción estándar simple, Additional Medicare y configuración por ENV.
 * Simplificaciones / Limitaciones:
 *  - Un único ingreso "Regular Income" (usuario puede añadir más manualmente luego)
 *  - Federal: brackets 2024 (Single) + deducción estándar aproximada (sin créditos / withholding tables reales)
 *  - Social Security: 6.2% hasta wage base (no prorrateo fino de cruce en mitad de periodo)
 *  - Medicare: 1.45% + 0.9% adicional sobre exceso del umbral (Single) anualizado de forma lineal
 *  - State: tasa plana opcional (placeholder) – se puede sustituir por tablas progresivas futuras
 *  - No considera pre‑tax deductions ni allowances; resultado es estimación educativa
 */
class AutoCalcService
{
    private array $federalBrackets2024Single = [
        // threshold => rate
        0 => 0.10,
        11600 => 0.12,
        47150 => 0.22,
        100525 => 0.24,
        191950 => 0.32,
        243725 => 0.35,
        609350 => 0.37,
    ];
     // Defaults (override via env if disponible)
     private int $socialSecurityWageBase; // 2024 base
     private float $ssRate = 0.062;
     private float $medicareRate = 0.0145;
     private float $additionalMedicareRate = 0.009; // excess
     private float $additionalMedicareThresholdSingle = 200000.0; // Single filing
     private float $standardDeductionSingle = 14600.0; // 2024 approx

    private array $stateFlat = [
        'CA' => 0.01, // placeholder simple rates
        'NY' => 0.02,
        'TX' => 0.0,
        'FL' => 0.0,
        'IL' => 0.015,
    ];

    public function __construct()
    {
        // Permitir override por ENV sin romper si helper no existe
        $this->socialSecurityWageBase = (int)(function_exists('env') ? env('SS_WAGE_BASE', 168600) : 168600);
        if (function_exists('env')) {
            $sd = env('STANDARD_DEDUCTION_SINGLE'); if ($sd !== null) $this->standardDeductionSingle = (float)$sd;
            $amThr = env('ADDITIONAL_MEDICARE_THRESHOLD_SINGLE'); if ($amThr !== null) $this->additionalMedicareThresholdSingle = (float)$amThr;
        }
    }

    public function compute(array $payload): array
    {
        $schedule = $payload['pay_schedule'] ?? 'biweekly';
        $map = ['weekly'=>52,'biweekly'=>26,'semi-monthly'=>24,'monthly'=>12];
        $periodsYear = $map[$schedule] ?? 26;

        // Determine gross per period and hours/rate
        $isSalary = (($payload['pay_type'] ?? 'hourly') === 'salary');
        if ($isSalary) {
            $grossPer = (float)$payload['annual_salary'] / max(1,$periodsYear);
            // approximate hours/rate unknown for salary; leave hours/rate null
            $hoursPer = null; $rate = null;
        } else {
            $hoursPer = (float)($payload['hours_per_period'] ?? 0);
            $rate = (float)($payload['hourly_rate'] ?? 0);
            $grossPer = $rate * $hoursPer;
        }

        $count = (int)($payload['stubs_count'] ?? 1);

        // Annualized and taxable calculation
        $annualGross = $grossPer * $periodsYear;
        $standardDeduction = $this->standardDeductionSingle; // could be read from tax_years table in future
        // FIT taxable wages: prorate standard deduction per period (simple approach)
        $fit_taxable_wages_per = max(0.0, $grossPer - ($standardDeduction / $periodsYear));
        $taxableAnnual = max(0.0, $annualGross - $standardDeduction);
        $federalAnnual = $this->estimateFederal($taxableAnnual);
        $federalPer = $federalAnnual / $periodsYear;

        // FICA
        $ss_wage_base = $this->socialSecurityWageBase;
        // assume ytd wages unknown here; apply simple per-period cap
        $ssPer = min($grossPer * $this->ssRate, max(0, ($ss_wage_base * $this->ssRate) / $periodsYear));
        $medicarePer = $grossPer * $this->medicareRate;
        $additionalMedPer = 0.0;
        if ($annualGross > $this->additionalMedicareThresholdSingle) {
            $excessAnnual = max(0.0, $annualGross - $this->additionalMedicareThresholdSingle);
            $additionalMedPer = ($excessAnnual * $this->additionalMedicareRate) / $periodsYear;
        }

        // State
        $stateCode = $payload['employee_state'] ?? null;
        $stateRate = ($stateCode && isset($this->stateFlat[$stateCode])) ? $this->stateFlat[$stateCode] : 0.0;
        $statePer = $grossPer * $stateRate;

        // Build per-stub arrays
        $earnings = array_fill(0, max(1,$count), []);
        $taxes = array_fill(0, max(1,$count), []);
        $deductions = array_fill(0, max(1,$count), []);
        $summary = array_fill(0, max(1,$count), null);

        for ($i=0;$i<$count;$i++) {
            // earnings: include hours/rate/current/ytd
            $ytdMultiplier = $i+1;
            $current = round($grossPer,2);
            $ytd = round($grossPer * $ytdMultiplier,2);
            $earnings[$i][] = [
                'label' => 'Regular Income',
                'hours' => $hoursPer !== null ? round($hoursPer,2) : null,
                'rate'  => $rate !== null ? round($rate,4) : null,
                'current' => $current,
                'ytd' => $ytd
            ];

            // taxes rows
            $federal_cur = round($federalPer,2);
            $ss_cur = round($ssPer,2);
            $medicare_cur = round($medicarePer,2);
            $addl_med_cur = round($additionalMedPer,2);
            $state_cur = round($statePer,2);

            $taxRow = [];
            $taxRow[] = ['label'=>'Federal Income Tax','current'=>$federal_cur,'ytd'=>round($federal_cur*$ytdMultiplier,2),'sort_order'=>0];
            $taxRow[] = ['label'=>'Social Security Employee Tax','current'=>$ss_cur,'ytd'=>round($ss_cur*$ytdMultiplier,2),'sort_order'=>1];
            $taxRow[] = ['label'=>'Employee Medicare','current'=>$medicare_cur,'ytd'=>round($medicare_cur*$ytdMultiplier,2),'sort_order'=>2];
            if ($addl_med_cur > 0) { $taxRow[] = ['label'=>'Additional Medicare','current'=>$addl_med_cur,'ytd'=>round($addl_med_cur*$ytdMultiplier,2),'sort_order'=>3]; }
            if ($state_cur > 0) { $taxRow[] = ['label'=>($stateCode ? $stateCode : 'State').' State Tax','current'=>$state_cur,'ytd'=>round($state_cur*$ytdMultiplier,2),'sort_order'=>10]; }

            $taxes[$i] = $taxRow;

            // deductions empty for now
            $deductions[$i] = [];

            $taxes_total = array_sum(array_map(fn($r)=>$r['current'],$taxRow));
            $deductions_total = 0.0;
            $net = round($current - $taxes_total - $deductions_total,2);

            $summary[$i] = [
                'gross' => $current,
                'fit_taxable_wages' => round($fit_taxable_wages_per,2),
                'taxes_total' => round($taxes_total,2),
                'deductions_total' => round($deductions_total,2),
                'net' => $net
            ];
        }

        return [
            'earnings' => $earnings,
            'taxes' => $taxes,
            'deductions' => $deductions,
            'summary' => $summary,
            'periods_per_year' => $periodsYear,
            'federal_taxable_annual' => round($taxableAnnual,2),
        ];
    }

    private function estimateFederal(float $taxableAnnual): float
    {
        $br = $this->federalBrackets2024Single;
        ksort($br);
        $tax = 0.0; $prevThreshold = 0; $prevRate = 0.0; $keys = array_keys($br);
        foreach ($keys as $idx=>$threshold) {
            $rate = $br[$threshold];
            if ($taxableAnnual <= $threshold) {
                $tax += ($taxableAnnual - $prevThreshold) * $prevRate;
                return $tax;
            }
            if ($idx>0) { // full bracket between prevThreshold and threshold
                $tax += ($threshold - $prevThreshold) * $prevRate;
            }
            $prevThreshold = $threshold;
            $prevRate = $rate;
        }
        // Above last threshold
        $tax += ($taxableAnnual - $prevThreshold) * $prevRate;
        return $tax;
    }
}
