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
use App\Repositories\WarrantyRepository;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use App\Services\WarrantyService;
use App\Services\CheckoutService;
use App\Services\CartService;

echo "==================================================\n";
echo "   YASIX STORE - DAY 5 END-TO-END VALIDATION      \n";
echo "==================================================\n";

$db = Connection::getInstance();
$orderRepo = new OrderRepository();
$warrantyRepo = new WarrantyRepository();
$orderService = new OrderService();
$warrantyService = new WarrantyService();
$checkoutService = new CheckoutService();
$cartRepo = new CartRepository();
$productRepo = new ProductRepository();

$adminId = 1;
$customerId = 2;
$unauthorizedUserId = 997;

// Setup clean state for unauthorized test user
$db->exec("DELETE FROM users WHERE id = {$unauthorizedUserId}");
$db->exec("INSERT INTO users (id, role_id, name, email, password_hash, phone, status) VALUES ({$unauthorizedUserId}, 2, 'Intruder User', 'intruder@yasix.local', 'hash', '+94770007777', 'ACTIVE')");

// 1. Transactional Order Placement & Historical Snapshots
echo "\n--- [1] ATOMIC ORDER PLACEMENT & HISTORICAL SNAPSHOTS ---\n";
$cartId = $cartRepo->getOrCreateCart($customerId);
$cartRepo->clearCart($cartId);

// Add Product 2 (Synapse Headset, $399, 24M warranty) and Product 4 (AeroBlade Keyboard, $249, 24M warranty)
$cartRepo->addItem($cartId, 2, 1);
$cartRepo->addItem($cartId, 4, 1);

$stockBeforeHeadset = (int)$productRepo->findById(2)['stock_quantity'];
$stockBeforeKeyboard = (int)$productRepo->findById(4)['stock_quantity'];

$checkoutData = $checkoutService->prepareCheckout($customerId, 1);
$postData = [
    'checkout_token' => $checkoutData['checkout_token'],
    'full_name' => 'Cyber Customer',
    'phone' => '+94 77 111 2233',
    'address_line1' => 'No. 777 Cyber Highway',
    'city' => 'Colombo',
    'postal_code' => '00100',
    'delivery_rule_id' => 1,
    'payment_method' => 'cod'
];
$orderResult = $checkoutService->placeOrder($customerId, $postData);
assert($orderResult['success'] === true, "Day 5 order placement must succeed");
$orderNumber = $orderResult['order_number'];
$orderId = (int)$orderResult['order_id'];

// Check atomic stock decrement
$stockAfterHeadset = (int)$productRepo->findById(2)['stock_quantity'];
$stockAfterKeyboard = (int)$productRepo->findById(4)['stock_quantity'];
assert($stockAfterHeadset === $stockBeforeHeadset - 1, "Headset stock must decrement by 1");
assert($stockAfterKeyboard === $stockBeforeKeyboard - 1, "Keyboard stock must decrement by 1");
echo "[PASS] Order #{$orderNumber} placed. Hardware inventory decremented atomically.\n";

// 2. Customer Order History & Pagination
echo "\n--- [2] CUSTOMER ORDER HISTORY & LKR FORMATTING ---\n";
$userOrders = $orderService->getUserOrders($customerId, 1, 10);
assert(!empty($userOrders['orders']), "Orders must not be empty");
$found = false;
foreach ($userOrders['orders'] as $ord) {
    if ($ord['order_number'] === $orderNumber) {
        $found = true;
        assert(str_contains($ord['formatted_total'], 'LKR'), "Total must be formatted in LKR");
        assert($ord['payment_method'] === 'CASH_ON_DELIVERY', "Payment method verified");
        assert($ord['status'] === 'PENDING', "Initial status verified");
        break;
    }
}
assert($found, "New order must appear in customer order history");
echo "[PASS] Paginated order history verified with LKR currency formatting.\n";

// 3. Strict Server-Side Authorization (IDOR Defense)
echo "\n--- [3] AUTHORIZATION & IDOR PREVENTION ---\n";
$intruderOrder = $orderService->getOrderDetails($orderNumber, $unauthorizedUserId);
assert($intruderOrder === null, "Unauthorized user must receive null/404 when querying another user's order");
$intruderTrack = $orderService->getOrderTracking($orderNumber, $unauthorizedUserId);
assert($intruderTrack === null, "Unauthorized user must receive null/404 when querying tracking");
echo "[PASS] IDOR vulnerabilities safely blocked on server.\n";

// 4. Order Details & Snapshot Integrity
echo "\n--- [4] HISTORICAL SPECIFICATION SNAPSHOT INTEGRITY ---\n";
$details = $orderService->getOrderDetails($orderNumber, $customerId);
assert($details !== null, "Owner must access order details");
assert($details['address_line1'] === 'No. 777 Cyber Highway', "Delivery address snapshot verified");
assert(count($details['items']) === 2, "Items count verified");
foreach ($details['items'] as $item) {
    assert((int)$item['warranty_months'] > 0, "Warranty months snapshot must be preserved (> 0)");
    assert((float)$item['unit_price'] > 0, "Unit price snapshot must be preserved");
}
echo "[PASS] Address snapshot, historical unit prices, and warranty duration preserved.\n";

// 5. Early Stage Customer Cancellation & Inventory Restoration
echo "\n--- [5] EARLY CUSTOMER CANCELLATION & STOCK RESTORATION ---\n";
$cancelResult = $orderService->cancelOrder($orderId, $customerId, 'CUSTOMER', 'Cancelled by customer test');
assert($cancelResult['success'] === true, "Cancellation of PENDING order must succeed");

