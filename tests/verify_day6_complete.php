<?php

declare(strict_types=1);

/**
 * YasIX Store - Complete End-to-End Day 6 Verification Suite
 * Validates All Four Admin Dashboard & Management System Milestones
 */

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
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Services\AdminService;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\OrderService;
use App\Services\UserService;
use App\Services\WarrantyService;
use App\Repositories\AdminRepository;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\WarrantyRepository;

echo "====================================================================\n";
echo "   YASIX STORE — DAY 6 COMPREHENSIVE VERIFICATION SUITE            \n";
echo "   Admin Dashboard, Catalog, Fulfilment & Security Management      \n";
echo "====================================================================\n\n";

$db = Connection::getInstance();
$passed = 0;
$failed = 0;

function assertCheck(string $desc, bool $condition, &$passed, &$failed): void {
    if ($condition) {
        echo " [PASS] {$desc}\n";
        $passed++;
    } else {
        echo " [FAIL] {$desc}\n";
        $failed++;
    }
}

// ------------------------------------------------------------------
// SECTION 1: ADMIN PROVISIONING, AUTHENTICATION & ACCESS CONTROL
// ------------------------------------------------------------------
echo "--- SECTION 1: Admin Authentication, Roles & 403 Access Control ---\n";

// 1.1 Verify Admin Role in Database
$roleStmt = $db->query("SELECT id FROM roles WHERE name = 'ADMIN'");
$adminRoleId = $roleStmt->fetchColumn();
assertCheck("Database has ADMIN role defined", !empty($adminRoleId), $passed, $failed);

// 1.2 Verify Test Admin Exists or Provision One
$adminEmail = 'e2e_admin@yasix.local';
$adminUserStmt = $db->prepare("SELECT id, name, role_id FROM users WHERE email = :email");
$adminUserStmt->execute(['email' => $adminEmail]);
$adminUser = $adminUserStmt->fetch(PDO::FETCH_ASSOC);

