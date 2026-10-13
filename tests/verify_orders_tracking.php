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
use App\Services\CartService;

echo "==================================================\n";
echo "   YASIX STORE - DAY 5 COMMIT 1 ORDER VERIFICATION \n";
echo "==================================================\n";

$db = Connection::getInstance();
$orderRepo = new OrderRepository();
$orderService = new OrderService();
$checkoutService = new CheckoutService();
$cartRepo = new CartRepository();
$cartService = new CartService();
$productRepo = new ProductRepository();

// Use User 1 (Admin) and User 2 (Customer)
$adminId = 1;
$customerId = 2;

// Clean up any test orders previously created for user 999
$db->exec("DELETE FROM users WHERE email = 'empty_orders@yasix.local'");
$db->exec("INSERT INTO users (id, role_id, name, email, password_hash, phone, status) VALUES (999, 2, 'No Order User', 'empty_orders@yasix.local', 'hash', '+94770009999', 'ACTIVE')");
$noOrderUserId = 999;

// 1. Customer with no orders
echo "\n--- [1] EMPTY STATE VERIFICATION ---\n";
$emptyOrders = $orderService->getUserOrders($noOrderUserId, 1, 10);
assert(empty($emptyOrders['orders']), "New customer with no purchases must have empty orders list");
assert($emptyOrders['total'] === 0, "Total orders must be 0");
echo "[PASS] Empty order state verified (0 orders returned).\n";

// 2. Prepare an order for customer (User 2)
echo "\n--- [2] SEED CUSTOMER ORDER WITH HISTORICAL SNAPSHOT ---\n";
$cartId = $cartRepo->getOrCreateCart($customerId);
$cartRepo->clearCart($cartId);
$cartRepo->addItem($cartId, 4, 1); // AeroBlade Cyber Keyboard ($249)
$cartRepo->addItem($cartId, 6, 2); // Synapse Ultra Cyber Mouse ($149 x 2 = $298)

$checkoutData = $checkoutService->prepareCheckout($customerId, 1);
$postData = [
    'checkout_token' => $checkoutData['checkout_token'],
    'full_name' => 'Cyber Customer',
    'phone' => '+94 77 111 2233',
    'address_line1' => 'Suite 701, Neon Matrix Tower',
    'address_line2' => 'Sector 4',
    'city' => 'Colombo',
    'postal_code' => '00100',
    'delivery_rule_id' => 1,
    'payment_method' => 'cod'
];
$orderResult = $checkoutService->placeOrder($customerId, $postData);
assert($orderResult['success'] === true, "Order placement must succeed");
$orderNumber = $orderResult['order_number'];
echo "[PASS] Created test order: {$orderNumber}\n";

// 3. Customer order history list verification
echo "\n--- [3] ORDER HISTORY RETRIEVAL & LKR FORMATTING ---\n";
$historyData = $orderService->getUserOrders($customerId, 1, 10);
assert(!empty($historyData['orders']), "Customer orders must not be empty");
assert($historyData['total'] >= 1, "Customer total count must be at least 1");

$foundOrder = null;
foreach ($historyData['orders'] as $ord) {
    if ($ord['order_number'] === $orderNumber) {
        $foundOrder = $ord;
        break;
    }
}
assert($foundOrder !== null, "Created order must be in customer order history");
assert(str_contains($foundOrder['formatted_total'], 'LKR'), "Total amount must be formatted with LKR currency");
assert($foundOrder['payment_method'] === 'CASH_ON_DELIVERY', "Payment method must be CASH_ON_DELIVERY");
assert($foundOrder['status'] === 'PENDING', "Initial order status must be PENDING");
echo "[PASS] Customer order history retrieved with accurate LKR formatting and metadata.\n";

// 4. Pagination verification
echo "\n--- [4] PAGINATION VERIFICATION ---\n";
$paged1 = $orderService->getUserOrders($customerId, 1, 1);
assert(count($paged1['orders']) === 1, "Page with limit 1 must return exactly 1 order");
assert($paged1['total_pages'] >= 1, "Total pages must be calculated correctly");
echo "[PASS] Pagination logic verified (limit 1 returns 1 order out of {$paged1['total']}).\n";

