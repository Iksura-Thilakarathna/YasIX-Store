<?php

/**
 * YasIX Store - Front Controller Entrypoint
 * Architecture: Modular Monolith MVC
 */

declare(strict_types=1);

// Require Global Helper Functions (e(), formatCurrency(), formatLKR(), csrf_field())
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

// PSR-4 Autoloader for App namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Require Sanitizer helper
require_once dirname(__DIR__) . '/app/Helpers/Sanitizer.php';

// Initialize Session
\App\Helpers\Session::start();

// Load Configuration
$config = require dirname(__DIR__) . '/config/config.php';

// Configure Error Reporting based on Environment
if (!($config['app']['debug'] ?? true)) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Global Exception Handler
set_exception_handler(function (\Throwable $e) use ($config): void {
    error_log((string)$e);
    if (!($config['app']['debug'] ?? true)) {
        http_response_code(500);
        $viewsDir = dirname(__DIR__) . '/views/';
        if (file_exists($viewsDir . '500.php')) {
            require $viewsDir . '500.php';
        } else {
            echo '<!DOCTYPE html><html><head><title>500 Internal Error</title></head><body style="background:#080a16;color:#fff;font-family:sans-serif;text-align:center;padding:50px;"><h1>500 - Internal Server Error</h1><p>A system anomaly occurred. Please try again later.</p></body></html>';
        }
        exit;
    }
    throw $e;
});

// Support development portfolio preview session if requested
if (($config['app']['env'] ?? 'development') === 'development' && isset($_GET['demo_view'])) {
    if ($_GET['demo_view'] === 'admin') {
        \App\Helpers\Session::set('user_id', 1000);
        \App\Helpers\Session::set('user_name', 'System Administrator');
        \App\Helpers\Session::set('user_email', 'admin@yasix.com');
        \App\Helpers\Session::set('role_id', 1);
        \App\Helpers\Session::set('role_name', 'ADMIN');
    } elseif ($_GET['demo_view'] === 'customer') {
        \App\Helpers\Session::set('user_id', 2);
        \App\Helpers\Session::set('user_name', 'Cyber Customer');
        \App\Helpers\Session::set('user_email', 'customer@yasix.local');
        \App\Helpers\Session::set('role_id', 2);
        \App\Helpers\Session::set('role_name', 'CUSTOMER');
    }
}

// Load Web Routes
$routes = require dirname(__DIR__) . '/routes/web.php';

// Instantiate Router & Dispatch Request
$router = new \App\Router($routes);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
