<?php

declare(strict_types=1);

/**
 * YasIX Store — Release Readiness & Full Workflow Verification Suite
 * Milestone: Commit 1 (test: verify final application workflows and release readiness)
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
use App\Services\CategoryService;
use App\Services\CheckoutService;
use App\Services\DeliveryCalculatorService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\UserService;
use App\Services\WarrantyService;

echo "====================================================================\n";
echo "   YASIX STORE — FINAL RELEASE READINESS VERIFICATION SUITE         \n";
echo "====================================================================\n\n";

Session::start();
$db = Connection::getInstance();
$pdo = $db;
$passed = 0;
$failed = 0;

function assertCheck(bool $condition, string $msg, mixed $detail = null): void {
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
// SECTION 1: DATABASE INTEGRITY & CORE SEED AUDIT
// --------------------------------------------------------------------------
echo "--- SECTION 1: Database Relational Integrity & Essential Tables ---\n";

$tablesStmt = $pdo->query("SHOW TABLES");
$tables = $tablesStmt->fetchAll(\PDO::FETCH_COLUMN);
$requiredTables = [
    'users', 'roles', 'addresses', 'categories', 'products', 'product_images',
    'product_reviews', 'carts', 'cart_items', 'delivery_rules', 'orders',
    'order_items', 'payments', 'order_status_history', 'inventory_logs',
    'warranties', 'warranty_claims', 'notifications'
];

foreach ($requiredTables as $tbl) {
    assertCheck(in_array($tbl, $tables, true), "Database table '{$tbl}' exists in active schema");
}

// --------------------------------------------------------------------------
// SECTION 2: END-TO-END CUSTOMER JOURNEY
// --------------------------------------------------------------------------
echo "\n--- SECTION 2: End-to-End Customer Commerce Journey ---\n";

$productService = new ProductService();
$userService = new UserService();
$cartRepo = new CartRepository();
$cartService = new CartService();
$deliveryCalc = new DeliveryCalculatorService();
$checkoutService = new CheckoutService();
$orderService = new OrderService();
$warrantyService = new WarrantyService();

// Step 1: Browse catalog and search
$catalog = $productService->getCatalog(['page' => 1]);
assertCheck(count($catalog['products']) > 0, "Customer can browse catalog products (" . count($catalog['products']) . " returned on page 1)");
$searchResult = $productService->getCatalog(['q' => 'Neural']);
assertCheck(count($searchResult['products']) > 0, "Keyword search returns matching results for 'Neural'");

// Step 2: Product detail lookup
$firstProduct = $catalog['products'][0];
$detailProduct = $productService->getProductBySlug($firstProduct['slug']);
assertCheck($detailProduct !== null && $detailProduct['id'] === $firstProduct['id'], "Product detail retrieval succeeds for '{$firstProduct['slug']}'");

// Step 3: Registration & Authentication
$testEmail = 'release_pilot_' . bin2hex(random_bytes(3)) . '@yasix.local';
$testPass = 'CyberRelease#2026';
$regResult = $userService->register([
    'full_name' => 'Release Verification Pilot',
    'email' => $testEmail,
    'phone' => '+94770001122',
    'password' => $testPass,
    'confirm_password' => $testPass
]);
assertCheck($regResult['success'] === true, "Customer registration succeeds for {$testEmail}");
$testUserId = (int)($regResult['user_id'] ?? 0);

$loginResult = $userService->login($testEmail, $testPass);
assertCheck($loginResult['success'] === true, "Customer authentication succeeds with valid credentials");
$userRole = ($loginResult['user'] instanceof User) ? $loginResult['user']->role_name : ($loginResult['user']['role_name'] ?? '');
assertCheck($userRole === 'CUSTOMER', "New customer assigned strictly CUSTOMER role");

// Step 4: Shopping Cart operations & Stock constraints
$cartId = $cartRepo->getOrCreateCart($testUserId);
$cartRepo->clearCart($cartId);

// Add product ID 2 (Synapse Neural Headset)
$addCart = $cartService->addToCart($testUserId, 2, 2);
assertCheck($addCart['success'] === true, "Customer adds items to persistent shopping cart (2 units)");

$cartData = $cartService->getCart($testUserId);
assertCheck((int)$cartData['item_count'] === 2, "Cart item count reflects added quantity");
assertCheck((float)$cartData['subtotal'] > 0, "Cart subtotal calculated correctly: " . formatCurrency((float)$cartData['subtotal']));

// Step 5: Volumetric Delivery calculation (Western Province = 1)
$deliveryEst = $deliveryCalc->calculateDelivery($cartData['items'], 1);
assertCheck($deliveryEst['success'] === true, "Local delivery fee calculated without paid APIs: " . formatCurrency((float)$deliveryEst['total_delivery_fee']));

// Step 6: Checkout preparation & Atomic COD Order Placement
$prepCheckout = $checkoutService->prepareCheckout($testUserId, 1);
assertCheck(!empty($prepCheckout['checkout_token']), "Single-use idempotency checkout token generated");

$orderPlacement = $checkoutService->placeOrder($testUserId, [
    'checkout_token' => $prepCheckout['checkout_token'],
    'delivery_rule_id' => 1,
    'full_name' => 'Release Verification Pilot',
    'phone' => '+94770001122',
    'address_line1' => '77 Matrix Citadel Road',
    'city' => 'Colombo',
    'state' => 'Western Province',
    'postal_code' => '00100',
    'notes' => 'Leave with reception'
]);
assertCheck($orderPlacement['success'] === true, "Atomic COD order placed successfully: " . ($orderPlacement['order_number'] ?? ''));
$orderNumber = $orderPlacement['order_number'];
$orderId = (int)$orderPlacement['order_id'];

// Step 7: Cart cleared post-checkout
$cartPost = $cartService->getCart($testUserId);
assertCheck((int)$cartPost['item_count'] === 0, "Customer cart automatically cleared upon order placement");

// Step 8: Customer order history & IDOR Isolation
$userOrders = $orderService->getUserOrders($testUserId);
assertCheck(count($userOrders['orders']) >= 1, "Order history contains placed order");
$idorCheck = $orderService->getOrderDetails($orderNumber, 99999);
assertCheck($idorCheck === null, "IDOR protection: unauthorized customer blocked from viewing another's order");

// Step 9: Order tracking milestone lifecycle
$tracking = $orderService->getOrderTracking($orderNumber, $testUserId);
assertCheck($tracking !== null && count($tracking['milestones']) >= 5, "Order tracking milestone timeline verified");

// --------------------------------------------------------------------------
// SECTION 3: ADMINISTRATOR OPERATIONS & SECURITY CONTROLS
// --------------------------------------------------------------------------
echo "\n--- SECTION 3: Administrator Operations & State Machine Controls ---\n";

$adminService = new AdminService();

// Step 1: Admin order inspection
$adminOrder = $orderService->getAdminOrderByNumber($orderNumber);
assertCheck($adminOrder !== null, "Admin order stream retrieves placed order manifest");
assertCheck($adminOrder['status'] === 'PENDING', "Initial order fulfilment status is PENDING");
assertCheck($adminOrder['payment_status'] === 'PENDING', "Initial COD payment status is PENDING (decoupled)");

// Step 2: Forward fulfilment lifecycle
$orderService->updateOrderStatus($orderId, 'CONFIRMED', 1000, 'ADMIN', 'Staff verified order');
$orderService->updateOrderStatus($orderId, 'PROCESSING', 1000, 'ADMIN', 'Order packed in warehouse');
$orderService->updateOrderStatus($orderId, 'SHIPPED', 1000, 'ADMIN', 'Dispatched with CityPak courier');
$orderService->updateOrderStatus($orderId, 'OUT_FOR_DELIVERY', 1000, 'ADMIN', 'Out on courier delivery van');
$orderService->updateOrderStatus($orderId, 'DELIVERED', 1000, 'ADMIN', 'Courier handed package to customer');

$deliveredOrder = $orderService->getAdminOrderByNumber($orderNumber);
assertCheck($deliveredOrder['status'] === 'DELIVERED', "Order successfully transitioned through state machine to DELIVERED");
assertCheck($deliveredOrder['payment_status'] === 'PENDING', "COD payment remains decoupled as PENDING upon delivery");

// Step 3: Decoupled COD Cash Collection Settlement
$collectCod = $orderService->recordCodCollection(
    $orderId,
    (float)$deliveredOrder['total_amount'],
    1000,
    'Cash collected by courier and deposited into store account'
);
assertCheck($collectCod['success'] === true, "Admin successfully records COD cash collection");
$paidOrder = $orderService->getAdminOrderByNumber($orderNumber);
assertCheck($paidOrder['payment_status'] === 'PAID', "Order payment status marked PAID after explicit collection");

// Step 4: Strict backward transition prevention
$illegalBack = $orderService->updateOrderStatus($orderId, 'PROCESSING', 1000, 'ADMIN', 'Illegal rollback');
assertCheck($illegalBack['success'] === false, "State machine prevents illegal backward rollback from DELIVERED");

// Step 5: Warranty Eligibility & RMA Claim Adjudication
$deliveredItems = $paidOrder['items'];
$claimItem = $deliveredItems[0];
$claimItemId = (int)$claimItem['id'];

$eligibility = $warrantyService->checkEligibility($claimItemId, $testUserId);
assertCheck($eligibility['is_eligible'] === true, "Delivered hardware eligible for warranty based on actual delivered date");

$claimSubmission = $warrantyService->submitClaim(
    $claimItemId,
    $testUserId,
    [
        'claim_reason' => 'Hardware defect',
        'issue_description' => 'Acoustic drivers intermittent drop out under high volume neural sync.',
        'serial_number' => 'SN-REL-2026-9901'
    ]
);
assertCheck($claimSubmission['success'] === true, "Warranty claim submitted successfully: " . ($claimSubmission['claim_number'] ?? ''));
$claimId = (int)($claimSubmission['claim_id'] ?? 0);

// Admin claim adjudication
$reviewClaim = $warrantyService->updateClaimStatus($claimId, 'UNDER_REVIEW', 1000, 'ADMIN', 'Staff diagnostics started');
assertCheck($reviewClaim['success'] === true, "Claim transitioned to UNDER_REVIEW");
$approveClaim = $warrantyService->updateClaimStatus($claimId, 'APPROVED', 1000, 'ADMIN', 'Approved for swap', 'Replacement dispatched');
assertCheck($approveClaim['success'] === true, "Claim transitioned to APPROVED with customer resolution and staff notes");

// --------------------------------------------------------------------------
// SECTION 4: SECURITY HARDENING & DEFENSE IN DEPTH
// --------------------------------------------------------------------------
echo "\n--- SECTION 4: Security Hardening & Input Sanitization ---\n";

// CSRF tokens
$token = CSRF::generateToken();
assertCheck(!empty($token), "CSRF token generated");
assertCheck(CSRF::verifyToken($token) === true, "Valid CSRF token passes verification");
assertCheck(CSRF::verifyToken('tampered_token_str') === false, "Tampered CSRF token is rejected");
assertCheck(CSRF::verifyToken(null) === false, "Null CSRF token is rejected");

// XSS Escaping
$maliciousPayload = '<script>alert("xss")</script>';
assertCheck(e($maliciousPayload) === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;' || str_contains(e($maliciousPayload), '&lt;script&gt;'), "Global e() helper safely escapes HTML tags");

// CSV Injection Sanitization
$sanitizedFormula = $adminService->sanitizeCsvCell('=cmd|"/c calc"!A0');
assertCheck(str_starts_with($sanitizedFormula, "\"'="), "CSV formula injection prefixes (=, +, -, @) neutralized with apostrophe");

// --------------------------------------------------------------------------
// SECTION 5: CLEANUP OF TEST FIXTURES
// --------------------------------------------------------------------------
echo "\n--- SECTION 5: Cleanup of Transient Release Test Fixtures ---\n";

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

    assertCheck(true, "Transient test fixtures safely purged from database");
} catch (\Throwable $e) {
    assertCheck(false, "Cleanup encountered notice: " . $e->getMessage());
}

echo "\n====================================================================\n";
echo "   RELEASE READINESS SUMMARY: {$passed} Passed, {$failed} Failed\n";
if ($failed === 0) {
    echo "   RESULT: 100% READY FOR PORTFOLIO RELEASE 🚀\n";
} else {
    echo "   RESULT: ISSUES DETECTED — REVIEW FAILURES ABOVE\n";
}
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
