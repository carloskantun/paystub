<?php
namespace App\Controllers;

use App\Services\PeriodsService;
use App\Services\PricingService;
use App\Services\AutoCalcService;

class CreateController
{
    private function getStep(): int { return max(1, min(3, (int)($_GET['step'] ?? 1))); }

    public function get()
    {
        $step = $this->getStep();
        $state = $this->loadState();
        $errors = [];
        $pricing = service(PricingService::class);
    $price = $pricing->calculate($state['stubs_count'], $state['template_key'] ?? 'horizontal_blue');
    $breakdown = $pricing->breakdown($state['stubs_count'], $state['template_key'] ?? 'horizontal_blue');
        $periods = $this->ensurePeriods($state);
    include __DIR__.'/../Views/create/layout.php';
    }

    public function post()
    {
        $step = $this->getStep();
        $next = $step;
        $errors = [];
        $state = $this->loadState();
        $nav = $_POST['nav'] ?? 'next';

        if ($nav === 'prev' && $step > 1) {
            // Simple back navigation: do not validate current step, just go back.
            $next = $step - 1;
        } else {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Invalid session token';
            }

            if ($step === 1) {
            $state['employer_name'] = trim($_POST['employer_name'] ?? '');
            $state['employer_address'] = trim($_POST['employer_address'] ?? '');
            $state['employer_ein'] = trim($_POST['employer_ein'] ?? '');
            $state['employer_phone'] = trim($_POST['employer_phone'] ?? '');
            $state['employee_name'] = trim($_POST['employee_name'] ?? '');
            $state['employee_address'] = trim($_POST['employee_address'] ?? '');
            $state['employee_ssn_last4'] = trim($_POST['employee_ssn_last4'] ?? '');
            $state['employee_number'] = trim($_POST['employee_number'] ?? '');
            $state['employee_title'] = trim($_POST['employee_title'] ?? '');
            $state['employee_state'] = strtoupper(substr(trim($_POST['employee_state'] ?? ''),0,2));
            $state['buyer_email'] = trim($_POST['buyer_email'] ?? ($state['buyer_email'] ?? ''));
            $state['pay_type'] = ($_POST['pay_type'] ?? 'hourly') === 'salary' ? 'salary':'hourly';
            $state['hourly_rate'] = (float)($_POST['hourly_rate'] ?? 0);
            $state['hours_per_period'] = (float)($_POST['hours_per_period'] ?? 0);
            $state['annual_salary'] = (float)($_POST['annual_salary'] ?? 0);
            $state['pay_schedule'] = $_POST['pay_schedule'] ?? 'biweekly';
            $state['stubs_count'] = max(1, min(12,(int)($_POST['stubs_count'] ?? 1)));
            $state['pay_anchor'] = $_POST['pay_anchor'] ?? '';
            $state['template_key'] = $state['template_key'] ?? 'horizontal_blue';

            // Handle employer logo upload (optional)
            if (isset($_FILES['employer_logo']) && is_array($_FILES['employer_logo']) && ($_FILES['employer_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $err = (int)($_FILES['employer_logo']['error'] ?? UPLOAD_ERR_OK);
                if ($err === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['employer_logo']['tmp_name'] ?? '';
                    $size = (int)($_FILES['employer_logo']['size'] ?? 0);
                    if ($tmp && is_uploaded_file($tmp) && $size > 0 && $size <= 1024*1024) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($tmp) ?: '';
                        $allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/svg+xml'=>'svg'];
                        if (isset($allowed[$mime])) {
                            $ext = $allowed[$mime];
                            $base = 'logo-'.substr(session_id(),0,8).'-'.time().'.'.$ext;
                            $dir = __DIR__.'/../../storage/tmp';
                            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                            $dest = $dir.'/'.$base;
                            if (@move_uploaded_file($tmp, $dest)) {
                                $state['employer_logo_path'] = realpath($dest) ?: $dest;
                                // Prepare data URI for preview and PDF embedding
                                $data = @file_get_contents($dest);
                                if ($data !== false) {
                                    $state['employer_logo_data'] = 'data:'.$mime.';base64,'.base64_encode($data);
                                }
                            } else {
                                $errors[] = 'Could not save uploaded logo.';
                            }
                        } else {
                            $errors[] = 'Unsupported logo format. Use PNG, JPG or SVG.';
                        }
                    } else {
                        $errors[] = 'Logo too large (max 1MB) or invalid upload.';
                    }
                } else {
                    $errors[] = 'Upload error (code '.$err.').';
                }
            }

            if ($state['employer_name'] === '') $errors[] = 'Employer name required';
            if ($state['employee_name'] === '') $errors[] = 'Employee name required';
            if (!preg_match('/^\d{4}$/', $state['employee_ssn_last4'])) $errors[]='SSN last 4 required';
            if (!filter_var($state['buyer_email'], FILTER_VALIDATE_EMAIL)) $errors[]='Valid email required';
            if ($state['pay_type']==='hourly' && $state['hourly_rate'] <= 0) $errors[] = 'Hourly rate > 0';
            if ($state['pay_type']==='hourly' && $state['hours_per_period'] <= 0) $errors[] = 'Hours per period > 0';
            if ($state['pay_type']==='salary' && $state['annual_salary'] <= 0) $errors[] = 'Annual salary > 0';
            $this->storeState($state);
                if (!$errors) { $next = 2; }
            } elseif ($step === 2) {
            $state['template_key'] = $_POST['template_key'] ?? 'classic_black';
            if (!isset(templates_config()[$state['template_key']])) $errors[]='Invalid template';
            // Al avanzar a Step 3, ejecutar cálculo y persistir si no hay errores
            if (!$errors) {
                try {
                    $periods = $this->ensurePeriods($state);
                    $calc = service(\App\Services\CalculationOrchestrator::class)->computeBatch([
                        'year'=>(int)date('Y'),
                        'stubs_count'=>$state['stubs_count'] ?? count($periods),
                        'pay_schedule'=>$state['pay_schedule'] ?? 'biweekly',
                        'pay_type'=>$state['pay_type'] ?? 'hourly',
                        'hourly_rate'=>$state['hourly_rate'] ?? 0,
                        'hours_per_period'=>$state['hours_per_period'] ?? 0,
                        'annual_salary'=>$state['annual_salary'] ?? 0,
                        'employee_state'=>$state['employee_state'] ?? null,
                    ]);
                    $orderId = service(\App\Services\OrdersService::class)->createOrUpdateFromCalc($state, $calc);
                    $state['order_id'] = $orderId;
                } catch (\Throwable $e) {
                    $errors[] = 'Persist calc failed: '.$e->getMessage();
                }
            }
            $this->storeState($state);
            if (!$errors) { $next = 3; }
            } elseif ($step === 3) {
                $state['buyer_email'] = trim($_POST['buyer_email'] ?? '');
                $state['accept_terms'] = !empty($_POST['accept_terms']);
                if (!filter_var($state['buyer_email'], FILTER_VALIDATE_EMAIL)) $errors[]='Valid email required';
                if (!$state['accept_terms']) $errors[]='Accept terms';
                $this->storeState($state);
                if (!$errors) {
                    // Ensure order persisted (should exist from Step 2). Recreate from calc if missing.
                    if (empty($state['order_id'])) {
                        try {
                            $periods = $this->ensurePeriods($state);
                            $calc = service(\App\Services\CalculationOrchestrator::class)->computeBatch([
                                'year'=>(int)date('Y'),
                                'stubs_count'=>$state['stubs_count'] ?? count($periods),
                                'pay_schedule'=>$state['pay_schedule'] ?? 'biweekly',
                                'pay_type'=>$state['pay_type'] ?? 'hourly',
                                'hourly_rate'=>$state['hourly_rate'] ?? 0,
                                'hours_per_period'=>$state['hours_per_period'] ?? 0,
                                'annual_salary'=>$state['annual_salary'] ?? 0,
                                'employee_state'=>$state['employee_state'] ?? null,
                            ]);
                            $state['order_id'] = service(\App\Services\OrdersService::class)->createOrUpdateFromCalc($state, $calc);
                            $this->storeState($state);
                        } catch (\Throwable $e) {
                            $errors[] = 'Could not persist order: '.$e->getMessage();
                        }
                    }
                    if (!$errors) {
                        // Create payment session directly (Stripe for now)
                        try {
                            $pricing = service(PricingService::class);
                            $amount = $pricing->calculate($state['stubs_count'], $state['template_key'] ?? 'horizontal_blue');
                            $session = service(\App\Services\PaymentsService::class)->createSession('stripe', [
                                'order_id' => $state['order_id'],
                                'amount'   => $amount,
                                'currency' => 'usd',
                                'email'    => $state['buyer_email'],
                            ]);
                            if (!empty($session['redirect_url'])) {
                                header('Location: '.$session['redirect_url']);
                                exit;
                            }
                            if (!empty($session['error'])) {
                                $errors[] = 'Payment error: '.$session['error'];
                            } else {
                                $errors[] = 'Unexpected payment response';
                            }
                        } catch (\Throwable $e) {
                            $errors[] = 'Payment exception: '.$e->getMessage();
                        }
                    }
                }
            }
        }
    $step = $next; $pricing = service(PricingService::class); $price = $pricing->calculate($state['stubs_count'], $state['template_key'] ?? 'horizontal_blue'); $breakdown = $pricing->breakdown($state['stubs_count'], $state['template_key'] ?? 'horizontal_blue'); $periods = $this->ensurePeriods($state);
        include __DIR__.'/../Views/create/layout.php';
    }

