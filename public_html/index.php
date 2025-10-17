<?php
// Punto de entrada HTTP.
require __DIR__ . '/../paystub/app/bootstrap.php';

use App\Controllers\FormController;
use App\Controllers\CreateController;
use App\Controllers\LandingController;
use App\Controllers\OrderController;
use App\Controllers\CheckoutController;
use App\Controllers\WebhookController;
use App\Controllers\PdfController;

$router = $GLOBALS['router'];

// Rutas Iteración 1 (mínimo viable): Formulario y Preview.
$router->map('GET', '/', [LandingController::class, 'show'], 'landing');
$router->map('GET', '/create', [CreateController::class, 'get'], 'wizard.get');
$router->map('GET', '/create/preview', [CreateController::class, 'preview'], 'wizard.preview');
$router->map('POST', '/create', [CreateController::class, 'post'], 'wizard.post');
// Legacy single-page form retained under alternate path
$router->map('GET', '/legacy-create', [FormController::class, 'showForm'], 'form.legacy');
$router->map('POST', '/preview', [FormController::class, 'preview'], 'preview');
$router->map('POST', '/order', [OrderController::class, 'create'], 'order.create');
$router->map('GET', '/order/[a:id]', [OrderController::class, 'show'], 'order.show');
$router->map('GET', '/order/[*:id]', [OrderController::class, 'show'], 'order.show.wild');
$router->map('POST', '/order/[a:id]/regenerate', [OrderController::class, 'regenerate'], 'order.regenerate');
$router->map('POST', '/checkout', [CheckoutController::class, 'createSession'], 'checkout.create');
$router->map('GET', '/order/[a:id]/status', [OrderController::class, 'status'], 'order.status');
$router->map('POST', '/webhook/stripe', [WebhookController::class, 'handle'], 'webhook.stripe');
$router->map('GET', '/pdf-zip/[*:token]', [PdfController::class, 'zip'], 'pdf.zip');
$router->map('GET', '/pdf/[*:token]', [PdfController::class, 'generate'], 'pdf.generate');

$match = $router->match();
// Simple access log for debugging routing / rewrites
if (isset($GLOBALS['logger'])) {
    $GLOBALS['logger']->info('HTTP '.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI']. ' match=' . json_encode($match ? ($match['name'] ?? 'unnamed') : null));
}
if ($match && is_array($match['target'])) {
    [$class, $method] = $match['target'];
    $controller = new $class();
    call_user_func_array([$controller, $method], $match['params']);
    exit;
}

// Manual fallback: if path starts with /order/UUID try to extract id and show
if (preg_match('#^/order/([0-9A-Za-z\-]{10,})#', $_SERVER['REQUEST_URI'], $m)) {
    if (isset($GLOBALS['logger'])) { $GLOBALS['logger']->warning('Manual order fallback for '.$m[1]); }
    $c = new OrderController();
    $c->show($m[1]);
    exit;
}

if (isset($GLOBALS['logger'])) { $GLOBALS['logger']->warning('404 Not Matched URI='.$_SERVER['REQUEST_URI']); }
header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
echo '404';
