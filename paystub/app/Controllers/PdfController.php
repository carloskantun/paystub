<?php
namespace App\Controllers;

use App\Services\PdfService;
use App\Services\OrdersService;

class PdfController
{
    public function generate(string $token)
    {
        $orderId = service(\App\Services\TokenService::class)->verify($token);
        if (!$orderId) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            echo 'Invalid or expired token';
            return;
        }
        $orders = service(OrdersService::class);
        $order = $orders->find($orderId);
        if (!$order || $order['status'] !== 'paid') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            echo 'Not available';
            return;
        }
        $items = $orders->fetchItems($orderId);
        $pdfPath = service(PdfService::class)->render($order['template_key'], [
            'order' => $order,
            'periods' => service(\App\Services\PeriodsService::class)->generate($order['pay_schedule'], (int)$order['count_stubs']),
            'items' => $items,
        ], false, $orderId);
        service(\App\Services\AuditService::class)->log('download', $orderId, []);
        if (!is_file($pdfPath)) {
            echo 'File not generated';
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="paystub-' . $orderId . '.pdf"');
        readfile($pdfPath);
    }

    public function zip(string $token)
    {
        $orderId = service(\App\Services\TokenService::class)->verify($token);
        if (!$orderId) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            echo 'Invalid or expired token';
            return;
        }
        $orders = service(OrdersService::class);
        $order = $orders->find($orderId);
        if (!$order || $order['status'] !== 'paid') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            echo 'Not available';
            return;
        }
        $dir = __DIR__ . '/../../storage/paystubs/' . $orderId;
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        // Generate individual PDFs if not present
        $periods = service(\App\Services\PeriodsService::class)->generate($order['pay_schedule'], (int)$order['count_stubs']);
        $items = $orders->fetchItems($orderId);
        $paths = [];
        for ($i=0;$i<count($periods);$i++) {
            $path = $dir . '/stub-' . ($i+1) . '.pdf';
            if (!is_file($path)) {
                $htmlPath = service(PdfService::class)->render($order['template_key'], [
                    'order' => $order,
                    'periods' => [$periods[$i]],
                    'items' => $items,
                    'stub_index' => 0,
                ], false, $orderId);
                // render() writes stub.pdf; copy for per-page if needed
                if (is_file($htmlPath) && $htmlPath !== $path) { @copy($htmlPath, $path); }
            }
            if (is_file($path)) { $paths[] = $path; }
        }
        if (!$paths) { header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); echo 'No files'; return; }
        $zipPath = $dir . '/bundle.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE|\ZipArchive::OVERWRITE) !== true) { echo 'zip error'; return; }
        foreach ($paths as $p){ $zip->addFile($p, basename($p)); }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="paystubs-'.$orderId.'.zip"');
        readfile($zipPath);
    }
}
