<?php

namespace App\Middleware;

use App\Helpers\Session;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(array $params = []): bool
    {
        Session::start();
        if (!Session::has('user_id')) {
            Session::setFlash('error', 'Please log in to access this page.');
            $returnUrl = $_SERVER['REQUEST_URI'] ?? '/';
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !str_starts_with($returnUrl, '/login') && !str_starts_with($returnUrl, '/logout')) {
                Session::set('return_url', $returnUrl);
            }
            $base = rtrim((string)(getenv('APP_URL') ?: ''), '/');
            header('Location: ' . ($base ? $base . '/login' : '/login'));
            exit;
        }
        return true;
    }
}
