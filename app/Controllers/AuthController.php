<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class AuthController extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function loginForm(): void
    {
        Session::start();
        if (Session::has('user_id')) {
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        View::render('auth/login', [
            'title' => 'Login - YasIX Store',
            'errors' => Session::getFlash('errors_list') ?? [],
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'old' => Session::getFlash('old_input') ?? []
        ]);
    }

    public function login(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try submitting again.');
            header('Location: ' . getenv('APP_URL') . '/login');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->userService->login($email, $password);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Session::setFlash('old_input', ['email' => $email]);
            header('Location: ' . getenv('APP_URL') . '/login');
            exit;
        }

        $user = $result['user'];
        Session::regenerate();
        Session::set('user_id', $user->id);
        Session::set('role_name', $user->role_name);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);

        Session::setFlash('success', "Welcome back to the Matrix, " . htmlspecialchars($user->name) . "!");
        if (strtoupper($user->role_name ?? '') === 'ADMIN') {
            header('Location: ' . getenv('APP_URL') . '/admin');
            exit;
        }
        header('Location: ' . getenv('APP_URL') . '/account');
        exit;
    }

    public function registerForm(): void
    {
        Session::start();
        if (Session::has('user_id')) {
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        View::render('auth/register', [
            'title' => 'Create Account - YasIX Store',
            'errors' => Session::getFlash('errors_list') ?? [],
            'error' => Session::getFlash('error'),
            'old' => Session::getFlash('old_input') ?? []
        ]);
    }

    public function register(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try submitting again.');
            header('Location: ' . getenv('APP_URL') . '/register');
            exit;
        }

        $input = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];

        $result = $this->userService->register($input);

        if (!$result['success']) {
            Session::setFlash('errors_list', $result['errors']);
            Session::setFlash('old_input', [
                'full_name' => $input['full_name'],
                'email' => $input['email'],
                'phone' => $input['phone']
            ]);
            header('Location: ' . getenv('APP_URL') . '/register');
            exit;
        }

        Session::setFlash('success', 'Registration successful! Access granted. Please log in with your credentials.');
        header('Location: ' . getenv('APP_URL') . '/login');
        exit;
    }

    public function logout(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        Session::destroy();
        Session::start();
        Session::setFlash('success', 'You have been disconnected from the Matrix safely.');
        header('Location: ' . getenv('APP_URL') . '/login');
        exit;
    }
}
