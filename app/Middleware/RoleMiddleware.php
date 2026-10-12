<?php

namespace App\Middleware;

use App\Helpers\Session;

class RoleMiddleware implements MiddlewareInterface
{
    public function handle(array $params = []): bool
    {
        Session::start();

        if (!Session::has('user_id')) {
            Session::setFlash('error', 'Please log in to access this section.');
            header('Location: ' . getenv('APP_URL') . '/login');
            exit;
        }

        $userRole = strtoupper(Session::get('role_name', ''));
        $allowedRoles = array_map('strtoupper', $params);

        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            Session::setFlash('error', 'Access Denied: You do not have permission to access this resource.');
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        return true;
    }
}
