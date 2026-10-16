<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OrderService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class OrderController extends BaseController
{
    private OrderService $orderService;

    public function __construct(?OrderService $orderService = null)
    {
        $this->orderService = $orderService ?? new OrderService();
    }

    /**
     * Customer Order History Page
     */
    public function index(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

        $orderData = $this->orderService->getUserOrders($userId, $page, 10);

        View::render('orders/index', [
            'title' => 'My Orders // YasIX Dispatch Matrix',
            'orders' => $orderData['orders'],
            'total' => $orderData['total'],
            'page' => $orderData['page'],
            'limit' => $orderData['limit'],
            'total_pages' => $orderData['total_pages'],
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Detailed Order Receipt and Overview
     */
    public function detail(string $orderNumber): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $order = $this->orderService->getOrderDetails($orderNumber, $userId);

        if (!$order) {
            // Strict authorization check: return 404 without leaking other users' orders
            (new ErrorController())->notFound();
            return;
        }

        $tracking = $this->orderService->getOrderTracking($orderNumber, $userId);

        View::render('orders/detail', [
            'title' => 'Order ' . e($order['order_number']) . ' // Specification Receipt',
            'order' => $order,
            'tracking' => $tracking,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Dedicated Order Tracking Timeline Page
     */
    public function track(string $orderNumber): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $tracking = $this->orderService->getOrderTracking($orderNumber, $userId);

        if (!$tracking) {
            // Strict authorization check: return 404 without leaking other users' orders
            (new ErrorController())->notFound();
            return;
        }

        View::render('orders/track', [
            'title' => 'Live Tracking #' . e($tracking['order']['order_number']) . ' // Courier Radar',
            'tracking' => $tracking,
            'order' => $tracking['order'],
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Customer Order Cancellation Endpoint
     */
    public function cancel(string $orderNumber): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try again.');
            header('Location: ' . getenv('APP_URL') . '/orders/' . urlencode($orderNumber));
            exit;
        }

        $order = $this->orderService->getOrderDetails($orderNumber, $userId);
        if (!$order) {
            (new ErrorController())->notFound();
            return;
        }

        $reason = trim($_POST['reason'] ?? 'Cancelled by customer');
        $result = $this->orderService->cancelOrder((int)$order['id'], $userId, 'CUSTOMER', $reason);

        if ($result['success']) {
            Session::setFlash('success', 'Order #' . $orderNumber . ' was successfully cancelled and items were restored to inventory.');
        } else {
            Session::setFlash('error', $result['message']);
        }

        header('Location: ' . getenv('APP_URL') . '/orders/' . urlencode($orderNumber));
        exit;
    }
}
