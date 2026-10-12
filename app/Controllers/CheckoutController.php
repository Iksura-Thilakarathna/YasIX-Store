<?php

namespace App\Controllers;

use App\Services\CheckoutService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class CheckoutController extends BaseController
{
    private CheckoutService $checkoutService;

    public function __construct()
    {
        $this->checkoutService = new CheckoutService();
    }

    public function index(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');
        $ruleId = isset($_GET['rule_id']) && is_numeric($_GET['rule_id']) ? (int)$_GET['rule_id'] : null;

        $checkoutData = $this->checkoutService->prepareCheckout($userId, $ruleId);

        if ($checkoutData['cart']['is_empty']) {
            Session::setFlash('error', 'Your shopping cart is empty. Please add products before checking out.');
            header('Location: ' . getenv('APP_URL') . '/cart');
            exit;
        }

        View::render('checkout/index', [
            'title' => 'Checkout - YasIX Cyber Store',
            'checkout' => $checkoutData,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    public function placeOrder(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try submitting your order again.');
            header('Location: ' . getenv('APP_URL') . '/checkout');
            exit;
        }

        $result = $this->checkoutService->placeOrder($userId, $_POST);

        if (!$result['success']) {
            Session::setFlash('error', $result['message']);
            header('Location: ' . getenv('APP_URL') . '/checkout');
            exit;
        }

        // Post/Redirect/Get pattern
        header('Location: ' . getenv('APP_URL') . '/checkout/success/' . urlencode($result['order_number']));
        exit;
    }

    public function success(?string $orderNumber = null): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');
        $targetOrderNumber = $orderNumber ?? $_GET['order_number'] ?? null;

        if (!$targetOrderNumber) {
            (new ErrorController())->notFound();
            return;
        }

        $order = $this->checkoutService->getOrderConfirmation($targetOrderNumber, $userId);

        if (!$order) {
            // Customer cannot view another user's order or non-existent order
            (new ErrorController())->notFound();
            return;
        }

        View::render('checkout/success', [
            'title' => 'Order Confirmed - ' . e($order['order_number']),
            'order' => $order
        ]);
    }
}
