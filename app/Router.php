<?php

namespace App;

use App\Controllers\ErrorController;
use Throwable;

class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Normalize Windows backslashes and base directory paths
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        }

        if (empty($path) || $path === '/index.php') {
            $path = '/';
        }

        $method = strtoupper($method);

        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            $controllerClass = $handler[0];
            $action = $handler[1];

            try {
                $controller = new $controllerClass();
                $controller->$action();
                return;
            } catch (Throwable $e) {
                if (filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
                    throw $e;
                }
                (new ErrorController())->notFound();
                return;
            }
        }

        (new ErrorController())->notFound();
    }
}
