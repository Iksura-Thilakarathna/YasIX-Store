<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class AccountController extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');
        $user = $this->userService->getUserById($userId);

        if (!$user) {
            Session::destroy();
            Session::start();
            Session::setFlash('error', 'User session invalid. Please log in again.');
            header('Location: ' . getenv('APP_URL') . '/login');
            exit;
        }

        View::render('account/index', [
            'title' => 'Customer Account Dashboard - YasIX Store',
            'user' => $user,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'profile_errors' => Session::getFlash('profile_errors') ?? [],
            'password_errors' => Session::getFlash('password_errors') ?? []
        ]);
    }

    public function updateProfile(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try submitting again.');
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        $userId = (int)Session::get('user_id');
        $input = [
            'full_name' => $_POST['full_name'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ];

        $result = $this->userService->updateProfile($userId, $input);

        if (!$result['success']) {
            Session::setFlash('profile_errors', $result['errors']);
            Session::setFlash('error', 'Please correct the highlighted errors in your profile.');
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        Session::set('user_name', trim($input['full_name']));
        Session::setFlash('success', 'Profile details updated successfully!');
        header('Location: ' . getenv('APP_URL') . '/account');
        exit;
    }

    public function changePassword(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try submitting again.');
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        $userId = (int)Session::get('user_id');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = $this->userService->changePassword($userId, $currentPassword, $newPassword, $confirmPassword);

        if (!$result['success']) {
            Session::setFlash('password_errors', $result['errors']);
            Session::setFlash('error', 'Failed to change password. Please check your inputs.');
            header('Location: ' . getenv('APP_URL') . '/account');
            exit;
        }

        Session::setFlash('success', 'Password updated successfully! Your account security is enhanced.');
        header('Location: ' . getenv('APP_URL') . '/account');
        exit;
    }
}
