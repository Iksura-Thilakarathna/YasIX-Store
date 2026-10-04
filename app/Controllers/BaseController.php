<?php

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;

abstract class BaseController
{
    protected function render(string $viewPath, array $data = [], ?string $layout = 'main'): void
    {
        View::render($viewPath, $data, $layout);
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
