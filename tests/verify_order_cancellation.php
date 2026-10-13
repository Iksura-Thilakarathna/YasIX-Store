<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/app/Helpers/Sanitizer.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Database\Connection;
use App\Repositories\OrderRepository;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use App\Services\CheckoutService;

echo "==================================================\n";
echo "   YASIX STORE - DAY 5 COMMIT 2 CANCELLATION TEST \n";
echo "==================================================\n";

$db = Connection::getInstance();
$orderRepo = new OrderRepository();
$orderService = new OrderService();
$checkoutService = new CheckoutService();
$cartRepo = new CartRepository();
$productRepo = new ProductRepository();

$adminId = 1;
$customerId = 2;

// 1. Create a fresh order for Customer (User 2)
echo "\n--- [1] ATOMIC ORDER CREATION & STOCK DECREMENT ---\n";
$productBefore = $productRepo->findById(7); // Quantum Core i11 ($899)
$initialStock = (int)$productBefore['stock_quantity'];

$cartId = $cartRepo->getOrCreateCart($customerId);
$cartRepo->clearCart($cartId);
$cartRepo->addItem($cartId, 7, 2); // 2 units

$checkoutData = $checkoutService->prepareCheckout($customerId, 1);
$postData = [
    'checkout_token' => $checkoutData['checkout_token'],
    'full_name' => 'Cyber Customer',
    'phone' => '+94 77 111 2233',
    'address_line1' => 'No. 99 Quantum Boulevard',
    'city' => 'Colombo',
    'postal_code' => '00100',
    'delivery_rule_id' => 1,
    'payment_method' => 'cod'
];
$orderResult = $checkoutService->placeOrder($customerId, $postData);
assert($orderResult['success'] === true, "Order placement must succeed");
$orderNumber = $orderResult['order_number'];
$orderId = (int)$orderResult['order_id'];

$productAfter = $productRepo->findById(7);
$stockAfterOrder = (int)$productAfter['stock_quantity'];
assert($stockAfterOrder === $initialStock - 2, "Stock must decrement by 2 (initial: {$initialStock}, after: {$stockAfterOrder})");
echo "[PASS] Order #{$orderNumber} placed. Stock decremented: {$initialStock} -> {$stockAfterOrder}.\n";

// 2. Customer cancellation of eligible PENDING order
echo "\n--- [2] ELIGIBLE CUSTOMER CANCELLATION & INVENTORY RESTORATION ---\n";
$cancelResult = $orderService->cancelOrder($orderId, $customerId, 'CUSTOMER', 'Customer requested test cancellation');
assert($cancelResult['success'] === true, "Customer cancellation of PENDING order must succeed");

$productRestored = $productRepo->findById(7);
$stockAfterCancel = (int)$productRestored['stock_quantity'];
assert($stockAfterCancel === $initialStock, "Stock must be fully restored upon cancellation ({$initialStock})");

$cancelledOrder = $orderService->getOrderDetails($orderNumber, $customerId);
assert($cancelledOrder['status'] === 'CANCELLED', "Order status must be updated to CANCELLED");
assert($cancelledOrder['payment_status'] === 'PENDING' || $cancelledOrder['payment_status'] === 'FAILED', "Unpaid COD payment must not be marked PAID");

// Check audit log
$history = $cancelledOrder['history'];
$lastHistory = end($history);
assert($lastHistory['new_status'] === 'CANCELLED', "Audit log must record CANCELLED transition");
assert($lastHistory['actor_role'] === 'CUSTOMER', "Audit log actor must be CUSTOMER");
echo "[PASS] Eligible cancellation successful. Stock restored accurately: {$stockAfterOrder} -> {$stockAfterCancel}.\n";

// 3. Repeated cancellation protection
echo "\n--- [3] PREVENT REPEATED / DUPLICATE CANCELLATION ---\n";
$repeatCancel = $orderService->cancelOrder($orderId, $customerId, 'CUSTOMER', 'Trying to cancel again');
assert($repeatCancel['success'] === false, "Repeated cancellation must be rejected");

$productStockUnchanged = $productRepo->findById(7);
assert((int)$productStockUnchanged['stock_quantity'] === $initialStock, "Stock must NOT be restored twice on duplicate cancellation");
echo "[PASS] Repeated cancellation safely blocked. Stock remained unchanged ({$initialStock}).\n";

// 4. Unauthorized customer cancellation (User attempting to cancel another's order)
echo "\n--- [4] UNAUTHORIZED CUSTOMER CANCELLATION DEFENSE ---\n";
$cartRepo->clearCart($cartId);
$cartRepo->addItem($cartId, 7, 1);
$checkoutData2 = $checkoutService->prepareCheckout($customerId, 1);
$postData['checkout_token'] = $checkoutData2['checkout_token'];
$orderResult2 = $checkoutService->placeOrder($customerId, $postData);
$order2Id = (int)$orderResult2['order_id'];
$order2Number = $orderResult2['order_number'];

