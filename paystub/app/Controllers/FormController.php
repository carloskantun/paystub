<?php
namespace App\Controllers;

use App\Services\PeriodsService;

class FormController
{
    public function showForm()
    {
        $errors = [];
        $old = [
            'employer_name' => '',
            'employer_address' => '',
            'employee_name' => '',
            'employee_address' => '',
            'employee_ssn_last4' => '',
            'pay_schedule'  => 'biweekly',
            'stubs_count'   => 2,
            'pay_type' => 'hourly', // hourly|salary
            'hourly_rate'   => 25,
            'hours_per_period' => 80,
            'annual_salary' => 60000,
            'earnings' => [['label'=>'Regular Pay','amount'=>'2000.00']],
            'deductions' => [['label'=>''],['label'=>'']],
            'taxes' => [['label'=>'Federal Tax','amount'=>'200.00'],['label'=>'Social Security','amount'=>'124.00'],['label'=>'Medicare','amount'=>'29.00']],
            'email' => '',
            'template_key' => 'black',
            'check_number' => '',
        ];
    $periods = service(PeriodsService::class)->generate($old['pay_schedule'], (int)$old['stubs_count']);
    include __DIR__ . '/../Views/form-branded.php';
    }

    public function preview()
    {
        $payload = [
            'employer_name' => trim($_POST['employer_name'] ?? ''),
            'employer_address' => trim($_POST['employer_address'] ?? ''),
            'employee_name' => trim($_POST['employee_name'] ?? ''),
            'employee_address' => trim($_POST['employee_address'] ?? ''),
            'employee_ssn_last4' => trim($_POST['employee_ssn_last4'] ?? ''),
            'pay_schedule'  => $_POST['pay_schedule'] ?? 'biweekly',
            'stubs_count'   => (int)($_POST['stubs_count'] ?? 1),
            'pay_type'      => ($_POST['pay_type'] ?? 'hourly') === 'salary' ? 'salary' : 'hourly',
            'hourly_rate'   => (float)($_POST['hourly_rate'] ?? 25),
            'hours_per_period' => (float)($_POST['hours_per_period'] ?? 80),
            'annual_salary' => (float)($_POST['annual_salary'] ?? 60000),
            'email' => trim($_POST['email'] ?? ''),
            'earnings' => $this->collectLines('earnings'),
            'deductions' => $this->collectLines('deductions'),
            'taxes' => $this->collectLines('taxes'),
            'template_key' => $_POST['template_key'] ?? 'black',
            'check_number' => trim($_POST['check_number'] ?? ''),
            'auto_mode' => isset($_POST['auto_mode']) ? 1 : 0,
            'employee_state' => $_POST['employee_state'] ?? null,
        ];

        $errors = [];
        if ($payload['employer_name'] === '') { $errors[] = 'Employer name required'; }
        if ($payload['employee_name'] === '') { $errors[] = 'Employee name required'; }
    if ($payload['stubs_count'] < 1 || $payload['stubs_count'] > 12) { $errors[] = 'Stubs count 1-12'; }
    if ($payload['hourly_rate'] <= 0) { $errors[] = 'Hourly rate > 0'; }
    if ($payload['hours_per_period'] <= 0) { $errors[] = 'Hours per period > 0'; }
    if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email required'; }
    $templates = templates_config();
    if (!isset($templates[$payload['template_key']])) { $errors[] = 'Invalid template'; }

        // Validaciones adicionales
        if ($payload['pay_type'] === 'salary' && $payload['annual_salary'] <= 0) { $errors[] = 'Annual salary > 0'; }
        if ($payload['pay_type'] === 'hourly') {
            if ($payload['hourly_rate'] <= 0) { $errors[] = 'Hourly rate > 0'; }
            if ($payload['hours_per_period'] <= 0) { $errors[] = 'Hours per period > 0'; }
        }

        if ($errors) {
            $old = $payload;
            include __DIR__ . '/../Views/form-branded.php';
            return;
        }

    // Auto-cálculo: siempre que auto_mode esté activo se generan impuestos si faltan; earnings solo si vacío
    if ($payload['auto_mode']) {
        $auto = service(\App\Services\AutoCalcService::class)->compute($payload);
        // Auto returns per-stub arrays; for preview flatten to first stub for display
        if (empty($payload['earnings'])) {
            $payload['earnings'] = $auto['earnings'][0] ?? [];
        }
        if (empty($payload['taxes'])) {
            $payload['taxes'] = $auto['taxes'][0] ?? [];
        }
        if (empty($payload['deductions'])) { $payload['deductions'] = $auto['deductions'][0] ?? []; }
        // Also expose a machine-friendly 'items' payload that PdfService.normalizePayload can consume when generating PDFs
        $items = ['earnings'=>[], 'deductions'=>[], 'taxes'=>[]];
        foreach (($auto['earnings'] ?? []) as $stubIndex => $lines) {
            foreach ($lines as $line) {
                $items['earnings'][] = array_merge($line, ['stub_index'=>$stubIndex]);
            }
        }
        foreach (($auto['deductions'] ?? []) as $stubIndex => $lines) {
            foreach ($lines as $line) {
                $items['deductions'][] = array_merge($line, ['stub_index'=>$stubIndex]);
            }
        }
        foreach (($auto['taxes'] ?? []) as $stubIndex => $lines) {
            foreach ($lines as $line) {
                $items['taxes'][] = array_merge($line, ['stub_index'=>$stubIndex]);
            }
        }
        $payload['items'] = $items;
    }
        // Normalizar payload manual (cuando no viene de auto) para asegurar keys: hours, rate, current, ytd
        if (!$payload['auto_mode']) {
            // Earnings: if entries use 'amount' convert to expected shape
            foreach ($payload['earnings'] as $k => $e) {
                $amt = (float)($e['amount'] ?? ($e['current'] ?? 0));
                $payload['earnings'][$k]['hours'] = $payload['pay_type'] === 'hourly' ? round((float)($payload['hours_per_period'] ?? 0), 2) : null;
                $payload['earnings'][$k]['rate']  = $payload['pay_type'] === 'hourly' ? round((float)($payload['hourly_rate'] ?? 0), 4) : null;
                $payload['earnings'][$k]['current'] = round($amt, 2);
                $payload['earnings'][$k]['ytd'] = round($amt, 2); // no historical data in preview
            }

            // Deductions: map amount -> current/ytd
            foreach ($payload['deductions'] as $k => $d) {
                $amt = (float)($d['amount'] ?? ($d['current'] ?? 0));
                $payload['deductions'][$k]['current'] = round($amt,2);
                $payload['deductions'][$k]['ytd'] = round($amt,2);
                if (!isset($payload['deductions'][$k]['pretax'])) { $payload['deductions'][$k]['pretax'] = 0; }
            }

            // Taxes: map amount -> current/ytd
            foreach ($payload['taxes'] as $k => $t) {
                $amt = (float)($t['amount'] ?? ($t['current'] ?? 0));
                $payload['taxes'][$k]['current'] = round($amt,2);
                $payload['taxes'][$k]['ytd'] = round($amt,2);
            }
        }
    $pricingService = service(\App\Services\PricingService::class);
    $periods = service(PeriodsService::class)->generate($payload['pay_schedule'], $payload['stubs_count']);
    $priceEstimate = $pricingService->calculate($payload['stubs_count'], $payload['template_key']);
    $priceBreakdown = $pricingService->breakdown($payload['stubs_count'], $payload['template_key']);
    // Periodos personalizados enviados por el usuario (si coinciden con stubs_count)
    $customPeriods = $this->collectPeriods($payload['stubs_count']);
    if ($customPeriods) { $periods = $customPeriods; }
    // Auto-add earning if none provided and we have pay_type info
    if (empty($payload['earnings'])) {
        $grossPer = 0.0;
        if ($payload['pay_type']==='salary') {
            $map = ['weekly'=>52,'biweekly'=>26,'semi-monthly'=>24,'monthly'=>12];
            $periodsYear = $map[$payload['pay_schedule']] ?? 26;
            $grossPer = $payload['annual_salary'] / max(1,$periodsYear);
        } else {
            $grossPer = $payload['hourly_rate'] * $payload['hours_per_period'];
        }
        $payload['earnings'][] = ['label'=>'Regular Income','amount'=>round($grossPer,2)];
    }
        $watermark = true; // preview siempre con marca de agua
    include __DIR__ . '/../Views/preview.php';
    }

