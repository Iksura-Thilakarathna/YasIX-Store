<?php

declare(strict_types=1);

/**
 * YasIX Store - Verification Suite for Commit 2: Product & Category Administration
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
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;

// Initialize session
Session::start();
Session::set('user_id', 1); // Admin ID
Session::set('role_name', 'ADMIN');

echo "==================================================\n";
echo "   YASIX STORE - COMMIT 2 VALIDATION SUITE        \n";
echo "   Product and Category Administration            \n";
echo "==================================================\n\n";

$pdo = Connection::getInstance();
$productService = new ProductService();
$categoryService = new CategoryService();
$productRepo = new ProductRepository();
$categoryRepo = new CategoryRepository();

// --------------------------------------------------------------------------
// TEST 1: Category Creation, Uniqueness & Relationship Protection
// --------------------------------------------------------------------------
echo "--- [1] CATEGORY CRUD & RELATIONSHIP PROTECTION --- \n";

$catName = "Cyber Photonics Test " . bin2hex(random_bytes(3));
$catSlug = "cyber-photonics-" . bin2hex(random_bytes(3));

$catResult = $categoryService->createCategory([
    'name' => $catName,
    'slug' => $catSlug,
    'description' => 'Test photonics category for validation.',
    'status' => 'ACTIVE'
]);

assert($catResult['success'] === true, 'Category creation failed: ' . ($catResult['error'] ?? ''));
$newCatId = (int)$catResult['category_id'];
echo "[PASS] Category created (#{$newCatId}) with slug: {$catSlug}\n";

// Test duplicate slug prevention
$dupCatResult = $categoryService->createCategory([
    'name' => 'Another Category',
    'slug' => $catSlug,
    'status' => 'ACTIVE'
]);
assert($dupCatResult['success'] === false, 'Category duplicate slug was not rejected');
echo "[PASS] Duplicate category slug correctly rejected by server validation.\n";

// --------------------------------------------------------------------------
// TEST 2: Product Creation & Validation Rules
// --------------------------------------------------------------------------
echo "\n--- [2] PRODUCT CREATION & VALIDATION CONSTRAINTS --- \n";

// Test invalid negative price
$invalidPriceResult = $productService->createProduct([
    'name' => 'Invalid Price SKU',
    'category_id' => $newCatId,
    'price' => -500,
    'stock_quantity' => 10
]);
assert($invalidPriceResult['success'] === false, 'Negative price was not rejected');
echo "[PASS] Negative price correctly blocked by validator.\n";

// Test invalid negative stock
$invalidStockResult = $productService->createProduct([
    'name' => 'Invalid Stock SKU',
    'category_id' => $newCatId,
    'price' => 15000,
    'stock_quantity' => -5
]);
assert($invalidStockResult['success'] === false, 'Negative stock was not rejected');
echo "[PASS] Negative stock correctly blocked by validator.\n";

// Test valid product creation
$prodName = "Quantum Prism Optical Co-Processor " . bin2hex(random_bytes(2));
$prodSlug = "quantum-prism-optics-" . bin2hex(random_bytes(2));

$prodResult = $productService->createProduct([
    'name' => $prodName,
    'slug' => $prodSlug,
    'brand' => 'YasIX Quantum',
    'category_id' => $newCatId,
    'description' => 'Military grade photonics optical co-processor unit.',
    'price' => 185000.00,
    'stock_quantity' => 12,
    'weight' => 2.45,
    'package_length' => 28.0,
    'package_width' => 18.0,
    'package_height' => 12.0,
    'warranty_months' => 6,
    'condition' => 'NEW',
    'status' => 'AVAILABLE',
    'featured' => 1,
    'image_path' => '/assets/images/products/quantum-chip.png'
]);

assert($prodResult['success'] === true, 'Product creation failed: ' . ($prodResult['error'] ?? ''));
$newProdId = (int)$prodResult['product_id'];
echo "[PASS] Product SKU #{$newProdId} created successfully.\n";

// Test duplicate product slug rejection
$dupProdResult = $productService->createProduct([
    'name' => 'Duplicate Slug SKU',
    'slug' => $prodSlug,
    'category_id' => $newCatId,
    'price' => 50000,
    'stock_quantity' => 5
]);
assert($dupProdResult['success'] === false, 'Duplicate product slug was not rejected');
echo "[PASS] Duplicate product slug correctly rejected.\n";

// Test category deletion safety: category has product, so deletion must fail
$catDeleteResult = $categoryService->deleteCategory($newCatId);
assert($catDeleteResult['success'] === false, 'Category with associated products was deleted without error');
echo "[PASS] Category deletion safely blocked when active products are associated.\n";

// --------------------------------------------------------------------------
// TEST 3: Product Update & Historical Order Integrity Check
// --------------------------------------------------------------------------
echo "\n--- [3] PRODUCT UPDATE & HISTORICAL SNAPSHOT IMMUTABILITY --- \n";

// Retrieve an existing historical order item from DB to test immutability
$histItemStmt = $pdo->query("SELECT id, order_id, product_id, unit_price, warranty_months FROM order_items LIMIT 1");
$histItem = $histItemStmt->fetch(PDO::FETCH_ASSOC);

if ($histItem) {
    $histProdId = (int)$histItem['product_id'];
    $histSavedPrice = (float)$histItem['unit_price'];
    $histSavedWarranty = (int)$histItem['warranty_months'];

    // Snapshot original product attributes to restore after immutability check
    $origProdStmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $origProdStmt->execute(['id' => $histProdId]);
    $origProd = $origProdStmt->fetch(PDO::FETCH_ASSOC);

    // Now update this product's current catalog price and warranty
    $updateResult = $productService->updateProduct($histProdId, [
        'name' => 'Updated Historical Name ' . bin2hex(random_bytes(2)),
        'category_id' => 1,
        'price' => $histSavedPrice + 99999.00,
        'stock_quantity' => 25,
        'warranty_months' => 36,
        'condition' => 'NEW',
        'status' => 'AVAILABLE'
    ]);
    assert($updateResult['success'] === true, 'Product update failed');

    // Re-verify that historical order item unit price and warranty did NOT change
    $checkHistStmt = $pdo->prepare("SELECT unit_price, warranty_months FROM order_items WHERE id = :id");
    $checkHistStmt->execute(['id' => $histItem['id']]);
    $verifiedHist = $checkHistStmt->fetch(PDO::FETCH_ASSOC);

    assert(abs((float)$verifiedHist['unit_price'] - $histSavedPrice) < 0.01, "Historical order item price was mutated! Expected {$histSavedPrice}, got {$verifiedHist['unit_price']}");
    assert((int)$verifiedHist['warranty_months'] === $histSavedWarranty, "Historical warranty snapshot was mutated!");

    echo "[PASS] Historical order item unit price (LKR " . number_format($histSavedPrice, 2) . ") and warranty snapshot intact after product catalog update.\n";

    // Restore product catalog record to prevent side-effects on subsequent test suites
    if ($origProd) {
        $productService->updateProduct($histProdId, [
            'name' => $origProd['name'],
            'category_id' => (int)$origProd['category_id'],
            'price' => (float)$origProd['price'],
            'stock_quantity' => (int)$origProd['stock_quantity'],
            'warranty_months' => (int)$origProd['warranty_months'],
            'condition' => $origProd['condition'],
            'status' => $origProd['status']
        ]);
    }
}

// --------------------------------------------------------------------------
// TEST 4: Product Deactivation & Public Storefront Hiding
// --------------------------------------------------------------------------
echo "\n--- [4] SOFT DEACTIVATION & STOREFRONT CATALOG ISOLATION --- \n";

// Deactivate our newly created product
$deactResult = $productService->toggleProductStatus($newProdId);
assert($deactResult['success'] === true, 'Failed to toggle product status');
assert($deactResult['status'] === 'INACTIVE', "Expected status INACTIVE, got {$deactResult['status']}");

// Verify it cannot be retrieved by public storefront findBySlugWithDetails
$storefrontFind = $productRepo->findBySlugWithDetails($prodSlug);
assert($storefrontFind === null, 'Deactivated (INACTIVE) product was returned by storefront findBySlugWithDetails');

// Verify it does not appear in public storefront filtered products
$storefrontCatalog = $productRepo->getFilteredProducts(['search' => $prodName]);
$foundInStorefront = false;
foreach ($storefrontCatalog as $sc) {
    if ((int)$sc['id'] === $newProdId) {
        $foundInStorefront = true;
        break;
    }
}
assert($foundInStorefront === false, 'INACTIVE product appeared in public filtered catalog');
echo "[PASS] Soft-deactivated (INACTIVE) product safely hidden from public customer catalog.\n";

// Reactivate product
$reactResult = $productService->toggleProductStatus($newProdId);
assert($reactResult['success'] === true && $reactResult['status'] === 'AVAILABLE', 'Reactivation failed');
echo "[PASS] Product successfully reactivated to AVAILABLE.\n";

// --------------------------------------------------------------------------
// TEST 5: Inventory Stock Adjustments & Audit Logging
// --------------------------------------------------------------------------
echo "\n--- [5] INVENTORY STOCK ADJUSTMENT & AUDIT TRAIL --- \n";

$auditReason = "Physical warehouse count sync cycle 2026.10";
$adjustResult = $productService->adjustStock($newProdId, 45, 1, $auditReason);

assert($adjustResult['success'] === true, 'Stock adjustment failed: ' . ($adjustResult['error'] ?? ''));
assert($adjustResult['new_stock'] === 45, 'New stock quantity mismatch');
assert($adjustResult['change'] === (45 - 12), 'Stock change calculation mismatch');

// Verify audit log record created
$logs = $productService->getInventoryLogs($newProdId, 5);
assert(!empty($logs), 'No inventory log found for adjusted product');
$latestLog = $logs[0];
assert((int)$latestLog['product_id'] === $newProdId, 'Log product_id mismatch');
assert((int)$latestLog['new_stock'] === 45, 'Log new_stock mismatch');
assert($latestLog['reason'] === $auditReason, 'Log reason mismatch');

echo "[PASS] Stock adjusted from 12 -> 45 units (+33 units) with audit reason and timestamp logged.\n";

// Test stock adjustment to 0 changes status to OUT_OF_STOCK
$zeroResult = $productService->adjustStock($newProdId, 0, 1, "Temporary zero-out test");
assert($zeroResult['success'] === true, 'Zero stock adjustment failed');
$zeroProduct = $productRepo->findByIdWithDetails($newProdId);
assert($zeroProduct['status'] === 'OUT_OF_STOCK', "Expected OUT_OF_STOCK, got {$zeroProduct['status']}");
echo "[PASS] Depleting stock to 0 automatically updates status to OUT_OF_STOCK.\n";

// --------------------------------------------------------------------------
// TEST 6: View Files Verification
// --------------------------------------------------------------------------
echo "\n--- [6] VIEW TEMPLATES EXISTENCE & SANITY --- \n";

$catalogViews = [
    'd:/Web/views/admin/products/index.php',
    'd:/Web/views/admin/products/create.php',
    'd:/Web/views/admin/products/edit.php',
    'd:/Web/views/admin/categories/index.php',
    'd:/Web/views/admin/inventory/index.php'
];

foreach ($catalogViews as $cv) {
    assert(file_exists($cv), "Missing view template: {$cv}");
}
echo "[PASS] All 5 product, category, and inventory view templates exist.\n\n";

echo "==================================================\n";
echo " RESULT: COMMIT 2 VALIDATION 100% SUCCESSFUL 🎯  \n";
echo "==================================================\n";