// Attempt cancellation by different user 999
$unauthorizedCancel = $orderService->cancelOrder($order2Id, 999, 'CUSTOMER', 'Malicious cancellation attempt');
assert($unauthorizedCancel['success'] === false, "Unauthorized customer must not cancel another's order");
echo "[PASS] Unauthorized customer cancellation blocked.\n";

// 5. Admin Status Lifecycle Progression
echo "\n--- [5] AUTHORIZED ADMIN STATUS LIFECYCLE TRANSITIONS ---\n";
// Non-admin attempting admin status transition
$unauthStatus = $orderService->updateOrderStatus($order2Id, 'CONFIRMED', $customerId, 'CUSTOMER');
assert($unauthStatus['success'] === false, "Customer must NOT be permitted to perform administrative status update");
echo "[PASS] Customer blocked from performing administrative status update.\n";

// Admin updates PENDING -> CONFIRMED
$adminConfirm = $orderService->updateOrderStatus($order2Id, 'CONFIRMED', $adminId, 'ADMIN', 'Confirmed by admin logistics');
assert($adminConfirm['success'] === true, "Admin transition PENDING -> CONFIRMED must succeed");

// Admin updates CONFIRMED -> PROCESSING
$adminProcess = $orderService->updateOrderStatus($order2Id, 'PROCESSING', $adminId, 'ADMIN', 'Packaging hardware');
assert($adminProcess['success'] === true, "Admin transition CONFIRMED -> PROCESSING must succeed");
echo "[PASS] Admin transitions PENDING -> CONFIRMED -> PROCESSING verified.\n";

// 6. Customer cancellation disallowed once PROCESSING has begun
echo "\n--- [6] CUSTOMER CANCELLATION DISALLOWED IN FULFILMENT STAGE ---\n";
$lateCancel = $orderService->cancelOrder($order2Id, $customerId, 'CUSTOMER', 'Attempting late cancellation');
assert($lateCancel['success'] === false, "Customer cancellation must be rejected once PROCESSING begins");
echo "[PASS] Customer cancellation rejected after fulfilment processing commenced.\n";

// 7. Invalid Status Transitions Rejected
echo "\n--- [7] INVALID STATUS TRANSITIONS REJECTED ---\n";
// From PROCESSING cannot jump directly to DELIVERED
$invalidJump = $orderService->updateOrderStatus($order2Id, 'DELIVERED', $adminId, 'ADMIN');
assert($invalidJump['success'] === false, "Illegal transition PROCESSING -> DELIVERED must be rejected");

// From PROCESSING to SHIPPED
$adminShip = $orderService->updateOrderStatus($order2Id, 'SHIPPED', $adminId, 'ADMIN', 'Handed to courier');
assert($adminShip['success'] === true, "PROCESSING -> SHIPPED must succeed");

// From SHIPPED to OUT_FOR_DELIVERY
$adminOut = $orderService->updateOrderStatus($order2Id, 'OUT_FOR_DELIVERY', $adminId, 'ADMIN', 'Rider assigned');
assert($adminOut['success'] === true, "SHIPPED -> OUT_FOR_DELIVERY must succeed");

// From OUT_FOR_DELIVERY to DELIVERED with COD payment collection
$adminDeliver = $orderService->updateOrderStatus($order2Id, 'DELIVERED', $adminId, 'ADMIN', 'Package delivered and cash collected', 'PAID');
assert($adminDeliver['success'] === true, "OUT_FOR_DELIVERY -> DELIVERED must succeed");

$deliveredOrder = $orderService->getOrderDetails($order2Number, $customerId);
assert($deliveredOrder['status'] === 'DELIVERED', "Delivered status verified");
assert(!empty($deliveredOrder['delivered_at']), "Delivered date must be recorded on delivery");
assert($deliveredOrder['payment_status'] === 'PAID', "Payment status must be PAID upon confirmed COD collection");
echo "[PASS] Valid transitions SHIPPED -> OUT_FOR_DELIVERY -> DELIVERED verified with delivery timestamp and payment collection.\n";

// Terminal state: Cannot transition from DELIVERED
$afterDeliveredCancel = $orderService->cancelOrder($order2Id, $adminId, 'ADMIN');
assert($afterDeliveredCancel['success'] === false, "Cannot cancel an already delivered order");
echo "[PASS] Terminal state DELIVERED protected from cancellation.\n";

echo "\n==================================================\n";
echo " RESULT: COMMIT 2 STATUS & CANCELLATION PASSED 🚀  \n";
echo "==================================================\n";
