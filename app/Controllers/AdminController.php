<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AdminService;
use App\Services\UserService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class AdminController extends BaseController
{
    private AdminService $adminService;
    private UserService $userService;

    public function __construct()
    {
        $this->adminService = new AdminService();
        $this->userService = new UserService();
    }

    /**
     * Admin Dashboard Route (/admin)
     */
    public function dashboard(): void
    {
        $dashboardData = $this->adminService->getDashboardData();

        $this->render('admin/index', array_merge($dashboardData, [
            'title' => 'Admin Mission Control | YasIX Store',
            'headerTitle' => 'Mission Control & Analytics Dashboard'
        ]), 'admin');
    }

    /**
     * Dedicated Admin Login Form (/admin/login)
     */
    public function loginForm(): void
    {
        Session::start();
        if (Session::has('user_id')) {
            $role = strtoupper((string)Session::get('role_name', ''));
            if ($role === 'ADMIN') {
                header('Location: ' . getenv('APP_URL') . '/admin');
                exit;
            }
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        View::render('admin/login', [
            'title' => 'Admin Clearance Portal - YasIX Store',
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'old' => Session::getFlash('old_input') ?? []
        ], null);
    }

    /**
     * Handle Admin Login Submission
     */
    public function login(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security clearance token expired or invalid.');
            header('Location: ' . getenv('APP_URL') . '/admin/login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = $this->userService->login($email, $password);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Session::setFlash('old_input', ['email' => $email]);
            header('Location: ' . getenv('APP_URL') . '/admin/login');
            exit;
        }

        $user = $result['user'];

        if (!$user->isAdmin()) {
            Session::setFlash('error', 'Access Denied: This portal requires verified ADMINISTRATOR credentials.');
            Session::setFlash('old_input', ['email' => $email]);
            header('Location: ' . getenv('APP_URL') . '/admin/login');
            exit;
        }

        Session::regenerate();
        Session::set('user_id', $user->id);
        Session::set('role_name', $user->role_name);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);

        Session::setFlash('success', 'Clearance verified. Welcome to YasIX Mission Control, ' . htmlspecialchars($user->name) . '.');
        header('Location: ' . getenv('APP_URL') . '/admin');
        exit;
    }

    /**
     * Admin Logout
     */
    public function logout(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin');
            exit;
        }

        Session::destroy();
        Session::start();
        Session::setFlash('success', 'Session terminated. Clearance revoked.');
        header('Location: ' . getenv('APP_URL') . '/admin/login');
        exit;
    }
}
