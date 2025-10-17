<?php
namespace App\Services;

/**
 * CalculationOrchestrator
 * Usa TaxConfigRepository para obtener parámetros y genera líneas para N stubs.
 * Simplificación inicial (sin manual overrides todavía).
 */
class CalculationOrchestrator
{
    private TaxConfigRepository $repo;
    public function __construct()
    {
        $this->repo = service(TaxConfigRepository::class);
    }

    public function computeBatch(array $input): array
    {
        $year = (int)($input['year'] ?? date('Y'));
        $schedule = $input['pay_schedule'] ?? 'biweekly';
        $map = ['weekly'=>52,'biweekly'=>26,'semi-monthly'=>24,'monthly'=>12];
        $periodsYear = $map[$schedule] ?? 26;
        $stubs = (int)($input['stubs_count'] ?? 1);
        $payType = $input['pay_type'] ?? 'hourly';
        $hours = (float)($input['hours_per_period'] ?? 0);
        $rate = (float)($input['hourly_rate'] ?? 0);
        $annualSalary = (float)($input['annual_salary'] ?? 0);
        $state = $input['employee_state'] ?? null;
        $filing = 'single'; // placeholder futuro

        // Config
        $stdDed = $this->repo->getStandardDeduction($year, $filing);
        $brackets = $this->repo->getFederalBrackets($year, $filing);
        $fica = $this->repo->getFica($year);
        $stateRate = $this->repo->getStateRate($year, $state);

        // Gross per period
        if ($payType === 'salary') {
            $grossPer = $annualSalary / max(1,$periodsYear);
        } else {
            $grossPer = $rate * $hours;
        }
        $grossPer = max(0,$grossPer);
        $annualGross = $grossPer * $periodsYear;

        // Federal taxable annual
        $taxableAnnual = max(0.0, $annualGross - $stdDed);
        $federalAnnual = $this->federalTax($taxableAnnual, $brackets);
        $federalPer = $federalAnnual / $periodsYear;

        // FICA progressive across periods
        $ssRate = $fica['ss_rate_employee'];
        $ssBase = $fica['ss_wage_base'];
        $medRate = $fica['medicare_rate'];
        $addThr = $fica['addl_medicare_threshold_single'];
        $addRate = $fica['addl_medicare_rate'];

        $earningsLines = [];
        $taxLines = [];
        $summaries = [];
        $cumGross = 0.0; $cumFed = 0.0; $cumSS = 0.0; $cumMed = 0.0; $cumState = 0.0;
        for ($i=0;$i<$stubs;$i++) {
            $cumGross += $grossPer;
            // Social Security: apply remaining base
            $remainingSS = max(0.0, $ssBase - ($cumGross - $grossPer));
            $ssCurrent = 0.0;
            if ($remainingSS > 0) {
                $ssCurrent = min($grossPer, $remainingSS) * $ssRate;
            }
            $cumSS += $ssCurrent;

            // Medicare
            $medCurrent = $grossPer * $medRate;
            // Additional Medicare only on wages above threshold within this segment
            $prevGross = $cumGross - $grossPer;
            $segment = 0.0;
            if ($cumGross > $addThr) {
                $segment = min($grossPer, $cumGross - max($prevGross, $addThr));
            }
            $addMedCurrent = $segment * $addRate;
            $cumMed += $medCurrent + $addMedCurrent;

            // Federal (even distribution per period)
            $cumFed += $federalPer;

            // State
            $stateCurrent = $grossPer * $stateRate;
            $cumState += $stateCurrent;

            $earningsLines[$i] = [
                ['label'=>'Regular','hours'=>$payType==='hourly'? round($hours,2) : null,'rate'=>$payType==='hourly'? round($rate,4) : null,'current'=>round($grossPer,2),'ytd'=>round($grossPer*($i+1),2)]
            ];

            // Build tax rows in fixed order; include zeros when not applicable
            $taxRow = [];
            // Federal
            $taxRow[] = ['label'=>'Federal Income Tax','current'=>round($federalPer,2),'ytd'=>round($cumFed,2),'sort_order'=>0];
            // Social Security
            $taxRow[] = ['label'=>'Social Security Employee Tax','current'=>round($ssCurrent,2),'ytd'=>round($cumSS,2),'sort_order'=>1];
            // Employee Medicare (base)
            $taxRow[] = ['label'=>'Employee Medicare','current'=>round($medCurrent,2),'ytd'=>round($cumMed,2),'sort_order'=>2];
            // Additional Medicare (may be zero)
            $taxRow[] = ['label'=>'Additional Medicare','current'=>round($addMedCurrent,2),'ytd'=>round($addMedCurrent*($i+1),2),'sort_order'=>3];
            // State tax (label uses state code if available)
            $stateLabel = ($state ? $state : 'State') . ' State Tax';
            $taxRow[] = ['label'=>$stateLabel,'current'=>round($stateCurrent,2),'ytd'=>round($cumState,2),'sort_order'=>10];

            $taxLines[$i] = $taxRow;

            $taxTotalCurrent = array_sum(array_map(fn($t)=>$t['current'],$taxRow));

            // FIT taxable wages: prorate standard deduction per period (simple approach)
            $fit_taxable_per = max(0.0, $grossPer - ($stdDed / $periodsYear));

            $summaries[$i] = [
                'gross'=>round($grossPer,2),
                'fit_taxable_wages'=>round($fit_taxable_per,2),
                'taxes_total'=>round($taxTotalCurrent,2),
                'deductions_total'=>0.0,
                'net'=>round($grossPer - $taxTotalCurrent,2)
            ];
        }

        return [
            'earnings'=>$earningsLines,
            'taxes'=>$taxLines,
            'deductions'=>array_fill(0,$stubs,[]),
            'summary'=>$summaries,
            'meta'=>[
                'gross_per_period'=>$grossPer,
                'annual_gross'=>$annualGross,
                'federal_taxable_annual'=>$taxableAnnual,
            ]
        ];
    }

    private function federalTax(float $taxableAnnual, array $brackets): float
    {
        $tax = 0.0; $prev=0.0; $prevRate = 0.0; $count = count($brackets);
        foreach ($brackets as $idx=>$b) {
            $thr = (float)$b['threshold']; $rate = (float)$b['rate'];
            if ($idx===0) { $prevRate = $rate; $prev = $thr; continue; }
            if ($taxableAnnual <= $thr) {
                $tax += ($taxableAnnual - $prev) * $prevRate; return $tax; }
            $tax += ($thr - $prev) * $prevRate; $prev = $thr; $prevRate = $rate;
        }
        // Above last threshold
        $tax += ($taxableAnnual - $prev) * $prevRate;
        return $tax;
    }
}
