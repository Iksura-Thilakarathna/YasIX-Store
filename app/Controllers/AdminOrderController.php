<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OrderService;
use App\Helpers\Session;
use App\Helpers\CSRF;

class AdminOrderController extends BaseController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function index(): void
    {
        $data = $this->orderService->getAdminOrders($_GET);

        $this->render('admin/orders/index', [
            'title' => 'Order Management & Fulfilment | YasIX Admin',
            'headerTitle' => 'Order Fulfilment Matrix',
            'orders' => $data['orders'],
            'filters' => $data['filters'],
            'pagination' => $data['pagination']
        ], 'admin');
    }

    public function detail(string $orderNumber): void
    {
        $order = $this->orderService->getAdminOrderByNumber(trim($orderNumber));

        if (!$order) {
            Session::setFlash('error', "Order '{$orderNumber}' not found in registry.");
            header('Location: ' . getenv('APP_URL') . '/admin/orders');
            exit;
        }

        $order['status_meta'] = $this->orderService->getOrderStatusMetadata($order['status']);
        $order['payment_meta'] = $this->orderService->getPaymentStatusMetadata($order['payment_status']);

        // Define valid transitions for the admin interface
        $allowedTransitions = [
            'PENDING' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['PROCESSING', 'CANCELLED'],
            'PROCESSING' => ['SHIPPED', 'CANCELLED'],
            'SHIPPED' => ['OUT_FOR_DELIVERY', 'DELIVERY_FAILED', 'RETURNED'],
            'OUT_FOR_DELIVERY' => ['DELIVERED', 'DELIVERY_FAILED', 'RETURNED'],
            'DELIVERY_FAILED' => ['OUT_FOR_DELIVERY', 'RETURNED', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
            'RETURNED' => []
        ];

        $nextPossibleStatuses = $allowedTransitions[$order['status']] ?? [];

        $this->render('admin/orders/detail', [
            'title' => "Order #{$order['order_number']} | YasIX Admin",
            'headerTitle' => "Order Terminal: #{$order['order_number']}",
            'order' => $order,
            'nextStatuses' => $nextPossibleStatuses
        ], 'admin');
    }

    public function updateStatus(string $orderNumber): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Action rejected.');
            header('Location: ' . getenv('APP_URL') . '/admin/orders/' . urlencode($orderNumber));
            exit;
        }

        $order = $this->orderService->getAdminOrderByNumber(trim($orderNumber));
        if (!$order) {
            Session::setFlash('error', 'Target order not found.');
            header('Location: ' . getenv('APP_URL') . '/admin/orders');
            exit;
        }

        $adminUser = Session::get('user');
        $adminId = (int)($adminUser['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $newPaymentStatus = !empty($_POST['payment_status']) ? trim($_POST['payment_status']) : null;

        $result = $this->orderService->updateOrderStatus(
            (int)$order['id'],
            $newStatus,
            $adminId,
            'ADMIN',
            $reason ?: null,
            $newPaymentStatus
        );

        if ($result['success']) {
            Session::setFlash('success', "Order #{$order['order_number']} updated successfully to {$newStatus}.");
        } else {
            Session::setFlash('error', "Status transition failed: " . ($result['message'] ?? 'Unknown error.'));
        }

        header('Location: ' . getenv('APP_URL') . '/admin/orders/' . urlencode($orderNumber));
        exit;
    }

    public function payments(): void
    {
        $data = $this->orderService->getAdminPayments($_GET);

        $this->render('admin/payments/index', [
            'title' => 'COD Payment Ledger | YasIX Admin',
            'headerTitle' => 'Cash On Delivery Settlement Matrix',
            'payments' => $data['payments'],
            'filters' => $data['filters'],
            'pagination' => $data['pagination']
        ], 'admin');
    }

    public function recordPayment(string $orderNumber): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Payment collection aborted.');
            header('Location: ' . getenv('APP_URL') . '/admin/orders/' . urlencode($orderNumber));
            exit;
        }

        $order = $this->orderService->getAdminOrderByNumber(trim($orderNumber));
        if (!$order) {
            Session::setFlash('error', 'Target order not found.');
            header('Location: ' . getenv('APP_URL') . '/admin/orders');
            exit;
        }

        $adminUser = Session::get('user');
        $adminId = (int)($adminUser['id'] ?? 0);
        $amount = (float)($_POST['amount_collected'] ?? $order['total_amount']);
        $notes = trim($_POST['notes'] ?? 'COD payment received in full by courier/admin');

        $result = $this->orderService->recordCodCollection(
            (int)$order['id'],
            $amount,
            $adminId,
            $notes
        );

        if ($result['success']) {
            Session::setFlash('success', "Payment for Order #{$order['order_number']} verified and recorded as COMPLETED (LKR " . number_format($amount, 2) . ").");
        } else {
            Session::setFlash('error', "Payment recording failed: " . ($result['message'] ?? 'Unknown error.'));
        }

        $redirectTo = !empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'payments' ? '/admin/payments' : '/admin/orders/' . urlencode($orderNumber);
        header('Location: ' . getenv('APP_URL') . $redirectTo);
        exit;
    }
}