$stockRestoredHeadset = (int)$productRepo->findById(2)['stock_quantity'];
$stockRestoredKeyboard = (int)$productRepo->findById(4)['stock_quantity'];
assert($stockRestoredHeadset === $stockBeforeHeadset, "Headset stock must be restored");
assert($stockRestoredKeyboard === $stockBeforeKeyboard, "Keyboard stock must be restored");

// Verify repeated cancellation blocked
$repeatCancel = $orderService->cancelOrder($orderId, $customerId, 'CUSTOMER');
assert($repeatCancel['success'] === false, "Repeated cancellation must be blocked");
assert((int)$productRepo->findById(2)['stock_quantity'] === $stockBeforeHeadset, "Stock must not be restored twice");
echo "[PASS] Customer cancellation verified with atomic stock restoration and anti-duplicate shield.\n";

// 6. Complete Order Lifecycle to DELIVERED
echo "\n--- [6] LIFECYCLE PROGRESSION & LEGITIMATE DELIVERY DATE ---\n";
// Create another order to advance through lifecycle
$cartRepo->clearCart($cartId);
$cartRepo->addItem($cartId, 2, 1); // 1 Headset
$checkoutData2 = $checkoutService->prepareCheckout($customerId, 1);
$postData['checkout_token'] = $checkoutData2['checkout_token'];
$orderResult2 = $checkoutService->placeOrder($customerId, $postData);
$order2Id = (int)$orderResult2['order_id'];
$order2Number = $orderResult2['order_number'];

// Advance: PENDING -> CONFIRMED -> PROCESSING -> SHIPPED -> OUT_FOR_DELIVERY -> DELIVERED
assert($orderService->updateOrderStatus($order2Id, 'CONFIRMED', $adminId, 'ADMIN')['success'] === true);
assert($orderService->updateOrderStatus($order2Id, 'PROCESSING', $adminId, 'ADMIN')['success'] === true);

// Once PROCESSING, customer cannot cancel
assert($orderService->cancelOrder($order2Id, $customerId, 'CUSTOMER')['success'] === false, "Customer cancellation must be blocked in PROCESSING");

assert($orderService->updateOrderStatus($order2Id, 'SHIPPED', $adminId, 'ADMIN')['success'] === true);
assert($orderService->updateOrderStatus($order2Id, 'OUT_FOR_DELIVERY', $adminId, 'ADMIN')['success'] === true);
assert($orderService->updateOrderStatus($order2Id, 'DELIVERED', $adminId, 'ADMIN', 'Delivered at doorstep', 'PAID')['success'] === true);

$deliveredOrder = $orderService->getOrderDetails($order2Number, $customerId);
assert($deliveredOrder['status'] === 'DELIVERED', "Status is DELIVERED");
assert(!empty($deliveredOrder['delivered_at']), "Delivered date recorded");
assert($deliveredOrder['payment_status'] === 'PAID', "COD payment marked PAID upon collection");
echo "[PASS] Lifecycle progression PENDING -> DELIVERED verified with delivery date and COD payment.\n";

// 7. Warranty Eligibility & Claim Workflow
echo "\n--- [7] WARRANTY ELIGIBILITY & CLAIM WORKFLOW ---\n";
$deliveredItemId = (int)$deliveredOrder['items'][0]['id'];
$eligibility = $warrantyService->checkEligibility($deliveredItemId, $customerId);
assert($eligibility['is_eligible'] === true, "Delivered item must be eligible for warranty");
assert($eligibility['warranty_months'] > 0, "Warranty months verified");
assert(!empty($eligibility['end_date']), "Warranty end date verified");

// Submit warranty claim
$claimSubmit = $warrantyService->submitClaim($deliveredItemId, $customerId, [
    'claim_reason' => 'Product not functioning',
    'issue_description' => 'Acoustic neural feedback disconnects after 5 minutes of operation.'
]);
assert($claimSubmit['success'] === true, "Warranty claim submission must succeed");
$claimNumber = $claimSubmit['claim_number'];
$claimId = (int)$claimSubmit['claim_id'];

// Check duplicate claim blocked
$dupClaim = $warrantyService->submitClaim($deliveredItemId, $customerId, [
    'claim_reason' => 'Hardware defect',
    'issue_description' => 'Trying to submit duplicate claim.'
]);
assert($dupClaim['success'] === false, "Duplicate active claim must be blocked");

// Customer cannot approve own claim
assert($warrantyService->updateClaimStatus($claimId, 'APPROVED', $customerId, 'CUSTOMER')['success'] === false);

// Admin diagnostic lifecycle: SUBMITTED -> UNDER_REVIEW -> APPROVED
assert($warrantyService->updateClaimStatus($claimId, 'UNDER_REVIEW', $adminId, 'ADMIN')['success'] === true);
assert($warrantyService->updateClaimStatus($claimId, 'APPROVED', $adminId, 'ADMIN', 'Approved', 'Replacement authorized under factory defect warranty')['success'] === true);

$claimDetails = $warrantyService->getClaimDetails($claimNumber, $customerId);
assert($claimDetails['status'] === 'APPROVED', "Claim approved");
assert($claimDetails['resolution_details'] !== null, "Resolution details verified");
echo "[PASS] Warranty eligibility, claim submission, duplicate prevention, and admin approval verified.\n";

// Clean up intruder user
$db->exec("DELETE FROM users WHERE id = {$unauthorizedUserId}");

echo "\n==================================================\n";
echo " RESULT: DAY 5 SUITE 100% VERIFIED & PRODUCTION READY 🚀\n";
echo "==================================================\n";