// 5. Order details & snapshot verification
echo "\n--- [5] ORDER DETAILS & PURCHASE-TIME SNAPSHOT ---\n";
$details = $orderService->getOrderDetails($orderNumber, $customerId);
assert($details !== null, "Order details must be found for owner");
assert($details['order_number'] === $orderNumber, "Order number matches");
assert(count($details['items']) === 2, "Must contain exactly 2 purchased items");

// Check snapshot unit prices
$keyboard = null;
$mouse = null;
foreach ($details['items'] as $item) {
    if ((int)$item['product_id'] === 4) $keyboard = $item;
    if ((int)$item['product_id'] === 6) $mouse = $item;
}
assert($keyboard !== null && (float)$keyboard['unit_price'] === 249.00, "Keyboard purchase-time unit price must be 249.00");
assert($mouse !== null && (float)$mouse['unit_price'] === 149.00 && (int)$mouse['quantity'] === 2, "Mouse purchase-time unit price must be 149.00 and qty 2");
assert((float)$keyboard['subtotal'] === 249.00, "Keyboard subtotal must be 249.00");
assert((float)$mouse['subtotal'] === 298.00, "Mouse subtotal must be 298.00");

// Check address snapshot
assert($details['address_line1'] === 'Suite 701, Neon Matrix Tower', "Historical address line 1 verified");
assert(!empty($details['customer_phone']), "Historical phone number verified");

// Check warranty snapshot
assert((int)$keyboard['warranty_months'] > 0, "Keyboard historical warranty months must be preserved (> 0)");
assert((int)$mouse['warranty_months'] > 0, "Mouse historical warranty months must be preserved (> 0)");
echo "[PASS] Historical prices, line subtotals, warranty months, and address snapshot fully verified.\n";

// 6. Security & Authorization verification (IDOR protection)
echo "\n--- [6] STRICT SERVER-SIDE AUTHORIZATION (IDOR CHECK) ---\n";
$unauthorizedAccess = $orderService->getOrderDetails($orderNumber, $noOrderUserId);
assert($unauthorizedAccess === null, "User 999 must NOT be able to view customer's order (IDOR defense)");
$unauthorizedTracking = $orderService->getOrderTracking($orderNumber, $noOrderUserId);
assert($unauthorizedTracking === null, "User 999 must NOT be able to track customer's order");
echo "[PASS] Unauthorized access to another customer's order safely blocked (returns null/404).\n";

// 7. Invalid order ID verification
echo "\n--- [7] NON-EXISTENT ORDER LOOKUP ---\n";
$nonExistent = $orderService->getOrderDetails('YAS-INVALID-99999', $customerId);
assert($nonExistent === null, "Non-existent order must safely return null");
echo "[PASS] Non-existent order safely handled without error.\n";

// 8. Order Tracking Timeline & Milestones verification
echo "\n--- [8] ORDER TRACKING TIMELINE & MILESTONES ---\n";
$tracking = $orderService->getOrderTracking($orderNumber, $customerId);
assert($tracking !== null, "Tracking data must be found for owner");
assert($tracking['current_status'] === 'PENDING', "Current status must be PENDING");
assert(count($tracking['milestones']) === 6, "Must define 6 standard lifecycle milestones");
assert($tracking['milestones'][0]['status'] === 'PENDING', "First milestone must be PENDING");
assert($tracking['milestones'][0]['state'] === 'current', "PENDING must be marked as 'current'");
assert($tracking['milestones'][1]['state'] === 'upcoming', "CONFIRMED must be marked as 'upcoming'");
assert(!empty($tracking['status_history']), "Initial status must be logged in status_history");
assert($tracking['status_history'][0]['new_status'] === 'PENDING', "Status history must record initial PENDING");
echo "[PASS] Tracking lifecycle milestones and audit history verified.\n";

// Clean up user 999
$db->exec("DELETE FROM users WHERE id = {$noOrderUserId}");

echo "\n==================================================\n";
echo " RESULT: COMMIT 1 ORDER HISTORY & TRACKING PASSED 🚀\n";
echo "==================================================\n";
