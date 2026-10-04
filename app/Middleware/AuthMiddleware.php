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
            header('Location: ' . getenv('APP_URL') . '/login');
            exit;
        }
        return true;
    }
}
