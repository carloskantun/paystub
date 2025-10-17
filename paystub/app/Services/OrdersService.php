<?php
namespace App\Services;

use Ramsey\Uuid\Uuid;
use App\Services\PeriodsService;
use PDO;

class OrdersService
{
    /**
     * Crea una orden en estado draft y devuelve UUID.
     * $data requiere: email, employer_name, employee_name, pay_schedule, stubs_count, template_key
     */
    public function save(array $data): string
    {
        $id = Uuid::uuid4()->toString();

        $periods = service(PeriodsService::class)->generate($data['pay_schedule'], (int)$data['stubs_count']);
        $periodStart = $periods ? min(array_column($periods, 'start_date')) : null;
        $periodEnd   = $periods ? max(array_column($periods, 'end_date')) : null;

        $pdo = db();
        $stmt = $pdo->prepare("INSERT INTO orders (id, tenant_id, email, status, template_key, pay_schedule, count_stubs, bundle_mode, period_start, period_end, employee_json, employer_json, created_at) VALUES (:id, :tenant_id, :email, 'draft', :template_key, :pay_schedule, :count_stubs, 'separate', :period_start, :period_end, :employee_json, :employer_json, NOW())");
        // Try to enrich from request/session if available
        $empAddr = isset($data['employer_address']) ? array_values(array_filter(array_map('trim', explode("\n", (string)$data['employer_address'])))) : [];
        $eeAddr  = isset($data['employee_address']) ? array_values(array_filter(array_map('trim', explode("\n", (string)$data['employee_address'])))) : [];
        if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
        $logoData = $_SESSION['wizard_state']['employer_logo_data'] ?? null;
        $logoPath = $_SESSION['wizard_state']['employer_logo_path'] ?? null;
        $stmt->execute([
            ':id' => $id,
            ':tenant_id' => null,
            ':email' => $data['email'],
            ':template_key' => $data['template_key'],
            ':pay_schedule' => $data['pay_schedule'],
            ':count_stubs' => (int)$data['stubs_count'],
            ':period_start' => $periodStart,
            ':period_end' => $periodEnd,
            ':employee_json' => json_encode([
                'name' => $data['employee_name'],
                'address' => $eeAddr,
                'ssn_last4' => $data['employee_ssn_last4'] ?? null,
                'employee_number' => $data['employee_number'] ?? null,
                'job_title' => $data['employee_title'] ?? null,
            ]),
            ':employer_json' => json_encode([
                'name' => $data['employer_name'],
                'address' => $empAddr,
                'ein' => $data['employer_ein'] ?? null,
                'phone' => $data['employer_phone'] ?? null,
                'logo_data' => $logoData,
                'logo_path' => $logoPath,
            ]),
        ]);

        service(\App\Services\AuditService::class)->log('create', $id, ['count' => (int)$data['stubs_count']]);

        // Si vienen líneas de items personalizados, persistirlas; si no, seed básico.
        $hasCustom = !empty($data['earnings']) || !empty($data['deductions']) || !empty($data['taxes']);
        if ($hasCustom) {
            // Pass the full state so persistItems can compute fit_taxable_wages when possible
            $this->persistItems($id, (int)$data['stubs_count'], $data['earnings'] ?? [], $data['deductions'] ?? [], $data['taxes'] ?? [], $data);
        } else {
            $this->seedStubData($id, $data);
        }

        return $id;
    }

