<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/app/Helpers/Sanitizer.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Database\Connection;
use App\Services\OrderService;
use App\Services\UserService;
use App\Services\WarrantyService;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\WarrantyRepository;

echo "=======================================================\n";
echo " YasIX-Store - Day 6: Admin Order, Customer & Warranty Test\n";
echo "=======================================================\n\n";

$db = Connection::getInstance();
$orderService = new OrderService();
$userService = new UserService();
$warrantyService = new WarrantyService();

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition, &$passed, &$failed): void {
    if ($condition) {
        echo " [PASS] {$desc}\n";
        $passed++;
    } else {
        echo " [FAIL] {$desc}\n";
        $failed++;
    }
}

// 1. Setup Test Admin and Customer
echo "--- 1. Setting Up Test Accounts ---\n";
// Ensure test admin
$adminStmt = $db->prepare("SELECT id FROM users WHERE email = 'admin_mgmt_test@yasix.local' LIMIT 1");
$adminStmt->execute();
$adminId = $adminStmt->fetchColumn();

if (!$adminId) {
    $db->prepare("INSERT INTO users (name, email, password_hash, role_id, status, created_at, updated_at)
        VALUES ('Management Admin', 'admin_mgmt_test@yasix.local', :hash, (SELECT id FROM roles WHERE name = 'ADMIN'), 'ACTIVE', NOW(), NOW())")
       ->execute(['hash' => password_hash('AdminPass@123', PASSWORD_BCRYPT)]);
    $adminId = (int)$db->lastInsertId();
} else {
    $adminId = (int)$adminId;
}

// Ensure test customer
$custEmail = 'customer_mgmt_test@yasix.local';
$custStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$custStmt->execute(['email' => $custEmail]);
$customerId = $custStmt->fetchColumn();

if (!$customerId) {
    $db->prepare("INSERT INTO users (name, email, phone, password_hash, role_id, status, created_at, updated_at)
        VALUES ('Test Customer Corp', :email, '0771234567', :hash, (SELECT id FROM roles WHERE name = 'CUSTOMER'), 'ACTIVE', NOW(), NOW())")
       ->execute(['email' => $custEmail, 'hash' => password_hash('CustomerPass@123', PASSWORD_BCRYPT)]);
    $customerId = (int)$db->lastInsertId();
} else {
    $customerId = (int)$customerId;
}

assertTest("Admin and customer fixtures initialized", $adminId > 0 && $customerId > 0, $passed, $failed);

// 2. Setup Test Product and Address
$prodStmt = $db->prepare("SELECT id, stock_quantity FROM products WHERE status = 'AVAILABLE' LIMIT 1");
$prodStmt->execute();
$product = $prodStmt->fetch(PDO::FETCH_ASSOC);

$addrStmt = $db->prepare("SELECT id FROM addresses WHERE user_id = :uid LIMIT 1");
$addrStmt->execute(['uid' => $customerId]);
$addressId = $addrStmt->fetchColumn();
if (!$addressId) {
    $db->prepare("INSERT INTO addresses (user_id, address_line1, city, state, postal_code, country)
        VALUES (:uid, '42 Cyber Street', 'Colombo', 'Western', '00100', 'Sri Lanka')")
       ->execute(['uid' => $customerId]);
    $addressId = (int)$db->lastInsertId();
} else {
    $addressId = (int)$addressId;
}

// Create fresh order for testing
$orderNumber = 'TEST-ORD-' . strtoupper(substr(uniqid(), -6));
$orderTotal = 45000.00;
$db->prepare("
    INSERT INTO orders (order_number, user_id, address_id, status, payment_status, payment_method, shipping_fee, total_amount, created_at, updated_at)
    VALUES (:num, :uid, :addr, 'PENDING', 'PENDING', 'COD', 350.00, :total, NOW(), NOW())
")->execute([
    'num' => $orderNumber,
    'uid' => $customerId,
    'addr' => $addressId,
    'total' => $orderTotal
]);
$orderId = (int)$db->lastInsertId();

$initialStock = (int)$product['stock_quantity'];
$db->prepare("
    INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months, created_at)
    VALUES (:oid, :pid, 'Cyber Audio Headset Mk II', 44650.00, 1, 44650.00, 6, NOW())
")->execute([
    'oid' => $orderId,
    'pid' => $product['id']
]);
$orderItemId = (int)$db->lastInsertId();

$db->prepare("
    INSERT INTO payments (order_id, amount, payment_method, status, transaction_reference, created_at)
    VALUES (:oid, :amount, 'COD', 'PENDING', :ref, NOW())
")->execute([
    'oid' => $orderId,
    'amount' => $orderTotal,
    'ref' => 'PAY-REF-' . $orderNumber
]);

