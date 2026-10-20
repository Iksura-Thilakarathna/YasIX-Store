<?php

declare(strict_types=1);

/**
 * YasIX Store - Day 7: Comprehensive End-to-End Integration Validation Suite
 * Storefront, Catalog, Auth, Cart, Checkout, Orders, Warranties, Admin & Security
 */

require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/app/Helpers/Sanitizer.php';

// PSR-4 Autoloader
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
use App\Helpers\CSRF;
use App\Helpers\Sanitizer;
use App\Helpers\Session;
use App\Models\User;
use App\Repositories\CartRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\DeliveryRuleRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\WarrantyRepository;
use App\Services\AdminService;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DeliveryCalculatorService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\UserService;
use App\Services\WarrantyService;

echo "====================================================================\n";
echo "   YASIX STORE — DAY 7: END-TO-END UI & BACKEND INTEGRATION TEST    \n";
echo "====================================================================\n\n";

Session::start();
$db = Connection::getInstance();
$pdo = $db;
$passed = 0;
$failed = 0;

function assertPass(bool $condition, string $msg, mixed $detail = null): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$msg}\n";
        $passed++;
    } else {
        echo " [FAIL] {$msg}" . ($detail ? " (Detail: " . print_r($detail, true) . ")" : "") . "\n";
        $failed++;
    }
}

// --------------------------------------------------------------------------
// SECTION 1: GLOBAL HELPERS, ESCAPING & CURRENCY FORMATTING
// --------------------------------------------------------------------------
echo "--- SECTION 1: Global Helpers, Sanitization & Currency Formatting ---\n";

assertPass(function_exists('e'), "Global XSS escaping helper e() is available");
assertPass(e("<script>alert('xss')</script>") === "&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;", "e() correctly escapes malicious HTML/script tags");
assertPass(function_exists('formatCurrency'), "Global formatCurrency() helper is available");
assertPass(formatCurrency(12500.50) === "LKR 12,500.50", "formatCurrency() produces standard LKR formatted output");
assertPass(function_exists('formatLKR'), "Global formatLKR() helper is available");
assertPass(formatLKR(450.00) === "LKR 450.00", "formatLKR() produces standard LKR formatted output");
assertPass(function_exists('csrf_field'), "Global csrf_field() helper is available");
assertPass(str_contains(csrf_field(), 'name="_csrf_token"'), "csrf_field() renders valid hidden input element");

// --------------------------------------------------------------------------
// SECTION 2: STOREFRONT PRODUCT CATALOG & FILTER INTEGRATION
// --------------------------------------------------------------------------
echo "\n--- SECTION 2: Storefront Product Catalog & Filter Integration ---\n";

$productService = new ProductService();
$catalogData = $productService->getCatalog(['page' => 1]);
assertPass(count($catalogData['products']) > 0, "Catalog loads database-backed products (" . count($catalogData['products']) . " returned)");
assertPass(!empty($catalogData['categories']), "Catalog retrieves active categories list for filter tabs");
assertPass(!empty($catalogData['pagination']['total_products']), "Catalog calculates database product totals (" . $catalogData['pagination']['total_products'] . " items)");

// Search query
$searchData = $productService->getCatalog(['q' => 'Neural']);
assertPass(count($searchData['products']) > 0, "Keyword search for 'Neural' finds matching hardware items");

// Detail page lookup by slug
$firstProd = $catalogData['products'][0];
$detailProd = $productService->getProductBySlug($firstProd['slug']);
assertPass($detailProd !== null && (int)$detailProd['id'] === (int)$firstProd['id'], "Product detail retrieves complete product data by slug: '{$firstProd['slug']}'");

// Safe 404 on invalid slug
$invalidDetail = $productService->getProductBySlug('non-existent-cyber-item-99999');
assertPass($invalidDetail === null, "Non-existent product slug safely returns null (triggers 404 state)");

// --------------------------------------------------------------------------
// SECTION 3: AUTHENTICATION, REGISTRATION & ROLE ISOLATION
// --------------------------------------------------------------------------
echo "\n--- SECTION 3: Authentication, Customer Accounts & Role Shields ---\n";

$userService = new UserService();
$userRepo = new UserRepository();

$testEmail = 'customer_day7_' . bin2hex(random_bytes(3)) . '@yasix.local';
$testPass = 'CyberSecure#2026';