    public function find(string $id): ?array
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function setStatus(string $id, string $status): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function fetchItems(string $orderId): array
    {
        $pdo = db();
        $items = ['earnings'=>[], 'deductions'=>[], 'taxes'=>[]];
        foreach (['earnings','deductions','taxes'] as $table) {
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE order_id = :id ORDER BY stub_index, sort_order, label");
            $stmt->execute([':id'=>$orderId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) { $items[$table][] = $r; }
        }
        return $items;
    }

    public function regenerate(string $id): ?string
    {
        $original = $this->find($id);
        if (!$original) { return null; }
        $newId = Uuid::uuid4()->toString();
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO orders (id, tenant_id, email, status, template_key, pay_schedule, count_stubs, bundle_mode, period_start, period_end, employee_json, employer_json, version_of, created_at) VALUES (:id, :tenant_id, :email, "draft", :template_key, :pay_schedule, :count_stubs, :bundle_mode, :period_start, :period_end, :employee_json, :employer_json, :version_of, NOW())');
        $stmt->execute([
            ':id' => $newId,
            ':tenant_id' => $original['tenant_id'],
            ':email' => $original['email'],
            ':template_key' => $original['template_key'],
            ':pay_schedule' => $original['pay_schedule'],
            ':count_stubs' => $original['count_stubs'],
            ':bundle_mode' => $original['bundle_mode'],
            ':period_start' => $original['period_start'],
            ':period_end' => $original['period_end'],
            ':employee_json' => $original['employee_json'],
            ':employer_json' => $original['employer_json'],
            ':version_of' => $original['id'],
        ]);
        service(\App\Services\AuditService::class)->log('regenerate', $newId, ['version_of' => $original['id']]);
        return $newId;
    }

    private function seedStubData(string $orderId, array $data): void
    {
        $pdo = db();
        $rate = isset($data['hourly_rate']) ? (float)$data['hourly_rate'] : 25.0;
        $hours = isset($data['hours_per_period']) ? (float)$data['hours_per_period'] : 80.0;
        $count = (int)$data['stubs_count'];
        $grossPer = $rate * $hours;
        $federalPct = 0.10; $ssPct = 0.062; $mediPct = 0.0145;
        for ($i=0;$i<$count;$i++) {
            $gross = $grossPer;
            $federal = round($gross * $federalPct,2); $ss = round($gross*$ssPct,2); $med = round($gross*$mediPct,2);
            $net = $gross - ($federal+$ss+$med);
            $pdo->prepare('INSERT INTO earnings (order_id, stub_index, label, hours, rate, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:h,:r,:c,:y,0,:origin)')->execute([':o'=>$orderId,':i'=>$i,':l'=>'Regular',':h'=>$hours,':r'=>$rate,':c'=>$gross,':y'=>$gross*($i+1),':origin'=>'seed']);
            foreach ([['Federal Income Tax',$federal],['Social Security Employee Tax',$ss],['Employee Medicare',$med]] as $idx=>$t) {
                $pdo->prepare('INSERT INTO taxes (order_id, stub_index, label, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:c,:y,:s,:origin)')->execute([':o'=>$orderId,':i'=>$i,':l'=>$t[0],':c'=>$t[1],':y'=>$t[1]*($i+1),':s'=>$idx,':origin'=>'seed']);
            }
            // compute a simple fit_taxable_wages per stub using AutoCalcService logic if available
            $ftw = 0.0;
            try {
                $auto = service(\App\Services\AutoCalcService::class);
                $calc = $auto->compute(['pay_schedule'=>$data['pay_schedule'] ?? 'biweekly','stubs_count'=>$count,'pay_type'=>$data['pay_type'] ?? 'hourly','hours_per_period'=>$hours,'hourly_rate'=>$rate,'employee_state'=>$data['employee_state'] ?? null]);
                $ftw = $calc['summary'][0]['fit_taxable_wages'] ?? 0.0;
            } catch (\Throwable $e) { $ftw = 0.0; }
            $pdo->prepare('UPDATE orders SET gross=:g, net=:n, taxes_total=:tt, fit_taxable_wages=:ftw WHERE id=:id')->execute([':g'=>$gross,':n'=>$net,':tt'=>$federal+$ss+$med,':ftw'=>$ftw,':id'=>$orderId]);
        }
    }

    private function persistItems(string $orderId, int $stubs, array $earnings, array $deductions, array $taxes, array $state = []): void
    {
        $pdo = db();
        $grossPer=0;$dedPer=0;$taxPer=0;
        foreach ($earnings as $e) { $grossPer += (float)($e['amount'] ?? $e['current'] ?? 0); }
        foreach ($deductions as $d) { $dedPer += (float)($d['amount'] ?? $d['current'] ?? 0); }
        foreach ($taxes as $t) { $taxPer += (float)($t['amount'] ?? $t['current'] ?? 0); }
        $netPer = $grossPer - $dedPer - $taxPer;
        for ($i=0;$i<$stubs;$i++) {
            $y = $i+1;
            foreach ($earnings as $idx=>$e) {
                if (($e['label'] ?? '')==='') continue;
                $hours = $e['hours'] ?? null;
                $rate = $e['rate'] ?? null;
                $current = (float)($e['amount'] ?? $e['current'] ?? 0);
                $ytd = (float)($e['ytd'] ?? ($current * $y));
                $pdo->prepare('INSERT INTO earnings (order_id, stub_index, label, hours, rate, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:h,:r,:c,:y,:s,:origin)')->execute([':o'=>$orderId,':i'=>$i,':l'=>$e['label'],':h'=>$hours,':r'=>$rate,':c'=>$current,':y'=>$ytd,':s'=>$idx,':origin'=>$e['origin'] ?? 'manual']);
            }
            foreach ($deductions as $idx=>$d) {
                if (($d['label'] ?? '')==='') continue;
                $current = (float)($d['amount'] ?? $d['current'] ?? 0);
                $ytd = (float)($d['ytd'] ?? ($current * $y));
                $pretax = (int)($d['pretax'] ?? 0);
                $pdo->prepare('INSERT INTO deductions (order_id, stub_index, label, pretax, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:p,:c,:y,:s,:origin)')->execute([':o'=>$orderId,':i'=>$i,':l'=>$d['label'],':p'=>$pretax,':c'=>$current,':y'=>$ytd,':s'=>$idx,':origin'=>$d['origin'] ?? 'manual']);
            }
            foreach ($taxes as $idx=>$t) {
                if (($t['label'] ?? '')==='') continue;
                $current = (float)($t['amount'] ?? $t['current'] ?? 0);
                $ytd = (float)($t['ytd'] ?? ($current * $y));
                $sort = (int)($t['sort_order'] ?? $idx);
                $pdo->prepare('INSERT INTO taxes (order_id, stub_index, label, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:c,:y,:s,:origin)')->execute([':o'=>$orderId,':i'=>$i,':l'=>$t['label'],':c'=>$current,':y'=>$ytd,':s'=>$sort,':origin'=>$t['origin'] ?? 'manual']);
            }
        }
        // Try to compute and persist fit_taxable_wages when state is provided
        $ftw = 0.0;
        try {
            if (!empty($state)) {
                $auto = service(\App\Services\AutoCalcService::class);
                $calc = $auto->compute(array_merge($state, ['stubs_count'=>$stubs]));
                $ftw = $calc['summary'][0]['fit_taxable_wages'] ?? 0.0;
            }
        } catch (\Throwable $e) { $ftw = 0.0; }

        $pdo->prepare('UPDATE orders SET gross=:g, deductions_total=:d, taxes_total=:t, net=:n, fit_taxable_wages=:ftw WHERE id=:id')->execute([':g'=>$grossPer,':d'=>$dedPer,':t'=>$taxPer,':n'=>$netPer,':ftw'=>$ftw,':id'=>$orderId]);
    }

    /**
     * createOrUpdateFromCalc
     * Persiste resultados del CalculationOrchestrator (origin=calc) al pasar a Step 3.
     * @param array $state Wizard state (requiere employer/employee/pay data)
     * @param array $calc  Resultado computeBatch (earnings/taxes/deductions/summary)
     */
    public function createOrUpdateFromCalc(array $state, array $calc): string
    {
        $pdo = db();
        $orderId = $state['order_id'] ?? null;

        // Generar periodos coherentes para period_start / period_end
        $periods = service(PeriodsService::class)->generate($state['pay_schedule'] ?? 'biweekly', (int)($state['stubs_count'] ?? 1));
        $periodStart = $periods ? min(array_column($periods, 'start_date')) : null;
        $periodEnd   = $periods ? max(array_column($periods, 'end_date')) : null;

        if ($orderId) {
            // Si existe, limpiar líneas anteriores
            $exists = $this->find($orderId);
            if ($exists) {
                foreach(['earnings','deductions','taxes'] as $tbl){ $pdo->prepare("DELETE FROM {$tbl} WHERE order_id=:id")->execute([':id'=>$orderId]); }
            } else {
                $orderId = null; // se regenerará
            }
        }
        if (!$orderId) {
            $orderId = Uuid::uuid4()->toString();
            $pdo->prepare('INSERT INTO orders (id, tenant_id, email, status, template_key, pay_schedule, count_stubs, bundle_mode, period_start, period_end, employee_json, employer_json, created_at) VALUES (:id,NULL,:email,\'draft\',:tpl,:sched,:count,\'separate\',:ps,:pe,:ejson,:mjson,NOW())')
                ->execute([
                    ':id'=>$orderId,
                    ':email'=>$state['buyer_email'] ?? '',
                    ':tpl'=>$state['template_key'] ?? 'horizontal_blue',
                    ':sched'=>$state['pay_schedule'] ?? 'biweekly',
                    ':count'=>(int)($state['stubs_count'] ?? 1),
                    ':ps'=>$periodStart,
                    ':pe'=>$periodEnd,
                    ':ejson'=>json_encode([
                        'name'=>$state['employee_name'] ?? '',
                        'address'=>array_values(array_filter(array_map('trim', explode("\n", $state['employee_address'] ?? '')))),
                        'ssn_last4'=>$state['employee_ssn_last4'] ?? '',
                        'employee_number'=>$state['employee_number'] ?? '',
                        'job_title'=>$state['employee_title'] ?? '',
                    ]),
                    ':mjson'=>json_encode([
                        'name'=>$state['employer_name'] ?? '',
                        'address'=>array_values(array_filter(array_map('trim', explode("\n", $state['employer_address'] ?? '')))),
                        'ein'=>$state['employer_ein'] ?? '',
                        'phone'=>$state['employer_phone'] ?? '',
                        'logo_data'=>$state['employer_logo_data'] ?? null,
                        'logo_path'=>$state['employer_logo_path'] ?? null,
                    ]),
                ]);
            service(\App\Services\AuditService::class)->log('create', $orderId, ['mode'=>'calc']);
        }

        $stubs = (int)($state['stubs_count'] ?? 1);
        // Insert earnings lines
        foreach ($calc['earnings'] as $i=>$lines) {
            foreach ($lines as $idx=>$line) {
                $pdo->prepare('INSERT INTO earnings (order_id, stub_index, label, hours, rate, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:h,:r,:c,:y,:s,\'calc\')')
                    ->execute([
                        ':o'=>$orderId, ':i'=>$i, ':l'=>$line['label'] ?? 'Line', ':h'=>$line['hours'] ?? null, ':r'=>$line['rate'] ?? null,
                        ':c'=>$line['current'] ?? 0, ':y'=>$line['ytd'] ?? ($line['current']??0), ':s'=>$idx
                    ]);
            }
        }
        foreach ($calc['deductions'] as $i=>$lines) {
            foreach ($lines as $idx=>$line) {
                $pdo->prepare('INSERT INTO deductions (order_id, stub_index, label, pretax, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,0,:c,:y,:s,\'calc\')')
                    ->execute([
                        ':o'=>$orderId, ':i'=>$i, ':l'=>$line['label'] ?? 'Deduction', ':c'=>$line['current'] ?? 0, ':y'=>$line['ytd'] ?? ($line['current']??0), ':s'=>$idx
                    ]);
            }
        }
        foreach ($calc['taxes'] as $i=>$lines) {
            foreach ($lines as $idx=>$line) {
                $pdo->prepare('INSERT INTO taxes (order_id, stub_index, label, current_amount, ytd_amount, sort_order, origin) VALUES (:o,:i,:l,:c,:y,:s,\'calc\')')
                    ->execute([
                        ':o'=>$orderId, ':i'=>$i, ':l'=>$line['label'] ?? 'Tax', ':c'=>$line['current'] ?? 0, ':y'=>$line['ytd'] ?? ($line['current']??0), ':s'=>$idx
                    ]);
            }
        }

        // Totales (usar primer summary como base y net promedio si varios)
        $firstSummary = $calc['summary'][0] ?? ['gross'=>0,'taxes_total'=>0,'deductions_total'=>0,'net'=>0,'fit_taxable_wages'=>0];
        $pdo->prepare('UPDATE orders SET gross=:g, taxes_total=:t, deductions_total=:d, net=:n, fit_taxable_wages=:ftw WHERE id=:id')
            ->execute([
                ':g'=>$firstSummary['gross'] ?? 0,
                ':t'=>$firstSummary['taxes_total'] ?? 0,
                ':d'=>$firstSummary['deductions_total'] ?? 0,
                ':n'=>$firstSummary['net'] ?? 0,
                ':ftw'=>$firstSummary['fit_taxable_wages'] ?? 0,
                ':id'=>$orderId
            ]);
        return $orderId;
    }
}