echo "\n--- 2. Testing Order Fulfilment Administration ---\n";

// Test listing orders
$ordersData = $orderService->getAdminOrders(['search' => $orderNumber]);
assertTest("Order listed in admin stream", !empty($ordersData['orders']) && $ordersData['orders'][0]['order_number'] === $orderNumber, $passed, $failed);

// Test order detail retrieval with snapshots
$orderDetail = $orderService->getAdminOrderByNumber($orderNumber);
assertTest("Order detail retrieves complete manifest & snapshots", 
    $orderDetail !== null && 
    $orderDetail['order_number'] === $orderNumber && 
    count($orderDetail['items']) > 0 && 
    $orderDetail['items'][0]['warranty_months'] == 6, 
    $passed, $failed
);

// Test Status Progression: PENDING -> CONFIRMED
$res1 = $orderService->updateOrderStatus($orderId, 'CONFIRMED', $adminId, 'ADMIN', 'Customer phone verified');
assertTest("Order advances PENDING -> CONFIRMED", $res1['success'] === true, $passed, $failed);

// Test Status Progression: CONFIRMED -> PROCESSING
$res2 = $orderService->updateOrderStatus($orderId, 'PROCESSING', $adminId, 'ADMIN', 'Dispatched to warehouse packing');
assertTest("Order advances CONFIRMED -> PROCESSING", $res2['success'] === true, $passed, $failed);

// Test Status Progression: PROCESSING -> SHIPPED
$res3 = $orderService->updateOrderStatus($orderId, 'SHIPPED', $adminId, 'ADMIN', 'Handed over to CityPak Courier');
assertTest("Order advances PROCESSING -> SHIPPED", $res3['success'] === true, $passed, $failed);

// Test Status Progression: SHIPPED -> OUT_FOR_DELIVERY
$res4 = $orderService->updateOrderStatus($orderId, 'OUT_FOR_DELIVERY', $adminId, 'ADMIN', 'Courier rider dispatched');
assertTest("Order advances SHIPPED -> OUT_FOR_DELIVERY", $res4['success'] === true, $passed, $failed);

// Test Status Progression: OUT_FOR_DELIVERY -> DELIVERED
$res5 = $orderService->updateOrderStatus($orderId, 'DELIVERED', $adminId, 'ADMIN', 'Delivered to customer doorstep');
assertTest("Order advances OUT_FOR_DELIVERY -> DELIVERED", $res5['success'] === true, $passed, $failed);

// Verify delivery does NOT automatically mark COD as paid
$orderAfterDelivered = $orderService->getAdminOrderByNumber($orderNumber);
assertTest("Delivery status does NOT automatically mark COD as paid", 
    $orderAfterDelivered['status'] === 'DELIVERED' && 
    $orderAfterDelivered['payment_status'] === 'PENDING' &&
    $orderAfterDelivered['delivered_at'] !== null, 
    $passed, $failed
);

// Test Invalid Status Jump (DELIVERED cannot jump back to PENDING)
$resInvalid = $orderService->updateOrderStatus($orderId, 'PENDING', $adminId, 'ADMIN', 'Illegal rewind');
assertTest("Illegal transition backwards from DELIVERED is strictly rejected", $resInvalid['success'] === false, $passed, $failed);

// Verify Lifecycle Audit Log
$history = (new OrderRepository())->getOrderStatusHistory($orderId);
assertTest("Lifecycle audit history records transitions", count($history) >= 5, $passed, $failed);

echo "\n--- 3. Testing COD Payment Settlement Ledger ---\n";

// Test listing payments
$paymentsData = $orderService->getAdminPayments(['search' => $orderNumber]);
assertTest("Payment record listed in settlement ledger", !empty($paymentsData['payments']), $passed, $failed);

// Test record COD collection
$colRes = $orderService->recordCodCollection($orderId, $orderTotal, $adminId, 'Courier cash remittance verified via receipt #CR-9081');
assertTest("COD payment collection recorded successfully", $colRes['success'] === true, $passed, $failed);

// Verify order payment status is now PAID and payment status is COMPLETED
$orderPaid = $orderService->getAdminOrderByNumber($orderNumber);
assertTest("Order payment_status is updated to PAID", $orderPaid['payment_status'] === 'PAID', $passed, $failed);
assertTest("Payment record status is updated to COMPLETED with collected timestamp", 
    $orderPaid['payment_record_status'] === 'COMPLETED' && 
    !empty($orderPaid['collected_at']) &&
    $orderPaid['collected_by_admin'] === 'Management Admin', 
    $passed, $failed
);