    private function collectLines(string $key): array
    {
        $labels = $_POST[$key]['label'] ?? [];
        $amounts = $_POST[$key]['amount'] ?? [];
        $result = [];
        foreach ($labels as $i=>$lbl) {
            $lbl = trim($lbl);
            $amt = isset($amounts[$i]) ? (float)$amounts[$i] : 0.0;
            if ($lbl === '' && $amt == 0.0) continue;
            $result[] = ['label'=>$lbl,'amount'=>$amt];
        }
        return $result;
    }

    private function collectPeriods(int $expected): array
    {
        $starts = $_POST['period_start'] ?? [];
        $ends   = $_POST['period_end'] ?? [];
        $pays   = $_POST['pay_date'] ?? [];
        if (!is_array($starts) || count($starts) !== $expected) return [];
        $out = [];
        for ($i=0; $i<$expected; $i++) {
            $s = trim((string)($starts[$i] ?? ''));
            $e = trim((string)($ends[$i] ?? ''));
            $p = trim((string)($pays[$i] ?? ''));
            if ($s === '' || $e === '' || $p === '') { return []; }
            $out[] = [
                'start_date' => $s,
                'end_date'   => $e,
                'pay_date'   => $p,
                'index'      => $i
            ];
        }
        return $out;
    }
}