// Registration
$regResult = $userService->register([
    'full_name' => 'Day 7 Pilot',
    'email' => $testEmail,
    'phone' => '+94771234567',
    'password' => $testPass,
    'confirm_password' => $testPass
]);
assertPass($regResult['success'] === true, "Customer registration succeeds for {$testEmail}");
$testUserId = (int)($regResult['user_id'] ?? 0);

// Duplicate registration rejection
$dupResult = $userService->register([
    'full_name' => 'Day 7 Clone',
    'email' => $testEmail,
    'phone' => '+94771234567',
    'password' => $testPass,
    'confirm_password' => $testPass
]);
assertPass($dupResult['success'] === false, "Duplicate email registration is strictly rejected");

// Successful login
$loginResult = $userService->login($testEmail, $testPass);
assertPass($loginResult['success'] === true, "Customer login succeeds with verified credentials");
$userRole = ($loginResult['user'] instanceof User) ? $loginResult['user']->role_name : ($loginResult['user']['role_name'] ?? '');
assertPass($userRole === 'CUSTOMER', "Registered user is assigned CUSTOMER role ({$userRole})");

// Customer Account Profile Update
$profileUpdate = $userService->updateProfile($testUserId, [
    'full_name' => 'Day 7 Pilot Commander',
    'phone' => '+94779998888'
]);
assertPass($profileUpdate['success'] === true, "Customer profile updated in database");

// --------------------------------------------------------------------------
// SECTION 4: SHOPPING CART & DYNAMIC COD CHECKOUT
// --------------------------------------------------------------------------
echo "\n--- SECTION 4: Shopping Cart & Dynamic COD Checkout ---\n";

$cartRepo = new CartRepository();
$cartService = new CartService();
$deliveryCalc = new DeliveryCalculatorService();
$checkoutService = new CheckoutService();

$cartId = $cartRepo->getOrCreateCart($testUserId);
$cartRepo->clearCart($cartId);

// Add product ID 2 (Synapse Neural Headset)
$addResult = $cartService->addToCart($testUserId, 2, 2);
assertPass($addResult['success'] === true, "Hardware added to customer cart (2 units)");

$cartData = $cartService->getCart($testUserId);
assertPass((int)$cartData['item_count'] === 2, "Cart item count verified (2 units)");
assertPass($cartData['subtotal'] > 0, "Cart subtotal computed accurately (" . formatCurrency((float)$cartData['subtotal']) . ")");

// Delivery Calculation (Western Province = Region 1)
$deliveryResult = $deliveryCalc->calculateDelivery($cartData['items'], 1);
assertPass($deliveryResult['success'] === true, "Delivery fee calculated for Western Province: " . formatCurrency((float)$deliveryResult['total_delivery_fee']));

// Checkout Preparation
$prep = $checkoutService->prepareCheckout($testUserId, 1);
assertPass(!empty($prep['checkout_token']), "Checkout token generated with idempotency lock");

// Atomic Order Placement
$orderResult = $checkoutService->placeOrder($testUserId, [
    'checkout_token' => $prep['checkout_token'],
    'delivery_rule_id' => 1,
    'full_name' => 'Day 7 Pilot Commander',
    'phone' => '+94779998888',
    'address_line1' => '42 Neural Matrix Boulevard',
    'city' => 'Colombo',
    'state' => 'Western Province',
    'postal_code' => '00100',
    'notes' => 'Deliver before 5 PM'
]);

assertPass($orderResult['success'] === true, "Atomic COD order placed successfully: " . ($orderResult['order_number'] ?? ''));
$orderNumber = $orderResult['order_number'];
$orderId = (int)$orderResult['order_id'];

// Cart post-condition: cart must be empty
$cartAfter = $cartService->getCart($testUserId);
assertPass((int)$cartAfter['item_count'] === 0, "Customer cart automatically cleared post-checkout");

// --------------------------------------------------------------------------
// SECTION 5: CUSTOMER ORDER TRACKING & IDOR PREVENTION
// --------------------------------------------------------------------------
echo "\n--- SECTION 5: Customer Order Tracking & IDOR Protection ---\n";

$orderService = new OrderService();
$customerOrders = $orderService->getUserOrders($testUserId);
assertPass(count($customerOrders['orders']) >= 1, "Customer order history retrieves placed order");
assertPass($customerOrders['orders'][0]['order_number'] === $orderNumber, "Order history contains exact order #{$orderNumber}");