// Test duplicate collection rejection
$colDup = $orderService->recordCodCollection($orderId, $orderTotal, $adminId, 'Duplicate attempt');
assertTest("Duplicate COD collection attempt is strictly rejected", $colDup['success'] === false, $passed, $failed);

// Test invalid amount (> total or <= 0)
$colOver = $orderService->recordCodCollection($orderId, 999999.00, $adminId);
assertTest("Excessive collection amount is rejected", $colOver['success'] === false, $passed, $failed);

echo "\n--- 4. Testing Order Cancellation & Stock Restoration ---\n";
// Create another order to test cancellation
$cancelOrderNumber = 'TEST-ORD-CANC-' . strtoupper(substr(uniqid(), -5));
$db->prepare("
    INSERT INTO orders (order_number, user_id, address_id, status, payment_status, payment_method, shipping_fee, total_amount, created_at, updated_at)
    VALUES (:num, :uid, :addr, 'PENDING', 'PENDING', 'COD', 350.00, 1350.00, NOW(), NOW())
")->execute([
    'num' => $cancelOrderNumber,
    'uid' => $customerId,
    'addr' => $addressId
]);
$cancelOrderId = (int)$db->lastInsertId();

$db->prepare("
    INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months, created_at)
    VALUES (:oid, :pid, 'Test Hardware', 1000.00, 2, 2000.00, 0, NOW())
")->execute([
    'oid' => $cancelOrderId,
    'pid' => $product['id']
]);

// Decrease stock to simulate placement
$db->prepare("UPDATE products SET stock_quantity = stock_quantity - 2 WHERE id = :id")->execute(['id' => $product['id']]);
$stockBeforeCancel = (int)$db->query("SELECT stock_quantity FROM products WHERE id = {$product['id']}")->fetchColumn();

// Cancel order via admin updateOrderStatus
$cancelRes = $orderService->updateOrderStatus($cancelOrderId, 'CANCELLED', $adminId, 'ADMIN', 'Customer requested cancellation prior to dispatch');
assertTest("Order successfully cancelled by admin", $cancelRes['success'] === true, $passed, $failed);

$stockAfterCancel = (int)$db->query("SELECT stock_quantity FROM products WHERE id = {$product['id']}")->fetchColumn();
assertTest("Product stock restored (+2) upon order cancellation", $stockAfterCancel === ($stockBeforeCancel + 2), $passed, $failed);

echo "\n--- 5. Testing Customer Management & Security Shields ---\n";

// Test listing customers
$customersData = $userService->getAdminCustomers(['search' => 'customer_mgmt_test']);
assertTest("Customer found in admin directory", !empty($customersData['customers']), $passed, $failed);

// Test customer profile details
$customerProfile = $userService->getCustomerDetails($customerId);
assertTest("Customer details include lifetime metrics & order history", 
    $customerProfile !== null && 
    (int)$customerProfile['total_orders'] >= 1 && 
    count($customerProfile['orders']) >= 1, 
    $passed, $failed
);

// Test customer suspension
$suspendRes = $userService->setCustomerStatus($customerId, 'SUSPENDED', $adminId);
assertTest("Customer account suspended successfully", $suspendRes['success'] === true && $suspendRes['status'] === 'SUSPENDED', $passed, $failed);

// Verify suspended customer login is blocked
$loginAttempt = $userService->login($custEmail, 'CustomerPass@123');
assertTest("Suspended customer is blocked from login", 
    $loginAttempt['success'] === false && 
    str_contains($loginAttempt['error'], 'suspended'), 
    $passed, $failed
);

// Test customer reactivation
$reactivateRes = $userService->setCustomerStatus($customerId, 'ACTIVE', $adminId);
assertTest("Customer account reactivated to ACTIVE", $reactivateRes['success'] === true && $reactivateRes['status'] === 'ACTIVE', $passed, $failed);

// Verify customer login succeeds again
$loginAgain = $userService->login($custEmail, 'CustomerPass@123');
assertTest("Reactivated customer can log in successfully", $loginAgain['success'] === true, $passed, $failed);

// Test Security Shield: Administrator cannot suspend themselves
$selfSuspend = $userService->setCustomerStatus($adminId, 'SUSPENDED', $adminId);
assertTest("Self-suspension by administrator is strictly blocked", 
    $selfSuspend['success'] === false && 
    str_contains($selfSuspend['error'], 'Security Violation'), 
    $passed, $failed
);

// Test Security Shield: Modifying another administrator account through customer management is blocked
$targetAdminStmt = $db->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'ADMIN') AND id != {$adminId} LIMIT 1");
$otherAdminId = $targetAdminStmt->fetchColumn();
if ($otherAdminId) {
    $adminTamper = $userService->setCustomerStatus((int)$otherAdminId, 'SUSPENDED', $adminId);
    assertTest("Admin privilege shield blocks altering admin accounts via customer terminal", 
        $adminTamper['success'] === false && 
        str_contains($adminTamper['error'], 'Security Policy'), 
        $passed, $failed
    );
}

