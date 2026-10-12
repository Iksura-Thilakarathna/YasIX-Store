<?php

// Day 4 Checkout & Cash on Delivery Verification Suite
chdir(__DIR__ . '/..');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\CartService;
use App\Services\CheckoutService;
use App\Repositories\ProductRepository;
use App\Repositories\OrderRepository;
use App\Helpers\Session;

Session::start();

echo "==================================================\n";
echo "   YASIX STORE - DAY 4 CHECKOUT & COD TEST        \n";
echo "==================================================\n";

$cartService = new CartService();
$checkoutService = new CheckoutService();
$productRepo = new ProductRepository();
$orderRepo = new OrderRepository();

// Setup Customer 2 (Cyber Customer)
$userId = 2;
$cartService->clearCart($userId);

// Fetch initial stock for Product 2 (YasIX Synapse Neural Headset, $399.00)
$initialProduct = $productRepo->findByIdWithDetails(2);
$initialStock = (int)$initialProduct['stock_quantity'];

// 1. Add item to cart
$cartService->addToCart($userId, 2, 2);

// 2. Prepare checkout
$checkoutPrep = $checkoutService->prepareCheckout($userId, 1);
if (!empty($checkoutPrep['checkout_token']) && $checkoutPrep['cart']['item_count'] === 2) {
    echo "[PASS] Checkout prepared successfully with token and cart items.\n";
} else {
    echo "[FAIL] Checkout preparation failed.\n";
    exit(1);
}

// 3. Place Cash on Delivery order
$orderFormData = [
    'checkout_token' => $checkoutPrep['checkout_token'],
    'full_name' => 'Cyber Customer',
    'phone' => '+94771112233',
    'address_line1' => 'No. 42 Cyber Tower, Neural St',
    'address_line2' => 'Level 8',
    'city' => 'Colombo',
    'delivery_rule_id' => 1,
    'postal_code' => '00500'
];

$orderResult = $checkoutService->placeOrder($userId, $orderFormData);
if ($orderResult['success'] && !empty($orderResult['order_number'])) {
    $orderNumber = $orderResult['order_number'];
    echo "[PASS] Transactional COD order placed successfully: {$orderNumber}\n";
} else {
    echo "[FAIL] Order placement failed: " . ($orderResult['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

// 4. Verify Order & Price Snapshots in Database
$order = $checkoutService->getOrderConfirmation($orderNumber, $userId);
if ($order && $order['payment_method'] === 'CASH_ON_DELIVERY' && $order['status'] === 'PENDING') {
    echo "[PASS] Order header and COD payment method verified.\n";
} else {
    echo "[FAIL] Order verification failed in database.\n";
    exit(1);
}

if (!empty($order['items']) && count($order['items']) === 1) {
    $orderedItem = $order['items'][0];
    if ((int)$orderedItem['product_id'] === 2 && (float)$orderedItem['unit_price'] == 399.00 && (int)$orderedItem['quantity'] === 2) {
        echo "[PASS] Order item historical price snapshot verified ($399.00 x 2 = $798.00).\n";
    } else {
        echo "[FAIL] Order item snapshot data incorrect.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Order items missing in database.\n";
    exit(1);
}

// 5. Verify Product Stock Was Decremented Atomically
$updatedProduct = $productRepo->findByIdWithDetails(2);
$updatedStock = (int)$updatedProduct['stock_quantity'];
if ($updatedStock === ($initialStock - 2)) {
    echo "[PASS] Product stock decremented atomically ({$initialStock} -> {$updatedStock}).\n";
} else {
    echo "[FAIL] Stock was not decremented correctly. Expected " . ($initialStock - 2) . ", found {$updatedStock}.\n";
    exit(1);
}

// 6. Verify Customer Cart Was Cleared After Order
$postCart = $cartService->getCart($userId);
if ($postCart['is_empty'] && $postCart['item_count'] === 0) {
    echo "[PASS] Customer cart cleared upon successful checkout.\n";
} else {
    echo "[FAIL] Cart was not cleared after order creation.\n";
    exit(1);
}

// 7. Verify Duplicate Submission Protection
$duplicateAttempt = $checkoutService->placeOrder($userId, $orderFormData);
if (!$duplicateAttempt['success']) {
    echo "[PASS] Duplicate checkout submission successfully blocked.\n";
} else {
    echo "[FAIL] Duplicate checkout was allowed.\n";
    exit(1);
}

// 8. Verify Authorization: Another customer cannot view this order
$otherUserOrder = $checkoutService->getOrderConfirmation($orderNumber, 1); // User 1
if ($otherUserOrder === null) {
    echo "[PASS] Unauthorized customer blocked from viewing order confirmation.\n";
} else {
    echo "[FAIL] Unauthorized user was able to access order details!\n";
    exit(1);
}

// 9. Verify Transaction Rollback on Insufficient Stock
$cartService->addToCart($userId, 2, 1);
$prepRollback = $checkoutService->prepareCheckout($userId, 1);

// Artificially change product stock to 0 to simulate race condition before commit
$db = \App\Database\Connection::getInstance();
$db->exec("UPDATE products SET stock_quantity = 0 WHERE id = 2");

$rollbackAttempt = $checkoutService->placeOrder($userId, [
    'checkout_token' => $prepRollback['checkout_token'],
    'full_name' => 'Tester',
    'phone' => '+94770000000',
    'address_line1' => 'Test Street',
    'city' => 'Colombo',
    'delivery_rule_id' => 1
]);

// Restore stock back
$db->exec("UPDATE products SET stock_quantity = {$updatedStock} WHERE id = 2");

if (!$rollbackAttempt['success']) {
    echo "[PASS] Transaction rollback successfully triggered when stock became insufficient.\n";
} else {
    echo "[FAIL] Transaction failed to rollback on stock error.\n";
    exit(1);
}

// Clean up test cart
$cartService->clearCart($userId);

echo "==================================================\n";
echo " RESULT: ALL COMMIT 3 CHECKOUT & COD TESTS PASSED 🚀\n";
echo "==================================================\n";
