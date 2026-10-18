<?php

namespace App\Middleware;

use App\Helpers\Session;

class RoleMiddleware implements MiddlewareInterface
{
    public function handle(array $params = []): bool
    {
        Session::start();

        if (!Session::has('user_id')) {
            $allowedRoles = array_map('strtoupper', $params);
            if (in_array('ADMIN', $allowedRoles, true)) {
                Session::setFlash('error', 'Administrator login required.');
                header('Location: ' . getenv('APP_URL') . '/admin/login');
            } else {
                Session::setFlash('error', 'Please log in to access this section.');
                header('Location: ' . getenv('APP_URL') . '/login');
            }
            exit;
        }

        $userRole = strtoupper(Session::get('role_name', ''));
        $allowedRoles = array_map('strtoupper', $params);

        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            $viewsDir = dirname(__DIR__, 2) . '/views/';
            $title = '403 Forbidden - Access Denied | YasIX Store';
            $error = 'Access Denied: You do not have the required administrative clearance.';
            if (file_exists($viewsDir . '403.php')) {
                require $viewsDir . 'layouts/header.php';
                require $viewsDir . '403.php';
                require $viewsDir . 'layouts/footer.php';
            } else {
                echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>Access Denied: Administrative privileges required.</p></body></html>";
            }
            exit;
        }

        return true;
    }
}
