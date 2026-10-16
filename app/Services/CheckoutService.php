<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use App\Services\UserService;
use App\Helpers\Session;

class CheckoutService extends BaseService
{
    private OrderRepository $orderRepository;
    private CartService $cartService;
    private DeliveryCalculatorService $deliveryCalculatorService;
    private UserService $userService;

    public function __construct(
        ?OrderRepository $orderRepository = null,
        ?CartService $cartService = null,
        ?DeliveryCalculatorService $deliveryCalculatorService = null,
        ?UserService $userService = null
    ) {
        $this->orderRepository = $orderRepository ?? new OrderRepository();
        $this->cartService = $cartService ?? new CartService();
        $this->deliveryCalculatorService = $deliveryCalculatorService ?? new DeliveryCalculatorService();
        $this->userService = $userService ?? new UserService();
    }

    public function prepareCheckout(int $userId, ?int $selectedRuleId = null): array
    {
        $cart = $this->cartService->getCart($userId);
        $user = $this->userService->getUserById($userId);
        $deliveryRules = $this->deliveryCalculatorService->getActiveRules();

        $ruleId = $selectedRuleId;
        if (!$ruleId && !empty($deliveryRules)) {
            $ruleId = (int)$deliveryRules[0]['id'];
        }

        $deliveryEstimate = null;
        if ($ruleId && !$cart['is_empty']) {
            $deliveryEstimate = $this->deliveryCalculatorService->calculateDelivery($cart['items'], $ruleId);
        }

        $shippingFee = ($deliveryEstimate && $deliveryEstimate['success']) ? $deliveryEstimate['total_delivery_fee'] : 0.00;
        $orderTotal = $cart['subtotal'] + $shippingFee;

        // Generate and record single-use checkout token for duplicate submission prevention
        $checkoutToken = bin2hex(random_bytes(16));
        Session::set('_checkout_token', $checkoutToken);

        return [
            'user' => $user,
            'cart' => $cart,
            'delivery_rules' => $deliveryRules,
            'selected_rule_id' => $ruleId,
            'delivery_estimate' => $deliveryEstimate,
            'shipping_fee' => $shippingFee,
            'order_total' => $orderTotal,
            'formatted_shipping_fee' => formatCurrency($shippingFee),
            'formatted_order_total' => formatCurrency($orderTotal),
            'checkout_token' => $checkoutToken
        ];
    }

    public function placeOrder(int $userId, array $formData): array
    {
        // 1. Idempotency & Duplicate Submission Check
        $submittedToken = $formData['checkout_token'] ?? null;
        $storedToken = Session::get('_checkout_token');

        if (!$submittedToken || !$storedToken || !hash_equals($storedToken, $submittedToken)) {
            return [
                'success' => false,
                'message' => 'Duplicate or expired checkout session. Please review and submit once.'
            ];
        }

        // 2. Validate Required Customer Fields
        $fullName = trim($formData['full_name'] ?? '');
        $phone = trim($formData['phone'] ?? '');
        $address1 = trim($formData['address_line1'] ?? '');
        $city = trim($formData['city'] ?? '');
        $ruleId = (int)($formData['delivery_rule_id'] ?? 0);

        if (empty($fullName) || empty($phone) || empty($address1) || empty($city) || $ruleId <= 0) {
            return [
                'success' => false,
                'message' => 'Please provide all required delivery details and select a delivery district.'
            ];
        }

        // 3. Reload Cart from Database
        $cart = $this->cartService->getCart($userId);
        if ($cart['is_empty']) {
            return [
                'success' => false,
                'message' => 'Your cart is empty. Please add items before placing an order.'
            ];
        }

        if ($cart['has_unavailable']) {
            return [
                'success' => false,
                'message' => 'One or more items in your cart are currently out of stock.'
            ];
        }

        // 4. Recalculate Shipping Fee on the Server
        $deliveryCalc = $this->deliveryCalculatorService->calculateDelivery($cart['items'], $ruleId);
        if (!$deliveryCalc['success']) {
            return [
                'success' => false,
                'message' => $deliveryCalc['message']
            ];
        }

        $shippingFee = (float)$deliveryCalc['total_delivery_fee'];
        $subtotal = (float)$cart['subtotal'];
        $totalAmount = round($subtotal + $shippingFee, 2);

        // 5. Generate Order Number
        $orderNumber = 'YAS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        // 6. Build Order & Items Data
        $orderData = [
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'shipping_fee' => $shippingFee,
            'status' => 'PENDING',
            'payment_method' => 'CASH_ON_DELIVERY',
            'payment_status' => 'PENDING',
            'address' => [
                'address_line1' => $address1,
                'address_line2' => trim($formData['address_line2'] ?? ''),
                'city' => $city,
                'state' => $deliveryCalc['region_name'],
                'postal_code' => trim($formData['postal_code'] ?? '')
            ]
        ];

        $orderItems = [];
        foreach ($cart['items'] as $item) {
            $orderItems[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['line_total'],
                'warranty_months' => (int)($item['warranty_months'] ?? 0)
            ];
        }

        $paymentData = [
            'payment_method' => 'CASH_ON_DELIVERY',
            'status' => 'PENDING',
            'transaction_reference' => 'COD-' . $orderNumber
        ];

        // 7. Execute Database Transaction
        $result = $this->orderRepository->createOrderWithTransaction(
            $orderData,
            $orderItems,
            $paymentData,
            (int)$cart['cart_id']
        );

        if ($result['success']) {
            // Invalidate checkout token to prevent double-submit
            Session::remove('_checkout_token');
        }

        return $result;
    }

    public function getOrderConfirmation(string $orderNumber, int $userId): ?array
    {
        return $this->orderRepository->getOrderWithDetails($orderNumber, $userId);
    }
}
