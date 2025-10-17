<?php
// bootstrap.php
// Carga de dependencias, entorno, router y helpers básicos.

use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

// Autoload (si alguien incluye bootstrap directo sin haber requerido autoload previamente)
if (!class_exists(Dotenv::class)) {
    $autoloadCandidate = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadCandidate)) {
        require $autoloadCandidate;
    }
}

// Localiza archivo .env (permitimos ubicación en carpeta actual o padre)
$envDirCandidates = [dirname(__DIR__), dirname(__DIR__, 2), getcwd()];
foreach ($envDirCandidates as $dir) {
    if ($dir && file_exists($dir . '/.env')) {
        Dotenv::createImmutable($dir)->load();
        break;
    }
}

// Logger simple global
if (!isset($GLOBALS['logger'])) {
    $logPath = sys_get_temp_dir() . '/paystub.log';
    $logger = new Logger('paystub');
    $logger->pushHandler(new StreamHandler($logPath, Level::Debug));
    $GLOBALS['logger'] = $logger;
}

// Función helper para obtener variable de entorno con default
if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

// Overlay de claves Stripe por modo (test/live) para no editar variables base
if (!function_exists('env_set')) {
    function env_set(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        @putenv($key . '=' . $value);
    }
}

$stripeMode = env('STRIPE_MODE'); // valores esperados: 'test' o 'live'
if ($stripeMode) {
    $U = strtoupper($stripeMode);
    $sec = env('STRIPE_' . $U . '_SECRET');
    $pk  = env('STRIPE_' . $U . '_PK');
    $wh  = env('STRIPE_' . $U . '_WEBHOOK_SECRET');
    if ($sec) { env_set('STRIPE_SECRET', $sec); }
    if ($pk)  { env_set('STRIPE_PK', $pk); }
    if ($wh)  { env_set('STRIPE_WEBHOOK_SECRET', $wh); }
}

// Cargar configuración de plantillas una sola vez
if (!function_exists('templates_config')) {
    function templates_config(): array
    {
        static $cache;
        if ($cache === null) {
            $file = __DIR__ . '/Config/templates.php';
            $cache = file_exists($file) ? require $file : [];
        }
        return $cache;
    }
}

// Instanciar router si no existe
if (!isset($GLOBALS['router'])) {
    $GLOBALS['router'] = new AltoRouter();

    // Base path configurable (por si el sitio vive en subcarpeta)
    $basePath = env('APP_BASE_PATH', '');
    if ($basePath) {
        $GLOBALS['router']->setBasePath($basePath);
    }
}

// Sencillo contenedor perezoso (para servicios) sin framework
if (!function_exists('service')) {
    function service(string $class)
    {
        static $instances = [];
        if (!isset($instances[$class])) {
            $instances[$class] = new $class();
        }
        return $instances[$class];
    }
}

// CSRF token muy simple (session-based). Si no hay sesión la inicia.
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}

// Conexión PDO perezosa
if (!function_exists('db')) {
    function db(): PDO
    {
        static $pdo;
        if (!$pdo) {
            $dsn = env('DB_DSN');
            if (!$dsn) {
                $host = env('DB_HOST', '127.0.0.1');
                $name = env('DB_NAME', 'paystub');
                $charset = 'utf8mb4';
                $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
            }
            $user = env('DB_USER', 'root');
            $pass = env('DB_PASS', '');
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $pdo = new PDO($dsn, $user, $pass, $options);
        }
        return $pdo;
    }
}