    // GET /create/preview?template=classic_black
    public function preview()
    {
        $state = $this->loadState();
    $tpl = $_GET['template'] ?? ($state['template_key'] ?? 'horizontal_blue');
    if (!isset(templates_config()[$tpl])) { $tpl = 'horizontal_blue'; }
        try {
            $periods = $this->ensurePeriods($state);
            $calc = service(\App\Services\CalculationOrchestrator::class)->computeBatch([
                'year'=>(int)date('Y'),
                'stubs_count'=>$state['stubs_count'] ?? count($periods),
                'pay_schedule'=>$state['pay_schedule'] ?? 'biweekly',
                'pay_type'=>$state['pay_type'] ?? 'hourly',
                'hourly_rate'=>$state['hourly_rate'] ?? 0,
                'hours_per_period'=>$state['hours_per_period'] ?? 0,
                'annual_salary'=>$state['annual_salary'] ?? 0,
                'employee_state'=>$state['employee_state'] ?? null,
            ]);
            $payload = [
                'folio' => 'DRAFT-' . substr(md5(session_id()),0,6),
                'template_key' => $tpl,
                'periods' => array_map(fn($p)=>[
                    'period_start'=>$p['start_date']??'',
                    'period_end'=>$p['end_date']??'',
                    'pay_date'=>$p['pay_date']??'',
                    'pay_schedule'=>$state['pay_schedule'] ?? 'biweekly',
                ], $periods),
                'employer' => [
                    'name' => $state['employer_name'] ?? '',
                    'address' => array_filter(array_map('trim', explode("\n", $state['employer_address'] ?? ''))),
                    'ein' => $state['employer_ein'] ?? '',
                    'phone' => $state['employer_phone'] ?? '',
                ],
                'employee' => [
                    'name' => $state['employee_name'] ?? '',
                    'address' => array_filter(array_map('trim', explode("\n", $state['employee_address'] ?? ''))),
                    'ssn_last4' => $state['employee_ssn_last4'] ?? '',
                    'employee_number' => $state['employee_number'] ?? '',
                    'job_title' => $state['employee_title'] ?? '',
                ],
                'earnings' => $calc['earnings'],
                'deductions' => $calc['deductions'],
                'taxes' => $calc['taxes'],
                'summary' => $calc['summary'],
                'distribution' => array_map(fn($_)=>[], $periods),
                'branding' => [
                    'watermark' => 'PREVIEW ONLY – createpaystubdocs.com',
                    'logo' => $state['employer_logo_data'] ?? null,
                ],
                'calc_meta' => $calc['meta'] ?? [],
            ];
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
            $payload = $payload; // expose local
            include __DIR__.'/../Views/create/preview-frame.php';
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<div style="padding:.75rem;color:#ef4444;font-size:.7rem;">Preview error: '.htmlspecialchars($e->getMessage()).'</div>';
            exit;
        }
    }

