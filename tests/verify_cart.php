<?php

// Day 4 Shopping Cart Foundation Verification Suite
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
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

echo "==================================================\n";
echo "   YASIX STORE - DAY 4 CART FOUNDATION TEST       \n";
echo "==================================================\n";

$cartService = new CartService();
$cartRepo = new CartRepository();
$productRepo = new ProductRepository();

// Test user ID 2 (Cyber Customer)
$testUserId = 2;

// Clean cart prior to test
$cartService->clearCart($testUserId);

// 1. Verify Empty Cart
$cart = $cartService->getCart($testUserId);
if ($cart['is_empty'] && $cart['item_count'] === 0 && $cart['subtotal'] == 0.00) {
    echo "[PASS] Empty cart verified successfully.\n";
} else {
    echo "[FAIL] Initial cart was not empty.\n";
    exit(1);
}

// 2. Add an available product (Product 2: YasIX Synapse Neural Headset, $399.00)
$addResult = $cartService->addToCart($testUserId, 2, 1);
if ($addResult['success']) {
    echo "[PASS] Added Product 2 to cart.\n";
} else {
    echo "[FAIL] Failed to add Product 2: {$addResult['message']}\n";
    exit(1);
}

// 3. Add same product again -> quantity should increase
$addAgain = $cartService->addToCart($testUserId, 2, 2);
$cart = $cartService->getCart($testUserId);
if ($addAgain['success'] && $cart['item_count'] === 3 && $cart['subtotal'] == (399.00 * 3)) {
    echo "[PASS] Adding same product increased quantity to 3 ($1,197.00 subtotal verified).\n";
} else {
    echo "[FAIL] Adding same product did not update quantity properly.\n";
    exit(1);
}

// 4. Add second product (Product 4: AeroBlade Cyber Keyboard, $249.00)
$addSecond = $cartService->addToCart($testUserId, 4, 1);
$cart = $cartService->getCart($testUserId);
$expectedSubtotal = (399.00 * 3) + 249.00;
if ($addSecond['success'] && count($cart['items']) === 2 && $cart['subtotal'] == $expectedSubtotal) {
    echo "[PASS] Second product added, multiple items subtotal verified ($" . number_format($cart['subtotal'], 2) . ").\n";
} else {
    echo "[FAIL] Second product addition failed.\n";
    exit(1);
}

// 5. Update quantity
$updateResult = $cartService->updateQuantity($testUserId, 2, 1);
$cart = $cartService->getCart($testUserId);
if ($updateResult['success'] && $cart['items'][0]['quantity'] === 1) {
    echo "[PASS] Cart quantity updated to 1.\n";
} else {
    echo "[FAIL] Cart quantity update failed.\n";
    exit(1);
}

// 6. Test Out-of-Stock product addition rejection (Product 9 is out of stock)
$outOfStock = $cartService->addToCart($testUserId, 9, 1);
if (!$outOfStock['success']) {
    echo "[PASS] Out-of-stock product addition correctly rejected.\n";
} else {
    echo "[FAIL] Out-of-stock product was allowed in cart.\n";
    exit(1);
}

// 7. Test Exceeding stock limit
$exceedStock = $cartService->addToCart($testUserId, 2, 9999);
if (!$exceedStock['success']) {
    echo "[PASS] Adding quantity exceeding available stock correctly rejected.\n";
} else {
    echo "[FAIL] Exceeding stock quantity was allowed.\n";
    exit(1);
}

// 8. Remove item
$removeResult = $cartService->removeFromCart($testUserId, 4);
$cart = $cartService->getCart($testUserId);
if ($removeResult['success'] && count($cart['items']) === 1) {
    echo "[PASS] Removed product 4 from cart.\n";
} else {
    echo "[FAIL] Failed to remove product 4.\n";
    exit(1);
}

// 9. Clear cart
$clearResult = $cartService->clearCart($testUserId);
$cart = $cartService->getCart($testUserId);
if ($clearResult['success'] && $cart['is_empty']) {
    echo "[PASS] Cart cleared successfully.\n";
} else {
    echo "[FAIL] Clear cart failed.\n";
    exit(1);
}

echo "==================================================\n";
echo " RESULT: ALL COMMIT 1 CART TESTS PASSED 🚀         \n";
echo "==================================================\n";