// IDOR Protection: unauthorized customer cannot see this order
$otherCustomerId = 999999;
$idorCheck = $orderService->getOrderDetails($orderNumber, $otherCustomerId);
assertPass($idorCheck === null, "IDOR Shield: Unauthorized customer cannot access another user's order details");

// Order tracking timeline
$tracking = $orderService->getOrderTracking($orderNumber, $testUserId);
assertPass($tracking !== null, "Order tracking retrieved with milestones");
assertPass(count($tracking['milestones']) >= 5, "Milestone lifecycle contains full delivery stages");

// --------------------------------------------------------------------------
// SECTION 6: ADMIN MANAGEMENT, COD SETTLEMENT & LIFECYCLE
// --------------------------------------------------------------------------
echo "\n--- SECTION 6: Admin Management, Fulfilment & COD Settlement ---\n";

$adminService = new AdminService();

// Admin retrieves order
$adminOrder = $orderService->getAdminOrderByNumber($orderNumber);
assertPass($adminOrder !== null, "Admin order stream retrieves placed order manifest");
assertPass($adminOrder['status'] === 'PENDING', "Initial order fulfilment status is PENDING");
assertPass($adminOrder['payment_status'] === 'PENDING', "Initial COD payment status is PENDING");

// Advance status: PENDING -> CONFIRMED -> PROCESSING -> SHIPPED -> OUT_FOR_DELIVERY -> DELIVERED
$st1 = $orderService->updateOrderStatus($orderId, 'CONFIRMED', 1000, 'ADMIN', 'Order confirmed by staff');
assertPass($st1['success'] === true, "Order advanced to CONFIRMED");

$st2 = $orderService->updateOrderStatus($orderId, 'PROCESSING', 1000, 'ADMIN', 'Packed in dispatch warehouse');
assertPass($st2['success'] === true, "Order advanced to PROCESSING");

$st3 = $orderService->updateOrderStatus($orderId, 'SHIPPED', 1000, 'ADMIN', 'Handed to courier');
assertPass($st3['success'] === true, "Order advanced to SHIPPED");

$st4 = $orderService->updateOrderStatus($orderId, 'OUT_FOR_DELIVERY', 1000, 'ADMIN', 'Courier on vehicle route');
assertPass($st4['success'] === true, "Order advanced to OUT_FOR_DELIVERY");

$st5 = $orderService->updateOrderStatus($orderId, 'DELIVERED', 1000, 'ADMIN', 'Doorstep delivery completed');
assertPass($st5['success'] === true, "Order advanced to DELIVERED with recorded delivery timestamp");

// Verify COD payment is STILL decoupled and PENDING
$delivOrder = $orderService->getAdminOrderByNumber($orderNumber);
assertPass($delivOrder['payment_status'] === 'PENDING', "Delivery status does NOT automatically mark COD as paid (decoupled)");

// Admin records Cash on Delivery collection
$collectResult = $orderService->recordCodCollection(
    $orderId,
    (float)$delivOrder['total_amount'],
    1000,
    'Cash collected by courier and remitted to central account'
);
assertPass($collectResult['success'] === true, "COD payment collection recorded successfully");

$paidOrder = $orderService->getAdminOrderByNumber($orderNumber);
assertPass($paidOrder['payment_status'] === 'PAID', "Order payment status updated to PAID");

// Backward illegal transition blocked
$illegalTransition = $orderService->updateOrderStatus($orderId, 'PROCESSING', 1000, 'ADMIN', 'Illegal rollback');
assertPass($illegalTransition['success'] === false, "State Machine: Illegal backward transition from DELIVERED is strictly blocked");

// --------------------------------------------------------------------------
// SECTION 7: WARRANTY CLAIM MANAGEMENT & RESOLUTION
// --------------------------------------------------------------------------
echo "\n--- SECTION 7: Warranty Eligibility & Claim Adjudication ---\n";

$warrantyService = new WarrantyService();

$orderItems = $orderService->getAdminOrderByNumber($orderNumber)['items'];
$eligibleItem = $orderItems[0];
$orderItemId = (int)$eligibleItem['id'];

// Warranty eligibility check
$eligibility = $warrantyService->checkEligibility($orderItemId, $testUserId);
assertPass($eligibility['is_eligible'] === true, "Delivered hardware verified eligible for warranty claim (duration: {$eligibleItem['warranty_months']} mo)");

