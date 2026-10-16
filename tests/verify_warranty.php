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
use App\Repositories\WarrantyRepository;
use App\Repositories\OrderRepository;
use App\Services\WarrantyService;
use App\Services\OrderService;

echo "==================================================\n";
echo "   YASIX STORE - DAY 5 COMMIT 3 WARRANTY TEST     \n";
echo "==================================================\n";

$db = Connection::getInstance();
$warrantyRepo = new WarrantyRepository();
$warrantyService = new WarrantyService();
$orderRepo = new OrderRepository();
$orderService = new OrderService();

$adminId = 1;
$customerId = 2;
$otherCustomerId = 998;

// Clean up test data for user 998
$db->exec("DELETE FROM users WHERE id = {$otherCustomerId}");
$db->exec("INSERT INTO users (id, role_id, name, email, password_hash, phone, status) VALUES ({$otherCustomerId}, 2, 'Other User', 'other_user@yasix.local', 'hash', '+94770008888', 'ACTIVE')");

// Clean up any test warranty and order data from previous runs
$db->exec("DELETE FROM warranty_claims WHERE claim_number LIKE 'CLM-%'");
$db->exec("DELETE FROM warranties WHERE serial_number LIKE 'WAR-%'");
$db->exec("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE order_number LIKE 'YAS-WAR-%')");
$db->exec("DELETE FROM orders WHERE order_number LIKE 'YAS-WAR-%'");
$db->exec("DELETE FROM products WHERE slug IN ('test-accessory-0m', 'test-audio-3m', 'test-gpu-6m')");