echo "\n--- 6. Testing Warranty Claims Administration ---\n";

// Register warranty for the delivered order item
$warrantyId = (new WarrantyRepository())->findOrCreateWarranty(
    $orderItemId,
    $customerId,
    (int)$product['id'],
    date('Y-m-d'),
    date('Y-m-d', strtotime('+6 months'))
);

// Customer submits a warranty claim
$claimNumber = 'CLM-TEST-' . strtoupper(substr(uniqid(), -5));
$createClaimRes = (new WarrantyRepository())->createClaimWithTransaction(
    $warrantyId,
    $customerId,
    $claimNumber,
    'Hardware defect',
    'Left audio driver crackling and dropping sound intermittently.'
);
assertTest("Warranty claim fixture created", $createClaimRes['success'] === true, $passed, $failed);
$claimId = (int)$createClaimRes['claim_id'];

// Test listing claims
$claimsList = $warrantyService->getAdminClaims(['search' => $claimNumber]);
assertTest("Claim listed in admin warranty registry", !empty($claimsList['claims']) && $claimsList['claims'][0]['claim_number'] === $claimNumber, $passed, $failed);

// Test claim detail retrieval
$claimDetail = $warrantyService->getAdminClaimById($claimId);
assertTest("Claim detail retrieves hardware info, customer description, and dates", 
    $claimDetail !== null && 
    $claimDetail['claim_number'] === $claimNumber && 
    $claimDetail['warranty_months'] == 6, 
    $passed, $failed
);

// Adjudication Transition 1: SUBMITTED -> UNDER_REVIEW
$adj1 = $warrantyService->updateClaimStatus(
    $claimId,
    'UNDER_REVIEW',
    $adminId,
    'ADMIN',
    'Claim received and assigned to hardware diagnostics team',
    'Your RMA claim has been assigned to technical inspection. Please ensure the unit is safely packed.',
    'Assigned to Tech Agent Bandara for serial verification with overseas supplier.'
);
assertTest("Claim transitioned SUBMITTED -> UNDER_REVIEW", $adj1['success'] === true, $passed, $failed);

// Verify internal notes and reviewer recorded
$claimAfterAdj1 = $warrantyService->getAdminClaimById($claimId);
assertTest("Internal audit notes and reviewer recorded in database", 
    $claimAfterAdj1['status'] === 'UNDER_REVIEW' && 
    str_contains($claimAfterAdj1['internal_notes'], 'Tech Agent Bandara') && 
    $claimAfterAdj1['reviewed_by_user_id'] == $adminId, 
    $passed, $failed
);

// Adjudication Transition 2: UNDER_REVIEW -> APPROVED
$adj2 = $warrantyService->updateClaimStatus(
    $claimId,
    'APPROVED',
    $adminId,
    'ADMIN',
    'Warranty Replacement Approved',
    'A brand new replacement headset will be dispatched to your delivery address.',
    'Supplier confirmed known batch defect. Supplier credit code: SUP-RET-901.'
);
assertTest("Claim transitioned UNDER_REVIEW -> APPROVED", $adj2['success'] === true, $passed, $failed);

// Adjudication Transition 3: APPROVED -> RESOLVED
$adj3 = $warrantyService->updateClaimStatus(
    $claimId,
    'RESOLVED',
    $adminId,
    'ADMIN',
    'Replacement unit dispatched',
    'Replacement package has been sent via courier #CP-987654.',
    'Replacement SKU deducted from buffer inventory and dispatched.'
);
assertTest("Claim transitioned APPROVED -> RESOLVED", $adj3['success'] === true, $passed, $failed);

// Adjudication Transition 4: RESOLVED -> CLOSED
$adj4 = $warrantyService->updateClaimStatus(
    $claimId,
    'CLOSED',
    $adminId,
    'ADMIN',
    'Claim Case Closed',
    'Customer confirmed receipt of replacement hardware. Case resolved.',
    'Case successfully resolved and archived.'
);
assertTest("Claim transitioned RESOLVED -> CLOSED", $adj4['success'] === true, $passed, $failed);

// Test Invalid Warranty Transition from CLOSED
$adjInvalid = $warrantyService->updateClaimStatus(
    $claimId,
    'SUBMITTED',
    $adminId,
    'ADMIN',
    'Invalid reset'
);
assertTest("Illegal transition backwards from CLOSED is strictly rejected", $adjInvalid['success'] === false, $passed, $failed);

echo "\n=======================================================\n";
echo " Test Summary: {$passed} Passed, {$failed} Failed\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
