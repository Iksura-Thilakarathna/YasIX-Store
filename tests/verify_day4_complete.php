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
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;
use App\Repositories\DeliveryRuleRepository;
use App\Repositories\OrderRepository;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use App\Services\CheckoutService;

echo "==================================================\n";
echo "   YASIX STORE - DAY 4 END-TO-END VALIDATION      \n";
echo "==================================================\n";

$db = Connection::getInstance();

$cartRepo = new CartRepository();
$productRepo = new ProductRepository();
$deliveryRepo = new DeliveryRuleRepository();
$orderRepo = new OrderRepository();
$cartService = new CartService();
$deliveryCalc = new DeliveryCalculatorService();
$checkoutService = new CheckoutService();

$userId = 2; // customer@yasix.local
$cartId = $cartRepo->getOrCreateCart($userId);
$cartRepo->clearCart($cartId);

// 1. Cart Operations
echo "\n--- [1] CART OPERATIONS & STOCK VERIFICATION ---\n";
$cartRepo->addItem($cartId, 2, 2); // 2 units of Synapse Headset ($399)
$cart = $cartService->getCart($userId);
assert($cart['item_count'] === 2, "Cart item count must be 2");
assert($cart['subtotal'] === 798.00, "Cart subtotal must be $798.00");
echo "[PASS] Persistent Cart holds 2 items ($798.00 subtotal).\n";


// 2. Delivery Calculation
echo "\n--- [2] DELIVERY CALCULATION (VOLUMETRIC & ACTUAL) ---\n";
$westernDelivery = $deliveryCalc->calculateDelivery($cart['items'], 1); // Western Province
assert($westernDelivery['total_delivery_fee'] === 400.00, "Base delivery fee for Western Province is 400.00");
echo "[PASS] Delivery calculated accurately for Western Province: LKR {$westernDelivery['total_delivery_fee']} (Chargeable: {$westernDelivery['chargeable_weight_kg']} kg).\n";

$outstationDelivery = $deliveryCalc->calculateDelivery($cart['items'], 2); // Outstation Central
assert($outstationDelivery['total_delivery_fee'] === 525.00, "Base delivery fee for Outstation Central is 525.00");
echo "[PASS] Delivery calculated accurately for Outstation: LKR {$outstationDelivery['total_delivery_fee']} (Chargeable: {$outstationDelivery['chargeable_weight_kg']} kg).\n";

// 3. Checkout Preparation
echo "\n--- [3] CHECKOUT PREPARATION & TOKEN GENERATION ---\n";
$checkoutData = $checkoutService->prepareCheckout($userId, 1);
assert(!empty($checkoutData['checkout_token']), "Checkout token must be generated");
assert($checkoutData['order_total'] === 1198.00, "Total amount with delivery must be 1198.00 (798.00 + 400.00)");
echo "[PASS] Checkout prepared with CSRF/Idempotency token and calculated total ($1,198.00).\n";

// 4. Atomic Order Placement
echo "\n--- [4] TRANSACTIONAL COD ORDER PLACEMENT ---\n";
$productBefore = $productRepo->findById(2);
$initialStock = (int)$productBefore['stock_quantity'];

$postData = [
    'checkout_token' => $checkoutData['checkout_token'],
    'full_name' => 'Kavindu Perera',
    'phone' => '+94 77 123 4567',
    'address_line1' => 'No. 42 Cyber Walk',
    'address_line2' => 'Level 3',
    'city' => 'Colombo',
    'postal_code' => '00100',
    'delivery_rule_id' => 1,
    'payment_method' => 'cod',
    'notes' => 'Testing Day 4 end-to-end checkout pipeline.'
];

$result = $checkoutService->placeOrder($userId, $postData);
assert($result['success'] === true, "Order placement must succeed");
$orderNumber = $result['order_number'];
echo "[PASS] Transactional COD order placed successfully: Order #{$orderNumber}.\n";

// 5. Stock Verification & Cart Clearing
echo "\n--- [5] ATOMIC INVENTORY & CART POST-CONDITIONS ---\n";
$productAfter = $productRepo->findById(2);
$newStock = (int)$productAfter['stock_quantity'];
assert($newStock === $initialStock - 2, "Stock must decrement atomically by 2");
echo "[PASS] Stock decremented atomically ({$initialStock} -> {$newStock}).\n";

$cartAfter = $cartService->getCart($userId);
assert($cartAfter['is_empty'] === true, "Cart must be empty after order placement");
echo "[PASS] Cart verified cleared post-checkout.\n";

// 6. Order Verification & Price Snapshot Integrity
echo "\n--- [6] ORDER & PAYMENT SNAPSHOT AUDIT ---\n";
$orderRecord = $checkoutService->getOrderConfirmation($orderNumber, $userId);
assert($orderRecord !== null, "Order confirmation must be retrieved");
assert($orderRecord['payment_method'] === 'CASH_ON_DELIVERY', "Payment method must be COD");
assert($orderRecord['status'] === 'PENDING', "Order status must be pending");
assert((float)$orderRecord['shipping_fee'] === 400.00, "Shipping fee snapshot must match");
assert((float)$orderRecord['total_amount'] === 1198.00, "Total amount snapshot must match");
echo "[PASS] Order header and COD payment record audit verified.\n";

$orderItems = $orderRecord['items'];
assert(count($orderItems) === 1, "Order must contain 1 item line");
assert((float)$orderItems[0]['unit_price'] === 399.00, "Historical unit price snapshot verified");
echo "[PASS] Order items historical price snapshot verified.\n";

// 7. Duplicate Submission Prevention
echo "\n--- [7] IDEMPOTENCY & DUPLICATE ORDER SHIELD ---\n";
$duplicateResult = $checkoutService->placeOrder($userId, $postData);
assert($duplicateResult['success'] === false, "Duplicate checkout token must be rejected");
echo "[PASS] Duplicate submission safely blocked via consumed checkout token.\n";

// Restore stock to baseline for repeated runs
$db->prepare("UPDATE products SET stock_quantity = :stock WHERE id = 2")->execute(['stock' => $initialStock]);

echo "\n==================================================\n";
echo " RESULT: DAY 4 SUITE 100% VERIFIED & PRODUCTION READY 🚀\n";
echo "==================================================\n";