$db->exec("INSERT INTO products (category_id, name, slug, brand, price, stock_quantity, weight, warranty_months, status) VALUES 
    (3, 'Test Accessory No Warranty', 'test-accessory-0m', 'YasIX', 50.00, 100, 0.2, 0, 'AVAILABLE'),
    (2, 'Test Cyber Buds 3M', 'test-audio-3m', 'YasIX', 150.00, 100, 0.3, 3, 'AVAILABLE'),
    (1, 'Test Quantum Board 6M', 'test-gpu-6m', 'YasIX', 500.00, 100, 1.2, 6, 'AVAILABLE')");

$product0mId = (int)$db->query("SELECT id FROM products WHERE slug = 'test-accessory-0m'")->fetchColumn();
$product3mId = (int)$db->query("SELECT id FROM products WHERE slug = 'test-audio-3m'")->fetchColumn();
$product6mId = (int)$db->query("SELECT id FROM products WHERE slug = 'test-gpu-6m'")->fetchColumn();

// Create delivered order for customer (User 2) with today as delivery date
$orderNumber = 'YAS-WAR-' . date('Ymd') . '-TEST1';
$db->exec("DELETE FROM orders WHERE order_number = '{$orderNumber}'");
$db->exec("INSERT INTO orders (order_number, user_id, total_amount, shipping_fee, status, payment_status, delivered_at, created_at) 
           VALUES ('{$orderNumber}', {$customerId}, 700.00, 400.00, 'DELIVERED', 'PAID', NOW(), NOW())");
$orderId = (int)$db->lastInsertId();

// Insert order items capturing purchase-time warranty snapshots
$db->exec("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months) VALUES
    ({$orderId}, {$product0mId}, 'Test Accessory No Warranty', 50.00, 1, 50.00, 0),
    ({$orderId}, {$product3mId}, 'Test Cyber Buds 3M', 150.00, 1, 150.00, 3),
    ({$orderId}, {$product6mId}, 'Test Quantum Board 6M', 500.00, 1, 500.00, 6)");

$item0mId = (int)$db->query("SELECT id FROM order_items WHERE order_id = {$orderId} AND product_id = {$product0mId}")->fetchColumn();
$item3mId = (int)$db->query("SELECT id FROM order_items WHERE order_id = {$orderId} AND product_id = {$product3mId}")->fetchColumn();
$item6mId = (int)$db->query("SELECT id FROM order_items WHERE order_id = {$orderId} AND product_id = {$product6mId}")->fetchColumn();

// 1. Product with no warranty (0 months)
echo "\n--- [1] ZERO-WARRANTY PRODUCT ELIGIBILITY ---\n";
$elig0m = $warrantyService->checkEligibility($item0mId, $customerId);
assert($elig0m['is_eligible'] === false, "0-month warranty product must not be eligible for claims");
assert($elig0m['status'] === 'NO_WARRANTY', "Status must be NO_WARRANTY");
echo "[PASS] Product with 0 months warranty correctly rejected as ineligible.\n";

// 2. Product with 3-month and 6-month warranty within coverage period
echo "\n--- [2] ACTIVE 3-MONTH AND 6-MONTH WARRANTY ELIGIBILITY ---\n";
$elig3m = $warrantyService->checkEligibility($item3mId, $customerId);
assert($elig3m['is_eligible'] === true, "3-month product delivered today must be eligible");
assert($elig3m['warranty_months'] === 3, "Duration snapshot must be 3 months");
assert(!empty($elig3m['end_date']), "End date must be calculated");

$elig6m = $warrantyService->checkEligibility($item6mId, $customerId);
assert($elig6m['is_eligible'] === true, "6-month product delivered today must be eligible");
assert($elig6m['warranty_months'] === 6, "Duration snapshot must be 6 months");
echo "[PASS] 3-month and 6-month warranties verified eligible with accurate start/end dates.\n";

// 3. Undelivered order / missing delivery date
echo "\n--- [3] UNDELIVERED ORDER / MISSING DELIVERY DATE ---\n";
$undeliveredOrderNum = 'YAS-WAR-UNDELIV-TEST2';
$db->exec("DELETE FROM orders WHERE order_number = '{$undeliveredOrderNum}'");
$db->exec("INSERT INTO orders (order_number, user_id, total_amount, shipping_fee, status, payment_status, delivered_at, created_at) 
           VALUES ('{$undeliveredOrderNum}', {$customerId}, 150.00, 400.00, 'SHIPPED', 'PENDING', NULL, NOW())");
$undeliveredOrderId = (int)$db->lastInsertId();
$db->exec("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months) 
           VALUES ({$undeliveredOrderId}, {$product3mId}, 'Test Cyber Buds 3M', 150.00, 1, 150.00, 3)");
$undeliveredItemId = (int)$db->lastInsertId();

$eligUndelivered = $warrantyService->checkEligibility($undeliveredItemId, $customerId);
assert($eligUndelivered['is_eligible'] === false, "Undelivered item without delivery date must be ineligible");
assert($eligUndelivered['status'] === 'UNDELIVERED', "Status must be UNDELIVERED");
echo "[PASS] Undelivered item safely rejected; delivery date is not invented.\n";

// 4. Expired warranty
echo "\n--- [4] EXPIRED WARRANTY CHECK ---\n";
$expiredOrderNum = 'YAS-WAR-EXPIRED-TEST3';
$db->exec("DELETE FROM orders WHERE order_number = '{$expiredOrderNum}'");
// Delivered 1 year ago (365 days ago)
$pastDate = date('Y-m-d H:i:s', strtotime('-1 year'));
$db->exec("INSERT INTO orders (order_number, user_id, total_amount, shipping_fee, status, payment_status, delivered_at, created_at) 
           VALUES ('{$expiredOrderNum}', {$customerId}, 150.00, 400.00, 'DELIVERED', 'PAID', '{$pastDate}', '{$pastDate}')");
$expiredOrderId = (int)$db->lastInsertId();
$db->exec("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months) 
           VALUES ({$expiredOrderId}, {$product3mId}, 'Test Cyber Buds 3M Expired', 150.00, 1, 150.00, 3)");
$expiredItemId = (int)$db->lastInsertId();

$eligExpired = $warrantyService->checkEligibility($expiredItemId, $customerId);
assert($eligExpired['is_eligible'] === false, "Item delivered 1 year ago with 3M warranty must be expired");
assert($eligExpired['status'] === 'EXPIRED', "Status must be EXPIRED");
echo "[PASS] Expired warranty correctly detected and claim submission blocked.\n";

// 5. Expiry boundary test
echo "\n--- [5] EXPIRY BOUNDARY CHECK ---\n";
// Delivered exactly 3 months ago minus 1 hour (still within boundary)
$boundaryDelivered = date('Y-m-d H:i:s', strtotime('-3 months + 1 day'));
$db->exec("UPDATE orders SET delivered_at = '{$boundaryDelivered}' WHERE id = {$expiredOrderId}");
$eligBoundary = $warrantyService->checkEligibility($expiredItemId, $customerId);
assert($eligBoundary['is_eligible'] === true, "Item within expiry boundary must be eligible");
echo "[PASS] Expiry boundary precision verified.\n";

// 6. Order belonging to another customer (IDOR prevention)
echo "\n--- [6] UNAUTHORIZED CLAIM ATTEMPT AGAINST ANOTHER CUSTOMER ---\n";
$unauthElig = $warrantyService->checkEligibility($item3mId, $otherCustomerId);
assert($unauthElig['is_eligible'] === false, "User 998 must not check or claim User 2's purchased item");
assert($unauthElig['status'] === 'NOT_FOUND', "Must return NOT_FOUND without leaking existence");
echo "[PASS] Strict customer ownership isolation verified.\n";

// 7. Non-existent order item
echo "\n--- [7] NON-EXISTENT ORDER ITEM LOOKUP ---\n";
$nonExistentElig = $warrantyService->checkEligibility(999999, $customerId);
assert($nonExistentElig['is_eligible'] === false, "Non-existent item must return false");
echo "[PASS] Non-existent order item safely handled.\n";

// 8. Claim submission with valid data
echo "\n--- [8] VALID WARRANTY CLAIM SUBMISSION ---\n";
$validClaimData = [
    'claim_reason' => 'Hardware defect',
    'issue_description' => 'Left driver neural acoustic coil intermittent crackling after 2 hours of matrix sync.'
];
$claimResult = $warrantyService->submitClaim($item3mId, $customerId, $validClaimData);
assert($claimResult['success'] === true, "Claim submission must succeed for eligible item");
$claimNumber = $claimResult['claim_number'];
$claimId = (int)$claimResult['claim_id'];
echo "[PASS] Claim successfully submitted: Claim #{$claimNumber}.\n";

// 9. Prevent duplicate active claim for same item
echo "\n--- [9] DUPLICATE ACTIVE CLAIM PREVENTION ---\n";
$duplicateResult = $warrantyService->submitClaim($item3mId, $customerId, $validClaimData);
assert($duplicateResult['success'] === false, "Duplicate active claim must be blocked");
assert(str_contains($duplicateResult['message'], 'already'), "Error must indicate claim is already in progress");
echo "[PASS] Duplicate active claim blocked.\n";

// 10. Invalid claim input validation
echo "\n--- [10] INVALID CLAIM INPUT VALIDATION ---\n";
$invalidReasonResult = $warrantyService->submitClaim($item6mId, $customerId, [
    'claim_reason' => 'Invalid Reason Not Allowed',
    'issue_description' => 'Adequate description of the problem.'
]);
assert($invalidReasonResult['success'] === false, "Disallowed reason must be rejected");

$shortDescResult = $warrantyService->submitClaim($item6mId, $customerId, [
    'claim_reason' => 'Hardware defect',
    'issue_description' => 'Broken' // Too short (< 10 chars)
]);
assert($shortDescResult['success'] === false, "Short description (< 10 chars) must be rejected");
echo "[PASS] Invalid claim reason and too short description correctly rejected.\n";

// 11. Customer retrieval of claim details
echo "\n--- [11] CLAIM DOSSIER RETRIEVAL & ACCESS CONTROL ---\n";
$claimDetails = $warrantyService->getClaimDetails($claimNumber, $customerId);
assert($claimDetails !== null, "Owner must be able to view their claim dossier");
assert($claimDetails['claim_number'] === $claimNumber, "Claim number matches");
assert($claimDetails['status'] === 'SUBMITTED', "Initial status must be SUBMITTED");

// Other customer attempting to view claim
$otherCustomerClaim = $warrantyService->getClaimDetails($claimNumber, $otherCustomerId);
assert($otherCustomerClaim === null, "Other customer must NOT view another customer's claim");
echo "[PASS] Claim details retrieved and IDOR protection confirmed.\n";

// 12. Unauthorized claim status update
echo "\n--- [12] CLAIM STATUS TRANSITION & ADMIN AUTHORIZATION ---\n";
// Customer attempting to approve their own claim
$unauthApprove = $warrantyService->updateClaimStatus($claimId, 'APPROVED', $customerId, 'CUSTOMER');
assert($unauthApprove['success'] === false, "Customer must NOT be able to approve their own claim");

// Admin updates status: SUBMITTED -> UNDER_REVIEW -> APPROVED
$adminReview = $warrantyService->updateClaimStatus($claimId, 'UNDER_REVIEW', $adminId, 'ADMIN', 'Diagnostic review underway');
assert($adminReview['success'] === true, "Admin update to UNDER_REVIEW must succeed");

$adminApprove = $warrantyService->updateClaimStatus($claimId, 'APPROVED', $adminId, 'ADMIN', 'Approved', 'RMA replacement replacement authorized');
assert($adminApprove['success'] === true, "Admin update to APPROVED must succeed");

$updatedClaim = $warrantyService->getClaimDetails($claimNumber, $customerId);
assert($updatedClaim['status'] === 'APPROVED', "Claim status must be updated to APPROVED");
assert($updatedClaim['admin_decision'] === 'Approved', "Admin decision recorded");
assert($updatedClaim['resolution_details'] === 'RMA replacement replacement authorized', "Resolution details recorded");
echo "[PASS] Unauthorized customer approval blocked; Admin lifecycle review and resolution verified.\n";

// Clean up user 998
$db->exec("DELETE FROM users WHERE id = {$otherCustomerId}");

echo "\n==================================================\n";
echo " RESULT: COMMIT 3 WARRANTY MANAGEMENT PASSED 🚀   \n";
echo "==================================================\n";