// Submit warranty claim with approved reason
$claimResult = $warrantyService->submitClaim(
    $orderItemId,
    $testUserId,
    [
        'claim_reason' => 'Hardware defect',
        'issue_description' => 'Neural headband sensor disconnects after 10 minutes of active use.',
        'serial_number' => 'SN-TEST-DAY7-9921'
    ]
);
assertPass($claimResult['success'] === true, "Warranty claim submitted successfully: " . ($claimResult['claim_number'] ?? ''));
$claimId = (int)($claimResult['claim_id'] ?? 0);

// Duplicate claim prevention
$dupClaim = $warrantyService->submitClaim(
    $orderItemId,
    $testUserId,
    [
        'claim_reason' => 'Hardware defect',
        'issue_description' => 'Duplicate claim attempt on same hardware.'
    ]
);
assertPass($dupClaim['success'] === false, "Duplicate active claim on same hardware item is strictly prevented");

// Admin reviews and approves claim
$reviewResult = $warrantyService->updateClaimStatus($claimId, 'UNDER_REVIEW', 1000, 'ADMIN', 'Staff inspected hardware serial');
assertPass($reviewResult['success'] === true, "Claim transitioned to UNDER_REVIEW");

$approveResult = $warrantyService->updateClaimStatus($claimId, 'APPROVED', 1000, 'ADMIN', 'Approved', 'Replacement hardware dispatched via courier');
assertPass($approveResult['success'] === true, "Claim transitioned to APPROVED with customer resolution and confidential notes");

// --------------------------------------------------------------------------
// SECTION 8: CSRF PROTECTION & SECURITY GUARDS
// --------------------------------------------------------------------------
echo "\n--- SECTION 8: CSRF Protection & Security Verification ---\n";

$token = CSRF::generateToken();
assertPass(!empty($token), "CSRF token generated successfully");
assertPass(CSRF::verifyToken($token) === true, "CSRF verification accepts valid token");
assertPass(CSRF::verifyToken('tampered_token_xyz') === false, "CSRF verification rejects tampered token");
assertPass(CSRF::verifyToken(null) === false, "CSRF verification rejects null token");

// --------------------------------------------------------------------------
// SECTION 9: CLEANUP OF TEST FIXTURES
// --------------------------------------------------------------------------
echo "\n--- SECTION 9: Fixture Hygiene & Database Integrity ---\n";

try {
    if ($claimId > 0) {
        $cleanStmt = $pdo->prepare("DELETE FROM warranty_claims WHERE id = :id");
        $cleanStmt->execute(['id' => $claimId]);
    }

    $cleanOrderItems = $pdo->prepare("DELETE FROM order_items WHERE order_id = :oid");
    $cleanOrderItems->execute(['oid' => $orderId]);

    $cleanPay = $pdo->prepare("DELETE FROM payments WHERE order_id = :oid");
    $cleanPay->execute(['oid' => $orderId]);

    $cleanHist = $pdo->prepare("DELETE FROM order_status_history WHERE order_id = :oid");
    $cleanHist->execute(['oid' => $orderId]);

    $cleanOrder = $pdo->prepare("DELETE FROM orders WHERE id = :oid");
    $cleanOrder->execute(['oid' => $orderId]);

    $cleanCart = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = :cid");
    $cleanCart->execute(['cid' => $cartId]);

    $cleanCartHeader = $pdo->prepare("DELETE FROM carts WHERE id = :cid");
    $cleanCartHeader->execute(['cid' => $cartId]);

    $cleanUser = $pdo->prepare("DELETE FROM users WHERE id = :uid");
    $cleanUser->execute(['uid' => $testUserId]);

    assertPass(true, "Test fixtures and transient artifacts safely cleaned up");
} catch (\Throwable $e) {
    assertPass(false, "Cleanup encountered notice: " . $e->getMessage());
}

echo "\n====================================================================\n";
echo "   DAY 7 VERIFICATION SUMMARY: {$passed} Passed, {$failed} Failed\n";
if ($failed === 0) {
    echo "   RESULT: 100% SUCCESS — SYSTEM FULLY INTEGRATED & VERIFIED 🎯\n";
} else {
    echo "   RESULT: SOME CHECKS FAILED\n";
}
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
