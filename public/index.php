<?php

/**
 * YasIX Store - Front Controller Entrypoint
 * Architecture: Modular Monolith MVC
 */

declare(strict_types=1);

// Require Global Helper Functions (e(), formatCurrency())
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

// Load Web Routes
$routes = require dirname(__DIR__) . '/routes/web.php';

// Instantiate Router & Dispatch Request
$router = new \App\Router($routes);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
