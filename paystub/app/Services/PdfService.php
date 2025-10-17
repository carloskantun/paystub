<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    public function render(string $templateKey, array $data, bool $watermark = false, ?string $orderId = null): string
    {
    $templates = templates_config();
    if (!isset($templates[$templateKey])) { $templateKey = 'horizontal_blue'; }
    $tplConf = $templates[$templateKey];
    $file = __DIR__ . '/../Views/' . ($tplConf['pdf_view'] ?? 'pdf-layout-classic-black.php');
        // Normalizar datos para que todas las plantillas funcionen (soporta $items o arreglos por índice)
        $data = $this->normalizePayload($data);
    // Merge top-level convenience keys
    $data['employer'] = $data['employer'] ?? (isset($data['order']['employer_json']) ? json_decode($data['order']['employer_json'], true) : []);
    $data['employee'] = $data['employee'] ?? (isset($data['order']['employee_json']) ? json_decode($data['order']['employee_json'], true) : []);
    // Branding passthrough (logo, colors)
    $data['branding'] = $data['branding'] ?? [];
    // Si vienen múltiples períodos generar múltiples páginas.
        $periods = $data['periods'] ?? [null];
        $pages = '';
        $index = 0;
        foreach ($periods as $period) {
            $stubData = $data;
            $stubData['period'] = $period;
            $stubData['stub_index'] = $index;
            // Inject tokens and layout so the view can style accordingly
            $pages .= $this->buildHtmlWithTokens($file, $stubData, $tplConf['tokens'] ?? [], $tplConf['layout'] ?? [], $watermark, $index > 0);
            $index++;
        }
    $html = '<html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;">' . $pages . '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dir = __DIR__ . '/../../storage/paystubs';
        if ($orderId) { $dir .= '/' . $orderId; }
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        $path = $dir . '/stub.pdf';
        if (!$watermark && is_file($path) && (time() - filemtime($path) < 30)) {
            return realpath($path) ?: $path;
        }
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter');
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents($path, $output);
        return realpath($path) ?: $path;
    }

    private function buildHtmlWithTokens(string $file, array $data, array $tokens, array $layout, bool $watermark, bool $pageBreak): string
    {
        ob_start();
        $payload = $data; // Exponer como $payload dentro de la plantilla
        // Exponer tokens y layout para la vista
        $tokens = $tokens;
        $layout = $layout;
        include $file;
        $content = ob_get_clean();
        if ($watermark) {
            $wm = '<div style="position:fixed;top:40%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:90px;color:rgba(0,0,0,.05);font-weight:700;letter-spacing:12px;">PREVIEW</div>';
            $content = $wm . $content;
        }
        if ($pageBreak) {
            $content = '<div style="page-break-before:always"></div>' . $content;
        }
        return $content;
    }

    private function normalizePayload(array $data): array
    {
        // Garantizar periods y count
        $periods = $data['periods'] ?? [];
        $stubCount = is_array($periods) ? count($periods) : 1;

        // Si ya vienen earnings/deductions/taxes/summary, no hacer nada
        $hasArrays = isset($data['earnings'], $data['deductions'], $data['taxes']);
        $hasItems = isset($data['items']) && is_array($data['items']);

        if (!$hasArrays && $hasItems) {
            $earnings = array_fill(0, max(1,$stubCount), []);
            $deductions = array_fill(0, max(1,$stubCount), []);
            $taxes = array_fill(0, max(1,$stubCount), []);
            $summary = array_fill(0, max(1,$stubCount), null);

            $sumGross = array_fill(0, max(1,$stubCount), 0.0);
            $sumTax = array_fill(0, max(1,$stubCount), 0.0);
            $sumDed = array_fill(0, max(1,$stubCount), 0.0);

            foreach (($data['items']['earnings'] ?? []) as $e) {
                $i = (int)($e['stub_index'] ?? 0);
                $earnings[$i][] = [
                    'label' => $e['label'] ?? 'Earning',
                    'hours' => isset($e['hours']) ? round((float)$e['hours'],2) : null,
                    'rate'  => isset($e['rate']) ? round((float)$e['rate'],4) : null,
                    'current' => round((float)($e['current_amount'] ?? 0),2),
                    'ytd'     => round((float)($e['ytd_amount'] ?? 0),2),
                ];
                $sumGross[$i] += (float)($e['current_amount'] ?? 0);
            }
            foreach (($data['items']['taxes'] ?? []) as $t) {
                $i = (int)($t['stub_index'] ?? 0);
                $taxes[$i][] = [
                    'label' => $t['label'] ?? 'Tax',
                    'current' => round((float)($t['current_amount'] ?? 0),2),
                    'ytd'     => round((float)($t['ytd_amount'] ?? 0),2),
                ];
                $sumTax[$i] += (float)($t['current_amount'] ?? 0);
            }
            foreach (($data['items']['deductions'] ?? []) as $d) {
                $i = (int)($d['stub_index'] ?? 0);
                $deductions[$i][] = [
                    'label' => $d['label'] ?? 'Deduction',
                    'current' => round((float)($d['current_amount'] ?? 0),2),
                    'ytd'     => round((float)($d['ytd_amount'] ?? 0),2),
                ];
                $sumDed[$i] += (float)($d['current_amount'] ?? 0);
            }
            for ($i=0;$i<count($summary);$i++) {
                // Prefer any precomputed fit_taxable_wages from order or passed summary; fallback to 0
                $ftw = 0.0;
                if (!empty($data['order']['fit_taxable_wages'])) { $ftw = (float)$data['order']['fit_taxable_wages']; }
                elseif (isset($data['summary'][$i]['fit_taxable_wages'])) { $ftw = (float)$data['summary'][$i]['fit_taxable_wages']; }

                $summary[$i] = [
                    'gross' => round($sumGross[$i],2),
                    'fit_taxable_wages' => round($ftw,2),
                    'taxes_total' => round($sumTax[$i],2),
                    'deductions_total' => round($sumDed[$i],2),
                    'net' => round($sumGross[$i] - $sumTax[$i] - $sumDed[$i],2),
                ];
            }
            $data['earnings'] = $earnings;
            $data['deductions'] = $deductions;
            $data['taxes'] = $taxes;
            $data['summary'] = $summary;
        }
        return $data;
    }
}