    private function ensurePeriods(array &$state): array
    {
        if (!empty($_POST['period_start'])) {
            $starts = $_POST['period_start']; $ends = $_POST['period_end'] ?? []; $pays = $_POST['pay_date'] ?? [];
            $out=[]; for($i=0;$i<$state['stubs_count'];$i++){ $out[]=[ 'start_date'=>$starts[$i]??'', 'end_date'=>$ends[$i]??'', 'pay_date'=>$pays[$i]??'', 'index'=>$i]; }
            $state['periods']=$out; return $out;
        }
        if (!isset($state['periods']) || count($state['periods'])!==$state['stubs_count']) {
            $state['periods'] = service(PeriodsService::class)->generate($state['pay_schedule'] ?? 'biweekly', $state['stubs_count'] ?? 1);
        }
        return $state['periods'];
    }

    private function loadState(): array
    {
        if (session_status()!==PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['wizard_state'])) {
            $_SESSION['wizard_state'] = [
                'employer_name' => '', 'employer_address'=>'','employer_ein'=>'','employer_phone'=>'',
                'employee_name'=>'','employee_address'=>'','employee_ssn_last4'=>'','employee_number'=>'','employee_title'=>'',
                'pay_type'=>'hourly','hourly_rate'=>25,'hours_per_period'=>80,'annual_salary'=>60000,
                'pay_schedule'=>'biweekly','stubs_count'=>2,'template_key'=>'horizontal_blue','periods'=>[],
            ];
        }
        return $_SESSION['wizard_state'];
    }

    private function storeState(array $state): void
    {
        if (session_status()!==PHP_SESSION_ACTIVE) session_start();
        $_SESSION['wizard_state'] = $state;
    }
}