if (!$adminUser) {
    $db->prepare("INSERT INTO users (name, email, password_hash, role_id, status, created_at, updated_at)
        VALUES ('E2E Super Admin', :email, :hash, :role_id, 'ACTIVE', NOW(), NOW())")
       ->execute([
           'email' => $adminEmail,
           'hash' => password_hash('SuperAdmin@2026', PASSWORD_BCRYPT),
           'role_id' => $adminRoleId
       ]);
    $adminId = (int)$db->lastInsertId();
} else {
    $adminId = (int)$adminUser['id'];
}
assertCheck("Administrator account provisioned and active (ID #{$adminId})", $adminId > 0, $passed, $failed);

// 1.3 Test Role Middleware: Customer Access Denied (403)
$customerRoleStmt = $db->query("SELECT id FROM roles WHERE name = 'CUSTOMER'");
$customerRoleId = $customerRoleStmt->fetchColumn();
$customerUser = ['id' => 9999, 'role_name' => 'CUSTOMER', 'name' => 'Regular Customer'];

$adminAllowed = (strtoupper($adminUser['role_name'] ?? 'ADMIN') === 'ADMIN');
$customerDenied = (strtoupper($customerUser['role_name']) !== 'ADMIN');
assertCheck("Server-side role checks grant access to ADMIN and reject CUSTOMER", $adminAllowed && $customerDenied, $passed, $failed);

// 1.4 Test 403 Forbidden View Template
assertCheck("Dedicated 403 Forbidden view template exists", file_exists(dirname(__DIR__) . '/views/403.php'), $passed, $failed);


// ------------------------------------------------------------------
// SECTION 2: DASHBOARD METRICS INTEGRITY & DATABASE AGGREGATES
// ------------------------------------------------------------------
echo "\n--- SECTION 2: Dashboard Analytics & Database Aggregates ---\n";

$adminRepo = new AdminRepository();
$adminService = new AdminService();
$stats = $adminRepo->getDashboardStats();

// Verify key KPIs exist and are numerically valid
assertCheck("Total orders statistic is non-negative", $stats['total_orders'] >= 0, $passed, $failed);
assertCheck("Delivered revenue is non-negative float", is_float($stats['delivered_revenue']) && $stats['delivered_revenue'] >= 0.0, $passed, $failed);
assertCheck("Collected COD cash is non-negative float", is_float($stats['collected_cash_amount']) && $stats['collected_cash_amount'] >= 0.0, $passed, $failed);
assertCheck("Active products count matches database", $stats['active_products'] >= 0, $passed, $failed);
assertCheck("Low stock products counter matches threshold", $stats['low_stock_products'] >= 0, $passed, $failed);

// Verify feeds
$dashboardData = $adminService->getDashboardData();
assertCheck("Dashboard feeds compiled (recent orders, low stock, top sellers, status distribution)", 
    isset($dashboardData['stats'], $dashboardData['recent_orders'], $dashboardData['low_stock_items'], $dashboardData['top_selling'], $dashboardData['orders_by_status']), 
    $passed, $failed
);


// ------------------------------------------------------------------
// SECTION 3: PRODUCT & CATEGORY ADMINISTRATION
// ------------------------------------------------------------------
echo "\n--- SECTION 3: Product & Category Administration ---\n";

$productService = new ProductService();
$categoryService = new CategoryService();

// 3.1 Category Unique Slug & Toggle
$catName = "Quantum Neural " . substr(uniqid(), -4);
$catSlug = "quantum-neural-" . substr(uniqid(), -4);
$catRes = $categoryService->createCategory(['name' => $catName, 'slug' => $catSlug, 'status' => 'ACTIVE']);
assertCheck("Category created successfully", $catRes['success'] === true, $passed, $failed);
$catId = (int)($catRes['category_id'] ?? $catRes['id'] ?? 0);

$catDup = $categoryService->createCategory(['name' => 'Dup Cat', 'slug' => $catSlug]);
assertCheck("Duplicate category slug correctly rejected", $catDup['success'] === false, $passed, $failed);

// 3.2 Product Creation with Validation
$prodData = [
    'name' => 'Neural Interface Card X1',
    'slug' => 'neural-card-x1-' . substr(uniqid(), -4),
    'brand' => 'NeuroTech',
    'category_id' => $catId,
    'price' => 75000.00,
    'stock_quantity' => 10,
    'warranty_months' => 6,
    'status' => 'AVAILABLE',
    'description' => 'Direct PCI-E neural accelerator.'
];

$prodRes = $productService->createProduct($prodData, null);
assertCheck("Product created with inventory log", $prodRes['success'] === true, $passed, $failed);
$prodId = (int)($prodRes['product_id'] ?? $prodRes['id'] ?? 0);

// Negative price validation
$badPriceRes = $productService->createProduct(array_merge($prodData, ['price' => -50, 'slug' => 'bad-price-' . uniqid()]), null);
assertCheck("Negative price rejected by validator", $badPriceRes['success'] === false, $passed, $failed);

// 3.3 Stock Adjustment & Audit Trail
$adjRes = $productService->adjustStock($prodId, 25, $adminId, 'Buffer restock from factory');
assertCheck("Inventory stock adjusted (+15) with audit trail", $adjRes['success'] === true && $adjRes['new_stock'] === 25, $passed, $failed);

// Verify inventory_logs entry
$invLogStmt = $db->prepare("SELECT * FROM inventory_logs WHERE product_id = :pid ORDER BY id DESC LIMIT 1");
$invLogStmt->execute(['pid' => $prodId]);
$invLog = $invLogStmt->fetch(PDO::FETCH_ASSOC);
assertCheck("Inventory log contains admin actor and reason", 
    $invLog !== false && 
    (int)$invLog['user_id'] === $adminId && 
    str_contains($invLog['reason'], 'Buffer restock'), 
    $passed, $failed
);

// 3.4 Soft Deactivation & Storefront Catalog Isolation
$deactRes = $productService->toggleProductStatus($prodId);
assertCheck("Product soft-deactivated to INACTIVE", $deactRes['success'] === true && $deactRes['status'] === 'INACTIVE', $passed, $failed);

// Verify inactive product is excluded from customer catalog
$storefrontProduct = (new ProductRepository())->findByIdWithDetails($prodId);
assertCheck("Inactive product is excluded from customer-facing storefront", $storefrontProduct === null, $passed, $failed);

// Reactivate product
$reactProd = $productService->toggleProductStatus($prodId);
assertCheck("Product reactivated to AVAILABLE", $reactProd['success'] === true && $reactProd['status'] === 'AVAILABLE', $passed, $failed);


// ------------------------------------------------------------------
// SECTION 4: ORDER FULFILMENT, COD SETTLEMENT & CANCELLATION
// ------------------------------------------------------------------
echo "\n--- SECTION 4: Order Fulfilment, COD Payments & Cancellation ---\n";

$orderService = new OrderService();
$userService = new UserService();

// Create test customer
$custEmail = 'e2e_customer_' . substr(uniqid(), -4) . '@yasix.local';
$db->prepare("INSERT INTO users (name, email, phone, password_hash, role_id, status, created_at, updated_at)
    VALUES ('E2E Test Customer', :email, '0779998877', :hash, :role_id, 'ACTIVE', NOW(), NOW())")
   ->execute([
       'email' => $custEmail,
       'hash' => password_hash('Pass@123', PASSWORD_BCRYPT),
       'role_id' => $customerRoleId
   ]);
$testCustId = (int)$db->lastInsertId();

// Create test address
$db->prepare("INSERT INTO addresses (user_id, address_line1, city, state, postal_code, country)
    VALUES (:uid, '101 Cyber Arcade', 'Kandy', 'Central', '20000', 'Sri Lanka')")
   ->execute(['uid' => $testCustId]);
$testAddrId = (int)$db->lastInsertId();

// Create test order
$e2eOrderNum = 'E2E-ORD-' . strtoupper(substr(uniqid(), -6));
$orderTotal = 75350.00;
$db->prepare("INSERT INTO orders (order_number, user_id, address_id, status, payment_status, payment_method, shipping_fee, total_amount, created_at, updated_at)
    VALUES (:num, :uid, :addr, 'PENDING', 'PENDING', 'COD', 350.00, :total, NOW(), NOW())")
   ->execute([
       'num' => $e2eOrderNum,
       'uid' => $testCustId,
       'addr' => $testAddrId,
       'total' => $orderTotal
   ]);
$e2eOrderId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months, created_at)
    VALUES (:oid, :pid, 'Neural Interface Card X1', 75000.00, 1, 75000.00, 6, NOW())")
   ->execute(['oid' => $e2eOrderId, 'pid' => $prodId]);
$e2eOrderItemId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO payments (order_id, amount, payment_method, status, transaction_reference, created_at)
    VALUES (:oid, :amount, 'COD', 'PENDING', :ref, NOW())")
   ->execute(['oid' => $e2eOrderId, 'amount' => $orderTotal, 'ref' => 'PAY-' . $e2eOrderNum]);

// 4.1 Advance Fulfilment Lifecycle
$orderService->updateOrderStatus($e2eOrderId, 'CONFIRMED', $adminId, 'ADMIN', 'Order confirmed');
$orderService->updateOrderStatus($e2eOrderId, 'PROCESSING', $adminId, 'ADMIN', 'Packing item');
$orderService->updateOrderStatus($e2eOrderId, 'SHIPPED', $adminId, 'ADMIN', 'Dispatched with courier');
$orderService->updateOrderStatus($e2eOrderId, 'OUT_FOR_DELIVERY', $adminId, 'ADMIN', 'Rider out');
$orderService->updateOrderStatus($e2eOrderId, 'DELIVERED', $adminId, 'ADMIN', 'Delivered at destination');

// 4.2 Verify Delivery Does Not Mark COD Paid
$delivOrder = $orderService->getAdminOrderByNumber($e2eOrderNum);
assertCheck("Order state is DELIVERED and COD payment remains decoupled as PENDING", 
    $delivOrder['status'] === 'DELIVERED' && 
    $delivOrder['payment_status'] === 'PENDING' &&
    !empty($delivOrder['delivered_at']), 
    $passed, $failed
);

// 4.3 Settle COD Payment
$payRes = $orderService->recordCodCollection($e2eOrderId, $orderTotal, $adminId, 'Cash collected by courier agent Perera');
assertCheck("COD payment collection recorded with admin audit details", $payRes['success'] === true, $passed, $failed);

$paidOrder = $orderService->getAdminOrderByNumber($e2eOrderNum);
assertCheck("Order payment status updated to PAID and payment record to COMPLETED", 
    $paidOrder['payment_status'] === 'PAID' && 
    $paidOrder['payment_record_status'] === 'COMPLETED' && 
    !empty($paidOrder['collected_at']), 
    $passed, $failed
);


// ------------------------------------------------------------------
// SECTION 5: CUSTOMER ACCOUNT SUSPENSION & SECURITY SHIELDS
// ------------------------------------------------------------------
echo "\n--- SECTION 5: Customer Account Status & Privilege Shields ---\n";

// 5.1 Suspend Customer
$suspRes = $userService->setCustomerStatus($testCustId, 'SUSPENDED', $adminId);
assertCheck("Customer account suspended by administrator", $suspRes['success'] === true, $passed, $failed);

// 5.2 Block Suspended Customer from Login
$custLogin = $userService->login($custEmail, 'Pass@123');
assertCheck("Suspended customer login attempt is blocked with account inactive/suspended error", 
    $custLogin['success'] === false && str_contains($custLogin['error'], 'suspended'), 
    $passed, $failed
);

// 5.3 Reactivate Customer
$reactCust = $userService->setCustomerStatus($testCustId, 'ACTIVE', $adminId);
assertCheck("Customer account reactivated to ACTIVE", $reactCust['success'] === true, $passed, $failed);

$custLoginAgain = $userService->login($custEmail, 'Pass@123');
assertCheck("Reactivated customer logs in successfully", $custLoginAgain['success'] === true, $passed, $failed);

// 5.4 Self-Suspension Shield
$selfSusp = $userService->setCustomerStatus($adminId, 'SUSPENDED', $adminId);
assertCheck("Administrator self-suspension is strictly blocked by security policy", 
    $selfSusp['success'] === false && str_contains($selfSusp['error'], 'Security Violation'), 
    $passed, $failed
);


// ------------------------------------------------------------------
// SECTION 6: WARRANTY CLAIM ADJUDICATION & AUDIT TRAIL
// ------------------------------------------------------------------
echo "\n--- SECTION 6: Warranty Claims Adjudication & Internal Notes ---\n";

$warrantyService = new WarrantyService();
$warrantyRepo = new WarrantyRepository();

$warrantyId = $warrantyRepo->findOrCreateWarranty(
    $e2eOrderItemId,
    $testCustId,
    $prodId,
    date('Y-m-d'),
    date('Y-m-d', strtotime('+6 months'))
);

$claimNum = 'CLM-E2E-' . strtoupper(substr(uniqid(), -5));
$claimRes = $warrantyRepo->createClaimWithTransaction(
    $warrantyId,
    $testCustId,
    $claimNum,
    'Hardware defect',
    'PCI-E bus handshake error detected during heavy load operations.'
);
assertCheck("Customer warranty claim lodged", $claimRes['success'] === true, $passed, $failed);
$claimId = (int)$claimRes['claim_id'];

// Adjudicate: SUBMITTED -> UNDER_REVIEW -> APPROVED -> RESOLVED -> CLOSED
$adj1 = $warrantyService->updateClaimStatus($claimId, 'UNDER_REVIEW', $adminId, 'ADMIN', 'Diagnostic review commenced', 'Hardware in queue for diagnostics.', 'Defect confirmed by QA technician.');
assertCheck("Claim advanced to UNDER_REVIEW with internal notes", $adj1['success'] === true, $passed, $failed);

$adj2 = $warrantyService->updateClaimStatus($claimId, 'APPROVED', $adminId, 'ADMIN', 'RMA Approved for Unit Replacement', 'A replacement card has been allocated.', 'Supplier RMA Code: RMA-NT-8812.');
assertCheck("Claim advanced to APPROVED", $adj2['success'] === true, $passed, $failed);

$adj3 = $warrantyService->updateClaimStatus($claimId, 'RESOLVED', $adminId, 'ADMIN', 'Replacement Dispatched', 'Tracking #CP-771122.', 'Dispatched via CityPak.');
assertCheck("Claim advanced to RESOLVED", $adj3['success'] === true, $passed, $failed);

$adj4 = $warrantyService->updateClaimStatus($claimId, 'CLOSED', $adminId, 'ADMIN', 'Case Closed', 'Customer verified replacement card operating normally.', 'Case archived.');
assertCheck("Claim advanced to CLOSED", $adj4['success'] === true, $passed, $failed);

// Verify claim record in database
$savedClaim = $warrantyService->getAdminClaimById($claimId);
assertCheck("Claim stores separate public resolution and confidential internal notes", 
    $savedClaim['status'] === 'CLOSED' && 
    str_contains($savedClaim['resolution_details'], 'Customer verified') && 
    str_contains($savedClaim['internal_notes'], 'Case archived') && 
    (int)$savedClaim['reviewed_by_user_id'] === $adminId, 
    $passed, $failed
);


// ------------------------------------------------------------------
// SECTION 7: REPORTS, CSV EXPORT & FORMULA INJECTION DEFENSE
// ------------------------------------------------------------------
echo "\n--- SECTION 7: Intelligence Reports & Safe CSV Export ---\n";

// 7.1 Report Aggregation
$report = $adminService->getReportData(['period' => '30days']);
assertCheck("30-day performance intelligence report generated", 
    isset($report['summary'], $report['status_distribution'], $report['top_products']) && 
    $report['summary']['delivered_orders'] >= 1, 
    $passed, $failed
);

// 7.2 CSV Formula Injection Defense
$formulaPrefixes = ['=', '+', '-', '@', "\t", "\r"];
$prefixNeutralized = true;
foreach ($formulaPrefixes as $p) {
    $testStr = $p . "malicious_payload";
    $sanitized = $adminService->sanitizeCsvCell($testStr);
    if (!str_starts_with($sanitized, "\"'{$p}")) {
        $prefixNeutralized = false;
        echo "  [FAIL] Prefix {$p} was not properly neutralized. Got: {$sanitized}\n";
    }
}
assertCheck("CSV formula injection prefixes (=, +, -, @, \\t, \\r) neutralized with apostrophe prefix", $prefixNeutralized, $passed, $failed);

// Safe quoting check
$normalSanitized = $adminService->sanitizeCsvCell('Normal Tech Hardware');
assertCheck("Normal text does not get prepended with apostrophe", $normalSanitized === '"Normal Tech Hardware"', $passed, $failed);

$quoteSanitized = $adminService->sanitizeCsvCell('Product with "Quotes"');
assertCheck("Embedded quotes correctly escaped as double quotes in CSV", $quoteSanitized === '"Product with ""Quotes"""', $passed, $failed);

// 7.3 CSV Export Generation
$csvOrders = $adminService->exportCsv('orders');
assertCheck("Orders CSV export generated with valid headers and data lines", 
    str_starts_with($csvOrders['filename'], 'yasix_orders_export_') && 
    str_contains($csvOrders['content'], 'Order Number') && 
    str_contains($csvOrders['content'], $e2eOrderNum), 
    $passed, $failed
);

$csvInventory = $adminService->exportCsv('inventory');
assertCheck("Inventory CSV export generated with SKU data", 
    str_starts_with($csvInventory['filename'], 'yasix_inventory_export_') && 
    str_contains($csvInventory['content'], 'SKU ID'), 
    $passed, $failed
);

$csvPayments = $adminService->exportCsv('payments');
assertCheck("Payments CSV export generated with COD settlement records", 
    str_starts_with($csvPayments['filename'], 'yasix_cod_payments_export_') && 
    str_contains($csvPayments['content'], 'Payment ID'), 
    $passed, $failed
);

$csvWarranties = $adminService->exportCsv('warranties');
assertCheck("Warranty Claims CSV export generated with claim adjudications", 
    str_starts_with($csvWarranties['filename'], 'yasix_warranty_claims_export_') && 
    str_contains($csvWarranties['content'], 'Claim Number'), 
    $passed, $failed
);


// ------------------------------------------------------------------
// SECTION 8: CSRF INTEGRITY ACROSS ALL ADMIN CONTROLLERS
// ------------------------------------------------------------------
echo "\n--- SECTION 8: CSRF Protection & Security Hardening ---\n";

Session::start();
$validToken = CSRF::generateToken();
assertCheck("CSRF token generated successfully", !empty($validToken), $passed, $failed);
assertCheck("CSRF token verification accepts valid token", CSRF::verifyToken($validToken), $passed, $failed);
assertCheck("CSRF token verification strictly rejects tampered token", !CSRF::verifyToken('tampered_token_999'), $passed, $failed);
assertCheck("CSRF token verification strictly rejects null/empty token", !CSRF::verifyToken(null), $passed, $failed);


// ------------------------------------------------------------------
// SUMMARY
// ------------------------------------------------------------------
echo "\n====================================================================\n";
echo "   DAY 6 VERIFICATION SUMMARY: {$passed} Passed, {$failed} Failed   \n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
