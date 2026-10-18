<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\Session;
use App\Helpers\CSRF;

class AdminCustomerController extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(): void
    {
        $data = $this->userService->getAdminCustomers($_GET);

        $this->render('admin/customers/index', [
            'title' => 'Customer Directory & Accounts | YasIX Admin',
            'headerTitle' => 'Registered Customer Accounts',
            'customers' => $data['customers'],
            'filters' => $data['filters'],
            'pagination' => $data['pagination']
        ], 'admin');
    }

    public function detail(string $id): void
    {
        $customerId = (int)$id;
        $customer = $this->userService->getCustomerDetails($customerId);

        if (!$customer) {
            Session::setFlash('error', "Customer #{$customerId} not found in user registry.");
            header('Location: ' . getenv('APP_URL') . '/admin/customers');
            exit;
        }

        $this->render('admin/customers/detail', [
            'title' => "Customer Profile: {$customer['name']} | YasIX Admin",
            'headerTitle' => "Customer Terminal: {$customer['name']}",
            'customer' => $customer
        ], 'admin');
    }

    public function toggleStatus(string $id): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Account modification rejected.');
            header('Location: ' . getenv('APP_URL') . '/admin/customers/' . urlencode($id));
            exit;
        }

        $targetUserId = (int)$id;
        $adminUser = Session::get('user');
        $adminId = (int)($adminUser['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'ACTIVE');

        $result = $this->userService->setCustomerStatus($targetUserId, $newStatus, $adminId);

        if ($result['success']) {
            Session::setFlash('success', "Customer account status successfully transitioned to {$result['status']}.");
        } else {
            Session::setFlash('error', $result['error'] ?? 'Failed to update customer account status.');
        }

        header('Location: ' . getenv('APP_URL') . '/admin/customers/' . urlencode($id));
        exit;
    }
}
